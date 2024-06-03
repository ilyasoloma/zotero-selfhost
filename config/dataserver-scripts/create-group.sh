#!/bin/bash

MYSQL="mysql -h mysql -P 3306 -u root -pzotero" 

getID=$(echo "SELECT MIN(libraryID + 1) AS minimum_id FROM ( SELECT libraryID FROM libraries UNION ALL SELECT MAX(libraryID) + 1 AS libraryID FROM libraries ) AS ids WHERE libraryID + 1 NOT IN (SELECT libraryID FROM libraries);" | $MYSQL zotero_master)
libID=$(echo "$getID" | tr -cd '[:digit:]')
gettID=$(echo "SELECT MIN(groupID + 1) AS minimum_id FROM ( SELECT groupID FROM groups UNION ALL SELECT MAX(groupID) + 1 AS groupID FROM groups ) AS ids WHERE groupID + 1 NOT IN (SELECT groupID FROM groups);" | $MYSQL zotero_master)
groupID=$(echo "$gettID" | tr -cd '[:digit:]')
name=$1
echo "INSERT INTO libraries VALUES ($libID, 'group', CURRENT_TIMESTAMP, 0, 2)" | $MYSQL zotero_master
echo "INSERT INTO groups VALUES ($groupID, $libID, '$name', '$name', 'PublicOpen', 'members', 'all', 'members', '', '', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1)" | $MYSQL zotero_master
echo "INSERT INTO shardLibraries VALUES ($libID, 'group', CURRENT_TIMESTAMP, 0)" | $MYSQL zotero_shard_2
echo "INSERT INTO groupUsers VALUES ($groupID, 1, 'owner', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)" | $MYSQL zotero_master

getUsr=$(echo "SELECT userID FROM users WHERE userID NOT IN (SELECT userID FROM groupUsers WHERE groupID = $groupID);" | $MYSQL zotero_master) 
subsID=$(echo "$getUsr" | tr -cd '[:digit:][:space:]')
#subsID=$getUsr
echo $subsID
declare -a array=($subsID)
#echo 
#${array[2]}


for currentID in "${array[@]}"
do
        #echo $currentID
        echo "INSERT INTO groupUsers VALUES ($groupID, $currentID, 'member', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)" | $MYSQL zotero_master
done

