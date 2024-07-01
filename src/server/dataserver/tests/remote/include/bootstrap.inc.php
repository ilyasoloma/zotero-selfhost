<?php
require '../../vendor/autoload.php';
require 'include/config.inc.php';

mb_language('uni');
mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');
ini_set('serialize_precision', -1);
require '../../model/Date.inc.php';
require '../../model/Utilities.inc.php';

class Z_Tests {
	public static $AWS;
}

define('Z_ENV_BASE_PATH', realpath(__DIR__ . '/../../../') . '/');

//
// Set up AWS service factory
//
$awsConfig = [
	'region' => $config['awsRegion'],
	'version' => 'latest'
];
//  Access key and secret (otherwise uses IAM role authentication)
if (!empty($config['awsAccessKey'])) {
	$awsConfig['credentials'] = [
		'key' => $config['awsAccessKey'],
		'secret' => $config['awsSecretKey']
	];
}
Z_Tests::$AWS = new Aws\Sdk($awsConfig);
unset($awsConfig);

// Wipe data and create API key
require_once 'http.inc.php';
$response = HTTP::post(
	$config['apiURLPrefix'] . "test/setup?u={$config['userID']}&u2={$config['userID2']}",
	" ",
	[],
	[
		"username" => $config['rootUsername'],
		"password" => $config['rootPassword']
	]
);
$json = json_decode($response->getBody());
if (!$json) {
	echo $response->getStatus() . "\n\n";
	echo $response->getBody();
	throw new Exception("Invalid test setup response");
}
$config['user1APIKey'] = $json->user1->apiKey;
$config['user2APIKey'] = $json->user2->apiKey;
// Deprecated
$config['apiKey'] = $json->user1->apiKey;
\Zotero\Tests\Config::update($config);

// Set up groups
require 'groups.inc.php';

/**
 * @param $arr
 * @return mixed
 */
function array_get_first($arr) {
	if (is_array($arr) && isset($arr[0])) {
		return $arr[0];
	}
	return null;
}
