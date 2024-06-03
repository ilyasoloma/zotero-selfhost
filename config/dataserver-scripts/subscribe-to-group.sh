#!/bin/bash

MYSQL="mysql -h mysql -P 3306 -u root -pzotero"
groupID=$1
getUsr=$(echo "SELECT userID FROM users WHERE userID NOT IN (SELECT userID FROM groupUsers WHERE groupID = $groupID);" | $MYSQL zotero_master) 
subsID=$(echo "$getUsr" | tr -cd '[:digit:][:space:]')
#subsID=$getUsr
#echo $subsID
declare -a array=($subsID)
#echo 
#${array[2]}


for currentID in "${array[@]}"
do
        #echo $currentID
        echo "INSERT INTO groupUsers VALUES ($groupID, $currentID, 'member', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)" | $MYSQL zotero_master
done

