#!/bin/sh

MYSQL="mysql -h mysql -P 3306 -u root -pzotero"

echo "SELECT MIN(libraryID + 1) AS minimum_id FROM ( SELECT libraryID FROM libraries UNION ALL SELECT MAX(libraryID) + 1 AS libraryID FROM libraries ) AS ids WHERE libraryID + 1 NOT IN (SELECT libraryID FROM libraries);" | $MYSQL zotero_master
