# ubiqmachine-webapp

## Scratchpad

    docker run --name sew-mysql -p 3306:3306 -e MYSQL_ROOT_PASSWORD=secret -d mysql:5.7
    docker exec -ti sew-mysql mysql -psecret -e "CREATE DATABASE sgew_dev;"
    php bin/console --env=dev doctrine:migrations:migrate

    php bin/console assets:install --symlink

    php bin/console --env=dev app:cloudinstancemanagement "AKI..." "QA8..." ~/Dropbox/cloudgaming/gaming-vm-keypair.pem


    
    rsync -avc --exclude app/config/parameters.yml --exclude .git --exclude var/cache/dev --exclude var/cache/test --exclude var/logs/dev.log --exclude var/logs/test.log ./ www-data@5.45.99.8:/opt/ubiqmachine-webapp/prod/

    
    sudo -u www-data php bin/console --env=prod cache:clear
    
    sudo -u www-data php bin/console --env=prod doctrine:migrations:migrate
    
    sudo -u www-data php bin/console --env=prod server:start



## Color scheme

http://paletton.com/#uid=53y0u0koMBxeEOzk8HwuDy6x1pp
