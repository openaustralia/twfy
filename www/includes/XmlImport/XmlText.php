<?php

namespace OpenAustralia\TWFY\XmlImport;

/**
 * Text handling that xml2db.pl gets from XML::Twig, HTML::Entities and Uncapitalise.
 */
class XmlText {

    /**
     * Words kept in a fixed form when an all-capitals heading is lowercased.
     */
    private const CANONICAL = [
        'a', 'an', 'at', 'as', 'and', 'are', 'but', 'by', 'ere', 'for', 'from', 'in', 'into', 'is', 'of', 'on',
        'onto', 'or', 'over', 'per', 'the', 'to', 'that', 'than', 'until', 'unto', 'upon', 'via', 'with', 'while',
        'whilst', 'within', 'without', 'etc', 'RAF', 'MoD', 'DoT', 'HIV', 'AIDS', 'ADHD', 'EU', 'LGC', 'BSE',
        'BCCI', 'NHS', 'TUC',
    ];

    /**
     * For each element of a file in document order, whether the file wrote it as <name/>.
     *
     * DOMDocument cannot tell <p></p> from <p/>, and XML::Twig keeps the difference when it writes XML back.
     *
     * @return list<bool>
     *   One flag per element.
     */
    public static function selfClosingFlags(string $path): array {
        $reader = new \XMLReader();
        if (!@$reader->open($path, null, LIBXML_NONET)) {
            throw new \RuntimeException("Could not open $path");
        }
        $flags = [];
        while (@$reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT) {
                $flags[] = $reader->isEmptyElement;
            }
        }
        $reader->close();
        return $flags;
    }

    /**
     * The XML inside an element, as XML::Twig's sprint(1) writes it with the "safe" output filter.
     *
     * Attributes come out sorted by name, and every character above ASCII as a numeric entity. Text escapes
     * only & and <, and indentation (whitespace with a newline) is dropped.
     *
     * @param \DOMNode $node
     *   The element.
     * @param list<bool> $selfClosing
     *   The file's self-closing flags, from selfClosingFlags().
     * @param int $index
     *   The element's position among all the file's elements in document order, counting from 0.
     */
    public static function innerXml(\DOMNode $node, array $selfClosing, int $index): string {
        return self::children($node, $selfClosing, $index);
    }

    /**
     * The XML of an element's children. $index is advanced past every element it writes.
     */
    private static function children(\DOMNode $node, array $selfClosing, int &$index): string {
        $xml = '';
        foreach ($node->childNodes as $child) {
            $xml .= self::nodeXml($child, $selfClosing, $index);
        }
        return $xml;
    }

    /**
     * One node's XML.
     */
    private static function nodeXml(\DOMNode $node, array $selfClosing, int &$index): string {
        if ($node instanceof \DOMElement) {
            $index++;
            $empty = $selfClosing[$index] ?? false;
            $attributes = [];
            foreach ($node->attributes as $attribute) {
                $attributes[$attribute->nodeName] = $attribute->nodeValue;
            }
            ksort($attributes, SORT_STRING);
            $open = '<' . $node->nodeName;
            foreach ($attributes as $name => $value) {
                $open .= ' ' . $name . '="' . self::numeric(str_replace(['&', '<', '"'], ['&amp;', '&lt;', '&quot;'], (string) $value)) . '"';
            }
            $inner = self::children($node, $selfClosing, $index);
            return $empty ? $open . '/>' : $open . '>' . $inner . '</' . $node->nodeName . '>';
        }
        if ($node instanceof \DOMText) {
            // XML::Twig drops text that is only whitespace and has a newline, such as indentation.
            if (preg_match('/^\s*$/', $node->nodeValue) && str_contains($node->nodeValue, "\n")) {
                return '';
            }
            return self::numeric(str_replace(['&', '<'], ['&amp;', '&lt;'], $node->nodeValue));
        }
        return '';
    }

    /**
     * Turns every character above ASCII into a decimal numeric entity.
     */
    private static function numeric(string $text): string {
        return mb_encode_numericentity($text, [0x80, 0x10FFFF, 0, 0x10FFFF], 'UTF-8');
    }

    /**
     * HTML::Entities' encode_entities(), but leaving the first apostrophe as it is.
     *
     * This matches xml2db.pl's encode_entities_noapos(), which replaces only the first "&#39;".
     */
    public static function entitiesNoApos(string $text): string {
        $encoded = str_replace('&#039;', '&#39;', htmlentities($text, ENT_QUOTES | ENT_HTML401, 'UTF-8'));
        $position = strpos($encoded, '&#39;');
        if ($position !== false) {
            $encoded = substr_replace($encoded, "'", $position, strlen('&#39;'));
        }
        return $encoded;
    }

    /**
     * Strips whitespace from both ends.
     */
    public static function strip(string $text): string {
        return preg_replace('/^\s+|\s+$/', '', $text);
    }

    /**
     * A time as the database wants it: HH:MM:SS, with seconds and a leading zero added when missing.
     */
    public static function canonTime(string $time): string {
        if (preg_match('/^0\d\d:/', $time)) {
            $time = substr($time, 1);
        }
        if (preg_match('/^\d\d?:\d\d$/', $time)) {
            $time .= ':00';
        }
        if (preg_match('/^\d:/', $time)) {
            $time = '0' . $time;
        }
        return $time;
    }

    /**
     * Mixes the case of the parts of a heading that are all capitals.
     */
    public static function fixCase(string $text): string {
        // Perl's split gives no parts for an empty string, so an empty heading stays empty.
        if ($text === '') {
            return '';
        }
        $parts = preg_split('/&#8212;/', $text);
        while (count($parts) > 1 && end($parts) === '') {
            array_pop($parts);
        }
        return implode(' &#8212; ', array_map([self::class, 'fixCasePart'], $parts));
    }

    /**
     * One part of a heading.
     */
    private static function fixCasePart(string $part): string {
        $part = preg_replace('/\s+/', ' ', preg_replace('/^\s+|\s+$/', '', $part));
        if (preg_match('/^[^a-z]+(&amp;[^a-z]+)*$/', $part)) {
            if ($part === '' || $part === '0') {
                throw new \RuntimeException('undefined part title');
            }
            $part = self::uncapitalise($part);
        }
        if ($part === '' || $part === '0') {
            throw new \RuntimeException('not defined title part');
        }
        return $part;
    }

    /**
     * Uncapitalise::format(): capitalises each word, keeps the canonical forms, and leaves vowel-less words in capitals.
     */
    private static function uncapitalise(string $text): string {
        $canonical = [];
        foreach (self::CANONICAL as $word) {
            $canonical[mb_strtolower($word)] = $word;
        }
        $pieces = preg_split('/([()\-.,;\s\/\\\\\[\]]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        foreach ($pieces as $piece) {
            if ($piece === '') {
                continue;
            }
            $lower = mb_strtolower($piece);
            if (isset($canonical[$lower])) {
                $result .= $canonical[$lower];
            } elseif (preg_match('/[aeiouy]/i', $piece)) {
                $result .= mb_strtoupper(mb_substr($piece, 0, 1)) . mb_strtolower(mb_substr($piece, 1));
            } else {
                $result .= mb_strtoupper($piece);
            }
        }
        $result = mb_strtoupper(mb_substr($result, 0, 1)) . mb_substr($result, 1);
        if (preg_match('/ \w$/u', $result)) {
            $result = mb_substr($result, 0, -1) . mb_strtoupper(mb_substr($result, -1));
        }
        return $result;
    }

}
