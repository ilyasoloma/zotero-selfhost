#!/bin/sh

MYSQL="mysql -h mysql -P 3306 -u root -pzotero"

echo "SET @@global.innodb_large_prefix = 1;" | $MYSQL
echo "SET GLOBAL sql_mode='' " | $MYSQL
echo "set global sql_mode = '' " | $MYSQL


