<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_mssql\tests\integration\extensions\data;

use \Exception;
use \ArrayAccess;
use \lithium\data\Connections;
//use \lithium\tests\integration\data\Company;

class SourceTest extends \lithium\tests\integration\data\SourceTest {

	/**
	 * Tests that a single record with a manually specified primary key can be created, persisted
	 * to an arbitrary data store, re-read and updated.
	 *
	 * @return void
	 */
	public function testSingleReadWriteWithKey() {
//		Company::invokeMethod('_connection')->invokeMethod('_execute', array(
//				'SET IDENTITY_INSERT ' . Company::meta('source') . ' ON')
//		);
//
//		$key = Company::meta('key');
//
//		$new = Company::create(array($key => 12345, 'name' => 'Acme, Inc.'));
//
//		$result = $new->data();
//		$expected = array($key => 12345, 'name' => 'Acme, Inc.');
//		$this->assertEqual($expected[$key], $result[$key]);
//		$this->assertEqual($expected['name'], $result['name']);
//
//		$this->assertFalse($new->exists());
//		$this->assertTrue($new->save());
//		$this->assertTrue($new->exists());
//
//		$existing = Company::find(12345);
//		$result = $existing->data();
//		$this->assertEqual($expected[$key], $result[$key]);
//		$this->assertEqual($expected['name'], $result['name']);
//		$this->assertTrue($existing->exists());
//
//		$existing->name = 'Big Brother and the Holding Company';
//		$this->assertTrue($existing->save());
//
//		$existing = Company::find(12345);
//		$result = $existing->data();
//		$expected['name'] = 'Big Brother and the Holding Company';
//		$this->assertEqual($expected[$key], $result[$key]);
//		$this->assertEqual($expected['name'], $result['name']);
//
//		$existing->delete();
//
//		Company::invokeMethod('_connection')->invokeMethod('_execute', array(
//				'SET IDENTITY_INSERT ' . Company::meta('source') . ' OFF')
//		);
	}
}

?>