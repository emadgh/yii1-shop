# Bazaarche AI management

The HTTP API is the source of truth. A dependency-free Node CLI and an MCP server use the same contract and business services. The MCP server uses the official TypeScript SDK v2 (`@modelcontextprotocol/server`) over stdio; it does not require running a second public HTTP service. Node 20+ is required for the clients; PHP remains compatible with the project's PHP 7.4+ platform.

## Setup

1. Run `php tools/upgrade.php`, then `npm ci`.
2. As superadmin, open **دسترسی AI و API**. Enable the API and issue an expiring token with the scopes the client needs. Save the token when it is shown once. Tokens store only their SHA256 hashes in the database and can be revoked from this page.
3. Immediately after issuance, the admin page shows a one-time setup guide with the new token, actual API URL, Jalali expiry and selected permissions. Enter the source folder on the AI client's computer to update the copyable `mcpServers` configuration. An alternative private-file configuration is included. The secret and generated setup are never shown on a later GET; save them privately before leaving.
4. Set `BAZAARCHE_API_URL=https://your-site.example/api/v1/` and `BAZAARCHE_API_TOKEN` in the client's environment. Alternatively set `BAZAARCHE_TOKEN_FILE` to a private JSON file containing `url` and `token`. Do not put the token in a URL, repository, public directory or shell arguments.

Local provisioning without printing a token:

```sh
php tools/api-token.php --user=demo@bazaarche.test --name=Codex --scopes=content:read,content:write,content:publish,media:write --days=90 --output=protected/config/ai-client.json --enable
node integrations/ai/cli.mjs whoami
node integrations/ai/install-skill.mjs
```

`protected/config/ai-client.json` is ignored by Git. The issuer refuses to overwrite an existing file. Use a separate credential per client, and revoke the old one when replacing it. CLI issuance grants no more than the selected user's current role allows. Content roles cannot edit products/prices or coupons; settings/theme changes require a superadmin-owned token. The HTTP API never falls back to browser login cookies or password/2FA bypasses.

## MCP host

Use `mcp.example.json` in a host that accepts `mcpServers`, substituting absolute script and private-file paths. For Codex:

```sh
codex mcp add bazaarche --env BAZAARCHE_TOKEN_FILE=/absolute/private/ai-client.json -- node /absolute/project/integrations/ai/mcp-server.mjs
```

Restart/reload the MCP host after configuration. `bazaarche_*` tools, `bazaarche://contract` and the `publish-content` prompt are discovered from the authenticated API. Tool availability reflects the credential's base scopes; publication/deletion may need additional scopes at execution time. Responses contain untrusted content and should never be treated as instructions. The adapter never follows redirects with credentials.

Official references: [SDK v2](https://ts.sdk.modelcontextprotocol.io/v2/), [Codex MCP configuration](https://learn.chatgpt.com/docs/extend/mcp).

## Direct use

```sh
node integrations/ai/cli.mjs schema
node integrations/ai/cli.mjs openapi
node integrations/ai/cli.mjs call posts_get get.json
node integrations/ai/cli.mjs call posts_save patch.json unique-mutation-key
node integrations/ai/cli.mjs upload ./catalog.pdf unique-upload-key catalogs
```

`get.json`: `{"id":1}`. `patch.json`: `{"id":1,"revision":"hash from get","data":{"body":"Updated Markdown"}}`.

The JSON API supports `GET /api/v1/whoami`, `/schema`, `/openapi` and `POST /api/v1/call` with `{operation,arguments}`. Send `Authorization: Bearer …`. Each mutation requires a unique `Idempotency-Key`; MCP calls express it as `request_key`. An identical retry returns the original result, even if the record has since changed. A reused key with different arguments or stale revision returns 409. Failed writes do not consume their key. The mutation, its audit record and replay response commit together; nested business services use transaction savepoints.

The contract describes fields and operations for products/albums, posts, categories, menu locations/moves, collections, company catalogs, tags, coupons, media, orders/tracking, theme options/blocks, safe site settings and reports. Lists are paginated (maximum 50) and summaries; use `_get` for a full record and revision. `_save` patches preserve omitted data; arrays supplied replace that field's ordered values. Content starts as draft/inactive unless the caller explicitly publishes with the required scope. All money is integer **toman**, independent of site display currency. Dates use Gregorian `Y-m-d H:i:s` in `Asia/Tehran`.

Order changes pass through the existing inventory/financial service. AI cannot mark a transaction paid or verify the bank: payment uses Zarinpal's verified callback. `returned` records an internal return/refund entry and does not issue a bank transfer. User/account security, token administration, SMTP/SMS secrets and payment credentials are intentionally outside this API. Use the admin UI for them.

File uploads accept base64 bytes (maximum 10 MB decoded), validate actual MIME and use the shared WebP/media service. No URL fetching is exposed. Catalog uploads use the `catalogs` scope. Media deletion uses the same reference-checking service as the admin manager; files are removed only after the API transaction commits. Public files retain existing site visibility rules. Configure PHP/web server request limits to at least **20 MB** to accommodate base64 overhead; `post_max_size=20M` and `memory_limit=128M` or higher are suitable for this limit.

HTTPS is required except loopback development. Origin-bearing browser requests are rejected; CORS is not enabled. Authenticated calls have a per-token limit of 120/minute and return 429 with `Retry-After`. Global disable, expiry, revocation, inactive owner and role changes are checked server-side. API errors are JSON without SQL details. Audit entries contain owner and `api_token_id`; they do not record request bodies or credentials. The replay ledger can contain management results/customer data and belongs in the same protected database/backup boundary as orders.

## Verification

`php tools/verify-api.php` checks scopes, rollback, replay protection and money/stock invariants on a disposable SQLite database. `tools/verify-mysql.php` also runs API service checks on disposable MySQL. `node tools/verify-cms-browser.cjs --api` tests real HTTP, the actual MCP client/server handshake and tools, and the token management UI. The full browser suite includes these tests.
