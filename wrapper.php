<?php

require_once 'core.php';

CONST ARGVCLIENTINDEX = 1;

function ParseHandlerResult($db, &$output, &$client)
{
 if (!isset($output[0]))
    {
     if ($client['cmd'] != 'INIT')
        LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] (OD: '$client[OD]', OV: '$client[OV]') didn't return any data!");
     return;
    }
 if ($result = json_decode($output[0], true)) $output = $result;
  else $output = ['cmd' => 'SET', 'value' => implode("\n", $output)];
  
 if (!isset($output['cmd']) || array_search($output['cmd'], ['EDIT', 'ALERT', 'DIALOG', 'CALL', 'SET', 'RESET']) === false)
    {
     LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] (OD: '$client[OD]', OV: '$client[OV]') returned undefined json!");
     return;
    }

 switch ($output['cmd'])
	{
	 case 'EDIT':
	      if ($client['cmd'] === 'CHANGE' || $client['cmd'] === 'INIT')
	         { 
		  LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] shouldn't return 'EDIT' command on object 'CHANGE' or 'INIT' event!");
		  return;
		 }
	      ConvertToString($output, ['data']);
	      if (!isset($output['data'])) $output['data'] = getElementProp($db, $client['ODid'], $client['oId'], $client['eId'], 'value');
	      cutKeys($output, ['cmd', 'data']);
	      break;
	 case 'ALERT':
	      if ($client['cmd'] === 'CHANGE' || $client['cmd'] === 'INIT')
	         {
		  LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] shouldn't return 'ALERT' command on object 'CHANGE' or 'INIT' event!");
		  return;
		 }
	      if (!isset($output['data']) || !ConvertToString($output, ['data']))
	         {
		  LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] returned undefined 'ALERT' message!");
		  return;
		 }
	      cutKeys($output, ['cmd', 'data']);
	      break;
	 case 'DIALOG':
	      if ($client['cmd'] === 'CHANGE' || $client['cmd'] === 'INIT')
	         {
		  LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] shouldn't return 'DIALOG' command on object 'CHANGE' or 'INIT' event!");
		  return;
		 }
	      if (!isset($output['data']) || gettype($output['data']) != 'array')
	         {
	          LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] returned incorrect 'DIALOG' command data!");
		  return;
		 }
	      if ($client['ODid'] != '1' || ($client['eId'] != '1' && $client['eId'] != '6')) if (isset($output['data']['flags']['cmd'])) unset($output['data']['flags']['cmd']);
	      cutKeys($output, ['cmd', 'data']);
	      break;
	 case 'CALL':
	      if ($client['cmd'] === 'CHANGE' || $client['cmd'] === 'INIT')
	         {
	          LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] shouldn't return 'CALL' command on object 'CHANGE' or 'INIT' event!");
		  return;
		 }
	      cutKeys($output, ['cmd', 'OD', 'OV', 'ODid', 'OVid', 'params']);
	      ConvertToString($output, ['OD', 'OV', 'ODid', 'OVid']);
	      if (!isset($output['params']) || gettype($output['params']) != 'array') $output['params'] = [];
	      if (!isset($output['ODid'], $output['OD'])) { $output['ODid'] = $client['ODid']; $output['OD'] = $client['OD']; }
	      if (!isset($output['OVid'], $output['OV'])) { $output['OVid'] = $client['OVid']; $output['OV'] = $client['OV']; }
	      break;
	 case 'SET':
	 case 'RESET':
	      ConvertToString($output, ['value', 'hint', 'description', 'alert'], ELEMENTDATAVALUEMAXCHAR);
	      break;
	}

 return true;
}    
    
function ConvertToString(&$arr, $keys, $limit = NULL)
{
 $result = true;
 
 foreach ($keys as $key => $value) if (isset($arr[$value]))
	 {
	  if (gettype($arr[$value]) === 'integer') $arr[$value] = strval($arr[$value]);
	   else if (gettype($arr[$value]) === 'array') $arr[$value] = json_encode($arr[$value]);
	   else if (gettype($arr[$value]) != 'string') { unset($arr[$value]); $result = false; }
	  if (isset($arr[$value]) && isset($limit) && strlen($arr[$value]) > $limit) $arr[$value] = substr($arr[$value], 0, $limit);
	 }
	 
 return $result;
}

function WriteElement($db, &$client, &$output, $version)
{
 // No element new version set (by SET/RESET command), so write previous version
 if (!isset($output['cmd']) || ($output['cmd'] != 'SET' && $output['cmd'] != 'RESET'))
    {
     $query = $db->prepare("UPDATE `data_$client[ODid]` SET eid$client[eId]=:json WHERE id=$client[oId] AND version=$version");
     $query->execute([':json' => getElementJSON($db, $client['ODid'], $client['oId'], $client['eId'], $version - 1)]);
     return;
    }

 // Update current object uniq element if exist
 if (isset($client['uniqelements'][$client['eId']]) && isset($output['value']))
    {
     $query = $db->prepare("UPDATE `uniq_$client[ODid]` SET eid$client[eId]=:value WHERE id=$client[oId]");
     $query->execute([':value' => $output['value']]);
    }

 // Read current element json data to merge it with new data in case of 'SET' command, then write to DB
 if ($output['cmd'] === 'SET' && gettype($oldData = getElementArray($db, $client['ODid'], $client['oId'], $client['eId'], $version - 1)) === 'array') $output = array_replace($oldData, $output);
 $query = $db->prepare("UPDATE `data_$client[ODid]` SET eid$client[eId]=:json WHERE id=$client[oId] AND version=$version");
 $query->execute([':json' => json_encode($output)]);
 return true;
}

function GetCMD($db, &$client)
{
 $cmdline = trim($client['allelements'][$client['eId']]['element'.array_search($client['cmd'], ['4'=>'DBLCLICK', '5'=>'KEYPRESS', '6'=>'INIT', '7'=>'CONFIRM', '8'=>'CHANGE'])]['data']);
 if (!($len = strlen($cmdline))) return '';
 $i = -1;
 $newcmdline = '';

 while (++$i < $len)
       {
        switch ($add = $cmdline[$i])
    	       {
    	        case "'":
    		     if (($j = strpos($cmdline, "'", $i + 1)) !== false && ($arr = json_decode(substr($cmdline, $i + 1, $j - $i - 1), true)) && isset($arr['prop']))
	    	       {	
	    		$i = $j;
			$add = getElementProp($db, $client['ODid'], $client['oId'], $client['eId'], $arr['prop']);
			if (!isset($add)) $add = '';
			$add = "'".str_replace("'", "'".'"'."'".'"'."'", $add)."'";
	    	       }
		    break;
    	       case "<":
    		    if (($j = strpos($cmdline, '>', $i + 1)) !== false && (($match = substr($cmdline, $i + 1, $j - $i - 1)) === 'data' || $match === 'user' || $match === 'oid' || $match === 'title')) // Check for <data|user|oid|title> match
	    	       {	
			$i = $j;
			if ($match === 'data') $add = $client['data'];
			 else if ($match === 'user') $add = $client['auth'];
			 else if ($match === 'oid') $add = $client['oId'];
			 else $add = $client['allelements'][$client['eId']]['element1']['data'];
			$add = "'".str_replace("'", "'".'"'."'".'"'."'", $add)."'";
	    	       }
	      }
       $newcmdline .= $add;
      }

 return $newcmdline;
}

// Init variables
$client	= json_decode($_SERVER['argv'][ARGVCLIENTINDEX], true);
$_client = ['ODid' => $client['ODid'], 'OD' => $client['OD'], 'OVid' => $client['OVid'], 'OV' => $client['OV'], 'params' => $client['params'], 'oId' => $client['oId'], 'eId' => $client['eId'], 'cid' => $client['cid'], 'uid' => $client['uid']];
$output = [];

if ($client['cmd'] === 'INIT' || $client['cmd'] === 'DELETEOBJECT')
   {
    $output[$client['eId']]['cmd'] = $client['cmd'];
   }
 else
   {
    if (!isset($client['data']) || (gettype($client['data']) != 'string' && gettype($client['data']) != 'array')) $client['data'] = '';
     else if (gettype($client['data']) === 'array') $client['data'] = json_encode($client['data'], JSON_HEX_APOS | JSON_HEX_QUOT);

    if (($cmdline = GetCMD($db, $client)) === '') exit;
    $output[$client['eId']] = [];

    exec($cmdline, $output[$client['eId']]);
    if (!ParseHandlerResult($db, $output[$client['eId']], $client)) exit;
   }

switch ($output[$client['eId']]['cmd'])
       {
        case 'DIALOG':
        case 'CALL':
        case 'EDIT':
        case 'ALERT':
	     $output = $output[$client['eId']] + $_client;
	     break;
        case 'SET':
        case 'RESET':
	     $excludeid = $client['eId'];
	     foreach ($client['allelements'] as $eid => $profile) if ($eid != $excludeid)
	    	     {
		      $client['eId'] = $eid;
		      $client['cmd'] = 'CHANGE';
		      if (($cmdline = GetCMD($db, $client)) === '') continue;
		      $output[$eid] = [];
		      exec($cmdline, $output[$eid]);
		      if (!ParseHandlerResult($db, $output[$eid], $client)) $output[$eid] = [];
		     }
	     if ($client['ODid'] === '1' && $excludeid === '1' && isset($output[$excludeid]['password'])) $passchange = '';
	     try {
	          $db->beginTransaction();
	          $query = $db->prepare("SELECT version FROM `data_$client[ODid]` WHERE id=$client[oId] AND lastversion=1 AND version!=0 FOR UPDATE");
	          $query->execute();
	          $version = $query->fetchAll(PDO::FETCH_NUM); // Get selected version
	          if (count($version) === 0) throw new Exception("Please refresh Object View, object with id=$client[oId] doesn't exist!"); // No rows found? Return an error
	          $version = intval($version[0][0]) + 1; // Increment version to use it as a new version of the object
	          
	          $query = $db->prepare("UPDATE `data_$client[ODid]` SET lastversion=0 WHERE id=$client[oId] AND lastversion=1"); // Unset last flag of the object current version and insert new object version with empty data
	          $query->execute();
	          $query = $db->prepare("INSERT INTO `data_$client[ODid]` (id,owner,version,lastversion) VALUES ($client[oId],:owner,$version,1)");
	          $query->execute([':owner' => $client['auth']]);
		  foreach ($client['allelements'] as $eid => $value)
			  {
			   $client['eId'] = $eid;
			   if (!WriteElement($db, $client, $output[$eid], $version)) unset($output[$eid]);
			  }
		    
		  $ruleresult = ProcessRules($db, $client, strval($version - 1), strval($version), 'Change object');
		  if ($ruleresult['action'] === 'Accept')
		     {
	              $db->commit();
		      if (isset($ruleresult['log'])) LogMessage($db, $client, $ruleresult['log']);
	    	      foreach ($output as $eid => $value) foreach ($value as $prop => $valeu)
		    	      if (array_search($prop, ['hint', 'description', 'value', 'style']) === false) unset($output[$eid][$prop]);
	    	      $output = ['cmd' => 'SET', 'data' => $output] + $_client;

		      if (isset($ruleresult['message']))		$output['alert'] = $ruleresult['message'];
	    	      if (isset($output['data'][$excludeid]['alert']))	$output['alert'] = $output['data'][$excludeid]['alert'];
			 
	    	      if (isset($passchange)) $output['passchange'] = strval($client['oId']);
	    	      if ($client['ODid'] === '1' && strval($client['eId']) === '6' && strval($client['uid']) === strval($client['oId']))
	    		 {
			  $output['customization'] = getUserCustomization($db, $client['uid']);
			  if (!isset($output['customization'])) unset($output['customization']);
			 }
	    	      break;
		     }
		 }
	     catch (PDOException $e)
		 {
		  preg_match("/Duplicate entry/", $msg = $e->getMessage()) === 1 ? $ruleresult = ['message' => 'Failed to write object data: unique elements duplicate entry!'] : $ruleresult = ['message' => "Failed to write object data: $msg"];
		  $ruleresult['log'] = $ruleresult['message'];
		 }
	     $db->rollBack();
	     if (isset($ruleresult['log'])) LogMessage($db, $client, $ruleresult['log']);
	     $output = ['cmd' => 'SET', 'data' => [$excludeid => ['cmd' => 'SET', 'value' => getElementProp($db, $client['ODid'], $client['oId'], $excludeid, 'value')]], 'alert' => $ruleresult['message']] + $_client;
	     $query = $db->prepare("INSERT INTO `$$` (client) VALUES (:client)");
	     $query->execute([':client' => json_encode($output, JSON_HEX_APOS | JSON_HEX_QUOT)]);
	     break;
        case 'INIT':
	     $data = $client['data'];
	     foreach ($client['allelements'] as $eid => $profile)
    		     {
		      $client['eId'] = $eid;
		      if (isset($data[$eid])) $client['data'] = $data[$eid]; else $client['data'] = '';
		      $output[$eid] = [];
		      if (($cmdline = GetCMD($db, $client)) === '') continue;
		      exec($cmdline, $output[$eid]);
		      if (!ParseHandlerResult($db, $output[$eid], $client)) unset($output[$eid]);
    		     }
	     $output = InsertObject($db, $client, $output, $_client);
	     break;
        case 'DELETEOBJECT':
	     $output = DeleteObject($db, $client, $_client);
	     break;
       }

if ($output != [])
   {
    $query = $db->prepare("INSERT INTO `$$` (client) VALUES (:client)");
    $query->execute([':client' => json_encode($output, JSON_HEX_APOS | JSON_HEX_QUOT)]);
   }
