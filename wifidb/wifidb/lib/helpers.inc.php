<?php
// Small standalone helpers used by init.inc.php. No config or database needed, so they can be unit tested.
// The theme name comes from a cookie and ends up in template/compile paths, so only accept an existing folder under themes/.
if(!function_exists('wifidb_resolve_theme')) {
	function wifidb_resolve_theme($requested, $install, $default)
	{
		if(is_string($requested) && preg_match('/^[A-Za-z0-9_-]+$/', $requested) && is_dir($install.'themes/'.$requested))
		{
			return $requested;
		}
		return $default;
	}
}
// Replacement for the FILTER_SANITIZE_STRING filter, which is deprecated in PHP 8.1. Same result: tags stripped, quotes encoded.
if(!function_exists('wifidb_sanitize_string')) {
	function wifidb_sanitize_string($value)
	{
		return str_replace(array("'", '"'), array('&#39;', '&#34;'), strip_tags((string)$value));
	}
	function wifidb_input_string($type, $name)
	{
		$value = filter_input($type, $name, FILTER_UNSAFE_RAW);
		return is_string($value) ? wifidb_sanitize_string($value) : $value;
	}
}
