<?php
/**
 *  DataExchangeHandler 
 *  - CLASS for .
 *    + key functions
 *  
 *  
 *  - WUSM - Washington University School of Medicine. 
 * @author David L. Heskett
 * @version 1.0
 * @date 20181108
 * @copyright &copy; 2018 Washington University, School of Medicine, Institute for Infomatics <a href="https://redcap.wustl.edu">redcap.wustl.edu</a>
 */

namespace WashingtonUniversity\ProjectToProjectFieldReplicationExternalModule;

use REDCap;

/** 
 * DataExchangeHandler - .
 */
class DataExchangeHandler
{
	public $message;
	public $flagError;
	public $flagErrorType;
	
	protected $errMsgs;
	protected $flagOverWriteBlanks;

	/**
	 * - set up our defaults.
	 */
	function __construct()
	{
		$this->flagError = null;
		$this->flagErrorType = 0;
		$this->flagOverWriteBlanks = false;
		$this->message = '';

		$this->errMsgs[] = 'Clear';
	}
		
	/**
	 * getErrorMsg - get the error message.
	 */
	public function getErrorMsg()
	{
		return $this->errMsgs[$this->flagErrorType];
	}

	/**
	 * hasErrors - tell us the error flag.
	 */
	public function hasErrors()
	{
		return $this->flagError;
	}

	/**
	 * addErrorMsg - get the error message.
	 */
	public function addErrorMsg($msg = null)
	{
		if ($this->message) {
			$this->message .= "\n";
		}
		
		if ($msg) {
			$this->message .= $msg;
		} else {
			$this->message .= $this->getErrorMsg();
		}
	}

	/**
	 * getMessage - get the debug message.
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * setOverWriteBlanks - set the flag as per REDCap::saveData, false = 'normal', true = 'overwrite'. All blank values will be ignored and will not be saved (existing saved values will be kept), but with 'overwrite', any blank values will overwrite any existing saved data. By default, 'normal' is used..
	 */
	public function setOverWriteBlanks($flag = false)
	{
		$this->flagOverWriteBlanks = $flag;
	}
	
	/**
	 * getSourceRedcapData - get source data, using REDCap::getData method.
	 */
	public function getSourceRedcapData($projectId, $recordsArray)  // read source
	{
		if ($this->flagError) {
			$this->addErrorMsg('getSourceRedcapData');
			return null;
		}

		$format = 'json';
		$filter = '';
		$fields = array();
		
		$events                 = null;  // array or null   An array of unique event names or event_id's, or alternatively a single unique event name or event_id 
		$groups                 = null;  // array or null   An array of unique group names or group_id's, or alternatively a single unique group name or group_id
		$combochecks            = false; // true or false   Sets the format in which data from checkbox fields are returned. for Array, use FALSE
		$exportDataAccessGroups = false; // true or false   Specifies whether or not to return the "redcap_data_access_group" field, when DAGS
		$exportSurveyFields     = false; // true or false   Specifies whether or not to return the survey identifier field (e.g., "redcap_survey_identifier") or survey timestamp fields (e.g., form_name+"_timestamp") when surveys are utilized in the project
		$filter                 = null;  // string or null  Advanced filters for reports, branching logic, Data Quality module
		$exportLabels           = false; // true or false   Sets the format of the data returned.   FALSE = raw data, TRUE = labels 
		$useCsvHeaders          = false; // true or false   Sets the format of the CSV headers returned (only applicable to 'csv' return formats).  FALSE = variable names, TRUE = Field Label text
			
		$records = \REDCap::getData($projectId, $format, $recordsArray, $fields, $events, $groups, $combochecks, $exportDataAccessGroups, $exportSurveyFields, $filter, $exportLabels, $useCsvHeaders);

		if ($records == null) {
			$this->flagError = true;
			$this->addErrorMsg('No records');
			return null;
		}
				
		return $records;
	}

	/**
	 * putDestinationRedcapData - put destination data, using REDCap::saveData method and saveData in json format.
	 */
	public function putDestinationRedcapData($destinationProjectId, $saveData)  // write destination
	{
		if ($this->flagError) {
			$this->addErrorMsg('putDestinationRedcapData');
			return null;
		}

		$overwriteState = 'normal';
		if ($this->flagOverWriteBlanks) {
			$overwriteState = 'overwrite';
		}
		
		$response = \REDCap::saveData($destinationProjectId, 'json', $saveData, $overwriteState, 'YMD');
		
		if ($response == null) {
			$this->flagError = true;
			$this->addErrorMsg('saveData problem');
			return null;
		}
		
		return $response;
	}


	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

}  // ***** end class


?>
