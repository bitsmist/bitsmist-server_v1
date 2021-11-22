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

namespace Bitsmist\v1\Util;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Database model utility class
// =============================================================================

class ModelUtil
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Loader.
	 *
	 * @var		Loader
	 */
	protected $loader = null;

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
	 * @param	$loader			Loader.
	 */
	public function __construct($loader)
	{

		$this->loader = $loader;

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Get items.
	 *
	 * @param	$request		Request.
	 * @param	$response		Response.
	 *
	 * @return	Data retrieved.
	 */
	public function getItems(ServerRequestInterface $request, ResponseInterface $response): ?array
	{

		$id = $this->loader->getRouteInfo("args")["id"];
		$gets = $request->getQueryParams();
		$dbs = $this->loader->getService("dbManager")->getPlugins();
		$spec = $this->loader->getAppInfo("spec");
		$fields = $spec["options"]["fields"] ?? "*";
		$searches = $spec["options"]["searches"] ?? null;
		$orders = $spec["options"]["orders"] ?? null;
		$limitParamName = $spec["options"]["modelOptions"]["params"]["limit"] ?? "_limit";
		$offsetParamName = $spec["options"]["modelOptions"]["params"]["offset"] ?? "_offset";
		$orderParamName = $spec["options"]["modelOptions"]["params"]["order"] ?? "_order";
		$listIdName = $spec["options"]["modelOptions"]["params"]["list"] ?? "list";

		$search = $searches[($gets["_search"] ?? "default")] ?? null;
		$limit = $gets[$limitParamName] ?? null;
		$offset = $gets[$offsetParamName] ?? null;
		$order = $orders[($gets[$orderParamName] ?? "default")] ?? null;

		$data = null;
		foreach ($dbs as $dbName => $db)
		{
			switch ($id)
			{
			case $listIdName:
				$search = $this->buildSearchKeys($search, $gets);

				// Get data
				$data = $db->select($spec[$dbName]["tableName"], $fields, $search, $order, $limit, $offset);
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
				$data = $db->selectById($spec[$dbName]["tableName"], $fields, [ "field" => $spec[$dbName]["keyName"], "value" => $id ]);
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
	 * @param	$response		Response.
	 *
	 * @return	Result.
	 */
	public function postItems(ServerRequestInterface $request, ResponseInterface $response): ?array
	{

		$id = $this->loader->getRouteInfo("args")["id"] ?? null;
		$posts = $request->getParsedBody();
		$spec = $this->loader->getAppInfo("spec");
		$fields = $spec["options"]["fields"] ?? "*";
		$newIdName = $spec["options"]["modelOptions"]["params"]["new"] ?? "new";

		$data = null;
		$dbs = $this->loader->getService("dbManager")->getPlugins();
		foreach ($dbs as $dbName => $db)
		{
			// beginTrans()

			try
			{
				$cnt = 0;
				for ($i = 0; $i < count($posts["items"]); $i++)
				{
					switch ($id)
					{
					case $newIdName:
					case null:
						$item = $this->buildFields($fields, $posts["items"][$i]);
						$cnt += $db->insert($spec[$dbName]["tableName"], $item);
						break;
					default:
						$item = $this->buildFields($fields, $posts["items"][$i]);
						$cnt += $db->insertWithId($spec[$dbName]["tableName"], $item, $posts["items"][$i][$spec[$dbName]["keyName"]]);
						break;
					}
				}
				$this->resultCount = $cnt;

				// commitTrans($dbName);
			}
			catch (Exception $e)
			{
				// rollbackTrans();
				throw $e;
			}
		}

        return $data;

	}

	// -------------------------------------------------------------------------

	/**
	 * Update items.
	 *
	 * @param	$request		Request.
	 * @param	$response		Response.
	 *
	 * @return	Result.
	 */
	public function putItems(ServerRequestInterface $request, ResponseInterface $response): ?array
	{

		$id = $this->loader->getRouteInfo("args")["id"];
		$gets = $request->getQueryParams();
		$posts = $request->getParsedBody();
		$spec = $this->loader->getAppInfo("spec");
		$fields = $spec["options"]["fields"] ?? "*";
		$searches = $spec["options"]["searches"] ?? null;
		$listIdName = $spec["options"]["modelOptions"]["params"]["list"] ?? "list";

		$data = null;
		$dbs = $this->loader->getService("dbManager")->getPlugins();
		foreach ($dbs as $dbName => $db)
		{
			// beginTrans();

			try
			{
				switch ($id)
				{
				case $listIdName:
					$item = $this->buildFields($fields, $posts["items"][0]);
					$search = $searches[($gets["_search"] ?? "default")] ?? null;
					$search = $this->buildSearchKeys($search, $gets);
					$cnt = $db->update($spec[$dbName]["tableName"], $item, $search);
					break;
				default:
					$item = $this->buildFields($fields, $posts["items"][0]);
					$cnt = $db->updateById($spec[$dbName]["tableName"], $item, ["field" => $spec[$dbName]["keyName"], "value" => $id]);
					break;
				}

				// commitTrans();
			}
			catch (Exception $e)
			{
				// rollbackTrans();
				throw $e;
			}
		}

        return $data;

	}

	// -------------------------------------------------------------------------

	/**
	 * Delete items.
	 *
	 * @param	$request		Request.
	 * @param	$response		Response.
	 *
	 * @return	Result.
	 */
	public function deleteItems(ServerRequestInterface $request, ResponseInterface $response): ?array
	{

		$id = $this->loader->getRouteInfo("args")["id"];
		$gets = $request->getQueryParams();
		$spec = $this->loader->getAppInfo("spec");
		$searches = $spec["options"]["searches"] ?? null;
		$listIdName = $spec["options"]["modelOptions"]["params"]["list"] ?? "list";

		$data = null;
		$dbs = $this->loader->getService("dbManager")->getPlugins();
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
						$cnt = $db->delete($spec[$dbName]["tableName"], $search);
						break;
					default:
						$cnt = $db->deleteById($spec[$dbName]["tableName"], ["field" => $spec[$dbName]["keyName"], "value" => $id]);
						break;
				}

				// commitTrans();
			}
			catch (Exception $e)
			{
				// rollbackTrans();
				throw $e;
			}
		}

        return $data;

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
	 * Build parameter array for search query from HTTP parameters and the spec.
	 *
	 * @param	&$search		Search spec.
	 * @param	$parameters		Parameters from HTTP.
	 *
	 * @return	Parameter array.
	 */
	private function buildSearchKeys(?array &$search, array $parameters): ?array
	{

		if ($search && is_array($search))
		{
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
		}

		return $search;

	}

	// -------------------------------------------------------------------------

	/**
	 * Build parameter array for fields from HTTP parameters and the spec.
	 *
	 * @param	&$fields		Fields spec.
	 * @param	$parameters		Parameters from HTTP.
	 *
	 * @return	Parameter array.
	 */
	private function buildFields(array &$fields, array $parameters): array
	{

		if ($fields && is_array($fields))
		{
			foreach ($fields as $key => &$item)
			{
				$parameter = $item["parameter"] ?? $key;
				if (array_key_exists($parameter, $parameters))
				{
					$item["value"] = $parameters[$parameter];
				}
			}
		}

		return $fields;

	}

}
