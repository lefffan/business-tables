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
     if (is_array($input = json_decode(file_get_contents("php://input"), true)))
     switch ($input['cmd'])
	    {
	    case 'New Object Database':
	          initNewODDialogElements();
		  $output = ['cmd' => 'DIALOG', 'data' => ['title'  => 'New Object Database', 'dialog'  => ['Database' => ['Properties' => $newProperties, 'Permissions' => $newPermissions], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => ['CREATE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['_callback' => 'NEWOD', 'style' => 'width: 750px; height: 600px;', 'esc' => '', 'display_single_profile' => '']]];
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
				     $odprops['flags']['callback'] = $input['data'];
				     $output = ['cmd' => 'DIALOG', 'data' => $odprops];
				    }
				 else $output = ['cmd' => 'INFO', 'alert' => "Unable to get '$input[data]' Object Database properties!"];
				}
		break;
		case 'GETMENU':
		     $output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		     break;
		case 'OBTAINMAIN':
		case 'GETMAIN':
		     Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS);
		     if (isset($error)) $output = ['cmd' => 'INFO', 'error' => $error];
		      else if (isset($alert)) $output = ['cmd' => 'INFO', 'alert' => $alert];
		      else if ($error = getMainFieldData($db)) $output = ['cmd' => 'INFO', 'error' => $error];
		      else $output = ['cmd' => 'REFRESHMAIN', 'data' => $objectTable];
		     $output['OD'] = $OD;
		     $output['OV'] = $OV;
		     break;
		case 'DELETEOBJECT':
		     Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | SET_CMD_DATA | CHECK_OID);
		     if (isset($error)) $output = ['cmd' => 'INFO', 'error' => $error];
		      else if (isset($alert)) $output = ['cmd' => 'INFO', 'alert' => $alert];
		      else if ($alert = DeleteObject($db)) $output = ['cmd' => 'INFO', 'alert' => $alert];
		      else if ($error = getMainFieldData($db)) $output = ['cmd' => 'INFO', 'error' => $error];
		      else $output = ['cmd' => 'REFRESHMAIN', 'data' => $objectTable];
		     $output['OD'] = $OD;
		     $output['OV'] = $OV;
		     break;
		case 'INIT':
		     Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | SET_CMD_DATA);
		     if (isset($error)) { $output = ['cmd' => 'INFO', 'OD' => $OD, 'OV' => $OV, 'error' => $error]; break; }
		     if (isset($alert)) { $output = ['cmd' => 'INFO', 'OD' => $OD, 'OV' => $OV, 'alert' => $alert]; break; }
		     
		     // Handle all elements of a new object
		     $output = [];
		     foreach ($allElementsArray as $id => $profile)
		             if (($handlerName = $profile['element4']['data']) != '')
		                if ($eventArray = parseJSONEventData($db, $profile['element5']['data'], $cmd, $id))
		    		   {
			            $eventArray['data'] = isset($data[$id]) ? $data[$id] : '';
			            $output[$id] = Handler($handlerName, json_encode($eventArray));
				    if ($output[$id]['cmd'] != 'SET' && $output[$id]['cmd'] != 'RESET') unset($output[$id]);
				   }
		     InsertObject($db);
		     if ($error = getMainFieldData($db)) $output = ['cmd' => 'INFO', 'error' => $error];
		      else $output = ['cmd' => 'REFRESHMAIN', 'data' => $objectTable];
		     $output['OD'] = $OD;
		     $output['OV'] = $OV;
		     break;
		case 'KEYPRESS':
		case 'DBLCLICK':
		case 'CONFIRM':
		     if (isset($input['data']['flags']['_callback'])) { $input['data']['flags']['_callback'] === 'EDITOD' ? $output = EditOD($db) : $output = NewOD($db); break; }
		     Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | SET_CMD_DATA | CHECK_OID | CHECK_EID);
		     if (isset($error)) { $output = ['cmd' => 'INFO', 'OD' => $OD, 'OV' => $OV, 'error' => $error]; break; }
		     if (isset($alert)) { $output = ['cmd' => 'INFO', 'OD' => $OD, 'OV' => $OV, 'alert' => $alert]; break; }
		     
		     // Search input cmd event and call the appropriate handler
		     if (($handlerName = $allElementsArray[$eid]['element4']['data']) != '' && $eventArray = parseJSONEventData($db, $allElementsArray[$eid]['element5']['data'], $cmd, $eid))
		        {
			 if (isset($data)) $eventArray['data'] = $data;
			 $output = [$eid => Handler($handlerName, json_encode($eventArray))];
			 if ($output[$eid]['cmd'] === 'SET' || $output[$eid]['cmd'] === 'RESET')
			    {
			     if ($alert = CreateNewObjectVersion($db)) $output = ['cmd' => 'INFO', 'alert' => $alert];
			      else
			        {
			         foreach ($output as $id => $value) if (!isset($arrayEIdOId[$id])) unset($output[$id]);
			         isset($output[$eid]['alert']) ? $output = ['cmd' => 'SET', 'oId' => $oid, 'data' => $output, 'alert' => $output[$eid]['alert']] : $output = ['cmd' => 'SET', 'oId' => $oid, 'data' => $output];
				}
			    }
			  else if ($output[$eid]['cmd'] === 'EDIT') isset($output[$eid]['data']) ? $output = ['cmd' => 'EDIT', 'data' => $output[$eid]['data'], 'oId' => $oid, 'eId' => $eid] : $output = ['cmd' => 'EDIT', 'oId' => $oid, 'eId' => $eid];
			  else if ($output[$eid]['cmd'] === 'ALERT') isset($output[$eid]['data']) ? $output = ['cmd' => 'INFO', 'alert' => $output[$eid]['data']] : $output = ['cmd' => 'INFO', 'alert' => ''];
			  else if ($output[$eid]['cmd'] === 'DIALOG' && isset($output[$eid]['data']) && is_array($output[$eid]['data']))
				  {
				   if (isset($output[$eid]['data']['flags']['_callback'])) unset($output[$eid]['data']['flags']['_callback']);
				   $output = ['cmd' => 'DIALOG', 'data' => $output[$eid]['data']];
				  }
			  else $output = ['cmd' => ''];
			 $output['OD'] = $OD;
			 $output['OV'] = $OV;
			 break;
			}
		     $output = ['cmd' => ''];
		     break;
		default:
	          $output = ['cmd' => 'INFO', 'log' => 'Controller report: unknown event "'.$input['cmd'].'" received from the browser!'];
		}
		
     if (!isset($output)) $output = ['cmd' => 'INFO', 'log' => 'Controller report: undefined controller message!'];
      echo json_encode($output);
    }
     
catch (PDOException $e)
    {
     loog($e);
     switch ($input['cmd'])
    	    {
	     case 'Edit Database Structure':
	    	  echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to get Object Database properties: '.$e->getMessage()]);
	          break;
		 case 'NEWOD':
			if (isset($odid))
			    {
			     $query = $db->prepare("DELETE FROM `$` WHERE id=$odid");
			     $query->execute();
			     $query = $db->prepare("DROP TABLE IF EXISTS `data_$odid`; DROP TABLE IF EXISTS `uniq_$odid`");
			     $query->execute();
			    }
			if (preg_match("/already exist/", $e->getMessage()) === 1 || preg_match("/Duplicate entry/", $e->getMessage()) === 1) echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to add new object database: OD name or data tables already exist!']);
			 else echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to add new object database: '.$e->getMessage()]);
		  break;
		case 'EDITOD':
			 echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to write OD properties: '.$e->getMessage()]);
		  break;
		case 'GETMENU':
			 echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to read sidebar OD list: '.$e->getMessage()]);
		break;
		case 'OBTAINMAIN':
		case 'GETMAIN':
		     echo json_encode(['cmd' => 'INFO', 'OD' => $OD, 'OV' => $OV, 'error' => 'Failed to get OD data: '.$e->getMessage()]);
		     break;
		case 'DELETEOBJECT':
		     echo json_encode(['cmd' => 'INFO', 'OD' => $OD, 'OV' => $OV, 'alert' => 'Failed to delete object: '.$e->getMessage()]);
		     break;
		case 'INIT':
		     if (preg_match("/Duplicate entry/", $e->getMessage()) === 1) echo json_encode(['cmd' => 'INFO', 'OD' => $OD, 'OV' => $OV, 'alert' => 'Failed to add new object: unique elements duplicate entry!']);
		      else echo json_encode(['cmd' => 'INFO', 'OD' => $OD, 'OV' => $OV, 'alert' => 'Failed to add new object: '.$e->getMessage()]);
		     break;
		case 'KEYPRESS':
		case 'DBLCLICK':
		     if (preg_match("/Duplicate entry/", $e->getMessage()) === 1) echo json_encode(['cmd' => 'INFO', 'OD' => $OD, 'OV' => $OV, 'alert' => 'Failed to write object data: unique elements duplicate entry!']);
		      else echo json_encode(['cmd' => 'INFO', 'OD' => $OD, 'OV' => $OV, 'alert' => 'Failed to write object data: '.$e->getMessage()]);
		     break;
		case 'CONFIRM':
		     if (preg_match("/Duplicate entry/", $e->getMessage()) === 1) $alert = 'Failed to write object data: unique elements duplicate entry!';
		      else $alert = 'Failed to write object data: '.$e->getMessage();
		     if (gettype($undo = getElementProperty($db, $eid)) === 'string') $undo = ['value' => ''];
		     echo json_encode(['cmd' => 'SET', 'OD' => $OD, 'OV' => $OV, 'oId' => $oid, 'data' => [$eid => $undo], 'alert' => $alert]);
		     break;
	     default:
		 echo json_encode(['cmd' => 'INFO', 'alert' => 'Controller unknown error: '.$e->getMessage()]);
	    }
    }
