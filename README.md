# ubiqmachine-webapp

## Dev setup

### If you want to use Docker for MySQL, but work with the app and PHP loacally

#### Prerequisites

* Docker for Mac (https://docs.docker.com/docker-for-mac/install/)
* PHP 7.1

#### Steps

    docker run --name ubiqmachine-mysql -p 3306:3306 -e MYSQL_ROOT_PASSWORD=secret -d mysql:5.7

    // Wait a moment for MySQL to start

    docker exec -ti ubiqmachine-mysql mysql -psecret -e "CREATE DATABASE ubiqmachine_webapp_dev;"

    php composer.phar install

        database_host: 127.0.0.1
        database_port: 
        database_name: ubiqmachine_webapp
        database_user: root
        database_password: secret
        mailer_transport: smtp
        mailer_host: 127.0.0.1
        mailer_user: 
        mailer_password: 
        secret (ThisTokenIsNotSoSecretChangeIt):
        paypal_api_username: manuel-facilitator_api1.kiessling.net
        paypal_api_password: HRCA69R59KW66GFC
        paypal_api_signature: AFcWxV21C7fd0v3bYYYRCpSSRl31AoDTxAHRx.0l91OkuS5M0NqyxtQv
        jms_payment_core_encryption_secret: def00000195a17f4515bcdbdbc271b343f07ecc53cb64d7e9ccdbdb3c10f7a74d31cbc51a1af971a0231f87976d506351213ee791c6cf8e74dc2c91e3198943eb7b7be88

    php bin/console --env=dev doctrine:migrations:migrate
    
    php bin/console server:start

Now open [http://127.0.0.1:8000]


### If you want to use Docker for everything

#### Prerequisites

* docker-engine >= 1.12 (https://docs.docker.com/engine/installation/)
* docker-compose >= 1.9 (https://docs.docker.com/compose/install/)
* for mac: docker-machine (https://docs.docker.com/machine/install-machine/)

#### Linux 

    docker-compose build
    docker-compose run --rm ub_phpfpm composer install --prefer-dist
    docker-compose up -d 
    bin/docker-ip-helper.sh
    bin/docker-console doc:data:create
    bin/console-docker doc:sch:up --force

#### Mac OS (docker-machine)

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

### dev

    docker run --name ubiqmachine-mysql -p 3306:3306 -e MYSQL_ROOT_PASSWORD=secret -d mysql:5.7
    docker exec -ti ubiqmachine-mysql mysql -psecret -e "CREATE DATABASE ubiqmachine_webapp_dev;"
    
    php composer.phar install
    
    php bin/console --env=dev doctrine:migrations:migrate

    php bin/console assets:install --symlink

    php bin/console --env=dev app:cloudinstancemanagement `cat ../infrastructure/puppet/modules/ubiqmachine-webapp/templates/etc/ubiqmachine-webapp/secrets/preprod/aws-api-key.txt` `cat ../infrastructure/puppet/modules/ubiqmachine-webapp/templates/etc/ubiqmachine-webapp/secrets/preprod/aws-api-secret.txt` ../infrastructure/puppet/modules/ubiqmachine-webapp/templates/etc/ubiqmachine-webapp/secrets/preprod/aws-keypair-private-key.pem
    Attempting to handle cloud instances of class: AppBundle\Entity\CloudInstance\AwsCloudInstance


### preprod

    rsync -avc --exclude app/config/parameters.yml --exclude .git --exclude var/cache/dev --exclude var/cache/test --exclude var/logs/dev.log --exclude var/logs/test.log ~/Dropbox/Projects/cloudgaming/ubiqmachine-webapp/ www-data@5.45.99.8:/opt/ubiqmachine-webapp/preprod/

    
    sudo -u www-data php bin/console --env=preprod cache:clear
    
    sudo -u www-data php bin/console --env=preprod doctrine:migrations:migrate
    
    watch -n5 'cd /opt/ubiqmachine-webapp/preprod && /usr/bin/php bin/console -v --env=preprod app:generatebillableitems'
    
    watch -n5 'cd /opt/ubiqmachine-webapp/preprod && /usr/bin/php bin/console --env=preprod app:cloudinstancemanagement $(cat /etc/ubiqmachine-webapp/secrets/preprod/aws-api-key.txt) $(cat /etc/ubiqmachine-webapp/secrets/preprod/aws-api-secret.txt) /etc/ubiqmachine-webapp/secrets/preprod/aws-keypair-private-key.pem'


### prod

    rsync -avc --exclude app/config/parameters.yml --exclude .git --exclude var/cache/dev --exclude var/cache/test --exclude var/logs/dev.log --exclude var/logs/test.log ~/Dropbox/Projects/cloudgaming/ubiqmachine-webapp/ www-data@5.45.99.8:/opt/ubiqmachine-webapp/prod/

    
    sudo -u www-data php bin/console --env=prod cache:clear
    
    sudo -u www-data php bin/console --env=prod doctrine:migrations:migrate
    
    watch -n5 'cd /opt/ubiqmachine-webapp/prod && /usr/bin/php bin/console -v --env=prod app:generatebillableitems'
        
    watch -n5 'cd /opt/ubiqmachine-webapp/prod && /usr/bin/php bin/console --env=prod app:cloudinstancemanagement $(cat /etc/ubiqmachine-webapp/secrets/prod/aws-api-key.txt) $(cat /etc/ubiqmachine-webapp/secrets/prod/aws-api-secret.txt) /etc/ubiqmachine-webapp/secrets/prod/aws-keypair-private-key.pem'
    



## Color scheme

http://paletton.com/#uid=53y0u0koMBxeEOzk8HwuDy6x1pp
