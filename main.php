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
	 if (isset($input['cmd']) && $input['cmd'] != 'LOGIN' && !isset($client['auth'])) $input['cmd'] = 'LOGOUT';

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
			   $input['OD'] = $input['OV'] = '';
			   $client['ODid'] = NULL;
			   $output['alert'] = "User '$user' has logged in from $ipport!";
			   Check($db, CHECK_OD_OV, $client, $input, $output);
		    	   $output['customization'] = getUserCustomization($db, $uid);
			   break;
		    	  }
		       $output = ['cmd' => 'DIALOG', 'data' => getLoginDialogData()];
		       $output['data']['dialog']['pad']['profile']['element1']['head'] = "\nWrong password or username, please try again!\n\nUsername";
		       $user ? $output['log'] = "Wrong passowrd or username '$user' from $ipport" : $output['log'] = "Empty username login attempt from $ipport";
		       break;
		  case 'LOGOUT': // Client context menu logout event or any other event from unauthorized client or pass change or timeout
		       $output = ['cmd' => 'DIALOG', 'data' => getLoginDialogData()];
		       if (isset($client['auth'])) $output['log'] = 'User '.$client['auth'].' has logged out!';
		       $client['auth'] = NULL;
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
			   $output = ['cmd' => 'DIALOG', 'data' => ['title'  => 'New Object Database', 'dialog'  => ['Database' => ['Properties' => $newProperties, 'Permissions' => $newPermissions], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => ['CREATE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['cmd' => 'New Object Database', 'style' => 'width: 760px; height: 720px;', 'esc' => '', 'display_single_profile' => '']]];
			   break;
		          }
		       $output['cmd'] = 'New Object Database';
		       $client['data'] = $input['data'];
		       $query = $db->prepare("INSERT INTO `$$$` (id,client) VALUES (:id,:client)");
		       $query->execute([':id' => $output['data'] = GenerateRandomString(), ':client' => json_encode($client)]);
		       break;
		  case 'Edit Database Structure':
		       Check($db, NULL, $client, $input, $output);
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
			   $output['alert'] = "Unable to get '$odname' Object Database properties!";
			   break;
			  }
		       $output['cmd'] = 'Edit Database Structure';
		       $client['data'] = $input['data'];
		       $query = $db->prepare("INSERT INTO `$$$` (id,client) VALUES (:id,:client)");
		       $query->execute([':id' => $output['data'] = GenerateRandomString(), ':client' => json_encode($client)]);
		       break;
		  case 'KEYPRESS':
		  case 'DBLCLICK':
		  case 'CONFIRM':
		  case 'INIT':
		  case 'DELETEOBJECT':
		       if (!Check($db, CHECK_OD_OV | GET_ELEMENTS | GET_VIEWS | CHECK_OID | CHECK_EID | CHECK_ACCESS, $client, $input, $output)) break;
		       $client['data'] = $input['data'];
		       //exec(PHPBINARY." wrapper.php $client[uid] $client[ODid] $client[OVid] '".json_encode($client, JSON_HEX_APOS | JSON_HEX_QUOT)."' >/dev/null &");
		       exec(PHPBINARY." wrapper.php '".json_encode($client, JSON_HEX_APOS | JSON_HEX_QUOT)."' >/dev/null &");
		       break;
		  default:
		       $output['alert'] = "Controller report: unknown client event '$input[cmd]'!";
		 }
	     }
	 catch (PDOException $e)
	     {
	      lg($e);
    	      $output['error'] = 'Controller error: '.$e->getMessage().'!';
	     }
	 
	 // Write output result to the client socket
	 if ($output != ['cmd' => ''] && isset($socketarray[$cid]))
	    {
	     if (isset($output['error'])) $client['ODid'] = $client['OVid'] = $client['OD'] = $client['OV'] = '';
	     if (isset($client['auth'])) $output['auth'] = $client['auth'];
	     socket_write($socket, encode(json_encode($output)));
	    }
	}
		
 // Process queue events from sql table `$$`
 $query = $db->prepare("SELECT * FROM `$$`");
 $query->execute();
 foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $value)
	 {
	  $handler = json_decode($value['client'], true);
	  switch ($handler['cmd'])
	         {
		  case 'EDIT':
		  case 'DIALOG':
		       if (isset($socketarray[$handler['cid']])) socket_write($socketarray[$handler['cid']], encode(json_encode($handler)));
		       break;
		  case 'SET':
		       foreach ($socketarray as $cid => $sock) if ($sock != $mainsocket)
		    	       if ($clientsarray[$cid]['ODid'] === $handler['ODid'] && $clientsarray[$cid]['OVid'] === $handler['OVid'] && $clientsarray[$cid]['params'] === $clientsarray[$handler['cid']]['params']) socket_write($sock, encode(json_encode($handler)));
		       break;
	    	  case 'CALL':
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
		       if (!Check($db, CHECK_OD_OV, $handler, $handler, $output)) break;
		       if (isset($handler['params'])) // OV refresh due to handler call command
		          {
			   MakeViewCall($db, $socketarray[$handler['cid']], $clientsarray[$handler['cid']], $handler);
			   break;
			  }
		       foreach ($socketarray as $cid => $sock) if ($sock != $mainsocket) // OV refresh due to add/remove object operation
		    	       if ($clientsarray[$cid]['ODid'] === $handler['ODid'] && $clientsarray[$cid]['OVid'] === $handler['OVid'] && $clientsarray[$cid]['params'] === $clientsarray[$handler['cid']]['params']) MakeViewCall($db, $sock, $clientsarray[$cid], $handler);
		       break;
		 }
	  $query = $db->prepare("DELETE FROM `$$` WHERE id=$value[id]");
	  $query->execute();
	 }
}
