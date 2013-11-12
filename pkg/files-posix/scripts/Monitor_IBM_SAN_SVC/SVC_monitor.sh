#!/bin/sh

inst=/usr/local/uptime
MIBDIRS=$inst/mibs
export MIBDIRS

/usr/local/uptime/apache/bin/php SVC_monitor.php
