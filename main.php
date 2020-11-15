<?php

try {
     require_once 'eroc.php';

     //lg(session_get_cookie_params());
     //lg($_SERVER);
     //lg(PHP_VERSION_ID);
     //lg(gettype([]));
     $input = json_decode(file_get_contents("php://input"), true);
     if (!isset($input['cmd'])) $input = ['cmd' => '']; // Set empty cmd in case of undefined or exit?
     
     if (!isset($_SESSION["u"]) || !isset($_SESSION['h']) || !password_verify(getUserPass($db, $_SESSION['u']), $_SESSION['h'])) // User is unauthenticated or password has been changed?
     if ($input['cmd'] === 'LOGIN' && ($user = $input['data']['dialog']['pad']['profile']['element1']['data']) != '' && ($pass = $input['data']['dialog']['pad']['profile']['element2']['data']) != '' && password_verify($pass, $hash = getUserPass($db, $uid = getUserId($db, $user)))) // Login dialog evet occured and user/pass are not empty and correct
	{
	 $_SESSION['u'] = $uid;
	 $_SESSION['h'] = password_hash($hash, PASSWORD_DEFAULT);
	 $customization = getUserCustomization($db, $uid);
	 if (!isset($input['data']['flags']['callback']))
	    {
	     $output = ['cmd' => 'INFO', 'alert' => "User '$user' has logged in!", 'user' => $user];
	     if (isset($customization)) $output['customization'] = $customization;
	     echo json_encode($output);
	     exit;
	    }
	 $input = $input['data']['flags']['callback'];
	}
      else
        {    
	 $output = ['cmd' => 'DIALOG', 'data' => getLoginDialogData()];
	 if ($input['cmd'] != 'LOGIN' || isset($input['data']['flags']['callback'])) $output['data']['flags']['callback'] = $input;
	 if ($input['cmd'] === 'LOGIN') $output['data']['dialog']['pad']['profile']['element1']['head'] = "\nWrong passowrd or username, please try again!\n\nUsername";
	 echo json_encode($output);
	 exit;
	}
	
     switch ($input['cmd'])
	    {
	     case 'GETMAINSTART':
	     case 'GETMAIN':
	          if (isset($input['data']['dialog']['pad']['profile']))
		     {
		      $objectSelection = [];
		      foreach ($input['data']['dialog']['pad']['profile'] as $key => $value) $objectSelection[$key] = $value['data'];
		     }
		  Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | CHECK_ACCESS);
		  $output = [ 'OD' => $OD, 'OV' => $OV, 'sidebar' => getODVNamesForSidebar($db) ];
		  if ($input['cmd'] === 'GETMAINSTART') 
		     {
	    	      $customization = getUserCustomization($db, $_SESSION['u']);
		      $output['log'] = "User '".getUserName($db, $_SESSION['u'])."' has started application!";
		     }
		  if (isset($error) || isset($alert))
		     {
		      $output['cmd'] = 'INFO';
		      isset($error) ? $output['error'] = $error : $output['alert'] = $alert;
		      break;
		     }
		  if (gettype($objectSelection) === 'array')
		     {
		      $output['cmd'] = 'DIALOG';
		      $output['data'] = $objectSelection;
		      break;
		     }
		  if ($error = getMainFieldData($db))
		     {
		      $output['cmd'] = 'INFO';
		      $output['error'] = $error;
		      break;
		     }
		  $output['cmd'] = 'REFRESH';
		  $output['data'] = $objectTable;
		  $output['props'] = $props;
		  if (isset($input['data']['dialog']['pad']['profile'])) $output['objectSelection'] = $objectSelection;
		  break;
	     case 'GETMENU':
		  $output = ['cmd' => '', 'sidebar' => getODVNamesForSidebar($db)];
		  break;
	    case 'LOGOUT':
		  $output = ['cmd' => 'DIALOG', 'data' => getLoginDialogData(), 'log' => "User '".getUserName($db, $_SESSION['u'])."' has logged out!"];
		  unset($_SESSION['u']);
		  break;
	    case 'New Object Database':
		  Check($db, CHECK_ACCESS);
		  if (isset($error) || isset($alert))
		     {
		      $output['cmd'] = 'INFO';
		      isset($error) ? $output['error'] = $error : $output['alert'] = $alert;
		      break;
		     }
		  if (!isset($input['data']))
		     {
	              initNewODDialogElements();
		      $output = ['cmd' => 'DIALOG', 'data' => ['title'  => 'New Object Database', 'dialog'  => ['Database' => ['Properties' => $newProperties, 'Permissions' => $newPermissions], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => ['CREATE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['cmd' => 'New Object Database', 'style' => 'width: 760px; height: 720px;', 'esc' => '', 'display_single_profile' => '']]];
		      break;
		     }
		  $output = NewOD($db);
		  break;
	    case 'Edit Database Structure':
		  if (isset($input['data']))
		  if (gettype($input['data']) === 'string')
		     {
 		      $query = $db->prepare("SELECT odprops FROM `$` WHERE odname=:odname");
		      $query->execute([':odname' => $input['data']]);
		      $odprops = json_decode($query->fetch(PDO::FETCH_NUM)[0], true);
				 if ($odprops)
				    {
				     $odprops['flags']['callback'] = $input['data'];
				     $odprops['title'] .= " - '".$input['data']."'";
				     $output = ['cmd' => 'DIALOG', 'data' => $odprops];
				    }
				 else $output = ['cmd' => 'INFO', 'alert' => "Unable to get '$input[data]' Object Database properties!"];
		     }
		   else if (gettype($input['data']) === 'array') $output = EditOD($db);
		  break;
		case 'DELETEOBJECT':
		     Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | SET_CMD_DATA | CHECK_OID | CHECK_ACCESS);
		     if (isset($error)) $output = ['cmd' => 'INFO', 'error' => $error];
		      else if (isset($alert)) $output = ['cmd' => 'INFO', 'alert' => $alert];
		      else if ($odid == 1 && $oid == STARTOBJECTID) $output = ['cmd' => 'INFO', 'alert' => 'System account cannot be deleted!'];
		      else if ($alert = DeleteObject($db)) $output = ['cmd' => 'INFO', 'alert' => $alert];
		      else
		        {
			 if (isset($input['objectSelection'])) $objectSelection = $input['objectSelection'];
			 if ($error = getMainFieldData($db)) $output = ['cmd' => 'INFO', 'error' => $error];
		          else $output = ['cmd' => 'REFRESH', 'data' => $objectTable, 'props' => $props];
			}
		     $output['OD'] = $OD;
		     $output['OV'] = $OV;
		     break;
		case 'INIT':
		     Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | SET_CMD_DATA | CHECK_ACCESS);
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
		     if (isset($input['objectSelection'])) $objectSelection = $input['objectSelection'];
		     if ($error = getMainFieldData($db)) $output = ['cmd' => 'INFO', 'error' => $error];
		      else $output = ['cmd' => 'REFRESH', 'data' => $objectTable, 'props' => $props];
		     $output['OD'] = $OD;
		     $output['OV'] = $OV;
		     break;
		case 'CUSTOMIZATION':
		case 'KEYPRESS':
		case 'DBLCLICK':
		case 'CONFIRM':
		     Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | SET_CMD_DATA | CHECK_OID | CHECK_EID | CHECK_ACCESS);
		     if (isset($error)) { $output = ['cmd' => 'INFO', 'OD' => $OD, 'OV' => $OV, 'error' => $error]; break; }
		     if (isset($alert)) { $output = ['cmd' => 'INFO', 'OD' => $OD, 'OV' => $OV, 'alert' => $alert]; break; }
			    
		     if ($input['cmd'] === 'CUSTOMIZATION' && $_SESSION['u'] == $input['oId'] && isset($input['data']['dialog']['pad']['misc customization']['element5']['data']))
		     if ($input['data']['dialog']['pad']['misc customization']['element5']['data'] != '' && ($uid = getUserId($db, $input['data']['dialog']['pad']['misc customization']['element5']['data'])) && $uid != $_SESSION['u'])
			$customization = getUserCustomization($db, $uid, true);
		      else
			$customization = $input['data']['dialog'];
		     if ($input['cmd'] === 'CUSTOMIZATION') $input['cmd'] = 'CONFIRM';
		     
		     // Search input cmd event and call the appropriate handler
		     if (($handlerName = $allElementsArray[$eid]['element4']['data']) != '' && $eventArray = parseJSONEventData($db, $allElementsArray[$eid]['element5']['data'], $cmd, $eid))
		        {
			 if (isset($data)) $eventArray['data'] = $data;
			 $output = [$eid => Handler($handlerName, json_encode($eventArray))];
			 switch ($output[$eid]['cmd']) // Process handler answer by the controller
				{
				 case 'SET':
				 case 'RESET':
				      if (!($alert = CreateNewObjectVersion($db)))
				         {
			        	  foreach ($output as $id => $value) if (!isset($props[$id])) unset($output[$id]);
			        	  isset($output[$eid]['alert']) ? $output = ['cmd' => 'SET', 'oId' => $oid, 'data' => $output, 'alert' => $output[$eid]['alert']] : $output = ['cmd' => 'SET', 'oId' => $oid, 'data' => $output];
					  $query = $db->prepare("SELECT id,version,owner,datetime,lastversion FROM `data_$odid` WHERE id=$oid AND lastversion=1 AND version!=0");
					  $query->execute();
					  foreach ($query->fetchAll(PDO::FETCH_ASSOC)[0] as $id => $value) $output['data'][$id] = $value;
					  break;
					 }
				      $output = ['cmd' => 'INFO', 'alert' => $alert];
				      break;
				 case 'EDIT':
				      isset($output[$eid]['data']) ? $output = ['cmd' => 'EDIT', 'data' => $output[$eid]['data'], 'oId' => $oid, 'eId' => $eid] : $output = ['cmd' => 'EDIT', 'oId' => $oid, 'eId' => $eid];
				      break;
				 case 'ALERT':
				      isset($output[$eid]['data']) ? $output = ['cmd' => 'INFO', 'alert' => $output[$eid]['data']] : $output = ['cmd' => 'INFO', 'alert' => ''];
				      break;
				 case 'DIALOG':
				      if (isset($output[$eid]['data']) && is_array($output[$eid]['data']))
				         {
					  if (isset($output[$eid]['data']['flags']['cmd']) && $handlerName != 'customization.php') unset($output[$eid]['data']['flags']['cmd']);
					  $output = ['cmd' => 'DIALOG', 'data' => $output[$eid]['data']];
					 }
				      break;
				 case 'CALLVIEW':
				      if (isset($output[$eid]['data']) && is_array($output[$eid]['data']))
				         {
					  if (isset($objectSelectionParamsDialog)) $output = ['cmd' => 'DIALOG', 'data' => $output[$eid]['data']];
					   else {}
					 }
				      break;
				 default:
				      $output = ['cmd' => ''];
				}
			 $output['OD'] = $OD;
			 $output['OV'] = $OV;
			 break;
			}
		     $output = ['cmd' => ''];
		     break;
		default:
	          $output = ['cmd' => 'INFO', 'log' => 'Controller report: unknown event "'.$input['cmd'].'" received from the client!'];
		}
		
     if (!isset($output)) $output = ['cmd' => 'INFO', 'alert' => 'Controller report: unknown error!'];
     if (isset($_SESSION['u'])) $output['user'] = getUserName($db, $_SESSION['u']);
     if (isset($customization)) $output['customization'] = $customization;
     echo json_encode($output);
    }
     
catch (PDOException $e)
    {
     lg($e);
     if (!isset($input['cmd'])) $input = ['cmd' => ''];
     
     switch ($input['cmd'])
    	    {
	     case 'New Object Database':
		  if (isset($odid))
		     {
		      $query = $db->prepare("DELETE FROM `$` WHERE id=$odid");
		      $query->execute();
		      $query = $db->prepare("DROP TABLE IF EXISTS `data_$odid`; DROP TABLE IF EXISTS `uniq_$odid`");
		      $query->execute();
		     }
		  if (preg_match("/already exist/", $e->getMessage()) === 1 || preg_match("/Duplicate entry/", $e->getMessage()) === 1)
		     echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to add new object database: OD name or data tables already exist!']);
		   else
		     echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to add new object database: '.$e->getMessage()]);
		  break;
	     case 'Edit Database Structure':
	    	  if (gettype($input['data']) === 'string') echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to get OD properties: '.$e->getMessage()]);
		   else echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to write OD properties: '.$e->getMessage()]);
	          break;
	     case 'GETMENU':
		  echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to read sidebar OD list: '.$e->getMessage()]);
		break;
		case 'GETMAINSTART':
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
		     if (isset($eid))
		        {
		         $undo = getElementArray($db, $eid);
		         if (!isset($undo)) $undo = ['value' => ''];
		         echo json_encode(['cmd' => 'SET', 'OD' => $OD, 'OV' => $OV, 'oId' => $oid, 'data' => [$eid => $undo], 'alert' => $alert]);
			 break;
			}
		     echo json_encode(['cmd' => 'INFO', 'alert' => $alert]);
		     break;
	     default:
		 echo json_encode(['cmd' => 'INFO', 'alert' => 'Controller unknown error: '.$e->getMessage()]);
	    }
    }
