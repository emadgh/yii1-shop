# Bazaarche direct API

From the project root, run:

```sh
node integrations/ai/cli.mjs whoami
node integrations/ai/cli.mjs schema
node integrations/ai/cli.mjs call posts_get get-post.json
node integrations/ai/cli.mjs call posts_save patch-post.json unique-mutation-key
node integrations/ai/cli.mjs upload /absolute/path/catalog.pdf unique-upload-key catalogs
```

The JSON file contains the operation arguments, for example `{"id":1}` for `posts_get`. For an update, use `{"id":1,"revision":"HASH_FROM_GET","data":{"body":"Markdown content"}}`. For creation, omit `id`/`revision`; use a unique English slug and the appropriate required model fields. Obtain the full allowed contract with `schema`; no arbitrary SQL or arbitrary model attributes are accepted.

Credentials come from `BAZAARCHE_API_TOKEN` with `BAZAARCHE_API_URL`, or from `BAZAARCHE_TOKEN_FILE`. The default private file is `protected/config/ai-client.json` relative to the working directory, containing `url` and `token`. The file is ignored by Git. The helper never prints the token. Do not read/display the file merely to confirm configuration; use `whoami`.

When this skill is installed globally and the current directory is not the project, set the client environment to the project’s absolute paths. The repository includes `integrations/ai/mcp.example.json` for MCP host setup; use an absolute script path and private token-file path. Start with `node integrations/ai/mcp-server.mjs`. Its stdout is reserved for the MCP protocol; diagnostics use stderr.

HTTP alternatives: `GET /api/v1/schema`, `GET /api/v1/whoami`, `GET /api/v1/openapi`, and `POST /api/v1/call` with `{operation,arguments}` and `Authorization: Bearer …`. Writes also require `Idempotency-Key`. Use HTTPS except local loopback; browser sessions and query-string tokens are not accepted.

Operation families:

- `products`, `posts`, `categories`, `menus`, `collections`, `catalogs`, `tags`, `coupons`: `_list`, `_get`, `_save`, `_delete`.
- `media_list`, `media_get`, `media_upload`, `media_delete` (base64 bytes, maximum 10 MB decoded).
- `categories_move`, `menus_move` with a current `tree_revision` and matching `location`.
- `orders_list`, `orders_get`, `orders_status`, `orders_tracking`; `reports_get`.
- `settings_get`, `settings_save`, `themes_get`, `themes_save`, `themes_activate`, `cache_clear`.

The installed global skill also has `scripts/client.mjs`, which supports these same CLI commands without any package dependency. Run it from the project root or set its credential environment explicitly. For local uploads it reads only the path supplied as the upload argument.
