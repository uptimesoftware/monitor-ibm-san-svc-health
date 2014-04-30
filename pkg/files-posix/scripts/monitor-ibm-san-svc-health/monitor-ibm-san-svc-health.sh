#!/bin/sh

inst=`grep pidfile /etc/init.d/uptime_core | head -n 1 | cut -d: -f2 | rev | cut -c 12- | rev | sed -e 's/^[ \t]*//'`
MIBDIRS=$inst/mibs
export MIBDIRS

/usr/local/uptime/apache/bin/php ../../plugins/scripts/monitor-ibm-san-svc-health/monitor-ibm-san-svc-health.php
