<?php

/**
 * @file
 * [From the WIKI]
 * The EDITQUEUE is a holding point for submitted content additions or modifications before they are made on the core data. It's quite a wide table, so as to hold as many different types of addition/modification as possible.
 *
 * These hold details of actions waiting to be approved, such as:
 *
 * creation or modification of glossary entries
 * creation or modification of attributes
 * associations
 * anything else?
 *
 * Specifying which of the above is happening is down to the edit_type field.
 *
 * edit_id is the unique id of the EDITQUEUE record
 * user_id is the id of the user who submitted the edit
 * edit_type is the kind of edit being made
 * epobject_id_l and epobject_id_h hold both eopbject_ids in the case of a new association, with epobject_id_l holding the single epobject_id if the edit only applies to one object. If there's a completely new object being submitted, this is left blank until the content is approved and then filled in with the new object id.
 * time_start and time_end are for associations
 * title and body are for new or changed content for a glossary entry
 * submitted is the datetime that the content was initially submitted by the user
 * editor_id is the id of the editor who made the decision on it
 * approved says yay or nay to the edit
 * decided is the datetime when the decision was made
 *
 *
 * While we're here...
 * Whenever a term is added to the glossary, it appears in the editqueue first.
 * Here it sits until a moderator has approved it, then it goes on it's merry way
 * to whichever db it be bound.
 *
 * Functions:
 *
 * add()            - pop one on the queue (should then alert moderators somehow)
 *
 * Moderators approve and decline submissions in the admin section, through the Editqueue model.
 */

/**
 * [TODO] what happens when two things with the same name are in the editqueue?
 */
class EDITQUEUE {


    public $editqueue_id = '';
    public $title = '';
    public $body = '';
    public $posted = '';

    /**
     *
     */
    public function __construct() {

    }

    /**
     *
     */
    public function add($data) {
        // This does the bare minimum.
        // The upper object should make sure it's passsing good data.
        // (for now!)

        /*
        For editqueue in this instance we need:
            user_id INTEGER,
            edit_type INTEGER,
            (epobject_id_l),
            title VARCHAR(255),
            body TEXT,
            submitted DATETIME,.
        */

        global $THEUSER;

        $q = parlDBQuery("INSERT INTO editqueue
						(user_id, edit_type, title, body, submitted)
						VALUES
						(?, ?, ?, ?, ?);",
            $THEUSER->user_id(), $data['edit_type'], $data['title'], $data['body'], $data['posted']);

        if ($q->success()) {
            // Set the object variables up.
            $this->editqueue_id = $q->insert_id();
            $this->title = $data['title'];
            $this->body = $data['body'];
            $this->posted = $data['posted'];

            return $this->editqueue_id;

        } else {
            return false;
        }
    }

}


/**
 * The edit queue for glossary terms.
 */
class GLOSSEDITQUEUE extends EDITQUEUE {

}
