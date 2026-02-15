
# WifiDB Top-level API — Detailed Reference

This file expands the short summary into concrete parameter lists and examples for the most commonly used top-level endpoints. For the modernized v2 endpoints see `api/v2/README.md` (preferred for programmatic clients).

1) `export.php` (legacy)
- Purpose: KML/KMZ generation similar to `api/v2/export.php`. Uses `func` to select operation (many of the same `func` values exist).
- Typical query params: `func`, `id`, `user`, `labeled`, `new_icons`, `all`, `xml`, `download`, `debug`.

2) `import.php` (legacy)
- Purpose: accept file uploads for import (older flow). Prefer `/api/v2/import.php` for new imports.
- Typical fields: multipart `file`, optional `title`, `notes`, `otherusers`.

3) `search.php` (GET)
- Purpose: AP search endpoint used by clients and UI.
- Accepted query parameters (all optional, but at least one should be provided):
	- `ssid` — SSID string (no percent wildcard `\%` alone)
	- `mac` — MAC address (hex string)
	- `radio` — radio type string
	- `chan` — numeric channel
	- `auth` — authentication string (e.g., "WPA2")
	- `encry` — encryption string (e.g., "AES")
- Notes: The endpoint rejects requests using `%` as a wildcard for all search fields simultaneously.

Example:
GET /api/search.php?ssid=CoffeeShop&chan=6

4) `latest.php` (GET)
- Purpose: return the newest AP as KML/KMZ.
- Parameters:
	- `labeled=1` — include labels
	- `xml=1` — return KML text instead of KMZ
	- `download` — override filename

Example:
GET /api/latest.php?labeled=1

5) `live.php` (GET or POST)
- Purpose: ingest live AP observations (used by hardware/embedded clients or mobile devices in "live" mode).
- Key params accepted (names are case-sensitive in many clients):
	- `SSID`, `Mac`, `Rad` (radio), `SecType`, `Chn` (channel), `Auth`, `Encry` — AP details
	- `Lat`, `Long`, `Sats`, `HDP`, `ALT`, `GEO`, `KMH`, `MPH`, `Track`, `Date`, `Time` — GPS and telemetry
	- `username` — reporting username (optional)
	- `SessionID` — session identifier (recommended)
- Behavior: validated fields are packaged and passed to `InsertLiveAP()`; the endpoint returns status via `$dbcore->Output()`.

6) `geojson.php` (GET)
- Purpose: return GeoJSON for AP search/exports. Common params mirror `export`/`search` (e.g., `func`, `id`, bounding params). Use when clients prefer GeoJSON over KML.

7) `locate.php` (GET)
- Purpose: locate/nearest AP queries. Typical parameters include latitude/longitude and radius; exact parameter names vary by implementation — inspect the specific file if you need precise names.

8) `gpx.php` (GET)
- Purpose: export runs in GPX format. Parameters typically mirror `export.php` (`func`, `id`, `download`, `xml`), returning GPX output (XML) or attachment.

9) `v2/` directory
- Prefer these endpoints for programmatic clients (see `api/v2/README.md`). They use `SWITCH_EXTRAS = apiv2` and are implemented to be more consistent for API consumers.

Tips for programmatic use:
- Prefer `api/v2` endpoints when available; they are written to be consumed by clients and to return structured output.
- For binary responses (KMZ/GPX), set `xml=1` when you need text/XML directly; otherwise handle the attachment as binary.
- For uploads, use multipart form POST with the `file` field set.
- If you run into SQL errors mentioning `LIMIT`, the server may be configured for SQL Server — some queries use `LIMIT` vs `TOP` depending on `dbcore->sql->service`.

Authentication / pre-logon (v2)

- `username` + `apikey`: v2 API handlers will read `username` and `apikey` from the request and, if API keys are enabled on the server, validate them. Include them on protected requests (imports, live sessions, schedule queries) to avoid authentication errors.

- File import pre-check: call `import.php?func=check_hash` with `hash=<md5>` to test whether a file is already known to the server. This avoids re-uploading large files unnecessarily.

- Live sessions: for continuous/live uploads, request a `SessionID` by POSTing `username`, `apikey`, `title`, `notes` to `live.php` (v2). Use the returned `SessionID` on subsequent `live.php` calls and close the session with `live.php?completed=1&username=...&apikey=...&SessionID=...`.

 - Some user accounts set `import_require_login` — those users must provide a valid API key for imports. If you see an error like "Your account requires an api key to import", verify the credentials before retrying the upload.

If you'd like, I can:
- generate curl examples for each endpoint with sample payloads
- produce JSON schema samples (where endpoints return JSON via `$dbcore->Output()`)
- or add per-`func` details for `api/v2/export.php` (explicit required params and sample KML snippets)

Client usage examples (real world)
 - **C# (WiFiDBClient/WDBAPI)**: the Windows client uses `System.Net.WebClient` to call administrative endpoints and upload files. Examples:
	 - Schedule/status (POST form):
		 - POST to `schedule.php` with form fields `username`, `apikey`, `func=waiting|finished|importing|bad|daemonstatuses`.
	 - Import/upload (multipart): compute MD5 of the file, then call `import.php` using `UploadFile()` while adding query-string/form values `username`, `apikey`, `title`, `notes`, `hash`.

 - **AutoIt (Vistumbler, WifiDB_Uploader)**: the AutoIt clients build multipart/form-data bodies and POST directly to the API endpoints.
	 - File upload (import.php): form fields include `file` (binary), and optional `apikey`, `username`, `otherusers`, `title`, `notes`.
	 - Live session start (live.php): POST `username`, `apikey`, `title`, `notes` to `live.php` to obtain a `SessionID` (v2 API); stop by calling `live.php?completed=1&username=...&apikey=...&SessionID=...`.

Curl examples (quick)
 - Check scheduled imports:
	 - `curl -X POST -d "username=USER&apikey=KEY&func=waiting" https://yourserver/api/v2/schedule.php`
 - Upload a file (import):
	 - `curl -F "file=@myrun.csv" -F "username=USER" -F "apikey=KEY" -F "title=My Run" https://yourserver/api/v2/import.php`

Notes:
 - Prefer `api/v2/*` endpoints where present. The clients shown here use the `output=xml` or JSON-like responses by default and parse XML/JSON returned by `$dbcore->Output()`.
 - When implementing clients, follow the patterns above: use `multipart/form-data` for file uploads, and simple POST fields for state/control endpoints.

