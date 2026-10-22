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

Initial baseline scope (before the operations update below): MySQL was not exercised and payment confirmation was manual. See the current MySQL/Zarinpal verification section below. Reservation expiry runs every 10 seconds in the local worker and on incoming application requests; a production server needs its own worker or minute scheduler as described in FEATURES.md.


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

## Dashboard and management tables — 2026-10-22

- 22 isolated SQLite assertions passed for actual discounted-price sorting/filtering, Persian/grouped numeric input, rial conversion, combined filters, enum validation, literal/wildcard escaping, sort whitelisting, aggregate report filtering, scoped order items, removed-product fallback, daily visit counts, zero-day filling, paused analytics and consistent reporting boundaries.
- Browser checks passed for dashboard daily bars, server-derived totals, pagination, column controls and sortable headings across all management listings, combined filters preserved after reload, reset, independent report grids, matching status colors/selection, linked order thumbnails, 90-day charts and mobile overflow.
- The existing product/blog/media/checkout/HTTP/theme browser suites passed after the GridView migration. New tests are included in the isolated browser runner; the HTTP reconciliation assertion now checks result badges rather than matching filter dropdown option labels.
- Filter inputs use bound parameters; sort expressions and enum choices are defined by the application. Reports filter the complete dataset before pagination. MySQL was outside that initial verification; the operations update below adds real MySQL smoke coverage.

## Category workspace and scoped PJAX — 2026-10-22

- 13 isolated SQLite assertions passed: subtree moves, descendant preservation, before/after/root operations, sibling renumbering, cycle/self/unknown target rejection, stale move/edit rejection and rollback.
- Category browser checks passed with actual pointer drag/drop: left sticky editor inside viewport, contextual search and collapse, persisted subtree moves, preserved unsaved text, CSRF and stale/cycle rejection, Ajax validation/save, local locking and mobile layout.
- Delayed-request checks verified that only the requested grid receives an overlay and inert interaction lock. Neighboring DOM references remained identical. Filters, shorter final-page pagination and back navigation preserved scroll.
- Simulated failed requests retained the existing table, cleared the overlay and allowed retry. Independent report filters remained in both visible controls and the URL after other-grid updates.
- `node tools/verify-cms-browser.cjs --all` passed the CMS, product/blog/media/checkout, HTTP, theme, admin-table and new category/PJAX suites. `--pjax` runs the focused category/PJAX browser checks on a temporary database.
- The existing 24 CMS checks also passed after adding optional hierarchy revisions. PHP/JavaScript syntax and Git whitespace checks passed. Local runtime remains PHP 8.4 / SQLite / Chrome.

## Jalali calendar and Ajax cart — 2026-10-22

- Added 20 isolated calendar checks covering Nowruz, leap Esfand, Persian/Arabic digits, invalid dates/times, conversion round trips, and half-open SQL date filters across the year boundary. Stored timestamps remain Gregorian in Tehran time.
- Dedicated browser coverage exercises product-page Ajax add with automatic navbar opening, popup quantity updates, cart-page quantity/removal, synchronized quantities, unchanged document markers, quantity validation, stock caps and CSRF rejection.
- Browser checks open the Jalali picker after PJAX, save coupon dates and compare the canonical SQL values, exercise grid date pickers, inspect category thumbnail sizing, and check popup bounds on mobile and across all four themes.
- The browser harness supports `--cart` for focused checks using a temporary SQLite database. The full suite also runs these checks. Local validation uses PHP 8.4 / SQLite / Chrome; MySQL and actual PHP 7.4 execution are not covered locally. Composer resolves against PHP 7.4.33 for dependency compatibility.

## Shop operations and MySQL — 2026-10-22

- Local database `yii1_shop` created on MySQL 9.1.0, 127.0.0.1:3306. Existing SQLite records imported with IDs preserved; SQLite retained. Root credentials are local-only. The running storefront now uses MySQL. Fixed SQLite initialization statements leaking into a MySQL configuration and fixed Yii DISTINCT column quoting in the facet query.
- 18 checks passed on a disposable real MySQL database: shipping-inclusive frozen order totals, InnoDB stock reservation, hashed tracking token, sandbox URL and Rial payload, server verification, duplicate callback receipt guard, late-payment review, RFC TOTP vector, encryption roundtrip, Persian SQL search, transactional outbox, warning deduplication and independent variant reserve/cancel stock.
- Dedicated operations browser smoke passed: blank/disabled Zarinpal settings, shipping persistence, private order tracking, operations grids, CSV export/import, managed 301 redirect, sitemap, variant selection, TOTP activation/replay protection and content-role route/sidebar restrictions.
- Full isolated browser suite passed after these changes, including product/blog/media/checkout, four themes/mobile, scoped PJAX and category drag/drop. Backup test copied media, verified checksum, changed fixture data and successfully restored the original values into that disposable database.
- PHP/JavaScript syntax and existing commerce service checks passed. CI now provisions MySQL 8.4 for a separate integration smoke job step; the remote run is not claimed as verified locally.
- External payment responses are controlled test fixtures. No real or network sandbox payment was attempted without a Merchant ID. SMTP/Kavenegar delivery is untested without service credentials and remains inactive. Bank refunds and review transactions require operator reconciliation; the internal ledger does not execute a bank refund.
- Address province/city fields are text fields, not a maintained geographic dropdown dataset. Imports support CSV, not native XLSX. Warehouse role is reporting-only; stock writes remain manager operations. TOTP key backup is required for account recovery.
