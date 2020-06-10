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
		     // Get Id, Element and OV sections from OD props
		     $query = $db->prepare("SELECT id, JSON_EXTRACT(odprops, '$.dialog.Element'), JSON_EXTRACT(odprops, '$.dialog.View')  FROM $ WHERE odname='$input[OD]'");
		     $query->execute();
		     
		     // Get 1st row where to data array: 1-id, 2-element profiles, 3-view profiles
		     $data = $query->fetchAll(PDO::FETCH_NUM)[0];
		     
		     // Decode element profiles array form OD props and remove 'New element' section
		     $elements = json_decode($data[1], true);
		     unset($elements['New element']);
		     
		     // Convert element assoc array to num array with element identificators as array elements instead of profile names
		     foreach ($elements as $profile => $value)
			     {
			      $eid = intval(substr($profile, strrpos($profile, ELEMENTPROFILENAMEADDSTRING) + strlen(ELEMENTPROFILENAMEADDSTRING)));  // Calculate current element id
			      $elements[$eid] = $elements[$profile];
			      unset($elements[$profile]);
			     }
			
		     // OD consists of no elements?
		     if (!is_array($elements) || !count($elements))
			{
			 $output = ['cmd' => 'INFO', 'error' => 'Object Database has no elements exist!'];
			 break;
			}
			 
		     // Move on. Get specified siew JSON element selection (what elements should be displayed and how)
		     $listJSON = json_decode($data[2], true);
		     $listJSON = trim($listJSON[$input['OV']]['element5']['data']);
		     
		     // List is empty? Set up default list for all elements: {"eid": "every", "oid": "title|0|newobj", "x": "0..", "y": "0|n"}
		     if ($listJSON === '')
		        {
			 $x = 0;
			 foreach ($elements as $eid => $value)
				 {
				  $listJSON .= '{"eid": "'.$eid.'", "oid": "'.strval(TITLEOBJECTID).'", "x": "'.strval($x).'", "y": "0", "style": "background-color: #BBB;"}'."\n";
				  $listJSON .= '{"eid": "'.$eid.'", "oid": "0", "x": "'.strval($x).'", "y": "n"}'."'\n";
				  $listJSON .= '{"eid": "'.$eid.'", "oid": "'.strval(NEWOBJECTID).'", "x": "'.strval($x).'", "y": "n", "style": "background-color: #AFF;"}'."\n";
				  $x++;
				 }
			}
		     
		     // Split listJSON data by lines to parse defined element identificators and to build eid-oid two dimension array.
		     // Undefined oid or oid - json line is ignored anyaway, but both undefined oid and oid 'style' and 'collapse' properties
		     // are parsed for undefined cells css style and collapse capability. Array structure:
		     //  ----------------------------------------------------------------
		     // |  \eid|       Element #0        |        Element #1..        	 | 
		     // |oid\  |         styles        	 | x,y,style,startevent..	 |
		     //  ----------------------------------------------------------------
		     // |  0   | for any oid/eid   	 | for default object element #1 |
		     //  ----- ----------------------------------------------------------
		     // |  1   | for whole new object    | for new object element #1     |
		     //  ----------------------------------------------------------------
		     // |  2   | for whole title object  | for title object element #1   |
		     //  ----------------------------------------------------------------
		     // |  3.. | for whole real object   | for real objects element #1   |
		     //  ----------------------------------------------------------------
		     $arrayEIdOId = [];
		     $elements[0] = $sqlElementList = '';
		     foreach (preg_split("/\n/", $listJSON) as $value) if ($j = json_decode($value, true, 2))
			     {
			      $j = cutKeys($j, ['eid', 'oid', 'x', 'y', 'style', 'collapse', 'startevent']);
			      if (!key_exists('eid', $j) || !key_exists('oid', $j)) 
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
			      
			      if (key_exists($eid, $elements) && ($eid != 0 || key_exists('style', $j))) // Non zero or zero with style eid index of elements exist?
			      if ($eid == 0 || (gettype($j['x']) === 'string' && gettype($j['y']) === 'string'))
				 {
				  if (!key_exists($eid, $arrayEIdOId)) $arrayEIdOId[$eid] = [];
				  if ($eid != 0)
				     {
				      $arrayEIdOId[$eid][$oid] = $j; // Fill eidoid array with parsed json string
				      $sqlElementList .= ',eid'.$j['eid']; // Collect elements list to use from sql query
				     }
				   else
				     {
				      $arrayEIdOId[$eid][$oid] = $j['style']; // Fill eidoid array with style property
				     }
				 }
			     }
			     
		     // No any element defined?	
		     if ($sqlElementList == '')
			{
			 $output = ['cmd' => 'INFO', 'error' => 'Specified view has no elements defined!'];
			 break;
			}
			
		     // Create result $objectTable array section. First step - init vars
		     $objectTable = $objectTableSrc = [];
		     $firstOId = getFirstOId($db, $data[0]); // Get first object id to use it as a static object that has one instance value for all objects in OD (for static elements only)

		     // Object list selection should depends on JSON 'oid' property, specified view page number object range and object selection expression match.
		     // While this features are not released, get all objects:
		     $query = $db->prepare("SELECT id$elementList FROM `data_$data[0]` WHERE last=1");
		     $query->execute();
		     
		     // Reindex $objectTable array to fit numeric indexes as object identificators to next format:
		     //  -----------------------------------------------------------------------------------
		     // |  \ eid|               |                                             		    |
		     // |   \   |       0       |           5.. (was 'eid5' column)             	    |
		     // |oid \  |               |                                             		    |
		     //  -----------------------------------------------------------------------------------
		     // |       |style rules    |                                             		    |
		     // |   0   |for undefined  |Apply object element props for all objects with element #5 |                                        		 |
		     // |       |cells          |                                             		    |
		     //  -----------------------------------------------------------------------------------
		     // |       |Apply styles   |"json" : JSON element data                   		    |
		     // |   1   |for whole      |"props": props for new object element #5 (eid=5,oid=0)     |	NEWOBJECTID
		     // |       |new object     |                                                    	    |
		     //  -----------------------------------------------------------------------------------
		     // |       |Apply styles   |"json" : JSON element data                   		    |
		     // |   2   |for whole      |"props": props for title object element #5 (eid=5,oid=0)   |	TITLEOBJECTID
		     // |       |title object   |                                                           |
		     //  -----------------------------------------------------------------------------------
		     // |       |Apply styles   |"json" : JSON element data                   		    |
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
			      $static = false;
			      if ($elements[$eid]['element3']['data'] === STATICELEMENTTYPE && isset($firstOId)) $static = true;
			      
			      // Iterate all objects identificators for current eid to fill $objectTable. First - for all object when oid=0:
			      if (key_exists(0, $arrayEIdOId[$eid])) foreach($objectTableSrc as $oid => $valeu)
				 {
				  if (!key_exists($oid, $objectTable)) $objectTable[$oid] = []; // Result $objectTable current object ($oid) doesn't exist? Create it
				  if (!$static) $objectTable[$oid][$eid]['json'] = $objectTableSrc[$oid][$eidstr]; // Set current element json data for non static element types
				  $objectTable[$oid][$eid] = ['props' => $arrayEIdOId[$eid][0]]; // Set current object element props data
				  //--------------------------Merge style rules start-----------------------
				  $styles = []; // CSS style rules in order of priority
				  if (isset($arrayEIdOId[0][0])) $styles[] = $arrayEIdOId[0][0]; // General style for all objects
				  if (isset($arrayEIdOId[0][$oid])) $styles[] = $arrayEIdOId[0][$oid]; // Object general style
				  if (isset($objectTable[$oid][$eid]['props']['style'])) $styles[] = $objectTable[$oid][$eid]['props']['style']; // Props style
				  if (isset($objectTableSrc[$oid][$eidstr]['style'])) $styles[] = $objectTableSrc[$oid][$eidstr]['style']; // Element style
				  $objectTable[$oid][$eid]['props']['style'] = mergeStyleRules($styles);
				  //---------------------------Merhe style rules end------------------------ 
				 }
				 
			      // Second - for other exact object oids:
		    	      foreach ($value as $oid => $props) if ($oid != 0)
				      {
				       $json = NULL;
				       if ($oid === NEWOBJECTID) $json = '{"value": ""}';
				       if ($oid === TITLEOBJECTID) $json = '{"value": "'.$elements[$eid]['element1']['data'].'"}';
				       if (key_exists($oid, $objectTableSrc))
				       if ($static) $json = '';
				        else $json = $objectTableSrc[$oid][$eidstr];
				       if (isset($json))
				          {
					   if (!key_exists($oid, $objectTable)) $objectTable[$oid] = [];
					   $objectTable[$oid][$eid] = ['json' => $json, 'props' => $props];
					   //--------------------------Merge style rules start-----------------------
					   $styles = []; // CSS style rules in order of priority
					   if (isset($arrayEIdOId[0][0])) $styles[] = $arrayEIdOId[0][0]; // General style for all objects
					   if (isset($arrayEIdOId[0][$oid])) $styles[] = $arrayEIdOId[0][$oid]; // Object general style
					   if (isset($objectTable[$oid][$eid]['props']['style'])) $styles[] = $objectTable[$oid][$eid]['props']['style']; // Props style
					   if (isset($objectTableSrc[$oid][$eidstr]['style'])) $styles[] = $objectTableSrc[$oid][$eidstr]['style']; // Element style
					   $objectTable[$oid][$eid]['props']['style'] = mergeStyleRules($styles);
					   //---------------------------Merhe style rules end------------------------ 
					  }
				      }
				      
			      // Iterate all objects identificators for current eid to fill $objectTable with static element
			      if ($static) foreach ($objectTable as $oid => $value)
			      if ($oid >= STARTOBJECTID && $oid != $firstOId && isset($objectTable[$oid][$eid]))
				 $objectTable[$oid][$eid]['json'] = $objectTableSrc[$firstOId][$eidstr];
			     }
			     
		     // Check the result data to be sent to client part
		     if (count($objectTable) > 0)
		        {
			 if (isset($undefinedProps)) $objectTable[0][0] = $undefinedProps;
			 $output = ['cmd' => 'REFRESHMAIN', 'data' => $objectTable];
			}
		      else
		        {
			 $output = ['cmd' => 'INFO', 'error' => 'Specified view has no objects defined!'];
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
