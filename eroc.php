<?php

const DATABASENAME			= 'OE8';
const MAXOBJECTS			= 100000;
const ODSTRINGMAXCHAR			= 64;
const USERSTRINGMAXCHAR			= '32';
const HANDLERDIR			= 'handlers';
const ELEMENTDATAVALUEMAXCHAR		= 10000;
const ELEMENTPROFILENAMEMAXCHAR		= 16;
const ELEMENTPROFILENAMEADDSTRING	= 'element id';
const UNIQKEYCHARLENGTH			= 300;
const UNIQELEMENTTYPE			= '+unique';
const NEWOBJECTID			= 1;
const TITLEOBJECTID			= 2;
const STARTOBJECTID			= 3;
const CHECK_OD_OV			= 0b00000001;
const GET_ELEMENT_PROFILES		= 0b00000010;
const GET_OBJECT_VIEWS			= 0b00000100;
const SET_CMD_DATA			= 0b00001000;
const CHECK_OID				= 0b00010000;
const CHECK_EID				= 0b00100000;
const DEFAULTUSER			= 'root';
const DEFAULTPASSWORD			= 'root';

error_reporting(E_ALL);
$db = new PDO('mysql:host=localhost;dbname='.DATABASENAME, 'root', '123');
$db->exec("SET NAMES UTF8");
$db->exec("ALTER DATABASE ".DATABASENAME." CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
session_start(['cookie_httponly' => true]);
    
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
     if (strlen($data['dialog']['Element']['New element']['element1']['data']) > ELEMENTDATAVALUEMAXCHAR/2)
        $data['dialog']['Element']['New element']['element1']['data'] = substr($data['dialog']['Element']['New element']['element1']['data'], 0, ELEMENTDATAVALUEMAXCHAR/2);
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
       else if (isset($data['dialog']['View'][$value['element1']['data']])) $data['dialog']['View'][$key]['element1']['data'] = $key.''; // New view name already exists? Discard changes
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
 $data['buttons'] = ['SAVE' => ' ', 'CANCEL' => 'background-color: red;'];
 if (!isset($data['flags'])) $data['flags'] = [];
 $data['flags']['_callback'] = 'EDITOD';
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
		    'element2' => ['type' => 'textarea', 'head' => 'List of users/groups (one by line) allowed or disallowed (see above) to edit this database properties:', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element4' => ['type' => 'textarea', 'head' => 'List of users/groups (one by line) allowed or disallowed (see above) to add/edit object elements:', 'data' => '', 'line' => ''],
		    'element5' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element6' => ['type' => 'textarea', 'head' => 'List of users/groups (one by line) allowed or disallowed (see above) to add/edit object views:', 'data' => '', 'line' => ''],
		    'element7' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element8' => ['type' => 'textarea', 'head' => 'List of users/groups (one by line) allowed or disallowed (see above) to add/edit database rules:', 'data' => '', 'line' => '']];

 $newElement	 = ['element1' => ['type' => 'textarea', 'head' => 'Element title to display in object view as a header', 'data' => '', 'line' => '', 'help' => 'To remove object element - set empty element header, description and handler file'],
		    'element2' => ['type' => 'textarea', 'head' => 'Element description', 'data' => '', 'line' => '', 'help' => 'Specified description is displayed as a hint on object view element headers navigation.<br>It is used to describe element purpose and its possible values.'],
		    'element3' => ['type' => 'checkbox', 'head' => 'Element type', 'data' => 'unique', 'line' => '', 'help' => "Unique element type guarantees element value uniqueness among all objects.<br>Element type cannot be changed after element creation."],
		    'element4' => ['type' => 'text', 'head' => 'Server side element event handler file that processes incoming user defined events (see event section below):', 'data' => '', 'line' => ''],
		    'element5' => ['type' => 'textarea', 'head' => 'JSON format event list', 'data' => '', 'line' => '', 'help' => 'Event JSON string (one per line) is a JSON to pass to the element handler as an input argument<br>when specified event occurs. JSONs properties:<br>"event" - event to be processed by the handler, JSONs with undefined event are ignored<br>"user" - user initiated event (automatically set by controller)<br>"eid" - element id (automatically set by controller)<br>"header" - element header (automatically set by controller)<br>Additionally some custom properties can be defined - its string values are sent to the handler<br>without changes with one exception - JSON formated value is replaced by element JSON data.<br>Format of the value: {"eid": "&lt;element id>", "prop": "&lt;element property>"}<br>where "prop" - element property, which value points to the specified by element &lt;eid> JSON data<br>property to be retrieved. In case of "eid" omitted - current element id value is used.<br>In the example below handler on mouse double click event gets JSON<br>with two custom properties. First property value is "test", second value -<br>json element data property "value" of current object element identificator 1:<br>{ "event": "DBLCLICK", "abc": "test", "def": {"eid": "1", "prop": "value"} }'],
		    'element6' => ['type' => 'textarea', 'head' => 'Element scheduler', 'data' => '', 'line' => '', 'help' => "Each element scheduler string (one per line) executes its handler &lt;count> times starting at<br>specified date/time and represents itself one by one space separated args in next format:<br>&lt;minute> &lt;hour> &lt;mday> &lt;month> &lt;wday> &lt;event> &lt;event data> &lt;count><br>See crontab file *nix manual page for date/time args. Zero &lt;count> - infinite calls count.<br>Scheduled call emulates mouse/keyboard events (DBLCLICK and KEYPRESS) with specified<br>&lt;event data> (for KEYPRESS only) and passes 'system' user as an user initiated<br>specified event. Any undefined arg - no call."]];
	
 $newView	 = ['element1' => ['type' => 'text', 'head' => 'Object View name', 'data' => '', 'line' => '', 'help' => "View name can be changed, but if it already exists, changes won't be applied.<br>So view name 'New view' can't be set as it is used as a name for new views creation.<br>To remove object view - set empty object view name string."],
		    'element2' => ['type' => 'textarea', 'head' => 'Object View description', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'textarea', 'head' => 'Object selection expression. Empty string selects all objects, error string - no objects.', 'data' => '', 'line' => ''],
		    'element4' => ['type' => 'radio', 'head' => 'Object view type', 'data' => '+Table|Scheme|Graph|Piechart|Map', 'line' => '', 'help' => "Select object view type from 'table' (displays objects in a form of a table),<br>'scheme' (displays object hierarchy built on uplink and downlink property),<br>'graph' (displays object graphic with one element on 'X' axis, other on 'Y'),<br>'piechart' (displays specified element value statistic on the piechart) and<br>'map' (displays objects on the geographic map)"],
		    'element5' => ['type' => 'textarea', 'head' => 'Element selection expression. Defines what elements should be displayed and how.', 'data' => '', 'line' => ''],
		    'element6' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element7' => ['type' => 'textarea', 'head' => 'List of users/groups (one by line) allowed or disallowed (see above) to have this OV on sidebar list:', 'data' => '', 'line' => ''],
		    'element8' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element9' => ['type' => 'textarea', 'head' => 'List of users/groups (one by line) allowed or disallowed (see above) to add/change/delete objects in this view:', 'data' => '', 'line' => '']];
							  
 $newRule	 = ['element1' => ['type' => 'text', 'head' => 'Rule name', 'data' => '', 'readonl' => '', 'line' => '', 'help' => "Rule name is displayed as title on the dialog box.<br>Rule name can be changed, but if it already exists, changes won't be applied.<br>So rule name 'New rule' can't be set as it is used as a name for new rules creation.<br>To remove the rule - set rule name to empty string."],
		    'element2' => ['type' => 'textarea', 'head' => 'Rule message', 'data' => '', 'line' => '', 'help' => 'Rule message is match case log message displayed in dialog box.<br>Object element id in figure {#id} or square [#id] brackets retreives<br>appropriate element id value or element id title respectively.<br>Escape character is "\".'],
		    'element3' => ['type' => 'select-one', 'head' => 'Rule action', 'data' => 'No action|Warning|Confirm|Reject|', 'line' => '', 'help' => "All actions shows up dialog box with rule message inside.<br>'Warning' action warns user and apply the changes.<br>'Reject' does the same, but cancels the changes with no chance to keep them.<br>'Confirm' asks wether keep it or reject."],
		    'element4' => ['type' => 'textarea', 'head' => 'Rule expression', 'data' => '', 'line' => '', 'help' => 'Empty or error expression does nothing']];
}

function createDefaultDatabases($db)
{
 /***********Create OD list data sql table if not exists*************************/
 $query = $db->prepare("CREATE TABLE IF NOT EXISTS `$` (id MEDIUMINT NOT NULL AUTO_INCREMENT, odname CHAR(64) NOT NULL, odprops JSON, UNIQUE(odname), PRIMARY KEY (id)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $query->execute();
 
 /***********Default OD 'Users' existence check*************************/
 $query = $db->prepare("SELECT odname FROM `$` WHERE odname='Users'");
 $query->execute();
 if (isset($query->fetchAll(PDO::FETCH_NUM)[0][0])) return;
 
 /***********Default OD 'Users' creating*************************/
 global $newProperties, $newPermissions, $newElement, $newView, $newRule;
 initNewODDialogElements();
 $newProperties['element1']['data'] = 'Users';
 $userOD = ['title'  => 'New Object Database', 'dialog'  => ['Database' => ['Properties' => $newProperties, 'Permissions' => $newPermissions], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => ['SAVE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['_callback' => 'EDITOD', 'style' => 'width: 760px; height: 670px;', 'esc' => '', 'display_single_profile' => '']];

 $newView['element1']['data'] = 'All users';
 $userOD['dialog']['View']['All users'] = $newView;

 $newElement['element1']['data'] = 'User';
 $newElement['element2']['data'] = "\nDouble click the username to change the password and other user properties";
 $newElement['element3']['data'] = UNIQELEMENTTYPE;
 $newElement['element4']['data'] = 'user.php';
 $newElement['element5']['data'] = '{"event":"INIT"}'."\n".'{"event": "DBLCLICK", "account": {"prop": "value"}, "odaddperm": {"prop": "odaddperm"}, "groups": {"prop": "groups"} }';
 $userOD['dialog']['Element']['User - element id1'] = $newElement;

 $newElement['element1']['data'] = 'Name';
 $newElement['element2']['data'] = '';
 $newElement['element3']['data'] = 'unique';
 $newElement['element4']['data'] = 'text.php';
 $newElement['element5']['data'] = '{"event":"INIT"}'."\n".'{"event": "DBLCLICK"}'."\n".'{"event": "KEYPRESS"}';
 $userOD['dialog']['Element']['Name - element id2'] = $newElement;

 $newElement['element1']['data'] = 'Telephone';
 $newElement['element2']['data'] = '';
 $newElement['element3']['data'] = 'unique';
 $newElement['element4']['data'] = 'text.php';
 $newElement['element5']['data'] = '{"event":"INIT"}'."\n".'{"event": "DBLCLICK"}'."\n".'{"event": "KEYPRESS"}';
 $userOD['dialog']['Element']['Telephone - element id3'] = $newElement;

 $newElement['element1']['data'] = 'Email';
 $newElement['element2']['data'] = '';
 $newElement['element3']['data'] = 'unique';
 $newElement['element4']['data'] = 'text.php';
 $newElement['element5']['data'] = '{"event":"INIT"}'."\n".'{"event": "DBLCLICK"}'."\n".'{"event": "KEYPRESS"}';
 $userOD['dialog']['Element']['Email - element id4'] = $newElement;

 $newElement['element1']['data'] = 'Comment';
 $newElement['element2']['data'] = '';
 $newElement['element3']['data'] = 'unique';
 $newElement['element4']['data'] = 'text.php';
 $newElement['element5']['data'] = '{"event":"INIT"}'."\n".'{"event": "DBLCLICK"}'."\n".'{"event": "KEYPRESS"}';
 $userOD['dialog']['Element']['Comment - element id5'] = $newElement;

 $newElement['element1']['data'] = 'Customization';
 $newElement['element2']['data'] = "\nDouble click appropriate cell to change color, font, background and other properties for the specified user";
 $newElement['element3']['data'] = UNIQELEMENTTYPE;
 $newElement['element4']['data'] = 'customization.php';
 $newElement['element5']['data'] = '{"event":"INIT"}'."\n".'{"event": "DBLCLICK", "dialog": {"prop": "dialog"}}';
 $userOD['dialog']['Element']['Customization - element id6'] = $newElement;

 $query = $db->prepare("INSERT INTO `$` (odname,odprops) VALUES ('Users',:odprops)");
 $query->execute([':odprops' => json_encode($userOD)]);

 /***********Creating Object Database (uniq instance)*************************/
 $query = $db->prepare("create table `uniq_1` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) AUTO_INCREMENT=".strval(STARTOBJECTID)." ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $query->execute();
 $query = $db->prepare("ALTER TABLE `uniq_1` ADD eid1 TEXT, ADD UNIQUE(eid1(".USERSTRINGMAXCHAR."))");
 $query->execute();
 $query = $db->prepare("ALTER TABLE `uniq_1` ADD eid6 TEXT, ADD UNIQUE(eid6(".USERSTRINGMAXCHAR."))");
 $query->execute();
 
 /***********Creating Object Database (actual data instance)*************************/
 $query = $db->prepare("create table `data_1` (id MEDIUMINT NOT NULL, last BOOL DEFAULT 1, version MEDIUMINT NOT NULL, date DATE, time TIME, user CHAR(64), eid1 JSON, eid2 JSON, eid3 JSON, eid4 JSON, eid5 JSON, eid6 JSON, PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $query->execute();
 global $odid, $allElementsArray, $uniqElementsArray, $output;
 $odid = '1';
 $allElementsArray = ['1' => '', '2' => '', '3' => '', '4' => '', '5' => '', '6' => ''];
 $uniqElementsArray = ['1' => '', '6' => ''];
 $output = ['1' => ['cmd' => 'RESET', 'value' => DEFAULTUSER, 'password' => password_hash(DEFAULTPASSWORD, PASSWORD_DEFAULT)], '6' => ['cmd' => 'RESET', 'value' => 'Default', 'dialog' => defaultCustomizationDialogJSON('Default')]];
 InsertObject($db);
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
			 foreach (json_decode($query->fetch(PDO::FETCH_NUM)[0], true) as $key => $valeu)
				 if ($key != 'New view') $arr[$value['odname']][$key] = '';
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

function Check($db, $flags)
{
 global $OD, $OV, $input, $alert, $error;
 
 if ($flags & CHECK_OD_OV)
    {
     global $odid;
     
     // Check input OD/OV vars existence
     if (!isset($input['OD']) || !isset($input['OV'])) return $error = 'Incorrect Object Database/View!';
 
     // Check any OD sql database existence
     $query = $db->prepare("SELECT id FROM $ LIMIT 1");
     $query->execute();
     if (count($query->fetchAll(PDO::FETCH_NUM)) == 0) return $error = 'Please create Object Database first!';

     // Empty value OD/OV check
     $OD = $input['OD'];
     $OV = $input['OV'];
     if ($OD === '' || $OV === '') return $error = 'Please create/select Object View!'; 
 
     // Check $OD existence and get its id
     $query = $db->prepare("SELECT id FROM $ WHERE odname='$OD'");
     $query->execute();
     if (count($odid = $query->fetchAll(PDO::FETCH_NUM)) == 0) return $error = 'Please create/select Object View!';
     $odid = $odid[0][0];
    }

 if ($flags & GET_ELEMENT_PROFILES)
    {
     global $allElementsArray, $uniqElementsArray;
     $allElementsArray = $uniqElementsArray = [];

     // Get odname $OD element section
     $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.Element') FROM $ WHERE id='$odid'");
     $query->execute();
     if (count($profiles = $query->fetchAll(PDO::FETCH_NUM)) == 0) return $error = 'Please create/select Object View!';

     // Decode element profiles array form OD props, remove 'New element' section and check elements existence
     $profiles = json_decode($profiles[0][0], true);
     unset($profiles['New element']);
     if (!is_array($profiles) || !count($profiles)) return $error = "Object Database '$OD' has no elements exist!";

     // Convert profiles assoc array to num array with element identificators as array elements instead of profile names and sort it
     foreach ($profiles as $profile => $value)
    	     {
    	      $id = intval(substr($profile, strrpos($profile, ELEMENTPROFILENAMEADDSTRING) + strlen(ELEMENTPROFILENAMEADDSTRING)));  // Calculate current element id
	      $allElementsArray[$id] = $profiles[$profile];
	      if ($value['element3']['data'] === UNIQELEMENTTYPE) $uniqElementsArray[$id] = '';
	     }
     ksort($allElementsArray, SORT_NUMERIC);	 
    }
    
 if ($flags & GET_OBJECT_VIEWS)
    {
     global $elementSelectionJSONList;

     // Get odname $OD view section
     $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE id='$odid'");
     $query->execute();
     if (count($profiles = $query->fetchAll(PDO::FETCH_NUM)) == 0) return $error = 'Please create/select Object View!';

     // Move on. Get specified view JSON element selection (what elements should be displayed and how)
     $elementSelectionJSONList = json_decode($profiles[0][0], true);
     if (!isset($elementSelectionJSONList[$OV]['element5']['data'])) return $error = 'Please create/select Object View!';
     
     // List is empty? Set up default list for all elements: {"eid": "every", "oid": "title|0|newobj", "x": "0..", "y": "0|n"}
     if (($elementSelectionJSONList = trim($elementSelectionJSONList[$OV]['element5']['data'])) === '')
        {
         $x = 0;
         foreach ($allElementsArray as $id => $value)
    	         {
	          $elementSelectionJSONList .= '{"eid": "'.$id.'", "oid": "'.strval(TITLEOBJECTID).'", "x": "'.strval($x).'", "y": "0"}'."\n";
		  $elementSelectionJSONList .= '{"eid": "'.$id.'", "oid": "0", "x": "'.strval($x).'", "y": "n-1"}'."\n";
	          $elementSelectionJSONList .= '{"eid": "'.$id.'", "oid": "'.strval(NEWOBJECTID).'", "x": "'.strval($x).'", "y": "n"}'."\n";
	          $x++;
	    	 }
	}
    }
    
 if ($flags & SET_CMD_DATA)
    {
     global $cmd, $data;

     // Check browser event (cmd) data to be valid and return alert in case of undefined data for KEYPRESS and CONFIRM events
     $cmd = $input['cmd'];
     if (isset($input['data'])) $data = $input['data'];
      else if ($cmd === 'KEYPRESS' || $cmd === 'CONFIRM') return $alert = 'Controller report: undefined browser event data!';
    }
 
 if (($flags & CHECK_OID) && $cmd != 'INIT')
    {
     global $oid;
     
     // Check object identificator value existence
     if (!isset($input['oId']) || $input['oId'] < STARTOBJECTID) return $alert = 'Incorrect object identificator value!';
     $oid = $input['oId'];
     
     // Check database object existence
     $query = $db->prepare("SELECT id FROM `data_$odid` WHERE id=$oid AND last=1 AND version!=0");
     $query->execute();
     if (count($query->fetchAll(PDO::FETCH_NUM)) == 0) return $alert = "Object with id=$oid doesn't exist!\nPlease refresh Object View";
     
     // Check oid object selection existence
     // ...
    }

 if (($flags & CHECK_EID) && $cmd != 'INIT')
    {
     global $eid, $arrayEIdOId;
     
     // Check element identificator value existence
     if (!isset($input['eId'])) return $alert = 'Incorrect element identificator value!';
     $eid = $input['eId'];
     
     // Check element identificator database existence
     if (!isset($allElementsArray[$eid])) return $alert = 'Incorrect element identificator value!';
     
     // Check eid element selection existence
     setElementSelectionIds();
     if (!isset($arrayEIdOId[$eid])) return $alert = 'Please refresh object view, element selection has been changed!';
    }
}

function Handler($handler, $input)
{
 include './'.HANDLERDIR.'/'.$handler;
 if (isset($output))
    {
     $output = json_decode($output, true);
     if (is_array($output) && isset($output['cmd']))
        {
	 // To avoid handler wrong behaviour check handler result and cut its unnecessary output data. First - EDIT and ALERT commands
	 if ($output['cmd'] === 'EDIT' || (substr($output['cmd'], 0, 4) === 'EDIT' && intval(substr($output['cmd'], 4)) > 0) || $output['cmd'] === 'ALERT')
	    if (isset($output['data']) && gettype($output['data']) === 'string') return ['cmd' => $output['cmd'], 'data' => $output['data']];
	     else return ['cmd' => $output['cmd']];
	 // Second - DIALOG command
	 if ($output['cmd'] === 'DIALOG')
	    if (is_array($output['data'])) return ['cmd' => $output['cmd'], 'data' => $output['data']];
	     else return ['cmd' => $output['cmd']];
	 // Third - SET and RESET commands
	 if ($output['cmd'] === 'SET' || $output['cmd'] === 'RESET')
	    {
	     if (isset($output['value']) && gettype($output['value']) != 'string') unset($output['value']);
	     if (isset($output['hint']) && gettype($output['hint']) != 'string') unset($output['hint']);
	     if (isset($output['description']) && gettype($output['description']) != 'string') unset($output['description']);
	     if (isset($output['alert']) && gettype($output['alert']) != 'string') unset($output['alert']);
	     if (isset($output['value']) && strlen($output['value']) > ELEMENTDATAVALUEMAXCHAR) $output['value'] = substr($output['value'], 0, ELEMENTDATAVALUEMAXCHAR);
	     return $output;
	    }
	}
    }
 return ['cmd' => 'UNDEFINED'];
}

function parseJSONEventData($db, $JSONs, $event, $id)
{
 foreach (preg_split("/\n/", $JSONs) as $line) // Split json list and parse its lines to find specified event
      if (($json = json_decode($line, true)) && isset($json['event']) && $json['event'] === $event) // Event match?
         {
	  $eventArray = ['event' => $event];
          foreach ($json as $prop => $value) // Search non reserved array elements to pass them to result event array
	       if ($prop != 'event' && $prop != 'data' && $prop != 'user' && $prop != 'eid' && $prop != 'header')
	       if (gettype($value) === 'string')
		  {
		   $eventArray[$prop] = $value;
		  }
		else if (gettype($value) === 'array' && isset($value['prop']) && gettype($value['prop']) === 'string') // start here
		  {
		   isset($value['eid']) ? $eventArray[$prop] = getElementProperty($db, $value['eid'], $value['prop']) : $eventArray[$prop] = getElementProperty($db, $id, $value['prop']);
		  }
	  break;
	 }
	 
 if (isset($eventArray)) return $eventArray;
 if ($event === 'CONFIRM') return ['event' => 'CONFIRM'];
 return NULL;
}

function getElementProperty($db, $elementId, $prop = NULL)
{
 global $odid, $oid, $eid;
 if (!isset($oid) || !isset($eid)) return NULL;
 if (!isset($elementId)) $elementId = $eid;

 if (isset($prop))
    {
     $query = $db->prepare("SELECT JSON_EXTRACT(eid".strval($elementId).", '$.".$prop."') FROM `data_$odid` WHERE id=$oid AND eid".strval($elementId)." IS NOT NULL ORDER BY version DESC LIMIT 1");
     $query->execute();
     $result = $query->fetchAll(PDO::FETCH_NUM);
     if (count($result) === 0 || count($result[0]) === 0) return NULL;
     $result = str_replace("\\n", "\n", substr($result[0][0], 1, -1));
     $result = str_replace('\\"', '"', $result);
     $result = str_replace("\\\\", "\\", $result);
     return $result;
    }
  else
    {
     $query = $db->prepare("SELECT eid".strval($elementId)." FROM `data_$odid` WHERE id=$oid AND eid".strval($elementId)." IS NOT NULL ORDER BY version DESC LIMIT 1");
     $query->execute();
     $result = $query->fetchAll(PDO::FETCH_NUM);
     if (count($result) === 0 || count($result[0]) === 0) return NULL;
     return json_decode($result[0][0], true);
    }
}

function getElementJSON($db, $elementId)
{
 global $odid, $oid, $eid;
 if (!isset($oid) || !isset($eid)) return NULL;
 if (!isset($elementId)) $elementId = $eid;

 $query = $db->prepare("SELECT eid".strval($elementId)." FROM `data_$odid` WHERE id=$oid AND eid".strval($elementId)." IS NOT NULL ORDER BY version DESC LIMIT 1");
 $query->execute();
 $result = $query->fetchAll(PDO::FETCH_NUM);
 if (count($result) === 0 || count($result[0]) === 0) return NULL;
 return $result[0][0];
}

function InsertObject($db)
{
 global $odid, $allElementsArray, $uniqElementsArray, $output;

 $query = $values = '';
 $params = [];
 foreach ($uniqElementsArray as $id => $value)
	 {
	  $query .= ",eid$id";
	  $values .= ",:eid$id";
	  isset($output[$id]['value']) ? $params[":eid$id"] = $output[$id]['value'] : $params[":eid$id"] = '';
	 }
 if ($query != '') { $query = substr($query, 1); $values = substr($values, 1); }

 $db->beginTransaction();
 $query = $db->prepare("INSERT INTO `uniq_$odid` ($query) VALUES ($values)");
 $query->execute($params);
 
 // Get 
 $query = $db->prepare("SELECT LAST_INSERT_ID()");
 $query->execute();
 // Generate new PDO exception in case of non correct last insert id value               
 if (intval($newId = $query->fetch(PDO::FETCH_NUM)[0]) < STARTOBJECTID)
    {
     $db->rollBack();
     throw new PDOException('Incorrect new object id value!', 0);
    }

 $query = 'id,version'; // Plus date, time, user
 $params = [':id' => $newId, ':version' => '1'];
 $values = ':id,:version';
 foreach ($allElementsArray as $id => $profile) if (isset($output[$id]))
	 if (($json = json_encode($output[$id])) !== false && isset($json))
	    {
	     $query .= ',eid'.strval($id);
	     $params[':eid'.strval($id)] = $json;
	     $values .= ",:eid".strval($id);
	    }
 $query = $db->prepare("INSERT INTO `data_$odid` ($query) VALUES ($values)");
 $query->execute($params);
 
 $db->commit();
}

function DeleteObject($db)
{
 global $odid, $oid;
 
 $db->beginTransaction();
 $query = $db->prepare("SELECT id FROM `data_$odid` WHERE id=$oid AND last=1 AND version!=0 FOR UPDATE");
 $query->execute();
 if (count($query->fetchAll(PDO::FETCH_NUM)) == 0)
    {
     $db->rollBack();
     return "Object with id=$oid is already deleted!\nPlease refresh Object View";
    }
 
 $query = $db->prepare("UPDATE `data_$odid` SET last=0 WHERE id=$oid AND last=1");
 $query->execute();
 $query = $db->prepare("INSERT INTO `data_$odid` (id,version,last) VALUES ($oid,0,1)");
 $query->execute();
 $query = $db->prepare("DELETE FROM `uniq_$odid` WHERE id=$oid");
 $query->execute();
 $db->commit();
}

function CreateNewObjectVersion($db)
{
 global $odid, $oid, $eid, $uniqElementsArray, $allElementsArray, $output;
 
 // Start transaction, select last existing (non zero) version of the object and block the corresponded row
 $db->beginTransaction();
 $query = $db->prepare("SELECT version FROM `data_$odid` WHERE id=$oid AND last=1 AND version!=0 FOR UPDATE");
 $query->execute();
    
 // Get selected version, check the result and calculate next version of the object to be created
 $version = $query->fetchAll(PDO::FETCH_NUM);
 // No rows found? Return an error
 if (count($version) === 0)
    {
     $db->rollBack();
     return "Object with id=$oid not found!\nPlease refresh Object View";
    }
 $version = intval($version[0][0]) + 1;

 // Unset last flag of the object current version and insert new object version with empty data
 $query = $db->prepare("UPDATE `data_$odid` SET last=0 WHERE id=$oid AND last=1; INSERT INTO `data_$odid` (id,version,last) VALUES ($oid,$version,1)");
 $query->execute();
 $query->closeCursor();

 // Update current object uniq element if exist and commit the transaction, so the new version is created.
 if (isset($uniqElementsArray[$eid]) && isset($output[$eid]['value']))
    {
     $query = $db->prepare("UPDATE `uniq_$odid` SET eid$eid=:value WHERE id=$oid");
     $query->execute([':value' => $output[$eid]['value']]);
    }
 $db->commit();
 
 // Read current element json data to merge it with new data in case of 'SET' command, then write to DB
 if ($output[$eid]['cmd'] === 'SET' && gettype($elementData = getElementProperty($db, $eid)) === 'array') $output[$eid] = array_replace($elementData, $output[$eid]);
 $query = $db->prepare("UPDATE `data_$odid` SET eid$eid=:json WHERE id=$oid AND version=$version");
 $query->execute([':json' => json_encode($output[$eid])]);
 
 foreach ($allElementsArray as $id => $profile) if ($id != $eid)
	 {
	  $json = NULL;
	  ///////////////////////////
          if (($handlerName = $profile['element4']['data']) != '' && ($eventArray = parseJSONEventData($db, $profile['element5']['data'], 'ONCHANGE', $id)))
	     {
	      $output[$id] = Handler($handlerName, json_encode($eventArray));
	      if (isset($uniqElementsArray[$id]) && isset($output[$id]['value']))
	         {
	          try {
		       $query = $db->prepare("UPDATE `uniq_$odid` SET eid$id=:value WHERE id=$oid");
		       $query->execute([':value' => $output[$id]['value']]);
		      }
	    	  catch (PDOException $e)
	              {
		       unset($output[$id]);
		      }
	         }
	      if (!isset($output[$id]) || ($output[$id]['cmd'] != 'SET' && $output[$id]['cmd'] != 'RESET'))
	         {
		  $json = getElementJSON($db, $id);
		 }
	       else
	         {
	          if ($output[$id]['cmd'] === 'SET' && gettype($elementData = getElementProperty($db, $id)) === 'array') $output[$id] = array_replace($elementData, $output[$id]);
	          if (($json = json_encode($output[$id])) === false) $json = NULL;
	         }
	     }
	  ////////////////////////
	  if (!isset($json)) $json = getElementJSON($db, $id);
	  if (isset($json))
	     {
	      $query = $db->prepare("UPDATE `data_$odid` SET eid$id=:json WHERE id=$oid AND version=$version");
	      $query->execute([':json' => $json]);
	     }
	   else unset($output[$id]);
	 }
}

function getMainFieldData($db)
{
 global $OD, $OV, $allElementsArray, $elementSelectionJSONList, $objectTable, $odid, $arrayEIdOId;

 // Create result $objectTable array section. First step - init objectTable array (result objects) and $objectTableSrc (object from sql database)
 $objectTable = $objectTableSrc = [];
			     
 // No any element defined?	
 if (($sqlElementList = setElementSelectionIds()) === '') return "Specified view '$OV' (database '$OD') has no elements defined!";
			
 // Object list selection should depends on JSON 'oid' property, specified view page number object range and object selection expression match.
 // While this features are not released, get all objects:
 $query = $db->prepare("SELECT id$sqlElementList FROM `data_$odid` WHERE last=1 AND version!=0");
 $query->execute();
		     
 // Reindex $objectTable array to fit numeric indexes as object identificators to next format:
 //  -----------------------------------------------------------------------------------
 // |  \ eid|               |                                             		|
 // |   \   |       0       |           5.. (was 'eid5' column)             	    	|
 // |oid \  |               |                                             		|
 //  -----------------------------------------------------------------------------------
 // |       |style rules    |                                             		|
 // |   0   |for undefined  |Apply object element props for all objects with element #5 |
 // |       |cells          |                                             		|
 //  -----------------------------------------------------------------------------------
 // |       |Apply styles   |"json" : JSON element data                   		|
 // |   1   |for whole      |"props": props for new object element #5 (eid=5,oid=0)     |	NEWOBJECTID
 // |       |new object     |                                                    	|
 //  -----------------------------------------------------------------------------------
 // |       |Apply styles   |"json" : JSON element data                   		|
 // |   2   |for whole      |"props": props for title object element #5 (eid=5,oid=0)   |	TITLEOBJECTID
 // |       |title object   |                                                           |
 //  -----------------------------------------------------------------------------------
 // |       |Apply styles   |"json" : JSON element data                   		|
 // |  3..  |for whole      |"props": props for real object element #5 (eid=5,oid=0)    |	STARTOBJECTID
 // |       |real object    |                                                   	|
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
				  //if (isset($objectTableSrc[$oid][$eidstr]['style'])) $styles[] = $objectTableSrc[$oid][$eidstr]['style']; // Element style
				  /**/
				  $ss = json_decode($objectTableSrc[$oid][$eidstr], true);
				  if (isset($ss['style'])) $styles[] = $ss['style']; // Element style
				  /**/
				  $objectTable[$oid][$eid]['props']['style'] = mergeStyleRules($styles);
				  //---------------------------Merge style rules end------------------------ 
				 }
				 
			      // Second - for other exact object oids:
		    	      foreach ($value as $oid => $props) if ($oid != 0)
				      {
				       $json = NULL;
				       if ($oid === NEWOBJECTID) $json = json_encode(['value' => '', 'hint' => 'Use mouse double click to enter element text for the new object']);
				       if ($oid === TITLEOBJECTID) $json = json_encode(['value' => $allElementsArray[$eid]['element1']['data'], 'description' => $allElementsArray[$eid]['element2']['data'], 'hint' => 'Title for object element id'.strval($eid).'. '.$allElementsArray[$eid]['element2']['data']]);
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
}

function setElementSelectionIds()
{
 global $arrayEIdOId, $elementSelectionJSONList, $allElementsArray, $objectTable;
 $arrayEIdOId = [];
 $sqlElementList = '';
 
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
 foreach (preg_split("/\n/", $elementSelectionJSONList) as $value)
      if ($j = json_decode($value, true, 2))
	 {
	  $j = cutKeys($j, ['eid', 'oid', 'x', 'y', 'style', 'collapse', 'startevent']);
	  if (!key_exists('eid', $j) || !key_exists('oid', $j)) // eid/oid property doesn't exist? Set some undefined cells features
	     {
	      if (!key_exists('eid', $j) && !key_exists('oid', $j) && (key_exists('style', $j) || key_exists('collapse', $j) || key_exists('tablestyle', $j)))
		 {
		  $objectTable[0] = [0 => []];
		  if (key_exists('style', $j)) $objectTable[0][0]['style'] = $j['style'];
		  if (key_exists('collapse', $j)) $objectTable[0][0]['collapse'] = $j['collapse'];
		  if (key_exists('tablestyle', $j)) $objectTable[0][0]['tablestyle'] = $j['tablestyle'];
		 }
	      continue;
	     }
				 
	  if (gettype($j['eid']) != 'string' || gettype($j['oid']) != 'string') continue; // JSON eid/oid property is not a string? Continue
	  if (!ctype_digit($j['eid']) || !ctype_digit($j['oid'])) continue; // JSON eid/oid property are not numerical? Continue
			      
	  $eid = intval($j['eid']);
	  $oid = intval($j['oid']);
	  
	  // Non zero or zero with style eid index of elements exist?
	  if ((key_exists($eid, $allElementsArray) && gettype($j['x']) === 'string' && gettype($j['y']) === 'string') || ($eid === 0 && key_exists('style', $j)))
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
 
 return $sqlElementList;
}

function NewOD($db)
{
 global $input;
 $input['cmd'] = 'NEWOD';
 
 // Get dialog OD name, cut it and check
 $odname = $input['data']['dialog']['Database']['Properties']['element1']['data'] = substr(trim($input['data']['dialog']['Database']['Properties']['element1']['data']), 0, ODSTRINGMAXCHAR);
 if ($odname === '') return $output = ['cmd' => 'INFO', 'alert' => 'Please input Object Database name!'];

 initNewODDialogElements();
 // Inserting new OD name
 $query = $db->prepare("INSERT INTO `$` (odname) VALUES (:odname)");
 $query->execute([':odname' => $odname]);
 // Getting created properties id
 $query = $db->prepare("SELECT LAST_INSERT_ID()");
 $query->execute();
 $odid = $query->fetch(PDO::FETCH_NUM)[0];
 // Creating instance of Object Database (OD) for json "value" property (for 'uniq' object elements only)
 $query = $db->prepare("create table `uniq_$odid` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) AUTO_INCREMENT=".strval(STARTOBJECTID)." ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $query->execute();                                                                                                                                   
 // Creating 'Object Database' (OD), consists of actual multiple object versions and its elements json data
 $query = $db->prepare("create table `data_$odid` (id MEDIUMINT NOT NULL, last BOOL DEFAULT 1, version MEDIUMINT NOT NULL, date DATE, time TIME, user CHAR(64), PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $query->execute();
 // Insert new OD properties
 $query = $db->prepare("UPDATE `$` SET odprops=:odprops WHERE id=$odid");
 $query->execute([':odprops' => json_encode(adjustODProperties($input['data'], $db, $odid))]);
		    
 return ['cmd' => 'REFRESH', 'data' => getODVNamesForSidebar($db)];
}
		
function EditOD($db)		
{
 global $input;
 $input['cmd'] = 'EDITOD';
 
 // Get dialog old and new OD name
 $newodname = $input['data']['dialog']['Database']['Properties']['element1']['data'] = substr($input['data']['dialog']['Database']['Properties']['element1']['data'], 0, ODSTRINGMAXCHAR);
 $oldodname = $input['data']['flags']['callback'] = substr($input['data']['flags']['callback'], 0, ODSTRINGMAXCHAR);
 // Getting old OD name id in `$`
 $query = $db->prepare("SELECT id FROM `$` WHERE odname=:odname");
 $query->execute([':odname' => $oldodname]);
 $odid = $query->fetch(PDO::FETCH_NUM)[0];
 // In case of empty OD name string try to remove current OD from the system
 if ($newodname === '')
 if ($input['data']['dialog']['Database']['Properties']['element2']['data'] === '' && count($input['data']['dialog']['Element']) === 1)
    {
     $query = $db->prepare("DELETE FROM `$` WHERE id=$odid");
     $query->execute();
     $query = $db->prepare("DROP TABLE IF EXISTS `uniq_$odid`; DROP TABLE IF EXISTS `data_$odid`");
     $query->execute();
     $query->closeCursor();
     return ['cmd' => 'REFRESH', 'data' => getODVNamesForSidebar($db)];
    }
  else return $output = ['cmd' => 'INFO', 'alert' => "To remove Object Database (OD) - empty 'name' and 'description' OD fields and remove all elements (see 'Element' tab)"];
			
 // Writing new properties
 initNewODDialogElements();
 $query = $db->prepare("UPDATE `$` SET odname=:odname,odprops=:odprops WHERE id=$odid");
 $query->execute([':odname' => $newodname, ':odprops' => json_encode(adjustODProperties($input['data'], $db, $odid))]);
		    
 return ['cmd' => 'REFRESH', 'data' => getODVNamesForSidebar($db)];
}

function getUserId($db, $user)
{
 if (gettype($user) != 'string' || $user === '') return '0';
 $query = $db->prepare("SELECT id FROM `uniq_1` WHERE eid1=:user");
 $query->execute([':user' => $user]);
 $id = $query->fetchAll(PDO::FETCH_NUM);
 if (isset($id[0][0])) return $id[0][0];
}

function getUserPass($db, $id)
{
 $query = $db->prepare("SELECT JSON_EXTRACT(eid1, '$.password') FROM `data_1` WHERE id=:id AND eid1 IS NOT NULL ORDER BY version DESC LIMIT 1");
 $query->execute([':id' => $id]);
 $pass = $query->fetchAll(PDO::FETCH_NUM);
 if (isset($pass[0][0])) return substr($pass[0][0], 1, -1);
}

function getUserName($db, $id)
{
 $query = $db->prepare("SELECT JSON_EXTRACT(eid1, '$.value') FROM `data_1` WHERE id=:id AND eid1 IS NOT NULL ORDER BY version DESC LIMIT 1");
 $query->execute([':id' => $id]);
 $name = $query->fetchAll(PDO::FETCH_NUM);
 if (isset($name[0][0])) return substr($name[0][0], 1, -1);
}

function defaultCustomizationDialogJSON($name)
{
 return json_encode(['pad' => ['Scheme' => ['element1' => ['type' => 'text', 'head' => 'Name:', 'data' => $name, 'line' => ''], 'element2' => ['type' => 'text', 'head' => 'Force to use scheme name:', 'data' => '', 'line' => '']], 'Body' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'Background color:', 'data' => 'red']]]]);
}

function getLoginDialogData()
{
 return [
	 'title'   => 'Login',
	 'dialog'  => ['pad' => ['profile' => ['element1' => ['head' => "\nUsername", 'type' => 'text'], 'element2' => ['head' => 'Password', 'type' => 'password']]]],
	 'buttons' => ['LOGIN' => ' '],
	 'flags'   => ['_callback' => 'LOGIN', 'style' => 'min-width: 350px; min-height: 140px; max-width: 1500px; max-height: 500px;']
	];
}
