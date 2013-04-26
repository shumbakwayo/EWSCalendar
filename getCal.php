#!/usr/bin/php
<?php
ini_set('display_errors',0);

include 'NTLMSoapClient.php';
include 'ExchangeNTLMSoapClient.php';
include 'NTLMStream.php';
include 'ExchangeNTLMStream.php';
date_default_timezone_set("America/Chicago");
@session_start();

$_SESSION['loginaccount']  = 'yourprivilegedaccount';
$_SESSION['loginpassword'] = 'yourprivilegedaccountpassword';
$start = microtime(true);

# Get the calendar accounts
if($argv[1]){ $calendars = preg_split('/\;|\,|\|/',$argv[1],-1,PREG_SPLIT_NO_EMPTY); }

@$startDate  = trim($argv[2]) && isset($argv[2])? gmdate("Y-m-d\TH:i:s\Z",strtotime(date("Y-m-d",strtotime($argv[2]))." 01:00:00")):gmdate("Y-m-d\TH:i:s\Z",strtotime(date('Y-m-01')));
@$endDate    = trim($argv[3]) && isset($argv[3])? gmdate("Y-m-d\TH:i:s\Z",strtotime(date("Y-m-d",strtotime($argv[3]))." 01:00:00")):gmdate("Y-m-d\TH:i:s\Z",strtotime("$startDate +1 month"));
//echo "[$startDate]\n";
//echo "[$endDate]\n";

foreach($calendars as $impersonate){

	//$_SESSION['impersonate']   = str_replace('@domain.name','',$impersonate);

	stream_wrapper_unregister('https');
	stream_wrapper_register('https', 'ExchangeNTLMStream') or die("Failed to register protocol");
	$wsdl   = "EWS/services.wsdl";
	$client = new ExchangeNTLMSoapClient($wsdl);

	/* Do something with the web service connection */
	stream_wrapper_restore('https');
	$client = new ExchangeNTLMSoapClient($wsdl); //print_r ($client);

	@$FindItem->Traversal               = "Shallow";
	@$FindItem->ItemShape->BaseShape    = "AllProperties";
	@$FindItem->ParentFolderIds->DistinguishedFolderId->Id                    = "calendar";
	//$FindItem->ParentFolderIds->DistinguishedFolderId->MailBox->EmailAddress = "{$_SESSION['impersonate']}@domain.name";
	@$FindItem->CalendarView->StartDate = $startDate;
	@$FindItem->CalendarView->EndDate   = $endDate;

	/* Get result */
	$result = $client->FindItem($FindItem);

	/* Fetch calendar items */
	@$calendaritems = $result->ResponseMessages->FindItemResponseMessage->RootFolder->Items->CalendarItem;
	
	//print_r($calendaritems);
	
	# Some glitch here. For some reason when I have only 1 item in the CalendarItem I am not able to traverse the object normally	
	if(count($calendaritems) == 1 && count($calendars) == 1){
		$myArray[] = $calendaritems;
		$calendaritems = &$myArray;
    }

	/* Loop through and  obtain calendar item contents */
	if(is_array($calendaritems)){
				
	foreach($calendaritems as $item) {
		@$Subject       = $item->Subject?  $item->Subject: '';
		@$Location      = $item->Location? $item->Location:'';
		@$Start         = $item->Start?    date('Y-m-d H:i:s',strtotime($item->Start)):'';
		@$End           = $item->End?      date('Y-m-d H:i:s',strtotime($item->End))  :'';
		@$IsAllDayEvent = $item->IsAllDayEvent? $item->IsAllDayEvent:'';
		@$IsAllDayEvent = $item->IsAllDayEvent? $item->IsAllDayEvent:'';
		$description    = $item->ItemId->Id;
		
		//echo "[{$item->Categories->String}]\n";
		
		$feed = false;
		$feed = $Subject? true:$feed;
		$feed = $item->Sensitivity        == 'Private'? false:$feed;
		@$feed = $item->Categories->String == 'Red Category'? false:$feed;
		
		if($feed) {
			$str = "$Start^$Subject^$End^$Location^$description^$IsAllDayEvent^{$_SESSION['impersonate']}^".substr(md5(uniqid(rand())),0,10);
			$myday     = date('Y-m-d',strtotime($Start));
			$calendarEvents[$myday][] = $str;
		}
	}
	}	
}
@ksort($calendarEvents);

//var_dump($calendarEvents);

echo serialize($calendarEvents);
//echo count($calendarEvents);
//$end = microtime(true);
//echo ($end - $start)."\n";