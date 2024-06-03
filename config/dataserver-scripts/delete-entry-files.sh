#!/bin/bash
MYSQL="mysql -h mysql -P 3306 -u root -pzotero"
echo "DELETE FROM storageFiles WHERE hash = '${1}'" | $MYSQL zotero_master
