<?php

/**
 * @file
 * The legacy glossary builds some links with the URL class, which other specs also stand in for.
 */

if (!class_exists('URL')) {
    /**
     * Minimal URL stand-in.
     */
    class URL {

        public function __construct(private string $page) {
        }

        /**
         * @param array<string, string> $params
         *   Query values.
         */
        public function insert(array $params): void {
        }

        public function generate(string $type = ''): string {
            return '/' . $this->page . '/';
        }

    }
}
