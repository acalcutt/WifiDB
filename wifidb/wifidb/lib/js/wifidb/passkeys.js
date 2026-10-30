/* Passkeys (WebAuthn) in the browser: signIn() proves a passkey for the second step of signing in, register() adds one.
 * The server (lib/two_factor.inc.php) makes the challenge and checks the answer; this only passes them to the browser's
 * navigator.credentials and back, as base64url text. Both return a promise of the server's JSON answer.
 */
(function (root) {
	'use strict';

	function toBuffer(text) {
		var s = String(text).replace(/-/g, '+').replace(/_/g, '/');
		while (s.length % 4) { s += '='; }
		var binary = atob(s);
		var bytes = new Uint8Array(binary.length);
		for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
		return bytes.buffer;
	}

	function toText(buffer) {
		var bytes = new Uint8Array(buffer);
		var binary = '';
		for (var i = 0; i < bytes.length; i++) { binary += String.fromCharCode(bytes[i]); }
		return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
	}

	function postJson(url, body) {
		return fetch(url, {
			method: 'POST', credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(body || {})
		}).then(function (response) { return response.json(); });
	}

	function supported() {
		return !!(root.navigator && root.navigator.credentials && root.PublicKeyCredential);
	}

	/** extra: fields to send along with the answer (e.g. remember); userVerification: 'required' to demand a PIN or fingerprint */
	function signIn(beginUrl, finishUrl, extra, userVerification) {
		return postJson(beginUrl, {}).then(function (options) {
			if (!options.ok) { return options; }
			return root.navigator.credentials.get({ publicKey: {
				challenge: toBuffer(options.challenge),
				rpId: options.rp_id,
				allowCredentials: (options.allow || []).map(function (id) { return { type: 'public-key', id: toBuffer(id) }; }),
				userVerification: userVerification || 'discouraged',
				timeout: 60000
			} }).then(function (credential) {
				var answer = Object.assign({}, extra || {}, {
					id: credential.id,
					clientDataJSON: toText(credential.response.clientDataJSON),
					authenticatorData: toText(credential.response.authenticatorData),
					signature: toText(credential.response.signature)
				});
				return postJson(finishUrl, answer);
			});
		});
	}

	function register(beginUrl, finishUrl, name) {
		return postJson(beginUrl, {}).then(function (options) {
			if (!options.ok) { return options; }
			return root.navigator.credentials.create({ publicKey: {
				challenge: toBuffer(options.challenge),
				rp: { id: options.rp_id, name: options.rp_name || 'WifiDB' },
				user: { id: toBuffer(options.user.id), name: options.user.name, displayName: options.user.name },
				pubKeyCredParams: [{ type: 'public-key', alg: -7 }, { type: 'public-key', alg: -257 }],
				excludeCredentials: (options.exclude || []).map(function (id) { return { type: 'public-key', id: toBuffer(id) }; }),
				authenticatorSelection: { residentKey: 'preferred', userVerification: 'preferred' },
				attestation: 'none',
				timeout: 60000
			} }).then(function (credential) {
				return postJson(finishUrl, {
					name: name || '',
					clientDataJSON: toText(credential.response.clientDataJSON),
					attestationObject: toText(credential.response.attestationObject)
				});
			});
		});
	}

	var api = { toBuffer: toBuffer, toText: toText, supported: supported, signIn: signIn, register: register };
	if (typeof module !== 'undefined' && module.exports) { module.exports = api; }
	if (root) { root.WifiDBPasskeys = api; }
})(typeof window !== 'undefined' ? window : this);
