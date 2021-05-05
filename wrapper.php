<?php

require_once 'core.php';

CONST ARGVCLIENTINDEX = 9;

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
  
 if (!isset($output['cmd']) || array_search($output['cmd'], ['EDIT', 'ALERT', 'DIALOG', 'CALL', 'SET', 'RESET', '']) === false)
    {
     LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] (OD: '$client[OD]', OV: '$client[OV]') returned undefined json!");
     return;
    }

 switch ($output['cmd'])
	{
	 case 'EDIT':
	      if ($client['cmd'] === 'CHANGE' || $client['cmd'] === 'INIT' || $client['cmd'] === 'SCHEDULE')
	         { 
		  LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] shouldn't return 'EDIT' command on object 'CHANGE' or 'INIT' event!");
		  return;
		 }
	      ConvertToString($output, ['data']);
	      if (!isset($output['data'])) $output['data'] = getElementProp($db, $client['ODid'], $client['oId'], $client['eId'], 'value');
	      cutKeys($output, ['cmd', 'data']);
	      break;
	 case 'ALERT':
	      if ($client['cmd'] === 'CHANGE' || $client['cmd'] === 'INIT' || $client['cmd'] === 'SCHEDULE')
	         {
		  LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] shouldn't return 'ALERT' command on object 'CHANGE' or 'INIT' event!");
		  return;
		 }
	      if (!isset($output['data']) || !ConvertToString($output, ['data']))
	         {
		  LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] returned undefined 'ALERT' message!");
		  return;
		 }
	      $output = ['cmd' => '', 'alert' => $output['data']];
	      break;
	 case 'DIALOG':
	      if ($client['cmd'] === 'CHANGE' || $client['cmd'] === 'INIT' || $client['cmd'] === 'SCHEDULE')
	         {
		  LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] shouldn't return 'DIALOG' command on object 'CHANGE' or 'INIT' event!");
		  return;
		 }
	      if (!isset($output['data']) || gettype($output['data']) != 'array')
	         {
	          LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] returned incorrect 'DIALOG' command data!");
		  return;
		 }
	      cutKeys($output, ['cmd', 'data']);
	      $output['data']['flags']['esc'] = '';
	      foreach ($output['data']['buttons'] as $button => $value)
	    	      {
		       if (isset($value['call'])) $output['data']['buttons'][$button]['call'] = 'CONFIRMDIALOG';
		        else unset($output['data']['buttons'][$button]['enterkey']);
		       if (!isset($output['data']['buttons'][$button]['timer'])) continue;
		       
		       if (isset($timer) || !ctype_digit($output['data']['buttons'][$button]['timer'])) unset($output['data']['buttons'][$button]['timer']);
		       if (isset($output['data']['buttons'][$button]['timer']))
		          {
			   $timer = intval($output['data']['buttons'][$button]['timer']);                                             
			   if ($timer < MINBUTTONTIMERMSEC) $output['data']['buttons'][$button]['timer'] = strval(MINBUTTONTIMERMSEC);
			   if ($timer > MAXBUTTONTIMERMSEC) $output['data']['buttons'][$button]['timer'] = strval(MAXBUTTONTIMERMSEC);
			  }
		      }	
	      break;
	 case 'CALL':
	      if ($client['cmd'] === 'CHANGE' || $client['cmd'] === 'INIT' || $client['cmd'] === 'SCHEDULE')
	         {
	          LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] shouldn't return 'CALL' command on object 'CHANGE' or 'INIT' event!");
		  return;
		 }
	      cutKeys($output, ['cmd', 'OD', 'OV', 'ODid', 'OVid', 'params']);
	      ConvertToString($output, ['OD', 'OV', 'ODid', 'OVid']);
	      if (!isset($output['params']) || gettype($output['params']) != 'array') $output['params'] = [];
	      if (!isset($output['ODid']) && !isset($output['OD'])) { $output['ODid'] = $client['ODid']; $output['OD'] = $client['OD']; }
	      if (!isset($output['OVid']) && !isset($output['OV'])) { $output['OVid'] = $client['OVid']; $output['OV'] = $client['OV']; }
	      if (!isset($output['ODid']))
	         {
		  $query = $db->prepare("SELECT id FROM $ WHERE odname=:odname");
		  $query->execute([':odname' => $output['OD']]);
		  $output['ODid'] = $query->fetchAll(PDO::FETCH_NUM);
		  isset($output['ODid'][0][0]) ? $output['ODid'] = $output['ODid'][0][0] : $output['ODid'] = '';
		 }
	      if (!isset($output['OVid']))
	         {
		  $output['OVid'] = '';
		  if ($output['ODid'] === '') break;
		  $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE id=:id");
		  $query->execute([':id' => $output['ODid']]);
		  foreach (json_decode($query->fetchAll(PDO::FETCH_NUM)[0][0], true) as $key => $View)
		       if ($key != 'New view' && $key === $output['OV'])
			  {
			   $output['OVid'] = $View['element1']['id'];
			   break;
			  }
		 }
	      break;
	 case 'SET':
	 case 'RESET':
	      ConvertToString($output, ['value', 'hint', 'description', 'alert'], ELEMENTDATAVALUEMAXCHAR);
	      break;
	 case '':
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

function GetCMD($db, &$client, $cmdline = false)
{
 if (!$cmdline) $cmdline = trim($client['allelements'][$client['eId']]['element'.array_search($client['cmd'], ['4'=>'INIT', '5'=>'DBLCLICK', '6'=>'KEYPRESS', '7'=>'INS', '8'=>'DEL', '9'=>'F2', '10'=>'F12', '11'=>'CONFIRM', '12'=>'CONFIRMDIALOG', '13'=>'CHANGE'])]['data']);
 if (!($len = strlen($cmdline))) return '';
 $i = -1;
 $newcmdline = '';

 while (++$i < $len)
       {
        switch ($add = $cmdline[$i])
    	       {
    	        case "'":
    		     if (($j = strpos($cmdline, "'", $i + 1)) !== false && ($arr = json_decode(substr($cmdline, $i + 1, $j - $i - 1), true)) && isset($arr['props']))
	    	       {
		        $add = NULL;
	    		$i = $j;
			cutKeys($arr, ['ODid', 'oId', 'eId', 'props']);
			if (!isset($arr['ODid']) || gettype($arr['ODid']) != 'string') $arr['ODid'] = $client['ODid'];
			if (!isset($arr['oId']) || gettype($arr['oId']) != 'string') $arr['oId'] = $client['oId'];
			if (!isset($arr['eId']) || gettype($arr['eId']) != 'string') $arr['eId'] = $client['eId'];

			if (gettype($arr['props']) === 'string')
			   {
			    $add = getElementProp($db, $arr['ODid'], $arr['oId'], $arr['eId'], $arr['props']);
			   }
			 else if (gettype($arr['props']) === 'array')
			   {
			    foreach($arr['props'] as $key => $value) $arr['props'][$key] = getElementProp($db, $arr['ODid'], $arr['oId'], $arr['eId'], $key);
			    $add = json_encode($arr['props'], JSON_HEX_APOS | JSON_HEX_QUOT);
			   }
			
			if (!isset($add)) $add = '';
			$add = "'".str_replace("'", "'".'"'."'".'"'."'", $add)."'";
	    	       }
		    break;
    	       case "<":
    		    if (($j = strpos($cmdline, '>', $i + 1)) !== false && (($match = substr($cmdline, $i + 1, $j - $i - 1)) === 'data' || $match === 'user' || $match === 'oid' || $match === 'event' || $match === 'title')) // Check for <data|user|oid|title> match
	    	       {	
			$i = $j;
			if ($match === 'data') $add = $client['data'];
			 else if ($match === 'user') $add = $client['auth'];
			 else if ($match === 'oid') $add = $client['oId'];
			 else if ($match === 'event') $add = $client['cmd'];
			 else $add = $client['allelements'][$client['eId']]['element1']['data'];
			$add = "'".str_replace("'", "'".'"'."'".'"'."'", $add)."'";
	    	       }
	      }
       $newcmdline .= $add;
      }

 return $newcmdline;
}

// Init variables
$_client = $client = json_decode($_SERVER['argv'][ARGVCLIENTINDEX], true);
$output = [];

if (!Check($db, GET_ELEMENTS | GET_VIEWS | CHECK_OID | CHECK_EID | CHECK_ACCESS, $client, $output))
   {
    $output = [$client['eId'] => $output + ['cmd' => '']];
   }
else if ($client['cmd'] === 'INIT' || $client['cmd'] === 'DELETEOBJECT')
   {
    $output = [$client['eId'] => ['cmd' => $client['cmd']]];
   }
 else
   {
    if (!isset($client['data']) || (gettype($client['data']) != 'string' && gettype($client['data']) != 'array')) $client['data'] = '';
     else if (gettype($client['data']) === 'array') $client['data'] = json_encode($client['data'], JSON_HEX_APOS | JSON_HEX_QUOT);

    if (isset($client['cmdline'])) $cmdline = $client['cmdline']; else $cmdline = false;
    if (($cmdline = GetCMD($db, $client, $cmdline)) === '') exit;
    $output[$client['eId']] = [];

    exec($cmdline, $output[$client['eId']]);
    if (!ParseHandlerResult($db, $output[$client['eId']], $client)) exit;
   }

switch ($output[$client['eId']]['cmd'])
       {
        case 'DIALOG':
        case 'EDIT':
        case '':
	     $output = $output[$client['eId']];
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
	          if (count($version) === 0) throw new Exception("Object id $client[oId] doesn't exist! Please refresh Object View."); // No rows found? Return an error
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
	    	      $output = ['cmd' => 'SET', 'data' => $output];

		      if (isset($ruleresult['message']) && $ruleresult['message'])	$output['alert'] = $ruleresult['message'];
	    	      if (isset($output['data'][$excludeid]['alert']))			$output['alert'] = $output['data'][$excludeid]['alert'];
			 
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
	     $output = ['cmd' => 'SET', 'data' => [$excludeid => ['cmd' => 'SET', 'value' => getElementProp($db, $client['ODid'], $client['oId'], $excludeid, 'value')]], 'alert' => $ruleresult['message']];
	     /*$query = $db->prepare("INSERT INTO `$$` (client) VALUES (:client)");
	     $query->execute([':client' => json_encode($output, JSON_HEX_APOS | JSON_HEX_QUOT)]);*/
	     break;
        case 'CALL':
	     $output = $output[$client['eId']];
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
	     AddObject($db, $client, $output);
	     break;
        case 'DELETEOBJECT':
	     DeleteObject($db, $client, $output);
	     break;
        case '':
	     $output = [];
	     break;
       }

if ($output != [])
   {
    $query = $db->prepare("INSERT INTO `$$` (client) VALUES (:client)");
    $query->execute([':client' => json_encode($output + $_client, JSON_HEX_APOS | JSON_HEX_QUOT)]);
   }
