# Verification — 2026-10-22

Runtime: PHP 8.4.0 with SQLite, GD/WebP, mbstring and fileinfo; Yii 1.1.32; Chrome through Playwright.

Passed checks:

- Theme manager: guest denied; ordinary admin denied even by direct POST; superadmin allowed. Invalid keys and GET activation rejected. Four desktop/mobile live themes have no horizontal overflow. Preview is private to the superadmin, persists through PJAX and does not mutate the active theme. Activation persists for guests and can roll back; the prior active theme was restored after testing. Superadmin media access remains available. Four generated design concepts are reference images, distinct from live implementation screenshots.

- PHP syntax validation of all application, public and CLI PHP files; JavaScript syntax checks.
- Composer manifest/lock validation and platform requirements, including GD.
- 29 service assertions on an isolated copy of the SQLite database: reservation duration, guarded stock decrement, insufficient-stock rollback, expiration, one-time release, paid-order transitions, return/refund, immutable totals, coupon minimum/expiry/capacity, money conversion, Markdown sanitization, WebP variants and inventory reconciliation.
- Two simultaneous purchases for the last unit: one succeeds. Two simultaneous uses of a single-use coupon: one succeeds; the rejected checkout leaves no stock or order writes.
- Passive background-worker check: an expired reservation is released and its stock restored without any web request.
- Browser flow: desktop and mobile storefront/admin; product creation and deletion; grouped price input; tag chips; specification table; shared multiple-image picker; searchable specification values; rendered Markdown and custom confirmation.
- Blog creation, single-image selection, tags, Markdown rendering and deletion.
- Actual settings save to rial, storefront conversion, then restore to toman.
- Actual checkout with WELCOME10, reservation confirmation and cancellation from admin.
- AJAX upload and drag/drop upload; valid WebP thumb/medium/large files; delete unreferenced file.
- Guest media API access denied; upload without CSRF rejected; spoofed image rejected; PDF accepted; image-only picker filters PDF; referenced-media deletion rejected.
- PJAX top progress bar visible while navigation is pending.
- Live financial/inventory page: every current product's stock matches its inventory ledger.
- Eight downloaded Picsum images imported locally; Vazir Regular/Medium/Bold served locally. Desktop/mobile/editor/media/report screenshots inspected.
- Demo seeder run twice after upgrade: the second run adds no duplicate active scenarios, catalog or media.

Re-run with:

- `php tools/verify-commerce.php`
- `node tools/verify-race.cjs`
- `node tools/verify-browser.cjs`
- `node tools/verify-http.cjs`

The service and race suites use disposable SQLite copies. Browser tests create and remove temporary products/posts/media, and leave a cancelled test order in the order history. Runtime images and logs are in `protected/runtime`. The browser scripts use this machine's existing Chrome/Playwright paths.

Scope: MySQL code paths were not executed here. Payment confirmation is manual; no bank gateway was introduced. Reservation expiry runs every 10 seconds in the local worker and on incoming application requests; a production server needs its own worker or minute scheduler as described in FEATURES.md.


## CMS and theme customization verification — 2026-10-22

- 130 PHP files and application/test JavaScript passed syntax checks.
- 24 additional isolated SQLite checks passed: hierarchical categories, cycle/missing-parent rejection, safe menu URLs and location constraints, collection ordering and limits, theme whitelist/invalid media rejection, product presentation overrides, catalog order blocking and price visibility.
- `node tools/verify-cms-browser.cjs --all` passed end to end on a separate SQLite copy and temporary server, including the earlier product/blog/media/checkout/HTTP/theme suites.
- Browser checks covered real menu and collection creation, persisted theme sections, chosen collection carousel, template/product overrides, descendant category results, hidden purchasing/prices/structured offers, direct cart POST rejection and ordinary-admin denial for theme customization.
- Analytics tests passed for actual anonymous browser beacons, event deduplication, CSRF, allowed paths, referrer host-only storage, hashed visitor identifiers and admin/DNT exclusions.
- All four themes were inspected at desktop and 390px mobile widths; no horizontal overflow. Generated product and hero artwork is registered in the shared media library with three WebP variants.
- Missing static files and dotted routes return 404. Closing the media dialog invalidates pending library results. Real media deletion refreshes both the dialog and library overview.
- A fresh SQLite database was installed and seeded successfully; repeated CMS seeding preserved the existing sample configuration.
- The 29 commerce checks and concurrent last-stock/coupon tests passed again.
- GitHub Actions workflow is included for fresh SQLite installation, syntax, service, concurrency and browser checks; a remote workflow result is separate from the locally observed passes above.

Scope: tested on PHP 8.4 / SQLite / Chrome. MySQL execution and an external Matomo instance were not exercised. Matomo remains optional and unset. Built-in daily visitor counts are browser-session estimates, not person-level identity.
