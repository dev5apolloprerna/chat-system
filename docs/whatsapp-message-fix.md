# WhatsApp sending and receiving fix

The PHP changes fix account selection, reply routing, conversation reuse, incoming broadcasts, status updates, and webhook verification. The chat view now subscribes with conversation IDs, polls both messages and the chat list, escapes message text, and keeps unsent text when Meta rejects a send. Both `/api/wa/webhook` and `/webhook/whatsapp` use the same controller. GET is for Meta verification; POST receives messages and statuses. Use one callback URL in Meta, preferably `https://YOUR-BACKEND/api/wa/webhook`.

## Configuration for your reported servers

Use Salexo as the primary backend for this installation:

- Browser/chat URL and Laravel `APP_URL`: `https://salexo.in/chat_system`.
- Meta webhook callback: `https://salexo.in/chat_system/api/wa/webhook`.
- Salexo database: `smaar1u8_chat_system`.

This is the intended callback URL for the checked-in API route; confirm that your hosting rewrite rules serve the Laravel app at this subdirectory. In Meta's app dashboard, replace the Getdemo callback with this Salexo callback, verify using Salexo's active account `verify_token`, and confirm the WABA's `messages` subscription. Configure Salexo's business phone/account records and credentials before switching. Check all Meta apps subscribed to the relevant WABA, since an old app subscription can still deliver to Getdemo.

Keep `getdemo_chat_system` separate if it is used for another installation. Do not copy its DB credentials into Salexo or add a second DB write in the message controller. Existing reply history in Getdemo will not move automatically; reconcile it separately after backups, matching messages by WhatsApp ID and phone/account identity, with conversation/user/account ID remapping. Do not merge raw numeric IDs or run `migrate:fresh` on production.

The passwords supplied in chat are intentionally absent from this patch. Rotate both on their hosting accounts and enter the replacements securely in their respective runtime configuration. The repository has no active hardcoded Salexo/Getdemo message URLs; deployed web-server routing and Meta's external configuration still need checking.

## Why two databases can appear

This code uses Laravel's default DB connection; it does not copy chat records to another server. A browser send is stored by the backend serving its request. An incoming WhatsApp reply is stored by the backend targeted by Meta's callback URL. Pusher only notifies browsers; it never replicates MySQL data. Changing Pusher keys cannot reconcile two databases.

If two app instances serve the same chat system, point both to the same intended database (including `DB_HOST`, `DB_PORT`, `DB_DATABASE`, and any `DATABASE_URL` override), and route Meta callbacks to this backend. Configure matching `APP_KEY` values if these instances share encrypted account tokens and sessions. Do not overwrite production settings with the local SQLite settings used for cloud validation. If the servers represent independent installations, keep their databases separate and configure the correct Meta app/WABA subscriptions and callback routing for each business phone ID. This repository alone cannot establish which deployment is currently receiving a live callback.

On the backend receiving a callback, the metadata `phone_number_id` must match an active `whatsapp_accounts.phone_number_id`. Its `display_phone_number` must be the correct international business number. The logged-in client's `phone_number_id`, `whatsapp_meta_no`, API version, and permanent token must belong to that same account. Unknown webhook accounts are rejected instead of silently assigning messages to the latest account. The send-text/template API now accepts `phone_number_id` to select an account; without a selection it defaults only when exactly one account is active.

## Deployment

1. Back up production files and data. Deploy all files in the fix together; the updated chat view and controller share the same response contract. No production data migration or database reset is included.
2. Check each server's DB configuration privately, including `DATABASE_URL`. Do not paste secrets or DB passwords into logs/chat. Existing number rows with `+digits` or digit-only formats are supported; new messages use `+digits`.
3. Set `WHATSAPP_APP_SECRET` securely to the Meta app secret to enable POST signature validation. This is a raw runtime secret used locally for HMAC verification, not a proxy placeholder. Without it, POST signatures are not enforced for compatibility; enable it before exposing the callback publicly. GET verification now requires the configured active account's `verify_token`.
4. Configure Meta's callback and subscribe the intended WABA/app to `messages`. Delivery/read statuses use the same callback. Confirm the app is subscribed for each business phone you intend to serve. Do not configure only the separate status endpoint.
5. Run `php artisan optimize:clear`, then rebuild configuration with `php artisan config:cache` if your deployment normally caches it. Restart existing queue workers with `php artisan queue:restart` if using an asynchronous queue. Use matching Pusher app settings across the backend and browsers; polling still retrieves stored messages if Pusher is unavailable.
6. Send A → B, reply B → A, then send A → B again. Confirm Meta's message IDs appear once, the sender/receiver numbers are correct, and the same conversation contains both directions. Repeat with two business accounts, including the account that is not the newest record. Check a rejected Meta send retains the typed text.

## Validation and limits

Run `php artisan test --filter=WhatsAppMessagingTest`. The tests fake Meta and broadcasts and use a declared in-memory chat schema because this checkout omits base production migrations for conversations, messages, clients, and WhatsApp accounts. They do not prove a production schema or Meta credentials work. The account-row lock protects concurrent inbound handling on databases supporting row locks; run webhook workers against the same primary database. A unique production index on `(whatsapp_account_id, client_ref)` is recommended after checking/deduplicating existing rows; no index is applied automatically.

The checkout also lacks the Sass file referenced by its Mix config, the normal public front controller, and referenced static assets. These existing packaging problems are separate from the messaging changes. Cloud validation uses an external development router, not a replacement production public directory. Live Meta delivery, two-server database settings, webhook subscriptions, and production assets must be verified on the deployed hosts.

Cloud validation completed: 13 messaging regression tests passed, PHP syntax checks passed, and both login pages returned HTTP 200 using the external development router. The complete suite has 14 passing tests and one existing root-page test failure (expected 200, actual 404). Locked npm/Composer installation and Composer platform requirements passed, including a complete installer repeat run. The asset build and fresh migrations remain blocked by the missing repository files described above. No live Meta messages, production DB connection, callback setting, or deployed-server change was performed.
