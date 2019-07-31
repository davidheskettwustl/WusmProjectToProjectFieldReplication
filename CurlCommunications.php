<?php
/**
 *  CurlCommunications 
 *  - CLASS for .
 *    + key functions
 *  
 *  
 *  - WUSM - Washington University School of Medicine. 
 * @author David L. Heskett
 * @version 1.0
 * @date 20181026
 * @copyright &copy; 2018 Washington University, School of Medicine, Institute for Infomatics <a href="https://redcap.wustl.edu">redcap.wustl.edu</a>
 */

namespace WashingtonUniversity\ProjectToProjectFieldReplicationExternalModule;


/** 
 * CurlCommunications - Api handler.
 */
class CurlCommunications
{
	public $message;
	
	private $flagProduction;
	private $apiUrl;
	private $debugFlag;

	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

	/**
	 * - set up our defaults.
	 */
	function __construct()
	{
		$this->flagProduction = false;
		$this->debugFlag = false;
		$this->apiUrl = null;
		$this->message = '';
	}

	/**
	 * productionModeOn - set to use SSL for production.
	 */
	public function productionModeOn()
	{
		$this->flagProduction = true;
	}

	/**
	 * debugModeOn - set to use debugging messaging.
	 */
	public function debugModeOn()
	{
		$this->debugFlag = true;
	}

	/**
	 * debugModeOff - turn off debugging.
	 */
	public function debugModeOff()
	{
		$this->debugFlag = false;
	}

	/**
	 * getMessage - get the debug message.
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * setApiUrl - set the url to use for api.
	 */
	public function setApiUrl($apiUrl)
	{
		$this->apiUrl = $apiUrl;
	}
	
	/**
	 * communicateCurl - do curl messaging.
	 */
	public function communicateCurl($apiQueryData) 
	{
		$apiUrl = $this->apiUrl;
		
		if (!$apiUrl) {
			$this->message .= 'NO URL SET';
			
			return null;
		}
		
		$ch = curl_init();
		
		if ($this->debugFlag) {
			$this->message .= 'communicateCurl() ';
			$this->message .= '<br>';
			$this->message .= 'url [' . $apiUrl . ']';
			$this->message .= '<br>';
			$this->message .= 'fields [' . print_r($apiQueryData, true) . ']';
			$this->message .= '<br>';
		}
		
		curl_setopt($ch, CURLOPT_URL, $apiUrl);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiQueryData, '', '&'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->flagProduction); // Set to TRUE for production use
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		
		$result = curl_exec($ch);
		
		curl_close($ch);
		
		return $result;
	}	


	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

}  // ***** end class

?>
