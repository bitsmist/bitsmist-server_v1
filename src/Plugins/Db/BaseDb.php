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

use Bitsmist\v1\Plugins\Base\PluginBase;

// =============================================================================
//	Base Database class
// =============================================================================

class BaseDb extends PluginBase
{

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	public function __construct($loader, ?array $options)
	{

		parent::__construct($loader, $options);

		$this->props["dsn"] = $options["dsn"] ?? null;
		$this->props["user"] = $options["user"] ?? null;
		$this->props["password"] = $options["password"] ?? null;
		$this->props["dbType"] = strtoupper($options["type"] ?? null);
		$this->props["connection"] = null;

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Connect to the database.
	 *
	 * @param		string		$setting			Db setting.
	 */
	public function open($setting = null)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Close the database connection.
	 */
	public function close()
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Begin transaction.
	 */
	public function beginTrans()
	{

		$this->logger->debug("", ["method"=>__METHOD__]);

	}

    // -------------------------------------------------------------------------

	/**
	 * Commit transaction.
	 */
	public function commitTrans()
	{

		$this->logger->debug("", ["method"=>__METHOD__]);

	}

    // -------------------------------------------------------------------------

	/**
	 * Rollback transaction.
	 */
	public function rollbackTrans()
	{

		$this->logger->debug("", ["method"=>__METHOD__]);

	}

    // -------------------------------------------------------------------------

	/**
	 * Select data.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param		array			$fields				Fields to retrieve.
     * @param		array			$keys				Search keys.
     * @param		array			$orders				Sort order.
     * @param		int				$limit				Limit.
     * @param		int				$offset				Offset.
	 *
	 * @return		int									Record count.
	 */
	public function select($tableName, $fields, $keys, $orders = null, $limit = null, $offset = null)
	{

		list($sql, $params) = $this->buildQuerySelect($tableName, $fields, $keys, $orders, $limit, $offset);
		$cmd = $this->createCommand($sql);

		return $this->getData($cmd, $params);

	}

    // -------------------------------------------------------------------------

	/**
	 * Select data by id.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param		array			$fields				Fields to retrieve.
	 * @param       string			$id					Target id.
	 *
	 * @return		int									Record count.
	 */
	public function selectById($tableName, $fields, $id)
	{

		list($sql, $params) = $this->buildQuerySelect($tableName, $fields, [["field" => $id["field"], "comparer" => "=", "value" => $id["value"]]]);
		$cmd = $this->createCommand($sql);

		return $this->getData($cmd, $params);

	}

    // -------------------------------------------------------------------------

	/**
	 * Insert data.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param       array       	$fields				Data to insert.
	 * @param       string			$id					Target id.
	 *
	 * @return		int									Record count.
	 */
	public function insert($tableName, $fields)
	{

		list($sql, $params) = $this->buildQueryInsert($tableName, $fields);
		$cmd = $this->createCommand($sql);

		return $this->execute($cmd, $params);

	}

    // -------------------------------------------------------------------------

	/**
	 * Insert data with Id specified.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param       array       	$fields				Data to insert.
	 * @param       string			$id					Target id.
	 *
	 * @return		int									Record count.
	 */
	public function insertWithId($tableName, $fields, $id)
	{

		list($sql, $params) = $this->buildQueryInsert($tableName, $fields);
		$cmd = $this->createCommand($sql);

		return $this->execute($cmd, $params);

	}

	// -------------------------------------------------------------------------

	/**
	 * Update data.
	 *
	 * @param       string			$tableName			Table name.
	 * @param       array       	$fields				Data to update.
	 * @param       array       	$keys				Search keys.
	 *
	 * @return		int									Record count.
	 */
	public function update($tableName, $fields, $keys = null)
	{

		list($sql, $params) = $this->buildQueryUpdate($tableName, $fields, $keys);
		$cmd = $this->createCommand($sql);

		return $this->execute($cmd, $params);

	}

	// -------------------------------------------------------------------------

	/**
	 * Update data by id.
	 *
	 * @param       array       	$tableName			Table name.
	 * @param       array       	$fields				Data to update.
	 * @param       array       	$id					Target id.
	 *
	 * @return		int									Record count.
	 */
	public function updateById($tableName, $fields,  $id)
	{

		list($sql, $params) = $this->buildQueryUpdate($tableName, $fields, [["field" => $id["field"], "comparer" => "=", "value" => $id["value"]]]);
		$cmd = $this->createCommand($sql);

		return $this->execute($cmd, $params);

	}

	// -------------------------------------------------------------------------

	/**
	 * Delete data.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param       array       	$keys           	Search keys.
	 *
	 * @return		int									Record count.
	 */
	public function delete($tableName, $keys)
	{

		list($sql, $params) = $this->buildQueryDelete($tableName, $keys);
		$cmd = $this->createCommand($sql);

		return $this->execute($cmd, $params);

	}

	// -------------------------------------------------------------------------

	/**
	 * Delete data by id.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param       string			$id					Target id.
	 *
	 * @return		int									Result count.
	 */
	public function deleteById($tableName, $id)
	{

		list($sql, $params) = $this->buildQueryDelete($tableName, [["field" => $id["field"], "comparer" => "=", "value" => $id["value"]]]);
		$cmd = $this->createCommand($sql);

		return $this->execute($cmd, $params);

	}

    // -------------------------------------------------------------------------

	/**
	 * Get data from database.
	 *
	 * @param		object			$cmd				Database command object.
	 *
	 * @return		array								Data retrieved.
	 */
	public function getData($cmd)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Return total record count.
	 *
	 * @return		int									Record count.
	 */
	public function getTotalCount()
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Execute the database command.
	 *
	 * @param		string			$cmd				Database command object.
	 *
	 * @return		int									Record count.
	 */
	public function execute($cmd)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Build select query using native query string.
	 *
	 * @param       array       	$query				Native query string.
	 * @param		array			$fields				Fields to retrieve.
     * @param		array			$keys				Search keys.
     * @param		array			$orders				Sort order.
     * @param		int				$limit				Limit.
     * @param		int				$offset				Offset.
	 *
	 * @return 		string								Query string.
	 */
	public function buildQuery($query, $fields = "*", $keys = null, $order = null, $limit = null, $offset = null)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Create the database command.
	 *
	 * @param		string			$sql				SQL.
	 *
	 * @return		object								Data command.
	 */
	public function createCommand($sql)
	{
	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	/**
	 * Build select query.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param		array			$fields				Fields to retrieve.
     * @param		array			$keys				Search keys.
     * @param		array			$orders				Sort order.
     * @param		int				$limit				Limit.
     * @param		int				$offset				Offset.
	 *
	 * @return 		string								Query string.
	 */
	protected function buildQuerySelect($tableName, $fields = "*", $keys = null, $orders = null, $limit = null, $offset = null)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Build insert query.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param       array       	$fields				Item data.
	 *
	 * @return 		string								Query string.
	 */
	protected function buildQueryInsert($tableName, $fields)
	{
	}

	// -------------------------------------------------------------------------

	/**
	 * Create UPDATE query.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param       array       	$fields				Item data.
     * @param		array			$keys				Search keys.
	 *
	 * @return 		string								Query string.
	 *
	 */
	protected function buildQueryUpdate($tableName, $fields, $keys)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Create DELETE query.
	 *
	 * @param       string			$tableName      	Table name.
     * @param		array			$keys				Search keys.
	 *
	 * @return 		string								Query string.
	 */
	protected function buildQueryDelete($tableName, $keys)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Builds the field part of the query.
	 *
	 * @param       array       	$fields				Fields to retrieve.
	 *
	 * @return 		string								Query string.
	 */
	protected function buildQueryFields($fields)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Build the order part of the query.
	 *
	 * @param       array      		 $orders			Sort order.
	 *
	 * @return 		string								Query string.
	 */
	protected function buildQueryOrder($orders)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Build the limit and offset part of the query.
	 *
	 * @param       int				$limit				Limit.
	 * @param       int				$offset				Offset.
	 *
	 * @return 		string								Query string.
	 */
	protected function buildQueryPagination($limit, $offset)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Build the where part of the query.
	 *
	 * @param       array       	$keys				Search keys.
	 *
	 * @return 		string								Query string.
	 */
	protected function buildQueryWhere($keys)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Dispatcher for building compare query for an item.
	 *
	 * @param       array       	$item				Search key item.
	 * @param       array       	&$params			Bind parameters.
	 *
	 * @return 		string								Query string.
	 */
	protected function buildQueryWhereEachCompareType($item, &$params = null)
	{

		$query = "";

		$compareType = $item["compareType"] ?? null;
		switch ($compareType)
		{
		case "flag":
			$query = $this->buildQueryWhereFlag($item, $params);
			break;
		case "flags":
			$query = $this->buildQueryWhereFlags($item, $params);
			break;
		case "items":
			$query = $this->buildQueryWhereItems($item, $params);
			break;
		case "match":
			$query = $this->buildQueryWhereMatch($item, $params);
			break;
		case "match_phrase":
			break;
		case "item":
		default:
			$query = $this->buildQueryWhereItem($item, $params);
			break;
		}

		return $query;

	}

    // -------------------------------------------------------------------------

	/**
	 * Build the item type search query.
	 *
	 * @param		array			&$params		Bind parameters.
	 * @param		array			$item			Search key array.
	 *
	 * @return		array							Query.
	 */
	protected function buildQueryWhereItem($item, &$params = null)
	{
	}

	// -------------------------------------------------------------------------

	/**
	 * Build the items type search query.
	 *
	 * @param		array			&$params		Bind parameters.
	 * @param		array			$item			Search key array.
	 *
	 * @return		array							Query.
	 */
	protected function buildQueryWhereItems($item, &$params = null)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Build the match type search query.
	 *
	 * @param		array			&$params		Bind parameters.
	 * @param		array			$item			Search key array.
	 * @param		array			$values			Query string.
	 */
	protected function buildQueryWhereMatch($item, &$params = null)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Build the flags type search query.
	 *
	 * @param		array			&$params		Bind parameters.
	 * @param		array			$item			Search key array.
	 * @param		array			$values			Query string.
	 */
	protected function buildQueryWhereFlags($item, &$params = null)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Build the flag type search query.
	 *
	 * @param		array			&$params		Bind parameters.
	 * @param		array			$item			Search key array.
	 *
	 * @return		array							Query.
	 */
	protected function buildQueryWhereFlag($item, &$params = null)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Build the compare query.
	 *
	 * @param		array			$field				Field.
	 * @param		array			$parameter			Bind parameter.
	 * @param		array			$value				Value.
	 * @param		array			$comparer			Comparer.
	 *
	 * @return		array								Query.
	 */
	protected function buildCompare($field, $parameter = "", $value = null, $comparer = "=")
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Build the query value.
	 *
	 * @param		string			$key				Field name.
	 * @param		object			$item				Field item.
	 *
	 * @return		array								Query value.
	 */
	protected function buildValue($key, $item)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Bind parameters.
	 *
	 * @param		object			&$cmd				Database command object.
	 * @param		array			$params				Parameters and values to bind.
	 */
	protected function assignParams(&$cmd, $params)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Bind referece to parameters variables.
	 *
	 * @param		object			$cmd				Database command.
	 * @param		string			$name				Field name.
	 * @param		int				$dataType			Field type.
	 * @param		object			$value				Variable to bind.
	 */
	protected function bindParam($cmd, $name, $dataType, $value = null)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Bind parameter values.
	 *
	 * @param		object			$cmd				Database command.
	 * @param		string			$name				Field name.
	 * @param		int				$dataType			Field type.
	 * @param		object			$value				Value to bind.
	 */
	public function bindValue($cmd, $name, $dataType, $value)
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Return database dependent command to get database date.
	 *
	 * @return		string								Command to get date.
	 */
	protected function getDBDate()
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Return database dependent command to get database date and time.
	 *
	 * @return		string								Command to get date.
	 */
	protected function getDBDateTime()
	{
	}

    // -------------------------------------------------------------------------

	/**
	 * Return database dependent escaped command.
	 *
	 * @return		string								Escaped command.
	 */
	protected function escape($sql)
	{

		return $sql;

	}

    // -------------------------------------------------------------------------

	/**
	 * Return database dependent escaped keyword for like search.
	 *
	 * @param		string			$keyword			Keyword to escape.
	 *
	 * @return		string								Escaped keyword.
	 */
	protected function escapeLike($keyword)
	{

		return $sql;

	}

    // -------------------------------------------------------------------------

	/**
	 * Returns record count field.
	 *
	 * @return		string							Record count.
	 */
	protected function getCountField()
	{

		return "";

	}

    // -------------------------------------------------------------------------

	/**
	 * Check whether the value passed is the database command.
	 *
	 * @param		object			$value				Value to examine.
	 *
	 * @return		bool								True when value is the database command.
	 */
	protected function isDbCommand($value)
	{

		if (substr($value, 0, 1) == "@" && substr($value, -1, 1) == "@")
		{
			return True;
		}
		else
		{
			return False;
		}

	}

    // -------------------------------------------------------------------------

	/**
	 * Push to the stack when value is not null.
	 *
	 * @param		object			&$stack				Stack.
	 * @param		object			$value				Value to push.
	 */
	protected function pushStack(&$stack, $value)
	{

		if ($value)
		{
			array_push($stack, $value);
		}

	}

    // -------------------------------------------------------------------------

	/**
	 * Pop from the stack.
	 *
	 * @param		object			&$stack				Stack.
	 * @return		object								Value popped from the stack.
	 */
	protected function popStack(&$stack)
	{

		$value = array_pop($stack);

		return $value;

	}

}
