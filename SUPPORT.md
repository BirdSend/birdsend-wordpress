# BirdSend WordPress Plugin — Support Guide

This guide is for **support staff** helping customers with common BirdSend plugin issues. For QA/release testing, see [TESTING.md](TESTING.md).

---

## Access requirements

- WordPress **Administrator** login on the customer site (or staging copy)
- Developer settings URL (bookmark internally):

  `wp-admin/admin.php?page=bswp-settings&action=developer`

This page is not in the BirdSend menu. It contains **QA Tools**, **Support Tools**, and **Activity Log**.

---

## Developer page overview

| Section | When to use |
|---------|-------------|
| **QA Tools** | Connection renewal problems, warning notice on wp-admin |
| **Support Tools** | “Sync all forms” stuck on Waiting for sync / Syncing |
| **Activity Log** | Confirm what the plugin did (renewal, email, sync, webhooks) |

---

## “Sync all forms” stuck on Waiting for sync / Syncing

### What the customer sees

On **BirdSend → Forms**, **Sync all forms** is disabled and shows:

- **Waiting for sync…** — queued, waiting for WordPress background tasks
- **Syncing…** — started but did not finish (often a timeout or crash)

### Support Tools (recommended fix)

Open **Developer settings → Support Tools**.

#### Step 1 — Check the status panel

| Field | What it means |
|-------|----------------|
| **Sync queue status** | `Idle` = OK. `Waiting for sync` or `Syncing` = stuck. |
| **BirdSend connected** | Must be **Yes**. |
| **Last plugin cron run** | Should update about every minute. **Over 5 minutes ago** → WP Cron may not be running. |

#### Step 2 — Run sync all forms now

1. Click **Run sync all forms now**.
2. Wait for the green or red notice.
3. Ask the customer to refresh **BirdSend → Forms**.

**Expected:** Green notice — sync completed; button enabled.

**If red:** Check [Connection warning notice](#birdsend-connection-warning-notice), then retry.

#### Step 3 — Reset sync status (if still stuck)

1. Click **Reset sync status** and confirm the dialog.
2. Customer refreshes Forms page — button should say **Sync all forms** again.
3. Click **Run sync all forms now** again, or ask customer to click **Sync all forms** once.

**Reset does not sync by itself** — it only clears the stuck lock.

### Quick decision guide

```
Forms stuck?
├── Not connected → Fix connection first
├── Waiting for sync + cron stale → Run sync all forms now
├── Syncing (stuck) → Reset sync status → Run sync all forms now
└── Still fails → Escalate to tech team
```

---

## BirdSend connection warning notice

### What the customer sees

Yellow banner on wp-admin pages:

> We could not automatically renew your connection to BirdSend…

BirdSend home may **still show Connected**.

The site owner may also receive an **email** (see below).

### Support steps

1. Open **Developer settings → QA Tools** — check **Connected**, **Last failed renewal**, **Warning notice active**.
2. Open **Activity Log** — filter `token_refresh_failed` and `renewal_failed_email`.
3. Ask the customer to:
   - Open **BirdSend** in WordPress admin
   - Click **Disconnect My BirdSend Account**
   - Connect their BirdSend account again
4. Click **Run connection renewal now** to confirm recovery (green notice).
5. If renewal keeps failing, escalate with Activity Log **Details** copied.

**Do not** use **Simulate warning (test UI only)** on a customer site — it is a fake warning for UI testing only and **does not send email**.

---

## Failure email (automatic)

When a **real** connection renewal fails, WordPress sends one email per 24 hours to:

**Settings → General → Administration Email Address**

| Item | Detail |
|------|--------|
| **Subject** | `[Site Name] BirdSend connection could not be renewed` |
| **Body** | Plain-language explanation + steps to disconnect and reconnect + link to BirdSend settings |

### How to confirm an email was sent

1. Developer settings → **Activity Log**
2. Filter: `renewal_failed_email`

**Details** shows recipient and subject.

If you see `renewal_failed_email_failed`, the site’s mail is misconfigured — escalate to tech (WP Mail SMTP, hosting SMTP, etc.).

### Testing failed renewal email (staging only)

Do **not** test on customer production sites.

**Easiest path (support + tech):**

1. Tech breaks staging credentials temporarily (see [TESTING.md — Negative testing](TESTING.md#negative-testing--failed-renewal--email)).
2. Support clicks **Run connection renewal now** on staging.
3. Check staging admin inbox + Activity Log for `renewal_failed_email`.
4. Tech restores credentials; support verifies green renewal + warning cleared.

**Note:** Only one failure email per 24 hours. Re-testing same day requires tech to clear the throttle option.

---

## Activity Log

Scroll to **Activity Log** on Developer settings.

| Event | Meaning |
|-------|---------|
| `token_refresh` | Renewal succeeded |
| `token_refresh_failed` | Renewal failed |
| `renewal_failed_email` | Failure notification email sent |
| `renewal_failed_email_failed` | Email could not be sent |

- Filter by event name (e.g. `renewal_failed_email`).
- **Details** boxes are copyable — paste into escalation tickets.

---

## When to escalate to the tech team

Escalate after you have tried the steps above and:

- **Run sync all forms now** fails while **BirdSend connected** is Yes
- **Run connection renewal now** fails after reconnect
- **Last plugin cron run** never updates (WP Cron / hosting issue)
- Activity Log shows `renewal_failed_email_failed`
- Customer reports missing/wrong forms after “successful” sync
- PHP error or white screen on Developer settings

**Include in escalation:**

- Site URL
- Plugin version (BirdSend home footer)
- Screenshot of **Support Tools** or **QA Tools** status
- Activity Log **Details** (copied text)
- Whether the customer received the failure email
- Steps already tried

---

## Tech team only (not for routine support)

```bash
# Sync queue
wp option get bswp_forms_sync_all
wp option update bswp_forms_sync_all 0   # same as Reset sync status

# Trigger plugin cron
wp cron event run bswp_every_minute_event
wp cron event run bswp_biweekly_token_refresh

# Renewal failure test setup
wp option get bswp_client --format=json > bswp_client.backup.json
wp option patch update bswp_client client_secret invalid-for-testing
wp option delete bswp_token_refresh_failed_email_at   # allow another test email

# Restore
wp option update bswp_client "$(cat bswp_client.backup.json)" --format=json

# Build release zip (from plugin repo root)
./scripts/build-release.sh
```

Full test plans: [TESTING.md](TESTING.md).
