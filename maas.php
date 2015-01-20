<?php

$mssock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
if($mssock === FALSE) { 
	die('socket_create failed: '.socket_strerror(socket_last_error())."\n");
}
if(!socket_bind($mssock, "0.0.0.0", 20810)) {
	socket_close($mssock);
	die('socket_bind failed: '.socket_strerror(socket_last_error())."\n");
}
$assock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
if($assock === FALSE) { 
	die('socket_create failed: '.socket_strerror(socket_last_error())."\n");
}
if(!socket_bind($assock, "0.0.0.0", 20800)) {
	socket_close($assock);
	die('socket_bind failed: '.socket_strerror(socket_last_error())."\n");
}
$outsock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 

socket_set_nonblock($mssock);
socket_set_nonblock($assock);
// Format: [n] => 'pre-encoded packet'
$GServerList = array();

while ( true ) {
	socket_recvfrom($mssock,$buf,2048,0,$clientIP,$clientPort);
	if ( $buf != '' ) {
		echo "MS: ".$buf."\n";
		$Packet = substr($buf,4);
		$Parts = explode(" ", $Packet);
		switch($Parts[1]) {
			case 'heartbeat':
				switch ($Parts[2]) {
					case "COD-4\n":
						$Server = "";
						$IPParts = explode(".",$clientIP);
						foreach ( $IPParts as $IPPart ) {
							$Server .= "\x".dechex($IPPart);
						}
						$cPort = (int)$clientPort;
						$encodedPort = "\x".dechex($clientPort & 0xff);
						$encodedPort .= "\x".dechex(($clientPort >> 8) & 0xff);
						$Server .= $encodedPort;
						$GServerList[] = $Server;
						break;
					case "flatline\n":
						break;
				}
				break;
			case 'getservers':
				$MaxAmount = count($GServerList);
				if ( round( $MaxAmount/100, 0, PHP_ROUND_HALF_UP ) > 1 ) {
					
				} else {
					$ResponsePacket = "\xff\xff\xff\xffgetserversResponse\n\x00\\\\";
					for ( $i = 0; $i < $MaxAmount; $i++ ) {
						$ResponsePacket .= $GServerList[$i]."\\\\";
					}
					$ResponsePacket .= "EOF";
				}
				if ( is_array($ResponsePacket) ) {
					
				} else {
					socket_sendto($outsock, $ResponsePacket, strlen($ResponsePacket), 0, $clientIP, $clientPort);
				}
				break;
		}
	}
	unset($buf);
	socket_recvfrom($assock,$buf,2048,0,$clientIP,$clientPort);
	if ( $buf != '' ) echo "AS: ".$buf."\n";
	unset($buf);
	usleep(500);
}

?>
