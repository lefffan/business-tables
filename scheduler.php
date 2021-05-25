<?php

/******************************************************************************************************
field         allowed values                   
-----         --------------                   
minute        0-59                             
hour          0-23                             
day of month  1-31                             
month         1-12
day of week   0-7 (0 or 7 is Sunday)
A field may be an asterisk (*), which always stands for ``first-last''.
Ranges of numbers are allowed.  Ranges are two numbers separated with a
hyphen.  The specified range is inclusive.  For example, 8-11 for an   
``hours'' entry specifies execution at hours 8, 9, 10 and 11.          
Lists are allowed.  A list is a set of numbers (or ranges) separated by
commas.  Examples: ``1,2,5,9'', ``0-4,8-12''.                          
Step values can be used in conjunction with ranges.  Following a range   
with ``/<number>'' specifies skips of the number's value through the     
range.  For example, ``0-3/2'' can be used in the hours field to specify 0,2 hours execution.
******************************************************************************************************/

require_once 'core.php';

function CompareCronField($cronfield, $nowvalue)
{
 if ($cronfield === '*') return true;
 
 foreach (explode(',', $cronfield) as $value)
      if (($pos = strpos($value, '/')) !== false)
	 {
	  if (strlen($value) > $pos + 1)
	  if (CompareCronField(substr($value, 0, $pos), $nowvalue))
	  if ($nowvalue % intval(substr($value, $pos + 1)) === 0) return true;
	 }
       else
	 {
	  if (($pos = strpos($value, '-')) === false && $value === strval($nowvalue)) return true;
	  if ($pos !== false && $nowvalue >= intval(substr($value, 0, $pos)) && $nowvalue <= intval(substr($value, $pos + 1))) return true;
	 }
	 
 return false;
}

function SplitCronLine($cronline, $len)
{
 if (($cronline = trim($cronline)) === '') return false;	// Cron line is empty? return
 $cronline = explode(' ', $cronline);				// Split cron line via spaces
 $cron = [];
 
 foreach ($cronline as $key => $value)
	 {
	  if (count($cron) === $len + 1) $cron[$len] .= ' '.$value;
	   else if (count($cron) === $len) $cron[] = $value;
	   else if (trim($value) !== '') $cron[] = trim($value);
	 }
 if (count($cron) <= $len) return false;
 return $cron;
}

$nowfields = ['minutes', 'hours', 'mday', 'mon', 'wday'];
$nowfieldscount = count($nowfields);

while (true)
{
 $now = getdate(); // [ 'seconds' => 16, 'minutes' => 30, 'hours' => 2, 'mday' => 11, 'wday' => 0, 'mon' => 4, 'year' => 2021, 'yday' => 100, 'weekday' => 'Sunday', 'month' => 'April', 0 => 1618108216 ]
 $query = $db->prepare("SELECT id,JSON_EXTRACT(odprops, '$.dialog.View'),odname,JSON_EXTRACT(odprops, '$.dialog.Element') FROM $");
 $query->execute();
 
 foreach ($query->fetchAll(PDO::FETCH_NUM) as $od)
 foreach (json_decode($od[1], true) as $key => $view) if ($key != 'New element')
 foreach (preg_split("/\n/", $view['element7']['data']) as $cronline) 
	 {
	  if (($cron = SplitCronLine($cronline, $nowfieldscount + 1)) === false) continue;
	  foreach ($nowfields as $key => $value) if (!($success = CompareCronField($cron[$key], $now[$value]))) break;
	  
	  if ($success && intval($cron[$nowfieldscount]) > 0)	// Execute cron handler in case of all successful fields
	     {
	      // Init client props
	      $client = ['auth' => 'system', 'uid' => getUserId($db, 'system'), 'ODid' => $od[0], 'OVid' => $view['element1']['id'], 'OD' => $od[2], 'OV' => $view['element1']['data'], 'eId' => $cron[$nowfieldscount], 'cmd' => 'SCHEDULE', 'params' => [], 'cmdline' => $cron[$nowfieldscount + 1], 'ip' => IP];
	      
	      if (gettype($objectselection = GetObjectSelection($db, trim($view['element4']['data']), $client['params'], $client['auth'])) === 'array') continue;
	      $query = $db->prepare("SELECT DISTINCT id FROM `data_$od[0]` $objectselection");
	      //$query = $db->prepare("SELECT id FROM `uniq_$od[0]`"); // All objects
	      $query->execute();

	      foreach ($query->fetchAll(PDO::FETCH_NUM) as $oid)
	    	      {
		       $client['oId'] = $oid[0];
	    	       //lg("Executing handler '$client[cmdline]' (OD id $client[ODid], object id $client[oId], element id $client[eId]) at $now[hours]:$now[minutes]");
	    	       // Execute wrapper <uid> <start time> <ODid> <OVid> <object id> <element id> <event> <ip> <client json>                   
	    	       exec(WRAPPERBINARY." '$client[uid]' ".strval(strtotime("now"))." '$client[ODid]' '$client[OVid]' '$client[oId]' '$client[eId]' '$client[cmd]' '$client[ip]' '".json_encode($client, JSON_HEX_APOS | JSON_HEX_QUOT)."' >/dev/null");
		      }
	     }
	 }
 $finish = getdate();
 if ($finish['minutes'] === $now['minutes'] && $finish['hours'] === $now['hours']) sleep(60 - $finish['seconds']);
}
