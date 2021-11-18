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
		    'element3' => ['type' => 'text', 'head' => 'Database size limit in MBytes. Undefined or zero value - no limit.', 'data' => ''],
		    'element4' => ['type' => 'text', 'head' => 'Database object count limit. Undefined or zero value - no limit.', 'data' => '', 'line' => ''],
		    'element6' => ['type' => 'radio', 'data' => "User/groups allowed list to change 'Database' section|+Disallowed list (allowed for others)|"],
		    'element7' => ['type' => 'textarea', 'data' => ''],
		    'element8' => ['type' => 'radio', 'data' => "User/groups list allowed to change 'Element' section|+Disallowed list (allowed for others)|"],
		    'element9' => ['type' => 'textarea', 'data' => ''],
		    'element10' => ['type' => 'radio', 'data' => "User/groups list allowed to change 'View' section|+Disallowed list (allowed for others)|"],
		    'element11' => ['type' => 'textarea', 'data' => ''],
		    'element12' => ['type' => 'radio', 'data' => "User/groups list allowed to change 'Rule' section|+Disallowed list (allowed for others)|"],
		    'element13' => ['type' => 'textarea', 'data' => '', 'line' => '']
		   ];

 $newElement	 = ['element1' => ['type' => 'textarea', 'head' => 'Name', 'data' => '', 'id' => '1', 'help' => 'Element name is used as a default element header text on object view element header navigation.<br>To remove element - set name, description and all handlers empty.'],
		    'element2' => ['type' => 'textarea', 'head' => 'Description', 'data' => '', 'line' => '', 'help' => 'Element description is displayed as a hint on object view element header navigation for default.<br>Describe here element usage and its possible values.'],
		    'element3' => ['type' => 'checkbox', 'head' => 'Element type', 'data' => 'unique|', 'line' => '', 'help' => "Unique element type guarantees element value uniqueness among all objects.<br>Element type cannot be changed after element creation."],
		    'element4' => ['type' => 'text', 'head' => "Handler command lines to process application events below", 'label' => "Handler for 'INIT' event:", 'data' => ''],
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

 $newView	 = ['element1' => ['type' => 'text', 'head' => 'View name', 'data' => '', 'id' => '1', 'help' => "View name may be changed, but if renamed view name already exists, changes are not applied.<br>So name 'New view' cannot be set as it is used as an option to create new views.<br>Empty view name removes the view.<br>In addition, symbol '_' as a first character in a view name string keeps unnecessary views<br>off sidebar, so these hidden views can be called from element handlers only."],
		    'element2' => ['type' => 'textarea', 'head' => 'Description', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'radio', 'head' => 'Template', 'data' => '+Table|Tree|Map|', 'help' => "Select object view type from 'table' (displays objects in a form of a table),<br>'scheme' (displays object hierarchy built on object selection link name),<br>'map' (displays objects on the geographic map)"],
		    'element4' => ['type' => 'textarea', 'head' => 'Object selection', 'help' => 'Object selection is a part of the sql query string, that selects objects for the view.<br>Empty string selection - all objects, error selection - no objects.<br>See appropriate help section for details.', 'data' => ''],
		    'element5' => ['type' => 'text', 'label' => 'Link name', 'data' => '', 'line' => ''],
		    'element6' => ['type' => 'textarea', 'head' => 'Element layout', 'data' => '', 'line' => '', 'help' => 'Element layout defines what elements should be displayed and how for the selected template.<br>Empty layout is a default behaviour, see appropriate help section for details.'],
		    'element7' => ['type' => 'textarea', 'head' => 'Scheduler', 'data' => '', 'line' => '', 'help' => "Scheduler is an instruction list (one per line), each instruction executes command line<br>at specified datetime for specified element for all objects of the view.<br>Instruction represents itself one by one space separated args in next format:<br>&lt;minute 0-59> &lt;hour 0-23> &lt;mday 1-31> &lt;month 1-12> &lt;wday 0-7> &lt;element id number> &lt;command line><br>See 'Database Configuration' help section for details."],
		    'element8' => ['type' => 'radio', 'data' => "User/groups list allowed to read this view|+Disallowed list (allowed for others)|"],
		    'element9' => ['type' => 'textarea', 'data' => ''],
		    'element10' => ['type' => 'radio', 'data' => "User/groups list allowed to change this view objects|+Disallowed list (allowed for others)|"],
		    'element11' => ['type' => 'textarea', 'data' => '']
		   ];

 $newRule	 = ['element1' => ['type' => 'text', 'head' => 'Name', 'data' => '', 'help' => "Rule profile name. It may be changed, but if renamed profile already exists, changes are not applied.<br>So name 'New rule' cannot be set as it is used as an option to create new rules.<br>Empty profile name removes the rule."],
		    'element2' => ['type' => 'textarea', 'head' => 'Rule message', 'data' => '', 'line' => '', 'help' => 'Rule message is a match case log message displayed in the dialog box.<br>Message text element id number in a figure brackets (example: {1}) retreives appropriate element name.'],
		    'element3' => ['type' => 'select-one', 'head' => 'Rule action', 'data' => '+Accept|Reject|', 'line' => '', 'help' => "'Accept' action applies object changes made by operation, 'Reject' cancels all changes."],
		    'element4' => ['type' => 'checkbox', 'head' => 'Rule apply operation', 'data' => 'Add object|Delete object|Change object|', 'line' => ''],
		    'element5' => ['type' => 'textarea', 'head' => 'Preprocessing rule', 'data' => '', 'help' => "Object instances before and after CRUD operations (add, delete, change) are passed to the analyzer and tested<br>on all rule profiles in alphabetical order until the match is found for both pre and post rules. When a match<br>is found, the action corresponding to the matching rule profile is performed. Default action is accept.<br>Accept action applies changes, while reject action cancels all changes made by the operation.<br><br>Rule test is a simple SQL query selection, so non empty result of that selection - match is found, empty<br>result - no match. Query format:<br>'SELECT .. FROM `OD` WHERE id=<object id> AND version=<version number> AND <(pre|post)-processing rule>'<br>Version number defines object version before (for pre-processing rule) and after (for post-processing rule)<br>operation. Also both rules may contain a parameter :user, that is replaced with the actual username (initiated<br>the operation) in the query string. Note that pre-processing rule for 'add object' operation is ignored - no<br>object before operation, so nothing to check. Empty or error rules are match case, but error rule displays<br>error message instead of a rule message.<br><br>Simple example: pre-processing rule JSON_EXTRACT(eid1, '$.value')='root' with the action 'reject' and rule<br>apply operation 'delete object' prevents root user removal. Example query will look like:<br>SELECT .. FROM `data_1` WHERE id='4' AND version='1' AND JSON_EXTRACT(eid1, '$.value')='root'.<br>Next example with both rules empty and reject action for all operations freezes the database, so all changes<br>are rejected.<br>Another example: first profile with action accept preprocessing rule owner=':user' and second profile<br>reject action with both empty rules allowes to change self-created objects only."],
		    'element6' => ['type' => 'textarea', 'head' => 'Postprocessing rule', 'data' => '', 'line' => '', 'help' => "Object instances before and after CRUD operations (add, delete, change) are passed to the analyzer and tested<br>on all rule profiles in alphabetical order until the match is found for both pre and post rules. When a match<br>is found, the action corresponding to the matching rule profile is performed. Default action is accept.<br>Accept action applies changes, while reject action cancels all changes made by the operation.<br><br>Rule test is a simple SQL query selection, so non empty result of that selection - match is found, empty<br>result - no match. Query format:<br>'SELECT .. FROM `OD` WHERE id=<object id> AND version=<version number> AND <(pre|post)-processing rule>'<br>Version number defines object version before (for pre-processing rule) and after (for post-processing rule)<br>operation. Also both rules may contain a parameter :user, that is replaced with the actual username (initiated<br>the operation) in the query string. Note that pre-processing rule for 'add object' operation is ignored - no<br>object before operation, so nothing to check. Empty or error rules are match case, but error rule displays<br>error message instead of a rule message.<br><br>Simple example: pre-processing rule JSON_EXTRACT(eid1, '$.value')='root' with the action 'reject' and rule<br>apply operation 'delete object' prevents root user removal. Example query will look like:<br>SELECT .. FROM `data_1` WHERE id='4' AND version='1' AND JSON_EXTRACT(eid1, '$.value')='root'.<br>Next example with both rules empty and reject action for all operations freezes the database, so all changes<br>are rejected.<br>Another example: first profile with action accept preprocessing rule owner=':user' and second profile<br>reject action with both empty rules allowes to change self-created objects only."],
		    'element7' => ['type' => 'checkbox', 'data' => '+Log rule message|', 'line' => '', 'help' => '']
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
 if ($client['ODid'] != '2' && isset($ruleresult['log'])) LogMessage($db, $client, $ruleresult['log']);
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

      $ruleresult = ProcessRules($db, $client, $version, 0, 'Delete object');
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

function ProcessRules($db, &$client, $preversion, $postversion, $operation)
{
 // Get rule profile json data
 $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.Rule') FROM $ WHERE id='$client[ODid]'");
 $query->execute();
 $Rules = $query->fetchAll(PDO::FETCH_NUM);

 // Move on. Return default action in case of empty rule profiles or decoding error
 if (!isset($Rules[0][0]) || gettype($Rules = json_decode($Rules[0][0], true)) != 'array') return ['action' => 'Accept', 'message' => ''];
 unset($Rules['New rule']); // Exlude service 'New rule' profile

 // Process non empty expression rules one by one
 foreach ($Rules as $key => $value)
	 {
	  if (strpos($value['element4']['data'], '+'.$operation) === false) continue; // No apply operation selected? Continue
	  strpos($value['element3']['data'], '+Accept') === false ? $action = 'Reject' : $action = 'Accept'; // Set accept/reject action
	  $message = ParseRuleMsgElementId($client['allelements'], trim($value['element2']['data'])); // and rule message

	  if (gettype($result = CheckRule($db, $client, trim($value['element5']['data']), $preversion)) === 'string')
	     return ['action' => $action, 'message' => $result, 'log' => $result]; // Return action in case of error (match case)
	  if ($result === false) continue; // Continue to next rule in case of no match

	  if (gettype($result = CheckRule($db, $client, trim($value['element6']['data']), $postversion)) === 'string')
	     return ['action' => $action, 'message' => $result, 'log' => $result]; // Return action in case of error (match case)
	  if ($result === false) continue; // Continue to next rule in case of no match
	
	  // Rule match occured. Return its action
	  $output = ['action' => $action, 'message' => $message];
	  if (substr($value['element7']['data'], 0, 1) === '+') $output['log'] = "Database rule '$key' match, action: '$action', message: '$message'"; // Log rule message in case of approprate checkbox is set
	  return $output;
	 }

 // Return default action
 return ['action' => 'Accept', 'message' => ''];
}

// Function returns next rule test results - true (match case), false (no match case) and string (pdo exception case query error)
function CheckRule($db, &$client, $rule, $version)
{
 if (!isset($rule, $version) || $rule === '' || $version === '') return true; // Unset or empty rule or version - return true (match case)
 $rule = str_replace(':user', $client['auth'], $rule); // Replace key :user with the actual username inited add/delete/change operation

 try {
      $query = $db->prepare("SELECT id FROM `data_$client[ODid]` WHERE id=$client[oId] AND version=$version AND $rule");
      $query->execute();
     }
 catch (PDOException $e)
     {
      return 'Rule error: '.$e->getMessage();
     }

 if (isset($query->fetchAll(PDO::FETCH_NUM)[0][0])) return true; // Non empty result? Return true (match case)
 return false; // Else return false (no match)
}

function SetLayoutProperties(&$client)
{
 // +-----------+-------------------------+------------------+----------------------------+
 // |   \       |                         |                  |                            |
 // |    \ oid  | 1|2|4..|*|              |                  |                            |
 // |     \     | expression (o, e, n, q) |      empty       |           unset            |
 // |  eid \    |                         | (eid is ignored) |      (eid is ignored)      |
 // |       \   |                         |                  |                            |
 // +-----------+-------------------------+------------------+----------------------------+
 // |           |                         |                  |                            |
 // |id         |  x (o, e, n, q),        |                  | table attributes           |
 // |owner      |  y (o, e, n, q),        |                  | and direction              |
 // |datetime   |  [value,                | [style, hiderow] | or                         |
 // |version    |  style,                 | (for undefined   | virtual elements:          |
 // |lastversion|  hint,                  | cell with no any | x (n),                     |
 // |1,2..      |  description,           | object element   | y (n),                     |
 // |*          |  event,                 | placed in)       | value,                     |
 // |           |  hidecol, hiderow]      |                  | [style, hint, description] |
 // |           |                         |                  |                            |
 // +-----------+-------------------------+------------------+----------------------------+

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
		   if (gettype($value) !== 'string' || array_search($key, ['x', 'y', 'style', 'value', 'hint', 'description', 'event']) === false)
		      unset($arr[$key]);
	      $layout['virtual'][] = $arr;
	      continue;
	     }
	   else
	     {
	      unset($arr['eid']);
	      $layout['table'] = $arr + $layout['table'];
	      continue;
	     }

	  // Retrieve correct values only
	  foreach ($arr as $key => $value)
		  if (gettype($value) !== 'string' || array_search($key, ['eid', 'oid', 'x', 'y', 'style', 'value', 'hint', 'description', 'event', 'hiderow', 'hidecol']) === false)
		     {
		      unset($arr[$key]);
		     }
		   else
		     {
		      if ($key === 'eid' || $key === 'oid' || $key === 'x' || $key === 'y' || $key === 'style') $arr[$key] = trim($value);
		     }
	  if (!count($arr)) continue;

	  // Check unset oid for virtual element (with x, y and value properties set)
	  if (!isset($arr['oid']))
	     {
	      unset($arr['eid']);
	      $layout['virtual'][] = $arr;
	      continue;
	     }

	  // Check oid for empty value treated as underfined (style for cell with no any object element placed in)
	  if (($oid = $arr['oid']) === '')
	     {
	      if (isset($arr['style'])) $layout['undefined']['style'] = $arr['style'];
	      if (isset($arr['hidecol'])) $layout['undefined']['hidecol'] = $arr['hidecol'];
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
		       if (!isset($src['description'])) $src['description'] = array_search($eid, SERVICEELEMENTS) === false ? $client['allelements'][$eid]['element2']['data'] : '';
		      }
		   if ($oidnum === NEWOBJECTID)
		      {
		       if (!isset($src['value'])) $src['value'] = '';
		       if (!isset($src['hint'])) $src['hint'] = '';
		       if (!isset($src['description'])) $src['description'] = '';
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
 $query = $db->prepare("SELECT JSON_EXTRACT(eid1, '$.groups') FROM `data_1` WHERE id=:id AND lastversion=1 AND version!=0");
 $query->execute([':id' => $id]);
 $groups = $query->fetchAll(PDO::FETCH_NUM);
 if (!isset($groups[0][0])) return [];
 return UnsetEmptyArrayElements(explode("\\n", substr($groups[0][0], 1, -1)));
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
     if ($i === $len || $objectSelection[$i] === '"' || $objectSelection[$i] === "'" || $objectSelection[$i] === ':' || $objectSelection[$i] === ' ' || $objectSelection[$i] === '\\')
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
 unset($params[':user']); // Is it needable?
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

 $query = $db->prepare("SELECT id,odname FROM `$`"); // Select OD list - ids and names
 $query->execute();
 foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $value)
	 {
	  $name = $value['odname'];
	  $id = $value['id'];
	  $sidebar[$id] = ['name' => $name, 'view' => []]; // Make sidebar based on OD id array. Array values - OD name and view list array
	  // Extract JSON OV structure
	  $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE odname='$name'");
	  $query->execute();
	  foreach (json_decode($query->fetch(PDO::FETCH_NUM)[0], true) as $key => $View) if ($key != 'New view')
		  {
		   $count = count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $View['element9']['data'])), "strcmp"));
		   $pos = strpos($View['element8']['data'], '+');
		   if (($count && $pos) || (!$count && !$pos)) continue; // Make OV off sidebar due to disallowed user/group intersection between requesting user and view display permissions ($View['element8-9']['data'])
		   $sidebar[$id]['view'][$View['element1']['id']] = $View['element1']['data']; // Make OV based on its ids array and set active OD view below
		   if ($id === $client['ODid'] && $View['element1']['id'] === $client['OVid']) $sidebar[$id]['active'] = $client['OVid'];
		  }
	 }
 return $sidebar;
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
     if (!isset($client['elementselection'], $client['objectselection'], $client['viewtype']) && ($output['error'] = "Object View '$client[OV]' of Database '$client[OD]' not found!")) return;

     // List is empty or includes '*' chars for a 'Table' view?
     // Set up default list for all elements: {"eid": "every", "oid": "title|0|newobj", "x": "0..", "y": "0|n"}
     if ($client['viewtype'] === 'Table ')
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
		  $client['elementselection'] .= '{"eid": "'.$id.'", "oid": "*", "x": "'.strval($x).'", "y": "'.$startline.'"}'."\n";
		  $x++;
		 }
	}

     if ($client['viewtype'] === 'Table')
	{
	 $layout = '';
	 foreach (preg_split("/\n/", $client['elementselection']) as $json)
		 {
		  if (gettype(json_decode($json, true)) === 'array')
		     {
		      $layout .= $json."\n";
		      continue;
		     }
		  $startline = $json === ' ' ? 'n+2' : 'n+1';
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
     // Check object existence, uncommented query is more faster (cause no load for all object selection), but ignores LIMIT sql option
     //$query = $db->prepare("SELECT id FROM (SELECT id,lastversion,version FROM `data_$client[ODid]` $client[objectselection]) _ WHERE id=$client[oId] AND lastversion=1 AND version!=0");
     $query = $db->prepare("SELECT id FROM (SELECT * FROM `data_$client[ODid]` WHERE id=$client[oId] AND lastversion=1 AND version!=0) _ $client[objectselection]");
     $query->execute();
     if (!isset($query->fetchAll(PDO::FETCH_NUM)[0][0]) && ($output['alert'] = "Please refresh, specified object (id=$client[oId]) doesn't exist in the view!")) return;
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
     if (count($View = $query->fetchAll(PDO::FETCH_NUM)) == 0 && ($output['error'] = "Database '$client[OD]' Object View '$client[OV]' not found!")) return;

     $View = json_decode($View[0][0], true)[$client['OV']];	// Set current view array data
     $groups = getUserGroups($db, $client['uid']);		// Get current user group list
     $groups[] = $client['auth'];				// and add username at the end of array

     // Check on 'display' permissions
     $count = count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $View['element9']['data'])), "strcmp"));
     $pos = strpos($View['element8']['data'], '+');
     if ((($count && $pos) || (!$count && !$pos)) && ($output['error'] = "OV read/write operations are not allowed!")) return;

     // Check 'writable' permissions for non-CALL event
     if ($client['cmd'] !== 'CALL')
	{
	 $count = count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $View['element11']['data'])), "strcmp"));
	 $pos = strpos($View['element10']['data'], '+');
	 if ((($count && $pos) || (!$count && !$pos)) && ($output['alert'] = "OV write operation are not allowed!")) return;
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

function QueueViewCall($db, $socket, $id, &$message)
{
 // Put request to the queue sql table that will be checked by view.php after client ajax request
 $query = $db->prepare("INSERT INTO `$$$` (id,client) VALUES (:id,:message)");
 $query->execute([':id' => $id, ':message' => $message]);
 if ($socket) fwrite($socket, encode($message));
}
