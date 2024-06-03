#!/bin/sh

MYSQL="mysql -h mysql -P 3306 -u root -pzotero"

echo "SELECT username FROM users" | $MYSQL zotero_master
echo "SELECT email FROM users_email" | $MYSQL zotero_www
