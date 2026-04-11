# They Work For You (aussie rules)

This is a fork of a 2001-ish era PHP app from MySociety in the UK, repurposed for Australia. This is the software running on https://openaustralia.org.au

## What is OpenAustralia.org.au ?

OpenAustralia.org.au is a website run by the non-partisan charity, OpenAustralia Foundation, which makes Australian government and parliamentary information easily accessible to the public through tools such as searching Hansard (parliamentary debates) and tracking politicians' voting records. The site aims to increase transparency and civic engagement in Australian democracy. It provides platforms to easily follow what MPs and Senators say and do, and tracks their registers of interests.

 ## What is this data?

Everything elected politicians say in Australia's Senate and Parliament is recorded in Australia's Official Hansard. This documentation is obtained using scapers (see https://github.com/openaustralia/openaustralia-parser/ )
and displayed on openaustralia.org.au. 

## data feeds

Data is also provided for public use at http://data.openaustralia.org.au/

TheyVoteForYou.org.au is one of the users of this data. 

## Development


### Installing php

Use `mise install` to install php. 

You may need to:
```bash
sudo apt update
sudo apt install plocate
# If you have many millions of files, the indexer may take a while.
# You can either wait or kill the indexer holding up apt install (which will complete the install),
# add exclusions to /etc/updatedb.conf and rerun.
sudo apt install re2c bison autoconf build-essential libxml2-dev libssl-dev libcurl4-openssl-dev libpng-dev libjpeg-dev libonig-dev libzip-dev
sudo apt-get install libgd-dev
```

### Installing composer managed dependencies

```bash
composer install
```

### Running the checks git does

```bash
make lint-ci | grep -v "No syntax errors detected in" # ignore all the "its ok" messages
make phpcs-ci 
composer validate
```

### Updating formatting

Use `phpcbf` to fix formatting that GitHub Actions complains about, eg:

```bash
./vendor/bin/phpcbf www/includes/easyparliament/alert.php www/includes/easyparliament/user.php
```
