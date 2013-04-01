<?php

/**
 * Description of wdbcli
 *
 * @author pferland
 */
class wdbcli extends dbcore
{
    function __construct($config)
    {
        $this->This_is_me   = getmypid();
        parent::__construct($config);
    }
    
    
}

?>
