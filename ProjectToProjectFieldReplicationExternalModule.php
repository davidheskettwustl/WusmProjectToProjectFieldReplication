<?php
/// An external module to help create Field copy from one Project to another Project as a source and destination relationship.
/**
 *  ProjectToProjectFieldReplicationExternalModule 
 *  - Project To Project Field Replication - manages field data copy from one project to another, a source and destination relationship.
 *    + key methods
 *       * redcap_save_record()
 * 
 *  - The EM handles field copy or replication from one project to another.
 *  
 *  
 *  - WUSM - Washington University School of Medicine. 
 * @author David L. Heskett
 * @version 1.5
 * @date 20181016
 * @copyright &copy; 2018 Washington University, School of Medicine, Institute for Infomatics <a href="https://redcap.wustl.edu">redcap.wustl.edu</a>
 */

namespace WashingtonUniversity\ProjectToProjectFieldReplicationExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

include_once 'DataExchangeHandler.php';

use REDCap;
use Logging;

class ProjectToProjectFieldReplicationExternalModule extends AbstractExternalModule
{
	private $version;      /**< the version of the module */ 
	private $projectId;    /**< holds a project ID */
	private $show;         /**< show project page: show = true, default (no show) = false */ 
	private $message;
	private $errorMsg;
	private $globalProject;
	
	// Source
	private $codebookSource;
	private $listFieldsSource;
	private $listFieldNamesSource;
	
	// Destination
	private $codebookDestination;
	private $listFieldsDestination;
	
	// PROJECT SETTINGS
	private $debugModeProject;

	private $destinationProjectId;
	private $overwrite_destination_blanks_flag;

	private $sourceProjectFlag;  //source_project_flag

	// SYSTEM SETTINGS
	private $debugModeSystem;
	private $debugModeLog;

	// GENERAL based on System and Project settings
	private $debugFlagProject;
	private $debugFlagSystem;
	private $debugLogFlag;
	private $debugFlag;
	private $flagOverwriteBlanks;
	
	// module version
	CONST MODULE_VERSION = '1.5';

	CONST PROJECT_NAME = 'Project To Project Field Replication';

	CONST ERR_MSG_NOT_ALLOWED_COPY_TO_DESTINATION = 'NOT ALLOWED to copy to Destination';

	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

	/**
	 * - set up our defaults.
	 */
	function __construct($projectId = null)
	{
		parent::__construct();
		
		$this->version = self::MODULE_VERSION;
		$this->projectId = null;
		$this->message = '';
		$this->errorMsg = '';
		$this->debugFlag = false;
		$this->debugLogFlag = false;

		$this->globalProject = null;
		
		// load project level settings if we are in a project
		if ($projectId !== null) {
			$this->projectId = $projectId;
			$flag = 'project';
			
		} else {
			$flag = 'system';
		}
		
		// PROJECT SETTINGS
		// SYSTEM SETTINGS
		$this->loadConfig($flag);  // get both system and project settings
	}

	/**
	 * methodInitialize - initializations here.
	 */
	public function methodInitialize() 
	{
	}

	/**
	 * loadSystemConfig - set up our system configs.
	 */
	public function loadSystemConfig()
	{
		$this->debugModeSystem = $this->getSystemSetting('debug_mode_system');
		$this->debugModeLog    = $this->getSystemSetting('debug_mode_log');
	}

	/**
	 * loadProjectConfig - set up our project configs.
	 */
	public function loadProjectConfig()
	{
		//source_project_flag
		$this->sourceProjectFlag = ($this->getProjectSetting('source_project_flag') ? true : false);

		// if we are source, we need a destination
		if ($this->sourceProjectFlag) {
			$this->destinationProjectId = $this->getProjectSetting('destination_project_id');
		} else {
			// if we are destination, we do not need a destination
			$this->destinationProjectId  = null;
		}
		
		//$this->destinationProjectId = $this->getProjectSetting('destination_project_id');
		
		// exclude_copy
		// Fields that will be ignored for the purpose of copy, the data will be cleared before saving to destination.
		$this->field_list_exclude_copy         = $this->getProjectSetting('field_list_exclude_copy');
		
		// when TRUE  (overwrite):  If source has blank fields then destination fields will be blanked.  
		// when FALSE (normal)   :  If source has blank fields then destination fields will be left as is.
		// see REDCap::saveData overwriteBehavior: normal (default), overwrite
		$this->overwrite_destination_blanks_flag         = $this->getProjectSetting('overwrite_destination_blanks_flag');		
		$this->flagOverwriteBlanks = ($this->overwrite_destination_blanks_flag ? true : false);
		
		$this->debugModeProject     = $this->getProjectSetting('debug_mode_project');
	}

	/**
	 * loadProjectConfigDefaults - set up our default configs for project level.
	 */
	public function loadProjectConfigDefaults()
	{
		// 
		$this->destinationProjectId  = null;

		$this->projectId             = 0;

		$this->debugModeProject      = 0;
	}

	/**
	 * loadConfig - set up our configs main.
	 */
	public function loadConfig($flag = 'system')
	{
		// always load system level settings
		$this->loadSystemConfig();

		// if we are a project load the project level settings
		if ($flag == 'project') {
			$this->loadProjectConfig();
		} else {
			$this->loadProjectConfigDefaults();
		}
		
		$this->debugFlagProject = $this->debugModeProject;
		$this->debugFlagSystem  = $this->debugModeSystem;
		$this->debugLogFlag     = ($this->debugModeLog ? true : false);
		$this->debugFlag        = ($this->debugModeSystem || $this->debugModeProject);
	}

	/**
	 * showJson - show a json parsable page.
	 */
	public function showJson($rsp) 
	{
		$jsonheader = 'Content-Type: application/json; charset=utf8';
		header($jsonheader);
		echo $rsp;
	}

	/**
	 * debugLog - (debug version) Simplified Logger messaging.
	 */
	public function debugLog($msg = '', $logDisplayMsg = 'P2PFieldReplicationEM')
	{
		if (!$this->debugLogFlag) {  // log mode off
			return;
		}
		
		// $sql, $table, $event, $record, $display, $descrip="", $change_reason="",
		//									$userid_override="", $project_id_override="", $useNOW=true, $event_id_override=null, $instance=null
		
		$logSql         = '';
		$logTable       = '';
		$logEvent       = 'OTHER';  // 'event' what events can we have?  DATA_EXPORT, INSERT, UPDATE, MANAGE, OTHER
		$logRecord      = '';
		$logDisplay     = $logDisplayMsg; // 'data_values'  (table: redcap_log_event)
		$logDescription = $msg;  // 'description' limit in size is 100 char (auto chops to size)
		
		Logging::logEvent($logSql, $logTable, $logEvent, $logRecord, $logDisplay, $logDescription);
	}
		
	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

	/**
	 * getProjectId - working project ID
	 */
	public function getProjectId() 
	{
		return $this->projectId;
	}

	/**
	 * setProjectId - working project ID
	 */
	public function setProjectId($id) 
	{
		$this->projectId = $id;
	}
	
	/**
	 * setDebugFlag - debug flag on
	 */
	public function setDebugFlag() 
	{
		$this->debugFlag = true;
	}

	/**
	 * clearDebugFlag - debug flag on
	 */
	public function clearDebugFlag() 
	{
		$this->debugFlag = false;
	}

	/**
	 * getVersion - get the version.
	 * @return The version number.
	 */
	public function getVersion()
	{
		return $this->version;
	}

	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	
	// Hooks
	// **********************************************************************	

	
	/**
	 * redcap_save_record - hook.
	 */
	public function redcap_save_record($projectId, $recordId, $instrument, $eventId, $groupId, $surveyHash, $responseId, $repeatInstance)
	{
		// get SOURCE configuration info, so we can know where the DESTINATION is (the DESTINATION Project ID)
		$this->loadConfig('project');

		$this->saveToRedcapData($projectId, $recordId, $instrument, $eventId, $groupId, $surveyHash, $responseId, $repeatInstance);
	}

	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

	/**
	 * saveToRedcapData - command and control.
	 */
	public function saveToRedcapData($projectIdSource, $recordId, $instrument, $eventId, $groupId, $surveyHash, $responseId, $repeatInstance)
	{
		// **********************************************************************	
		// Initialize
		// **********************************************************************	
		// Destination
		$projectIdDestination = $this->destinationProjectId;
		
		// **********************************************************************	
		if (($projectIdSource > 0) && ($projectIdDestination != null)) {  // keep Working

		// **********************************************************************	
		// Process Data
		// **********************************************************************	
			$this->setRememberGlobalProj();  // Initialize piece, vital we keep track of the global Proj so REDCap stays in shape for other internal handling.

			// **********************************************************************	
			// Process Source Data
			// Process Destination Data
			// Save Data
			// **********************************************************************		
			$errorFlag = $this->processSourceToDestination($projectIdSource, $projectIdDestination, $recordId);
				
			// **********************************************************************	
			// Finish
			// **********************************************************************	
			$this->finishProcessAndLog($errorFlag);
	
			// keep the global Proj in 
			$this->getRememberGlobalProj();  // vital we keep track of the global Proj so REDCap stays in shape for other internal handling.
		}
	}

	/**
	 * finishProcessAndLog - finish up and log handling.
	 */
	public function finishProcessAndLog($errorFlag)
	{
		if ($errorFlag) {
			// some failure
			//
			$errorMsg = $this->errorMsg;
			// log the error.
			$this->handleLogging($errorMsg);			
		} else {
			// success
			// do we want to log the success and info perhaps about it?
			//$this->handleLogging();
		}
	}

	/**
	 * handleLogging - finish up and log handling.
	 */
	public function handleLogging($msg)
	{
		if ($this->debugFlag) {
			//self::PROJECT_NAME . 
			// $this->message
			$this->debugLog($msg);
		}
	}

	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

	/**
	 * setRememberGlobalProj - store the global Proj project value, remember it.
	 */
	public function setRememberGlobalProj()
	{
		global $Proj;
		$this->globalProject = $Proj;
	}

	/**
	 * getRememberGlobalProj - fetch the global Proj project value.
	 */
	public function getRememberGlobalProj()
	{
		global $Proj;
		$Proj = $this->globalProject;
		
		return $this->globalProject;
	}

	/**
	 * processSourceToDestination - 
	 */
	public function processSourceToDestination($projectIdSource, $projectIdDestination, $record)
	{
		$errFlag = false;
		
		// set up the data handler
		$x = new DataExchangeHandler();
		
		$isAllowed   = $this->isAllowedDestination($projectIdSource, $projectIdDestination);
		if(!$isAllowed) {
			$this->errorMsg = self::ERR_MSG_NOT_ALLOWED_COPY_TO_DESTINATION . ' Source: ' . $projectIdSource . ' Dest: ' . $projectIdDestination;
			$errFlag = true;
			return $errFlag; // NOT ALLOWED
		}
		
		$x->setOverWriteBlanks($this->flagOverwriteBlanks);  // the flag true will blank out destination fields if source fields are also blank.
	
		$records = array($record);
		
		$sourceData = $x->getSourceRedcapData($projectIdSource, $records);

		$sourceData = $this->filterSourceData($sourceData);  // use excludes to clean out unwanted field data
		
		$output = $x->putDestinationRedcapData($projectIdDestination, $sourceData);

		if ($x->hasErrors()) {
			$this->errorMsg = $x->getMessage();
			$errFlag = true;
		}
		
		return $errFlag;
	}
	
	/**
	 * getFilterList - get list of exclude copy fields from the config.
	 */
	private function getFilterList()
	{
		$filterList = array();
		$excludeList = $this->field_list_exclude_copy;
		
		if ($excludeList == null) { // nothing to exclude, so skip it
			$this->filterList = null;
			return null;
		}
		
		// REDCap::getRecordIdField  Determines the variable name of the Record ID field (i.e. the first field) of the current project
		//
		$recordIdField = REDCap::getRecordIdField();  // method that gives us the actual name (may be other than the standard 'record_id' as the field is also configurable for its name.

		foreach ($excludeList as $key => $field) {
			if ($field == $recordIdField) { // don't remove the record id data, it is vital to make and update records.
				$filterList[$field] = false;
				continue;
			}
			$filterList[$field] = true;
		}
		
		$this->filterList = $filterList;
		
		return $filterList;
	}

	/**
	 * filterSourceData - part of exclued copy feature, removing fields we do not want to copy to destination.
	 */
	private function filterSourceData($sourceData)
	{
		$this->getFilterList();
		$flagFilterProcessed = false;

		$arr = json_decode($sourceData);  // make json data into an array, an array is simpler to walk through and reference.
		if ($this->filterList == null) {
			return $sourceData;
		}
		
		foreach ($arr as $key => $val) {
			// walk through the exclude list and clear out values so destination gets empty values.
			foreach ($this->filterList as $filterKey => $excludeFlag) {
				if ($excludeFlag) {
					if (!$filterKey) {
						continue;
					}
					$val->$filterKey = '';  // clear the actual field value.  NOTE: the reference trick $val -> $filterKey  
					
					$flagFilterProcessed = true;
				}
			}
		}
		
		// if we changed anything use the new data, else just give back what we started with
		if ($flagFilterProcessed) {
			$filteredData = json_encode($arr);  // turn the array back into json data, json is smoother for the data processing save, because it is.
		} else {
			$filteredData = $sourceData;
		}
		
		return $filteredData;
	}

	/**
	 * displayDataAndStop - dev view data and stop page process so can view data.  Debugging handy method.
	 */
	private function displayDataAndStop($json)
	{
		$this->showJson($json);
		exit;
	}

	/** 
	 * isAllowedDestination - check if the destination will allow the source to save data in the destination records.
	 */
	private function isAllowedDestination($sourceId = null, $destId = null)
	{
		$allowedFlag = false;
		
		if ($sourceId == $destId) {  // do not copy to ourselves
			return $allowedFlag;
		}
		
		if ($destId) { // this value is pulled from the database and is a pure input to begin with and the caller method always provides the value.
			//
			// SELECT `value` as allowedSourceId FROM redcap_external_module_settings where project_id = 789 and `key` = 'allowed_project_id';
			//
			$sql = 'SELECT `value` as allowedSourceId FROM redcap_external_module_settings where project_id = ' . db_escape($destId) . ' and `key` = ' ."'" . 'allowed_project_id' . "'" . '';
			
			$q = db_query($sql);
	
			// set the flag
			if ($q) {
				$allowedSourceId = db_result($q, 0, 'allowedSourceId');
				$allowedFlag = ($allowedSourceId == $sourceId ? true : false);
			}
		}
		
		return $allowedFlag;
	}
		
} // *** end class

	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

?>
