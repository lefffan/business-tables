<?php

require_once 'const.php';
require_once 'connect.php';

function rmSQLinjectionChars($str) // Function removes dangerous chars such as: ; ' " %
{
 return str_replace(';', '', str_replace('"', '', str_replace("'", '', str_replace("%", '', $str))));
}

function lg($arg, $title = '', $echo = false) // Save input $arg to error.log and echo it if necessary
{
 if ($title) $title = '----------------------------'.$title.'----------------------------';
 file_put_contents(APPDIR.'error.log', "\n".$title, FILE_APPEND);
 file_put_contents(APPDIR.'error.log', "\n", FILE_APPEND);
 file_put_contents(APPDIR.'error.log', var_export($arg, true), FILE_APPEND);
 if ($echo) echo $title, $arg;
}

function adjustODProperties($db, $data, $ODid)
{
 global $newElement, $newView, $newRule;
 initNewODDialogElements();
 
 // Check some vars
 if (!isset($db, $ODid, $data['dialog']['Database']['Properties']['element1']['data'])) return NULL;

 // Element section handle
 if (!isset($data['dialog']['Element']['New element']['element1']['data'])) return NULL;
 $eidnew = strval($data['dialog']['Element']['New element']['element1']['id']);
 foreach ($data['dialog']['Element'] as $key => $value)
	 {
	  // Fetching element id
	  $eid = strval($value['element1']['id']);
	  // Remove element from db and dialog props in case of empty name, description and all hanlders
	  if ($value['element1']['data'] === '' && $value['element2']['data'] === '' && $value['element4']['data'] === '' && $value['element5']['data'] === '' && $value['element6']['data'] === '' && $value['element7']['data'] === '' && $value['element8']['data'] === '' && $value['element9']['data'] === '' && $value['element10']['data'] === '' && $value['element11']['data'] === '' && $value['element12']['data'] === '' && $value['element13']['data'] === '')
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
	  $element['element1']['data'] = substr($element['element1']['data'], 0, ELEMENTDATAVALUEMAXCHAR);
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
	  // Process element new profile after 'element name' rename
	  if ($profile != $key)
	     {
	      $data['dialog']['Element'][$profile] = $data['dialog']['Element'][$key];
	      unset($data['dialog']['Element'][$key]);
	     }
	 }
 $data['dialog']['Element']['New element'] = $newElement; // Reset 'New element' profile to default
 $data['dialog']['Element']['New element']['element1']['id'] = $eidnew; // and set its possible id
 
 // New view section handle
 if (!isset($data['dialog']['View']['New view']['element1']['data'])) return NULL;
 $vidnew = strval($data['dialog']['View']['New view']['element1']['id']);
 $viewpad = &$data['dialog']['View'];
 foreach ($viewpad as $key => $value)
	 if (!isset($value['element1']['data'], $value['element2']['data']) || $value['element1']['data'] === '')
	    unset($data['dialog']['View'][$key]); // Dialog 'View' profile corrupted or view name is empty? Remove it
	  elseif ($value['element1']['data'] === 'New view')
	    $viewpad[$key]['element1']['data'] = $key.''; // Discard changes for the view named 'New view'
 foreach ($viewpad as $key => $value)
	 if (isset($viewpad[$value['element1']['data']]))
	    {
	     $viewpad[$key]['element1']['data'] = $key.''; // New view name already exists? Discard changes
	    }
	  else
	    {
	     $viewpad[$key]['element1']['head'] = "View (id$vidnew) name";
	     $vidnew = strval(intval($vidnew) + 1);
	     $viewpad[$value['element1']['data']] = $viewpad[$key];	// Otherwise create new view with new view name
	     unset($viewpad[$key]);					// and remove old view
	    }
 $data['dialog']['View']['New view'] = $newView; // Reset 'New view' profile to default
 $data['dialog']['View']['New view']['element1']['id'] = $vidnew; // and set its possible i

 // New rule section handle
 if (!isset($data['dialog']['Rule']['New rule']['element1']['data'])) return NULL;
 $rulepad = &$data['dialog']['Rule'];
 foreach ($rulepad as $key => $value)
	 if (!isset($value['element1']['data'], $value['element2']['data']) || $value['element1']['data'] === '')
	    unset($data['dialog']['Rule'][$key]); // Dialog 'Rule' profile corrupted or rule name is empty? Remove it
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
 unset($data['dialog']['Rule']['New rule']);		// Remove 'New rule' option
 ksort($data['dialog']['Rule'], SORT_STRING);		// and sort array in order rules are perfomed
 $data['dialog']['Rule']['New rule'] = $newRule;	// Reset 'New rule' profile to default

 // Return result data
 $data['title'] = 'Edit Object Database Structure';
 $data['buttons'] = SAVECANCEL;
 $data['buttons']['SAVE']['call'] = 'Database Configuration';
 if (!isset($data['flags'])) $data['flags'] = [];
 return $data;
}

function initNewODDialogElements()
{
 global $newProperties, $newElement, $newView, $newRule;

 $newProperties  = ['element1' => ['type' => 'text', 'head' => 'Database name', 'data' => '', 'help' => "To remove database without recovery - remove all elements in 'Element' tab<br>and set database name with its description empty."],
		    'element2' => ['type' => 'textarea', 'head' => 'Database description', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'radio', 'data' => "User/group list the database is visible for|+Hidden for user/group list (visible for others)|"],
		    'element4' => ['type' => 'textarea', 'data' => '', 'line' => ''],
		    //'element3' => ['type' => 'text', 'head' => 'Database size limit in MBytes. Undefined or zero value - no limit.', 'data' => ''],
		    //'element4' => ['type' => 'text', 'head' => 'Database object count limit. Undefined or zero value - no limit.', 'data' => '', 'line' => ''],
		    'element6' => ['type' => 'radio', 'data' => "'Database' user section|+Disallowed list (allowed for others)|"],
		    'element7' => ['type' => 'textarea', 'data' => ''],
		    'element8' => ['type' => 'radio', 'data' => "User/group list allowed to change 'Element' section|+Disallowed list (allowed for others)|"],
		    'element9' => ['type' => 'textarea', 'data' => ''],
		    'element10' => ['type' => 'radio', 'data' => "User/group list allowed to change 'View' section|+Disallowed list (allowed for others)|"],
		    'element11' => ['type' => 'textarea', 'data' => ''],
		    'element12' => ['type' => 'radio', 'data' => "User/group list allowed to change 'Rule' section|+Disallowed list (allowed for others)|"],
		    'element13' => ['type' => 'textarea', 'data' => '', 'line' => '']
		   ];

 $newElement	 = ['element1' => ['type' => 'textarea', 'head' => 'Name', 'data' => '', 'id' => '1', 'help' => 'Element name is used as a default element header text on object view element header navigation.<br>To remove element - set name, description and all handlers empty.'],
		    'element2' => ['type' => 'textarea', 'head' => 'Description', 'data' => '', 'line' => '', 'help' => 'Element description is displayed as a hint on object view element header navigation for default.<br>Describe here element usage and its possible values.'],
		    'element3' => ['type' => 'checkbox', 'head' => 'Element type', 'data' => 'unique|', 'line' => '', 'help' => "Unique element type guarantees element value uniqueness among all objects.<br>Element type cannot be changed after element creation."],
		    'element4' => ['type' => 'text', 'head' => "Handler command lines to process application events below", 'label' => "'INIT' event:", 'data' => ''],
		    'element5' => ['type' => 'text', 'label' => "'DBLCLICK' event:", 'data' => ''],
		    'element6' => ['type' => 'text', 'label' => "'KEYPRESS' event:", 'data' => ''],
		    'element7' => ['type' => 'text', 'label' => "'INS' event:", 'data' => ''],
		    'element8' => ['type' => 'text', 'label' => "'DEL' event:", 'data' => ''],
		    'element9' => ['type' => 'text', 'label' => "'F2' event:", 'data' => ''],
		    'element10' => ['type' => 'text', 'label' => "'F12' event:", 'data' => ''],
		    'element11' => ['type' => 'text', 'label' => "'CONFIRM' event:", 'data' => ''],
		    'element12' => ['type' => 'text', 'label' => "'CONFIRMDIALOG' event:", 'data' => ''],
		    'element13' => ['type' => 'text', 'label' => "'CHANGE' event:", 'data' => '', 'line' => '']
		   ];

 $newView	 = ['element1' => ['type' => 'text', 'head' => 'View name', 'data' => '', 'id' => '1', 'help' => "View name may be changed, but if renamed view name already exists, changes are not applied.<br>So name 'New view' cannot be set as it is used as an option to create new views.<br>Empty view name removes the view.<br>In addition, symbol '_' as a first character in a view name string keeps unnecessary views<br>off sidebar, so these hidden views can be called from element handlers only."],
		    'element2' => ['type' => 'textarea', 'head' => 'Description', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'radio', 'head' => 'Template', 'data' => '+Table|Tree|Map|', 'help' => "Select object view type from 'table' (displays objects in a form of a table),<br>'scheme' (displays object hierarchy built on object selection link name),<br>'map' (displays objects on the geographic map)"],
		    'element4' => ['type' => 'textarea', 'head' => 'Object selection', 'help' => 'Object selection is a part of the sql query string, that selects objects for the view.<br>Empty string selection - all objects, error selection - no objects.<br>See appropriate help section for details.', 'data' => ''],
		    'element5' => ['type' => 'text', 'label' => 'Link name', 'data' => '', 'line' => ''],
		    'element6' => ['type' => 'textarea', 'head' => 'Element layout', 'data' => '', 'line' => '', 'help' => 'Element layout defines what elements should be displayed and how for the selected template.<br>Empty layout is a default behaviour, see appropriate help section for details.'],
		    'element7' => ['type' => 'textarea', 'head' => 'Scheduler', 'data' => '', 'line' => '', 'help' => "Scheduler is an instruction list (one per line), each instruction executes command line<br>at specified datetime for specified element for all objects of the view.<br>Instruction represents itself one by one space separated args in next format:<br>&lt;minute 0-59> &lt;hour 0-23> &lt;mday 1-31> &lt;month 1-12> &lt;wday 0-7> &lt;element id number> &lt;command line><br>See 'Database Configuration' help section for details."],
		    'element8' => ['type' => 'radio', 'data' => "User/group list allowed to read this view|+Disallowed list (allowed for others)|"],
		    'element9' => ['type' => 'textarea', 'data' => ''],
		    'element10' => ['type' => 'radio', 'data' => "User/group list allowed to change this view objects|+Disallowed list (allowed for others)|"],
		    'element11' => ['type' => 'textarea', 'data' => '']
		   ];

 $newRule	 = ['element1' => ['type' => 'text', 'head' => 'Name', 'data' => '', 'help' => "Rule profile name. It may be changed, but if renamed profile already exists, changes are not applied.<br>So name 'New rule' cannot be set as it is used as an option to create new rules.<br>Empty profile name removes the rule."],
		    'element2' => ['type' => 'textarea', 'head' => 'Rule message', 'data' => '', 'line' => '', 'help' => 'Rule message is a match case log message displayed on the client side dialog box.'],
		    'element3' => ['type' => 'select-one', 'head' => 'Rule action', 'data' => '+Accept|Reject|', 'line' => '', 'help' => "'Accept' action agrees specified event or operation, 'Reject' action cancels event or changes made by the operation."],
		    'element4' => ['type' => 'checkbox', 'head' => 'Rule apply operation/event', 'data' => 'Add object|Delete object|Change object<br>|DBLCLICK|KEYPRESS|INS|DEL|F2|F12|', 'line' => ''],
		    'element5' => ['type' => 'textarea', 'head' => 'Rule query', 'data' => '', 'help' => "Any mouse/keyboard client side event or object add/delete/change operation is passed to the analyzer<br>to test on all rule profiles in alphabetical order (for the specified event or/and operation) until<br>the match is found. Rule query is a list of SQL query strings (one by line). Non-empty and non-zero<br>result of all query strings - match case, any empty, error or zero char '0' result - no match.<br>When a match is found, the action corresponding to the matching rule profile is performed, otherwise<br>default action 'accept' is applied."],
		    'element6' => ['type' => 'checkbox', 'data' => '+Log rule message|', 'line' => '', 'help' => '']
		   ];
}

function CalculateElementPropQuery($element, $prop = 'value')
{
 if (array_search($element, SERVICEELEMENTS) !== false) return $element; // Service element match? Return its name
 if (!$prop) $prop = 'value';
 return 'JSON_UNQUOTE(JSON_EXTRACT(eid'.strval($element).", '$.".$prop."'))"; // Otherwise return unqouted eidid->>'$.prop'
}

function getElementProp($db, $ODid, $oid, $eid, $prop, $version = NULL)
{
 // Check input
 if (!isset($ODid) || !isset($oid) || !isset($eid)) return NULL;

 // Calculate query parts of element column name and version selection
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

 if (!isset($result[0][0])) return NULL;
 return $result[0][0];
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
      $ruleresult = ProcessRules($db, $client, 'Add object', '1', '1');
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
 if ($client['ODid'] != '2' && isset($ruleresult['log'])) LogMessage($db, $client, $ruleresult['log']);
 $output = ['cmd' => '', 'alert' => $ruleresult['message']];
}

function removeDir($dir)
{
 if ($files = glob($dir.'/*')) foreach($files as $file) is_dir($file) ? removeDir($file) : unlink($file);
 rmdir($dir);
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

      $ruleresult = ProcessRules($db, $client, 'Delete object', $version, '0');
      if ($ruleresult['action'] === 'Accept')
         {
	  $db->commit();
	  if (isset($ruleresult['log'])) LogMessage($db, $client, $ruleresult['log']);
	  $output = ['cmd' => 'DELETEOBJECT'];
	  if (isset($ruleresult['message']) && $ruleresult['message']) $output['alert'] = $ruleresult['message'];
	  $dir = UPLOADDIR."$client[ODid]/$client[oId]";
	  if (is_dir($dir)) removeDir($dir);
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
 if ($client['ODid'] != '2' && isset($ruleresult['log'])) LogMessage($db, $client, $ruleresult['log']);
 $output = ['cmd' => '', 'alert' => $ruleresult['message']];
}

function ParseRuleMsgElementId(&$client, $msg)
{
 if (preg_match_all('|\{\d+\}|', $msg, $matches)) foreach($matches[0] as $value)
    {
     $id = substr($value, 1, -1);
     if (isset($client[$id]['element1']['data'])) $msg = str_replace($value, $client[$id]['element1']['data'], $msg);
    }
 return $msg;
}

function ProcessRules($db, &$client, $operation, $preversion, $postversion)
{
 // Get rule profile json data
 $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.Rule') FROM $ WHERE id='$client[ODid]'");
 $query->execute();
 $Rules = $query->fetchAll(PDO::FETCH_NUM);

 // Move on. Return default action in case of empty rule profiles or decoding error
 if (!isset($Rules[0][0]) || gettype($Rules = json_decode($Rules[0][0], true)) != 'array') return ['action' => 'Accept', 'message' => ''];
 unset($Rules['New rule']); // Exlude service 'New rule' profile

 // Process non empty expression rules one by one
 foreach ($Rules as $key => $rule)
	 {
	  $action = strpos($rule['element3']['data'], '+Accept') === false ? 'Reject' : 'Accept'; // Set accept/reject action
	  if (strpos($rule['element4']['data'], '+'.$operation) === false) continue; // No apply operation selected? Continue
	  if (($querytext = trim($rule['element5']['data'])) === '') continue; // Query is empty? Continue
	  $querytext = str_replace(':user', $client['auth'], $querytext); // Replace with actual username inited the operation
	  $querytext = str_replace(':preversion', $preversion, $querytext); // Replace with object version before operation
	  $querytext = str_replace(':postversion', $postversion, $querytext); // Replace with object version after operation
	  $querytext = str_replace(':oid', $client['oId'], $querytext); // Replace with object id
	  $querytext = str_replace(':odtable', "`data_$client[ODid]`", $querytext); // Replace with sql table

	  foreach (preg_split("/\n/", $querytext) as $querystring) // Perform a rule query
		  {
		   try { $query = $db->prepare($querystring); $query->execute(); }
		   catch (PDOException $e) { return ['action' => 'Accept', 'message' => 'Rule error: '.$e->getMessage()]; }
		   $result = $query->fetch(PDO::FETCH_NUM);
		   if (!isset($result[0]) || !$result[0] || $result[0] === '0') continue 2;
		  }

	  // Rule match occured. Return its action
	  $output = ['action' => $action, 'message' => trim($rule['element2']['data'])];
	  if (substr($rule['element6']['data'], 0, 1) === '+') $output['log'] = "Database rule '$key' match, action: '$action', message: '$output[message]'"; // Log rule message in case of approprate checkbox is set
	  return $output;
	 }

 // Return default action
 return ['action' => 'Accept', 'message' => ''];
}

function SetLayoutProperties(&$client, $db = NULL)
{
 $client['layout'] = ['elements' => [], 'virtual' => [], 'undefined' => [], 'table' => []];
 $layout = &$client['layout'];
 $order = [];
 $e = 0;

 foreach (preg_split("/\n/", $client['elementselection']) as $json)
      if (($arr = json_decode($json, true, 3)) && gettype($arr) === 'array')
	 {
	  // Check object id (oid) for unset value and unset at least one of three virtual props to assume JSON as a table attributes
	  if (!isset($arr['oid']))
	  if (isset($arr['x'], $arr['y'], $arr['value']))
	     {
	      foreach ($arr as $key => $value)
		   if (gettype($value) !== 'string' || array_search($key, ['x', 'y', 'style', 'value', 'hint', 'event']) === false) unset($arr[$key]);
	      if (isset($db, $arr['x'], $arr['y'], $arr['value']))
		 {
		  if (stripos(trim($arr['value']), 'SELECT ') === 0)
		     {
		      try {
			   $query = $db->prepare($value);
			   $query->execute();
			   $value = $query->fetchAll(PDO::FETCH_NUM);
			   if (isset($value[0][0])) $arr['value'] = $value[0][0];
			  }
		      catch (PDOException $e) {}
		     }
		  $layout['virtual'][] = $arr;
		 }
	      continue;
	     }
	   else
	     {
	      unset($arr['eid']);
	      foreach ($arr as $key => $value) if (gettype($value) !== 'string') unset($arr[$key]);
	      $layout['table'] = $arr + $layout['table'];
	      continue;
	     }

	  // Retrieve correct values only
	  foreach ($arr as $key => $value)
		  if (gettype($value) !== 'string' || array_search($key, ['eid', 'oid', 'x', 'y', 'style', 'value', 'hint', 'event', 'hiderow', 'hidecol']) === false)
		     {
		      unset($arr[$key]);
		     }
		   else
		     {
		      if ($key === 'eid' || $key === 'oid' || $key === 'x' || $key === 'y' || $key === 'style') $arr[$key] = trim($value);
		     }
	  if (!count($arr)) continue;

	  // Check oid for empty value treated as underfined (style for cell with no any object element placed in)
	  if (($oid = $arr['oid']) === '')
	     {
	      if (isset($arr['style'])) $layout['undefined']['style'] = $arr['style'];
	      if (isset($arr['hiderow'])) $layout['undefined']['hiderow'] = $arr['hiderow'];
	      continue;
	     }

	  // Parse element list in eid property if set (with '*' for all elements), otherwise continue
	  if (!isset($arr['eid'])) continue;
	  $eids = [];
	  foreach (preg_split("/,/", $arr['eid']) as $value) if ($eid = trim($value))
		  if (array_search($eid, SERVICEELEMENTS) !== false || isset($client['allelements'][$eid]))
		     {
		      $eids[$eid] = true;
		      if (!isset($order[$eid])) { $order[$eid] = $e; $e++; }
		     }
		   else if ($eid === '*') foreach ($client['allelements'] as $id => $valeu)
		     {
		      $eids[$id] = true;
		      if (!isset($order[$id])) { $order[$id] = $e; $e++; }
		     }
	  if (!count($eids)) continue;

	  // If oid is a number - check the range and negative value, otherwise treat it as an expression and check for restricted vars
	  $oidnum = NULL;
	  if (ctype_digit($oid) || ($oid[0] === '-' && ctype_digit(substr($oid, 1))))
	     {
	      if (($oidnum = intval($oid)) !== TITLEOBJECTID && $oidnum !== NEWOBJECTID && $oidnum < STARTOBJECTID) continue;
	     }
	   else
	     {
	      if ($oid !== '*' && preg_match("/[^oenq\+\-\;\&\|\!\*\/0123456789\.\%\>\<\=\(\) ]/", $oid)) continue;
	     }

	  // Object id (oid) is a number or asterisk, all other values - expression. Process all elements in $eids for the specified oid
	  unset($arr['eid']);
	  foreach ($eids as $eid => $value)
		  {
		   $src = $arr;
		   if (!isset($layout['elements'][$eid])) $layout['elements'][$eid] = ['*' => [], 'expression' => [], 'order' => $order[$eid]];
		   if ($oidnum === TITLEOBJECTID)
		      {
		       if (!isset($src['value'])) $src['value'] = ($pos = array_search($eid, SERVICEELEMENTS)) === false ? $client['allelements'][$eid]['element1']['data'] : SERVICEELEMENTTITLES[$pos];
		       if (!isset($src['hint'])) $src['hint'] = array_search($eid, SERVICEELEMENTS) === false ? $client['allelements'][$eid]['element2']['data'] : '';
		      }
		   if ($oidnum === NEWOBJECTID)
		      {
		       if (!isset($src['value'])) $src['value'] = '';
		       if (!isset($src['hint'])) $src['hint'] = '';
		      }
		   if ($oid === '*' || isset($oidnum))
		      {
		       unset($src['oid']);
		       if (!isset($layout['elements'][$eid][$oid])) $layout['elements'][$eid][$oid] = [];
		       $layout['elements'][$eid][$oid] = $src + $layout['elements'][$eid][$oid];
		       continue;
		      }
		   $layout['elements'][$eid]['expression'][] = $src;
		  }
	 }
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
 // Fetch group list extracted from user JSON
 $query = $db->prepare("SELECT JSON_EXTRACT(eid1, '$.groups') FROM `data_1` WHERE id=:id AND lastversion=1 AND version!=0");
 $query->execute([':id' => $id]);
 $groups = $query->fetchAll(PDO::FETCH_NUM);
 if (!isset($groups[0][0])) return [];
 // Convert group list to the array
 $groups = UnsetEmptyArrayElements(explode("\\n", substr($groups[0][0], 1, -1)));
 // Check group names on existed username and exlude the group in case of true
 foreach ($groups as $key => $name)
	 {
	  $query = $db->prepare("SELECT id FROM `uniq_1` WHERE eid1=:name");
	  $query->execute([':name' => $name]);
	  $group = $query->fetchAll(PDO::FETCH_NUM);
	  if (isset($group[0][0])) unset($groups[$key]);
	 }
 return $groups;
}

function UnsetEmptyArrayElements($arr)
{
 if (!is_array($arr)) return []; // Return empty array in case of wrong input
 foreach ($arr as $key => $value) if ($value === '' || gettype($value) != 'string') unset($arr[$key]);
 return $arr;
}

function getUserODAddPermission($db, $id)
{
 $query = $db->prepare("SELECT JSON_EXTRACT(eid1, '$.odaddperm') FROM `data_1` WHERE id=:id AND lastversion=1 AND version!=0");
 $query->execute([':id' => $id]);
 $odaddperm = $query->fetchAll(PDO::FETCH_NUM);
 if (isset($odaddperm[0][0])) return substr($odaddperm[0][0], 1, -1);
 return '';
}

function getUserProps($db, $id, $props)
{
 if (gettype($props) !== 'array' || !count($props)) return [];
 $query = '';
 foreach ($props as $prop) $query .= "JSON_UNQUOTE(JSON_EXTRACT(eid1, '$.$prop')) as $prop,";
 $query = substr($query, 0, -1);

 $query = $db->prepare("SELECT $query FROM `data_1` WHERE id=:id AND lastversion=1 AND version!=0");
 $query->execute([':id' => $id]);
 $data = $query->fetchAll(PDO::FETCH_ASSOC);
 if (isset($data[0])) return $data[0];
 return [];
}

function getUserCustomization($db, $uid)
{
 $customization = json_decode(getElementProp($db, '1', $uid, '6', 'dialog'), true); // Get current user JSON customization and decode it
 if (($error = json_last_error_msg()) !== 'No error') return $error;

 // If current user customization forces to use another user customization, and the user doesn't point to itself and does exist - get it
 if (($forceuser = $customization['pad']['application']['element3']['data']) != '' && $forceuser != 'system' && ($forceuser = getUserId($db, $forceuser)))
 if (isset($forceuser) && $uid != $forceuser)
    {
     $forceuser = json_decode(getElementProp($db, '1', $forceuser, '6', 'dialog'), true);
     if (isset($forceuser)) return $forceuser;
    }

 return $customization;
}

function getLoginDialogData($title = '')
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
  else 
    {
     $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
    }
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
 // Init some vars lg("---------------------Function decode start---------------------");
 $datalength = strlen($data); //lg("Input data length = $datalength");
 ord($data[0]) > 127 ? $decoded = ['fin' => 1, 'datalength' => $datalength] : $decoded = ['fin' => 0, 'datalength' => $datalength];

 // Calculating opcode
 $firstByteBinary = sprintf('%08b', ord($data[0]));
 $secondByteBinary = sprintf('%08b', ord($data[1]));
 $opcode = bindec(substr($firstByteBinary, 4, 4));
 if ($opcode == 8) return; // lg("---------------------Function decode finish: closed frame---------------------\n");
 if ($opcode > 8) return false; //lg("---------------------Function decode finish: control frame---------------------\n");
 //lg("Data frame with opcode = $opcode and FIN flag = $decoded[fin]");

 // Masked/unmasked frame calculating
 $isMasked = ($secondByteBinary[0] == '1') ? true : false;
 if (!$isMasked) return; // lg("---------------------Function decode finish: unmasked frame---------------------\n");
 $payloadLength = ord($data[1]) & 127; //lg("Frame is masked with payload length = $payloadLength");

 // Calculating frame length
 if ($payloadLength === 126)
    {
     $mask = substr($data, 4, 4);
     $payloadOffset = 8;
     $framelength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
    }
  elseif ($payloadLength === 127)
    {
     $mask = substr($data, 10, 4);
     $payloadOffset = 14;
     $framelength = '';
     for ($i = 0; $i < 8; $i++) $framelength .= sprintf('%08b', ord($data[$i + 2]));
     $framelength = bindec($framelength) + $payloadOffset;
    }
  else
    {
     $mask = substr($data, 2, 4);
     $payloadOffset = 6;
     $framelength = $payloadLength + $payloadOffset;
    }
 $decoded['framelength'] = $framelength; // lg("Frame length = ".strval($framelength));

 // We have to check for large frames here - socket_recv cuts at 1024 bytes so if websocket frame is more than 1024 bytes, then we have to wait until whole data is transfered
 if ($datalength < $framelength) return $decoded; // lg("---------------------Function decode finish: frame is defragmentated---------------------\n");

 $payload ='';
 for ($i = $payloadOffset; $i < $framelength; $i++) if (isset($data[$i])) $payload .= $data[$i] ^ $mask[($i - $payloadOffset) % 4];
 $decoded['payload'] = $payload;

 return $decoded; // lg("---------------------Function decode finish: success---------------------\n");
}

function handshake($connect)
{
 $info = array();

 $line = fgets($connect);
 $header = explode(' ', $line);
 $info['method'] = $header[0];
 $info['uri'] = $header[1];

 while ($line = rtrim(fgets($connect)))
       {
	if (!(preg_match('/\A(\S+): (.*)\z/', $line, $matches))) break;
	$info[$matches[1]] = $matches[2];
       }

 $address = explode(':', stream_socket_get_name($connect, true));
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

function GenerateRandomString($length = USERPASSMINLENGTH)
{
 $len = strlen($permittedchars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
 $randomstring = '';
 for ($i = 0; $i < $length; $i++) $randomstring .= $permittedchars[mt_rand(0, $len - 1)];

 return $randomstring;
}

function Swap(&$a, &$b)
{
 $swap = $a;
 $a = $b;
 $b = $swap;
}

function Sidebar($db, &$client)
{
 $groups = getUserGroups($db, $client['uid']); // Get current user group list
 $groups[] = getUserName($db, $client['uid']); // and add username at the end of array
 $sidebar = [];
 $user = getUserProps($db, $client['uid'], ['odvisible', 'odvisiblelist', 'odwrite', 'odwritelist']);
 if (!isset($user['odvisible'], $user['odvisiblelist'], $user['odwrite'], $user['odwritelist'])) return [];

 $query = $db->prepare("SELECT id,odname FROM `$`"); // Select OD list - ids and names
 $query->execute();
 foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $value)
	 {
	  // Extract JSON OD configuration structure (database and view section)
	  $name = $value['odname'];
	  $id = $value['id'];
	  $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.Database.Properties'),JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE odname='$name'");
	  $query->execute();
	  $dbprops = $query->fetchAll(PDO::FETCH_NUM);
	  if (!isset($dbprops[0][0])) continue;
	  $viewlist = json_decode($dbprops[0][1], true);
	  $dbprops = json_decode($dbprops[0][0], true);
	  if (gettype($dbprops) !== 'array' || gettype($viewlist) !== 'array') continue;

	  // Check OD visible
	  if (ViewRestrictionMatch(intval($id), 0, $user['odvisiblelist'], $user['odvisible'])) continue; // OD is hidden for the user
	  if (UserRestrictionMatch($groups, $dbprops['element4']['data'], $dbprops['element3']['data'])) continue; // OD is hidden for the user

	  // Create OD record for the sidebar array
	  $sidebar[$id] = ['name' => $name, 'view' => []]; // Make sidebar based on OD id array. Array values - OD name and view list array
	  foreach ($viewlist as $key => $View) if ($key != 'New view')
		  {
		   if (ViewRestrictionMatch(intval($id), intval($View['element1']['id']), $user['odvisiblelist'], $user['odvisible'])) continue; // OD is hidden for the user
		   if (UserRestrictionMatch($groups, $View['element9']['data'], $View['element8']['data'])) continue; // OV is hidden for the user
		   $sidebar[$id]['view'][$View['element1']['id']] = $View['element1']['data']; // Make OV based on its ids array and set active OD view below
		   if ($id === $client['ODid'] && $View['element1']['id'] === $client['OVid']) $sidebar[$id]['active'] = $client['OVid'];
		  }
	 }
 return $sidebar;
}

function ViewRestrictionMatch($odid, $viewid, $list, $listtype)
{
 $pos = strpos($listtype, '+') ? true : false;

 foreach (preg_split("/\n/", $list) as $value)
	 {
	  $od = preg_split("/\:/", $value, 3);
	  $ov = isset($od[1]) ? intval(trim($od[1])) : 0;
	  $od = intval(trim($od[0]));
	  if ($pos)
	     {
	      if ($odid === $od && ((!$viewid && !$ov) || ($viewid && ($ov === $viewid || !$ov)))) return true;
	     }
	   else
	     {
	      if ($odid === $od && (!$viewid || ($odid === $od && $viewid && ($ov === $viewid || !$ov)))) return false;
	     }
	 }

 return !$pos;
}

function UserRestrictionMatch(&$usergroups, $userlist, $listtype)
{
 $count = count(array_uintersect($usergroups, UnsetEmptyArrayElements(explode("\n", $userlist)), "strcmp"));
 $pos = strpos($listtype, '+');
 if (($count && $pos) || (!$count && !$pos)) return true;
}


function LinkNamesStringToArray($names)
{
 // Initing link names array
 $linknames = [];
 // Calculating delimiter
 (($posAND = strpos($names, '/')) !== false && (($posOR = strpos($names, '|')) === false || $posOR > $posAND)) ? $delimiter = '/' : $delimiter = '|';
 // Calculating link names separated by delimiter
 foreach (preg_split("/\\".$delimiter."/", $names) as $name) if (trim($name)) $linknames[] = trim($name);
 // Set empty key flag for delimiter '/'
 if ($linknames !== [] && $delimiter === '/') $linknames[''] = '';
 return $linknames;
}

function Check($db, $flags, &$client, &$output)
{
 if ($flags & CHECK_OD_OV)
    {
     $output['sidebar'] = Sidebar($db, $client);
     if (count($output['sidebar']) == 0 && ($output['error'] = 'Please create Object Database first!')) return;
     if ($client['ODid'] === '' && ($output['error'] = 'Please create/select Object View!')) return;

     // Fetch OD id in case of OD name exist
     if (!isset($client['ODid']) && isset($client['OD']))
	 foreach ($output['sidebar'] as $id => $value) if ($value['name'] === $client['OD'] && ($client['ODid'] = $id)) break;

     // Fetch OV id in case of OV name exist
     if (isset($client['ODid']) && !isset($client['OVid']) && isset($client['OV']))
	 foreach ($output['sidebar'][$client['ODid']]['view'] as $id => $value) if ($value === $client['OV'] && ($client['OVid'] = $id)) break;

     if (!isset($output['sidebar'][$client['ODid']]['view'][$client['OVid']]) && ($output['error'] = "Database '$client[OD]' or its View '$client[OV]' not found!")) return;
     $client['OD'] = $output['sidebar'][$client['ODid']]['name'];
     $client['OV'] = $output['sidebar'][$client['ODid']]['view'][$client['OVid']];
    }

 if ($flags & GET_ELEMENTS)
    {
     $client['allelements'] = $client['uniqelements'] = []; // Flush all and uniq elements array and build them below
     $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.Element') FROM $ WHERE id='$client[ODid]'"); // Get element section
     $query->execute();
     if (!count($elements = $query->fetchAll(PDO::FETCH_NUM)) && ($output['error'] = "Object View '$client[OV]' of Database '$client[OD]' not found!")) return;

     // Convert profiles assoc array to num array with element identificators as array elements instead of profile names and sort it
     foreach (json_decode($elements[0][0], true) as $key => $value) if ($key != 'New element') // Go through all element profiles
    	     {
	      $id = intval($value['element1']['id']); // Calculate current element id
	      $client['allelements'][$id] = $value;
	      if ($value['element3']['data'] === UNIQELEMENTTYPE) $client['uniqelements'][$id] = '';
	     }

     if (!count($client['allelements']) && ($output['error'] = "Database '$client[OD]' has no elements exist!")) return;
     ksort($client['allelements'], SORT_NUMERIC);
    }

 if ($flags & GET_VIEWS)
    {
     // Flush object selection, element layout (ex-element-selection), view type (template) and link name
     unset($client['objectselection'], $client['elementselection'], $client['viewtype'], $client['linknames']);
     $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE id='$client[ODid]'");
     $query->execute();

     foreach (json_decode($query->fetchAll(PDO::FETCH_NUM)[0][0], true) as $value)
	  if ($value['element1']['id'] === $client['OVid'])
	     {
	      $client['viewtype'] = substr($value['element3']['data'], ($pos = strpos($value['element3']['data'], '+')) + 1, strpos($value['element3']['data'], '|', $pos) - $pos -1);
	      $client['objectselection'] = trim($value['element4']['data']);
	      $client['linknames'] = $value['element5']['data'];
	      $client['elementselection'] = $value['element6']['data'];
	      break;
	     }
     if (!isset($client['elementselection'], $client['objectselection'], $client['viewtype'], $client['linknames']))
	{
	 $output['error'] = "Object View '$client[OV]' of Database '$client[OD]' not found!";
	 return;
	}

     $client['linknames'] = LinkNamesStringToArray($client['linknames']);

     if ($client['viewtype'] === 'Table')
	{
	 if (trim($client['elementselection']) === '') $client['elementselection'] = '*';
	 $layout = '';
	 foreach (preg_split("/\n/", $client['elementselection']) as $json) if ($json)
		 {
		  if (gettype(json_decode($json, true)) === 'array')
		     {
		      $layout .= $json."\n";
		      continue;
		     }
		  $startline = $json[0] === ' ' ? 'n+2' : 'n+1';
		  $elements = [];
		  $x = 0;
		  foreach (preg_split("/,/", $json) as $eid)
			  if (($element = trim($eid)) === '*') foreach ($client['allelements'] as $id => $value) $elements[$id] = true;
			   else if (array_search($element, SERVICEELEMENTS) !== false || isset($client['allelements'][$element])) $elements[$element] = true;
		  foreach ($elements as $id => $value)
			  {
			   $layout .= '{"eid": "'.$id.'", "oid": "'.strval(TITLEOBJECTID).'", "x": "'.strval($x).'", "y": "0"}'."\n";
			   if ($startline === 'n+2') $layout.= '{"eid": "'.$id.'", "oid": "'.strval(NEWOBJECTID).'", "x": "'.strval($x).'", "y": "1"}'."\n";
			   $layout .= '{"eid": "'.$id.'", "oid": "*", "x": "'.strval($x).'", "y": "'.$startline.'"}'."\n";
			   $x++;
			  }
		 }
	 $client['elementselection'] = $layout;
	}

     // List is empty for a 'Tree' view? Set up default list for all elements appearance: {'title1': '', 'value1': '', 'title2': ''..} 
     if ($client['viewtype'] === 'Tree')
     if ($client['elementselection'] === '')
        {
	 $client['elementselection'] = ['id' => ''];
	 foreach ($client['allelements'] as $id => $value) $client['elementselection'][$id] = '';
	}
      else
        {
	 foreach (preg_split("/\n/", $client['elementselection']) as $value)
		 if (gettype($arr = json_decode($value, true, 2)) === 'array') break;
	 if (gettype($arr) !== 'array') $arr = ['id' => ''];
	 foreach ($arr as $id => $value)
		 if (!isset($client['allelements'][$id]) && array_search($id, SERVICEELEMENTS) === false && $id !== 'direction')
		    unset($arr[$id]);
	 $client['elementselection'] = $arr;
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
     if ($client['oId'] < STARTOBJECTID && ($output['alert'] = 'Incorrect object identificator value!')) return;
     // Avoid object id = STARTOBJECTID (system user from User OD) to be deleted
     if ($client['oId'] === STARTOBJECTID && intval($client['ODid']) === 1 && $client['cmd'] === 'DELETEOBJECT' && ($output['alert'] = 'System account cannot be deleted!')) return;

     // Check for changes of object selection
     if (gettype($client['objectselection'] = GetObjectSelection($client['objectselection'], $client['params'], $client['auth'])) === 'array' && ($output['alert'] = "Object selection has been changed, please refresh Object View!")) return;

     // Check object existence
     if ($client['linknames'] === [])
        {
	 $query = $db->prepare("SELECT id FROM (SELECT * FROM `data_$client[ODid]` WHERE id=$client[oId] AND lastversion=1 AND version!=0) _ $client[objectselection]");
	 $query->execute();
	 if (!isset($query->fetchAll(PDO::FETCH_NUM)[0][0]) && ($output['alert'] = "Please refresh, specified object (id=$client[oId]) doesn't exist in the view!")) return;
        }
      else
        {
	 $tree = [];
	 CreateTree($db, $client, 0, $tree, 'EXISTENCE');
	 if (!isset($client['objects'][$client['oId']]) && ($output['alert'] = "Please refresh, specified object (id=$client[oId]) doesn't exist in the view!")) return;
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
     SetLayoutProperties($client);
     if (!isset($client['layout']['elements'][strval($client['eId'])]) && ($output['alert'] = "Please refresh Object View, specified element id doesn't exist!")) return;
    }

 if ($flags & CHECK_ACCESS)
 if ($client['cmd'] === 'New Database')
    {
     if (getUserODAddPermission($db, $client['uid']) != '+Allow user to add Object Databases|' && ($output['alert'] = "New OD add operation is not allowed!"))
	return;
    }
  else if (array_search($client['cmd'], ['CALL', 'DELETEOBJECT', 'INIT', 'DBLCLICK', 'KEYPRESS', 'INS', 'DEL', 'F2', 'F12', 'CONFIRM', 'CONFIRMDIALOG', 'SCHEDULE']) !== false)
    {
     $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE id='$client[ODid]'");
     $query->execute();
     if (!count($View = $query->fetchAll(PDO::FETCH_NUM)) && ($output['error'] = "Database '$client[OD]' Object View '$client[OV]' not found!")) return;

     $View = json_decode($View[0][0], true)[$client['OV']];	// Set current view array data
     $groups = getUserGroups($db, $client['uid']);		// Get current user group list
     $groups[] = $client['auth'];				// and add username at the end of array

     $user = getUserProps($db, $client['uid'], ['odvisible', 'odvisiblelist', 'odwrite', 'odwritelist']);
     if (!isset($user['odvisible'], $user['odvisiblelist'], $user['odwrite'], $user['odwritelist']) && ($output['error'] = "Unknown user '$client[auth]'!")) return;

     // Check on 'display' permissions
     if (ViewRestrictionMatch(intval($client['ODid']), intval($client['OVid']), $user['odvisiblelist'], $user['odvisible']) && ($output['error'] = "OV read/write operations are not allowed!")) return;
     if (UserRestrictionMatch($groups, $View['element9']['data'], $View['element8']['data']) && ($output['error'] = "OV read/write operations are not allowed!")) return;

     // Check 'writable' permissions for non-CALL event
     if ($client['cmd'] !== 'CALL')
	{
	 if (ViewRestrictionMatch(intval($client['ODid']), intval($client['OVid']), $user['odwritelist'], $user['odwrite']) && ($output['alert'] = "OV write operation are not allowed!")) return;
	 if (UserRestrictionMatch($groups, $View['element11']['data'], $View['element10']['data']) && ($output['alert'] = "OV write operation are not allowed!")) return;
	}

     // Check rules
     if (array_search($client['cmd'], ['DBLCLICK', 'KEYPRESS', 'INS', 'DEL', 'F2', 'F12']) !== false)
	{
	 $query = $db->prepare("SELECT version FROM `data_$client[ODid]` WHERE id=$client[oId] AND lastversion=1");
	 $query->execute();
	 $version = $query->fetchAll(PDO::FETCH_NUM);
	 if (!isset($version[0][0]) && ($output['alert'] = "Please refresh, specified object (id=$client[oId]) doesn't exist in the view!")) return;

	 $ruleresult = ProcessRules($db, $client, $client['cmd'], $version[0][0], $version[0][0]);
	 if ($ruleresult['action'] === 'Reject')
	    {
	     $output['alert'] = $ruleresult['message'];
	     if (isset($ruleresult['log']) && $ruleresult['log']) $output['log'] = $ruleresult['log'];
	     return;
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

function QueueCall($db, $socket, $id, &$message)
{
 // Put request to the queue sql table that will be checked by view.php after client ajax request
 $query = $db->prepare("INSERT INTO `$$$` (id,client) VALUES (:id,:message)");
 $query->execute([':id' => $id, ':message' => $message]);
 if ($socket) fwrite($socket, encode($message));
}

function CreateTree($db, &$client, $oid, &$data, $cmd)
{
 // Init head object and all tree objects global array for loop detection at the 1st entry
 if ($oid === 0)
    {
     $client['objects'] = [];
     if (!($oid = GetHeadId($db, $client))) return;
     $client['objects'][$oid] = true;
     $data = ['link' => [], 'oid' => $oid]; // Init tree with head object
     switch ($cmd) // Process command
	    {
	     case 'TABLE':
		  $client['tree'] = [];
		  GetObjectData($db, $client, $oid);
		  break;
	     case 'SEARCH':
		  $client['tree'] = [];
		  GetObjectSearchData($db, $client, $oid);
		  break;
	     case 'TREE':
		  $data['content'] = [[], []];
		  GetTreeElementContent($db, $client, $data['content'], $oid);
		  $data['class'] = 'treeelement';
		  break;
	     case 'EXISTENCE':
		  if ($client['oId'] === $oid) return;
		  break;
	    }
    }

 // Tree search is finished?
 if ((isset($client['treelastnode']) && $client['treelastnode'] === $oid) || (!isset($client['treelastnode']) && $cmd === 'EXISTENCE' && $client['oId'] === $oid) || ($cmd === 'SEARCH' && !$client['limit'])) return true;

 // Get object all element link and value props
 if (!($count = count($object = GetObjectElementLinksArray($db, $client, $oid)))) return;

 // Get through all elements matched link names
 $linknames = $client['linknames'];
 for ($i = 0; $i < $count; $i += 3)
 foreach (LinkMatch($linknames, $object[$i + 1]) as $value)
	 {
	  // Generate tree content
	  if ($cmd === 'TREE') $content = [ ['id' => $object[$i], 'title' => $client['allelements'][$object[$i]]['element1']['data'], 'value' => $object[$i + 2]], ['id' => $value[0], 'title' => GetELementTitle($value[0], $client['allelements'])] ];

	  // Search uplink object id
	  try {
	       $query = $db->prepare("SELECT id FROM `data_$client[ODid]` WHERE lastversion=1 AND version!=0 AND $value[1] LIMIT 1");
	       $query->execute();
	      }
	  catch (PDOException $e) // Syntax error? Make virtual error node with error message as a content
	      {
	       if ($cmd === 'TREE') // Each $data (for cmd=TREE) array element is class (content css class name), content (elemnt list and its values) and link (array of uplink nodes): ['link' => [nodes array], 'content' => [eid, etitle, evalue], 'class' => '']
		  {
		   $content[2]['value'] = "Object link selection syntax error:<br>'$value[1]'";
		   $data['link'][] = ['content' => $content, 'class' => 'treeerror'];
		  }
	       continue; // Go to next uplink object search via $select
	      }

	  // Uplink object not found? Make virtual error node with error message as a content and continue
	  $uplinkoid = $query->fetch(PDO::FETCH_NUM);
	  $query->closeCursor();
	  if (!isset($uplinkoid[0]))
	     {
	      if ($cmd === 'TREE')
		  {
	           $content[2]['value'] = "Object link selection points to nonexistent object:<br>'$value[1]'";
		   $data['link'][] = ['content' => $content, 'class' => 'treeerror'];
		  }
	      continue;
	     }

	  // Check loop via uplink object id existence in $objects array that consists of object ids already in the tree. Continue if exists, otherwise remember uplink object id in $objects array
	  if (isset($client['objects'][$uplinkoid = $uplinkoid[0]]))
	     {
	      if ($cmd === 'TREE')
		  {
		   $content[2]['value'] = "Loop detected on link:<br>from remote node [object id'$oid']<br>to me [object id'$uplinkoid']!";
		   $data['link'][] = ['content' => $content, 'class' => 'treeerror'];
		  }
	      continue;
	     }

	  // Remember uplink object id for loop detection
	  $client['objects'][$uplinkoid] = true;

	  // Build tree element and define uplink node tree via recursive function call
	  $data['link'][] = ['link' => [], 'oid' => $uplinkoid];
	  $index = array_key_last($data['link']);
	  if (($result = CreateTree($db, $client, $uplinkoid, $data['link'][$index], $cmd)) && isset($client['treelastnode']))
	     {
	      DeleteTree($data['link'], $client['objects'], $index);
	      $data['link'] = [$data['link'][$index]];
	      $index = 0;
	     }
	  switch ($cmd)
		 {
		  case 'TABLE':
		       if ($result || !isset($client['treelastnode'])) GetObjectData($db, $client, $uplinkoid);
		       break;
		  case 'SEARCH':
		       if ($result || !isset($client['treelastnode'])) GetObjectSearchData($db, $client, $uplinkoid);
		       break;
		  case 'TREE':
		       $data['link'][$index] += ['content' => $content, 'class' => 'treeelement'];
		       GetTreeElementContent($db, $client, $data['link'][$index]['content'], $uplinkoid);
		       break;
		 }
	  if ($result) return true;
	 }
}

function GetObjectSearchData($db, &$client, $oid)
{
 if (!$client['limit']) return;

 try {
      $query = $db->prepare("SELECT $client[select] FROM (SELECT * FROM `data_$client[ODid]` WHERE id=$oid AND lastversion=1 AND version!=0) _ WHERE $client[selection]");
      $query->execute();
      $object = $query->fetchAll(PDO::FETCH_NUM);
      if (isset($object[0]))
	 {
	  $client['tree'][] = $object[0];
	  $client['limit'] --;
	 }
     }
 catch (PDOException $e) {}
}

function GetObjectData($db, &$client, $oid)
{
 try {
      $query = $db->prepare("SELECT $client[elementquery] FROM `data_$client[ODid]` WHERE id=$oid AND lastversion=1 AND version!=0");
      $query->execute();
      $object = $query->fetchAll(PDO::FETCH_ASSOC);
      if (isset($object[0])) $client['tree'][] = $object[0];
     }
 catch (PDOException $e) {}
}

function DeleteTree(&$tree, &$objects, $index = NULL)
{
 foreach ($tree as $key => $value)
	 {
	  if ($key === $index) continue;
	  unset($objects[$value['oid']]);
	  DeleteTree($value['link'], $objects);
	 }
}

function GetHeadId($db, &$client)
{
 $selection = preg_split("/\n/", $client['objectselection']);
 if (gettype($selection) !== 'array') return;

 unset($client['treelastnode']);
 if (isset($selection[1]))
    {
     $client['objectselection'] = $selection[1];
     $client['treelastnode'] = GetHead($db, $client);
    }

 $client['objectselection'] = $selection[0];
 return GetHead($db, $client);
}

function GetHead($db, &$client)
{
 // Execute object selection data from OD to get first found object to build the tree from
 try {
      $query = $db->prepare("SELECT id,version,lastversion FROM `data_$client[ODid]` $client[objectselection]");
      $query->execute();
     }
 catch (PDOException $e)
     {
      return;
     }

 // Get 1st found real object
 while ($head = $query->fetch(PDO::FETCH_ASSOC))
       if (isset($head['id']) && $head['lastversion'] === '1' && $head['version'] !== '0')
	  {
	   $query->closeCursor();
	   return $head['id'];
	  }
}

function LinkMatch(&$linknames, $linkprop)
{
 if (gettype($linkprop) !== 'string' || !$linkprop) return [];
 $links = []; // Array of [<remote element id>, <uplink object selection>]

 // Calculate matched link names (via '|' or '/') list to element link prop
 foreach (preg_split("/\n/", $linkprop) as $value)
	 {
	  if (!trim($value) || gettype($last = preg_split("/\|/", $value, 3)) !== 'array') continue; // Is linkprop line splited to 2 or 3 elements?
	  if (count($last) !== 3) continue; // All fields are defined
	  if (in_array(trim($last[0]), $linknames))
	     {
	      $links[] = [trim($last[1]), trim($last[2])]; // Check linprop link names to match view props link names
	      if (isset($linknames[''])) $linknames = [trim($last[0])]; // Reset linknames array to use $last[0] as 1st found link name for other object element links (for '/' divided names, with key '' set, only)
	     }
	 }

 // Return matched links result array
 return $links;
}

function GetObjectElementLinksArray($db, &$client, $oid)
{
 // Build a query for all elements to fetch their link and value props
 $query = '';
 foreach ($client['allelements'] as $eid => $element)
	 $query .= "$eid, JSON_UNQUOTE(JSON_EXTRACT(eid$eid, '$.link')), JSON_UNQUOTE(JSON_EXTRACT(eid$eid, '$.value')), ";
 if (!$query) return [];
 $query = substr($query, 0, -2);

 // Execute the query
 try {
      $query = $db->prepare("SELECT $query FROM `data_$client[ODid]` WHERE id=$oid AND lastversion=1 AND version!=0");
      $query->execute();
      $object = $query->fetchAll(PDO::FETCH_NUM);
     }
 catch (PDOException $e)
     {
      unset($object);
     }

 // Return result array of object all element link and value props
 if (isset($object[0][0])) return $object[0];
 return []; // No fetched object? Return empty array
}

function GetTreeElementContent($db, &$client, &$content, $oid)
{
 // Content is array of object elements: [<downlink node linked object element>, <local node linked object element>, <first layout element of local node>, <second..>]
 // Each array element consists of three props: element identificator, its title and value

 // First go through all elements in the layout and put them to the content
 foreach ($client['elementselection'] as $eid => $value)
	 if ($eid != 'direction') $content[] = ['id' => $eid, 'title' => GetElementTitle($eid, $client['allelements'])];

 // Make query string to select element values from DB
 $query = '';
 foreach ($content as $key => $e) if ($key)
	 {
	  if (!isset($e['id'])) $query .= 'NULL,';
	   elseif (array_search($e['id'], SERVICEELEMENTS) !== false) $query .= $e['id'].',';
	   elseif (!isset($client['allelements'][$e['id']])) $query .= 'NULL,';
	   else $query .= 'JSON_EXTRACT(eid'.$e['id'].", '$.value'),";
	 }

 // Select prepared elements above from object id $oid and put them to content array begining from index 1.
 try {
      $query = $db->prepare('SELECT '.substr($query, 0, -1)." FROM `data_$client[ODid]` WHERE lastversion=1 AND version!=0 AND id=$oid");
      $query->execute();
      foreach ($query->fetch(PDO::FETCH_NUM) as $key => $value) $value ? $content[$key + 1]['value'] = $value : $content[$key + 1]['value'] = '';
     }
 catch (PDOException $e)
     {
      lg($e);
     }
}

function GetElementTitle($eid, &$allelements)
{
 if (isset($allelements[$eid])) $title = $allelements[$eid]['element1']['data'];
  elseif (array_search($eid, SERVICEELEMENTS) !== false) $title = $eid;
  else  $title = "Unknown '$eid'";

 return $title;
}

function IsDirEmpty($dir)
{
 if (is_dir($dir)) foreach (scandir($dir) as $name) if ($name !== '.' && $name !== '..') return true;
 return false;
}

function GetObjectSelection($objectSelection, $params, $user, $anyway = false)
{
 // Check input paramValues array and add reserved :user parameter value
 if (gettype($objectSelection) != 'string' || ($objectSelection = trim($objectSelection)) === '') return DEFAULTOBJECTSELECTION;
 $i = -1;
 $len = strlen($objectSelection);
 if (gettype($params) != 'array') $params = [];
 $params[':user'] = $user;
 $isDialog = false;
 $objectSelectionNew = '';
 $objectSelectionParamsDialogProfiles = [];

 // Check $objectSelection every char and retrieve params in non-quoted substrings started with ':' and finished with space or another ':'
 while  (++$i <= $len)
     // Parameter delimiter char (single/double quote, colon or space) detected
     if ($i === $len || $objectSelection[$i] === '"' || $objectSelection[$i] === "'" || $objectSelection[$i] === ':' || $objectSelection[$i] === ' ' || $objectSelection[$i] === '\\' || $objectSelection[$i] === "\n")
	{
	 if (isset($newparam))
	 if (isset($params[$newparam])) // Object selection input parameter key does exist? Do code below
	    {
	     // Add appropriate dialog element (html <input>) for the new parameter with existing parameter data
	     $objectSelectionParamsDialogProfiles[$newparam] = ['head' => "\n".str_replace('_', ' ', substr($newparam, 1)).':', 'type' => 'text', 'data' => $params[$newparam]];
	     if (!$isDialog) $objectSelectionNew .= $params[$newparam]; // Insert appropriate pramater value to object selection
	    }
	  else // Otherwise dialog is required, so add appropriate dialog element (html <input>) for the new parameter with empty data
	    {
	     $objectSelectionParamsDialogProfiles[$newparam] = ['head' => "\n".str_replace('_', ' ', substr($newparam, 1)).':', 'type' => 'text', 'data' => ''];
	     $isDialog = true;
	    }
	 if ($i === $len) break; // Break in case of end line
	 $newparam = NULL;	 // No new paramter for default
	 $objectSelection[$i] === ':' ? $newparam = ':' : $objectSelectionNew .= $objectSelection[$i]; // Char ':' starts new param, otherwise just record current char to the object selection string
	}
      else if (isset($newparam)) $newparam .= $objectSelection[$i]; // Otherwise: if new parameter is being setting - record current char
      else $objectSelectionNew .= $objectSelection[$i]; // Otherwise record current char to the object selection string

 //  In case of no dialog (or anyway flag set) - return object selection string
 if (!$isDialog || $anyway) return $objectSelectionNew;

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
