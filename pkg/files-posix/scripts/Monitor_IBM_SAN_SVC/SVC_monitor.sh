#!/bin/sh

inst=/opt/uptime
MIBDIRS=$inst/mibs
export MIBDIRS

/usr/local/uptime/apache/bin/php SVC_monitor.php
