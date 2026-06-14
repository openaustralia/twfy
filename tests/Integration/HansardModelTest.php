<?php

/**
 * @file
 * Tests for Hansard model queries used across converted code.
 */

use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Hansard;

/**
 * Tests for Hansard model ORM queries.
 */
class HansardModelTest extends TransactionalTestCase {

    private static int $nextId = 80000;

    /**
     * Insert a minimal hansard record.
     */
    private function insertHansard(array $overrides = []): void {
        $id = self::$nextId++;
        $defaults = [
            'epobject_id' => $id,
            'gid' => 'uk.org.publicwhip/test.' . $id,
            'htype' => 12,
            'speaker_id' => 0,
            'major' => 1,
            'section_id' => 0,
            'subsection_id' => 0,
            'hpos' => 0,
            'hdate' => '2024-01-15',
            'source_url' => '',
        ];
        Hansard::query()->insert(array_merge($defaults, $overrides));
    }

    public function test_total_items_counts_by_major(): void {
        $this->insertHansard(['major' => 1]);
        $this->insertHansard(['major' => 1]);
        $this->insertHansard(['major' => 3]);

        $this->assertEquals(2, Hansard::where('major', 1)->count());
        $this->assertEquals(1, Hansard::where('major', 3)->count());
    }

    public function test_total_speeches_counts_htype_12(): void {
        $this->insertHansard(['major' => 1, 'htype' => 12]);
        $this->insertHansard(['major' => 1, 'htype' => 10]);
        $this->insertHansard(['major' => 1, 'htype' => 12]);

        $this->assertEquals(2, Hansard::where('major', 1)->where('htype', 12)->count());
    }

    public function test_total_questions_counts_minor_1(): void {
        $this->insertHansard(['major' => 3, 'minor' => 1]);
        $this->insertHansard(['major' => 3, 'minor' => 2]);
        $this->insertHansard(['major' => 3, 'minor' => 1]);

        $this->assertEquals(2, Hansard::where('major', 3)->where('minor', 1)->count());
    }

    public function test_min_max_hdate(): void {
        $this->insertHansard(['hdate' => '2020-03-01']);
        $this->insertHansard(['hdate' => '2023-06-15']);
        $this->insertHansard(['hdate' => '2021-11-20']);

        $this->assertEquals('2020-03-01', Hansard::min('hdate'));
        $this->assertEquals('2023-06-15', Hansard::max('hdate'));
    }

    public function test_distinct_hdate_count(): void {
        $this->insertHansard(['hdate' => '2024-01-01']);
        $this->insertHansard(['hdate' => '2024-01-01']);
        $this->insertHansard(['hdate' => '2024-01-02']);

        $this->assertEquals(2, Hansard::distinct()->count('hdate'));
    }

    public function test_next_date_after(): void {
        $this->insertHansard(['hdate' => '2024-01-10']);
        $this->insertHansard(['hdate' => '2024-01-15']);
        $this->insertHansard(['hdate' => '2024-01-20']);

        $next = Hansard::where('hdate', '>', '2024-01-10')->min('hdate');
        $this->assertEquals('2024-01-15', $next);
    }

    public function test_prev_date_before(): void {
        $this->insertHansard(['hdate' => '2024-01-10']);
        $this->insertHansard(['hdate' => '2024-01-15']);
        $this->insertHansard(['hdate' => '2024-01-20']);

        $prev = Hansard::where('hdate', '<', '2024-01-20')->max('hdate');
        $this->assertEquals('2024-01-15', $prev);
    }

    public function test_next_date_returns_null_when_no_later_date(): void {
        $this->insertHansard(['hdate' => '2024-01-10']);

        $next = Hansard::where('hdate', '>', '2024-01-10')->min('hdate');
        $this->assertNull($next);
    }

    public function test_where_gid_with_join(): void {
        $epId = self::$nextId++;
        Hansard::query()->insert([
            'epobject_id' => $epId,
            'gid' => 'uk.org.publicwhip/debate/2024-01-15.123.0',
            'htype' => 12,
            'speaker_id' => 100,
            'major' => 1,
            'section_id' => 0,
            'subsection_id' => 0,
            'hpos' => 5,
            'hdate' => '2024-01-15',
            'source_url' => '',
        ]);
        Epobject::query()->insert([
            'epobject_id' => $epId,
            'body' => 'Test speech body',
            'type' => 1,
        ]);

        $result = Hansard::join('epobject', 'hansard.epobject_id', '=', 'epobject.epobject_id')
          ->where('hansard.gid', 'uk.org.publicwhip/debate/2024-01-15.123.0')
          ->first(['hansard.gid', 'hansard.major', 'hansard.speaker_id', 'epobject.body']);

        $this->assertNotNull($result);
        $this->assertEquals(1, $result->major);
        $this->assertEquals(100, $result->speaker_id);
        $this->assertEquals('Test speech body', $result->body);
    }

    public function test_distinct_hdate_major(): void {
        $this->insertHansard(['hdate' => '2024-01-01', 'major' => 1]);
        $this->insertHansard(['hdate' => '2024-01-01', 'major' => 3]);
        $this->insertHansard(['hdate' => '2024-01-02', 'major' => 1]);

        $rows = Hansard::distinct()->get(['hdate', 'major']);
        $this->assertCount(3, $rows);
    }

    public function test_committee_sittings_count(): void {
        $this->insertHansard(['major' => 6, 'minor' => 42, 'htype' => 10]);
        $this->insertHansard(['major' => 6, 'minor' => 42, 'htype' => 10]);
        $this->insertHansard(['major' => 6, 'minor' => 42, 'htype' => 12]);

        $sittings = Hansard::where('major', 6)->where('minor', 42)->where('htype', 10)->count();
        $this->assertEquals(2, $sittings);
    }

    public function test_session_speech_counts_by_minor(): void {
        $this->insertHansard(['major' => 6, 'htype' => 12, 'minor' => 10]);
        $this->insertHansard(['major' => 6, 'htype' => 12, 'minor' => 10]);
        $this->insertHansard(['major' => 6, 'htype' => 12, 'minor' => 20]);

        $counts = Hansard::where('major', 6)
          ->where('htype', 12)
          ->whereIn('minor', [10, 20])
          ->groupBy('minor')
          ->selectRaw('minor, COUNT(*) AS c')
          ->pluck('c', 'minor')
          ->all();

        $this->assertEquals(2, $counts[10]);
        $this->assertEquals(1, $counts[20]);
    }

    public function test_year_navigation_queries(): void {
        $this->insertHansard(['major' => 1, 'hdate' => '2020-06-01']);
        $this->insertHansard(['major' => 1, 'hdate' => '2021-03-15']);
        $this->insertHansard(['major' => 1, 'hdate' => '2022-09-20']);

        $prevyear = Hansard::where('major', 1)
          ->whereRaw('year(hdate) < ?', [2022])
          ->orderByDesc('hdate')
          ->limit(1)
          ->selectRaw("DATE_FORMAT(hdate, '%Y') AS year")
          ->value('year');

        $nextyear = Hansard::where('major', 1)
          ->whereRaw('year(hdate) > ?', [2020])
          ->orderBy('hdate')
          ->limit(1)
          ->selectRaw("DATE_FORMAT(hdate, '%Y') AS year")
          ->value('year');

        $this->assertEquals('2021', $prevyear);
        $this->assertEquals('2021', $nextyear);
    }

}
