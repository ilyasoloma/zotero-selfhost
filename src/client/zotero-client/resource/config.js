var ZOTERO_CONFIG = {
	GUID: 'zotero@chnm.gmu.edu',
	ID: 'zotero', // used for db filename, etc.
	CLIENT_NAME: 'Zotero',
	DOMAIN_NAME: 'localhost',
	REPOSITORY_URL: 'https://repo.zotero.org/repo/',
	BASE_URI: 'http://localhost:8080/',
	WWW_BASE_URL: 'http://localhost:8080/',
	PROXY_AUTH_URL: '',
	API_URL: 'http://localhost:8080/',
	STREAMING_URL: 'ws://localhost:8081/',
	SERVICES_URL: 'https://services.zotero.org/',
	API_VERSION: 3,
	CONNECTOR_MIN_VERSION: '5.0.39', // show upgrade prompt for requests from below this version
	PREF_BRANCH: 'extensions.zotero.',
	BOOKMARKLET_ORIGIN: 'https://www.zotero.org',
	BOOKMARKLET_URL: 'http://localhost:8080/bookmarklet/',
	START_URL: "http://localhost:8080/start",
	QUICK_START_URL: "http://localhost:8080/support/quick_start_guide",
	PDF_TOOLS_URL: "http://localhost:8080/download/xpdf/",
	SUPPORT_URL: "http://localhost:8080/support/",
	TROUBLESHOOTING_URL: "http://localhost:8080/support/getting_help",
	FEEDBACK_URL: "https://forums.zotero.org/",
	CONNECTORS_URL: "http://localhost:8080/download/connectors"
};

if (typeof process === 'object' && process + '' === '[object process]'){
	module.exports = ZOTERO_CONFIG;
} else {
	var EXPORTED_SYMBOLS = ["ZOTERO_CONFIG"];
}
