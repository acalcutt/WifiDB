# Sourced by test-template.sh after test-two-factor.inc.sh: passkeys in a real browser against the booted container.
# Chromium (Playwright image) with a virtual authenticator talks to https://wifidb.test, a Caddy proxy with a throwaway
# certificate in front of the container, because passkeys only work over https on the site's own name.

PLAYWRIGHT_VERSION=1.49.1
tls=wifidb-test-tls-$id

log "passkeys in a real browser"
# tls internal: Caddy signs its own certificate (it would otherwise ask Let's Encrypt, which refuses a .test name)
docker run -d --name "$tls" --network "$net" --network-alias wifidb.test caddy:2 sh -c \
    'printf "https://wifidb.test {\n\ttls internal\n\treverse_proxy %s:80\n}\n" "$0" > /tmp/Caddyfile && caddy run --config /tmp/Caddyfile --adapter caddyfile' "$ct1" >/dev/null 2>&1
sleep 5
trap 'docker rm -f "$tls" >/dev/null 2>&1; cleanup' EXIT

pkpass="a different long passphrase"
pkhash=$(docker exec -e P="$pkpass" "$ct1" php -r 'echo password_hash(getenv("P"), PASSWORD_BCRYPT);')
docker exec "$db" mariadb -uwifidb -ptestpw wifi -e "INSERT INTO user_info (username, password, uid, email, validated) VALUES ('pkuser', '$pkhash', 'pk1', 'pk@example.test', 0)"

if docker run --rm -i --network "$net" -e USER_NAME=pkuser -e PASS="$pkpass" "mcr.microsoft.com/playwright:v$PLAYWRIGHT_VERSION-noble" \
    sh -c "mkdir /t && cd /t && cat > test.js && npm install --silent --no-audit --no-fund playwright@$PLAYWRIGHT_VERSION >/dev/null 2>&1 && node test.js" \
    < "$(dirname "$(readlink -f "$0")")/test-passkey.js" 2>&1 | tee /tmp/passkey-out.$id | grep -E '^  (ok|FAIL)|^       at'; then
    :
fi
if grep -q '^  FAIL' /tmp/passkey-out.$id || ! grep -q '^  ok' /tmp/passkey-out.$id; then
    bad "the passkey browser test did not pass"; tail -15 /tmp/passkey-out.$id | sed 's/^/       /'
fi
rm -f /tmp/passkey-out.$id
