#!/usr/bin/env bash

cd /opt/ubiqmachine-webapp/preprod
/usr/bin/php bin/console --env=preprod app:cloudinstancemanagement \
  $(cat /etc/ubiqmachine-webapp/secrets/preprod/aws-api-key.txt) \
  $(cat /etc/ubiqmachine-webapp/secrets/preprod/aws-api-secret.txt) \
  /etc/ubiqmachine-webapp/secrets/preprod/aws-keypair-private-key.pem \
  $(cat /etc/ubiqmachine-webapp/secrets/preprod/paperspace-api-key.txt) \
  >> /var/tmp/ubiqmachine-webapp.preprod.cloudinstancemanagement.`date +%Y-%m-%d`.log 2>&1
