<?php
// ops.php — с отладкой, чтобы увидеть сырой ответ
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'JustSMP.minerent.io';
$port = 25575;
$password = 'JustSrun777';

$result = [
    'players' => [],
    'operators' => [],
    'raw_list' => '',   // <-- для отладки
    'raw_op' => ''      // <-- для отладки
];

try {
    $socket = fsockopen($host, $port, $errno, $errstr, 2);
    if (!$socket) {
        throw new Exception("RCON connection failed");
    }

    // Аутентификация
    $authPacket = pack('VVVa*', 3, 0, 0, $password) . "\x00";
    fwrite($socket, $authPacket);
    $response = fread($socket, 4096);
    if (strpos($response, "\x00") === false) {
        throw new Exception("RCON auth failed");
    }

    // ===== 1. Получаем список игроков через /list =====
    $cmdPacket = pack('VVVa*', 2, 1, 0, '/list') . "\x00";
    fwrite($socket, $cmdPacket);
    $response = fread($socket, 4096);
    $response = substr($response, 8);
    $response = trim($response);
    
    $result['raw_list'] = $response; // сохраняем сырой ответ

    // Парсим /list
    $players = [];
    
    // Вариант 1: "There are 2 of a max of 50 players online: Drun_777, Denchick1789"
    if (strpos($response, 'players online:') !== false) {
        $parts = explode('players online:', $response);
        if (isset($parts[1])) {
            $list = trim($parts[1]);
            if (!empty($list)) {
                $players = array_map('trim', explode(',', $list));
            }
        }
    } 
    // Вариант 2: "online: Drun_777, Denchick1789" (сокращённый)
    elseif (strpos($response, 'online:') !== false) {
        $parts = explode('online:', $response);
        if (isset($parts[1])) {
            $list = trim($parts[1]);
            if (!empty($list)) {
                $players = array_map('trim', explode(',', $list));
            }
        }
    }
    // Вариант 3: "Drun_777, Denchick1789" (только имена)
    elseif (strpos($response, ',') !== false) {
        $players = array_map('trim', explode(',', $response));
    }

    $result['players'] = $players;

    // ===== 2. Получаем список операторов через /op list =====
    $cmdPacket = pack('VVVa*', 2, 1, 0, '/op list') . "\x00";
    fwrite($socket, $cmdPacket);
    $response = fread($socket, 4096);
    $response = substr($response, 8);
    $response = trim($response);
    
    $result['raw_op'] = $response;

    $operators = [];
    if (strpos($response, 'Operators:') !== false) {
        $parts = explode('Operators:', $response);
        if (isset($parts[1])) {
            $opList = trim($parts[1]);
            if (!empty($opList)) {
                $operators = array_map('trim', explode(',', $opList));
            }
        }
    }

    $result['operators'] = $operators;

    fclose($socket);

} catch (Exception $e) {
    $result = ['players' => [], 'operators' => [], 'raw_list' => $e->getMessage(), 'raw_op' => ''];
}

echo json_encode($result);
?>
