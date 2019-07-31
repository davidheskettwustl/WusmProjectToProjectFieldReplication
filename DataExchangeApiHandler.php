<?php
/**
 *  DataExchangeApiHandler 
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

include_once 'CurlCommunications.php';

use REDCap;

/** 
 * DataExchangeApiHandler - .
 */
class DataExchangeApiHandler extends DataExchangeHandler
{
	private $commLink;

	private $destToken;
	private $sorcToken;
	
	CONST ERR_NO_COMM      = 1;
	CONST ERR_NO_SRC_TOKEN = 2;
	CONST ERR_NO_DST_TOKEN = 3;
	
	/**
	 * - set up our defaults.
	 */
	function __construct()
	{
		parent::__construct();

		$this->commLink = null;
		$this->destToken = null;
		$this->sorcToken = null;

		$this->errMsgs[] = 'No Curl Comms';
		$this->errMsgs[] = 'No Source Token';
		$this->errMsgs[] = 'No Destination Token';
	}
		
	/**
	 * setComm - set the curl comm.
	 */
	public function setComm($commObj)
	{
		$this->commLink = $commObj;
	}

	/**
	 * setComm - set the curl comm.
	 */
	public function setCommApiUrl($apiUrl)
	{
		if ($this->commLink) {
			$this->commLink->setApiUrl($apiUrl);
		}
	}
	
	/**
	 * setComm - set the curl comm.
	 */
	public function setTokenSorc($token)
	{
		$this->sorcToken = $token;
	}

	/**
	 * setComm - set the curl comm.
	 */
	public function setTokenDest($token)
	{
		$this->destToken = $token;
	}

	/**
	 * getSourceDataApi - get source data.
	 */
	public function getSourceDataApi($recordsArray, $formsArray, $eventsArray)  // read source
	{
		if (!$this->commLink) {
			$this->flagError = true;
			$this->flagErrorType = self::ERR_NO_COMM;
			$this->addErrorMsg();
		}

		if (!$this->sorcToken) {
			$this->flagError = true;
			$this->flagErrorType = self::ERR_NO_SRC_TOKEN;
			$this->addErrorMsg();
		}
		
		if ($this->flagError) {
			$this->addErrorMsg('getSourceDataApi');
			return null;
		}

		$apiQueryData = array(
		    'token'                  => $this->sorcToken,
		    'content'                => 'record',
		    'format'                 => 'json',
		    'type'                   => 'eav',
		    'records'                => $recordsArray,
		    'forms'                  => $formsArray,
		    'events'                 => $eventsArray,
		    'rawOrLabel'             => 'raw',
		    'rawOrLabelHeaders'      => 'raw',
		    'exportCheckboxLabel'    => 'false',
		    'exportSurveyFields'     => 'false',
		    'exportDataAccessGroups' => 'false',
		    'returnFormat'           => 'json'
		);
		
		$output = $this->commLink->communicateCurl($apiQueryData);

		if ($output == null) {
			$this->flagError = true;
			$this->addErrorMsg($this->commLink->getMessage());
			return null;
		}
				
		return $output;
	}

	/**
	 * putDestinationApi - put destination data.
	 */
	public function putDestinationApi($data)  // write destination
	{
		if (!$this->commLink) {
			$this->flagError = true;
			$this->flagErrorType = self::ERR_NO_COMM;
			$this->addErrorMsg();
		}

		if (!$this->destToken) {
			$this->flagError = true;
			$this->flagErrorType = self::ERR_NO_DST_TOKEN;
			$this->addErrorMsg();
		}

		if ($this->flagError) {
			$this->addErrorMsg('putDestinationApi');
			return null;
		}

		$overwriteState = 'normal';
		if ($this->flagOverWriteBlanks) {
			$overwriteState = 'overwrite';
		}
		
		$apiQueryData = array(
		    'token'             => $this->destToken,
		    'content'           => 'record',
		    'format'            => 'json',
		    'type'              => 'eav',
		    'overwriteBehavior' => $overwriteState,
		    'forceAutoNumber'   => 'false',
		    'data'              => $data,
		    'returnContent'     => 'count',
		    'returnFormat'      => 'json'
		);

		$output = $this->commLink->communicateCurl($apiQueryData);
		
		if ($output == null) {
			$this->flagError = true;
			$this->addErrorMsg($this->commLink->getMessage());
			return null;
		}
		
		return $output;
	}

	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

}  // ***** end class

?>
