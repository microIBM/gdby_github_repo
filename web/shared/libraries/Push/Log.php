<?php
/**
 * Apn Logger
 *
 * @author fengzbao@qq.com
 * @copyright Copyright (c) fzb.me
 * @version $Id:1.0.0, Log.php, 2015-09-02 16:13 created (updated)$
 */

namespace Push;


class Log
{
    public function log($sMessage)
    {
        printf("%s ApnPHP[%d]: %s\n",
            date('r'), getmypid(), trim($sMessage)
        );
    }

}