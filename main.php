<?php

require_once 'core.php';

function MakeViewCall($db, &$socket, &$client, $handler)
{
 $handler['uid'] = $client['uid'];
 $handler['auth'] = $client['auth'];
 if (!isset($handler['params'])) $handler['params'] = $client['params'];
 
 $query = $db->prepare("INSERT INTO `$$$` (id,client) VALUES (:id,:client)");
 $query->execute([':id' => $handler['data'] = GenerateRandomString(), ':client' => json_encode($handler)]);
 socket_write($socket, encode(json_encode($handler)));
}

error_reporting(E_ALL);	// Report all errors
set_time_limit(0);	// set script execution time to unlimited value
ob_implicit_flush();	// Turn implicit system buffer flushing on 

$query = $db->prepare("DELETE FROM `$$`");
$query->execute();
$query = $db->prepare("DELETE FROM `$$$`");
$query->execute();

if (false === ($mainsocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) { lg('Create socket connection error: '.socket_strerror(socket_last_error())); return; }
if (false === socket_bind($mainsocket, IP, PORT)) { lg('Socket binding error: '.socket_strerror(socket_last_error())); return; }
if (false === socket_listen($mainsocket)) { lg('Set socket option error: '.socket_strerror(socket_last_error())); return; }
$socketarray = [$mainsocket];
$clientsarray = [[]];

while (true)
{
 $read = $socketarray; // Make copy of array of sockets
 $write = $except = null;

 if (socket_select($read, $write, $except, 0, SOCKETTIMEOUTUSEC) === false) // Waiting for the sockets accessable for reading without timeout
    {
     lg('Socket wait status error: '.socket_strerror(socket_last_error()));
     return;
    }
	    
 if (in_array($mainsocket, $read))
    {
     if (($newsocket = socket_accept($mainsocket)) && ($info = handshake($newsocket)))
	{
	 lg('New web socket connection from '.$info['ip'].':'.$info['port']." accepted.\nUser Agent: ".$info['User-Agent']);
	 $socketarray[] = $newsocket;
	 $clientsarray[] = ['auth' => NULL, 'authtime' => NULL, 'uid' => NULL, 'ip' => $info['ip'], 'port' => $info['port'], 'User-Agent' => $info['User-Agent'], 'ODid' => '', 'OVid' => '', 'OD' => '', 'OV' => ''];
	}
     unset($read[array_search($mainsocket, $read)]);
    }

 $now = intval(strtotime("now"));
 foreach($read as $socket)
	{
	 $client = &$clientsarray[$cid = array_search($socket, $socketarray)];
	 $client['cid'] = $cid;
	 $output = ['cmd' => ''];
	 $ipport = $client['ip'].':'.$client['port'];
	 $data = socket_read($socket, SOCKETREADMAXBYTES);
	 $decoded = decode($data);
	 $input = json_decode($decoded['payload'], true);
	 if (gettype($input) === 'array' && !isset($input['data'])) $input['data'] = '';
	 if (isset($input['cmd']) && $input['cmd'] != 'LOGIN')
	    {
	     if (!isset($client['auth'])) $input['cmd'] = 'LOGOUT';
	      else if ($now - $client['authtime'] > SESSIONLIFETIME) $input['cmd'] = 'TIMEOUT';
	    }
	     
	 try {
	      // Client close socet connection event or unknown command?
	      if (false === $decoded || 'close' === $decoded['type'] || !isset($input['cmd']))
		 {
		  socket_shutdown($socket);
		  socket_close($socket);
		  $output['alert'] = "Client $ipport web socket connection closed due to undefined or close event";
		  unset($socketarray[$cid]);
		  unset($clientsarray[$cid]);
		 }
	       else switch ($input['cmd'])
	    	 {
		  case 'LOGIN': // Client context menu login dialog event. Check if user/pass are correct and not empty
		       if (($user = $input['data']['dialog']['pad']['profile']['element1']['data']) != '' &&  ($pass = $input['data']['dialog']['pad']['profile']['element2']['data']) != '' && password_verify($pass, $hash = getUserPass($db, $uid = getUserId($db, $user))))
		    	  {
			   $client['auth'] = $user;
			   $client['uid'] = $uid;
			   $client['authtime'] = $now;
			   $input['OD'] = $input['OV'] = '';
			   $client['ODid'] = NULL;
			   Check($db, CHECK_OD_OV, $client, $input, $output);
			   $output['log'] = "User '$user' has logged in from $ipport and user agent: ".$client['User-Agent']."!";
		    	   $output['customization'] = getUserCustomization($db, $uid);
			   //------------------Log out login user from other sessions------------------------
		    	   foreach ($socketarray as $sockid => $sock)
		    	        if ($sock != $mainsocket && $sockid != $cid && $clientsarray[$sockid]['uid'] === $uid)
				   {
				    socket_write($sock, encode(json_encode(['cmd' => 'DIALOG', 'data' => getLoginDialogData(), 'sidebar' => [], 'auth' => '', 'error' => ''])));
				    $clientsarray[$sockid]['uid'] = $clientsarray[$sockid]['auth'] = NULL;
				    break;
				   }
			   //--------------------------------------------------------------------------------
			   break;
		    	  }
		       $output = ['cmd' => 'DIALOG', 'data' => getLoginDialogData()];
		       $output['data']['dialog']['pad']['profile']['element1']['head'] = "\nWrong password or username, please try again!\n\nUsername";
		       $user ? $output['log'] = "Wrong passowrd or username '$user' from $ipport" : $output['log'] = "Empty username login attempt from $ipport";
		       break;
		  case 'TIMEOUT':
		  case 'LOGOUT': // Client context menu logout event or any other event from unauthorized client or pass change or timeout
		       $output = ['cmd' => 'DIALOG', 'data' => getLoginDialogData(), 'sidebar' => [], 'auth' => '', 'error' => ''];
		       if ($input['cmd'] === 'TIMEOUT') $output['data']['dialog']['pad']['profile']['element1']['head'] = "\nSession timeout, please log in!\n\nUsername";
		        else if (isset($client['auth'])) $output['log'] = 'User '.$client['auth'].' has logged out!';
		       $client['uid'] = $client['auth'] = NULL;
		       break;
		  case 'SIDEBAR': // Client sidebar items wrap/unwrap event
		       Check($db, CHECK_OD_OV, $client, $input, $output);
		       break;
		  case 'CALL': // Client OD data fetch event
		       if (!Check($db, CHECK_OD_OV, $client, $input, $output)) break;
		       $client['params'] = [];
		       if (isset($input['data']['dialog']['pad']['profile'])) // First convert input dialog data to object selection params data
			  foreach ($input['data']['dialog']['pad']['profile'] as $key => $value) $client['params'][$key] = $value['data'];
		       $output['cmd'] = 'CALL';
		       $query = $db->prepare("INSERT INTO `$$$` (id,client) VALUES (:id,:client)");
		       $query->execute([':id' => $output['data'] = GenerateRandomString(), ':client' => json_encode($client)]);
		       break;
		  case 'New Object Database':
		       if (!Check($db, CHECK_ACCESS, $client, $input, $output)) break;
		       if ($input['data'] === '')
		          {
	    		   initNewODDialogElements();
			   $output = ['cmd' => 'DIALOG', 'data' => ['title'  => 'New Object Database', 'dialog'  => ['Database' => ['Properties' => $newProperties, 'Permissions' => $newPermissions], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => CREATECANCEL, 'flags'  => ['style' => 'width: 760px; height: 720px;', 'esc' => '', 'display_single_profile' => '']]];
			   $output['data']['buttons']['CREATE']['call'] = 'New Object Database';
			   break;
		          }
		       $output['cmd'] = 'New Object Database';
		       $client['data'] = $input['data'];
		       $query = $db->prepare("INSERT INTO `$$$` (id,client) VALUES (:id,:client)");
		       $query->execute([':id' => $output['data'] = GenerateRandomString(), ':client' => json_encode($client)]);
		       break;
		  case 'Edit Database Structure':
		       if (gettype($input['data']) === 'string')
		    	  {
 			   $query = $db->prepare("SELECT odname,odprops FROM `$` WHERE id=:id");
			   $query->execute([':id' => $input['data']]);
			   $odprops = $query->fetch(PDO::FETCH_NUM);
			   $odname = $odprops[0];
		    	   if ($odprops = json_decode($odprops[1], true))
			      {
			       $odprops['flags']['callback'] = $input['data'];
			       $odprops['title'] .= " - '$odname' (id $input[data])";
			       ksort($odprops['dialog'], SORT_STRING);
			       $output = ['cmd' => 'DIALOG', 'data' => $odprops];
			       break;
			      }
			   $output['alert'] = "Object Database doesn't exist!";
			   break;
			  }
		       $client['cmd'] = $output['cmd'] = $input['cmd'];
		       $client['data'] = $input['data'];
		       $query = $db->prepare("INSERT INTO `$$$` (id,client) VALUES (:id,:client)");
		       $query->execute([':id' => $output['data'] = GenerateRandomString(), ':client' => json_encode($client)]);
		       break;
		  case 'INIT':
		  case 'DBLCLICK':
		  case 'KEYPRESS':
		  case 'INS':
		  case 'DEL':
		  case 'F2':
		  case 'F12':
		  case 'CONFIRM':
		  case 'CONFIRMDIALOG':
		  case 'DELETEOBJECT':
		       if (!Check($db, CHECK_OD_OV | GET_ELEMENTS | GET_VIEWS | CHECK_OID | CHECK_EID | CHECK_ACCESS, $client, $input, $output)) break;
		       $client['data'] = $input['data'];
		       //exec(PHPBINARY." wrapper.php $client[uid] $client[ODid] $client[OVid] '".json_encode($client, JSON_HEX_APOS | JSON_HEX_QUOT)."' >/dev/null &");
		       exec(PHPBINARY." wrapper.php '".json_encode($client, JSON_HEX_APOS | JSON_HEX_QUOT)."' >/dev/null &");
		       break;
		  case 'Task Manager':
		       $client['data'] = $input['data'];
		       exec(PHPBINARY." taskmanager.php '".json_encode($client, JSON_HEX_APOS | JSON_HEX_QUOT)."' >/dev/null &");
		       break;
		  default:
		       $output['log'] = $output['alert'] = "Controller report: unknown event '$input[cmd]' from client $ipport and user '$input[auth]'!";
		 }
	     }
	 catch (PDOException $e)
	     {
	      $msg = $e->getMessage();
	      if (preg_match("/SQL server has gone away/", $msg) === 1)
	         {
		  ResetAllAuth($clientsarray);
		  include 'connect.php';
		  LogMessage($db, $clientsarray[$cid], $msg);
		  break;
		 }
    	      $output['log'] = $output['alert'] = "Controller error: $msg!";
	     }
	 
	 // Write output result to the client socket
	 if ($output != ['cmd' => ''] && isset($socketarray[$cid]))
	    {
	     if (isset($output['log'])) LogMessage($db, $client, $output['log']);
	     if (isset($output['error'])) $client['ODid'] = $client['OVid'] = $client['OD'] = $client['OV'] = '';
	     if (isset($client['auth'])) $output['auth'] = $client['auth'];
	     socket_write($socket, encode(json_encode($output)));
	    }
	}

 try {
 // Process queue events from sql table `$$`
 $query = $db->prepare("SELECT * FROM `$$`");
 $query->execute();
 foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $value)
	 {
	  $handler = json_decode($value['client'], true);
	  
	  if (isset($handler['passchange'])) foreach ($socketarray as $cid => $sock)
	  if ($sock != $mainsocket && $clientsarray[$cid]['uid'] === $handler['passchange'])
	     {
	      socket_write($sock, encode(json_encode(['cmd' => 'DIALOG', 'data' => getLoginDialogData(), 'sidebar' => [], 'auth' => '', 'error' => ''])));
	      $handler['passchange'] = $clientsarray[$cid]['uid'] = $clientsarray[$cid]['auth'] = NULL;
	      $clientsarray[$cid]['ODid'] = $clientsarray[$cid]['OVid'] = $clientsarray[$cid]['OD'] = $clientsarray[$cid]['OV'] = '';
	     }
	     
	  switch ($handler['cmd'])
	         {
		  case 'Task Manager':
		       $cid = $handler['cid'];
		       if (isset($socketarray[$cid]))
		          {
			   $handler['cmd'] = 'DIALOG';
			   socket_write($socketarray[$cid], encode(json_encode($handler)));
			  }
		       break;
		  case 'ALERT':
		  case 'DIALOG':
		  case 'EDIT':
		       $cid = $handler['cid'];
		       if (isset($socketarray[$cid]) && $clientsarray[$cid]['ODid'] === $handler['ODid'] && $clientsarray[$cid]['OVid'] === $handler['OVid'] && $clientsarray[$cid]['params'] === $handler['params'])
			  {
			   if ($handler['cmd'] === 'ALERT') $handler = ['cmd' => '', 'alert' => $handler['data']];
			   socket_write($socketarray[$cid], encode(json_encode($handler)));
			  }
		       break;
		  case 'SET':
		       if (isset($handler['alert'])) $alert = $handler['alert']; else unset($alert);
		       unset($handler['alert']);
		       
		       foreach ($socketarray as $cid => $sock)
		    	    if ($sock != $mainsocket && $clientsarray[$cid]['auth'])
			    if ($clientsarray[$cid]['ODid'] === $handler['ODid'] && $clientsarray[$cid]['OVid'] === $handler['OVid'] && $clientsarray[$cid]['params'] === $handler['params'])
			    if (isset($alert) && $cid === $handler['cid'])
			       socket_write($sock, encode(json_encode($handler + ['alert' => $alert])));
			     else
			       socket_write($sock, encode(json_encode($handler)));
		       break;
	    	  case 'CALL':
		       // OV refresh due to add/remove object event
		       if (!isset($handler['params']))
			  {
		    	   if (isset($handler['alert'])) $alert = $handler['alert']; else unset($alert);
			   unset($handler['alert']);
		       
			   foreach ($socketarray as $cid => $sock) if ($sock != $mainsocket && $clientsarray[$cid]['auth'])
				   if ($clientsarray[$cid]['ODid'] === $handler['ODid'] && $clientsarray[$cid]['OVid'] === $handler['OVid'])
				   if (isset($alert) && $cid === $handler['cid'])
				      MakeViewCall($db, $sock, $clientsarray[$cid], $handler + ['alert' => $alert]);
				    else
				      MakeViewCall($db, $sock, $clientsarray[$cid], $handler);
			   break;
			  }
		  
		       // OV refresh due to handler call command
		       if (!isset($handler['ODid']))
		          {
			   $query = $db->prepare("SELECT id FROM $ WHERE odname=:odname");
			   $query->execute([':odname' => $handler['OD']]);
			   if (count($handler['ODid'] = $query->fetchAll(PDO::FETCH_NUM)) > 0) $handler['ODid'] = $handler['ODid'][0][0]; else $handler['ODid'] = '';
			  }
		       if (!isset($handler['OVid']))
		          {
			   $handler['OVid'] = '';
			   $query = $db->prepare("SELECT JSON_EXTRACT(odprops, '$.dialog.View') FROM $ WHERE id=:id");
			   $query->execute([':id' => $handler['ODid']]);
			   foreach (json_decode($query->fetch(PDO::FETCH_NUM)[0], true) as $key => $View)
				if ($key != 'New view' && $key === $handler['OV']) { $handler['OVid'] = $View['element1']['id']; break; }
			  }
		       if (Check($db, CHECK_OD_OV, $handler, $handler, $output))
		          MakeViewCall($db, $socketarray[$handler['cid']], $clientsarray[$handler['cid']], $handler);
		       break;
		 }
	  $query = $db->prepare("DELETE FROM `$$` WHERE id=$value[id]");
	  $query->execute();
	 }
 }
 catch (PDOException $e)
 {
  lg($msg = $e->getMessage());
  $client = [];
  if (preg_match("/SQL server has gone away/", $msg) === 1)
     {
      ResetAllAuth($clientsarray);
      include 'connect.php';
     }
  LogMessage($db, $client, $msg);
 }
}
