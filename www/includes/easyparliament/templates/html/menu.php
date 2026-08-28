<?php
/*
    The primary site navigation bar: the section tabs (Home, Debates, ...) and,
    on the right, the login/account links.

    Included from PAGE::menu(), in whose scope this runs - $top_links is
    already built there, and $this is the PAGE object (used below to call
    user_bar(), same as everywhere else in this class).
*/
?>
                        <nav id="menu" aria-label="Main menu" class="!box-border max-md:!w-screen flex flex-col gap-1 bg-[#26343b] px-4 py-2 md:flex-row md:items-center md:justify-between md:gap-4 md:px-8">
                            <div id="bottommenu">
                                <ul class="!m-0 flex list-none flex-wrap gap-1 !p-0">
                                    <li><?php print implode("</li>\n\t\t\t<li>", $top_links); ?></li>
                                </ul>
                            </div>
                            <div id="topmenu">
                                <?php
                                $user_bottom_links = $this->user_bar($top_hilite, $bottom_hilite);
                                if ($user_bottom_links) {
                                    $bottom_links = $user_bottom_links;
                                }
                                ?>
                            </div>
                        </nav> <!-- end #menu -->
