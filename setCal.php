#!/usr/bin/php
<?php
ini_set('display_errors',0);
$start = microtime(true);

include 'NTLMSoapClient.php';
include 'ExchangeNTLMSoapClient.php';
include 'NTLMStream.php';
include 'ExchangeNTLMStream.php';
date_default_timezone_set("America/Chicago");
@session_start();

$_SESSION['loginaccount']  = 'yourprivilegedaccount';
$_SESSION['loginpassword'] = 'yourprivilegedaccountpassword';

# Get the calendar accounts
if($argv[1]){ $calendars = preg_split('/\;|\,|\|/',$argv[1],-1,PREG_SPLIT_NO_EMPTY); }

foreach($calendars as $impersonate){

	$_SESSION['impersonate']   = str_replace('@domain.name','',$impersonate);

	stream_wrapper_unregister('https');
	stream_wrapper_register('https', 'ExchangeNTLMStream') or die("Failed to register protocol");
	$wsdl   = "EWS/services.wsdl";
	$client = new ExchangeNTLMSoapClient($wsdl);

	/* Do something with the web service connection */
	stream_wrapper_restore('https');
	$client = new ExchangeNTLMSoapClient($wsdl);

	$CreateItem->SendMeetingInvitations                                   = "SendToNone";
	$CreateItem->SavedItemFolderId->DistinguishedFolderId->Id             = "calendar";
	$CreateItem->ExchangeImpersonation->ConnectingSID->PrimarySmtpAddress = $_SESSION['impersonate']."@domain.name";
	$CreateItem->Items->CalendarItem = array();
	
	for($i = 0; $i < 1; $i++) {
		$CreateItem->Items->CalendarItem[$i]->Subject              = "Plea Bargain";
		$CreateItem->Items->CalendarItem[$i]->Start                = gmdate("Y-m-d\TH:i:s\Z",strtotime("2012-04-25 12:35:00")); # ISO date format. Z denotes UTC time
		$CreateItem->Items->CalendarItem[$i]->End                  = gmdate("Y-m-d\TH:i:s\Z",strtotime("2012-04-25 12:35:00"));
		$CreateItem->Items->CalendarItem[$i]->IsAllDayEvent        = false;
		$CreateItem->Items->CalendarItem[$i]->LegacyFreeBusyStatus = "Busy";
		$CreateItem->Items->CalendarItem[$i]->Location             = "Grocery Store of your choice";
		$CreateItem->Items->CalendarItem[$i]->Categories->String   = "MyCategory";
		$CreateItem->Items->CalendarItem[$i]->Sensitivity          = "Private";
		$CreateItem->Items->CalendarItem[$i]->Body->_              = "Buy snacks for the office as penalty for not finishing up the Highway Cleanup. Cheetos & Ginger Snaps most welcome.";
		$CreateItem->Items->CalendarItem[$i]->Body->BodyType       = "Text";
	}
	$result = $client->CreateItem($CreateItem);
	print_r($result);
}

$end = microtime(true);

echo ($end - $start)."\n";