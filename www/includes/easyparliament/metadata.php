<?php

/**
 * @file
 * This file will be included by data.php.
 */

// The path of the file should be set as METADATAPATH in config.php.

// What are session_vars ?
// When generating a URL to a page using the URL class (in url.php), any
// GET variables for the page whose keys are listed in its session_vars below
// will automatically be put in the URL.

// For example, in this metadata we might have:
// 'search' => array (
//         'url' => 'search/',
//        'sidebar' => 'search',
//        'session_vars' => array ('s')
// ),.

// If we are at the URL www.domain.org/search/?s=blair&page=2
// and we used the URL class to generate a link to the search page like this:
//         $URL = new URL('search');
//        $newurl = $URL->generate();

// Then $newurl would be: /search/?s=blair
//
// sidebar:
// If you have a 'sidebar' element for a page then that page will have its content
// set to a restricted width and a sidebar will be inserted. The contents of this
// will be include()d from a file in template/sidebars/ of the name of the 'sidebar'
// value ('search.php' in the example above).

/* Items a page might have:

menu        An array of 'text' and 'title' which are used if the page
appears in the site menu.
title        Used for the <title> and the page's heading on the page.
heading        If present *this* is used for the page's heading on the page, in
in place of the title.
url            The URL from the site webroot for this page.
parent        What page is this page's parent (see below).
session_vars        If present, whenever a URL is generated to this page using the
URL class, any POST/GET variables with matching names are
automatically appended to the url.
track (deprecated)         Do we want to include the Extreme Tracker javascript on this page?
rss            Does the content of this page (or some of it) have an RSS version?
If so, 'rss' should be set to '/a/path/to/the/feed.rdf'.


PARENTS
The site's menu has a top menu and a bottom, sub-menu. What is displayed in the
sub-menu depends on which page is selected in the top menu. This is worked out
from the bottom up, by looking at pages' parents. Here's an example top and bottom
menu, with the capitalised items hilited:

Home    HANSARD        Glossary    Help

DEBATES        Written Answers

If we were viewing a particular debate, we would be on the 'debate' page. The parent
of this is 'debatesfront', which is the DEBATES link in the bottom menu - hence its
hilite. The parent of 'debatesfront' is 'hansard', hence its hilite in the top menu.

This may, of course, make no sense at all...

If a page has no parent it is either in the top menu or no menu items should be hilited.
The actual contents of each menu is determined in $PAGE->menu().

 */

$this->page = [

// Things used on EVERY page, unless overridden for a page:
  'default' => [
    'parent'    => '',
    'session_vars' => ['super_debug'],
    'sitetitle'        => 'OpenAustralia.org',
        // Deprecated   'track'        => false.
  ],



    // Every page on the site should have an entry below...

    // KEEP THE PAGES IN ALPHABETICAL ORDER! TA.

  'about' => [
        'title'            => 'About us',
        'url'            => 'about/'
    ],

  'addcomment'  => [
    'url'            => 'addcomment/',
  ],

  'admin_alerts' => [
    'title'            => 'Email alerts',
    'parent'        => 'admin',
    'url'            => 'admin/alerts.php',
  ],
  'alert_stats' => [
    'title'            => 'Email alerts',
    'parent'        => 'admin',
    'url'            => 'admin/alert_stats.php',
  ],
  'admin_badusers' => [
        'title'            => 'Bad users',
        'parent'        => 'admin',
        'url'            => 'admin/badusers.php'
    ],
  'admin_home' => [
        'title'            => 'Home',
        'parent'        => 'admin',
        'url'            => 'admin/'
    ],
  'admin_comments' => [
        'title'            => 'Recent comments',
        'parent'        => 'admin',
        'url'            => 'admin/comments.php'
    ],
  'admin_commentreport' => [
        'title'            => 'Processing a comment report',
        'parent'        => 'admin',
        'url'            => 'admin/report.php',
        'session_vars'    => ['rid', 'cid']
    ],
  'admin_commentreports' => [
        'title'            => 'Outstanding comment reports',
        'parent'        => 'admin',
        'url'            => 'admin/reports.php'
    ],
  'admin_failedsearches' => [
        'title'            => 'Failed searches',
        'parent'        => 'admin',
        'url'            => 'admin/failedsearches.php'
    ],
  'admin_glossary' => [
        'title'            => 'Manage glossary entries',
        'parent'        => 'admin',
        'url'            => 'admin/glossary.php'
    ],
  'admin_glossary_pending' => [
        'title'            => 'Review pending glossary entries',
        'parent'        => 'admin',
        'url'            => 'admin/glossary_pending.php'
    ],
  'admin_searchlogs' => [
        'title'            => 'Recent searches',
        'parent'        => 'admin',
        'url'            => 'admin/searchlogs.php'
    ],
  'admin_popularsearches' => [
        'title'            => 'Popular searches in last 30 days (first 1000)',
        'parent'        => 'admin',
        'url'            => 'admin/popularsearches.php'
    ],
  'admin_statistics' => [
        'title'            => 'General statistics',
        'parent'        => 'admin',
        'url'            => 'admin/statistics.php'
    ],
  'admin_trackbacks' => [
        'title'            => 'Recent trackbacks',
        'parent'        => 'admin',
        'url'            => 'admin/trackbacks.php'
    ],

    // Added by Richard Allan for email alert functions.

  'alert' => [
    'menu'            => [
            'text'            => 'Email Alerts',
            'title'            => "Set up alerts for updates on a Representative by email",
            'sidebar'        => 'alert'

        ],
    'title'            => 'OpenAustralia.org Email Alerts',
    'url'            => 'alert/',
  ],
  'alertconfirm' => [
        'track'            => TRUE,
        'url'            => 'alert/confirm/'
    ],
  'alertconfirmfailed' => [
        'title'            => 'Oops!',
        'track'            => TRUE,
        'url'            => 'alert/confirm/'
    ],
  'alertconfirmsucceeded' => [
        'title'            => 'Alert Confirmed!',
        'track'            => TRUE,
        'url'            => 'alert/confirm/'
    ],
  'alertdelete' => [
        'track'            => TRUE,
        'url'            => 'alert/delete/'
    ],
  'alertdeletefailed' => [
        'title'            => 'Oops!',
        'track'            => TRUE,
        'url'            => 'alert/delete/'
    ],
  'alertdeletesucceeded' => [
        'title'            => 'Alert Unsubscribed!',
        'track'            => TRUE,
        'url'            => 'alert/delete/'
    ],
  'alertundeletesucceeded' => [
        'title'            => 'Alert Resubscribed!',
        'track'            => TRUE,
        'url'            => 'alert/undelete/'
    ],
  'alertundeletefailed' => [
        'title'            => 'Oops!',
        'track'            => TRUE,
        'url'            => 'alert/undelete/'
    ],
  'alertwelcome' => [
    'title'            => 'Email Alerts',
    'url'            => 'alert/',
  ],

    // End of ALERTS additions.

    // 'api_front'        => array (
    // 'menu'            => array (
    // 'text'            => 'API',
    // 'title'            => 'Access our data'
    // ),
    // 'title'            => 'OpenAustralia API',
    // 'url'            => 'api/'
    // ),
  'api_doc_front'        => [
        'menu'            => [
            'text'            => 'API',
            'title'            => 'Access our data'
        ],
        'parent'        => 'api_front',
        'url'            => 'api/'
    ],
  'api_key'        => [
        'title'            => 'API Key',
        'parent'        => 'api_front',
        'url'            => 'api/'
    ],

  'cards' => [
        'title'            => 'MP Stats Cards',
        'url'            => 'cards/'
    ],

  'commentreport' => [
        'title'            => 'Reporting a comment',
        'url'            => 'report/',
        'session_vars'    => ['id']
    ],

  'comments_recent' => [
        'menu'            => [
            'text'            => 'Recent comments',
            'title'            => "Recently posted comments"
        ],
        'title'            => "Recent comments",
        'url'            => 'comments/recent/'
    ],

  'contact' => [
        'title'            => 'Contact OpenAustralia.org',
        'url'            => 'contact/'
    ],

  'debate'  => [
    'parent'        => 'debatesfront',
    'track'            => TRUE,
    'url'            => 'debate/',
    'session_vars'    => ['id'],
  ],
  'debates'  => [
    'parent'        => 'debatesfront',
    'track'            => TRUE,
    'url'            => 'debates/',
    'session_vars'    => ['id'],
  ],
  'debatesday' => [
    'parent'        => 'debatesfront',
    'session_vars'    => ['d'],
    'track'            => TRUE,
    'url'            => 'debates/',
  ],
  'debatesfront' => [
        'menu'            => [
            'text'            => 'House Debates',
            'title'            => "House debates"
        ],
        'parent'        => 'hansard',
        'title'            => 'House debates',
        'track'            => TRUE,
        'rss'            => 'debates/debates.rss',
        'url'            => 'debates/'
    ],
  'debatesyear' => [
        'parent'        => 'debatesfront',
        'title'            => 'Debates for ',
        'url'            => 'debates/'
    ],
  'epvote' => [
        'url'            => 'vote/'
    ],

  'gadget' => [
    'url'            => 'gadget/',
    'title'            => 'OpenAustralia Google gadget',
  ],

  'glossary' => [
        'heading'        => 'Glossary',
        'parent'        => 'help_us_out',
        'track'            => TRUE,
        'url'            => 'glossary/'
    ],
  'glossary_addterm' => [
        'menu'            => [
            'text'            => 'Add a term',
            'title'            => "Add a definition for a term to the glossary"
        ],
        'parent'        => 'help_us_out',
        'title'            => 'Add a glossary item',
        'url'            => 'addterm/',
        'session_vars'    => ['g']
    ],
  'glossary_addlink' => [
        'menu'            => [
            'text'            => 'Add a link',
            'title'            => "Add an external link"
        ],
        'parent'        => 'help_us_out',
        'title'            => 'Add a link',
        'url'            => 'addlink/',
        'session_vars'    => ['g']
    ],
  'glossary_item' => [
        'heading'        => 'Glossary heading',
        'parent'        => 'help_us_out',
        'track'            => TRUE,
        'url'            => 'glossary/',
        'session_vars'    => ['g']
    ],
  'hansard' => [
        'menu'            => [
            'text'            => 'Debates',
            'title'            => "House of Representatives and Senate debates"
        ],
        'title'            => 'House of Representatives and Senate debates',
        'track'            => TRUE,
        'url'            => 'hansard/'
    ],
  'hansard_date' => [
        'parent'        => 'hansard',
        'title'            => 'House of Representatives',
        'track'            => TRUE,
        'url'            => 'hansard/'
    ],
  'help' => [
        'menu'            => [
            'text'            => 'Help',
            'title'            => "Answers to your questions"
        ],
        'title'            => 'Help',
        'track'            => TRUE,
        'url'            => 'help/'
    ],
  'help_us_out' => [
        'menu'            => [
            'text'            => 'Glossary',
            'title'            => "Parliament's jargon explained"
        ],
        'title'            => 'Glossary',
        'heading'        => 'Add a glossary item',
        'url'            => 'addterm/',
        'sidebar'        => 'glossary_add'
    ],
  'home' => [
        'menu'            => [
            'text'            => 'Home',
            'title'            => "The front page of the site"
        ],
        'title'            => "Are your Representatives and Senators working for you in Australia's Parliament?",
        'track'            => TRUE,
        'rss'            => 'news/index.rdf',
        'url'            => ''
    ],
  'houserules' => [
        'title'            => 'House rules',
        'url'            => 'houserules/'
    ],

  'linktous' => [
        'title'            => 'Link to us',
        'heading'        => 'How to link to us',
        'url'            => 'help/linktous/'
    ],

  'lordsdebate'  => [
    'parent'        => 'lordsdebatesfront',
    'track'            => TRUE,
    'url'            => 'senate/',
    'session_vars'    => ['gid'],
  ],
  'lordsdebates'  => [
    'parent'        => 'lordsdebatesfront',
    'track'            => TRUE,
    'url'            => 'senate/',
    'session_vars'    => ['id'],
  ],
  'lordsdebatesday' => [
    'parent'        => 'lordsdebatesfront',
    'session_vars'    => ['d'],
    'track'            => TRUE,
    'url'            => 'senate/',
  ],
  'lordsdebatesfront' => [
        'menu'            => [
            'text'            => 'Senate Debates',
            'title'            => "Senate debates"
        ],
        'parent'        => 'hansard',
        'title'            => 'Senate debates',
        'track'            => TRUE,
        'rss'            => 'senate/senate.rss',
        'url'            => 'senate/'
    ],
  'lordsdebatesyear' => [
        'parent'        => 'lordsdebatesfront',
        'title'            => 'Debates for ',
        'url'            => 'senate/'
    ],

  'peer' => [
        'title'            => 'Senator',
        'track'            => TRUE,
        'url'            => 'senator/'
    ],
  'peers' => [
         'menu'            => [
            'text'            => 'Senators',
            'title'            => "List of all Senators"
        ],
         'title'            => 'All Senators',
         'track'            => TRUE,
         'url'            => 'senators/'
    ],

  'mla' => [
        'title'            => 'MLA',
        'track'            => TRUE,
        'url'            => 'mla/'
    ],
  'mlas' => [
         'menu'            => [
            'text'            => 'All MLAs',
            'title'            => "List of all MLAs"
        ],
         'title'            => 'All MLAs',
         'track'            => TRUE,
         'url'            => 'mlas/'
    ],

  'msp' => [
        'title'            => 'MSP',
        'track'            => TRUE,
        'url'            => 'msp/'
    ],
  'msps' => [
         'menu'            => [
            'text'            => 'All MSPs',
            'title'            => "List of all MSPs"
        ],
         'title'            => 'All MSPs',
         'track'            => TRUE,
         'url'            => 'msps/'
    ],

    /* Not 'Your MP', whose name is 'yourmp'... */
  'mp' => [
        'title'            => 'MP',
        'track'            => TRUE,
        'url'            => 'mp/'
    ],
  'emailfriend' => [
        'title'            => 'Send this page to a friend',
        'track'            => TRUE,
        'url'            => 'email/'
    ],
  'c4_mp' => [
        'title'            => 'MP',
        'track'            => TRUE,
        'url'            => 'mp/c4/'
    ],
  'c4x_mp' => [
        'title'            => 'MP',
        'track'            => TRUE,
        'url'            => 'mp/c4x/'
    ],
    // The directory MPs' RSS feeds are stored in.
  'mp_rss' => [
        'url'            => 'rss/mp/'
    ],

  'mps' => [
         'menu'            => [
            'text'            => 'Representatives',
            'title'            => "Your Representative and list of all Members of the House of Representatives"
        ],
         'title'            => 'All Members of the House of Representatives',
         'track'            => TRUE,
         'url'            => 'mps/'
    ],
  'c4_mps' => [
        'title' => 'All MPs',
        'track' => TRUE,
        'url' => 'mps/c4/'
    ],
  'c4x_mps' => [
        'title' => 'All MPs',
        'track' => TRUE,
        'url' => 'mps/c4x/'
    ],

  'nidebate'  => [
    'parent'        => 'nidebatesfront',
    'track'            => TRUE,
    'url'            => 'ni/',
    'session_vars'    => ['gid'],
  ],
  'nidebates'  => [
    'parent'        => 'nidebatesfront',
    'track'            => TRUE,
    'url'            => 'ni/',
    'session_vars'    => ['id'],
  ],
  'nidebatesday' => [
    'parent'        => 'nidebatesfront',
    'session_vars'    => ['d'],
    'track'            => TRUE,
    'url'            => 'ni/',
  ],
  'nidebatesfront' => [
        'menu'            => [
            'text'            => 'NIA Debates',
            'title'            => "Northern Ireland Assembly debates"
        ],
        'parent'        => 'hansard',
        'title'            => 'Northern Ireland Assembly debates',
        'track'            => TRUE,
        'rss'            => 'ni/ni.rss',
        'url'            => 'ni/'
    ],
  'nidebatesyear' => [
        'parent'        => 'nidebatesfront',
        'title'            => 'Debates for ',
        'url'            => 'ni/'
    ],

  'otheruseredit' => [
        'pg'            => 'editother',
        'title'            => "Editing a user's data",
        'url'            => 'user/'
    ],
  'privacy' => [
        'title'            => 'Privacy Policy',
        'url'            => 'privacy/'
    ],

    /* Public bill committees */
  'pbc_front' => [
        'menu'            => [
            'text'            => 'Public Bill Committees',
            'title'            => "Public Bill Committees (formerly Standing Committees) debates"
        ],
        'parent'        => 'hansard',
        'title'            => 'Public Bill Committees',
        'rss'            => 'pbc/pbc.rss',
        'url'            => 'pbc/'
    ],
  'pbc_session' => [
    'title' => 'Session',
    'url' => 'pbc/',
    'parent' => 'pbc_front',
  ],
  'pbc_bill' => [
    'title' => '',
    'url' => 'pbc/',
    'parent' => 'pbc_front',
    'session_vars'    => ['bill'],
  ],
  'pbc_clause' => [
    'parent'        => 'pbc_front',
    'url'            => 'pbc/',
    'session_vars'    => ['id'],
  ],
  'pbc_speech' => [
    'parent'        => 'pbc_front',
    'url'            => 'pbc/',
    'session_vars'    => ['id'],
  ],

  'raw' => [
        'title'            => 'Raw data',
        'url'            => 'raw/'
    ],

  'regmem' => [
        'title'            => 'Changes to the Register of Members\' Interests',
        'url'            => 'regmem/'
    ],

  'regmem_date' => [
        'url'            => 'regmem/',
        'parent'        => 'regmem'
    ],

  'regmem_mp' => [
        'url'            => 'regmem/',
        'parent'        => 'regmem'
    ],

  'regmem_diff' => [
        'url'            => 'regmem/',
        'parent'        => 'regmem'
    ],

  'royal' => [
        'title'            => 'Royal',
        'url'            => 'royal/'
    ],

  'search'        => [
        'sidebar'        => 'search',
        'track'            => TRUE,
        'url'            => 'search/',
        'session_vars'    => ['s', 'pid', 'o', 'pop']
    ],
  'search_help'        => [
        'sidebar'        => 'search',
        'title'            => 'Help with searching',
        'url'            => 'search/'
    ],

  'sitenews'        => [
        'menu'            => [
            'text'            => 'News',
            'title'            => "News about changes to this website"
        ],
        'rss'            => 'news/index.rdf',
        'sidebar'        => 'sitenews',
        'title'            => 'OpenAustralia news',
        'track'            => TRUE,
        'url'            => 'news/'
    ],
  'sitenews_archive'        => [
        'parent'        => 'sitenews',
        'rss'            => 'news/index.rdf',
        'sidebar'        => 'sitenews',
        'title'            => 'Archive',
        'track'            => TRUE,
        'url'            => 'news/archives/'
    ],
  'sitenews_atom'     => [
        'url'            => 'news/atom.xml'
    ],
  'sitenews_date'    => [
        'parent'        => 'sitenews',
        'rss'            => 'news/index.rdf',
        'sidebar'        => 'sitenews'
    ],
  'sitenews_individual'    => [
    'parent'        => 'sitenews',
    'rss'            => 'news/index.rdf',
    'sidebar'        => 'sitenews',
    // Deprecated         'track'            => true.
  ],
  'sitenews_rss1'     => [
        'url'            => 'news/index.rdf'
    ],
  'sitenews_rss2'     => [
        'url'            => 'news/index.xml'
    ],

  'skin'        => [
        'title'            => 'Skin this site',
        'url'            => 'skin/'
    ],

    /* Scottish Parliament */
  'spdebate'  => [
    'parent'        => 'spdebatesfront',
    'track'            => TRUE,
    'url'            => 'sp/',
    'session_vars'    => ['id'],
  ],
  'spdebates'  => [
    'parent'        => 'spdebatesfront',
    'track'            => TRUE,
    'url'            => 'sp/',
    'session_vars'    => ['id'],
  ],
  'spdebatesday' => [
    'parent'        => 'spdebatesfront',
    'session_vars'    => ['d'],
    'track'            => TRUE,
    'url'            => 'sp/',
  ],
  'spdebatesfront' => [
        'menu'            => [
            'text'            => 'Scottish Parliament Debates',
            'title'            => "Scottish Parliament debates"
        ],
        'parent'        => 'hansard',
        'title'            => 'Scottish Parliament debates',
        'track'            => TRUE,
        'rss'            => 'sp/sp.rss',
        'url'            => 'sp/'
    ],
  'spdebatesyear' => [
        'parent'        => 'spdebatesfront',
        'title'            => 'Debates for ',
        'url'            => 'sp/'
    ],
  'spwrans'  => [
        'parent'        => 'spwransfront',
        'url'            => 'spwrans/',
        'session_vars'    => ['id']
    ],
  'spwransday'  => [
        'parent'        => 'spwransfront',
        'url'            => 'spwrans/'
    ],
  'spwransfront'  => [
        'menu'            => [
            'text'            => 'SP written answers',
            'title'            => "Written Answers"
        ],
        'parent'        => 'hansard',
        'title'            => 'Scottish Parliament Written answers',
        'url'            => 'spwrans/'
    ],
  'spwransmp' => [
        'parent'        => 'spwransfront',
        'title'            => 'For questions asked by ',
        'url'            => 'spwrans/'
    ],
  'spwransyear' => [
        'parent'        => 'spwransfront',
        'title'            => 'Scottish Parliament Written answers for ',
        'url'            => 'spwrans/'
    ],

    // The URL 3rd parties need to ping something here.
  'trackback' => [
        'url'            => 'trackback/'
    ],

  'useralerts' => [
        'menu'            => [
            'text'            => 'Email Alerts',
            'title'            => 'Check your email alerts'
        ],
        'title'            => 'Your Email Alerts',
        'url'            => 'user/alerts/',
        'parent'        => 'userviewself'
    ],
  'userchangepc' => [
        'title'            => 'Change your Representative',
        'url'            => 'user/changepc/'
    ],
  'userconfirm' => [
    // Deprecated     'track'            => true,.
        'url'            => 'user/confirm/'
    ],
  'userconfirmed' => [
        'sidebar'        => 'userconfirmed',
        'title'            => 'Welcome to OpenAustralia.org!',
    // Deprecated     'track'            => true,.
        'url'            => 'user/confirm/'
    ],
  'userconfirmfailed' => [
        'title'            => 'Oops!',
    // Deprecated     'track'            => true,.
        'url'            => 'user/confirm/'
    ],
  'useredit' => [
        'pg'            => 'edit',
        'title'            => 'Edit your details',
        'url'            => 'user/'
    ],
  'userjoin' => [
                'menu'                  => [
                        'text'                  => 'Join',
                        'title'                 => "Joining is free and allows you to post comments"
                ],
                'pg'                    => 'join',
                'sidebar'               => 'userjoin',
                'title'                 => 'Join OpenAustralia.org',
                // Deprecated    'track'                 => true,.
                'url'                   => 'user/'
        ],
  'getinvolved' => [
        'menu'            => [
            'text'            => 'Get involved',
            'title'            => "Contribute to OpenAustralia.org"
        ],
        'pg'            => 'getinvolved',
        'sidebar'        => 'userjoin',
        'title'            => 'Contribute to OpenAustralia.org',
        // Deprecated     'track'            => true,.
        'url'            => 'getinvolved/'
    ],
  'userlogin' => [
        'menu'            => [
            'text'            => 'Log in',
            'title'            => "If you've already joined, log in to post comments"
        ],
        'sidebar'        => 'userlogin',
        'title'            => 'Log in',
        // Deprecated     'track'            => true,.
        'url'            => 'user/login/'
    ],

  'userlogout' => [
        'menu'            => [
            'text'            => 'Log out',
            'title'            => "Log out"
        ],
        'url'            => 'user/logout/'
    ],
  'userpassword' => [
        'title'            => 'Change password',
        'url'            => 'user/password/'
    ],
  'userprompt' => [
        'title'            => 'Please log in',
        'url'            => 'user/prompt/'
    ],
  'userview' => [
        'session_vars'    => ['u'],
        'url'            => 'user/'
    ],
  'userviewself' => [
        'menu'            => [
            'text'            => 'Your details',
            'title'            => "View and edit your details"
        ],
        'url'            => 'user/'
    ],
  'userwelcome' => [
        'title'            => 'Welcome!',
        'url'            => 'user/'
    ],
  'whall'  => [
    'parent'        => 'whallfront',
    'url'            => 'whall/',
    'session_vars'    => ['id'],
  ],
  'whalls'  => [
    'parent'        => 'whallfront',
    'url'            => 'whall/',
    'session_vars'    => ['id'],
  ],
  'whallday' => [
    'parent'        => 'whallfront',
    'session_vars'    => ['d'],
    'url'            => 'whall/',
  ],
  'whallfront' => [
        'menu'            => [
            'text'            => 'Westminster Hall',
            'title'            => "Westminster Hall debates"
        ],
        'parent'        => 'hansard',
        'title'            => 'Westminster Hall debates',
        'rss'            => 'whall/whall.rss',
        'url'            => 'whall/'
    ],
  'whallyear' => [
        'parent'        => 'whallfront',
        'title'            => 'Westminster Hall debates for ',
        'url'            => 'whall/'
    ],

  'wms' => [
        'parent'        => 'wmsfront',
        'url'            => 'wms/',
        'session_vars'    => ['id']
    ],
  'wmsday' => [
        'parent'        => 'wmsfront',
        'session_vars'    => ['d'],
        'url'            => 'wms/'
    ],
  'wmsfront' => [
        'menu'            => [
            'text'            => 'Written Ministerial Statements',
            'title'            => 'Written Ministerial Statements'
        ],
        'parent'        => 'hansard',
        'title'            => 'Written Ministerial Statements',
        'rss'            => 'wms/wms.rss',
        'url'            => 'wms/'
    ],
  'wmsyear' => [
        'parent'        => 'wmsfront',
        'title'            => 'Written Ministerial Statements for ',
        'url'            => 'wms/'
    ],

  'wrans'  => [
        'parent'        => 'wransfront',
        'url'            => 'wrans/',
        'session_vars'    => ['id']
    ],
  'wransday'  => [
        'parent'        => 'wransfront',
        'url'            => 'wrans/'
    ],
  'wransfront'  => [
        'menu'            => [
            'text'            => 'Written Answers',
            'title'            => "Written Answers"
        ],
        'parent'        => 'hansard',
        'title'            => 'Written answers',
        'url'            => 'wrans/'
    ],
  'wransmp' => [
        'parent'        => 'wransfront',
        'title'            => 'For questions asked by ',
        'url'            => 'wrans/'
    ],
  'wransyear' => [
        'parent'        => 'wransfront',
        'title'            => 'Written answers for ',
        'url'            => 'wrans/'
    ],

  'yourmp' => [
        'menu'            => [
            'text'            => '<em>Your</em> Representative',
            'title'            => "Find out about your Member of the House of Representatives"
        ],
        'sidebar'        => 'yourmp',
        'title'            => 'Your MP',
        'url'            => 'mp/'
    ],
  'yourmp_recent' => [
        'menu'            => [
            'text'            => 'Recent appearances',
            'title'            => "Recent speeches and written answers by this MP"
        ],
        'parent'        => 'yourmp',
        'title'            => "Your MP's recent appearances in parliament",
        'url'            => 'mp/?recent=1'
    ],
];



// We just use the sections for creating page headings/titles.
// The 'title' is always used for the <title> tag of the page.
// The text displayed on the page itself will also be this,
// UNLESS the section has a 'heading', in which case that's used instead.

$this->section = [


    'about' => [
        'title'     => 'About Us'
    ],
    'admin' => [
        'title'        => 'Admin'
    ],
    'debates' => [
        'title'     => 'Debates',
        'heading'    => 'House of Commons Debates'
    ],
    'help_us_out' => [
        'title'     => 'Help Us Out'
    ],
    'hansard' => [
        'title'     => 'Hansard'
    ],
    'home' => [
        'title'     => 'Home'
    ],
    'mp' => [
        'title'     => 'Your MP'
    ],
    'search' => [
        'title'     => 'Search'
    ],
    'sitenews' => [
        'title'     => 'OpenAustralia news'
    ],
    'wrans' => [
        'title'     => 'Written Answers'
    ]

];
