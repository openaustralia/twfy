<?php

/**
 * @file
 * The old address of the Busiest API keys report, which bookmarks and links may still use.
 */

header('Location: /admin/api_keys.php' . ($_GET ? '?' . http_build_query($_GET) : ''), true, 301);
exit;
