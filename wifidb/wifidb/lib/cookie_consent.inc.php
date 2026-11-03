<?php
/*
cookie_consent.inc.php, whether a visitor is asked before Google Analytics and AdSense use cookies. Site Settings'
"Visitors' consent" says who: every visitor, those where the law requires it (by their country,
lib/visitor_country.inc.php), or nobody. The banner and the choices it saves are lib/js/cookie-consent.js.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class CookieConsent
{
	/** Where consent is required before analytics and ad cookies (ePrivacy rules): the EU and the rest of the EEA, the UK and Switzerland. */
	const COUNTRIES = array(
		'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
		'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
		'IS', 'LI', 'NO',
		'GB', 'CH',
	);
	const MODES = array('ask', 'region', 'off');
	const COOKIE = 'vi_cookie_consent';   # the visitor's choice (lib/js/cookie-consent.js)

	/** "ask" (every visitor), "region" (where it's required) or "off" (nobody); anything else is "ask". */
	public static function Mode($setting)
	{
		return in_array($setting, self::MODES, true) ? $setting : 'ask';
	}

	/**
	 * Is this visitor asked? @param string|null $country their country, null when it isn't known (asked, to be safe)
	 */
	public static function Ask($mode, $country)
	{
		$mode = self::Mode($mode);
		if($mode === 'region') { return $country === null || in_array(strtoupper($country), self::COUNTRIES, true); }
		return $mode === 'ask';
	}
}
