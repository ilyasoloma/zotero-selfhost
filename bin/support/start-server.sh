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

docker-compose start;
