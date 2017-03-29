# ubiqmachine-webapp

## Setup

Prerequisite: 
* docker-engine >= 1.12 (https://docs.docker.com/engine/installation/)
* docker-compose >= 1.9 (https://docs.docker.com/compose/install/)
* for mac: docker-machine (https://docs.docker.com/machine/install-machine/)

### Linux 

    docker-compose build
    docker-compose run --rm ub_phpfpm composer install --prefer-dist
    docker-compose up -d 
    bin/docker-ip-helper.sh
    bin/docker-console doc:data:create
    bin/console-docker doc:sch:up --force

### Mac OS (docker-machine)

    docker-machine start ubiqmachine
    eval $(docker-machine env ubiqmachine)
    docker-compose run --rm ub_phpfpm composer install --prefer-dist
    docker-compose up -d
    bin/docker-ip-helper.sh $(docker-machine ip ubiqmachine)
    bin/docker-console doc:data:create
    bin/console-docker doc:sch:up --force
    
Now open [http://ubiqmachine.local]

## Coding Rules

All DateTime values must always be handled as UTC, and must always explicitly be created so:

    $dt = new \DateTime('now', new \DateTimeZone('UTC'));
    
Also, when taking parameters of type DateTime, please check for compliance like so:
    
    if ($dt->getTimezone()->gedtName() !== 'UTC') throw new \Exception();
    
If you want to present a datetime to the user, please convert at the last moment possible (view layer etc.).


## Scratchpad

    docker run --name sew-mysql -p 3306:3306 -e MYSQL_ROOT_PASSWORD=secret -d mysql:5.7
    docker exec -ti sew-mysql mysql -psecret -e "CREATE DATABASE sgew_dev;"
    php bin/console --env=dev doctrine:migrations:migrate

    php bin/console assets:install --symlink

    php bin/console --env=dev app:cloudinstancemanagement "AKI..." "QA8..." ~/Dropbox/cloudgaming/gaming-vm-keypair.pem


    
    rsync -avc --exclude app/config/parameters.yml --exclude .git --exclude var/cache/dev --exclude var/cache/test --exclude var/logs/dev.log --exclude var/logs/test.log ~/Dropbox/Projects/cloudgaming/ubiqmachine-webapp/ www-data@5.45.99.8:/opt/ubiqmachine-webapp/preprod/

    
    sudo -u www-data php bin/console --env=preprod cache:clear
    
    sudo -u www-data php bin/console --env=preprod doctrine:migrations:migrate
    
    php bin/console --env=preprod app:cloudinstancemanagement `echo /etc/ubiqmachine/webapp/secrets/aws-api-key.txt` `echo /etc/ubiqmachine/webapp/secrets/aws-api-secret.txt` /etc/ubiqmachine/webapp/secrets/aws-keypair-private-key.pem
    

    rsync -avc --exclude app/config/parameters.yml --exclude .git --exclude var/cache/dev --exclude var/cache/test --exclude var/logs/dev.log --exclude var/logs/test.log ~/Dropbox/Projects/cloudgaming/ubiqmachine-webapp/ www-data@5.45.99.8:/opt/ubiqmachine-webapp/prod/

    
    sudo -u www-data php bin/console --env=prod cache:clear
    
    sudo -u www-data php bin/console --env=prod doctrine:migrations:migrate
    



## Color scheme

http://paletton.com/#uid=53y0u0koMBxeEOzk8HwuDy6x1pp
