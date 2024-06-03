#!/bin/bash

MYSQL="mysql -h mysql -P 3306 -u root -pzotero"
getID=$(echo "SELECT MIN(libraryID + 1) AS minimum_id FROM ( SELECT libraryID FROM libraries UNION ALL SELECT MAX(libraryID) + 1 AS libraryID FROM libraries ) AS ids WHERE libraryID + 1 NOT IN (SELECT libraryID FROM libraries);" | $MYSQL zotero_master)
ID=$(echo "$getID" | tr -cd '[:digit:]')

echo "INSERT INTO libraries VALUES ($ID, 'user', CURRENT_TIMESTAMP, 0, 1)" | $MYSQL zotero_master
echo "INSERT INTO users VALUES ($ID, $ID, '${1}')" | $MYSQL zotero_master
echo "INSERT INTO storageAccounts VALUES ($ID, ${4}, '2030-12-31 23:59:59')" | $MYSQL zotero_master
echo "INSERT INTO users VALUES ($ID, '${1}', MD5('${2}'), 'normal')" | $MYSQL zotero_www
echo "INSERT INTO users_email (userID, email) VALUES ($ID, '${3}')" | $MYSQL zotero_www
echo "INSERT INTO shardLibraries VALUES ($ID, 'user', CURRENT_TIMESTAMP, 0)" | $MYSQL zotero_shard_1
echo "INSERT INTO storage_institutions (institutionID, domain, storageQuota) VALUES ($ID, 'zotero.cml', ${4})" | $MYSQL zotero_www
echo "INSERT INTO storage_institution_email (institutionID, email) VALUES ($ID, '${3}')" | $MYSQL zotero_www


getGr=$(echo "SELECT groupID FROM groups;" | $MYSQL zotero_master) 
groupID=$(echo "$getGr" | tr -cd '[:digit:][:space:]')
declare -a array=($groupID)
for currentID in "${array[@]}"
do
        #echo $currentID
        echo "INSERT INTO groupUsers VALUES ($currentID, $ID, 'member', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)" | $MYSQL zotero_master
done
