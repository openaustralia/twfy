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
            $entry = self::speakerEntry($speaker);
            $view->speakerName = $entry->name;
            $view->speakerInitials = $entry->initials;
            $view->speakerUrl = $entry->url;
            $view->speakerDescription = $entry->description;
            $view->avatarUrl = $entry->avatarUrl;

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
     * The name/initials/url/avatar/description fields for one raw $speaker array
     * (the shape HANSARDLIST::_get_speaker() returns), as a HansardSpeakerRosterEntry
     * - the same fields forSpeech() above builds for a single speech's speaker, but
     * factored out so a list of speakers with no per-speech data of their own (eg
     * HansardSectionIndexItem::fromSubrow()'s "who spoke on this topic" line, or
     * www/docs/index.php's own latest_activity_items()) can build the same shape
     * without going via a fake speech row.
     */
    public static function speakerEntry(array $speaker): HansardSpeakerRosterEntry {
        $entry = new HansardSpeakerRosterEntry();
        $entry->name = ucfirst(member_full_name(
            $speaker['house'],
            $speaker['title'],
            $speaker['first_name'],
            $speaker['last_name'],
            $speaker['constituency']
        ));
        // From first_name/last_name directly, not by splitting $name's words - that
        // string can start with a title ("Senator Penny Allman-Payne"), which would
        // wrongly give "SP" instead of "PA".
        $entry->initials = self::initials($speaker['first_name'], $speaker['last_name']);
        $entry->url = $speaker['url'];
        $entry->description = self::speakerDescription($speaker);

        // $smallonly=false here (the old stripe rendering passed true): that 'S' size
        // is only 44x59px, fine floated at its natural size but blurry upscaled into
        // a larger circle. 'L' (88x118, same aspect ratio) downscales instead, which
        // looks sharp - see speech.php for the matching object-top crop, since these
        // are head-and-shoulders portraits with more empty space below the shoulders
        // than above the head.
        [$image] = find_rep_image($speaker['person_id'], false);
        $entry->avatarUrl = $image;

        return $entry;
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
     * The pagination bar's prev/all/next data (resources/views/hansard/pagination.php),
     * built from the same page metadata $PAGE->nextprevlinks() itself reads (set by
     * HANSARDLIST::_get_nextprev_items()) - each of prev/up/next is optional (eg no
     * "prev" on the very first debate ever recorded), and a present one isn't always a
     * link (nextprevlinks() falls back to plain text with no 'url' in some cases too).
     *
     * $chamberTitle is $hansardmajors[major]['title'] ("House debates"/"Senate
     * debates") - used to replace the server-built 'up' label ("All Senate debates on
     * 18 Aug 2026", or sometimes just "See the whole debate", depending which branch
     * of _get_nextprev_items() fired) with "All Senate debates on this day": the date
     * is already shown in the card's own header, so repeating it here was redundant.
     *
     * $dateUrl has to overwrite 'up''s URL along with the label, not just the label:
     * HANSARDLIST::_get_nextprev_items()'s ordinary-item branch (the one that reaches
     * here for an individual speech/procedural page) points 'up' at the parent
     * subsection/section's own page, labelled "See the whole debate" to match -
     * accurate for that URL, but a different destination to the day-listing page the
     * "All ... on this day" label promises. Sentry finding on #227/#228.
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
            // Title (the hover tooltip, pagination.php) needs overwriting along with
            // label/url - it otherwise keeps the parent subsection/section's name
            // from HANSARDLIST::_get_nextprev_items(), stale against the new
            // destination and label above.
            $nextPrev['up']['title'] = $nextPrev['up']['label'];
        }
        return $nextPrev;
    }

    /**
     * "What is X?" - a short explainer for the specific kind of parliamentary
     * business this section is (a Bill, the Adjournment, a Committee reference,
     * Question Time, ...), for the page's right-hand sidebar (resources/views/
     * hansard/about-debates.php). Keyed on the section's own title (exact match -
     * these are a small, fairly fixed vocabulary the chamber itself uses, not free
     * text) rather than major/htype, so the same lookup applies to a transcript
     * page's eyebrow-line section title and a section-index page's own h1 alike.
     * Not an exhaustive list of every section title this site will ever show - just
     * the ones seen/asked about so far. Falls back to generic, chamber-level wording
     * (was a link-only sidebar block - sidebars/hocdebates_short.php etc. - now
     * inlined here instead) for anything not in this list, so every page still gets
     * *something* rather than nothing.
     *
     * $chamberTitle is $hansardmajors[major]['title'] ("House debates"/"Senate
     * debates"), used only for the ultimate fallback when $major itself isn't one of
     * the two known ones either.
     *
     * @return array{title: string, bodyHtml: string}
     *   The sidebar's heading and body HTML.
     */
    public static function aboutSection(string $sectionTitle, int $major, ?string $chamberTitle): array {
        // Nowdoc (not a concatenation chain) for each bodyHtml deliberately: SonarCloud's
        // duplication check normalises string literals, so five entries built the same
        // way ('title' => STRING, 'bodyHtml' => STRING . STRING . STRING . ...) read as
        // one repeated structural block regardless of what the strings actually say.
        // One string token per entry instead of five removes that false positive
        // without changing what's on the page.
        $adjournmentBodyHtml = <<<'HTML'
            <p>At the end of most sitting days, before the House formally adjourns, individual
            members get a few minutes each to speak on almost anything &#8212; a local issue, a
            national one, a tribute to a constituent.</p><p>Topics don&rsquo;t need to relate to
            any bill before the House, which is why each one is its own separate item rather than
            one continuous debate.</p>
            HTML;
        $billsBodyHtml = <<<'HTML'
            <p>A <strong>Bill</strong> is a proposed law. Most go through several stages in each
            chamber &#8212; typically a First Reading, a Second Reading (the main debate on what
            the bill does and why), and a Third Reading &#8212; before a vote.</p><p>A Bill only
            becomes law once both the House and the Senate have passed the same version and it
            receives Royal Assent.</p>
            HTML;
        $committeesBodyHtml = <<<'HTML'
            <p>Parliamentary <strong>committees</strong> look into a bill, an area of government
            spending, or a particular issue in more depth than the whole chamber has time for
            &#8212; hearing evidence, taking submissions, and reporting back with findings.</p>
            <p>This section is members moving to set one up, refer something to it, or deal with
            its report.</p>
            HTML;
        $mpiBodyHtml = <<<'HTML'
            <p>A <strong>Matter of Public Importance</strong> is a debate on one topical issue a
            member has specifically proposed, separate from the day&rsquo;s other business
            &#8212; a chance to put a case on record on something that isn&rsquo;t already before
            the House as a bill or motion.</p>
            HTML;
        $questionTimeBodyHtml = <<<'HTML'
            <p><strong>Questions without Notice</strong> &#8212; Question Time &#8212; is where
            members put questions directly to Ministers with no advance notice, and Ministers
            answer on the spot.</p>
            HTML;

        $sectionExplanations = [
            'Adjournment' => ['title' => 'What is the Adjournment?', 'bodyHtml' => $adjournmentBodyHtml],
            'Bills' => ['title' => 'What is a Bill?', 'bodyHtml' => $billsBodyHtml],
            'Committees' => ['title' => 'What are Committees?', 'bodyHtml' => $committeesBodyHtml],
            'Matters of Public Importance' => ['title' => 'What is a Matter of Public Importance?', 'bodyHtml' => $mpiBodyHtml],
            // Lower-case "without" - matches the section title as this fork's own
            // data actually has it (verified against both a House and a Senate
            // sitting day), not the more conventional-looking capitalised form.
            'Questions without Notice' => ['title' => 'What is Question Time?', 'bodyHtml' => $questionTimeBodyHtml],
        ];
        $sectionExplanation = $sectionExplanations[$sectionTitle] ?? null;

        $aboutTitleByMajor = [
            1 => 'What are House debates?',
            101 => 'What are Senate debates?',
        ];
        // Same wording as sidebars/hocdebates.php / holdebates.php - duplicated
        // rather than shared, since those files also call
        // $PAGE->block_start()/block_end() to draw their own box, which isn't what's
        // wanted here. Keep the two in sync by hand if this wording ever changes.
        $aboutBodyHtmlByMajor = [
            1 => '<p><strong>Debates</strong> in the House of Representatives are an opportunity for members from all parties to <strong>scrutinise</strong> government legislation and <strong>raise important local, national or topical issues</strong>.</p><p>And sometimes to shout at each other.</p>',
            101 => '<p><strong>Debates</strong> in the Senate are an opportunity for Senators from all parties to <strong>scrutinise</strong> government legislation and <strong>raise important local, national or topical issues</strong>.</p><p>And sometimes to shout at each other.</p>',
        ];

        return [
            'title' => $sectionExplanation['title'] ?? ($aboutTitleByMajor[$major] ?? 'What are ' . ($chamberTitle ?: 'debates') . '?'),
            'bodyHtml' => $sectionExplanation['bodyHtml'] ?? ($aboutBodyHtmlByMajor[$major] ?? ''),
        ];
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
    // Where this speaker first speaks in *this particular* listing - eg
    // www/docs/index.php's "Latest Activity" item, a whole top-level section that
    // can span several subsections/topics. Distinct from $url above (their own
    // MP/senator profile page, used by speaker-roster.php) - null unless the
    // caller actually sets it (only latest_activity_items() does), so every other
    // existing use of this class (speaker-roster.php, speaker-chips.php's other
    // callers) is unaffected.
    public ?string $firstSpeechUrl = null;

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

/**
 * One entry in a section-index page (resources/views/hansard/section-index.php) - eg
 * an Adjournment debate's own gid page, which lists each MP's separate, unrelated
 * topic ("Climate Change", "Humanitarian and Refugee Visas", ...) as its own entry.
 * Unlike HansardSpeechView/HansardProceduralView, each entry here is a link to a
 * completely separate debate (its own gid, its own already-Plates-rendered
 * transcript page) - not content living on the section-index page itself.
 */
class HansardSectionIndexItem {
    public string $titleHtml;
    public ?string $url = null;
    public ?string $countLabel = null;
    public ?string $excerptHtml = null;
    /** @var HansardSpeakerRosterEntry[] Who spoke on this topic, in speaking order. */
    public array $speakers = [];

    /**
     * $row is one of $data['subrows'] (see the bottom of hansard_gid.php) -
     * $hansardmajors is the global config array (dbtypes.php), needed to tell a
     * "Wrans"/"WMS"-style major (where $row['contentcount'] doesn't mean "speeches",
     * see the 'other' check below) apart from a debate-style one.
     */
    public static function fromSubrow(array $row, array $hansardmajors): self {
        $item = new self();
        $item->titleHtml = $row['body'];

        $hasContent = false;
        if (isset($row['contentcount']) && $row['contentcount'] > 0) {
            $hasContent = true;
        } elseif ($row['htype'] == '11' && $hansardmajors[$row['major']]['type'] == 'other') {
            $hasContent = true;
        }

        if ($hasContent) {
            $item->url = $row['listurl'];

            $parts = [];
            if ($hansardmajors[$row['major']]['type'] != 'other') {
                // All Wrans have 2 speeches, all WMS have 1 - no need to say so.
                $plural = $row['contentcount'] == 1 ? 'speech' : 'speeches';
                $parts[] = $row['contentcount'] . " $plural";
            }
            if (($row['totalcomments'] ?? 0) > 0) {
                $plural = $row['totalcomments'] == 1 ? 'comment' : 'comments';
                $parts[] = $row['totalcomments'] . " $plural";
            }
            if (count($parts) > 0) {
                $item->countLabel = implode(', ', $parts);
            }
        }

        if (isset($row['excerpt']) && $row['excerpt'] != '') {
            $item->excerptHtml = trim_characters($row['excerpt'], 0, 200);
        }

        if (!empty($row['speakers'])) {
            $item->speakers = array_map(
                fn($speaker) => HansardSpeechView::speakerEntry($speaker),
                $row['speakers']
            );
        }

        return $item;
    }

}
