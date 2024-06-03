#!/bin/sh

MYSQL="mysql -h mysql -P 3306 -u root -pzotero"

echo "SELECT libraryID FROM libraries;" | $MYSQL zotero_master
