export VTE_ROOTDIR=`dirname "$0"`/..
export USE_PHP=php

cd $VTE_ROOTDIR

$USE_PHP -f cron/CleanLogs.php
