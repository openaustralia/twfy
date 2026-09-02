<?php

/**
 * @file
 * Front page. Restructured to borrow the layout of Donna's homepage mockup
 * (https://kattekrab.github.io/openausmockup.html) - hero + search, a 3-feature row,
 * a "Latest Activity in Parliament" two-column feed, and a charity/about callout at
 * the bottom (where "What's all this about?" moved to - see about_this_site_card())
 * - kept in our own navy/teal/slate palette (PR #225) rather than the mockup's blue.
 *
 * Markup lives in Plates templates under resources/views/front/ - see
 * FrontPageView.php for the plain-data-in-plain-data-out logic that feeds them
 * (mirrors HansardSpeechView.php's split for the debate transcript page). The
 * functions below are orchestration only: DB/global access and building the data
 * each template needs, no HTML of their own beyond what's still directly
 * DB-coupled (latest_activity_items()) and hasn't been worth pulling out on its own.
 */

$this_page = "home";

include_once __DIR__ . "/../includes/easyparliament/init.php";
include_once __DIR__ . "/../includes/easyparliament/member.php";
include_once __DIR__ . "/../includes/easyparliament/FrontPageView.php";
include_once __DIR__ . "/../includes/easyparliament/HansardSpeechView.php";

$PAGE->page_start();

// This page has no real sidebar content, so skip the (otherwise empty) sidebar column.
$PAGE->stripe_start('side', '', true);
$message = $PAGE->recess_message();
if ($message != '') {
    print '<p id="warning" class="!box-border !w-[calc(100vw-2rem)]">' . $message . '</p>';
}

// get_plates_engine() (utility.php): shared with page.php's own every-page footer
// render, rather than a second, identically-configured Engine here.
$platesEngine = get_plates_engine();

/**
 * Display the homepage Hansard search form.
 */
function search_hero() {
    global $platesEngine;

    $SEARCHURL = new URL('search');
    echo $platesEngine->render('front/search-hero', [
        'searchUrl' => $SEARCHURL->generate(),
        'keyword' => get_http_var('keyword'),
        'popularSearchesLabel' => popular_searches_label(),
    ]);
}

/**
 * FrontPageView::popularSearchesLabel()'s input - the SEARCHLOG DB lookup itself,
 * kept here rather than in FrontPageView.php since that class is DB-free by design.
 */
function popular_searches_label() {
    global $SEARCHLOG;
    return FrontPageView::popularSearchesLabel($SEARCHLOG->popular_recent(10));
}

/**
 * The mockup's 3-icon feature row ("Track Your Reps" / "Read the Debates" / "Get
 * Free Email Alerts") - same three things the old "At OpenAustralia.org you can:"
 * bullet list offered (find-your-MP, search, email alerts), just laid out as the
 * mockup's icon-circle columns instead of a plain <ol>. The postcode-lookup and
 * logged-in/keyword-aware branching are unchanged, still resolved here (mp_block()
 * below) - only the surrounding markup moved to resources/views/front/.
 */
function feature_row() {
    global $platesEngine;

    $HANSARDURL = new URL('hansard');
    $keyword = get_http_var('keyword');
    echo $platesEngine->render('front/feature-row', [
        'mpBlock' => mp_block(),
        'hansardUrl' => $HANSARDURL->generate('none'),
        'emailAlertUrl' => FrontPageView::emailAlertUrl($keyword),
        'emailAlertText' => FrontPageView::emailAlertText($keyword),
    ]);
}

/**
 * Resolves $THEUSER's constituency (a real DB lookup via MEMBER, when set) into the
 * plain array FrontPageView::mpBlock() expects - null when there's no MP to show
 * (no constituency set, or the resolved MEMBER wasn't valid).
 */
function mp_block() {
    global $THEUSER, $MPURL;

    $member = null;
    if ($THEUSER->constituency_is_set()) {
        // (We don't allow the user to search for a postcode if they
        // already have one set in their prefs.)
        $MEMBER = new MEMBER(['constituency' => $THEUSER->constituency()]);
        if ($MEMBER->valid) {
            $left_house = $MEMBER->left_house();
            $member = [
                'first_name' => $MEMBER->first_name(),
                'last_name' => $MEMBER->last_name(),
                'still_in_house' => $left_house[1]['date'] == '9999-12-31',
            ];
        }
    }

    $CHANGEURL = new URL('userchangepc');
    return FrontPageView::mpBlock($member, $MPURL->generate(), $CHANGEURL->generate());
}

/**
 * "Latest Activity in Australian Parliament" - the two-column (House/Senate) full
 * agenda for the most recent sitting day: every top-level section (a Bill, a
 * Committee's report, Question Time, ...), in the order they actually happened -
 * start of the day first, not the mockup's own "last few things said" feed. Closer
 * to the original TWFY homepage's major_summary() (utility.php, still used as-is by
 * mobile.php/hansard/index.php - no LIMIT there either) than to the mockup itself;
 * each item also shows its first speaker (avatar/name/party-electorate), which
 * major_summary() never did.
 */
function latest_activity() {
    ?>
    <?php
    // max-w-5xl mx-auto: the mockup caps this section at its own container class
    // (responsive, ~1280px at this width) rather than letting it run the full content
    // width the way this section did before - matches the cap already used on the
    // feature row above and keeps the two columns from stretching so wide the cards
    // read as sparse.
    ?>
    <section class="mx-4 mb-12 md:mx-8">
        <h2 class="mb-1 text-center text-3xl font-bold text-slate-900">Latest Activity in Australian Parliament</h2>
        <p class="mb-8 text-center text-slate-600">The full day's business from the House and the Senate.</p>
        <div class="mx-auto grid max-w-5xl grid-cols-1 gap-8 md:grid-cols-2">
            <?php
            // text-green-700/text-red-700: the House and Senate chambers' own actual
            // colours (green benches, red benches) - a real, recognisable Australian
            // parliamentary convention, not an arbitrary choice.
            ?>
            <?php latest_activity_column(1, 'House of Representatives', 'text-green-700'); ?>
            <?php latest_activity_column(101, 'Senate', 'text-red-700'); ?>
        </div>
    </section>
    <?php
}

/**
 * One "Latest Activity" column for a single hansard major (1 = House, 101 = Senate -
 * the two this fork actually parses, same pair the debate transcript page's own
 * $usePlatesTemplate check uses).
 */
function latest_activity_column($major, $chamberName, $iconColorClass) {
    global $hansardmajors, $platesEngine;

    $LIST = $major == 101 ? new LORDSDEBATELIST() : new DEBATELIST();
    $recent = $LIST->most_recent_day();
    if (!isset($recent['hdate'])) {
        return;
    }

    $items = latest_activity_items($LIST, $major, $recent['hdate']);
    $DAYURL = new URL($hansardmajors[$major]['page_all']);
    $DAYURL->insert(['d' => $recent['hdate']]);

    echo $platesEngine->render('front/latest-activity-column', [
        'chamberName' => $chamberName,
        'iconColorClass' => $iconColorClass,
        // House and Senate don't always share a most recent sitting day (eg one
        // chamber rises for the week before the other), so this is each column's
        // own $recent['hdate'], not one shared page-level date.
        'date' => format_date($recent['hdate'], SHORTDATEFORMAT),
        'items' => $items,
        'dayUrl' => $DAYURL->generate('none'),
        'viewAllLabel' => $major == 101 ? 'the Senate' : 'the House',
    ]);
}

/**
 * The first $maxItemsShown top-level debate sections for one hansard major on one
 * date, in the order they happened (start of day first) - same section_id=0 query
 * major_summary() (utility.php) itself runs (unlimited there, just scoped to one
 * major instead of the House's whole 1/2/3/4/5 group), capped here since a busy
 * sitting day can run to 20-30 sections and the column's own "View all from the
 * House/Senate" link (see latest_activity_column()) already exists as the place
 * to see the rest - showing literally everything on the homepage read as far too
 * busy to scan (real feedback after seeing it live, not a guess). $LIST is the
 * DEBATELIST/LORDSDEBATELIST latest_activity_column() already built - reusing its
 * own _get_speaker() (hansardlist.php) for each item's speakers means a speaker
 * who appears in several sections in one day (eg the Speaker themself) is only
 * queried once, not once per section.
 *
 * There's no single "item url" any more - the section heading itself
 * ("Bills"/"Committees"/...) is just a label now, not a link. Every actual link
 * is on something specific: each topic to its own subsection page (see
 * FrontPageView::summarizeTopics()), each speaker chip to where they first speak
 * (same #anchor convention as HANSARDLIST::_get_listurl()'s own htype=12 branch -
 * the parent subsection's own gid as the page, the speech's own gid as a
 * fragment).
 *
 * A section can have many distinct speakers (a whole day's Bills debate, say) -
 * capped at $maxSpeakersShown, same "+N more" convention as
 * resources/views/hansard/speaker-chips.php's own $moreCount. Only the capped
 * speakers are actually resolved to full member data - the "how many more" count
 * comes from the id list itself, cheap to fetch in full. Not every section has a
 * speaker at all (eg a bare procedural heading with no htype=12 rows under it) -
 * 'speakers' stays [] for those.
 */
function latest_activity_items($LIST, $major, $date) {
    global $hansardmajors;

    $maxItemsShown = 10;
    $maxSpeakersShown = 1;
    $maxTopicsShown = 5;
    $items = [];
    $q = parlDBQuery('SELECT hansard.epobject_id, body FROM hansard, epobject
			WHERE hansard.epobject_id = epobject.epobject_id AND section_id=0
			AND hdate=?
			AND major=' . intval($major) . '
			ORDER BY hpos ASC LIMIT ' . intval($maxItemsShown), $date);

    $LISTURL = new URL($hansardmajors[$major]['page_all']);

    for ($i = 0; $i < $q->rows(); $i++) {
        $section_epobject_id = $q->field($i, 'epobject_id');

        // Topics: a subsection links straight to its own page (no #anchor needed -
        // unlike a speech, the subsection itself IS the whole page) - same as
        // HANSARDLIST::_get_listurl()'s own htype=11 branch.
        $topicsQ = parlDBQuery('SELECT epobject.body, hansard.gid FROM hansard, epobject
				WHERE hansard.epobject_id = epobject.epobject_id
				AND hansard.section_id=' . intval($section_epobject_id) . '
				AND hansard.htype=11
				ORDER BY hansard.hpos ASC');
        $subsections = [];
        for ($t = 0; $t < $topicsQ->rows(); $t++) {
            $topicURL = clone $LISTURL;
            $topicURL->insert(['id' => fix_gid_from_db($topicsQ->field($t, 'gid'))]);
            $subsections[] = ['title' => $topicsQ->field($t, 'body'), 'url' => $topicURL->generate('none')];
        }
        $topics = FrontPageView::summarizeTopics($subsections, $maxTopicsShown);

        // Speakers: fetch every htype=12 row's speaker_id/gid/parent-subsection gid
        // up front (ordered by hpos, so the first occurrence per speaker_id - kept
        // by FrontPageView::firstSpeechBySpeaker(), which the raw rows are handed
        // to below - is genuinely their first speech), then only resolve full
        // member data for the ones actually shown.
        $speechRowsQ = parlDBQuery('SELECT hansard.speaker_id, hansard.gid AS speech_gid, sub.gid AS subsection_gid
				FROM hansard
				JOIN hansard AS sub ON hansard.subsection_id = sub.epobject_id
				WHERE hansard.section_id=' . intval($section_epobject_id) . '
				AND hansard.htype=12 AND hansard.speaker_id != 0
				ORDER BY hansard.hpos ASC');

        $speechRows = [];
        for ($r = 0; $r < $speechRowsQ->rows(); $r++) {
            $speechRows[] = [
                'speaker_id' => $speechRowsQ->field($r, 'speaker_id'),
                'speech_gid' => $speechRowsQ->field($r, 'speech_gid'),
                'subsection_gid' => $speechRowsQ->field($r, 'subsection_gid'),
            ];
        }
        $firstSpeechBySpeaker = FrontPageView::firstSpeechBySpeaker($speechRows);

        $speakers = [];
        $shownSpeakerIds = array_slice(array_keys($firstSpeechBySpeaker), 0, $maxSpeakersShown);
        foreach ($shownSpeakerIds as $speakerId) {
            $speakerData = $LIST->_get_speaker($speakerId, $date);
            if (empty($speakerData)) {
                continue;
            }
            $entry = HansardSpeechView::speakerEntry($speakerData);
            $firstSpeech = $firstSpeechBySpeaker[$speakerId];
            $speechURL = clone $LISTURL;
            $speechURL->insert(['id' => fix_gid_from_db($firstSpeech['subsection_gid'])]);
            $entry->firstSpeechUrl = $speechURL->generate('none') . '#g' . gid_to_anchor(fix_gid_from_db($firstSpeech['speech_gid']));
            $speakers[] = $entry;
        }
        // count($speakers), not count($shownSpeakerIds): the latter is attempted
        // lookups, not what's actually rendered - _get_speaker() can come back empty
        // for one of them (skipped above), which would otherwise undercount "+N more"
        // by however many lookups failed.
        $moreSpeakersCount = max(0, count($firstSpeechBySpeaker) - count($speakers));

        $items[] = [
            'title' => $q->field($i, 'body'),
            'speakers' => $speakers,
            'moreSpeakersCount' => $moreSpeakersCount,
            'topics' => $topics['topics'],
            'moreTopicsCount' => $topics['moreCount'],
        ];
    }

    return $items;
}

/**
 * "We're a small charity with a big mission." - the mockup's own bottom-of-page
 * charity callout, using the content of sidebars/whatisthissite.php's shared khaki
 * "What's all this about?" block (also used on 404.php, index-election.php,
 * gadget/index.php and mobile.php, so left untouched there - see the note on
 * latest_activity() above: duplicate short, static content into new-design markup
 * rather than restyle a template several unrelated pages still rely on) - this site's
 * donate/about messaging was already basically that card, just under a different
 * heading.
 */
function about_this_site_card() {
    global $platesEngine;

    $URL = new URL('about');
    echo $platesEngine->render('front/about-this-site', ['aboutUrl' => $URL->generate()]);
}

$MPURL = new URL('yourmp');

search_hero();
feature_row();
latest_activity();
about_this_site_card();

$PAGE->stripe_end();
$PAGE->page_end();
