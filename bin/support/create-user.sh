#!/bin/bash

declare -A users

while IFS=';' read -r ids username password email storageQuota; do
	 users["$ids"]="$ids $username $password $email $storageQuota"
done < users.csv

for i in "${!users[@]}"; do
  echo "POST: ${users[$i]}"
  sudo docker-compose exec -T app-zotero /var/www/zotero/admin/create-user.sh ${users[$i]}
done
echo "All users added!"
