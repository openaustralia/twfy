# Adaptive layout replaces separate mobile pages

The site's original mobile support is a second, parallel set of templates - `mobile.php`, `hansard_gid_mobile.php`
and friends - reached via a device-detecting rewrite in `conf/httpd.conf.ubuntu` (`mobile=1`). Each one duplicates
its desktop equivalent's logic with its own markup, so a change to page behaviour has to be made twice or it drifts
(see `docs/agents/issue-tracker.md`'s tracked debt, openaustralia/openaustralia#943). The redesign starting with
the debate transcript page (#939, PR #227) and continuing with the front page (PR #228) instead builds one
Tailwind-responsive template per page, using `md:`/`lg:` breakpoints to adapt layout at the CSS level rather than
serving different PHP entirely.

## Considered options

- Keep maintaining the separate `*_mobile.php` templates alongside each redesigned page: no new work up front, but
  permanently doubles the maintenance cost of every future change to a redesigned page, and the mobile templates
  would still be stuck on the pre-redesign look.
- Adaptive layout, one template per page (chosen): a single Plates template (eg `resources/views/hansard/
  transcript.php`) renders for every device, with Tailwind responsive classes handling the layout differences -
  matches how the rest of the modern web works, and means there's only ever one place to fix a bug or land a
  design change.

## Consequences

- `mobile.php`, `hansard_gid_mobile.php` and the other legacy mobile templates are frozen, not maintained forward -
  each carries a comment noting they're slated for removal (#943) and won't receive the redesign. Bugs found in
  them are only fixed if trivial; otherwise file an issue instead of investing further.
- `hansard_gid.php`'s `$usePlatesTemplate` gate only applies to non-mobile requests - the mobile device rewrite in
  `conf/httpd.conf.ubuntu` routes around it entirely, straight to the legacy mobile template. This is deliberate,
  not a gap: see the comment at the top of `hansard_gid_mobile.php`.
- As each further page gets redesigned (adjournment/section-index pages, PR #231, and beyond), its own
  `*_mobile.php` equivalent should be marked the same way rather than ported forward.
