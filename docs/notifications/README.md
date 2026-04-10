# In-app notifications API (Laravel + Pusher)

This document is for **frontend developers** integrating the Nikora API notification system. Notifications are stored in the database (`app_notifications` table, column `data` for JSON metadata). The HTTP JSON field for that column is named **`payload`** (not `data`) so it does not clash with Laravel’s JsonResource `data` wrapper.

## Business rules (enforced on the server)

- Users **without** admin notification roles (`Super Admin`, `Recall Admin` — see `config/notifications.php`) may **only** receive notifications where `module === "chat"`.
- **Admins** (those roles) can receive all supported modules.
- **Creating** a notification via `POST /api/notifications` with a non-`chat` module requires the caller to be an admin. Targeting a non-admin with a non-`chat` module returns **422** with a `module` validation error.
- The frontend must **never** assume it can filter or authorize modules; the API and realtime payloads already reflect what each user is allowed to receive.

## Environment variables

### Backend (`.env`)

| Variable | Purpose |
|----------|---------|
| `BROADCAST_CONNECTION` | Set to `pusher` for realtime (Laravel 11+; older docs may say `BROADCAST_DRIVER`). |
| `PUSHER_APP_ID` | Pusher app id |
| `PUSHER_APP_KEY` | Pusher key (also used on the client) |
| `PUSHER_APP_SECRET` | Pusher secret (server only) |
| `PUSHER_APP_CLUSTER` | e.g. `eu`, `mt1` |
| `PUSHER_HOST` | Optional; custom host |
| `PUSHER_PORT` | Optional; default `443` |
| `PUSHER_SCHEME` | `https` recommended |

Use `BROADCAST_CONNECTION=log` or `null` locally if you do not need websockets.

### Frontend (e.g. Vite `.env`)

| Variable | Purpose |
|----------|---------|
| `VITE_PUSHER_APP_KEY` | Same value as `PUSHER_APP_KEY` |
| `VITE_PUSHER_APP_CLUSTER` | Same as `PUSHER_APP_CLUSTER` |
| `VITE_API_URL` | Base URL of the API (for REST and broadcasting auth) |

Queue: broadcasting uses queued jobs by default (`ShouldBroadcast`). Run a queue worker in production (`php artisan queue:work`) so events reach Pusher.

## Authentication

All routes below require a **JWT** (same as the rest of the API): `Authorization: Bearer {token}`.

## API reference

Base path: `/api` (same as existing API).

### GET `/api/notifications`

**Auth:** required  

**Query parameters**

| Param | Type | Description |
|-------|------|-------------|
| `per_page` | int (1–100) | Page size, default 25 |
| `page` | int | Standard Laravel pagination |
| `module` | string | One of the `NotificationModule` values (e.g. `chat`, `products`) |
| `is_read` | string/bool | `true` / `false` / `1` / `0` |
| `type` | string | Exact notification type (e.g. `chat.new_message`) |

**Example response (200)**

```json
{
  "data": [
    {
      "id": 1,
      "type": "chat.new_message",
      "title": "New message from Ada",
      "message": "Hello…",
      "module": "chat",
      "payload": { "conversation_id": 3, "message_id": 12 },
      "is_read": false,
      "read_at": null,
      "sender_id": 2,
      "sender": { "id": 2, "name": "Ada" },
      "entity_id": 12,
      "entity_type": "chat_message",
      "action": "sent",
      "created_at": "2026-04-10T12:00:00.000000Z",
      "updated_at": "2026-04-10T12:00:00.000000Z"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "from": 1, "last_page": 1, "per_page": 25, "to": 1, "total": 1 }
}
```

### GET `/api/notifications/unread-count`

**Auth:** required  

**Response (200)**

```json
{ "unread_count": 3 }
```

### PATCH `/api/notifications/{notification}/read`

**Auth:** required  

Marks one notification as read. `{notification}` must belong to the authenticated user (otherwise **404**).

**Response (200):** same shape as one item in the list (`data` wrapper with the resource fields).

### PATCH `/api/notifications/read-all`

**Auth:** required  

**Response (200)**

```json
{ "updated_count": 12 }
```

### POST `/api/notifications`

**Auth:** required  

Creates a notification for another user (or yourself). Caller must be an **admin** unless `module` is `chat`. Recipient rules still apply (non-admins cannot receive non-chat modules).

**Body (JSON)**

| Field | Required | Type | Notes |
|-------|----------|------|-------|
| `user_id` | yes | int | Recipient |
| `module` | yes | string | Enum value, e.g. `chat`, `products` |
| `type` | yes | string | Enum value, e.g. `custom`, `product.created` |
| `title` | yes | string | max 255 |
| `message` | yes | string | max 5000 |
| `data` | no | object | Stored in DB; exposed in API as `payload` |
| `sender_id` | no | int | Defaults to authenticated user |
| `entity_id` | no | int | |
| `entity_type` | no | string | |
| `action` | no | string | |

**Success:** **201** with the created notification resource (standard `data` wrapper).

**Errors**

- **403** — Non-admin attempted non-chat creation.
- **422** — Validation errors, or recipient may only receive chat (`module` error).

### DELETE `/api/notifications/{notification}`

**Auth:** required  

**Response (200)**

```json
{ "message": "Notification deleted successfully" }
```

## Modules and types (reference)

**Modules** (`module` field): `chat`, `dashboard`, `manufacturers`, `products`, `batches`, `warehouse_locations`, `receivings`, `tickets`, `ticket_messages`, `action_logs`, `user_roles`, `roles`, `permissions`.

**Types** (`type` field): see `App\Enums\NotificationType` in the backend (e.g. `chat.new_message`, `product.created`, `ticket.closed`, `user_role.assigned`).

## Pusher / private channel integration

### Channel naming

- Subscribe to: **`private-user.{userId}`** where `userId` is the authenticated user’s numeric id.
- Laravel registers the channel as `user.{userId}` in `routes/channels.php`; Pusher prefixes private channels with `private-`.

### Authorizing the private channel

1. Include the JWT in the **broadcasting auth** request the Pusher client performs.
2. Laravel exposes broadcasting auth under the API prefix with `auth:api` middleware (see `bootstrap/app.php`):  
   **POST** `/api/broadcasting/auth`  
   Body (typical): `socket_id`, `channel_name` (e.g. `private-user.5`).

If auth fails, Pusher reports a subscription error; the client should surface “Unable to subscribe to notifications”.

### Event name

- **Pusher event:** `notification.created` (listen without the leading dot; Laravel uses `broadcastAs`).

### Payload example (realtime)

```json
{
  "notification": {
    "id": 42,
    "type": "product.created",
    "title": "Product created",
    "message": "SKU-1 (Widget) was created.",
    "module": "products",
    "payload": { "product_id": 9 },
    "is_read": false,
    "read_at": null,
    "sender_id": 3,
    "sender": { "id": 3, "name": "Admin" },
    "entity_id": 9,
    "entity_type": "product",
    "action": "created",
    "created_at": "2026-04-10T12:00:00.000000Z",
    "updated_at": "2026-04-10T12:00:00.000000Z"
  }
}
```

Realtime delivery is **best-effort**; the database remains the source of truth. Always reconcile with `GET /api/notifications` after reconnect.

## Recommended frontend flow

1. After login, call `GET /api/notifications` and `GET /api/notifications/unread-count`.
2. Initialize Pusher (or Laravel Echo) with the app key and cluster.
3. Authenticate private channels against `/api/broadcasting/auth` using the JWT.
4. Subscribe to `private-user.{currentUserId}`.
5. Listen for `notification.created`: prepend the `notification` object into local state and increment unread count (if `is_read` is false).
6. When the user opens a notification, call `PATCH /api/notifications/{id}/read` and update local state / decrement count.
7. Optionally offer “Mark all read” via `PATCH /api/notifications/read-all`.
8. On focus / interval, refetch the list to heal missed events.

Mark-read endpoints **only** update the database. You may update the UI optimistically; optional future `notification.read` broadcast is not required for correctness.

## Error handling (client)

| Situation | Typical HTTP | Action |
|-----------|--------------|--------|
| Missing/invalid JWT | 401 | Redirect to login / refresh token |
| Non-admin creates non-chat notification | 403 | Hide UI or show permission message |
| Recipient cannot receive module | 422 `module` | Do not offer that action |
| Wrong user’s notification id | 404 | Remove stale id from UI |
| Validation | 422 | Show field errors |
| Broadcasting auth failure | 401/403 from `/api/broadcasting/auth` | Retry login; do not expose secret key |

## Security notes

- Private channels must be **authorized by the backend**; never embed secrets in the frontend except the **public** Pusher key.
- **Do not** trust the client to enforce admin vs non-admin rules; the API and `NotificationService` enforce them.
- Treat notification text as **untrusted** for HTML rendering (escape or use text nodes).

## Server-side triggers (existing modules)

Notifications are created from existing flows, including:

- **Chat:** new direct message (other participants).
- **Manufacturers / products / batches / warehouse locations / receivings:** create, update, delete → **admins only**.
- **Tickets:** created, closed, reopened, deleted (and status changes via ticket update) → **admins only**.
- **Ticket messages:** new message → **admins only**.
- **User roles:** assign/remove role → **admins only** (`user_roles` module).

Optional programmatic use: `NotificationService::notifyAdministratorsActionLog(...)` for sensitive audit-style alerts.

Adjust `config/notifications.php` if your deployment should treat additional roles as notification admins.
