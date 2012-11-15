<?php
/*
 * Basic lib to interface Stanbic Mobile Money.
 * 
 * Code is a port of @takinbo's python lib at:
 * http://pypi.python.org/pypi/python-stanbicmm
 * https://github.com/timbaobjects/python-stanbicmm
 *
 * @author Opeyemi Obembe (@kehers) / digitalcraftstudios.com
 * @version 0.1
 */
 
// Bring in the Exceptions
require("stanbicmm_exceptions.php");

// Define the url endpoints
define("AUTH_URL", "https://mobilemoney.stanbic.com/do/login");
define("ERROR_URL", "https://mobilemoney.stanbic.com/do/error");
define("TRANSACTIONS_EXPORT_URL",
	"https://mobilemoney.stanbic.com/do/exportAccountHistoryToCsv");

/**
 * StanbicMM
 */
class StanbicMM {
	
	// User account number
	private $account;
	
	// User pin
	private $pin;
	
	// Cookie file
	private $cookiefile;

    /**
     * Class constructor
     *
     * @param string $account the user account number
     * @param string $pin the user pin
	 * @throws SyntaxException if parameter missing or invalid
     */
	public function __construct($account, $pin) {
		
		if (!is_numeric($account) || !is_numeric($pin))
			throw new SyntaxException("Invalid account [number] or pin");
			
		$this->account  = $account;
		$this->pin 		= $pin;
		$this->cookiefile = tempnam ("", "stanbicMM");
	}
	
    /**
     * Get transactions
     *
     * @param array $params array of the custom filter parameters:
	 *   to_date: date string representing the end date filter 
	 *   from_date: date string representing the start date filter 
	 *   txn_ref: transaction reference value.
	 *		Dont specify date filters if this is set.
     */
	public function get_transactions($params = array()) {
		self::auth();
		
		$param_map = array(
			'to_date' => 'query(period).end',
			'from_date' => 'query(period).begin',
			'txn_ref' => 'query(transactionNumber)'
		);
		
		$post_params = array(
			'advanced' => 'false',
			'memberId' => '0',
			'typeId' => '5',
			'query(owner)' => '0',
			'query(type)' => '5'
		);
		
		foreach ($params as $key => $value) {
			// Is a 'valid' parameter passed in?
			if (!array_key_exists($key, $param_map))
				continue;
				
			if (strpos($key, '_date'))
				$value = date('d/m/Y', strtotime($value));
			
			$post_params[$param_map[$key]] = $value;
		}
		
		// If user passed in custom post parameters
		if (count($post_params) > 5)
			$post_params['advanced'] = 'true';
			
		$response = self::http(TRANSACTIONS_EXPORT_URL, $post_params, true);
		// echo $response; #debug
		
		// I am using fgetcsv to parse csv data
		// So I need to save in file first and pass in the handle
		// If you run on PHP 5 >= 5.3.0, str_getcsv saves the BS
		$fp = tmpfile();
		fwrite($fp, $response);
		rewind($fp);
		$csv_length = strlen($response);
		// We only take date, description, amount, reference, sender, 
		//	recipient, currency and comment.
		
		// If you need more prisoners, select below :)
		/*
		"Date","Transaction type","Amount","From / to","From / to - Full name",
		"Merchant ID","Terminal Short Code","Reference Number","Recons ID",
		"Sales Agent Code","Outlet Id","Outlet Name","Merchant Id",
		"Merchant Name","Description","Transaction number",
		"Parent transaction number","Children transaction numbers",
		"Trace number","???transfer.fromOwner???","???transfer.toOwner???"
		*/
		$result = array();
		while (($data = fgetcsv($fp, $csv_length)) !== FALSE) {
			$line = array();
			$line['date'] = date('d/m/Y H:i:s', strtotime($data[0]));
			$line['description'] = $data[14];
			$line['amount'] = str_replace(',', '', $data[2]);
			$line['reference'] = $data[15];
			$line['sender'] = $data[19];
			$line['recipient'] = $data[20];
			$line['currency'] = 'NGN';
			$line['comment'] = $data[1];
			
			$result[] = $line;
		}
		
		fclose($fp);
		
		// Remove csv header
		unset($result[0]);
		
		// print_r($result); #debug
		return $result;
	}
	
	// Private functions
	
    /**
     * Authenticates user
	 * @throws AuthDeniedException
     */
	private function auth() {
		$params = array(
			'principal' => $this->account,
			'password' => $this->pin
		);
		
		$response = self::http(AUTH_URL, $params);
		if (strpos($response, ERROR_URL) !== false) {
			throw new AuthDeniedException("Authentication error :/");
		}
		
		return true;
	}
	
    /**
     * HTTP request handler
	 *
	 * @params string url
	 * @params array post_data key to value post parameters
	 * @params boolean follow To follow redirects to new location or not
	 * @returns string response the page content or redirection location
     */
	private function http($url, $post_data = null, $follow = false) {
		// echo $url; #debug
		// print_r($post_data); #debug
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		if ($follow)
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		else
			curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $this->cookiefile); 
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $this->cookiefile); 
		// Set to 1 to verify SSL
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		if (isset($post_data)) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
		
		$response = curl_exec($ch);
		$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		// echo $status; #debug
		
		// Its a redirect
		if (!$follow && $status == 302) {
			// Lets get out the location
			$lines = preg_split("/\r\n/", $response);
			foreach ($lines as $line) {
				// echo $line.'<br>'; #debug
				if (preg_match("|location:\s*(.*)|i", $line, $match)) {
					// print_r($match); #debug
					$response = $match[1];
					break;
				}
			}
		}
		
		curl_close($ch);
		
		//echo $response; #debug
		//echo $this->cookiefile; #debug
		return $response;
	}
}
?>