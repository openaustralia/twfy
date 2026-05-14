#!/usr/bin/env ruby
require 'net/http'
require 'uri'

EXCLUDED = %w[example.com]
MAX_REDIRECTS = 10

def follow(url, limit = MAX_REDIRECTS)
  STDOUT.flush
  return [:error, "too many redirects"] if limit == 0

  begin
    uri = URI.parse(url)
  rescue URI::InvalidURIError
    return [:error, "bad URI: #{url}"]
  end
  return [:error, "not HTTP/HTTPS"] unless uri.is_a?(URI::HTTP)

  begin
    res = Net::HTTP.get_response(uri)
  rescue => e
    return [:error, e.message]
  end

  code = res.code.to_i
  case code
  when 200..299
    [:ok, code]
  when 301, 308
    dest = res['location']
    inner = follow(dest, limit - 1)
    final = case inner[0]
            when :ok        then dest
            when :temp      then inner[1]
            when :permanent then inner[1]
            else return [inner[0], "redirect to broken link: #{dest}"]
            end
    [:permanent, final, code]
  when 302, 303, 307
    [:temp, res['location'], code]
  when 400..599
    [code, "HTTP #{code}"]
  else
    [code, "unexpected HTTP #{code}"]
  end
end

def format_files(files)
  uniq = files.uniq
  if uniq.size > 5
    "#{uniq.first(5).join(', ')}, and #{uniq.size - 5} more"
  else
    uniq.join(', ')
  end
end

url_files = Hash.new { |h, k| h[k] = [] }

Dir.glob('**/*.php').each do |file|
  next if file.start_with? "vendor/"

  puts "Parsing #{file}..."
  File.read(file).scan(%r{https?://[^\s"'<>\\\{\}\(\)]+}).each do |url|
    url_files[url] << file unless EXCLUDED.any? { |ex| url.include?(ex) }
  end
end

puts "Checking urls ..."

counts              = Hash.new(0)
permanent_redirects = []
temp_redirects      = []
broken              = []

url_files.each do |url, files|
  result = follow(url)
  source = format_files(files)

  status = result[0]
  counts[status] += 1
  case status
  when :ok
    # ignore
  when :permanent
    puts "sed -i 's|#{url}|#{result[1]}|g' #{files.join(' ')}"
  when :temp
    puts "# Ignore #{result[2]} redirect #{url} to #{result[1]} in files: #{source}"
  else
    puts "# BROKEN #{url} (#{status}) in files: #{source}"
  end
end

puts "Results...", ""

puts
fmt = "%-12s %6s"
puts fmt % ["Status", "Count"]
puts fmt % ["-" * 12, "-" * 6]
total = 0
counts.sort_by { |code, _| code.to_s }.each do |code, count|
  puts fmt % [code, count]
  total += count
end
puts fmt % ["", "=" * 6]
puts fmt % ["Total", total]
