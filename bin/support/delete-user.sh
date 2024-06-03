#!/bin/bash
while getopts ":i:u:" flag; do
  case "${flag}" in
    i)
      sudo docker-compose exec -T app-zotero /var/www/zotero/admin/delete-user.sh  -i ${OPTARG}
      ;;
    u)
      sudo docker-compose exec -T app-zotero /var/www/zotero/admin/delete-user.sh  -u ${OPTARG}
      ;;
    *)
      echo "Flag 'i' - for libraryID; Falg 'u' - for username"
      exit 1
      ;;
  esac
done

