#!/usr/bin/env ruby
# Generate sitemap.xml for quick and easy search engine updating

require 'rubygems'
require 'active_record'
require 'enumerator'
require "../../rblib/config"
require 'builder'
require 'zlib'

# Load database information from the mysociety configuration
MySociety::Config.set_file("../conf/general")

# Establish the connection to the database
ActiveRecord::Base.establish_connection(
	:adapter  => "mysql",
	:host     => MySociety::Config.get('DB_HOST'),
	:username => MySociety::Config.get('DB_USER'),
	:password => MySociety::Config.get('DB_PASSWORD'),
	:database => MySociety::Config.get('DB_NAME')
)

class Member < ActiveRecord::Base
	set_table_name "member"
	
	def full_name
		"#{first_name} #{last_name}"
	end
	
	def Member.find_all_person_ids
		Member.find(:all, :group => "person_id").map{|m| m.person_id}
	end
	
	# Find the most recent member for the given person_id
	def Member.find_most_recent_by_person_id(person_id)
		Member.find_all_by_person_id(person_id, :order => "entered_house DESC", :limit => 1).first
	end
	
	# Returns the unique url for this member.
	# Obviously this doesn't really belong in the model but, you know, for the time being...
	# URLs without the initial http://www.openaustralia.org bit
	def url
		if house == 1
			house_url = "mp"
		elsif house == 2
			house_url = "senator"
		else
			throw "Unexpected value for house"
		end
		# The url is made up of the full_name, constituency and house
		# TODO: Need to correctly encode the urls
		"/" + house_url + "/" + full_name.downcase.tr(' ', '_') + '/' + constituency.downcase
	end
end

class Comment < ActiveRecord::Base
	# The most recently added comment
	def Comment.most_recent
		Comment.find(:all, :order => "posted DESC", :limit => 1).first
	end
	
	def last_modified
		posted
	end
end

class Hansard < ActiveRecord::Base
	set_table_name "hansard"
	set_primary_key "epobject_id"
	
	has_many :comments, :foreign_key => "epobject_id"
	
	# Return all dates for which there are speeches on that day in the given house
	def Hansard.find_all_dates_for_house(house)
		find_all_by_major(house_to_major(house), :group => 'hdate').map {|h| h.hdate}
	end
	
	def Hansard.house_to_major(house)
		if house == "reps"
			1
		elsif house == "senate"
			101
		else
			throw "Unexpected value for house: #{house}"
		end
	end
	
	def Hansard.most_recent_in_house(house)
		find_all_by_major(house_to_major(house), :order => "hdate DESC, htime DESC", :limit => 1).first
	end
	
	def Hansard.most_recent
		find(:all, :order => "hdate DESC, htime DESC", :limit => 1).first
	end
	
	def Hansard.find_all_sections_by_date_and_house(date, house)
		find_all_by_major_and_hdate_and_htype(house_to_major(house), date, 10)
	end
	
	def house
		if major == 1
			"reps"
		elsif major == 101
			"senate"
		else
			throw "Unexpected value of major: #{major}"
		end
	end
	
	def section?
		htype == 10
	end
	
	def subsection?
		htype == 11
	end
	
	def speech?
		htype == 12
	end
	
	# Takes the modification times of any comments on a speech into account
	def last_modified_including_comments
		if speech?
			(comments.map{|c| c.last_modified} << last_modified).max
		else
			speeches.map{|s| s.last_modified_including_comments}.max
		end
	end
	
	# The last time this was modified. Takes into account all subsections and speeches under this
	# if this is a section or subsection.
	def last_modified
		if speech?
			modified
		else
			speeches.map{|s| s.last_modified}.max
		end
	end
	
	# Returns all the hansard objects which are contained by this Hansard object
	# For example, if this is a section, it returns all the subsections
	def speeches
		if section?
			Hansard.find_all_by_section_id_and_htype(epobject_id, 11)
		elsif subsection?
			Hansard.find_all_by_subsection_id(epobject_id)
		elsif speech?
			return []
		else
			throw "Unknown hansard type"
		end
	end
	
	def numeric_id
		if gid =~ /^uk.org.publicwhip\/(lords|debate)\/(.*)$/
			$~[2]
		else
			throw "Unexpected form of gid #{gid}"
		end
	end
	
	# TODO: There seems to be an assymetry between the reps and senate in their handling of the two different kinds of url below
	# Must investigate this
	
	# Returns the unique url for this bit of the Hansard
	# Again, this should not really be in the model
	def url
		"/" + (house == "reps" ? "debate" : "senate") + "/?id=" + numeric_id
	end
	
	def Hansard.url_for_date(hdate, house)
		"/" + (house == "reps" ? "debates" : "senate") + "/?d=" + hdate.to_s		
	end
end

# A news item
class News
	def initialize(title, date)
		@title, @date = title, date
	end
	
	def last_modified
		Time.parse(@date)
	end
	
	# The most recently added news item
	def News.most_recent
		find_all.max {|a,b| a.last_modified <=> b.last_modified}
	end
	
	def News.find_all
		news = []
		MySociety::Config.fork_php do |child|
		    child.print('<?php require "../www/docs/news/editme.php"; foreach ($all_news as $k => $v) { print $v[0]."\n"; print $v[2]."\n"; } ?>')
		    child.close_write()
		    child.readlines().map{|l| l.strip}.each_slice(2) do |title,date|
				news << News.new(title, date)
			end
		end
		news
	end
	
	def url
		"/news/archives/#{url_encoded_date}/#{url_encoded_title}"
	end
	
	def url_encoded_title
		@title.downcase.gsub(/[^a-z0-9 ]/, '').tr(' ', '_')[0..15]
	end
	
	def url_encoded_date
		@date[0..9].tr('-', '/')
	end
end

class SitemapUrl
	attr_reader :loc, :changefreq, :lastmod
	
	CHANGEFREQ_VALUES = ["always", "hourly", "daily", "weekly", "monthly", "yearly", "never"]
	
	def initialize(loc, options)
		@loc = loc
		@changefreq = options.delete(:changefreq)
		@changefreq = @changefreq.to_s if @changefreq
		@lastmod = options.delete(:lastmod)
		throw "Invalid value #{@changefreq} for changefreq" unless @changefreq.nil? || CHANGEFREQ_VALUES.include?(@changefreq)
		throw "Invalid options in add_url" unless options.empty?
	end
end

class Sitemap
	# These are limits that are imposed on a single sitemap file by the specification
	MAX_URLS_PER_FILE = 50000
	# This is the uncompressed size of a single sitemap file
	MAX_BYTES_PER_FILE = 10485760
	
	SITEMAP_XMLNS = "http://www.sitemaps.org/schemas/sitemap/0.9"
	
	def initialize(domain, path, web_path)
		@domain, @path, @web_path = domain, path, web_path
		@urls = []
	end
	
	def add_url(loc, options = {})
		@urls << SitemapUrl.new(loc, options)
	end
	
	def Sitemap.w3c_date(date)
		date.utc.strftime("%Y-%m-%dT%H:%M:%S+00:00")
	end 
		
	# Returns the sitemap xml as a string for the given urls
	def xml(urls)
		# Should really not use any indentation to make the resulting file as small as possible
		x = Builder::XmlMarkup.new(:indent => 2)
		x.instruct! :xml, :version => "1.0", :encoding => "UTF-8"
		x.urlset(:xmlns => SITEMAP_XMLNS) do
			urls.each do |url|
				x.url do
					x.loc("http://" + @domain + url.loc)
					x.changefreq(url.changefreq) if url.changefreq
					x.lastmod(Sitemap.w3c_date(url.lastmod)) if url.lastmod
				end
			end
		end		
	end
	
	# Returns the sitemap index xml as a string
	def index_xml(no_sitemap_files)
	    x = Builder::XmlMarkup.new(:indent => 1)
		x.instruct! :xml, :version => "1.0", :encoding => "UTF-8"
		x.sitemapindex(:xmlns => SITEMAP_XMLNS) do
			(1..no_sitemap_files).each do |i|
				x.sitemap do
					x.loc(sitemap_url(i))
					x.lastmod(Sitemap.w3c_date(Time.now))
				end
			end
		end
	end
	
	# Path on the filesystem to the sitemap index file
	# This needs to be at the root of the web path to include all the urls below it
	def sitemap_index_path
		"#{@path}sitemap.xml"
	end
	
	def sitemap_url(index)
		"http://#{@domain}#{@web_path}sitemaps/sitemap#{index}.xml.gz"
	end
	
	def sitemap_path(index)
		"#{@path}sitemaps/sitemap#{index}.xml.gz"
	end
	
	def output
		# Make things simple by assuming that we will always have a sitemap index file
		no_sitemap_files = (@urls.size.to_f / MAX_URLS_PER_FILE).ceil
		no_urls_per_sitemap_file = (@urls.size.to_f / no_sitemap_files).ceil

		puts "Writing sitemap index (#{sitemap_index_path})..."
		File.open(sitemap_index_path, 'w') { |f| f << index_xml(no_sitemap_files) }
		index = 1
		@urls.each_slice(no_urls_per_sitemap_file) do |urls|
			sitemap = xml(urls)
			sitemap_file_size = sitemap.size
			puts "Writing sitemap file (#{sitemap_path(index)})..."
			Zlib::GzipWriter.open(sitemap_path(index)) {|f| f << sitemap}
			throw "Sitemap file #{sitemap_path(index)} is too big" if sitemap_file_size > MAX_BYTES_PER_FILE
			index = index + 1
		end
	end
end

s = Sitemap.new(MySociety::Config.get('DOMAIN'), MySociety::Config.get('BASEDIR'), MySociety::Config.get('WEBPATH'))

# Arrange some static URL's with the most quickly changing at the top

# Add some static URLs with dynamic content
s.add_url "/", :changefreq => :hourly,
	:lastmod => [Comment.most_recent.last_modified, Hansard.most_recent.last_modified, News.most_recent.last_modified].max
s.add_url "/comments/recent/", :changefreq => :hourly, :lastmod => Comment.most_recent.last_modified
s.add_url "/debates/", :changefreq => :daily, :lastmod => Hansard.most_recent_in_house("reps").last_modified
s.add_url "/hansard/", :changefreq => :daily, :lastmod => Hansard.most_recent.last_modified
s.add_url "/senate/", :changefreq => :daily, :lastmod => Hansard.most_recent_in_house("senate").last_modified
s.add_url "/news/", :changefreq => :weekly, :lastmod => News.most_recent.last_modified
s.add_url "/mps/", :changefreq => :monthly
s.add_url "/senators/", :changefreq => :monthly

# Add some static URLs with no dynamic content
s.add_url "/about/", :changefreq => :monthly
s.add_url "/contact/", :changefreq => :monthly
s.add_url "/help/", :changefreq => :monthly
s.add_url "/houserules/", :changefreq => :monthly
# The find out about your representative page
s.add_url "/mp/", :changefreq => :monthly
s.add_url "/privacy/", :changefreq => :monthly
# Help with Searching
s.add_url "/search/", :changefreq => :monthly
s.add_url "/alert/", :changefreq => :monthly

# Not going to include the glossary until we actually start to use it
# urls << "/glossary/"
# No point in including yearly overview of days in which speeches occur because there's nothing on
# the page to search on

# All the Hansard urls (for both House of Representatives and the Senate)
Hansard.find(:all).each do |h|
	# Saying the Hansard could change monthly because of reparsing
	s.add_url h.url, :changefreq => :monthly, :lastmod => h.last_modified_including_comments
end

# URLs for daily highlights of speeches in Reps and Senate
["reps", "senate"].each do |house|
	Hansard.find_all_dates_for_house(house).each do |hdate|
		s.add_url Hansard.url_for_date(hdate, house),
			:changefreq => :monthly,
			:lastmod => Hansard.find_all_sections_by_date_and_house(hdate, house).map{|h| h.last_modified}.max
	end
end

# All the member urls (Representatives and Senators)
Member.find_all_person_ids.each do |person_id|
	# Could change daily because of recent speeches they make
	s.add_url Member.find_most_recent_by_person_id(person_id).url, :changefreq => :daily
end

# Include the news items
News.find_all.each do |n|
	s.add_url n.url, :changefreq => :monthly, :lastmod => n.last_modified
end

s.output
