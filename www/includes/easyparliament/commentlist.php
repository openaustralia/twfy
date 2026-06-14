<?php

use OpenAustralia\TWFY\Models\Comments;
use OpenAustralia\TWFY\Models\Member;

/*     The class for displaying one or more comments.
(There's also a function for adding a new comment to the DB because I wasn't
sure where else to put it!).

This works similarly to the HANSARDLIST class.

To display all the comments for an epobject you'll do:

$args = array ('epobject_id' => $epobject_id);
$COMMENTLIST = new COMMENTLIST;
$COMMENTLIST->display ('ep', $args);

This will call the _get_data_by_ep() function which passes variables to the
_get_comment_data() function. This gets the comments from the DB and returns
an array of comments.

The render() function is then called, which includes a template and
goes through the array, displaying the comments. See the HTML comments.php
template for the format.
NOTE: You'll need to pass the 'body' of the comment through filter_user_input()
and linkify() first.

You could also just call the $COMMENTLIST->render() array with an array
of comment data and display directly (used for previewing user input).

 */

include_once __DIR__ . '/../dbtypes.php';

/**
 *
 */
class COMMENTLIST {

    public $page = '';

    /**
     *
     */
    public function __construct() {
        global $this_page;

        // We use this to create permalinks to comments. For the moment we're
        // assuming they're on the same page we're currently looking at:
        // debate, wran, etc.
        $this->page = $this_page;

    }

    /**
     *
     */
    public function display($view, $args = [], $format = 'html') {
        global $PAGE;

        // $view is what we're viewing by:
        // 'ep' is all the comments attached to an epobject.
        // 'user' is all the comments written by a user.
        // 'recent' is the most recent comments.

        // $args is an associative array of stuff like
        // 'epobject_id' => '37'
        // Where 'epobject_id' is an epobject_id.
        // Or 'gid' is a hansard item gid.

        // Replace a hansard object gid with an epobject_id.
        // $args = $this->_fix_gid($args);

        // $format is the format the data should be rendered in.

        if ($view == 'ep' || $view == 'user' || $view == 'recent' || $view == 'search') {
            // What function do we call for this view?
            $function = '_get_data_by_' . $view;
            // Get all the dta that's to be rendered.
            $data = $this->$function($args);

        } else {
            // Don't have a valid $view;.
            $PAGE->error_message("You haven't specified a view type.");
            return false;
        }

        if ($view == 'user') {
            $template = 'comments_user';
        } elseif ($view == 'recent') {
            $template = 'comments_recent';
        } elseif ($view == 'search') {
            $template = 'comments_search';
        } else {
            $template = 'comments';
        }

        $this->render($data, $format, $template);

        return true;
    }

    /**
     *
     */
    public function render($data, $format = 'html', $template = 'comments') {
        $valid_formats = ['html', 'api'];
        if (!in_array($format, $valid_formats, true)) {
            $format = 'html';
        }

        include __DIR__ . '/../easyparliament/templates/' . $format . '/' . $template . '.php';
    }

    /**
     *
     */
    public function _get_data_by_ep($args) {
        // Get all the data attached to an epobject.
        global $PAGE;

        twfy_debug(get_class($this), "getting data by epobject");

        // What we return.
        $data = [];
        if (!is_numeric($args['epobject_id'])) {
            $PAGE->error_message("Sorry, we don't have a valid epobject id");
            return $data;
        }

        // For getting the data.
        $input = [
            'amount' => [
                'user' => true
            ],
            'where' => [
                'comments.epobject_id=' => $args['epobject_id'],
                // 'visible=' => '1'
            ],
            'order' => 'posted ASC'
        ];

        $commentsdata = $this->_get_comment_data($input);

        $data['comments'] = $commentsdata;

        if (isset($args['user_id']) && $args['user_id'] != '') {
            // We'll pass this on to the template so it can highlight the user's comments.
            $data['info']['user_id'] = $args['user_id'];
        }

        return $data;

    }

    /**
     *
     */
    public function _get_data_by_user($args) {
        // Get a user's most recent comments.
        global $PAGE;

        twfy_debug(get_class($this), "getting data by user");

        // What we return.
        $data = [];

        if (!is_numeric($args['user_id'])) {
            $PAGE->error_message("Sorry, we don't have a valid user id");
            return $data;
        }

        if (isset($args['num']) && is_numeric($args['num'])) {
            $num = (int) $args['num'];
        } else {
            $num = 10;
        }

        if (isset($args['page']) && is_numeric($args['page'])) {
            $page = (int) $args['page'];
        } else {
            $page = 1;
        }

        $offset = $num * ($page - 1);

        // Get the most recent comments grouped by epobject, with hansard/speaker details.
        $rows = Comments::query()
          ->selectRaw('MAX(comments.comment_id) AS comment_id')
          ->selectRaw('MAX(comments.posted) AS posted')
          ->selectRaw('COUNT(*) AS total_comments')
          ->addSelect([
                'comments.epobject_id',
                'hansard.major',
                'hansard.gid',
                'users.firstname',
                'users.lastname',
                'epobject.body',
                'member.first_name',
                'member.last_name',
            ])
          ->join('users', 'comments.user_id', '=', 'users.user_id')
          ->join('epobject', 'comments.epobject_id', '=', 'epobject.epobject_id')
          ->join('hansard', 'comments.epobject_id', '=', 'hansard.epobject_id')
          ->leftJoin('member', 'hansard.speaker_id', '=', 'member.member_id')
          ->where('users.user_id', $args['user_id'])
          ->where('visible', 1)
          ->groupBy('comments.epobject_id')
          ->orderByDesc('posted')
          ->limit($num)
          ->offset($offset)
          ->get();

        $comments = [];
        $comment_ids = [];

        foreach ($rows as $n => $row) {
            $comments[$n] = [
                'comment_id' => $row->comment_id,
                'posted' => $row->posted,
                'total_comments' => $row->total_comments,
                'epobject_id' => $row->epobject_id,
                'firstname' => $row->firstname,
                'lastname' => $row->lastname,
                'hbody' => $row->body,
                'speaker' => [
                    'first_name' => $row->first_name,
                    'last_name' => $row->last_name,
                ],
            ];

            $urldata = [
                'major' => $row->major,
                'gid' => $row->gid,
                'comment_id' => $row->comment_id,
                'user_id' => $args['user_id'],
            ];

            $comments[$n]['url'] = $this->_comment_url($urldata);
            $comment_ids[] = $row->comment_id;
        }

        if (!empty($comment_ids)) {
            $bodies = Comments::whereIn('comment_id', $comment_ids)
              ->pluck('body', 'comment_id');

            foreach ($comments as $n => $commentdata) {
                $comments[$n]['body'] = $bodies[$comments[$n]['comment_id']] ?? '';
            }
        }

        $data['comments'] = $comments;
        $data['results_per_page'] = $num;
        $data['page'] = $page;
        $data['total_results'] = Comments::where('visible', 1)
          ->where('user_id', $args['user_id'])
          ->distinct('epobject_id')
          ->count('epobject_id');
        return $data;

    }

    /**
     *
     */
    public function _get_data_by_recent($args) {
        // $args should contain 'num', indicating how many to get.
        // and perhaps pid too, for a particular person

        twfy_debug(get_class($this), "getting data by recent");

        // What we return.
        $data = [];

        if (isset($args['num']) && is_numeric($args['num'])) {
            $num = $args['num'];
        } else {
            $num = 25;
        }

        if (isset($args['page']) && is_numeric($args['page'])) {
            $page = $args['page'];
        } else {
            $page = 1;
        }

        $limit = $num * ($page - 1) . ',' . $num;

        $where = [
            'visible=' => '1'
        ];
        if (isset($args['pid']) && is_numeric($args['pid'])) {
            $where['person_id='] = $args['pid'];
        }
        $input = [
            'amount' => [
                'user' => true
            ],
            'where' => $where,
            'order' => 'posted DESC',
            'limit' => $limit
        ];

        $commentsdata = $this->_get_comment_data($input);

        $data['comments'] = $commentsdata;
        $data['results_per_page'] = $num;
        $data['page'] = $page;
        if (isset($args['pid']) && is_numeric($args['pid'])) {
            $data['pid'] = $args['pid'];
            $member = Member::where('left_house', '9999-12-31')
              ->where('person_id', $args['pid'])
              ->first(['title', 'first_name', 'last_name', 'constituency', 'house']);
            $data['full_name'] = member_full_name(
                $member->house ?? '',
                $member->title ?? '',
                $member->first_name ?? '',
                $member->last_name ?? '',
                $member->constituency ?? ''
            );
            $data['total_results'] = Comments::where('visible', 1)
              ->join('hansard', 'comments.epobject_id', '=', 'hansard.epobject_id')
              ->join('member', 'hansard.speaker_id', '=', 'member.member_id')
              ->where('member.person_id', $args['pid'])
              ->count();
        } else {
            $data['total_results'] = Comments::where('visible', 1)->count();
        }
        return $data;
    }

    /**
     *
     */
    public function _get_data_by_search($args) {
        // $args should contain 'num', indicating how many to get.

        twfy_debug(get_class($this), "getting data by search");

        // What we return.
        $data = [];

        if (isset($args['num']) && is_numeric($args['num'])) {
            $num = $args['num'];
        } else {
            $num = 10;
        }

        if (isset($args['page']) && is_numeric($args['page'])) {
            $page = $args['page'];
        } else {
            $page = 1;
        }

        $limit = $num * ($page - 1) . ',' . $num;

        $input = [
            'amount' => [
                'user' => true
            ],
            'where' => [
                'comments.body LIKE' => "%$args[s]%"
            ],
            'order' => 'posted DESC',
            'limit' => $limit
        ];

        $commentsdata = $this->_get_comment_data($input);

        $data['comments'] = $commentsdata;
        $data['search'] = $args['s'];
        return $data;
    }

    /**
     *
     */
    public function _comment_url($urldata) {
        global $hansardmajors;

        // Pass it the major and gid of the comment's epobject and the comment_id.
        // And optionally the user's id, for highlighting the comments on the destination page.
        // It returns the URL for the comment.

        $major = $urldata['major'];
        $gid = $urldata['gid'];
        $comment_id = $urldata['comment_id'];
        $user_id = $urldata['user_id'] ?? false;

        // If you change stuff here, you might have to change it in
        // $COMMENT->_set_url() too...

        // We'll generate permalinks for each comment.
        // Assuming every comment is from the same major...
        $page = $hansardmajors[$major]['page'];
        $gidvar = $hansardmajors[$major]['gidvar'];

        $URL = new URL($page);

        // In includes/utility.php.
        $gid = fix_gid_from_db($gid);
        $URL->insert([$gidvar => $gid]);
        if ($user_id) {
            $URL->insert(['u' => $user_id]);
        }
        $url = $URL->generate() . '#c' . $comment_id;

        return $url;
    }

    /**
     *
     */
public function _get_comment_data($input) {
        // Generic function for getting hansard data from the DB.
        // It returns an empty array if no data was found.
        // It returns an array of items if 1 or more were found.
        // Each item is an array of key/value pairs.
        // eg:
        /*
        array (
        0    => array (
        'comment_id'    => '2',
        'user_id'        => '10',
        'body'            => 'The text of the comment is here.',
        etc...
        ),
        1    => array (
        'comment_id'    => '3',
        etc...
        )
        );
         */

        // $input is an array of things needed for the SQL query:
        // 'amount' has one or more of :
        // 'user'=>true - Users' names.
        // 'hansard'=>true - Body text from the hansard items.
        // 'where' is an associative array of stuff for the WHERE clause, eg:
        // array ('id=' => '37', 'posted>' => '2003-12-31 00:00:00');
        // 'order' is a string for the $order clause, eg 'hpos DESC'.
        // 'limit' as a string for the $limit clause, eg '21,20'.

        $amount = $input['amount'] ?? [];
        $wherearr = $input['where'];
        $order = $input['order'] ?? '';
        $limit = $input['limit'] ?? '';

        // The fields to fetch from db. 'table' => array ('field1', 'field2').
        $fieldsarr = [
            'comments' => ['comment_id', 'user_id', 'epobject_id', 'body', 'posted', 'modflagged', 'visible'],
            'hansard' => ['major', 'gid']
        ];

        // Yes, we need the gid of a comment's associated hansard object
        // to make the comment's URL. And we have to go via the epobject
        // table to do that.
        $join = 'INNER JOIN epobject ON comments.epobject_id = epobject.epobject_id
					INNER JOIN hansard ON comments.epobject_id = hansard.epobject_id';

        // Add on the stuff for getting a user's details.
        if (!empty($amount['user'])) {
            $fieldsarr['users'] = ['firstname', 'lastname', 'user_id'];
            // Like doing "FROM comments, users" but it's easier to add
            // an "INNER JOIN..." automatically to the query.
            $join .= ' INNER JOIN users ON comments.user_id = users.user_id ';
        }

        // Add on that we need to get the hansard item's body.
        if (!empty($amount['hansard'])) {
            $fieldsarr['epobject'] = ['body'];
            $fieldsarr['member'] = ['first_name', 'last_name'];
            $join .= ' LEFT OUTER JOIN member ON hansard.speaker_id = member.member_id';
        }

        if (isset($wherearr['person_id='])) {
            $join .= ' INNER JOIN member ON hansard.speaker_id = member.member_id';
        }

        $fieldsarr2 = [];
        // Construct the $fields clause.
        foreach ($fieldsarr as $table => $tablesfields) {
            foreach ($tablesfields as $n => $field) {
                // HACK.
                // If we're getting the body of a hansard object, we need to
                // get it AS 'hbody', so we don't confuse with the comment's 'body'
                // element.
                if ($table == 'epobject' && $field == 'body') {
                    $field .= ' AS hbody';
                }
                $fieldsarr2[] = $table . '.' . $field;
            }
        }
        $fields = implode(', ', $fieldsarr2);

        $wherearr2 = [];
        // Construct the $where clause.
        // FIXME: parameterise this query.
        foreach ($wherearr as $key => $val) {
            $wherearr2[] = "$key'" . addslashes($val) . "'";
        }
        $where = implode(" AND ", $wherearr2);

        if ($order != '') {
            $order = "ORDER BY $order";
        }
        if ($limit != '') {
            $limit = "LIMIT $limit";
        }

        // Finally, do the query!
        $q = parlDBQuery("SELECT $fields
						FROM 	comments
						$join
						WHERE $where
						$order
						$limit
						");

        // Format the data into an array for returning.
        $data = [];

        if ($q->rows() > 0) {

            // If you change stuff here, you might have to change it in
            // $COMMENT->_set_url() too...

            // We'll generate permalinks for each comment.
            // Assuming every comment is from the same major...

            for ($n = 0; $n < $q->rows(); $n++) {

                // Put each row returned into its own array in $data.
                foreach ($fieldsarr as $table => $tablesfields) {
                    foreach ($tablesfields as $m => $field) {

                        // HACK 2.
                        // If we're getting the body of a hansard object, we have
                        // got it AS 'hbody', so we didn't duplicate the comment's 'body'
                        // element.
                        if ($table == 'epobject' && $field == 'body') {
                            $field = 'hbody';
                        }

                        $data[$n][$field] = $q->field($n, $field);
                    }
                }

                $urldata = [
                    'major' => $q->field($n, 'major'),
                    'gid' => $data[$n]['gid'],
                    'comment_id' => $data[$n]['comment_id'],
                    // 'user_id' =>
                ];
                $data[$n]['url'] = $this->_comment_url($urldata);
            }
        }

        return $data;

}

}
