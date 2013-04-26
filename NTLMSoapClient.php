<?php

# Class used to fetch the WSDL file
class NTLMSoapClient extends SoapClient {
	function __doRequest($request, $location, $action, $version) { //echo "[\nRequest => $request,\n Location => $location,\n Action => $action,\n Version => $version\n]\n";
		
		/* Set the request header */
		$headers = array(
			'Method: POST',
			'Connection: Keep-Alive',
			'User-Agent: PHP-SOAP-CURL',
			'Content-Type: text/xml; charset=UTF-8',
			'SOAPAction: "'.$action.'"',
		);  
		
		$this->__last_request_headers = $headers;
		
		$ch       = curl_init($location); // Initializes a new session and return a cURL handle for use with the curl_setopt(), curl_exec(), and curl_close() functions.
		
		/* If $_SESSION['impersonate'] is defined then include the impersonation header. 
		 * Impersonation needs to be configured for a priviledged user in Active Directory.
		 */
		if(isset($_SESSION['impersonate'])){
			$impers="<SOAP-ENV:Header>
			<ns1:ExchangeImpersonation>
			<ns1:ConnectingSID>
			<ns1:PrimarySmtpAddress>{$_SESSION['impersonate']}@domain.name</ns1:PrimarySmtpAddress>
			</ns1:ConnectingSID>
			</ns1:ExchangeImpersonation>
			</SOAP-ENV:Header>
			";
			$request = str_replace("><SOAP-ENV:Body",">$impers<SOAP-ENV:Body",$request);	
		}
		
		/* Versus calling curl_setopt($myhandle, $myoption, $myvalue ) { e.g. curl_setopt($ch, CURLOPT_POST, true) } for each cURL option:
		 * Set multiple options for a cURL transfer using the curl_setopt_array function
		 */
		
		$options = array(
							  CURLOPT_RETURNTRANSFER => true,
		                 CURLOPT_HTTPHEADER     => $headers,
				           CURLOPT_POST           => true,
				           CURLOPT_POSTFIELDS     => $request,
				           CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				           CURLOPT_HTTPAUTH       => CURLAUTH_NTLM,
				           CURLOPT_USERPWD        => $_SESSION['loginaccount'].'@domain.name:'.$_SESSION['loginpassword'],
				           CURLOPT_SSL_VERIFYPEER => false,
			              CURLOPT_SSL_VERIFYHOST => false
		                );
		
	   curl_setopt_array($ch,$options);
	
		$response = curl_exec($ch);
		
		//echo "Start Response\n"; print_r($response); echo "End Response\n";
		
		return $response;
	}   
	function __getLastRequestHeaders() { return implode("n", $this->__last_request_headers)."n"; }   
}
