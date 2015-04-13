<?php
/******************************************************

  This file is part of OpenWebSoccer-Sim.

  OpenWebSoccer-Sim is free software: you can redistribute it 
  and/or modify it under the terms of the 
  GNU Lesser General Public License 
  as published by the Free Software Foundation, either version 3 of
  the License, or any later version.

  OpenWebSoccer-Sim is distributed in the hope that it will be
  useful, but WITHOUT ANY WARRANTY; without even the implied
  warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
  See the GNU Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public 
  License along with OpenWebSoccer-Sim.  
  If not, see <http://www.gnu.org/licenses/>.

******************************************************/

/**
 * Data Base Connection class.
 * 
 * @author Ingo Hofmann
 */
class DbConnection {

	/**
	 * @var mysqli MySQLi connection instance.
	 */
	public $connection;
	
	private static $_instance;
	private $_queryCache;
	
	/**
	 * @return the only instance during current request.
	 */
	public static function getInstance() {
		if(self::$_instance == NULL) {
			self::$_instance = new DbConnection();
		}
		return self::$_instance;
	}
	
	// hide constructor (Singleton implementation)
	private function __construct() {
	}

	/**
	 * Connects to a data base and keeps connection open until end of script execution.
	 * 
	 * @param string $host DB host
	 * @param string $user DB user
	 * @param string $password DB password
	 * @param string $dbname DB name
	 * @throws Exception if connecting failed.
	 */
	public function connect($host, $user, $password, $dbname) {
		@$this->connection = new mysqli($host, $user, $password, $dbname);
		@$this->connection->set_charset('utf8');
		
		if (mysqli_connect_error()) {
			throw new Exception('Database Connection Error (' . mysqli_connect_errno() . ') '. mysqli_connect_error());
		}
	}
	
	/**
	 * Close the connection.
	 */
	public function close() {
		$this->connection->close();
	}
	
	/**
	 * Executes an SQL SELECT statement. Parameter values will be escaped against SQL injection.
	 * 
	 * @param sring|array $columns List of columns to select. Either a string or as an assoc. array with key=DB column name, value=alias name.
	 * @param string $fromTable Name of table to select, including prefix. JOINs can be appended.
	 * @param string $whereCondition Part that comes after keyworkd WHERE in query; 
	 * 			parameters shall be added with placeholders with format as defined at {@link http://php.net/manual/en/function.sprintf.php sprintf()}
	 * @param string|array $parameters one dimensional array of parameters which will be escaped or a single value if the WHERE part contains only one parameter.
	 * @param string|int $limit Maximum number of items to return. Can also include start index for paging.
	 * @return mysqli_result|false mysqli_result instance or false on failure.
	 */
	public function querySelect($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) {
		
		$queryStr = $this->buildQueryString($columns, $fromTable, $whereCondition, $parameters, $limit);
		return $this->executeQuery($queryStr);
	}
	
	/**
	 * Executes an SQL SELECT statement, in case the same query has not been executed within the same page request before. 
	 * Stores results in a memory cache and returns results from it in case query gets called twice.
	 * The return value is always a two dimensional array, unlike at querySelect().
	 * Parameter values will be escaped against SQL injection.
	 *
	 * @param sring|array $columns List of columns to select. Either a string or as an assoc. array with key=DB column name, value=alias name.
	 * @param string $fromTable Name of table to select, including prefix. JOINs can be appended.
	 * @param string $whereCondition Part that comes after keyworkd WHERE in query;
	 * 			parameters shall be added with placeholders with format as defined at {@link http://php.net/manual/en/function.sprintf.php sprintf()}
	 * @param string|array $parameters one dimensional array of parameters which will be escaped or a single value if the WHERE part contains only one parameter.
	 * @param string|int $limit Maximum number of items to return. Can also include start index for paging.
	 * @return array Two dimensional array of result items. First dimension is rows, second is array of column values.
	 */
	public function queryCachedSelect($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) {
		$queryStr = $this->buildQueryString($columns, $fromTable, $whereCondition, $parameters, $limit);
		
		// get result from cache
		if (isset($this->_queryCache[$queryStr])) {
			return $this->_queryCache[$queryStr];
		}
		
		// build result list
		$result = $this->executeQuery($queryStr);
		$rows = array();
		while ($row = $result->fetch_array()) {
			$rows[] = $row;
		}
		$result->free();
		$this->_queryCache[$queryStr] = $rows;
		return $rows;
	}
	
	/**
	 * Executes an SQL UPDATE statement. Parameter values will be escaped against SQL injection.
	 *
	 * @param array $columns List of columns to update. Array with key=DB column name, value=new value to store.
	 * @param string $fromTable Name of table to update, including prefix. JOINs can be appended.
	 * @param string $whereCondition Part that comes after keyworkd WHERE in query;
	 * 			parameters shall be added with placeholders with format as defined at {@link http://php.net/manual/en/function.sprintf.php sprintf()}
	 * @param string|array $parameters one dimensional array of parameters which will be escaped or a single value if the WHERE part contains only one parameter.
	 */
	public function queryUpdate($columns, $fromTable, $whereCondition, $parameters) {
		$queryStr = 'UPDATE ' . $fromTable . ' SET ';
		
		$queryStr = $queryStr . self::buildColumnsValueList($columns);
		
		// WHERE
		$queryStr = $queryStr . ' WHERE ';
		$wherePart = self::buildWherePart($whereCondition, $parameters);
		$queryStr = $queryStr . $wherePart;
		
		$this->executeQuery($queryStr);
		
		// refresh cache
		$this->_queryCache = array();
	}
	
	/**
	 * Executes an SQL DELETE statement. Parameter values will be escaped against SQL injection.
	 *
	 * @param string $fromTable Name of table where to delete, including prefix. JOINs can be appended.
	 * @param string $whereCondition Part that comes after keyworkd WHERE in query;
	 * 			parameters shall be added with placeholders with format as defined at {@link http://php.net/manual/en/function.sprintf.php sprintf()}
	 * @param string|array $parameters one dimensional array of parameters which will be escaped or a single value if the WHERE part contains only one parameter.
	 */
	public function queryDelete($fromTable, $whereCondition, $parameters) {
		$queryStr = 'DELETE FROM ' . $fromTable;
		
		// WHERE
		$queryStr = $queryStr . ' WHERE ';
		$wherePart = self::buildWherePart($whereCondition, $parameters);
		$queryStr = $queryStr . $wherePart;
		
		$this->executeQuery($queryStr);
		
		// refresh cache
		$this->_queryCache = array();
	}	
	
	/**
	 * Executes an SQL INSERT statement. Parameter values will be escaped against SQL injection.
	 *
	 * @param array $columns List of columns with values to store in new item. Array with key=DB column name, value=new value to store.
	 * @param string $fromTable Name of table to select, including prefix.
	 */
	public function queryInsert($columns, $fromTable) {
		$queryStr = 'INSERT ' . $fromTable . ' SET ';
		
		$queryStr = $queryStr . $this->buildColumnsValueList($columns);
		
		$this->executeQuery($queryStr);
	}
	
	/**
	 * @return int last inserted ID.
	 */
	public function getLastInsertedId() {
		return $this->connection->insert_id;
	}
	
	private function buildQueryString($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) {
		$queryStr = 'SELECT ';
		
		// columns
		if (is_array($columns)) {
				
			$firstColumn = TRUE;
			foreach($columns as $dbName => $aliasName) {
				if (!$firstColumn) {
					$queryStr = $queryStr .', ';
				} else {
					$firstColumn = FALSE;
				}
				if (is_numeric($dbName)) {
					$dbName = $aliasName;
				}
		
				$queryStr = $queryStr . $dbName. ' AS '. $aliasName;
			}
		} else {
			$queryStr = $queryStr . $columns;
		}
		
		// FROM
		$queryStr = $queryStr . ' FROM ' . $fromTable . ' WHERE ';
		
		// WHERE
		$wherePart = self::buildWherePart($whereCondition, $parameters);
		
		// add limit
		if (!empty($limit)) {
			$wherePart = $wherePart . ' LIMIT ' . $limit;
		}
		$queryStr = $queryStr . $wherePart;
		return $queryStr;
	}
	
	private function buildColumnsValueList($columns) {
		$queryStr = '';
	
		$firstColumn = TRUE;
		foreach($columns as $dbName => $value) {
			if (!$firstColumn) {
				$queryStr = $queryStr . ', ';
			} else {
				$firstColumn = FALSE;
			}
			
			if (strlen($value)) {
				$columnValue = '\'' . $this->connection->real_escape_string($value) .'\'';
			} else {
				$columnValue = 'DEFAULT';
			}
			
			
			$queryStr = $queryStr . $dbName . '=' . $columnValue;
		}
		
		return $queryStr;
	}
	
	private function buildWherePart($whereCondition, $parameters) {
		$maskedParameters = self::prepareParameters($parameters);
		
		return vsprintf($whereCondition, $maskedParameters);
	}
	
	private function prepareParameters($parameters) {
		if(!is_array($parameters)){
			$parameters = array($parameters);
		}
		// mask input
		$arrayLength = count($parameters);
		for($i = 0; $i < $arrayLength; $i++) {
			$parameters[$i] = $this->connection->real_escape_string(trim($parameters[$i]));
		}
		
		return $parameters;
	}
	
	/**
	 * Queries a DB query, without escaping, caching or mapping.
	 * 
	 * @param string $queryStr Executed the specified query.
	 * @throws Exception if error occured.
	 * @return mixed result of mysqli query
	 */
	public function executeQuery($queryStr) {
		$queryResult = $this->connection->query($queryStr);
		
		if (!$queryResult) {
			throw new Exception('Database Query Error: ' . $this->connection->error);
		}
		
		return $queryResult;
	}

}
?>