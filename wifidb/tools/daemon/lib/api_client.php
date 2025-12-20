<?php
/**
 * Created by JetBrains PhpStorm.
 * User: sysferland
 * Date: 4/23/13
 * Time: 12:15 AM
 * To change this template use File | Settings | File Templates.
 */

class api_client
{
    function __construct() {
        $this->id = 0;
        $this->sock = "";
        $this->ip_addr = "0.0.0.0";
        $this->username = "Unknown";
        $this->apikey = "";
        $this->lastlogin = "";
        $this->loginQuote = "";
    }
}