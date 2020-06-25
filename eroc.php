<?php

const DATABASENAME			= 'OE7';
const MAXOBJECTS			= 100000;
const ODSTRINGMAXCHAR			= 32;
const HANDLERDIR			= 'handlers';
const ELEMENTPROFILENAMEMAXCHAR		= 16;
const ELEMENTPROFILENAMEADDSTRING	= 'element id';
const UNIQKEYCHARLENGTH			= 300;
const UNIQELEMENTTYPE			= '+unique|';
const NEWOBJECTID			= 1;
const TITLEOBJECTID			= 2;
const STARTOBJECTID			= 3;

error_reporting(E_ALL);
$db = new PDO('mysql:host=localhost;dbname='.DATABASENAME, 'root', '123');
$db->exec("SET NAMES UTF8");
$db->exec("ALTER DATABASE ".DATABASENAME." CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function rmSQLinjectionChars($str) // Function removes dangerous chars such as: ; ' " %
{
 return str_replace(';', '', str_replace('"', '', str_replace("'", '', str_replace("%", '', $str))));
}

function loog($arg) // Function saves input $arg to error.log
{
 file_put_contents('error.log', var_export($arg, true), FILE_APPEND);
 file_put_contents('error.log', "\n-------------------------------END LOG-------------------------------\n", FILE_APPEND);
}

function adjustODProperties($data, $db, $id)
{
 global $newElement, $newView, $newRule;
 
 // Is incoming arg array?
 if (!is_array($data)) return NULL;

 // Database section handle
 if (!isset($db) || !isset($id)) return NULL; // No db defined or OD identificator?
 if (!isset($data['dialog']['Database']['Properties']['element1']['data'])) return NULL; // Database name exists?
 if ($data['dialog']['Database']['Properties']['element1']['data'] == '') return NULL; // Empty database name?
 
 // New element section handle
 if (!isset($data['dialog']['Element']['New element'])) return NULL;
 $eidmax = 0;
 foreach ($data['dialog']['Element'] as $key => $value)
      if (!isset($value['element1']['data']) || !isset($value['element2']['data']) || !isset($value['element4']['data'])) // Dialog 'Element' pad corrupted?
	 {
	  return NULL;
	 }
       else if ($key != 'New element') // Remove empty elements for non new element profile
	 {
	  $eid = intval(substr($key, strrpos($key, ELEMENTPROFILENAMEADDSTRING) + strlen(ELEMENTPROFILENAMEADDSTRING)));  // Calculate current element id
	  $data['dialog']['Element'][$key]['element4']['data'] = trim($data['dialog']['Element'][$key]['element4']['data']);
	  if ($eid > $eidmax) $eidmax = $eid; // Calculate max element id
	  if ($value['element1']['data'] === '' && $value['element2']['data'] === '' && $value['element4']['data'] === '')
	     {
	      $eid = strval($eid);
	      $db->beginTransaction();
	      $query = $db->prepare("ALTER TABLE `data_$id` DROP COLUMN eid$eid");
	      $query->execute();
	      if ($value['element3']['data'] === UNIQELEMENTTYPE)
		 {
		  $query = $db->prepare("ALTER TABLE `uniq_$id` DROP COLUMN eid$eid");
		  $query->execute();
		 }
	      unset($data['dialog']['Element'][$key]);		// Element name, description and handler file are empty? Remove element.
	      $db->commit();
	     }
	   else
	     {
	      $name = $value['element1']['data'];
	      if (strlen($name) > ELEMENTPROFILENAMEMAXCHAR) $name = substr($name, 0, ELEMENTPROFILENAMEMAXCHAR - 2).'..';
	      $name .= ' - '.ELEMENTPROFILENAMEADDSTRING.$eid;
	      if ($name != $key)
	         {
		  $data['dialog']['Element'][$name] = $data['dialog']['Element'][$key];
	          unset($data['dialog']['Element'][$key]);
		 }
	     }
	 }
 // New element have been set? Create it
 if ($data['dialog']['Element']['New element']['element1']['data'] != '' || $data['dialog']['Element']['New element']['element2']['data'] != '' || $data['dialog']['Element']['New element']['element4']['data'] != '')
    {
     $data['dialog']['Element']['New element']['element4']['data'] = trim($data['dialog']['Element']['New element']['element4']['data']);
     $data['dialog']['Element']['New element']['element3']['readonly'] = '';
     $data['dialog']['Element']['New element']['element3']['head'] .= ' (readonly)';
     $name = $data['dialog']['Element']['New element']['element1']['data'];
     if (strlen($name) > ELEMENTPROFILENAMEMAXCHAR) $name = substr($name, 0, ELEMENTPROFILENAMEMAXCHAR - 2).'..';
     $eid = strval($eidmax + 1);
     $id = strval($id);
     // Add object element column to database
     $db->beginTransaction();
     $query = $db->prepare("ALTER TABLE `data_$id` ADD eid$eid JSON");
     $query->execute();
     if ($data['dialog']['Element']['New element']['element3']['data'] === UNIQELEMENTTYPE)
        {
         $query = $db->prepare("ALTER TABLE `uniq_$id` ADD eid$eid TEXT, ADD UNIQUE(eid$eid(".UNIQKEYCHARLENGTH."))");
	 $query->execute();
	}
     $data['dialog']['Element'][$name.' - '.ELEMENTPROFILENAMEADDSTRING.$eid] = $data['dialog']['Element']['New element'];
     $data['dialog']['Element']['New element'] = $newElement;
     $db->commit();
    }
    
 // New view section handle
 if (!isset($data['dialog']['View']['New view']['element1']['data'])) return NULL;
 foreach ($data['dialog']['View'] as $key => $value)
      if (!isset($value['element1']['data']) || !isset($value['element2']['data'])) return NULL; // Dialog 'View' pad corrupted?
       else if ($value['element1']['data'] === '') unset($data['dialog']['View'][$key]);	 // View name is empty? Remove it
       else if (isset($data['dialog']['View'][$value['element1']['data']])) $data['dialog']['View'][$key]['element1']['data'] = $key; // New view name already exists? Discard changes
       else
	 {
	  $data['dialog']['View'][$value['element1']['data']] = $data['dialog']['View'][$key];	// Otherwise create new view with new view name
	  unset($data['dialog']['View'][$key]);							// and remove old view
	 }
 $data['dialog']['View']['New view'] = $newView; // Reset 'New view' profile to default

 // New rule section handle
 if (!isset($data['dialog']['Rule']['New rule']['element1']['data'])) return NULL;
 foreach ($data['dialog']['Rule'] as $key => $value)
      if (!isset($value['element1']['data']) || !isset($value['element2']['data'])) return NULL; // Dialog 'Rule' pad corrupted?
       else if ($value['element1']['data'] === '') unset($data['dialog']['Rule'][$key]);	 // Rule name is empty? Remove it
       else if (isset($data['dialog']['Rule'][$value['element1']['data']])) $data['dialog']['Rule'][$key]['element1']['data'] = $key; // New rule name already exists? Discard changes
       else
	 {
	  $data['dialog']['Rule'][$value['element1']['data']] = $data['dialog']['Rule'][$key];	// Otherwise create new rule with new rule name
	  unset($data['dialog']['Rule'][$key]);							// and remove old rule
	 }
 $data['dialog']['Rule']['New rule'] = $newRule; // Reset 'New rule' profile to default
 
 // Return result data
 $data['title'] = 'Edit Object Database Structure';
 if (!isset($data['flags'])) $data['flags'] = [];
 $data['flags']['ok'] = 'SAVE';
 return $data;
}					

function initNewODDialogElements()
{
 global $newProperties, $newPermissions, $newElement, $newView, $newRule;
 
 $newProperties  = ['element1' => ['type' => 'text', 'head' => 'Database name', 'data' => '', 'line' => '', 'help' => "To remove database without recovery - set empty database name string and its description.<br>Remove all elements (see 'Element' tab) also."],
		    'element2' => ['type' => 'textarea', 'head' => 'Database description', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'text', 'head' => 'Database size limit in MBytes. Emtpy, undefined or zero value - no limit.', 'data' => '', 'line' => ''],
		    'element4' => ['type' => 'text', 'head' => 'Database object count limit. Emtpy, undefined or zero value - no limit.', 'data' => '', 'line' => ''],
		    'element5' => ['type' => 'text', 'head' => 'Max object versions in range 0-65535. Emtpy or undefined string - zero value', 'data' => '', 'line' => '', 'help' => 'Each object has some instances (versions) beginning with version number 1.<br>Once some object data has been changed, its version is incremented by one. <br>Max version value limits object max possible stored instances. Values description:<br>0 - no object data versions stored at all, only one (last) version<br>1 - only last version stored also, but deleted objects remain in database (marked by zero version)<br>2 - any object has two versions stored<br>3 - any object has three versions stored<br>4 - ...<br><br>Once database created, this value can be increased or redused. Reducing max version number<br>has two options - first or last versions of each object will be removed from the database.']];
		    
 $newPermissions = ['element1' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element2' => ['type' => 'textarea', 'head' => 'List of users and groups (one by line) allowed or disallowed (depending on list type above) to add new databases or edit its properties:', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element4' => ['type' => 'textarea', 'head' => 'List of users and groups (one by line) allowed or disallowed (depending on list type above) to add/edit object element properties:', 'data' => '', 'line' => ''],
		    'element5' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element6' => ['type' => 'textarea', 'head' => 'List of users and groups (one by line) allowed or disallowed (depending on list type above) to add/edit object view properties:', 'data' => '', 'line' => ''],
		    'element7' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element8' => ['type' => 'textarea', 'head' => 'List of users and groups (one by line) allowed or disallowed (depending on list type above) to add/edit database rules:', 'data' => '', 'line' => '']];

 $newElement	 = ['element1' => ['type' => 'textarea', 'head' => 'Element title to display in object view as a header', 'data' => '', 'line' => '', 'help' => 'To remove object element - set empty element header, description and handler file'],
		    'element2' => ['type' => 'textarea', 'head' => 'Element description', 'data' => '', 'line' => '', 'help' => 'Specified description is displayed as a hint on object view element headers navigation.<br>It is used to describe element purpose and its possible values.'],
		    'element3' => ['type' => 'checkbox', 'head' => 'Element type', 'data' => 'unique', 'line' => '', 'help' => "Unique element type guarantees element value uniqueness among all objects.<br>Element type cannot be changed after element creation."],
		    'element4' => ['type' => 'text', 'head' => 'Server side element event handler file that processes incoming user defined events (see event section below):', 'data' => '', 'line' => ''],
		    'element5' => ['type' => 'textarea', 'head' => 'JSON format event list', 'data' => '', 'line' => '', 'help' => 'Event JSON string (one per line) is a JSON to pass to the element handler as an input argument<br>when specified event occurs. JSONs properties:<br>"event" - event to be processed by the handler, JSONs with undefined event are ignored<br>"user" - user initiated event (automatically set by controller)<br>"eid" - element id (automatically set by controller)<br>"header" - element header (automatically set by controller)<br>Plus any user defined property can be used - its string values are sent to the handler without changes<br>with one exception - JSON formated value is replaced by object element JSON data. Format of the value:<br>{"OD": "&lt;Object Database>", "OV": "&lt;Object View>", "oid": "&lt;Object Id>", "eid": "&lt;Element Id", "any prop": "..."}<br>"any prop" - at least one custom property. Its value points to specified by OD/OV/eid/oid<br>element JSON data property to be retrieved. In case of "OD", "OV", "oId" or "eId" omitted -<br>current object database/view and object/element id values are used.<br>In the example below handler for element id 2 on mouse double click event gets JSON<br>with two properties. First property value is "test", second value -<br>json element data property "count" value of current object element identificator 1:<br>{ "event": "DBLCLICK", "abc": "test", "def": {"eid": "1", "xyz": "count", } }'],
		    'element6' => ['type' => 'textarea', 'head' => 'Element scheduler', 'data' => '', 'line' => '', 'help' => "Each element scheduler string (one per line) executes its handler &lt;count> times starting at<br>specified date/time and represents itself one by one space separated args in next format:<br>&lt;minute> &lt;hour> &lt;mday> &lt;month> &lt;wday> &lt;event> &lt;event data> &lt;count><br>See crontab file *nix manual page for date/time args. Zero &lt;count> - infinite calls count.<br>Scheduled call emulates mouse/keyboard events (DBLCLICK and KEYPRESS) with specified<br>&lt;event data> (for KEYPRESS only) and passes 'system' user as an user initiated<br>specified event. Any undefined arg - no call."]];
	
 $newView	 = ['element1' => ['type' => 'text', 'head' => 'Object View name', 'data' => '', 'line' => '', 'help' => "View name can be changed, but if it already exists, changes won't be applied.<br>So view name 'New view' can't be set as it is used as a name for new views creation.<br>To remove object view - set empty object view name string."],
		    'element2' => ['type' => 'textarea', 'head' => 'Object View description', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'textarea', 'head' => 'Object selection expression. Empty string selects all objects, error string - no objects.', 'data' => '', 'line' => ''],
		    'element4' => ['type' => 'radio', 'head' => 'Object view type', 'data' => '+Table|Scheme|Graph|Piechart|Map', 'line' => '', 'help' => "Select object view type from 'table' (displays objects in a form of a table),<br>'scheme' (displays object hierarchy built on uplink and downlink property),<br>'graph' (displays object graphic with one element on 'X' axis, other on 'Y'),<br>'piechart' (displays object statistic on the piechart) and<br>'map' (displays objects on the geographic map)"],
		    'element5' => ['type' => 'textarea', 'head' => 'Element selection expression. Defines what elements should be displayed and how.', 'data' => '', 'line' => ''],
		    'element6' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element7' => ['type' => 'textarea', 'head' => 'List of users and groups (one by line) allowed or disallowed (depending on list type above) to have this OV on the sidebar list, so able to select it:', 'data' => '', 'line' => ''],
		    'element8' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element9' => ['type' => 'textarea', 'head' => 'List of users and groups (one by line) allowed or disallowed (depending on list type above) to add/edit/delete objects:', 'data' => '', 'line' => '']];
							  
 $newRule	 = ['element1' => ['type' => 'text', 'head' => 'Rule name', 'data' => '', 'readonl' => '', 'line' => '', 'help' => "Rule name is displayed as title on the dialog box.<br>Rule name can be changed, but if it already exists, changes won't be applied.<br>So rule name 'New rule' can't be set as it is used as a name for new rules creation.<br>To remove the rule - set rule name to empty string."],
		    'element2' => ['type' => 'textarea', 'head' => 'Rule message', 'data' => '', 'line' => '', 'help' => 'Rule message is match case log message displayed in dialog box.<br>Object element id in figure {#id} or square [#id] brackets retreives<br>appropriate element id value or element id title respectively.<br>Escape character is "\".'],
		    'element3' => ['type' => 'select-one', 'head' => 'Rule action', 'data' => 'No action|Warning|Confirm|Reject|', 'line' => '', 'help' => "All actions shows up dialog box with rule message inside.<br>'Warning' action warns user and apply the changes.<br>'Reject' does the same, but cancels the changes with no chance to keep them.<br>'Confirm' asks wether keep it or reject."],
		    'element4' => ['type' => 'checkbox', 'head' => 'Log the message', 'data' => 'Log', 'line' => ''],
		    'element5' => ['type' => 'textarea', 'head' => 'Rule expression', 'data' => '', 'line' => '', 'help' => 'Empty or error expression does nothing']];
}

function createDefaultDatabases($db)
{
 $query = $db->prepare("CREATE TABLE IF NOT EXISTS `$` (id MEDIUMINT NOT NULL AUTO_INCREMENT, odname CHAR(64) NOT NULL, odprops JSON, UNIQUE(odname), PRIMARY KEY (id)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $query->execute();
}

function getODVNamesForSidebar($db)
{
	$arr = [];
	$query = $db->prepare("SELECT odname FROM `$`");
	$query->execute();
	foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $value)
			 {
			 $arr[$value['odname']] = [];
			 $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE odname='$value[odname]'");
			 $query->execute();
			 foreach (json_decode($query->fetch(PDO::FETCH_NUM)[0], 1) as $key => $v) if ($key != 'New view') $arr[$value['odname']][$key] = '';
			}
 return $arr;
}

function cutKeys($arr, $keys) // Function cuts all keys of $array except of keys defined in $keys array element values
{
 $result = [];
 foreach ($keys as $value)
	 if (key_exists($value, $arr)) $result[$value] = $arr[$value];
 return $result;
}

function mergeStyleRules($rules)
{
 $resultArray = [];
 $resultStyle = '';

 foreach($rules as $value) // Iterate all args
	if (isset($value) && gettype($value) === 'string') // Value is set and is string?
	   foreach (preg_split('/;/', $value) as $rule) // Split current rule collection by ';' char
		   if (($pos = strpos($rule, ':')) > 0 && strlen($rule) > $pos + 1) // Some chars before and after ':'?
		      $resultArray[trim(substr($rule, 0, $pos))] = substr($rule, $pos + 1); // Record rule to $resultArray

 foreach ($resultArray as $key => $values) $resultStyle .= $key.':'.$resultArray[$key].'; '; // Convert $resultArray to css style string

 if ($resultStyle != '') $resultStyle = substr($resultStyle, 0, -1);
 return $resultStyle;
}

function checkODOV($db, $input, $cmdcheck = false)
{
 global $OD, $OV, $odid;
 
 // Check input OD/OV vars existence
 if (!isset($input['OD']) || !isset($input['OV'])) return 'Incorrect Object Database/View!';
 
 // Check any OD sql database existence
 $query = $db->prepare("SELECT id FROM $ LIMIT 1");
 $query->execute();
 if (count($query->fetchAll(PDO::FETCH_NUM)) == 0) return 'Please create Object Database first!';

 // Empty value OD/OV check
 if ($input['OD'] === '' || $input['OV'] === '') return 'Please create/select Object View!'; 
 $OD = $input['OD'];
 $OV = $input['OV'];
 
 // Check $OD existence and get its id
 $query = $db->prepare("SELECT id FROM $ WHERE odname='$OD'");
 $query->execute();
 if (count($odid = $query->fetchAll(PDO::FETCH_NUM)) == 0) return 'Please create/select Object View!';
 $odid = $odid[0][0];
}

function getODProps($db)
{			
 global $OD, $OV, $odid, $allElementsArray, $uniqElementsArray, $elementSelectionJSONList;
 $allElementsArray = $uniqElementsArray = [];
  
 // Get odname $OD props
 $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View'), JSON_EXTRACT(odprops, '$.dialog.Element') FROM $ WHERE id='$odid'");
 $query->execute();
 if (count($data = $query->fetchAll(PDO::FETCH_NUM)) == 0) return 'Please create/select Object View!';
 $data = $data[0];
			
 // Move on. Get specified view JSON element selection (what elements should be displayed and how)
 $elementSelectionJSONList = json_decode($data[0], true);
 if (!isset($elementSelectionJSONList[$OV]['element5']['data'])) return 'Please create/select Object View!';
 $elementSelectionJSONList = trim($elementSelectionJSONList[$OV]['element5']['data']);
  		     
 // Decode element profiles array form OD props, remove 'New element' section and check elements existence
 $elements = json_decode($data[1], true);
 unset($elements['New element']);
 if (!is_array($elements) || !count($elements)) return 'Object Database has no elements exist!';

 // Convert elements assoc array to num array with element identificators as array elements instead of profile names and sort it
 foreach ($elements as $profile => $value)
	 {
	  $eid = intval(substr($profile, strrpos($profile, ELEMENTPROFILENAMEADDSTRING) + strlen(ELEMENTPROFILENAMEADDSTRING)));  // Calculate current element id
	  $allElementsArray[$eid] = $elements[$profile];
	  if ($value['element3']['data'] === UNIQELEMENTTYPE) $uniqElementsArray[$eid] = '';
	 }
 ksort($allElementsArray, SORT_NUMERIC);	 
	 
 // List is empty? Set up default list for all elements: {"eid": "every", "oid": "title|0|newobj", "x": "0..", "y": "0|n"}
 if ($elementSelectionJSONList === '')
    {
     $x = 0;
     foreach ($allElementsArray as $eid => $value)
    	     {
	      $elementSelectionJSONList .= '{"eid": "'.$eid.'", "oid": "'.strval(TITLEOBJECTID).'", "x": "'.strval($x).'", "y": "0", "style": "background-color: #BBB;"}'."\n";
	      $elementSelectionJSONList .= '{"eid": "'.$eid.'", "oid": "0", "x": "'.strval($x).'", "y": "n-1"}'."\n";
	      $elementSelectionJSONList .= '{"eid": "'.$eid.'", "oid": "'.strval(NEWOBJECTID).'", "x": "'.strval($x).'", "y": "n", "style": "background-color: #AFF;"}'."\n";
	      $x++;
	     }
    }
}

function checkObjectElementID($input)
{
 global $oid, $eid, $allElementsArray, $cmd, $data;

 // Check browser event (cmd) data to be valid and return an error in case of undefined data for KEYPRESS and CONFIRM events
 $cmd = $input['cmd'];
 if (isset($input['data'])) $data = $input['data'];
  else if ($cmd === 'KEYPRESS' || $cmd === 'CONFIRM') return 'Controller report: undefined browser event data!';

 // Check object/element id existence/correctness. In case of 'INIT' this check is not required
 if ($cmd != 'INIT')
    {
     if (!isset($input['eId']) || !isset($input['oId']) || $input['oId'] < STARTOBJECTID) return 'Incorrect object/element identificator value!';
     if (isset($allElementsArray) && !isset($allElementsArray[$input['eId']])) return 'Incorrect element identificator value!';
     $oid = $input['oId'];
     $eid = $input['eId'];
    }
}

function Handler($handler, $input)
{
 include './'.HANDLERDIR.'/'.$handler;
 if (isset($output))
    {
     $output = json_decode($output, true);
     if (is_array($output) && isset($output['cmd'])) return $output;
    }
 return ['cmd' => 'UNDEFINED'];
}

function parseJSONEventData($JSONs, $event)
{
 foreach (preg_split("/\n/", $JSONs) as $line) // Split json list and parse its lines to find specified event
      if (($json = json_decode($line, true)) && isset($json['event']) && $json['event'] === $event) // Event match?
         {
	  $eventArray = ['event' => $event];
          foreach ($json as $prop => $value) // Search non reserved array elements to pass them to result event array
	       if ($prop != 'event' && $prop != 'event data' && $prop != 'user' && $prop != 'eid' && $prop != 'header')
	       if ($j = json_decode($json[$prop], true))
		  {
		   
		  }
	        else
		  {
		   $eventArray[$prop] = $json[$prop];
		  }
	  break;
	 }
	 
 if (isset($eventArray)) return $eventArray;
 if ($event === 'CONFIRM') return ['event' => 'CONFIRM'];
 return NULL;
}

function InsertObject($db, $output)
{
 global $odid, $allElementsArray, $uniqElementsArray;

 $query = $values = ''; 
 foreach ($uniqElementsArray as $id => $value)
	 {
	  $query .= ",eid$id";
	  isset($output[$id]['value']) ? $values .= ",'".$output[$id]['value']."'" : $values .= ",''";
	 }
 if ($query != '') { $query = substr($query, 1); $values = substr($values, 1); }

 $db->beginTransaction();
 $query = $db->prepare("INSERT INTO `uniq_$odid` ($query) VALUES ($values)");
 $query->execute();                                                                  
 
 $query = $db->prepare("SELECT LAST_INSERT_ID()");                                   
 $query->execute();                                                                  
 // Generate new exception in case of non correct last insert id value               
 if (intval($newId = $query->fetch(PDO::FETCH_NUM)[0]) < STARTOBJECTID) throw new Exception();

 $query = 'id,version'; // Plus date, time, user
 $values = $newId.',1';
 foreach ($allElementsArray as $id => $value) if (isset($output[$id]))
	 {
	  $json = str_replace("\\", "\\\\", json_encode($output[$id]));
	  if (isset($json)) { $query .= ',eid'.strval($id); $values .= ",'".$json."'"; }
	 }
 $query = $db->prepare("INSERT INTO `data_$odid` ($query) VALUES ($values)");
 $query->execute();
 
 $db->commit();
}

function DeleteObject($db, $id)
{
 global $odid;
 
 $db->beginTransaction();
 $query = $db->prepare("SELECT id FROM `data_$odid` WHERE id=$id AND last=1 AND version!=0 FOR UPDATE");
 $query->execute();
 if (count($query->fetchAll(PDO::FETCH_NUM)) == 0)
    {
     $db->rollBack();
     return "Object (identificator $id) not found!";
    }
 
 $query = $db->prepare("UPDATE `data_$odid` SET last=0 WHERE id=$id AND last=1");
 $query->execute();
 $query = $db->prepare("INSERT INTO `data_$odid` (id,version) VALUES ($id,0)");
 $query->execute();
 $query = $db->prepare("DELETE FROM `uniq_$odid` WHERE id=$id");
 $query->execute();
 $db->commit();
}

function UpdateObject($db, $output)
{
 global $oid;
 
 foreach ($output as $eid => $json) if (isset($json['cmd']))
	 {
	 }
}

function getMainFieldData($db)
{
 global $allElementsArray, $elementSelectionJSONList, $objectTable, $odid;
 $arrayEIdOId = [];
 $allElementsArray[0] = $sqlElementList = '';
 
 // Split listJSON data by lines to parse defined element identificators and to build eid-oid two dimension array.
 // Undefined oid or oid - json line is ignored anyaway, but both undefined oid and oid 'style' and 'collapse' properties
 // are parsed for undefined cells css style and collapse capability. Array structure:
 //  ----------------------------------------------------------------
 // |  \eid|       Element #0        |        Element #1..           | 
 // |oid\  |         styles          | x,y,style,startevent..        |
 //  ----------------------------------------------------------------
 // |  0   | for any oid/eid   	     | for default object element #1 |
 //  ----- ----------------------------------------------------------
 // |  1   | for whole new object    | for new object element #1     |
 //  ----------------------------------------------------------------
 // |  2   | for whole title object  | for title object element #1   |
 //  ----------------------------------------------------------------
 // |  3.. | for whole real object   | for real objects element #1   |
 //  ----------------------------------------------------------------
 foreach (preg_split("/\n/", $elementSelectionJSONList) as $value) if ($j = json_decode($value, true, 2))
	 {
	  $j = cutKeys($j, ['eid', 'oid', 'x', 'y', 'style', 'collapse', 'startevent']);
	  if (!key_exists('eid', $j) || !key_exists('oid', $j)) // eid/oid property doesn't exist? Set some undefined cells features
	     {
	      if (!key_exists('eid', $j) && !key_exists('oid', $j))
		 {
		  $undefinedProps = [];
		  if (key_exists('style', $j)) $undefinedProps['style'] = $j['style'];
		  if (key_exists('collapse', $j)) $undefinedProps['collapse'] = $j['collapse'];
		 }
	      continue;
	     }
				 
			      if (gettype($j['eid']) != 'string' || gettype($j['oid']) != 'string') continue; // JSON eid/oid property is not a string? Continue
			      if (!ctype_digit($j['eid']) || !ctype_digit($j['oid'])) continue; // JSON eid/oid property are not numerical? Continue
			      
			      $eid = intval($j['eid']);
			      $oid = intval($j['oid']);
			      
			      if (key_exists($eid, $allElementsArray) && ($eid != 0 || key_exists('style', $j))) // Non zero or zero with style eid index of elements exist?
			      if ($eid == 0 || (gettype($j['x']) === 'string' && gettype($j['y']) === 'string'))
				 {
				  if (!key_exists($eid, $arrayEIdOId))
				     {
				      $arrayEIdOId[$eid] = [];
				      if ($eid != 0) $sqlElementList .= ',eid'.$j['eid']; // Collect elements list to use from sql query
				     }
				  if ($eid != 0) $arrayEIdOId[$eid][$oid] = $j; // Fill eidoid array with parsed json string
				   else $arrayEIdOId[$eid][$oid] = $j['style']; // Fill eidoid array with style property
				 }
			     }
			     
		     // No any element defined?	
		     if ($sqlElementList == '') return 'Specified view has no elements defined!';
			
		     // Create result $objectTable array section. First step - init objectTable array (result objects) and $objectTableSrc (object from sql database)
		     $objectTable = $objectTableSrc = [];
		     // Object list selection should depends on JSON 'oid' property, specified view page number object range and object selection expression match.
		     // While this features are not released, get all objects:
		     $query = $db->prepare("SELECT id$sqlElementList FROM `data_$odid` WHERE last=1 AND version!=0");
		     $query->execute();
		     
		     // Reindex $objectTable array to fit numeric indexes as object identificators to next format:
		     //  -----------------------------------------------------------------------------------
		     // |  \ eid|               |                                             		    	|
		     // |   \   |       0       |           5.. (was 'eid5' column)             	    	|
		     // |oid \  |               |                                             		    	|
		     //  -----------------------------------------------------------------------------------
		     // |       |style rules    |                                             		    	|
		     // |   0   |for undefined  |Apply object element props for all objects with element #5 |                                        		 |
		     // |       |cells          |                                             		    	|
		     //  -----------------------------------------------------------------------------------
		     // |       |Apply styles   |"json" : JSON element data                   		   	 	|
		     // |   1   |for whole      |"props": props for new object element #5 (eid=5,oid=0)     |	NEWOBJECTID
		     // |       |new object     |                                                    	    |
		     //  -----------------------------------------------------------------------------------
		     // |       |Apply styles   |"json" : JSON element data                   		    	|
		     // |   2   |for whole      |"props": props for title object element #5 (eid=5,oid=0)   |	TITLEOBJECTID
		     // |       |title object   |                                                           |
		     //  -----------------------------------------------------------------------------------
		     // |       |Apply styles   |"json" : JSON element data                   		    	|
		     // |  3..  |for whole      |"props": props for real object element #5 (eid=5,oid=0)    |	STARTOBJECTID
		     // |       |real object    |                                                   	    |
		     //  -----------------------------------------------------------------------------------
		     foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $value)
		    	     {
			      $oid = intval($value['id']);    // Get object id of current 'id' column of the fetched array
			      $objectTableSrc[$oid] = $value; // Create row with object-id as an index for $objectTableSrc array
			     }
			     
		     // Rewrite $objectTableSrc array (to the table above) on eidoid array to $objectTable, not forgeting about static element status.
		     // In the future release create first object (static) flag whether it is on the object list or not, then remove it at the end or not.
		     // So - iterate all elements with non zero identificators (real elements)
		     foreach ($arrayEIdOId as $eid => $value) if ($eid != 0)
		    	     {
			      $eidstr = 'eid'.strval($eid);
			      
			      // Iterate all objects identificators for current eid to fill $objectTable. First - for all object when oid=0:
			      if (key_exists(0, $arrayEIdOId[$eid])) foreach($objectTableSrc as $oid => $valeu)
				 {
				  if (!key_exists($oid, $objectTable)) // Result $objectTable current object ($oid) doesn't exist? Create it
				     {
				      $objectTable[$oid] = []; // Result $objectTable current object ($oid) doesn't exist? Create it
				      $objectTable[$oid][$eid] = [];
				     }
				  $objectTable[$oid][$eid]['json'] = $objectTableSrc[$oid][$eidstr]; // Set current element json data
				  $objectTable[$oid][$eid]['props'] = $arrayEIdOId[$eid][0]; // Set current object element props data
				  //----------------Merge CSS style rules in order of priority--------------
				  $styles = [];
				  if (isset($arrayEIdOId[0][0])) $styles[] = $arrayEIdOId[0][0]; // General style for all objects
				  if (isset($arrayEIdOId[0][$oid])) $styles[] = $arrayEIdOId[0][$oid]; // Object general style
				  if (isset($objectTable[$oid][$eid]['props']['style'])) $styles[] = $objectTable[$oid][$eid]['props']['style']; // Props style
				  if (isset($objectTableSrc[$oid][$eidstr]['style'])) $styles[] = $objectTableSrc[$oid][$eidstr]['style']; // Element style
				  $objectTable[$oid][$eid]['props']['style'] = mergeStyleRules($styles);
				  //---------------------------Merge style rules end------------------------ 
				 }
				 
			      // Second - for other exact object oids:
		    	      foreach ($value as $oid => $props) if ($oid != 0)
				      {
				       $json = NULL;
				       if ($oid === NEWOBJECTID) $json = json_encode(['value' => '']);
				       if ($oid === TITLEOBJECTID) $json = json_encode(['value' => $allElementsArray[$eid]['element1']['data']]);
				       if (key_exists($oid, $objectTableSrc)) $json = $objectTableSrc[$oid][$eidstr];
				       if (isset($json))
				          {
					   if (!key_exists($oid, $objectTable)) $objectTable[$oid] = [];
					   $objectTable[$oid][$eid] = ['json' => $json, 'props' => $props];
					   //----------------Merge CSS style rules in order of priority--------------
					   $styles = [];
					   if (isset($arrayEIdOId[0][0])) $styles[] = $arrayEIdOId[0][0]; // General style for all objects
					   if (isset($arrayEIdOId[0][$oid])) $styles[] = $arrayEIdOId[0][$oid]; // Object general style
					   if (isset($objectTable[$oid][$eid]['props']['style'])) $styles[] = $objectTable[$oid][$eid]['props']['style']; // Props style
					   if (isset($objectTableSrc[$oid][$eidstr]['style'])) $styles[] = $objectTableSrc[$oid][$eidstr]['style']; // Element style
					   $objectTable[$oid][$eid]['props']['style'] = mergeStyleRules($styles);
					   //---------------------------Merge style rules end------------------------ 
					  }
				      }
			     }
			     
 // Check the result data to be sent to client part
 if (count($objectTable) < 1) return 'Specified view has no objects defined!';
  else if (isset($undefinedProps)) $objectTable[0][0] = $undefinedProps;
}
