<?php

require_once 'const.php';
require_once 'connect.php';

function rmSQLinjectionChars($str) // Function removes dangerous chars such as: ; ' " %
{
 return str_replace(';', '', str_replace('"', '', str_replace("'", '', str_replace("%", '', $str))));
}

function lg($arg, $title = 'LOG', $echo = false) // Function saves input $arg to error.log and echo it
{
 if ($echo && gettype($arg) === 'string')
    {
     echo "\n----------------------------".$title."-------------------------------\n";
     echo $arg;
    }
 file_put_contents(APPDIR.'error.log', "\n----------------------------".$title."-------------------------------\n", FILE_APPEND);
 file_put_contents(APPDIR.'error.log', var_export($arg, true), FILE_APPEND);
}

function adjustODProperties($db, $data, $ODid)
{
 global $newElement, $newView, $newRule;
 initNewODDialogElements();
 
 // Check some vars
 if (!isset($db, $ODid, $data['dialog']['Database']['Properties']['element1']['data'], $data['dialog']['Element']['New element'])) return NULL;

 // Element section handle
 if (!isset($data['dialog']['Element']['New element']['element1']['data'])) return NULL;
 $eidnew = strval($data['dialog']['Element']['New element']['element1']['id']);
 foreach ($data['dialog']['Element'] as $key => $value)
	 {
	  // Fetching element id
	  $eid = strval($value['element1']['id']);
	  // Remove element from db and dialog props in case of empty name, description and all hanlders
	  if ($value['element1']['data'] === '' && $value['element2']['data'] === '' && $value['element4']['data'] === '' && $value['element5']['data'] === '' && $value['element6']['data'] === '' && $value['element7']['data'] === '')
	     {
	      if ($key === 'New element')
	         {
		  unset($data['dialog']['Element'][$key]);
		  continue;
		 }
	      $db->beginTransaction();
	      $query = $db->prepare("ALTER TABLE `data_$ODid` DROP COLUMN eid$eid");
	      $query->execute();
	      if ($value['element3']['data'] === UNIQELEMENTTYPE)
		 {
		  $query = $db->prepare("ALTER TABLE `uniq_$ODid` DROP COLUMN eid$eid");
		  $query->execute();
		 }
	      unset($data['dialog']['Element'][$key]);		// Element name, description and handler file are empty? Remove element.
	      $db->commit();
	      continue;
	     }
	  // Limiting element title to ELEMENTDATAVALUEMAXCHAR as it is displayed as a regular element
	  $element = &$data['dialog']['Element'][$key];
	  if (strlen($element['element1']['data']) > ELEMENTDATAVALUEMAXCHAR) $element['element1']['data'] = substr($element['element1']['data'], 0, ELEMENTDATAVALUEMAXCHAR);
	  // Calculating current element profile name
	  $profile = trim($value['element1']['data']);
	  if (strlen($profile) > ELEMENTPROFILENAMEMAXCHAR) $profile = substr($profile, 0, ELEMENTPROFILENAMEMAXCHAR - 2).'..';
	  $profile .= ELEMENTPROFILENAMEADDSTRING.$eid.')';
	  // Processing new element
	  if ($key === 'New element')
	     {
	      $element['element3']['readonly'] = '';
	      $element['element3']['head'] .= ' (readonly)';
    	      $db->beginTransaction();
    	      $query = $db->prepare("ALTER TABLE `data_$ODid` ADD eid$eid JSON");
    	      $query->execute();
    	      if ($value['element3']['data'] === UNIQELEMENTTYPE)
    		 {
        	  $query = $db->prepare("ALTER TABLE `uniq_$ODid` ADD eid$eid BLOB(65535), ADD UNIQUE(eid$eid(".UNIQKEYCHARLENGTH."))");
		  $query->execute();
		 }
    	      $db->commit();
	      $data['dialog']['Element'][$key]['element1']['id'] = $eidnew;
	      $data['dialog']['Element'][$profile] = $data['dialog']['Element'][$key];
	      $eidnew = strval(intval($eidnew) + 1);
	      continue;
	     }
	  // Processing element new profile
	  if ($profile != $key)
	     {
	      $data['dialog']['Element'][$profile] = $data['dialog']['Element'][$key];
	      unset($data['dialog']['Element'][$key]);
	     }
	 }
 $data['dialog']['Element']['New element'] = $newElement; // Reset 'New element' profile to default
 $data['dialog']['Element']['New element']['element1']['id'] = $eidnew;
 
 // New view section handle
 if (!isset($data['dialog']['View']['New view']['element1']['data'])) return NULL;
 $vidnew = strval($data['dialog']['View']['New view']['element1']['id']);
 $viewpad = &$data['dialog']['View'];
 foreach ($viewpad as $key => $value) if (!isset($value['element1']['data']) || !isset($value['element2']['data']) || $value['element1']['data'] === '') unset($data['dialog']['View'][$key]); // Dialog 'View' profile corrupted or view name is empty? Remove it
 foreach ($viewpad as $key => $value) 
	 if (isset($viewpad[$value['element1']['data']]))
	    {
	     $viewpad[$key]['element1']['data'] = $key.''; // New view name already exists? Discard changes
	    }
	  else
	    {
	     $vidnew = strval(intval($vidnew) + 1);
	     $viewpad[$value['element1']['data']] = $viewpad[$key];	// Otherwise create new view with new view name
	     unset($viewpad[$key]);					// and remove old view
	    }
 $data['dialog']['View']['New view'] = $newView; // Reset 'New view' profile to default
 $data['dialog']['View']['New view']['element1']['id'] = $vidnew;

 // New rule section handle
 if (!isset($data['dialog']['Rule']['New rule']['element1']['data'])) return NULL;
 $rulepad = &$data['dialog']['Rule'];
 foreach ($rulepad as $key => $value) if (!isset($value['element1']['data']) || !isset($value['element2']['data']) || $value['element1']['data'] === '') unset($data['dialog']['Rule'][$key]); // Dialog 'Rule' profile corrupted or rule name is empty? Remove it
 foreach ($rulepad as $key => $value)
      if (isset($rulepad[$value['element1']['data']]))
         {
	  $rulepad[$key]['element1']['data'] = $key.''; // New rule name already exists? Discard changes
	 }
       else
	 {
	  $rulepad[$value['element1']['data']] = $rulepad[$key];	// Otherwise create new rule with new rule name
	  unset($rulepad[$key]);					// and remove old rule
	 }
 unset($data['dialog']['Rule']['New rule']);
 ksort($data['dialog']['Rule'], SORT_STRING);
 $data['dialog']['Rule']['New rule'] = $newRule; // Reset 'New rule' profile to default
 
 // Return result data
 $data['title'] = 'Edit Object Database Structure';
 $data['buttons'] = SAVECANCEL;
 $data['buttons']['SAVE']['call'] = 'Edit Database Structure';
 if (!isset($data['flags'])) $data['flags'] = [];
 return $data;
}					

function initNewODDialogElements()
{
 global $newProperties, $newPermissions, $newElement, $newView, $newRule;
 
 $newProperties  = ['element1' => ['type' => 'text', 'head' => 'Database name', 'data' => '', 'help' => "To remove database without recovery - set empty database name string and its description.<br>Remove all elements (see 'Element' tab) also."],
		    'element2' => ['type' => 'textarea', 'head' => 'Database description', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'text', 'head' => 'Database size limit in MBytes. Undefined or zero value - no limit.', 'data' => ''],
		    'element4' => ['type' => 'text', 'head' => 'Database object count limit. Undefined or zero value - no limit.', 'data' => '', 'line' => ''],
		    //'element5' => ['type' => 'text', 'head' => 'Max object versions in range 0-65535. Emtpy or undefined string - zero value', 'data' => '', 'line' => '', 'help' => 'Each object has some instances (versions) beginning with version number 1.<br>Once some object data has been changed, its version is incremented by one. <br>Max version value limits object max possible stored instances. Values description:<br>0 - no object data versions stored at all, only one (last) version<br>1 - only last version stored also, but deleted objects remain in database (marked by zero version)<br>2 - any object has two versions stored<br>3 - any object has three versions stored<br>4 - ...<br><br>Once database created, this value can be increased or redused. Reducing max version number<br>has two options - first or last versions of each object will be removed from the database.']
		    'element6' => ['type' => 'radio', 'data' => "User/groups allowed list to change 'Database' section|+Disallowed list (allowed for others)|"],
		    'element7' => ['type' => 'textarea', '_head' => "You must be aware of disallowing all users via empty user/group allowed list", 'data' => ''],
		    'element8' => ['type' => 'radio', 'data' => "User/groups list allowed to change 'Element' section|+Disallowed list (allowed for others)|"],
		    'element9' => ['type' => 'textarea', 'data' => ''],
		    'element10' => ['type' => 'radio', 'data' => "User/groups list allowed to change 'View' section|+Disallowed list (allowed for others)|"],
		    'element11' => ['type' => 'textarea', 'data' => ''],
		    'element12' => ['type' => 'radio', 'data' => "User/groups list allowed to change 'Rule' section|+Disallowed list (allowed for others)|"],
		    'element13' => ['type' => 'textarea', 'data' => '', 'line' => '']
		   ];

 $newElement	 = ['element1' => ['type' => 'textarea', 'head' => 'Element title to display in object view as a header', 'data' => '', 'id' => '1', 'help' => 'To remove object element - set empty element header, description and handler file'],
		    'element2' => ['type' => 'textarea', 'head' => 'Element description', 'data' => '', 'line' => '', 'help' => 'Specified description is displayed as a hint on object view element headers navigation.<br>It is used to describe element purpose and its possible values.'],
		    'element3' => ['type' => 'checkbox', 'head' => 'Element type', 'data' => 'unique|', 'line' => '', 'help' => "Unique element type guarantees element value uniqueness among all objects.<br>Element type cannot be changed after element creation."],
		    'element4' => ['type' => 'text', 'head' => "Handler command lines to process application events below", 'label' => "Handler for 'INIT' event:", 'data' => '', 'help' => 'hhhhhhh'],
		    'element5' => ['type' => 'text', 'label' => "Handler for 'DBLCLICK' event:", 'data' => ''],
		    'element6' => ['type' => 'text', 'label' => "Handler for 'KEYPRESS' event:", 'data' => ''],
		    'element7' => ['type' => 'text', 'label' => "Handler for 'INS' event:", 'data' => ''],
		    'element8' => ['type' => 'text', 'label' => "Handler for 'DEL' event:", 'data' => ''],
		    'element9' => ['type' => 'text', 'label' => "Handler for 'F2' event:", 'data' => ''],
		    'element10' => ['type' => 'text', 'label' => "Handler for 'F12' event:", 'data' => ''],
		    'element11' => ['type' => 'text', 'label' => "Handler for 'CONFIRM' event:", 'data' => ''],
		    'element12' => ['type' => 'text', 'label' => "Handler for 'CONFIRMDIALOG' event:", 'data' => ''],
		    'element13' => ['type' => 'text', 'label' => "Handler for 'CHANGE' event:", 'data' => '', 'line' => '']
		   ];
	
 $newView	 = ['element1' => ['type' => 'text', 'head' => 'Name', 'data' => '', 'id' => '1', 'help' => "View name can be changed, but if renamed view name already exists, changes won't be applied.<br>So view name 'New view' can't be set as it is used as an option to create new views.<br>Also symbol '_' as a first character in view name string keeps unnecessary views off sidebar,<br>so they can be called from element handler only.<br>To remove object view - set empty view name string."],
		    'element2' => ['type' => 'textarea', 'head' => 'Description', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'radio', 'head' => 'Template', 'data' => '+Table|Tree|Graph|Piechart|Map|', 'help' => "Select object view type from 'table' (displays objects in a form of a table),<br>'scheme' (displays object hierarchy built on uplink and downlink property),<br>'graph' (displays object graphic with one element on 'X' axis, other on 'Y'),<br>'piechart' (displays specified element value statistic on the piechart) and<br>'map' (displays objects on the geographic map)"],
		    'element4' => ['type' => 'textarea', 'head' => 'Object selection expression. Empty string selects all objects, error string - no objects.', 'data' => ''],
		    'element5' => ['type' => 'text', 'label' => 'Object selection link type', 'data' => '', 'line' => ''],
		    'element6' => ['type' => 'textarea', 'head' => 'Element layout. Defines what elements should be displayed and how.', 'data' => '', 'line' => ''],
		    'element7' => ['type' => 'textarea', 'head' => 'Scheduler', 'data' => '', 'line' => '', 'help' => "Each element scheduler string (one per line) executes its handler &lt;count> times starting at<br>specified date/time and represents itself one by one space separated args in next format:<br>&lt;minute> &lt;hour> &lt;mday> &lt;month> &lt;wday> &lt;event> &lt;event data> &lt;count><br>See crontab file *nix manual page for date/time args. Zero &lt;count> - infinite calls count.<br>Scheduled call emulates mouse/keyboard events (DBLCLICK and KEYPRESS) with specified<br>&lt;event data> (for KEYPRESS only) and passes 'system' user as an user initiated<br>specified event. Any undefined arg - no call."],
		    'element8' => ['type' => 'radio', 'data' => "User/groups list allowed to read this view|+Disallowed list (allowed for others)|"],
		    'element9' => ['type' => 'textarea', 'data' => ''],
		    'element10' => ['type' => 'radio', 'data' => "User/groups list allowed to change this view objects|+Disallowed list (allowed for others)|"],
		    'element11' => ['type' => 'textarea', 'data' => '']
		   ];
							  
 $newRule	 = ['element1' => ['type' => 'text', 'head' => 'Rule name', 'data' => '', 'help' => "Rule name is displayed as title on the dialog box.<br>Rule name can be changed, but if it already exists, changes won't be applied.<br>So rule name 'New rule' can't be set as it is used as a name for new rules creation.<br>To remove the rule - set rule name to empty string."],
		    'element2' => ['type' => 'textarea', 'head' => 'Rule message', 'data' => '', 'line' => '', 'help' => 'Rule message is match case log message displayed in dialog box.<br>Object element id in figure {#id} or square [#id] brackets retreives<br>appropriate element id value or element id title respectively.<br>Escape character is "\".'],
		    'element3' => ['type' => 'select-one', 'head' => 'Rule action', 'data' => '+Accept|Reject|', 'line' => '', 'help' => "'Accept' action apply object changes, 'Reject' cancels."],
		    'element4' => ['type' => 'checkbox', 'head' => 'Rule apply operation', 'data' => 'Add object|Delete object|Change object|', 'line' => ''],
		    'element5' => ['type' => 'textarea', 'head' => 'Preprocessing rule', 'data' => '', 'help' => 'Empty or error expression does nothing'],
		    'element6' => ['type' => 'textarea', 'head' => 'Postprocessing rule', 'data' => '', 'line' => '', 'help' => 'Empty or error expression does nothing'],
		    'element7' => ['type' => 'checkbox', 'data' => '+Log rule message|', 'line' => '', 'help' => '']
		   ];
}

function CalculateElementPropQuery($element, $prop = 'value')
{
 if (array_search($element, SERVICEELEMENTS) === false)
    {
     if (!isset($prop) || !$prop) $prop = 'value';
     $element = 'JSON_UNQUOTE(JSON_EXTRACT(eid'.strval($element).", '$.".$prop."'))";
    }
 return $element;
}

function getElementProp($db, $ODid, $oid, $eid, $prop, $version = NULL)
{
 if (!isset($ODid) || !isset($oid) || !isset($eid)) return NULL;

 $eid = CalculateElementPropQuery($eid, $prop);
 isset($version) ? $version = "version='".strval($version)."'" : $version = 'lastversion=1 AND version!=0';

 $query = $db->prepare("SELECT $eid FROM `data_$ODid` WHERE id=$oid AND $version");
 $query->execute();

 $result = $query->fetchAll(PDO::FETCH_NUM);
 if (!isset($result[0][0])) return NULL;
 $result = $result[0][0];
 $result = str_replace("\\n", "\n", $result);
 $result = str_replace('\\"', '"', $result);
 $result = str_replace('\\/', '/', $result);
 return str_replace("\\\\", "\\", $result);
}

function getElementArray($db, $ODid, $oid, $eid, $version = NULL)
{
 return json_decode(getElementJSON($db, $ODid, $oid, $eid, $version), true);
}

function getElementJSON($db, $ODid, $oid, $eid, $version = NULL)
{
 if (isset($version)) $query = $db->prepare("SELECT eid".strval($eid)." FROM `data_$ODid` WHERE id=$oid AND version='".strval($version)."'");
  else $query = $db->prepare("SELECT eid".strval($eid)." FROM `data_$ODid` WHERE id=$oid AND lastversion=1 AND version!=0");
 $query->execute();
 $result = $query->fetchAll(PDO::FETCH_NUM);

 if (isset($result[0][0])) return $result[0][0];
 return NULL;
}

function AddObject($db, &$client, &$output)
{
 $query = $values = '';
 $params = [];
 
 // Prepare uniq elements query
 foreach ($client['uniqelements'] as $eid => $value)
	 {
	  $query .= ",eid$eid";
	  $values .= ",:eid$eid";
	  isset($output[$eid]['value']) ? $params[":eid$eid"] = $output[$eid]['value'] : $params[":eid$eid"] = '';
	 }
 if ($query != '') { $query = substr($query, 1); $values = substr($values, 1); }

 try {
      // Start transaction, insert uniq elements, calculate inserted object id and insert actual object to data_<ODid> sql table
      $db->beginTransaction();
      $query = $db->prepare("INSERT INTO `uniq_$client[ODid]` ($query) VALUES ($values)");
      $query->execute($params);
 
      // Get last inserted object id
      $query = $db->prepare("SELECT LAST_INSERT_ID()");
      $query->execute();
      $newId = $query->fetchAll(PDO::FETCH_NUM)[0][0];
 
      // Prepare actual elements query
      $query  = "id,version,owner";
      $params = [':id' => $newId, ':version' => '1', ':owner' => $client['auth']];
      $values = ':id,:version,:owner';
      foreach ($client['allelements'] as $eid => $profile) if (isset($output[$eid]) && ($json = json_encode($output[$eid])) !== false)
	      {
	       $query .= ',eid'.strval($eid);
	       $params[':eid'.strval($eid)] = $json;
	       $values .= ",:eid".strval($eid);
	      }
      $query = $db->prepare("INSERT INTO `data_$client[ODid]` ($query) VALUES ($values)");
      $query->execute($params);
    
      $client['oId'] = $newId;
      $ruleresult = ProcessRules($db, $client, NULL, '1', 'Add object');
      if ($ruleresult['action'] === 'Accept')
         {
          $db->commit();
	  if (isset($ruleresult['log'])) LogMessage($db, $client, $ruleresult['log']);
	  $output = ['cmd' => 'INIT'];
	  if (isset($ruleresult['message']) && $ruleresult['message']) $output['alert'] = $ruleresult['message'];
	  return;
	 }
     }
 catch (PDOException $e)
     {
      preg_match("/Duplicate entry/", $msg = $e->getMessage()) === 1 ? $ruleresult = ['message' => 'Failed to add new object: unique elements duplicate entry!'] : $ruleresult = ['message' => "Failed to add new object: $msg"];
      $ruleresult['log'] = $ruleresult['message'];
     }

 $db->rollBack();
 if (isset($ruleresult['log'])) LogMessage($db, $client, $ruleresult['log']);
 $output = ['cmd' => '', 'alert' => $ruleresult['message']];
}

function DeleteObject($db, &$client, &$output)
{
 try {
      $db->beginTransaction();
      $query = $db->prepare("SELECT version FROM `data_$client[ODid]` WHERE id=$client[oId] AND lastversion=1 AND version!=0 FOR UPDATE");
      $query->execute();
      $version = $query->fetchAll(PDO::FETCH_NUM);
      if (!isset($version[0][0])) { $db->rollBack(); return []; }
      $version = $version[0][0];
      
      $query = $db->prepare("UPDATE `data_$client[ODid]` SET lastversion=0 WHERE id=$client[oId] AND lastversion=1");
      $query->execute();
      $query = $db->prepare("INSERT INTO `data_$client[ODid]` (id,version,lastversion,owner) VALUES ($client[oId],0,1,:owner)");
      $query->execute([':owner' => $client['auth']]);
      $query = $db->prepare("DELETE FROM `uniq_$client[ODid]` WHERE id=$client[oId]");
      $query->execute();
      
      $ruleresult = ProcessRules($db, $client, $version, NULL, 'Delete object');
      if ($ruleresult['action'] === 'Accept')
         {
	  $db->commit();
	  if (isset($ruleresult['log'])) LogMessage($db, $client, $ruleresult['log']);
	  $output = ['cmd' => 'DELETEOBJECT'];
	  if (isset($ruleresult['message']) && $ruleresult['message']) $output['alert'] = $ruleresult['message'];
	  if ($client['ODid'] === '1') $output['passchange'] = strval($client['oId']);
	  return;
	 }
     }
 catch (PDOException $e)
     {
      $ruleresult = ['message' => 'Failed to delete object: '.$e->getMessage()];
      $ruleresult['log'] = $ruleresult['message'];
     }

 $db->rollBack();
 if (isset($ruleresult['log'])) LogMessage($db, $client, $ruleresult['log']);
 $output = ['cmd' => '', 'alert' => $ruleresult['message']];
}

function ProcessRules($db, &$client, $preversion, $postversion, $operation)
{
 // Get rule section json data
 $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.Rule') FROM $ WHERE id='$client[ODid]'");
 $query->execute();
 $Rules = $query->fetchAll(PDO::FETCH_NUM);
 
 // Move on. Return default action in case of empty rule selection or decoding error
 if (!isset($Rules[0][0]) || gettype($Rules = json_decode($Rules[0][0], true)) != 'array') return ['action' => 'Accept', 'message' => ''];
 unset($Rules['New rule']);
 
 // Process non empty expression rules one by one
 foreach ($Rules as $key => $value)
	 {
	  if (strpos($value['element4']['data'], '+'.$operation) === false) continue;
	  strpos($value['element3']['data'], '+Accept') === false ? $action = 'Reject' : $action = 'Accept';
	  $message = trim($value['element2']['data']);

	  if (gettype($result = CheckRule($db, $client, trim($value['element5']['data']), $preversion)) === 'string') return ['action' => $action, 'message' => $result, 'log' => $result];
	  if ($result === false) continue;

	  if (gettype($result = CheckRule($db, $client, trim($value['element6']['data']), $postversion)) === 'string') return ['action' => $action, 'message' => $result, 'log' => $result];
	  if ($result === false) continue;
	  
	  // Rule match occured. Return its action
	  $output = ['action' => $action, 'message' => $message];
	  if (substr($value['element7']['data'], 0, 1) === '+') $output['log'] = "Database rule '$key' match, action: '$action', message: '$message'"; // Log rule message in case of approprate checkbox is set
	  return $output;
	 }

 // Return default action
 return ['action' => 'Accept', 'message' => ''];
}

function CheckRule($db, &$client, $rule, $version)
{
 if (!isset($rule, $version) || $rule === '' || $version === '') return true;

 try {
      $query = $db->prepare("SELECT id FROM `data_$client[ODid]` WHERE id=$client[oId] AND version=$version AND $rule");
      $query->execute();
     }
 catch (PDOException $e)      
     {
      return 'Rule error: '.$e->getMessage();
     }
     
 if (isset($query->fetchAll(PDO::FETCH_NUM)[0][0])) return true;
 return false;
}

function setElementSelectionIds(&$client)
{
 $props = [];

 //  ------ --------------------------------- ----------------------------------- ---------------------------------------
 // |  \eid|                0                |              1..         	 | id,version,owner,datetime,lastversion |
 // |oid\  |                                 | 					 |					 |
 //  ------ --------------------------------- ----------------------------------- ---------------------------------------
 // |  0   | undefined cell: style, collapse | object in selection:     	 | object service info:			 |
 // |      | html table:     tablestyle      | x, y, style, collapse    	 | x, y, style				 |
 //  ------ --------------------------------- ----------------------------------- ---------------------------------------
 // |  1   | new object:     style     	     | new object:			 |		   -			 |
 // |      | 				     | x, y, style, event, hint*	 |					 |
 //  ------ --------------------------------- ----------------------------------- ---------------------------------------
 // |  2   | title object:   style           | title object:			 |		   -			 |
 // |      | 				     | x, y, style, title*, hint*	 |					 |
 //  ------ --------------------------------- ----------------------------------- ---------------------------------------
 // | 3..  | exact object:   style     	     | exact object: 			 | object service info:			 |
 // |      | 				     | x, y, style, event, collapse	 | x, y, style				 |
 //  ------ --------------------------------- ----------------------------------- ---------------------------------------
 // *props set automatically
 
 foreach (preg_split("/\n/", $client['elementselection']) as $value)
      if ($arr = json_decode($value, true, 2))
	 {
	  cutKeys($arr, ['eid', 'oid', 'x', 'y', 'style', 'collapse', 'event', 'tablestyle']); // Retrieve correct values only
	  if (!key_exists('eid', $arr)) $arr['eid'] = '0'; // Set 'eid' key default value to zero
	  if (!key_exists('oid', $arr)) $arr['oid'] = '0'; // Set 'oid' key default value to zero

	  if (gettype($arr['eid']) != 'string' || gettype($arr['oid']) != 'string') continue; // JSON eid/oid properties are not strings? Continue
	  if ($arr['eid'] != 'id' && $arr['eid'] != 'version' && $arr['eid'] != 'owner' && $arr['eid'] != 'datetime' && $arr['eid'] != 'lastversion')
	  if (!ctype_digit($arr['eid']) || !ctype_digit($arr['oid'])) continue; // JSON eid/oid properties are not numerical and not one of 'id', 'version', 'owner', 'datetime' or 'lastversion'? Continue
	  
	  $eid = $arr['eid'];	// Creating aliases
	  $oid = $arr['oid'];	// Creating aliases
	  if (!isset($props[$eid])) $props[$eid] = [];		// Result array $props has 'eid' element undefined? Create it
	  if (!isset($props[$eid][$oid])) $props[$eid][$oid] = [];	// Result array $props has 'oid' of 'eid' element undefined? Create it
	  
	  switch ($eid)
		 {
		  case '0': // Parse zero element that defines styles for new, title, selection and exact objects
		       if ($oid == '0')
		          {
			   if (key_exists('collapse', $arr)) $props[$eid][$oid]['collapse'] = '';
			   if (key_exists('tablestyle', $arr)) $props[$eid][$oid]['tablestyle'] = $arr['tablestyle'];
			  }
		       if (key_exists('style', $arr)) $props[$eid][$oid]['style'] = $arr['style'];
		       break;
		  case 'id': // Parse service elements that defines styles and x-y coordinates for selection and exact objects
		  case 'version':
		  case 'owner':
		  case 'datetime':
		  case 'lastversion':
		       if ((intval($oid) == 0 || intval($oid) == TITLEOBJECTID || intval($oid) == NEWOBJECTID || intval($oid) >= STARTOBJECTID) && gettype($arr['x']) === 'string' && gettype($arr['y']) === 'string')
		          {
			   $props[$eid][$oid] = ['x' => $arr['x'], 'y' => $arr['y']];
			   if (key_exists('style', $arr)) $props[$eid][$oid]['style'] = $arr['style'];
			   if (intval($oid) == TITLEOBJECTID) switch ($eid)
			      {
			       case 'id':
			    	    $props[$eid][$oid]['title'] = 'Id';
				    $props[$eid][$oid]['hint'] = 'Object identificator';
				    break;
			       case 'version':
			    	    $props[$eid][$oid]['title'] = 'Version';
				    $props[$eid][$oid]['hint'] = 'Object version number';
				    break;
			       case 'owner':
			    	    $props[$eid][$oid]['title'] = 'Owner';
				    $props[$eid][$oid]['hint'] = 'User created object version';
				    break;
			       case 'datetime':
			    	    $props[$eid][$oid]['title'] = 'Date and time';
				    $props[$eid][$oid]['hint'] = 'Date and time object version was created';
				    break;
			       case 'lastversion':
			    	    $props[$eid][$oid]['title'] = 'Last version';
				    $props[$eid][$oid]['hint'] = 'Last version flag means actual object version';
				    break;
			      }
			  }
		       break;
		  default: // Parse all other numeric elements that defines styles, x-y coordinates, 'collapse' capability and 'event' event for new, title, selection and exact objects
		       if (!key_exists($eid, $client['allelements'])) break;
		       if (key_exists('event', $arr)) $props[$eid][$oid]['event'] = $arr['event'];
		       if (isset($arr['x'], $arr['y']) && gettype($arr['x']) === 'string' && gettype($arr['y']) === 'string')
		          {
			   $props[$eid][$oid]['x'] = $arr['x'];
			   $props[$eid][$oid]['y'] = $arr['y'];
			   if (key_exists('collapse', $arr)) $props[$eid][$oid]['collapse'] = '';
			   if (key_exists('style', $arr)) $props[$eid][$oid]['style'] = $arr['style'];
			   if (intval($oid) == TITLEOBJECTID)
			      {
			       $props[$eid][$oid]['title'] = $client['allelements'][$eid]['element1']['data'];
			       $props[$eid][$oid]['hint'] = $client['allelements'][$eid]['element2']['data'];
			      }
			   if (intval($oid) == NEWOBJECTID) $props[$eid][$oid]['hint'] = "Table cell to input new object text for element id: $eid";
			  }
		 }
	 }
	 
 return $props;
}

function getUserId($db, $user)
{
 if (gettype($user) != 'string' || $user === '') return;
 $query = $db->prepare("SELECT id FROM `uniq_1` WHERE eid1=:user");
 $query->execute([':user' => $user]);
 $id = $query->fetchAll(PDO::FETCH_NUM);
 if (isset($id[0][0])) return $id[0][0];
}

function getUserPass($db, $id)
{
 $pass = getElementProp($db, '1', $id, '1', 'password');
 if (!isset($pass) || gettype($pass) != 'string') return '';
 return $pass;
}

function getUserName($db, $id)
{
 $name = getElementProp($db, '1', $id, '1', 'value');
 if (!isset($name) || gettype($name) != 'string') return '';
 return $name;
}

function getUserGroups($db, $id)
{
 $query = $db->prepare("SELECT JSON_EXTRACT(eid1, '$.groups') FROM `data_1` WHERE id=:id AND lastversion=1 AND version!=0");
 $query->execute([':id' => $id]);
 $groups = $query->fetchAll(PDO::FETCH_NUM);
 if (!isset($groups[0][0])) return [];
 return UnsetEmptyArrayElements(explode("\\n", substr($groups[0][0], 1, -1)));
}

function UnsetEmptyArrayElements($arr)
{
 if (!is_array($arr)) return [];
 foreach ($arr as $key => $value)
	 if ($value === '' || gettype($value) != 'string') unset($arr[$key]);
 return $arr;
}

function getUserODAddPermission($db, $id)
{
 $query = $db->prepare("SELECT JSON_EXTRACT(eid1, '$.odaddperm') FROM `data_1` WHERE id=:id AND lastversion=1 AND version!=0");
 $query->execute([':id' => $id]);
 $odaddperm = $query->fetchAll(PDO::FETCH_NUM);
 if (isset($odaddperm[0][0])) return substr($odaddperm[0][0], 1, -1);
  else return '';
}

function getUserCustomization($db, $uid)
{
 $customization = json_decode(getElementProp($db, '1', $uid, '6', 'dialog'), true); // Get current user JSON customization and decode it

 // If current user customization forces to use another user customization, and the user doesn't point to itself and does exist - get it
 if (($forceuser = $customization['pad']['application']['element3']['data']) != '' && $forceuser != 'system' && ($forceuser = getUserId($db, $forceuser)))
 if (isset($forceuser) && $uid != $forceuser)
    {
     $forceuser = json_decode(getElementProp($db, '1', $forceuser, '6', 'dialog'), true);
     if (isset($forceuser)) return $forceuser;
    }

 return $customization;
}

function getLoginDialogData($title = NULL)
{
 if (!$title) $title = "\nUsername";
 return [
	 'title'   => 'Login',
	 'dialog'  => ['pad' => ['profile' => ['element1' => ['head' => $title, 'type' => 'text'], 'element2' => ['head' => 'Password', 'type' => 'password']]]],
	 'buttons' => ['LOGIN' => ['value' => 'LOGIN', 'call' => 'LOGIN', 'enterkey' => '']],
	 'flags'   => ['style' => 'min-width: 350px; min-height: 140px; max-width: 1500px; max-height: 500px;']
	];
}

function LogMessage($db, &$client, $log)
{
 $msg = '';
 if (isset($client['auth']) && $client['auth']) $msg .= "USER: '$client[auth]', ";
 if (isset($client['OD']) && $client['OD']) $msg .= "OD: '$client[OD]', OV: '$client[OV]', ";
 if (isset($client['oId']) && $client['oId']) $msg .= "OBJECT ID: '$client[oId]', ";
 if (isset($client['eId']) && $client['eId']) $msg .= "ELEMENT ID: '$client[eId]', ";

 if ($msg != '') $msg = '[ '.substr($msg, 0, -2).' ] ';
 lg($msg .= $log);
							                                                                                                           
 $_client = ['ODid' => '2', 'OVid' => '1', 'OD' => 'Logs', 'OV' => 'All logs', 'allelements' => ['1' => ''], 'uniqelements' => [], 'params' => []];
 isset($client['auth']) ? $_client['auth'] = $client['auth'] : $_client['auth'] = 'system';
 $output = ['1' => ['cmd' => 'RESET', 'value' => $msg] + DEFAULTELEMENTPROPS];

 AddObject($db, $_client, $output);
 $query = $db->prepare("INSERT INTO `$$` (client) VALUES (:client)");
 $query->execute([':client' => json_encode($output + $_client, JSON_HEX_APOS | JSON_HEX_QUOT)]);
}

function encode($payload, $type = 'text', $masked = false)
{
 $frameHead = array();
 $payloadLength = strlen($payload);
     
 switch ($type)
    	{
	 case 'text':
	      $frameHead[0] = 129; // first byte indicates FIN, Text-Frame (10000001)
	      break;
	 case 'close':
	      $frameHead[0] = 136; // first byte indicates FIN, Close Frame(10001000)
		  break;
	     case 'ping':
		  $frameHead[0] = 137; // first byte indicates FIN, Ping frame (10001001)
		  break;
	     case 'pong':
		  $frameHead[0] = 138; // first byte indicates FIN, Pong frame (10001010)
		  break;
	    }
	    
     if ($payloadLength > 65535) // set mask and payload length (using 1, 3 or 9 bytes)
        {
	 $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
	 $frameHead[1] = ($masked === true) ? 255 : 127;
	 for ($i = 0; $i < 8; $i++) $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
	 if ($frameHead[2] > 127) return array('type' => '', 'payload' => '', 'error' => 'frame too large (1004)'); // most significant bit MUST be 0
	}
      elseif ($payloadLength > 125)
	{
	 $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
	 $frameHead[1] = ($masked === true) ? 254 : 126;
	 $frameHead[2] = bindec($payloadLengthBin[0]);
	 $frameHead[3] = bindec($payloadLengthBin[1]);
	}
      else $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
      
     foreach (array_keys($frameHead) as $i) $frameHead[$i] = chr($frameHead[$i]); // Convert frame-head to string
     
     if ($masked === true) // generate a random mask:
        {
	 $mask = [];
	 for ($i = 0; $i < 4; $i++) $mask[$i] = chr(rand(0, 255));
	 $frameHead = array_merge($frameHead, $mask);
	}
     $frame = implode('', $frameHead);
     
     for ($i = 0; $i < $payloadLength; $i++) $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i]; // Append payload to frame
     
 return $frame;
}
    
function decode($data)
{
     if (!strlen($data))
        {
	 lg('Socket data zero length!');
	 return false;
	}
     
     $unmaskedPayload = '';
     $decodedData = array();
     
     // Estimate frame type:
     $firstByteBinary = sprintf('%08b', ord($data[0]));
     $secondByteBinary = sprintf('%08b', ord($data[1]));
     $opcode = bindec(substr($firstByteBinary, 4, 4));
     $isMasked = ($secondByteBinary[0] == '1') ? true : false;
     $payloadLength = ord($data[1]) & 127;
     
     if (!$isMasked)
        {
	 lg('Unmasked frame is received!');
	 //return false;
	 return array('type' => '', 'payload' => '', 'error' => 'protocol error (1002)'); // Unmasked frame is received
	}
     
     switch ($opcode)
    	    {
	     case 1:
	          $decodedData['type'] = 'text'; // Text frame
		  break;
	     case 2:
	          $decodedData['type'] = 'binary'; // Binary frame
		  break;
	     case 8:
	          $decodedData['type'] = 'close'; // Connection close frame
		  break;
	     case 9:
	          $decodedData['type'] = 'ping'; // Ping frame
		  break;
	     case 10:
	          $decodedData['type'] = 'pong'; // Pong frame
		  break;
	     default:
		  lg('Socket data unknown opcode (1003)!');
		  //return false;
	          return array('type' => '', 'payload' => '', 'error' => 'unknown opcode (1003)');
	    }
     if ($payloadLength === 126)
        {
	 $mask = substr($data, 4, 4);
	 $payloadOffset = 8;
	 $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
	}
      elseif ($payloadLength === 127)
        {
	 $mask = substr($data, 10, 4);
	 $payloadOffset = 14;
	 $tmp = '';
	 for ($i = 0; $i < 8; $i++) $tmp .= sprintf('%08b', ord($data[$i + 2]));
	 $dataLength = bindec($tmp) + $payloadOffset;
	 unset($tmp);
	}
      else
        {
	 $mask = substr($data, 2, 4);
	 $payloadOffset = 6;
	 $dataLength = $payloadLength + $payloadOffset;
	}

     // We have to check for large frames here - socket_recv cuts at 1024 bytes so if websocket-frame is > 1024 bytes, then we have to wait until whole data is transferd
     if (strlen($data) < $dataLength)
        {
	 lg('Socket data is more than 1024 bytes, so wait until whole data is transferd, input data is '.strval(strlen($data)).'bytes, actual data is '.strval($dataLength).'bytes');
	 return false;
	}

     if ($isMasked)
        {
	 for ($i = $payloadOffset; $i < $dataLength; $i++)
	     {
	      $j = $i - $payloadOffset;
	      if (isset($data[$i])) $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
	     }
	 $decodedData['payload'] = $unmaskedPayload;
	}
      else
        {
	 $payloadOffset = $payloadOffset - 4;
	 $decodedData['payload'] = substr($data, $payloadOffset);
	}
     
     return $decodedData;
}

function handshake($connect) {
	$info = array();

	$line = fgets($connect);
	$header = explode(' ', $line);
	$info['method'] = $header[0];
	$info['uri'] = $header[1];

	while ($line = rtrim(fgets($connect))) {
	    if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
		$info[$matches[1]] = $matches[2];
	    } else {
		break;
	    }
	}

	$address = explode(':', stream_socket_get_name($connect, true)); //╨┐╨╛╨╗╤Г╤З╨░╨╡╨╝ ╨░╨┤╤А╨╡╤Б ╨║╨╗╨╕╨╡╨╜╤В╨░
	$info['ip'] = $address[0];
	$info['port'] = $address[1];

	if (empty($info['Sec-WebSocket-Key'])) return false;

	$SecWebSocketAccept = base64_encode(pack('H*', sha1($info['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	$upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	    "Upgrade: websocket\r\n" .
	    "Connection: Upgrade\r\n" .
	    "Sec-WebSocket-Accept:".$SecWebSocketAccept."\r\n\r\n";
	fwrite($connect, $upgrade);

	return $info;
    }

function handshake1($socket)
{
     $info = [];
     $data = socket_read($socket, 1000);
     $lines = explode("\r\n", $data);
     
     //lg($lines); // 7 => 'Origin: http://192.168.9.39' - so check origin header to be http or https to 192.168.9.39
     foreach ($lines as $i => $line)
    	     {
	      if ($i)
	         {
		  if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) $info[$matches[1]] = $matches[2];
		 }
	       else
	         {
		  $header = explode(' ', $line);
		  $info['method'] = $header[0];
		  $info['uri'] = $header[1];
		 }
	      if (empty(trim($line))) break;
	     }
	     
     $ip = $port = null;
     if (!socket_getpeername($socket, $ip, $port)) return false;
     $info['ip'] = $ip;
     $info['port'] = $port;
     if (empty($info['Sec-WebSocket-Key'])) return false;

     $SecWebSocketAccept = base64_encode(pack('H*', sha1($info['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
     $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
                "Upgrade: websocket\r\n" .
		"Connection: Upgrade\r\n" .
		"Sec-WebSocket-Accept:".$SecWebSocketAccept."\r\n\r\n";
     socket_write($socket, $upgrade);
     return $info;
}

function GenerateRandomString($length = USERPASSMINLENGTH)
{
 $len = strlen($permittedchars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
 $randomstring = '';
 
 for ($i = 0; $i < $length; $i++) $randomstring .= $permittedchars[mt_rand(0, $len - 1)];
 
 return $randomstring;
}

function GetObjectSelection($db, $objectSelection, $params, $user)
{
 // Check input paramValues array and add reserved :user parameter value
 if (gettype($objectSelection) != 'string' || ($objectSelection = trim($objectSelection)) === '') return DEFAULTOBJECTSELECTION;
 $i = -1;
 $len = strlen($objectSelection);
 if (gettype($params) != 'array') $params = [];
 $params[':user'] = $user;
 $isDialog = false;
 $objectSelectionNew = '';
 
 // Check $objectSelection every char and retrieve params in non-quoted substrings started with ':' and finished with space or another ':'
 while  (++$i <= $len)
     if ($i === $len || $objectSelection[$i] === '"' || $objectSelection[$i] === "'" || $objectSelection[$i] === ':' || $objectSelection[$i] === ' ')
	{
	 if (isset($newparam))
	 if (isset($params[$newparam]))
	    {
	     $objectSelectionParamsDialogProfiles[$newparam] = ['head' => "\n".str_replace('_', ' ', substr($newparam, 1)).':', 'type' => 'text', 'data' => $params[$newparam]];
	     if (!$isDialog) $objectSelectionNew .= $params[$newparam];
	    }
	  else
	    {
	     $objectSelectionParamsDialogProfiles[$newparam] = ['head' => "\n".str_replace('_', ' ', substr($newparam, 1)).':', 'type' => 'text', 'data' => ''];
	     $isDialog = true;
	    }
	 if ($i === $len) break;
	 $newparam = NULL;
	 if ($objectSelection[$i] === ':') $newparam = ':';
	  else $objectSelectionNew .= $objectSelection[$i];
	}
      else if (isset($newparam)) $newparam .= $objectSelection[$i];
      else $objectSelectionNew .= $objectSelection[$i];

 //  In case of no dialog - return object selection string
 unset($params[':user']); // Is it needable?
 if (!$isDialog) return $objectSelectionNew;
 
 // Otherwise return dialog array
 $buttons = OKCANCEL;
 $buttons['OK']['call'] = 'CALL';
 $buttons['CANCEL']['error'] = 'View output has been canceled';
 return [
	 'title'   => 'Object View parameters',
	 'dialog'  => ['pad' => ['profile' => $objectSelectionParamsDialogProfiles]],
	 'buttons' => $buttons,
	 'flags'   => ['style' => 'min-width: 350px; min-height: 140px; max-width: 1500px; max-height: 500px;', 'esc' => '']
	];
}

function Swap(&$a, &$b)
{
 $swap = $a;
 $a = $b;
 $b = $swap;
}

function Sidebar($db, &$client)
{
 $groups = getUserGroups($db, $client['uid']);	// Get current user group list
 $groups[] = getUserName($db, $client['uid']);	// and add username at the end of array

 $sidebar = [];
 $query = $db->prepare("SELECT id,odname FROM `$`");
 $query->execute();
 foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $value)
	 {
	  $name = $value['odname'];
	  $id = $value['id'];
	  $sidebar[$id] = ['name' => $name, 'view' => []];
	  
	  $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE odname='$name'");
	  $query->execute();
	  foreach (json_decode($query->fetch(PDO::FETCH_NUM)[0], true) as $key => $View) if ($key != 'New view')
		  {
		   $count = count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $View['element9']['data'])), "strcmp"));
		   $pos = strpos($View['element8']['data'], '+');
		   if (($count && $pos) || (!$count && !$pos)) continue;
		   $sidebar[$id]['view'][$View['element1']['id']] = $View['element1']['data'];
		   if ($id === $client['ODid'] && $View['element1']['id'] === $client['OVid']) $sidebar[$id]['active'] = $client['OVid'];
		  }
	 }
 
 return $sidebar;
}

function Check($db, $flags, &$client, &$output)
{
 if ($flags & CHECK_OD_OV)
    {
     // Copy input OD/OV ids and names if exist
     $output['sidebar'] = Sidebar($db, $client);
     
     if (count($output['sidebar']) == 0)
        {
	 $output['error'] = 'Please create Object Database first!';
	 return;
	}
     if ($client['ODid'] === '')
        {
	 $output['error'] = 'Please create/select Object View!';
	 return;
	}
     if (!isset($output['sidebar'][$client['ODid']]['view'][$client['OVid']]))
        {
	 $output['error'] = "Database '$client[OD]' or its View '$client[OV]' not found!";
	 return;
	}
     
     $client['OD'] = $output['sidebar'][$client['ODid']]['name'];
     $client['OV'] = $output['sidebar'][$client['ODid']]['view'][$client['OVid']];
    }

 if ($flags & GET_ELEMENTS)
    {
     $client['allelements'] = $client['uniqelements'] = [];
     // Get element section
     $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.Element') FROM $ WHERE id='$client[ODid]'");
     $query->execute();
     if (!count($elements = $query->fetchAll(PDO::FETCH_NUM)))
        { 
	 $output['error'] = "Object View '$client[OV]' of Database '$client[OD]' not found!";
	 return;
	}

     // Convert profiles assoc array to num array with element identificators as array elements instead of profile names and sort it
     foreach (json_decode($elements[0][0], true) as $key => $value) if ($key != 'New element')
    	     {
	      $id = intval($value['element1']['id']); // Calculate current element id
	      $client['allelements'][$id] = $value;
	      if ($value['element3']['data'] === UNIQELEMENTTYPE) $client['uniqelements'][$id] = '';
	     }
     if (!count($client['allelements']))
        {
	 $output['error'] = "Database '$client[OD]' has no elements exist!";
	 return;
	}
     ksort($client['allelements'], SORT_NUMERIC);
    }
    
 if ($flags & GET_VIEWS)
    {
     // Get view section
     unset($client['objectselection'], $client['elementselection'], $client['viewtype'], $client['linktype']);
     
     $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE id='$client[ODid]'");
     $query->execute();
     foreach (json_decode($query->fetchAll(PDO::FETCH_NUM)[0][0], true) as $value)
	  if ($value['element1']['id'] === $client['OVid'])
	     {
	      $client['viewtype'] = substr($value['element3']['data'], ($pos = strpos($value['element3']['data'], '+')) + 1, strpos($value['element3']['data'], '|', $pos) - $pos -1);
	      $client['objectselection'] = trim($value['element4']['data']);
	      $client['linktype'] = $value['element5']['data'];
	      $client['elementselection'] = trim($value['element6']['data']);
	      break;
	     }
     if (!isset($client['elementselection'], $client['objectselection'], $client['viewtype']))
        {
	 $output['error'] = "Object View '$client[OV]' of Database '$client[OD]' not found!";
	 return;
	}

     // List is empty or includes '*' chars for a 'Table' view? Set up default list for all elements: {"eid": "every", "oid": "title|0|newobj", "x": "0..", "y": "0|n"}
     if ($client['viewtype'] === 'Table')
     if ($client['elementselection'] === '' || $client['elementselection'] === '*' || $client['elementselection'] === '**' || $client['elementselection'] === '***')
        {
         $x = 0;
	 $startline = 'n+1';
	 if ($client['elementselection'] === '*' || $client['elementselection'] === '***') $startline = 'n+2';
	 $arr = $client['allelements'];
	 if ($client['elementselection'] === '**' || $client['elementselection'] === '***') $arr = ['id' => '', 'version' => '', 'owner' => '', 'datetime' => ''] + $arr;
	 $client['elementselection'] = '';
         foreach ($arr as $id => $value)
    	         {
	          $client['elementselection'] .= '{"eid": "'.$id.'", "oid": "'.strval(TITLEOBJECTID).'", "x": "'.strval($x).'", "y": "0"}'."\n";
	          if ($startline === 'n+2') $client['elementselection'] .= '{"eid": "'.$id.'", "oid": "'.strval(NEWOBJECTID).'", "x": "'.strval($x).'", "y": "1"}'."\n";
		  $client['elementselection'] .= '{"eid": "'.$id.'", "oid": "0", "x": "'.strval($x).'", "y": "'.$startline.'"}'."\n";
	          $x++;
	    	 }
	}

     // List is empty for a 'Tree' view? Set up default list for all elements appearance: {'title1': '', 'value1': '', 'title2': ''..} 
     if ($client['viewtype'] === 'Tree')
     if ($client['elementselection'] === '')
        {
	 $client['elementselection'] = ['id' => '', 'datetime' => ''];
	 foreach ($client['allelements'] as $id => $value) $client['elementselection'][$id] = '';
	}
      else
        {
	 $arr = [];
	 foreach (preg_split("/\n/", $client['elementselection']) as $value)
		 if (gettype($arr = json_decode($value, true, 2)) === 'array') break;
	 gettype($arr) === 'array' ? $client['elementselection'] = $arr : $client['elementselection'] = [];
	}
    }

 if ($flags & CHECK_OID)
 if ($client['cmd'] === 'INIT')
    {
     $client['oId'] = 0;
    }
  else
    {
     if (!isset($client['oId'])) $client['oId'] = 0;

     // Check object identificator value existence
     if ($client['oId'] < STARTOBJECTID)
        {
	 $output['alert'] = 'Incorrect object identificator value!';
	 return;
	}

     // Avoid object id = STARTOBJECTID (system user from User OD) to be deleted
     if ($client['oId'] === STARTOBJECTID && intval($client['ODid']) === 1 && $client['cmd'] === 'DELETEOBJECT')
        {
	 $output['alert'] = 'System account cannot be deleted!';
	 return;
	}
     
     // Check for changes of object selection
     if (gettype($client['objectselection'] = GetObjectSelection($db, $client['objectselection'], $client['params'], $client['auth'])) === 'array')
        {
	 $output['alert'] = "Object selection has been changed, please refresh Object View!";
	 return;
	}
     
     // Check object existence
     $query = $db->prepare("SELECT id FROM `data_$client[ODid]` WHERE id=$client[oId] and lastversion=1 and concat(id,lastversion) IN (SELECT concat(id,lastversion) FROM `data_$client[ODid]` $client[objectselection])");
     $query->execute();
     if (!isset($query->fetchAll(PDO::FETCH_NUM)[0][0]))
        {
	 $output['alert'] = "Please refresh Object View, specified object (id=$client[oId]) doesn't exist!";
	 return;
	}
    }

 if ($flags & CHECK_EID)
 if ($client['cmd'] === 'INIT' || $client['cmd'] === 'DELETEOBJECT')
    {
     $client['eId'] = 0;
    }
  else
    {
     if (!isset($client['eId'])) $client['eId'] = 0;
     
     // Check eid element selection existence
     if (!isset(setElementSelectionIds($client)[strval($client['eId'])]))
        {
	 $output['alert'] = "Please refresh Object View, specified element id doesn't exist!";
	 return;
	}
    }

 if ($flags & CHECK_ACCESS)
 if ($client['cmd'] === 'New Object Database')
    {
     if (getUserODAddPermission($db, $client['uid']) != '+Allow user to add Object Databases|')
        $output['alert'] = "New OD add operation is not allowed!";
    }
  else if (array_search($client['cmd'], ['CALL', 'DELETEOBJECT', 'INIT', 'DBLCLICK', 'KEYPRESS', 'INS', 'DEL', 'F2', 'F12', 'CONFIRM', 'CONFIRMDIALOG', 'SCHEDULE']) !== false)
    {
     $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE id='$client[ODid]'");
     $query->execute();
     if (count($View = $query->fetchAll(PDO::FETCH_NUM)) == 0)
        {
	 $output['error'] = "Database '$client[OD]' Object View '$client[OV]' not found!";
	 return;
	}
      else
	{	  
	 $View = json_decode($View[0][0], true)[$client['OV']]; // Set current view array data
	 $groups = getUserGroups($db, $client['uid']);		// Get current user group list
	 $groups[] = $client['auth'];				// and add username at the end of array
	  
	 // Check on 'display' permissions
	 $count = count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $View['element9']['data'])), "strcmp"));
	 $pos = strpos($View['element8']['data'], '+');
	 if (($count && $pos) || (!$count && !$pos))
	    {
	     $output['error'] = "OV read/write operations is not allowed!";
	     return;
	    }

	 // Check 'writable' permissions for non-CALL event
	 if ($client['cmd'] !== 'CALL')
	    {
	     $count = count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $View['element11']['data'])), "strcmp"));
	     $pos = strpos($View['element10']['data'], '+');
	     if (($count && $pos) || (!$count && !$pos))
	        {
		 $output['alert'] = "OV write operation is not allowed!";
		 return;
		}
	    }
	}
    }
  else
    {
     $output['error'] = "Unknown client event '$client[cmd]'!";
     return;
    }
    
 return true;
}

function CopyArrayElements(&$from, &$to, $props)
{
 foreach ($props as $value) isset($from[$value]) ? $to[$value] = $from[$value] : $to[$value] = '';
}

function cutKeys(&$arr, $keys) // Function cuts all keys of array $arr except of keys defined in $keys array
{
 foreach ($arr as $key => $value) if (array_search($key, $keys) === false) unset($arr[$key]);
}

function CopyKeys(&$arr, $keys)
{                                                                     
 $result = [];
 foreach ($keys as $value) if (isset($arr[$value])) $result[$value] = $arr[$value];
 return $result;                                                      
}
