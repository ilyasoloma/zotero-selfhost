<?
/*
    ***** BEGIN LICENSE BLOCK *****
    
    This file is part of the Zotero Data Server.
    
    Copyright © 2012 Center for History and New Media
                     George Mason University, Fairfax, Virginia, USA
                     http://zotero.org
    
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.
    
    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    ***** END LICENSE BLOCK *****
*/

namespace APIv3;
use API3 as API;
require_once 'APITests.inc.php';
require_once 'include/api3.inc.php';

class ItemTests extends APITests {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		API::userClear(self::$config['userID']);
		API::groupClear(self::$config['ownedPrivateGroupID']);
	}
	
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		API::userClear(self::$config['userID']);
		API::groupClear(self::$config['ownedPrivateGroupID']);
	}
	
	
	public function testNewEmptyBookItem() {
		$json = API::createItem("book", false, $this);
		$json = $json['successful'][0]['data'];
		$this->assertEquals("book", (string) $json['itemType']);
		$this->assertSame("", $json['title']);
		$this->assertSame("", $json['date']);
		$this->assertSame("", $json['place']);
		return $json;
	}
	
	
	public function testNewEmptyBookItemMultiple() {
		$json = API::getItemTemplate("book");
		
		$data = array();
		$json->title = "A";
		$data[] = $json;
		$json2 = clone $json;
		$json2->title = "B";
		$data[] = $json2;
		$json3 = clone $json;
		$json3->title = "C";
		$json3->numPages = 200;
		$data[] = $json3;
		
		$response = API::postItems($data);
		$this->assert200($response);
		$libraryVersion = $response->getHeader("Last-Modified-Version");
		$json = API::getJSONFromResponse($response);
		$this->assertCount(3, $json['successful']);
		// Deprecated
		$this->assertCount(3, $json['success']);
		
		// Check data in write response
		for ($i = 0; $i < 3; $i++) {
			$this->assertEquals($json['successful'][$i]['key'], $json['successful'][$i]['data']['key']);
			$this->assertEquals($libraryVersion, $json['successful'][$i]['version']);
			$this->assertEquals($libraryVersion, $json['successful'][$i]['data']['version']);
			$this->assertEquals($data[$i]->title, $json['successful'][$i]['data']['title']);
		}
		//$this->assertArrayNotHasKey('numPages', $json['successful'][0]['data']);
		//$this->assertArrayNotHasKey('numPages', $json['successful'][1]['data']);
		$this->assertEquals($data[2]->numPages, $json['successful'][2]['data']['numPages']);
		
		// Check in separate request, to be safe
		$json = API::getItem($json['success'], $this, 'json');
		$itemJSON = array_shift($json);
		$this->assertEquals("A", $itemJSON['data']['title']);
		$itemJSON = array_shift($json);
		$this->assertEquals("B", $itemJSON['data']['title']);
		$itemJSON = array_shift($json);
		$this->assertEquals("C", $itemJSON['data']['title']);
		$this->assertEquals(200, $itemJSON['data']['numPages']);
	}
	
	
	/**
	 * @depends testNewEmptyBookItem
	 */
	public function testEditBookItem($json) {
		$key = $json['key'];
		$version = $json['version'];
		
		$newTitle = "New Title";
		$numPages = 100;
		$creatorType = "author";
		$firstName = "Firstname";
		$lastName = "Lastname";
		
		$json['title'] = $newTitle;
		$json['numPages'] = $numPages;
		$json['creators'][] = array(
			'creatorType' => $creatorType,
			'firstName' => $firstName,
			'lastName' => $lastName
		);
		
		$response = API::userPut(
			self::$config['userID'],
			"items/$key",
			json_encode($json),
			array(
				"Content-Type: application/json",
				"If-Unmodified-Since-Version: $version"
			)
		);
		$this->assert204($response);
		$json = API::getItem($key, $this, 'json')['data'];
		
		$this->assertEquals($newTitle, $json['title']);
		$this->assertEquals($numPages, $json['numPages']);
		$this->assertEquals($creatorType, $json['creators'][0]['creatorType']);
		$this->assertEquals($firstName, $json['creators'][0]['firstName']);
		$this->assertEquals($lastName, $json['creators'][0]['lastName']);
	}
	
	
	public function testDate() {
		$date = 'Sept 18, 2012';
		$parsedDate = '2012-09-18';
		
		$json = API::createItem("book", array(
			"date" => $date
		), $this, 'jsonData');
		$key = $json['key'];
		
		$response = API::userGet(
			self::$config['userID'],
			"items/$key"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($date, $json['data']['date']);
		
		// meta.parsedDate (JSON)
		$this->assertEquals($parsedDate, $json['meta']['parsedDate']);
		
		// zapi:parsedDate (Atom)
		$xml = API::getItem($key, $this, 'atom');
		$this->assertEquals($parsedDate, array_get_first($xml->xpath('/atom:entry/zapi:parsedDate')));
	}
	
	
	public function testDateWithoutDay() {
		$date = 'Sept 2012';
		$parsedDate = '2012-09';
		
		$json = API::createItem("book", array(
			"date" => $date
		), $this, 'jsonData');
		$key = $json['key'];
		
		$response = API::userGet(
			self::$config['userID'],
			"items/$key"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($date, $json['data']['date']);
		
		// meta.parsedDate (JSON)
		$this->assertEquals($parsedDate, $json['meta']['parsedDate']);
		
		// zapi:parsedDate (Atom)
		$xml = API::getItem($key, $this, 'atom');
		$this->assertEquals($parsedDate, array_get_first($xml->xpath('/atom:entry/zapi:parsedDate')));
	}
	
	
	public function testDateWithoutMonth() {
		$date = '2012';
		$parsedDate = '2012';
		
		$json = API::createItem("book", array(
			"date" => $date
		), $this, 'jsonData');
		$key = $json['key'];
		
		$response = API::userGet(
			self::$config['userID'],
			"items/$key"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($date, $json['data']['date']);
		
		// meta.parsedDate (JSON)
		$this->assertEquals($parsedDate, $json['meta']['parsedDate']);
		
		// zapi:parsedDate (Atom)
		$xml = API::getItem($key, $this, 'atom');
		$this->assertEquals($parsedDate, array_get_first($xml->xpath('/atom:entry/zapi:parsedDate')));
	}
	
	
	public function testDateUnparseable() {
		$json = API::createItem("book", array(
			"date" => 'n.d.'
		), $this, 'jsonData');
		$key = $json['key'];
		
		$response = API::userGet(
			self::$config['userID'],
			"items/$key"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals('n.d.', $json['data']['date']);
		
		// meta.parsedDate (JSON)
		$this->assertArrayNotHasKey('parsedDate', $json['meta']);
		
		// zapi:parsedDate (Atom)
		$xml = API::getItem($key, $this, 'atom');
		$this->assertCount(0, $xml->xpath('/atom:entry/zapi:parsedDate'));
	}
	
	
	public function testDateAccessed8601() {
		$date = '2014-02-01T01:23:45Z';
		$data = API::createItem("book", array(
			'accessDate' => $date
		), $this, 'jsonData');
		$this->assertEquals($date, $data['accessDate']);
	}
	
	
	public function testDateAccessed8601TZ() {
		$date = '2014-02-01T01:23:45-0400';
		$dateUTC = '2014-02-01T05:23:45Z';
		$data = API::createItem("book", array(
			'accessDate' => $date
		), $this, 'jsonData');
		$this->assertEquals($dateUTC, $data['accessDate']);
	}
	
	
	public function testDateAccessedSQL() {
		$date = '2014-02-01 01:23:45';
		$date8601 = '2014-02-01T01:23:45Z';
		$data = API::createItem("book", array(
			'accessDate' => $date
		), $this, 'jsonData');
		$this->assertEquals($date8601, $data['accessDate']);
	}
	
	
	public function testDateAccessedInvalid() {
		$date = 'February 1, 2014';
		$response = API::createItem("book", array(
			'accessDate' => $date
		), $this, 'response');
		$this->assert400ForObject($response, "'accessDate' must be in ISO 8601 or UTC 'YYYY-MM-DD[ hh:mm:ss]' format or 'CURRENT_TIMESTAMP' (February 1, 2014)");
	}
	
	
	public function testDateAddedNewItem8601() {
		// In case this is ever extended to other objects
		$objectType = 'item';
		$objectTypePlural = API::getPluralObjectType($objectType);
		
		$dateAdded = "2013-03-03T21:33:53Z";
		
		switch ($objectType) {
		case 'item':
			$itemData = array(
				"title" => "Test",
				"dateAdded" => $dateAdded
			);
			$data = API::createItem("videoRecording", $itemData, $this, 'jsonData');
			break;
		}
		
		$this->assertEquals($dateAdded, $data['dateAdded']);
	}
	
	
	public function testDateAddedNewItem8601TZ() {
		// In case this is ever extended to other objects
		$objectType = 'item';
		$objectTypePlural = API::getPluralObjectType($objectType);
		
		$dateAdded = "2013-03-03T17:33:53-0400";
		$dateAddedUTC = "2013-03-03T21:33:53Z";
		
		switch ($objectType) {
		case 'item':
			$itemData = array(
				"title" => "Test",
				"dateAdded" => $dateAdded
			);
			$data = API::createItem("videoRecording", $itemData, $this, 'jsonData');
			break;
		}
		
		$this->assertEquals($dateAddedUTC, $data['dateAdded']);
	}
	
	
	public function testDateAddedNewItemSQL() {
		// In case this is ever extended to other objects
		$objectType = 'item';
		$objectTypePlural = API::getPluralObjectType($objectType);
		
		$dateAdded = "2013-03-03 21:33:53";
		$dateAdded8601 = "2013-03-03T21:33:53Z";
		
		switch ($objectType) {
		case 'item':
			$itemData = array(
				"title" => "Test",
				"dateAdded" => $dateAdded
			);
			$data = API::createItem("videoRecording", $itemData, $this, 'jsonData');
			break;
		}
		
		$this->assertEquals($dateAdded8601, $data['dateAdded']);
	}
	
	
	public function testDateModified() {
		// In case this is ever extended to other objects
		$objectType = 'item';
		$objectTypePlural = API::getPluralObjectType($objectType);
		
		switch ($objectType) {
		case 'item':
			$itemData = array(
				"title" => "Test"
			);
			$json = API::createItem("videoRecording", $itemData, $this, 'jsonData');
			break;
		}
		
		$objectKey = $json['key'];
		$dateModified1 = $json['dateModified'];
		
		// Make sure we're in the next second
		sleep(1);
		
		//
		// If no explicit dateModified, use current timestamp
		//
		$json['title'] = "Test 2";
		unset($json['dateModified']);
		$response = API::userPut(
			self::$config['userID'],
			"$objectTypePlural/$objectKey",
			json_encode($json)
		);
		$this->assert204($response);
		
		switch ($objectType) {
		case 'item':
			$json = API::getItem($objectKey, $this, 'json')['data'];
			break;
		}
		
		$dateModified2 = $json['dateModified'];
		$this->assertNotEquals($dateModified1, $dateModified2);
		
		// Make sure we're in the next second
		sleep(1);
		
		//
		// If existing dateModified, use current timestamp
		//
		$json['title'] = "Test 3";
		$json['dateModified'] = trim(preg_replace("/[TZ]/", " ", $dateModified2));
		$response = API::userPut(
			self::$config['userID'],
			"$objectTypePlural/$objectKey",
			json_encode($json)
		);
		$this->assert204($response);
		
		switch ($objectType) {
		case 'item':
			$json = API::getItem($objectKey, $this, 'json')['data'];
			break;
		}
		
		$dateModified3 = $json['dateModified'];
		$this->assertNotEquals($dateModified2, $dateModified3);
		
		//
		// If explicit dateModified, use that
		//
		$newDateModified = "2013-03-03T21:33:53Z";
		$json['title'] = "Test 4";
		$json['dateModified'] = $newDateModified;
		$response = API::userPut(
			self::$config['userID'],
			"$objectTypePlural/$objectKey",
			json_encode($json)
		);
		$this->assert204($response);
		
		switch ($objectType) {
		case 'item':
			$json = API::getItem($objectKey, $this, 'json')['data'];
			break;
		}
		$dateModified4 = $json['dateModified'];
		$this->assertEquals($newDateModified, $dateModified4);
	}
	
	
	// TODO: Make this the default and remove above after clients update code
	public function testDateModifiedTmpZoteroClientHack() {
		// In case this is ever extended to other objects
		$objectType = 'item';
		$objectTypePlural = API::getPluralObjectType($objectType);
		
		switch ($objectType) {
		case 'item':
			$itemData = array(
				"title" => "Test"
			);
			$json = API::createItem("videoRecording", $itemData, $this, 'jsonData');
			break;
		}
		
		$objectKey = $json['key'];
		$dateModified1 = $json['dateModified'];
		
		// Make sure we're in the next second
		sleep(1);
		
		//
		// If no explicit dateModified, use current timestamp
		//
		$json['title'] = "Test 2";
		unset($json['dateModified']);
		$response = API::userPut(
			self::$config['userID'],
			"$objectTypePlural/$objectKey",
			json_encode($json),
			// TODO: Remove
			[
				"User-Agent: Firefox"
			]
		);
		$this->assert204($response);
		
		switch ($objectType) {
		case 'item':
			$json = API::getItem($objectKey, $this, 'json')['data'];
			break;
		}
		
		$dateModified2 = $json['dateModified'];
		$this->assertNotEquals($dateModified1, $dateModified2);
		
		// Make sure we're in the next second
		sleep(1);
		
		//
		// If dateModified provided and hasn't changed, use that
		//
		$json['title'] = "Test 3";
		$json['dateModified'] = trim(preg_replace("/[TZ]/", " ", $dateModified2));
		$response = API::userPut(
			self::$config['userID'],
			"$objectTypePlural/$objectKey",
			json_encode($json),
			// TODO: Remove
			[
				"User-Agent: Firefox"
			]
		);
		$this->assert204($response);
		
		switch ($objectType) {
		case 'item':
			$json = API::getItem($objectKey, $this, 'json')['data'];
			break;
		}
		
		$this->assertEquals($dateModified2, $json['dateModified']);
		
		//
		// If dateModified is provided and has changed, use that
		//
		$newDateModified = "2013-03-03T21:33:53Z";
		$json['title'] = "Test 4";
		$json['dateModified'] = $newDateModified;
		$response = API::userPut(
			self::$config['userID'],
			"$objectTypePlural/$objectKey",
			json_encode($json),
			// TODO: Remove
			[
				"User-Agent: Firefox"
			]
		);
		$this->assert204($response);
		
		switch ($objectType) {
		case 'item':
			$json = API::getItem($objectKey, $this, 'json')['data'];
			break;
		}
		$this->assertEquals($newDateModified, $json['dateModified']);
	}
	
	
	public function testDateModifiedCollectionChange() {
		$collectionKey = API::createCollection('Test', false, $this, 'key');
		$json = API::createItem("book", ["title" => "Test"], $this, 'jsonData');
		
		$objectKey = $json['key'];
		$dateModified1 = $json['dateModified'];
		
		$json['collections'] = [$collectionKey];
		
		// Make sure we're in the next second
		sleep(1);
		
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json])
		);
		$this->assert200ForObject($response);
		
		$json = API::getItem($objectKey, $this, 'json')['data'];
		$dateModified2 = $json['dateModified'];
		
		// Date Modified shouldn't have changed
		$this->assertEquals($dateModified1, $dateModified2);
	}
	
	
	public function testChangeItemType() {
		$json = API::getItemTemplate("book");
		$json->title = "Foo";
		$json->numPages = 100;
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			array("Content-Type: application/json")
		);
		$key = API::getFirstSuccessKeyFromResponse($response);
		$json1 = API::getItem($key, $this, 'json')['data'];
		$version = $json1['version'];
		
		$json2 = API::getItemTemplate("bookSection");
		
		foreach ($json2 as $field => &$val) {
			if ($field != "itemType" && isset($json1[$field])) {
				$val = $json1[$field];
			}
		}
		
		$response = API::userPut(
			self::$config['userID'],
			"items/$key",
			json_encode($json2),
			array(
				"Content-Type: application/json",
				"If-Unmodified-Since-Version: $version"
			)
		);
		$this->assert204($response);
		$json = API::getItem($key, $this, 'json')['data'];
		$this->assertEquals("bookSection", $json['itemType']);
		$this->assertEquals("Foo", $json['title']);
		$this->assertArrayNotHasKey("numPages", $json);
	}
	
	
	//
	// PATCH (single item)
	//
	public function testPatchItem() {
		$itemData = array(
			"title" => "Test"
		);
		$json = API::createItem("book", $itemData, $this, 'jsonData');
		$itemKey = $json['key'];
		$itemVersion = $json['version'];
		
		$patch = function ($context, $config, $itemKey, $itemVersion, &$itemData, $newData) {
			foreach ($newData as $field => $val) {
				$itemData[$field] = $val;
			}
			$response = API::userPatch(
				$config['userID'],
				"items/$itemKey?key=" . $config['apiKey'],
				json_encode($newData),
				array(
					"Content-Type: application/json",
					"If-Unmodified-Since-Version: $itemVersion"
				)
			);
			$context->assert204($response);
			$json = API::getItem($itemKey, $this, 'json')['data'];
			
			foreach ($itemData as $field => $val) {
				$context->assertEquals($val, $json[$field]);
			}
			$headerVersion = $response->getHeader("Last-Modified-Version");
			$context->assertGreaterThan($itemVersion, $headerVersion);
			$context->assertEquals($json['version'], $headerVersion);
			
			return $headerVersion;
		};
		
		$newData = array(
			"date" => "2013"
		);
		$itemVersion = $patch($this, self::$config, $itemKey, $itemVersion, $itemData, $newData);
		
		$newData = array(
			"title" => ""
		);
		$itemVersion = $patch($this, self::$config, $itemKey, $itemVersion, $itemData, $newData);
		
		$newData = array(
			"tags" => array(
				array(
					"tag" => "Foo"
				)
			)
		);
		$itemVersion = $patch($this, self::$config, $itemKey, $itemVersion, $itemData, $newData);
		
		$newData = array(
			"tags" => array()
		);
		$itemVersion = $patch($this, self::$config, $itemKey, $itemVersion, $itemData, $newData);
		
		$key = API::createCollection('Test', false, $this, 'key');
		$newData = array(
			"collections" => array($key)
		);
		$itemVersion = $patch($this, self::$config, $itemKey, $itemVersion, $itemData, $newData);
		
		$newData = array(
			"collections" => array()
		);
		$itemVersion = $patch($this, self::$config, $itemKey, $itemVersion, $itemData, $newData);
	}
	
	public function testPatchAttachment() {
		$json = API::createAttachmentItem("imported_file", [], false, $this, 'jsonData');
		$itemKey = $json['key'];
		$itemVersion = $json['version'];
		
		$filename = "test.pdf";
		$mtime = 1234567890000;
		$md5 = "390d914fdac33e307e5b0e1f3dba9da2";
		
		$response = API::userPatch(
			self::$config['userID'],
			"items/$itemKey",
			json_encode([
				"filename" => $filename,
				"mtime" => $mtime,
				"md5" => $md5,
			]),
			[
				"Content-Type: application/json",
				"If-Unmodified-Since-Version: $itemVersion"
			]
		);
		$this->assert204($response);
		$json = API::getItem($itemKey, $this, 'json')['data'];
		
		$this->assertEquals($filename, $json['filename']);
		$this->assertEquals($mtime, $json['mtime']);
		$this->assertEquals($md5, $json['md5']);
		$headerVersion = $response->getHeader("Last-Modified-Version");
		$this->assertGreaterThan($itemVersion, $headerVersion);
		$this->assertEquals($json['version'], $headerVersion);
	}
	
	public function testPatchNote() {
		$text = "<p>Test</p>";
		$newText = "<p>Test 2</p>";
		$json = API::createNoteItem($text, false, $this, 'jsonData');
		$itemKey = $json['key'];
		$itemVersion = $json['version'];
		
		$response = API::userPatch(
			self::$config['userID'],
			"items/$itemKey",
			json_encode([
				"note" => $newText
			]),
			[
				"Content-Type: application/json",
				"If-Unmodified-Since-Version: $itemVersion"
			]
		);
		$this->assert204($response);
		$json = API::getItem($itemKey, $this, 'json')['data'];
		
		$this->assertEquals($newText, $json['note']);
		$headerVersion = $response->getHeader("Last-Modified-Version");
		$this->assertGreaterThan($itemVersion, $headerVersion);
		$this->assertEquals($json['version'], $headerVersion);
	}
	
	public function testPatchNoteOnBookError() {
		$json = API::createItem("book", [], $this, 'jsonData');
		$itemKey = $json['key'];
		$itemVersion = $json['version'];
		
		$response = API::userPatch(
			self::$config['userID'],
			"items/$itemKey",
			json_encode([
				"note" => "Test"
			]),
			[
				"Content-Type: application/json",
				"If-Unmodified-Since-Version: $itemVersion"
			]
		);
		$this->assert400($response, "'note' property is valid only for note and attachment items");
	}
	
	//
	// PATCH (multiple items)
	//
	public function testPatchItems() {
		$itemData = [
			"title" => "Test"
		];
		$json = API::createItem("book", $itemData, $this, 'jsonData');
		$itemKey = $json['key'];
		$itemVersion = $json['version'];
		
		$patch = function ($context, $config, $itemKey, $itemVersion, &$itemData, $newData) {
			foreach ($newData as $field => $val) {
				$itemData[$field] = $val;
			}
			$newData['key'] = $itemKey;
			$newData['version'] = $itemVersion;
			$response = API::userPost(
				$config['userID'],
				"items",
				json_encode([$newData]),
				[
					"Content-Type: application/json"
				]
			);
			$context->assert200ForObject($response);
			$json = API::getItem($itemKey, $this, 'json')['data'];
			
			foreach ($itemData as $field => $val) {
				$context->assertEquals($val, $json[$field]);
			}
			$headerVersion = $response->getHeader("Last-Modified-Version");
			$context->assertGreaterThan($itemVersion, $headerVersion);
			$context->assertEquals($json['version'], $headerVersion);
			
			return $headerVersion;
		};
		
		$newData = [
			"date" => "2013"
		];
		$itemVersion = $patch($this, self::$config, $itemKey, $itemVersion, $itemData, $newData);
		
		$newData = [
			"title" => ""
		];
		$itemVersion = $patch($this, self::$config, $itemKey, $itemVersion, $itemData, $newData);
		
		$newData = [
			"tags" => [
				[
					"tag" => "Foo"
				]
			]
		];
		$itemVersion = $patch($this, self::$config, $itemKey, $itemVersion, $itemData, $newData);
		
		$newData = [
			"tags" => []
		];
		$itemVersion = $patch($this, self::$config, $itemKey, $itemVersion, $itemData, $newData);
		
		$key = API::createCollection('Test', false, $this, 'key');
		$newData = [
			"collections" => [$key]
		];
		$itemVersion = $patch($this, self::$config, $itemKey, $itemVersion, $itemData, $newData);
		
		$newData = [
			"collections" => []
		];
		$itemVersion = $patch($this, self::$config, $itemKey, $itemVersion, $itemData, $newData);
	}
	
	public function testNewComputerProgramItem() {
		$data = API::createItem("computerProgram", false, $this, 'jsonData');
		$key = $data['key'];
		$this->assertEquals("computerProgram", $data['itemType']);
		
		$version = "1.0";
		$data['versionNumber'] = $version;
		
		$response = API::userPut(
			self::$config['userID'],
			"items/$key",
			json_encode($data),
			[
				"Content-Type: application/json"
			]
		);
		$this->assert204($response);
		$json = API::getItem($key, $this, 'json');
		$this->assertEquals($version, $json['data']['versionNumber']);
	}
	
	
	public function testNewInvalidBookItem() {
		$json = API::getItemTemplate("book");
		
		// Missing item type
		$json2 = clone $json;
		unset($json2->itemType);
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json2]),
			array("Content-Type: application/json")
		);
		$this->assert400ForObject($response, "'itemType' property not provided");
		
		// contentType on non-attachment
		$json2 = clone $json;
		$json2->contentType = "text/html";
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json2]),
			array("Content-Type: application/json")
		);
		$this->assert400ForObject($response, "'contentType' is valid only for attachment items");
		
		// more tests
	}
	
	
	public function testEditTopLevelNote() {
		$noteText = "<p>Test</p>";
		
		$json = API::createNoteItem($noteText, null, $this, 'jsonData');
		$noteText = "<p>Test Test</p>";
		$json['note'] = $noteText;
		$response = API::userPut(
			self::$config['userID'],
			"items/{$json['key']}",
			json_encode($json)
		);
		$this->assert204($response);
		
		$response = API::userGet(
			self::$config['userID'],
			"items/{$json['key']}"
		);
		$this->assert200($response);
		$json = API::getJSONFromResponse($response)['data'];
		$this->assertEquals($noteText, $json['note']);
	}
	
	
	public function testEditChildNote() {
		$noteText = "<p>Test</p>";
		$key = API::createItem("book", [ "title" => "Test" ], $this, 'key');
		$json = API::createNoteItem($noteText, $key, $this, 'jsonData');
		$noteText = "<p>Test Test</p>";
		$json['note'] = $noteText;
		$response = API::userPut(
			self::$config['userID'],
			"items/{$json['key']}",
			json_encode($json)
		);
		$this->assert204($response);
		
		$response = API::userGet(
			self::$config['userID'],
			"items/{$json['key']}"
		);
		$this->assert200($response);
		$json = API::getJSONFromResponse($response)['data'];
		$this->assertEquals($noteText, $json['note']);
	}
	
	
	public function testConvertChildNoteToParentViaPatch() {
		$key = API::createItem("book", [ "title" => "Test" ], $this, 'key');
		$json = API::createNoteItem("", $key, $this, 'jsonData');
		$json['parentItem'] = false;
		$response = API::userPatch(
			self::$config['userID'],
			"items/{$json['key']}",
			json_encode($json)
		);
		$this->assert204($response);
		$json = API::getItem($json['key'], $this, 'json')['data'];
		$this->assertArrayNotHasKey('parentItem', $json);
	}
	
	
	public function test_should_convert_child_note_to_top_level_and_add_to_collection_via_PATCH() {
		$collectionKey = API::createCollection('Test', false, $this, 'key');
		$parentItemKey = API::createItem("book", false, $this, 'key');
		$noteJSON = API::createNoteItem("", $parentItemKey, $this, 'jsonData');
		$noteJSON['parentItem'] = false;
		$noteJSON['collections'] = [$collectionKey];
		$response = API::userPatch(
			self::$config['userID'],
			"items/{$noteJSON['key']}",
			json_encode($noteJSON)
		);
		$this->assert204($response);
		$json = API::getItem($noteJSON['key'], $this, 'json')['data'];
		$this->assertArrayNotHasKey('parentItem', $json);
		$this->assertCount(1, $json['collections']);
		$this->assertEquals($collectionKey, $json['collections'][0]);
	}
	
	
	public function test_should_convert_child_note_to_top_level_and_add_to_collection_via_PUT() {
		$collectionKey = API::createCollection('Test', false, $this, 'key');
		$parentItemKey = API::createItem("book", false, $this, 'key');
		$noteJSON = API::createNoteItem("", $parentItemKey, $this, 'jsonData');
		unset($noteJSON['parentItem']);
		$noteJSON['collections'] = [$collectionKey];
		$response = API::userPut(
			self::$config['userID'],
			"items/{$noteJSON['key']}",
			json_encode($noteJSON)
		);
		$this->assert204($response);
		$json = API::getItem($noteJSON['key'], $this, 'json')['data'];
		$this->assertArrayNotHasKey('parentItem', $json);
		$this->assertCount(1, $json['collections']);
		$this->assertEquals($collectionKey, $json['collections'][0]);
	}
	
	
	// See note in validateJSONItem()
	public function test_should_convert_child_attachment_to_top_level_and_add_to_collection_via_PATCH_without_parentItem_false() {
		$collectionKey = API::createCollection('Test', false, $this, 'key');
		$parentItemKey = API::createItem("book", false, $this, 'key');
		$attachmentJSON = API::createAttachmentItem("linked_url", [], $parentItemKey, $this, 'jsonData');
		unset($attachmentJSON['parentItem']);
		$attachmentJSON['collections'] = [$collectionKey];
		$response = API::userPatch(
			self::$config['userID'],
			"items/{$attachmentJSON['key']}",
			json_encode($attachmentJSON)
		);
		$this->assert204($response);
		$json = API::getItem($attachmentJSON['key'], $this, 'json')['data'];
		$this->assertArrayNotHasKey('parentItem', $json);
		$this->assertCount(1, $json['collections']);
		$this->assertEquals($collectionKey, $json['collections'][0]);
	}
	
	
	public function testEditTitleWithCollectionInMultipleMode() {
		$collectionKey = API::createCollection('Test', false, $this, 'key');
		
		$json = API::createItem("book", [
			"title" => "A",
			"collections" => [
				$collectionKey
			]
		], $this, 'jsonData');
		
		$version = $json['version'];
		$json['title'] = "B";
		
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json])
		);
		$this->assert200ForObject($response);
		
		$json = API::getItem($json['key'], $this, 'json')['data'];
		$this->assertEquals("B", $json['title']);
		$this->assertGreaterThan($version, $json['version']);
	}
	
	
	public function testEditTitleWithTagInMultipleMode() {
		$tag1 = [
			"tag" => "foo",
			"type" => 1
		];
		$tag2 = [
			"tag" => "bar"
		];
		
		$json = API::createItem("book", [
			"title" => "A",
			"tags" => [$tag1]
		], $this, 'jsonData');
		
		$this->assertCount(1, $json['tags']);
		$this->assertEquals($tag1, $json['tags'][0]);
		
		$version = $json['version'];
		$json['title'] = "B";
		$json['tags'][] = $tag2;
		
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json])
		);
		$this->assert200ForObject($response);
		
		$json = API::getItem($json['key'], $this, 'json')['data'];
		$this->assertEquals("B", $json['title']);
		$this->assertGreaterThan($version, $json['version']);
		$this->assertCount(2, $json['tags']);
		$this->assertContains($tag1, $json['tags']);
		$this->assertContains($tag2, $json['tags']);
	}
	
	
	/**
	 * If null is passed for a value, it should be treated the same as an empty string, not create
	 * a NULL in the database.
	 *
	 * TODO: Since we don't have direct access to the database, our test for this is changing the
	 * item type and then trying to retrieve it, which isn't ideal. Some way of checking the DB
	 * state would be useful.
	 */
	public function test_should_treat_null_value_as_empty_string() {
		$json = [
			'itemType' => 'book',
			'numPages' => null
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json])
		);
		$this->assert200ForObject($response);
		$json = API::getJSONFromResponse($response);
		$key = $json['successful'][0]['key'];
		$json = API::getItem($key, $this, 'json');
		
		// Change the item type to a type without the field
		$json = [
			'version' => $json['version'],
			'itemType' => 'journalArticle'
		];
		API::userPatch(
			self::$config['userID'],
			"items/$key",
			json_encode($json)
		);
		
		$json = API::getItem($key, $this, 'json');
		$this->assertArrayNotHasKey('numPages', $json);
	}
	
	
	public function testNewEmptyAttachmentFields() {
		$key = API::createItem("book", false, $this, 'key');
		$json = API::createAttachmentItem("imported_url", [], $key, $this, 'jsonData');
		$this->assertNull($json['md5']);
		$this->assertNull($json['mtime']);
	}
	
	
	public function testNewTopLevelImportedFileAttachment() {
		$response = API::get("items/new?itemType=attachment&linkMode=imported_file");
		$json = json_decode($response->getBody());
		
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			array("Content-Type: application/json")
		);
		$this->assert200($response);
	}
	
	
	//
	// Embedded-image attachments
	//
	public function test_should_create_embedded_image_attachment_for_note() {
		$noteKey = API::createNoteItem("Test", null, $this, 'key');
		$imageKey = API::createAttachmentItem(
			'embedded_image', ['contentType' => 'image/png'], $noteKey, $this, 'key'
		);
	}
	
	
	public function test_num_children_and_children_on_note_with_embedded_image_attachment() {
		$noteKey = API::createNoteItem("Test", null, $this, 'key');
		$imageKey = API::createAttachmentItem(
			'embedded_image', ['contentType' => 'image/png'], $noteKey, $this, 'key'
		);
		$response = API::userGet(
			self::$config['userID'],
			"items/$noteKey"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals(1, $json['meta']['numChildren']);
		
		$response = API::userGet(
			self::$config['userID'],
			"items/$noteKey/children"
		);
		$this->assert200($response);
		$json = API::getJSONFromResponse($response);
		$this->assertCount(1, $json);
		$this->assertEquals($imageKey, $json[0]['key']);
	}
	
	
	public function test_should_reject_embedded_image_attachment_without_parent() {
		$response = API::get("items/new?itemType=attachment&linkMode=embedded_image");
		$json = json_decode($response->getBody());
		$json->parentItem = false;
		$json->contentType = 'image/png';
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert400ForObject($response, "Embedded-image attachment must have a parent item");
	}
	
	
	public function test_should_reject_changing_parent_of_embedded_image_attachment() {
		$note1Key = API::createNoteItem("Test 1", null, $this, 'key');
		$note2Key = API::createNoteItem("Test 2", null, $this, 'key');
		$response = API::get("items/new?itemType=attachment&linkMode=embedded_image");
		$json = json_decode($response->getBody());
		$json->parentItem = $note1Key;
		$json->contentType = 'image/png';
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert200ForObject($response);
		$json = API::getJSONFromResponse($response);
		$key = $json['successful'][0]['key'];
		$json = API::getItem($key, $this, 'json');
		
		// Change the parent item
		$json = [
			'version' => $json['version'],
			'parentItem' => $note2Key
		];
		$response = API::userPatch(
			self::$config['userID'],
			"items/$key",
			json_encode($json)
		);
		$this->assert400($response, "Cannot change parent item of embedded-image attachment");
	}
	
	
	public function test_should_reject_clearing_parent_of_embedded_image_attachment() {
		$noteKey = API::createNoteItem("Test", null, $this, 'key');
		$response = API::get("items/new?itemType=attachment&linkMode=embedded_image");
		$json = json_decode($response->getBody());
		$json->parentItem = $noteKey;
		$json->contentType = 'image/png';
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert200ForObject($response);
		$json = API::getJSONFromResponse($response);
		$key = $json['successful'][0]['key'];
		$json = API::getItem($key, $this, 'json');
		
		// Clear the parent item
		$json = [
			'version' => $json['version'],
			'parentItem' => false
		];
		$response = API::userPatch(
			self::$config['userID'],
			"items/$key",
			json_encode($json)
		);
		$this->assert400($response, "Cannot change parent item of embedded-image attachment");
	}
	
	
	public function test_should_reject_invalid_content_type_for_embedded_image_attachment() {
		$noteKey = API::createNoteItem("Test", null, $this, 'key');
		$response = API::get("items/new?itemType=attachment&linkMode=embedded_image");
		$json = json_decode($response->getBody());
		$json->parentItem = $noteKey;
		$json->contentType = 'application/pdf';
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert400ForObject($response, "Embedded-image attachment must have an image content type");
	}
	
	
	public function test_should_reject_embedded_note_for_embedded_image_attachment() {
		$noteKey = API::createNoteItem("Test", null, $this, 'key');
		$response = API::get("items/new?itemType=attachment&linkMode=embedded_image");
		$json = json_decode($response->getBody());
		$json->parentItem = $noteKey;
		$json->note = '<p>Foo</p>';
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert400ForObject($response, "'note' property is not valid for embedded images");
	}
	
	
	/*
	Disabled -- see note at Zotero_Item::checkTopLevelAttachment()
	
	public function testNewInvalidTopLevelAttachment() {
		$linkModes = array("linked_url", "imported_url");
		foreach ($linkModes as $linkMode) {
			$response = API::get("items/new?itemType=attachment&linkMode=$linkMode");
			$json = json_decode($response->getBody());
			
			$response = API::userPost(
				self::$config['userID'],
				"items",
				json_encode([$json]),
				array("Content-Type: application/json")
			);
			$this->assert400ForObject($response, "Only file attachments and PDFs can be top-level items");
		}
	}
	*/
	
	
	/**
	 * It should be possible to edit an existing PDF attachment without sending 'contentType'
	 * (which would cause a new attachment to be rejected)
	 */
	/*
	Disabled -- see note at Zotero_Item::checkTopLevelAttachment()
	
	public function testPatchTopLevelAttachment() {
		$json = API::createAttachmentItem("imported_url", [
			'title' => 'A',
			'contentType' => 'application/pdf',
			'filename' => 'test.pdf'
		], false, $this, 'jsonData');
		
		// With 'attachment' and 'linkMode'
		$json = [
			'itemType' => 'attachment',
			'linkMode' => 'imported_url',
			'key' => $json['key'],
			'version' => $json['version'],
			'title' => 'B'
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert200ForObject($response);
		$json = API::getItem($json['key'], $this, 'json')['data'];
		$this->assertEquals("B", $json['title']);
		
		// Without 'linkMode'
		$json = [
			'itemType' => 'attachment',
			'key' => $json['key'],
			'version' => $json['version'],
			'title' => 'C'
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert200ForObject($response);
		$json = API::getItem($json['key'], $this, 'json')['data'];
		$this->assertEquals("C", $json['title']);
		
		// Without 'itemType' or 'linkMode'
		$json = [
			'key' => $json['key'],
			'version' => $json['version'],
			'title' => 'D'
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert200ForObject($response);
		$json = API::getItem($json['key'], $this, 'json')['data'];
		$this->assertEquals("D", $json['title']);
	}*/
	
	
	public function testNewEmptyLinkAttachmentItemWithItemKey() {
		$key = API::createItem("book", false, $this, 'key');
		API::createAttachmentItem("linked_url", [], $key, $this, 'json');
		
		$response = API::get("items/new?itemType=attachment&linkMode=linked_url");
		$json = json_decode($response->getBody(), true);
		$json['parentItem'] = $key;
		require_once '../../model/Utilities.inc.php';
		require_once '../../model/ID.inc.php';
		$json['key'] = \Zotero_ID::getKey();
		$json['version'] = 0;
		
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			array("Content-Type: application/json")
		);
		$this->assert200ForObject($response);
	}
	
	
	public function testEditEmptyLinkAttachmentItem() {
		$key = API::createItem("book", false, $this, 'key');
		$json = API::createAttachmentItem("linked_url", [], $key, $this, 'jsonData');
		
		$key = $json['key'];
		$version = $json['version'];
		
		$response = API::userPut(
			self::$config['userID'],
			"items/$key",
			json_encode($json),
			array(
				"Content-Type: application/json",
				"If-Unmodified-Since-Version: $version"
			)
		);
		$this->assert204($response);
		$json = API::getItem($key, $this, 'json')['data'];
		// Item shouldn't change
		$this->assertEquals($version, $json['version']);
		
		return $json;
	}
	
	
	public function testEditEmptyImportedURLAttachmentItem() {
		$key = API::createItem("book", false, $this, 'key');
		$json = API::createAttachmentItem("imported_url", [], $key, $this, 'jsonData');
		
		$key = $json['key'];
		$version = $json['version'];
		
		$response = API::userPut(
			self::$config['userID'],
			"items/$key",
			json_encode($json),
			array(
				"Content-Type: application/json",
				"If-Unmodified-Since-Version: $version"
			)
		);
		$this->assert204($response);
		$json = API::getItem($key, $this, 'json')['data'];
		// Item shouldn't change
		$this->assertEquals($version, $json['version']);
		
		return $json;
	}
	
	
	/**
	 * @depends testEditEmptyLinkAttachmentItem
	 */
	public function testEditLinkAttachmentItem($json) {
		$key = $json['key'];
		$version = $json['version'];
		
		$contentType = "text/xml";
		$charset = "utf-8";
		
		$json['contentType'] = $contentType;
		$json['charset'] = $charset;
		
		$response = API::userPut(
			self::$config['userID'],
			"items/$key",
			json_encode($json),
			array(
				"Content-Type: application/json",
				"If-Unmodified-Since-Version: $version"
			)
		);
		$this->assert204($response);
		$json = API::getItem($key, $this, 'json')['data'];
		$this->assertEquals($contentType, $json['contentType']);
		$this->assertEquals($charset, $json['charset']);
	}
	
	/**
	 * @group attachments
	 */
	public function testCreateLinkedFileAttachment() {
		$key = API::createItem("book", false, $this, 'key');
		$path = 'attachments:tést.txt';
		$json = API::createAttachmentItem(
			"linked_file", [
				'path' => $path
			], $key, $this, 'jsonData'
		);
		$this->assertEquals('linked_file', $json['linkMode']);
		// Linked file should have path
		$this->assertEquals($path, $json['path']);
		// And shouldn't have other attachment properties
		$this->assertArrayNotHasKey('filename', $json);
		$this->assertArrayNotHasKey('md5', $json);
		$this->assertArrayNotHasKey('mtime', $json);
	}
	
	/**
	 * @group attachments
	 */
	public function test_should_reject_linked_file_attachment_in_group() {
		$key = API::groupCreateItem(self::$config['ownedPrivateGroupID'], "book", false, $this, 'key');
		$path = 'attachments:tést.txt';
		$response = API::groupCreateAttachmentItem(
			self::$config['ownedPrivateGroupID'],
			"linked_file", [
				'path' => $path
			], $key, $this, 'response'
		);
		$this->assert400ForObject($response, "Linked files can only be added to user libraries");
	}
	
	/**
	 * Date Modified should be updated when a field is changed if not included in upload
	 */
	public function testDateModifiedChangeOnEdit() {
		$json = API::createAttachmentItem("linked_file", [], false, $this, 'jsonData');
		$modified = $json['dateModified'];
		
		for ($i = 1; $i <= 2; $i++) {
			sleep(1);
			unset($json['dateModified']);
			
			switch ($i) {
				case 1:
					$json['note'] = "Test";
					break;
				
				case 2:
					$json['tags'] = [
						[
							'tag' => 'A'
						]
					];
					break;
			}
			
			
			$response = API::userPut(
				self::$config['userID'],
				"items/{$json['key']}",
				json_encode($json),
				["If-Unmodified-Since-Version: " . $json['version']]
			);
			$this->assert204($response);
			
			$json = API::getItem($json['key'], $this, 'json')['data'];
			$this->assertNotEquals($modified, $json['dateModified'], "Date Modified not changed on loop $i");
			$modified = $json['dateModified'];
		}
	}
	
	/**
	 * Date Modified shouldn't be changed if 1) dateModified is provided or 2) certain fields are changed
	 */
	public function testDateModifiedNoChange() {
		$collectionKey = API::createCollection('Test', false, $this, 'key');
		
		$json = API::createItem('book', false, $this, 'jsonData');
		$modified = $json['dateModified'];
		
		for ($i = 1; $i <= 4; $i++) {
			sleep(1);
			
			// For all tests after the first one, unset Date Modified, which would normally cause
			// it to be updated
			if ($i > 1) {
				unset($json['dateModified']);
			}
			
			switch ($i) {
			case 1:
				$json['title'] = 'A';
				break;
			
			case 2:
				$json['collections'] = [$collectionKey];
				break;
			
			case 3:
				$json['deleted'] = true;
				break;
			
			case 4:
				$json['deleted'] = false;
				break;
			}
			
			$response = API::userPost(
				self::$config['userID'],
				"items",
				json_encode([$json]),
				[
					"If-Unmodified-Since-Version: " . $json['version'],
					// TODO: Remove
					[
						"User-Agent: Firefox"
					]
				]
			);
			$this->assert200($response);
			$json = API::getJSONFromResponse($response)['successful'][0]['data'];
			$this->assertEquals($modified, $json['dateModified'], "Date Modified changed on loop $i");
		}
	}
	
	public function testEditAttachmentAtomUpdatedTimestamp() {
		$xml = API::createAttachmentItem("linked_file", [], false, $this, 'atom');
		$data = API::parseDataFromAtomEntry($xml);
		$atomUpdated = (string) array_get_first($xml->xpath('//atom:entry/atom:updated'));
		$json = json_decode($data['content'], true);
		$json['note'] = "Test";
		
		sleep(1);
		
		$response = API::userPut(
			self::$config['userID'],
			"items/{$data['key']}",
			json_encode($json),
			array("If-Unmodified-Since-Version: " . $data['version'])
		);
		$this->assert204($response);
		
		$xml = API::getItemXML($data['key']);
		$atomUpdated2 = (string) array_get_first($xml->xpath('//atom:entry/atom:updated'));
		$this->assertNotEquals($atomUpdated2, $atomUpdated);
	}
	
	
	public function testEditAttachmentAtomUpdatedTimestampTmpZoteroClientHack() {
		$xml = API::createAttachmentItem("linked_file", [], false, $this, 'atom');
		$data = API::parseDataFromAtomEntry($xml);
		$atomUpdated = (string) array_get_first($xml->xpath('//atom:entry/atom:updated'));
		$json = json_decode($data['content'], true);
		unset($json['dateModified']);
		$json['note'] = "Test";
		
		sleep(1);
		
		$response = API::userPut(
			self::$config['userID'],
			"items/{$data['key']}",
			json_encode($json),
			[
				"If-Unmodified-Since-Version: " . $data['version'],
				// TODO: Remove
				[
					"User-Agent: Firefox"
				]
			]
		);
		$this->assert204($response);
		
		$xml = API::getItemXML($data['key']);
		$atomUpdated2 = (string) array_get_first($xml->xpath('//atom:entry/atom:updated'));
		$this->assertNotEquals($atomUpdated2, $atomUpdated);
	}
	
	
	public function testNewAttachmentItemInvalidLinkMode() {
		$response = API::get("items/new?itemType=attachment&linkMode=linked_url");
		$json = json_decode($response->getBody());
		
		// Invalid linkMode
		$json->linkMode = "invalidName";
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			array("Content-Type: application/json")
		);
		$this->assert400ForObject($response, "'invalidName' is not a valid linkMode");
		
		// Missing linkMode
		unset($json->linkMode);
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			array("Content-Type: application/json")
		);
		$this->assert400ForObject($response, "'linkMode' property not provided");
	}
	
	
	/**
	 * @depends testNewEmptyBookItem
	 */
	public function testNewAttachmentItemMD5OnLinkedURL($json) {
		$parentKey = $json['key'];
		
		$response = API::get("items/new?itemType=attachment&linkMode=linked_url");
		$json = json_decode($response->getBody());
		$json->parentItem = $parentKey;
		
		$json->md5 = "c7487a750a97722ae1878ed46b215ebe";
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			array("Content-Type: application/json")
		);
		$this->assert400ForObject($response, "'md5' is valid only for imported and embedded-image attachments");
	}
	
	
	/**
	 * @depends testNewEmptyBookItem
	 */
	public function testNewAttachmentItemModTimeOnLinkedURL($json) {
		$parentKey = $json['key'];
		
		$response = API::get("items/new?itemType=attachment&linkMode=linked_url");
		$json = json_decode($response->getBody());
		$json->parentItem = $parentKey;
		
		$json->mtime = "1332807793000";
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			array("Content-Type: application/json")
		);
		$this->assert400ForObject($response, "'mtime' is valid only for imported and embedded-image attachments");
	}
	
	
	/**
	 * Changing existing 'md5' and 'mtime' values to null was originally prevented, but some client
	 * versions were sending null, so now we just ignore it.
	 *
	 * At some point, we should check whether any clients are still doing this and restore the
	 * restriction if not. These should only be cleared on a storage purge.
	 */
	//public function test_cannot_change_existing_storage_properties_to_null() {
	public function test_should_ignore_null_for_existing_storage_properties() {
		$key = API::createItem("book", [], $this, 'key');
		$json = API::createAttachmentItem(
			"imported_url",
			[
				'md5' => md5(\Zotero_Utilities::randomString(50)),
				'mtime' => time()
			],
			$key,
			$this,
			'jsonData'
		);
		
		$key = $json['key'];
		$version = $json['version'];
		
		$props = ["md5", "mtime"];
		foreach ($props as $prop) {
			$json2 = $json;
			$json2[$prop] = null;
			$response = API::userPut(
				self::$config['userID'],
				"items/$key",
				json_encode($json2),
				[
					"Content-Type: application/json",
					"If-Unmodified-Since-Version: $version"
				]
			);
			//$this->assert400($response);
			//$this->assertEquals("Cannot change existing '$prop' to null", $response->getBody());
			$this->assert204($response);
		}
		
		$json3 = API::getItem($json['key']);
		$this->assertEquals($json['md5'], $json3['data']['md5']);
		$this->assertEquals($json['mtime'], $json3['data']['mtime']);
	}
	
	
	public function testMappedCreatorTypes() {
		$json = [
			[
				'itemType' => 'presentation',
				'title' => 'Test',
				'creators' => [
					[
						"creatorType" => "author",
						"name" => "Foo"
					]
				]
			],
			[
				'itemType' => 'presentation',
				'title' => 'Test',
				'creators' => [
					[
						"creatorType" => "editor",
						"name" => "Foo"
					]
				]
			]
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode($json)
		);
		// 'author' gets mapped automatically
		$this->assert200ForObject($response);
		// Others don't
		$this->assert400ForObject($response, false, 1);
	}
	
	
	public function testLibraryUser() {
		$json = API::createItem('book', false, $this, 'json');
		$this->assertEquals('user', $json['library']['type']);
		$this->assertEquals(self::$config['userID'], $json['library']['id']);
		$this->assertEquals(self::$config['displayName'], $json['library']['name']);
		$this->assertRegExp('%^https?://[^/]+/' . self::$config['username'] . '$%', $json['library']['links']['alternate']['href']);
		$this->assertEquals('text/html', $json['library']['links']['alternate']['type']);
	}
	
	
	public function testLibraryGroup() {
		$json = API::groupCreateItem(self::$config['ownedPrivateGroupID'], 'book', [], $this, 'json');
		$this->assertEquals('group', $json['library']['type']);
		$this->assertEquals(self::$config['ownedPrivateGroupID'], $json['library']['id']);
		$this->assertEquals(self::$config['ownedPrivateGroupName'], $json['library']['name']);
		$this->assertRegExp('%^https?://[^/]+/groups/[0-9]+$%', $json['library']['links']['alternate']['href']);
		$this->assertEquals('text/html', $json['library']['links']['alternate']['type']);
	}
	
	
	public function test_createdByUser() {
		$json = API::groupCreateItem(self::$config['ownedPrivateGroupID'], 'book', [], $this, 'json');
		$this->assertEquals(self::$config['userID'], $json['meta']['createdByUser']['id']);
		$this->assertEquals(self::$config['username'], $json['meta']['createdByUser']['username']);
		// TODO: Name and URI
	}
	
	
	public function testNumChildrenJSON() {
		$json = API::createItem("book", false, $this, 'json');
		$this->assertEquals(0, $json['meta']['numChildren']);
		$key = $json['key'];
		
		API::createAttachmentItem("linked_url", [], $key, $this, 'key');
		
		$response = API::userGet(
			self::$config['userID'],
			"items/$key"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals(1, $json['meta']['numChildren']);
		
		API::createNoteItem("Test", $key, $this, 'key');
		
		$response = API::userGet(
			self::$config['userID'],
			"items/$key"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals(2, $json['meta']['numChildren']);
	}
	
	
	public function testNumChildrenAtom() {
		$xml = API::createItem("book", false, $this, 'atom');
		$this->assertEquals(0, (int) array_get_first($xml->xpath('/atom:entry/zapi:numChildren')));
		$data = API::parseDataFromAtomEntry($xml);
		$key = $data['key'];
		
		API::createAttachmentItem("linked_url", [], $key, $this, 'key');
		
		$response = API::userGet(
			self::$config['userID'],
			"items/$key?content=json"
		);
		$xml = API::getXMLFromResponse($response);
		$this->assertEquals(1, (int) array_get_first($xml->xpath('/atom:entry/zapi:numChildren')));
		
		API::createNoteItem("Test", $key, $this, 'key');
		
		$response = API::userGet(
			self::$config['userID'],
			"items/$key?content=json"
		);
		$xml = API::getXMLFromResponse($response);
		$this->assertEquals(2, (int) array_get_first($xml->xpath('/atom:entry/zapi:numChildren')));
	}
	
	
	public function test_num_children_and_children_on_attachment_with_annotation() {
		$key = API::createItem("book", false, $this, 'key');
		$attachmentKey = API::createAttachmentItem(
			"imported_url",
			[
				'contentType' => 'application/pdf',
				'title' => 'bbb'
			],
			$key, $this, 'key'
		);
		$annotationKey = API::createAnnotationItem(
			'image',
			['annotationComment' => 'ccc'],
			$attachmentKey,
			$this,
			'key'
		);
		$response = API::userGet(
			self::$config['userID'],
			"items/$attachmentKey"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals(1, $json['meta']['numChildren']);
		
		$response = API::userGet(
			self::$config['userID'],
			"items/$attachmentKey/children"
		);
		$this->assert200($response);
		$json = API::getJSONFromResponse($response);
		$this->assertCount(1, $json);
		$this->assertEquals('ccc', $json[0]['data']['annotationComment']);
	}
	
	
	public function testTop() {
		API::userClear(self::$config['userID']);
		
		$collectionKey = API::createCollection('Test', false, $this, 'key');
		$emptyCollectionKey = API::createCollection('Empty', false, $this, 'key');
		
		$parentTitle1 = "Parent Title";
		$childTitle1 = "This is a Test Title";
		$parentTitle2 = "Another Parent Title";
		$parentTitle3 = "Yet Another Parent Title";
		$noteText = "This is a sample note.";
		$parentTitleSearch = "title";
		$childTitleSearch = "test";
		$dates = ["2013", "January 3, 2010", ""];
		$orderedDates = [$dates[2], $dates[1], $dates[0]];
		$itemTypes = ["journalArticle", "newspaperArticle", "book"];
		
		$parentKeys = [];
		$childKeys = [];
		
		$parentKeys[] = API::createItem($itemTypes[0], [
			'title' => $parentTitle1,
			'date' => $dates[0],
			'collections' => [
				$collectionKey
			]
		], $this, 'key');
		$childKeys[] = API::createAttachmentItem("linked_url", [
			'title' => $childTitle1
		], $parentKeys[0], $this, 'key');
		
		$parentKeys[] = API::createItem($itemTypes[1], [
			'title' => $parentTitle2,
			'date' => $dates[1]
		], $this, 'key');
		$childKeys[] = API::createNoteItem($noteText, $parentKeys[1], $this, 'key');
		$childKeys[] = API::createAttachmentItem(
			'embedded_image',
			[
				'contentType' => 'image/png'
			],
			$childKeys[sizeOf($childKeys) - 1],
			$this,
			'key'
		);
		
		// Create item with deleted child that matches child title search
		$parentKeys[] = API::createItem($itemTypes[2], [
			'title' => $parentTitle3
		], $this, 'key');
		API::createAttachmentItem("linked_url", [
			'title' => $childTitle1,
			'deleted' => true
		], $parentKeys[sizeOf($parentKeys) - 1], $this, 'key');
		
		// Add deleted item with non-deleted child
		$deletedKey = API::createItem("book", [
			'title' => "This is a deleted item",
			'deleted' => true
		], $this, 'key');
		API::createNoteItem("This is a child note of a deleted item.", $deletedKey, $this, 'key');
		
		// /top, JSON
		$response = API::userGet(
			self::$config['userID'],
			"items/top"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$json = API::getJSONFromResponse($response);
		$done = [];
		foreach ($json as $item) {
			$this->assertContains($item['key'], $parentKeys);
			$this->assertNotContains($item['key'], $done);
			$done[] = $item['key'];
		}
		
		// /top, Atom
		$response = API::userGet(
			self::$config['userID'],
			"items/top?content=json"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/zapi:key');
		$this->assertCount(sizeOf($parentKeys), $xpath);
		foreach ($parentKeys as $parentKey) {
			$this->assertContains($parentKey, $xpath);
		}
		
		// /top, JSON, in collection
		$response = API::userGet(
			self::$config['userID'],
			"collections/$collectionKey/items/top"
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertCount(1, $json);
		$this->assertEquals($parentKeys[0], $json[0]['key']);
		
		// /top, Atom, in collection
		$response = API::userGet(
			self::$config['userID'],
			"collections/$collectionKey/items/top?content=json"
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/zapi:key');
		$this->assertCount(1, $xpath);
		$this->assertContains($parentKeys[0], $xpath);
		
		// /top, JSON, in empty collection
		$response = API::userGet(
			self::$config['userID'],
			"collections/$emptyCollectionKey/items/top"
		);
		$this->assert200($response);
		$this->assertNumResults(0, $response);
		$this->assertTotalResults(0, $response);
		
		// /top, keys
		$response = API::userGet(
			self::$config['userID'],
			"items/top?format=keys"
		);
		$this->assert200($response);
		$keys = explode("\n", trim($response->getBody()));
		$this->assertCount(sizeOf($parentKeys), $keys);
		foreach ($parentKeys as $parentKey) {
			$this->assertContains($parentKey, $keys);
		}
		
		// /top, keys, in collection
		$response = API::userGet(
			self::$config['userID'],
			"collections/$collectionKey/items/top?format=keys"
		);
		$this->assert200($response);
		$this->assertEquals($parentKeys[0], trim($response->getBody()));
		
		// /top with itemKey for parent, JSON
		$response = API::userGet(
			self::$config['userID'],
			"items/top?itemKey=" . $parentKeys[0]
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($parentKeys[0], $json[0]['key']);
		
		// /top with itemKey for parent, Atom
		$response = API::userGet(
			self::$config['userID'],
			"items/top?content=json&itemKey=" . $parentKeys[0]
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/zapi:key');
		$this->assertEquals($parentKeys[0], (string) array_shift($xpath));
		
		// /top with itemKey for parent, JSON, in collection
		$response = API::userGet(
			self::$config['userID'],
			"collections/$collectionKey/items/top?itemKey=" . $parentKeys[0]
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($parentKeys[0], $json[0]['key']);
		
		// /top with itemKey for parent, Atom, in collection
		$response = API::userGet(
			self::$config['userID'],
			"collections/$collectionKey/items/top?content=json&itemKey=" . $parentKeys[0]
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/zapi:key');
		$this->assertEquals($parentKeys[0], (string) array_shift($xpath));
		
		// /top with itemKey for parent, keys
		$response = API::userGet(
			self::$config['userID'],
			"items/top?format=keys&itemKey=" . $parentKeys[0]
		);
		$this->assert200($response);
		$this->assertEquals($parentKeys[0], trim($response->getBody()));
		
		// /top with itemKey for parent, keys, in collection
		$response = API::userGet(
			self::$config['userID'],
			"collections/$collectionKey/items/top?format=keys&itemKey=" . $parentKeys[0]
		);
		$this->assert200($response);
		$this->assertEquals($parentKeys[0], trim($response->getBody()));
		
		// /top with itemKey for child, JSON
		$response = API::userGet(
			self::$config['userID'],
			"items/top?itemKey=" . $childKeys[0]
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($parentKeys[0], $json[0]['key']);
		
		// /top with itemKey for child, Atom
		$response = API::userGet(
			self::$config['userID'],
			"items/top?content=json&itemKey=" . $childKeys[0]
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/zapi:key');
		$this->assertEquals($parentKeys[0], (string) array_shift($xpath));
		
		// /top with itemKey for child, keys
		$response = API::userGet(
			self::$config['userID'],
			"items/top?format=keys&itemKey=" . $childKeys[0]
		);
		$this->assert200($response);
		$this->assertEquals($parentKeys[0], trim($response->getBody()));
		
		// /top, Atom, with q for all items
		$response = API::userGet(
			self::$config['userID'],
			"items/top?q=$parentTitleSearch"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$json = API::getJSONFromResponse($response);
		$done = [];
		foreach ($json as $item) {
			$this->assertContains($item['key'], $parentKeys);
			$this->assertNotContains($item['key'], $done);
			$done[] = $item['key'];
		}
		
		// /top, Atom, with q for all items
		$response = API::userGet(
			self::$config['userID'],
			"items/top?content=json&q=$parentTitleSearch"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/zapi:key');
		$this->assertCount(sizeOf($parentKeys), $xpath);
		foreach ($parentKeys as $parentKey) {
			$this->assertContains($parentKey, $xpath);
		}
		
		// /top, JSON, in collection, with q for all items
		$response = API::userGet(
			self::$config['userID'],
			"collections/$collectionKey/items/top?q=$parentTitleSearch"
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($parentKeys[0], $json[0]['key']);
		
		// /top, Atom, in collection, with q for all items
		$response = API::userGet(
			self::$config['userID'],
			"collections/$collectionKey/items/top?content=json&q=$parentTitleSearch"
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/zapi:key');
		$this->assertCount(1, $xpath);
		$this->assertContains($parentKeys[0], $xpath);
		
		// /top, JSON, with q for child item
		$response = API::userGet(
			self::$config['userID'],
			"items/top?q=$childTitleSearch"
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($parentKeys[0], $json[0]['key']);
		
		// /top, Atom, with q for child item
		$response = API::userGet(
			self::$config['userID'],
			"items/top?content=json&q=$childTitleSearch"
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/zapi:key');
		$this->assertCount(1, $xpath);
		$this->assertContains($parentKeys[0], $xpath);
		
		// /top, JSON, in collection, with q for child item
		$response = API::userGet(
			self::$config['userID'],
			"collections/$collectionKey/items/top?q=$childTitleSearch"
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($parentKeys[0], $json[0]['key']);
		
		// /top, Atom, in collection, with q for child item
		$response = API::userGet(
			self::$config['userID'],
			"collections/$collectionKey/items/top?content=json&q=$childTitleSearch"
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/zapi:key');
		$this->assertCount(1, $xpath);
		$this->assertContains($parentKeys[0], $xpath);
		
		// /top, JSON, with q for all items, ordered by title
		$response = API::userGet(
			self::$config['userID'],
			"items/top?q=$parentTitleSearch"
				. "&order=title"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$json = API::getJSONFromResponse($response);
		$returnedTitles = [];
		foreach ($json as $item) {
			$returnedTitles[] = $item['data']['title'];
		}
		$orderedTitles = [$parentTitle1, $parentTitle2, $parentTitle3];
		sort($orderedTitles);
		$this->assertEquals($orderedTitles, $returnedTitles);
		
		// /top, Atom, with q for all items, ordered by title
		$response = API::userGet(
			self::$config['userID'],
			"items/top?content=json&q=$parentTitleSearch"
				. "&order=title"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/atom:title');
		$this->assertCount(sizeOf($parentKeys), $xpath);
		$orderedTitles = [$parentTitle1, $parentTitle2, $parentTitle3];
		sort($orderedTitles);
		$orderedResults = array_map(function ($val) {
			return (string) $val;
		}, $xpath);
		$this->assertEquals($orderedTitles, $orderedResults);
		
		// /top, Atom, with q for all items, ordered by date asc
		$response = API::userGet(
			self::$config['userID'],
			"items/top?q=$parentTitleSearch"
				. "&order=date&sort=asc"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$json = API::getJSONFromResponse($response);
		$orderedResults = array_map(function ($val) {
			return $val['data']['date'];
		}, $json);
		$this->assertEquals($orderedDates, $orderedResults);
		
		// /top, Atom, with q for all items, ordered by date asc
		$response = API::userGet(
			self::$config['userID'],
			"items/top?content=json&q=$parentTitleSearch"
				. "&order=date&sort=asc"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/atom:content');
		$this->assertCount(sizeOf($parentKeys), $xpath);
		$orderedResults = array_map(function ($val) {
			return json_decode($val)->date;
		}, $xpath);
		$this->assertEquals($orderedDates, $orderedResults);
		
		// /top, JSON, with q for all items, ordered by date desc
		$response = API::userGet(
			self::$config['userID'],
			"items/top?q=$parentTitleSearch"
				. "&order=date&sort=desc"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$json = API::getJSONFromResponse($response);
		$orderedDatesReverse = array_reverse($orderedDates);
		$orderedResults = array_map(function ($val) {
			return $val['data']['date'];
		}, $json);
		$this->assertEquals($orderedDatesReverse, $orderedResults);
		
		// /top, Atom, with q for all items, ordered by date desc
		$response = API::userGet(
			self::$config['userID'],
			"items/top?content=json&q=$parentTitleSearch"
				. "&order=date&sort=desc"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/atom:content');
		$this->assertCount(sizeOf($parentKeys), $xpath);
		$orderedDatesReverse = array_reverse($orderedDates);
		$orderedResults = array_map(function ($val) {
			return json_decode($val)->date;
		}, $xpath);
		$this->assertEquals($orderedDatesReverse, $orderedResults);
		
		// /top, Atom, with q for all items, ordered by item type asc
		$response = API::userGet(
			self::$config['userID'],
			"items/top?q=$parentTitleSearch"
				. "&order=itemType"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$json = API::getJSONFromResponse($response);
		$orderedItemTypes = $itemTypes;
		sort($orderedItemTypes);
		$orderedResults = array_map(function ($val) {
			return $val['data']['itemType'];
		}, $json);
		$this->assertEquals($orderedItemTypes, $orderedResults);
		
		// /top, Atom, with q for all items, ordered by item type asc
		$response = API::userGet(
			self::$config['userID'],
			"items/top?content=json&q=$parentTitleSearch"
				. "&order=itemType"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/zapi:itemType');
		$this->assertCount(sizeOf($parentKeys), $xpath);
		$orderedItemTypes = $itemTypes;
		sort($orderedItemTypes);
		$orderedResults = array_map(function ($val) {
			return (string) $val;
		}, $xpath);
		$this->assertEquals($orderedItemTypes, $orderedResults);
		
		// /top, Atom, with q for all items, ordered by item type desc
		$response = API::userGet(
			self::$config['userID'],
			"items/top?q=$parentTitleSearch"
				. "&order=itemType&sort=desc"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$json = API::getJSONFromResponse($response);
		$orderedItemTypes = $itemTypes;
		rsort($orderedItemTypes);
		$orderedResults = array_map(function ($val) {
			return $val['data']['itemType'];
		}, $json);
		$this->assertEquals($orderedItemTypes, $orderedResults);
		
		// /top, Atom, with q for all items, ordered by item type desc
		$response = API::userGet(
			self::$config['userID'],
			"items/top?content=json&q=$parentTitleSearch"
				. "&order=itemType&sort=desc"
		);
		$this->assert200($response);
		$this->assertNumResults(sizeOf($parentKeys), $response);
		$xml = API::getXMLFromResponse($response);
		$xpath = $xml->xpath('//atom:entry/zapi:itemType');
		$this->assertCount(sizeOf($parentKeys), $xpath);
		$orderedItemTypes = $itemTypes;
		rsort($orderedItemTypes);
		$orderedResults = array_map(function ($val) {
			return (string) $val;
		}, $xpath);
		$this->assertEquals($orderedItemTypes, $orderedResults);
	}
	
	
	public function testTopWithSince() {
		API::userClear(self::$config['userID']);
		
		$version1 = API::getLibraryVersion();
		$parentKeys[0] = API::createItem("book", [], $this, 'key');
		$version2 = API::getLibraryVersion();
		$childKeys[0] = API::createAttachmentItem("linked_url", [], $parentKeys[0], $this, 'key');
		$version3 = API::getLibraryVersion();
		$parentKeys[1] = API::createItem("journalArticle", [], $this, 'key');
		$version4 = API::getLibraryVersion();
		$childKeys[1] = API::createNoteItem("", $parentKeys[1], $this, 'key');
		$version5 = API::getLibraryVersion();
		$parentKeys[2] = API::createItem("book", [], $this, 'key');
		$version6 = API::getLibraryVersion();
		
		$response = API::userGet(
			self::$config['userID'],
			"items/top?since=$version1"
		);
		$this->assertNumResults(3, $response);
		
		$response = API::userGet(
			self::$config['userID'],
			"items?since=$version1"
		);
		$this->assertNumResults(5, $response);
		
		$response = API::userGet(
			self::$config['userID'],
			"items/top?format=versions&since=$version4"
		);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$keys = array_keys($json);
		$this->assertEquals($parentKeys[2], $keys[0]);
	}
	
	
	public function test_top_should_return_top_level_item_for_three_level_hierarchy() {
		API::userClear(self::$config['userID']);
		
		// Create parent item, PDF attachment, and annotation
		$itemKey = API::createItem("book", ['title' => 'aaa'], $this, 'key');
		$attachmentKey = API::createAttachmentItem(
			"imported_url", [
				'contentType' => 'application/pdf',
				'title' => 'bbb'
			], $itemKey, $this, 'key'
		);
		$annotationKey = API::createAnnotationItem(
			'highlight',
			['annotationComment' => 'ccc'],
			$attachmentKey,
			$this,
			'key'
		);
		
		//
		// Search for descendant items in /top mode
		//
		$response = API::userGet(
			self::$config['userID'],
			"items/top?q=bbb"
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals("aaa", $json[0]['data']['title']);
		
		$response = API::userGet(
			self::$config['userID'],
			"items/top?itemType=annotation" // TEMP: Until we can search comment
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals("aaa", $json[0]['data']['title']);
		
		$response = API::userGet(
			self::$config['userID'],
			"items/top?itemKey=$imageKey" // Only way to search for an embedded image?
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals("aaa", $json[0]['data']['title']);
	}
	
	
	public function testIncludeTrashed() {
		API::userClear(self::$config['userID']);
		
		$key1 = API::createItem("book", false, $this, 'key');
		$key2 = API::createItem("book", [
			"deleted" => 1
		], $this, 'key');
		$key3 = API::createNoteItem("", $key1, $this, 'key');
		
		// All three items should show up with includeTrashed=1
		$response = API::userGet(
			self::$config['userID'],
			"items?includeTrashed=1"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertCount(3, $json);
		$keys = [$json[0]['key'], $json[1]['key'], $json[2]['key']];
		$this->assertContains($key1, $keys);
		$this->assertContains($key2, $keys);
		$this->assertContains($key3, $keys);
		
		// ?itemKey should show the deleted item
		$response = API::userGet(
			self::$config['userID'],
			"items?itemKey=$key2,$key3&includeTrashed=1"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertCount(2, $json);
		$keys = [$json[0]['key'], $json[1]['key']];
		$this->assertContains($key2, $keys);
		$this->assertContains($key3, $keys);
		
		// /top should show the deleted item
		$response = API::userGet(
			self::$config['userID'],
			"items/top?includeTrashed=1"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertCount(2, $json);
		$keys = [$json[0]['key'], $json[1]['key']];
		$this->assertContains($key1, $keys);
		$this->assertContains($key2, $keys);
	}
	
	
	public function testTrash() {
		API::userClear(self::$config['userID']);
		
		$key1 = API::createItem("book", false, $this, 'key');
		$key2 = API::createItem("book", [
			"deleted" => 1
		], $this, 'key');
		
		// Item should show up in trash
		$response = API::userGet(
			self::$config['userID'],
			"items/trash"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertCount(1, $json);
		$this->assertEquals($key2, $json[0]['key']);
		
		// And not show up in main items
		$response = API::userGet(
			self::$config['userID'],
			"items"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertCount(1, $json);
		$this->assertEquals($key1, $json[0]['key']);
		
		// Including with ?itemKey
		$response = API::userGet(
			self::$config['userID'],
			"items?itemKey=" . $key2
		);
		$json = API::getJSONFromResponse($response);
		$this->assertCount(0, $json);
	}
	
	
	public function test_patch_of_item_should_set_trash_state() {
		$json = API::createItem("book", [], $this, 'json');
		
		$data = [
			[
				'key' => $json['key'],
				'version' => $json['version'],
				'deleted' => true
			]
		];
		$response = API::postItems($data);
		$json = API::getJSONFromResponse($response);
		
		$this->assertArrayHasKey('deleted', $json['successful'][0]['data']);
		$this->assertEquals(1, $json['successful'][0]['data']['deleted']);
	}
	
	
	public function test_patch_of_item_should_clear_trash_state() {
		$json = API::createItem("book", [
			"deleted" => true
		], $this, 'json');
		
		$data = [
			[
				'key' => $json['key'],
				'version' => $json['version'],
				'deleted' => false
			]
		];
		$response = API::postItems($data);
		$json = API::getJSONFromResponse($response);
		
		$this->assertArrayNotHasKey('deleted', $json['successful'][0]['data']);
	}
	
	
	public function test_patch_of_item_in_trash_without_deleted_should_not_remove_it_from_trash() {
		$json = API::createItem("book", [
			"deleted" => true
		], $this, 'json');
		
		$data = [
			[
				'key' => $json['key'],
				'version' => $json['version'],
				'title' => 'A'
			]
		];
		$response = API::postItems($data);
		$json = API::getJSONFromResponse($response);
		
		$this->assertArrayHasKey('deleted', $json['successful'][0]['data']);
		$this->assertEquals(1, $json['successful'][0]['data']['deleted']);
	}
	
	
	public function testParentItem() {
		$json = API::createItem("book", false, $this, 'jsonData');
		$parentKey = $json['key'];
		$parentVersion = $json['version'];
		
		$json = API::createAttachmentItem("linked_file", [], $parentKey, $this, 'jsonData');
		$childKey = $json['key'];
		$childVersion = $json['version'];
		
		$this->assertArrayHasKey('parentItem', $json);
		$this->assertEquals($parentKey, $json['parentItem']);
		
		// Remove the parent, making the child a standalone attachment
		unset($json['parentItem']);
		
		$response = API::userPut(
			self::$config['userID'],
			"items/$childKey",
			json_encode($json),
			array("If-Unmodified-Since-Version: " . $childVersion)
		);
		$this->assert204($response);
		
		$json = API::getItem($childKey, $this, 'json')['data'];
		$this->assertArrayNotHasKey('parentItem', $json);
	}
	
	
	public function test_should_reject_parentItem_that_matches_item_key() {
		$response = API::get("items/new?itemType=attachment&linkMode=imported_file");
		$json = json_decode($response->getBody());
		require_once '../../model/ID.inc.php';
		$json->key = \Zotero_ID::getKey();
		$json->version = 0;
		$json->parentItem = $json->key;
		
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json])
		);
		$msg = "Item $json->key cannot be a child of itself";
		// TEMP
		$msg .= "\n\nCheck your database integrity from the Advanced → Files and Folders pane of the Zotero preferences.";
		$this->assert400ForObject($response, $msg);
	}
	
	
	public function testParentItemPatch() {
		$json = API::createItem("book", false, $this, 'jsonData');
		$parentKey = $json['key'];
		$parentVersion = $json['version'];
		
		$json = API::createAttachmentItem("linked_file", [], $parentKey, $this, 'jsonData');
		$childKey = $json['key'];
		$childVersion = $json['version'];
		
		$this->assertArrayHasKey('parentItem', $json);
		$this->assertEquals($parentKey, $json['parentItem']);
		
		$json = array(
			'title' => 'Test'
		);
		
		// With PATCH, parent shouldn't be removed even though unspecified
		$response = API::userPatch(
			self::$config['userID'],
			"items/$childKey",
			json_encode($json),
			array("If-Unmodified-Since-Version: " . $childVersion)
		);
		$this->assert204($response);
		
		$json = API::getItem($childKey, $this, 'json')['data'];
		$this->assertArrayHasKey('parentItem', $json);
		$childVersion = $json['version'];
		
		// But it should be removed with parentItem: false
		$json = [
			'parentItem' => false
		];
		$response = API::userPatch(
			self::$config['userID'],
			"items/$childKey",
			json_encode($json),
			["If-Unmodified-Since-Version: " . $childVersion]
		);
		$this->assert204($response);
		$json = API::getItem($childKey, $this, 'json')['data'];
		$this->assertArrayNotHasKey('parentItem', $json);
	}
	
	
	public function test_should_move_attachment_with_annotation_under_regular_item() {
		$json = API::createItem("book", false, $this, 'jsonData');
		$itemKey = $json['key'];
		
		// Create standalone attachment to start
		$json = API::createAttachmentItem(
			"imported_file", ['contentType' => 'application/pdf'], null, $this, 'jsonData'
		);
		$attachmentKey = $json['key'];
		
		// Create image annotation
		$annotationKey = API::createAnnotationItem('highlight', null, $attachmentKey, $this, 'key');
		
		// /top for the annotation key should return the attachment
		$response = API::userGet(
			self::$config['userID'],
			"items/top?itemKey=$annotationKey"
		);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($attachmentKey, $json[0]['key']);
		
		// Move attachment under regular item
		$json[0]['data']['parentItem'] = $itemKey;
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json[0]['data']])
		);
		$this->assert200ForObject($response);
		
		// /top for the annotation key should now return the regular item
		$response = API::userGet(
			self::$config['userID'],
			"items/top?itemKey=$annotationKey"
		);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($itemKey, $json[0]['key']);
	}
	
	
	public function test_should_move_attachment_with_annotation_out_from_under_regular_item() {
		$json = API::createItem("book", false, $this, 'jsonData');
		$itemKey = $json['key'];
		
		// Create standalone attachment to start
		$attachmentJSON = API::createAttachmentItem(
			"imported_file", ['contentType' => 'application/pdf'], $itemKey, $this, 'jsonData'
		);
		$attachmentKey = $attachmentJSON['key'];
		
		// Create image annotation
		$annotationKey = API::createAnnotationItem('highlight', null, $attachmentKey, $this, 'key');
		
		// /top for the annotation key should return the item
		$response = API::userGet(
			self::$config['userID'],
			"items/top?itemKey=$annotationKey"
		);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($itemKey, $json[0]['key']);
		
		// Move attachment under regular item
		$attachmentJSON['parentItem'] = false;
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$attachmentJSON])
		);
		$this->assert200ForObject($response);
		
		// /top for the annotation key should now return the attachment item
		$response = API::userGet(
			self::$config['userID'],
			"items/top?itemKey=$annotationKey"
		);
		$this->assertNumResults(1, $response);
		$json = API::getJSONFromResponse($response);
		$this->assertEquals($attachmentKey, $json[0]['key']);
	}
	
	
	public function test_deleting_parent_item_should_delete_child_linked_file_attachment() {
		$json = API::createItem("book", false, $this, 'jsonData');
		$parentKey = $json['key'];
		$parentVersion = $json['version'];
		
		$json = API::createAttachmentItem("linked_file", [], $parentKey, $this, 'jsonData');
		$childKey = $json['key'];
		$childVersion = $json['version'];
		
		$response = API::userGet(
			self::$config['userID'],
			"items?itemKey=$parentKey,$childKey"
		);
		$this->assertNumResults(2, $response);
		
		$response = API::userDelete(
			self::$config['userID'],
			"items/$parentKey",
			["If-Unmodified-Since-Version: " . $parentVersion]
		);
		$this->assert204($response);
		
		$response = API::userGet(
			self::$config['userID'],
			"items?itemKey=$parentKey,$childKey"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertNumResults(0, $response);
	}
	
	
	public function test_deleting_parent_item_should_delete_attachment_and_child_annotation() {
		$json = API::createItem("book", false, $this, 'jsonData');
		$itemKey = $json['key'];
		
		$attachmentKey = API::createAttachmentItem(
			"imported_url",
			['contentType' => 'application/pdf'],
			$itemKey,
			$this,
			'key'
		);
		$json = API::createAnnotationItem('highlight', null, $attachmentKey, $this, 'jsonData');
		$annotationKey = $json['key'];
		$version = $json['version'];
		
		// Delete parent item
		$response = API::userDelete(
			self::$config['userID'],
			"items?itemKey=$itemKey",
			["If-Unmodified-Since-Version: " . $version]
		);
		$this->assert204($response);
		
		// All items should be gone
		$response = API::userGet(
			self::$config['userID'],
			"items?itemKey=$itemKey,$attachmentKey,$annotationKey"
		);
		$this->assert200($response);
		$this->assertNumResults(0, $response);
	}
	
	
	public function test_deleting_linked_file_attachment_should_delete_child_annotation() {
		$json = API::createItem("book", false, $this, 'jsonData');
		$itemKey = $json['key'];
		
		$attachmentKey = API::createAttachmentItem(
			"linked_file", ['contentType' => 'application/pdf'], $itemKey, $this, 'key'
		);
		$json = API::createAnnotationItem(
			'highlight', null, $attachmentKey, $this, 'jsonData'
		);
		$annotationKey = $json['key'];
		$version = $json['version'];
		
		// Delete parent item
		$response = API::userDelete(
			self::$config['userID'],
			"items?itemKey=$attachmentKey",
			["If-Unmodified-Since-Version: " . $version]
		);
		$this->assert204($response);
		
		// Child items should be gone
		$response = API::userGet(
			self::$config['userID'],
			"items?itemKey=$itemKey,$attachmentKey,$annotationKey"
		);
		$this->assert200($response);
		$this->assertNumResults(1, $response);
	}
	
	
	public function test_should_allow_changing_parent_item_of_annotation_to_another_file_attachment() {
		$attachment1Key = API::createAttachmentItem(
			"imported_url",
			['contentType' => 'application/pdf'],
			null,
			$this,
			'key'
		);
		$attachment2Key = API::createAttachmentItem(
			"imported_url",
			['contentType' => 'application/pdf'],
			null,
			$this,
			'key'
		);
		$jsonData = API::createAnnotationItem('highlight', null, $attachment1Key, $this, 'jsonData');
		
		// Change the parent item
		$json = [
			'version' => $jsonData['version'],
			'parentItem' => $attachment2Key
		];
		$response = API::userPatch(
			self::$config['userID'],
			"items/{$jsonData['key']}",
			json_encode($json)
		);
		$this->assert204($response);
	}
	
	
	public function test_should_reject_changing_parent_item_of_annotation_to_invalid_items() {
		$itemKey = API::createItem("book", false, $this, 'key');
		$linkedURLAttachmentKey = API::createAttachmentItem("linked_url", [], $itemKey, $this, 'key');
		
		$attachmentKey = API::createAttachmentItem(
			"imported_url",
			['contentType' => 'application/pdf'],
			null,
			$this,
			'key'
		);
		$jsonData = API::createAnnotationItem('highlight', null, $attachmentKey, $this, 'jsonData');
		
		// No parent
		$json = [
			'version' => $jsonData['version'],
			'parentItem' => false
		];
		$response = API::userPatch(
			self::$config['userID'],
			"items/{$jsonData['key']}",
			json_encode($json)
		);
		$this->assert400($response, "Annotation must have a parent item");
		
		// Regular item
		$json = [
			'version' => $jsonData['version'],
			'parentItem' => $itemKey
		];
		$response = API::userPatch(
			self::$config['userID'],
			"items/{$jsonData['key']}",
			json_encode($json)
		);
		$this->assert400($response, "Parent item of highlight annotation must be a PDF attachment");
		
		// Linked-URL attachment
		$json = [
			'version' => $jsonData['version'],
			'parentItem' => $linkedURLAttachmentKey
		];
		$response = API::userPatch(
			self::$config['userID'],
			"items/{$jsonData['key']}",
			json_encode($json)
		);
		$this->assert400($response, "Parent item of highlight annotation must be a PDF attachment");
	}
	
	
	public function test_deleting_parent_item_should_delete_note_and_embedded_image_attachment() {
		$json = API::createItem("book", false, $this, 'jsonData');
		$itemKey = $json['key'];
		$itemVersion = $json['version'];
		
		// Create embedded-image attachment
		$noteKey = API::createNoteItem(
			'<p>Test</p>', $itemKey, $this, 'key'
		);
		
		// Create image annotation
		$attachmentKey = API::createAttachmentItem(
			'embedded_image', ['contentType' => 'image/png'], $noteKey, $this, 'key'
		);
		
		// Check that all items can be found
		$response = API::userGet(
			self::$config['userID'],
			"items?itemKey=$itemKey,$noteKey,$attachmentKey"
		);
		$this->assertNumResults(3, $response);
		
		$response = API::userDelete(
			self::$config['userID'],
			"items/$itemKey",
			["If-Unmodified-Since-Version: " . $itemVersion]
		);
		$this->assert204($response);
		
		$response = API::userGet(
			self::$config['userID'],
			"items?itemKey=$itemKey,$noteKey,$attachmentKey"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertNumResults(0, $response);
	}
	
	
	public function test_deleting_parent_item_should_delete_attachment_and_annotation() {
		$json = API::createItem("book", false, $this, 'jsonData');
		$itemKey = $json['key'];
		$itemVersion = $json['version'];
		
		$json = API::createAttachmentItem(
			"imported_file", ['contentType' => 'application/pdf'], $itemKey, $this, 'jsonData'
		);
		$attachmentKey = $json['key'];
		$attachmentVersion = $json['version'];
		
		$annotationKey = API::createAnnotationItem(
			'highlight',
			['annotationComment' => 'ccc'],
			$attachmentKey,
			$this,
			'key'
		);
		
		// Check that all items can be found
		$response = API::userGet(
			self::$config['userID'],
			"items?itemKey=$itemKey,$attachmentKey,$annotationKey"
		);
		$this->assertNumResults(3, $response);
		
		$response = API::userDelete(
			self::$config['userID'],
			"items/$itemKey",
			["If-Unmodified-Since-Version: " . $itemVersion]
		);
		$this->assert204($response);
		
		$response = API::userGet(
			self::$config['userID'],
			"items?itemKey=$itemKey,$attachmentKey,$annotationKey"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertNumResults(0, $response);
	}
	
	
	public function test_deleting_user_library_attachment_should_delete_lastPageIndex_setting() {
		$json = API::createAttachmentItem(
			"imported_file", ['contentType' => 'application/pdf'], null, $this, 'jsonData'
		);
		$attachmentKey = $json['key'];
		$attachmentVersion = $json['version'];
		
		$settingKey = "lastPageIndex_u_$attachmentKey";
		$response = API::userPut(
			self::$config['userID'],
			"settings/$settingKey",
			json_encode([
				"value" => 123,
				"version" => 0
			]),
			["Content-Type: application/json"]
		);
		$this->assert204($response);
		
		$response = API::userDelete(
			self::$config['userID'],
			"items/$attachmentKey",
			["If-Unmodified-Since-Version: " . $attachmentVersion]
		);
		$this->assert204($response);
		
		$response = API::userGet(
			self::$config['userID'],
			"settings/$settingKey"
		);
		$this->assert404($response);
		
		// Setting shouldn't be in delete log
		$response = API::userGet(
			self::$config['userID'],
			"deleted?since=$attachmentVersion"
		);
		$json = API::getJSONFromResponse($response);
		$this->assertNotContains($settingKey, $json['settings']);
	}
	
	
	public function test_deleting_group_library_attachment_should_delete_lastPageIndex_setting_for_all_users() {
		$json = API::groupCreateAttachmentItem(
			self::$config['ownedPrivateGroupID'],
			"imported_file",
			['contentType' => 'application/pdf'],
			null,
			$this,
			'jsonData'
		);
		$attachmentKey = $json['key'];
		$attachmentVersion = $json['version'];
		
		//
		// Add setting to both group members
		//
		// Set as user 1
		$settingKey = "lastPageIndex_g" . self::$config['ownedPrivateGroupID'] . "_$attachmentKey";
		$response = API::userPut(
			self::$config['userID'],
			"settings/$settingKey",
			json_encode([
				"value" => 123,
				"version" => 0
			]),
			["Content-Type: application/json"]
		);
		$this->assert204($response);
		
		// Set as user 2
		API::useAPIKey(self::$config['user2APIKey']);
		$response = API::userPut(
			self::$config['userID2'],
			"settings/$settingKey",
			json_encode([
				"value" => 234,
				"version" => 0
			]),
			["Content-Type: application/json"]
		);
		$this->assert204($response);
		
		API::useAPIKey(self::$config['user1APIKey']);
		
		// Delete group item
		$response = API::groupDelete(
			self::$config['ownedPrivateGroupID'],
			"items/$attachmentKey",
			["If-Unmodified-Since-Version: " . $attachmentVersion]
		);
		$this->assert204($response);
		
		//
		// Setting should be gone for both group users
		//
		$response = API::userGet(
			self::$config['userID'],
			"settings/$settingKey"
		);
		$this->assert404($response);
		
		$response = API::superGet(
			"users/" . self::$config['userID2'] . "/settings/$settingKey"
		);
		$this->assert404($response);
	}
	
	
	public function test_should_preserve_createdByUserID_on_undelete() {
		$json = API::groupCreateItem(
			self::$config['ownedPrivateGroupID'], "book", false, $this, 'json'
		);
		$jsonData = $json['data'];
		
		$this->assertEquals($json['meta']['createdByUser']['username'], self::$config['username']);
		
		$response = API::groupDelete(
			self::$config['ownedPrivateGroupID'],
			"items/{$json['key']}",
			["If-Unmodified-Since-Version: " . $json['version']]
		);
		$this->assert204($response);
		
		API::useAPIKey(self::$config['user2APIKey']);
		$jsonData['version'] = 0;
		$response = API::groupPost(
			self::$config['ownedPrivateGroupID'],
			"items",
			json_encode([$jsonData]),
			[
				"Content-Type: application/json"
			]
		);
		$json = API::getJSONFromResponse($response);
		
		// createdByUser shouldn't have changed
		$this->assertEquals(
			$json['successful'][0]['meta']['createdByUser']['username'],
			self::$config['username']
		);
	}
	
	
	public function test_should_return_409_on_missing_parent() {
		$missingParentKey = "BDARG2AV";
		$json = API::createNoteItem("<p>test</p>", $missingParentKey, $this);
		$this->assert409ForObject($json, "Parent item $missingParentKey not found");
		$this->assertEquals($missingParentKey, $json['failed'][0]['data']['parentItem']);
	}
	
	
	public function test_should_return_409_on_missing_parent_if_parent_failed() {
		// Collection
		$collectionKey = API::createCollection("A", null, $this, 'key');
		
		$version = API::getLibraryVersion();
		$parentKey = "BDARG2AV";
		$tag = \Zotero_Utilities::randomString(300);
		
		// Parent item
		$item1JSON = API::getItemTemplate("book");
		$item1JSON->key = $parentKey;
		$item1JSON->creators = [
			[
                "firstName" => "A.",
                "lastName" => "Nespola",
                "creatorType" => "author"
            ]
		];
		$item1JSON->tags = [
			[
				"tag" => "A"
			],
			[
				"tag" => $tag
			]
		];
		$item1JSON->collections = [$collectionKey];
		// Child note
		$item2JSON = API::getItemTemplate("note");
		$item2JSON->parentItem = $parentKey;
		// Child attachment with note
		// TODO: Use template function
		$response = API::get("items/new?itemType=attachment&linkMode=linked_url");
		$item3JSON = json_decode($response->getBody());
		$item3JSON->parentItem = $parentKey;
		$item3JSON->note = "Test";
		
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$item1JSON, $item2JSON, $item3JSON]),
			[
				"Content-Type: application/json",
				"If-Unmodified-Since-Version: $version"
			]
		);
		$this->assert200($response);
		$json = API::getJSONFromResponse($response);
		$this->assert413ForObject($json, null, 0);
		$this->assert409ForObject($json, "Parent item $parentKey not found", 1);
		$this->assertEquals($parentKey, $json['failed'][1]['data']['parentItem']);
		$this->assert409ForObject($json, "Parent item $parentKey not found", 2);
		$this->assertEquals($parentKey, $json['failed'][2]['data']['parentItem']);
	}
	
	
	public function test_should_return_409_on_missing_collection() {
		$missingCollectionKey = "BDARG2AV";
		$json = API::createItem("book", [ 'collections' => [$missingCollectionKey] ], $this);
		$this->assert409ForObject($json, "Collection $missingCollectionKey not found");
		$this->assertEquals($missingCollectionKey, $json['failed'][0]['data']['collection']);
	}
	
	
	public function test_should_return_409_if_a_note_references_a_note_as_a_parent_item() {
		$parentKey = API::createNoteItem("<p>Parent</p>", null, $this, 'key');
		$json = API::createNoteItem("<p>Parent</p>", $parentKey, $this);
		$this->assert409ForObject($json, "Parent item cannot be a note or attachment");
		$this->assertEquals($parentKey, $json['failed'][0]['data']['parentItem']);
	}
	
	
	public function test_should_return_409_if_an_attachment_references_a_note_as_a_parent_item() {
		$parentKey = API::createNoteItem("<p>Parent</p>", null, $this, 'key');
		$json = API::createAttachmentItem("imported_file", [], $parentKey, $this, 'responseJSON');
		$this->assert409ForObject($json, "Parent item cannot be a note or attachment");
		$this->assertEquals($parentKey, $json['failed'][0]['data']['parentItem']);
	}
	
	
	public function test_should_allow_emoji_in_title() {
		$title = "🐶"; // 4-byte character
		
		$key = API::createItem("book", array("title" => $title), $this, 'key');
		
		// Test entry (JSON)
		$response = API::userGet(
			self::$config['userID'],
			"items/$key"
		);
		$this->assertStringContainsString("\"title\": \"$title\"", $response->getBody());
		
		// Test feed (JSON)
		$response = API::userGet(
			self::$config['userID'],
			"items"
		);
		$this->assertStringContainsString("\"title\": \"$title\"", $response->getBody());
		
		// Test entry (Atom)
		$response = API::userGet(
			self::$config['userID'],
			"items/$key?content=json"
		);
		$this->assertStringContainsString("\"title\": \"$title\"", $response->getBody());
		
		// Test feed (Atom)
		$response = API::userGet(
			self::$config['userID'],
			"items?content=json"
		);
		$this->assertStringContainsString("\"title\": \"$title\"", $response->getBody());
	}
}
