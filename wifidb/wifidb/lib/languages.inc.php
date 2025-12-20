<?php
/*
Database.inc.php, holds the database interactive functions.
Copyright (C) 2011 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

ou should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/

class languages
{
    function __construct($basepath)
    {
        $this->base                 = $basepath;
        $this->current_language     = null;
        $this->code                 = '';
        $this->supported_languages  = $this->FindLanguages();
    }
    
    function FindLanguageType($string = "")
    {
        $string = trim($string);
        foreach($this->supported_languages as $lang)
        {
            if(in_array($string, $lang[1]['SearchWords']))
            {
                $this->current_language = $lang;
                return $lang[1]['Info']['LanguageCode'];
            }
        }
        return 0;
    }
    
    function FindLanguages()
    {
        $tmp = array();
        $lang_path = $this->base."lib/languages";
        $d = dir($lang_path);
        while (false !== ($entry = $d->read()))
        {
            if($entry == ".." || $entry == "."){continue;}
            $lang_array = array($entry, INI::read($lang_path.'/'.$entry));
            if(!@$lang_array[1]['Info']['LanguageCode']){continue;}
            $tmp[$lang_array[1]['Info']['LanguageCode']] = $lang_array;
        }
        $d->close();
        return $tmp;
    }
}



class INI {
    /**
     *  WRITE
     */
    static function write($filename, $ini) {
        $string = '';
        foreach(array_keys($ini) as $key) {
            $string .= '['.$key."]\n";
            $string .= INI::write_get_string($ini[$key], '')."\n";
        }
        file_put_contents($filename, $string);
    }
    /**
     *  write get string
     */
    static function write_get_string(& $ini, $prefix) {
        $string = '';
        ksort($ini);
        foreach($ini as $key => $val) {
            if (is_array($val)) {
                $string .= INI::write_get_string($ini[$key], $prefix.$key.'.');
            } else {
                $string .= $prefix.$key.' = '.str_replace("\n", "\\\n", INI::set_value($val))."\n";
            }
        }
        return $string;
    }
    /**
     *  manage keys
     */
    static function set_value($val) {
        if ($val === true) { return 'true'; }
        else if ($val === false) { return 'false'; }
        return $val;
    }
    /**
     *  READ
     */
    static function read($filename) {
        $ini = array();
        $lines = file($filename);
        $section = 'default';
        $multi = '';
        foreach($lines as $line) {
            if (substr($line, 0, 1) !== ';') {
                $line = str_replace("\r", "", str_replace("\n", "", $line));
                if (preg_match('/^\[(.*)\]/', $line, $m)) {
                    $section = $m[1];
                } else if ($multi === '' && preg_match('/^([a-z0-9_.\[\]-]+)\s*=\s*(.*)$/i', $line, $m)) {
                    $key = $m[1];
                    $val = $m[2];
                    if (substr($val, -1) !== "\\") {
                        $val = trim($val);
                        INI::manage_keys($ini[$section], $key, $val);
                        $multi = '';
                    } else {
                        $multi = substr($val, 0, -1)."\n";
                    }
                } else if ($multi !== '') {
                    if (substr($line, -1) === "\\") {
                        $multi .= substr($line, 0, -1)."\n";
                    } else {
                        INI::manage_keys($ini[$section], $key, $multi.$line);
                        $multi = '';
                    }
                }
            }
        }
        
        $consts = array();
        array_walk_recursive($ini, array('INI', 'replace_consts'), $consts);
        return $ini;
    }
    /**
     *  manage keys
     */
    static function get_value($val) {
        if (preg_match('/^-?[0-9]$/i', $val)) { return intval($val); } 
        else if (strtolower($val) === 'true') { return true; }
        else if (strtolower($val) === 'false') { return false; }
        else if (preg_match('/^"(.*)"$/i', $val, $m)) { return $m[1]; }
        else if (preg_match('/^\'(.*)\'$/i', $val, $m)) { return $m[1]; }
        return $val;
    }
    /**
     *  manage keys
     */
    static function get_key($val) {
        if (preg_match('/^[0-9]$/i', $val)) { return intval($val); }
        return $val;
    }
    /**
     *  manage keys
     */
    static function manage_keys(& $ini, $key, $val) {
        if (preg_match('/^([a-z0-9_-]+)\.(.*)$/i', $key, $m)) {
            INI::manage_keys($ini[$m[1]], $m[2], $val);
        } else if (preg_match('/^([a-z0-9_-]+)\[(.*)\]$/i', $key, $m)) {
            if ($m[2] !== '') {
                $ini[$m[1]][INI::get_key($m[2])] = INI::get_value($val);
            } else {
                $ini[$m[1]][] = INI::get_value($val);
            }
        } else {
            $ini[INI::get_key($key)] = INI::get_value($val);
        }
    }
    /**
     *  replace utility
     */
    static function replace_consts(& $item, $key, $consts) {
        if (is_string($item)) {
            $item = strtr($item, $consts);
        }
    }
}
?>