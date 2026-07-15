#!/usr/bin/perl

# Generates per-department Oral Answers RSS feeds from recent Hansard data.

use warnings;
use strict;
use FindBin;
use lib "$FindBin::Bin/../../perllib";
use XML::RSS;
use DBI;
use mySociety::Config;
mySociety::Config::set_file('../conf/general');

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('DB_NAME'). ':host=' . mySociety::Config::get('DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('DB_USER'), mySociety::Config::get('DB_PASSWORD'), { RaiseError => 1, PrintError => 0 });

my $Output_Dir= shift || die "usage: $0 output_dir/\n";

my $query= $dbh->prepare("
	SELECT hansard.*, epobject.body FROM hansard, epobject WHERE hdate >= DATE_SUB(NOW(), INTERVAL 30 day) AND major=1 AND htype= 10 AND hansard.epobject_id = epobject.epobject_id AND epobject.body LIKE 'Oral Answers%'
	");
$query->execute();



my %oral;
while (my $result = $query->fetchrow_hashref) {

	my $subquery= $dbh->prepare(" SELECT epobject.epobject_id, epobject.body, hansard.gid
				        FROM epobject, hansard
                        WHERE hansard.section_id=?
                            AND hansard.epobject_id= epobject.epobject_id
                            AND htype=11");
	$subquery->execute($result->{epobject_id});
	while (my $r= $subquery->fetchrow_hashref) {
        my ($id)= $r->{gid} =~ m#\/([^/]+)$#;
        push @{$oral{$result->{body}}}, [$result->{epobject_id}, $result->{hdate}, $id , $r->{body}] ;
	}
}


foreach my $area (keys %oral) {
my $rss = new XML::RSS (version => '1.0');
$rss->channel(
	title => "$area",
	link => "http://www.openaustralia.org/debates/",
	description => "$area via OpenAustralia.org - http://www.openaustralia.org/ .",
	dc => {
		subject => '',
		creator => 'OpenAustralia.org',
		publisher => 'OpenAustralia.org',
		rights => 'Parliamentary Copyright',
		language => 'en-gb',
		ttl => 600
	},
	syn => {
		updatePeriod => 'daily',
		updateFrequency => '1',
		updateBase => '1901-01-01T00:00+00:00',
	},
);

	my ($dept_name)= $area =~ m#; (.*)#;

	foreach my $topic (@{$oral{$area}}) {

		$rss->add_item(
			title=>"$dept_name &mdash; $topic->[3]",
			link=>'http://www.openaustralia.org/debates/?id=' . $topic->[2],
            description=>"$area - $topic->[3]"
        );
	}
	my $filename= $dept_name;
	$filename =~ s/[^a-z0-9]//gi;

	open (OUT, ">$Output_Dir/$filename.rss") || die "can't open $Output_Dir/$filename.rss:$!";
	print OUT $rss->as_string;
	close (OUT);
}

