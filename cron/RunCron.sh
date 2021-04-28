#! /bin/sh

USE_PHP=php
CRONDIR=`dirname $0`

cd "$CRONDIR"
$USE_PHP -f RunCron.php $*
