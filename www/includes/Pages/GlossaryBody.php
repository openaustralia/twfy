<?php

namespace OpenAustralia\TWFY\Pages;

/**
 * A definition as HTML: stripped of all but the few tags people may use, with web addresses and other terms linked.
 */
final class GlossaryBody {

    /**
     * The only HTML the glossary views print without escaping it.
     *
     * @param string $body
     *   The definition as the person wrote it.
     * @param int|null $glossary_id
     *   The term the definition belongs to, so it is shown in bold, not linked to itself. A new term has none.
     */
    public static function html(string $body, ?int $glossary_id = null): string {
        $args = ['sort' => 'regexp_replace'];
        if ($glossary_id !== null) {
            $args['glossary_id'] = $glossary_id;
        }
        return (new \GLOSSARY($args))->glossarise(filter_user_input($body, 'comment'), 0, 1);
    }

}
