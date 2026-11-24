<?php

/**
 * @file
 */

/**
 * Renders one of the legacy templates in www/includes/easyparliament/templates as its list class does.
 */
final class LegacyTemplate {

    /**
     * The HTML a template prints for some data.
     *
     * @param string $name
     *   The template's path under templates/, without .php, such as html/hansard_recent_wrans.
     * @param array<string, mixed> $data
     *   What the list class passes to the template as $data.
     */
    public static function render(string $name, array $data): string {
        $render = function (string $file, array $data): string {
            ob_start();
            include $file;
            return ob_get_clean();
        };
        return $render(__DIR__ . '/../../../www/includes/easyparliament/templates/' . $name . '.php', $data);
    }

}
