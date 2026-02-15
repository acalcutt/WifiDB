
# WifiDB API v2 — Endpoint Reference (detailed)

This document expands the quick summary: it lists parameters, expected methods, and example requests for the three v2 endpoints in this folder.

Notes common to v2 endpoints:
- Each endpoint includes `include('../../lib/init.inc.php')` and sets `SWITCH_EXTRAS = "apiv2"`.
- Programmatic clients should expect binary file downloads from export endpoints (KMZ) unless `xml=1` is provided. Import/schedule endpoints return structured output via `$dbcore->Output()`.

1) `export.php` (GET)
- Purpose: produce KML/KMZ exports and KML network-link manifests for APs, lists, regions and users.
- Required parameter: `func` — selects which export operation to run. The most-used `func` values and their parameters are:
  - `exp_latest`
    - Params: `labeled=1|0` (optional), `xml=1|0` (optional), `download` (override filename)
    - Output: newest AP as KML/KMZ (binary) or KML text with `xml=1`.

  - `exp_list`
    - Params: `id` (required — file/list id), `labeled`, `all`, `new_icons`, `xml`
    - Output: KML/KMZ for a single list (file) id.

  - `exp_ap`
    - Params: `id` (required — AP_ID), `labeled`, `new_icons`, `xml`
    - Output: KML/KMZ focused on a single AP and related signal history.

  - `exp_user_all`
    - Params: `user` (required — username), `labeled`, `all`, `new_icons`, `xml`
    - Output: KML/KMZ network links or folder containing the user's lists with GPS results.

  - Region / bulk funcs: `exp_daily`, `exp_all`, `exp_country` (requires `country_code`), `exp_admin1` (requires `country_code` + `admin1_id`), `exp_admin2` (requires `country_code` + `admin1_id` + `admin2_id`), `exp_all_netlink`, `exp_latest_netlink`, `exp_user_netlink` (requires `user`), `exp_ap_netlink` (requires `id`).

- Common query parameters:
  - `id` — numeric id for APs/lists
  - `user` — username
  - `labeled=1|0` — include labels in KML
  - `new_icons=1|0` — use newer icon set
  - `all=1|0` — include historical (old) APs as well
  - `xml=1|0` — return KML/XML text instead of KMZ binary
  - `debug=1` — enable extra debug output (where implemented)
  - `download` — override response filename (used in Content-Disposition)

Example requests:
GET /api/v2/export.php?func=exp_latest&labeled=1
GET /api/v2/export.php?func=exp_list&id=123&xml=1

Implementation notes:
- By default the endpoint packages KML into a KMZ binary and sets `Content-Disposition: attachment`.

2) `import.php` (POST multipart/form-data)
- Purpose: accept file uploads for import (CSV, Wigle CSV, GPX, etc.).
- Required: multipart `file` field containing the upload. If missing, the handler returns an error via `$dbcore->Output()`.
- Optional form fields:
  - `title` — human-friendly title (default: current server date/time)
  - `notes` — optional notes to attach to the import
  - `otherusers` — optional CSV list of other usernames to attribute
  - `func=check_hash` with `hash` — call the CheckHash flow if you only want to test whether a file/hash exists

- Upload behavior:
  - Uploaded files are copied to the server side `import/up/` directory with a generated name (`APIupload_<rand>_<origname>`).
  - The import code attempts to detect Wigle CSV format (sets `ext='wiglecsv'`) by peeking at the first lines.
  - `ImportVS1()` is invoked to process the uploaded file; results are returned by `$dbcore->Output()`.

Example (curl):
curl -F "file=@myrun.csv" -F "title=My Run" https://yourserver/api/v2/import.php

3) `schedule.php` (GET)
- Purpose: administrative/status queries for scheduled imports and daemon status.
- Required parameter: `func` — one of `waiting`, `importing`, `finished`, `bad`, `daemonstatuses`.
- Optional date filters: `StartDate` and `EndDate` (if omitted, full date range is used).
- Returns: structured output via `$dbcore->Output()` (usually JSON-like arrays and status objects).

Example:
GET /api/v2/schedule.php?func=waiting

---
If you want, I can add per-`func` schema examples for `export.php` (showing exact JSON or KML snippets) or produce curl examples that cover authorization and api-key usage in contexts where credentials are required.

Client usage examples (real code references)

- **WiFiDBClient (C#)** — the `WDBAPI` library shows common calls:
  - `schedule.php` is called via POST form (`username`, `apikey`, `func=waiting|finished|importing|bad|daemonstatuses`).
  - `import.php` is called with `UploadFile()` and query/form fields `username`, `apikey`, `title`, `notes`, `hash` (MD5) so the server can detect duplicate uploads.

- **Vistumbler / WifiDB_Uploader (AutoIt)** — concrete patterns used in the AutoIt code:
  - File import: POST multipart to `import.php` with `file` (binary) plus `apikey`, `username`, `otherusers`, `title`, `notes`.
  - Live session: POST `username`, `apikey`, `title`, `notes` to `live.php` to create a session (returns `SessionID`); close session with `live.php?completed=1&username=...&apikey=...&SessionID=...`.

Short curl examples (v2)
 - Schedule/status: `curl -X POST -d "username=USER&apikey=KEY&func=waiting" https://yourserver/api/v2/schedule.php`
 - Import/upload: `curl -F "file=@myrun.csv" -F "username=USER" -F "apikey=KEY" -F "title=My Run" https://yourserver/api/v2/import.php`

These examples mirror the real client implementations in this repository (see `WiFiDBClient/WDBAPI` and `Vistumbler/WifiDB_Uploader`). Use them as a basis for language-specific client libraries.

Authentication / pre-logon (v2)

- API key validation: v2 endpoints read `username` and `apikey` from the request. When the server has `EnableAPIKey` enabled the API will call `ValidateAPIKey()` and reject requests with an invalid or missing API key. Include `username` and `apikey` on all protected requests.

- Import pre-check (optional but recommended): before uploading a file, clients can call the `check_hash` function to avoid duplicate uploads:
  - `POST /api/v2/import.php?func=check_hash` with form field `hash=<md5-of-file>` — the server returns whether the hash is `waiting`, `importing`, `finished` or `unknown`.

- Live-session flow (for live AP uploads):
  - Start a session: `POST /api/live.php` with multipart/form fields `username`, `apikey`, `title`, `notes` — response contains `SessionID` (v2 JSON output). Save this `SessionID` for subsequent live uploads.
  - Send live APs: include `SessionID` in each `live.php` request (`SessionID=<value>`). The server will validate the session and associate incoming live data with the open session.
  - End session: call `GET /api/live.php?completed=1&username=...&apikey=...&SessionID=...` to mark the session closed.

Notes:
 - Some user accounts have `import_require_login` enabled; those accounts require a valid username+apikey pair for file imports. The API will return an error message if the key is not valid for that user (see `APILoginCheck()` in server code).
 - `import.php` (v2) still computes the uploaded file's MD5 server-side and performs duplicate checks independently; passing `hash` on the client side is useful for a pre-check using `func=check_hash` to avoid unnecessary uploads.
