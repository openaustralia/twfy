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

# Get current value of latest batch
$q = $db->query('SELECT max(indexbatch_id) as max_batch_id FROM indexbatch');
$max_batch_id = $q->field(0, 'max_batch_id');
mlog("max_batch_id: " . $max_batch_id . "\n");

# Last sent is timestamp of last alerts gone out.
# Last batch is the search index batch number last alert went out to.
$lastsent = file('alerts-lastsent');
$lastupdated = trim($lastsent[0]);
if (!$lastupdated) $lastupdated = strtotime('00:00 today');

if(isset($lastsent[1]))
    $lastbatch = trim($lastsent[1]);
else
    $lastbatch = 0;


mlog("lastupdated: $lastupdated lastbatch: $lastbatch\n");

# Construct query fragment to select search index batches which
# have been made since last time we ran
$batch_query_fragment = "";
for ($i=$lastbatch + 1; $i <= $max_batch_id; $i++) {
	$batch_query_fragment .= "batch:$i ";
}
$batch_query_fragment = trim($batch_query_fragment);
mlog("batch_query_fragment: " . $batch_query_fragment . "\n");

# For testing purposes, specify nomail on command line to not send out emails
$nomail = false;
$outboundfolder ='/srv/www/openaustralia/twfy/scripts/mails/';
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
$email_text = '';
$email_html = '';
$globalsuccess = 1;

# Fetch all confirmed, non-deleted alerts
$confirmed = 1; $deleted = 0;
$alertdata = $LIVEALERTS->fetch($confirmed, $deleted);
$alertdata = $alertdata['data'];

$DEBATELIST = new DEBATELIST; # Nothing debate specific, but has to be one of them

$sects = array('', 'House of Representatives debate', 'Westminster Hall debate', 'Written Answer', 'Written Ministerial Statement', 'Northern Ireland Assembly debate');
$sects[101] = 'Senate debate';
$sects_short = array('', 'debate', 'westminhall', 'wrans', 'wms', 'ni');
$sects_short[101] = 'senate';
$search_results = array();

$outof = count($alertdata);
$start_time = time();

// work through the alerts (I think they're sorted by email)
// append the hit results to an outbound email
// if the email address changes, send what we have and start a new email
foreach ($alertdata as $alertitem) {
	$active++;
	$alert_email_addr = $alertitem['email'];
	if ($onlyemail_addr && $alert_email_addr != $onlyemail_addr) continue;
	if ($fromemail_addr && strtolower($alert_email_addr) <= $fromemail_addr) continue;
	if ($toemail_addr && strtolower($alert_email_addr) >= $toemail_addr) continue;
	$criteria_raw = $alertitem['criteria'];
	$criteria_batch = $criteria_raw . " " . $batch_query_fragment;

	if ($alert_email_addr != $current_email_addr) {  // if the email address changes, send the email and start a new one
		if ($email_text)
			write_and_send_email($current_email, $user_id, $email_text, $email_html);
		$current_email_addr = $alert_email_addr;
		$email_text = '';
		$email_html = '';
		
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

	// Perform search for this alert criteris
	$search_results_data = null;
	if (!isset($search_results[$criteria_batch])) {
		mlog("  ALERT $active/$outof QUERY $queries : Xapian query '$criteria_batch'");
		$start = getmicrotime();
		$SEARCHENGINE = new SEARCHENGINE($criteria_batch);
		#mlog("query_remade: " . $SEARCHENGINE->query_remade() . "\n");
		$args = array(
			's' => $criteria_raw, # Note: use raw here for URLs, whereas search engine has batch
			'threshold' => $lastupdated, # Return everything added since last time this script was run
			'o' => 'c',
			'num' => 1000, // this is limited to 1000 in hansardlist.php anyway
			'pop' => 1,
			'e' => 1 // Don't escape ampersands
		);
		$search_results_data = $DEBATELIST->_get_data_by_search($args);
		# add to cache (but only for speaker queries, which are commonly repeated)
		if (preg_match('#^speaker:\d+$#', $criteria_raw, $m)) {
			mlog(", caching");
			$search_results[$criteria_batch] = $search_results_data;
		}
		#		unset($SEARCHENGINE);
		$total_results = $search_results_data['info']['total_results'];
		$queries++;
		mlog(", hits ".$total_results.", time ".(getmicrotime()-$start)."\n");
	} else {
		mlog("  ACTION $active/$outof CACHE HIT : Using cached result for '$criteria_batch'\n");
		$search_results_data = $search_results[$criteria_batch];
	}

// results_for_email['major'][0]['title']

	if (isset($search_results_data['rows']) && count($search_results_data['rows']) > 0) {
		usort($search_results_data['rows'], 'sort_by_stuff'); # Sort results into order, by major, then date, then hpos
		$o = array();
		$major = 0;
		$count = array();
		$total = 0;
		$results_for_email = array(); // used as an array of dicts
		$any_content = false;
		foreach ($search_results_data['rows'] as $row) {
			if ($major != $row['major']) {
				$count[$major] = $total; 
				$total = 0;
				$major = $row['major'];
				$o[$major] = '';
				$results_for_email[$major]=array();
				$k = 3;  //this could be a general config var, or a user pref
			}
			//mlog($row['major'] . " " . $row['gid'] ."\n");
			if ($row['hdate']< '2007-01-14') continue;  // content age limit?  I had to change it from 2008-01-14 to get results in dev system
			$q = $db->query('SELECT gid_from FROM gidredirect WHERE gid_to=\'uk.org.publicwhip/' . $sects_short[$major] . '/' . mysql_escape_string($row['gid']) . "'");
			if ($q->rows() > 0) continue;
			
			--$k;
			if ($k>=0) {
				$any_content = true;
				$result=array(); // this will contain a dict of content parts
				
				$parentbody = str_replace(array('&#8212;','<span class="hi">','</span>'), array('-','*','*'), $row['parent']['body']);
				
				$result['body'] = str_replace(array('&#163;','&#8212;','<span class="hi">','</span>'), array("\xa3",'-','*','*'), $row['body']);
				$result['title'] = $parentbody . ' (' . format_date($row['hdate'], SHORTDATEFORMAT) . ')';
				$result['url'] = 'http://www.openaustralia.org' . $row['listurl'];
				if (isset($row['speaker']) && count($row['speaker']))
				    $result['speaker'] = html_entity_decode(member_full_name($row['speaker']['house'], $row['speaker']['title'], $row['speaker']['first_name'], $row['speaker']['last_name'], $row['speaker']['constituency']));
				
				$o[$major] .= $result['title'] . "\n";
				$o[$major] .= $result['url'] . "\n";
				if(isset($result['speaker'])) $o[$major] .= $result['speaker'] . " : ";
				$o[$major] .= wordwrap($result['body'],72) . "\n\n";
				
				$results_for_email[$major][$k]=$result;
				//mlog(var_dump($result));
			}
			$total++;
		}
		$count[$major] = $total;
//		mlog(var_dump($o));		
//		mlog(var_dump($results_for_email));
		if ($any_content) {
			# Add data to email_text
			$desc = trim(html_entity_decode($search_results_data['searchdescription']));
			$deschead = ucfirst(str_replace('containing ', '', $desc));
			foreach ($results_for_email as $major => $theseresults) {
				$need_heading=true;
				if($need_heading){
					$heading = $deschead . ' : ' . $count[$major] . ' ' . $sects[$major] . ($count[$major]!=1?'s':'');
					$email_text .= $heading . "\n" . str_repeat('=',strlen($heading))."\n";
					$email_html .= "<p>" . $heading . "</p>\n";
					if ($count[$major] > 3) {
						$url_seemore="http://www.openaustralia.org/search/?s=".urlencode($criteria_raw)."+section:".$sects_short[$major]."&o=d";
						$email_text .= "There are more results than we have shown here. See more: \n $url_seemore \n\n";
						$email_html .= "<p>There are more results than we have shown here. <a url='$url_seemore'>See more</a></p>\n";
					}
					$need_heading=false;
				}
				foreach ($theseresults as $result) {
					if ($result['body']) {
						//plain text
						$email_text .= $result['title'] . "\n";
						$email_text .= $result['url'] . "\n";
						$email_text .= ($result['speaker'] ? $result['speaker'] . " : " : "");
						$email_text .= $result['body'] ."\n\n";
						
						//html
						$email_html .= "<p><a url='" . $result['url'] . "'>" . $result['title'] . "</a></p>\n";
						$email_html .= ($result['speaker'] ? '<p>' . $result['speaker'] . '</p>' . "\n" : "");
						$email_html .= '<p>' . $result['body'] . '</p><br />' . "\n";
					}
				}
				$url_unsubscribe="http://www.openaustralia.org/D/" . $alertitem['alert_id'] . '-' . $alertitem['registrationtoken'];
				$email_text .= "To unsubscribe from your alert for items " . $desc . ", please use:\n $url_unsubscribe \n\n";
				$email_html .= "<p><a url='" . $url_unsubscribe . "'>Unsubscibe alerts " . $desc . "</a></p>\n";
		    }
		}
	}
}
if ($email_text)
	write_and_send_email($current_email_addr, $user_id, $email_text, $email_html);

if($outboundfolder && $email_text) {
    file_put_contents($outboundfolder . $user_id . '_text',$email_text);
    file_put_contents($outboundfolder . $user_id . '_html',$email_html);
}
mlog("\n");

$msg_log = "Active alerts: $active\nEmail lookups: $registered registered, $unregistered unregistered\nQuery lookups: $queries\nSent emails: $sentemails\n";
if ($globalsuccess) {
	$msg_log .= 'Everything went swimmingly, in ';
} else {
	$msg_log .= 'Something went wrong! Total time: ';
}
$msg_log .= (getmicrotime()-$global_start)."\n\n";
mlog($msg_log);
if (!$nomail && !$onlyemail_addr) {
	$fp = fopen('alerts-lastsent', 'w');
	fwrite($fp, time() . "\n");
	fwrite($fp, $max_batch_id);
	fclose($fp);
	mail(ALERT_STATS_EMAILS, 'Email alert statistics', $msg_log, 'From: Email Alerts <'. ALERT_STATS_SENDER .'>');
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

function write_and_send_email($to_email_addr, $user_id, $email_text, $email_html) {
	global $globalsuccess, $sentemails, $nomail, $start_time;

	$email_text .= '===================='."\n\n";
	if ($user_id) {
		$email_text .= "As a registered user, visit http://www.openaustralia.org/user/\n";
		$email_text .= "to unsubscribe from, or manage, your alerts.\n";
		
		$email_html .= "<p>As a registered user, you can <a url='http://www.openaustralia.org/user/'>manage your alerts online</a>.\n";
	} else {
		$email_text .= "If you register on the site, you will be able to manage your\n";
		$email_text .= " alerts there as well as post comments. :)\n";
		$email_html .= "<p>If you <a url='http://www.openaustralia.org/user/?pg=join'>register online</a> you will be able to manage you alerts, and post comments too.</a></p>\n";
	}
	$sentemails++;
	mlog("SEND $sentemails : Sending email to $to_email_addr ... ");
	$mime_boundary=uniqid('mime-boundary-');
	
	$multipart_text  = "--$mime_boundary \n";
	$multipart_text .= "Content-type: text/plain;charset=\"utf-8\" \n";
	$multipart_text .= "Content-transfer-encoding: 7bit \n";
	
	$multipart_html = "--$mime_boundary \n";
	$multipart_html .= "Content-Type: text/html; charset=\"iso-8859-1\" \n";
	$multipart_html .= "Content-Transfer-Encoding: quoted-printable \n";
	
	$template_data = array('to' => $to_email_addr, 'template' => 'alert_mailout_multipart');
	$template_merge = array('MIMEBOUNDARY'=>$mime_boundary, 'MIMEBOUNDARY_TEXT' => $multipart_text, 'TEXTMESSAGE' => $email_text, 'MIMEBOUNDARY_HTML' => $multipart_html, 'HTMLMESSAGE' => $email_html);
	if (!$nomail) {
		$success = send_template_email($template_data, $template_merge, true); // true = "Precedence: bulk"
		mlog("sent ... ");
		# sleep if time between sending mails is less than a certain number of seconds on average
		if (((time() - $start_time) / $sentemails) < 0.5 ) { # number of seconds per mail not to be quicker than
			mlog("pausing ... ");
			sleep(1);
		}
	} else {
		mlog($data);
		$success = 1;
	}
	mlog("done\n");
	if (!$success) $globalsuccess = 0;
}

?>
