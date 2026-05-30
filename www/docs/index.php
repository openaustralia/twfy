<?php

/**
 * @file
 */

$this_page = "home";

include_once __DIR__ . "/../includes/easyparliament/init.php";
include_once __DIR__ . "/../includes/easyparliament/member.php";

$PAGE->page_start();

$PAGE->stripe_start();
$message = $PAGE->recess_message();
if ($message != '') {
    print '<p id="warning">' . $message . '</p>';
}

//
// SEARCH AND RECENT HANSARD.

$HANSARDURL = new URL('hansard');
$MPURL = new URL('yourmp');
$PAGE->block_start(['id' => 'intro', 'title' => 'At OpenAustralia.org you can:']);
?>
<ol>

    <?php

    /**
     * Find out more about your MP / Find out more about David Howarth, your MP.
     */
    function your_mp_bullet_point() {
        global $THEUSER, $MPURL;
        print "<li>";
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
                <div class="mt-3 max-w-xl rounded-2xl border border-[#AE967F] bg-gradient-to-b from-white to-[#FDF5F5] p-5 shadow-sm">
                    <p class="m-0 text-base font-semibold text-[#B82E00]">
                        <a class="text-[#B82E00] no-underline hover:underline" href="<?php echo $MPURL->generate(); ?>"><strong>Find out more about <?php echo $mpname; ?>, your
                                    <?php echo $former ?> Representative</strong></a>
                    </p>
                    <p class="mt-3 mb-0">
                        <a
                            href="<?php echo $CHANGEURL->generate(); ?>"
                            class="inline-flex items-center rounded-md border px-3 py-1.5 text-sm font-semibold no-underline"
                            style="background-color:#880101;color:#ffffff;border-color:#880101;"
                        >Change</a>
                    </p>
                </div>
                <?php
            }
        }

        if ($pc_form) { ?>
            <form action="<?php echo $MPURL->generate(); ?>" method="get">
                <div class="mt-3 max-w-xl rounded-2xl border border-[#AE967F] bg-gradient-to-b from-white to-[#FDF5F5] p-5 shadow-sm">
                    <p class="m-0 text-base font-semibold text-[#B82E00]">Find out more about your Representative</p>
                    <div class="mt-3 rounded-lg border border-[#DECEB3] bg-white p-3">
                        <label for="pc" class="block text-sm font-semibold text-[#880101]">Enter your Australian postcode</label>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <input
                                type="text"
                                name="pc"
                                id="pc"
                                size="8"
                                maxlength="10"
                                class="h-10 w-32 rounded-md border border-[#AE967F] bg-white px-3 py-2 text-sm text-[#880101] shadow-sm outline-none focus:border-[#B82E00] focus:ring-2 focus:ring-[#EBA668]"
                            >
                            <button
                                type="submit"
                                class="inline-flex h-10 min-w-24 items-center justify-center rounded-md border px-4 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-[#EBA668]"
                                style="background-color:#880101;color:#ffffff;border-color:#880101;"
                            >Find</button>
                        </div>
                    </div>
                </div>
            </form>
            <?php
        }
        print "</li>";
    }

    /**
     * Search / Search for 'mouse'.
     */
    function search_bullet_point() {
        global $SEARCHURL;
        ?>
        <li>
            <?php
            $SEARCHURL = new URL('search');
            ?>
            <form action="<?php echo $SEARCHURL->generate(); ?>" method="get">
                <p><strong><label
                            for="s">Search<?php echo get_http_var("keyword") ? ' Hansard for \'' . htmlspecialchars(get_http_var("keyword")) . '\'' : '' ?>:</label></strong>
                    <input type="text" name="s" id="s" size="15" maxlength="100" class="text"
                        value="<?php echo htmlspecialchars(get_http_var("keyword")) ?>">&nbsp;&nbsp;<input type="submit"
                        value="SEARCH" class="submit">
                </p>
                <?php
                // Display popular queries.
                global $SEARCHLOG;
                $popular_searches = $SEARCHLOG->popular_recent(10);
                if (count($popular_searches) > 0) {
                    ?>
                    <p>Popular searches today:
                        <?php
                        $lentotal = 0;
                        $correct_amount = [];
                        // Select a number of queries that will fit in the space.
                        foreach ($popular_searches as $popular_search) {
                            $len = strlen($popular_search['visible_name']);
                            if ($lentotal + $len > 32) {
                                continue;
                            }
                            $lentotal += $len;
                            array_push($correct_amount, $popular_search['display']);
                        }
                        print implode(", ", $correct_amount);
                        ?>
                    </p> <?php
                }
                ?>
            </form>
        </li>
        <?php
    }

    /**
     * Sign up to be emailed when something relevant to you happens in Parliament
     * Sign up to be emailed when 'mouse' is mentioned in Parliament.
     */
    function email_alert_bullet_point() {
        if (get_http_var("keyword")) { ?>
            <li>
                <p><a href="<?php echo WEBPATH . "alert?keyword=" . htmlspecialchars(get_http_var('keyword')) ?>&only=1"><strong>Sign
                            up to be emailed when '<?php echo htmlspecialchars(get_http_var('keyword')) ?>' is mentioned in
                            Parliament</strong></a></p>
            </li>
        <?php } else { ?>
            <li>
                <p><a href="<?php echo WEBPATH . "alert/" ?>"><strong>Sign up to be emailed when something relevant to you
                            happens in Parliament</strong></a></p>
            </li>
        <?php }
    }

    /**
     * Comment on (recent debates)
     */
    function comment_on_recent_bullet_point() {
        global $hansardmajors;
        ?>
        <li>
            <p><strong>Read and comment on:</strong></p>

            <?php
            $DEBATELIST = new DEBATELIST();
            $data[1] = $DEBATELIST->most_recent_day();
            $WRANSLIST = new WRANSLIST();
            $data[3] = $WRANSLIST->most_recent_day();
            $WHALLLIST = new WHALLLIST();
            $data[2] = $WHALLLIST->most_recent_day();
            $WMSLIST = new WMSLIST();
            $data[4] = $WMSLIST->most_recent_day();
            $LORDSDEBATELIST = new LORDSDEBATELIST();
            $data[101] = $LORDSDEBATELIST->most_recent_day();
            $NILIST = new NILIST();
            $data[5] = $NILIST->most_recent_day();
            foreach (array_keys($hansardmajors) as $major) {
                if (array_key_exists($major, $data)) {
                    unset($data[$major]['listurl']);
                    if (count($data[$major]) == 0) {
                        unset($data[$major]);
                    }
                }
            }
            major_summary($data);
            ?>
        </li>
        <?php
    }

    if (get_http_var('keyword')) {
        // This is for links from Google adverts, where we want to
        // promote the features relating to their original search higher
        // than "your MP".
        search_bullet_point();
        email_alert_bullet_point();
        your_mp_bullet_point();
        comment_on_recent_bullet_point();
    } else {
        your_mp_bullet_point();
        search_bullet_point();
        email_alert_bullet_point();
        comment_on_recent_bullet_point();
    }

    ?>
</ol>
<?php
$PAGE->block_end();

$includes = [
    [
        'type' => 'include',
        'content' => 'whatisthissite'
    ]
];
$PAGE->stripe_end($includes);
$PAGE->page_end();
