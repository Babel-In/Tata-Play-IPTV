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
$channelInfo = getChannel($id);
$dashUrl = $channelInfo['streamData']['initialUrl'] ?? exit("Error: Stream URL not found.");
$replacement = generateRandomString();
$dashUrl = str_replace('master', $replacement, $dashUrl);
$manifestContent = fetchMPDManifest($dashUrl, $userAgent, $userIP);
$widevinePssh = extractVideoUrlFromManifest($manifestContent, dirname($dashUrl), $userAgent, $userIP);

if (!$widevinePssh) {
    exit("Error: Could not extract PSSH or KID.");
}
if ($worldwide === "no") {
    $baseUrl = dirname($dashUrl) . "/dash/";
} else {
    $baseUrl = $HostUrl . "$id/dash/";
}

$psshSet = $widevinePssh['pssh']; // get pssh
$kid = $widevinePssh['kid']; // get kid
$pattern = '/<ContentProtection\s+schemeIdUri="(urn:[^"]+)"\s+value="Widevine"\/>/'; // pssh pattern

$manifestContent = str_replace('<BaseURL>dash/</BaseURL>', '<BaseURL>' . $baseUrl . '</BaseURL>', $manifestContent); // add baseUrl
$manifestContent = preg_replace('/\b(init.*?\.dash|media.*?\.m4s)(\?idt=[^"&]*)?("|\b)(\?decryption_key=[^"&]*)?("|\b)(&idt=[^&"]*(&|$))?/', "$1$3$5$6$7", $manifestContent); // remove decryption_key  etc if there
$manifestContent = preg_replace_callback($pattern, function ($matches) use ($psshSet) {
    return '<ContentProtection schemeIdUri="' . $matches[1] . '"> <cenc:pssh>' . $psshSet . '</cenc:pssh></ContentProtection>';
}, $manifestContent); // add pssh to mpd
$manifestContent = preg_replace('/xmlns="urn:mpeg:dash:schema:mpd:2011"/', '$0 xmlns:cenc="urn:mpeg:cenc:2013"', $manifestContent);
$new_content = "<ContentProtection schemeIdUri=\"urn:mpeg:dash:mp4protection:2011\" value=\"cenc\" cenc:default_KID=\"$kid\"/>";  // kid maker
$manifestContent = str_replace('<ContentProtection value="cenc" schemeIdUri="urn:mpeg:dash:mp4protection:2011"/>', $new_content, $manifestContent); // ass kid to mpd


header('Content-Type: application/dash+xml');
header('Content-Disposition: attachment; filename="'.$genreName.'|'.$id.'.mpd"');
echo $manifestContent;