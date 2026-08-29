<?php

/**
 * @file
 * Plain view models for the Plates-rendered debate transcript (House representatives
 * and Senate only - major 1 and 101, see hansard_gid.php). Each one takes the existing
 * $row HansardObjectData array (documented at the bottom of hansard_gid.php) and turns
 * it into a finished, escaping-agnostic set of fields - no HTML strings, no direct
 * echo/print, no $PAGE-> calls. The Plates template is the only thing that turns these
 * into markup; this class is the only thing that reads $row's raw fields.
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
    public ?string $speakerUrl = null;
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
            $view->speakerUrl = $speaker['url'];
            $view->speakerDescription = self::speakerDescription($speaker);

            // $smallonly=false here (the old stripe rendering passed true): that 'S'
            // size is only 44x59px, fine floated at its natural size but blurry
            // upscaled into a 48px circle. 'L' (88x118, same aspect ratio) downscales
            // instead, which looks sharp - see speech.php for the matching object-top
            // crop, since these are head-and-shoulders portraits with more empty
            // space below the shoulders than above the head.
            [$image] = find_rep_image($speaker['person_id'], false);
            $view->avatarUrl = $image;

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
        $view->isInterjection = (bool) preg_match('/\binterjecting[—-]/i', $row['body']);

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
     *
     */
    private static function speakerDescription(array $speaker): string {
        $desc = '';
        if ($speaker['party'] != 'Speaker' && $speaker['party'] != 'Deputy Speaker'
            && $speaker['party'] != 'President' && $speaker['constituency']) {
            $desc .= $speaker['constituency'] . ', ';
        }
        $desc .= htmlentities($speaker['party']);
        if (isset($speaker['office'])) {
            $desc .= ', ' . $speaker['office'][0]['pretty'];
        }
        return $desc;
    }

    /**
     * Same body-cleanup steps hansard_gid.php's old rendering path already applied
     * (search highlighting/glossarising already happened earlier, on $data['rows'],
     * before either rendering path runs) - kept identical so the two paths produce
     * the same text, just different surrounding markup.
     */
    private static function cleanBody(string $body): string {
        $body = str_replace('pwmotiontext="moved"', 'class="moved"', $body);
        $body = str_replace('<a href="h', '<a rel="nofollow" href="h', $body);
        return str_replace('</p><p', '</p> <p', $body);
    }

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
        $view->bodyHtml = $row['body'];
        $view->contextLinkHtml = context_link($row);
        $view->commentTeaserHtml = generate_commentteaser($row, $info['major']);
        return $view;
    }

}
