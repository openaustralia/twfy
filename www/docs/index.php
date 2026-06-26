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
?>
<section class="mb-10 rounded-[2rem] bg-white p-8 shadow-lg ring-1 ring-slate-200">
    <div class="max-w-4xl">
        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-material-600">OpenAustralia</p>
        <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">Making Parliament easier to explore</h1>
        <p class="mt-4 text-lg leading-8 text-slate-600">Search Hansard, find your representative, sign up for alerts and keep up with the latest debates from the Australian Parliament.</p>
    </div>
</section>

<div class="grid gap-6 xl:grid-cols-2">

    <?php

    function your_mp_card() {
        global $THEUSER, $MPURL;

        $pc_form = true;
        if ($THEUSER->constituency_is_set()) {
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
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg">
                    <div class="flex items-center gap-3 text-material-600">
                        <span class="material-icons text-3xl">public</span>
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Your Representative</h2>
                            <p class="text-sm text-slate-600">View details for your current MP.</p>
                        </div>
                    </div>
                    <div class="mt-6 space-y-4">
                        <p class="text-slate-700"><strong><a class="text-material-700 hover:text-material-900" href="<?php echo $MPURL->generate(); ?>">Find out more about <?php echo $mpname; ?>, your <?php echo $former ?> Representative</a></strong></p>
                        <p class="text-sm text-slate-500">Not the right address? <a class="font-medium text-material-600 hover:text-material-800" href="<?php echo $CHANGEURL->generate(); ?>">Change postcode</a>.</p>
                    </div>
                </article>
                <?php
            }
        }

        if ($pc_form) {
            ?>
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg">
                <div class="flex items-center gap-3 text-material-600">
                    <span class="material-icons text-3xl">emoji_people</span>
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Find your Representative</h2>
                        <p class="text-sm text-slate-600">Enter your Australian postcode to see your MP.</p>
                    </div>
                </div>
                <form action="<?php echo $MPURL->generate(); ?>" method="get" class="mt-6 space-y-4">
                    <label class="block text-sm font-medium text-slate-700" for="pc">Australian postcode</label>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <input id="pc" name="pc" type="text" maxlength="10" class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 focus:border-material-500 focus:outline-none focus:ring-2 focus:ring-material-100" placeholder="e.g. 2000">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-material-600 px-5 py-3 text-sm font-semibold text-white hover:bg-material-700 focus:outline-none focus:ring-2 focus:ring-material-200">Search</button>
                    </div>
                </form>
            </article>
            <?php
        }
    }

    function search_card() {
        global $SEARCHURL;
        ?>
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg">
            <div class="flex items-center gap-3 text-material-600">
                <span class="material-icons text-3xl">search</span>
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Search Hansard</h2>
                    <p class="text-sm text-slate-600">Look for speeches, questions, committees and topics across Parliament.</p>
                </div>
            </div>
            <form action="<?php echo $SEARCHURL->generate(); ?>" method="get" class="mt-6 space-y-4">
                <label class="sr-only" for="s">Search Hansard</label>
                <input id="s" name="s" type="text" maxlength="100" value="<?php echo htmlspecialchars(get_http_var('keyword')); ?>" class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 focus:border-material-500 focus:outline-none focus:ring-2 focus:ring-material-100" placeholder="Search Parliament...">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
                    <button type="submit" class="rounded-2xl bg-material-600 px-5 py-3 text-sm font-semibold text-white hover:bg-material-700 focus:outline-none focus:ring-2 focus:ring-material-200">Search</button>
                    <?php
                    global $SEARCHLOG;
                    $popular_searches = $SEARCHLOG->popular_recent(10);
                    if (count($popular_searches) > 0) {
                        $lentotal = 0;
                        $correct_amount = [];
                        foreach ($popular_searches as $popular_search) {
                            $len = strlen($popular_search['visible_name']);
                            if ($lentotal + $len > 32) {
                                continue;
                            }
                            $lentotal += $len;
                            array_push($correct_amount, $popular_search['display']);
                        }
                        if (count($correct_amount) > 0) {
                            ?>
                            <p class="text-sm text-slate-500">Popular today: <?php echo implode(', ', $correct_amount); ?></p>
                            <?php
                        }
                    }
                    ?>
                </div>
            </form>
        </article>
        <?php
    }

    function alert_card() {
        ?>
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg">
            <div class="flex items-center gap-3 text-material-600">
                <span class="material-icons text-3xl">notifications</span>
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Email alerts</h2>
                    <p class="text-sm text-slate-600">Get notified when Parliament mentions topics you care about.</p>
                </div>
            </div>
            <div class="mt-6 text-slate-700">
                <?php if (get_http_var('keyword')) { ?>
                    <p><a class="font-semibold text-material-700 hover:text-material-800" href="<?php echo WEBPATH . 'alert?keyword=' . htmlspecialchars(get_http_var('keyword')) . '&only=1'; ?>">Sign up to be emailed when '<?php echo htmlspecialchars(get_http_var('keyword')); ?>' is mentioned in Parliament.</a></p>
                <?php } else { ?>
                    <p><a class="font-semibold text-material-700 hover:text-material-800" href="<?php echo WEBPATH . 'alert/'; ?>">Sign up to be emailed when something relevant to you happens in Parliament.</a></p>
                <?php } ?>
            </div>
        </article>
        <?php
    }

    function recent_card() {
        global $hansardmajors;
        ?>
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg">
            <div class="flex items-center gap-3 text-material-600">
                <span class="material-icons text-3xl">forum</span>
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Recent debates</h2>
                    <p class="text-sm text-slate-600">Read and comment on recent parliamentary debates.</p>
                </div>
            </div>
            <div class="mt-6 space-y-3 text-slate-700">
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
            </div>
        </article>
        <?php
    }

    if (get_http_var('keyword')) {
        search_card();
        alert_card();
        your_mp_card();
        recent_card();
    } else {
        your_mp_card();
        search_card();
        alert_card();
        recent_card();
    }

    ?>
</div>
<?php
$includes = [
    [
        'type' => 'include',
        'content' => 'whatisthissite'
    ]
];
$PAGE->stripe_end($includes);
$PAGE->page_end();
