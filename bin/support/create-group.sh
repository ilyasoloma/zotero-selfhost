#!/bin/sh

if [ -z "$1" ]; then
	echo "Usage: create-group.sh {slug Group}"
	exit 1
fi

sudo docker-compose exec -T app-zotero /var/www/zotero/admin/create-group.sh ${1} 
