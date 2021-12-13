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

use Bitsmist\v1\Plugins\DB\CurlDB;

// =============================================================================
//	Elasticsearch database class
// =============================================================================

class ElasticsearchDB extends CurlDB
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

	public function __construct($container, ?array $options)
    {

		parent::__construct($container, $options);

        $this->props["dbType"] = "ELASTICSEARCH";

	}

	// -------------------------------------------------------------------------
	//	Public
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

		if (is_array($this->props["lastResponse"]["hits"]["total"]))
		{
			$result = $this->props["lastResponse"]["hits"]["total"]["value"];
		}
		else
		{
			$result = $this->props["lastResponse"]["hits"]["total"];
		}

		return $result;

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	protected function initCurl($cmd)
	{

		// Super
		parent::initCurl($cmd);

		// Set options
		curl_setopt($this->props["connection"], CURLOPT_HTTPHEADER, [
			"Content-Type: application/json",
		]);
		curl_setopt($this->props["connection"], CURLOPT_RETURNTRANSFER, true);

	}

	// -------------------------------------------------------------------------

	protected function buildUrl($cmd)
	{

		$type = "_doc";
		$command = "";
		if (!array_key_exists("id", $cmd))
		{
			switch ($cmd["method"])
			{
			case "GET" :
				$command = "_search";
				$type = "";
				break;
			case "PUT" :
				$command = "_update_by_query";
				break;
			case "DELETE" :
				$command = "_delete_by_query";
				break;
			}
		}

		$url = $this->props["dsn"] . "/" . $cmd["tableName"] .
			($type ? "/" . $type : "") .
			(array_key_exists("id", $cmd) ? "/" . $cmd["id"] : "" ) .
			($command ? "/" . $command : "");

		return $url;

	}

	// -------------------------------------------------------------------------

	protected function convertResponse($cmd, string $response)
	{

		return json_decode($response, true);

	}

	// -------------------------------------------------------------------------

	protected function checkResponse($cmd, $response)
	{

		if (!is_array($response) || array_key_exists("error", $response))
		{
			$type = $response["error"]["root_cause"][0]["type"] ?? "";
			$reason = $response["error"]["root_cause"][0]["reason"] ?? $response["error"] ?? "";

			$this->logger->error("Elasticsearch returned an error: type = {type}, reason = {reason}", [
				"method"=>__METHOD__,
				"type"=>$type,
				"reason"=>$reason
			]);

			throw new \RuntimeException("Elasticsearch returned an error. " .
				"type=" . $type .
				", reason=" . $reason
			);
		}

	}

	// -------------------------------------------------------------------------

	protected function getResultCount($cmd, $response)
	{

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

		$ret = array();
		$ret["method"] = "GET";
		$ret["tableName"] = $tableName;
		$ret["url"]  = $this->buildUrl((array)$ret);
		$ret["query"] = ( $query ? json_encode($query) : null );

		return array($ret, null);

	}

    // -------------------------------------------------------------------------

	protected function buildQuerySelectById($tableName, $fields = "*", $id)
	{

		list($query, $params) = $this->buildQuerySelect($tableName, $fields);

		$query["id"] = $id["value"];
		$query["url"]  = $this->buildUrl((array)$query);

		return array($query, $params);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryInsert($tableName, $fields)
	{

		$query = array();

		foreach ($fields as $key => $item)
		{
			$query[$key] = $this->buildValue($key, $item);
		}

		$ret = array();
		$ret["method"] = "POST";
		$ret["tableName"] = $tableName;
		$ret["url"]  = $this->buildUrl((array)$ret);
		$ret["query"] = ( $query ? json_encode($query) : null );

		return array($ret, null);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryInsertWithId($tableName, $fields, $id)
	{

		list($query, $params) = $this->buildQueryInsert($tableName, $fields);

		$query["id"] = $id["value"];
		$query["url"]  = $this->buildUrl((array)$query);

		return array($query, $params);

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

		$ret = array();
		$ret["method"] = "PUT";
		$ret["tableName"] = $tableName;
		$ret["url"]  = $this->buildUrl((array)$ret);
		$ret["query"] = ( $query ? json_encode($query) : null );

		return array($ret, null);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryUpdateById($tableName, $fields, $id)
	{

		$query = array();

		foreach ($fields as $key => $item)
		{
			$query[$key] = $this->buildValue($key, $item);
		}

		if (count($query) == 0)
		{
			$query = null;
		}

		$ret = array();
		$ret["method"] = "PUT";
		$ret["tableName"] = $tableName;
		$ret["query"] = ( $query ? json_encode($query) : null );
		$ret["id"] = $id["value"];
		$ret["url"]  = $this->buildUrl((array)$ret);

		return array($ret, null);

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
		/*
		else
		{
			$query["query"]["match_all"] = (object)[];
		}
		 */

		if (count($query) == 0)
		{
			$query = null;
		}

		$ret = array();
		$ret["method"] = "DELETE";
		$ret["tableName"] = $tableName;
		$ret["url"]  = $this->buildUrl((array)$ret);
		$ret["query"] = ( $query ? json_encode($query) : null );

		return array($ret, null);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryDeleteById($tableName, $id)
	{

		list($query, $params) = $this->buildQueryDelete($tableName);

		$query["id"] = $id["value"];
		$query["url"] = $this->buildUrl((array)$query);

		return array($query, $params);

	}

    // -------------------------------------------------------------------------

	protected function buildQueryFields(?array $fields)
	{

		$fieldList = [];

		foreach ((array)$fields as $key => $item)
		{
			//$fieldList[] = $this->escape($key);
			$fieldList[] = $key;
		}

		return $fieldList;

	}

    // -------------------------------------------------------------------------

	protected function buildQueryOrder(?array $orders)
	{

		$sort = [];

		foreach ((array)$orders as $key => $value)
		{
			//$sort[] = array($key => $this->escape($value));
			$sort[] = array($key => $value);
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
