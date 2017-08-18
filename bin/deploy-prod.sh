#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

rsync \
    -avc \
    --delete \
    --exclude app/config/parameters.yml \
    --exclude .git \
    --exclude .idea \
    --exclude var/sessions \
    --exclude var/cache/prod \
    --exclude var/cache/preprod \
    --exclude var/cache/dev \
    --exclude var/cache/test \
    --exclude var/logs/prod.log \
    --exclude var/logs/preprod.log \
    --exclude var/logs/dev.log \
    --exclude var/logs/test.log \
    $DIR/../ www-data@185.162.248.214:/opt/ubiqmachine-webapp/prod/

[ "$1" == "quick" ] && exit 0

ssh root@185.162.248.214 'cd /opt/ubiqmachine-webapp/prod/ && sudo -u www-data SYMFONY_ENV=prod php composer.phar install'

ssh root@185.162.248.214 'sudo -u www-data php /opt/ubiqmachine-webapp/prod/bin/console --env=prod doctrine:migrations:migrate --no-interaction'

ssh root@185.162.248.214 'sudo -u www-data php /opt/ubiqmachine-webapp/prod/bin/console --env=prod cache:clear --no-interaction'
