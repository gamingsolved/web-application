#!/usr/bin/env bash

cd /opt/ubiqmachine-webapp/preprod
/usr/bin/php bin/console -v --env=preprod app:generatebillableitems >> /var/tmp/ubiqmachine-webapp.preprod.generatebillableitems.`date +%Y-%m-%d`.log 2>&1
