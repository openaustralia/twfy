<?php

namespace OpenAustralia\TWFY\XmlImport;

/**
 * Where the importer prints progress. Standard output by default, so tests can read it from a buffer.
 */
class Messages {

    /**
     * Creates the printer.
     *
     * @param bool $cronquiet
     *   True to leave out the routine messages that --cronquiet silences.
     * @param callable(string): void|null $write
     *   Where text goes. Defaults to standard output.
     */
    public function __construct(private readonly bool $cronquiet, private $write = null) {
    }

    /**
     * Prints a message that is always shown.
     */
    public function say(string $text): void {
        if ($this->write === null) {
            echo $text;
            return;
        }
        ($this->write)($text);
    }

    /**
     * Prints a routine message, unless --cronquiet is on.
     */
    public function info(string $text): void {
        if (!$this->cronquiet) {
            $this->say($text);
        }
    }

}
