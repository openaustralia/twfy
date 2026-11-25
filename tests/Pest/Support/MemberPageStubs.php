<?php

/**
 * @file
 * What the member page specs share: stand-ins for what isn't loaded in tests, and visits through Laravel.
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use OpenAustralia\TWFY\Auth\ErrorPage;
use OpenAustralia\TWFY\Http\LegacyLayout;
use OpenAustralia\TWFY\Parliament\RecentSpeeches;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stand-ins and requests for the member page specs.
 */
final class MemberPageStubs {

    /**
     * Stands in for the legacy layout, error page and search. The layout shows its page key and subtitle.
     */
    public static function bind(): void {
        twfy_laravel()->bind(LegacyLayout::class, fn () => new class extends LegacyLayout {

            public function top(string $page, array $metadata = []): string {
                return "[top:$page:" . ($metadata['subtitle'] ?? '') . ']';
            }

            public function bottom(): string {
                return '[bottom]';
            }

        });
        twfy_laravel()->bind(ErrorPage::class, fn () => new class extends ErrorPage {

            public function render(int $status): string {
                return "[error:$status]";
            }

        });
        self::speeches([]);
    }

    /**
     * Stands in for the visitor.
     *
     * @param string $electorate
     *   The electorate they have given.
     * @param int|null $userId
     *   Their user id, if signed in.
     */
    public static function visitor(string $electorate = '', ?int $userId = null): void {
        $GLOBALS['THEUSER'] = new class($electorate, $userId) {

            public function __construct(private string $electorate, private ?int $userId) {
            }

            public function constituency(): string {
                return $this->electorate;
            }

            public function isloggedin(): bool {
                return $this->userId !== null;
            }

            public function user_id(): ?int {
                return $this->userId;
            }

        };
    }

    /**
     * Stands in for the search.
     *
     * @param list<array<string, string>>|null $speeches
     *   What the search gives, or null when it is broken.
     */
    public static function speeches(?array $speeches): void {
        twfy_laravel()->bind(RecentSpeeches::class, fn () => new class($speeches) extends RecentSpeeches {

            public function __construct(private ?array $speeches) {
            }

            public function for(int $personId): ?array {
                return $this->speeches;
            }

        });
    }

    /**
     * The response to a visit.
     */
    public static function get(string $address): Response {
        return twfy_laravel()->make(Kernel::class)->handle(Request::create($address));
    }

}
