<?php
namespace li3_mssql\tests\integration\extensions\data;

use lithium\data\Connections;
use lithium\tests\mocks\data\Companies;

//class CrudTest extends \lithium\test\Integration {
class CrudTest extends \lithium\tests\integration\data\CrudTest {

	protected $_connection = null;

	protected $_key = null;

	public function setUp() {
		Companies::config();
		$this->_key = Companies::key();
		$this->_connection = Connections::get('test-mssql');
	}

	/**
	 * Skip the test if no test database connection available.
	 *
	 * @return void
	 */
	public function skip() {
		$isAvailable = (
				Connections::get('test-mssql', array('config' => true)) &&
						Connections::get('test-mssql')->isConnected(array('autoConnect' => true))
		);
		$this->skipIf(!$isAvailable, "No test connection available.");
	}

	public function testUpdateWithNewProperties() {
		// This isn't necessary in a db test
	}

}
