# scalablegraphics-enduser-webapp

## Scratchpad

    docker run --name sew-mysql -p 3306:3306 -e MYSQL_ROOT_PASSWORD=secret -d mysql:5.7
    docker exec -ti sew-mysql mysql -psecret -e "CREATE DATABASE symfony;"
