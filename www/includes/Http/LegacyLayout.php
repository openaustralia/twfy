<?php

namespace OpenAustralia\TWFY\Http;

/**
 * The legacy site header and footer, for the layout.legacy Blade view to draw a page between.
 */
class LegacyLayout {

    /**
     * Everything above the page's own content, with a viewport that fits the device.
     *
     * @param string $page
     *   The legacy page key, which sets the title and the menu.
     */
    public function top(string $page): string {
        global $PAGE, $DATA, $this_page;

        $this_page = $page;
        $DATA->set_page_metadata($page, 'responsive', true);

        return $this->capture(fn () => $PAGE->page_start());
    }

    /**
     * Everything below the page's own content.
     */
    public function bottom(): string {
        global $PAGE;

        return $this->capture(fn () => $PAGE->page_end());
    }

    /**
     * What a PAGE method prints.
     *
     * page_start() opens an output buffer and page_end() closes one, so this gives them a spare to work in.
     */
    private function capture(\Closure $print): string {
        $level = ob_get_level();
        ob_start();
        ob_start();
        $print();
        while (ob_get_level() > $level + 1) {
            ob_end_flush();
        }
        return (string) ob_get_clean();
    }

}
