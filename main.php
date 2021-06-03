<?php

require_once 'core.php';

function MakeViewCall($db, &$socket, &$client, $handler)
{
 CopyArrayElements($client, $handler, ['auth', 'uid']);
 if (!isset($handler['params'])) $handler['params'] = $client['params'];
 $handler['cmd'] = 'CALL'; 
 
 $query = $db->prepare("INSERT INTO `$$$` (id,client) VALUES (:id,:client)");
 $query->execute([':id' => $handler['data'] = GenerateRandomString(), ':client' => json_encode($handler)]);
 fwrite($socket, encode(json_encode($handler)));
}

error_reporting(E_ALL);	// Report all errors
set_time_limit(0);	// set script execution time to unlimited value
ob_implicit_flush();	// Turn implicit system buffer flushing on 

// Flush all queue tables
$query = $db->prepare("DELETE FROM `$$`");
$query->execute();
$query = $db->prepare("DELETE FROM `$$$`");
$query->execute();

$context = stream_context_create();
stream_context_set_option($context, 'ssl', 'local_cert', '/etc/letsencrypt/archive/tabels.app/cert1.pem');
stream_context_set_option($context, 'ssl', 'local_pk', '/etc/letsencrypt/archive/tabels.app/privkey1.pem');
stream_context_set_option($context, 'ssl', 'passphrase', '');
stream_context_set_option($context, 'ssl', 'verify_peer', false);
stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
#stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
$mainsocket = stream_socket_server('ssl://'.IP.':'.strval(PORT), $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context);
$socketarray = [$mainsocket];
$clientsarray = [[]];

while (true)
{
 $read = $socketarray; // Make copy of array of sockets
 $write = $except = null;

 if (stream_select($read, $write, $except, 0, SOCKETTIMEOUTUSEC) === false) // Waiting for the sockets accessable for reading without timeout
    {
     lg('Socket wait status error: '.socket_strerror(socket_last_error()));
     return;
    }

 // Client array:
 // 'auth': client username 
 // 'authtime': user login time stamp
 // 'uid': user id
 // 'ip': client ip address
 // 'port': client tcp/udp port
 // 'User-Agent': Client user agent name
 // 'ODid'/'OVid': Client OD/OV identificators
 // 'OD'/'OV': Client OD/OV names
 // 'params' object selection args array
 // 'oId'/'eId': Object/element ids client event inited by
 // 'cid': Socket array index, used as a client identificator

 if (in_array($mainsocket, $read))
    {
     if (($newsocket = stream_socket_accept($mainsocket)) && ($info = handshake($newsocket)))
	{
	 //lg('New web socket connection from '.$info['ip'].':'.$info['port']." accepted.\nUser Agent: ".$info['User-Agent']);
	 stream_set_blocking ($newsocket, false);
	 $socketarray[] = $newsocket;
	 $clientsarray[] = ['ip' => $info['ip'], 'port' => $info['port'], 'User-Agent' => $info['User-Agent'], 'ODid' => '', 'OVid' => '', 'OD' => '', 'OV' => '', 'streamdata' => ''];
	}
     unset($read[array_search($mainsocket, $read)]);
    }

 $now = strtotime("now");
 foreach($read as $cid => $socket)
	{
	 $decoded = stream_get_contents($socket);
	 if (($length = strlen($decoded)) === 0) continue;
	 if ($length === 16384)
	    {
	     $clientsarray[$cid]['streamdata'] .= $decoded;
	     continue;
	    }
	 $decoded = decode($clientsarray[$cid]['streamdata'].$decoded);
	 $clientsarray[$cid]['streamdata'] = '';

	 // Client close socet connection event or unknown command?
	 if (false === $decoded || 'close' === $decoded['type'])
	    {
	     fclose($socket);
	     unset($socketarray[$cid]);
	     unset($clientsarray[$cid]);
	     continue;
	    }
	
	 $input = json_decode($decoded['payload'], true);
	 if (!isset($input['cmd'])) continue;
	
	 $client = &$clientsarray[$cid];
	 $client['cid'] = $cid;
	 
	 // Init input args
	 $output = ['cmd' => ''];
	 CopyArrayElements($input, $client, ['ODid', 'OVid', 'OD', 'OV', 'cmd', 'data', 'oId', 'eId']);
	 unset($input);
	 if ($client['cmd'] != 'LOGIN' && (!isset($client['auth']) || $now - $client['authtime'] > SESSIONLIFETIME)) $client['cmd'] = 'LOGOUT';
	     
	 try {
	      switch ($client['cmd'])
	    	 {
		  case 'LOGIN': // Client context menu login dialog event. Check if user/pass are correct and not empty
		       if (($user = $client['data']['dialog']['pad']['profile']['element1']['data']) != '' &&  ($pass = $client['data']['dialog']['pad']['profile']['element2']['data']) != '' && password_verify($pass, $hash = getUserPass($db, $uid = getUserId($db, $user))))
		    	  {
			   $client['auth'] = $user;
			   $client['uid'] = $uid;
			   $client['authtime'] = $now;
			   $client['ODid'] = $client['OVid'] = $client['OD'] = $client['OV'] = '';
			   Check($db, CHECK_OD_OV, $client, $output);
			   $output['log'] = "Logged in from $client[ip] with user agent: ".$client['User-Agent']."!";
			   $output['customization'] = getUserCustomization($db, $uid);
			   //------------------Log out new login user from other session------------------------
			   foreach ($socketarray as $sockid => $sock)
			        if ($sock != $mainsocket && $sockid != $cid && isset($clientsarray[$sockid]['uid']) && $clientsarray[$sockid]['uid'] === $uid)
				   {
				    fwrite($sock, encode(json_encode(['cmd' => 'DIALOG', 'data' => getLoginDialogData("\nUser other session detected!\n\nUsername"), 'sidebar' => [], 'auth' => '', 'error' => ''])));
				    cutKeys($clientsarray[$sockid], ['ip', 'port', 'User-Agent']);
				    break;
				   }
			   //-----------------------------------------------------------------------------------
			   break;
		    	  }
		  case 'LOGOUT': // Client context menu logout event or any other event from unauthorized client. Also wrong pass, pass change, timeout
		       $output = ['cmd' => 'DIALOG', 'sidebar' => [], 'auth' => '', 'error' => ''];
		       if ($client['cmd'] === 'LOGOUT')
		          {
			   $title = NULL;
			   if (isset($client['auth'])) $now - $client['authtime'] > SESSIONLIFETIME ? $title = "\nSession timeout, please log in!\n\nUsername" : $output['log'] = 'User '.$client['auth'].' has logged out!';
			  }
			else
			  {
			   $title = "\nWrong password or username, please try again!\n\nUsername";
			   $user ? $output['log'] = "Wrong passowrd or username '$user' from $client[ip]" : $output['log'] = "Empty username login attempt from $client[ip]";
			  }
		       $output['data'] = getLoginDialogData($title);
		       cutKeys($client, ['ip', 'port', 'User-Agent', 'streamdata']);
		       break;
		  case 'CALL': // Client OD data fetch event
		       // Get client input OV params from dialog data if exist
		       $client['params'] = [];
		       if (isset($client['data']['dialog']['pad']['profile'])) foreach ($client['data']['dialog']['pad']['profile'] as $key => $value) $client['params'][$key] = $value['data'];
		  case 'SIDEBAR': // Client sidebar items wrap/unwrap event
		  case 'New Object Database':
		  case 'Edit Database Structure':
		       $output['cmd'] = $client['cmd'];
		       $query = $db->prepare("INSERT INTO `$$$` (id,client) VALUES (:id,:client)");
		       $query->execute([':id' => $output['data'] = GenerateRandomString(), ':client' => json_encode($client)]);
		       break;
		  case 'INIT':
		       Check($db, CHECK_OID, $client, $output);
		  case 'DELETEOBJECT':
			Check($db, CHECK_EID, $client, $output);
		  case 'DBLCLICK':
		  case 'KEYPRESS':
		  case 'INS':
		  case 'DEL':
		  case 'F2':
		  case 'F12':
		  case 'CONFIRM':
		  case 'CONFIRMDIALOG':
		       // wrapper <uid> <start time> <ODid> <OVid> <object id> <element id> <event> <ip> <client json>
		       exec(WRAPPERBINARY." '$client[uid]' ".strval($now)." '$client[ODid]' '$client[OVid]' '$client[oId]' '$client[eId]' '$client[cmd]' '$client[ip]' '".json_encode($client, JSON_HEX_APOS | JSON_HEX_QUOT)."' >/dev/null &");
		       break;
		  case 'Task Manager':
		       exec(PHPBINARY." taskmanager.php '".json_encode($client, JSON_HEX_APOS | JSON_HEX_QUOT)."' >/dev/null &");
		       break;
		  default:
		       $output['log'] = $output['alert'] = "Controller report: unknown event '$client[cmd]' from client $client[ip] and user '$client[auth]'!";
		 }
	     }
	 catch (PDOException $e)
	     {
	      if (preg_match("/SQL server has gone away/", $msg = $e->getMessage()) === 1)
	         {
		  foreach ($clientsarray as $key => $value) if (isset($clientsarray[$key]['auth'])) cutKeys($clientsarray[$key], ['ip', 'port', 'User-Agent']);
		  include 'connect.php';
		 }
    	      $output['log'] = $output['alert'] = "Controller error: $msg!";
	     }
	 
	 // Write output result to the client socket
	 if ($output != ['cmd' => ''] && isset($socketarray[$cid]))
	    {
	     if (isset($output['log'])) LogMessage($db, $client, $output['log']);
	     if (isset($output['error'])) $client['ODid'] = $client['OVid'] = $client['OD'] = $client['OV'] = '';
	     if (isset($client['auth'])) $output['auth'] = $client['auth'];
	     fwrite($socket, encode(json_encode($output)));
	    }
	}

 try {
 // Process queue events from sql table `$$`
 $query = $db->prepare("SELECT * FROM `$$`");
 $query->execute();
 foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $value)
	 {
	  $handler = json_decode($value['client'], true);
	  isset($handler['cid']) ? $hid = $handler['cid'] : $hid = -1;
	  
	  if (isset($handler['passchange'])) foreach ($socketarray as $cid => $sock)
	  if (isset($clientsarray[$cid]['uid']) && $clientsarray[$cid]['uid'] === $handler['passchange'])
	     {
	      fwrite($sock, encode(json_encode(['cmd' => 'DIALOG', 'data' => getLoginDialogData(), 'sidebar' => [], 'auth' => '', 'error' => ''])));
	      cutKeys($clientsarray[$cid], ['ip', 'port', 'User-Agent']);
	     }
	  unset($handler['passchange']);
	  
	  switch ($handler['cmd'])
	         {
		  case 'DIALOG':
		  case 'UPDATEDIALOG':
		  case 'EDIT':
		  case '':
		       if (isset($socketarray[$hid]) && $clientsarray[$hid]['ODid'] === $handler['ODid'] && $clientsarray[$hid]['OVid'] === $handler['OVid'] && ($handler['ODid'] === '' || $clientsarray[$hid]['params'] === $handler['params']))
			  fwrite($socketarray[$hid], encode(json_encode($handler)));
		       break;
		  case 'SET':
		       if (isset($handler['alert'])) $alert = $handler['alert']; else unset($alert);
		       unset($handler['alert']);
		       
		       foreach ($socketarray as $cid => $sock)
		    	    if (isset($clientsarray[$cid]['auth']) && $clientsarray[$cid]['ODid'] === $handler['ODid'] && $clientsarray[$cid]['OVid'] === $handler['OVid'] && $clientsarray[$cid]['params'] === $handler['params'])
			    if (isset($alert) && $cid === $hid) fwrite($sock, encode(json_encode($handler + ['alert' => $alert])));
			     else fwrite($sock, encode(json_encode($handler)));
			     
		       break;
	    	  case 'INIT':
	    	  case 'DELETEOBJECT':
		       if (isset($handler['alert'])) $alert = $handler['alert']; else unset($alert);
		       unset($handler['alert']);

		       foreach ($socketarray as $cid => $sock)
			    if (isset($clientsarray[$cid]['auth']) && $clientsarray[$cid]['ODid'] === $handler['ODid'] && $clientsarray[$cid]['OVid'] === $handler['OVid'] && $clientsarray[$cid]['params'] === $handler['params'])
			    if (isset($alert) && $cid === $hid) MakeViewCall($db, $sock, $clientsarray[$cid], $handler + ['alert' => $alert]);
			     else MakeViewCall($db, $sock, $clientsarray[$cid], $handler);
			     
		       break;
	    	  case 'CALL':
		       if (Check($db, CHECK_OD_OV, $handler, $output)) MakeViewCall($db, $socketarray[$hid], $clientsarray[$hid], $handler);
		       break;
		 }
	  $query = $db->prepare("DELETE FROM `$$` WHERE id=$value[id]");
	  $query->execute();
	 }
 }
 catch (PDOException $e)
 {
  if (preg_match("/SQL server has gone away/", $msg = $e->getMessage()) === 1)
     {
      foreach ($clientsarray as $key => $value) if (isset($clientsarray[$key]['auth'])) cutKeys($clientsarray[$key], ['ip', 'port', 'User-Agent']);
      include 'connect.php';
     }
  $client = [];
  LogMessage($db, $client, $msg);
 }
}
