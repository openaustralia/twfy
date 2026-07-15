requires 'DBI';
requires 'DBD::mysql';
requires 'HTML::Entities';
requires 'HTML::Parser';
requires 'LWP::Simple';
requires 'Search::Xapian';
requires 'URI';
requires 'XML::RSS';
requires 'XML::Simple';
requires 'XML::Twig';

on 'develop' => sub {
    requires 'Perl::Critic';
};
