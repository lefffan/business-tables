<?php

try {
     require_once 'eroc.php';
     createDefaultDatabases($db);
    }
catch (PDOException $e)
    {
     loog($e);
     echo json_encode(['cmd' => 'INFO', 'error' => $e->getMessage()]);
     exit;
    }

try {
     $input = json_decode(file_get_contents("php://input"), true);
     
     switch ($input['cmd'])
	    {
	    case 'New Object Database':
	          initNewODDialogElements();
		  $output = ['cmd' => 'DIALOG', 'data' => ['title'  => 'New Object Database', 'dialog'  => ['Database' => ['Properties' => $newProperties, 'Permissions' => $newPermissions], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'flags'  => ['esc' => '', 'ok' => 'CREATE', 'display_single_profile' => '']]];
		  break;
	    case 'Edit Database Structure':
			if (isset($input['data']))
				{
				 initNewODDialogElements();
 				 $query = $db->prepare("SELECT odprops FROM `$` WHERE odname=:odname");
				 $query->execute([':odname' => $input['data']]);
				 $odprops = json_decode($query->fetch(PDO::FETCH_NUM)[0], true);
				 if ($odprops)
				    {
				     $odprops['flags']['callbackData'] = $input['data'];
				     $output = ['cmd' => 'DIALOG', 'data' => $odprops];
				    }
				 else $output = ['cmd' => 'INFO', 'alert' => "Unable to get '$input[data]' Object Database properties!"];
				}
		break;
	    case 'NEWOD':
		if (is_array($input['data']))
		   {
		    // Get dialog OD name, cut it and check
		    $odname = $input['data']['dialog']['Database']['Properties']['element1']['data'] = substr(trim($input['data']['dialog']['Database']['Properties']['element1']['data']), 0, ODSTRINGMAXCHAR);
		    if ($odname === '')
		       {
		        $output = ['cmd' => 'INFO', 'alert' => 'Please input Object Database name!'];
		        break;
		       }
		    initNewODDialogElements();
		    // Inserting new OD name
		    $query = $db->prepare("INSERT INTO `$` (odname) VALUES (:odname)");
		    $query->execute([':odname' => $odname]);
		    // Getting created properties id
		    $query = $db->prepare("SELECT LAST_INSERT_ID()");
		    $query->execute();
		    $id = $query->fetch(PDO::FETCH_NUM)[0];
		    // Creating instance of Object Database (OD) for json "value" property (for 'uniq' object elements only)
		    $query = $db->prepare("create table `uniq_$id` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) AUTO_INCREMENT=".STARTOBJECTID." ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		    $query->execute();                                                                                                                                   
		    // Creating 'Object Database' (OD), consists of actual multiple object versions and its elements json data
		    $query = $db->prepare("create table `data_$id` (id MEDIUMINT NOT NULL, last BOOL DEFAULT 1, version MEDIUMINT NOT NULL, date DATE, time TIME, user CHAR(64), PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		    $query->execute();
		    // Insert new OD properties
		    $query = $db->prepare("UPDATE `$` SET odprops=:odprops WHERE id=$id");
		    $query->execute([':odprops' => json_encode(adjustODProperties($input['data'], $db, $id))]);
		    //-------------------------------------------------------------------------------------
		   }
		$output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		break;
	    case 'EDITOD':
		if (is_array($input['data']))
		   {
		    // Get dialog old and new OD name
		    $newodname = $input['data']['dialog']['Database']['Properties']['element1']['data'] = substr($input['data']['dialog']['Database']['Properties']['element1']['data'], 0, ODSTRINGMAXCHAR);
		    $oldodname = $input['data']['flags']['callbackData'] = substr($input['data']['flags']['callbackData'], 0, ODSTRINGMAXCHAR);
		    // Getting old OD name id in `$`
		    $query = $db->prepare("SELECT id FROM `$` WHERE odname=:odname");
		    $query->execute([':odname' => $oldodname]);
		    $id = $query->fetch(PDO::FETCH_NUM)[0];
		    // In case of empty OD name string try to remove current OD from the system
		    if ($newodname === '')
		    if ($input['data']['dialog']['Database']['Properties']['element2']['data'] === '' && count($input['data']['dialog']['Element']) === 1)
		       {
		        $query = $db->prepare("DELETE FROM `$` WHERE id=$id");
			$query->execute();
			$output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		        $query = $db->prepare("DROP TABLE IF EXISTS `uniq_$id`; DROP TABLE IF EXISTS `data_$id`");
			$query->execute();
			break;
		       }
		     else
		       {
		        $output = ['cmd' => 'INFO', 'alert' => "To remove Object Database (OD) - empty 'name' and 'description' OD fields and remove all elements (see 'Element' tab)"];
			break;
		       }
			// Writing new properties
			initNewODDialogElements();
			$query = $db->prepare("UPDATE `$` SET odname=:odname,odprops=:odprops WHERE id=$id");
			$query->execute([':odname' => $newodname, ':odprops' => json_encode(adjustODProperties($input['data'], $db, $id))]);
		   }
		   $output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		break;
		case 'GETMENU':
		   $output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		break;
		case 'GETMAIN':
		     // Get OV and Element sections from OD props (first row of fetchAll result)
			 $query = $db->prepare("SELECT id, JSON_EXTRACT(odprops, '$.dialog.Element'), JSON_EXTRACT(odprops, '$.dialog.View')  FROM $ WHERE odname='$input[OD]'");
			 $query->execute();
			 $data = $query->fetchAll(PDO::FETCH_NUM)[0];
			 // Get element array form OD props and remove 'New element' section
			 $elements = json_decode($data[1], true);
			 unset($elements['New element']);
			 // Convert element assoc array to num array with element identificators as array elements
			 foreach ($elements as $profile => $value)
				 {
				  $eid = intval(substr($profile, strrpos($profile, ELEMENTPROFILENAMEADDSTRING) + strlen(ELEMENTPROFILENAMEADDSTRING)));  // Calculate current element id
				  $elements[$eid] = $elements[$profile];
				  unset($elements[$profile]);
				 }
			 if (!is_array($elements) || !count($elements))
			    {
			     $output = ['cmd' => 'INFO', 'error' => 'Object Database has no elements exist!'];
			     break;
			    }
			 // *JSON string format, one by line:
			 // {"eid":	 	"0..",
			 //  "oid":	 	"0..",
			 //  "x":	 	"0..",
			 //  "y":	 	"0..",
			 //  "style":     	"css style rules",
			 //  "collapse":	"true|false",
			 //  "startevent":	"DBLCLICK|KEYPRESS<char1><char2>..<charN>"}
			 //
			 // Description:
			 // 
			 // eid,oid	- selects object with id <oid> and its element with id <eid>.
			 //		  Zero or absent 'eid' and 'oid' together selects table cells with no object elements attached to.
			 //		  Zero or absent 'oid' selects all object with specified 'eid'
			 // eid	     	- element id, starts from one. However zero or absent 'eid' can be used together with 'style' property -
			 //		  for unused table cells (cells with no object elements) to style them by this properrty value (see below).
			 // oid      	- object id, starts from one. However zero id defines virtual object as a header for each element,
			 //	          '-1' id is new object id - table cells with that id is used to create new object.
			 //	          JSON string properties with absent 'oid' applied to all other ('oid 'undefined) objects.
			 // x,y	     	- defined with <eid>/<oid> object element table coordinates for the specified view.
			 //		  Coordinates may can be in the form of expression, that consists of variable <n>
			 //		  (current object number in the list, starts from one - 1st object on the page)
			 //		  and/or <q> - page object quantity (see view scheme properties). Absent property - no 
			 // style	- style css attribute rules for defined by x,y table cell coordinates
			 // startevent	- emulate mouse/keyboard event at OV refresh/draw
			 // collapse	- Object elements with that property set in a whole row/column empty data on table view will be collapsed.
			 //	          Default is false.
			 // 
			 // * - Empty JSON description selects example 1 table type for all elements in OD, error JSON displays nothing.
			 // * - In case of duplicate 'eid'/'oid' combination - last instance is used.
			 //
			 // Example 1:
			 // We have Object Database (OD) consists of some persons. Each person has some 'element', for a example name, age and phone.
			 // In context of a system - persons are objects; name, age and phone - are object elements. To display name and age information
			 // in a classic table we need a simple Object View (OV) with next JSON lines:
			 // { "eid": "1", "oid": "0", "x": "0", "y": "0" }*
			 // { "eid": "1", "x": "0", "y": "n" }
			 // { "eid": "2", "oid": "0", "x": "1", "y": "0" }**
			 // { "eid": "2", "x": "1", "y": "n" }
			 //
			 // *  - header for element id 1 with 0,0 coordinates at the left-upper corner of the table
			 // ** - header for element id 1 with 1,0 coordinates ath the table 1st row and 2nd column, all headers should be defined explicitly 
			 //
			 // So the table will look like a simple table with two headers (name and age) and person list:
			 // ---------------
			 //| Name  |  Age  |	- object id 0 (header)
			 // ---------------
			 //| Mary  |  23   |	- object id 1
			 // ---------------
			 //| John  |  32   |	- object id 2  
			 // ---------------
			 //|  ...  |  ...  |  
			 // ---------------
			 //	
			 // element element
			 //  id 1    id 2
			 //
			 //
			 // Example 2:
			 // ---------------
			 //| Mary  |  23   |	
			 // ---------------
			 //| John  |  32   |
			 // ---------------
			 //|  ...  |  ...  |  
			 // ----------------
			 //
			 // Same table with no header. JSON description:
			 // { "eid": "1", "x": "0", "y": "n-1" }
			 // { "eid": "2", "x": "1", "y": "n-1" }
			 //
			 // Example 3:
			 // 	    --------- --------
			 //   ...  | newname | newage |		y = 0 (line 1)
			 //         --------- --------
			 //   ...      ...	...		y = 1 (line 2)
			 //        
			 //   ...      ...	...    		y = 2 (line 3)
			 //        
			 //   ...      ...	...   		y = 3 (line 4)
			 //        
			 //   ...      ...	...   		y = 4 (line 5)
			 //            
			 //   ...      ...	...   		y = 5 (line 6)
			 // -------
			 //| John  |   ...	...		y = 6 (line 7)
			 // -------
			 //|  32   |   ...	...		y = 7 (line 8)
			 // -------
			 //| Mary  |   ...	...		y = 8 (line 9)
			 // -------
			 //|  23   |   ...	...		y = 9 (line 10)
			 // -------
			 //
			 //  x=0       x=1	x=2
			 //
			 // Same database, but all object data starts from the bottom of the page (q=10), with one element by line and
			 // new name/age input at the top of the table. Also set no border with red background for unused cells. JSON description:
			 // { "eid": "1", "x": "0", "y": "q-2*n" }
			 // { "eid": "2", "x": "0", "y": "q-2*n+1" }
			 // { "eid": "1", "oid": "-1", "x": "1", "y": "0" }*
			 // { "eid": "2", "oid": "-1", "x": "2", "y": "0" }**
			 // { "style": "border: none; background: red;" }
			 //
			 // * - Name element input for a new object
			 // * - Age element input for a new object
			 
			 // Get specified View format (what elements should be displayed and how)
			 $format = json_decode($data[2], true);
			 $format = trim($format[$input['OV']]['element5']['data']);
			 if ($format === '')
			    {
			     //use default format by foreach ($elements as $id => $value)
			    }
			 // Split format data by lines to parse defined element identificators and to build eid-oid two dimension array
			 $arrayOIdEId = [];
			 $elements[0] = $elementList = '';
			 foreach (preg_split("/\n/", $format) as $value) if ($j = json_decode($value, true))
				 {
				  if (!isset($j['eid'])) $j['eid'] = '0';
				  if (!isset($j['oid'])) $j['oid'] = '0';
				  $eid = abs(intval($j['eid']));
				  $oid = abs(intval($j['oid']));
				  if (isset($elements[$eid]) && ($eid != 0 || isset($j['style'])))
				     {
				      if (!isset($arrayOIdEId[$eid])) $arrayOIdEId[$eid] = [];
				      $arrayOIdEId[$eid][$oid] = $j;
				      if ($eid != 0) $elementList .= ',eid'.$j['eid'];
				     }
				 }
			 // Is any element defined?	
			 if ($elementList != '')
			    {
			     // Object list (should depends on JSOn 'oid' property plus specified view page range in the future)
			     // should consists of zero (empty) row, 1st row (new object), 2nd row (title object)
			     // and other user inserted objects starting with <STARTOBJECTID> id.
			     // Plus this list should match specified view expression, don't forget it.
			     // While it is not released, get all object and reindex result array to fit numeric indexes as object identificators
			     $query = $db->prepare("SELECT id$elementList FROM `data_$data[0]` WHERE last=1");
			     $query->execute();
			     $data = [];
			     foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $value) $data[intval($value['id'])] = $value;
			     // Insert element titles row with index <TITLEOBJECTID> to the result array
			     $data[TITLEOBJECTID] = [];
			     foreach ($arrayOIdEId as $key => $value) if ($key > 0) $data[TITLEOBJECTID]['eid'.strval($key)] = $elements[$eid]['element1']['data'];
			     // Reset result array depending on object elements global status
			     // ...
			     // Check the result data to be sent to client part
			     //loog($data);
			     if (count($data) > 0) $output = ['cmd' => 'REFRESHMAIN', 'data' => $data, 'format' => $arrayOIdEId];
			      else $output = ['cmd' => 'INFO', 'error' => 'Specified view has no objects defined!'];
			    }
			  else 
			    {
			     $output = ['cmd' => 'INFO', 'error' => 'Specified view has no elements defined!'];
			    }
			 break;
		default:
	          $output = ['cmd' => 'INFO', 'alert' => 'Unknown event "'.$input['cmd'].'" received from the browser!'];
		}
		
     if (!isset($output)) $output = ['cmd' => 'INFO', 'alert' => 'Undefined controller message!'];
     echo json_encode($output);
    }
     
catch (PDOException $e)
    {
     loog($e);
     switch ($input['cmd'])
    	    {
	     case 'CONFIRM':
	          echo json_encode(['cmd' => 'INFO', 'alert' => 'Some text: '.$e->getMessage()]);
		  break;
		 case 'NEWOD':
			if (isset($id))
			    {
			     $query = $db->prepare("DELETE FROM `$` WHERE id=$id");
			     $query->execute();
			     $query = $db->prepare("DROP TABLE IF EXISTS `data_$id`; DROP TABLE IF EXISTS `uniq_$id`");
			     $query->execute();
			    }
			if (preg_match("/already exist/", $e->getMessage()) === 1 || preg_match("/Duplicate entry/", $e->getMessage()) === 1)
			      echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to add new object database: OD name or data tables already exist!']);
			 else echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to add new object database: '.$e->getMessage()]);
		  break;
		case 'EDITOD':
			 echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to write OD properties: '.$e->getMessage()]);
		  break;
		case 'GETMENU':
			 echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to get sidebar OD/OV list: '.$e->getMessage()]);
		  break;
	     default:
		 echo json_encode(['cmd' => 'INFO', 'alert' => 'Unknown error: '.$e->getMessage()]);
	    }
    }
