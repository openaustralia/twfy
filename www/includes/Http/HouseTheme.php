<?php

namespace OpenAustralia\TWFY\Http;

use OpenAustralia\TWFY\Enums\House;

/**
 * The classes that colour a house's pages: burgundy for the Senate, green and teal for the House.
 *
 * The class names are written whole, so Tailwind finds them.
 */
final class HouseTheme {

    /**
     * Holds one house's classes. Use for().
     */
    private function __construct(
      public readonly string $hero,
      public readonly string $chip,
      public readonly string $photo,
      public readonly string $label,
      public readonly string $link,
      public readonly string $button,
      public readonly string $icon,
      public readonly string $outline,
      public readonly string $rule,
      public readonly string $tint,
    ) {
    }

    /**
     * A house's colours.
     */
    public static function for(House $house): self {
        return match ($house) {
            House::Representatives => new self(
                hero: 'bg-teal-900',
                chip: 'bg-green-700',
                photo: 'border-green-700 bg-teal-100',
                label: '!text-teal-200',
                link: '!text-teal-700',
                button: 'bg-teal-700 hover:bg-teal-800',
                icon: 'bg-teal-100 !text-teal-700',
                outline: 'border-teal-700 !text-teal-700',
                rule: 'border-teal-700',
                tint: 'bg-teal-100',
            ),
            House::Senate => new self(
                hero: 'bg-[#4a1424]',
                chip: 'bg-red-700',
                photo: 'border-red-700 bg-rose-100',
                label: '!text-rose-200',
                link: '!text-rose-900',
                button: 'bg-rose-900 hover:bg-rose-950',
                icon: 'bg-rose-100 !text-rose-900',
                outline: 'border-rose-900 !text-rose-900',
                rule: 'border-rose-900',
                tint: 'bg-rose-100',
            ),
        };
    }

}
