<?php
// ops.php — в корневой папке сайта

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'JustSMP.minerent.io';
$port = 25575;
$password = 'JustSrun777';

$operators = [];

try {
    $socket = fsockopen($host, $port, $errno, $errstr, 2);
    if (!$socket) {
        throw new Exception("RCON connection failed");
    }

    $authPacket = pack('VVVa*', 3, 0, 0, $password) . "\x00";
    fwrite($socket, $authPacket);
    $response = fread($socket, 4096);

    if (strpos($response, "\x00") === false) {
        throw new Exception("RCON auth failed");
    }

    $cmdPacket = pack('VVVa*', 2, 1, 0, '/op list') . "\x00";
    fwrite($socket, $cmdPacket);
    $response = fread($socket, 4096);

    $response = substr($response, 8);
    $response = trim($response);
    
    if (strpos($response, 'Operators:') !== false) {
        $parts = explode('Operators:', $response);
        if (isset($parts[1])) {
            $opList = trim($parts[1]);
            if (!empty($opList)) {
                $operators = array_map('trim', explode(',', $opList));
            }
        }
    }

    fclose($socket);

} catch (Exception $e) {
    $operators = [];
}

echo json_encode(['operators' => $operators]);
?>
