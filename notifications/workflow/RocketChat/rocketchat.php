#!/usr/bin/php
<?php
// Sanity check
if($argc!=4)
{
	error_log("This process should only be called as an evQueue plugin\n");
	die(5);
}

// Read configuration
$stdin = fopen('php://stdin','r');

$config_str = stream_get_contents($stdin);

if($config_str==false)
{
	error_log("No configuration could be read on stdin\n");
	die(1);
}

// Decode configuration
$config = json_decode($config_str);
if($config===null)
{
	error_log("Unable to decode json data\n");
	die(2);
}

if(!isset($config->notificationconf->when) || !isset($config->notificationconf->color) || !isset($config->notificationconf->room))
{
	error_log("Invalid configuration\n");
	die(3);
}

// Read workflow instance informations from evQueue engine
$xml = simplexml_load_string($config->instance);
$workflow_attributes = $xml->attributes();

// Extract mail informations from config
$when = $config->notificationconf->when;
$color = $config->notificationconf->color;
$room = $config->notificationconf->room;

if($when!='ON_SUCCESS' && $when!='ON_ERROR' && $when!='ON_BOTH')
{
	error_log("Invalid value for 'when' parameter\n");
	die(6);
}

// When should be trigger alert
if($when=='ON_SUCCESS' && $argv[2]!=0)
	die();

if($when=='ON_ERROR' && $argv[2]==0)
	die();

$msg = '**STATUS**: '.$workflow_attributes['status'];
if ($workflow_attributes['comment'] != '' && $workflow_attributes['comment'] != null){
    $comment = str_replace("\"","*",$workflow_attributes['comment']);
    $msg .= ' **COMMENT**: '.$comment;
}

$json = '{"alias": "evQueue",';
$json .= '"avatar": "data:image/png;base64,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",';
$json .= '"channel": "'.$room.'",';
$json .= '"text": "Report for workflow '.$workflow_attributes['name'].'",';
$json .= '"attachments": [';
$json .= '{"title": "Alert Details", "collapsed": false, "color": "'.$color.'",';
$json .= '"title_ink": "'.$config->pluginconf->link.'",'; 
$json .= '"title_link_download": false,';
$json .= '"fields": [';
$json .= '{"short": true, "title": "ID", "value": "'.$workflow_attributes['id'].'"},';
$json .= '{"short": true, "title": "Workflow", "value": "'.$workflow_attributes['name'].'"},';
$json .= '{"short": true, "title": "Start Time", "value": "'.$workflow_attributes['start_time'].'"},';
$json .= '{"short": true, "title": "End Time", "value": "'.$workflow_attributes['end_time'].'"},';
$json .= '{"short": false, "title": "Event", "value": "'.$msg.'"}';
$json .= ']}]}';

#file_put_contents('/tmp/evqueue_rocketchat.txt', "\n".$json."\n" , FILE_APPEND);

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $config->pluginconf->url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $json,
  CURLOPT_HTTPHEADER => array(
    "X-Auth-Token: ".$config->pluginconf->token,
    "X-User-Id: ".$config->pluginconf->uid,
    "Content-Type: application/json",
    "cache-control: no-cache"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}