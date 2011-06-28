<?php

/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_mssql\extensions\data\source\ms_sql;

class Result extends \lithium\data\source\database\Result {

    public function prev() {
        if ($this->_current = $this->_prev()) {
            $this->_iterator--;
            return $this->_current;
        }
    }

    protected function _prev() {
        if ($this->_resource && $this->_iterator) {
            if (mssql_data_seek($this->_resource, $this->_iterator - 1)) {
                return mssql_data_seek($this->_resource, $this->_iterator - 1);
            }
        }
    }

    protected function _next() {
        if ($this->_resource) {
            $inRange = $this->_iterator < mssql_num_rows($this->_resource);
            if ($inRange && mssql_data_seek($this->_resource, $this->_iterator)) {
                return mssql_fetch_row($this->_resource);
            }
        }
    }

    protected function _close() {
        if ($this->_resource) {
            mssql_free_result($this->_resource);
            $this->_resource = null;
        }
    }

}
?>