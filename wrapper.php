<?php

require_once 'core.php';

const ARGVCLIENTINDEX = 9;
const GROUPEVENTS = ['CHANGE', 'INIT', 'SCHEDULE'];
const HANDLEREVENTS = ['EDIT', 'ALERT', 'DIALOG', 'CALL', 'SET', 'RESET', 'UPLOADDIALOG', 'DOWNLOADDIALOG', 'UNLOADDIALOG', 'GALLERY', ''];
// 65-90 a-z 48-57 0-9 96-107 numpad0-9*+ 109-111 numpad-./ 186-192 ;=,->/` 219-222 [\]' 32space FF59; FF61= FF173- 226\ F1-F12 112-123 INS45 DEL46
const USEREVENTKEYCODES = [65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,48,49,50,51,52,53,54,55,56,57,112,113,114,115,116,117,118,119,120,121,122,123,32,45,46,219,221];
const KEYCODESYMBOLRANGES = [65,90,48,57,96,107,109,111,186,192,219,222,32,32,59,59,61,61,173,173,226,226];

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
	  // Parse handler result data, if failed - unset output
	  if (!ParseHandlerResult($db, $output[$eid], $client)) unset($output[$eid]);
	 }
}

function ParseHandlerResult($db, &$output, &$client)
{
 if (!isset($output[0])) return;
 $output = gettype($result = json_decode($output[0], true)) === 'array' ? $result : ['cmd' => 'SET', 'value' => implode("\n", $output)];
 $logmsg = "Element id$client[eId] handler for object id$client[oId] ";

 // Incorrect handler response JSON
 if ((!isset($output['cmd']) || array_search($output['cmd'], HANDLEREVENTS) === false) && !LogMessage($db, $client, $logmsg.'returned incorrect json!')) return;
 // Parse handler output array
 switch ($output['cmd'])
	{
	 case 'EDIT':
	      // Unsupported command
	      if (array_search($client['cmd'], GROUPEVENTS) !== false && !LogMessage($db, $client, $logmsg."shouldn't return 'EDIT' command on '$client[cmd]' event!")) return;
	      ConvertToString($output, ['data']);
	      if (!isset($output['data'])) $output['data'] = getElementProp($db, $client['ODid'], $client['oId'], $client['eId'], 'value');
	      cutKeys($output, ['cmd', 'data']);
	      break;
	 case 'ALERT':
	      // Unsupported command
	      if (array_search($client['cmd'], GROUPEVENTS) !== false && !LogMessage($db, $client, $logmsg."shouldn't return 'ALERT' command on '$client[cmd]' event!")) return;
	      // Undefined alert
	      if ((!isset($output['data']) || !ConvertToString($output, ['data'])) && !LogMessage($db, $client, $logmsg."returned undefined 'ALERT' message!")) return;
	      $output = ['cmd' => '', 'alert' => $output['data']];
	      break;
	 case 'DIALOG':
	      // Unsupported command
	      if (array_search($client['cmd'], GROUPEVENTS) !== false && !LogMessage($db, $client, $logmsg."shouldn't return 'DIALOG' command on '$client[cmd]' event!")) return;
	      // Undefined dialog
	      if ((!isset($output['data']) || gettype($output['data']) != 'array') && !LogMessage($db, $client, $logmsg."returned incorrect 'DIALOG' command data!")) return;
	      cutKeys($output, ['cmd', 'data']);
	      // User handler dialog should always be escapeable to avoid interface lock
	      $output['data']['flags']['esc'] = '';
	      foreach ($output['data']['buttons'] as $button => $value)
		      {
		       // Always set 'CONFIRMDIALOG' as a handler dialog button event (if 'call' exists), otherwise unset enterkey prop to not call button event on 'enter' key
		       if (isset($value['call'])) $output['data']['buttons'][$button]['call'] = 'CONFIRMDIALOG';
		        else unset($output['data']['buttons'][$button]['enterkey']);
		       // Continue for no button timer
		       if (!isset($output['data']['buttons'][$button]['timer'])) continue;
		       // Timer for previous buttons already exists or non-digital timer string? Unset timer prop and continue
		       if (isset($timer) || !ctype_digit($output['data']['buttons'][$button]['timer']))
			  {
			   unset($output['data']['buttons'][$button]['timer']);
			   continue;
			  }
		       // No previous button timers and new button timer does exist and digital? Set it
		       $timer = intval($output['data']['buttons'][$button]['timer']);
		       if ($timer < MINBUTTONTIMERMSEC) $output['data']['buttons'][$button]['timer'] = strval(MINBUTTONTIMERMSEC);
		       if ($timer > MAXBUTTONTIMERMSEC) $output['data']['buttons'][$button]['timer'] = strval(MAXBUTTONTIMERMSEC);
		      }
	      break;
	 case 'CALL':
	      // Unsupported command
	      if (array_search($client['cmd'], GROUPEVENTS) !== false && !LogMessage($db, $client, $logmsg."shouldn't return 'CALL' command on '$client[cmd]' event!")) return;
	      cutKeys($output, ['cmd', 'ODid', 'OVid', 'OD', 'OV', 'params']);
	      if (!isset($output['OD']) && !isset($output['ODid'])) $output['ODid'] = $client['ODid'];
	      if (!isset($output['OV']) && !isset($output['OVid'])) $output['OVid'] = $client['OVid'];
	      break;
	 case 'SET':
	 case 'RESET':
	      // Adjust value, hint, link, style and alert properties
	      ConvertToString($output, ['value', 'hint', 'link', 'style', 'alert'], ELEMENTDATAVALUEMAXCHAR);
	      if ($client['cmd'] === 'CHANGE') unset($output['alert']); // Alert is not supported for object 'CHANGE' event
	      break;
	 case 'UPLOADDIALOG':
	 case 'DOWNLOADDIALOG':
	 case 'UNLOADDIALOG':
	 case 'GALLERY':
	      if (array_search($client['cmd'], GROUPEVENTS) !== false && !LogMessage($db, $client, $logmsg."shouldn't return '$output[cmd]' command on '$client[cmd]' event!")) return;
	      cutKeys($output, ['cmd']);
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
	   else if (gettype($arr[$value]) != 'string' && !($result = false)) unset($arr[$value]);
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
 if ($output['cmd'] === 'SET' && gettype($oldData = getElementArray($db, $client['ODid'], $client['oId'], $client['eId'], $version - 1)) === 'array')
    $output = array_replace($oldData, $output);
 if ($output['cmd'] === 'RESET') $output += DEFAULTELEMENTPROPS;

 $query = $db->prepare("UPDATE `data_$client[ODid]` SET eid$client[eId]=:json WHERE id=$client[oId] AND version=$version");
 $query->execute([':json' => json_encode($output, JSON_UNESCAPED_UNICODE)]);
 return true;
}

function GetElementProperty($db, $output, &$client, $recursion)
{
 if (gettype($output) !== 'array') return '';

 if ($client['oId'] === 0)
    $errormessage = "Incorrect JSON input argument for database '$client[OD]' (view '$client[OV]') and new object (element id$client[eId]) handler call: ";
  else
    $errormessage = "Incorrect JSON input argument for database '$client[OD]' (view '$client[OV]') and object id$client[oId] (element id$client[eId]) handler call: ";

 $recursion++;
 //if ($recursion > ARGRECURSIONNUM && !LogMessage($db, $client, $errormessage."recursive calls exceed max allowed ($recursion)!")) return '';
 if ($recursion > ARGRECURSIONNUM) return '';

 // Fetch OD/OV, check them and their access
 if (!isset($output['ODid']) && !isset($output['OD'])) $output['ODid'] = $client['ODid'];
 if (!isset($output['OVid']) && !isset($output['OV'])) $output['OVid'] = $client['OVid'];
 $output = ['cmd' => 'CALL', 'uid' => $client['uid'], 'auth' => $client['auth']] + $output;
 if (!Check($db, CHECK_OD_OV | GET_ELEMENTS | GET_VIEWS | CHECK_ACCESS, $output, $output) && !LogMessage($db, $client, $errormessage.$output['error'])) return '';

 // Fetch input array :parameters and unset all unknown
 foreach ($output as $key => $value)
	 if ($key[0] === ':')
	    {
	     if (gettype($value) !== 'string') $output[$key] = GetElementProperty($db, $value, $client, $recursion);
	    }
	  else
	    {
	     if (!in_array($key, ['OD', 'OV', 'ODid', 'OVid', 'viewtype', 'objectselection', 'elementselection', 'allelements', 'object', 'element', 'prop', 'limit', 'linknames', 'regex', 'regexp'])) unset($output[$key]);
	    }

 // Fetch result lines limit
 $limit = '1';
 if (isset($output['limit']) && ctype_digit($output['limit']) && intval($output['limit']) > 0) $limit = strval(min(intval($output['limit']), ARGRESULTLIMITNUM));

 // Get OD/OV object selection
 if ($output['ODid'] === $client['ODid'] && $output['OVid'] === $client['OVid']) $output += $client['params'];
 $output['objectselection'] = GetObjectSelection($output['objectselection'], $output, $client['auth']);
 //if (gettype($output['objectselection']) !== 'string' && !LogMessage($db, $client, $errormessage.'incomplete object selection parameters!')) return '';
 if (gettype($output['objectselection']) !== 'string') return '';

 // Set default input arg object selection
 if (!isset($output['object']) && $output['ODid'] === $client['ODid'] && $output['OVid'] === $client['OVid'])
    {
     $output['object'] = "id=$client[oId] AND lastversion=1 AND version!=0";
    }
  else if (!isset($output['object']) || gettype($output['object']) !== 'string' || trim($output['object']) === '')
    {
     $output['object'] = '1=1';
    }
  else
    {
     $output['object'] = GetObjectSelection($output['object'], $output, $client['auth'], true);
    }

 // Calculate prop
 $prop = isset($output['prop']) ? trim($output['prop']) : 'value';

 // Calculate elements list via $output['element']. Absent case - current element is used, empty list - all elements of the view, otherwise elements via comma
 if (isset($output['element']) && gettype($output['element']) === 'string') $output['element'] = trim($output['element']); else $output['element'] = $client['eId'];
 if ($output['viewtype'] === 'Table') { SetLayoutProperties($output); $elements = $output['layout']['elements']; }
  else if ($output['viewtype'] === 'Tree') $elements = $output['elementselection'];
 $select = '';
 if ($output['element'] === '')
    {
     foreach ($elements as $eid => $value)
	     if (in_array($eid, SERVICEELEMENTS)) $select .= ','.$eid;
	      elseif ($eid !== '0') $select .= ",JSON_UNQUOTE(JSON_EXTRACT(eid$eid, '$.$prop'))";
    }
  else
    {
     foreach (preg_split("/,/", $output['element']) as $eid)
	     if (in_array($eid, SERVICEELEMENTS)) $select .= ','.$eid;
	      elseif (isset($elements[$eid])) $select .= ",JSON_UNQUOTE(JSON_EXTRACT(eid$eid, '$.$prop'))";
    }
 //if (!$select && !LogMessage($db, $client, $errormessage."specified element is not defined in a view 'element layout' or incorrect!")) return '';
 if (!$select) return '';
 $select = substr($select, 1);

 // Calculate two regexp strings
 if (isset($output['regexp']) && ($output['regexp'] = CalcRegex($output['regexp'], $output, $client['auth'])) === false) return '';
 if (isset($output['regex']) && ($output['regex'] = CalcRegex($output['regex'], $output, $client['auth'])) === false) return '';

 // Result query
 if ($output['linknames'] === [])
    {
     try {
	  $query = $db->prepare("SELECT $select FROM (SELECT * FROM `data_$output[ODid]` $output[objectselection]) _ WHERE $output[object]");
	  $query->execute();
	 }
     catch (PDOException $e)
	 {
	  LogMessage($db, $client, $errormessage.$e->getMessage());
	  return '';
	 }
     $output['tree'] = $query->fetchAll(PDO::FETCH_NUM);
    }
  else
    {
     $data = [];
     $output['select'] = $select;
     $output['limit'] = intval($limit);
     CreateTree($db, $output, 0, $data, 'SEARCH');
    }
 if (!isset($output['tree'][0][0])) return '';

 // Search on regex and regexp
 $result = '';
 foreach ($output['tree'] as $value) foreach ($value as $val)
	 {
	  if (isset($output['regex']))
	     {
	      $matches = [];
	      if (!preg_match($output['regex'], $val, $matches)) continue;
	      if (isset($output['regexp']) && !preg_match($output['regexp'], $matches[0])) continue;
	     }
	  $result .= $val."\n";
          $limit --;
          if (!$limit) break 2;
	 }

 return substr($result, 0, -1);
}

function CalcRegex($regex, &$output, $auth)
{
 if (gettype($regex) !== 'string' || ($regex = trim($regex)) === '' || $regex[0] !== '/' || ($pos = strpos($regex, '/', 2)) === false) return false;

 if (!($flags = substr($regex, $pos + 1))) $flags = '';
 $regex = substr($regex, 1, $pos - 1);
 $regex = GetObjectSelection($regex, $output, $auth, true);
 if (!$regex) return false;

 return '/'.$regex.'/'.$flags;
}

function GetCMD($db, &$client, $cmdline = false)
{
 // Caclulate incoming client event modificator keys
 if ($client['cmd'] === 'DBLCLICK')
    {
     $event = 'DBLCLICK';
     $modificators = intval($client['data']);
    }
  else if (ctype_digit($client['cmd']))
    {
     $event = intval($client['cmd']) & 255;
     $modificators = (intval($client['cmd']) & 65280) / 256;
    }
  else
    {
     $event = $client['cmd'];
    }

 // Search specified event above (excluding 'SCHEDULE' event in case of $cmdline exist)
 if (!$cmdline)
 foreach ($client['allelements'][$client['eId']] as $key => $value)
	 {
	  $eid = intval(substr($key, 7)); // Calculate interface element id number
	  if ($eid < 11 || !($eid % 2) || ($pos = strpos($value['event'], '+') + 1) === false) continue; // Then check it to be more than 10 and odd. Serach for the selected event also
	  if (!isset($modificators) || $modificators === $value['modificators']) // Check Ctrl, Alt, Shift, Meta modificators

	  if ($event === ($elementevent = substr($value['event'], $pos, strpos($value['event'], '|', $pos) - $pos))) // Exact event match
	     {
	      $cmdline = $value['data'];
	      break;
	     }

	  if (gettype($event) === 'integer') continue; // No key down event? Continue

	  if (!(($i = array_search($event, USEREVENTKEYCODES)) === false) && USEREVENTCODES[$i] === $elementevent) // Exact key event match
	     {
	      $cmdline = $value['data'];
	      break;
	     }
	
	  if (RangeTest($event, KEYCODESYMBOLRANGES)) $cmdline = $value['data']; // May be symbol key to match KEYPRESS
	 }

 // Check result command line
 if (!($len = strlen($cmdline))) return '';
 $i = -1;
 $newcmdline = '';

 // Parse command line args to replace
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

 // Return modified command line
 return $newcmdline;
}

function GetCMD1($db, &$client, $cmdline = false)
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
		    //case 'oid':   $add = DoubleQuote($client['oId']); break;
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
    if ($client['cmd'] === 'SCHEDULE') // Log error result for 'SCHEDULE' event, otherwise error is displayed as a main view message
       isset($output['error']) ? LogMessage($db, $client, $output['error']) : LogMessage($db, $client, $output['alert']);
    if (isset($output['log'])) LogMessage($db, $client, $output['log']);
    $output = [$client['eId'] => ['cmd' => ''] + $output];
   }
else if ($client['cmd'] === 'INIT' || $client['cmd'] === 'DELETEOBJECT')
   {
    $output = [$client['eId'] => ['cmd' => $client['cmd']]];
   }
 else
   {
    if (!isset($client['data']) || (gettype($client['data']) != 'string' && gettype($client['data']) != 'array')) $client['data'] = '';
     else if (gettype($client['data']) === 'array') $client['data'] = json_encode($client['data'], JSON_HEX_APOS | JSON_HEX_QUOT);

    // CMD line already defined (for 'SCHEDULE' event)? Pass it to GetCMD
    if (($cmdline = GetCMD($db, $client, isset($client['cmdline']) ? $client['cmdline'] : false)) === '') exit;
    $output[$client['eId']] = [];
    exec($cmdline, $output[$client['eId']]);
    if (!ParseHandlerResult($db, $output[$client['eId']], $client)) exit;
   }

$currenteid = $client['eId'];
switch ($output[$currenteid]['cmd'])
       {
        case 'DIALOG':
        case 'EDIT':
        case 'CALL':
        case '':
        case 'UPLOADDIALOG':
        case 'DOWNLOADDIALOG':
        case 'UNLOADDIALOG':
        case 'GALLERY':
	     $output = $output[$client['eId']];
	     if ($output['cmd'] === 'DOWNLOADDIALOG' || $output['cmd'] === 'UNLOADDIALOG' || $output['cmd'] === 'GALLERY')
		{
		 $list = [];
		 $dir = UPLOADDIR."$client[ODid]/$client[oId]/$client[eId]";
		 if (is_dir($dir)) foreach (scandir($dir) as $name) if ($name !== '.' && $name !== '..')
		    {
		     $ext = pathinfo($name);
		     $ext = isset($ext['extension']) ? $ext['extension'] : '';
		     if ($output['cmd'] !== 'GALLERY' || array_search($ext, ['jpg', 'png', 'gif', 'bmp']) !== false) $list[] = $name;
		    }
		 count($list) ? $output['list'] = $list : $output = ['cmd' => '', 'alert' => $output['cmd'] === 'GALLERY' ? 'No images attached to the object element! Upload some image files first!' : 'No files attached to the object element. Upload some files first!'];
		 break;
		}
	     if ($output['cmd'] === 'CALL') // Unset client OD/OV parameters to use them from handler responce of 'CALL' event
		{
		 unset($_client['OD'], $_client['OV'], $_client['ODid'], $_client['OVid']);
		 break;
		}
	     break;
        case 'SET':
        case 'RESET':
	     // Password property for Users OD element id 1 is set? Note it to logout appropriate user
	     if ($client['ODid'] === '1' && $currenteid === '1' && isset($output[$currenteid]['password'])) $passchange = '';
	     // Write all object data
	     try {
	          $db->beginTransaction();
		  // Block last object version row for update and calculate last version number
	          $query = $db->prepare("SELECT version,mask FROM `data_$client[ODid]` WHERE id=$client[oId] AND lastversion=1 AND version!=0 FOR UPDATE");
	          $query->execute();
	          $version = $query->fetchAll(PDO::FETCH_NUM);
		  // No rows found? Return an error
	          if (!isset($version[0])) throw new Exception("Object id $client[oId] doesn't exist! Please refresh the View.");
	          $mask = $version[0][1];
	          $version = intval($version[0][0]) + 1; // Increment version to use it as a new version of the object
		  // Unset last flag of the object current version and insert new object version with empty data
	          $query = $db->prepare("UPDATE `data_$client[ODid]` SET lastversion=0 WHERE id=$client[oId] AND lastversion=1");
	          $query->execute();
		  // Insert new object empty version
	          $query = $db->prepare("INSERT INTO `data_$client[ODid]` (id,owner,version,lastversion) VALUES ($client[oId],:owner,$version,1)");
	          $query->execute([':owner' => $client['auth']]);
		  $newmask = '';
		  // Remember initiated 'SET/RESET' commands element alert message
		  if (isset($output[$currenteid]['alert'])) $alert = $output[$currenteid]['alert'];
		  unset($output[$currenteid]['alert']);
		  // Write current element
		  WriteElement($db, $client, $output[$currenteid], $version);
		  // Process 'CHANGE' event for other elements on object element change
		  ProcessCHANGEevent($db, $client, $output, $currenteid);
		  // Write all other elements 'CHANGE' event data 
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
		  $client['eId'] = $currenteid;
		  // Check changed object on existing rules
		  $ruleresult = ProcessRules($db, $client, 'Change object', strval($version - 1), strval($version));
		  if ($ruleresult['action'] === 'Accept')
		     {
		      if ($newmask != '') // Update new object version with new element data
			 {
			  $query = $db->prepare("UPDATE `data_$client[ODid]` SET mask='".substr($newmask, 0, -1)."' WHERE id=$client[oId] AND version=$version");
			  $query->execute();
			 }
		      if ($mask != '') // Remove object previous version non-changed element data
			 {
			  $query = $db->prepare("UPDATE `data_$client[ODid]` SET $mask WHERE id=$client[oId] AND version=".strval($version - 1));
			  $query->execute();
			 }
		      try { $db->commit(); } // Commit object update operation
		      catch (PDOException $e) { }
		      if (isset($ruleresult['log'])) LogMessage($db, $client, $ruleresult['log']); // Log rule
		      // Unset properties of all element of the object different from 'hint', 'value', 'style' and 'alert'
		      foreach ($output as $eid => $value) foreach ($value as $prop => $valeu)
			      if (array_search($prop, ['hint', 'value', 'style', 'link', 'alert']) === false) unset($output[$eid][$prop]);
		      $output = ['cmd' => 'SET', 'data' => $output];
		      // Log rule message if main element alert doesn't exist
		      if (isset($ruleresult['message']) && $ruleresult['message'])	$output['alert'] = $ruleresult['message'];
		      if (isset($alert))						$output['alert'] = $alert;
		      // Check pass or customization change
		      if (isset($passchange)) $output['passchange'] = strval($client['oId']);
		      if ($client['ODid'] === '1' && strval($client['eId']) === '6' && strval($client['uid']) === strval($client['oId']))
			 {
			  $output['customization'] = getUserCustomization($db, $client['uid']);
			  if (gettype($output['customization']) !== 'array')
			     {
			      if (gettype($output['customization']) === 'string') $output['alert'] = 'Customization JSON coding error: '. $output['customization'];
			      unset($output['customization']);
			     }
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
        case 'INIT':
	     $data = $client['data'];
	     foreach ($client['allelements'] as $eid => $profile)
		     {
		      $client['eId'] = $eid;
		      $client['data'] = isset($data[$eid]) ? $data[$eid] : '';
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
