<?php

/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_MsSql\tests\cases\extensions\data\source;

use \lithium\data\Connections;
use \li3_mssql\extensions\data\source\MsSql;
use \li3_mssql\tests\mocks\extensions\data\source\MockMsSql;
use \lithium\data\model\Query;

class MsSqlTest extends \lithium\test\Unit {

    protected $_dbConfig = array();
    /**
     * Instance of the SqlSrv adapter
     *
     * @var object
     */
    public $db = null;

    /**
     * Skip the test if a MsSql adapter configuration is unavailable.
     *
     * @return void
     * @todo Tie into the Environment class to ensure that the test database is being used.
     */

	public function skip() {
		$this->_dbConfig = Connections::get('test', array('config' => true));
		$isAvailable = (
				$this->_dbConfig &&
						Connections::get('test')->isConnected(array('autoConnect' => true))
		);
		$this->skipIf(!$isAvailable, "No test connection available.");

		$isDatabase = Connections::get('test') instanceof \lithium\data\source\Database;
		$this->skipIf(!$isDatabase, "The 'test' connection is not a relational database.");

		$this->db = Connections::get('test');
		$mockBase = LITHIUM_LIBRARY_PATH . '/li3_mssql/tests/mocks/extensions/data/source/database/adapter/';
		$files = array('companies' => '_companies.sql');
		$files = array_diff_key($files, array_flip($this->db->sources()));

		foreach ($files as $file) {
			$sqlFile = $mockBase . strtolower($this->_dbConfig['adapter']) . $file;
			$this->skipIf(!file_exists($sqlFile), "SQL file $sqlFile does not exist.");
			$sql = file_get_contents($sqlFile);
			$this->db->read($sql, array('return' => 'resource'));
		}
	}

	public function setUp() {
        $this->db = new MsSql($this->_dbConfig);
    }

    /**
     * Tests that the object is initialized with the correct default values.
     *
     * @return void
     */
    public function testConstructorDefaults() {
        $db = new MockMsSql(array('autoConnect' => false, 'port' => 1433));
        $result = $db->get('_config');
        $expected = array(
            'autoConnect' => false,
            'port' => 1433,
            'persistent' => true,
            'host' => 'localhost',
            'login' => 'root',
            'password' => '',
            'database' => NULL,
            'init' => true,
        );
        $this->assertEqual($expected, $result);
    }

    /**
     * Tests that this adapter can connect to the database, and that the status is properly
     * persisted.
     *
     * @return void
     */
    public function testDatabaseConnection() {
        $db = new MsSql(array('autoConnect' => false) + $this->_dbConfig);
        $this->assertTrue($db->connect());
        $this->assertTrue($db->isConnected());

        $this->assertTrue($db->disconnect());
        $this->assertFalse($db->isConnected());
    }


    /*******************************************************************/

	public function testDatabaseEncoding() {
        $this->assertTrue($this->db->isConnected());
//        $this->assertTrue($this->db->encoding('utf8'));
//        $this->assertEqual('UTF-8', $this->db->encoding());
//
//        $this->assertTrue($this->db->encoding('UTF-8'));
//        $this->assertEqual('UTF-8', $this->db->encoding());
    }

    public function testValueByIntrospect() {
        $expected = "'string'";
        $result = $this->db->value("string");
        $this->assertTrue(is_string($result));
        $this->assertEqual($expected, $result);

        // @todo Escaping fails
        $expected = "'''this string is escaped'''";
        $result = $this->db->value("'this string is escaped'");
        $this->assertTrue(is_string($result));
        $this->assertEqual($expected, $result);

        $this->assertIdentical(1, $this->db->value(true));
        $this->assertIdentical(1, $this->db->value('1'));
        $this->assertIdentical(1.1, $this->db->value('1.1'));
    }   

    public function testColumnAbstraction() {
        $result = $this->db->invokeMethod('_column', array('varchar'));
        $this->assertIdentical(array('type' => 'string'), $result);

        $result = $this->db->invokeMethod('_column', array('tinyint(1)'));
        $this->assertIdentical(array('type' => 'boolean'), $result);

        $result = $this->db->invokeMethod('_column', array('varchar(255)'));
        $this->assertIdentical(array('type' => 'string', 'length' => '255'), $result);

        $result = $this->db->invokeMethod('_column', array('text'));
        $this->assertIdentical(array('type' => 'text'), $result);

        $result = $this->db->invokeMethod('_column', array('text'));
        $this->assertIdentical(array('type' => 'text'), $result);

        // @todo Decimals fail
        $result = $this->db->invokeMethod('_column', array('decimal(12,2)'));
        $this->assertIdentical(array('type' => 'float', 'length' => 12, 'precision' => 2), $result);

        $result = $this->db->invokeMethod('_column', array('int(11)'));
        $this->assertIdentical(array('type' => 'integer', 'length' => '11'), $result);
    }

    public function testRawSqlQuerying() {
        $this->assertTrue($this->db->create(
                'INSERT INTO companies (name, active) VALUES (?, ?)', array('Test', 1)
            ));

        $result = $this->db->read('SELECT * FROM companies AS Company WHERE name = {:name}', array(
                'name' => 'Test',
                'return' => 'array'
            ));
        $this->assertEqual(1, count($result));
        $expected = array('id', 'name', 'active', 'created', 'modified');
        $this->assertEqual($expected, array_keys($result[0]));

        $this->assertTrue(is_numeric($result[0]['id']));
        unset($result[0]['id']);

        $expected = array('name' => 'Test', 'active' => 1, 'created' => null, 'modified' => null);
        $this->assertIdentical($expected, $result[0]);

        $this->assertTrue($this->db->delete('DELETE FROM companies WHERE name = {:name}', array(
                'name' => 'Test'
            )));

        $result = $this->db->read('SELECT * FROM companies AS Company WHERE name = {:name}', array(
                'name' => 'Test',
                'return' => 'array'
            ));
        $this->assertFalse($result);
    }

    public function testAbstractColumnResolution() {
        
    }

    public function testDescribe() {
        
    }

    public function testExecuteException() {
        // @todo Execute Exception fails
//        $this->expectException();
//        $this->db->read('SELECT deliberate syntax error');
    }

    public function testEnabledFeatures() {
        $this->assertTrue(MsSql::enabled());
        $this->assertTrue(MsSql::enabled('relationships'));
        $this->assertFalse(MsSql::enabled('arrays'));
    }

    public function testSourcesQuerying() {
        $sources = $this->db->sources();
        $this->assertTrue(is_array($sources));
        $this->assertFalse(empty($sources));
    }

    public function testQueryOrdering() {
        $insert = new Query(array(
                'type' => 'create',
                'source' => 'companies',
                'data' => array(
                    'name' => 'Foo',
                    'active' => true,
                    'created' => date('Y-m-d H:i:s')
                )
            ));
        $this->assertIdentical(true, $this->db->create($insert));

        $insert->data(array(
            'name' => 'Bar',
            'created' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
        ));
        $this->assertIdentical(true, $this->db->create($insert));

        $insert->data(array(
            'name' => 'Baz',
            'created' => date('Y-m-d H:i:s', strtotime('-10 minutes'))
        ));
        $this->assertIdentical(true, $this->db->create($insert));

        $read = new Query(array(
                'type' => 'read',
                'source' => 'companies',
                'fields' => array('name'),
                'order' => array('created' => 'asc')
            ));
        $result = $this->db->read($read, array('return' => 'array'));
        $expected = array(
            array('name' => 'Baz'),
            array('name' => 'Bar'),
            array('name' => 'Foo')
        );
        $this->assertEqual($expected, $result);

        $read->order(array('created' => 'desc'));
        $result = $this->db->read($read, array('return' => 'array'));
        $expected = array(
            array('name' => 'Foo'),
            array('name' => 'Bar'),
            array('name' => 'Baz')
        );
        $this->assertEqual($expected, $result);

        $delete = new Query(array('type' => 'delete', 'source' => 'companies'));
        $this->assertTrue($this->db->delete($delete));
    }

    /**
     * Ensures that DELETE queries are not generated with table aliases, as MsSql does not support
     * this.
     *
     * @return void
     */
    public function testDeletesWithoutAliases() {
        $delete = new Query(array('type' => 'delete', 'source' => 'companies'));
        $this->assertTrue($this->db->delete($delete));
    }
    

}

?>