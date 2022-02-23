<?php
/*
PHP to make Asterisk dial an internal extension and make an outbound phone call.
*/
$ip = '127.0.0.1';// IP address of asterisks
$port = 5038;// Replace with your port. See /etc/asterisk/manager.conf
$username = "TestUser";// Replace with your username. See /etc/asterisk/manager.conf
$password = "TestPass";// Replace with your password. See /etc/asterisk/manager.conf
$internalPhoneline = "255";// Replace with your internal phone extension to ring
$context = "context";// context for outbound calls. See /etc/asterisk/extensions.conf
$target = "5555555555";// Replace with target phone number to call


// Create socket connection
$socket = stream_socket_client('tcp://'.$ip.':'.$port);
if ( $socket )
{
	echo "[ OK ] Connected. Sending authentication request.\r\n";

	// Create authentication request
	$authenticationRequest = "Action: Login\r\n";
	$authenticationRequest .= "Username: $username\r\n";
	$authenticationRequest .= "Secret: $password\r\n";
	$authenticationRequest .= "Events: off\r\n\r\n";

	// Send authentication request
	$auth = stream_socket_sendto($socket, $authenticationRequest);
	if ( $auth )
	{
		usleep(200000);// wait for server
		$authResponse = fread($socket, 4096);// Read response

		// Check if authentication was successful
		if ( strpos($authResponse, 'Success') !== FALSE )
		{
			echo "[ OK ] Authenticated. Initiating call.\r\n";

			// Create originate request
			$originateRequest = "Action: Originate\r\n";
			$originateRequest .= "Channel: SIP/$internalPhoneline\r\n";
			$originateRequest .= "Callerid: Click2Call <".$target.">\r\n";
			$originateRequest .= "Exten: $target\r\n";
			$originateRequest .= "Context: $context\r\n";
			$originateRequest .= "Priority: 1\r\n";
			$originateRequest .= "Async: yes\r\n\r\n";

			// Send originate request
			$originate = stream_socket_sendto($socket, $originateRequest);
			if ( $originate )
			{
				usleep(200000);// Wait for server
				$originateResponse = fread($socket, 4096);// Read response

				// Check if originate was successful
				if ( strpos($originateResponse, 'Success') !== FALSE )
				{
					echo "[ OK ] Call initiated. Dialing...\r\n";
				} else {
					echo "[ FAIL ] Could not initiate call.\r\n";
				}
			} else {
				echo "[ FAIL ] Could not write call initiation request to socket.\r\n";
			}
		} else {
			echo "[ FAIL ] Could not authenticate to Asterisk.\r\n";
		}
	} else {
		echo "[ FAIL ] Could not write authentication request to socket.\r\n";
	}
} else {
	echo "[ FAIL ] Unable to connect to socket.\r\n";
}
