# BirdSend WordPress Plugin — Testing Guide

This document covers testing for **connection auto-renewal**, **failure emails**, **admin notices**, **Support Tools**, **QA Tools**, **Activity Log**, and **release packaging**.

Use a **staging site** only. Do not run destructive or email tests on production.

For support staff handling customer issues, see [SUPPORT.md](SUPPORT.md).

---

## Before you start

### Who should run which tests?

| Section | Audience |
|---------|----------|
| [QA tester checklist](#qa-tester-checklist) | QA / non-technical testers |
| [Negative testing — failed renewal & email](#negative-testing--failed-renewal--email) | QA (with staging mail) + tech setup |
| [Technical team checklist](#technical-team-checklist) | Developers / DevOps |

### URLs

| Page | URL |
|------|-----|
| BirdSend home (connect / disconnect) | `wp-admin/admin.php?page=bswp-settings` |
| Developer (QA Tools, Support Tools, Activity Log) | `wp-admin/admin.php?page=bswp-settings&action=developer` |
| Forms | `wp-admin/admin.php?page=bswp-settings&action=forms` |

### Requirements for QA

- WordPress admin login with **Administrator** role
- BirdSend account connected on staging (unless testing connect/disconnect)
- For **email tests**: staging must send mail (`wp_mail`), and **Settings → General → Administration Email Address** must be an inbox you can check (not a black hole address)

### Developer page sections

| Section | Purpose |
|---------|---------|
| **Developer Settings** | App/API URL overrides (do not change in QA) |
| **QA Tools** | Connection renewal testing |
| **Support Tools** | Stuck “Sync all forms” fixes |
| **Activity Log** | Read-only event history |

---

## QA tester checklist

These tests use only the WordPress admin UI unless noted.

### 1. Confirm Developer page sections

1. Open **Developer settings** (`action=developer`).
2. Confirm **QA Tools**, **Support Tools**, and **Activity Log** cards are visible.

**Expected:** QA Tools shows connection status and three buttons. Support Tools shows sync status and two buttons.

---

### 2. Connect and disconnect flow

1. Open **BirdSend** home page.
2. If not connected, connect your BirdSend account.
3. Confirm **Connected to BirdSend Account**.
4. Disconnect, then connect again.

**Expected:**

- Reconnect shows **Connected** again.
- QA Tools → **Next scheduled renewal** shows a date/time with timezone.

---

### 3. Run connection renewal (happy path)

1. Open **Developer settings → QA Tools**.
2. Note **Last successful renewal** (may be empty on first run).
3. Click **Run connection renewal now**.

**Expected:**

- Green notice: “Connection renewal completed successfully”.
- **Last successful renewal** updates (with timezone).
- **Warning notice active** stays **No**.
- BirdSend home still shows **Connected**.
- Activity Log: new `token_refresh` row.

---

### 4. Simulate warning notice (UI only — safe, no email)

1. Click **Simulate warning (test UI only)** and confirm the dialog.
2. Open any wp-admin page (e.g. Dashboard).

**Expected:**

- Yellow BirdSend warning on wp-admin (with line break after “may stop working.”).
- BirdSend home **still shows Connected**.
- **No** `renewal_failed_email` row in Activity Log (this is a fake UI test only).

3. Click **Dismiss this notice** on the yellow bar, or use **Clear warning notice** on Developer settings.

---

### 5. Support Tools — sync all forms (happy path)

1. Open **BirdSend → Forms** and note the **Sync all forms** button.
2. Open **Developer settings → Support Tools**.
3. Click **Run sync all forms now**.

**Expected:**

- Green notice: “Forms sync completed successfully”.
- **Sync queue status** → **Idle**.
- Forms page → **Sync all forms** button enabled.

---

### 6. Activity Log

1. On Developer settings, scroll to **Activity Log**.
2. Filter by `token_refresh` → **Filter**.
3. Copy text from a **Details** box.

**Expected:**

- Entries from renewal / sync tests appear.
- **Created At** includes timezone, e.g. `(UTC+8)`.
- Details are full copyable text (not truncated).

**Common event names:**

| Event | Meaning |
|-------|---------|
| `token_refresh` | Renewal succeeded |
| `token_refresh_failed` | Renewal failed |
| `renewal_failed_email` | Failure email sent |
| `renewal_failed_email_failed` | `wp_mail` failed |

---

### 7. Forms page dates

Open **Forms** and confirm **Updated At** / **Last Sync At** include timezone in parentheses.

---

### 8. Warning notice copy review

Use **Simulate warning (test UI only)**, then review the yellow bar.

**Check:**

- [ ] Wording matches approved copy
- [ ] Line break after “may stop working.”
- [ ] “Disconnect My BirdSend Account” is in quotes
- [ ] **Go to BirdSend** link works

---

### 9. QA report template

Include:

- Staging URL
- Plugin version (BirdSend home footer)
- Screenshot of QA Tools or Support Tools status
- Activity Log row (copy **Details**)
- Steps to reproduce

---

## Negative testing — failed renewal & email

**Simulate warning (test UI only) does not send email.** You need a **real** failed renewal.

### Prerequisites

1. **Staging site** with BirdSend connected.
2. **Mail works** on staging — send a test email from another plugin or WordPress “Site Health” if unsure.
3. **Admin email** (`Settings → General → Administration Email Address`) goes to an inbox you can open.
4. **One email per 24 hours** — if you repeat this test same day, ask tech to clear the throttle (see below) or wait 24 hours.

### Option A — Best for QA (tech prepares, QA verifies)

**Tech (once on staging):**

```bash
# 1. Backup credentials
wp option get bswp_client --format=json > ~/bswp_client.backup.json

# 2. Break the client secret (forces renewal to fail)
wp option patch update bswp_client client_secret invalid-for-testing

# 3. Clear email throttle so a new failure can send mail
wp option delete bswp_token_refresh_failed_email_at
```

**QA (no CLI):**

1. Open **Developer settings → QA Tools** → click **Run connection renewal now**.
2. Confirm **red** notice: renewal failed; site should **still show Connected**.
3. Open any wp-admin page → confirm **yellow BirdSend warning**.
4. Check **Administration Email Address** inbox for:

   **Subject:** `[Site Name] BirdSend connection could not be renewed`

5. Open **Activity Log** and confirm rows exist:
   - `token_refresh_failed`
   - `renewal_failed_email` (or `renewal_failed_email_failed` if mail is broken)

**Tech (restore after test):**

```bash
wp option update bswp_client "$(cat ~/bswp_client.backup.json)" --format=json
# Or reconnect via BirdSend UI (disconnect + connect)

wp option delete bswp_token_refresh_failed_at
wp option delete bswp_token_refresh_failed_email_at
```

Then QA runs **Run connection renewal now** again → green success, warning gone.

---

### Option B — Tech-only (CLI cron path)

Same credential break as Option A, then:

```bash
wp cron event run bswp_biweekly_token_refresh
wp option get bswp_token                  # should still exist (not disconnected)
wp option get bswp_token_refresh_failed_at  # should be set
```

Check inbox and Activity Log as in Option A.

---

### Option C — Reactive failure (disconnect + email)

Harder to trigger on demand. Occurs when an API call gets 401 and renewal fails with invalid credentials. Same credential break as Option A, then trigger any API action (e.g. **Support Tools → Run sync all forms now** or sync a single form).

**Expected:**

- Site may show **not connected**
- Activity Log: `token_refresh_failed` with “Connection renewal failed during API use”
- Email subject same as Option A; body mentions disconnection

Restore credentials afterward.

---

### Email troubleshooting

| Problem | What to check |
|---------|----------------|
| No email received | Spam folder; `renewal_failed_email_failed` in Activity Log; host SMTP / WP Mail SMTP |
| No second email same day | 24h throttle — `wp option delete bswp_token_refresh_failed_email_at` |
| Email on staging you want to disable | Add to staging `wp-config.php` or mu-plugin: `add_filter( 'bswp_send_renewal_failed_email', '__return_false' );` |

---

## Technical team checklist

### Prerequisites

- SSH access
- [WP-CLI](https://wp-cli.org/) recommended, **or** [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/)

---

### T1. Cron scheduled after connect

```bash
wp cron event list | grep bswp_biweekly
```

**Expected:** `bswp_biweekly_token_refresh` on `(BS) Every Two Weeks`.

---

### T2. Site-level OAuth client stored

```bash
wp option get bswp_client --format=json
```

**Expected:** `client_id` and `client_secret` present after connect.

---

### T3. Manual biweekly cron (happy path)

```bash
wp cron event run bswp_biweekly_token_refresh
wp option get bswp_token_refreshed_at
```

**Expected:** Timestamp updated; Activity Log `token_refresh`.

---

### T4. Failed renewal + email (see [Negative testing](#negative-testing--failed-renewal--email))

Verify `bswp_token` still exists after scheduled failure; email + Activity Log entries.

---

### T5. Legacy migration (user meta → site option)

```bash
wp option delete bswp_client
```

Reload wp-admin, then:

```bash
wp option get bswp_client
wp cron event list | grep bswp_biweekly
```

---

### T6. Plugin deactivation

```bash
wp cron event list | grep bswp_biweekly
```

**Expected:** Hook removed after deactivation.

---

### T7. Build release zip

From plugin repo root:

```bash
./scripts/build-release.sh
```

**Expected:** Prints full path, e.g. `Created /path/to/birdsend-email-marketing.zip`.

Verify exclusions:

```bash
unzip -l ../birdsend-email-marketing.zip | grep -E "SUPPORT\.md|TESTING\.md|webpack|composer\.json"
```

**Expected:** No matches. `readme.txt` and `vendor/` should be inside the zip.

Optional WP-CLI packaging (requires extra package):

```bash
wp package install wp-cli/dist-archive-command
wp dist-archive . "$(dirname "$PWD")/birdsend-email-marketing.zip" --plugin-dirname=birdsend-email-marketing
```

---

### T8. Release smoke test

1. QA runs [QA checklist](#qa-tester-checklist) sections 1–8.
2. Tech runs **T1**, **T3**, **T7**.
3. Spot-check failed renewal email on staging (**Option A**).

---

## Quick reference — Developer page buttons

### QA Tools

| Button | What it does | Sends email? |
|--------|----------------|--------------|
| **Run connection renewal now** | Real renewal (same as biweekly cron) | Only if renewal **fails** |
| **Simulate warning (test UI only)** | Fake admin warning | **No** |
| **Clear warning notice** | Removes warning flag | No |

### Support Tools

| Button | What it does |
|--------|----------------|
| **Run sync all forms now** | Immediate form sync |
| **Reset sync status** | Clears stuck Waiting/Syncing lock (confirm dialog) |

---

## Related files

| File | Purpose |
|------|---------|
| `libs/helpers.php` | Renewal, email, activity log, date formatting |
| `libs/forms.php` | Form sync + Support Tools backend |
| `libs/cron.php` | Biweekly renewal + minute cron |
| `libs/init.php` | Schedules, migration |
| `includes/admin-developer.php` | QA Tools, Support Tools, Activity Log UI |
| `includes/admin-functions.php` | Actions, notices |
| `scripts/build-release.sh` | Release zip builder |
| `.distignore` | Files excluded from release zip |
