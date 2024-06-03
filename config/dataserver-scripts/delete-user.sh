#!/bin/bash
MYSQL="mysql -h mysql -P 3306 -u root -pzotero"
while getopts ":i:u:" flag; do
  case "${flag}" in
    i)
      libID=${OPTARG}
      getID=$(echo "SELECT userID FROM users WHERE username = '$user';" | $MYSQL zotero_master)
      usID=$(echo "$getID" | tr -cd '[:digit:]')

      ;;
    u)
      user=${OPTARG}
      getID=$(echo "SELECT libraryID FROM users WHERE username = '$user';" | $MYSQL zotero_master)
      libID=$(echo "$getID" | tr -cd '[:digit:]')
      getID=$(echo "SELECT userID FROM users WHERE username = '$user';" | $MYSQL zotero_master)
      usID=$(echo "$getID" | tr -cd '[:digit:]')
      ;;
    *)
      echo "Flag 'i' - for libraryID; Falg 'u' - for username"
      exit 1
      ;;
  esac
done

echo "DELETE FROM storageAccounts WHERE userID = $libID" | $MYSQL zotero_master
echo "DELETE FROM libraries WHERE libraryID = '$libID'" | $MYSQL zotero_master
echo "DELETE FROM users WHERE libraryID = '$libID'" | $MYSQL zotero_master
echo "DELETE FROM groupUsers WHERE userID = '$usID'" | $MYSQL zotero_master
echo "DELETE FROM users WHERE userID = '$libID'" | $MYSQL zotero_www
echo "DELETE FROM users_email WHERE userID = '$libID'" | $MYSQL zotero_www
echo "DELETE FROM shardLibraries WHERE libraryID = '$libID'" | $MYSQL zotero_shard_1
echo "DELETE FROM storage_institutions WHERE institutionID = '$libID'" | $MYSQL zotero_www
echo "DELETE FROM storage_institution_email WHERE institutionID = '$libID'" | $MYSQL zotero_www

