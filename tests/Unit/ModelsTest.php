<?php

use PHPUnit\Framework\TestCase;
use OpenAustralia\TWFY\Models\Bills;
use OpenAustralia\TWFY\Models\Commentreport;
use OpenAustralia\TWFY\Models\Comments;
use OpenAustralia\TWFY\Models\Constituency;
use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Hansard;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\Moffice;
use OpenAustralia\TWFY\Models\SearchQueryLog;
use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\Models\Uservotes;
use OpenAustralia\TWFY\Models\PostcodeLookup;
use OpenAustralia\TWFY\Models\Alert;
use OpenAustralia\TWFY\Models\Anonvote;
use OpenAustralia\TWFY\Models\ApiKey;
use OpenAustralia\TWFY\Models\ApiStat;
use OpenAustralia\TWFY\Models\Consinfo;
use OpenAustralia\TWFY\Models\Editqueue;
use OpenAustralia\TWFY\Models\Gidredirect;
use OpenAustralia\TWFY\Models\Glossary;
use OpenAustralia\TWFY\Models\Indexbatch;
use OpenAustralia\TWFY\Models\Memberinfo;
use OpenAustralia\TWFY\Models\Mention;
use OpenAustralia\TWFY\Models\PbcMember;
use OpenAustralia\TWFY\Models\Personinfo;
use OpenAustralia\TWFY\Models\Title;
use OpenAustralia\TWFY\Models\VideoTimestamp;

/**
 *
 */
class ModelsTest extends TestCase {

    // --- Bills ---

    public function test_bills_table_name(): void {
        $model = new Bills();
        $this->assertSame('bills', $model->getTable());
    }

    public function test_bills_primary_key(): void {
        $model = new Bills();
        $this->assertSame('id', $model->getKeyName());
    }

    public function test_bills_uses_timestamps(): void {
        $model = new Bills();
        $this->assertTrue($model->usesTimestamps());
    }

    public function test_bills_fillable(): void {
        $model = new Bills();
        $this->assertContains('title', $model->getFillable());
        $this->assertContains('url', $model->getFillable());
        $this->assertContains('session', $model->getFillable());
    }

    public function test_bills_casts_lords_as_bool(): void {
        $model = new Bills();
        $casts = $model->getCasts();
        $this->assertSame('bool', $casts['lords']);
    }

    // --- Commentreport ---

    public function test_commentreport_table_name(): void {
        $model = new Commentreport();
        $this->assertSame('commentreports', $model->getTable());
    }

    public function test_commentreport_primary_key(): void {
        $model = new Commentreport();
        $this->assertSame('report_id', $model->getKeyName());
    }

    public function test_commentreport_has_timestamps(): void {
        $model = new Commentreport();
        $this->assertTrue($model->usesTimestamps());
    }

    // --- Comments ---

    public function test_comments_table_name(): void {
        $model = new Comments();
        $this->assertSame('comments', $model->getTable());
    }

    public function test_comments_primary_key(): void {
        $model = new Comments();
        $this->assertSame('comment_id', $model->getKeyName());
    }

    public function test_comments_fillable(): void {
        $model = new Comments();
        $this->assertContains('user_id', $model->getFillable());
        $this->assertContains('body', $model->getFillable());
        $this->assertContains('visible', $model->getFillable());
    }

    public function test_comments_casts(): void {
        $model = new Comments();
        $casts = $model->getCasts();
        $this->assertSame('datetime', $casts['posted']);
        $this->assertSame('bool', $casts['visible']);
    }

    // --- Constituency ---

    public function test_constituency_table_name(): void {
        $model = new Constituency();
        $this->assertSame('constituency', $model->getTable());
    }

    public function test_constituency_fillable(): void {
        $model = new Constituency();
        $this->assertContains('name', $model->getFillable());
        $this->assertContains('cons_id', $model->getFillable());
    }

    public function test_constituency_casts(): void {
        $model = new Constituency();
        $casts = $model->getCasts();
        $this->assertSame('date', $casts['from_date']);
        $this->assertSame('date', $casts['to_date']);
        $this->assertSame('bool', $casts['main_name']);
    }

    // --- Epobject ---

    public function test_epobject_table_name(): void {
        $model = new Epobject();
        $this->assertSame('epobject', $model->getTable());
    }

    public function test_epobject_primary_key(): void {
        $model = new Epobject();
        $this->assertSame('epobject_id', $model->getKeyName());
    }

    public function test_epobject_has_timestamps(): void {
        $model = new Epobject();
        $this->assertTrue($model->usesTimestamps());
    }

    public function test_epobject_fillable(): void {
        $model = new Epobject();
        $this->assertContains('title', $model->getFillable());
        $this->assertContains('body', $model->getFillable());
    }

    // --- Hansard ---

    public function test_hansard_table_name(): void {
        $model = new Hansard();
        $this->assertSame('hansard', $model->getTable());
    }

    public function test_hansard_primary_key(): void {
        $model = new Hansard();
        $this->assertSame('epobject_id', $model->getKeyName());
    }

    public function test_hansard_not_incrementing(): void {
        $model = new Hansard();
        $this->assertFalse($model->getIncrementing());
    }

    public function test_hansard_has_timestamps(): void {
        $model = new Hansard();
        $this->assertTrue($model->usesTimestamps());
    }

    public function test_hansard_fillable(): void {
        $model = new Hansard();
        $this->assertContains('gid', $model->getFillable());
        $this->assertContains('htype', $model->getFillable());
        $this->assertContains('speaker_id', $model->getFillable());
        $this->assertContains('major', $model->getFillable());
        $this->assertContains('hdate', $model->getFillable());
    }

    public function test_hansard_casts(): void {
        $model = new Hansard();
        $casts = $model->getCasts();
        $this->assertSame('date', $casts['hdate']);
        $this->assertSame('datetime', $casts['created']);
        $this->assertSame('datetime', $casts['modified']);
    }

    // --- Member ---

    public function test_member_table_name(): void {
        $model = new Member();
        $this->assertSame('member', $model->getTable());
    }

    public function test_member_primary_key(): void {
        $model = new Member();
        $this->assertSame('member_id', $model->getKeyName());
    }

    public function test_member_not_incrementing(): void {
        $model = new Member();
        $this->assertFalse($model->getIncrementing());
    }

    // --- Moffice ---

    public function test_moffice_table_name(): void {
        $model = new Moffice();
        $this->assertSame('moffice', $model->getTable());
    }

    public function test_moffice_primary_key(): void {
        $model = new Moffice();
        $this->assertSame('moffice_id', $model->getKeyName());
    }

    public function test_moffice_has_timestamps(): void {
        $model = new Moffice();
        $this->assertTrue($model->usesTimestamps());
    }

    public function test_moffice_fillable(): void {
        $model = new Moffice();
        $this->assertContains('dept', $model->getFillable());
        $this->assertContains('position', $model->getFillable());
        $this->assertContains('person', $model->getFillable());
    }

    public function test_moffice_casts(): void {
        $model = new Moffice();
        $casts = $model->getCasts();
        $this->assertSame('int', $casts['moffice_id']);
        $this->assertSame('int', $casts['person']);
        $this->assertSame('date', $casts['from_date']);
        $this->assertSame('date', $casts['to_date']);
    }

    // --- SearchQueryLog ---

    public function test_search_query_log_table_name(): void {
        $model = new SearchQueryLog();
        $this->assertSame('search_query_log', $model->getTable());
    }

    public function test_search_query_log_has_timestamps(): void {
        $model = new SearchQueryLog();
        $this->assertTrue($model->usesTimestamps());
    }

    public function test_search_query_log_fillable(): void {
        $model = new SearchQueryLog();
        $this->assertContains('query_string', $model->getFillable());
        $this->assertContains('page_number', $model->getFillable());
        $this->assertContains('count_hits', $model->getFillable());
        $this->assertContains('ip_address', $model->getFillable());
        $this->assertContains('query_time', $model->getFillable());
    }

    // --- User ---

    public function test_user_table_name(): void {
        $model = new User();
        $this->assertSame('users', $model->getTable());
    }

    public function test_user_primary_key(): void {
        $model = new User();
        $this->assertSame('user_id', $model->getKeyName());
    }

    public function test_user_fillable(): void {
        $model = new User();
        $this->assertContains('firstname', $model->getFillable());
        $this->assertContains('email', $model->getFillable());
        $this->assertContains('password', $model->getFillable());
    }

    public function test_user_hidden_fields(): void {
        $model = new User();
        $this->assertContains('password', $model->getHidden());
        $this->assertContains('registrationtoken', $model->getHidden());
        $this->assertContains('api_key', $model->getHidden());
    }

    public function test_user_casts(): void {
        $model = new User();
        $casts = $model->getCasts();
        $this->assertSame('bool', $casts['deleted']);
        $this->assertSame('bool', $casts['confirmed']);
        $this->assertSame('datetime', $casts['lastvisit']);
    }

    // --- Uservotes ---

    public function test_uservotes_table_name(): void {
        $model = new Uservotes();
        $this->assertSame('uservotes', $model->getTable());
    }

    public function test_uservotes_not_incrementing(): void {
        $model = new Uservotes();
        $this->assertFalse($model->getIncrementing());
    }

    public function test_uservotes_fillable(): void {
        $model = new Uservotes();
        $this->assertContains('user_id', $model->getFillable());
        $this->assertContains('epobject_id', $model->getFillable());
        $this->assertContains('vote', $model->getFillable());
    }

    public function test_uservotes_casts_vote_as_int(): void {
        $model = new Uservotes();
        $casts = $model->getCasts();
        $this->assertSame('int', $casts['vote']);
    }

    // --- PostcodeLookup ---

    public function test_postcode_lookup_table_name(): void {
        $model = new PostcodeLookup();
        $this->assertSame('postcode_lookup', $model->getTable());
    }

    public function test_postcode_lookup_has_timestamps(): void {
        $model = new PostcodeLookup();
        $this->assertTrue($model->usesTimestamps());
    }

    public function test_postcode_lookup_is_not_incrementing(): void {
        $model = new PostcodeLookup();
        $this->assertFalse($model->getIncrementing());
    }

    public function test_postcode_lookup_fillable(): void {
        $model = new PostcodeLookup();
        $this->assertContains('postcode', $model->getFillable());
        $this->assertContains('name', $model->getFillable());
    }

    // --- Alert ---

    public function test_alert_table_name(): void {
        $model = new Alert();
        $this->assertSame('alerts', $model->getTable());
    }

    public function test_alert_primary_key(): void {
        $model = new Alert();
        $this->assertSame('alert_id', $model->getKeyName());
    }

    public function test_alert_has_timestamps(): void {
        $model = new Alert();
        $this->assertTrue($model->usesTimestamps());
    }

    public function test_alert_fillable(): void {
        $model = new Alert();
        $this->assertContains('email', $model->getFillable());
        $this->assertContains('criteria', $model->getFillable());
    }

    public function test_alert_casts(): void {
        $model = new Alert();
        $casts = $model->getCasts();
        $this->assertSame('bool', $casts['deleted']);
        $this->assertSame('bool', $casts['confirmed']);
    }

    // --- Anonvote ---

    public function test_anonvote_table_name(): void {
        $model = new Anonvote();
        $this->assertSame('anonvotes', $model->getTable());
    }

    public function test_anonvote_primary_key(): void {
        $model = new Anonvote();
        $this->assertSame('epobject_id', $model->getKeyName());
        $this->assertFalse($model->getIncrementing());
    }

    public function test_anonvote_casts(): void {
        $model = new Anonvote();
        $casts = $model->getCasts();
        $this->assertSame('int', $casts['yes_votes']);
        $this->assertSame('int', $casts['no_votes']);
    }

    // --- ApiKey ---

    public function test_api_key_table_name(): void {
        $model = new ApiKey();
        $this->assertSame('api_key', $model->getTable());
    }

    public function test_api_key_fillable(): void {
        $model = new ApiKey();
        $this->assertContains('api_key', $model->getFillable());
        $this->assertContains('user_id', $model->getFillable());
    }

    // --- ApiStat ---

    public function test_api_stat_table_name(): void {
        $model = new ApiStat();
        $this->assertSame('api_stats', $model->getTable());
    }

    public function test_api_stat_fillable(): void {
        $model = new ApiStat();
        $this->assertContains('api_key', $model->getFillable());
        $this->assertContains('query', $model->getFillable());
    }

    // --- Consinfo ---

    public function test_consinfo_table_name(): void {
        $model = new Consinfo();
        $this->assertSame('consinfo', $model->getTable());
    }

    public function test_consinfo_not_incrementing(): void {
        $model = new Consinfo();
        $this->assertFalse($model->getIncrementing());
    }

    public function test_consinfo_fillable(): void {
        $model = new Consinfo();
        $this->assertContains('constituency', $model->getFillable());
        $this->assertContains('data_key', $model->getFillable());
        $this->assertContains('data_value', $model->getFillable());
    }

    // --- Editqueue ---

    public function test_editqueue_table_name(): void {
        $model = new Editqueue();
        $this->assertSame('editqueue', $model->getTable());
    }

    public function test_editqueue_primary_key(): void {
        $model = new Editqueue();
        $this->assertSame('edit_id', $model->getKeyName());
    }

    public function test_editqueue_fillable(): void {
        $model = new Editqueue();
        $this->assertContains('title', $model->getFillable());
        $this->assertContains('body', $model->getFillable());
    }

    // --- Gidredirect ---

    public function test_gidredirect_table_name(): void {
        $model = new Gidredirect();
        $this->assertSame('gidredirect', $model->getTable());
    }

    public function test_gidredirect_primary_key(): void {
        $model = new Gidredirect();
        $this->assertSame('gid_from', $model->getKeyName());
        $this->assertSame('string', $model->getKeyType());
        $this->assertFalse($model->getIncrementing());
    }

    public function test_gidredirect_fillable(): void {
        $model = new Gidredirect();
        $this->assertContains('gid_from', $model->getFillable());
        $this->assertContains('gid_to', $model->getFillable());
    }

    // --- Glossary ---

    public function test_glossary_table_name(): void {
        $model = new Glossary();
        $this->assertSame('glossary', $model->getTable());
    }

    public function test_glossary_primary_key(): void {
        $model = new Glossary();
        $this->assertSame('glossary_id', $model->getKeyName());
    }

    public function test_glossary_fillable(): void {
        $model = new Glossary();
        $this->assertContains('title', $model->getFillable());
        $this->assertContains('body', $model->getFillable());
    }

    // --- Indexbatch ---

    public function test_indexbatch_table_name(): void {
        $model = new Indexbatch();
        $this->assertSame('indexbatch', $model->getTable());
    }

    public function test_indexbatch_primary_key(): void {
        $model = new Indexbatch();
        $this->assertSame('indexbatch_id', $model->getKeyName());
    }

    // --- Memberinfo ---

    public function test_memberinfo_table_name(): void {
        $model = new Memberinfo();
        $this->assertSame('memberinfo', $model->getTable());
    }

    public function test_memberinfo_not_incrementing(): void {
        $model = new Memberinfo();
        $this->assertFalse($model->getIncrementing());
    }

    public function test_memberinfo_fillable(): void {
        $model = new Memberinfo();
        $this->assertContains('member_id', $model->getFillable());
        $this->assertContains('data_key', $model->getFillable());
        $this->assertContains('data_value', $model->getFillable());
    }

    // --- Mention ---

    public function test_mention_table_name(): void {
        $model = new Mention();
        $this->assertSame('mentions', $model->getTable());
    }

    public function test_mention_primary_key(): void {
        $model = new Mention();
        $this->assertSame('mention_id', $model->getKeyName());
    }

    public function test_mention_fillable(): void {
        $model = new Mention();
        $this->assertContains('gid', $model->getFillable());
        $this->assertContains('type', $model->getFillable());
        $this->assertContains('url', $model->getFillable());
    }

    // --- PbcMember ---

    public function test_pbc_member_table_name(): void {
        $model = new PbcMember();
        $this->assertSame('pbc_members', $model->getTable());
    }

    public function test_pbc_member_fillable(): void {
        $model = new PbcMember();
        $this->assertContains('member_id', $model->getFillable());
        $this->assertContains('bill_id', $model->getFillable());
    }

    public function test_pbc_member_casts(): void {
        $model = new PbcMember();
        $casts = $model->getCasts();
        $this->assertSame('bool', $casts['chairman']);
        $this->assertSame('bool', $casts['attending']);
    }

    // --- Personinfo ---

    public function test_personinfo_table_name(): void {
        $model = new Personinfo();
        $this->assertSame('personinfo', $model->getTable());
    }

    public function test_personinfo_not_incrementing(): void {
        $model = new Personinfo();
        $this->assertFalse($model->getIncrementing());
    }

    public function test_personinfo_fillable(): void {
        $model = new Personinfo();
        $this->assertContains('person_id', $model->getFillable());
        $this->assertContains('data_key', $model->getFillable());
        $this->assertContains('data_value', $model->getFillable());
    }

    // --- Title ---

    public function test_title_table_name(): void {
        $model = new Title();
        $this->assertSame('titles', $model->getTable());
    }

    public function test_title_primary_key(): void {
        $model = new Title();
        $this->assertSame('title', $model->getKeyName());
        $this->assertSame('string', $model->getKeyType());
        $this->assertFalse($model->getIncrementing());
    }

    public function test_title_fillable(): void {
        $model = new Title();
        $this->assertContains('title', $model->getFillable());
    }

    // --- VideoTimestamp ---

    public function test_video_timestamp_table_name(): void {
        $model = new VideoTimestamp();
        $this->assertSame('video_timestamps', $model->getTable());
    }

    public function test_video_timestamp_fillable(): void {
        $model = new VideoTimestamp();
        $this->assertContains('gid', $model->getFillable());
        $this->assertContains('user_id', $model->getFillable());
        $this->assertContains('atime', $model->getFillable());
    }

    public function test_video_timestamp_casts(): void {
        $model = new VideoTimestamp();
        $casts = $model->getCasts();
        $this->assertSame('bool', $casts['deleted']);
    }

}
