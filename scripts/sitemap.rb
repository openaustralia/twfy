#!/usr/bin/env ruby
# Generate sitemap.xml for quick and easy search engine updating

require 'rubygems'
require 'active_record'
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
	
	# Returns the unique url for this bit of the Hansard
	# Again, this should not really be in the model
	def url
		if gid =~ /^uk.org.publicwhip\/(lords|debate)\/(.*)$/
			"/" + ($~[1] == "debate" ? "debate" : "senate") + "/?id=" + $~[2]
		else
			throw "Unexpected form of gid #{gid}"
		end
	end
end

# All the member urls (Representatives and Senators)
urls = Member.find_all_person_ids.map {|person_id| Member.find_most_recent_by_person_id(person_id).url}
# All the Hansard urls (for both House of Representatives and the Senate)
urls = urls + Hansard.find(:all).map {|h| h.url}

# Add some static URLs
#urls = ""

prefix = "http://" + MySociety::Config.get('DOMAIN')
urls.each do |url|
	puts prefix + url
end

puts "There were #{urls.size} urls in the sitemap"


