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

// =============================================================================
//	MySql database class
// =============================================================================

class MysqlDb extends PdoDb
{

	// -------------------------------------------------------------------------
    //  Constructor, Destructor
    // -------------------------------------------------------------------------

	public function __construct($loader, ?array $options)
    {

		parent::__construct($loader, $options);

        $this->props["dbType"] = "MYSQL";

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function getTotalCount()
	{

		$this->logger->debug("query = SELECT FOUND_ROWS()", ["method"=>__METHOD__]);

		$result = 0;

		$cmd = $this->createCommand("SELECT FOUND_ROWS()");
		$cmd->execute();
		$records = $cmd->fetchAll();
		$result = $records[0]["FOUND_ROWS()"];

		return $result;

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	protected function getDBDate()
	{

		$command = "CURRENT_DATE()";

		return $command;

	}

    // -------------------------------------------------------------------------

	protected function getDBDateTime()
	{

		$command = "CONCAT(CURRENT_DATE(), ' ', CURRENT_TIME())";

		return $command;

	}

    // -------------------------------------------------------------------------

	public function getCountField()
	{

		$result = "SQL_CALC_FOUND_ROWS";

		return $result;

	}

}
