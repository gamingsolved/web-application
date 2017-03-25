#!/bin/bash
args=("$@")
CONTAINER_IDS=$(docker ps | cut -d" " -f1 | grep -v "CONTAINER")
cp /etc/hosts /tmp/hosts.bak
cp /etc/hosts /tmp/hosts.tmp
for i in $CONTAINER_IDS; do
    if [[ ${args[0]} == '' ]]; then
        IP=$(docker inspect --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $i)
    else
        IP=${args[0]}
    fi

    CONTAINER_NAME=$(docker inspect --format '{{ .Name }}' $i | tail -c +2)
    SERVICE_NAME=$(docker inspect $i | grep "com.docker.compose.service" | cut -d'"' -f4)
    VIRTUAL_HOSTS=$(docker inspect $i | egrep -i 'NGINX_HOST|VIRTUAL_HOST|VHOST' | cut -d"=" -f 2 | grep -o '[A-Za-z\.,]*' | head -n1 | sed 's/,/ /g')
    cat /tmp/hosts.tmp | grep -v $CONTAINER_NAME > /tmp/hosts2.tmp
    mv /tmp/hosts2.tmp /tmp/hosts.tmp
    echo "$IP $SERVICE_NAME $CONTAINER_NAME $VIRTUAL_HOSTS"
    echo "$IP $SERVICE_NAME $CONTAINER_NAME $VIRTUAL_HOSTS" >> /tmp/hosts.tmp
done;
sudo cp /tmp/hosts.tmp /etc/hosts
