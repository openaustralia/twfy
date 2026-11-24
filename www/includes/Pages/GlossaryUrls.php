<?php

namespace OpenAustralia\TWFY\Pages;

/**
 * The addresses the glossary pages link to.
 */
final class GlossaryUrls {

    /**
     * The A to Z index.
     */
    public static function index(): string {
        return WEBPATH . 'glossary/';
    }

    /**
     * One term's page.
     */
    public static function term(int $glossary_id): string {
        return self::index() . '?gl=' . $glossary_id;
    }

    /**
     * The index for a letter.
     */
    public static function letter(string $letter): string {
        return self::index() . '?az=' . rawurlencode($letter);
    }

    /**
     * The page for suggesting a term.
     */
    public static function add(): string {
        return WEBPATH . 'addterm/';
    }

    /**
     * Where a signed-out visitor is sent to sign in.
     */
    public static function signIn(): string {
        return WEBPATH . 'user/prompt/';
    }

    /**
     * A Hansard search for an exact phrase.
     */
    public static function search(string $phrase): string {
        return WEBPATH . 'search/?' . http_build_query(['s' => '"' . $phrase . '"']);
    }

}
