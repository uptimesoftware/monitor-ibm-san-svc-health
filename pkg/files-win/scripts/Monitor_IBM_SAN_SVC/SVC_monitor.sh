#!/bin/sh

inst=`grep pidfile /etc/init.d/uptime_core | head -n 1 | cut -d: -f2 | rev | cut -c 12- | rev`
MIBDIRS=$inst/mibs
export MIBDIRS

/usr/local/uptime/apache/bin/php SVC_monitor.php
