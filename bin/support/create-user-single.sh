#!/bin/sh

if [ -z "$1" -o -z "$2" -o -z "$3" -o -z "$4" ]; then
	echo "Usage: ./create-user.sh {username} {password} {email} {storageQuota}"
	exit 1
fi

sudo docker-compose exec -T app-zotero /var/www/zotero/admin/create-user.sh ${1} ${2} ${3} ${4} 
