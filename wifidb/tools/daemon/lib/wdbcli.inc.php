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
        $this->cli          = 1;
        parent::__construct($config);
    }
    
    
}

?>
