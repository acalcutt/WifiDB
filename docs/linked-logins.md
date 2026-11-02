# Signing in with Google, GitHub, Discord or Facebook

Members can link an outside account to their WifiDB account and then sign in with it instead of their password. Nothing
is on until an administrator turns a provider on.

## For members

- **Link one:** User Control Panel → Security → **Linked accounts**. Give your password and press **Link a Google
  account** (or GitHub, ...); sign in at Google and you're sent back with it linked. One account from each provider.
- **Sign in with it:** the sign-in page shows **Or sign in with Google** (and the others that are on). If you have
  two-factor sign-in on, it still asks for your code or passkey afterwards.
- **Signed in with one that isn't linked yet:** the page offers to sign in with your username and password and link it
  in one go (with your second factor too, if you have it on), or, when sign-ups are open, to create an account, which
  gets the outside account linked. Either has 10 minutes.
- **Unlink one:** Security → Linked accounts → **Unlink**, with your password.
- Linking and unlinking each leave a notice under Notifications, so you'd see it if it wasn't you.

An outside account only ever signs in to the WifiDB account it was linked to. Nothing is matched by e-mail address: a
Google account with the same address as yours does not get into your account unless you linked it.

## For administrators

Open **Sign-in Providers** in the menu (`admin/logins.php`, needs admin mode). For each provider:

1. Make an OAuth app (a "client") in the provider's console; the page links to it.
2. Give it the **Redirect URI** the page shows, exactly: `https://<your site>/<root>/logins.php?callback=google` (and
   `github`, `discord`, `facebook`).
3. Paste its client ID and client secret into the page, tick **Sign in with ...**, and save.

| Provider | Console | Notes |
|---|---|---|
| Google | console.cloud.google.com → APIs & Services → Credentials → Create credentials → OAuth client ID (Web application) | Asks for `openid email profile`; set up the OAuth consent screen first. |
| GitHub | github.com → Settings → Developer settings → OAuth Apps → New OAuth App | The "Authorization callback URL" is the redirect URI. Asks only for who it is (`read:user`). |
| Discord | discord.com/developers/applications → New Application → OAuth2 | Add the redirect URI under Redirects. Asks only for who it is (`identify`). |
| Facebook | developers.facebook.com/apps → Create App → Facebook Login | Add the redirect URI under Valid OAuth Redirect URIs. Asks for `email public_profile`. |

The secrets are kept with the site settings, encrypted with `two_factor_key` (the page warns if it isn't set). Every
change leaves the administrators a notice. Turning a provider off stops sign-ins with it at once; the links stay, and
work again if it's turned back on.

## How it works

- The sign-in is OAuth 2's authorization code flow on the server, with PKCE for Google. The `state` sent to the provider
  must come back with a matching cookie set in the same browser, and works once, within 10 minutes.
- The sign-ins under way are kept in the database (`linked_login_states`, by the SHA-256 of their token), so with
  several nodes behind a load balancer any node can finish one that another started. They aren't in backups, and a
  restore empties them.
- The links are in `user_linked_logins`: the provider, its id for the person, the member, and the name and e-mail
  address it gave when linked (shown on the Security page).
- A linked sign-in is refused, as a password sign-in is, for an account that's locked or hasn't confirmed its e-mail.
- Admin mode always asks for its own second factor, however the administrator signed in.

Run `tools/utilites/migrate.php` after updating to create the two tables (`0009_linked_logins`).

For tests, `linked_logins_endpoints` in `config.inc.php` points a provider's sign-in, token and user addresses somewhere
else (`proxmox/test-linked-logins.inc.sh` runs a stand-in provider, `proxmox/test-oauth-provider.php`).
