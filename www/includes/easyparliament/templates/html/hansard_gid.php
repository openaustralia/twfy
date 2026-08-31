<?php
// For displaying the main Hansard content listings (by gid),
// and individual Hansard items (the comments are handled separately
// by COMMENTLIST and the comments.php template).

// Remember, we are currently within the DEBATELIST or WRANSLISTS class,
// in the render() function.

// The array $data will be packed full of luverly stuff about hansard objects.
// See the bottom of this document for information about its structure and contents...

global $PAGE, $this_page, $GLOSSARY, $hansardmajors, $DATA;

include_once __DIR__ . "/../../../easyparliament/searchengine.php";
include_once __DIR__ . "/../../../easyparliament/member.php";
include_once __DIR__ . "/../../../easyparliament/HansardSpeechView.php";

twfy_debug("TEMPLATE", "hansard_gid.php");

// Will set the page headings and start the page HTML if it hasn't
// already been started.
if (!isset($data['info'])) {
    // No data! We'll be exiting here! but send a 404 so that spiders stop indexing the page.
    header("HTTP/1.0 404 Not Found");
    # Bots have some fiddled with wrans, so we get errors here sometimes that don't
    # signify a real problem.
    #trigger_error("Not enough data to display anything.", E_USER_ERROR);
    exit;
}

// House representatives (1) and Senate (101) - the two houses this fork actually
// parses - get the new Plates-rendered transcript. Everything else (Wrans, WMS, and
// the UK/NI/Scotland majors left over from the mySociety fork this codebase started
// from, none of which are populated on this site) keeps the existing stripe-based
// rendering below untouched. See openaustralia/openaustralia#939 and the debate-page
// redesign plan for why. Reads $data['info']['major'], so this has to come after the
// isset($data['info']) guard above, not before it - moving it here fixes a real
// warning on the known bot-triggered no-data path that guard exists for.
$usePlatesTemplate = in_array($data['info']['major'], [1, 101], true);
$plates_items = [];
if ($usePlatesTemplate) {
    $platesEngine = new League\Plates\Engine(__DIR__ . "/../../../../resources/views");

    // "House debates"/"Senate debates" (chamberLabel) and "House of Representatives"/
    // "Senate" (chamberNames, indexed by major) - two different existing labels for
    // the same thing, kept separate since transcript.php's eyebrow line uses the
    // first and its "chamber" row the second. Both the transcript card and the
    // section-index page (below) show one or the other somewhere on the page - see
    // openaustralia/openaustralia's own "which house was this?" feedback on the
    // section-index page, which had neither.
    $chamberNames = [1 => 'House of Representatives', 101 => 'Senate'];
    $chamberLabel = $hansardmajors[$data['info']['major']]['title'] ?? '';

    // Same "d=" listing link hansardlist.php itself builds for its own nextprev 'up'
    // link ("All House debates on 20 August 2026") - $page_all is the internal page
    // key ('debates'/'lordsdebates') the URL class maps to the real /debates/,
    // /senate/ etc. path. remove(['id']) matters: without it this page's own ?id=
    // survives into the generated URL alongside ?d=. Computed here, unconditionally,
    // so the transcript card's own date link (and buildNextPrev()'s 'up' link, just
    // below) can both use it.
    $dateURL = new URL($hansardmajors[$data['info']['major']]['page_all']);
    $dateURL->insert(['d' => $data['info']['date']]);
    $dateURL->remove(['id']);

    // "All Senate debates on 18 August 2026 / « Previous debate / Next debate »" - was
    // the stripe-foot block at the bottom of the page (still is, for non-Plates
    // majors - see the guarded stripe_start('foot') below). Computed here,
    // unconditionally for every Plates page (not just ones with a full transcript
    // card - see the $data['subrows'] branch below, eg an Adjournment debate's
    // section-index page, which has no $plates_items at all but still wants this
    // same pagination). Same page metadata $PAGE->nextprevlinks() itself reads, set
    // earlier by HANSARDLIST::_get_nextprev_items() - each of prev/up/next is
    // optional (eg no "prev" on the very first debate ever recorded), and a present
    // one isn't always a link (nextprevlinks() falls back to plain text with no
    // 'url' in some cases too).
    $nextprevdata = $DATA->page_metadata($this_page, 'nextprev') ?: [];
    $nextPrev = HansardSpeechView::buildNextPrev(
        $nextprevdata,
        $hansardmajors[$data['info']['major']]['title'] ?? 'debates',
        $dateURL->generate('none')
    );
}

$PAGE->page_start();

if (!$usePlatesTemplate) {
    $PAGE->stripe_start('head-1');

    $sidebar = $hansardmajors[$data['info']['major']]['sidebar_short'];

    $PAGE->stripe_end(array(
        array(
            'type' => 'include',
            'content' => $sidebar
        )
    ));
}
// For Plates pages, sidebars/hocdebates_short.php / holdebates_short.php's "What are
// House debates?"/"What are Senate debates?" link (to /debates/#help or /senate/#help)
// becomes its own block in the right-hand column instead - see $aboutTitle/
// $aboutBodyHtml below and resources/views/hansard/about-debates.php.

if ($data['info']['date'] == date('Y-m-d')) { ?>
    <div style="padding: 4px; margin: 1em; color: #000000; background-color: #ffeeee; border: solid 2px #ff0000;">
        Warning: Showing data from the current day is <strong>experimental</strong> and may not work correctly.
    </div>
    <?php
}

if (isset($data['rows'])) {
    // For highlighting
    $SEARCHENGINE = null;
    if (isset($data['info']['searchstring']) && $data['info']['searchstring'] != '') {
        $SEARCHENGINE = new SEARCHENGINE($data['info']['searchstring']);
    }

    // Before we print the body text we need to insert glossary links
    // and highlight search string words.

    // This doesn't quite work yet, as clashes with
    // highlighting of constituency name. Also the link
    // text comes out a bit long?
    // $row['body'] = preg_replace('#<phrase class="honfriend" id="uk.org.publicwhip/member/(\d+)" name="(.*?)">(.*)</phrase>#', '<a href="/mp/?m=$1" title="Our page on $2">$3</a>', $row['body']);

    $bodies = array();
    foreach ($data['rows'] as $row) {
        $bodies[] = $row['body'];
    }
    if (isset($data['info']['glossarise']) && ($data['info']['glossarise'] == 1)) {
        // And glossary phrases
        $bodies = $GLOSSARY->glossarise($bodies, 1);
    }
    if ($SEARCHENGINE) {
        // We have some search terms to highlight.
        $bodies = $SEARCHENGINE->highlight($bodies);
    }
    if (isset($data['info']['glossarise']) && ($data['info']['glossarise'] == 1)) {
        // Now we replace the title attributes for the glossarised links
        // to avoid words being highlighted within them.
        $bodies = $GLOSSARY->glossarise_titletags($bodies, 1);
    }
    for ($i = 0; $i < count($data['rows']); $i++) {
        if ($data['rows'][$i]['htype'] == 12)
            $data['rows'][$i]['body'] = $bodies[$i];
    }

    // Stores the current time of items, so we can tell when an item appears
    // at a new time.
    $timetracker = 0;

    $stripecount = 0; // Used to generate stripes.

    $first_speech_displayed = 0; // We want to know when to insert the javascript that pulls in flash video

    // We're going to be just cycling through each row of data for this page.
    // When we get the first section, we put its text in $section_title.
    // When we get the first subsection, we put its text in $subsection_title.
    // When we get the first item that is neither section or subsection, we
    // print these titles.
    $section_title = '&nbsp;';
    $subsection_title = '&nbsp;';

    // So we don't keep on printing the titles!
    $titles_displayed = false;
    foreach ($data['rows'] as $row) {
        if (count($row) == 0) {
            // Oops, there's nothing in this row. A check just in case.
            continue;
        }

        // DISPLAY SECTION AND SUBSECTION HEADINGS.
        if (!$titles_displayed && $row['htype'] != '10' && $row['htype'] != '11') {
            if ($usePlatesTemplate) {
                // Rendered inside the card itself instead (see transcript.php) - no
                // separate stripe-head-2 title bar above it, so there's only one
                // title on the page, not two.
                $titles_displayed = true;
            } else {
                // Output the titles we've got so far.

                $PAGE->stripe_start('head-2');
                ?>
                <h4><?php echo $section_title; ?></h4>
                <h5><?php echo $subsection_title; ?></h5>
                <?php

                $PAGE->stripe_end(array(
                    array(
                        'type' => 'nextprev'
                    )
                ));

                $titles_displayed = true;
            }
        }

        // NOW, depending on the contents of this row, we do something different...
        if ($row['htype'] == '10') {
            $section_title = $row['body'];
            twfy_debug("DATAMODEL", "epobjectid " . htmlentities($row['epobject_id']));
        } elseif ($row['htype'] == '11') {
            $subsection_title = $row['body'];
        } elseif ($row['htype'] == '13') {
            // DEBATE PROCEDURAL.

            if ($usePlatesTemplate) {
                $plates_items[] = HansardProceduralView::forProcedural($row, $data['info']);
                ob_flush();
                continue;
            }

            $stripecount++;
            $style = $stripecount % 2 == 0 ? '1' : '2';

            $PAGE->stripe_start('procedural-' . $style);

            echo $row['body'];

            echo context_link($row);

            $sidebarhtml = generate_commentteaser($row, $data['info']['major']);

            $PAGE->stripe_end(array(
                array(
                    'type' => 'html',
                    'content' => $sidebarhtml
                )
            ));


        } elseif ($row['htype'] == '12') {
            // A STANDARD SPEECH OR WRANS TEXT.

            if ($usePlatesTemplate) {
                // Same "is this a new time?" check as the stripe path below, just
                // without any of the stripe rendering that goes with it.
                $showTimestamp = substr($row['htime'], 0, 5) != $timetracker && $row['htime'] != "00:00:00";
                if ($showTimestamp) {
                    $timetracker = substr($row['htime'], 0, 5);
                }
                $plates_items[] = HansardSpeechView::forSpeech($row, $data['info'], $showTimestamp);
                ob_flush();
                continue;
            }

            $stripecount++;
            $style = $stripecount % 2 == 0 ? '1' : '2';

            $video_content = '';
            if ($data['info']['major'] == 1 && $first_speech_displayed == 0) { # only Commons debates currently
                $autostart = get_http_var('autoPlay');
                $video_content = "<p class='video'><script type=\"text/javascript\" src=\"https://parlvid.mysociety.org/video.cgi?gid=";
                $video_content .= $row['gid'];
                if ($autostart == 'true') {
                    $video_content .= "&autostart=true";
                }
                $video_content .= "&output=js-full\"></script></p>";
                $first_speech_displayed = true;
            }

            // If this item is at a new time, then print the time.
            if (substr($row['htime'], 0, 5) != $timetracker && $row['htime'] != "00:00:00") {

                $PAGE->stripe_start('time-' . $style);

                echo "\t\t\t\t<p>" . format_time($row['htime'], TIMEFORMAT) . "</p>\n";

                $PAGE->stripe_end();

                // Set the timetracker to the current time
                $timetracker = substr($row['htime'], 0, 5);

                $stripecount++;
                $style = $stripecount % 2 == 0 ? '1' : '2';
            }


            if (isset($row['speaker']) && ((isset($row['speaker']['member_id']) && isset($data['info']['member_id']) && $row['speaker']['member_id'] == $data['info']['member_id']) || (isset($row['speaker']['person_id']) && isset($data['info']['person_id']) && $row['speaker']['person_id'] == $data['info']['person_id']))) {
                $style .= '-on';
            }

            // gid_to_anchor() is in utility.php
            $id = 'g' . gid_to_anchor($row['gid']);

            $PAGE->stripe_start($style, $id);

            ?>
            <a name="<?php echo $id; ?>"></a>
            <?php

            if (isset($row['speaker']) && count($row['speaker']) > 0) {
                // We have a speaker to print.

                $speaker = $row['speaker'];
                $speakername = ucfirst(member_full_name($speaker['house'], $speaker['title'], $speaker['first_name'], $speaker['last_name'], $speaker['constituency']));
                echo '<p class="speaker"><a href="', $speaker['url'], '" title="See more information about ', $speakername, '">';
                list($image, $sz) = find_rep_image($speaker['person_id'], true);
                if ($image) {
                    echo '<img src="', $image, '" class="portrait" alt="Photo of ', $speakername, '"';
                    if (get_http_var('partycolours')) {
                        echo ' style="border: 3px solid ', party_to_colour($speaker['party']), ';"';
                    }
                    echo '>';
                }
                echo '<strong>', $speakername, '</strong></a> <small>';
                $desc = '';
                if ($speaker['party'] != 'Speaker' && $speaker['party'] != 'Deputy Speaker' && $speaker['party'] != "President" && $speaker['constituency']) {
                    $desc .= $speaker['constituency'] . ', ';
                }
                if (get_http_var('wordcolours')) {
                    $desc .= '<span style="color: ' . party_to_colour($speaker['party']) . '">';
                }
                $desc .= htmlentities($speaker['party']);
                if (get_http_var('wordcolours')) {
                    $desc .= '</span>';
                }
                if (isset($speaker['office'])) {
                    $desc .= ', ' . $speaker['office'][0]['pretty'];
                }
                if ($desc) {
                    print "($desc)";
                }

                if ($hansardmajors[$data['info']['major']]['type'] == 'debate' && $this_page == $hansardmajors[$data['info']['major']]['page_all']) {
                    ?> | <a href="<?php echo $row['commentsurl']; ?>" title="Copy this URL to link directly to this piece of text"
                    class="permalink">Link to this</a><?php
                }
                if (isset($row['source_url']) && $row['source_url'] != '') {
                    echo ' | <a href="', $row['source_url'], '" title="The source of this piece of text">',
                        ($hansardmajors[$data['info']['major']]['location'] == 'Scotland' ? 'Official Report' : 'Hansard'),
                        ' source</a>';
                }

                if ($data['info']['major'] == 8 && preg_match('#\d{4}-\d\d-\d\d\.(.*?)\.q#', $row['gid'], $m)) {
                    # Scottish Wrans only
                    print " | Question $m[1]";
                }
                echo "</small>";
            }

            $body = $row['body'];

            if ($hansardmajors[$data['info']['major']]['location'] == 'Scotland') {
                $body = preg_replace('# (S\dW-\d+) #', ' <a href="/spwrans/?spid=$1">$1</a> ', $body);
                $body = preg_replace(
                    '#<citation id="uk\.org\.publicwhip/(.*?)/(.*?)">\[(.*?)\]</citation>#e',
                    "'[<a href=\"/' . ('$1'=='spor'?'sp/?g':('$1'=='spwa'?'spwrans/?':'debate/?')) . 'id=$2' . '\">$3</a>]'",
                    $body
                );
            }

            $body = str_replace('pwmotiontext="moved"', 'class="moved"', $body);
            $body = str_replace('<a href="h', '<a rel="nofollow" href="h', $body); # As even sites in Hansard lapse and become spam-sites
            echo str_replace('</p><p', '</p> <p', $body); # NN4 font size bug

            echo context_link($row);

            $sidebarhtml = '';
            $extrahtml = '';

            if (isset($row['votes']) && (!strstr($row['gid'], 'q'))) {
                $sidebarhtml .= generate_votes($row['votes'], $row['major'], $row['epobject_id'], $row['gid']);
            }

            $sidebarhtml .= generate_commentteaser($row, $data['info']['major']);

            $PAGE->stripe_end(array(
                array(
                    'type' => 'html',
                    'content' => $sidebarhtml
                ),
                array(
                    'type' => 'extrahtml',
                    'content' => $extrahtml
                )
            ));


        } // End htype 12.

        ob_flush(); //flush the output buffer

    } // End cycling through rows.

    if ($usePlatesTemplate) {
        // "What is X?" - a short explainer for the page's right-hand sidebar
        // (resources/views/hansard/about-debates.php), shared by the transcript
        // card and the section-index page below. See HansardSpeechView::
        // aboutSection() for the actual lookup/fallback logic and its own doc
        // comment.
        $about = HansardSpeechView::aboutSection(
            $section_title,
            $data['info']['major'],
            $hansardmajors[$data['info']['major']]['title'] ?? null
        );
        $aboutTitle = $about['title'];
        $aboutBodyHtml = $about['bodyHtml'];
    }

    if ($usePlatesTemplate && count($plates_items) > 0) {
        // stripe_start() below would otherwise auto-print $PAGE->heading() the first
        // time it's called on the page - "House debates"/"Senate debates" plus the
        // formatted date, sourced from page metadata set generically in
        // set_hansard_headings(). The card (transcript.php) carries both instead -
        // the chamber label next to the section title, the date in its own row below
        // - so suppress the original to avoid showing either twice. Set only here,
        // inside count($plates_items) > 0, not unconditionally on $usePlatesTemplate
        // alone: every stripe_start() call in the row loop above is already gated
        // behind $usePlatesTemplate (each takes an early continue instead), so
        // nothing there needs this set early - and a Plates-major page with zero
        // rows to show falls through to the plain $section_title/$subsection_title
        // stripe_start('head-2') fallback further down, which does still need
        // $PAGE->heading() to fire normally, or the page loses its heading entirely.
        $PAGE->heading_displayed = true;

        // $chamberNames/$dateURL/$aboutTitle/$aboutBodyHtml/$nextPrev: computed once,
        // unconditionally for every Plates page, near the top of this file / just
        // above - see the comments there.
        //
        // No breadcrumb trail on this card: it already carries a link to "all
        // debates on this day" (the date row below) and to the neighbouring debates
        // either side (the pagination bar) - a "Home / House of Representatives /
        // 20 August 2026" trail above the title would only be repeating links that
        // are already on the page, one click away either way.

        // A subsection heading (htype 11) isn't guaranteed to occur before this
        // renders - transcript.php puts $subsectionTitle in the page's main <h1>,
        // unlike the old stripe-head-2 rendering above (h5, well below $section_title's
        // own h4), so a still-unset '&nbsp;' sentinel here would show as a blank
        // headline instead of a barely-noticeable blank second line. When that
        // happens, $section_title becomes the <h1> instead, and drops out of the
        // eyebrow line above it so it isn't shown twice.
        $hasSubsectionTitle = $subsection_title !== '&nbsp;';

        echo $platesEngine->render('hansard/transcript', [
            'items' => $plates_items,
            'speakers' => HansardSpeechView::buildRoster($plates_items),
            // $hansardmajors[...]['title'] is "House debates"/"Senate debates" - the
            // same text the old stripe-head-1 h2 printed above the card (see
            // $PAGE->heading_displayed suppression below). Shown next to $section_title
            // in the card's eyebrow line instead, so it's not lost, just moved.
            'chamberLabel' => $hansardmajors[$data['info']['major']]['title'] ?? '',
            'sectionTitle' => $hasSubsectionTitle ? $section_title : '',
            'subsectionTitle' => $hasSubsectionTitle ? $subsection_title : $section_title,
            // Matches LONGERDATEFORMAT (what the suppressed old h3 used, eg "Tuesday, 18
            // August 2026") rather than the plain 'j F Y' this used before.
            'date' => date('l, j F Y', strtotime($data['info']['date'])),
            // 'none': a plain URL, not pre-HTML-escaped - transcript.php's $this->e()
            // does that itself, same as every other href here (see chamberLabel etc.).
            // generate()'s default ('html') already returns "&amp;"-joined args, which
            // $this->e() would then escape a second time into "&amp;amp;".
            'dateUrl' => $dateURL->generate('none'),
            'chamber' => $chamberNames[$data['info']['major']] ?? '',
            // $aboutTitle/$aboutBodyHtml: "What is a Bill?"/"What is the
            // Adjournment?"/etc, or a generic chamber-level fallback - computed once
            // just above, from $section_title, shared with the section-index page's
            // own about card below. See resources/views/hansard/about-debates.php.
            'aboutTitle' => $aboutTitle,
            'aboutBodyHtml' => $aboutBodyHtml,
            'nextPrev' => $nextPrev,
        ]);
    }

    if (!$titles_displayed) {
        if ($usePlatesTemplate) {
            // Same reasoning as the identical check earlier in this file: rendered
            // inside the card below instead, so skip the old stripe-head-2 title bar
            // to avoid showing it twice. Section-index pages like an Adjournment
            // debate's summary (see the $data['subrows'] branch just below) can reach
            // here with $titles_displayed still false, since their $data['rows'] never
            // hits a non-10/11 row to set it true.
            $titles_displayed = true;
        } else {
            $PAGE->stripe_start('head-2');
            ?>
            <h4><?php echo $section_title; ?></h4>
            <h5><?php echo $subsection_title; ?></h5>
            <?php
            $PAGE->stripe_end(array(
                array(
                    'type' => 'nextprev'
                )
            ));
            $titles_displayed = true;
        }
    }

    if (isset($data['subrows']) && $usePlatesTemplate) {
        // A section-index page (eg an Adjournment debate - "Climate Change",
        // "Humanitarian and Refugee Visas" etc, each its own separate debate with its
        // own gid, not content living on this page) - not a transcript, so no
        // hansard/transcript.php card here. One template (section-index-page.php)
        // now owns this whole page's shell (card, pagination, "about this section"
        // sidebar) - orchestration here is just building the item list.
        // $aboutTitle/$aboutBodyHtml: computed once, just above, from
        // $section_title - same lookup the transcript card's sidebar uses. No
        // breadcrumb trail here either - same reasoning as the transcript card
        // above: the pagination bar already links to "all debates on this day".
        $sectionIndexItems = array_map(
            fn($row) => HansardSectionIndexItem::fromSubrow($row, $hansardmajors),
            $data['subrows']
        );

        echo $platesEngine->render('hansard/section-index-page', [
            'chamberLabel' => $chamberLabel,
            'sectionTitle' => $section_title,
            'items' => $sectionIndexItems,
            'nextPrev' => $nextPrev,
            'aboutTitle' => $aboutTitle,
            'aboutBodyHtml' => $aboutBodyHtml,
            // Matches LONGERDATEFORMAT (what the suppressed old h3 used, eg "Tuesday,
            // 18 August 2026") - same format transcript.php's own 'date' uses.
            'date' => date('l, j F Y', strtotime($data['info']['date'])),
            // 'none': a plain URL, not pre-HTML-escaped - section-index-page.php's
            // $this->e() does that itself, same as transcript.php's own 'dateUrl'.
            'dateUrl' => $dateURL->generate('none'),
            'chamber' => $chamberNames[$data['info']['major']] ?? '',
        ]);
    } elseif (isset($data['subrows'])) {
        $PAGE->stripe_start();
        print '<ul>';
        foreach ($data['subrows'] as $row) {
            print '<li>';
            if (isset($row['contentcount']) && $row['contentcount'] > 0) {
                $has_content = true;
            } elseif ($row['htype'] == '11' && $hansardmajors[$row['major']]['type'] == 'other') {
                $has_content = true;
            } else {
                $has_content = false;
            }
            if ($has_content) {
                print '<a href="' . $row['listurl'] . '"><strong>' . $row['body'] . '</strong></a> ';
                // For the "x speeches, x comments" text.
                $moreinfo = array();
                if ($hansardmajors[$row['major']]['type'] != 'other') {
                    // All wrans have 2 speeches, so no need for this.
                    // All WMS have 1 speech
                    $plural = $row['contentcount'] == 1 ? 'speech' : 'speeches';
                    $moreinfo[] = $row['contentcount'] . " $plural";
                }
                if ($row['totalcomments'] > 0) {
                    $plural = $row['totalcomments'] == 1 ? 'comment' : 'comments';
                    $moreinfo[] = $row['totalcomments'] . " $plural";
                }
                if (count($moreinfo) > 0) {
                    print "<small>(" . implode(', ', $moreinfo) . ") </small>";
                }
            } else {
                // Nothing in this item, so no link.
                print '<strong>' . $row['body'] . '</strong>';
            }
            if (isset($row['excerpt'])) {
                print "<br>\n\t\t\t\t\t<span class=\"excerpt-debates\">" . trim_characters($row['excerpt'], 0, 200) . "</span>";
            }
        }
        print '</ul>';
        $PAGE->stripe_end();
    }
} else {
    ?>
    <p>No data to display.</p>

    <?php
}


if (
    !$usePlatesTemplate
    && ($this_page == 'debates' || $this_page == 'whall' || $this_page == 'lordsdebates' || $this_page == 'nidebates')
) {
    // Previous / Index / Next links, if any.

    $PAGE->stripe_start('foot');
    ?>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<?php
    $PAGE->stripe_end(array(
        array(
            'type' => 'nextprev'
        )
    ));
}


// Returns the "See this X in context" link's HTML, or '' outside a single-debate
// page. Used to echo directly inline in the old stripe-based rendering below, and
// to build a HansardSpeechView/HansardProceduralView field in the Plates-rendered
// path (see HansardSpeechView.php) - kept as one function, not duplicated, since
// both paths want exactly the same link.
function context_link($row)
{
    global $this_page;

    if ($this_page != 'debate') {
        return '';
    }

    if ($row['htype'] == '12') {
        $thing = 'speech';
    } else {
        $thing = 'item';
    }

    ob_start();
    ?>
    <p><small><strong><a href="<?php echo $row['listurl']; ?>" class="permalink"
                    title="See this <?php echo $thing; ?> within the entire debate">See this <?php echo $thing; ?> in
                    context</a></strong></small></p>
    <?php
    return ob_get_clean();
}


//$totalcomments, $comment, $commenturl
function generate_commentteaser($row, $major)
{
    // Returns HTML for the one fragment of comment and link for the sidebar.
    // $totalcomments is the number of comments this item has on it.
    // $comment is an array like:
    /* $comment = array (
        'comment_id' => 23,
        'user_id'	=> 34,
        'body'		=> 'Blah blah...',
        'posted'	=> '2004-02-24 23:45:30',
        'username'	=> 'phil'
        )
    */
    // $url is the URL of the item's page, which contains comments.

    global $this_page, $THEUSER, $hansardmajors;

    $html = '';

    if ($hansardmajors[$major]['type'] == 'debate' && $hansardmajors[$major]['page_all'] == $this_page) {

        if ($row['totalcomments'] > 0) {
            $comment = $row['comment'];

            // If the comment is longer than the speech body, we want to trim it
            // to be the same length so they fit next to each other.
            // But the comment typeface is smaller, so we scale things slightly too...
            $targetsize = round(strlen($row['body']) * 0.6);

            if ($targetsize > strlen($comment['body'])) {
                // This comment will fit in its entirety.
                $commentbody = $comment['body'];

                if ($row['totalcomments'] > 1) {
                    $morecount = $row['totalcomments'] - 1;
                    $plural = $morecount == 1 ? 'comment' : 'comments';
                    $linktext = "Read $morecount more $plural";
                }

            } else {
                // This comment needs trimming.
                $commentbody = htmlentities(trim_characters($comment['body'], 0, $targetsize));
                if ($row['totalcomments'] > 1) {
                    $morecount = $row['totalcomments'] - 1;
                    $plural = $morecount == 1 ? 'comment' : 'comments';
                    $linktext = "Continue reading (and $morecount more $plural)";
                } else {
                    $linktext = 'Continue reading';
                }
            }

            $html = '<em>' . htmlentities($comment['username']) . '</em>: ' . prepare_comment_for_display($commentbody);

            if (isset($linktext)) {
                $html .= ' <a href="' . $row['commentsurl'] . '#c' . $comment['comment_id'] . '" title="See any comments posted about this">' . $linktext . '</a>';
            }

            $html .= '<br><br>';
        }

        // 'Add a comment' link.
        if (!$THEUSER->isloggedin()) {
            $URL = new URL('userprompt');
            $URL->insert(array('ret' => $row['commentsurl']));
            $commentsurl = $URL->generate();
        } else {
            $commentsurl = $row['commentsurl'];
        }

        $html = "\t\t\t\t" . '<p class="comment-teaser">' . $html . "</p>\n";
    }

    return $html;
}

$votelinks_so_far = 0;
function generate_votes($votes, $major, $id, $gid)
{
    /*
    Returns HTML for the 'Interesting?' links (debates) or the 'Does this answer
    the question?' links (wrans) in the sidebar.

    We have yes/no, even for debates, for which we only allow people to say 'yes'.

    $votes = => array (
        'user'	=> array (
            'yes'	=> '21',
            'no'	=> '3'
        ),
        'anon'	=> array (
            'yes'	=> '132',
            'no'	=> '30'
        )
    )

    $major is the htype of this item (eg, 12 or 13 for debates, 61 or 62 for wrans).
    $id is an epobject_id.
    */

    global $this_page, $votelinks_so_far;

    // What we return.
    $html = '';

    $URL = new URL($this_page);
    $returl = $URL->generate();

    $VOTEURL = new URL('epvote');
    $VOTEURL->insert(array('v' => '1', 'id' => $id, 'ret' => $returl));

    if (($major == 3 || $major == 8) && ($votelinks_so_far > 0 || preg_match('#r#', $gid))) { # XXX
        // Wrans.
        $yesvotes = $votes['user']['yes'] + $votes['anon']['yes'];
        $novotes = $votes['user']['no'] + $votes['anon']['no'];

        $yesplural = $yesvotes == 1 ? 'person thinks' : 'people think';
        $noplural = $novotes == 1 ? 'person thinks' : 'people think';

        $html .= '<strong>Does this answer the above question?</strong><br>';

        $html .= '<span class="wransvote"><a href="' . $VOTEURL->generate() . '" title="Rate this as answering the question">Yes!</a> ' . $yesvotes . ' ' . $yesplural . ' so!<br>';

        $VOTEURL->insert(array('v' => '0'));

        $html .= '<a href="' . $VOTEURL->generate() . '" title="Rate this as NOT answering the question">No!</a> ' . $novotes . ' ' . $noplural . ' not!</span>';

    } elseif ($major == 1) {
        // Debates.

        /*
        We aren't putting Interesting? buttons in for now...

        $VOTEURL->insert(array('v'=>'1'));
        $totalvotes = $votes['user']['yes'] + $votes['anon']['yes'];
        $plural = $totalvotes == 1 ? 'person thinks' : 'people think';

        $html .= '<a href="' . $VOTEURL->generate() . '" title="Rate this as being interesting">Interesting?</a> ' . $totalvotes . ' ' . $plural . ' so!';
        */

    }

    $votelinks_so_far++;
    $html = "\t\t\t\t<p class=\"vote\">$html</p>\n";
    return $html;

}

/*

Structure of the $data array.

(Notes for the diagram below...)
The 'info' section is metadata about the results set as a whole.

'rows' is an array of items to display, each of which has a set of Hansard object data and more. The item could be a section heading, subsection, speech, written question, procedural, etc, etc.


In the diagram below, 'HansardObjectData' indicates a standard set of key/value
pairs along the lines of:
    'epobject_id'	=> '31502',
    'gid'			=> '2003-12-31.475.3',
    'hdate'			=> '2003-12-31',
    'htype'			=> '12',
    'body'			=> 'A lot of text here...',
    'listurl'		=> '/debates/?id=2003-12-31.475.0#g2003-12-31.475.3',
    'commentsurl'	=> '/debate/?id=2003-12-31.475.3',
    'speaker_id'	=> '931',
    'speaker'		=> array (
        'member_id'		=> '931',
        'first_name'	=> 'Peter',
        'last_name'		=> 'Hain',
        'constituency'	=> 'Neath',
        'party'			=> 'Lab',
        'url'			=> '/member/?id=931'
    ),
    'totalcomments'	=> 5,
    'comment'		=> array (
        'user_id'		=> '45',
        'body'			=> 'Comment text here...',
        'posted'		=> '2003-12-31 23:00:00',
        'username'		=> 'William Thornton',
    ),
    'votes'	=> array (
        'user'	=> array (
            'yes'	=> '21',
            'no'	=> '3'
        ),
        'anon'	=> array (
            'yes'	=> '132',
            'no'	=> '30'
        )
    ),
    etc.

Note: There are two URLs.
    'listurl' is a link to the item in context, in the list view.
    'commentsurl' is the page where we can see this item and all its comments.

Note: Speaker's only there if there is a speaker for this item.


$data = array (

    'info' => array (
        'date'	=> '2003-12-31',
        'text'	=> 'A brief bit of text for a title...',
        'searchwords' => array ('fox', 'hunting')
    ),

    'rows' => array (
        0 => array ( HansardObjectData ),
        1 => array ( HansardObjectData ), etc...
    )
);


*/
?>
