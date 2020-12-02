<?php

try {
     require_once 'eroc.php';

     //lg(session_get_cookie_params());
     //lg($_SERVER);
     //lg(PHP_VERSION_ID);
     //lg(gettype([]));
     $input = json_decode(file_get_contents("php://input"), true);
     if (!isset($input['cmd'])) $input = ['cmd' => '']; // Set empty cmd in case of undefined or exit?

     if ($input['cmd'] != 'LOGOUT') // Always perform check user auth except logout context menu push event
     if (!isset($_SESSION["u"]) || !isset($_SESSION['h']) || !password_verify(getUserPass($db, $_SESSION['u']), $_SESSION['h'])) // User is unauthenticated or password has been changed?
     if ($input['cmd'] === 'LOGIN' && ($user = $input['data']['dialog']['pad']['profile']['element1']['data']) != '' && ($pass = $input['data']['dialog']['pad']['profile']['element2']['data']) != '' && password_verify($pass, $hash = getUserPass($db, $uid = getUserId($db, $user)))) // Login dialog evet occured and user/pass are correct and not empty
	{
	 $_SESSION['u'] = $uid;
	 $_SESSION['h'] = password_hash($hash, PASSWORD_DEFAULT);
	 $customization = getUserCustomization($db, $uid);
	 if (!isset($input['data']['flags']['callback']))
	    {
	     $output = ['cmd' => 'INFO', 'alert' => "User '$user' has logged in!", 'user' => $user];
	     if (isset($customization)) $output['customization'] = $customization;
	     echo json_encode($output);
	     LogMessage($db, $output['alert'], 'info');
	     exit;
	    }
	 $input = $input['data']['flags']['callback'];
	 if ($input['cmd'] === 'Edit Database Structure' && gettype($input['data']) != 'string') exit; // Disallow OD dialog overwrite its data after session timeout
	 if ($input['cmd'] === 'LOGIN') $input = ['cmd' => 'GETMAINSTART', 'OD' => '', 'OV' => ''];
	}
      else // Login dialog evet occured, but user/pass are empty or wrong
        {    
	 $output = ['cmd' => 'DIALOG', 'data' => getLoginDialogData()];
	 if ($input['cmd'] != 'LOGIN' || isset($input['data']['flags']['callback'])) $output['data']['flags']['callback'] = $input;
	 if ($input['cmd'] === 'LOGIN') $output['data']['dialog']['pad']['profile']['element1']['head'] = "\nWrong password or username, please try again!\n\nUsername";
	 echo json_encode($output);
	 if (isset($user)) if ($user === '') LogMessage($db, 'Empty username login attempt', 'info');
	  else LogMessage($db, "Wrong passowrd or username '$user'", 'info');
	 exit;
	}
     if (isset($_SESSION['u'])) $currentuser = getUserName($db, $_SESSION['u']);
	
     switch ($input['cmd'])
	    {
	     case 'GETMAINSTART':
	     case 'GETMAIN':
	          if (isset($input['data']['dialog']['pad']['profile']))
		     {
		      $input['paramsOV'] = [];
		      foreach ($input['data']['dialog']['pad']['profile'] as $key => $value) $input['paramsOV'][$key] = $value['data'];
		     }
		  if ($input['cmd'] === 'GETMAINSTART') 
		     {
	    	      $customization = getUserCustomization($db, $_SESSION['u']);
		      if (isset($_SERVER["HTTP_USER_AGENT"])) $log = 'Application has started on '.$_SERVER['HTTP_USER_AGENT'];
		       else $log = 'Application has been started';
		     }
		  if (!Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | CHECK_ACCESS)) getMainFieldData($db);
		  break;
	     case 'GETMENU':
		  $output = ['cmd' => ''];
		  $sidebar = getODVNamesForSidebar($db);
		  break;
	     case 'LOGOUT':
		  $output = ['cmd' => 'DIALOG', 'data' => getLoginDialogData()];
		  if (isset($_SESSION['u'])) unset($_SESSION['u']);
		  if (isset($currentuser))
		     {
		      $log = "User $currentuser has logged out!";
		      $currentuser = NULL;
		     }
		  break;
	     case 'New Object Database':
		  if (Check($db, CHECK_ACCESS)) break;
		  if (!isset($input['data']))
		     {
	              initNewODDialogElements();
		      $output = ['cmd' => 'DIALOG', 'data' => ['title'  => 'New Object Database', 'dialog'  => ['Database' => ['Properties' => $newProperties, 'Permissions' => $newPermissions], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => ['CREATE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['cmd' => 'New Object Database', 'style' => 'width: 760px; height: 720px;', 'esc' => '', 'display_single_profile' => '']]];
		      break;
		     }
		  $output = NewOD($db);
		  break;
	     case 'Edit Database Structure':
		  if (!isset($input['data'])) break;
		  
		  $odname = $input['data'];
		  if (gettype($odname) === 'string')
		     {
 		      $query = $db->prepare("SELECT odprops FROM `$` WHERE odname=:odname");
		      $query->execute([':odname' => $odname]);
		      if ($odprops = json_decode($query->fetch(PDO::FETCH_NUM)[0], true))
			 {
			  $odprops['flags']['callback'] = $odname;
			  $odprops['title'] .= " - '$odname'";
			  ksort($odprops['dialog'], SORT_STRING);
			  $output = ['cmd' => 'DIALOG', 'data' => $odprops];
			  break;
			 }
		      $output = ['cmd' => 'INFO', 'alert' => "Unable to get '$odname' Object Database properties!"];
		      break;
		     }
		     
		  if (gettype($odname) === 'array') $output = EditOD($db);
		  break;
	     case 'DELETEOBJECT':
		  if (Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | SET_CMD_DATA | CHECK_OID | CHECK_ACCESS)) break;
		  if (!DeleteObject($db)) getMainFieldData($db);
		  break;
	     case 'INIT':
		  if (Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | SET_CMD_DATA | CHECK_ACCESS)) break;
		  //------------------Handle all elements of a new object------------------
		  $output = [];
		  foreach ($allElementsArray as $id => $profile)
		       if (($handlerName = $profile['element4']['data']) != '' && ($eventArray = parseJSONEventData($db, $profile['element5']['data'], $cmd, $id)))
		    	  {
			   $eventArray['data'] = isset($data[$id]) ? $data[$id] : '';
			   $output[$id] = Handler($handlerName, json_encode($eventArray));
			   if ($output[$id]['cmd'] != 'SET' && $output[$id]['cmd'] != 'RESET') unset($output[$id]);
			  }
		  InsertObject($db);
		  //-----------------------------------------------------------------------
		  getMainFieldData($db);
		  break;
	     case 'CUSTOMIZATION':
	     case 'KEYPRESS':
	     case 'DBLCLICK':
	     case 'CONFIRM':
		  if (Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | SET_CMD_DATA | CHECK_OID | CHECK_EID | CHECK_ACCESS)) break;
			    
		  if ($input['cmd'] === 'CUSTOMIZATION' && $_SESSION['u'] == $input['oId'] && isset($input['data']['dialog']['pad']['misc customization']['element5']['data']))
		  if ($input['data']['dialog']['pad']['misc customization']['element5']['data'] != '' && ($uid = getUserId($db, $input['data']['dialog']['pad']['misc customization']['element5']['data'])) && $uid != $_SESSION['u'])
		     $customization = getUserCustomization($db, $uid, true);
		   else
		     $customization = $input['data']['dialog'];
		  if ($input['cmd'] === 'CUSTOMIZATION') $cmd = 'CONFIRM';
		  
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
				 case 'CALL':
				      if (isset($output[$eid]['data']) && is_array($output[$eid]['data']) && isset($OD) && isset($OV))
				         {
					  $input = ['cmd' => 'GETMAIN', 'paramsOV' => []];
					  if (isset($output[$eid]['data']['Params'])) $input['paramsOV'] = $output[$eid]['data']['Params'];
					  if (!isset($output[$eid]['data']['OD'])) $input['OD'] = $OD; else $input['OD'] = $output[$eid]['data']['OD'];
					  if (!isset($output[$eid]['data']['OV'])) $input['OV'] = $OV; else $input['OV'] = $output[$eid]['data']['OV'];
					  $output = ['cmd' => 'CALL'];
					  if (!Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | CHECK_ACCESS)) getMainFieldData($db);
				         }
				      break;
			     }
		     }
		  break;
	     default:
	          $output = ['cmd' => 'INFO', 'alert' => 'Controller report: unknown event "'.$input['cmd'].'" received from the client!'];
	    }
    }
     
catch (PDOException $e)
    {
     lg($e);
     $msg = $e->getMessage();
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
		  if (preg_match("/Duplicate entry/", $msg) === 1) $alert = 'Failed to add new OD: database name or its tables already exist!';
		   else $alert = "Failed to add new OD: $msg";
		  break;
	     case 'Edit Database Structure':
	    	  if (gettype($input['data']) === 'string') $alert = "Failed to get OD properties: $msg";
		   else $alert = "Failed to write OD properties: $msg";
	          break;
	     case 'GETMENU':
		  $alert = "Failed to read sidebar OD list: $msg";
		  break;
	     case 'GETMAINSTART':
	     case 'GETMAIN':
		  $alert = "Failed to get OD data: $msg";
		  break;
	     case 'DELETEOBJECT':
		  $alert = "Failed to delete object: $msg";
		  break;
	     case 'INIT':
		  if (preg_match("/Duplicate entry/", $msg) === 1) $alert = 'Failed to add new object: unique elements duplicate entry!';
		   else $alert = "Failed to add new object: $msg";
		  break;
	     case 'KEYPRESS':
	     case 'DBLCLICK':
	     case 'CONFIRM':
		  if (preg_match("/Duplicate entry/", $msg) === 1) $alert = 'Failed to write object data: unique elements duplicate entry!';
		   else $alert = "Failed to write object data: $msg";
		  if ($input['cmd'] === 'CONFIRM' && isset($eid))
		     {
		      $undo = getElementArray($db, $eid);
		      if (!isset($undo)) $undo = ['value' => ''];
		      $output = ['cmd' => 'SET', 'oId' => $oid, 'data' => [$eid => $undo], 'alert' => $alert];
		     }
		  break;
	     default:
		  echo json_encode(['cmd' => 'INFO', 'error' => "Controller unknown error: '$msg'"]);
		  exit;
	    }
    }

// Check output command
if (!isset($output['cmd']))
if (isset($error)) $output = ['cmd' => 'INFO', 'error' => $error];
 else if (isset($alert)) $output = ['cmd' => 'INFO', 'alert' => $alert];
  else $output = ['cmd' => ''];

// Add some data tou output result     
if (isset($log))		$output['log'] = $log;
if (isset($OD))			$output['OD'] = $OD;
if (isset($OV))			$output['OV'] = $OV;
if (isset($currentuser))	$output['user'] = $currentuser;
if (isset($customization))	$output['customization'] = $customization;
if (isset($sidebar))		$output['sidebar'] = $sidebar;
    
// Exception occured and active transaction does exist? Roll it back to allow save corresponded log message to the database
if (isset($msg) && $db->inTransaction()) $db->rollBack();

// Echo output result
echo json_encode($output);

// Get current user and build part of the log message
$prefix = '';
if (isset($currentuser))	$prefix .= "['$currentuser']";
if (isset($OD) && $OD != '')	$prefix .= "[OD '$OD']";
if (isset($OV) && $OV != '')	$prefix .= "[OV '$OV']";
if ($prefix != '')		$prefix .= ': ';

// Log the message
if (isset($output['log']))					LogMessage($db, $prefix.$output['log'], 'info');
if (isset($output['alert']))					LogMessage($db, $prefix.$output['alert'], 'alert');
if (isset($output['error']) && (!isset($OD) || $OD != ''))	LogMessage($db, $prefix.$output['error']);
