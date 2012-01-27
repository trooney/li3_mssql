<?php

/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_mssql\extensions\data\source;

use lithium\data\model\QueryException;
use lithium\util\String;

class MsSql extends \lithium\data\source\Database {

    protected $_classes = array(
        'entity' => 'lithium\data\entity\Record',
        'set' => 'lithium\data\collection\RecordSet',
        'relationship' => 'lithium\data\model\Relationship',
        'result' => 'li3_mssql\extensions\data\source\ms_sql\Result',
    );
    protected $_columns = array(
        'primary_key' => array('name' => 'IDENTITY (1, 1) NOT NULL'),
        'string' => array('name' => 'varchar', 'length' => '255'),
        'text' => array('name' => 'varchar', 'length' => 'max'),
        'integer' => array('name' => 'integer', 'length' => 11, 'formatter' => 'intval'),
        'float' => array('name' => 'float', 'formatter' => 'floatval'),
        'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s.u', 'formatter' => 'date'),
        'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
        'time' => array('name' => 'datetime', 'format' => 'H:i:s', 'formatter' => 'date'),
        'date' => array('name' => 'datetime', 'format' => 'Y-m-d', 'formatter' => 'date'),
        'binary' => array('name' => 'varbinary', 'length' => 'max'),
        'boolean' => array('name' => 'bit')
    );
    protected $_strings = array(
        'read' => "SELECT {:limit} {:fields} FROM {:source} {:alias}
            {:joins} {:conditions} {:group} {:order} {:comment}",
        'paged' => "SELECT * FROM (
            SELECT {:fields}, ROW_NUMBER() OVER({:order}) AS [__LI3_ROW_NUMBER__]
            FROM {:source} {:alias} {:joins} {:conditions} {:group}
            ) a {:limit} {:comment}",
        'create' => "INSERT INTO {:source} ({:fields}) VALUES ({:values}) {:comment}",
        'update' => "UPDATE {:source} SET {:fields} {:conditions} {:comment}",
        'delete' => "DELETE {:flags} FROM {:source} {:aliases} {:conditions} {:comment}",
        'schema' => "CREATE TABLE {:source} (\n{:columns}\n) {:indexes} {:comment}",
        'join' => "{:type} JOIN {:source} {:alias} {:constraint}"
    );
    protected $_quotes = array("[", "]");

	protected $_is64bit = null;

    public function __construct(array $config = array()) {
	    // @todo hack to detect 64-bit machines, used in quoting int values later
	    $this->_is64bit = (intval("9223372036854775807") === 9223372036854775807);

        $defaults = array(
	        'host' => 'localhost',
	        'port' => '1433'
		);
        parent::__construct($config + $defaults);
    }

    public static function enabled($feature = null) {
        if (!$feature) {
            return extension_loaded('mssql');
        }
        $features = array(
            'arrays' => false,
            'transactions' => false,
            'booleans' => true,
            'relationships' => true,
        );
        return isset($features[$feature]) ? $features[$feature] : null;
    }

    public function connect() {
        $config = $this->_config;
        $this->_isConnected = false;
        $host = $config['host'];
	    $port = $config['port'];
	    $separator = (PHP_OS == 'Windows') ? ',' : ':';

        if (!$config['database']) {
            return false;
        }

        $this->connection = mssql_connect($host . $separator . $port, $config['login'], $config['password'], true);

        if (!$this->connection) {
            return false;
        }

        if (mssql_select_db($config['database'], $this->connection)) {
            $this->_isConnected = true;
        } else {
            return false;
        }

        return $this->_isConnected;
    }

    public function disconnect() {
        if ($this->_isConnected) {
            $this->_isConnected = !mssql_close($this->connection);
            return!$this->_isConnected;
        }
        return true;
    }

    public function sources($model = null) {
        $_config = $this->_config;
        $params = compact('model');

        return $this->_filter(__METHOD__, $params, function($self, $params) use ($_config) {
                    $name = $self->name($_config['database']);

                    if (!$result = $self->invokeMethod('_execute', array("SELECT TABLE_NAME FROM [INFORMATION_SCHEMA].[TABLES];"))) {
                        return null;
                    }
                    $entities = array();

                    while ($data = $result->next()) {
                        list($entities[]) = $data;
                    }

                    return $entities;
                });
    }

    public function describe($entity, array $meta = array()) {
        $params = compact('entity', 'meta');
        return $this->_filter(__METHOD__, $params, function($self, $params, $chain) {
                    extract($params);

                    $name = $self->invokeMethod('_entityName', array($entity));
                    $sql = "SELECT COLUMN_NAME as Field, DATA_TYPE as Type, "
                            . "COL_LENGTH('{$name}', COLUMN_NAME) as Length, IS_NULLABLE As [Null], "
                            . "COLUMN_DEFAULT as [Default], "
                            . "COLUMNPROPERTY(OBJECT_ID('{$name}'), COLUMN_NAME, 'IsIdentity') as [Key], "
                            . "NUMERIC_SCALE as Size FROM INFORMATION_SCHEMA.COLUMNS "
                            . "WHERE TABLE_NAME = '{$name}'";

                    $columns = $self->read($sql, array('return' => 'array'));
                    $fields = array();

                    foreach ($columns as $column) {
                        $fields[$column['Field']] = array(
                            'type' => $column['Type'],
                            'length' => $column['Length'],
                            'null' => ($column['Null'] == 'YES' ? true : false),
                            'default' => $column['Default'],
                            'key' => ($column['Key'] == 1 ? 'primary' : null)
                        );
                    }


                    return $fields;
                });
    }

    public function encoding($encoding = null) {
        return true;
    }

    public function value($value, array $schema = array()) {
        if (($result = parent::value($value, $schema)) !== null) {
            // @todo Hack for 32-bit integers
            if (!$this->_is64bit && is_string($value) && preg_match('/[0-9]/', $value)) {
                $result = (strval(intval($value)) == $value) ? intval($value) : strval($value);
            }
            return $result;
        }
	    return "'" . $this->_mssql_escape_string((string)$value) . "'"; // original
    }

	// Fugly escaping that doesn't work quite right.
	protected function _mssql_escape_string($data = '') {
		if (is_numeric($data)) return $data;

		$non_displayables = array(
			'/%0[0-8bcef]/', // url encoded 00-08, 11, 12, 14, 15
			'/%1[0-9a-f]/', // url encoded 16-31
			'/[\x00-\x08]/', // 00-08
			'/\x0b/', // 11
			'/\x0c/', // 12
			'/[\x0e-\x1f]/' // 14-31
		);

		foreach ($non_displayables as $regex)
			$data = preg_replace($regex, '', $data);
		$data = str_replace("'", "''", $data);
		return $data;
	}


    public function schema($query, $resource = null, $context = null) {
        if (is_object($query)) {
            return parent::schema($query, $resource, $context);
        }
        $result = array();
        $count = mssql_num_fields($resource->resource());

        for ($i = 0; $i < $count; $i++) {
            $result[] = mssql_field_name($resource->resource(), $i);
        }
        return $result;
    }

    public function limit($limit, $context) {
        $offset = $context->offset() ? : 0;

        if ($limit === NULL) {
            return null;
        }

        if ($offset === 0) {
            return "TOP {$limit}";
        }

        $limit += $offset++;

        return "WHERE [__LI3_ROW_NUMBER__] between {$offset} and {$limit}";
    }

    public function error() {
        if (mssql_get_last_message()) { //&& strstr(mssql_get_last_message(), 'hanged database context to')) {
            return array(0, \mssql_get_last_message());
        }
        return null;
    }

    public function alias($alias, $context) {
        if ($context->type() == 'update' || $context->type() == 'delete') {
            return;
        }
        return parent::alias($alias, $context);
    }

    public function create($query, array $options = array()) {

        if (is_object($query)) {
            $table = $query->source();
	        $model = $query->model();
	        $key = $model ? $model::key() : false;
	        $fields = array_keys($query->data());
			if (in_array($key, $fields)) {
				$this->_execute("Set IDENTITY_INSERT [dbo].[{$table}] On");
			}

        }
        return parent::create($query, $options);
    }

    protected function _execute($sql, array $options = array()) {
        $defaults = array();
        $options += $defaults;
        mssql_select_db($this->_config['database'], $this->connection);

        return $this->_filter(__METHOD__, compact('sql', 'options'), function($self, $params) {
                    $sql = $params['sql'];
                    $options = $params['options'];

//	                // @todo ugly hack to strip out primary key
//					if (isset($options['key'])) {
//						$key = $options['key'];
//						$sql = preg_replace("/\[" . $key . "\]\s=\s'(\d+)',/ ", '', $sql);
//					}

                    $resource = mssql_query($sql, $self->connection);

                    if ($resource === true) {
                        return true;
                    }
                    if (is_resource($resource)) {
                        return $self->invokeMethod('_instance', array('result', compact('resource')));
                    }
                    list($code, $error) = $self->error();
                    throw new QueryException("{$sql}: {$error}", $code);
                });
    }

    protected function _results($results) {
        $numFields = mssql_num_fields($results);
        $index = $j = 0;

        while ($j < $numFields) {
            $column = mssql_fetch_field($results, $j);
            $name = $column->name;
            $table = $column->table;
            $this->map[$index++] = empty($table) ? array(0, $name) : array($table, $name);
            $j++;
        }
    }

	/**
	 * Gets the last auto-generated ID from the query that inserted a new record.
	 *
	 * @param object $query The `Query` object associated with the query which generated
	 * @return mixed Returns the last inserted ID key for an auto-increment column or a column
	 *         bound to a sequence.
	 */
	protected function _insertId($query) {
		$resource = $this->_execute('SELECT @@identity as insertId');
		list($id) = $resource->next();
		return ($id && $id !== '0') ? $id : null;
	}

    protected function _column($real) {
        if (is_array($real)) {
            $length = '';
            if (isset($real['length'])) {
                $length = $real['length'];
                if ($length === -1) {
                    $length = 'max';
                }
                $length = '(' . $length . ')';
            }
            return $real['type'] . $length;
        }

        if (!preg_match('/(?P<type>[^(]+)(?:\((?P<length>[^)]+)\))?/', $real, $column)) {
            return $real;
        }
        $column = array_intersect_key($column, array('type' => null, 'length' => null));

        switch (true) {
            case $column['type'] === 'datetime':
                break;
            case ($column['type'] == 'tinyint' && $column['length'] == '1'):
            case ($column['type'] == 'bit'):
                $column = array('type' => 'boolean');
                break;
            case (strpos($column['type'], 'int') !== false):
                $column['type'] = 'integer';
                break;
            case (strpos($column['type'], 'text') !== false):
                $column['type'] = 'text';
                break;
            case strpos($column['type'], 'char') !== false:
                if (isset($column['length']) && $column['length'] === 'max') {
                    $column['type'] = 'text';
                    unset($column['length']);
                } else {
                    $column['type'] = 'string';
                }
                break;
            case (strpos($column['type'], 'binary') !== false || $column['type'] == 'image'):
                $column['type'] = 'binary';
                break;
            case preg_match('/float|double|decimal/', $column['type']):
                $column['type'] = 'float';
                $length = intval($column['length']);
                $precision = intval(preg_replace('/(.+),/', '', $column['length']));
                unset($column['length']);
				$column += compact('length', 'precision');
                break;
            default:
                $column['type'] = 'text';
                break;
        }
        return $column;
    }

	public function update($query, array $options = array()) {
		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			$query = $params['query'];

			// @todo hack to strip primary key from query
			$whitelist = $query->whitelist();
			unset($whitelist[$query->key()]);
			$query->whitelist($whitelist);

			$params = $query->export($self);

			$sql = $self->renderCommand('update', $params);

			if ($self->invokeMethod('_execute', array($sql))) {
				if ($query->entity()) {
					$query->entity()->sync();
				}
				return true;
			}
			return false;
		});
	}
}

?>