<?php

/*
 * ACCESS CLASS
 *
 * @DESCRIPTION		communicate with db. work as data layer
 * @AUTHOR			Joel Catantan	
 * @DATE			October 2, 2015
 */
abstract class QueryBuilder
{
	// db config
	private $_dbConfig;

	private $_table 	= '';
	private $_alias 	= '';
	private $_primary 	= '';

	private $_select    = '';
	private $_where		= '';
	private $_join		= '';
	private $_limit		= '';
	private $_groupBy	= '';
	private $_offset	= '';
	private $_orderBy	= '';

	/*
	 * Class Constructor
	 * 
	 * @access	protected
	 * @param	n/a
	 * @return 	void
	 */
    private function GetConfig()
    {
		require_once(realpath(dirname(__FILE__)) .  DIRECTORY_SEPARATOR .'Connect.php');
		
		$this->_dbConfig = $db[$active_connection];
    }

	protected function From($table = '', $alias = '', $primary = '')
	{
		$this->_table   = $table;
		$this->_alias   = $alias;
		$this->_primary = $primary;
	}

	/*
	 * WHERE Clause
	 * 
	 * @access	protected
	 * @param	[string or array] query condition
	 * @param	[boolean] used to create condition for join clause
	 * @return 	void
	 */
	protected function Where($where, $join = false)
	{
		$where2 = '';
		
		if(is_array($where))
		{
			foreach($where as $column => $value)
			{
				$column2 = $column;
				$column2 = explode(' ', $column2);
				
				$condition = '=';
				
				if(count($column2) == 2)
				{
					$condition = $column2[1];
					$column = $column2[0];
				}
				
				$where2 .= ($where2 != '' ? "\n    AND " : '') ."`$column` $condition '$value'";
			}
		}

		else $where2 = $where;
		
		if($join) return $where2;

		$this->_where = 'WHERE '. ($where2 == '' ? '1' : $where2) ."\n";
	}
	
	/*
	 * SELECT Clause
	 * 
	 * @access	protected
	 * @param	[string]
	 * @param	[boolean] false meaning you dont need backticks insertion
	 * @return 	void
	 */
	protected function Select($select, $backticks = true)
	{
		$select2 = '';
		
		if($backticks)
		{
			$select = explode(',', $select);
			
			foreach($select as $volumn)
			{
				$select2 .= ($select2 != '' ? ', ' : '') .'`'. trim($volumn) .'`';
			}
		}
		
		else $select2 = $select;

		$this->_select = 'SELECT '. ($select2 == '``' ? '*' : $select2) . "\n";
	}

	/*
	 * LIMIT and OFFSET Clause
	 * 
	 * @access	protected
	 * @param	[int] for limit
	 * @param	[int] for offset
	 * @return 	void
	 */
	protected function Limit($limit, $offset = 0)
	{
		$this->_limit = "LIMIT $offset, $limit\n";
	}

	/*
	 * GROUP BY Clause
	 * 
	 * @access	protected
	 * @param	[string] list of columns
	 * @return 	void
	 */
	protected function GroupBy($groupby)
	{
		$groupBy2 = '';
		
		$groupby = explode(',', $groupby);
		
		foreach($groupby as $value)
		{
			$groupBy2 .= ($groupBy2 != '' ? ", " : '') .'`'. trim($value) .'`';
		}

		$this->_groupBy = "GROUP BY $groupBy2\n";
	}

	/*
	 * JOIN Clause
	 * 
	 * @access	protected
	 * @param	[int] for limit
	 * @param	[int] for offset
	 * @return 	void
	 */
	protected function Join($table, $on, $type = 'INNER')
	{
		$table2 = explode(' ', $table);

		$table = "`{$table2[0]}`". (isset($table2[1]) ? " `{$table2[1]}`" : '');

		$on = $this->Where($on, true);

		$this->_join .= strtoupper($type). " JOIN $table ON $on \n";
	}

	/*
	 * Construct SQL Query
	 * 
	 * @access	private
	 * @param	[bool] true meaning you want to construct a query for getting get_total
	 * @return 	[string] final query
	 */
	private function SelectQuery($count = false)
	{
		$query = 
			($this->_select == '' ? "SELECT *\n" : $this->_select) .
			'FROM `'. $this->_table .'` `'. $this->_alias ."`\n" .
			($this->_join != '' ? $this->_join : '') .
			$this->_where;

		if(! $count)
		{
			$query .=
				($this->_orderBy != '' ? $this->_orderBy : '') .
				($this->_groupBy != '' ? $this->_groupBy : '') .
				($this->_offset != '' ? $this->_offset : '') .
				($this->_limit != '' ? $this->_limit : '');
		}

		return $query;
	}

	/*
	 * Get record by its ID
	 * 
	 * @access	protected
	 * @param	[int] id of record that you want to fetch
	 * @param	[string] list of column that you want to select
	 * @param	[bool] false meaning you dont need backticks insertion for select clause
	 * @return 	[array] null meaning "no such record"
	 */
	protected function GetByID($id, $select = '', $backticks = true)
	{
		$this->Select($select, $backticks);
		$this->Where(array($this->_primary => $id));

		return current($this->Execute($this->SelectQuery()));
	}

	/*
	 * Update the record
	 * 
	 * @access	protected
	 * @param	[array] new data
	 * @param	[string or array] used for where clause
	 * @return 	[boolean] false meaning update is not succeeded
	 */
	protected function Update($setOfData, $where = '')
	{
		$this->Where($where);

		$data = '';

		foreach($setOfData as $index => $value)
		{
			$data .= ($data != '' ? ', ' : '') ."`$index` = '$value'";
		}

		$query = 'UPDATE `'. $this->_table ."` \n" .
			     'SET ' . $data ."\n" .
			      $this->_where;

		return $this->Execute($query, 2);
	}

	/*
	 * Get total number of results
	 * 
	 * @access	protected
	 * @param	[string or array] used for where clause
	 * @return 	[int]
	 */
	protected function getTotal($where = '')
	{
		$this->Select('COUNT(*) `total_rows`', false);
		$this->Where($where);

		$total = $this->Execute($this->SelectQuery(true));

		return $total[0]['total_rows'];
	}

	/*
	 * Insert new record
	 * 
	 * @access	protected
	 * @param	[array] set of record
	 * @return 	[bool or int] if success, return Insert ID. false meaning not succeeded
	 */
	protected function Insert($data)
	{
		$column2 = '';
		$value2 = '';

		foreach($data as $column => $value)
		{
			$column2 .= ($column2 != '' ? ', ' : '') . "`$column`";
			$value2 .= ($value2 != '' ? ', ' : '') . "'$value'";
		}

		$query = 'INSERT INTO `'. $this->_table ."` ($column2) \n" .
			     "VALUES ($value2)";

		return $this->Execute($query, 1);
	}

	/*
	 * ORDER BY Clause
	 * 
	 * @access	protected
	 * @param	[string or array] array, meaning you want multiple column for order by
	 * @param 	[string] value must be 'asc' or 'desc'. leave it blank if you pass array in first parameter
	 * @return 	void
	 */
	protected function OrderBy($sort, $order = '')
	{
		$sort2 = '';

		if(is_array($sort))
		{
			foreach($sort as $index => $value)
			{
				$sort2 .= ($sort2 != '' ? ", " : ''). "`$index` ". strtoupper($value);
			}
		}

		else $sort2 = "`$sort` ". strtoupper($order);

		$this->_orderBy = "ORDER BY $sort2 \n";
	}

	/*
	 * Get specific record
	 * 
	 * @access	protected
	 * @param	n/a
	 * @return 	[array]
	 */
	protected function Fetch()
	{
		$this->_limit(1);

		return current($this->Execute($this->SelectQuery()));
	}

	/*
	 * Get multiple records
	 * 
	 * @access	protected
	 * @param	n/a
	 * @return 	[multi array]
	 */
	protected function FetchRecords()
	{
		return $this->Execute($this->SelectQuery());
	}

	/*
	 * Execute the constructed query
	 * 
	 * @access	private
	 * @param	[string] sql query that will be executed
	 * @param 	[int] 0 = select record, 1 = insert new record, 2 = update existing record
	 * @return 	[mixed] result of query
	 */
	private function Execute($query, $type = 0)
	{
        if($this->_dbConfig == null)
        {
            $this->GetConfig();
        }

		mysql_connect($this->_dbConfig['host'], $this->_dbConfig['username'], $this->_dbConfig['password'])
			or die(mysql_error());

		mysql_select_db($this->_dbConfig['dbname'])
			or die(mysql_error());

		$result = mysql_query($query)
			or die('<pre>'. mysql_error() ."\n\n$query"); 

		$returnData = array();

		if($type == 0)
		{
			if (mysql_num_rows($result) > 0)
			{
				while($row = mysql_fetch_assoc($result))
				{
					foreach($row as $column => $value)
					{
						if(is_numeric($column))
						{
							unset($row[$column]);
						}
					}

					$returnData[] = $row;
				}

				mysql_free_result($result);
			}
		}
		else
		{
			if($result)
			{
				$returnData = $type == 1 ? mysql_insert_id() : true;
			}
			else
			{
				$returnData = false;
			}
		}
		
		$this->ResetGlobalVariables();

		return $returnData;
	}

	/*
	 * Clear existed query
	 * 
	 * @access	private
	 * @param	[string] sql query that will be executed
	 * @param 	[int] 0 = select record, 1 = insert new record, 2 = update existing record
	 * @return 	[mixed] result of query
	 */
	private function ResetGlobalVariables()
	{
		$this->_select	    = '';
		$this->_where	    = '';
		$this->_join		= '';
		$this->_limit	    = '';
		$this->_groupBy	= '';
		$this->_offset	    = '';
		$this->_orderBy	= '';

        $this->_table       = '';
		$this->_alias       = '';
		$this->_primary     = '';
	}
}