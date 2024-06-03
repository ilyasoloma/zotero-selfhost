#!/bin/bash

getHash=$(docker-compose exec -T app-zotero /var/www/zotero/admin/get-delete-list.sh)
declare -a arrayHash=($getHash)
./mc ls zotero-storage/zotero/
for currentHash in "${arrayHash[@]}"
do
	./mc rm zotero-storage/zotero/$currentHash
	docker-compose exec -T app-zotero /var/www/zotero/admin/delete-entry-files.sh ${currentHash}
done
echo "Done"
