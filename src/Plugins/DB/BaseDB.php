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

use Bitsmist\v1\Plugins\Base\PluginBase;

// =============================================================================
//	Base Database class
// =============================================================================

abstract class BaseDB extends PluginBase
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Logger.
	 *
	 * @var		Logger
	 */
	protected $logger = null;

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	public function __construct($name, array $options = null, $container)
	{

		parent::__construct($name, $options, $container);

		$this->logger = $container["services"]["logger"];

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
	 * @param		string		$setting			DB setting.
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
	public function select(string $tableName, ?array $fields = null, ?array $keys = null, ?array $orders = null, ?int $limit = null, ?int $offset = null)
	{

		list($query, $params) = $this->buildQuerySelect($tableName, $fields, $keys, $orders, $limit, $offset);
		$cmd = $this->createCommand($query);

		return $this->getData($cmd, $params);

	}

    // -------------------------------------------------------------------------

	/**
	 * Select data by id.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param		array			$fields				Fields to retrieve.
	 * @param       array			$id					Target id.
	 *
	 * @return		int									Record count.
	 */
	public function selectById(string $tableName, array $fields = null, array $id)
	{

		list($query, $params) = $this->buildQuerySelectById($tableName, $fields, $id);
		$cmd = $this->createCommand($query);

		return $this->getData($cmd, $params);

	}

    // -------------------------------------------------------------------------

	/**
	 * Insert data.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param       array       	$fields				Data to insert.
	 *
	 * @return		int									Record count.
	 */
	public function insert(string $tableName, array $fields)
	{

		list($query, $params) = $this->buildQueryInsert($tableName, $fields);
		$cmd = $this->createCommand($query);

		return $this->execute($cmd, $params);

	}

    // -------------------------------------------------------------------------

	/**
	 * Insert data with Id specified.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param       array       	$fields				Data to insert.
	 * @param       array			$id					Target id.
	 *
	 * @return		int									Record count.
	 */
	public function insertWithId(string $tableName, array $fields, array $id)
	{

		list($query, $params) = $this->buildQueryInsertWithId($tableName, $fields, $id);
		$cmd = $this->createCommand($query);

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
	public function update(string $tableName, array $fields, ?array $keys = null)
	{

		list($query, $params) = $this->buildQueryUpdate($tableName, $fields, $keys);
		$cmd = $this->createCommand($query);

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
	public function updateById(string $tableName, array $fields,  array $id)
	{

		list($query, $params) = $this->buildQueryUpdateById($tableName, $fields, $id);
		$cmd = $this->createCommand($query);

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
	public function delete(string $tableName, ?array $keys = null)
	{

		list($query, $params) = $this->buildQueryDelete($tableName, $keys);
		$cmd = $this->createCommand($query);

		return $this->execute($cmd, $params);

	}

	// -------------------------------------------------------------------------

	/**
	 * Delete data by id.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param       array			$id					Target id.
	 *
	 * @return		int									Result count.
	 */
	public function deleteById(string $tableName, array $id)
	{

		list($query, $params) = $this->buildQueryDeleteById($tableName, $id);
		$cmd = $this->createCommand($query);

		return $this->execute($cmd, $params);

	}

    // -------------------------------------------------------------------------

	/**
	 * Create the database command.
	 *
	 * @param		object			$query				Query.
	 *
	 * @return		object								Data command.
	 */
	public function createCommand($query)
	{

		return $query;

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
	 * @param		object			$cmd				Database command object.
	 *
	 * @return		int									Record count.
	 */
	public function execute($cmd)
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
	abstract protected function buildQuerySelect(string $tableName, ?array $fields = null, ?array $keys = null, ?array $orders = null, ?int $limit = null, ?int $offset = null);

    // -------------------------------------------------------------------------

	/**
	 * Build select by id query.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param		array			$fields				Fields to retrieve.
	 * @param       array			$id					Target id.
	 *
	 * @return 		string								Query string.
	 */
	abstract protected function buildQuerySelectById(string $tableName, array $fields = null, array $id);

    // -------------------------------------------------------------------------

	/**
	 * Build insert query.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param       array       	$fields				Item data.
	 *
	 * @return 		string								Query string.
	 */
	abstract protected function buildQueryInsert(string $tableName, array $fields);

    // -------------------------------------------------------------------------

	/**
	 * Build insert with id query.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param		array			$fields				Fields to retrieve.
	 * @param       array			$id					Target id.
	 *
	 * @return 		string								Query string.
	 */
	abstract protected function buildQueryInsertWithId(string $tableName, array $fields, array $id);

	// -------------------------------------------------------------------------

	/**
	 * Build update query.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param       array       	$fields				Item data.
     * @param		array			$keys				Search keys.
	 *
	 * @return 		string								Query string.
	 *
	 */
	abstract protected function buildQueryUpdate(string $tableName, array $fields, ?array $keys = null);

    // -------------------------------------------------------------------------

	/**
	 * Build update by id query.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param		array			$fields				Fields to retrieve.
	 * @param       array			$id					Target id.
	 *
	 * @return 		string								Query string.
	 */
	abstract protected function buildQueryUpdateById(string $tableName, array $fields, array $id);

    // -------------------------------------------------------------------------

	/**
	 * Build delete query.
	 *
	 * @param       string			$tableName      	Table name.
     * @param		array			$keys				Search keys.
	 *
	 * @return 		string								Query string.
	 */
	abstract protected function buildQueryDelete(string $tableName, ?array $keys = null);

    // -------------------------------------------------------------------------

	/**
	 * Build delete by id query.
	 *
	 * @param       string			$tableName      	Table name.
	 * @param       array			$id					Target id.
	 *
	 * @return 		string								Query string.
	 */
	abstract protected function buildQueryDeleteById(string $tableName, array $id);

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
	 * Check whether the value passed is the database command.
	 *
	 * @param		object			$value				Value to examine.
	 *
	 * @return		bool								True when value is the database command.
	 */
	protected function isDBCommand(string $value)
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
	protected function pushStack(array &$stack, $value)
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
	protected function popStack(array &$stack)
	{

		$value = array_pop($stack);

		return $value;

	}

}
