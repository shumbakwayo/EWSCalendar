#!/usr/bin/php
<?php
ini_set('display_errors',0);

include 'NTLMSoapClient.php';
include 'ExchangeNTLMSoapClient.php';
include 'NTLMStream.php';
include 'ExchangeNTLMStream.php';

@session_start();
date_default_timezone_set("America/Chicago");
$start = microtime(true);

//print_r($argv);
$_SESSION['impersonate']   = $argv[1]? str_replace('@domain.name','',$argv[1]):'default.userid';
$ItemId                    = $argv[2]? $argv[2]:'';
$_SESSION['loginaccount']  = 'yourprivilegedaccount';
$_SESSION['loginpassword'] = 'yourprivilegedaccountpassword';

/* Ensure the ItemId argument is supplied. */
if(!$ItemId) die("Please supply an ItemId\n");

stream_wrapper_unregister('https');
stream_wrapper_register('https', 'ExchangeNTLMStream') or die("Failed to register protocol");
$wsdl   = "EWS/services.wsdl";
$client = new ExchangeNTLMSoapClient($wsdl);

/* Do something with the web service connection */
stream_wrapper_restore('https');
$client = new ExchangeNTLMSoapClient($wsdl); //print_r ($client);

@$GetItem->Traversal               = "Shallow";
@$GetItem->ItemShape->BaseShape    = "AllProperties";
@$GetItem->ItemIds->ItemId->Id     = $ItemId;

/* Get result */
$result = $client->GetItem($GetItem);

//print_r($result);
$results['subject']  = $result->ResponseMessages->GetItemResponseMessage->Items->CalendarItem->Subject;
$results['location'] = $result->ResponseMessages->GetItemResponseMessage->Items->CalendarItem->Location;
$results['start']    = $result->ResponseMessages->GetItemResponseMessage->Items->CalendarItem->Start;
$results['end']      = $result->ResponseMessages->GetItemResponseMessage->Items->CalendarItem->End;
$results['allday']   = $result->ResponseMessages->GetItemResponseMessage->Items->CalendarItem->IsAllDayEvent;
$description         = trim(str_replace('&nbsp;','<br />',strip_tags($result->ResponseMessages->GetItemResponseMessage->Items->CalendarItem->Body->_)));
//$description         = trim(str_replace('&nbsp;','<br />',($result->ResponseMessages->GetItemResponseMessage->Items->CalendarItem->Body->_)));

while(strpos($description,'<br /><br />')!== false) $description = str_replace('<br /><br />','<br />',$description);

if(preg_replace("/\n|\r/","",$description) == '<br /><br />') $description = '';
if(substr($description,-6) == "<br />") $description = trim(substr($description,0,-6));

$results['description'] = $description;

echo serialize($results);
	
$end = microtime(true);

//echo ($end - $start)."\n";