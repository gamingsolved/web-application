#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

rsync \
    -avc \
    --delete \
    --exclude app/config/parameters.yml \
    --exclude .git \
    --exclude .idea \
    --exclude var/sessions \
    --exclude var/cache/dev \
    --exclude var/cache/test \
    --exclude var/logs/dev.log \
    --exclude var/logs/test.log \
    $DIR/../ www-data@5.45.99.8:/opt/ubiqmachine-webapp/preprod/

ssh root@ubiqmachine.com 'sudo -u www-data php /opt/ubiqmachine-webapp/preprod/bin/console --env=preprod doctrine:migrations:migrate --no-interaction'

ssh root@ubiqmachine.com 'sudo -u www-data php /opt/ubiqmachine-webapp/preprod/bin/console --env=preprod cache:clear --no-interaction'
