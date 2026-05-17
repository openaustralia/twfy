<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/commentlist.php';

use PHPUnit\Framework\TestCase;

class TestableCommentList extends COMMENTLIST {

    public array $capturedInput = [];
    private array $commentDataToReturn;

    public function __construct(array $commentDataToReturn = []) {
        $this->commentDataToReturn = $commentDataToReturn;
    }

    public function _get_comment_data($input) {
        $this->capturedInput = $input;
        return $this->commentDataToReturn;
    }

}

class CommentListTest extends TestCase {

    public function test_get_data_by_search_uses_defaults_and_returns_search_payload(): void {
        $expectedComments = [
            ['comment_id' => '1', 'body' => 'Budget comment'],
        ];
        $commentList = new TestableCommentList($expectedComments);

        $data = $commentList->_get_data_by_search(['s' => 'budget']);

        $this->assertSame($expectedComments, $data['comments']);
        $this->assertSame('budget', $data['search']);
        $this->assertSame(
            [
                'amount' => ['user' => TRUE],
                'where' => ['comments.body LIKE' => '%budget%'],
                'order' => 'posted DESC',
                'limit' => '0,10',
            ],
            $commentList->capturedInput
        );
    }

    public function test_get_data_by_search_respects_num_and_page_arguments(): void {
        $commentList = new TestableCommentList([]);

        $commentList->_get_data_by_search([
            's' => 'health',
            'num' => 25,
            'page' => 3,
        ]);

        $this->assertSame('%health%', $commentList->capturedInput['where']['comments.body LIKE']);
        $this->assertSame('50,25', $commentList->capturedInput['limit']);
        $this->assertSame('posted DESC', $commentList->capturedInput['order']);
    }

    public function test_get_data_by_search_falls_back_for_non_numeric_num_and_page(): void {
        $commentList = new TestableCommentList([]);

        $commentList->_get_data_by_search([
            's' => 'climate',
            'num' => 'abc',
            'page' => 'xyz',
        ]);

        $this->assertSame('0,10', $commentList->capturedInput['limit']);
    }

    public function test_get_data_by_search_wraps_special_characters_in_like_wildcards(): void {
        $commentList = new TestableCommentList([]);

        $data = $commentList->_get_data_by_search(['s' => "children's services"]);

        $this->assertSame("children's services", $data['search']);
        $this->assertSame(
            "%children's services%",
            $commentList->capturedInput['where']['comments.body LIKE']
        );
    }

}
