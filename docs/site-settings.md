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
| Google Analytics measurement ID | `analytics_id` | `G-…` (or paste Google's whole tag). Empty: no analytics. See below. |
| AdSense publisher ID, ad units | `adsense_client`, `adsense_slot`, `adsense_slot_menu` | `ca-pub-…` and the ad units' numbers (or paste their code): the ad at the foot of each page, and the one under the mobile theme's menu. Empty: no ads (or none there). |
| Visitors' consent to analytics and ad cookies | `analytics_consent` (where it's required) | Who sees the cookie banner. See below. |

Each setting starts with the value in `config.inc.php`, under the same name. Once it is saved on the page, the saved
value is used, and the page says where each value comes from. The values are kept in the `site_settings` table
(migration 0004, applied by `tools/utilites/migrate.php`). Every change leaves the administrators a notice saying who changed
what.

## Google Analytics, AdSense and the cookie banner

Nothing of Google's is loaded until its ID is set on Site Settings. With either set, every page starts with Google's
consent mode saying no to analytics and ad cookies, and **Visitors' consent** decides who is asked to allow them:

- **Ask where it's required** (the default): visitors in the EU and the rest of the European Economic Area, the UK and
  Switzerland see the cookie banner; everyone else gets analytics and ads without being asked. Anyone whose country
  can't be told is asked too.
- **Ask every visitor**: everyone sees the banner.
- **Don't ask**: nobody does. Only allowed where consent isn't required.

A choice is kept in the visitor's browser for a year; **Cookie choices** in the footer opens the banner again. Until a
visitor allows them, Google is told not to use cookies for analytics or personalized ads. For ads in the EEA, UK and
Switzerland, Google also asks publishers for a consent message from a Google-certified provider; AdSense's own
*Privacy & messaging* can show one.

**The visitor's country** is looked up on the server (`lib/visitor_country.inc.php`), in a copy of DB-IP's free country
database (IP to Country Lite, [CC BY 4.0](https://db-ip.com), credited in the footer while it's in use); nothing about the
visitor is sent anywhere. A CDN in front that gives the country (Cloudflare's `CF-IPCountry`) is used instead.
`tools/cron/update_geoip` (daily) fetches a new copy once the one there is a month old, and puts it in place only after
it opens and knows a country, so if DB-IP is down, moves or goes away, the copy there keeps working. In `config.inc.php`,
`geoip_country_db` is where the copy is (the Proxmox template: `/srv/wifidb-data/geoip/`, starting from a copy the
template was built with) and `geoip_country_source` where it's fetched from: DB-IP's unless set, or any other MMDB
country database (MaxMind's GeoLite2, IPinfo's), with `%s` for the month. Without a copy, everyone is asked.

Signing in with Google, GitHub, Discord or Facebook is set up on its own page, **Sign-in Providers**
(`admin/logins.php`), kept in the same table: see [linked-logins.md](linked-logins.md).

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
