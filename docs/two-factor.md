# Two-factor sign-in and notifications

## For members

Open **User Control Panel → Security** (`cp/security.php`).

- **Authenticator app** (Aegis, Google Authenticator, 1Password, ...): scan the QR code or type the key, then enter the six
  digits it shows. **Passkey**: a fingerprint, face, PIN or security key your device already has; it needs a browser that
  supports passkeys and the site served over https.
- Adding your first method turns two-factor sign-in on and shows **ten recovery codes**, once. Each works one time if you
  lose your device; keep them somewhere safe. You can have up to ten authenticator apps and ten passkeys.
- After the password, sign-in asks for a code or a passkey. **Remember this computer for 30 days** skips that on one
  computer; changing your password or your methods ends the remembering.
- **Ask before I change my preferences** (off by default) makes the profile, e-mail and notification preferences ask for
  a code or passkey first, then stay open for 10 minutes.
- Removing a method, making new recovery codes and turning the sign-in question off all ask for your password again, and
  each leaves a notice under Notifications. Removing the last method turns two-factor off.
- Ten wrong codes in ten minutes lock further tries for a while.

## Admin mode (administrators)

Being an Administrator is not enough to use the admin buttons (reset or delete any import, failed and bad files, daemon
PIDs, schedules) or the Files Bad page. Turn on **Admin mode** from the menu first: it asks for an authenticator or
recovery code, or a passkey, **every time**. The code that signed you in or confirmed your preferences does not count, a
code can't be used twice, a passkey must also check the person (PIN or fingerprint), and "remember this computer" does not
apply. It ends after 30 minutes without using an admin page, or with **Leave admin mode**. An administrator with no
authenticator app or passkey is sent to the Security page to add one: without one there is no admin mode.

A member deleting their own imports is an ordinary action and does not need admin mode.

## For the site's owner

- Set `two_factor_key` in `lib/config.inc.php` (make one with `php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'`).
  It encrypts the authenticator secrets in the database; **keep it**: with a different key the saved authenticators stop
  working. Without a key they are stored unencrypted. The Proxmox template makes one at first boot and keeps it on the
  data disk. `two_factor_issuer` is the name an app shows (default `WifiDB`).
- Passkeys are tied to the site's address (`hosturl`), so they need https, and changing the address invalidates them.
- Run `php tools/utilites/migrate.php` after upgrading; it creates the tables. Until then the Security page says it isn't
  available and sign-in works as before. The template does this at every first boot.
- A member locked out with no recovery codes: on the server run `php tools/utilites/reset_two_factor.php USERNAME`. It
  removes their authenticator apps, passkeys and recovery codes, ends all their sessions and leaves them a notice; they
  sign in with the password and set two-factor up again. (On the Proxmox template: `runuser -u www-data -- php
  /opt/wifidb/tools/utilites/reset_two_factor.php USERNAME`.)

## Notifications

Control Panel → Notifications lists what happened: imports starting, finishing or failing (with the reason), new members
and errors for administrators. Settings picks which kinds you want; errors for administrators can't be switched off.
E-mail is separate (Email Preferences), and sign-up validation and password reset always stay e-mail.

## Tests

PHPUnit covers the codes, passkey checks, sessions and pages. `proxmox/test-template.sh` signs in with an authenticator
code end to end, and `proxmox/test-passkey.js` drives Chromium with a virtual authenticator to add a passkey, sign in with
it and use it for the preferences check.
