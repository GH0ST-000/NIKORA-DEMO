# Ticket API Documentation

A complete support ticketing system that allows customers to submit and track issues while enabling support teams to manage and resolve requests.

---

## Table of Contents

- [Authentication](#authentication)
- [Access Control](#access-control)
- [Data Model](#data-model)
- [Endpoints](#endpoints)
  - [List Tickets](#list-tickets)
  - [Create Ticket](#create-ticket)
  - [Get Ticket](#get-ticket)
  - [Update Ticket](#update-ticket)
  - [Delete Ticket](#delete-ticket)
  - [Close Ticket](#close-ticket)
  - [Reopen Ticket](#reopen-ticket)
  - [List Messages](#list-messages)
  - [Add Message](#add-message)
- [Lifecycle Flow](#lifecycle-flow)
- [Error Reference](#error-reference)
- [Admin Panel](#admin-panel)
- [Permissions Reference](#permissions-reference)

---

## Authentication

All ticket endpoints require a valid JWT token. Obtain one via the login endpoint.

**Login**

```
POST /api/auth/login
```

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Response**

```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

Pass the token in every subsequent request:

```
Authorization: Bearer <access_token>
```

---

## Access Control

The system has two tiers of access:

| Actor | Description | Capabilities |
|-------|-------------|--------------|
| **Customer** | Any authenticated user with `create_ticket` permission | Create tickets, view own tickets, update title/description of own tickets, close/reopen own tickets, reply to own tickets |
| **Support Agent / Admin** | User with `view_any_ticket` + `update_ticket` + `delete_ticket` | View all tickets, update status/priority/assignment, delete tickets, reply to any ticket |

**Key rule:** Customers can only see and interact with their own tickets. Users with `view_any_ticket` see all tickets across the system.

**Required permissions per action:**

| Action | Minimum permission |
|--------|-------------------|
| List tickets | `create_ticket` (own only) or `view_any_ticket` (all) |
| View ticket | Owner of ticket, or `view_ticket` |
| Create ticket | `create_ticket` |
| Update ticket (full) | `update_ticket` |
| Update ticket (title/description only) | Owner of ticket |
| Delete ticket | `delete_ticket` |
| Close ticket | Owner of ticket, or `update_ticket` |
| Reopen ticket | Owner of ticket, or `update_ticket` |
| Add message | Owner of ticket, or `update_ticket` |

---

## Data Model

### Ticket object

```json
{
  "id": 1,
  "title": "Cannot login to my account",
  "description": "I have been trying to login but keep getting an error.",
  "status": "open",
  "priority": "high",
  "user_id": 12,
  "user": {
    "id": 12,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "assigned_to": null,
  "assignee": null,
  "messages_count": 3,
  "attachments": [],
  "messages": [],
  "closed_at": null,
  "created_at": "2026-04-06T10:00:00.000000Z",
  "updated_at": "2026-04-06T10:05:00.000000Z"
}
```

**Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique ticket identifier |
| `title` | string | Short summary of the issue |
| `description` | string | Full description of the issue |
| `status` | string | Current status (see values below) |
| `priority` | string | Urgency level (see values below) |
| `user_id` | integer | ID of the customer who opened the ticket |
| `user` | object\|null | Customer details (loaded on list and show) |
| `assigned_to` | integer\|null | ID of the support agent assigned |
| `assignee` | object\|null | Assignee details (loaded on list and show) |
| `messages_count` | integer | Number of replies (included on list) |
| `attachments` | array | File attachments (included on show) |
| `messages` | array | Reply thread (included on show only) |
| `closed_at` | string\|null | ISO 8601 timestamp when ticket was closed |
| `created_at` | string | ISO 8601 creation timestamp |
| `updated_at` | string | ISO 8601 last updated timestamp |

**Status values:**

| Value | Meaning |
|-------|---------|
| `open` | Newly created, awaiting response |
| `in_progress` | Being actively worked on |
| `resolved` | Issue has been resolved |
| `closed` | Ticket is closed — no new replies accepted |

**Priority values:**

| Value | Meaning |
|-------|---------|
| `low` | Non-urgent, no immediate impact |
| `medium` | Moderate impact, standard handling (default) |
| `high` | Critical issue requiring immediate attention |

### Message object

```json
{
  "id": 5,
  "ticket_id": 1,
  "user_id": 12,
  "user": {
    "id": 12,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "body": "Here is some additional information about the issue.",
  "created_at": "2026-04-06T10:10:00.000000Z",
  "updated_at": "2026-04-06T10:10:00.000000Z"
}
```

### Attachment object

```json
{
  "id": 2,
  "ticket_id": 1,
  "file_path": "tickets/attachments/uuid.pdf",
  "file_name": "screenshot.pdf",
  "file_size": 204800,
  "mime_type": "application/pdf",
  "created_at": "2026-04-06T10:00:00.000000Z",
  "updated_at": "2026-04-06T10:00:00.000000Z"
}
```

---

## Endpoints

### List Tickets

Returns a paginated list of tickets. Customers see only their own tickets. Admins with `view_any_ticket` see all tickets.

```
GET /api/tickets
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `per_page` | integer | No | Items per page. Range: 1–100. Default: `25` |
| `status` | string | No | Filter by status: `open`, `in_progress`, `resolved`, `closed` |
| `priority` | string | No | Filter by priority: `low`, `medium`, `high` |
| `search` | string | No | Search by keyword in `title` or `description` |

**Example request:**

```
GET /api/tickets?status=open&priority=high&per_page=10
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "title": "Cannot login to my account",
      "description": "I have been trying to login...",
      "status": "open",
      "priority": "high",
      "user_id": 12,
      "user": { "id": 12, "name": "John Doe", "email": "john@example.com" },
      "assigned_to": null,
      "assignee": null,
      "messages_count": 2,
      "attachments": [],
      "messages": [],
      "closed_at": null,
      "created_at": "2026-04-06T10:00:00.000000Z",
      "updated_at": "2026-04-06T10:00:00.000000Z"
    }
  ],
  "links": {
    "first": "http://app.test/api/tickets?cursor=",
    "last": null,
    "prev": null,
    "next": "http://app.test/api/tickets?cursor=eyJpZCI6..."
  },
  "meta": {
    "path": "http://app.test/api/tickets",
    "per_page": 10,
    "next_cursor": "eyJpZCI6...",
    "prev_cursor": null
  }
}
```

> Pagination uses cursor-based navigation. Use `next_cursor` / `prev_cursor` values as the `cursor` query parameter to navigate pages.

---

### Create Ticket

Creates a new ticket. The authenticated user automatically becomes the ticket owner.

```
POST /api/tickets
```

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `title` | string | Yes | Short summary. Max 255 characters |
| `description` | string | Yes | Full issue description. Max 10,000 characters |
| `priority` | string | No | `low`, `medium`, or `high`. Defaults to `medium` |

**Example request:**

```json
{
  "title": "Cannot login to my account",
  "description": "I have been trying to login but keep getting a 401 error after entering my password correctly.",
  "priority": "high"
}
```

**Response `201 Created`:**

```json
{
  "data": {
    "id": 1,
    "title": "Cannot login to my account",
    "description": "I have been trying to login but keep getting a 401 error after entering my password correctly.",
    "status": "open",
    "priority": "high",
    "user_id": 12,
    "user": { "id": 12, "name": "John Doe", "email": "john@example.com" },
    "assigned_to": null,
    "assignee": null,
    "messages_count": null,
    "attachments": [],
    "messages": [],
    "closed_at": null,
    "created_at": "2026-04-06T10:00:00.000000Z",
    "updated_at": "2026-04-06T10:00:00.000000Z"
  }
}
```

> `status` is always set to `open` on creation. `title` and `description` are automatically trimmed of leading/trailing whitespace.

---

### Get Ticket

Returns full ticket details including the reply thread and attachments.

```
GET /api/tickets/{id}
```

**Example request:**

```
GET /api/tickets/1
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "data": {
    "id": 1,
    "title": "Cannot login to my account",
    "description": "I have been trying to login but keep getting a 401 error.",
    "status": "in_progress",
    "priority": "high",
    "user_id": 12,
    "user": { "id": 12, "name": "John Doe", "email": "john@example.com" },
    "assigned_to": 5,
    "assignee": { "id": 5, "name": "Agent Smith", "email": "agent@support.com" },
    "messages_count": 2,
    "attachments": [
      {
        "id": 1,
        "ticket_id": 1,
        "file_path": "tickets/attachments/uuid.png",
        "file_name": "error-screenshot.png",
        "file_size": 98304,
        "mime_type": "image/png",
        "created_at": "2026-04-06T10:00:00.000000Z",
        "updated_at": "2026-04-06T10:00:00.000000Z"
      }
    ],
    "messages": [
      {
        "id": 1,
        "ticket_id": 1,
        "user_id": 12,
        "user": { "id": 12, "name": "John Doe", "email": "john@example.com" },
        "body": "The error started happening after the latest update.",
        "created_at": "2026-04-06T10:05:00.000000Z",
        "updated_at": "2026-04-06T10:05:00.000000Z"
      },
      {
        "id": 2,
        "ticket_id": 1,
        "user_id": 5,
        "user": { "id": 5, "name": "Agent Smith", "email": "agent@support.com" },
        "body": "We are investigating the issue and will update you shortly.",
        "created_at": "2026-04-06T10:15:00.000000Z",
        "updated_at": "2026-04-06T10:15:00.000000Z"
      }
    ],
    "closed_at": null,
    "created_at": "2026-04-06T10:00:00.000000Z",
    "updated_at": "2026-04-06T10:20:00.000000Z"
  }
}
```

> Customers will receive `403 Forbidden` if they attempt to view a ticket that does not belong to them.

---

### Update Ticket

Updates ticket fields. Behavior differs by role:

- **Customers (owner, no `update_ticket` permission):** Can only update `title` and `description`. Attempts to change `status`, `priority`, or `assigned_to` are silently ignored.
- **Support agents / admins (with `update_ticket` permission):** Can update all fields including `status`, `priority`, and `assigned_to`.

```
PUT /api/tickets/{id}
```

**Request body** (all fields optional — only send what needs to change):

| Field | Type | Who can set | Description |
|-------|------|-------------|-------------|
| `title` | string | Owner, Admin | Max 255 characters |
| `description` | string | Owner, Admin | Max 10,000 characters |
| `status` | string | Admin only | `open`, `in_progress`, `resolved`, `closed` |
| `priority` | string | Admin only | `low`, `medium`, `high` |
| `assigned_to` | integer\|null | Admin only | User ID of support agent, or `null` to unassign |

> Setting `status` to `closed` via this endpoint automatically sets `closed_at` to the current timestamp.

**Example — Admin assigns and progresses a ticket:**

```json
{
  "status": "in_progress",
  "priority": "high",
  "assigned_to": 5
}
```

**Example — Customer updates description:**

```json
{
  "description": "Also seeing the issue in Safari, not just Chrome."
}
```

**Response `200 OK`:**

```json
{
  "data": {
    "id": 1,
    "title": "Cannot login to my account",
    "description": "Also seeing the issue in Safari, not just Chrome.",
    "status": "in_progress",
    "priority": "high",
    "user_id": 12,
    "user": { "id": 12, "name": "John Doe", "email": "john@example.com" },
    "assigned_to": 5,
    "assignee": { "id": 5, "name": "Agent Smith", "email": "agent@support.com" },
    "messages_count": null,
    "attachments": [],
    "messages": [],
    "closed_at": null,
    "created_at": "2026-04-06T10:00:00.000000Z",
    "updated_at": "2026-04-06T10:30:00.000000Z"
  }
}
```

---

### Delete Ticket

Permanently deletes a ticket along with all its messages and attachments.

```
DELETE /api/tickets/{id}
```

Requires `delete_ticket` permission.

**Response `200 OK`:**

```json
{
  "message": "Ticket deleted successfully"
}
```

---

### Close Ticket

Closes the ticket. No new messages can be added to a closed ticket. Sets `closed_at` timestamp.

```
POST /api/tickets/{id}/close
```

Available to: ticket owner, or user with `update_ticket` permission.

**Response `200 OK`:**

```json
{
  "data": {
    "id": 1,
    "status": "closed",
    "closed_at": "2026-04-06T11:00:00.000000Z",
    ...
  }
}
```

**Error — ticket already closed `422 Unprocessable Content`:**

```json
{
  "message": "Ticket is already closed"
}
```

---

### Reopen Ticket

Reopens a closed ticket. Sets `status` back to `open` and clears `closed_at`.

```
POST /api/tickets/{id}/reopen
```

Available to: ticket owner, or user with `update_ticket` permission.

**Response `200 OK`:**

```json
{
  "data": {
    "id": 1,
    "status": "open",
    "closed_at": null,
    ...
  }
}
```

**Error — ticket is not closed `422 Unprocessable Content`:**

```json
{
  "message": "Ticket is not closed"
}
```

---

### List Messages

Returns the reply thread for a ticket in chronological order (oldest first).

```
GET /api/tickets/{id}/messages
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `per_page` | integer | No | Items per page. Range: 1–100. Default: `25` |

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "ticket_id": 1,
      "user_id": 12,
      "user": { "id": 12, "name": "John Doe", "email": "john@example.com" },
      "body": "The error started happening after the latest update.",
      "created_at": "2026-04-06T10:05:00.000000Z",
      "updated_at": "2026-04-06T10:05:00.000000Z"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

---

### Add Message

Adds a reply to the ticket thread. Blocked on closed tickets.

```
POST /api/tickets/{id}/messages
```

Available to: ticket owner, or user with `update_ticket` permission.

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `body` | string | Yes | Message content. Max 10,000 characters |

**Example request:**

```json
{
  "body": "Here is some additional information. The error code shown is AUTH_TOKEN_EXPIRED."
}
```

**Response `201 Created`:**

```json
{
  "data": {
    "id": 3,
    "ticket_id": 1,
    "user_id": 12,
    "user": { "id": 12, "name": "John Doe", "email": "john@example.com" },
    "body": "Here is some additional information. The error code shown is AUTH_TOKEN_EXPIRED.",
    "created_at": "2026-04-06T10:40:00.000000Z",
    "updated_at": "2026-04-06T10:40:00.000000Z"
  }
}
```

**Error — ticket is closed `422 Unprocessable Content`:**

```json
{
  "message": "Cannot add messages to a closed ticket"
}
```

---

## Lifecycle Flow

### Standard customer-to-support flow

```
Customer creates ticket
        │
        ▼
  status: open
        │
        ▼
Support agent picks up ticket, assigns to self
  PUT /api/tickets/{id}
  { "status": "in_progress", "assigned_to": 5 }
        │
        ▼
  status: in_progress
        │
  Both parties exchange messages
  POST /api/tickets/{id}/messages
        │
        ▼
Agent resolves the issue
  PUT /api/tickets/{id}
  { "status": "resolved" }
        │
        ▼
  status: resolved
        │
Customer confirms resolution, ticket is closed
  POST /api/tickets/{id}/close
        │
        ▼
  status: closed  ◄──── No more messages allowed
        │
        │ (Optional) Customer needs to reopen
        ▼
  POST /api/tickets/{id}/reopen
        │
        ▼
  status: open  ──────► cycle continues
```

### Status transition rules

| From | To | Who |
|------|----|-----|
| `open` | `in_progress` | Admin |
| `open` | `resolved` | Admin |
| `open` | `closed` | Owner, Admin |
| `in_progress` | `resolved` | Admin |
| `in_progress` | `closed` | Owner, Admin |
| `resolved` | `closed` | Owner, Admin |
| `closed` | `open` | Owner, Admin (via reopen endpoint) |
| Any | `open` | Owner, Admin (via reopen endpoint) |

---

## Error Reference

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| `200` | Success |
| `201` | Created successfully |
| `401` | Missing or invalid JWT token |
| `403` | Authenticated but not authorized for this action |
| `404` | Ticket or resource not found |
| `422` | Validation failed or business rule violated |

### Validation error format

```json
{
  "message": "The title field is required.",
  "errors": {
    "title": [
      "The title field is required."
    ]
  }
}
```

### Common validation errors

| Field | Rule | Message |
|-------|------|---------|
| `title` | required | `The title field is required.` |
| `title` | max:255 | `The title field must not be greater than 255 characters.` |
| `description` | required | `The description field is required.` |
| `priority` | in:low,medium,high | `The selected priority is invalid.` |
| `status` | in:open,in_progress,resolved,closed | `The selected status is invalid.` |
| `assigned_to` | exists:users,id | `The selected assigned to is invalid.` |

---

## Admin Panel

Tickets are also visible and manageable in the Filament admin panel at `/admin`.

**Navigation:** Support → Tickets

**Features:**
- Full table with status and priority badges, reply count, customer name, assignee
- Filter by status and priority
- Search by ticket title
- Edit form with full field access (status, priority, assignment)
- Bulk delete

**Super Admin account** (created via `php artisan db:seed --class=SuperAdminSeeder`):

| Field | Value |
|-------|-------|
| Email | `superAdmin@geoaudit.com` |
| Password | `password` |
| Role | `Super Admin` (all permissions) |

---

## Permissions Reference

Permissions follow the pattern `{action}_{resource}`. Ticket permissions:

| Permission | Description |
|------------|-------------|
| `create_ticket` | Create tickets and view own tickets |
| `view_any_ticket` | View all tickets (not just own) |
| `view_ticket` | View a specific ticket (granted by `view_any_ticket` or ownership) |
| `update_ticket` | Update any ticket — status, priority, assignment, all fields |
| `delete_ticket` | Permanently delete tickets |
| `restore_ticket` | Restore soft-deleted tickets |
| `force_delete_ticket` | Force-delete tickets |

Assign permissions to users via the role system or directly:

```
POST /api/users/{user}/roles
{
  "role": "Recall Admin"
}
```

The `Recall Admin` and `Super Admin` roles have all permissions including full ticket access.
