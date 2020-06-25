<?php

try {
     require_once 'eroc.php';
     createDefaultDatabases($db);
     /*$c = ["a" => 1, '""' => 1];
     loog($c);
     unset($c['""']);
     loog($c);*/
    }
catch (PDOException $e)
    {
     loog($e);
     echo json_encode(['cmd' => 'INFO', 'error' => $e->getMessage()]);
     exit;
    }

try {
     if (is_array($input = json_decode(file_get_contents("php://input"), true)))
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
		    $query = $db->prepare("create table `uniq_$id` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) AUTO_INCREMENT=".strval(STARTOBJECTID)." ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		    $query->execute();                                                                                                                                   
		    // Creating 'Object Database' (OD), consists of actual multiple object versions and its elements json data
		    $query = $db->prepare("create table `data_$id` (id MEDIUMINT NOT NULL, last BOOL DEFAULT 1, version MEDIUMINT NOT NULL, date DATE, time TIME, user CHAR(64), PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		    $query->execute();
		    // Insert new OD properties
		    $query = $db->prepare("UPDATE `$` SET odprops=:odprops WHERE id=$id");
		    $query->execute([':odprops' => json_encode(adjustODProperties($input['data'], $db, $id))]);
		    //-------------------------------------------------------------------------------------
		   }
		$output = ['cmd' => 'REFRESH', 'data' => getODVNamesForSidebar($db)];
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
			$output = ['cmd' => 'REFRESH', 'data' => getODVNamesForSidebar($db)];
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
		     $output = ['cmd' => 'REFRESH', 'data' => getODVNamesForSidebar($db)];
		     break;
		case 'GETMENU':
		     $output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		     break;
		case 'GETMAIN':
		     // Check input OD/OV to be valid and elements existence
		     if (gettype($error = checkODOV($db, $input)) === 'string' || gettype($error = getODProps($db)) === 'string' || gettype($error = getMainFieldData($db)) === 'string')
			{
			 $output = ['cmd' => 'INFO', 'error' => $error];
			 break;
			}
		     $output = ['cmd' => 'REFRESHMAIN', 'data' => $objectTable];
		     break;
		case 'DELETEOBJECT':
		     // Check input OD/OV to be valid, input object/element id vars existence/correctness and other data
		     if (gettype($error = checkODOV($db, $input)) === 'string' || gettype($alert = checkObjectElementID($input)) === 'string' || gettype($alert = DeleteObject($db, $oid)) === 'string' || gettype($error = getODProps($db)) === 'string' || gettype($error = getMainFieldData($db)) === 'string')
			{
			 if (isset($error)) $output = ['cmd' => 'INFO', 'error' => $error];
			  else $output = ['cmd' => 'INFO', 'alert' => $alert];
			 break;
			}
		     $output = ['cmd' => 'REFRESHMAIN', 'data' => $objectTable];
		     break;
		case 'KEYPRESS':
		case 'DBLCLICK':
		case 'CONFIRM':
		case 'INIT':
		     // Check input OD/OV to be valid, elements existence and input object/element id vars existence/correctness
		     if (gettype($error = checkODOV($db, $input)) === 'string' || gettype($error = getODProps($db)) === 'string' || gettype($alert = checkObjectElementID($input)) === 'string')
			{
			 if (isset($error)) $output = ['cmd' => 'INFO', 'error' => $error];
			  else $output = ['cmd' => 'INFO', 'alert' => $alert];
			 break;
			}
			
		     // For 'INIT' event handle all elements of new object, otherwise handle only current element 
		     if ($cmd === 'INIT') $elements = $allElementsArray;
		      else $elements = [$eid => $allElementsArray[$eid]];
		     $output = [];
		     
		     foreach ($elements as $element => $elementProfile)
		             if (($handlerName = $elementProfile['element4']['data']) != '')
		                if ($eventArray = parseJSONEventData($elementProfile['element5']['data'], $cmd))
		    		   {
			            $eventArray['event data'] = isset($data[$element]) ? $data[$element] : '';
			            $output[$element] = Handler($handlerName, json_encode($eventArray));
				    if ($output[$element]['cmd'] != 'SET' && $output[$element]['cmd'] != 'RESET')
				    if ($cmd === 'INIT' || ($output[$element]['cmd'] != 'EDIT' && $output[$element]['cmd'] != 'DIALOG' && $output[$element]['cmd'] != 'ALERT'))
				       unset($output[$element]);
				   }
		     if ($cmd === 'INIT')
		        {
			 InsertObject($db, $output);
			 $output = ['cmd' => 'REFRESH', 'data' => getODVNamesForSidebar($db)];
			}
		      else if (isset($output[$eid]))
		        {
			 UpdateObject($db, $output);
		         //Handle ONCHANGE if no INIT
			 //UpdateObject(); 
			 $output = ['cmd' => ''];
			}
		     break;
		default:
	          $output = ['cmd' => 'INFO', 'alert' => 'Controller report: unknown event "'.$input['cmd'].'" received from the browser!'];
		}
		
     if (!isset($output)) $output = ['cmd' => 'INFO', 'alert' => 'Controller report: undefined controller message!'];
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
		case 'INIT':
			if (preg_match("/Duplicate entry/", $e->getMessage()) === 1)
			      echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to add new object database for some unique elements:  Duplicate entry!']);
			 else echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to add new object: '.$e->getMessage()]);
		break;
	     default:
		 echo json_encode(['cmd' => 'INFO', 'alert' => 'Unknown error: '.$e->getMessage()]);
	    }
    }
