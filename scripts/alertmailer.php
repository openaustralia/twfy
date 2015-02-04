<?php
/* 
 * Name: alertmailer.php
 * Description: Mailer for email alerts
 * $Id: alertmailer.php,v 1.20 2008/01/25 16:24:14 twfy-live Exp $
 */

function mlog($message) {
	print $message;
}

include '../www/includes/easyparliament//init.php';
ini_set('memory_limit', -1);
include_once INCLUDESPATH . 'easyparliament/member.php';

$global_start = getmicrotime();
$db = new ParlDB;

// Get current value of latest batch
$q = $db->query('SELECT max(indexbatch_id) as max_batch_id FROM indexbatch');
$max_batch_id = $q->field(0, 'max_batch_id');
mlog("max_batch_id: " . $max_batch_id . "\n");

// Last sent is timestamp of last alerts gone out.
// Last batch is the search index batch number last alert went out to.
$lastsent = file('alerts-lastsent');
$lastupdated = trim($lastsent[0]);
if (!$lastupdated) $lastupdated = strtotime('00:00 today');
$lastbatch = trim($lastsent[1]);
if (!$lastbatch) $lastbatch = 0;
mlog("lastupdated: $lastupdated lastbatch: $lastbatch\n");


// extract html wrappers from html email template
// tokens have been added to the template to mark the start and end of section of the html
$html_template_filename='edm.html'; // FIXME - move this to config
$filename = INCLUDESPATH . "easyparliament/templates/emails/" . $html_template_filename;
if (!file_exists($filename)) {
    $PAGE->error_message("Sorry, we could not find the email template '" . $filename . "'.");
    return false; }
// Get the text from the template.
$content = file_get_contents($filename);
//content, start_token, end_token
$html_email_sections['TOP']=extract_content_between_tokens($content,'<!-- CUT_HERE START_TOP -->','<!-- CUT_HERE END_TOP -->',false);
$html_email_sections['MEMBER_HEADER']=extract_content_between_tokens($content,'<!-- CUT_HERE START_MEMBER_SEARCH_HEADER -->','<!-- CUT_HERE END_MEMBER_SEARCH_HEADER -->',true);
$html_email_sections['MEMBER_ITEM']=extract_content_between_tokens($content,'<!-- CUT_HERE START_MEMBER_WRAPPER -->','<!-- CUT_HERE END_MEMBER_WRAPPER -->',true);
$html_email_sections['UNSUB_ALERT']=extract_content_between_tokens($content,'<!-- CUT_HERE START_END_OF_SECTION_UNSUBSCRIBE -->','<!-- CUT_HERE END_END_OF_SECTION_UNSUBSCRIBE -->',true);
$html_email_sections['VIEW_MORE']=extract_content_between_tokens($content,'<!-- CUT_HERE START_VIEW_MORE_LINK -->','<!-- CUT_HERE END_VIEW_MORE_LINK -->',true);
$html_email_sections['PHRASE_HEADER']=extract_content_between_tokens($content,'<!-- CUT_HERE START_PHRASE_SEARCH_HEADER -->','<!-- CUT_HERE END_PHRASE_SEARCH_HEADER -->',true);
$html_email_sections['PHRASE_ITEM']=extract_content_between_tokens($content,'<!-- CUT_HERE START_PHRASE_WRAPPER -->','<!-- CUT_HERE END_PHRASE_WRAPPER -->',true);
$html_email_sections['ALERT_PREFS']=extract_content_between_tokens($content,'<!-- CUT_HERE START_NEWSLETTER_PREFERENCES_MANAGEMENT -->','<!-- CUT_HERE END_NEWSLETTER_PREFERENCES_MANAGEMENT -->',true);
$html_email_sections['SEPERATION_LINE']=extract_content_between_tokens($content,'<!-- CUT_HERE START_SEPERATION_LINE -->','<!-- CUT_HERE END_SEPERATION_LINE -->',true);
$html_email_sections['BOTTOM']=extract_content_between_tokens($content,'<!-- CUT_HERE START_BOTTOM -->','<!-- CUT_HERE END_BOTTOM -->',false);
//mlog($html_email_sections['MEMBER_HEADER']);


// Construct query fragment to select search index batches which
// have been made since last time we ran
$batch_query_fragment = "";
for ($i=$lastbatch + 1; $i <= $max_batch_id; $i++) {
	$batch_query_fragment .= "batch:$i ";
}
$batch_query_fragment = trim($batch_query_fragment);
mlog("batch_query_fragment: " . $batch_query_fragment . "\n");

// For testing purposes, specify nomail on command line to not send out emails
$nomail = false;
$onlyemail_addr = '';
$fromemail_addr = '';
$toemail_addr = '';
for ($k=1; $k<$argc; $k++) {
	if ($argv[$k] == '--nomail')
		$nomail = true;
	if (preg_match('#^--only=(.*)$#', $argv[$k], $m))
		$onlyemail_addr = $m[1];
	if (preg_match('#^--from=(.*)$#', $argv[$k], $m))
		$fromemail_addr = $m[1];
	if (preg_match('#^--to=(.*)$#', $argv[$k], $m))
		$toemail_addr = $m[1];
}

if ($nomail) mlog("NOT SENDING EMAIL\n");
if (($fromemail_addr && $onlyemail_addr) || ($toemail_addr && $onlyemail_addr)) {
	mlog("Can't have both from/to and only!\n");
	exit;
}

$active = 0;
$queries = 0;
$unregistered = 0;
$registered = 0;
$sentemails = 0;

$LIVEALERTS = new ALERT;

$current_email_addr = '';
$email_plaintext = '';  // this is for the plaintext content
$email_html = ''; // this is for the html content
$globalsuccess = 1;

// Fetch all confirmed, non-deleted alerts
$confirmed = 1; $deleted = 0;
$alertdata = $LIVEALERTS->fetch($confirmed, $deleted);
$alertdata = $alertdata['data'];

$DEBATELIST = new DEBATELIST; // Nothing debate specific, but has to be one of them

$sects = array('', 'House of Representatives debate', 'Westminster Hall debate', 'Written Answer', 'Written Ministerial Statement', 'Northern Ireland Assembly debate');
$sects[101] = 'Senate debate';
$sects_short = array('', 'debate', 'westminhall', 'wrans', 'wms', 'ni');
$sects_short[101] = 'senate';
$results = array();

$outof = count($alertdata);
$start_time = time();
foreach ($alertdata as $alertitem) {
	$active++;
	$alert_email_addr = $alertitem['email'];
	if ($onlyemail_addr && $alert_email_addr != $onlyemail_addr) continue;
	if ($fromemail_addr && strtolower($alert_email_addr) <= $fromemail_addr) continue;
	if ($toemail_addr && strtolower($alert_email_addr) >= $toemail_addr) continue;
	$criteria_raw = $alertitem['criteria'];
	$criteria_batch = $criteria_raw . " " . $batch_query_fragment;

	if ($alert_email_addr != $current_email_addr) {
		if ($email_plaintext)
			write_and_send_email($current_email_addr, $user_id, $email_plaintext);
		$current_email_addr = $alert_email_addr;
		$email_plaintext = '';
		$email_html = $html_email_sections['TOP']; // start with this piece of the html template, no values to swap in
		$q = $db->query('SELECT user_id FROM users WHERE email = \''.mysql_escape_string($alert_email_addr)."'");
		if ($q->rows() > 0) {
			$user_id = $q->field(0, 'user_id');
			$registered++;
		} else {
			$user_id = 0;
			$unregistered++;
		}
		mlog("\nEMAIL: $alert_email_addr, uid $user_id; memory usage : ".memory_get_usage()."\n");
	}

	$search_result_data = null;
	if (!isset($results[$criteria_batch])) {
		mlog("  ALERT $active/$outof QUERY $queries : Xapian query '$criteria_batch'");
		$start = getmicrotime();
		$SEARCHENGINE = new SEARCHENGINE($criteria_batch);
		//mlog("query_remade: " . $SEARCHENGINE->query_remade() . "\n");
		$args = array(
			's' => $criteria_raw, // Note: use raw here for URLs, whereas search engine has batch
			'threshold' => $lastupdated, // Return everything added since last time this script was run
			'o' => 'c',
			'num' => 1000, // this is limited to 1000 in hansardlist.php anyway
			'pop' => 1,
			'e' => 1 // Don't escape ampersands
		);
		$search_result_data = $DEBATELIST->_get_data_by_search($args);
		// add to cache (but only for speaker queries, which are commonly repeated)
		if (preg_match('#^speaker:\d+$#', $criteria_raw, $m)) {
			mlog(", caching");
			$results[$criteria_batch] = $search_result_data;
		}
		//		unset($SEARCHENGINE);
		$total_results = $search_result_data['info']['total_results'];
		$queries++;
		mlog(", hits ".$total_results.", time ".(getmicrotime()-$start)."\n");
	} else {
		mlog("  ACTION $active/$outof CACHE HIT : Using cached result for '$criteria_batch'\n");
		$search_result_data = $results[$criteria_batch];
	}

	if (isset($search_result_data['rows']) && count($search_result_data['rows']) > 0) {
		usort($search_result_data['rows'], 'sort_by_stuff'); // Sort results into order, by major, then date, then hpos
		$major = 0; 
		$count = array(); 
		$total = 0;
		$results_for_email = array(); // used as an array of array of result parts
		$any_content = false;
		foreach ($search_result_data['rows'] as $row) {
			if ($major != $row['major']) {
				$count[$major] = $total; $total = 0;
				$major = $row['major'];
				$results_for_email[$major]=array();  // new array to hold the results to be sent
				$k = 3;
			}
			//mlog($row['major'] . " " . $row['gid'] ."\n");
			if ($row['hdate'] < '2007-01-14') continue;  // I had to change this 2007, to get results from the dev db
			$q = $db->query('SELECT gid_from FROM gidredirect WHERE gid_to=\'uk.org.publicwhip/' . $sects_short[$major] . '/' . mysql_escape_string($row['gid']) . "'");
			if ($q->rows() > 0) continue;
			--$k;
			if ($k>=0) {
				$any_content = true;
				$result=array(); // this will contain a dict of result parts
				// gather the parts
				$result['date'] = format_date($row['hdate'], SHORTDATEFORMAT);
				$result['title'] = $row['parent']['body'];
				$result['body'] = $row['body'];
				$result['url'] = 'http://www.openaustralia.org' . $row['listurl'];
				if (isset($row['speaker']) && count($row['speaker']))
				    $result['speaker'] = html_entity_decode(member_full_name($row['speaker']['house'], $row['speaker']['title'], $row['speaker']['first_name'], $row['speaker']['last_name'], $row['speaker']['constituency']));
				
				// save the result in part form
				$results_for_email[$major][$k]=$result;
			}
			$total++;
		}
		$count[$major] = $total;

		if ($any_content) {
			// Add data to email_text
			$desc = trim(html_entity_decode($search_result_data['searchdescription']));
			$deschead = ucfirst(str_replace('containing ', '', $desc));
			foreach ($results_for_email as $major => $theseresults) {
				$heading = $deschead . ' : ' . $count[$major] . ' ' . $sects[$major] . ($count[$major]!=1?'s':'');
				$email_plaintext .= "$heading\n";
				$email_plaintext .= str_repeat('=',strlen($heading))."\n\n";
				
				if (preg_match('#^speaker:\d+$#', $criteria_raw, $m))  // it's a person alert
				{  
				    //mlog("Person : " . $criteria_raw . "\n");
				    $email_html .= $html_email_sections['MEMBER_HEADER'];
				    $email_html = str_replace('{ALERT_TERM}',$result['speaker'],$email_html); // swap in the values
				}
				else // it's a phrase alert
				{
				    //mlog("Phrase : " . $criteria_raw . "\n");
				    $email_html .= $html_email_sections['PHRASE_HEADER'];
				    $email_html = str_replace('{ALERT_TERM}',$criteria_raw,$email_html); // swap in the values
				}
				$email_html = str_replace('{ITEM_COUNT}',$count[$major],$email_html); // swap in the values
				$email_html = str_replace('{ITEM_HOUSE}',$sects[$major],$email_html); // swap in the values

				if ($count[$major] > 3) { // this is for the text emails which have this at the top
				    $url_seemore="http://www.openaustralia.org/search/?s=".urlencode($criteria_raw)."+section:".$sects_short[$major]."&o=d";
				    $email_plaintext .= "There are more results than we have shown here. See more: \n $url_seemore \n\n";
				}

				if ($count[$major] > 3) {
					$url_seemore="http://www.openaustralia.org/search/?s=".urlencode($criteria_raw)."+section:".$sects_short[$major]."&o=d";
					$email_plaintext .= "There are more results than we have shown here. See more: \n $url_seemore \n\n";
					$email_html .= "<p>There are more results than we have shown here. <a href='$url_seemore'>See more</a></p>\n";
				}
				foreach ($theseresults as $result) {
					if ($result['body']) {
						//plain text
						$email_plaintext .= str_replace(array('&#8212;','<span class="hi">','</span>'), array('-','*','*'), $result['title']) . "\n";
						$email_plaintext .= $result['url'] . "\n";
						$email_plaintext .= ($result['speaker'] ? $result['speaker'] . " : " : "");
						$email_plaintext .= str_replace(array('&#163;','&#8212;','<span class="hi">','</span>'), array("\xa3",'-','*','*'), $result['body']) ."\n\n";
						//html
						$cleaned_title = str_replace(array('&#8212;','<span class="hi">','</span>'), array('-','<strong>','</strong>'), $result['title']) . "\n";
						$email_html .= "<p><a href='" . $result['url'] . "'>" . $cleaned_title . "</a></p>\n";
						$email_html .= ($result['speaker'] ? '<p>' . $result['speaker'] . '</p>' . "\n" : "");
						$tagged_alert_term_body=str_replace(array('&#163;','&#8212;','<span class="hi">','</span>'), array("\xa3",'-','<strong>','</strong>'), $result['body']);
						$email_html .= '<p>' . $tagged_alert_term_body . '</p><br />' . "\n";
					}
				}
			}
			$url_unsubscribe="http://www.openaustralia.org/D/" . $alertitem['alert_id'] . '-' . $alertitem['registrationtoken'];
			$email_plaintext .= "To unsubscribe from your alert for items " . $desc . ", please use:\n $url_unsubscribe \n\n";
			$email_html .= "<p><a href='" . $url_unsubscribe . "'>Unsubscibe alerts " . $desc . "</a></p>\n";
		}
	}
}
if ($email_plaintext && $email_html)  //somewhat unessesary but clearer, for someone working on this fuction
	write_and_send_email($current_email_addr, $user_id, $email_plaintext, $email_html);

mlog("\n");

$sss = "Active alerts: $active\nEmail lookups: $registered registered, $unregistered unregistered\nQuery lookups: $queries\nSent emails: $sentemails\n";
if ($globalsuccess) {
	$sss .= 'Everything went swimmingly, in ';
} else {
	$sss .= 'Something went wrong! Total time: ';
}
$sss .= (getmicrotime()-$global_start)."\n\n";
mlog($sss);
if (!$nomail && !$onlyemail_addr) {
	$fp = fopen('alerts-lastsent', 'w');
	fwrite($fp, time() . "\n");
	fwrite($fp, $max_batch_id);
	fclose($fp);
	mail(ALERT_STATS_EMAILS, 'Email alert statistics', $sss, 'From: Email Alerts <'. ALERT_STATS_SENDER .'>');
}
mlog(date('r') . "\n");

function sort_by_stuff($a, $b) {
	if ($a['major'] > $b['major']) return 1;
	if ($a['major'] < $b['major']) return -1;

	if ($a['hdate'] < $b['hdate']) return 1;
	if ($a['hdate'] > $b['hdate']) return -1;

	if ($a['hpos'] == $b['hpos']) return 0;
	return ($a['hpos'] > $b['hpos']) ? 1 : -1;
}

function write_and_send_email($to_email_addr, $user_id, $email_plaintext, $email_html) {
	global $globalsuccess, $sentemails, $nomail, $start_time;

	$email_plaintext .= '===================='."\n\n";
	if ($user_id) {  // change the message depending on if the alert user is a registered user
		$email_plaintext .= "As a registered user, visit http://www.openaustralia.org/user/\n";
		$email_plaintext .= "to unsubscribe from, or manage, your alerts.\n";
		$email_html .= "<p>As a registered user, you can <a href='http://www.openaustralia.org/user/'>manage your alerts online</a>.\n";
	} else {
		$email_plaintext .= "If you register on the site, you will be able to manage your\n";
		$email_plaintext .= " alerts there as well as post comments. :)\n";
		$email_html .= "<p>If you <a href='http://www.openaustralia.org/user/?pg=join'>register online</a> you will be able to manage you alerts, and post comments too.</a></p>\n";
	}
	$email_html .= $html_email_sections['BOTTOM'];
	$sentemails++;
	mlog("SEND $sentemails : Sending email to $to_email_addr ... ");
	
	// the mime spec says a unique is is needed for the boundary
	// I'm not sure if it means unique in the message, or unique mail system
	// I opted to make it unique in the system (reasonably)
	$mime_boundary=uniqid('mime-boundary-'); // we pass this on to the template function, to be used in the email header (utility.php)
	
	$multipart_text  = "--$mime_boundary \n";
	$multipart_text .= "Content-type: text/plain;charset=\"utf-8\" \n";
	$multipart_text .= "Content-transfer-encoding: 7bit \n";
	
	$multipart_html = "--$mime_boundary \n";
	$multipart_html .= "Content-Type: text/html; charset=\"iso-8859-1\" \n";
	$multipart_html .= "Content-Transfer-Encoding: quoted-printable \n";
	
	$template_data = array('to' => $to_email_addr, 'template' => 'alert_mailout_multipart');
	$template_merge = array('MIMEBOUNDARY'=>$mime_boundary, 'MIMEBOUNDARY_TEXT' => $multipart_text, 'TEXTMESSAGE' => $email_plaintext, 'MIMEBOUNDARY_HTML' => $multipart_html, 'HTMLMESSAGE' => $email_html);
	if (!$nomail) {
		$success = send_template_email($template_data, $template_merge, true); // true = "Precedence: bulk"
		mlog("sent ... ");
		// sleep if time between sending mails is less than a certain number of seconds on average
		if (((time() - $start_time) / $sentemails) < 0.5 ) { // number of seconds per mail not to be quicker than
			mlog("pausing ... ");
			sleep(1);
		}
	} else {
		mlog($email_plaintext);
		$success = 1;
	}
	mlog("done\n");
	if (!$success) $globalsuccess = 0;
}

function extract_content_between_tokens($content,$start_token,$end_token,$include_tokens) {
        // this function takes a monolithic html tamplate, and using start/end tokens, extract specific fragments
        // the tokens are encoded into the template as HTML comments <!--...-->

        $start_position = strpos($content, $start_token) + strlen($start_token);
        $end_position = strpos($content, $end_token, $start_position) ;.

        if($include_tokens).
        {
	    $start_position = $start_position - strlen($start_token);.
	    $end_position = $end_position + strlen($end_token);
        }
        
        //mlog($start_token.' '.$start_position."\n");
        //mlog($end_token.' '.$end_position."\n");
        $result = substr($content, $start_position, $end_position - $start_position);
        return $result;
}


?>
