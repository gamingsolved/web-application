#!/usr/bin/env bash

cd /opt/ubiqmachine-webapp/prod
/usr/bin/php bin/console --env=prod app:cloudinstancemanagement $(cat /etc/ubiqmachine-webapp/secrets/prod/aws-api-key.txt) $(cat /etc/ubiqmachine-webapp/secrets/prod/aws-api-secret.txt) /etc/ubiqmachine-webapp/secrets/prod/aws-keypair-private-key.pem >> /var/tmp/ubiqmachine-webapp.prod.cloudinstancemanagement.`date +%Y-%m-%d`.log 2>&1
