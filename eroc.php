<?php

const DATABASENAME			= 'OE8';
const MAXOBJECTS			= 100000;
const ODSTRINGMAXCHAR			= 64;
const USERSTRINGMAXCHAR			= '64';
const USERPASSMINLENGTH			= '8';
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
const CHECK_ACCESS			= 0b01000000;
const DEFAULTUSER			= 'root';
const DEFAULTPASSWORD			= 'root';
const SESSIONLIFETIME			= 36000;
const DEFAULTOBJECTSELECTION		= 'WHERE lastversion=1 AND version!=0';

error_reporting(E_ALL);
$db = new PDO('mysql:host=localhost;dbname='.DATABASENAME, 'root', '123');
$db->exec("SET NAMES UTF8");
$db->exec("ALTER DATABASE ".DATABASENAME." CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

session_start();
if (isset($_SERVER['HTTP_HOST'])) setcookie(session_name(), session_id(), time() + SESSIONLIFETIME, '', $_SERVER['HTTP_HOST'], false, true);
    
function rmSQLinjectionChars($str) // Function removes dangerous chars such as: ; ' " %
{
 return str_replace(';', '', str_replace('"', '', str_replace("'", '', str_replace("%", '', $str))));
}

function lg($arg) // Function saves input $arg to error.log
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
         $query = $db->prepare("ALTER TABLE `uniq_$id` ADD eid$eid BLOB(65535), ADD UNIQUE(eid$eid(".UNIQKEYCHARLENGTH."))");
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
 $data['flags']['cmd'] = 'Edit Database Structure';
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
		    'element2' => ['type' => 'textarea', 'head' => "List of users/groups (one by line) allowed or disallowed (see above) to edit this database properties.\nYou must be aware of disallowing all users, so avoid user/group empty list with 'allowed' type list", 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element4' => ['type' => 'textarea', 'head' => 'List of users/groups (one by line) allowed or disallowed (see above) to add/edit object elements', 'data' => '', 'line' => ''],
		    'element5' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element6' => ['type' => 'textarea', 'head' => 'List of users/groups (one by line) allowed or disallowed (see above) to add/edit object views', 'data' => '', 'line' => ''],
		    'element7' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)'],
		    'element8' => ['type' => 'textarea', 'head' => 'List of users/groups (one by line) allowed or disallowed (see above) to add/edit database rules', 'data' => '', 'line' => '']];

 $newElement	 = ['element1' => ['type' => 'textarea', 'head' => 'Element title to display in object view as a header', 'data' => '', 'line' => '', 'help' => 'To remove object element - set empty element header, description and handler file'],
		    'element2' => ['type' => 'textarea', 'head' => 'Element description', 'data' => '', 'line' => '', 'help' => 'Specified description is displayed as a hint on object view element headers navigation.<br>It is used to describe element purpose and its possible values.'],
		    'element3' => ['type' => 'checkbox', 'head' => 'Element type', 'data' => 'unique|', 'line' => '', 'help' => "Unique element type guarantees element value uniqueness among all objects.<br>Element type cannot be changed after element creation."],
		    'element4' => ['type' => 'text', 'head' => 'Server side element event handler file that processes incoming user defined events (see event section below):', 'data' => '', 'line' => ''],
		    'element5' => ['type' => 'textarea', 'head' => 'JSON format event list', 'data' => '', 'line' => '', 'help' => 'Event JSON string (one per line) is a JSON to pass to the element handler as an input argument<br>when specified event occurs. JSONs properties:<br>"event" - event to be processed by the handler, JSONs with undefined event are ignored<br>"user" - user initiated event (automatically set by controller)<br>"eid" - element id (automatically set by controller)<br>"header" - element header (automatically set by controller)<br>Additionally some custom properties can be defined - its string values are sent to the handler<br>without changes with one exception - JSON formated value is replaced by element JSON data.<br>Format of the value: {"eid": "&lt;element id>", "prop": "&lt;element property>"}<br>where "prop" - element property, which value points to the specified by element &lt;eid> JSON data<br>property to be retrieved. In case of "eid" omitted - current element id value is used.<br>In the example below handler on mouse double click event gets JSON<br>with two custom properties. First property value is "test", second value -<br>json element data property "value" of current object element identificator 1:<br>{ "event": "DBLCLICK", "abc": "test", "def": {"eid": "1", "prop": "value"} }'],
		    'element6' => ['type' => 'textarea', 'head' => 'Element scheduler', 'data' => '', 'line' => '', 'help' => "Each element scheduler string (one per line) executes its handler &lt;count> times starting at<br>specified date/time and represents itself one by one space separated args in next format:<br>&lt;minute> &lt;hour> &lt;mday> &lt;month> &lt;wday> &lt;event> &lt;event data> &lt;count><br>See crontab file *nix manual page for date/time args. Zero &lt;count> - infinite calls count.<br>Scheduled call emulates mouse/keyboard events (DBLCLICK and KEYPRESS) with specified<br>&lt;event data> (for KEYPRESS only) and passes 'system' user as an user initiated<br>specified event. Any undefined arg - no call."]];
	
 $newView	 = ['element1' => ['type' => 'text', 'head' => 'Name', 'data' => '', 'line' => '', 'help' => "View name can be changed, but if renamed view name already exists, changes won't be applied.<br>So view name 'New view' can't be set as it is used as an option to create new views.<br>Also symbol '_' as a first character in view name string keeps unnecessary views off sidebar,<br>so they can be called from element handler only.<br>To remove object view - set empty view name string."],
		    'element2' => ['type' => 'textarea', 'head' => 'Description', 'data' => '', 'line' => ''],
		    'element3' => ['type' => 'textarea', 'head' => 'Object selection expression. Empty string selects all objects, error string - no objects.', 'data' => '', 'line' => ''],
		    'element4' => ['type' => 'radio', 'head' => 'Type', 'data' => '+Table|Uplink scheme|Downlink scheme|Graph|Piechart|Map|', 'line' => '', 'help' => "Select object view type from 'table' (displays objects in a form of a table),<br>'scheme' (displays object hierarchy built on uplink and downlink property),<br>'graph' (displays object graphic with one element on 'X' axis, other on 'Y'),<br>'piechart' (displays specified element value statistic on the piechart) and<br>'map' (displays objects on the geographic map)"],
		    'element5' => ['type' => 'textarea', 'head' => 'Element selection expression. Defines what elements should be displayed and how.', 'data' => '', 'line' => ''],
		    'element6' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)|'],
		    'element7' => ['type' => 'textarea', 'head' => 'List of users/groups (one by line) allowed or disallowed (see above) to display this view', 'data' => '', 'line' => ''],
		    'element8' => ['type' => 'radio', 'data' => 'allowed list (disallowed for others)|+disallowed list (allowed for others)|'],
		    'element9' => ['type' => 'textarea', 'head' => 'List of users/groups (one by line) allowed or disallowed (see above) to add/change/delete objects in this view', 'data' => '', 'line' => '']];
							  
 $newRule	 = ['element1' => ['type' => 'text', 'head' => 'Rule name', 'data' => '', 'line' => '', 'help' => "Rule name is displayed as title on the dialog box.<br>Rule name can be changed, but if it already exists, changes won't be applied.<br>So rule name 'New rule' can't be set as it is used as a name for new rules creation.<br>To remove the rule - set rule name to empty string."],
		    'element2' => ['type' => 'textarea', 'head' => 'Rule message', 'data' => '', 'line' => '', 'help' => 'Rule message is match case log message displayed in dialog box.<br>Object element id in figure {#id} or square [#id] brackets retreives<br>appropriate element id value or element id title respectively.<br>Escape character is "\".'],
		    'element3' => ['type' => 'select-one', 'head' => 'Rule action', 'data' => '+No action|Warning|Confirm|Reject|', 'line' => '', 'help' => "All actions shows up dialog box with rule message inside.<br>'Warning' action warns user and apply the changes.<br>'Reject' does the same, but cancels the changes with no chance to keep them.<br>'Confirm' asks wether keep it or reject."],
		    'element4' => ['type' => 'textarea', 'head' => 'Rule expression', 'data' => '', 'line' => '', 'help' => 'Empty or error expression does nothing']];
}

function getODVNamesForSidebar($db)
{
 global $currentuser;
 
 if (!isset($_SESSION['u'])) return [];
 $groups = getUserGroups($db, $_SESSION['u']); // Get current user group list
 $groups[] = $currentuser; // and add username at the end of array

 $arr = [];
 $query = $db->prepare("SELECT odname FROM `$`");
 $query->execute();
 foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $value)
	 {
	  $arr[$value['odname']] = [];
	  $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE odname='$value[odname]'");
	  $query->execute();
	  foreach (json_decode($query->fetch(PDO::FETCH_NUM)[0], true) as $key => $View) if ($key != 'New view')
		  {
		   if (count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $View['element7']['data'])), "strcmp")))
		      { if ($View['element6']['data'] === 'allowed list (disallowed for others)|+disallowed list (allowed for others)|') continue; }
		    else 
		      { if ($View['element6']['data'] === '+allowed list (disallowed for others)|disallowed list (allowed for others)|') continue; }
		   $arr[$value['odname']][$key] = '';
		  }
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

function Check($db, $flags)
{
 global $input, $OD, $OV, $paramsOV, $sidebar, $alert, $error, $currentuser;

 if ($flags & CHECK_OD_OV)
    {
     global $odid;
     
     // Check input OD/OV vars existence
     if (!isset($input['OD']) || !isset($input['OV'])) return $error = 'Incorrect Object Database/View!';
 
     // Check any OD for the current user
     $sidebar = getODVNamesForSidebar($db);
     if (count($sidebar) == 0) return $error = 'Please create Object Database first!';

     // Empty value OD/OV check
     $OD = $input['OD'];
     $OV = $input['OV'];
     if ($OD === '' || $OV === '') return $error = 'Please create/select Object View!'; 
 
     // Check $OD existence and get its id
     $query = $db->prepare("SELECT id FROM $ WHERE odname='$OD'");
     $query->execute();
     if (count($odid = $query->fetchAll(PDO::FETCH_NUM)) == 0) return $error = "Database '$OD' Object View '$OV' not found!";
     $odid = $odid[0][0];
     if (isset($input['paramsOV'])) $paramsOV = $input['paramsOV'];
      else $paramsOV = [];
    }

 if ($flags & GET_ELEMENT_PROFILES)
    {
     global $allElementsArray, $uniqElementsArray;
     $allElementsArray = $uniqElementsArray = [];

     // Get odname $OD element section
     $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.Element') FROM $ WHERE id='$odid'");
     $query->execute();
     if (count($profiles = $query->fetchAll(PDO::FETCH_NUM)) == 0) return $error = "Database '$OD' Object View '$OV' not found!";

     // Decode element profiles array form OD props, remove 'New element' section and check elements existence
     $profiles = json_decode($profiles[0][0], true);
     unset($profiles['New element']);
     if (!is_array($profiles) || !count($profiles)) return $error = "Database '$OD' has no elements exist!";

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
     global $elementSelection, $objectSelection;
     
     // Get odname $OD view section
     $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE id='$odid'");
     $query->execute();
     if (count($viewProfiles = $query->fetchAll(PDO::FETCH_NUM)) == 0) return $error = "Database '$OD' Object View '$OV' not found!";

     // Move on. Get specified view JSON element selection (what elements should be displayed and how)
     $viewProfiles = json_decode($viewProfiles[0][0], true);
     if (!isset($viewProfiles[$OV]['element5']['data'])) return $error = "Database '$OD' Object View '$OV' not found!";

     // Fetch object selection query string
     $objectSelection = $viewProfiles[$OV]['element3']['data'];
     
     // List is empty? Set up default list for all elements: {"eid": "every", "oid": "title|0|newobj", "x": "0..", "y": "0|n"}
     if (($elementSelection = trim($viewProfiles[$OV]['element5']['data'])) === '' || $elementSelection === '*' || $elementSelection === '**' || $elementSelection === '***')
        {
         $x = 0;
	 $startline = 'n+1';
	 if ($elementSelection === '*' || $elementSelection === '***') $startline = 'n+2';
	 $arr = $allElementsArray;
	 if ($elementSelection === '**' || $elementSelection === '***') $arr = ['id' => '', 'version' => '', 'owner' => '', 'datetime' => ''] + $arr;
	 $elementSelection = '';
         foreach ($arr as $id => $value)
    	         {
	          $elementSelection .= '{"eid": "'.$id.'", "oid": "'.strval(TITLEOBJECTID).'", "x": "'.strval($x).'", "y": "0"}'."\n";
	          if ($startline === 'n+2') $elementSelection .= '{"eid": "'.$id.'", "oid": "'.strval(NEWOBJECTID).'", "x": "'.strval($x).'", "y": "1"}'."\n";
		  $elementSelection .= '{"eid": "'.$id.'", "oid": "0", "x": "'.strval($x).'", "y": "'.$startline.'"}'."\n";
	          $x++;
	    	 }
	}
    }

 if ($flags & SET_CMD_DATA)
    {
     global $cmd, $data;

     // Check client event (cmd) data to be valid and return alert in case of undefined data for KEYPRESS and CONFIRM events
     $cmd = $input['cmd'];
     if (isset($input['data'])) $data = $input['data'];
      else if ($cmd === 'KEYPRESS' || $cmd === 'CONFIRM') return $alert = 'Undefined client event data!';
    }
 
 if (($flags & CHECK_OID) && $cmd != 'INIT')
    {
     global $oid;
     
     // Check object identificator value existence
     if (!isset($input['oId']) || $input['oId'] < STARTOBJECTID) return $alert = 'Incorrect object identificator value!';
     if (($oid = $input['oId']) === STARTOBJECTID && $odid == 1 && $cmd === 'DELETEOBJECT') return $alert = 'System account cannot be deleted!';
     
     // Check database object existence -> Check oid object selection existence
     $query = $db->prepare("SELECT id FROM `data_$odid` WHERE id=$oid AND lastversion=1 AND version!=0");
     $query->execute();
     if (count($query->fetchAll(PDO::FETCH_NUM)) == 0) return $alert = "Object with id=$oid doesn't exist!\nPlease refresh Object View";
    }

 if (($flags & CHECK_EID) && $cmd != 'INIT')
    {
     global $eid, $props;
     
     // Check element identificator value existence
     if (!isset($input['eId'])) return $alert = 'Incorrect element identificator value!';
     $eid = $input['eId'];
     
     // Check element identificator database existence
     if (!isset($allElementsArray[$eid])) return $alert = 'Incorrect element identificator value!';
     
     // Check eid element selection existence
     setElementSelectionIds();
     if (!isset($props[strval($eid)])) return $alert = 'Please refresh object view, element selection has been changed!';
    }

 if ($flags & CHECK_ACCESS)
    {
     if (!isset($_SESSION['u'])) return $alert = 'Please authorize!';
     switch ($input['cmd'])
    	    {
    	     case 'New Object Database':
	          if (getUserODAddPermission($db, $_SESSION['u']) != '+Allow user to add Object Databases|') return $alert = "You're not allowed to add Object Databases!";
		  break;
	     case 'GETMAINSTART':
	     case 'GETMAIN':
	     case 'DELETEOBJECT':
	     case 'INIT':
	     case 'KEYPRESS':
	     case 'DBLCLICK':
	     case 'CONFIRM':
	          if (($input['cmd'] === 'GETMAINSTART' || $input['cmd'] === 'GETMAIN') && ($input['OD'] === '' || $input['OV'] === '')) return;
		  
		  if (!isset($OD) || !isset($OV) || !isset($odid)) return $alert = "You're not allowed to modify this Object View!";
		     
	          $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE id='$odid'");
		  $query->execute();
		  if (count($View = $query->fetchAll(PDO::FETCH_NUM)) == 0) return $alert = "You're not allowed to modify this Object View!";
		  $View = json_decode($View[0][0], true)[$OV];	// Set current view array data
		  $groups = getUserGroups($db, $_SESSION['u']); // Get current user group list
		  $groups[] = $currentuser; // and add username at the end of array
		  if (count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $View['element7']['data'])), "strcmp")))
		     {
		      if ($View['element6']['data'] === 'allowed list (disallowed for others)|+disallowed list (allowed for others)|')
		         return $error = "You're not allowed to display or modify this Object View!";
		     }
		   else
		     {
		      if ($View['element6']['data'] === '+allowed list (disallowed for others)|disallowed list (allowed for others)|')
		         return $error = "You're not allowed to display or modify this Object View!";
		     }
		  if ($input['cmd'] === 'GETMAIN' || $input['cmd'] === 'GETMAINSTART') return;
		  
		  if (count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $View['element9']['data'])), "strcmp")))
		     {
		      if ($View['element8']['data'] === 'allowed list (disallowed for others)|+disallowed list (allowed for others)|')
		         return $alert = "You're not allowed to modify this Object View!";
		     }
		   else
		     {
		      if ($View['element8']['data'] === '+allowed list (disallowed for others)|disallowed list (allowed for others)|')
		         return $alert = "You're not allowed to modify this Object View!";
		     }
		  break;
	    }
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
	 // Then CALL command
	 if ($output['cmd'] === 'CALL')
	 if (!isset($output['data'])) return ['cmd' => $output['cmd']];
	  else if (gettype($output['data']) === 'array') return ['cmd' => $output['cmd'], 'data' => $output['data']];
	}
    }
 return ['cmd' => 'UNDEFINED'];
}

function parseJSONEventData($db, $JSONs, $event, $id)
{
 global $currentuser;
 
 foreach (preg_split("/\n/", $JSONs) as $line) // Split json list and parse its lines to find specified event
      if (($json = json_decode($line, true)) && isset($json['event']) && $json['event'] === $event) // Event match?
         {
	  $eventArray = ['event' => $event];
          foreach ($json as $prop => $value) // Search non reserved array elements to pass them to result event array
	       if ($prop != 'event' && $prop != 'data' && $prop != 'user' && $prop != 'title')
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
	 
 if (!isset($eventArray) && $event === 'CONFIRM') $eventArray = ['event' => 'CONFIRM'];
 if (isset($eventArray))
    {
     global $allElementsArray;
     $eventArray['user'] = $currentuser;
     $eventArray['title'] = $allElementsArray[$id]['element1']['data'];
     return $eventArray;
    }
}

function getElementProperty($db, $elementId, $prop, $version = NULL)
{
 global $odid, $oid, $eid;
 if (!isset($elementId)) $elementId = $eid;
 return getElementPropStrict($db, $odid, $oid, $elementId, $prop, $version);
}


function getElementPropStrict($db, $odid, $oid, $eid, $prop, $version = NULL)
{
 if (!isset($odid) || !isset($oid) || !isset($eid) || !isset($prop)) return NULL;

 if (isset($version)) $query = $db->prepare("SELECT JSON_EXTRACT(eid".strval($eid).", '$.".$prop."') FROM `data_$odid` WHERE id=$oid AND version='".strval($version)."'");
  else $query = $db->prepare("SELECT JSON_EXTRACT(eid".strval($eid).", '$.".$prop."') FROM `data_$odid` WHERE id=$oid AND lastversion=1 AND version!=0");
 $query->execute();
 
 $result = $query->fetchAll(PDO::FETCH_NUM);
 if (!isset($result[0][0])) return NULL;
 
 $result = str_replace("\\n", "\n", substr($result[0][0], 1, -1));
 $result = str_replace('\\"', '"', $result);
 return str_replace("\\\\", "\\", $result);
}

function getElementArray($db, $elementId, $version = NULL)
{
 global $odid, $oid, $eid;
 if (!isset($oid) || !isset($eid)) return NULL;
 if (!isset($elementId)) $elementId = $eid;

 if (isset($version)) $query = $db->prepare("SELECT eid".strval($elementId)." FROM `data_$odid` WHERE id=$oid AND version='".strval($version)."'");
  else $query = $db->prepare("SELECT eid".strval($elementId)." FROM `data_$odid` WHERE id=$oid AND lastversion=1 AND version!=0");
 $query->execute();
 
 $result = $query->fetchAll(PDO::FETCH_NUM);
 if (!isset($result[0][0])) return NULL;

 return json_decode($result[0][0], true);
}

function getElementJSON($db, $elementId, $version = NULL)
{
 global $odid, $oid, $eid;
 if (!isset($oid) || !isset($eid)) return NULL;
 if (!isset($elementId)) $elementId = $eid;

 if (isset($version)) $query = $db->prepare("SELECT eid".strval($elementId)." FROM `data_$odid` WHERE id=$oid AND version='".strval($version)."'");
  else $query = $db->prepare("SELECT eid".strval($elementId)." FROM `data_$odid` WHERE id=$oid AND lastversion=1 AND version!=0");
 $query->execute();

 $result = $query->fetchAll(PDO::FETCH_NUM);
 if (!isset($result[0][0])) return NULL;
 
 return $result[0][0];
}

function InsertObject($db, $owner = NULL)
{
 global $odid, $allElementsArray, $uniqElementsArray, $output, $currentuser;

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
 
 // Get last inserted object id
 $query = $db->prepare("SELECT LAST_INSERT_ID()");
 $query->execute();
 // Generate new PDO exception in case of non correct last insert id value               
 if (intval($newId = $query->fetch(PDO::FETCH_NUM)[0]) < STARTOBJECTID)
    {
     $db->rollBack();
     throw new PDOException('Incorrect new object id value!', 0);
    }

 if (!isset($owner)) $owner = $currentuser;
 $query = 'id,version,owner';
 $params = [':id' => $newId, ':version' => '1', ':owner' => $owner];
 $values = ':id,:version,:owner';
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
 global $odid, $oid, $alert, $currentuser;
 
 $db->beginTransaction();
 $query = $db->prepare("SELECT id FROM `data_$odid` WHERE id=$oid AND lastversion=1 AND version!=0 FOR UPDATE");
 $query->execute();
 if (count($query->fetchAll(PDO::FETCH_NUM)) == 0)
    {
     $db->rollBack();
     return $alert = "Object with id=$oid is already deleted!\nPlease refresh Object View";
    }

 $query = $db->prepare("UPDATE `data_$odid` SET lastversion=0 WHERE id=$oid AND lastversion=1");
 $query->execute();
 $query = $db->prepare("INSERT INTO `data_$odid` (id,version,lastversion,owner) VALUES ($oid,0,1,:owner)");
 $query->execute([':owner' => $currentuser]);
 $query = $db->prepare("DELETE FROM `uniq_$odid` WHERE id=$oid");
 $query->execute();
 $db->commit();
}

function WriteElement($db, $oid, $eid, $version)
{
 global $uniqElementsArray, $odid, $output;

 if (!isset($output[$eid]['cmd']) || ($output[$eid]['cmd'] != 'SET' && $output[$eid]['cmd'] != 'RESET')) // No element new version exist, so wrote
    {
     $query = $db->prepare("UPDATE `data_$odid` SET eid$eid=:json WHERE id=$oid AND version=$version");
     $query->execute([':json' => getElementJSON($db, $eid, $version - 1)]);
     unset($output[$eid]);
     return;
    }
  else if (isset($uniqElementsArray[$eid]) && isset($output[$eid]['value'])) // Update current object uniq element if exist and commit the transaction, so the new version is created.
    {
     $query = $db->prepare("UPDATE `uniq_$odid` SET eid$eid=:value WHERE id=$oid");
     $query->execute([':value' => $output[$eid]['value']]);
    }

 // Read current element json data to merge it with new data in case of 'SET' command, then write to DB
 if ($output[$eid]['cmd'] === 'SET' && gettype($oldData = getElementArray($db, $eid, $version - 1)) === 'array') $output[$eid] = array_replace($oldData, $output[$eid]);
 $query = $db->prepare("UPDATE `data_$odid` SET eid$eid=:json WHERE id=$oid AND version=$version");
 $query->execute([':json' => json_encode($output[$eid])]);
}

function CreateNewObjectVersion($db)
{
 global $odid, $oid, $eid, $uniqElementsArray, $allElementsArray, $output, $currentuser;
 
 //--------------Start transaction, select last existing (non zero) version of the object and block the corresponded row---------------
 $db->beginTransaction();
 $query = $db->prepare("SELECT version FROM `data_$odid` WHERE id=$oid AND lastversion=1 AND version!=0 FOR UPDATE");
 $query->execute();
 // Get selected version, check the result and calculate next version of the object to be created

 $version = $query->fetchAll(PDO::FETCH_NUM);
 // No rows found? Return an error
 if (count($version) === 0) { $db->rollBack(); return "Object with id=$oid not found!\nPlease refresh Object View"; }
 // Increment version to use it as a new version of the object
 $version = intval($version[0][0]) + 1;
 
 // Unset last flag of the object current version and insert new object version with empty data
 $query = $db->prepare("UPDATE `data_$odid` SET lastversion=0 WHERE id=$oid AND lastversion=1; INSERT INTO `data_$odid` (id,owner,version,lastversion) VALUES ($oid,:owner,$version,1)");
 $query->execute([':owner' => $currentuser]);
 $query->closeCursor();
 $db->commit();
 //------------------------------------------------------------------------------------------------------------------------------------
 
 //------------Empty object version is created, so start new transaction and write all object elements handler result data-------------
 $db->beginTransaction();
 WriteElement($db, $oid, $eid, $version); // First write element data that initiated SET/RESET command
 foreach ($allElementsArray as $id => $profile) if ($id != $eid) // Second - write all other elemtnts data as answers to the ONCHANGE command
	 {
	  $output[$id] = NULL;
	  if (($handlerName = $profile['element4']['data']) != '' && ($eventArray = parseJSONEventData($db, $profile['element5']['data'], 'ONCHANGE', $id)))
	     $output[$id] = Handler($handlerName, json_encode($eventArray));
	  try { WriteElement($db, $oid, $id, $version); }
	  catch (PDOException $e) { unset($output[$id]); }
	 }
 $db->commit();
 //------------------------------------------------------------------------------------------------------------------------------------
}

function getMainFieldData($db)
{
 global $OD, $OV, $odid, $props, $objectSelection, $paramsOV, $output, $error;

 // Get object selection query string, in case of array as a return result send dialog to the client to fetch up object selection params
 $objectSelection = GetObjectSelection($db, $objectSelection);
 if (gettype($objectSelection) === 'array')
    {
     if ((!isset($output['cmd']) || $output['cmd'] != 'CALL') && $paramsOV != []) $objectSelection['title'] = 'View parameters has been changed, please try again!';
     $output = ['cmd' => 'DIALOG', 'data' => $objectSelection];
     return;
    }
 
 // Get element selection query string, in case of empty result return no element message as an error
 $elementQueryString = '';
 setElementSelectionIds();
 foreach ($props as $key => $value) if (intval($key) > 0) $elementQueryString .= ',eid'.$key;
 if ($elementQueryString === '') return $error = "Specified view '$OV' (database '$OD') has no elements defined!";
     
 // Return OV refresh command to the client with object selection sql query result as a main field data
 $query = $db->prepare("SELECT id,version,owner,datetime,lastversion$elementQueryString FROM `data_$odid` $objectSelection");
 $query->execute();
 $output = ['cmd' => 'REFRESH', 'data' => $query->fetchAll(PDO::FETCH_ASSOC), 'props' => $props, 'paramsOV' => $paramsOV];
}

function GetObjectSelection($db, $objectSelection)
{
 global $paramsOV, $currentuser;
 
 // Check input paramValues array and add reserved :user parameter value
 if (gettype($objectSelection) != 'string' || ($objectSelection = trim($objectSelection)) === '') return DEFAULTOBJECTSELECTION;
 $i = -1;
 $len = strlen($objectSelection);
 if (gettype($paramsOV) != 'array') $paramsOV = [];
 $paramsOV[':user'] = $currentuser;
 $isDialog = false;
 $objectSelectionNew = '';
 
 // Check $objectSelection every char and retrieve params in non-quoted substrings started with ':' and finished with space or another ':'
 while  (++$i <= $len)
     if ($i === $len || $objectSelection[$i] === '"' || $objectSelection[$i] === "'" || $objectSelection[$i] === ':' || $objectSelection[$i] === ' ')
	{
	 if (isset($newparam))
	 if (isset($paramsOV[$newparam]))
	    {
	     $objectSelectionParamsDialogProfiles[$newparam] = ['head' => "\n".str_replace('_', ' ', substr($newparam, 1)).':', 'type' => 'text', 'data' => $paramsOV[$newparam]];
	     if (!$isDialog) $objectSelectionNew .= $paramsOV[$newparam];
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
 unset($paramsOV[':user']);
 if (!$isDialog) return $objectSelectionNew;
 
 // Otherwise return dialog array
 return [
	 'title'   => 'Object View parameters',
	 'dialog'  => ['pad' => ['profile' => $objectSelectionParamsDialogProfiles]],
	 'buttons' => ['OK' => ' ', 'CANCEL' => 'background-color: red;'],
	 'flags'   => ['cmd' => 'GETMAIN',
		       'style' => 'min-width: 350px; min-height: 140px; max-width: 1500px; max-height: 500px;']
	];
}

function setElementSelectionIds()
{
 global $props, $elementSelection, $allElementsArray;
 $props = [];
 
 //  ------ --------------------------------- ----------------------------------- ---------------------------------------
 // |  \eid|                0                |              1..         	 | id,version,owner,datetime,lastversion |
 // |oid\  |                                 | 					 |					 |
 //  ------ --------------------------------- ----------------------------------- ---------------------------------------
 // |  0   | undefined cell: style, collapse | object in selection:     	 | object service info:			 |
 // |      | html table:     tablestyle      | x, y, style, collapse    	 | x, y, style				 |
 //  ------ --------------------------------- ----------------------------------- ---------------------------------------
 // |  1   | new object:     style     	     | new object:			 |		   -			 |
 // |      | 				     | x, y, style, startevent, _hint	 |					 |
 //  ------ --------------------------------- ----------------------------------- ---------------------------------------
 // |  2   | title object:   style           | title object:			 |		   -			 |
 // |      | 				     | x, y, style, _title, _hint	 |					 |
 //  ------ --------------------------------- ----------------------------------- ---------------------------------------
 // | 3..  | exact object:   style     	     | exact object: 			 | object service info:			 |
 // |      | 				     | x, y, style, startevent, collapse | x, y, style				 |
 //  ------ --------------------------------- ----------------------------------- ---------------------------------------
 
 
 foreach (preg_split("/\n/", $elementSelection) as $value)
      if ($arr = json_decode($value, true, 2))
	 {
	  $arr = cutKeys($arr, ['eid', 'oid', 'x', 'y', 'style', 'collapse', 'startevent', 'tablestyle']); // Retrieve correct values only
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
		  default: // Parse all other numeric elements that defines styles, x-y coordinates, collapse capability and 'startevent' event for new, title, selection and exact objects
		       if (!key_exists($eid, $allElementsArray)) break;
		       if (key_exists('startevent', $arr)) $props[$eid][$oid]['startevent'] = $arr['startevent'];
		       if (gettype($arr['x']) === 'string' && gettype($arr['y']) === 'string')
		          {
			   $props[$eid][$oid]['x'] = $arr['x'];
			   $props[$eid][$oid]['y'] = $arr['y'];
			   if (key_exists('collapse', $arr)) $props[$eid][$oid]['collapse'] = '';
			   if (key_exists('style', $arr)) $props[$eid][$oid]['style'] = $arr['style'];
			   if (intval($oid) == TITLEOBJECTID)
			      {
			       $props[$eid][$oid]['title'] = $allElementsArray[$eid]['element1']['data'];
			       $props[$eid][$oid]['hint'] = $allElementsArray[$eid]['element2']['data'];
			      }
			   if (intval($oid) == NEWOBJECTID) $props[$eid][$oid]['hint'] = "Table cell to input new object data for element id: $eid";
			  }
		 }
	 }
}

function CheckODString($odname)
{
 return substr(str_replace("'", '', str_replace('"', '', trim(str_replace("\\", '', $odname)))), 0, ODSTRINGMAXCHAR);
}

function NewOD($db)
{
 global $input;
 
 // Get dialog OD name, cut it and check
 $odname = CheckODString($input['data']['dialog']['Database']['Properties']['element1']['data']);
 if ($odname === '') return $output = ['cmd' => 'INFO', 'alert' => 'Object Database name cannot be empty!'];
 $input['data']['dialog']['Database']['Properties']['element1']['data'] = $odname;

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
 $query = $db->prepare("create table `data_$odid` (id MEDIUMINT NOT NULL, lastversion BOOL DEFAULT 1, version MEDIUMINT NOT NULL, owner CHAR(64), datetime DATETIME DEFAULT NOW(), PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $query->execute();
 // Insert new OD properties
 $query = $db->prepare("UPDATE `$` SET odprops=:odprops WHERE id=$odid");
 $query->execute([':odprops' => json_encode(adjustODProperties($input['data'], $db, $odid))]);
		    
 return ['cmd' => '', 'sidebar' => getODVNamesForSidebar($db)];
}
		
function EditOD($db)		
{
 global $input, $currentuser;
 
 // Get dialog old and new OD name
 $newodname = CheckODString($input['data']['dialog']['Database']['Properties']['element1']['data']);
 $input['data']['dialog']['Database']['Properties']['element1']['data'] = $newodname;
 $oldodname = $input['data']['flags']['callback'];
 
 // Getting old OD name id in `$`
 $query = $db->prepare("SELECT id, odprops FROM `$` WHERE odname=:odname");
 $query->execute([':odname' => $oldodname]);
 $odid = $query->fetchAll(PDO::FETCH_NUM);
 if (isset($odid[0][0]) && isset($odid[0][1]))
    {
     $odprops = $odid[0][1];
     $odid = $odid[0][0];
    }
  else return $output = ['cmd' => 'INFO', 'alert' => "Failed to get Object Database properties!"];
 
 // In case of empty OD name string try to remove current OD from the system
 if ($newodname === '')
 if ($input['data']['dialog']['Database']['Properties']['element2']['data'] === '' && count($input['data']['dialog']['Element']) === 1)
    {
     $query = $db->prepare("DELETE FROM `$` WHERE id=$odid");
     $query->execute();
     $query = $db->prepare("DROP TABLE IF EXISTS `uniq_$odid`; DROP TABLE IF EXISTS `data_$odid`");
     $query->execute();
     $query->closeCursor();
     return ['cmd' => '', 'sidebar' => getODVNamesForSidebar($db)];
    }
  else return $output = ['cmd' => 'INFO', 'alert' => "To remove Object Database (OD) - empty 'name' and 'description' OD fields and remove all elements (see 'Element' tab)"];

 // Decode current OD props
 $odprops = json_decode($odprops, true);
 if (isset($odprops['dialog']['Database']['Permissions'])) $dbPermissions = $odprops['dialog']['Database']['Permissions'];
  else return $output = ['cmd' => 'INFO', 'alert' => "Failed to get Object Database properties!"];
  
 // Check current OD permissions to fetch new OD data from dialog box - $input['data']['dialog']['Database']['Permissions'])..
 $alertstring = '';
 $groups = getUserGroups($db, $_SESSION['u']); // Get current user group list
 $groups[] = $currentuser; // and add username at the end of array
 
 // Check 'Database' pad change permissions
 if ($input['data']['dialog']['Database'] != $odprops['dialog']['Database'])
 if (count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $dbPermissions['element2']['data'])), "strcmp")))
    {
     if ($dbPermissions['element1']['data'] === 'allowed list (disallowed for others)|+disallowed list (allowed for others)|')
	{
	 $alertstring .= "'Database'";
	 $input['data']['dialog']['Database'] = $odprops['dialog']['Database'];
	}
    }
  else
    {
     if ($dbPermissions['element1']['data'] === '+allowed list (disallowed for others)|disallowed list (allowed for others)|')
	{
	 $alertstring .= "'Database'";
	 $input['data']['dialog']['Database'] = $odprops['dialog']['Database'];
	}
    }
 // Check 'Element' pad change permissions
 if ($input['data']['dialog']['Element'] != $odprops['dialog']['Element'])
 if (count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $dbPermissions['element4']['data'])), "strcmp")))
    {
     if ($dbPermissions['element3']['data'] === 'allowed list (disallowed for others)|+disallowed list (allowed for others)|')
	{
	 $alertstring .= "'Element'";
	 $input['data']['dialog']['Element'] = $odprops['dialog']['Element'];
	}
    }
  else
    {
     if ($dbPermissions['element3']['data'] === '+allowed list (disallowed for others)|disallowed list (allowed for others)|')
	{
	 $alertstring .= "'Element'";
	 $input['data']['dialog']['Element'] = $odprops['dialog']['Element'];
	}
    }
 // Check 'View' pad change permissions
 if ($input['data']['dialog']['View'] != $odprops['dialog']['View'])
 if (count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $dbPermissions['element6']['data'])), "strcmp")))
    {
     if ($dbPermissions['element5']['data'] === 'allowed list (disallowed for others)|+disallowed list (allowed for others)|')
	{
	 $alertstring .= "'View'";
	 $input['data']['dialog']['View'] = $odprops['dialog']['View'];
	}
    }
  else
    {
     if ($dbPermissions['element5']['data'] === '+allowed list (disallowed for others)|disallowed list (allowed for others)|')
	{
	 $alertstring .= "'View'";
	 $input['data']['dialog']['View'] = $odprops['dialog']['View'];
	}
    }
 // Check 'Rule' pad change permissions
 if ($input['data']['dialog']['Rule'] != $odprops['dialog']['Rule'])
 if (count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $dbPermissions['element8']['data'])), "strcmp")))
    {
     if ($dbPermissions['element7']['data'] === 'allowed list (disallowed for others)|+disallowed list (allowed for others)|')
	{
	 $alertstring .= "'Rule'";
	 $input['data']['dialog']['Rule'] = $odprops['dialog']['Rule'];
	}
    }
  else
    {
     if ($dbPermissions['element7']['data'] === '+allowed list (disallowed for others)|disallowed list (allowed for others)|')
	{
	 $alertstring .= "'Rule'";
	 $input['data']['dialog']['Rule'] = $odprops['dialog']['Rule'];
	}
    }

 // Writing new properties
 initNewODDialogElements();
 $query = $db->prepare("UPDATE `$` SET odname=:odname,odprops=:odprops WHERE id=$odid");
 $query->execute([':odname' => $newodname, ':odprops' => json_encode(adjustODProperties($input['data'], $db, $odid))]);

 // Return result		    
 if ($alertstring === '') return ['cmd' => '', 'sidebar' => getODVNamesForSidebar($db)];
 return ['cmd' => 'INFO', 'alert' => "You're not allowed to change ".$alertstring." properties!", 'sidebar' => getODVNamesForSidebar($db)];
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
 $query = $db->prepare("SELECT JSON_EXTRACT(eid1, '$.password') FROM `data_1` WHERE id=:id AND lastversion=1 AND version!=0");
 $query->execute([':id' => $id]);
 $pass = $query->fetchAll(PDO::FETCH_NUM);
 if (isset($pass[0][0])) return substr($pass[0][0], 1, -1);
}

function getUserName($db, $id)
{
 if (!isset($id)) return '';
 $query = $db->prepare("SELECT JSON_EXTRACT(eid1, '$.value') FROM `data_1` WHERE id=:id AND lastversion=1 AND version!=0");
 $query->execute([':id' => $id]);
 $name = $query->fetchAll(PDO::FETCH_NUM);
 if (isset($name[0][0])) return substr($name[0][0], 1, -1);
 return '';
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

function getUserCustomization($db, $oid, $current = false)
{
 // Check $id existence
 if (!isset($oid)) return;
 // Get current user JSON customization and decode it
 $customization = json_decode(getElementPropStrict($db, '1', $oid, '6', 'dialog'), true);
 // Wrong result data? Return NULL
 if (!isset($customization) || $customization === false || $customization === true || !is_array($customization) || !isset($customization['pad']['misc customization']['element5']['data'])) return;
 // Flag 'current' is set? Return current customization
 if ($current) return $customization;
 
 // If current user customization forces to use another user customization, and the user does exist, and the user id doesn't point to itself - get it and return it
 if ($customization['pad']['misc customization']['element5']['data'] != '' && ($uid = getUserId($db, $customization['pad']['misc customization']['element5']['data'])) && strval($uid) != strval($id))
    {
     $customization = json_decode(getElementPropStrict($db, '1', $uid, '6', 'dialog'), true);
     if (!isset($customization) || $customization === false || $customization === true || !is_array($customization) || !isset($customization['pad']['misc customization']['element5']['data'])) return;
    }
 
 return $customization;
}

function getLoginDialogData()
{
 return [
	 'title'   => 'Login',
	 'dialog'  => ['pad' => ['profile' => ['element1' => ['head' => "\nUsername", 'type' => 'text'], 'element2' => ['head' => 'Password', 'type' => 'password']]]],
	 'buttons' => ['LOGIN' => ' '],
	 'flags'   => ['cmd' => 'LOGIN', 'style' => 'min-width: 350px; min-height: 140px; max-width: 1500px; max-height: 500px;']
	];
}

function LogMessage($db, $message, $type = 'error')
{
 global $odid, $allElementsArray, $uniqElementsArray, $output;

 $odid = '2';
 $allElementsArray = ['1' => '', '2' => ''];
 $uniqElementsArray = [];
 $output = ['1' => ['cmd' => 'RESET', 'value' => $type], '2' => ['cmd' => 'RESET', 'value' => $message]];
 InsertObject($db, 'system');
}

function defaultCustomizationDialogJSON()
{
 // To transfer uiProfile from main.js: get uiProfile JSON from console by "console.log(JSON.stringify(uiProfile))" and put it json_decode below.
 // Don't forget to escape single quotes by "\'"
 $uiProfile = json_decode('{"body":{"target":"body","background-color":"#343E54;"},"sidebar":{"target":".sidebar","background-color":"rgb(17,101,176);","border-radius":"5px;","color":"#9FBDDF;","width":"13%;","height":"90%;","left":"4%;","top":"5%;","scrollbar-color":"#1E559D #266AC4;","scrollbar-width":"thin;","box-shadow":"4px 4px 5px #222;"},"sidebar wrap icon":{"wrap":"&#9658;","unwrap":"&#9660;"},"sidebar wrap cell":{"target":".wrap","font-size":"70%;","padding":"3px 5px;"},"sidebar item active":{"target":".itemactive","background-color":"#4578BF;","color":"#FFFFFF;","font":"1.1em Lato, Helvetica;"},"sidebar item hover":{"target":".sidebar tr:hover","background-color":"#4578BF;","cursor":"pointer;"},"sidebar object database":{"target":".sidebar-od","padding":"3px 5px 3px 0px;","margin":"0px;","color":"","width":"100%;","font":"1.1em Lato, Helvetica;"},"sidebar object view":{"target":".sidebar-ov","padding":"2px 5px 2px 10px;","margin":"0px;","color":"","font":"0.9em Lato, Helvetica;"},"main field":{"target":".main","width":"76%;","height":"90%;","left":"18%;","top":"5%;","border-radius":"5px;","background-color":"#EEE;","scrollbar-color":"#CCCCCC #FFFFFF;","box-shadow":"4px 4px 5px #111;"},"main field table":{"target":"table","margin":"0px;"},"main field table cursor cell":{"outline":"red auto 1px","shadow":"0 0 5px rgba(100,0,0,0.5)"},"main field table title cell":{"target":".titlecell","padding":"10px;","border":"1px solid #999;","color":"black;","background":"#CCC;","font":"","text-align":"center"},"main field table newobject cell":{"target":".newobjectcell","padding":"10px;","border":"1px solid #999;","color":"black;","background":"rgb(191,255,191);","font":"","text-align":"center"},"main field table data cell":{"target":".datacell","padding":"10px;","border":"1px solid #999;","color":"black;","background":"","font":"12px/14px arial;","text-align":"center"},"main field table undefined cell":{"target":".undefinedcell","padding":"10px;","border":"1px solid #999;","background":"rgb(255,235,235);"},"main field table mouse pointer":{"target":".main table tbody tr td:not([contenteditable=true])","cursor":"cell;"},"main field message":{"target":".main h1","color":"#BBBBBB;"},"scrollbar":{"target":"::-webkit-scrollbar","width":"8px;","height":"8px;"},"context menu":{"target":".contextmenu","width":"240px;","background-color":"#F3F3F3;","color":"#1166aa;","border":"solid 1px #dfdfdf;","box-shadow":"1px 1px 2px #cfcfcf;","font-family":"sans-serif;","font-size":"16px;","font-weight":"300;","line-height":"1.5;","padding":"12px 0;"},"context menu item":{"target":".contextmenuItems","margin-bottom":"4px;","padding-left":"10px;"},"context menu item cursor":{"target":".contextmenuItems:hover:not(.greyContextMenuItem)","cursor":"pointer;"},"context menu item active":{"target":".activeContextMenuItem","color":"#fff;","background-color":"#0066aa;"},"context menu item grey":{"target":".greyContextMenuItem","color":"#dddddd;"},"hint":{"target":".hint","background-color":"#CAE4B6;","color":"#7E5A1E;","border":"none;","padding":"5px;"},"box":{"target":".box","background-color":"rgb(233,233,233);","color":"#1166aa;","border-radius":"5px;","border":"solid 1px #dfdfdf;","box-shadow":"2px 2px 4px #cfcfcf;"},"dialog box title":{"target":".title","background-color":"rgb(209,209,209);","color":"#555;","border":"#000000;","border-radius":"5px 5px 0 0;","font":"bold .9em Lato, Helvetica;","padding":"5px;"},"dialog box pad":{"target":".pad","background-color":"rgb(223,223,223);","border-left":"none;","border-right":"none;","border-top":"none;","border-bottom":"none;","padding":"5px;","margin":"0;","font":".9em Lato, Helvetica;","color":"#57C;","border-radius":"5px 5px 0 0;"},"dialog box active pad":{"target":".activepad","background-color":"rgb(209,209,209);","border-left":"none;","border-right":"none;","border-top":"none;","border-bottom":"none;","padding":"5px;","margin":"0;","font":"bold .9em Lato, Helvetica;","color":"#57C;","border-radius":"5px 5px 0 0;"},"dialog box pad bar":{"target":".padbar","background-color":"transparent;","border":"none;","padding":"4px;","margin":"10px 0 15px 0;"},"dialog box divider":{"target":".divider","background-color":"transparent;","margin":"5px 10px 5px 10px;","height":"0px;","border-bottom":"1px solid #CCC;","border-top-color":"transparent;","border-left-color":"transparent;","border-right-color":"transparent;"},"dialog box button":{"target":".button","background-color":"#13BB72;","border":"none;","padding":"10px;","margin":"10px;","border-radius":"5px;","font":"bold 12px Lato, Helvetica;","color":"white;"},"dialog box button and pad hover":{"target":".button:hover, .pad:hover","cursor":"pointer;","background":"","color":"","border":""},"dialog box element headers":{"target":".element-headers","margin":"5px 5px 5px 5px;","font":".9em Lato, Helvetica;","color":"#555;","text-shadow":"none;"},"dialog box help icon":{"target":".help-icon","padding":"1px;","font":".9em Lato, Helvetica;","color":"#555;","background":"#FF0;","border-radius":"40%;"},"dialog box help icon hover":{"target":".help-icon:hover","padding":"1px;","font":"bold 1em Lato, Helvetica;","color":"black;","background":"#E8E800;","cursor":"pointer;","border-radius":"40%;"},"dialog box select":{"target":".select","background-color":"rgb(243,243,243);","color":"#57C;","font":".8em Lato, Helvetica;","margin":"0px 10px 5px 10px;","outline":"none;","border":"1px solid #777;","padding":"0px 0px 0px 0px;","overflow":"auto;","max-height":"10em;","scrollbar-width":"thin;","min-width":"10em;","width":"auto;","display":"inline-block;"},"dialog box select option":{"target":".select > div","padding":"2px 20px 2px 5px;","margin":"0px;"},"dialog box select option hover":{"target":".select:not([type*=\'o\']) > div:hover","background-color":"rgb(209,209,209);","color":""},"dialog box select option selected":{"target":".selected","background-color":"rgb(209,209,209);","color":"#fff;"},"dialog box select option expanded":{"target":".expanded","margin":"0px !important;","position":"absolute;"},"dialog box radio":{"target":"input[type=radio]","background":"transparent;","border":"1px solid #777;","font":".8em/1 sans-serif;","margin":"3px 5px 3px 10px;","border-radius":"20%;","width":"1.2em;","height":"1.2em;"},"dialog box radio checked":{"target":"input[type=radio]:checked::after","content":"","color":"white;"},"dialog box radio checked background":{"target":"input[type=radio]:checked","background":"#00a0df;","border":"1px solid #00a0df;"},"dialog box radio label":{"target":"input[type=radio] + label","color":"#57C;","font":".8em Lato, Helvetica;","margin":"0px 10px 0px 0px;"},"dialog box checkbox":{"target":"input[type=checkbox]","background":"#f3f3f3;","border":"1px solid #777;","font":".8em/1 sans-serif;","margin":"3px 5px 3px 10px;","border-radius":"50%;","width":"1.2em;","height":"1.2em;"},"dialog box checkbox checked":{"target":"input[type=checkbox]:checked::after","content":"","color":"white;"},"dialog box checkbox checked background":{"target":"input[type=checkbox]:checked","background":"#00a0df;","border":"1px solid #00a0df;"},"dialog box checkbox label":{"target":"input[type=checkbox] + label","color":"#57C;","font":".8em Lato, Helvetica;","margin":"0px 10px 0px 0px;"},"dialog box input text":{"target":"input[type=text]","margin":"0px 10px 5px 10px;","padding":"2px 5px;","background":"#f3f3f3;","border":"1px solid #777;","outline":"none;","color":"#57C;","border-radius":"5%;","font":".9em Lato, Helvetica;","width":"300px;"},"dialog box input password":{"target":"input[type=password]","margin":"0px 10px 5px 10px;","padding":"2px 5px;","background":"#f3f3f3;","border":"1px solid #777;","outline":"","color":"#57C;","border-radius":"5%;","font":".9em Lato, Helvetica;","width":"300px;"},"dialog box input textarea":{"target":"textarea","margin":"0px 10px 5px 10px;","padding":"2px 5px;","background":"#f3f3f3;","border":"1px solid #777;","outline":"","color":"#57C;","border-radius":"5%;","font":".9em Lato, Helvetica;","width":"300px;"},"effects":{"hint":"hotnews","contextmenu":"rise","box":"slideup","select":"rise","box filter":"grayscale(0.5)"},"hotnews hide":{"target":".hotnewshide","visibility":"hidden;","transform":"scale(0) rotate(0deg);","opacity":"0;","transition":"all .4s;","-webkit-transition":"all .4s;"},"hotnews show":{"target":".hotnewsshow","visibility":"visible;","transform":"scale(1) rotate(720deg);","opacity":"1;","transition":".4s;","-webkit-transition":".4s;","-webkit-transition-property":"transform, opacity","transition-property":"transform, opacity"},"fade hide":{"target":".fadehide","visibility":"hidden;","opacity":"0;","transition":"all .5s;","-webkit-transition":"all .5s;"},"fade show":{"target":".fadeshow","visibility":"visible;","opacity":"1;","transition":"opacity .5s;","-webkit-transition":"opacity .5s;"},"grow hide":{"target":".growhide","visibility":"hidden;","transform":"scale(0);","transition":"all .4s;","-webkit-transition":"all .4s;"},"grow show":{"target":".growshow","visibility":"visible;","transform":"scale(1);","transition":"transform .4s;","-webkit-transition":"transform .4s;"},"slideleft hide":{"target":".slidelefthide","visibility":"hidden;","transform":"translate(1000%);","transition":"all .4s cubic-bezier(1,-0.01,1,-0.09);","-webkit-transition":"all .4s cubic-bezier(1,-0.01,1,-0.09);"},"slideleft show":{"target":".slideleftshow","visibility":"visible;","transform":"translate(0%);","transition":"all .4s cubic-bezier(.06,1.24,0,.98);","-webkit-transition":"all .4s cubic-bezier(.06,1.24,0,.98);"},"slideright hide":{"target":".sliderighthide","visibility":"hidden;","transform":"translate(-1000%);","transition":"all .4s cubic-bezier(1,-0.01,1,-0.09);","-webkit-transition":"all .4s cubic-bezier(1,-0.01,1,-0.09);"},"slideright show":{"target":".sliderightshow","visibility":"visible;","transform":"translate(0%);","transition":"all .4s cubic-bezier(.06,1.24,0,.98);","-webkit-transition":"transform .4s cubic-bezier(.06,1.24,0,.98);"},"slideup hide":{"target":".slideuphide","visibility":"hidden;","transform":"translate(0%, 1000%);","transition":"all .4s cubic-bezier(1,-0.01,1,-0.09);","-webkit-transition":"all .4s cubic-bezier(1,-0.01,1,-0.09);"},"slideup show":{"target":".slideupshow","visibility":"visible;","transform":"translate(0%, 0%);","transition":"all .4s cubic-bezier(.06,1.24,0,.98);","-webkit-transition":"transform .4s cubic-bezier(.06,1.24,0,.98);"},"slidedown hide":{"target":".slidedownhide","visibility":"hidden;","transform":"translate(0%, 1000%);","transition":"all .4s cubic-bezier(1,-0.01,1,-0.09);","-webkit-transition":"all .4s cubic-bezier(1,-0.01,1,-0.09);"},"slidedown show":{"target":".slidedownshow","visibility":"visible;","transform":"translate(0%, 0%);","transition":"all .4s cubic-bezier(.06,1.24,0,.98);","-webkit-transition":"transform .4s cubic-bezier(.06,1.24,0,.98);"},"fall hide":{"target":".fallhide","visibility":"hidden;","transform-origin":"left top;","transform":"scale(2);","opacity":"0;","transition":"all .4s;","-webkit-transition":"all .4s;"},"fall show":{"target":".fallshow","visibility":"visible;","transform-origin":"left top;","transform":"scale(1);","opacity":"1;","transition":".4s;","-webkit-transition":".4s;","-webkit-transition-property":"transform, opacity","transition-property":"transform, opacity"},"rise hide":{"target":".risehide","visibility":"hidden;","transform-origin":"left top;","transform":"scale(0);","transition":"all .2s cubic-bezier(.38,1.02,.69,.97);","-webkit-transition":"all .2s cubic-bezier(.38,1.02,.69,.97);"},"rise show":{"target":".riseshow","visibility":"visible;","transform-origin":"left top;","transform":"scale(1);","transition":"transform .4s cubic-bezier(.06,1.24,0,.98);","-webkit-transition":"transform .4s cubic-bezier(.06,1.24,0,.98);"},"none hide":{"target":".nonehide","visibility":"hidden;"},"none show":{"target":".noneshow","visibility":"visible;"},"misc customization":{"objects per page":"50","next page bottom reach":"","previous page top reach":"","Force to use next user customization (empty or non-existent user - current is used)":"","mouseover hint timer in msec":"1000"}}', true);
 $dialog = ['pad' => []];
 
 foreach ($uiProfile as $profile => $value)
	 {
	  $i = 1;
	  $dialog['pad'][$profile] = [];
	  if (isset($value['target']))
	     {
	      $dialog['pad'][$profile]['element0'] = ['head' => "CSS selector: '".$value['target']."'. Customize css selector properties below:", 'target' => $value['target']];
	      $dialog['pad'][$profile]['element1'] = ['head' => ''];
	     }
	  foreach ($value as $key => $val) if ($key != 'target')
		  {
		   $i++;
		   $dialog['pad'][$profile]['element'.strval($i)] = ['type' => 'text', 'head' => $key.':', 'data' => $val, 'line' => ''];
		  }
	 }
 return json_encode($dialog);
}
