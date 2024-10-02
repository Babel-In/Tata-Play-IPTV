<?php

include '_config.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
 
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$id = $_GET['id'] ?? exit("Error: ID not provided.");
$data = $_GET['data'] ?? exit("Error: data not provided.");
$channelInfo = getChannel($id);
$dashUrl = $channelInfo['streamData']['initialUrl'] ?? exit("Error: Stream URL not found.");
$mpd = str_replace("master.mpd", "dash/$data", $dashUrl);

header('Content-Type: application/dash+xml');
header('Content-Disposition: attachment; filename="'.$genreName.'|'.$data.'.txt"');
echo fetchMPDManifest($mpd,$userAgent,$userIP);