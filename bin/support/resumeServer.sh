#!/bin/sh

# Настройка ограничений для elasticsearch
# без них elastic будет запускаться и тут же отваливаться
sysctl -w vm.max_map_count=262144; 
sysctl vm.overcommit_memory=1;

read -p "Kill all running docker containers (y/n)?" yn
case $yn in 
	[yY] ) docker kill $(docker ps -q);
		break;;
	[nN] ) break;;
	* ) echo invalid response;;
esac

docker-compose up -d;
echo "sleep 10 second...";
sleep 10;
docker-compose exec app-zotero bash -c 'cd /var/www/zotero/misc ; MYSQL="mysql -h mysql -P 3306 -u root -pzotero"; echo "SET @@global.innodb_large_prefix = 1;" | $MYSQL; echo "set global sql_mode = 'STRICT_TRANS_TABLES';" | $MYSQL;'
docker-compose exec app-zotero bash -c 'aws --endpoint-url "http://minio:9000" s3 mb s3://zotero'
docker-compose exec app-zotero bash -c 'aws --endpoint-url "http://minio:9000" s3 mb s3://zotero-fulltext'
docker-compose exec app-zotero bash -c 'aws --endpoint-url "http://localstack:4575" sns create-topic --name zotero'

# Если необходимо следить за консольным логом - раскомментировать
# docker-compose up 
