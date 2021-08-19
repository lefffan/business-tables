<?php

require_once 'core.php';

function CheckODString($odname)
{
 return substr(str_replace("'", '', str_replace('"', '', trim(str_replace("\\", '', $odname)))), 0, ODSTRINGMAXCHAR);
}

function GetTreeElementContent($db, &$client, &$content, $oid)
{
 // Content is array of object elements:
 // first - downlink node linked object element,
 // second - local node linked object element,
 // next - other elements of local node object (defined in element layout) in a row.
 // Each array element consists of three props: identificator, title and value
 if (isset($content[1]['id'], $client['allelements'][$content[1]['id']]))
    $content[1]['title'] = $client['allelements'][$content[1]['id']]['element1']['data'];

 // Go through all elements in the element layout and put them to the content
 foreach ($client['elementselection'] as $key => $value)
	 if (array_search($key, SERVICEELEMENTS) !== false) $content[] = ['id' => $key, 'title' => $key, 'value' => ''];
	  elseif (isset($client['allelements'][$key])) $content[] = ['id' => $key, 'title' => $client['allelements'][$key]['element1']['data'], 'value' => ''];

 // Make query string to select element values from DB
 $query = '';
 foreach ($content as $key => $value) if ($key)
	 {
	  if (!isset($value['id'])) $query .= 'NULL,';
	   else if (array_search($value['id'], SERVICEELEMENTS) !== false) $query .= $value['id'].',';
	   else if (!isset($client['allelements'][$value['id']])) $query .= 'NULL,';
	   else $query .= 'JSON_EXTRACT(eid'.$value['id'].", '$.value'),";
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

function DefineNodeLinks($db, &$client, $oid, $type, &$tree, &$objects)
{
 // Each $tree array element is class (content css class name), content and link (array of uplink nodes):
 // ['link' => [nodes array], 'content' => [eid, etitle, evalue], 'class' => '']

 foreach ($client['allelements'] as $eid => $evalue)
	 {
	  //--------------Link props fetch--------------
	  try {
	       foreach (preg_split("/\|/", $type) as $linktype) if ($linktype)
		       {
			$query = $db->prepare("SELECT JSON_UNQUOTE(JSON_EXTRACT(eid$eid, '$.link_remote_object_selection')), JSON_UNQUOTE(JSON_EXTRACT(eid$eid, '$.link_remote_element_id')), JSON_UNQUOTE(JSON_EXTRACT(eid$eid, '$.value')), JSON_EXTRACT(eid$eid, '$.value') FROM `data_$client[ODid]` WHERE id=$oid AND lastversion=1 AND version!=0 AND JSON_EXTRACT(eid$eid, '$.link_type')='$linktype'");
			$query->execute();
			$object = $query->fetchAll(PDO::FETCH_NUM);
			if (isset($object[0][0], $object[0][1])) break;	// Remote object links (selection, element id) of current element eid$eid and current $linktype found
		       }
	       if (!isset($object[0][0], $object[0][1])) continue;	// No remote object links found in a cycle above
	       $selection = $object[0][0];				// Uplink node object selection
	       $content = [['id' => $eid, 'title' => $evalue['element1']['data'], 'value' => ''], ['id' => $object[0][1]]];
	       if (isset($object[0][2])) $content[0]['value'] = $object[0][2];
	      }
	  catch (PDOException $e)
	      {
	       $content[2]['value'] = "Error getting object id '$oid' element id '$eid' link properties: ".$e->getMessage();
	       $tree['link'][] = ['content' => $content, 'class' => 'treeerror'];
	       continue;
	      }

	  //--------------Uplink node object id calculation (via selection from 'linkoid' prop)--------------
	  $contentinit = $content; // Remember base node content as initcontent
	  foreach (preg_split("/\|/", $selection) as $select) if ($select)
		  {
		   $content = $contentinit;	// Init current content from init content
		   try {			// Search uplink object id view $select
			$query = $db->prepare("SELECT id FROM `data_$client[ODid]` WHERE lastversion=1 AND version!=0 AND $select");
			$query->execute();
		       }
		   catch (PDOException $e)	// Syntax error $select query? Make virtual error node with error message as a content
		       {
			$content[2]['value'] = "Object id '$oid' element id '$eid' linkoid property selection syntax error: ".$e->getMessage();
			$tree['link'][] = ['content' => $content, 'class' => 'treeerror'];
			continue; // Go to next uplink object search via $select
		       }
		   $uplinkoid = $query->fetch(PDO::FETCH_NUM);	// Fetch uplink object id search result
		   if (!isset($uplinkoid[0]))			// No uplink object found? Make virtual error node with error message as a content
		      {
		       $content[2]['value'] = "Object id '$oid' element id '$eid' links to unexisting object: '$selection'";
		       $tree['link'][] = ['content' => $content, 'class' => 'treeerror'];
		       continue;		// Go to next uplink object search via $select
		      }
		   $uplinkoid = $uplinkoid[0];	// Remember uplink object id

		   // Get tree element content, uplink and local linked elements
		   GetTreeElementContent($db, $client, $content, $uplinkoid);

		   // Check loop via uplink object id existence in $objects array that consists of object ids already in the tree. Continue if exists, otherwise remember uplink object id in $objects array
		   if (isset($objects[$uplinkoid]) && ($tree['link'][] = ['content' => [$content[0], $content[1], ['value' => "Loop detected on link from remote node [object id'$oid'] to me [object id'$uplinkoid']!"]], 'class' => 'treeerror'])) continue;
		   $objects[$uplinkoid] = true;

		   // Build tree element and define uplink node tree via  recursive function call
		   $tree['link'][] = ['link' => [], 'content' => $content, 'class' => 'treeelement'];
		   end($tree['link']);
		   DefineNodeLinks($db, $client, $uplinkoid, $type, $tree['link'][key($tree['link'])], $objects);
		  }
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
		      // Check link type
		      if ($client['linktype'] === '' && ($output['error'] = "Specified view '".$client['OV']."' has no link type defined!")) break;
		      // Get object selection query string, array result is treated as dialog content to define view params
		      $client['objectselection'] = GetObjectSelection($db, $client['objectselection'], $client['params'], $client['auth']);
		      if (gettype($client['objectselection']) === 'array' && ($output = ['cmd' => 'DIALOG', 'data' => $client['objectselection'], 'ODid' => $client['ODid'], 'ODid' => $client['OVid']] + $output)) break;

		      $query = $db->prepare("SELECT id FROM `data_$client[ODid]` $client[objectselection]");
		      $query->execute();
		      $headid = $query->fetch(PDO::FETCH_ASSOC); // Get object selection first object to build the tree from
		      if (!isset($headid['id']) && ($output['error'] = "Specified view '".$client['OV']."' has no objects matched current selection!")) break;
		      $headid = $headid['id'];		// Put head object id in headid var
		      $objects = [$headid => true];	// Remember head object id in a global array for loop detection
		      $content = [[], []];		// Init empty content for head object

		      GetTreeElementContent($db, $client, $content, $headid);
		      $tree = ['link' => [], 'content' => $content, 'class' => 'treeelement']; // Init tree with head object
		      DefineNodeLinks($db, $client, $headid, $client['linktype'], $tree, $objects); // and build uplink part
		      $output = ['cmd' => 'Tree', 'tree' => $tree] + $output;
		      (isset($client['elementselection']['direction']) && $client['elementselection']['direction'] === 'up') ? $output['direction'] = 'up' : $output['direction'] = 'down';
		      break;
		     }

		  // Get OV data for a 'Table' view template
		  if ($client['viewtype'] === 'Table')
		     {
		      // Get object selection query string, array result is treated as dialog content to define view params
		      $client['objectselection'] = GetObjectSelection($db, $client['objectselection'], $client['params'], $client['auth']);
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
