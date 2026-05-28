<?php

/**
 * @file
 */

include '/data/vhost/www.openaustralia.org/includes/easyparliament/init.php';
// Include INCLUDESPATH . 'easyparliament/member.php';.

$q = parlDBQuery(
    'SELECT person_id, first_name, last_name, constituency,
		COUNT(hansard.epobject_id) AS wrans,
		SUM(yes_votes) + IFNULL((SELECT SUM(vote) FROM uservotes, hansard, member AS member2
				  WHERE uservotes.epobject_id=hansard.epobject_id
					AND hansard.speaker_id=member2.member_id
					AND member.person_id=member2.person_id), 0) AS yes,
		SUM(no_votes) + IFNULL((SELECT COUNT(vote)-SUM(vote) FROM uservotes, hansard, member AS member2
				 WHERE uservotes.epobject_id=hansard.epobject_id
					AND hansard.speaker_id=member2.member_id
					AND member.person_id=member2.person_id), 0) AS no
	FROM hansard
		LEFT JOIN member ON hansard.speaker_id=member.member_id
		LEFT JOIN anonvotes ON hansard.epobject_id=anonvotes.epobject_id
		WHERE major = 3 AND minor = 2 AND left_house > curdate()
	GROUP BY person_id
	ORDER BY first_name, last_name, constituency');

for ($i = 0; $i < $q->rows(); $i++) {
    $p_id = $q->field($i, 'person_id');
    $name = $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name');
    $con = $q->field($i, 'constituency');
    $wrans = $q->field($i, 'wrans');
    $yes = $q->field($i, 'yes');
    $no = $q->field($i, 'no');
    if ($p_id) {
        $qq = parlDBQuery('(SELECT hansard.epobject_id FROM hansard, member, uservotes
			WHERE hansard.epobject_id=uservotes.epobject_id
				AND hansard.speaker_id=member.member_id
				AND person_id = ? AND major=3 AND minor=2 AND left_house>curdate())
		UNION
			(SELECT hansard.epobject_id FROM hansard, member, anonvotes
			 WHERE hansard.epobject_id=anonvotes.epobject_id
			 	AND hansard.speaker_id=member.member_id
				AND person_id = ? AND major=3 AND minor=2 AND left_house>curdate())', $p_id, $p_id);
        $wrans_with_votes = $qq->rows();
    } else {
        $wrans_with_votes = '';
    }
    print "$name\t$con\t$wrans\t$wrans_with_votes\t$yes\t$no\n";
}
