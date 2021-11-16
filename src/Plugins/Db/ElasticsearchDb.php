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

namespace Bitsmist\v1\Plugins\Db;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Plugins\Db\BaseDb;

// =============================================================================
//	Elasticsearch database class
// =============================================================================

class ElasticsearchDb extends BaseDb
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Bool conversion table.
	 *
	 * @var			array
	 */
	private $bools = [ "AND" => "must", "OR" => "should" ];

	// -------------------------------------------------------------------------
    //  Constructor, Destructor
    // -------------------------------------------------------------------------

	public function __construct($loader, ?array $options)
    {

		parent::__construct($loader, $options);

        $this->props["dbType"] = "ELASTICSEARCH";

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function open($setting = null)
	{

		$this->props["lastResponse"] = null;
		$this->props["connection"] = curl_init();

		$this->logger->debug(
			"dsn = {dsn}, user = {user}, password = {password}", ["method"=>__METHOD__, "dsn"=>$this->props["dsn"], "user"=>$this->props["user"], "password"=>substr($this->props["password"], 0, 1) . "*******"]
		);

		return $this->props["connection"];

	}

    // -------------------------------------------------------------------------

	public function close()
	{

		if ($this->props["connection"])
		{
			curl_close($this->props["connection"]);
			$this->props["connection"] = null;

			$this->logger->debug("dsn = {dsn}", ["method"=>__METHOD__, "dsn"=>$this->props["dsn"]]);
		}

	}

	// -------------------------------------------------------------------------

	public function select($tableName, $fields, $keys = null, $orders = null, $limit = null, $offset = null)
	{

		list($query) = $this->buildQuerySelect($tableName, $fields, $keys, $orders, $limit, $offset);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "GET";
		$cmd["index"] = $tableName;
		$cmd["command"] = "_search";
		$cmd["url"]  = $this->props["dsn"] . "/" . $cmd["index"] . "/" . $cmd["command"];

		return $this->getData($cmd);

	}

	// -------------------------------------------------------------------------

	public function selectById($tableName, $fields, $id)
	{

		list($query) = $this->buildQuerySelect($tableName, $fields);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "GET";
		$cmd["index"] = $tableName;
		$cmd["id"] = $id["value"];
		$cmd["url"] = $this->props["dsn"] . "/" . $cmd["index"] . "/_doc/" . $cmd["id"];

		return $this->getData($cmd);

	}

    // -------------------------------------------------------------------------

	public function insert($tableName, $fields)
	{

		list($query) = $this->buildQueryInsert($tableName, $fields);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "POST";
		$cmd["index"] = $tableName;
		$cmd["url"]  = $this->props["dsn"] . "/" . $cmd["index"] . "/_doc/";

		return $this->execute($cmd);

	}

    // -------------------------------------------------------------------------

	public function insertWithId($tableName, $fields, $id)
	{

		list($query) = $this->buildQueryInsert($tableName, $fields);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "POST";
		$cmd["index"] = $tableName;
		$cmd["id"] = $id;
		$cmd["url"] = $this->props["dsn"] . "/" . $cmd["index"] . "/_doc/" . $cmd["id"];

		return $this->execute($cmd);

	}

	// -------------------------------------------------------------------------

	public function update($tableName, $fields, $keys = null)
	{

		list($query) = $this->buildQueryUpdate($tableName, $fields, $keys);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "POST";
		$cmd["index"] = $tableName;
		$cmd["command"] = "_update_by_query";
		$cmd["url"]  = $this->props["dsn"] . "/" . $cmd["index"] . "/" . $cmd["command"];

		return $this->execute($cmd);

	}

	// -------------------------------------------------------------------------

	public function updateById($tableName, $fields, $id)
	{

		list($query) = $this->buildQueryUpdateById($tableName, $fields, $id);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "PUT";
		$cmd["index"] = $tableName;
		$cmd["id"] = $id["value"];
		$cmd["url"] = $this->props["dsn"] . "/" . $cmd["index"] . "/_doc/" . $cmd["id"];

		return $this->execute($cmd);

	}

	// -------------------------------------------------------------------------

	public function delete($tableName, $keys)
	{

		list($query) = $this->buildQueryDelete($tableName, $keys);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "POST";
		$cmd["index"] = $tableName;
		$cmd["command"] = "_delete_by_query";
		$cmd["url"]  = $this->props["dsn"] . "/" . $cmd["index"] . "/" . $cmd["command"];

		return $this->execute($cmd);

	}

	// -------------------------------------------------------------------------

	public function deleteById($tableName, $id)
	{

		$cmd = array();
		$cmd["method"] = "DELETE";
		$cmd["index"] = $tableName;
		$cmd["id"] = $id["value"];
		$cmd["url"] = $this->props["dsn"] . "/" . $cmd["index"] . "/_doc/" . $cmd["id"];

		return $this->execute($cmd);

	}

    // -------------------------------------------------------------------------

	public function getData($cmd, $params = null)
	{

		$this->execute($cmd);

		$data = array();
		$response = $this->props["lastResponse"];
		if (is_array($response))
		{
			if ($response["hits"] ?? null)
			{
				for ($i = 0; $i < count($response["hits"]["hits"]); $i++)
				{
					$data[] = $response["hits"]["hits"][$i]["_source"];
				}
			}
			else if ($response["found"] ?? null)
			{
				$data[] = $response["_source"];
			}
		}

		return $data;

	}

    // -------------------------------------------------------------------------

	public function getTotalCount()
	{

		$result = 0;

		if ($this->props["lastResponse"])
		{
			if (is_array($this->props["lastResponse"]["hits"]["total"]))
			{
				$result = $this->props["lastResponse"]["hits"]["total"]["value"];
			}
			else
			{
				$result = $this->props["lastResponse"]["hits"]["total"];
			}
		}

		return $result;

	}

    // -------------------------------------------------------------------------

	public function execute($cmd, $params = null)
	{

		$this->logger->info("method = {httpmethod}, url = {url}", ["method"=>__METHOD__, "httpmethod"=>$cmd["method"], "url"=>$cmd["url"]]);

		// Init
		$this->props["headers"] = [
			"Content-Type: application/json",
		];
		curl_setopt($this->props["connection"], CURLOPT_URL, $cmd["url"]);
		curl_setopt($this->props["connection"], CURLOPT_CUSTOMREQUEST, $cmd["method"]);
		curl_setopt($this->props["connection"], CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->props["connection"], CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->props["connection"], CURLOPT_HTTPHEADER, $this->props["headers"]);
		if (($cmd["query"] ?? null) !== null)
		{
			curl_setopt($this->props["connection"], CURLOPT_POSTFIELDS, $cmd["query"]);
			$this->logger->info("query = {query}", ["method"=>__METHOD__, "query"=>$cmd["query"]]);
		}
		curl_setopt($this->props["connection"], CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

		// Exec
		$ret = curl_exec($this->props["connection"]);
		if (!$ret)
		{
			$this->logger->error("curl_exec() failed: {message}", ["method"=>__METHOD__, "message"=>curl_error($this->props["connection"])]);
			throw new HttpException(HttpException::ERRNO_EXCEPTION, HttpException::ERRMSG_EXCEPTION);
		}
		$this->logger->debug("ret = {ret}",["method"=>__METHOD__, "ret"=>$ret]);

		// Response check
		$response = json_decode($ret, true);
		$this->props["lastResponse"] = $response;
		if (is_array($response))
		{
			if (array_key_exists("error", $response))
			{
				$this->logger->error("Elasticsearch returned an error: type = {type}, reason = {reason}", ["method"=>__METHOD__, "type"=>$response["error"]["root_cause"][0]["type"], "reason"=>$response["error"]["root_cause"][0]["reason"]]);
				throw new HttpException(HttpException::ERRNO_EXCEPTION, HttpException::ERRMSG_EXCEPTION);
			}
		}

		// Result count
		$cnt = 0;
		switch ($cmd["method"])
		{
		case "POST":
			if (($response["result"] ?? null )== "created")
			{
				$cnt = 1;
			}
			break;
		case "PUT":
			if (($response["result"] ?? null )== "updated")
			{
				$cnt = 1;
			}
			break;
		case "DELETE":
			if (($response["result"] ?? null )== "deleted")
			{
				$cnt = 1;
			}
			break;
		}

		return $cnt;

	}

    // -------------------------------------------------------------------------

	public function buildQuery($query, $fields = "*", $keys = null, $order = null, $limit = null, $offset = null)
	{
	}

    // -------------------------------------------------------------------------

	public function createCommand($query = null)
	{

		$cmd = array();

		if ($query !== null)
		{
			$cmd["query"] = json_encode($query);
		}

		return $cmd;

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	protected function buildQuerySelect($tableName, $fields = "*", $keys = null, $orders = null, $limit = null, $offset = null)
	{

		$query = array();

		// Feilds
		if ($fields != "*")
		{
			$query["_source"] = $this->buildQueryFields($fields);
		}

		// Key
		if ($keys !== null)
		{
			list($where) = $this->buildQueryWhere($keys);
			$query["query"] = $where;
		}

		// Order
		if ($orders !== null)
		{
			$order = $this->buildQueryOrder($orders);
			$query["sort"] = $order;
		}

		// Pagination
		if ($offset !== null)
		{
			$query["from"] = $offset;
		}

		if ($limit != null)
		{
			$query["size"] = $limit;
		}

		if (count($query) == 0)
		{
			$query = null;
		}

		return array($query);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryInsert($tableName, $fields)
	{

		$query = array();

		foreach ($fields as $key => $item)
		{
			$query[$key] = $this->buildValue($key, $item);
		}

		return array($query);

	}

	// -------------------------------------------------------------------------

	protected function buildQueryUpdate($tableName, $fields, $keys = null)
	{

		$query = array();

		$query["script"] = "";
		foreach ($fields as $key => $item)
		{
			$query["script"] .= "ctx._source." . $key . "=\"" . $this->buildValue($key, $item) . "\";";
		}

		// Key
		if ($keys !== null && is_array($keys) && count($keys) > 0)
		{
			list($where) = $this->buildQueryWhere($keys);
			$query["query"] = $where;
		}

		if (count($query) == 0)
		{
			$query = null;
		}

		return array($query);

	}

	// -------------------------------------------------------------------------

	protected function buildQueryUpdateById($tableName, $fields, $id)
	{

		$query = array();

		foreach ($fields as $key => $item)
		{
			$query[$key] = $this->buildValue($key, $item);
		}

		return array($query);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryDelete($tableName, $keys = null)
	{

		$query = array();

		// Key
		if ($keys !== null && is_array($keys) && count($keys) > 0)
		{
			list($where) = $this->buildQueryWhere($keys);
			$query["query"] = $where;
		}
		else
		{
			$query["query"]["match_all"] = (object)[];
		}

		if (count($query) == 0)
		{
			$query = null;
		}

		return array($query);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryDeleteById($tableName, $id)
	{

		$query = array();

		// Key
		if ($keys !== null && is_array($keys) && count($keys) > 0)
		{
			list($where) = $this->buildQueryWhere($keys);
			$query["query"] = $where;
		}
		else
		{
			$query["query"]["match_all"] = (object)[];
		}

		if (count($query) == 0)
		{
			$query = null;
		}

		return array($query);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryFields($fields)
	{

		$fieldList = "*";
		if (is_array($fields))
		{
			$fieldList = [];
			foreach ($fields as $key => $item)
			{
				$fieldList[] = $this->escape($key);
			}
		}

		return $fieldList;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryOrder($orders)
	{

		$sort = [];
		if ($orders)
		{
			foreach ($orders as $key => $value)
			{
				$sort[] = array($key => $this->escape($value));
			}
		}

		return $sort;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhere($keys)
	{

		$stack = array();
		$query = array();

		if ($keys && is_array($keys))
		{
			for ($i = 0; $i < count($keys); $i++)
			{
				$value	= $keys[$i]["value"] ?? null;
				$type	= $keys[$i]["type"] ?? null;
				switch ($type)
				{
				case "parameters":
					$query = array();
					$bool = $this->bools[$keys[$i]["operator"]] ?? null;
					$parameters = $keys[$i]["fields"] ?? array();
					for ($j = 0; $j < count($parameters); $j++)
					{
						$value = $parameters[$j]["value"] ?? null;
						if ($value)
						{
							$query["bool"][$bool][] = $this->buildQueryWhereEachCompareType($parameters[$j]);
						}
					}
					if (count($query) > 0)
					{
						$this->pushStack($stack, $query);
					}
					break;
				case "operator":
					$bool = $this->bools[$value] ?? null;
					if ($bool)
					{
						$op1 = $this->popStack($stack);
						$op2 = $this->popStack($stack);
						$item = array();
						if ($op1 && $op2)
						{
							$item["bool"][$bool][] = $op1;
							$item["bool"][$bool][] = $op2;
							$this->pushStack($stack, $item);
						}
						else if ($op1)
						{
							$item["bool"][$bool][] = $op1;
							$this->pushStack($stack, $item);
						}
					}
					break;
				case "item":
				default:
					if ($value)
					{
						$this->pushStack($stack, $this->buildQueryWhereEachCompareType($keys[$i]));
					}
					break;
				}

			}
		}

		$query = $this->popStack($stack);

		return array($query);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhereItem($item, &$params = null)
	{

		$comparer	= $item["comparer"] ?? "=";
		$field		= $item["field"] ?? "";
		$parameter	= $item["parameter"] ?? $field;
		$value		= $item["value"] ?? "";

		$query = $this->buildCompare($field, $parameter, $value, $comparer);

		return $query;

	}

	// -------------------------------------------------------------------------

	protected function buildQueryWhereItems($item, &$params = null)
	{

		$comparer	= $item["comparer"] ?? "=";
		$field		= $item["field"] ?? null;
		$op			= $item["operator"] ?? "OR";
		$bool		= $this->bools[$op] ?? null;
		$parameter	= $item["parameter"] ?? $field;
		$value		= $item["value"] ?? "";
		$items		= explode(",", $value);

		$query = array();
		for ($i = 0; $i < count($items); $i++)
		{
			$query["bool"][$bool][] = $this->buildCompare($field, null, $items[$i], $comparer);
		}

		return $query;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhereMatch($item, &$params = null)
	{

		$comparer	= $item["comparer"] ?? "=";
		$field		= $item["field"] ?? null;
		$op			= $item["operator"] ?? "OR";
		$bool		= $this->bools[$op] ?? null;
		$value		= $item["value"] ?? null;

		$query = array();
		$items = preg_split("/ /", trim($value));
		for ($i = 0; $i < count($items); $i++)
		{
			$query["bool"][$bool][] = $this->buildCompare($field, null, $items[$i], $comparer);
		}

		return $query;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhereFlags($item, &$params = null)
	{

		$comparer	= $item["comparer"] ?? "=";
		$fields		= $item["fields"] ?? null;
		$op			= $item["operator"] ?? "OR";
		$bool		= $this->bools[$op] ?? null;
		$value		= $item["value"] ?? "";
		$attrs		= explode(",", $value);

		$query = array();
		foreach ($fields as $key => $item)
		{
			if (in_array($key, $attrs))
			{
				$query["bool"][$bool][] = $this->buildCompare($item["field"], null, $item["value"], $item["comparer"]);
			}
		}

		return $query;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryWhereFlag($item, &$params = null)
	{

		$comparer	= $item["comparer"] ?? "=";
		$field		= $item["field"] ?? "";
		$parameter	= $item["parameter"] ?? $field;
		$value		= $item["value"] ?? "";

		$query = $this->buildCompare($field, $parameter, $value, $comparer);

		return $query;

	}

	// -------------------------------------------------------------------------

	protected function buildCompare($field, $parameter = "", $value = null, $comparer = "=")
	{

		$value = str_replace("@CURRENT_DATETIME@", "now", $value);

		$ret = [];
		switch((string)$value)
		{
		case "@NULL@":
			switch ($comparer)
			{
			case "=":
				$ret["bool"]["must_not"] = ["exists" => ["field" => $field]];
				break;
			case "!":
				$ret["bool"]["must_not"] = ["exists" => ["field" => $field]];
				break;
			}
			break;
		default:
			switch ($comparer)
			{
			case "<=":
				$ret["range"][$field]["lte"] = $value;
				break;
			case "<":
				$ret["range"][$field]["lt"] = $value;
				break;
			case ">=":
				$ret["range"][$field]["gte"] = $value;
				break;
			case ">":
				$ret["range"][$field]["gt"] = $value;
				break;
			case "like":
				//$ret["match"][$field] = $value;
				$ret["match_phrase"][$field] = $value;
				break;
			default:
				$ret["term"][$field] = $value;
				break;
			}
			break;
		}

		return $ret;

	}

	// -------------------------------------------------------------------------

	protected function buildValue($key, $item)
	{

		$ret = $value = $item["value"] ?? null;

		switch((string)$value)
		{
		case "@NULL@":
			$ret = null;
			break;
		case "@CURRENT_DATETIME@":
			$ret = $this->getDBDateTime();
			break;
		}

		switch($item["type"] ?? null)
		{
		case "DATE":
			$ret = date(DATE_ISO8601, strtotime($value));
			break;
		}

		return $ret;

	}

    // -------------------------------------------------------------------------

	protected function getDBDateTime()
	{

		$command = date(DATE_ISO8601);

		return $command;

	}

}
