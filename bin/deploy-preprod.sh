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
    $DIR/../ www-data@185.162.248.214:/opt/ubiqmachine-webapp/preprod/

ssh root@185.162.248.214 'cd /opt/ubiqmachine-webapp/preprod/ && sudo -u www-data SYMFONY_ENV=preprod php composer.phar install'

ssh root@185.162.248.214 'sudo -u www-data php /opt/ubiqmachine-webapp/preprod/bin/console --env=preprod doctrine:migrations:migrate --no-interaction'

ssh root@185.162.248.214 'sudo -u www-data php /opt/ubiqmachine-webapp/preprod/bin/console --env=preprod cache:clear --no-interaction'
