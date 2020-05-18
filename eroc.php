<?php

const MAXOBJECTS = 100000;

error_reporting(E_ALL);
$db = new PDO('mysql:host=localhost;dbname=OE4', 'root', '123'); 
$db->exec("SET NAMES UTF8");
$db->exec("ALTER DATABASE OE3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci");
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

function adjustODProperties($data)
{
 global $newProperties, $newElement, $newView, $newRule;
 
 // Is incoming arg array?
 if (!is_array($data)) return NULL;

 // Database section handle
 if (!isset($data['dialog']['Database']['Properties']['element1']['data'])) return NULL; // Database name exists?
 if ($data['dialog']['Database']['Properties']['element1']['data'] == '') return NULL; // Empty database name?
 
 // New element section handle
 if (!isset($data['dialog']['Element']['New element'])) return NULL;
 $id = 1;
 $isNew = false;
 foreach ($data['dialog']['Element'] as $key => $value)
      if (!isset($value['element1']['data']) || !isset($value['element2']['data']) || !isset($value['element4']['data'])) // Dialog 'Element' pad corrupted?
	 {
	  return NULL;
	 }
       else if ($key != 'New element') // Remove empty elements for non new element profile
	 {
	  if ($value['element1']['data'] === '' && $value['element2']['data'] === '' && $value['element4']['data'] === '')
	     unset($data['dialog']['Element'][$key]);		// Element name, description and handler file are empty? Remove element.
	   else if (intval(substr($key, strlen('element id'))) > $id)
	     $id = intval(substr($key, strlen('element id')));	// Otherwise get Current element id if it greater than last max value
	 }
       else if ($value['element1']['data'] != '' || $value['element2']['data'] != '' || $value['element4']['data'] === '') $isNew = true;
 if ($isNew) // New element have been set? Create it
    {
     $data['dialog']['Element']['element id'.strval($id + 1)] = $data['dialog']['Element']['New element'];
     $data['dialog']['Element']['New element'] = $newElement;
    }
    
 // New view section handle
 if (!isset($data['dialog']['View']['New view']['element1']['data'])) return NULL;
 foreach ($data['dialog']['View'] as $key => $value)
      if (!isset($value['element1']['data']) || !isset($value['element2']['data'])) return NULL; // Dialog 'View' pad corrupted?
       else if ($value['element1']['data'] === '') unset($data['dialog']['View'][$key]);	 // View name is empty? Remove it
       else if (isset($data['dialog']['View'][$value['element1']['data']])) $value['element1']['data'] = $key; // New view name already exists? Discard changes
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
       else if (isset($data['dialog']['Rule'][$value['element1']['data']])) $value['element1']['data'] = $key; // New rule name already exists? Discard changes
       else
	 {
	  $data['dialog']['Rule'][$value['element1']['data']] = $data['dialog']['Rule'][$key];	// Otherwise create new rule with new rule name
	  unset($data['dialog']['Rule'][$key]);							// and remove old rule
	 }
 $data['dialog']['Rule']['New rule'] = $newRule; // Reset 'New rule' profile to default
 
 // Return result data
 $data['title'] = 'Edit Object Database structure';
 return $data;
}					

function initNewODDialogElements()
{
 global $newProperties, $newElement, $newView, $newRule;
 
 $newProperties  = ['element1' => ['type' => 'text', 'head' => 'Database name', 'data' => '', 'line' => '', 'help' => 'To remove database without recovery - set empty database name string and its description'],
		    'element2' => ['type' => 'textarea', 'head' => 'Database description', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'text', 'head' => 'Database size limit in MBytes. Emtpy, undefined or zero value - no limit.', 'data' => '', 'line' => ''],
		    'element4' => ['type' => 'text', 'head' => 'Database object count limit. Emtpy, undefined or zero value - no limit.', 'data' => '', 'line' => ''],
		    'element5' => ['type' => 'text', 'head' => 'Max object versions in range 0-65535. Emtpy or undefined string - zero value', 'data' => '', 'line' => '', 'help' => 'Each object has some instances (versions) beginning with version number 1.<br>Once some object data has been changed, its version is incremented by one. <br>Max version value limits object max possible stored instances. Values description:<br>0 - no object data versions stored at all, only one (last) version<br>1 - only last version stored also, but deleted objects remain in database (marked by zero version)<br>2 - any object has two versions stored<br>3 - any object has three versions stored<br>4 - ...<br><br>Once database created, this value can be increased or redused. Reducing max version number<br>has two options - first or last versions of each object will be removed from the database.'],
		    'element6' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element7' => ['type' => 'textarea', 'head' => 'List of users and groups (one by line) allowed or disallowed (depending on list type above) to add new databases or edit its properties:', 'data' => '', 'line' => ''],
		    'element8' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element9' => ['type' => 'textarea', 'head' => 'List of users and groups (one by line) allowed or disallowed (depending on list type above) to add/edit object element properties:', 'data' => '', 'line' => ''],
		    'element10' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element11' => ['type' => 'textarea', 'head' => 'List of users and groups (one by line) allowed or disallowed (depending on list type above) to add/edit object view properties:', 'data' => '', 'line' => ''],
		    'element12' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element13' => ['type' => 'textarea', 'head' => 'List of users and groups (one by line) allowed or disallowed (depending on list type above) to add/edit database rules:', 'data' => '', 'line' => '']];
		    
 $newElement	 = ['element1' => ['type' => 'textarea', 'head' => 'Element title to display in object view as a header', 'data' => '', 'line' => '', 'help' => 'To remove object element - set empty element header, description and handler file'],
		    'element2' => ['type' => 'textarea', 'head' => 'Element description', 'data' => '', 'line' => '', 'help' => 'Specified description is displayed as a hint on object view element headers navigation.<br>It is used to describe element purpose and its possible values.'],
		    'element3' => ['type' => 'radio', 'head' => 'Element type', 'data' => '+standart|static|unique', 'line' => '', 'help' => "Static type implies element value with one single instance for all objects in object database,<br>while unique element type guarantees element value uniqueness among all objects.<br>Normal type doesn't have all these features."],
		    'element4' => ['type' => 'text', 'head' => 'Server side element event handler file that processes incoming user defined events (see event section below):', 'data' => '', 'line' => ''],
		    'element5' => ['type' => 'textarea', 'head' => 'Element scheduler', 'data' => '', 'line' => '', 'help' => "Element sheduler is some strings (one by line), each of them executes its element handler<br>starting at specified date/time with space separated args one by one in next format:<br>'minute hour mday month wday event count'.<br>See crontab file *nix manual page. Any undefined arg - no call. Scheduled call emulates<br>mouse/keyboard events and passes 'system' user as an user initiated specified event."],
		    'element6' => ['type' => 'select-one', 'head' => 'New element event', 'data' => 'NONE|DBLCLICK|F2|F12|INS|DEL|KEYPRESS|CONFIRM|NEWOBJECT|DELETEOBJECT|CHANGE', 'help' => 'Element event such as keyborad press (F2, F12, INS, DEL, KEYPRESS), mouse (DBLCLICK),<br>callback event (CONFIRM) or object event (NEWOBJECT, DELETEOBJECT, CHANGE)<br>to pass as a part of JSON string below.'],
		    'element7' => ['type' => 'text', 'head' => 'Element handler args', 'data' => '', 'line' => '', 'help' => "JSON string to pass to element handler when specified event above occurs. Some properties <br>of that JSON are reserved to pass some service data. They are 'event', 'user' initiated<br>event, element 'id' and its 'header'. User defined properties can be set to string or<br>another JSON with Object Database 'OD', Object View 'OV', Object Id 'oId',<br>Element Id 'eId' and element property. In case of 'OD', 'OV', 'oId' or 'eId' omitted -<br>current object database/view and object/element id values are used.<br>To remove element event handle set event to 'NONE' and handler args to empty string."]];
	
 $newView	 = ['element1' => ['type' => 'text', 'head' => 'Object View name', 'data' => '', 'line' => '', 'help' => "View name can be changed, but if it already exists, changes won't be applied.<br>So view name 'New view' can't be set as it is used as a name for new views creation.<br>To remove object view - set empty object view name string."],
		    'element2' => ['type' => 'textarea', 'head' => 'Object View description', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'textarea', 'head' => 'Object View expression (selects objects for the specified view). Empty string selects all objects, error string - no objects.', 'data' => '', 'line' => ''],
		    'element4' => ['type' => 'radio', 'head' => 'Object view type', 'data' => '+Table|Scheme|Graph|Piechart|Map', 'line' => '', 'help' => "Select object view type from 'table' (displays objects in a form of a table),<br>'scheme' (displays object hierarchy built on uplink and downlink property),<br>'graph' (displays object graphic with one element on 'X' axis, other on 'Y'),<br>'piechart' (displays object statistic on the piechart) and<br>'map' (displays objects on the geographic map)"],
		    'element5' => ['type' => 'textarea', 'head' => 'Object view element expression. Defines what elements should be displayed and how.', 'data' => '', 'line' => ''],
		    'element6' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element7' => ['type' => 'textarea', 'head' => 'List of users and groups (one by line) allowed or disallowed (depending on list type above) to have this OV on the sidebar list, so able to select it:', 'data' => '', 'line' => ''],
		    'element8' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element9' => ['type' => 'textarea', 'head' => 'List of users and groups (one by line) allowed or disallowed (depending on list type above) to add/edit/delete objects:', 'data' => '', 'line' => '']];
							  
 $newRule	 = ['element1' => ['type' => 'text', 'head' => 'Rule name', 'data' => '', 'line' => '', 'help' => "Rule name is displayed as title on the dialog box.<br>Rule name can be changed, but if it already exists, changes won't be applied.<br>So rule name 'New rule' can't be set as it is used as a name for new rules creation.<br>To remove the rule - set rule name to empty string."],
		    'element2' => ['type' => 'textarea', 'head' => 'Rule message', 'data' => '', 'line' => '', 'help' => 'Rule message is match case log message displayed in dialog box.<br>Object element id in figure {#id} or square [#id] brackets retreives<br>appropriate element id value or element id title respectively.<br>Escape character is "\".'],
		    'element3' => ['type' => 'select-one', 'head' => 'Rule action', 'data' => 'No action|Warning|Confirm|Reject|', 'line' => '', 'help' => "All actions shows up dialog box with rule message inside.<br>'Warning' action warns user and apply the changes.<br>'Reject' does the same, but cancels the changes with no chance to keep them.<br>'Confirm' asks wether keep it or reject."],
		    'element4' => ['type' => 'checkbox', 'head' => 'Log the message', 'data' => 'Log', 'line' => ''],
		    'element5' => ['type' => 'textarea', 'head' => 'Rule expression', 'data' => '', 'line' => '', 'help' => 'Empty or error expression does nothing']];
}

function createDefaultDatabases($db)
{
 $query = $db->prepare("CREATE TABLE IF NOT EXISTS `$` (odname CHAR(64) NOT NULL, odprops JSON NOT NULL, PRIMARY KEY (odname)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
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