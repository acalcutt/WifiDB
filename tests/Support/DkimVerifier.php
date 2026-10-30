<?php
/**
 * A small DKIM verifier (RFC 6376), written from the spec and independent of PHPMailer, so a test can tell whether a
 * message that PHPMailer signed really verifies. Supports rsa-sha256 with simple or relaxed canonicalization.
 */
final class DkimVerifier
{
    /** @return array [bool ok, string reason] */
    public static function Verify(string $raw, string $publicKeyPem): array
    {
        $raw = str_replace(["\r\n", "\n"], ["\n", "\r\n"], $raw);   // CRLF throughout
        $split = strpos($raw, "\r\n\r\n");
        if ($split === false) { return [false, 'no body separator']; }
        $headerBlock = substr($raw, 0, $split);
        $body = substr($raw, $split + 4);

        // headers, each with its folded continuation lines kept (needed for "simple")
        $headers = [];
        foreach (explode("\r\n", $headerBlock) as $line) {
            if ($line !== '' && ($line[0] === ' ' || $line[0] === "\t") && $headers) { $headers[count($headers) - 1] .= "\r\n" . $line; }
            else { $headers[] = $line; }
        }

        $signature = null; $signatureIndex = null;
        foreach ($headers as $i => $h) {
            if (stripos($h, 'dkim-signature:') === 0) { $signature = $h; $signatureIndex = $i; break; }
        }
        if ($signature === null) { return [false, 'no DKIM-Signature header']; }

        $tags = [];
        foreach (explode(';', preg_replace('/\s+/', ' ', substr($signature, strlen('dkim-signature:')))) as $part) {
            if (strpos($part, '=') === false) { continue; }
            [$k, $v] = explode('=', $part, 2);
            $tags[trim($k)] = trim($v);
        }
        foreach (['v', 'a', 'd', 's', 'h', 'bh', 'b'] as $required) {
            if (!isset($tags[$required])) { return [false, "missing tag $required"]; }
        }
        if ($tags['v'] !== '1' || $tags['a'] !== 'rsa-sha256') { return [false, 'unsupported version or algorithm ' . $tags['a']]; }
        [$headerCanon, $bodyCanon] = array_pad(explode('/', $tags['c'] ?? 'simple/simple'), 2, 'simple');

        // body hash
        $canonBody = self::CanonBody($body, $bodyCanon);
        if (isset($tags['l'])) { $canonBody = substr($canonBody, 0, (int)$tags['l']); }
        if (base64_encode(hash('sha256', $canonBody, true)) !== preg_replace('/\s+/', '', $tags['bh'])) { return [false, 'body hash does not match']; }

        // the signed headers, last occurrence first for repeated names
        $used = [];
        $data = '';
        foreach (explode(':', $tags['h']) as $name) {
            $name = strtolower(trim($name));
            for ($i = count($headers) - 1; $i >= 0; $i--) {
                if ($i === $signatureIndex || isset($used[$i])) { continue; }
                if (stripos($headers[$i], $name . ':') === 0) { $used[$i] = true; $data .= self::CanonHeader($headers[$i], $headerCanon) . "\r\n"; break; }
            }
        }
        // the signature header itself, with an empty b=
        $emptied = preg_replace('/(\bb=)[^;]*/', '$1', $signature, 1);
        $data .= self::CanonHeader($emptied, $headerCanon);

        $ok = openssl_verify($data, base64_decode(preg_replace('/\s+/', '', $tags['b'])), $publicKeyPem, OPENSSL_ALGO_SHA256);
        return $ok === 1 ? [true, 'ok'] : [false, 'signature does not verify'];
    }

    public static function Tags(string $raw): array
    {
        $unfolded = preg_replace('/\r?\n[ \t]+/', ' ', $raw);
        if (!preg_match('/^DKIM-Signature:\s*(.*)$/mi', $unfolded, $m)) { return []; }
        $tags = [];
        foreach (explode(';', $m[1]) as $part) {
            if (strpos($part, '=') !== false) { [$k, $v] = explode('=', $part, 2); $tags[trim($k)] = trim($v); }
        }
        return $tags;
    }

    private static function CanonBody(string $body, string $mode): string
    {
        if ($mode === 'relaxed') {
            $lines = explode("\r\n", $body);
            foreach ($lines as &$line) { $line = rtrim(preg_replace('/[ \t]+/', ' ', $line), ' '); }
            unset($line);
            $body = rtrim(implode("\r\n", $lines), "\r\n");
            return $body === '' ? '' : $body . "\r\n";
        }
        $body = rtrim($body, "\r\n");
        return $body . "\r\n";
    }

    private static function CanonHeader(string $header, string $mode): string
    {
        if ($mode === 'relaxed') {
            [$name, $value] = array_pad(explode(':', $header, 2), 2, '');
            return strtolower(trim($name)) . ':' . trim(preg_replace('/[ \t]+/', ' ', preg_replace('/\r\n[ \t]/', ' ', $value)));
        }
        return $header;
    }
}
