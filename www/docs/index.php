<?php

/**
 * @file
 * Front page. Restructured to borrow the layout of Donna's homepage mockup
 * (https://kattekrab.github.io/openausmockup.html) - hero + search, a 3-feature row,
 * a "Latest Activity in Parliament" two-column feed, and a charity/about callout at
 * the bottom (where "What's all this about?" moved to - see about_this_site_card())
 * - kept in our own navy/teal/slate palette (PR #225) rather than the mockup's blue.
 */

$this_page = "home";

include_once __DIR__ . "/../includes/easyparliament/init.php";
include_once __DIR__ . "/../includes/easyparliament/member.php";

$PAGE->page_start();

// This page has no real sidebar content, so skip the (otherwise empty) sidebar column.
$PAGE->stripe_start('side', '', true);
$message = $PAGE->recess_message();
if ($message != '') {
    print '<p id="warning" class="!box-border !w-[calc(100vw-2rem)]">' . $message . '</p>';
}

/**
 * Display the homepage Hansard search form.
 */
function search_hero() {
    $SEARCHURL = new URL('search');
    $keyword = get_http_var('keyword');
    ?>
    <section class="mx-4 mb-8 box-border rounded-lg bg-[#26343b] p-6 text-white shadow-md md:mx-8 md:p-8">
        <h2 class="!text-white mb-2 text-2xl font-semibold">🔎 Search Hansard</h2>
        <?php
        // text-lg: matches the body-copy size introduced on the debate transcript
        // page (resources/views/hansard/speech.php) - was unstyled before (so
        // whatever size global.css's 80%-scaled body default worked out to).
        ?>
        <p class="mb-5 text-lg text-slate-200">Find speeches, debates and decisions from Australia's Federal Parliament.</p>
        <form action="<?php echo $SEARCHURL->generate(); ?>" method="get" class="flex flex-col gap-3 sm:flex-row">
            <label for="hero-search" class="sr-only">Search Hansard</label>
            <input type="text" name="s" id="hero-search" maxlength="100" class="!m-0 !min-w-0 !w-full box-border rounded border-0 px-4 py-3 text-base text-slate-900 shadow-sm" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Search by topic, person or phrase">
            <?php
            // bg-teal-700, not teal-600: white-on-teal-600 is 3.74:1 (axe-core
            // color-contrast, needs 4.5:1 for normal-weight text) - teal-700 clears it.
            ?>
            <button type="submit" class="rounded bg-teal-700 px-6 py-3 font-semibold text-white shadow-sm hover:bg-teal-600">Search</button>
        </form>
        <?php popular_searches(); ?>
    </section>
    <?php
}

/**
 * Display popular searches that fit in the homepage search hero.
 */
function popular_searches() {
    global $SEARCHLOG;
    $popular_searches = $SEARCHLOG->popular_recent(10);
    if (count($popular_searches) == 0) {
        return;
    }

    $lentotal = 0;
    $correct_amount = [];
    foreach ($popular_searches as $popular_search) {
        $len = strlen($popular_search['visible_name']);
        if ($lentotal + $len > 32) {
            continue;
        }
        $lentotal += $len;
        $correct_amount[] = $popular_search['display'];
    }
    print '<p class="!mb-0 !mt-4 text-sm text-slate-200 [&_a]:!text-teal-200 [&_a:hover]:!text-white">Popular searches today: ' . implode(', ', $correct_amount) . '</p>';
}

/**
 * The mockup's 3-icon feature row ("Track Your Reps" / "Read the Debates" / "Get
 * Free Email Alerts") - same three things the old "At OpenAustralia.org you can:"
 * bullet list offered (find-your-MP, search, email alerts), just laid out as the
 * mockup's icon-circle columns instead of a plain <ol>. The postcode-lookup and
 * logged-in/keyword-aware branching are unchanged, still inside your_mp_bullet_point()
 * and email_alert_bullet_point() below - only the surrounding markup is new.
 */
function feature_row() {
    ?>
    <section class="mx-4 mb-12 rounded-2xl bg-slate-50 px-4 py-10 md:mx-8 md:px-8">
        <div class="mx-auto grid max-w-5xl grid-cols-1 gap-6 md:grid-cols-3">
            <div class="rounded-2xl bg-white p-6 text-center shadow-md md:p-8">
                <?php feature_icon('👤'); ?>
                <?php your_mp_bullet_point(); ?>
            </div>
            <?php
            // The whole card links to the same place as the "Debates" nav item
            // (metadata.php's 'hansard' page) - !-prefixed classes since a plain
            // colour class loses to layout.css's legacy "a:link" rule (specificity
            // 0,1,1 beats a class selector's 0,1,0), same fix used throughout the
            // debate transcript page's own templates this session.
            $HANSARDURL = new URL('hansard');
            ?>
            <a href="<?php echo htmlspecialchars($HANSARDURL->generate('none')) ?>"
                class="block rounded-2xl bg-white p-6 text-center shadow-md !text-inherit !no-underline hover:shadow-lg md:p-8">
                <?php feature_icon('📜'); ?>
                <h3 class="mb-2 text-lg font-semibold text-slate-900">Read the Debates</h3>
                <p class="text-slate-600">Access and search the complete record of what's said in the House of
                    Representatives and the Senate.</p>
            </a>
            <div class="rounded-2xl bg-white p-6 text-center shadow-md md:p-8">
                <?php feature_icon('✉️'); ?>
                <?php email_alert_bullet_point(); ?>
            </div>
        </div>
    </section>
    <?php
}

/**
 * One feature-row icon circle.
 */
function feature_icon($emoji) {
    ?>
    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-teal-100 text-2xl"><?php echo $emoji ?></div>
    <?php
}

/**
 * Find out more about your MP / Find out more about David Howarth, your MP - the
 * first of the feature-row's three columns.
 */
function your_mp_bullet_point() {
    global $THEUSER, $MPURL;
    $pc_form = true;
    if ($THEUSER->constituency_is_set()) {
        // (We don't allow the user to search for a postcode if they
        // already have one set in their prefs.)

        $MEMBER = new MEMBER(['constituency' => $THEUSER->constituency()]);
        if ($MEMBER->valid) {
            $pc_form = false;
            $CHANGEURL = new URL('userchangepc');
            $mpname = $MEMBER->first_name() . ' ' . $MEMBER->last_name();
            $former = "";
            $left_house = $MEMBER->left_house();
            if ($left_house[1]['date'] != '9999-12-31') {
                $former = 'former';
            }
            ?>
            <h3 class="mb-2 text-lg font-semibold text-slate-900">Your Representative</h3>
            <p class="text-slate-600"><a href="<?php echo $MPURL->generate(); ?>" class="font-semibold !text-teal-800 hover:!text-teal-600">Find out
                    more about <?php echo $mpname; ?>, your <?php echo $former ?> Federal Representative</a>
                (<a href="<?php echo $CHANGEURL->generate(); ?>" class="!text-teal-800 hover:!text-teal-600">Change</a>)</p>
            <?php
        }
    }

    if ($pc_form) { ?>
        <h3 class="mb-2 text-lg font-semibold text-slate-900">Track Your Reps</h3>
        <p class="mb-3 text-slate-600">Find out who your local MP and Senators are, see their speeches, and track
            their activity in Parliament.</p>
        <form action="<?php echo $MPURL->generate(); ?>" method="get" class="flex justify-center gap-2">
            <label for="pc" class="sr-only">Enter your Australian postcode here</label>
            <input type="text" name="pc" id="pc" size="8" maxlength="10" placeholder="Postcode"
                class="!m-0 w-24 rounded border border-solid border-slate-300 px-3 py-2 text-slate-900">
            <input type="submit" value="Go"
                class="!m-0 rounded bg-teal-700 px-4 py-2 font-semibold text-white hover:bg-teal-600">
        </form>
        <?php
    }
}

/**
 * Sign up to be emailed when something relevant to you happens in Parliament
 * Sign up to be emailed when 'mouse' is mentioned in Parliament - the feature-row's
 * third column.
 */
function email_alert_bullet_point() {
    ?>
    <h3 class="mb-2 text-lg font-semibold text-slate-900">Get Free Email Alerts</h3>
    <?php if (get_http_var("keyword")) { ?>
        <p class="mb-3 text-slate-600">Get notified when '<?php echo htmlspecialchars(get_http_var('keyword')) ?>' is mentioned in Parliament.</p>
        <a class="font-semibold !text-teal-800 hover:!text-teal-600" href="<?php echo WEBPATH . "alert?keyword=" . htmlspecialchars(get_http_var('keyword')) ?>&only=1">Create and manage email alerts</a>
    <?php } else { ?>
        <p class="mb-3 text-slate-600">Sign up to get an email whenever your representative speaks or a keyword you
            care about is mentioned.</p>
        <a class="font-semibold !text-teal-800 hover:!text-teal-600" href="<?php echo WEBPATH . "alert/" ?>">Create and manage email alerts</a>
    <?php }
}

/**
 * "Latest Activity in Parliament" - the mockup's two-column (House/Senate) feed of
 * recent debates, each with its first speaker and time, not just major_summary()'s
 * (utility.php) plain list of section titles/links. major_summary() is shared by
 * mobile.php and hansard/index.php too, so left as-is there; this runs its own small
 * query instead of changing what those pages get.
 */
function latest_activity() {
    ?>
    <section class="mx-4 mb-12 md:mx-8">
        <h2 class="mb-1 text-center text-3xl font-bold text-slate-900">Latest Activity in Parliament</h2>
        <p class="mb-8 text-center text-slate-600">Recent debates from the House and the Senate.</p>
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
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
    global $hansardmajors;

    $LIST = $major == 101 ? new LORDSDEBATELIST() : new DEBATELIST();
    $recent = $LIST->most_recent_day();
    if (!isset($recent['hdate'])) {
        return;
    }

    $items = latest_activity_items($major, $recent['hdate'], 5);
    $DAYURL = new URL($hansardmajors[$major]['page_all']);
    $DAYURL->insert(['d' => $recent['hdate']]);
    ?>
    <div>
        <?php
        // Heroicons "building-library" (MIT) - same icon the debate transcript page
        // uses next to the chamber name (see resources/views/hansard/transcript.php).
        ?>
        <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold text-slate-900">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                class="h-5 w-5 <?php echo $iconColorClass ?>" aria-hidden="true">
                <path d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
            </svg>
            <?php echo htmlspecialchars($chamberName) ?>
        </h3>
        <div class="space-y-3">
            <?php foreach ($items as $item): ?>
                <a href="<?php echo htmlspecialchars($item['url']) ?>"
                    class="block rounded-lg bg-slate-50 p-4 !no-underline hover:bg-slate-100">
                    <p class="font-semibold !text-slate-900"><?php echo $item['title'] ?></p>
                    <?php if ($item['speaker']): ?>
                        <p class="text-sm text-slate-600">Spoken by: <?php echo htmlspecialchars($item['speaker']) ?></p>
                    <?php endif; ?>
                    <?php
                    // text-slate-600, not slate-400: 2.45:1 on the card's slate-50
                    // background (axe-core color-contrast, needs 4.5:1).
                    ?>
                    <p class="text-xs text-slate-600"><?php echo htmlspecialchars($item['when']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
        <a href="<?php echo htmlspecialchars($DAYURL->generate('none')) ?>" class="mt-4 inline-block font-semibold !text-teal-800 hover:!text-teal-600 !no-underline">View
            all from <?php echo $major == 101 ? 'the Senate' : 'the House' ?> &rarr;</a>
    </div>
    <?php
}

/**
 * The $limit most recent debate sections for one hansard major on one date, each
 * with its first speaker and time. One extra small query per section (fine at this
 * $limit) - major_summary() (utility.php) only ever fetches title/gid, since none of
 * its other callers need a speaker name.
 */
function latest_activity_items($major, $date, $limit) {
    global $hansardmajors;

    $items = [];
    $q = parlDBQuery('SELECT hansard.epobject_id, body, gid FROM hansard, epobject
			WHERE hansard.epobject_id = epobject.epobject_id AND section_id=0
			AND hdate="' . $date . '"
			AND major=' . intval($major) . '
			ORDER BY hpos DESC LIMIT ' . intval($limit));

    $LISTURL = new URL($hansardmajors[$major]['page_all']);

    for ($i = 0; $i < $q->rows(); $i++) {
        $section_epobject_id = $q->field($i, 'epobject_id');
        $gid = fix_gid_from_db($q->field($i, 'gid'));

        $speakerQ = parlDBQuery('SELECT hansard.htime, member.title, member.first_name, member.last_name
				FROM hansard, member
				WHERE hansard.speaker_id = member.member_id
				AND hansard.section_id=' . intval($section_epobject_id) . '
				AND hansard.htype=12
				ORDER BY hansard.hpos LIMIT 1');

        $speaker = '';
        $when = format_date($date, SHORTDATEFORMAT);
        if ($speakerQ->rows()) {
            $speaker = ucfirst(member_full_name(1, $speakerQ->field(0, 'title'), $speakerQ->field(0, 'first_name'), $speakerQ->field(0, 'last_name'), ''));
            $htime = $speakerQ->field(0, 'htime');
            if ($htime && $htime != '00:00:00') {
                $when .= ', ' . format_time($htime, TIMEFORMAT);
            }
        }

        $URL = clone $LISTURL;
        $URL->insert(['id' => $gid]);
        $items[] = [
            'title' => $q->field($i, 'body'),
            'speaker' => $speaker,
            'when' => $when,
            'url' => $URL->generate('none'),
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
    $URL = new URL('about');
    $abouturl = $URL->generate();
    ?>
    <section class="mx-4 mb-12 rounded-2xl bg-slate-50 px-4 py-12 md:mx-8 md:px-8">
        <div class="mx-auto max-w-2xl rounded-2xl bg-white p-6 text-center shadow-md md:p-10">
            <h2 class="mb-4 text-2xl font-bold text-slate-900">We're a small charity with a big mission.</h2>
            <div class="space-y-4 text-lg text-slate-700">
                <p><strong>Hansard, made findable.</strong> Searchable transcripts of the Australian Federal Parliament.</p>
                <p><a href="<?php echo $abouturl; ?>" class="!text-teal-800 hover:!text-teal-600" title="link to About Us page">OpenAustralia.org.au</a> is
                    an independent collection of Hansard, the official record of the Australian Federal Parliament.
                    The <a href="https://www.oaf.org.au" class="!text-teal-800 hover:!text-teal-600">OpenAustralia Foundation</a> is a public
                    digital online library; independent and strictly non-partisan. As a <a href="https://www.acnc.gov.au/charity/55c2c06e21ac71e9359a0590b9fc100e" class="!text-teal-800 hover:!text-teal-600">registered
                        charity</a>, it is powered by donations from people like you.</p>
            </div>
            <div class="mt-6">
                <a class="inline-block rounded bg-teal-700 px-6 py-3 font-semibold !text-white !no-underline shadow-sm hover:bg-teal-600" href="https://donate.oaf.org.au/">Support OpenAustralia.org</a>
            </div>
        </div>
    </section>
    <?php
}

$MPURL = new URL('yourmp');

search_hero();
feature_row();
latest_activity();
about_this_site_card();

$PAGE->stripe_end();
$PAGE->page_end();
