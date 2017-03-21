# scalablegraphics-enduser-webapp

## Scratchpad

    docker run --name sew-mysql -p 3306:3306 -e MYSQL_ROOT_PASSWORD=secret -d mysql:5.7
    docker exec -ti sew-mysql mysql -psecret -e "CREATE DATABASE sgew_dev;"
    php bin/console --env=dev doctrine:migrations:migrate

    php bin/console assets:install --symlink

    php bin/console --env=dev app:cloudinstancemanagement "AKI..." "QA8..." ~/Dropbox/cloudgaming/gaming-vm-keypair.pem


## Color scheme

http://paletton.com/#uid=53y0u0koMBxeEOzk8HwuDy6x1pp
