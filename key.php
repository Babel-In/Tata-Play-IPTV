<?php

include '_config.php';
$id = $_GET['id'] ?? exit("Error: ID not provided.");
$url = "https://babel-in.xyz/tata/key";

$json = handshake($url, $id, $ApiKey);
$data = json_decode($json, true);
$keyPart = $data['key'];
$keys = json_encode($keyPart, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

header('Content-Type: application/json');
echo $keys;