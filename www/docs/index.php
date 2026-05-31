<?php

/**
 * @file
 */

$this_page = "home";

include_once __DIR__ . "/../includes/easyparliament/init.php";
include_once __DIR__ . "/../includes/easyparliament/member.php";

$PAGE->page_start();

$PAGE->stripe_start('side', 'home-page');
$message = $PAGE->recess_message();
if ($message != '') {
    print '<p id="warning">' . $message . '</p>';
}

//
// SEARCH AND RECENT HANSARD.

$HANSARDURL = new URL('hansard');
$MPURL = new URL('yourmp');
$PAGE->block_start(['id' => 'intro', 'title' => 'home']);
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
                <div class="oa-intro-card">
                    <p class="oa-intro-title">
                        <a class="oa-intro-link" href="<?php echo $MPURL->generate(); ?>"><strong>Find out more about <?php echo $mpname; ?>, your
                                    <?php echo $former ?> Representative</strong></a>
                    </p>
                    <p class="oa-intro-actions">
                        <a
                            href="<?php echo $CHANGEURL->generate(); ?>"
                            class="oa-btn-primary"
                        >Change</a>
                    </p>
                </div>
                <?php
            }
        }

        if ($pc_form) { ?>
            <form action="<?php echo $MPURL->generate(); ?>" method="get">
                <div class="oa-intro-card">
                    <p class="oa-intro-title">Find out more about your Representative</p>
                    <div class="oa-intro-subpanel">
                        <label for="pc" class="oa-field-label">Enter your Australian postcode</label>
                        <div class="oa-field-row">
                            <input
                                type="text"
                                name="pc"
                                id="pc"
                                size="8"
                                maxlength="10"
                                class="oa-postcode-input"
                            >
                            <button
                                type="submit"
                                class="oa-btn-primary"
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
                <div class="oa-intro-card">
                    <p class="oa-intro-title"><label
                            for="s">Search<?php echo get_http_var("keyword") ? ' Hansard for \'' . htmlspecialchars(get_http_var("keyword")) . '\'' : '' ?>:</label></p>
                    <div class="oa-intro-subpanel">
                        <div class="oa-field-row">
                            <input type="text" name="s" id="s" size="15" maxlength="100" class="oa-postcode-input"
                                value="<?php echo htmlspecialchars(get_http_var("keyword")) ?>">
                            <input type="submit" value="Search" class="oa-btn-primary">
                        </div>
                    </div>
                <?php
                // Display popular queries.
                global $SEARCHLOG;
                $popular_searches = $SEARCHLOG->popular_recent(10);
                if (count($popular_searches) > 0) {
                    ?>
                    <p class="mt-3 mb-0 text-sm">Popular searches today:
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
                </div>
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
                <div class="oa-intro-card">
                    <p class="oa-intro-title">Sign up to be emailed when '<?php echo htmlspecialchars(get_http_var('keyword')) ?>' is mentioned in Parliament</p>
                    <p class="oa-intro-actions">
                        <a
                            href="<?php echo WEBPATH . "alert?keyword=" . htmlspecialchars(get_http_var('keyword')) ?>&only=1"
                            class="oa-btn-primary"
                        >Create email alert</a>
                    </p>
                </div>
            </li>
        <?php } else { ?>
            <li>
                <div class="oa-intro-card">
                    <p class="oa-intro-title">Sign up to be emailed when something relevant to you happens in Parliament</p>
                    <p class="oa-intro-actions">
                        <a
                            href="<?php echo WEBPATH . "alert/" ?>"
                            class="oa-btn-primary"
                        >Create email alert</a>
                    </p>
                </div>
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
            <?php
            # THE HOUSE
            $DEBATELIST = new DEBATELIST();
            $data[1] = $DEBATELIST->most_recent_day();

            # THE SENATE
            $SENATEDEBATELIST = new SENATEDEBATELIST();
            $data[101] = $SENATEDEBATELIST->most_recent_day();

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
