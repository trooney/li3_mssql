<?php
namespace li3_mssql\tests\integration\extensions\data;
/*
 * \lithium\tests\integration\data\CrudTest
 *
 */


use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\tests\mocks\data\source\Images;
use lithium\tests\mocks\data\source\Galleries;
use lithium\util\String;

class DatabaseTest extends \lithium\tests\integration\data\DatabaseTest {

	public function skip() {
		$this->_dbConfig = Connections::get('test-mssql', array('config' => true));
		$isAvailable = (
				$this->_dbConfig &&
						Connections::get('test-mssql')->isConnected(array('autoConnect' => true))
		);
		$this->skipIf(!$isAvailable, "No test connection available.");

		$isDatabase = Connections::get('test-mssql') instanceof \lithium\data\source\Database;
		$this->skipIf(!$isDatabase, "The 'test-mssql' connection is not a relational database.");

		$this->db = Connections::get('test-mssql');

		$mockBase = LITHIUM_LIBRARY_PATH . '/li3_mssql/tests/mocks/data/source/database/adapter/';
		$files = array('galleries' => '_galleries.sql', 'images' => '_images.sql');
		$files = array_diff_key($files, array_flip($this->db->sources()));

		foreach ($files as $file) {
			$sqlFile = $mockBase . strtolower($this->_dbConfig['adapter']) . $file;
			$this->skipIf(!file_exists($sqlFile), "SQL file $sqlFile does not exist.");
			$sql = file_get_contents($sqlFile);
			$this->db->read($sql, array('return' => 'resource'));
		}

	}

}