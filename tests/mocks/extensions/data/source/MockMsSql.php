<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 * @author          
 */

namespace li3_mssql\tests\mocks\extensions\data\source;

class MockMsSql extends \li3_mssql\extensions\data\source\MsSql {

	public function get($var) {
		return $this->{$var};
	}
}

?>