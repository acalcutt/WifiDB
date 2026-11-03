<?php
/*
Database.inc.php, holds the database interactive functions.
Copyright (C) 2016 Phil Ferland

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

class xml
{
    ######################################################
    ###   http://mysrc.blogspot.com/2007/02/php-xml-to-array-and-backwards.html
    ######################################################
    function xml2ary(&$string="")
    {
        if($string == "") return -1;
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($parser, $string, $vals, $index);
        xml_parser_free($parser);

        $mnary=array();
        $ary=&$mnary;
        foreach ($vals as $r) {
            $t=$r['tag'];
            if ($r['type']=='open') {
                if (isset($ary[$t])) {
                    if (isset($ary[$t][0])) $ary[$t][]=array(); else $ary[$t]=array($ary[$t], array());
                    $cv=&$ary[$t][count($ary[$t])-1];
                } else $cv=&$ary[$t];
                if (isset($r['attributes'])) {foreach ($r['attributes'] as $k=>$v) $cv['_a'][$k]=$v;}
                $cv['_c']=array();
                $cv['_c']['_p']=&$ary;
                $ary=&$cv['_c'];

            } elseif ($r['type']=='complete') {
                if (isset($ary[$t])) { // same as open
                    if (isset($ary[$t][0])) $ary[$t][]=array(); else $ary[$t]=array($ary[$t], array());
                    $cv=&$ary[$t][count($ary[$t])-1];
                } else $cv=&$ary[$t];
                if (isset($r['attributes'])) {foreach ($r['attributes'] as $k=>$v) $cv['_a'][$k]=$v;}
                $cv['_v']=(isset($r['value']) ? $r['value'] : '');

            } elseif ($r['type']=='close') {
                $ary=&$ary['_p'];
            }
        }

        xml::_del_p($mnary);
        return $mnary;
    }

    // _Internal: Remove recursion in result array
    function _del_p(&$ary) {
        foreach ($ary as $k=>$v) {
            if ($k==='_p') unset($ary[$k]);
            elseif (is_array($ary[$k])) xml::_del_p($ary[$k]);
        }
    }

    function ArrayToXML($array=array(), $d=0)
    {
        if($d === 0)
        {
            $res[] = '<?xml version="1.0" encoding="utf-8"?>'."\r\n";
        }
        foreach($array as $tag=>$val)
        {
            if(is_array($val))
            {
                $tag    = str_replace(" ", "_", $tag);
                $sp     = str_repeat("\t", $d);
                $res[] = "$sp<$tag>\r\n".$this->ArrayToXML($val, $d+1)."$sp</$tag>\r\n";
            }else
            {
                $tag    = str_replace(" ", "_", $tag);
                $sp     = str_repeat("\t", $d);
                $res[]  = "$sp<$tag>$val</$tag>\r\n";
            }
        }
        return implode('', $res);
    }

    // Insert element into array
    function ins2ary(&$ary, $element, $pos) {
        $ar1=array_slice($ary, 0, $pos); $ar1[]=$element;
        $ary=array_merge($ar1, array_slice($ary, $pos));
    }
    ####
    ###################################################



}
?>