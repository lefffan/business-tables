<?php

require_once 'core.php';

error_reporting(E_ALL);	// Report all errors
set_time_limit(0);	// set script execution time to unlimited value
ob_implicit_flush();	// Turn implicit system buffer flushing on 

if (false === ($mainsocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) { lg('Create socket connection error: '.socket_strerror(socket_last_error())); return; }
if (false === socket_bind($mainsocket, IP, PORT)) { lg('Socket binding error: '.socket_strerror(socket_last_error())); return; }
if (false === socket_listen($mainsocket)) { lg('Set socket option error: '.socket_strerror(socket_last_error())); return; }
$socketarray = [$mainsocket];
$clientsarray = [[]];
lg('Server started');

while (true)
      {
       $read = $socketarray;					// Make copy of array of sockets
       $write = $except = null;
	    
       if (!socket_select($read, $write, $except, null)) break;	// Waiting for the sockets accessable for reading without timeout
	    
       if (in_array($mainsocket, $read))
	  {
	   if (($newsocket = socket_accept($mainsocket)) && ($info = handshake($newsocket)))
	      {
	       lg('New web socket connection from '.$info['ip'].':'.$info['port']." accepted.\nUser Agent: ".$info['User-Agent']);
	       $socketarray[] = $newsocket;
	       $clientsarray[] = ['auth' => NULL, 'authtime' => NULL, 'uid' => NULL, 'ip' => $info['ip'], 'port' => $info['port'], 'User-Agent' => $info['User-Agent'], 'OD' => '', 'OV' => ''];
	      }
	   unset($read[array_search($mainsocket, $read)]);
	  }

       foreach ($read as $socket)
	       {
	        $output = $error = $alert = $log = $sidebar = $customization = NULL;
		$client = &$clientsarray[$cid = array_search($socket, $socketarray)];
		$ipport = $client['ip'].':'.$client['port'];
		$data = socket_read($socket, 100000);
		$decoded = decode($data);
		$input = json_decode($decoded['payload'], true);
		if (gettype($input) === 'array' && !isset($input['data'])) $input['data'] = '';
		
	   try {
		// Client close socet connection event or unknown command?
		if (false === $decoded || 'close' === $decoded['type'] || !isset($input['cmd']))
		   {
		    socket_shutdown($socket);
		    socket_close($socket);
		    $log = "Client $ipport web socket connection closed due to undefined or close event";
		    unset($socketarray[$cid]);
		    unset($clientsarray[$cid]);
		   }
		   
		// Client context menu login vent
		else if ($input['cmd'] === 'LOGIN')
		   {
		    // Login dialog evet occured and user/pass are correct and not empty 
		    if (($user = $input['data']['dialog']['pad']['profile']['element1']['data']) != '' &&  ($pass = $input['data']['dialog']['pad']['profile']['element2']['data']) != '' && password_verify($pass, $hash = getUserPass($db, $uid = getUserId($db, $user))))
		       {
		        $customization = getUserCustomization($db, $uid);
			$client['auth'] = $user;
			$client['uid'] = $uid;
			$client['OD'] = $client['OV'] = '';
			$client['ODid'] = NULL;
			$alert = "User '$user' has logged in from $ipport!";
			count($sidebar = getODVNamesForSidebar($db)) == 0 ? $error = 'Please create Object Database first!' : $error = 'Please create/select Object View!';
		       }
		    else // Login dialog evet occured, but user/pass are empty or wrong
		       {
		        $output = ['cmd' => 'DIALOG', 'data' => getLoginDialogData()];
			$output['data']['dialog']['pad']['profile']['element1']['head'] = "\nWrong password or username, please try again!\n\nUsername";
			$user ? $log = "Wrong passowrd or username '$user' from $ipport" : $log = "Empty username login attempt from $ipport";
		       }
		   }
		
		// Client context menu logout event or any other event from unauthorized client or pass change or timeout
		else if ($input['cmd'] === 'LOGOUT' || !isset($client['auth']))
		   {
		    $output = ['cmd' => 'DIALOG', 'data' => getLoginDialogData()];
		    if (isset($client['auth'])) $log = 'User '.$client['auth'].' has logged out!';
		    $client['auth'] = NULL;
		   }
		   
		// Client sidebar items wrap/unwrap event
		else if ($input['cmd'] === 'GETSIDEBAR')
		   {
		    if (count($sidebar = getODVNamesForSidebar($db)) == 0) $error = 'Please create Object Database first!';
		     else if (!isset($sidebar[$client['OD']], $sidebar[$client['OD']][$client['OV']])) $error = 'Please create/select Object View!';
		   }
		
		// Client OD data fetch event
		else if ($input['cmd'] === 'GETMAIN')
		   {
		    $client['data'] = '';
		    if (isset($input['data']['dialog']['pad']['profile'])) // First convert input dialog data to object selection params data
		       {
		        $data = [];
			foreach ($input['data']['dialog']['pad']['profile'] as $key => $value) $data[$key] = $value['data'];
			$client['data'] = $data;
		       }
		    if (!Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | CHECK_ACCESS)) getMainFieldData($db);
		   }
		   
		// Context menu object delete event
		else if ($input['cmd'] === 'DELETEOBJECT')
		   {
		    if (!Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | CHECK_OID | CHECK_ACCESS) && !DeleteObject($db)) getMainFieldData($db);
		   }
		   
		// Context menu new OD or its dialog apply data event
		else if ($input['cmd'] === 'New Object Database')
		   {
		    if (!Check($db, CHECK_ACCESS))
		    if ($input['data'] === '')
		       {
	    		initNewODDialogElements();
			$output = ['cmd' => 'DIALOG', 'data' => ['title'  => 'New Object Database', 'dialog'  => ['Database' => ['Properties' => $newProperties, 'Permissions' => $newPermissions], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => ['CREATE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['cmd' => 'New Object Database', 'style' => 'width: 760px; height: 720px;', 'esc' => '', 'display_single_profile' => '']]];
		       }
		     else
		       {
			$output = NewOD($db);
		       }
		   }
		   
		// Context menu edit OD or its dialog apply data event
		else if ($input['cmd'] === 'Edit Database Structure')
		   {
		    if (gettype($odname = $input['data']) === 'string')
		       {
 			$query = $db->prepare("SELECT odprops FROM `$` WHERE odname=:odname");
			$query->execute([':odname' => $odname]);
		        if ($odprops = json_decode($query->fetch(PDO::FETCH_NUM)[0], true))
			   {
			    $odprops['flags']['callback'] = $odname;
			    $odprops['title'] .= " - '$odname'";
			    ksort($odprops['dialog'], SORT_STRING);
			    $output = ['cmd' => 'DIALOG', 'data' => $odprops];
			   }
			 else
			   {
			    $alert = "Unable to get '$odname' Object Database properties!";
			   }
		       }
		     else
		       {
		        if (gettype($odname) === 'array') $output = EditOD($db);
		       }
		   }
		   
		// Element event
		else if ($input['cmd'] === 'CUSTOMIZATION' || $input['cmd'] === 'KEYPRESS' || $input['cmd'] === 'DBLCLICK' || $input['cmd'] === 'CONFIRM' || $input['cmd'] === 'INIT')
		   {
		    if (!Check($db, CHECK_OD_OV | GET_ELEMENT_PROFILES | GET_OBJECT_VIEWS | CHECK_OID | CHECK_EID | CHECK_ACCESS))
		       {
		        if ($input['cmd'] === 'CUSTOMIZATION' && $client['uid'] == $input['oId'] && isset($input['data']['dialog']['pad']['misc customization']['element5']['data']))
			if (($forceuser = $input['data']['dialog']['pad']['misc customization']['element5']['data']) != '' && ($uid = getUserId($db, $forceuser)) && $uid != $client['uid'])
		    	   $customization = getUserCustomization($db, $uid, true);
		         else
		    	   $customization = $input['data']['dialog'];
			if ($input['cmd'] === 'CUSTOMIZATION') $input['cmd'] = 'CONFIRM';
			if ($input['cmd'] === 'INIT') $oid = $eid = 0;
			if (gettype($input['data']) === 'string') { $type = 'string'; $data = str_replace("'", "'".'"'."'".'"'."'", $input['data']); }
			 else { $type = 'json'; $data = json_encode($input['data'], JSON_HEX_APOS | JSON_HEX_QUOT); }
			exec(PHPBINARY." wrapper.php $cid '$client[auth]' $client[ODid] $oid $eid $type $input[cmd] '$data' '".json_encode($allElementsArray, JSON_HEX_APOS | JSON_HEX_QUOT)."' &");
		       }
		   }
		   
		// Unknown client event
		else $alert = "Controller report: unknown event '$input[cmd]' received from the client $ipport!";
	       }
	       
	 /***********************************************************************************************/
	 catch (PDOException $e)
	       {
		       lg($e);
    		       $msg = $e->getMessage();
		       switch ($input['cmd'])
		    	      {
			       case 'New Object Database':
			    	    if (isset($input['ODid']))
				       {
					$query = $db->prepare("DELETE FROM `$` WHERE id=$odid");
					$query->execute();
					$query = $db->prepare("DROP TABLE IF EXISTS `data_$odid`; DROP TABLE IF EXISTS `uniq_$odid`");
					$query->execute();
				       }
				    preg_match("/Duplicate entry/", $msg) === 1 ? $alert = 'Failed to add new OD: database name or its tables already exist!' : $alert = "Failed to add new OD: $msg";
	        		    break;
	    		       case 'Edit Database Structure':
	    			    gettype($input['data']) === 'string' ? $alert = "Failed to get OD properties: $msg" : $alert = "Failed to write OD properties: $msg";
	        		    break;
	    		       case 'GETMENU':
	    			    $alert = "Failed to read sidebar OD list: $msg";
	        		    break;
	    		       case 'GETMAIN':
	    			    $error = "Failed to get OD data: $msg";
	        		    break;
	    		       case 'DELETEOBJECT':
	    			    $alert = "Failed to delete object: $msg";
	        		    break;
	    		       case 'INIT':
			    	    preg_match("/Duplicate entry/", $msg) === 1 ? $alert = 'Failed to add new object: unique elements duplicate entry!' : $alert = "Failed to add new object: $msg";
	        		    break;
			       case 'CUSTOMIZATION':
			       case 'KEYPRESS':
			       case 'DBLCLICK':
			       case 'CONFIRM':
				    $alert = "Client event '".$input['cmd']."' unknown error: $msg";
	        		    break;
			       default:                                                                                   
			            lg("Controller unknown error: '$msg'");
				    exit;
			      }                        
	       }
	 /***********************************************************************************************/
		
		// Exception occured and active transaction does exist? Roll it back to allow save corresponded log message to the database
		if (isset($msg) && $db->inTransaction()) $db->rollBack();
		
		// Add some elements to the output result
		if (!isset($output))			$output = ['cmd' => ''];
		if (isset($error))			{ $output['error'] = $error; $client['OD'] = $client['OV'] = ''; }
		if (isset($alert))			$output['alert'] = $alert;
		if (isset($log))			$output['log'] = $log;
		if (isset($sidebar))			$output['sidebar'] = $sidebar;
		if (isset($customization))		$output['customization'] = $customization;
		if (isset($client['auth']))		$output['user'] = $client['auth'];
		
		// Write  output result to the client socket
		if (isset($clientsarray[$cid])) socket_write($socket, encode(json_encode($output)));
		
		// Get current user and build part of the log message
									$prefix = '';
		if (isset($client['auth']))				$prefix .= "['$client[auth]']";
		if ($client['OD'] != '')				$prefix .= "[OD '$client[OD]'] [OV '$client[OV]']";
		if ($prefix != '')					$prefix .= ': ';

		// Log the message
		if (isset($output['log']))				LogMessage($db, $prefix.$output['log'], 'info');
		if (isset($output['alert']))				LogMessage($db, $prefix.$output['alert'], 'alert');
		if (isset($output['error']) && $client['OD'] != '')	LogMessage($db, $prefix.$output['error']);
	       }
      }
