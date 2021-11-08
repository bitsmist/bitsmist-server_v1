<?php
// =============================================================================
/**
 * Bitsmist - PHP WebAPI Server Framework
 *
 * @copyright		Masaki Yasutake
 * @link			https://bitsmist.com/
 * @license			https://github.com/bitsmist/bitsmist/blob/master/LICENSE
 */
// =============================================================================

namespace Bitsmist\v1\Plugins\Db;

use Bitsmist\v1\Plugins\Db\BaseDb;
use PDO;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * PDO database class.
 */
class PdoDb extends BaseDb
{

	// -------------------------------------------------------------------------
    //  Constructor, Destructor
    // -------------------------------------------------------------------------

    public function __construct($options)
    {

		parent::__construct($options);
        $this->props["dbType"] = "PDO";

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function open($setting = null)
	{

		$this->logger->debug(
			"dsn = {dsn}, user = {user}, password = {password}", ["method"=>__METHOD__, "dsn"=>$this->props["dsn"], "user"=>$this->props["user"], "password"=>substr($this->props["password"], 0, 1) . "*******"]
		);

		$this->props["connection"] = new PDO($this->props["dsn"], $this->props["user"], $this->props["password"], [
			PDO::ATTR_ERRMODE				=> PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES		=> false,
			PDO::ATTR_PERSISTENT			=> true,
		]);

		return $this->props["connection"];

	}

    // -------------------------------------------------------------------------

	public function close()
	{

		if ($this->props["connection"]){
			$this->props["connection"] = null;

			$this->logger->debug("dsn = {dsn}", ["method"=>__METHOD__, "dsn"=>$this->props["dsn"]]);
		}

	}

    // -------------------------------------------------------------------------

	public function beginTrans()
	{

		$this->props["connection"]->beginTransaction();

		$this->logger->debug("", ["method"=>__METHOD__]);

	}

    // -------------------------------------------------------------------------

	public function commitTrans()
	{

		$this->props["connection"]->commit();

		$this->logger->debug("", ["method"=>__METHOD__]);

	}

    // -------------------------------------------------------------------------

	public function rollbackTrans()
	{

		$this->props["connection"]->rollBack();

		$this->logger->debug("", ["method"=>__METHOD__]);

	}

    // -------------------------------------------------------------------------

	public function getData($cmd, $params = null)
	{

		$this->logger->info("query = {query}", ["method"=>__METHOD__, "query"=>$cmd->queryString]);

		// Bind params
		$this->assignParams($cmd, $params);

		// Get data
		$cmd->execute();
		$records = $cmd->fetchAll();

		return $records;

	}

    // -------------------------------------------------------------------------

	public function getTotalCount()
	{

		$result = 0;

		$this->logger->debug("query = {query}", ["method"=>__METHOD__, "query"=>$this->props["totalCountSql"]]);

		$cmd = $this->createCommand($this->props["totalCountSql"]);
		$this->assignParams($cmd, $this->props["totalCountParams"]);
		$cmd->execute();
		$records = $cmd->fetchAll();
		$result = $records[0]["COUNT(*)"];

		return $result;

	}

    // -------------------------------------------------------------------------

	public function execute($cmd, $params = null)
	{

		$this->logger->info("query = {query}", ["method"=>__METHOD__, "query"=>$cmd->queryString]);

		// Bind params
		$this->assignParams($cmd, $params);

		// Execute
		$cnt = (int)$cmd->execute();

		return $cnt;

	}

    // -------------------------------------------------------------------------

	public function buildQuery($query, $fields = "*", $keys = null, $order = null, $limit = null, $offset = null)
	{

		$params = null;
		$sql = $query;

		$sqlFields =  ( $limit ? $this->getCountField() . " " : "" ) . $fields;

		list($sqlWhere, $params) = $this->buildQueryWhere($keys);
		if ($sqlWhere)
		{
			$sqlWhere = "WHERE " . $sqlWhere;
		}

		$sqlOrder = $this->buildQueryOrder($order);
		if ($sqlOrder)
		{
			$sqlOrder = "ORDER BY " . $sqlOrder;
		}

		if ($limit || $offset)
		{
			$sqlPagination = $this->buildQueryPagination($limit, $offset);
		}

		$sql = str_replace("@FIELDS@", $sqlFields, $sql);
		$sql = str_replace("@WHERE@", $sqlWhere, $sql);
		$sql = str_replace("@ORDER@", $sqlOrder, $sql);
		$sql = str_replace("@PAGINATION@", $sqlPagination, $sql);

		$this->logger->debug("query = {query}", ["method"=>__METHOD__, "query"=>$sql]);

		return array($sql, $params);

	}

	// -------------------------------------------------------------------------

	public function createCommand($sql)
	{

		return ($this->props["connection"]->prepare($sql));

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------
	//
	protected function buildQuerySelect($tableName, $fields = "*", $keys = null, $orders = null, $limit = null, $offset = null)
	{

		// Escape
		$tableName = $this->escape($tableName);
		$limit = $this->escape($limit);
		$offset = $this->escape($offset);

		// Key
		list($where, $params) = $this->buildQueryWhere($keys);

		$sql =
			"SELECT " . ( $limit ? $this->getCountField() . " " : "" ) . $this->buildQueryFields($fields) .
			" FROM `" . $tableName . "`".
			( $where ? " WHERE " . $where : "" ) .
			( $orders ? " ORDER BY " . $this->buildQueryOrder($orders) : "" ) .
			( $limit ? " LIMIT " . $limit : "" ) .
			( $offset ? " OFFSET " . $offset : "" );

		// Sql for total record count
		$this->props["totalCountSql"] = "SELECT COUNT(*) FROM `" . $tableName . ( $where ? "` WHERE " . $where : "" );
		$this->props["totalCountParams"] = $params;

		$this->logger->debug("query = {query}", ["method"=>__METHOD__, "query"=>$sql]);


		return array($sql, $params);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryInsert($tableName, $fields)
	{

		// Escape
		$tableName = $this->escape($tableName);

		$sql1 = "";
		$sql2 = "";
		$parmas = array();
		foreach ($fields as $key => $item)
		{
			$key = $this->escape($key);

			$sql1 .= $key . ",";
			$sql2 .= $this->buildParam($key, $item, $params) . ",";
		}
		$sql1 = rtrim($sql1, ",");
		$sql2 = rtrim($sql2, ",");

		$sql = "INSERT INTO `" .$tableName . "` (" . $sql1 . ") VALUES (" . $sql2 . ") ";

		$this->logger->debug("query = {query}", ["method"=>__METHOD__, "query"=>$sql]);

		return array($sql, $params);

	}

	// -------------------------------------------------------------------------

	protected function buildQueryUpdate($tableName, $fields, $keys)
	{

		// Escape
		$tableName = $this->escape($tableName);

		// Data
		$field = "";
		$fieldParams = array();
		foreach ($fields as $key => $item)
		{
			$key = $this->escape($key);
			$field .= $key . "=" .$this->buildParam($key, $item, $fieldParams) . ",";
		}
		$field = rtrim($field, ",");

		// Key
		list($where, $params) = $this->buildQueryWhere($keys);

		$sql = "UPDATE `" . $tableName . "` SET " . $field . " WHERE " . $where ;

		$this->logger->debug("query = {query}", ["method"=>__METHOD__, "query"=>$sql]);

		return array($sql, array_merge($params, $fieldParams));

	}

    // -------------------------------------------------------------------------

	protected function buildQueryDelete($tableName, $keys)
	{

		// Escape
		$tableName = $this->escape($tableName);

		list($where, $params) = $this->buildQueryWhere($keys);

		$sql = "DELETE FROM `" . $tableName . "` WHERE " . $where;

		$this->logger->debug("query = {query}", ["method"=>__METHOD__, "query"=>$sql]);

		return array($sql, $params);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryFields($fields)
	{

		$fieldList = "*";
		if (is_array($fields))
		{
			$fieldList = "";
			foreach ($fields as $key => $item)
			{
				$fieldList .= $this->escape($key) . ",";
			}
			$fieldList = rtrim($fieldList, ",");
		}

		return $fieldList;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryOrder($orders)
	{

		$orderList = "";
		if ($orders)
		{
			$orderList = "";
			foreach ($orders as $key => $value)
			{
				$orderList .= $this->escape($key) . " " . $this->escape($value) . ",";
			}
			$orderList = rtrim($orderList, ",");
		}

		return $orderList;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryPagination($limit, $offset)
	{

		$sql = "";
		if ($limit)
		{
			$sql = "LIMIT " . $limit . " OFFSET " . $offset;
		}

		return $sql;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhere($keys)
	{

		$where = "";
		$stack = array();
		$params = array();

		if ($keys && is_array($keys))
		{
			for ($i = 0; $i < count($keys); $i++)
			{
				$value = $keys[$i]["value"] ?? null;
				$type = $keys[$i]["type"] ?? null;
				switch ($type)
				{
				case "parameters":
					$query = "";
					$operator = $keys[$i]["operator"];
					$parameters = $keys[$i]["fields"] ?? array();
					for ($j = 0; $j < count($parameters); $j++)
					{
						$value = $parameters[$j]["value"] ?? null;
						if ($value)
						{
							$comp = $this->buildQueryWhereEachCompareType($parameters[$j], $params);
							if ($comp)
							{
								$query .= $comp . " " . $operator . " ";
							}
						}
					}
					if (strlen($query) > 0)
					{
						$query = "(" . substr($query, 0, strlen($query) - strlen($operator) - 2) . ")";
						$this->pushStack($stack, $query);
					}
					break;
				case "operator":
					if ($value)
					{
						$op1 = array_pop($stack);
						$op2 = array_pop($stack);
						if ($op1 && $op2)
						{
							array_push($stack, "(" . $op1 . " " . $value . " " . $op2 . ")");
						}
						else if ($op1)
						{
							array_push($stack, $op1);
						}
					}
					break;
				case "item":
				default:
					if ($value)
					{
						$this->pushStack($stack, $this->buildQueryWhereEachCompareType($keys[$i], $params));
					}
				}
			}
		}

		$where = array_pop($stack);

		return array($where, $params);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhereItem($item, &$params = null)
	{

		$comparer	= $item["comparer"] ?? "=";
		$field		= $item["field"] ?? "";
		$parameter	= $item["parameter"] ?? $field;
		$value		= $item["value"] ?? "";
		$sql		= "";

		$comp = $this->buildCompare($field, "key_" . $parameter, $value, $comparer, $params);
		if ($comp)
		{
			$sql = "(" . $comp . ")";
		}

		$this->logger->debug("query = {query}", ["method"=>__METHOD__, "query"=>$sql]);

		return $sql;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhereItems($item, &$params = null)
	{

		$comparer	= $item["comparer"] ?? "=";
		$field		= $item["field"] ?? null;
		$op			= $item["operator"] ?? "OR";
		$parameter	= $item["parameter"] ?? $field;
		$value		= $item["value"] ?? "";

		$items		= explode(",", $value);

		$sql = "";
		for ($i = 0; $i < count($items); $i++)
		{
			$sql .= $field . $comparer . ":" . "key_" . $parameter . "_" . $i . " " . $op . " ";
			$params["key_" . $parameter ."_" . $i] = $items[$i];
		}
		if ($sql)
		{
			$sql = "(" . rtrim($sql, " " . $op . " ") . ")";
		}

		$this->logger->debug("query = {query}", ["method"=>__METHOD__, "query"=>$sql]);

		return $sql;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhereMatch($item, &$params = null)
	{

		$field		= $item["field"] ?? null;
		$value		= $item["value"] ?? null;

		$search = "";
		$words = preg_split("/ /", trim($value));
		for ($i = 0; $i < count($words); $i++)
		{
			if (substr($words[$i], 0, 1) == "-" || substr($words[$i], 0, 1) == "+")
			{
				$search .= " " . trim($words[$i]);
			}
			else
			{
				$search .= " +" . trim($words[$i]);
			}
		}
		$sql = "(match(" . $field . ") against(:" . "key_" . $field . " in boolean mode))";
		$params["key_" . $field] = $search;

		$this->logger->debug("search = {search}, sql = {sql}", ["method"=>__METHOD__, "search"=>$search, "sql"=>$sql]);

		return $sql;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhereFlag($item, &$params = null)
	{

		$comparer	= $item["comparer"] ?? "=";
		$field		= $item["field"] ?? "";
		$parameter	= $item["parameter"] ?? $field;
		$value		= $item["value"] ?? "";
		$sql			= "";

		$comp = $this->buildCompare($field, "key_" . $parameter, $value, $comparer, $params);
		if ($comp)
		{
			$sql = "(" . $comp . ")";
		}

		$this->logger->debug("query = {query}", ["method"=>__METHOD__, "query"=>$sql]);

		return $sql;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhereFlags($item, &$params = null)
	{

		$comparer	= $item["comparer"] ?? "=";
		$fields		= $item["fields"] ?? null;
		$op			= $item["operator"] ?? "OR";
		$value		= $item["value"] ?? "";
		$attrs		= explode(",", $value);
		$sql		= "";

		$sql = "";
		foreach ($fields as $key => $item)
		{
			if (in_array($key, $attrs))
			{
				$comp = $this->buildCompare($item["field"], "key_" . $item["field"], $item["value"], $item["comparer"], $params);
				if ($comp)
				{
					$sql = $comp . " " . $op . " ";
				}
			}
		}
		if ($sql)
		{
			$sql = "(" . rtrim($sql, " " . $op . " ") . ")";
		}

		$this->logger->debug("query = {query}", ["method"=>__METHOD__, "query"=>$sql]);

		return $sql;

	}

    // -------------------------------------------------------------------------

	protected function buildCompare($field, $parameter = "", $value = null, $comparer = "=", &$params = null)
	{

		$ret = "";
		switch((string)$value)
		{
		case "@ALL@":
			$ret = "";
			break;
		case "@NOTNULL@":
			$ret = $field . " IS NOT NULL";
			break;
		case "@NULL@":
			if ($comparer == "=")
			{
				$ret = $field . " IS NULL";
			}
			else if ($comparer == "!=")
			{
				$ret = $field . " IS NOT NULL";
			}
			break;
		case "@CURRENT_DATETIME@":
			$ret = $field . " " . $comparer . " " . $this->getDBDateTime();
			break;
		case "@SESSION_USER_ID@":
			$ret = $field . " " . $comparer . " :" . $this->escape($parameter);
			$params[$parameter] = $_SESSION["USER"]["ID"];
			break;
		default:
			$ret = $field . " " . $comparer . " :" . $this->escape($parameter);
			if ($comparer == "like")
			{
				$params[$parameter] = "%" . $value . "%";
			}
			else
			{
				$params[$parameter] = $value;
			}
			break;
		}

		return $ret;

	}

	// -------------------------------------------------------------------------

	protected function buildParam($key, $item, &$params)
	{

		$ret = $value = ($item["value"] ?? null);

		switch((string)$value)
		{
		case "@NULL@":
			$ret = "NULL";
			break;
		case "@CURRENT_DATETIME@":
			$ret = $this->getDBDateTime();
			break;
		case "@SESSION_USER_ID@":
			$ret = ":" . $this->escape($key);
			$params[$key] = $_SESSION["USER"]["ID"] ?? null;
			break;
		default:
			$ret = ":" . $this->escape($key);
			$params[$key] = $item["value"] ?? null;
			break;
		}

		return $ret;

	}

	// -------------------------------------------------------------------------

	protected function buildValue($key, $item)
	{

		$ret = $value = ($item["value"] ?? null);

		switch((string)$value)
		{
		case "@NULL@":
			$ret = "NULL";
			break;
		case "@CURRENT_DATETIME@":
			$ret = $this->getDBDateTime();
			break;
		}

		return $ret;

	}

    // -------------------------------------------------------------------------

	protected function assignParams(&$cmd, $params)
	{

		if ($cmd && $params && is_array($params))
		{
			foreach ($params as $key => $value) {
				//if (substr($key, 0, 1) != "_")
				{
					//if (!$this->isDbCommand($value))
					{
						$value = ( $value === "" ? null : $value);
						$this->bindValue($cmd, $key, $this->getParamType($value), $value);
					}
				}
			}
		}

	}

    // -------------------------------------------------------------------------

	protected function bindParam($cmd, $name, $dataType, $value = null)
	{

		$this->logger->info("name = {name}, dataType = {dataType}, value = {value}", ["method"=>__METHOD__, "name"->$name, "dataType"=>$dataType, "value"=>$value]);

		$cmd->bindParam($name, $value, $dataType);

	}

    // -------------------------------------------------------------------------

	public function bindValue($cmd, $name, $dataType, $value)
	{

		$this->logger->info("name = " . $name . ", value = " . $value, ["method"=>__METHOD__, "name"=>$name, "value"=>$value]);

		$cmd->bindValue($name, $value, $dataType);

	}

    // -------------------------------------------------------------------------

	protected function getDBDate()
	{

		$command = "";
		switch($this->props["dbType"])
		{
			case "SQLITE":
				$command = "date('now', 'localtime')";
				break;
			case "MYSQL":
				$command = "CURRENT_DATE()";
				break;
		}

		return $command;

	}

    // -------------------------------------------------------------------------

	protected function getDBDateTime()
	{

		$command = "";
		switch($this->props["dbType"])
		{
			case "SQLITE":
				$command = "datetime('now', 'localtime')";
				break;
			case "MYSQL":
				$command = "CONCAT(CURRENT_DATE(), ' ', CURRENT_TIME())";
				break;
		}

		return $command;

	}

    // -------------------------------------------------------------------------

	protected function escapeLike($keyword)
	{

		return addcslashes($keyword, "\_%");

	}

    // -------------------------------------------------------------------------

	protected function escape($sql)
	{

		$result = $sql;
		switch($this->props["dbType"])
		{
			case "SQLITE":
			case "MYSQL":
				$result = str_replace("\\", "\\\\", str_replace("'", "''", $sql));
				break;
		}

		return $result;

	}

    // -------------------------------------------------------------------------

	protected function getCountField()
	{

		return "";

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

 	/**
	 * Returns parameter type.
	 *
	 * @param		object			$value			Value to examine.
	 * @return		bool							Parameter type.
	 */
	private function getParamType($target)
	{

		$paramType = PDO::PARAM_STR;

		switch (gettype($target))
		{
			case "boolean":
				$paramType = PDO::PARAM_BOOL;
				break;
			case "integer":
				$paramType = PDO::PARAM_INT;
				break;
		}

		return $paramType;

	}

}

