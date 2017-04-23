#!/usr/bin/env bash

cd /opt/ubiqmachine-webapp/prod
/usr/bin/php bin/console -v --env=prod app:generatebillableitems >> /var/tmp/ubiqmachine-webapp.prod.generatebillableitems.`date +%Y-%m-%d`.log 2>&1
