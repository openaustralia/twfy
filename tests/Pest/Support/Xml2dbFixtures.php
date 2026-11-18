<?php

/**
 * @file
 */

/**
 * Small, invented Hansard XML in the shape openaustralia-parser writes, for the xml2db specs.
 */
final class Xml2dbFixtures {

    public const DATE = '2024-03-19';

    /**
     * The gid the parser would give item $minor of section $major on $date.
     */
    public static function gid(string $house, string $date, int $major, int $minor): string {
        $kind = $house === 'senate' ? 'lords' : 'debate';
        return "uk.org.publicwhip/$kind/$date.$major.$minor";
    }

    /**
     * A major heading, a minor heading and one speech by member 10.
     *
     * @param string $speech
     *   The speech's inner XML.
     * @param string|null $proof
     *   The debates element's proof attribute, or NULL to leave it off.
     */
    public static function day(
      string $house = 'representatives',
      string $date = self::DATE,
      string $speech = '<p>First words.</p>',
      ?string $proof = '0',
    ): string {
        return self::wrap(
          self::majorHeading($house, $date, 'TREASURY') .
          self::minorHeading($house, $date, 'Fictional Bill 2024') .
          self::speech($house, $date, 3, $speech),
          $proof
        );
    }

    /**
     * Wraps items in a debates element.
     */
    public static function wrap(string $items, ?string $proof = '0'): string {
        $attribute = $proof === null ? '' : " proof=\"$proof\"";
        return "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<debates$attribute>\n$items</debates>\n";
    }

    /**
     * A major heading, item 1.
     */
    public static function majorHeading(string $house, string $date, string $title): string {
        $gid = self::gid($house, $date, 1, 1);
        return "<major-heading id=\"$gid\" nospeaker=\"true\" time=\"10:00:00\" url=\"https://example.invalid/hansard/$date#1\">$title</major-heading>\n";
    }

    /**
     * A minor heading, item 2.
     */
    public static function minorHeading(string $house, string $date, string $title): string {
        $gid = self::gid($house, $date, 1, 2);
        return "<minor-heading id=\"$gid\" nospeaker=\"true\" time=\"10:01:00\" url=\"https://example.invalid/hansard/$date#2\">$title</minor-heading>\n";
    }

    /**
     * A speech by member 10, or a procedural one with no speaker when $speakerid is empty.
     */
    public static function speech(
      string $house,
      string $date,
      int $minor,
      string $inner,
      string $speakerid = 'uk.org.publicwhip/member/10',
      string $speakername = 'Alex Example',
    ): string {
        $gid = self::gid($house, $date, 1, $minor);
        $speaker = $speakerid === '' ? '' : " speakerid=\"$speakerid\" speakername=\"$speakername\"";
        return "<speech id=\"$gid\"$speaker time=\"10:05\" url=\"https://example.invalid/hansard/$date#$minor\">$inner</speech>\n";
    }

}
