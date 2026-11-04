# Mail settings

WiFiDB sends two kinds of mail, and can use two accounts for them. (Mail is sent with PHPMailer, installed by Composer and pinned to an exact version in `composer.json`.)

| Kind | Sent by | Account |
|---|---|---|
| **Sign-up and password-reset mail** (the validation link) | `wdbmail::mail_validation` | the sign-up account, `signup_smtp_*` |
| **Notifications** (new users, new KMZ, schedule and import messages) | `wdbmail::mail_users` | the main account, `smtp_*` |

Anything the sign-up account doesn't set is the main account's, so with none of it set, sign-up mail goes through the
main account too. Both are signed with the same DKIM key (below).

## The Mail page

**Mail** in the menu (`admin/mail.php`, needs admin mode) sets it all from the site:

- the mail server and account (server, port, encryption, the login, the From name and address, Reply-To), and whether
  members get notification mail; for Gmail or Google Workspace, the login can be a Google account connected on the page
  (OAuth) instead of a password (below);
- a sign-up account, if you want one: anything left empty is the main account's;
- DKIM signing: the domain, the selector and the key. **Make a key** makes a 2048-bit key, keeps it in the database
  (encrypted, as the password is), so every server signs with it, and shows the TXT record to publish and its name;
  you can paste a key you have instead;
- **Check**, which does what `check_mail.php` does (below), and **Send a test message**, from either account, which
  shows what the mail server said, without the login.

The page starts from the settings in `config.inc.php` (below), under the same names. Once a setting is saved on the
page, that value is used, on every server, and the one in `config.inc.php` is ignored. Every change leaves the
administrators a notice. `smtp_timeout`, `smtp_options`, `smtp_debug`, `DKIM_private` (a key file),
`DKIM_passphrase`, `ListUnsubscribe` and `XMailer` are set only in `config.inc.php`.

## Checking from the command line

```bash
php tools/utilites/check_mail.php                          # the settings, the DKIM key, DNS, SPF and DMARC
php tools/utilites/check_mail.php --send you@example.org   # ... and send a signed test message
php tools/utilites/check_mail.php --send you@example.org --signup   # from the sign-up account
```

It checks the settings as the site uses them: `config.inc.php`'s, with what was saved on the Mail page over them. It
prints `[ OK ]`, `[WARN]` or `[FAIL]` for each thing and exits 1 if any failed. In the Proxmox template run it as the
web server's user, so it can read the key: `pct exec 210 -- su -s /bin/bash www-data -c "php /opt/wifidb/tools/utilites/check_mail.php"`.

## Where the settings are in config.inc.php

In `lib/config.inc.php` (copy `lib/config.inc.php.example`). In the Proxmox template that file is built for you; put
your changes in `/srv/wifidb-data/config/web.overrides.php`, which is kept across upgrades (it is only rewritten if you
set `WIFIDB_REGENERATE=1`, so don't, after editing it):

```php
<?php
return array (
  'wifidb_tools' => '/opt/wifidb/tools/',
  'hosturl' => 'https://wifidb.example.org',
  // ... the settings first boot wrote ...
  'smtp_host' => 'smtp.example.org',     // <- add yours inside the array, one line each
  'smtp_port' => 587,
);
```

Changes take effect on the next request and the next run of a cron job. The monitor service (`wifidb-monitord`) reads the settings when it starts, so restart it after a change: `systemctl restart wifidb-monitord`.

## The settings

| Setting | What it is | Example |
|---|---|---|
| `email_validation` | `1`: new members must click the link in an e-mail before they can log in. **Needs working mail**: with it on and SMTP unset, nobody can finish signing up. `0` turns it off. | `1` |
| `wifidb_email_updates` | `1`: send the notification mail (new users, imports and so on) | `1` |
| `smtp_host`, `smtp_port` | The mail server | `smtp.gmail.com`, `587` |
| `smtp_secure` | `tls` (STARTTLS, port 587) or `ssl` (port 465) | `tls` |
| `smtp_auth`, `smtp_authtype` | Log in to the server, and how (`''`: the best way the server offers; `XOAUTH2`: a Google account connected on the Mail page) | `true`, `LOGIN` |
| `smtp_oauth_client_id`, `smtp_oauth_secret` | The OAuth client Google accounts are connected through (the Mail page) | |
| `smtp_user`, `smtp_pass` | The account's login. Gmail and Google Workspace need an **app password** (Google account → Security → App passwords), not the account password. | |
| `smtp_from` | The From address. Gmail only lets you send as the account's own address or one of its aliases. | `wifidb@example.org` |
| `smtp_from_name` | The name shown with the From address, or empty for the address alone (both accounts) | `WiFiDB` |
| `smtp_replyto` | Reply-To address, or empty | |
| `smtp_options` | PHP stream options for the TLS connection. Leave `verify_peer` on; only a mail server with a self-signed certificate needs it off. | |
| `smtp_timeout` | Seconds to wait for the mail server before giving up on a message (both accounts). A sign-up or password reset then says the mail couldn't be sent, instead of the page timing out. | `15` |
| `smtp_debug` | `0` off, `2` print the whole conversation with the server, for finding a problem. **The log includes the encoded login** (base64 is not encryption), so do not post it anywhere | `0` |
| `signup_smtp_*` | The same set of settings (`host`, `port`, `user`, `pass`, `from`, `replyto`, `secure`, `auth`, `authtype`, `options`, `debug`) for the sign-up account. Leave them all out to use the main account. | |
| `DKIM_domain` | The domain that signs: the part after `@` in the From address, or a parent of it | `example.org` |
| `DKIM_selector` | A name for this key, so you can have several (and change keys without a gap) | `wifidb` |
| `DKIM_private` | The **full path** of the private key file (below). A key made or pasted on the Mail page is used instead. | `/srv/wifidb-data/config/dkim/wifidb.pem` |
| `DKIM_passphrase` | The key's passphrase, or empty (a key made as below has none) | |
| `DKIM_identity` | The signing identity, normally the From address | `wifidb@example.org` |
| `DKIM_copyHeaderFields` | `true` is fine | `true` |
| `ListUnsubscribe` | The `List-Unsubscribe` header: where to send an unsubscribe request, and the preferences page | `<postmaster@example.org>, <https://wifidb.example.org/cp/?func=pref>` |
| `XMailer` | What goes in the `X-Mailer` header | |

## Gmail or Google Workspace

The server is `smtp.gmail.com`, port 587, STARTTLS. Google no longer takes the account's own password from a mail
program (for Workspace accounts, since May 2025), so sign in one of two ways:

- **A Google account (OAuth)**, the way Google recommends: no password is kept, only a token Google can take back. Set
  up below.
- **An app password** (Google account → Security → App passwords; it needs 2-Step Verification, and Advanced
  Protection turns app passwords off) as the password, signing in with LOGIN:

  ```php
  'smtp_host' => 'smtp.gmail.com', 'smtp_port' => 587, 'smtp_secure' => 'tls',
  'smtp_auth' => true, 'smtp_authtype' => 'LOGIN',
  'smtp_user' => 'wifidb@example.org', 'smtp_pass' => '<an app password>', 'smtp_from' => 'wifidb@example.org',
  ```

On Google Workspace, the **SMTP relay service** (`smtp-relay.gmail.com`, set up in the Admin console under Gmail →
Routing) can also take the site's mail by the servers' addresses, with no sign-in at all: on the Mail page, turn
*Sign in to the mail server* off. It needs every node's public address in the relay's list.

### Signing in with a Google account (OAuth)

1. **Make an OAuth client** in a Google Cloud project (console.cloud.google.com), one for mail rather than the one
   *Sign-in Providers* uses:
   - *APIs & Services → OAuth consent screen*: for a Workspace account make it **Internal** (no review, and the
     connection doesn't expire). For a gmail.com account it has to be External: add the scope
     `https://mail.google.com/` and **publish it** (*In production*). Left in *Testing*, Google ends the connection
     after 7 days. Google doesn't review an app only you use, but shows a warning while you connect it: *Advanced →
     Go to (the app)*.
   - *Credentials → Create credentials → OAuth client ID*, type **Web application**, with the **Authorized redirect URI**
     the Mail page shows (`https://<your site>/admin/mail.php?oauth=google`).
2. On the Mail page, save the client ID and secret, then **Connect with Google** for the mail account and sign in as
   the account mail is sent from. The page saves its address as the username (and the From address, if that was
   empty) and switches *How to sign in* to the Google account. The sign-up account can have its own, connected the
   same way; without one it signs in as the mail account does.
3. **Check** asks Google for an access token, and **Send a test message** sends one.

Only the refresh token is kept (encrypted, with the site settings), so every node signs in with it. Google takes it back
if the account's password changes, if it goes unused for six months, or if the site's access is removed from the
account (myaccount.google.com → Security → Third-party apps). After that, mail from that account fails, and the log and
the check say to connect the account again. **Disconnect** removes the token here and tells Google.

For tests, `mail_oauth_endpoints` in `config.inc.php` points Google's addresses (`authorize`, `token`, `userinfo`,
`revoke`) somewhere else.

Google signs mail it relays with its own DKIM key for your domain (the `google._domainkey` record Workspace has you
publish), so mail through Gmail **passes DKIM and DMARC even if WiFiDB's own signature is not set up**. WiFiDB's
signature is a second one on top of it. If you do set it up, publish its record too (below): a signature whose
record is missing counts as a failed signature, though receivers ignore it when another valid one is present.

## DKIM: signing mail yourself

You need it if you send through your own mail server or a provider that does not sign for you.

1. **Make a key.**
   - On the Mail page: set the signing domain and save, then **Make a key**. It shows the record to publish and its
     name, and the key is kept in the database for every server, so step 3 is done too (set the identity there if
     you want one). Until the record is published, receivers can't check the signature, so publish it soon after.
     To change keys later, make the new one with a new selector: the old record can stay up for mail already sent.
   - Proxmox template, as a file: `pct exec 210 -- wifidb-dkim generate` (the selector defaults to `wifidb`; give another as the
     second word). It makes `/srv/wifidb-data/config/dkim/wifidb.pem`, readable by root and the web server only, and
     prints the record to publish. `wifidb-dkim show` prints it again. It will not replace an existing key.
   - Anywhere else:
     ```bash
     openssl genrsa -out /path/to/wifidb.pem 2048
     chown root:www-data /path/to/wifidb.pem && chmod 0640 /path/to/wifidb.pem   # the web server's and the daemons' user must read it
     echo "v=DKIM1; k=rsa; p=$(openssl rsa -in /path/to/wifidb.pem -pubout -outform DER 2>/dev/null | base64 -w0)"
     ```
2. **Publish the record** as a TXT record named `<selector>._domainkey.<domain>`, with the text it printed
   (`v=DKIM1; k=rsa; p=...`). Most DNS hosts take it as one line; a zone file needs it cut into pieces of at most 255
   characters (`wifidb-dkim` prints that form too). DNS can take a while to spread.
3. **Set** `DKIM_domain`, `DKIM_selector`, `DKIM_private` (the full path) and `DKIM_identity` as in the table (for a
   key file; the Mail page has all but the file).
4. **Check:** **Check** on the Mail page, or `php tools/utilites/check_mail.php`. It reads the key, looks up the record in DNS and compares the two. Then
   `--send you@example.org` and look at the received message's headers: `Authentication-Results` should say
   `dkim=pass` for your domain (and `spf=pass`, `dmarc=pass`).

### Things that go wrong (the check finds all of these)

- **A wrong key path sends the mail unsigned, with no error.** PHPMailer only signs when the file exists. A typo, or a key
  the web server's user cannot read, is invisible until someone looks at the headers.
- **A relative path** (`private.pem`) means a different folder for the web server and for the daemons. Use a full path.
- **The record is missing, or is for another key.** The signature then fails at the receiver. After making a new key the
  old record must be replaced. To change keys without a gap, make a new selector, publish it, switch `DKIM_selector`,
  and remove the old record a few days later.
- **The identity or the From address is outside the signing domain.** `DKIM_identity` must be under `DKIM_domain`
  (otherwise the signature is invalid), and a From address under another domain, as with a plain `@gmail.com` account,
  is signed but does not count toward DMARC for that mail.
- **A 1024-bit key** works but is weak; use 2048. A record ending `t=y` marks the key as "testing": remove that once
  mail verifies.

## SPF and DMARC

These are DNS records for the domain mail is sent *from*, separate from DKIM:

- **SPF** lists the servers allowed to send for the domain, for example `v=spf1 include:_spf.google.com ~all` for
  Google, or your own server's address (`ip4:203.0.113.5`).
- **DMARC** (a TXT record at `_dmarc.<domain>`) tells receivers what to do when SPF and DKIM do not line up; start with
  `v=DMARC1; p=none; rua=mailto:you@example.org` and read the reports before tightening it.

`check_mail.php` tells you if either is missing.
