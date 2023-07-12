# Zotero-selfhost
Zotero Selfhost - это полноценный репозиторий, призванный облегчить развертывание локального [Zotero](https://www.zotero.org) с использованием последних версий клиента и сервера Zotero. Этот репозиторий является продолжением работы [foxsen](https://github.com/foxsen/zotero-selfhost). Были добавлены некоторые инструменты администрирования, а также выполнена адаптация сервера данных для работы с новыми клиентами Zotero.
> Текущая сборка не поддерживает работу 
> с заметками (элементы Notes). При попытках
> добавления в любой из доступных каталогов
> заметки - клиент будет [поражен](https://www.youtube.com/watch?v=dQw4w9WgXcQ).
## Архитектура сервера
Zotero сложный проект, состоящий из множества компонентов, каждый из которых реализован с помощью различных языков и технологий. Используются такие языки, как javascript/python/php/c/c++/shell/ и др., такие технологии, как node.js/reactive/XULRunner/mysql/apache/redis/websocket/elasticsearch/memcached/localstack/aws/phpadmin и др. Стоит заметить, что имеющейся доступной документации действительно недостаточно. Видимо, ввиду данных фактов, в сети и отсутствует описание архитектуры.
+- подробный и, наверное, более правильный обзор архитектуры выполнил [foxsen](https://github.com/foxsen/zotero-selfhost). Его текст и составляет фундамент моего понимания архитектуры. Ниже опишу максимально коротко архитектуру Zotero, как ее понимаю я в данный момент.

В первом приближении, Zotero представляет собой клиент-серверную систему с некоторыми серверными и некоторыми клиентскими компонентами, причем и серверы, и клиенты зависят от некоторых других вспомогательных компонентов.

Графически, структура выглядит следующим образом:
![scheme](https://raw.githubusercontent.com/ilyasoloma/zotero-selfhost/main/scheme.png)
- Ядром серверной части является написанный на старом php **dataserver**, который обеспечивает основной функционал Zotero_API. Сам по себе активно поддерживается разработчиками, однако содержит много легаси. 
- Структура данных хранится в БД **mysql**. 
- Файлы хранятся в хранилище **amazon S3** или **webdav**. 
- Для реализации SNS/SQS, dataserver использует **localstack**.
- Для кэширования данных и снижения нагрузки на сервер БД Zotero применяют **memcached**.
- **Elasticsearch** для поддержки поиска.
- **redis** для ограничения скорости запросов (возможно, и для обработки уведомлений). Этот момент мне 
не до конца ясен, потому что сам по себе и dataserver и клиент содержат на борту ограничители запросов. 
- Дополнительно прикручивается **StatsD** для получения статистических данных (наверное).



## Развёртывание и работа с сервером
### Подготовка и клонирование репозитория
Основным инструментом при развертывании является docker-compose:
```bash
sudo apt update
sudo apt install docker
sudo apt install docker-compose
```
Клонирование репозитория:
```bash
mkdir /path/to/your/app && cd /path/to/your/app
git clone --recursive --remote-submodules https://github.com/ilyasoloma/zotero-selfhost.git
cd ./zotero-selfhost 
```
### Установка патчей и конфигурирование
Для облегчения последующих обновлений, необходимые изменения в официальном коде поддерживаются в виде патчей в каталоге
src/patches/, запускаются в каталоге верхнего уровня, для их применения следует выполнить ./utils/patch.sh.
>Файл ```patch.sh``` содержит флаги ```patch_dataserver```, ```patch_zotero_client``` и  ```patch_web_library```. 
>Если флаг равен 0, то выбранный каталог патчиться не будет. Для развертывания сервера
>достаточно установить патчи в каталоги dataserver и web_library. Эти флаги установлены
>по умолчанию.
```bash
./utils/patch.sh 
```

***В файле ```config/config.inc.php``` заменить все *localhost* на ip адрес сервера. Допускается не менять config.inc.php, если предполагается работа с Zotero только на том компьютере, на котором будет установлен сервер.**

```php
<?
class Z_CONFIG {
	public static $API_ENABLED = true;
	...
	                                                    Меняем на
    public static $BASE_URI = 'http://localhost:8080'; -----------> 'http://132.123.123.132:8080'
	public static $API_BASE_URI = 'http://localhost:8080/'; ------>  'http://132.123.123.132:8080/';
	etc...
```

### Первый запуск и инициализация сервера
Для корректной работы пакетов *Zotero-selfhost* перед запуском необходимо выполнить:
```bash
sysctl -w vm.max_map_count=262144 
sysctl vm.overcommit_memory=1
``` 
```sysctl -w vm.max_map_count=262144``` – ElasticSearch по умолчанию выделяет память для хранения индексов. Однако некоторые VM ограничивают ее. Минимально возможная память - 262144. Не увеличив память Elastic будет подниматься и тут же падать.
```sysctl vm.overcommit_memory=1``` – Для стабильной работы redis и elasticsearch требуется отключение обработки перерасхода памяти. С этим параметром (как это понял я), в случае обработки сложных задач, нагрузка на память возрастет, но одновременно увеличится производительность задач, активно использующих память.
Для запуска сервера выполните в корневом каталоге проекта:
```bash
docker-compose up -d
```
При первом запуске docker-compose автоматически загрузит и поднимет недостающие пакеты (mysql, redis, minio, elasticsearch, и т.п.). **ВАЖНО**: на время загрузки и установки поисковика ElasticSearch, должен быть подключен VPN (в РФ загрузка невозможна). Иначе docker-compose вернет ошибку. При последующих запусках VPN не нужен.
Убедитесь, что все контейнеры успешно запущены. Это можно сделать либо командой:
```bash
docker ps -a
```
Либо сторонним софтом (bmon, dockstation, и т.д.)

Запустите скрипт для инициализации DB, S3 и SNS:
```bash
./bin/init.sh
```
По итогам инициализации будет доступен 1 пользователь (admin/admin) и 1 общая группа "Shared". 

*Доступ к пакетам*:

| Name          | URL                                           |
| ------------- | --------------------------------------------- |
| Zotero API    | http://localhost:8080                         |
| Stream ws     | ws://localhost:8081                           |
| S3 Web UI     | http://localhost:8082                         |
| PHPMyAdmin    | http://localhost:8083                         |

*Default login/password*:

| Name          | Login                    | Password           |
| ------------- | ------------------------ | ------------------ |
| Zotero API    | admin                    | admin              |
| S3 Web UI     | zotero                   | zoterodocker       |
| PHPMyAdmin    | root                     | zotero             |

Для мониторинга и просмотра логов контейнеров можно, но необязательно, использовать:
```bash
./bin/run.sh
```
или 
```bash
docker-compose up
```
### Последующие (пере)запуски
В случае падения или намеренного отключения, сервер можно поднять следующим образом:
```bash
./bin/support/resumeServer.sh
```
Дополнительная инициализация не потребуется. Пользователи и хранилище сохранятся.

### Краткое описание средств поддержки
 | Path                                 |  Description                                      |
 | -------------------------------------| --------------------------------------------------|
 | ```bin/init.sh```                    | См. выше                                          |
 | ```bin/run.sh```                     | См. выше                                          |
 | ```bin/support/create-user```        | Добавляет пользователей из users.csv, находящегося|
 |                                      | в корневом каталоге сервера                       |
 | ```bin/support/create-user-single``` | Создает одного пользователя                       |
| ```bin/support/lsit-user```           | Выводит в консоль список пользователей            |
| ```bin/support/resumeServer.sh```     | См. выше                                          |

## Адаптация клиента
Для текущей версии сервера, клиент *Zotero* необязательно собирать вручную, но если есть особое желание, то предлагаю обратиться к README файлу [foxsen](https://github.com/foxsen/zotero-selfhost). Переписывать туториал не вижу смысла. Собственно и сам пользовался готовыми билдами. 
Допускается установка stable версии с [официального сайта](https://www.zotero.org/download/), в котором необходимо изменить zotero.jar файл:
```
diff --git a/resource/config.js b/resource/config.js
index ed0737ded..81223efe3 100644
--- a/resource/config.js
+++ b/resource/config.js
@@ -2,30 +2,30 @@ var ZOTERO_CONFIG = {
 	GUID: 'zotero@chnm.gmu.edu',
 	ID: 'zotero', // used for db filename, etc.
 	CLIENT_NAME: 'Zotero',
-	DOMAIN_NAME: 'zotero.org',
+	DOMAIN_NAME: 'IP_SERVER',
 	REPOSITORY_URL: 'https://repo.zotero.org/repo/',
-	BASE_URI: 'http://zotero.org/',
-	WWW_BASE_URL: 'https://www.zotero.org/',
-	PROXY_AUTH_URL: 'https://zoteroproxycheck.s3.amazonaws.com/test',
-	API_URL: 'https://api.zotero.org/',
-	STREAMING_URL: 'wss://stream.zotero.org/',
+	BASE_URI: 'http://IP_SERVER:8080/',
+	WWW_BASE_URL: 'http://IP_SERVER:8080/',
+	PROXY_AUTH_URL: '',
+	API_URL: 'http://IP_SERVER:8080/',
+	STREAMING_URL: 'ws://IP_SERVER:8081/',
 	SERVICES_URL: 'https://services.zotero.org/',
 	API_VERSION: 3,
 	CONNECTOR_MIN_VERSION: '5.0.39', // show upgrade prompt for requests from below this version
 	PREF_BRANCH: 'extensions.zotero.',
 	BOOKMARKLET_ORIGIN: 'https://www.zotero.org',
-	BOOKMARKLET_URL: 'https://www.zotero.org/bookmarklet/',
-	START_URL: "https://www.zotero.org/start",
-	QUICK_START_URL: "https://www.zotero.org/support/quick_start_guide",
-	PDF_TOOLS_URL: "https://www.zotero.org/download/xpdf/",
-	SUPPORT_URL: "https://www.zotero.org/support/",
-	TROUBLESHOOTING_URL: "https://www.zotero.org/support/getting_help",
+	BOOKMARKLET_URL: 'http://IP_SERVER:8080/bookmarklet/',
+	START_URL: "http://IP_SERVER:8080/start",
+	QUICK_START_URL: "http://IP_SERVER:8080/support/quick_start_guide",
+	PDF_TOOLS_URL: "http://IP_SERVER:8080/download/xpdf/",
+	SUPPORT_URL: "http://IP_SERVER:8080/support/",
+	TROUBLESHOOTING_URL: "http://IP_SERVER:8080/support/getting_help",
 	FEEDBACK_URL: "https://forums.zotero.org/",
-	CONNECTORS_URL: "https://www.zotero.org/download/connectors"
+	CONNECTORS_URL: "http://IP_SERVER:8080/download/connectors"
 };
```
```
diff --git a/chrome/content/zotero/xpcom/storage/zfs.js b/chrome/content/zotero/xpcom/storage/zfs.js
index 794b5cbad..ff27a001d 100644
--- a/chrome/content/zotero/xpcom/storage/zfs.js
+++ b/chrome/content/zotero/xpcom/storage/zfs.js
@@ -636,6 +636,10 @@ Zotero.Sync.Storage.Mode.ZFS.prototype = {
 		}
 		
 		var blob = new Blob([params.prefix, file, params.suffix]);
+
+		// FIXME: change https://zotero.your.domain to your server link
+		var url = params.url.replace(/http:\/\/localhost:8082/, 'https://IP_SERVER:8082');
+		params.url = url;
 		
 		try {
 			var req = yield Zotero.HTTP.request(

```
где IP_SERVER - IP адрес локального сервера. 
## TODO
- Решить проблему с заметками;
- Внедрить больше инструментов администрирования. Например, скрипты для создания общих каталогов, 
     редактирования прав пользователей, удаления пользователей etc. Желательно для инструментов подготовить GUI; 
- Доработать web-library. На данный момент работа с файлами доступно только через клиент. Браузерная версия стоит костылем;
- Обновить и адаптировать dataserver до последней версии.
- Добавить параметры сервера mysql (set sql_mode) для решения проблем с синтаксической совместимостью. например, разрешить использование нулевых дат.
- Сборка stream-server и tinymce-clean-serve в образ вместо сборки их во время выполнения. 
- Добавить в образы необходимые для отладки пакеты (хотя бы nano или vim)


