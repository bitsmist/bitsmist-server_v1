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

namespace Bitsmist\v1\Utils;

use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Database handler class
// =============================================================================

class DBUtil
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Returned records count.
	 *
	 * @var		Returned record count
	 */
	public $resultCount = 0;

	/**
	 * Total records count.
	 *
	 * @var		Total record count
	 */
	public $totalCount = 0;

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param	options			Middleware options.
	 */
	public function __construct(?array $options)
	{

		$this->options = $options;

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	/**
	 * Get items.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Data retrieved.
	 */
	public function getItems(ServerRequestInterface $request): ?array
	{

		$id = $request->getAttribute("routeInfo")["args"]["id"];
		$gets = $request->getQueryParams();
		$settings = $request->getAttribute("settings");
		$fields = ($this->options["fields"] ?? null ? $this->buildFields($this->options["fields"], $gets) : null);
		$searches = $this->options["searches"] ?? null;
		$orders = $this->options["orders"] ?? null;
		$limitParamName = $this->options["specialParameters"]["limit"] ?? "_limit";
		$offsetParamName = $this->options["specialParameters"]["offset"] ?? "_offset";
		$orderParamName = $this->options["specialParameters"]["order"] ?? "_order";
		$listIdName = $this->options["specialParameters"]["list"] ?? "list";

		$search = $searches[($gets["_search"] ?? "default")] ?? null;
		$limit = $gets[$limitParamName] ?? null;
		$offset = $gets[$offsetParamName] ?? null;
		$order = $orders[($gets[$orderParamName] ?? "default")] ?? null;

		$data = null;
		$dbs = $request->getAttribute("services")["db"]->getPlugins();
		foreach ($dbs as $dbName => $db)
		{
			switch ($id)
			{
			case $listIdName:
				$search = $this->buildSearchKeys($search, $gets);

				// Get data
				$data = $db->select($settings[$dbName]["tableName"], $fields, $search, $order, $limit, $offset);
				if ($data) {
					$this->resultCount = count($data);
					$this->totalCount = count($data);
				}

				if ($limit)
				{
					// Get total count
					$this->totalCount = $db->getTotalCount();
				}

				break;
			default:
				$data = $db->selectById($settings[$dbName]["tableName"], $fields, [ "field" => $settings[$dbName]["keyName"] ?? "", "value" => $id ]);
				$this->resultCount = count($data);
				$this->totalCount = count($data);
				break;
			}
		}

		return $data;

	}

	// -------------------------------------------------------------------------

   	/**
	 * Insert items.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Result.
	 */
	public function postItems(ServerRequestInterface $request): ?array
	{

		$id = $request->getAttribute("routeInfo")["args"]["id"];
		$settings = $request->getAttribute("settings");
		$fields = $this->options["fields"] ?? null;
		$newIdName = $this->options["specialParameters"]["new"] ?? "new";
		$items = $this->getParamsFromBody($request, $settings["options"] ?? null);

		$data = null;
		$dbs = $request->getAttribute("services")["db"]->getPlugins();
		foreach ($dbs as $dbName => $db)
		{
			// beginTrans()

			try
			{
				$cnt = 0;
				for ($i = 0; $i < count($items); $i++)
				{
					switch ($id)
					{
					case $newIdName:
					case null:
						$item = $this->buildFields($fields, $items[$i]);
						$cnt += $db->insert($settings[$dbName]["tableName"], $item);
						break;
					default:
						$item = $this->buildFields($fields, $items[$i]);
						$cnt += $db->insertWithId($settings[$dbName]["tableName"], $item, ["field" => $settings[$dbName]["keyName"] ?? "", "value" => $id]);
						break;
					}
				}
				$this->resultCount = $cnt;

				// commitTrans($dbName);
			}
			catch (\Exception $e)
			{
				// rollbackTrans();
				throw $e;
			}
		}

        return null;

	}

	// -------------------------------------------------------------------------

	/**
	 * Update items.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Result.
	 */
	public function putItems(ServerRequestInterface $request): ?array
	{

		$id = $request->getAttribute("routeInfo")["args"]["id"];
		$gets = $request->getQueryParams();
		$settings = $request->getAttribute("settings");
		$fields = $this->options["fields"] ?? null;
		$searches = $this->options["searches"] ?? null;
		$listIdName = $this->options["specialParameters"]["list"] ?? "list";
		$items = $this->getParamsFromBody($request, $settings["options"] ?? null);

		$dbs = $request->getAttribute("services")["db"]->getPlugins();
		foreach ($dbs as $dbName => $db)
		{
			// beginTrans();

			try
			{
				switch ($id)
				{
				case $listIdName:
					$item = $this->buildFields($fields, $items[0]);
					$search = $searches[($gets["_search"] ?? "default")] ?? null;
					$search = $this->buildSearchKeys($search, $gets);
					$cnt = $db->update($settings[$dbName]["tableName"], $item, $search);
					break;
				default:
					$item = $this->buildFields($fields, $items[0]);
					$cnt = $db->updateById($settings[$dbName]["tableName"], $item, ["field" => $settings[$dbName]["keyName"] ?? "", "value" => $id]);
					break;
				}
				$this->resultCount = $cnt;

				// commitTrans();
			}
			catch (\Exception $e)
			{
				// rollbackTrans();
				throw $e;
			}
		}

        return null;

	}

	// -------------------------------------------------------------------------

	/**
	 * Delete items.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Result.
	 */
	public function deleteItems(ServerRequestInterface $request): ?array
	{

		$id = $request->getAttribute("routeInfo")["args"]["id"];
		$gets = $request->getQueryParams();
		$settings = $request->getAttribute("settings");
		$searches = $this->options["searches"] ?? null;
		$listIdName = $this->options["specialParameters"]["list"] ?? "list";

		$dbs = $request->getAttribute("services")["db"]->getPlugins();
		foreach ($dbs as $dbName => $db)
		{
			// beginTrans();

			try
			{
				switch ($id)
				{
					case $listIdName:
						$search = $searches[($gets["_search"] ?? "default")] ?? null;
						$search = $this->buildSearchKeys($search, $gets);
						$cnt = $db->delete($settings[$dbName]["tableName"], $search);
						break;
					default:
						$cnt = $db->deleteById($settings[$dbName]["tableName"], ["field" => $settings[$dbName]["keyName"] ?? "", "value" => $id]);
						break;
				}
				$this->resultCount = $cnt;

				// commitTrans();
			}
			catch (\Exception $e)
			{
				// rollbackTrans();
				throw $e;
			}
		}

        return null;

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
  	 * Get parameter arrays from body.
	 *
	 * @param	$request		Request object.
	 * @param	$options		Options.
	 *
	 * @return	array			Parameter arrays.
     */
	private function getParamsFromBody($request, $options)
	{

		$itemsParamName = $options["body"]["specialParameters"]["items"] ?? null;
		$itemParamName = $options["body"]["specialParameters"]["item"] ?? null;

		if ($itemParamName)
		{
			$items = array(($request->getParsedBody())[$itemParamName] ?? null);
		}
		else if ($itemsParamName)
		{
			$items = ($request->getParsedBody())[$itemsParamName] ?? null;
		}
		else
		{
			$items = array($request->getParsedBody());
		}

		return $items;

	}

	// -------------------------------------------------------------------------

	/**
	 * Build parameter array for search query from URL parameters and the spec.
	 *
	 * @param	&$search		Search spec.
	 * @param	$parameters		URL parameters.
	 *
	 * @return	array			Parameter array.
	 */
	private function buildSearchKeys(?array &$search, array $parameters): ?array
	{

		if (!$search) return null;

		for ($i = 0; $i < count($search); $i++)
		{
			$type = $search[$i]["type"] ?? null;
			if ($type == "parameters")
			{
				$this->buildSearchKeys($search[$i]["fields"], $parameters);
			}
			else
			{
				$parameter = $search[$i]["parameter"] ?? null;
				if ($parameter)
				{
					$value  = $parameters[$parameter] ?? null;
					$compareType = $search[$i]["compareType"] ?? null;
					switch ($compareType)
					{
					case "flag":
						if (($parameters[$parameter] ?? null) === null || $value == 0 || $value == "off")
						{
							$search[$i]["value"] = $search[$i]["defaultValue"] ?? null;
						}
						break;
					default:
						if ($value !== null)
						{
							$search[$i]["value"] = $value;
						}
					}
				}
			}
		}

		return $search;

	}

	// -------------------------------------------------------------------------

	/**
	 * Dispatch buildFieldsFromList() or buildFieldsAll() depending on a parameter.
	 *
	 * @param	$fields			Fields spec.
	 * @param	$parameters		URL parameters.
	 *
	 * @return	array			Parameter array.
	 */
	private function buildFields(?array $fields, array $parameters): array
	{

		if ($fields)
		{
			return $this->buildFieldsFromList($fields, $parameters);
		}
		else
		{
			return $this->buildFieldsAll($fields, $parameters);
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Build parameter array for fields from HTTP parameters and the spec.
	 *
	 * @param	$fields			Fields spec.
	 * @param	$parameters		URL parameters.
	 *
	 * @return	array			Parameter array.
	 */
	private function buildFieldsFromList(?array $fields, array $parameters): array
	{

		$result = array();

		foreach ((array)$fields as $key => $item)
		{
			if (is_numeric($key))
			{
				$key = $item;
				$result[$key] = array();
			}
			else
			{
				$result[$key] = $item;
			}

			// Get a value from URL parameter if exists
			$parameterName = $item["parameter"] ?? $key;
			if (array_key_exists($parameterName, $parameters))
			{
				$result[$key]["value"] = $parameters[$parameterName];
			}
		}

		return $result;

	}

	// -------------------------------------------------------------------------

	/**
	 * Build parameter array for fields from HTTP parameters and the spec.
	 *
	 * @param	$fields			Fields spec.
	 * @param	$parameters		URL parameters.
	 *
	 * @return	Parameter array.
	 */
	private function buildFieldsAll(?array $fields, array $parameters): array
	{

		$result = array();

		foreach ((array)$parameters as $key => $value)
		{
			if ($fields == null || array_key_exists($key, $fields))
			{
				$result[$key] = array();
				$result[$key]["value"] = $value;
			}
		}

		return $result;

	}

}
