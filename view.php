<?php

require_once 'core.php';

function CheckODString($odname)
{
 return substr(str_replace("'", '', str_replace('"', '', trim(str_replace("\\", '', $odname)))), 0, ODSTRINGMAXCHAR);
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
      foreach ($query->fetch(PDO::FETCH_NUM) as $key => $value)
	      $value ? $content[$key + 1]['value'] = $value : $content[$key + 1]['value'] = '';
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

function LinksIntersection($linknames, $linkprop, &$allelements)
{
 if (gettype($linknames) === 'string') $linknames = [$linknames];
 if (gettype($linknames) !== 'array') return [];
 $links = []; // Array of [<remote element id>, <uplink object selection>]

 // Takes view props link names (via '|' or '/') and element link prop to calc intersected names
 if ($linkprop) foreach (preg_split("/\n/", $linkprop) as $value)
    if (trim($value) && gettype($last = preg_split("/\|/", $value, 3)) === 'array') // Is linkprop line splited to 2 or 3 elements?
    if (count($last) === 3)						// All fields are defined
    if (($last[0] = trim($last[0])) && in_array($last[0], $linknames))	// Check linprop link names to match view props link names
       $links[] = [trim($last[1]), trim($last[2])];

 return $links;
}

// Each $tree array element is class (content css class name), content (elemnt list and its values) and link (array of uplink nodes): ['link' => [nodes array], 'content' => [eid, etitle, evalue], 'class' => '']
function DefineNodeLinks($db, &$client, $oid, &$linknames, &$tree, &$objects)
{
 // Build a query for all elements to fetch their link and value props
 $query = '';
 foreach ($client['allelements'] as $eid => $element)
	 $query .= "$eid, JSON_UNQUOTE(JSON_EXTRACT(eid$eid, '$.link')), JSON_UNQUOTE(JSON_EXTRACT(eid$eid, '$.value')), ";
 if (!$query) return;
 $query = substr($query, 0, -2);

 // Execute the query
 try {
      $query = $db->prepare("SELECT $query FROM `data_$client[ODid]` WHERE id=$oid AND lastversion=1 AND version!=0");
      $query->execute();
      $srcobject = $query->fetchAll(PDO::FETCH_NUM);
     }
 catch (PDOException $e)
     {
      unset($srcobject);
     }
 if (!isset($srcobject[0])) return; // No fetched object? Return
 $srcobject = $srcobject[0];
 $count = count($srcobject);

 // Test all link names one by one for the first match in case of non OR ('|') names delimiter (isset($linknames['']))
 $objlinknames = $linknames;
 if (isset($objlinknames['']))
    {
     foreach ($objlinknames as $key => $name) if ($key !== '')
     for ($i = 0; $i < $count; $i += 3)
	 if (LinksIntersection($name, $srcobject[$i + 1], $client['allelements']) !== [] && ($match = [$name])) break 2;
     if (!isset($match)) return;
     $objlinknames = $match;
    }

 for ($i = 0; $i < $count; $i += 3)
 if (($links = LinksIntersection($objlinknames, $srcobject[$i + 1], $client['allelements'])) !== [] && ($srccontent = [['id' => $srcobject[$i], 'title' => $client['allelements'][$srcobject[$i]]['element1']['data'], 'value' => $srcobject[$i + 2]]]))
 foreach ($links as $value) // Get through all matched link names in a element link property
	 {
	  $content = $srccontent;
	  $content[] = ['id' => $value[0], 'title' => GetELementTitle($value[0], $client['allelements'])];
	  // Search uplink object id
	  try {
	       $query = $db->prepare("SELECT id FROM `data_$client[ODid]` WHERE lastversion=1 AND version!=0 AND $value[1] LIMIT 1");
	       $query->execute();
	      }
	  // Syntax error? Make virtual error node with error message as a content
	  catch (PDOException $e)
	      {
	       $content[2]['value'] = "Object selection syntax error:<br>'$value[1]'<br>See 'Element layout' help section for right syntax..";
	       $tree['link'][] = ['content' => $content, 'class' => 'treeerror'];
	       continue; // Go to next uplink object search via $select
	      }

	  // Uplink object not found? Make virtual error node with error message as a content and continue
	  $uplinkoid = $query->fetch(PDO::FETCH_NUM);
	  if (!isset($uplinkoid[0]))
	     {
	      $content[2]['value'] = "Object selection links to nonexistent object:<br>'$value[1]'";
	      $tree['link'][] = ['content' => $content, 'class' => 'treeerror'];
	      continue;
	     }

	  // Check loop via uplink object id existence in $objects array that consists of object ids already in the tree. Continue if exists, otherwise remember uplink object id in $objects array
	  if (isset($objects[$uplinkoid = $uplinkoid[0]]))
	     {
	      $content[2]['value'] = "Loop detected on link:<br>from remote node [object id'$oid']<br>to me [object id'$uplinkoid']!";
	      $tree['link'][] = ['content' => $content, 'class' => 'treeerror'];
	      continue;
	     }
	  $objects[$uplinkoid] = true; // Remember uplink object id for loop detection

	  // Get tree element content, uplink and local linked elements
	  GetTreeElementContent($db, $client, $content, $uplinkoid);

	  // Build tree element and define uplink node tree via  recursive function call
	  $tree['link'][] = ['link' => [], 'content' => $content, 'class' => 'treeelement'];
	  DefineNodeLinks($db, $client, $uplinkoid, $linknames, $tree['link'][array_key_last($tree['link'])], $objects);
	 }
}

function NewOD($db, &$client, &$output)
{
 // Get dialog OD name, cut it and check
 $odname = CheckODString($client['data']['dialog']['Database']['Properties']['element1']['data']);
 if ($odname === '' && ($output = ['cmd' => '', 'alert' => 'Object Database name cannot be empty!'])) return;
 $client['data']['dialog']['Database']['Properties']['element1']['data'] = $odname;

 // Inserting new OD name with empty database configuration
 $query = $db->prepare("INSERT INTO `$` (odname) VALUES (:odname)");
 $query->execute([':odname' => $odname]);

 // Getting created properties id
 $query = $db->prepare("SELECT LAST_INSERT_ID()");
 $query->execute();
 $client['newODid'] = $id = $query->fetchAll(PDO::FETCH_NUM)[0][0];

 // Creating 'uniq' OD instance for json "value" element properties (for 'uniq' object elements only)
 $query = $db->prepare("create table `uniq_$id` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) AUTO_INCREMENT=".strval(STARTOBJECTID)." ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $query->execute();

 // Creating 'Object Database' (OD), consists of actual multiple object versions and its elements json data
 $query = $db->prepare("create table `data_$id` (id MEDIUMINT NOT NULL, mask TEXT, lastversion BOOL DEFAULT 1, version MEDIUMINT NOT NULL, owner CHAR(64), datetime DATETIME DEFAULT NOW(), PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $query->execute();
 $query = $db->prepare("ALTER TABLE `data_$id` ADD INDEX (`lastversion`)");
 $query->execute();

 // Insert new OD properties
 $query = $db->prepare("UPDATE `$` SET odprops=:odprops WHERE id=$id");
 $query->execute([':odprops' => json_encode(adjustODProperties($db, $client['data'], $id))]);

 return true;
}

function EditOD($db, &$client, &$output)
{
 // Get dialog old and new OD name
 $newodname = CheckODString($client['data']['dialog']['Database']['Properties']['element1']['data']);
 $client['data']['dialog']['Database']['Properties']['element1']['data'] = $newodname;
 $id = intval($client['data']['flags']['callback']);

 // Getting old OD name in `$`
 $query = $db->prepare("SELECT odname,odprops FROM `$` WHERE id=:id");
 $query->execute([':id' => $id]);
 $oldodname = $query->fetchAll(PDO::FETCH_NUM);

 if (!isset($oldodname[0][0], $oldodname[0][1]) && ($output = ['cmd' => '', 'alert' => "Object Database has already been removed!"])) return;
 $odprops = $oldodname[0][1];
 $oldodname = $oldodname[0][0];

 // In case of empty OD name string try to remove current OD from the system
 if ($newodname === '')
    {
     if (($client['data']['dialog']['Database']['Properties']['element2']['data'] != '' || count($client['data']['dialog']['View']) != 1) && ($output = ['cmd' => '', 'alert' => "To remove Object Database (OD) - remove all views first, then empty 'name' and 'description' OD fields!"])) return;
     $query = $db->prepare("DELETE FROM `$` WHERE id=:id");
     $query->execute([':id' => $id]);
     $query = $db->prepare("DROP TABLE IF EXISTS `uniq_$id`");
     $query->execute();
     $query = $db->prepare("DROP TABLE IF EXISTS `data_$id`");
     $query->execute();
     return true;
    }

 // Decode current OD props
 $odprops = json_decode($odprops, true);
 if (!isset($odprops['dialog']['Database']['Properties']) && ($output = ['cmd' => '', 'alert' => "Failed to get Object Database '$oldodname' properties!"])) return;
 $section = $odprops['dialog']['Database']['Properties'];

 // Check current OD permissions to fetch new OD data from dialog box - $client['data']['dialog']['Database']['Permissions'])..
 $groups = getUserGroups($db, $client['uid']); // Get current user group list
 $groups[] = $client['auth']; // and add username at the end of array
 $alertstring = '';

 // Check 'Database' pad change permissions
 if ($client['data']['dialog']['Database'] != $odprops['dialog']['Database'])
    {
     $count = count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $section['element7']['data'])), "strcmp"));
     $pos = strpos($section['element6']['data'], '+');
     if (($count && $pos) || (!$count && !$pos))
	{
	 $alertstring .= "'Database', ";
	 $client['data']['dialog']['Database'] = $odprops['dialog']['Database'];
	 $newodname = $oldodname;
	}
    }

 // Check 'Element' pad change permissions
 if ($client['data']['dialog']['Element'] != $odprops['dialog']['Element'])
    {
     $count = count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $section['element9']['data'])), "strcmp"));
     $pos = strpos($section['element8']['data'], '+');
     if (($count && $pos) || (!$count && !$pos))
	{
	 $alertstring .= "'Element', ";
	 $client['data']['dialog']['Element'] = $odprops['dialog']['Element'];
	}
    }

 // Check 'View' pad change permissions
 if ($client['data']['dialog']['View'] != $odprops['dialog']['View'])
    {
     $count = count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $section['element11']['data'])), "strcmp"));
     $pos = strpos($section['element10']['data'], '+');
     if (($count && $pos) || (!$count && !$pos))
	{
	 $alertstring .= "'View', ";
	 $client['data']['dialog']['View'] = $odprops['dialog']['View'];
	}
    }

 // Check 'Rule' pad change permissions
 if ($client['data']['dialog']['Rule'] != $odprops['dialog']['Rule'])
    {
     $count = count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $section['element13']['data'])), "strcmp"));
     $pos = strpos($section['element12']['data'], '+');
     if (($count && $pos) || (!$count && !$pos))
	{
	 $alertstring .= "'Rule', ";
	 $client['data']['dialog']['Rule'] = $odprops['dialog']['Rule'];
	}
    }

 // If alert string is not empty copy it to output result
 if ($alertstring) $output['alert'] = "You're not allowed to change ".substr($alertstring, 0, -2)." properties!";
 // Writing new properties
 $query = $db->prepare("UPDATE `$` SET odname=:odname,odprops=:odprops WHERE id=:id");
 $query->execute([':odname' => $newodname, ':odprops' => json_encode(adjustODProperties($db, $client['data'], $id)), ':id' => $id]);

 return true;
}

try {
     $output = ['cmd' => ''];
     $input = json_decode(file_get_contents("php://input"), true);
     $query = $db->prepare("SELECT now()-time,client FROM `$$$` WHERE id='$input'");
     $query->execute();
     $client = $query->fetchAll(PDO::FETCH_NUM)[0];
     $query = $db->prepare("DELETE FROM `$$$` WHERE id='$input'");
     $query->execute();
    }
catch (PDOException $e)
    {
     lg($e, 'View.php PDO exception');
     echo json_encode(['cmd' => '', 'error' => 'PDO driver exception error!']);
     exit;
    }

if (intval($client[0]) > CALLTIMEOUT)
   {
    echo json_encode(['cmd' => '', 'error' => "Server call request timeout with $client[0]sec, please try again!"]);
    exit;
   }
$client = json_decode($client[1], true);

try {
     switch ($client['cmd'])
	    {
	     case 'SIDEBAR': // Client sidebar items wrap/unwrap event
		  Check($db, CHECK_OD_OV, $client, $output);
		  break;
	     case 'CALL':
		  // Check input data correctness
		  if (!isset($client['allelements'], $client['elementselection'], $client['objectselection'], $client['viewtype']) && !Check($db, CHECK_OD_OV | GET_ELEMENTS | GET_VIEWS | CHECK_ACCESS, $client, $output)) break;

		  // Get OV data for a 'Tree' view template
		  if ($client['viewtype'] === 'Tree')
		     {
		      // Check link names
		      $linknames = [];
		      (($posAND = strpos($client['linknames'], '/')) !== false && (($posOR = strpos($client['linknames'], '|')) === false || $posOR > $posAND)) ? $delimiter = '/' : $delimiter = '|';
		      foreach (preg_split("/\\".$delimiter."/", $client['linknames']) as $name) if (trim($name)) $linknames[] = trim($name);
		      if ($linknames === [] && ($output['error'] = "Specified view '".$client['OV']."' has no link names defined!")) break;
		      if ($delimiter === '/') $linknames[''] = '';

		      // Get object selection query string, array result is treated as dialog content to define view params
		      $client['objectselection'] = GetObjectSelection($client['objectselection'], $client['params'], $client['auth']);
		      if (gettype($client['objectselection']) === 'array' && ($output = ['cmd' => 'DIALOG', 'data' => $client['objectselection'], 'ODid' => $client['ODid'], 'ODid' => $client['OVid']] + $output)) break;

		      // Get object selection head object id
		      $query = $db->prepare("SELECT id FROM `data_$client[ODid]` $client[objectselection]");
		      $query->execute();
		      $headid = $query->fetch(PDO::FETCH_ASSOC); // Get object selection first object to build the tree from
		      if (!isset($headid['id']) && ($output['error'] = "Specified view '".$client['OV']."' has no objects matched current selection!")) break;
		      $headid = $headid['id'];		// Put head object id in headid var
		      $objects = [$headid => true];	// Remember head object id in a global array for loop detection
		      $content = [[], []];		// Init empty content for head object

		      // Build the tree
		      GetTreeElementContent($db, $client, $content, $headid);
		      $tree = ['link' => [], 'content' => $content, 'class' => 'treeelement']; // Init tree with head object
		      DefineNodeLinks($db, $client, $headid, $linknames, $tree, $objects); // and build uplink part
		      $output = ['cmd' => 'Tree', 'tree' => $tree] + $output;
		      (isset($client['elementselection']['direction']) && $client['elementselection']['direction'] === 'up') ? $output['direction'] = 'up' : $output['direction'] = 'down';
		      break;
		     }

		  // Get OV data for a 'Table' view template
		  if ($client['viewtype'] === 'Table')
		     {
		      // Get object selection query string, array result is treated as dialog content to define view params
		      $client['objectselection'] = GetObjectSelection($client['objectselection'], $client['params'], $client['auth']);
		      if (gettype($client['objectselection']) === 'array' && ($output = ['cmd' => 'DIALOG', 'data' => $client['objectselection'], 'ODid' => $client['ODid'], 'ODid' => $client['OVid']] + $output)) break;

		      // Get element selection query string, in case of empty result return no element message as an error
		      $elementQueryString = '';
		      $props = setElementSelectionIds($client);
		      foreach ($props as $key => $value) if (intval($key) > 0) $elementQueryString .= ",JSON_UNQUOTE(JSON_EXTRACT(eid$key, '$.value')) as eid$key"."value,JSON_UNQUOTE(JSON_EXTRACT(eid$key, '$.style')) as eid$key"."style,JSON_UNQUOTE(JSON_EXTRACT(eid$key, '$.hint')) as eid$key"."hint,JSON_UNQUOTE(JSON_EXTRACT(eid$key, '$.description')) as eid$key"."description";
		      if ($elementQueryString === '' && ($output['error'] = "Database '$client[OD]' Object View '$client[OV]' has no elements defined!")) break;

		      // Return OV refresh command to the client with object selection sql query result as a main field data
		      $query = $db->prepare("SELECT id,version,owner,datetime,lastversion$elementQueryString FROM `data_$client[ODid]` $client[objectselection]");
		      $query->execute();
		      $output = ['cmd' => 'Table', 'data' => $query->fetchAll(PDO::FETCH_ASSOC), 'props' => $props, 'params' => $client['params']] + $output;
		      break;
		     }
		  $output = ['cmd' => '', 'error' => "Template '$client[viewtype]' is not supported!"];
		  break;
	     case 'New Database':
	          if (!Check($db, CHECK_ACCESS, $client, $output)) break;
		  if ($client['data'] === '')
		     {
		      initNewODDialogElements();
		      $output = ['cmd' => 'DIALOG',
		      'data' => ['title'  => 'New Database',
				 'dialog' => ['Database' => ['Properties' => $newProperties],
					      'Element' => ['New element' => $newElement],
					      'View' => ['New view' => $newView],
					      'Rule' => ['New rule' => $newRule]],
				 'buttons' => CREATECANCEL,
				 'flags'  => ['style' => 'width: 760px; height: 720px;', 'esc' => '', 'padprofilehead' => ['Element' => "Select element", 'View' => "Select view", 'Rule' => "Select rule"]]]];
		      $output['data']['buttons']['CREATE']['call'] = 'New Database';
		      break;
		     }
		  if (!NewOD($db, $client, $output) || !Check($db, CHECK_OD_OV, $client, $output)) break;
	          break;
	     case 'Database Configuration':
	          if (gettype($client['data']) === 'string') // Input data is a string (OD name), so get OD props
		     {
 		      $query = $db->prepare("SELECT odname,odprops FROM `$` WHERE id=:id");
		      $query->execute([':id' => $client['data']]);
		      $odprops = $query->fetch(PDO::FETCH_NUM);
		      $odname = $odprops[0];
		      if (!($odprops = json_decode($odprops[1], true)) && ($output['alert'] = "Object Database doesn't exist!")) break; // Incorrect/absent OD props

		      $odprops['flags']['callback'] = $client['data'];		// Put OD name in a callback property
		      $odprops['title'] .= " '$odname' (id $client[data])";	// Set dialog title
		      ksort($odprops['dialog'], SORT_STRING);			// Sort dialog pads
		      $output = ['cmd' => 'DIALOG', 'data' => $odprops];	// Build output dialog
		      break;
		     }
		  if (!EditOD($db, $client, $output) || !Check($db, CHECK_OD_OV, $client, $output)) break;
		  break;
	    }
    }
catch (PDOException $e)
    {
     lg($e);
     $msg = $e->getMessage();
     switch ($client['cmd'])
	    {
	     case 'CALL':
	          $output = ['cmd' => '', 'error' => "Failed to get Object View: $msg"];
		  break;
	     case 'New Database':
		  if (isset($client['newODid']))
		     {
		      $query = $db->prepare("DELETE FROM `$` WHERE id=$client[newODid]");
		      $query->execute();
		      $query = $db->prepare("DROP TABLE IF EXISTS `data_$client[newODid]`");
		      $query->execute();
		      $query = $db->prepare("DROP TABLE IF EXISTS `uniq_$client[newODid]`");
		      $query->execute();
		     }
		  $output = ['cmd' => '', 'alert' => 'Failed to add new Object Database: '];
		  $output['alert'] .= preg_match("/Duplicate entry/", $msg) === 1 ? 'OD name already exists!' : $msg;
		  break;
	     case 'Database Configuration':
	          $output = ['cmd' => '', 'alert' => "Failed to write Object Database properties: $msg"];
		  break;
	    }
    }

// Echo output result
echo json_encode($output);
