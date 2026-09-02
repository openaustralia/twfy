<?php

/**
 * @file
 * Plain view models for the Plates-rendered debate transcript (House representatives
 * and Senate only - major 1 and 101, see hansard_gid.php). Each one takes the existing
 * $row HansardObjectData array (documented at the bottom of hansard_gid.php) and turns
 * it into a finished, escaping-agnostic set of fields - some (bodyHtml,
 * contextLinkHtml, commentTeaserHtml) do carry pre-built, pre-sanitised HTML through
 * from existing helpers, but this class builds no markup structure of its own: no
 * direct echo/print, no $PAGE-> calls. The Plates template is the only thing that
 * turns these into a finished page; this class is the only thing that reads $row's
 * raw fields.
 *
 * See openaustralia/openaustralia#939 for why this split exists.
 */

/**
 *
 */
class HansardSpeechView {
    public string $id;
    public ?string $timestamp = null;
    public ?string $speakerName = null;
    public ?string $speakerInitials = null;
    public ?string $speakerUrl = null;
    // member_id, falling back to person_id - member_id is null for a chair role like
    // "The Deputy Speaker" (see isCurrentSpeaker below, which has the same fallback),
    // so this is buildRoster()'s dedup key precisely because it's stable per person
    // even when speakerUrl isn't (also null for chair roles) and speakerName alone
    // isn't (shared across whoever's sitting in the chair at the time).
    public ?string $speakerId = null;
    public ?string $speakerDescription = null;
    public ?string $avatarUrl = null;
    public bool $isCurrentSpeaker = false;
    public bool $isInterjection = false;
    public string $bodyHtml = '';
    public ?string $sourceUrl = null;
    public ?string $sourceLabel = null;
    public ?string $permalinkUrl = null;
    public string $contextLinkHtml = '';
    public string $commentTeaserHtml = '';

    /**
     * $showTimestamp is false when this speech falls at the same time as the one
     * before it - hansard_gid.php's existing $timetracker logic decides that, since
     * it has to look across the whole page's rows, not just this one.
     */
    public static function forSpeech(array $row, array $info, bool $showTimestamp): self {
        $view = new self();
        $view->id = 'g' . gid_to_anchor($row['gid']);

        if ($showTimestamp && $row['htime'] != '00:00:00') {
            $view->timestamp = format_time($row['htime'], TIMEFORMAT);
        }

        if (isset($row['speaker']) && count($row['speaker']) > 0) {
            $speaker = $row['speaker'];
            $view->speakerName = ucfirst(member_full_name(
                $speaker['house'],
                $speaker['title'],
                $speaker['first_name'],
                $speaker['last_name'],
                $speaker['constituency']
            ));
            // From first_name/last_name directly, not by splitting $speakerName's
            // words - that string can start with a title ("Senator Penny
            // Allman-Payne"), which would wrongly give "SP" instead of "PA".
            $view->speakerInitials = self::initials($speaker['first_name'], $speaker['last_name']);
            $view->speakerUrl = $speaker['url'];
            $view->speakerDescription = self::speakerDescription($speaker);

            // $smallonly=false here (the old stripe rendering passed true): that 'S'
            // size is only 44x59px, fine floated at its natural size but blurry
            // upscaled into the 64px/32px circles the templates use (speech.php,
            // speaker-roster.php). 'L' (88x118, same aspect ratio) downscales
            // instead, which looks sharp - see speech.php for the matching object-top
            // crop, since these are head-and-shoulders portraits with more empty
            // space below the shoulders than above the head.
            [$image] = find_rep_image($speaker['person_id'], false);
            $view->avatarUrl = $image;

            if (isset($speaker['member_id'])) {
                $view->speakerId = (string) $speaker['member_id'];
            } elseif (isset($speaker['person_id'])) {
                $view->speakerId = (string) $speaker['person_id'];
            }

            $view->isCurrentSpeaker =
                (isset($speaker['member_id']) && isset($info['member_id']) && $speaker['member_id'] == $info['member_id'])
                || (isset($speaker['person_id']) && isset($info['person_id']) && $speaker['person_id'] == $info['person_id']);
        }

        $view->bodyHtml = self::cleanBody($row['body']);

        // No stored flag distinguishes an interjection from an ordinary speech by the
        // time it reaches this table (xml2db.pl doesn't keep the parser's own
        // interjection: value anywhere) - this only reliably catches the *anonymous*
        // case ("Government members interjecting—", "Opposition senators
        // interjecting—"), which is also unresolved to a real speaker (see
        // speakerName above) and gets a plain-text "X:" line baked into the body by
        // the loader instead. A named MP's own interjection has a real speaker and
        // no way to tell it apart from an ordinary speech with what's stored today.
        // ?? '': $row['body'] can be null, same as cleanBody()'s own param above.
        // Matches "interjecting" alone, not "interjecting" + a dash: real body text
        // stores the dash as the entity "&#8212;", not a literal em-dash/hyphen
        // character, so requiring one right after the word never matched anything.
        // speakerName === null: without this, a named MP's own speech that merely
        // mentions "interjecting" in its prose would wrongly get the amber
        // interjection card too. Copilot finding on #227.
        $view->isInterjection = $view->speakerName === null
            && (bool) preg_match('/\binterjecting\b/i', $row['body'] ?? '');

        if (isset($row['source_url']) && $row['source_url'] != '') {
            $view->sourceUrl = $row['source_url'];
            $view->sourceLabel = 'Hansard source';
        }

        if (isset($row['commentsurl'])) {
            $view->permalinkUrl = $row['commentsurl'];
        }

        $view->contextLinkHtml = context_link($row);
        $view->commentTeaserHtml = generate_commentteaser($row, $info['major']);

        return $view;
    }

    /**
     * Every caller treats this return value as already-escaped and echoes it raw
     * (eg speech.php: "already-escaped by HansardSpeechView") - htmlentities() has
     * to cover the whole assembled string in one pass, not just $speaker['party'],
     * or a "&"/"<" in $speaker['constituency'] or $speaker['office'][0]['pretty']
     * reaches the page unescaped.
     */
    private static function speakerDescription(array $speaker): string {
        $desc = '';
        // HANSARDLIST::_get_speaker() already translates the raw member.party code
        // (SPK/CWM/DCWM/PRES/DPRES) into one of these via dbtypes.php's $parties
        // before $speaker ever reaches here - hyphenated, not spaced, and
        // Deputy-President wasn't checked for at all before this fix (so a Deputy
        // President's constituency was shown when it shouldn't have been, same as
        // every other chair role - Sentry only caught the Deputy Speaker case).
        $chairRoles = ['Speaker', 'Deputy-Speaker', 'President', 'Deputy-President'];
        if (!in_array($speaker['party'], $chairRoles) && $speaker['constituency']) {
            $desc .= $speaker['constituency'] . ', ';
        }
        $desc .= $speaker['party'];
        // isset() alone isn't enough - _get_speaker() only ever appends to
        // 'office', never sets it to [], but a caller building $speaker by hand
        // (eg a test fixture) could still pass an empty array through.
        if (!empty($speaker['office'])) {
            $desc .= ', ' . $speaker['office'][0]['pretty'];
        }
        return htmlentities($desc);
    }

    /**
     * Two-letter avatar-fallback initials (eg "PA" for Penny Allman-Payne), matching
     * the mockup's own placeholder avatars ("https://placehold.co/48x48/.../?text=LT"
     * for Lidia Thorpe) - falls back to whichever of first/last name is present when
     * only one is, and to '' when neither is (so callers can treat an empty string as
     * "nothing to show" the same way they'd treat null).
     *
     * $firstName/$lastName carry no type hints here - member.first_name/last_name
     * are nullable columns (db/schema.sql), and a real row can hand this function a
     * null. A string type hint would fatal with a TypeError instead of just
     * producing a blank initial - Sentry finding on #227.
     */
    public static function initials($firstName, $lastName): string {
        $initials = mb_substr((string) $firstName, 0, 1) . mb_substr((string) $lastName, 0, 1);
        return mb_strtoupper($initials);
    }

    /**
     * cleanBody() applies the same body-cleanup steps hansard_gid.php's old
     * rendering path already applied (search highlighting/glossarising already ran
     * earlier, on $data['rows'], before either rendering path runs) - it keeps them
     * identical so the two paths produce the same text, just different surrounding
     * markup - plus one step the old path never needed: stripping stray self-closed
     * <i/> tags.
     *
     * Some hansard bodies contain a literal, empty "<i/>" between two real <i>...</i>
     * phrases (an artifact of the parser's XML-to-HTML conversion, likely a collapsed
     * empty <phrase italics="yes"> - see eg senate 2026-08-19.164, "Civil penalty
     * provision" / "Exception" definitions). HTML doesn't treat "<i/>" as self-closing
     * - browsers read it as an ordinary opening <i> with no matching close, which then
     * swallows every paragraph after it into one giant italic run for the rest of the
     * page. The old stripe rendering carried this exact same malformed markup and
     * broke just as badly - it just never showed, since nothing styled stripe rows to
     * make "is this text italic" visible. This new design colours .italic/
     * .indentitalic text, which makes the bug impossible to miss, so it's worth fixing
     * here rather than leaving for openaustralia-parser/a DB cleanup.
     */
    public static function cleanBody(?string $body): string {
        $body ??= '';
        $body = str_replace('pwmotiontext="moved"', 'class="moved"', $body);
        $body = str_replace('<a href="h', '<a rel="nofollow" href="h', $body);
        $body = str_replace('<i/>', '', $body);
        return str_replace('</p><p', '</p> <p', $body);
    }

    /**
     * buildRoster() builds the "Speakers in this debate" roster from the
     * already-built list of per-row view models (see forSpeech()) - one entry per
     * distinct speaker, deduped on speakerId first (member_id/person_id - stable per
     * person even for a chair role like "The Deputy Speaker", which has neither a
     * speakerUrl nor a name unique to one MP), falling back to speakerUrl or
     * speakerName only when speakerId itself is unset. It orders them by how much
     * they actually said - summed word count across all their speeches on this
     * page, most first - not just how many times they spoke, since one long speech
     * can outweigh several short interjections. Procedural rows (no speaker) don't
     * contribute.
     */
    public static function buildRoster(array $items): array {
        $roster = [];
        foreach ($items as $item) {
            if (!($item instanceof self) || !$item->speakerName) {
                continue;
            }
            $key = $item->speakerId ?? ($item->speakerUrl ?: $item->speakerName);
            if (!isset($roster[$key])) {
                $entry = new HansardSpeakerRosterEntry();
                $entry->name = $item->speakerName;
                $entry->initials = $item->speakerInitials;
                $entry->url = $item->speakerUrl;
                $entry->avatarUrl = $item->avatarUrl;
                $entry->description = $item->speakerDescription;
                $roster[$key] = $entry;
            }
            $roster[$key]->speechCount++;
            // html_entity_decode() first: strip_tags() removes markup but leaves
            // entities like "&#8212;" as literal text, which str_word_count() then
            // counts as a word of its own, inflating the roster's ordering.
            $roster[$key]->wordCount += str_word_count(html_entity_decode(strip_tags($item->bodyHtml)));
        }

        $roster = array_values($roster);
        usort($roster, fn($a, $b) => $b->wordCount <=> $a->wordCount);

        return $roster;
    }

    /**
     * The transcript card's prev/up/next pagination bar, from $DATA->page_metadata()'s
     * 'nextprev' shape - each direction is optional. 'up' is relabelled from "See the
     * whole debate" to "All House/Senate debates on this day", pointing at $dateUrl
     * instead - label/url/title must all change together, or title (the hover
     * tooltip) goes stale. Sentry finding on #227.
     */
    public static function buildNextPrev(array $nextprevdata, string $chamberTitle, string $dateUrl): array {
        $nextPrev = [];
        foreach (['prev', 'up', 'next'] as $direction) {
            if (isset($nextprevdata[$direction]['body'])) {
                $nextPrev[$direction] = [
                    'label' => $nextprevdata[$direction]['body'],
                    'url' => $nextprevdata[$direction]['url'] ?? null,
                    'title' => $nextprevdata[$direction]['title'] ?? '',
                ];
            }
        }
        if (isset($nextPrev['up'])) {
            $nextPrev['up']['label'] = 'All ' . $chamberTitle . ' on this day';
            $nextPrev['up']['url'] = $dateUrl;
            $nextPrev['up']['title'] = $nextPrev['up']['label'];
        }
        return $nextPrev;
    }

    /**
     * Falls back subsection title -> section title -> the major's own label
     * ("House debates"/"Senate debates") when neither heading row occurred on the
     * page - a still-unset $sentinel title would otherwise reach transcript.php's
     * <h2> literally. eyebrowSectionTitle is the same section title shown in the
     * card's eyebrow line, but blanked instead of left at $sentinel when only a
     * subsection heading (htype 11, no htype 10) occurred - transcript.php treats
     * any non-empty sectionTitle as real and renders a "·" separator before it.
     * Sentry finding on #227.
     */
    public static function resolveTranscriptTitle(string $sectionTitle, string $subsectionTitle, string $sentinel, string $majorTitle): array {
        $hasSubsectionTitle = $subsectionTitle !== $sentinel;
        $finalTitle = $hasSubsectionTitle ? $subsectionTitle : $sectionTitle;
        if ($finalTitle === $sentinel) {
            $finalTitle = $majorTitle;
        }
        $eyebrowSectionTitle = ($hasSubsectionTitle && $sectionTitle !== $sentinel) ? $sectionTitle : '';
        return [
            'hasSubsectionTitle' => $hasSubsectionTitle,
            'finalTitle' => $finalTitle,
            'eyebrowSectionTitle' => $eyebrowSectionTitle,
        ];
    }

}

/**
 * One row in the "Speakers in this debate" roster (see
 * HansardSpeechView::buildRoster()).
 */
class HansardSpeakerRosterEntry {
    public string $name;
    public ?string $initials = null;
    public ?string $url = null;
    public ?string $avatarUrl = null;
    public ?string $description = null;
    public int $speechCount = 0;
    public int $wordCount = 0;

}

/**
 *
 */
class HansardProceduralView {
    public string $id;
    public string $bodyHtml;
    public string $contextLinkHtml = '';
    public string $commentTeaserHtml = '';

    /**
     *
     */
    public static function forProcedural(array $row, array $info): self {
        $view = new self();
        $view->id = 'g' . gid_to_anchor($row['gid']);
        // Same cleanup as HansardSpeechView::forSpeech() - see cleanBody()'s own
        // comment for why the <i/> strip matters even for these short rows.
        $view->bodyHtml = HansardSpeechView::cleanBody($row['body']);
        $view->contextLinkHtml = context_link($row);
        $view->commentTeaserHtml = generate_commentteaser($row, $info['major']);
        return $view;
    }

}
