#!/bin/bash

cd /var/www/zotero/admin/
php -f ./storage_existing_files > /dev/null
MYSQL="mysql -h mysql -P 3306 -u root -pzotero" 

getHash=$(echo "SELECT hash FROM storageFiles WHERE storageFileID NOT IN (SELECT storageFileID FROM storageFilesExisting);" | $MYSQL zotero_master)
deleteHash=$(echo $getHash | sed 's/hash//g')

declare -a arrayDel=($deleteHash)
echo ${arrayDel[@]}


