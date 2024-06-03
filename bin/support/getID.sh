#!/bin/bash

ID=$(docker-compose exec app-zotero bash -c "/var/www/zotero/admin/getID.sh")
output=$(echo "$ID" | tr -cd '[:digit:]')
echo "$output"


