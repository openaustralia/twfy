<?php

/**
 * @file
 * Tests for the SEARCHENGINE class (www/includes/easyparliament/searchengine.php)
 *
 * These tests call the actual SEARCHENGINE methods to verify query parsing,
 * highlighting, word position finding, and query reconstruction.
 */

use PHPUnit\Framework\TestCase;

// Define XAPIANDB so the constructor doesn't early-return
if (!defined('XAPIANDB')) {
    define('XAPIANDB', '/tmp/fake_xapian_db_for_tests');
}

// Stub XapianStem if Xapian bindings aren't installed
if (!class_exists('XapianStem')) {
    class XapianStem {
        public function __construct($language) {}
        public function stem_word($word) { return $word; }
    }
}


// Include dbtypes for $hansardmajors
require_once __DIR__ . '/../www/includes/dbtypes.php';

// Include the class under test
require_once __DIR__ . '/../www/includes/easyparliament/searchengine.php';

/**
 * Tests for SEARCHENGINE query parsing
 */
class SearchEngineTest extends TestCase {

    // --- Query Parsing Tests ---

    public function testSingleWordParsing(): void {
        $engine = new SEARCHENGINE('hello');

        $this->assertEquals(['hello'], $engine->words);
        $this->assertEmpty($engine->phrases);
        $this->assertEmpty($engine->prefixed);
        $this->assertEmpty($engine->excluded);
    }

    public function testMultipleWordsParsing(): void {
        $engine = new SEARCHENGINE('hello world');

        $this->assertEquals(['hello', 'world'], $engine->words);
        $this->assertEmpty($engine->phrases);
    }

    public function testPhraseParsing(): void {
        $engine = new SEARCHENGINE('"climate change"');

        $this->assertEmpty($engine->words);
        $this->assertCount(1, $engine->phrases);
        $this->assertEquals(['climate', 'change'], $engine->phrases[0]);
    }

    public function testMultiplePhrasesParsing(): void {
        $engine = new SEARCHENGINE('"climate change" "global warming"');

        $this->assertEmpty($engine->words);
        $this->assertCount(2, $engine->phrases);
        $this->assertEquals(['climate', 'change'], $engine->phrases[0]);
        $this->assertEquals(['global', 'warming'], $engine->phrases[1]);
    }

    public function testExcludedWordParsing(): void {
        $engine = new SEARCHENGINE('climate -denial');

        $this->assertEquals(['climate'], $engine->words);
        $this->assertEquals(['denial'], $engine->excluded);
    }

    public function testSpeakerPrefixParsing(): void {
        $engine = new SEARCHENGINE('speaker:10024');

        $this->assertEmpty($engine->words);
        $this->assertCount(1, $engine->prefixed);
        $this->assertEquals(['speaker', '10024'], $engine->prefixed[0]);
    }

    public function testSectionPrefixMapsToMajor(): void {
        $engine = new SEARCHENGINE('section:debates');
        $this->assertEquals(['major', '1'], $engine->prefixed[0]);

        $engine = new SEARCHENGINE('section:wrans');
        $this->assertEquals(['major', '3'], $engine->prefixed[0]);

        $engine = new SEARCHENGINE('section:lords');
        $this->assertEquals(['major', '101'], $engine->prefixed[0]);

        $engine = new SEARCHENGINE('section:wms');
        $this->assertEquals(['major', '4'], $engine->prefixed[0]);

        $engine = new SEARCHENGINE('section:ni');
        $this->assertEquals(['major', '5'], $engine->prefixed[0]);
    }

    public function testGroupbyPrefixParsing(): void {
        $engine = new SEARCHENGINE('test groupby:day');
        $found = false;
        foreach ($engine->prefixed as $item) {
            if ($item[0] === 'groupby' && $item[1] === 'day') {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testGroupbyDateNormalizesToDay(): void {
        $engine = new SEARCHENGINE('test groupby:date');
        foreach ($engine->prefixed as $item) {
            if ($item[0] === 'groupby') {
                $this->assertEquals('day', $item[1]);
            }
        }
    }

    public function testGroupbyDebateNormalization(): void {
        foreach (['debates', 'debate', 'department', 'departments', 'dept'] as $variant) {
            $engine = new SEARCHENGINE("test groupby:$variant");
            foreach ($engine->prefixed as $item) {
                if ($item[0] === 'groupby') {
                    $this->assertEquals('debate', $item[1], "groupby:$variant should normalize to 'debate'");
                }
            }
        }
    }

    public function testMixedQueryParsing(): void {
        $engine = new SEARCHENGINE('climate "global warming" speaker:10024 -denial');

        $this->assertEquals(['climate'], $engine->words);
        $this->assertEquals([['global', 'warming']], $engine->phrases);
        $this->assertEquals(['denial'], $engine->excluded);
        $this->assertCount(1, $engine->prefixed);
        $this->assertEquals(['speaker', '10024'], $engine->prefixed[0]);
    }

    public function testWordsAreLowercased(): void {
        $engine = new SEARCHENGINE('Hello WORLD');

        $this->assertEquals(['hello', 'world'], $engine->words);
    }

    public function testSectionRepresentativesMapToMajor1(): void {
        $engine = new SEARCHENGINE('section:representatives');
        $this->assertEquals(['major', '1'], $engine->prefixed[0]);
    }

    public function testSectionSenateMapToMajor101(): void {
        $engine = new SEARCHENGINE('section:senate');
        $this->assertEquals(['major', '101'], $engine->prefixed[0]);
    }

    // --- query_remade() Tests ---

    public function testSimpleWordsRemade(): void {
        $engine = new SEARCHENGINE('hello world');
        $remade = $engine->query_remade();

        $this->assertStringContainsString('hello', $remade);
        $this->assertStringContainsString('world', $remade);
        $this->assertStringContainsString('AND', $remade);
    }

    public function testPhraseRemade(): void {
        $engine = new SEARCHENGINE('"climate change"');
        $remade = $engine->query_remade();

        $this->assertStringContainsString('"climate change"', $remade);
    }

    public function testExcludedWordRemade(): void {
        $engine = new SEARCHENGINE('climate -denial');
        $remade = $engine->query_remade();

        $this->assertStringContainsString('climate', $remade);
        $this->assertStringContainsString('NOT', $remade);
        $this->assertStringContainsString('denial', $remade);
    }

    public function testPrefixedRemade(): void {
        $engine = new SEARCHENGINE('test speaker:10024');
        $remade = $engine->query_remade();

        $this->assertStringContainsString('speaker:10024', $remade);
        $this->assertStringContainsString('test', $remade);
    }

    public function testGroupbyNotIncludedInRemade(): void {
        $engine = new SEARCHENGINE('test groupby:day');
        $remade = $engine->query_remade();

        $this->assertStringNotContainsString('groupby', $remade);
    }

    public function testMultipleSamePrefixUsesOR(): void {
        $engine = new SEARCHENGINE('test speaker:10024 speaker:10025');
        $remade = $engine->query_remade();

        $this->assertStringContainsString('OR', $remade);
        $this->assertStringContainsString('speaker:10024', $remade);
        $this->assertStringContainsString('speaker:10025', $remade);
    }

    // --- highlight() Tests ---

    public function testHighlightsSingleWord(): void {
        $engine = new SEARCHENGINE('climate');
        $result = $engine->highlight('We discussed climate policy today.');

        $this->assertStringContainsString('<span class="hi">climate</span>', $result);
    }

    public function testHighlightIsCaseInsensitive(): void {
        $engine = new SEARCHENGINE('climate');
        $result = $engine->highlight('Climate change is real.');

        $this->assertStringContainsString('<span class="hi">Climate</span>', $result);
    }

    public function testHighlightsMultipleWords(): void {
        $engine = new SEARCHENGINE('climate change');
        $result = $engine->highlight('We need climate action to address change.');

        $this->assertStringContainsString('<span class="hi">climate</span>', $result);
        $this->assertStringContainsString('<span class="hi">change</span>', $result);
    }

    public function testHighlightsPhrase(): void {
        $engine = new SEARCHENGINE('"climate change"');
        $result = $engine->highlight('The climate change debate continues.');

        $this->assertStringContainsString('<span class="hi">', $result);
        $this->assertStringContainsString('climate', $result);
    }

    public function testHighlightPreservesNonMatchingText(): void {
        $engine = new SEARCHENGINE('climate');
        $result = $engine->highlight('The weather is nice today.');

        $this->assertStringNotContainsString('<span class="hi">', $result);
        $this->assertEquals('The weather is nice today.', $result);
    }

    public function testHighlightNumericWordWithCommas(): void {
        $engine = new SEARCHENGINE('1000');
        $result = $engine->highlight('The cost was 1,000 pounds.');

        $this->assertStringContainsString('<span class="hi">', $result);
    }

    // --- position_of_first_word() Tests ---

    public function testFindsWordPosition(): void {
        $engine = new SEARCHENGINE('climate');
        $pos = $engine->position_of_first_word('The debate on climate policy was heated.');

        $this->assertGreaterThan(0, $pos);
    }

    public function testReturnsZeroWhenWordNotFound(): void {
        $engine = new SEARCHENGINE('xyzzy');
        $pos = $engine->position_of_first_word('Nothing relevant here.');

        $this->assertEquals(0, $pos);
    }

    public function testFindsEarliestWord(): void {
        $engine = new SEARCHENGINE('later early');
        $body = 'first early then later in the text';
        $pos = $engine->position_of_first_word($body);

        // Should find "early" (position ~7) before "later" (position ~16)
        $this->assertGreaterThan(0, $pos);
        $this->assertLessThan(10, $pos);
    }

    public function testPhrasePositionFound(): void {
        $engine = new SEARCHENGINE('"climate change"');
        $pos = $engine->position_of_first_word('The issue of climate change is important.');

        $this->assertGreaterThan(0, $pos);
    }

    // --- make_phrase() Tests ---

    public function testMakePhraseJoinsWords(): void {
        $engine = new SEARCHENGINE('test');
        $result = $engine->make_phrase(['climate', 'change']);

        $this->assertEquals('"climate change"', $result);
    }

    public function testMakePhraseSingleWord(): void {
        $engine = new SEARCHENGINE('test');
        $result = $engine->make_phrase(['hello']);

        $this->assertEquals('"hello"', $result);
    }

    // --- stem() Tests ---

    public function testStemLowercases(): void {
        $engine = new SEARCHENGINE('test');
        $result = $engine->stem('HELLO');

        // With our stub XapianStem the word is just lowercased
        $this->assertEquals('hello', $result);
    }
}
