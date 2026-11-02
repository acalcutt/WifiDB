# Site settings and spam protection

## Site Settings (administrators)

**Site Settings** in the menu (`admin/settings.php`) changes a few settings from the site instead of `lib/config.inc.php`.
It needs admin mode (see [two-factor.md](two-factor.md)).

| Setting | Starts from (config.inc.php) | What it does |
|---|---|---|
| Sign-ups are open | `signups_open` (on) | Off: the "Create a user account" link goes away and sign-ups are refused. Existing members are not affected. |
| New accounts confirm their e-mail | `email_validation` | A new member clicks a link in an e-mail before signing in. Needs working mail. |
| Check sign-ups and messages with CleanTalk | `cleantalk_enabled` (off) | See below. |
| CleanTalk access key | `cleantalk_key` | Encrypted with `two_factor_key`, never shown again. |
| Backups: nightly, copies in S3 | `backup_daily`, `backup_s3_*` | See [backups.md](backups.md). |
| Shared files in S3 | `files_s3_*` | For several nodes on one database: uploads go to this bucket so any node can import them. See [proxmox.md](proxmox.md#several-nodes-on-one-database). |

Each setting starts with the value in `config.inc.php`, under the same name. Once it is saved on the page, the saved
value is used, and the page says where each value comes from. The values are kept in the `site_settings` table
(migration 0004, applied by `tools/utilites/migrate.php`). Every change leaves the administrators a notice saying who changed
what.

## CleanTalk

[CleanTalk](https://cleantalk.org) is an optional paid spam-checking service. When it is on, WifiDB asks it about:

- every **sign-up**: the e-mail address, username and IP address;
- **private messages** from members without a track record: no finished import, an account younger than 90 days,
  and below moderator rank.

What CleanTalk calls spam is refused with its reason. Nothing is held for moderation. Uploads are never sent, and
CleanTalk's whole-site firewall is not used (it could block Vistumbler and other API clients).

- **It fails open.** If CleanTalk can't be reached within 5 seconds, returns an error, or the subscription has run out, the
  sign-up or message goes through. The site log says it wasn't checked.
- **The key is checked when saved.** A key CleanTalk refuses saves nothing. If CleanTalk can't be reached, the key is
  saved with a warning.
- **The IP address** is the visitor's: `REMOTE_ADDR`. Behind a proxy, Apache's `mod_remoteip` sets it from
  `X-Forwarded-For` (the Proxmox template does this when `WIFIDB_EXTERNAL_PROXY` is set). Without that, every visitor
  looks like the proxy.
- The sign-up and message forms tell CleanTalk whether the form's script ran and how long the form took to fill in,
  which helps it tell people from bots.
- The privacy policy (`content/privacy_content.html`) says what is sent to CleanTalk. Update it if your site's policy
  differs.

For tests, `cleantalk_server` and `cleantalk_api` in `config.inc.php` point the checks somewhere else.
