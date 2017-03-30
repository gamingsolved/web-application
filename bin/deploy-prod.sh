#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

rsync \
    -avc \
    --exclude app/config/parameters.yml \
    --exclude .git \
    --exclude .idea \
    --exclude var/sessions \
    --exclude var/cache/dev \
    --exclude var/cache/test \
    --exclude var/logs/dev.log \
    --exclude var/logs/test.log \
    $DIR/../ www-data@5.45.99.8:/opt/ubiqmachine-webapp/prod/

ssh root@ubiqmachine.com 'sudo -u www-data php /opt/ubiqmachine-webapp/prod/bin/console --env=prod doctrine:migrations:migrate --no-interaction'

ssh root@ubiqmachine.com 'sudo -u www-data php /opt/ubiqmachine-webapp/prod/bin/console --env=prod cache:clear --no-interaction'
