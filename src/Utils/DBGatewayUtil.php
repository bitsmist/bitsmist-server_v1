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

use Bitsmist\v1\Utils\Util;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Database Gateway utility class
// =============================================================================

class DBGatewayUtil
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Data retrieved from DB.
	 *
	 * @var	array	Data
	 */
	public $results;

	/**
	 * Data retrieved from the last DB.
	 *
	 * @var	array	Data
	 */
	public $lastResult;

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
	//	Public
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

		// Get settings
		$settings = $request->getAttribute("settings");
		$listIdName = $settings["options"]["query"]["specialParameters"]["list"] ?? "list";

		// Get Parameters
		$id = $this->getID($request);
		list ($limit, $offset) = $this->getLimitOffset($settings, $request->getQueryParams());

		$this->initResults();
		foreach ($request->getAttribute("services")["db"] as $dbName => $db)
		{
			$count = $totalCount = 0;
			$items = null;
			$fields = $this->getField($settings, null, $db->getOption("fields"));

			switch ($id)
			{
			case $listIdName:
				$search = $this->getSearch($settings, $request->getQueryParams(), $db->getOption("searches"));
				$order = $this->getOrder($settings, $request->getQueryParams());
				$items = $db->select($settings[$dbName]["tableName"], $fields, $search, $order, $limit, $offset);
				if ($items) {
					$count = $totalCount = count($items);
				}

				// Get total count
				if ($limit)
				{
					$totalCount = $db->getTotalCount();
				}
				break;
			default:
				$items = $db->selectById($settings[$dbName]["tableName"], $fields, [ "fieldName" => $settings[$dbName]["keyName"] ?? "", "value" => $id ]);
				if ($items) {
					$count = $totalCount = count($items);
				}
				break;
			}

			$this->addResult($count, $totalCount, $items);
		}

		return $items;

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

		// Get settings
		$settings = $request->getAttribute("settings");
		$newIdName = $settings["options"]["query"]["specialParameters"]["new"] ?? "new";

		// Get parameters
		$id = $this->getID($request);
		$items = Util::getItemsFromBody($request, $settings["options"] ?? null);

		$this->initResults();
		foreach ($request->getAttribute("services")["db"] as $dbName => $db)
		{
			$cnt = 0;
			$item = null;

			$db->beginTrans();
			try
			{
				for ($i = 0; $i < count($items); $i++)
				{
					switch ($id)
					{
					case $newIdName:
						$item = $this->getField($settings, $items[$i], $db->getOption("fields"));
						$cnt += $db->insert($settings[$dbName]["tableName"], $item);
						break;
					default:
						$item = $this->getField($settings, $items[$i], $db->getOption("fields"));
						$cnt += $db->insertWithId($settings[$dbName]["tableName"], $item, ["fieldName" => $settings[$dbName]["keyName"] ?? "", "value" => $id]);
						break;
					}
				}

				$db->commitTrans($dbName);
			}
			catch (\Exception $e)
			{
				$db->rollbackTrans();
				throw $e;
			}

			$this->addResult($cnt, $cnt, $item);
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

		// Get settings
		$settings = $request->getAttribute("settings");
		$listIdName = $settings["options"]["query"]["specialParameters"]["list"] ?? "list";

		// Get parameters
		$id = $this->getID($request);
		$items = Util::getItemsFromBody($request, $settings["options"] ?? null);

		$this->initResults();
		foreach ($request->getAttribute("services")["db"] as $dbName => $db)
		{
			$cnt = 0;
			$item = null;

			$db->beginTrans();
			try
			{
				switch ($id)
				{
				case $listIdName:
					$item = $this->getField($settings, $items[0], $db->getOption("fields"));
					$search = $this->getSearch($settings, $request->getQueryParams(), $db->getOption("searches"));
					$cnt = $db->update($settings[$dbName]["tableName"], $item, $search);
					break;
				default:
					$item = $this->getField($settings, $items[0], $db->getOption("fields"));
					$cnt = $db->updateById($settings[$dbName]["tableName"], $item, ["fieldName" => $settings[$dbName]["keyName"] ?? "", "value" => $id]);
					break;
				}
				$this->resultCount = $cnt;
				$this->totalCount = $cnt;

				$db->commitTrans();
			}
			catch (\Exception $e)
			{
				$db->rollbackTrans();
				throw $e;
			}

			$this->addResult($cnt, $cnt, $item);
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

		// Get settings
		$settings = $request->getAttribute("settings");
		$listIdName = $settings["options"]["query"]["specialParameters"]["list"] ?? "list";

		// Get parameters
		$id = $this->getID($request);

		$this->initResults();
		foreach ($request->getAttribute("services")["db"] as $dbName => $db)
		{
			$cnt = 0;

			$db->beginTrans();
			try
			{
				switch ($id)
				{
				case $listIdName:
					$search = $this->getSearch($settings, $request->getQueryParams(), $db->getOption("searches"));
					$cnt = $db->delete($settings[$dbName]["tableName"], $search);
					break;
				default:
					$cnt = $db->deleteById($settings[$dbName]["tableName"], ["fieldName" => $settings[$dbName]["keyName"] ?? "", "value" => $id]);
					break;
				}

				$db->commitTrans();
			}
			catch (\Exception $e)
			{
				$db->rollbackTrans();
				throw $e;
			}

			$this->addResult($cnt, $cnt, null);
		}

        return null;

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	/**
	 * Init result variables.
	 */
	protected function initResults()
	{

		$this->results = array();

	}

	// -------------------------------------------------------------------------

	/**
	 * Add result to result array.
	 *
	 * @param	$count			Record count.
	 * @param	$totalCount		Total count.
	 * @param	$items			Data..
	 */
	protected function addResult($count, $totalCount, $items)
	{

		$result = array(
			"count"			=> $count,
			"totalCount"	=> $totalCount,
			"items"			=> $items,
		);

		$this->results[] = $result;
		$this->lastResult = $result;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get id value from URL parameters.
	 *
	 * @param	$settings		Search settings.
	 * @param	$params			URL parameters.
	 *
	 * @return	array			Parameter array.
	 */
	protected function getID($request)
	{

		$id = $request->getAttribute("routeInfo")["args"]["id"] ?? "";

		return $id;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get field parameter array from settings and URL parameters.
	 *
	 * @param	$settings		Search settings.
	 * @param	$params			URL parameters.
	 * @param	$dbSettings		DB specific search settings.
	 *
	 * @return	array			Parameter array.
	 */
	protected function getField($settings, $params, $dbSettings = null)
	{

		$fieldSettings = Util::convertToAssocArray($this->options["fields"] ?? null);

		// Merge DB specific options
		if ($dbSettings)
		{
			$fieldSettings = array_replace_recursive($fieldSettings, Util::convertToAssocArray($dbSettings));
		}

		if ($fieldSettings)
		{
			return $this->buildFieldsFromSettings($fieldSettings, $params);
		}
		else
		{
			return $this->buildFieldsFromParameters($params);
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Get search parameter array from settings and URL parameters.
	 *
	 * @param	$settings		Search settings.
	 * @param	$params			URL parameters.
	 * @param	$dbSettings		DB specific search settings.
	 *
	 * @return	array			Parameter array.
	 */
	protected function getSearch($settings, $params, $dbSettings = null)
	{

		$searchParamName = $settings["options"]["query"]["specialParameters"]["search"] ?? "_search";
		$searchPattern = $params[$searchParamName] ?? "default";
		$searchSettings = $this->options["searches"][$searchPattern] ?? null;

		// Merge DB specific options
		if ($dbSettings && dbSettings[$searchPattern])
		{
			$searchSettings = array_replace_recursive($searchSettings, $dbSettings[$searchPattern]);
		}

		return  $this->buildSearchKeys($searchSettings, $params);

	}

	// -------------------------------------------------------------------------

	/**
	 * Get order parameter array from settings and URL parameters.
	 *
	 * @param	$settings		Search settings.
	 * @param	$params			URL parameters.
	 * @param	$dbSettings		DB specific search settings.
	 *
	 * @return	array			Parameter array.
	 */
	protected function getOrder($settings, $params, $dbSettings = null)
	{

		$orderParamName = $settings["options"]["query"]["specialParameters"]["order"] ?? "_order";
		$orderPattern = $params[$orderParamName] ?? "default";
		$orderSettings = $this->options["orders"][$orderPattern] ?? null;

		// Merge DB specific options
		if ($dbSettings && $dbSettings[$orderPattern])
		{
			$orderSettings = array_replace_recursive($orderSettings, $dbSettings[$orderPattern]);
		}

		return $orderSettings;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get limit/offset value from URL parameters.
	 *
	 * @param	$settings		Search settings.
	 * @param	$params			URL parameters.
	 *
	 * @return	array			Parameter array.
	 */
	protected function getLimitOffset($settings, $params)
	{

		$limitParamName = $settings["options"]["query"]["specialParameters"]["limit"] ?? "_limit";
		$limit = $params[$limitParamName] ?? null;

		$offsetParamName = $settings["options"]["query"]["specialParameters"]["offset"] ?? "_offset";
		$offset = $params[$offsetParamName] ?? null;

		return array($limit, $offset);

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
	 * Build parameter array for search query from URL parameters and settings.
	 *
	 * @param	$searchSettings	Search settings.
	 * @param	$parameters		URL parameters.
	 *
	 * @return	array			Parameter array.
	 */
	private function buildSearchKeys(?array $searchSettings, array $params): ?array
	{

		$ret = array();

		for ($i = 0; $i < count((array)$searchSettings); $i++)
		{
			$item = $searchSettings[$i];

			$type = $searchSettings[$i]["type"] ?? null;
			if ($type == "parameters")
			{
				$item["fields"] = $this->buildSearchKeys($searchSettings[$i]["fields"], $params);
			}
			else
			{
				$parameterName = $searchSettings[$i]["parameterName"] ?? null;
				if ($parameterName)
				{
					$value = $params[$parameterName] ?? null;
					$compareType = $searchSettings[$i]["compareType"] ?? null;
					switch ($compareType)
					{
					case "flag":
						if (($params[$parameterName] ?? null) === null || $value == 0 || $value == "off")
						{
							$item["value"] = $searchSettings[$i]["defaultValue"] ?? null;
						}
						break;
					default:
						if ($value !== null)
						{
							$item["value"] = $value;
						}
					}
				}
			}

			$ret[] = $item;
		}

		return $ret;

	}

	// -------------------------------------------------------------------------

	/**
	 * Build fields array from both URL parameters and settings.
	 *
	 * @param	$fields			Fields settings.
	 * @param	$parameters		URL parameters.
	 *
	 * @return	array			Parameter array.
	 */
	private function buildFieldsFromSettings(?array $fields, ?array $parameters): array
	{

		$result = array();

		foreach ((array)$fields as $key => $item)
		{
			// Get a value from URL parameter if exists
			if ($parameters)
			{
				$parameterName = $item["parameterName"] ?? $key;
				if (array_key_exists($parameterName, $parameters))
				{
					$item["value"] = $parameters[$parameterName];
				}
			}

			if (array_key_exists("value", (array)$item))
			{
				$result[$key] = $item;
			}
		}

		return $result;

	}

	// -------------------------------------------------------------------------

	/**
	 * Build fields array from URL parameters.
	 *
	 * @param	$parameters		URL parameters.
	 *
	 * @return	Parameter array.
	 */
	private function buildFieldsFromParameters(?array $parameters): ?array
	{

		$result = array();

		foreach ((array)$parameters as $key => $value)
		{
			$result[$key] = array("value" => $value);
		}

		return $result;

	}

}
