<?php
/*
 * NS1Parser.inc.php
 * Library for reading NetStumbler NS1 Binary Files (Version 12)
 * Originally for Vistumbler / WifiDB
 */

class NS1Parser {
	
	private $data;
	private $pos = 0;
	private $len = 0;

	public function parse($filePath) {
		if (!file_exists($filePath)) {
			return array('error' => "File not found: $filePath");
		}

		$this->data = file_get_contents($filePath);
		$this->len = strlen($this->data);
		$this->pos = 0;

		$sig = substr($this->data, 0, 4);
		if ($sig === "NetS") {
			return $this->parseBinary();
		} elseif (substr($this->data, 0, 1) === "#" || preg_match('/^# \$Creator:.*$/m', substr($this->data, 0, 100))) {
			return $this->parseText();
		} else {
			return array('error' => "Invalid Signature: $sig"); // Keep old error for compatibility or update
		}
	}

	private function parseBinary() {
		$this->pos = 0; // Reset just in case
		$sig = $this->readString(4);
		if ($sig !== "NetS") {
			return array('error' => "Invalid Signature: $sig");
		}

		$ver = $this->readUInt32();
		if ($ver != 12) {
			// supporting older versions might be possible but Vistumbler focuses on 12
			// return array('error' => "Unsupported Version: $ver");
		}

		$apCount = $this->readUInt32();
		$aps = array();

		for ($i = 0; $i < $apCount; $i++) {
			if ($this->pos >= $this->len) break;
			$aps[] = $this->readAP($ver);
		}

		return array(
			'version' => $ver,
			'aps' => $aps
		);
	}
	
	private function parseText() {
		$lines = explode("\n", $this->data);
		$aps = array();
		$header = array();
		$ver = 12; // Assume 12 for text mapping capability

		// Find Format/Header
		// Typically lines start with #. One line contains column names.
		// "Latitude	Longitude	( SSID ) ..."
		
		foreach($lines as $line) {
			$line = trim($line);
			if (empty($line)) continue;

			if (substr($line, 0, 1) === "#") {
				// Check for header definition
				if (stripos($line, "Latitude") !== false && stripos($line, "SSID") !== false) {
					// Parse Header
					$cleanLine = substr($line, 1); // remove #
					$parts = explode("\t", $cleanLine);
					$header = array_map('trim', $parts);
				}
				continue;
			}

			// Data line
			// Parse using tab delimiter
			// Note: Some text files might reuse the same column structure even without header
			if (empty($header)) {
				// Fallback default header based on Wi-Scan-Format
				// Latitude	Longitude	( SSID )	Type	( BSSID )	Time ...
				// But safer to wait for header or assume standard index
				// Lets assume standard index if no header found by first data line? 
				// Better to require header or map blindly? 
				// We'll map blindly if no header found? No, let's look for standard positions.
			}

			$cols = explode("\t", $line);
			// Simple mapping assuming standard order from Wi-Scan format if header is complex
			// But header analysis is safer. 
			// Standard: Lat, Lon, (SSID), Type, (BSSID), Time, [SNR Sig Noise], #(Name), Flags, ChanBits, Bcn, Data, LastCh
			
			// Index 0: Lat
			// Index 1: Lon
			// Index 2: ( SSID )
			// Index 3: Type
			// Index 4: ( BSSID )
			// Index 5: Time
			// Index 6: [ SNR Sig Noise ]
			// Index 7: # ( Name )
			// Index 8: Flags
			// Index 9: ChanBits
			// Index 10: BcnIntvl
			// Index 11: DataRate
			// Index 12: LastChannel
			
			if (count($cols) < 13) continue; // Skip malformed
			
			$ap = array();
			
			// Parse Lat/Lon (N 40.71)
			$ap['best_lat'] = $this->parseCoord($cols[0]);
			$ap['best_lon'] = $this->parseCoord($cols[1]);
			
			// SSID (remove parens)
			$ap['ssid'] = trim($cols[2], " ()");
			
			// BSSID (remove parens)
			$ap['bssid'] = trim($cols[4], " ()");
			
			// Time
			// "14:30:25 (GMT)" - Only time? Header has date. 
			// If date is missing, we use today? Or file creation time? Or 1970?
			// Ideally we find $DateGMT in header.
			// Let's scan header for DateGMT
			
			// Signal [ 50 100 50 ]
			if (preg_match('/\[\s*(\d+)\s+(\d+)\s+(\d+)\s*\]/', $cols[6], $matches)) {
				$ap['max_snr'] = $matches[1];
				$ap['max_signal'] = $this->normalizeSignal($matches[2]); // Convert % to dBm if needed
				$ap['min_noise'] = $this->normalizeSignal($matches[3]);
			} else {
				$ap['max_signal'] = -100;
			}
			
			// Name
			$ap['name'] = trim($cols[7], " #()");
			
			// Flags
			$ap['flags'] = intval($cols[8]); // This corresponds to basic flags.
			// Text format usually doesn't have the 32-bit APFlags (v12) column?
			// Wi-Scan-Format.md doesn't mention APFlags column for v12 specific...
			// "Flags Field... 4-digit decimal". 
			// If it's v12, maybe it has extra columns?
			// If not, we can't extract diff between WPA3 and WPA2 unless it's in the text file. 
			// Vistumbler 12 might verify if it adds columns.
			// Assuming standard Wi-Scan for now.
			
			$ap['ap_flags'] = 0; // Default
			
			// Channels
			$ap['channels'] = hexdec($cols[9]);
			$ap['beacon_interval'] = intval($cols[10]);
			$ap['data_rate'] = intval($cols[11]);
			$ap['last_channel'] = intval($cols[12]);
			
			// History (One point)
			$dp = array();
			$dp['time'] = $this->parseTime($cols[5]); // Returns Windows FileTime
			$dp['signal'] = $ap['max_signal']; // Use parsed signal
			$dp['noise'] = $ap['min_noise'];
			$dp['loc_source'] = 1; // GPS
			$gps = array();
			$gps['lat'] = $ap['best_lat'];
			$gps['lon'] = $ap['best_lon'];
			$gps['alt'] = 0;
			$dp['gps'] = $gps;
			
			$ap['history'] = array($dp);
			
			$aps[] = $ap;
		}
		
		return array('version' => $ver, 'aps' => $aps);
	}
	
	private function parseCoord($str) {
		// "N 40.7128"
		$parts = explode(" ", trim($str));
		if (count($parts) != 2) return 0.0;
		$val = floatval($parts[1]);
		if (strtoupper($parts[0]) == 'S' || strtoupper($parts[0]) == 'W') {
			$val = -$val;
		}
		return $val;
	}
	
	private function normalizeSignal($val) {
		$v = intval($val);
		if ($v > 0) {
			// Assume Percentage (0-100), convert to dBm
			// Formula reverse of: Sig = 100 - 80 * (-30 - dBm) / 55
			// Sig = 100 - 1.45 * (-30 - dBm)
			// Sig - 100 = -1.45 * (-30 - dBm)
			// (Sig - 100) / -1.45 = -30 - dBm
			// dBm = -30 - ((Sig - 100) / -1.45)
			
			// Using standard WiFiDB constants roughly: Max -30, Diss -85. Diff 55.
			// 80 * (Max - x) / 55
			// Fac = 80/55 = 1.4545
			// dBm = Max + (Sig - 100) * (Range / 80)
			// dBm = -30 + ($v - 100) * (55 / 80);
			return round(-30 + ($v - 100) * (55 / 80));
		}
		return $v; // Already negative dBm
	}
	
	private $fileDate = null;
	private function parseTime($str) {
		// "14:30:25 (GMT)"
		// Need date. Scan header for $DateGMT: YYYY-MM-DD
		if (!$this->fileDate) {
			if (preg_match('/\$DateGMT: (\d{4}-\d{2}-\d{2})/', $this->data, $m)) {
				$this->fileDate = $m[1];
			} else {
				$this->fileDate = date("Y-m-d"); // Fallback
			}
		}
		
		$timeStr = trim(preg_replace('/\(.*?\)/', '', $str)); // Remove (GMT)
		$dt = $this->fileDate . " " . $timeStr; // 2026-02-07 14:30:25
		
		$unix = strtotime($dt . " UTC");
		// Convert to Windows FileTime (100ns chunks since 1601)
		// Unix * 10,000,000 + 116444736000000000
		return ($unix * 10000000) + 116444736000000000;
	}

	private function readAP($ver) {
		$ap = array();

		// SSID
		$ssidLen = $this->readUInt8();
		$ap['ssid'] = $this->readString($ssidLen);

		// BSSID
		$bssidBytes = $this->readString(6);
		$ap['bssid'] = strtoupper(bin2hex($bssidBytes));
		// Format BSSID
		$ap['bssid'] = wordwrap($ap['bssid'], 2, ':', true);

		// Metrics
		$ap['max_signal'] = $this->readInt32();
		$ap['min_noise'] = $this->readInt32();
		$ap['max_snr'] = $this->readInt32();
		$ap['flags'] = $this->readUInt32();
		$ap['beacon_interval'] = $this->readUInt32();
		$ap['first_seen'] = $this->readInt64();
		$ap['last_seen'] = $this->readInt64();
		$ap['best_lat'] = $this->readDouble();
		$ap['best_lon'] = $this->readDouble();

		// Data Points
		$dataCount = $this->readUInt32();
		$ap['history'] = array();
		for ($j = 0; $j < $dataCount; $j++) {
			$dp = array();
			$dp['time'] = $this->readInt64();
			$dp['signal'] = $this->readInt32();
			$dp['noise'] = $this->readInt32();
			$dp['loc_source'] = $this->readInt32();
			
			if ($dp['loc_source'] == 1) { // GPS
				$gps = array();
				$gps['lat'] = $this->readDouble();
				$gps['lon'] = $this->readDouble();
				$gps['alt'] = $this->readDouble();
				$gps['sats'] = $this->readUInt32();
				$gps['speed'] = $this->readDouble();
				$gps['track'] = $this->readDouble();
				$gps['magvar'] = $this->readDouble();
				$gps['hdop'] = $this->readDouble();
				$dp['gps'] = $gps;
			}
			$ap['history'][] = $dp;
		}

		// Name
		$nameLen = $this->readUInt8();
		$ap['name'] = $this->readString($nameLen);

		if ($ver >= 8) {
			$ap['channels'] = $this->readUInt64();
			$ap['last_channel'] = $this->readUInt32();
			$ap['ip'] = $this->readUInt32();
		}
		
		if ($ver >= 11) {
			$ap['min_signal'] = $this->readInt32();
			$ap['max_noise'] = $this->readInt32();
			$ap['data_rate'] = $this->readUInt32(); // x100kbps (55 = 5.5mbps) 
			$ap['ip_subnet'] = $this->readUInt32();
			$ap['ip_mask'] = $this->readUInt32();
		}
		
		if ($ver >= 12) {
			$ap['ap_flags'] = $this->readUInt32();
			$ieLen = $this->readUInt32();
			$ap['ies'] = $this->readString($ieLen);
		}

		return $ap;
	}

	// Helpers
	private function readString($len) {
		$s = substr($this->data, $this->pos, $len);
		$this->pos += $len;
		return $s;
	}

	private function readUInt8() {
		$v = unpack("C", substr($this->data, $this->pos, 1));
		$this->pos += 1;
		return $v[1];
	}

	private function readUInt32() {
		$v = unpack("V", substr($this->data, $this->pos, 4));
		$this->pos += 4;
		return $v[1];
	}

	private function readInt32() {
		// PHP's unpack 'l' is machine dependent, but 'V' is always unsigned little endian.
		// To get signed little endian 32-bit:
		$v = unpack("l", substr($this->data, $this->pos, 4)); // 'l' is signed long (32bit on 32bit systems, 64bit on 64bit? No 'l' is machine dependent size/endian in early PHP versions)
		// For safety with little endian 32-bit signed:
		$data = substr($this->data, $this->pos, 4);
		$this->pos += 4;
		$val = unpack("V", $data)[1];
		if ($val >= 2147483648) $val -= 4294967296;
		return $val;
	}
	
	private function readInt64() {
		// PHP < 5.6 doesn't have 'P' or 'q'. 
		// Assuming 64-bit PHP for large ints, or we lose precision.
		// Little Endian.
		$v = unpack("P", substr($this->data, $this->pos, 8));
		$this->pos += 8;
		return $v[1];
	}

	private function readUInt64() {
		$v = unpack("P", substr($this->data, $this->pos, 8));
		$this->pos += 8;
		return $v[1];
	}

	private function readDouble() {
		$v = unpack("d", substr($this->data, $this->pos, 8));
		$this->pos += 8;
		return $v[1];
	}
}

?>
