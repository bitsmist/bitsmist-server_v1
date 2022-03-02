<?php
// =============================================================================
/**
 * Bitsmist Server - PHP WebAPI Server Framework
 *
 * @copyright		Masaki Yasutake
 * @link			https://bitsmist.com/
 * @license			https://github.com/bitsmist/bitsmist/blob/master/LICENSE
 */
// =============================================================================

namespace Bitsmist\v1\Plugins\DB;

use Bitsmist\v1\Plugins\DB\BaseDB;
use PDO;

// =============================================================================
//	PDO database class
// =============================================================================

class PdoDB extends BaseDB
{

	// -------------------------------------------------------------------------
    //  Constructor, Destructor
    // -------------------------------------------------------------------------

	public function __construct($name, array $options = null, $container)
    {

		parent::__construct($name, $options, $container);

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function open($setting = null)
	{

		$this->logger->debug(
			"dsn={dsn}, user={user}, password={password}", [
				"method" => __METHOD__,
				"dsn" => $this->props["dsn"],
				"user" => $this->props["user"],
				"password" => substr($this->props["password"] ?? "", 0, 1) . "*******"
			]
		);

		// Default options
		$options = [
			PDO::ATTR_ERRMODE				=> PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES		=> false,
			PDO::ATTR_PERSISTENT			=> true,
		];

		// Custom options
		foreach ((array)($this->options["pdoOptions"] ?? null) as $key => $value)
		{
			$value = ( is_string($value) && substr($value, 0, 5) == "PDO::" ? constant($value) : $value );
			$options[constant($key)] = $value;
		}

		// Connect to database
		$this->props["connection"] = new PDO($this->props["dsn"], $this->props["user"], $this->props["password"], $options);

		return $this->props["connection"];

	}

    // -------------------------------------------------------------------------

	public function close()
	{

		$this->props["connection"] = null;

		$this->logger->debug("dsn={dsn}", ["method"=>__METHOD__, "dsn"=>$this->props["dsn"]]);

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

	public function createCommand($sql)
	{

		return ($this->props["connection"]->prepare($sql));

	}

    // -------------------------------------------------------------------------

	public function getData($cmd, $params = null)
	{

		$this->logger->info("query={query}", ["method"=>__METHOD__, "query"=>$cmd->queryString]);

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

		$this->logger->debug("query={query}", ["method"=>__METHOD__, "query"=>$this->props["totalCountSql"]]);

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

		$this->logger->info("query={query}", ["method"=>__METHOD__, "query"=>$cmd->queryString]);

		// Bind params
		$this->assignParams($cmd, $params);

		// Execute
		$cnt = 0;
		if ($cmd->execute())
		{
			$cnt = $cmd->rowCount();
		}

		return $cnt;

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	protected function buildQuerySelect(string $tableName, ?array $fields = null, ?array $keys = null, ?array $orders = null, ?int $limit = null, ?int $offset = null)
	{

		// Escape
		$tableName = $this->escape($tableName);
		$limit = $this->escape($limit);
		$offset = $this->escape($offset);

		// Key
		list($where, $params) = $this->buildQueryWhere($keys);

		$sql =
			"SELECT " . ( $limit ? $this->getCountField() . " " : "" ) . ($fields == null ? "*" : $this->buildQueryFields($fields)) .
			" FROM `" . $tableName . "`".
			( $where ? " WHERE " . $where : "" ) .
			( $orders ? " ORDER BY " . $this->buildQueryOrder($orders) : "" ) .
			( $limit ? " LIMIT " . $limit : "" ) .
			( $offset ? " OFFSET " . $offset : "" );

		// Sql for total record count
		$this->props["totalCountSql"] = "SELECT COUNT(*) FROM `" . $tableName . ( $where ? "` WHERE " . $where : "" );
		$this->props["totalCountParams"] = $params;

		$this->logger->debug("query={query}", ["method"=>__METHOD__, "query"=>$sql]);

		return array($sql, $params);

	}

    // -------------------------------------------------------------------------

	protected function buildQuerySelectById(string $tableName, array $fields = null, array $id)
	{

		return $this->buildQuerySelect($tableName, $fields, [["fieldName" => $id["fieldName"], "comparer" => "=", "value" => $id["value"]]]);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryInsert(string $tableName, array $fields)
	{

		// Escape
		$tableName = $this->escape($tableName);

		$sqlFields = "";
		$sqlValues = "";
		$params = array();
		foreach ($fields as $key => $item)
		{
			$key = $this->escape($key);

			$sqlFields .= $key . ",";
			$sqlValues .= $this->buildParam($key, $item, $params) . ",";
		}
		$sqlFields = rtrim($sqlFields, ",");
		$sqlValues = rtrim($sqlValues, ",");

		$sql = "INSERT INTO `" .$tableName . "` (" . $sqlFields . ") VALUES (" . $sqlValues . ") ";

		$this->logger->debug("query={query}", ["method"=>__METHOD__, "query"=>$sql]);

		return array($sql, $params);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryInsertWithId(string $tableName, array $fields, array $id)
	{

		$fields[$id["fieldName"]] = array();
		$fields[$id["fieldName"]]["value"] = $id["value"];

		return $this->buildQueryInsert($tableName, $fields);

	}

	// -------------------------------------------------------------------------

	protected function buildQueryUpdate(string $tableName, array $fields, ?array $keys = null)
	{

		// Escape
		$tableName = $this->escape($tableName);

		// Data
		$fieldList = "";
		$fieldParams = array();
		foreach ($fields as $key => $item)
		{
			$key = $this->escape($key);
			$fieldList .= $key . "=" .$this->buildParam($key, $item, $fieldParams) . ",";
		}
		$fieldList = rtrim($fieldList, ",");

		// Key
		list($where, $params) = $this->buildQueryWhere($keys);

		$sql = "UPDATE `" . $tableName . "` SET " . $fieldList . ( $where ? " WHERE " . $where : "" );

		$this->logger->debug("query={query}", ["method"=>__METHOD__, "query"=>$sql]);

		return array($sql, array_merge($params, $fieldParams));

	}

    // -------------------------------------------------------------------------

	protected function buildQueryUpdateById(string $tableName, array $fields, array $id)
	{

		return $this->buildQueryUpdate($tableName, $fields, [["fieldName" => $id["fieldName"], "comparer" => "=", "value" => $id["value"]]]);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryDelete(string $tableName, ?array $keys = null)
	{

		// Escape
		$tableName = $this->escape($tableName);

		list($where, $params) = $this->buildQueryWhere($keys);

		$sql = "DELETE FROM `" . $tableName . "`" . ( $where ? " WHERE " . $where : "" );

		$this->logger->debug("query={query}", ["method"=>__METHOD__, "query"=>$sql]);

		return array($sql, $params);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryDeleteById(string $tableName, array $id)
	{

		return $this->buildQueryDelete($tableName, [["fieldName" => $id["fieldName"], "comparer" => "=", "value" => $id["value"]]]);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryFields(?array $fields)
	{

		$fieldList = "";

		foreach ((array)$fields as $key => $item)
		{
			if (array_key_exists("fieldName", (array)$item))
			{
				$fieldList .= $this->escape($item["fieldName"]) . " AS " . $this->escape($key) . ",";
			}
			else
			{
				$fieldList .= $this->escape($key) . ",";
			}
		}
		$fieldList = rtrim($fieldList, ",");

		return ( $fieldList ? $fieldList : "*" );

	}

    // -------------------------------------------------------------------------

	protected function buildQueryOrder(?array $orders)
	{

		$orderList = "";

		foreach ((array) $orders as $key => $value)
		{
			$orderList .= $this->escape($key) . " " . $this->escape($value) . ",";
		}
		$orderList = rtrim($orderList, ",");

		return $orderList;

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

		$comparer		= $item["comparer"] ?? "=";
		$fieldName		= $item["fieldName"] ?? "";
		$parameterName	= $item["parameterName"] ?? $fieldName;
		$value			= $item["value"] ?? "";
		$sql			= "";

		$comp = $this->buildCompare($fieldName, "key_" . $parameterName, $value, $comparer, $params);
		if ($comp)
		{
			$sql = "(" . $comp . ")";
		}

		$this->logger->debug("query={query}", ["method"=>__METHOD__, "query"=>$sql]);

		return $sql;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhereItems($item, &$params = null)
	{

		$comparer		= $item["comparer"] ?? "=";
		$fieldName		= $item["fieldName"] ?? "";
		$op				= $item["operator"] ?? "OR";
		$parameterName	= $item["parameterName"] ?? $fieldName;
		$value			= $item["value"] ?? "";
		$items			= explode(",", $value);

		$sql = "";
		for ($i = 0; $i < count($items); $i++)
		{
			$sql .= $fieldName . $comparer . ":" . "key_" . $parameterName . "_" . $i . " " . $op . " ";
			$params["key_" . $parameterName ."_" . $i] = $items[$i];
		}
		if ($sql)
		{
			$sql = "(" . rtrim($sql, " " . $op . " ") . ")";
		}

		$this->logger->debug("query={query}", ["method"=>__METHOD__, "query"=>$sql]);

		return $sql;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhereMatch($item, &$params = null)
	{

		$fieldName	= $item["fieldName"] ?? null;
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
		$sql = "(match(" . $fieldName . ") against(:" . "key_" . $fieldName . " in boolean mode))";
		$params["key_" . $fieldName] = $search;

		$this->logger->debug("search={search}, sql={sql}", ["method"=>__METHOD__, "search"=>$search, "sql"=>$sql]);

		return $sql;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhereFlag($item, &$params = null)
	{

		$comparer		= $item["comparer"] ?? "=";
		$fieldName		= $item["fieldName"] ?? "";
		$parameterName	= $item["parameterName"] ?? $fieldName;
		$value			= $item["value"] ?? "";
		$sql			= "";

		$comp = $this->buildCompare($fieldName, "key_" . $parameterName, $value, $comparer, $params);
		if ($comp)
		{
			$sql = "(" . $comp . ")";
		}

		$this->logger->debug("query={query}", ["method"=>__METHOD__, "query"=>$sql]);

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
				$comp = $this->buildCompare($item["fieldName"], "key_" . $item["fieldName"], $item["value"], $item["comparer"], $params);
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

		$this->logger->debug("query={query}", ["method"=>__METHOD__, "query"=>$sql]);

		return $sql;

	}

    // -------------------------------------------------------------------------

	protected function buildCompare($fieldName, $parameterName = "", $value = null, $comparer = "=", &$params = null)
	{

		$ret = "";
		switch((string)$value)
		{
		case "@ALL@":
			$ret = "";
			break;
		case "@NOTNULL@":
			$ret = $fieldName . " IS NOT NULL";
			break;
		case "@NULL@":
			if ($comparer == "=")
			{
				$ret = $fieldName . " IS NULL";
			}
			else if ($comparer == "!=")
			{
				$ret = $fieldName . " IS NOT NULL";
			}
			break;
		case "@CURRENT_DATETIME@":
			$ret = $fieldName . " " . $comparer . " " . $this->getDBDateTime();
			break;
		case "@SESSION_USER_ID@":
			$ret = $fieldName . " " . $comparer . " :" . $this->escape($parameterName);
			$rootName = $this->container["settings"]["options"]["session"]["name"] ?? "authInfo";
			$idName = $this->container["settings"]["options"]["session"]["user"]["idName"] ?? "id";
			$params[$parameterName] = $_SESSION[$rootName][$idName];
			break;
		default:
			$ret = $fieldName . " " . $comparer . " :" . $this->escape($parameterName);
			if ($comparer == "like")
			{
				$params[$parameterName] = "%" . $value . "%";
			}
			else
			{
				$params[$parameterName] = $value;
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
			$rootName = $this->container["settings"]["options"]["session"]["name"] ?? "authInfo";
			$idName = $this->container["settings"]["options"]["session"]["user"]["idName"] ?? "id";
			$params[$key] = $_SESSION[$rootName][$idName];
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

		$this->logger->info("name={name}, dataType={dataType}, value={value}", ["method"=>__METHOD__, "name"=>$name, "dataType"=>$dataType, "value"=>$value]);

		$cmd->bindParam($name, $value, $dataType);

	}

    // -------------------------------------------------------------------------

	public function bindValue($cmd, $name, $dataType, $value)
	{

		$this->logger->info("name=" . $name . ", value=" . $value, ["method"=>__METHOD__, "name"=>$name, "value"=>$value]);

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
				$result = str_replace("\\", "\\\\", str_replace("'", "''", $sql ?? ""));
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
