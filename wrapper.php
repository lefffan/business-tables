<?php

// The script is called by next php functions for NEWOBJECT event and other events respectively:
// exec(PHPBINARY." wrapper.php $cid '$client[auth]' 0 0 0 NEWOBJECT '".json_encode($input['data'])."' '".json_encode([])."' &");
// exec(PHPBINARY." wrapper.php $cid '$client[auth]' $client[ODid] $oid $eid $input[cmd] '".json_encode($input['data'])."' '".json_encode($allElementsArray)."' &");

require_once 'core.php';

/*$cid		= $_SERVER['argv'][1];
$user		= $_SERVER['argv'][2];
$ODid		= $_SERVER['argv'][3];
$oid		= $_SERVER['argv'][4];
$eid		= $_SERVER['argv'][5];
$event		= $_SERVER['argv'][6];
$data		= json_decode($_SERVER['argv'][7], true);
$allElements	= json_decode($_SERVER['argv'][8], true);
$output = [];
//lg($_SERVER['argv']);
//exec("nohup /usr/local/apache2/htdocs/a.sh > /dev/null 2>&1 & echo $!", $output);

if ($cmdline = trim($allElements[$eid]['element4']['data']) === '') exit;*/
$json = '{"d":""}';
$cmdline = "hui-hui123456<data>'$json'";
$i = -1;
$qoute = $cmd = '';
$len = strlen($cmdline);

while (++$i < $len) switch ($cmdline[$i])
      {
       case "'":
    	    if (($j = strpos($cmdline, "'", $i + 1)) !== false && json_decode(substr($cmdline, $i + 1, $j - $i - 1), true))
	       {	
	        $i = $j;
		$cmd .= "'json'";
    		break;
	       }
	     else
	       {	
		$cmd .= $cmdline[$i];
    		break;
	       }
       case "<":
    	    if (($j = strpos($cmdline, '>', $i + 1)) !== false && (($match = substr($cmdline, $i + 1, $j - $i - 1)) === 'data' || $match === 'user' || $match === 'oid' || $match === 'title')) // Check for <data|user|oid|title> match
	       {	
	        $i = $j;
		$cmd .= 'huyax';
    		break;
	       }
       default:
	    $cmd .= $cmdline[$i];
      }

echo $cmd;    
echo "\n";


/*		  if (($handlerName = $allElementsArray[$eid]['element4']['data']) === '' || !($eventArray = parseJSONEventData($db, $allElementsArray[$eid]['element5']['data'], $input['cmd'], $eid))) break;
		  if (isset($data)) $eventArray['data'] = $data;
		  $output = [$eid => Handler($handlerName, json_encode($eventArray))];
		  switch ($output[$eid]['cmd']) // Process handler answer by the controller
			 {
			  case 'SET':
			  case 'RESET':
			       if (CreateNewObjectVersion($db)) break;
			       foreach ($output as $id => $value) if (!isset($props[$id])) unset($output[$id]);
			       isset($output[$eid]['alert']) ? $output = ['cmd' => 'SET', 'oId' => $oid, 'data' => $output, 'alert' => $output[$eid]['alert']] : $output = ['cmd' => 'SET', 'oId' => $oid, 'data' => $output];
			       $query = $db->prepare("SELECT id,version,owner,datetime,lastversion FROM `data_$odid` WHERE id=$oid AND lastversion=1 AND version!=0");
			       $query->execute();
			       foreach ($query->fetchAll(PDO::FETCH_ASSOC)[0] as $id => $value) $output['data'][$id] = $value;
			       break;
			  case 'EDIT':
			       isset($output[$eid]['data']) ? $output = ['cmd' => 'EDIT', 'data' => $output[$eid]['data'], 'oId' => $oid, 'eId' => $eid] : $output = ['cmd' => 'EDIT', 'oId' => $oid, 'eId' => $eid];
			       break;
			  case 'ALERT':
			       isset($output[$eid]['data']) ? $output = ['cmd' => 'INFO', 'alert' => $output[$eid]['data']] : $output = ['cmd' => 'INFO', 'alert' => ''];
			       break;
			  case 'DIALOG':
			       if (!isset($output[$eid]['data']) || !is_array($output[$eid]['data'])) break;
			       if (isset($output[$eid]['data']['flags']['cmd']) && $handlerName != 'customization.php') unset($output[$eid]['data']['flags']['cmd']);
			       $output = ['cmd' => 'DIALOG', 'data' => $output[$eid]['data']];
			       break;
			  case 'CALL':
			       if (!isset($output[$eid]['data']) || !is_array($output[$eid]['data']) || !isset($OD) || !isset($OV)) break;
			       $input = ['cmd' => 'GETMAIN', 'paramsOV' => []];
			       if (isset($output[$eid]['data']['Params'])) $input['paramsOV'] = $output[$eid]['data']['Params'];
			       if (!isset($output[$eid]['data']['OD'])) $input['OD'] = $OD; else $input['OD'] = $output[$eid]['data']['OD'];
			       if (!isset($output[$eid]['data']['OV'])) $input['OV'] = $OV; else $input['OV'] = $output[$eid]['data']['OV'];
			       $output = ['cmd' => 'CALL'];
			       if (!Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | CHECK_ACCESS)) getMainFieldData($db);
			       break;
			  default:
			       if ($cmd === 'CONFIRM') SetUndoOutput($db, $oid, $eid);
			 }
			 
			 
/*		// Context menu object delete event
		else if ($input['cmd'] === 'NEWOBJECT')
		   {
		    if (!Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | CHECK_ACCESS))
		       {
			$output = [];
			foreach ($allElementsArray as $id => $profile)
			if (($handlerName = $profile['element4']['data']) != '' && ($eventArray = parseJSONEventData($db, $profile['element5']['data'], $cmd, $id)))
		    	   {
			    $eventArray['data'] = isset($data[$id]) ? $data[$id] : '';
			    $output[$id] = Handler($handlerName, json_encode($eventArray));
			    if ($output[$id]['cmd'] != 'SET' && $output[$id]['cmd'] != 'RESET') unset($output[$id]);
			   }
		        InsertObject($db);
			getMainFieldData($db);
		       }
		   }
*/