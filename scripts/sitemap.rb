#!/usr/bin/env ruby
# Generate sitemap.xml for quick and easy search engine updating

require 'rubygems'
require 'active_record'
require 'enumerator'
require "../../rblib/config"

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

class Hansard < ActiveRecord::Base
	set_table_name "hansard"
	
	# Return all dates for which there are speeches on that day in the given house
	def Hansard.find_all_dates_for_house(house)
		if house == "reps"
			major = 1
		elsif house == "senate"
			major = 101
		else
			throw "Unexpected value for house: #{house}"
		end
		find(:all, :conditions => ['major = ?', major], :group => 'hdate').map {|h| h.hdate}
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
	
	def News.find_all
		news = []
		IO.popen('php', "w+") do |child|
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

urls = []

# URLs for daily highlights of speeches in Reps and Senate
["reps", "senate"].each do |house|
	urls = urls + Hansard.find_all_dates_for_house(house).map{|hdate| Hansard.url_for_date(hdate, house)}
end

# All the member urls (Representatives and Senators)
urls = urls + Member.find_all_person_ids.map {|person_id| Member.find_most_recent_by_person_id(person_id).url}
# All the Hansard urls (for both House of Representatives and the Senate)
urls = urls + Hansard.find(:all).map {|h| h.url}

# Include the news items
urls = urls + News.find_all.map {|n| n.url}

# Not going to include the glossary until we actually start to use it
# urls << "/glossary/"

# Add some static URLs
urls << "/"
urls << "/about/"
urls << "/alert/"
# TODO: Comments appear on Hansard pages. So the last modified date should take account of the comments
urls << "/comments/recent/"
urls << "/contact/"
urls << "/debates/"
urls << "/hansard/"
urls << "/help/"
urls << "/houserules/"
# The find out about your representative page
urls << "/mp/"
urls << "/mps/"
# TODO: Also include all the news items (This isn't stored in the database)
urls << "/news/"
urls << "/privacy/"
# Help with Searching
urls << "/search/"
urls << "/senate/"
urls << "/senators/"

# No point in including yearly overview of days in which speeches occur because there's nothing on
# the page to search on

prefix = "http://" + MySociety::Config.get('DOMAIN')
urls.each do |url|
	puts prefix + url
end

puts "There were #{urls.size} urls in the sitemap"


