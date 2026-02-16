<?php 

    function sendSMS($to_number = '', $message = '') {

        $sid = "AC86fb2e2be5cf0abae422583144d269ba"; // Your Account SID from www.twilio.com/console
        $token = "1fede382306357965dd3b63b0ea0f1d5"; // Your Auth Token from www.twilio.com/console
        $messagingServiceSID = "MG21918fc3c30d962116c4011d94c121a5"; // Your messaging service SID
        $twilio_number = "+12185412008";




        // 1. Ensure the recipient number is in E.164 format
        $to_number = preg_replace('/^\+?/', '+', $to_number); 
        
		if (!preg_match('/^\+\d{1,3}\d{10}$/', $to_number)) {  // If the number is missing a country code, assume US (+1)
			$to_number = "+1" . ltrim($to_number, '+'); // Remove leading + before appending
		}


        // 2. Twilio API url
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";


        // 3. Construct data for the API request
		$data = [
			"MessagingServiceSid" => $messagingServiceSID, 
			"To" => $to_number,
			"Body" => $message
		];


        // 4.Initialize cURL
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_USERPWD, "{$sid}:{$token}");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
			
		// 5. Execute and get the response
		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);


        // 6. Check response status and return
		if ($http_code == 201) {
			return json_decode($response, true);
		} else {
			return ["status" => "failed", "error" => $error ?: $response];
		}		
    }


    // Testing (uncomment to test):
    /*$result = sendSMS('8013762050', 'Test message from PrefPic.');
    echo "<pre>";
	    print_r($result);
    echo "</pre>";*/

// Optional URL handler if accessed via browser
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['to']) && isset($_GET['message'])) {
    $response = sendSMS($_GET['to'], $_GET['message']);
    header('Content-Type: application/json');
    echo json_encode($response);
}

?>