#!/bin/sh

sudo docker-compose exec app-zotero bash -c 'cd /var/www/zotero/admin && ./reinit.sh'

