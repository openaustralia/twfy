<?php

/**
 * @file
 */

include_once __DIR__ . "/../postcode.php";
include_once __DIR__ . "/glossary.php";

use Illuminate\Database\Capsule\Manager as DB;
use OpenAustralia\TWFY\Models\Member as MemberModel;

/**
 *
 */
class MEMBER {


    public $valid = false;
    public $member_id;
    public $person_id;
    public $first_name;
    public $title;
    public $last_name;
    public $constituency;
    public $party;
    public $other_parties;
    public $houses = [];
    public $entered_house = [];
    public $left_house = [];
    public $extra_info = [];
    /**
     * Is this MP THEUSERS's MP?
     */
    public $the_users_mp = false;
    public $canonical = true;
    /**
     * Which house we should display this person in.
     */
    public $house_disp = 0;

    /**
     * Mapping member table 'house' numbers to text.
     */
    public $houses_pretty = [
        0 => 'Royal Family',
        1 => 'House of Commons',
        2 => 'House of Lords',
        3 => 'Northern Ireland Assembly',
        4 => 'Scottish Parliament',
    ];

    /**
     * Mapping member table reasons to text.
     */
    public $reasons = [
        'became_peer' => 'Became peer',
        'by_election' => 'Byelection',
        'changed_party' => 'Changed party',
        'declared_void' => 'Declared void',
        'died' => 'Died',
        'disqualified' => 'Disqualified',
        'general_election' => 'Federal election',
        'general_election_standing' => ['Federal election (standing again)', 'Federal election (stood again)'],
        'general_election_not_standing' => 'did not stand for re-election',
        'reinstated' => 'Reinstated',
        'resigned' => 'Resigned',
        'still_in_office' => 'Still in office',
        'dissolution' => 'Dissolved for election',
        // Scottish Parliament.
        'regional_election' => 'Election',
        'replaced_in_region' => 'Appointed, regional replacement',

    ];

    /**
     *
     */
    public function __construct($args) {
        // $args is a hash like one of:
        // member_id         => 237
        // person_id         => 345
        // constituency     => 'Braintree'
        // postcode            => 'e9 6dw'

        // If just a constituency we currently just get the current member for
        // that constituency.

        global $PAGE, $this_page;

        $person_id = '';
        if (isset($args['member_id']) && is_numeric($args['member_id'])) {
            $person_id = $this->member_id_to_person_id($args['member_id']);
        } elseif (isset($args['name'])) {
            $con = $args['constituency'] ?? '';
            $person_id = $this->name_to_person_id($args['name'], $con);
        } elseif (isset($args['constituency'])) {
            $person_id = $this->constituency_to_person_id($args['constituency']);
        } elseif (isset($args['postcode'])) {
            $person_id = $this->postcode_to_person_id($args['postcode']);
        } elseif (isset($args['person_id']) && is_numeric($args['person_id'])) {
            $person_id = $args['person_id'];
        }

        if (!$person_id) {
            $this->valid = false;
            return;
        }

        if (is_array($person_id)) {
            if ($this_page == 'peer') {
                // Hohoho, how long will I get away with this for?
                // Not very long, it made Lord Patel go wrong.
                $person_id = $person_id[0];
            } else {
                $this->valid = false;
                $this->person_id = $person_id;
                return;
            }
        }
        $this->valid = true;

        // Get the data.
        $rows = MemberModel::where('person_id', $person_id)
          ->orderBy('left_house', 'desc')
          ->orderBy('house')
          ->get();

        if ($rows->isEmpty()) {
            $this->valid = false;
            return;
        }

        $this->house_disp = 0;
        foreach ($rows as $row) {
            $house = $row->house;
            if (!in_array($house, $this->houses)) {
                $this->houses[] = $house;
            }
            $const = $row->constituency;
            $party = $row->party;
            $entered_house = $row->entered_house;
            $left_house = $row->left_house;
            $entered_reason = $row->entered_reason;
            $left_reason = $row->left_reason;

            $entered_time = strtotime($entered_house);
            $left_time = strtotime($left_house);
            if ($left_time === -1) {
                $left_time = false;
            }

            if (!isset($this->entered_house[$house]) || $entered_time < $this->entered_house[$house]['time']) {
                $this->entered_house[$house] = [
                    'time' => $entered_time,
                    'date' => $entered_house,
                    'date_pretty' => $this->entered_house_text($entered_house),
                    'reason' => $this->entered_reason_text($entered_reason),
                ];
            }

            if (!isset($this->left_house[$house])) {
                $this->left_house[$house] = [
                    'time' => $left_time,
                    'date' => $left_house,
                    'date_pretty' => $this->left_house_text($left_house),
                    'reason' => $this->left_reason_text($left_reason),
                    'constituency' => $const,
                    'party' => $this->party_text($party)
                ];
            }

            // The Monarch.
            if (
                $house == 0
                // MSPs and.
                || (!$this->house_disp && $house == 4)
                // MLAs have lowest priority.
                || (!$this->house_disp && $house == 3)
                // Lords have highest priority.
                || ($this->house_disp != 2 && $house == 2)
                // MPs have higher priority than MLAs.
                || ((!$this->house_disp || $this->house_disp == 3) && $house == 1)
            ) {
                // OA-306 assure that person's party affiliation and constituency
                // are derived from thier latest membership role.
                if (
                    !isset($this->left_house[$this->house_disp])
                    || ($left_house >= $this->left_house[$this->house_disp]['date'])
                ) {
                    $this->house_disp = $house;
                    $this->constituency = $const;
                    $this->party = $party;

                    $this->member_id = $row->member_id;
                    $this->title = $row->title;
                    $this->first_name = $row->first_name;
                    $this->last_name = $row->last_name;
                    $this->person_id = $row->person_id;
                }
            }

            if ($left_reason == 'changed_party') {
                $this->other_parties[] = [
                    'from' => $this->party_text($row->party),
                    'date' => $row->left_house
                ];
            }
        }

        // Loads extra info from DB - you now have to call this from outside
        // when you need it, as some uses of MEMBER are lightweight (e.g.
        // in searchengine.php)
        // $this->load_extra_info();

        $this->set_users_mp();
    }

    /**
     *
     */
    public function member_id_to_person_id($member_id) {
        $person_id = MemberModel::where('member_id', $member_id)->value('person_id');
        return $person_id ?? false;
    }

    /**
     *
     */
    public function postcode_to_person_id($postcode) {
        twfy_debug('MP', "postcode_to_person_id converting postcode to person");
        $constituency = strtolower(postcode_to_constituency($postcode));
        return $this->constituency_to_person_id($constituency);
    }

    /**
     *
     */
    public function constituency_to_person_id($constituency) {
        global $PAGE;
        if ($constituency == '') {
            $PAGE->error_message("Sorry, no constituency was found.");
            return false;
        }

        $normalised = normalise_constituency_name($constituency);
        if ($normalised) {
            $constituency = $normalised;
        }

        $person_id = MemberModel::where('constituency', $constituency)
          ->where('left_reason', 'still_in_office')
          ->value('person_id');

        if ($person_id) {
            return $person_id;
        }

        $person_id = MemberModel::where('constituency', $constituency)
          ->orderBy('left_house', 'desc')
          ->value('person_id');

        return $person_id ?? false;
    }

    /**
     *
     */
    public function name_to_person_id($name, $const = '') {
        global $PAGE, $this_page;
        if ($name == '') {
            $PAGE->error_message('Sorry, no name was found.');
            return false;
        }

        $query = MemberModel::query()
          ->select('person_id', 'constituency', 'left_house')
          ->distinct();

        $success = preg_match('#^(.*?) (.*?) (.*?)$#', $name, $m);
        if (!$success) {
            $success = preg_match('#^(.*?)() (.*)$#', $name, $m);
        }
        if (!$success) {
            $PAGE->error_message('Sorry, that name was not recognised.');
            return false;
        }
        $first_name = $m[1];
        $middle_name = $m[2];
        $last_name = $m[3];
        $house = (strstr($this_page, 'mp')) ? 1 : 2;
        $query->where('house', $house);
        // When there's no middle name, avoid concatenating a stray space
        // that would never match under MySQL 8 NO PAD collations.
        if ($middle_name !== '') {
            $query->where(function ($q) use ($first_name, $middle_name, $last_name) {
                $q->where(function ($qq) use ($first_name, $middle_name, $last_name) {
                    $qq->where('first_name', $first_name . ' ' . $middle_name)
                      ->where('last_name', $last_name);
                })->orWhere(function ($qq) use ($first_name, $middle_name, $last_name) {
                    $qq->where('first_name', $first_name)
                      ->where('last_name', $middle_name . ' ' . $last_name);
                });
            });
        } else {
            $query->where('first_name', $first_name)
              ->where('last_name', $last_name);
        }
        if ($const) {
            $normalised = normalise_constituency_name($const);
            if ($normalised && strtolower($normalised) != strtolower($const)) {
                $this->canonical = false;
                $const = $normalised;
            }
        }

        if ($const) {
            $query->where('constituency', $const);
        }
        $rows = $query->orderBy('left_house', 'desc')->get();
        if ($rows->count() > 1) {
            // Hacky as a very hacky thing that's graduated in hacking from the University of Hacksville
            // Anyone who wants to do it properly, feel free.
            // note the above comment was imported from SVN into git in about 2002, and look it's still here.

            $person_ids = [];
            $consts = [];
            foreach ($rows as $row) {
                $pid = $row->person_id;
                if (!in_array($pid, $person_ids)) {
                    $person_ids[] = $pid;
                    $consts[] = $row->constituency;
                }
            }
            if (count($person_ids) == 1) {
                return $person_ids[0];
            }
            $this->constituency = $consts;
            return $person_ids;
        } elseif ($rows->count() > 0) {
            return $rows->first()->person_id;
        } elseif ($const && $this_page != 'peer') {
            $this->canonical = false;
            return $this->name_to_person_id($name);
        } else {
            $PAGE->error_message("Sorry, there is no current member with that name.");
            return false;
        }
    }

    /**
     *
     */
    public function set_users_mp() {
        // Is this MP THEUSER's MP?
        global $THEUSER;
        if (is_object($THEUSER) && $THEUSER->constituency_is_set() && $this->current_member(1)) {
            twfy_debug('MP', "set_users_mp converting postcode to person");
            $constituency = $THEUSER->constituency();
            if ($constituency == $this->constituency()) {
                $this->the_users_mp = true;
            }
        }
    }

    /**
     * Grabs extra information (e.g. external links) from the database.
     */
    public function load_extra_info() {

        $offices = DB::table('moffice')->where('person', $this->person_id)->orderBy('from_date', 'desc')->get();
        foreach ($offices as $office) {
            $this->extra_info['office'][] = (array) $office;
        }

        $memberInfoRows = DB::table('memberinfo')->where('member_id', $this->member_id)->get(['data_key', 'data_value']);
        foreach ($memberInfoRows as $row) {
            $this->extra_info[$row->data_key] = $row->data_value;
        }

        $personInfoRows = DB::table('personinfo')->where('person_id', $this->person_id)->get(['data_key', 'data_value']);
        foreach ($personInfoRows as $row) {
            $this->extra_info[$row->data_key] = $row->data_value;
        }

        // Info specific to constituency (e.g. election results page on Guardian website)
        $consInfoRows = DB::table('consinfo')->where('constituency', $this->constituency)->get(['data_key', 'data_value']);
        foreach ($consInfoRows as $row) {
            $this->extra_info[$row->data_key] = $row->data_value;
        }

        if (array_key_exists('guardian_mp_summary', $this->extra_info)) {
            $guardian_url = $this->extra_info['guardian_mp_summary'];
            $this->extra_info['guardian_register_member_interests'] =
                str_replace("/person/", "/person/parliamentrmi/", $guardian_url);
            $this->extra_info['guardian_parliament_history'] =
                str_replace("/person/", "/person/parliament/", $guardian_url);
            $this->extra_info['guardian_biography'] = $guardian_url;
            $this->extra_info['guardian_candidacies'] =
                str_replace("/person/", "/person/candidacies/", $guardian_url);
            $this->extra_info['guardian_howtheyvoted'] =
                str_replace("/person/", "/person/howtheyvoted/", $guardian_url);
            $this->extra_info['guardian_contactdetails'] =
                str_replace("/person/", "/person/contactdetails/", $guardian_url);
        }

        if (array_key_exists('public_whip_rebellions', $this->extra_info)) {
            $rebellions = $this->extra_info['public_whip_rebellions'];
            $rebel_desc = "<unknown>";
            if ($rebellions == 0) {
                $rebel_desc = "never";
            } elseif ($rebellions <= 1) {
                $rebel_desc = "hardly ever";
            } elseif ($rebellions <= 3) {
                $rebel_desc = "occasionally";
            } elseif ($rebellions <= 5) {
                $rebel_desc = "sometimes";
            } elseif ($rebellions > 5) {
                $rebel_desc = "quite often";
            }
            $this->extra_info['public_whip_rebel_description'] = $rebel_desc;
        }

        if (isset($this->extra_info['public_whip_attendrank'])) {
            $prefix = ($this->house(2) ? 'L' : '');
            $this->extra_info[$prefix . 'public_whip_division_attendance_rank'] = $this->extra_info['public_whip_attendrank'];
            $this->extra_info[$prefix . 'public_whip_division_attendance_rank_outof'] = $this->extra_info['public_whip_attendrank_outof'];
            $this->extra_info[$prefix . 'public_whip_division_attendance_quintile'] = floor($this->extra_info['public_whip_attendrank'] / ($this->extra_info['public_whip_attendrank_outof'] + 1) * 5);
        }
        if ($this->house(2) && isset($this->extra_info['public_whip_division_attendance'])) {
            $this->extra_info['Lpublic_whip_division_attendance'] = $this->extra_info['public_whip_division_attendance'];
            unset($this->extra_info['public_whip_division_attendance']);
        }

        if (array_key_exists('register_member_interests_html', $this->extra_info) && ($this->extra_info['register_member_interests_html'] != '')) {
            $args = [
                "sort" => "regexp_replace"
            ];
            $GLOSSARY = new GLOSSARY($args);
            $this->extra_info['register_member_interests_html'] =
                $GLOSSARY->glossarise($this->extra_info['register_member_interests_html']);
        }

        $q = parlDBQuery('SELECT COUNT(*) AS c from alerts WHERE criteria LIKE "%speaker:' . $this->person_id . '%" AND confirmed AND NOT deleted');
        $this->extra_info['number_of_alerts'] = $q->field(0, 'c');

        if (isset($this->extra_info['reading_ease'])) {
            $this->extra_info['reading_ease'] = round($this->extra_info['reading_ease'], 2);
            $this->extra_info['reading_year'] = round($this->extra_info['reading_year'], 0);
            $this->extra_info['reading_age'] = $this->extra_info['reading_year'] + 4;
            $this->extra_info['reading_age'] .= '&ndash;' . ($this->extra_info['reading_year'] + 5);
        }

        // Public Bill Committees.
        $q = parlDBQuery('SELECT bill_id,session,title, SUM(attending) AS a,SUM(chairman) AS c
		from pbc_members, bills
		WHERE bill_id = bills.id AND member_id = ? GROUP BY bill_id', $this->member_id());
        $this->extra_info['pbc'] = [];
        for ($i = 0; $i < $q->rows(); $i++) {
            $bill_id = $q->field($i, 'bill_id');
            $c = parlDBQuery('SELECT COUNT(*) AS c from hansard WHERE major=6 AND minor=? AND htype=10', $bill_id);
            $c = $c->field(0, 'c');
            $title = $q->field($i, 'title');
            $attending = $q->field($i, 'a');
            $chairman = $q->field($i, 'c');
            $this->extra_info['pbc'][$bill_id] = [
                'title' => $title,
                'session' => $q->field($i, 'session'),
                'attending' => $attending,
                'chairman' => ($chairman > 0),
                'outof' => $c
            ];
        }

    }

    /**
     * Functions for accessing things about this Member.
     */
    public function member_id() {
        return $this->member_id;
    }

    /**
     *
     */
    public function person_id() {
        return $this->person_id;
    }

    /**
     *
     */
    public function first_name() {
        return $this->first_name;
    }

    /**
     *
     */
    public function last_name() {
        return $this->last_name;
    }

    /**
     *
     */
    public function full_name($no_mp_title = false) {
        $title = $this->title;
        if ($no_mp_title && $this->house_disp == 1) {
            $title = '';
        }
        return member_full_name($this->house_disp, $title, $this->first_name, $this->last_name, $this->constituency);
    }

    /**
     *
     */
    public function houses() {
        return $this->houses;
    }

    /**
     *
     */
    public function house($house) {
        return in_array($house, $this->houses) ? true : false;
    }

    /**
     *
     */
    public function constituency() {
        return $this->constituency;
    }

    /**
     *
     */
    public function party() {
        return $this->party;
    }

    /**
     *
     */
    public function party_text($party = null) {
        global $parties;
        if (!$party) {
            $party = $this->party;
        }
        if (isset($parties[$party])) {
            return $parties[$party];
        } else {
            return $party;
        }
    }

    /**
     *
     */
    public function entered_house($house = 0) {
        if ($house) {
            return array_key_exists($house, $this->entered_house) ? $this->entered_house[$house] : null;
        }
        return $this->entered_house;
    }

    /**
     *
     */
    public function entered_house_text($entered_house) {
        if (!$entered_house) {
            return '';
        }
        [$year, $month, $day] = explode('-', $entered_house);
        if ($month == 1 && $day == 1 && $this->house(2)) {
            return $year;
        } elseif (checkdate((int) $month, (int) $day, (int) $year) && $year != '9999') {
            return format_date($entered_house, LONGDATEFORMAT);
        } else {
            return "n/a";
        }
    }

    /**
     *
     */
    public function left_house($house = null) {
        if (!is_null($house)) {
            return array_key_exists($house, $this->left_house) ? $this->left_house[$house] : null;
        }
        return $this->left_house;
    }

    /**
     *
     */
    public function left_house_text($left_house) {
        if (!$left_house) {
            return '';
        }
        [$year, $month, $day] = explode('-', $left_house);
        if (checkdate((int) $month, (int) $day, (int) $year) && $year != '9999') {
            return format_date($left_house, LONGDATEFORMAT);
        } else {
            return "n/a";
        }
    }

    /**
     *
     */
    public function entered_reason() {
        return $this->entered_reason;
    }

    /**
     *
     */
    public function entered_reason_text($entered_reason) {
        if (isset($this->reasons[$entered_reason])) {
            return $this->reasons[$entered_reason];
        } else {
            return $entered_reason;
        }
    }

    /**
     *
     */
    public function left_reason() {
        return $this->left_reason;
    }

    /**
     *
     */
    public function left_reason_text($left_reason, $mponly = 0) {
        if (isset($this->reasons[$left_reason])) {
            $left_reason = $this->reasons[$left_reason];
            if (is_array($left_reason)) {
                $max = MemberModel::max('left_house');
                if ((!$mponly && $max == $this->left_house) || ($mponly && $max == $this->mp_left_house)) {
                    return $left_reason[0];
                } else {
                    return $left_reason[1];
                }
            } else {
                return $left_reason;
            }
        } else {
            return $left_reason;
        }
    }

    /**
     *
     */
    public function extra_info() {
        return $this->extra_info;
    }

    /**
     *
     */
    public function current_member($house = 0) {
        $current = [];
        foreach (array_keys($this->houses_pretty) as $h) {
            $lh = $this->left_house($h);
            $current[$h] = ($lh && $lh['date'] == '9999-12-31');
        }
        if ($house) {
            return $current[$house];
        }
        return $current;
    }

    /**
     *
     */
    public function the_users_mp() {
        return $this->the_users_mp;
    }

    /**
     *
     */
    public function url($absolute = true) {
        $house = $this->house_disp;
        if ($house == 1) {
            $URL = new URL('mp');
        } elseif ($house == 2) {
            $URL = new URL('peer');
        } elseif ($house == 3) {
            $URL = new URL('mla');
        } elseif ($house == 4) {
            $URL = new URL('msp');
        } elseif ($house == 0) {
            $URL = new URL('royal');
        }
        $member_url = make_member_url($this->full_name(true), $this->constituency(), $house);
        if ($absolute) {
            // Scheme-relative URL: the browser preserves the current scheme,
            // so http dev hosts stay on http and prod stays on https.
            return '//' . DOMAIN . $URL->generate('none') . $member_url;
        } else {
            return $URL->generate('none') . $member_url;
        }
    }

    /**
     *
     */
    public function display() {
        global $PAGE;

        $member = [
            'member_id' => $this->member_id(),
            'person_id' => $this->person_id(),
            'constituency' => $this->constituency(),
            'party' => $this->party_text(),
            'other_parties' => $this->other_parties,
            'houses' => $this->houses(),
            'entered_house' => $this->entered_house(),
            'left_house' => $this->left_house(),
            'current_member' => $this->current_member(),
            'full_name' => $this->full_name(),
            'the_users_mp' => $this->the_users_mp(),
            'house_disp' => $this->house_disp,
        ];

        $PAGE->display_member($member, $this->extra_info);
    }

    /**
     *
     */
    public function previous_mps() {
        $previous_people = '';
        $entered_house = $this->entered_house(1);
        if (is_null($entered_house)) {
            return '';
        }
        $members = MemberModel::where('house', 1)
          ->where('constituency', $this->constituency())
          ->where('person_id', '!=', $this->person_id())
          ->where('entered_house', '<', $entered_house['date'])
          ->groupBy('person_id', 'first_name', 'last_name')
          ->orderBy('entered_house', 'desc')
          ->get([
              'person_id',
              'first_name',
              'last_name',
              DB::raw('MAX(entered_house) as entered_house'),
          ]);
        foreach ($members as $member) {
            $pid = $member->person_id;
            $name = $member->first_name . ' ' . $member->last_name;
            $previous_people .= '<li><a href="' . WEBPATH . 'mp/?pid=' . $pid . '">' . $name . '</a></li>';
        }
        return $previous_people;
    }

    /**
     *
     */
    public function future_mps() {
        $future_people = '';
        $entered_house = $this->entered_house(1);
        if (is_null($entered_house)) {
            return '';
        }
        $members = MemberModel::where('house', 1)
          ->where('constituency', $this->constituency())
          ->where('person_id', '!=', $this->person_id())
          ->where('entered_house', '>', $entered_house['date'])
          ->groupBy('person_id', 'first_name', 'last_name')
          ->orderBy('entered_house', 'asc')
          ->get([
              'person_id',
              'first_name',
              'last_name',
              DB::raw('MIN(entered_house) as entered_house'),
          ]);
        foreach ($members as $member) {
            $name = $member->first_name . ' ' . $member->last_name;
            $future_people .= '<li><a href="' . WEBPATH . 'mp/?pid=' . $member->person_id . '">' . $name . '</a></li>';
        }
        return $future_people;
    }

}

// From http://news.bbc.co.uk/nol/shared/bsp/hi/vote2004/css/styles.css
global $party_colours;
$party_colours = [
    "Con" => "#333399",
    "DU" => "#cc6666",
    "Ind" => "#eeeeee",
    "Ind Con" => "#ddddee",
    "Ind Lab" => "#eedddd",
    "Ind UU" => "#ccddee",
    "Lab" => "#cc0000",
    "Lab/Co-op" => "#cc0000",
    // "#ff9900",
    "LDem" => "#f1cc0a",
    "PC" => "#33CC33",
    "SDLP" => "#8D9033",
    "SF" => "#2B7255",
    "SNP" => "#FFCC00",
    "UKU" => "#99CCFF",
    "UU" => "#003677",

    "Speaker" => "#999999",
    "Deputy Speaker" => "#999999",
    "CWM" => "#999999",
    "DCWM" => "#999999",
    "SPK" => "#999999",
];

/**
 *
 */
function party_to_colour($party) {
    global $party_colours;
    if (isset($party_colours[$party])) {
        return $party_colours[$party];
    } else {
        return "#eeeeee";
    }
}

/**
 *
 */
function find_rep_image($pid, $smallonly = false) {
    $image = null;
    $sz = null;
    if (!$smallonly && is_file(FILEIMAGEPATH . 'mpsL/' . $pid . '.jpg')) {
        $image = IMAGEPATH . 'mpsL/' . $pid . '.jpg';
        $sz = 'L';
    } elseif (!$smallonly && is_file(FILEIMAGEPATH . 'mpsL/' . $pid . '.jpeg')) {
        $image = IMAGEPATH . 'mpsL/' . $pid . '.jpeg';
        $sz = 'L';
    } elseif (!$smallonly && is_file(FILEIMAGEPATH . 'mpsL/' . $pid . '.png')) {
        $image = IMAGEPATH . 'mpsL/' . $pid . '.png';
        $sz = 'L';
    } elseif (is_file(FILEIMAGEPATH . 'mps/' . $pid . '.jpg')) {
        $image = IMAGEPATH . 'mps/' . $pid . '.jpg';
        $sz = 'S';
    } elseif (is_file(FILEIMAGEPATH . 'mps/' . $pid . '.jpeg')) {
        $image = IMAGEPATH . 'mps/' . $pid . '.jpeg';
        $sz = 'S';
    } elseif (is_file(FILEIMAGEPATH . 'mps/' . $pid . '.png')) {
        $image = IMAGEPATH . 'mps/' . $pid . '.png';
        $sz = 'S';
    }
    return [$image, $sz];
}
