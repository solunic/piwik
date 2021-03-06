<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * 
 * @category Piwik
 * @package Piwik
 */

/**
 * @package Piwik
 * @subpackage Piwik_API
 */
class Piwik_API_DataTableGenericFilter
{
	private static $genericFiltersInfo = null;

	/**
	 * Constructor
	 *
	 * @param $request
	 */
	function __construct( $request )
	{
		$this->request = $request;
	}

	/**
	 * Filters the given data table
	 *
	 * @param Piwik_DataTable  $table
	 */
	public function filter($table)
	{
		$this->applyGenericFilters($table);
	}
	
	/**
	 * Returns an array containing the information of the generic Piwik_DataTable_Filter 
	 * to be applied automatically to the data resulting from the API calls.
	 *
	 * Order to apply the filters:
	 * 1 - Filter that remove filtered rows
	 * 2 - Filter that sort the remaining rows
	 * 3 - Filter that keep only a subset of the results
	 * 4 - Presentation filters
	 * 
	 * @return array  See the code for spec
	 */
	public static function getGenericFiltersInformation()
	{
		if (is_null(self::$genericFiltersInfo))
		{
			self::$genericFiltersInfo = array(
				'Pattern' => array(
									'filter_column' 			=> array('string', 'label'), 
									'filter_pattern' 			=> array('string'),
							),
				'PatternRecursive' => array(
									'filter_column_recursive' 	=> array('string', 'label'), 
									'filter_pattern_recursive' 	=> array('string'),
							),
				'ExcludeLowPopulation'	=> array(
									'filter_excludelowpop' 		=> array('string'), 
									'filter_excludelowpop_value'=> array('float', '0'),
							),
				'AddColumnsProcessedMetrics'	=> array(
									'filter_add_columns_when_show_all_columns'	=> array('integer')
							),
				'AddColumnsProcessedMetricsGoal'	=> array(
									'filter_update_columns_when_show_all_goals'	=> array('integer'),
									'idGoal' => array('string', Piwik_DataTable_Filter_AddColumnsProcessedMetricsGoal::GOALS_OVERVIEW),
							),
				'Sort' => array(
									'filter_sort_column' 		=> array('string'),
									'filter_sort_order' 		=> array('string', 'desc'),
							),
				'Truncate' => array(
									'filter_truncate'			=> array('integer'),
							),
				'Limit' => array(
									'filter_offset' 			=> array('integer', '0'),
									'filter_limit' 				=> array('integer'),
									'keep_summary_row'			=> array('integer', '0'),
							),
			);
		}

		return self::$genericFiltersInfo;
	}
	
	/**
	 * Apply generic filters to the DataTable object resulting from the API Call.
	 * Disable this feature by setting the parameter disable_generic_filters to 1 in the API call request.
	 * 
	 * @param Piwik_DataTable  $datatable
	 * @return bool
	 */
	protected function applyGenericFilters($datatable)
	{
		if($datatable instanceof Piwik_DataTable_Array )
		{
			$tables = $datatable->getArray();
			$filterWasApplied = false;
			foreach($tables as $table)
			{
				$filterWasApplied = $this->applyGenericFilters($table);
			}
			return;
		}
		
		$genericFilters = self::getGenericFiltersInformation();
		
		$filterApplied = false;
		foreach($genericFilters as $filterName => $parameters)
		{
			$filterParameters = array();
			$exceptionRaised = false;
			foreach($parameters as $name => $info)
			{
				// parameter type to cast to
				$type = $info[0];
				
				// default value if specified, when the parameter doesn't have a value
				$defaultValue = null;
				if(isset($info[1]))
				{
					$defaultValue = $info[1];
				}
				
				// third element in the array, if it exists, overrides the name of the request variable
				$varName = $name;
				if(isset($info[2]))
				{
					$varName = $info[2];
				}
				
				try {
					$value = Piwik_Common::getRequestVar($name, $defaultValue, $type, $this->request);
					settype($value, $type);
					$filterParameters[] = $value;
				}
				catch(Exception $e)
				{
					$exceptionRaised = true;
					break;
				}
			}

			if(!$exceptionRaised)
			{
				$datatable->filter($filterName, $filterParameters);
				$filterApplied = true;
			}
		}
		return $filterApplied;
	}
}
