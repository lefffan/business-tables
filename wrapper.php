<?php

require_once 'core.php';

CONST ARGVCLIENTINDEX = 9;

function ProcessCHANGEevent($db, &$client, &$output, $currenteid)
{
 // Function goes through all elements except current (element initiated object data change) and calls its 'CHANGE' event handlers
 // No handler or its execution result - unset output array data for appropriate index as element id
 $client['cmd'] = 'CHANGE';
 foreach ($client['allelements'] as $eid => $profile) if ($eid != $currenteid)
	 {
	  $client['eId'] = $eid;
	  if (($cmdline = GetCMD($db, $client)) === '') continue; // No handler
	  exec($cmdline, $output[$eid]);
	  if (!ParseHandlerResult($db, $output[$eid], $client)) unset($output[$eid]); // Parse handler result data, if failed - unset output
	 }
}

function ParseHandlerResult($db, &$output, &$client)
{
 if (!isset($output[0])) return;
 $output = gettype($result = json_decode($output[0], true)) === 'array' ? $result : ['cmd' => 'SET', 'value' => implode("\n", $output)];

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
		  LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] shouldn't return 'EDIT' command on object '$client[cmd]' event!");
		  return;
		 }
	      ConvertToString($output, ['data']);
	      if (!isset($output['data'])) $output['data'] = getElementProp($db, $client['ODid'], $client['oId'], $client['eId'], 'value');
	      cutKeys($output, ['cmd', 'data']);
	      break;
	 case 'ALERT':
	      if ($client['cmd'] === 'CHANGE' || $client['cmd'] === 'INIT' || $client['cmd'] === 'SCHEDULE')
	         {
		  LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] shouldn't return 'ALERT' command on object '$client[cmd]' event!");
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
		  LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] shouldn't return 'DIALOG' command on object '$client[cmd]' event!");
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
	          LogMessage($db, $client, "Handler for element id $client[eId] and object id $client[oId] shouldn't return 'CALL' command on object '$client[cmd]' event!");
		  return;
		 }
	      cutKeys($output, ['cmd', 'ODid', 'OVid', 'params']);
	      break;
	 case 'SET':
	 case 'RESET':
	      // Adjust value, hint, description, style, alert properties, empty property is not allowed unset($output['']) ?
	      ConvertToString($output, ['value', 'hint', 'description', 'style', 'alert'], ELEMENTDATAVALUEMAXCHAR);
	      if ($client['cmd'] === 'CHANGE') unset($output['alert']);
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
 if ($output['cmd'] === 'RESET') $output += DEFAULTELEMENTPROPS;

 $query = $db->prepare("UPDATE `data_$client[ODid]` SET eid$client[eId]=:json WHERE id=$client[oId] AND version=$version");
 $query->execute([':json' => json_encode($output, JSON_UNESCAPED_UNICODE)]);
 return true;
}

function GetElementProperty($db, $output, &$client, $recursion)
{
 if (gettype($output) !== 'array') return '';

 if ($client['oId'] === 0) $errormessage = "Incorrect JSON input argument for database '$client[OD]' (view '$client[OV]') and new object (element id$client[eId]) handler call: ";
  else $errormessage = "Incorrect JSON input argument for database '$client[OD]' (view '$client[OV]') and object id$client[oId] (element id$client[eId]) handler call: ";

 $recursion++;
 if ($recursion > ARGRECURSIONNUM && !LogMessage($db, $client, $errormessage."recursive calls exceed max allowed ($recursion)!")) return '';

 // Fetch OD/OV, check them and their access
 if (!isset($output['ODid']) && !isset($output['OD'])) $output['ODid'] = $client['ODid'];
 if (!isset($output['OVid']) && !isset($output['OV'])) $output['OVid'] = $client['OVid'];
 $output = ['cmd' => 'CALL', 'uid' => $client['uid'], 'auth' => $client['auth']] + $output;
 if (!Check($db, CHECK_OD_OV | GET_ELEMENTS | GET_VIEWS | CHECK_ACCESS, $output, $output) && !LogMessage($db, $client, $errormessage.$output['error'])) return '';
 if ($output['viewtype'] !== 'Table' && !LogMessage($db, $client, $errormessage.'allowed for table templates only!')) return '';

 // Fetch input array :parameters and unset all unknown
 foreach ($output as $key => $value)
	 if ($key[0] === ':')
	    {
	     if (gettype($value) !== 'string') $output[$key] = GetElementProperty($db, $value, $client, $recursion);
	    }
	  else
	    {
	     if (!in_array($key, ['OD', 'OV', 'ODid', 'OVid', 'objectselection', 'elementselection', 'allelements', 'selection', 'element', 'prop', 'limit'])) unset($output[$key]);
	    }

 // Fetch result lines limit
 $limit = 1;
 if (isset($output['limit']) && ctype_digit($output['limit']) && intval($output['limit']) > 0) $limit = strval(min(intval($output['limit']), ARGRESULTLIMITNUM));

 // Get OD/OV object selection
 $output['objectselection'] = GetObjectSelection($output['objectselection'], $output, $client['auth']);
 if (gettype($output['objectselection']) !== 'string' && !LogMessage($db, $client, $errormessage.'incomplete object selection parameters!')) return '';

 // Set default input arg object selection
 if (!isset($output['selection']) || gettype($output['selection']) !== 'string' || $output['selection'] === '')
    $output['selection'] = "id=$client[oId] AND lastversion=1 AND version!=0";
  else
    $output['selection'] = GetObjectSelection($output['selection'], $output, $client['auth'], true);

 // Calculate prop
 $prop = isset($output['prop']) ? trim($output['prop']) : 'value';

 // Calculate element. Absent case - current element is used.
 $element = isset($output['element']) ? trim($output['element']) : $client['eId'];

 // Calculate select clause. In case of regular expression (/../) use all elements in a layout.
 if ($element[0] === '/' && $element[strlen($element) - 1] === '/')
    {
     $regular = $select = '';
     $props = setElementSelectionIds($output);
     foreach ($props as $eid => $value)
	     if (in_array($eid, SERVICEELEMENTS)) $select .= ','.$eid;
	      elseif ($eid !== '0') $select .= ",JSON_UNQUOTE(JSON_EXTRACT(eid$eid, '$.$prop'))";
     if (!$select) return '';
     $select = substr($select, 1);
     $element = GetObjectSelection($element, $output, $client['auth']);
    }
  elseif (in_array($element, SERVICEELEMENTS)) $select = $element;
  elseif (ctype_digit($element) && ($props = setElementSelectionIds($output)) && isset($props[$element])) $select = "JSON_UNQUOTE(JSON_EXTRACT(eid$element, '$.$prop'))";
  elseif (!LogMessage($db, $client, $errormessage."specified element doesn't exist in a view 'element layout' or incorrect!")) return '';

 // Result query
 try {
      $query = $db->prepare("SELECT $select FROM (SELECT * FROM `data_$output[ODid]` WHERE $output[selection] LIMIT $limit) _ $output[objectselection]");
      $query->execute();
     }
 catch (PDOException $e)
     {
      LogMessage($db, $client, $errormessage.$e->getMessage());
      return '';
     }
 $data = $query->fetchAll(PDO::FETCH_NUM);
 if (!isset($data[0][0])) return '';

 // Search element in result $data
 $result = '';
 if (isset($regular))
    {
     foreach ($data as $value) foreach ($value as $val) if (preg_match($element, $val))
	     {
	      $result .= $val."\n";
	      if (strlen($result) > ELEMENTDATAVALUEMAXCHAR) return substr($result, 0, ELEMENTDATAVALUEMAXCHAR);
	     }
    }
  else
    {
     foreach ($data as $value) $result .= $value[0]."\n";
    }
 if (strlen($result) > ELEMENTDATAVALUEMAXCHAR) return substr($result, 0, ELEMENTDATAVALUEMAXCHAR);
 return substr($result, 0, -1);
}

function GetCMD($db, &$client, $cmdline = false)
{
 if (!$cmdline) $cmdline = trim($client['allelements'][$client['eId']]['element'.array_search($client['cmd'], ['4'=>'INIT', '5'=>'DBLCLICK', '6'=>'KEYPRESS', '7'=>'INS', '8'=>'DEL', '9'=>'F2', '10'=>'F12', '11'=>'CONFIRM', '12'=>'CONFIRMDIALOG', '13'=>'CHANGE'])]['data']);
 if (!($len = strlen($cmdline))) return '';
 $i = -1;
 $newcmdline = '';

 while (++$i < $len)
       {
    	if (($add = $cmdline[$i]) === "'" && ($j = strpos($cmdline, "'", $i + 1)) !== false)
	   {
	    $newcmdline .= "'".substr($cmdline, $i + 1, $j - $i - 1)."'";
	    $i = $j;
	    continue;
	   }
        if ($add === '>') continue;
    	if ($add === '<')
	   {
	    if (($j = strpos($cmdline, '>', $i + 1)) === false) continue;
	    switch ($match = substr($cmdline, $i + 1, $j - $i - 1))
		   {
		    case 'data':  $add = DoubleQuote($client['data']); break;
		    case 'user':  $add = DoubleQuote($client['auth']); break;
		    case 'oid':   $add = DoubleQuote($client['oId']); break;
		    case 'event': $add = DoubleQuote($client['cmd']); break;
		    case 'title': $add = DoubleQuote($client['allelements'][$client['eId']]['element1']['data']); break;
		    case 'datetime': $datetime = new DateTime(); $add = DoubleQuote($datetime->format('Y-m-d H:i:s')); break;
		    default: if (gettype($add = json_decode($match, true)) !== 'array') $add = DoubleQuote("<$match>"); // Quote pair angle brackets to avoid stdin/stdout
			      else $add = DoubleQuote(GetElementProperty($db, $add, $client, 0));
		   }
	    $i = $j;
	   }
       $newcmdline .= $add;
      }
 return $newcmdline;
}

function DoubleQuote($string)
{
 return "'".str_replace("'", "'".'"'."'".'"'."'", $string)."'";
}

// Init variables
$_client = $client = json_decode($_SERVER['argv'][ARGVCLIENTINDEX], true);
$output = [];

if (!Check($db, GET_ELEMENTS | GET_VIEWS | CHECK_OID | CHECK_EID | CHECK_ACCESS, $client, $output))
   {
    if ($client['cmd'] === 'SCHEDULE')
       isset($output['error']) ? LogMessage($db, $client, $output['error']) : LogMessage($db, $client, $output['alert']);
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

$currenteid = $client['eId'];
switch ($output[$currenteid]['cmd'])
       {
        case 'DIALOG':
        case 'EDIT':
        case '':
	     $output = $output[$client['eId']];
	     break;
        case 'SET':
        case 'RESET':
	     // Password property for Users OD element id 1 is set? Note it to logout appropriate user
	     if ($client['ODid'] === '1' && $currenteid === '1' && isset($output[$currenteid]['password'])) $passchange = '';
	     // Write all object data
	     try {
	          $db->beginTransaction();
	          $query = $db->prepare("SELECT version,mask FROM `data_$client[ODid]` WHERE id=$client[oId] AND lastversion=1 AND version!=0 FOR UPDATE");
	          $query->execute();
	          $version = $query->fetchAll(PDO::FETCH_NUM); // Get selected version
	          if (count($version) === 0) throw new Exception("Object id $client[oId] doesn't exist! Please refresh Object View."); // No rows found? Return an error
	          $mask = $version[0][1];
	          $version = intval($version[0][0]) + 1; // Increment version to use it as a new version of the object
	          
	          $query = $db->prepare("UPDATE `data_$client[ODid]` SET lastversion=0 WHERE id=$client[oId] AND lastversion=1"); // Unset last flag of the object current version and insert new object version with empty data
	          $query->execute();
	          $query = $db->prepare("INSERT INTO `data_$client[ODid]` (id,owner,version,lastversion) VALUES ($client[oId],:owner,$version,1)");
	          $query->execute([':owner' => $client['auth']]);
		  $newmask = '';

		  if (isset($output[$currenteid]['alert'])) $alert = $output[$currenteid]['alert'];
		  unset($output[$currenteid]['alert']);
		  WriteElement($db, $client, $output[$currenteid], $version);
		  ProcessCHANGEevent($db, $client, $output, $currenteid);
		  foreach ($client['allelements'] as $eid => $value) if ($eid != $currenteid)
			  {
			   $client['eId'] = $eid;
			   if (!isset($output[$eid])) $output[$eid] = [];
			   if (!WriteElement($db, $client, $output[$eid], $version))
			      {
			       unset($output[$eid]);
			       $newmask .= "eid$eid=NULL,";
			      }
			  }

		  $ruleresult = ProcessRules($db, $client, strval($version - 1), strval($version), 'Change object');
		  if ($ruleresult['action'] === 'Accept')
		     {
		      if ($newmask != '')
		         {
			  $query = $db->prepare("UPDATE `data_$client[ODid]` SET mask='".substr($newmask, 0, -1)."' WHERE id=$client[oId] AND version=$version");
		          $query->execute();
			 }
		      if ($mask != '')
		         {
			  $query = $db->prepare("UPDATE `data_$client[ODid]` SET $mask WHERE id=$client[oId] AND version=".strval($version - 1));
		          $query->execute();
			 }
	              try { $db->commit(); }
		      catch (PDOException $e) { }
		      if (isset($ruleresult['log'])) LogMessage($db, $client, $ruleresult['log']);
	    	      foreach ($output as $eid => $value) foreach ($value as $prop => $valeu)
		    	      if (array_search($prop, ['hint', 'description', 'value', 'style', 'alert']) === false) unset($output[$eid][$prop]);
	    	      $output = ['cmd' => 'SET', 'data' => $output];

		      if (isset($ruleresult['message']) && $ruleresult['message'])	$output['alert'] = $ruleresult['message'];
	    	      if (isset($alert))						$output['alert'] = $alert;
			 
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
	     $output = ['cmd' => 'SET', 'data' => [$currenteid => ['cmd' => 'SET', 'value' => getElementProp($db, $client['ODid'], $client['oId'], $currenteid, 'value')]], 'alert' => $ruleresult['message']];
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
		      ParseHandlerResult($db, $output[$eid], $client);
		      $output[$eid] += DEFAULTELEMENTPROPS;
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
