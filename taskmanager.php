<?php

require_once 'core.php';

// Init vars
$bgcolor = 'rgb(172,189,172);';
$bgcolorkill = 'rgb(217,174,174);';
$font = 'bold';
$now = strtotime("now");
$output  = [];
$table  = [];
$table['line0']   = [
	      'PID' => ['value' => 'PID', 'style' => "background-color: $bgcolor font-weight: $font", 'call' => ''], 
	      'Handler' => ['value' => 'Handler', 'style' => "background-color: $bgcolor font-weight: $font", 'call' => ''],
	      'Exe time' => ['value' => 'Exe time', 'style' => "background-color: $bgcolor font-weight: $font", 'call' => ''],
	      'Initiator' => ['value' => 'Initiator', 'style' => "background-color: $bgcolor font-weight: $font", 'call' => ''],
	      'Ip' => ['value' => 'Ip', 'style' => "background-color: $bgcolor font-weight: $font", 'call' => ''],
	      'Event' => ['value' => 'Event', 'style' => "background-color: $bgcolor font-weight: $font", 'call' => ''],
	      'Database' => ['value' => 'Database', 'style' => "background-color: $bgcolor font-weight: $font", 'call' => ''],
	      'View' => ['value' => 'View', 'style' => "background-color: $bgcolor font-weight: $font", 'call' => ''],
	      'OId' => ['value' => 'OId', 'style' => "background-color: $bgcolor font-weight: $font", 'call' => ''],
	      'EId' => ['value' => 'EId', 'style' => "background-color: $bgcolor font-weight: $font", 'call' => ''],
	      'Kill' => ['value' => 'Kill', 'style' => "background-color: $bgcolorkill font-weight: $font"],
	     ];

// Parse client data
$client  = json_decode($_SERVER['argv'][1], true);

// Parse pid number to kill appropriate process id
if (isset($client['data']['flags']['event']) && intval($pid = $client['data']['flags']['event']) > 0) exec(KILLWRAPPERPROCESSESCMD.' '.strval($pid));

// Get current sort column from dialog data, otherwise use deault value
$sort = 'PID*'; // Default
$cmd = 'DIALOG';
if (isset($client['data']['dialog']['pad']['profile']['element2']['data'])) // Incoming dialog data does exist (non first task manager call)?
   {
    foreach (json_decode($client['data']['dialog']['pad']['profile']['element2']['data'], true)['line0'] as $value) // Get table header ('line0' row) to parse column to sort on
	    if (!(strpos($value['value'], '*') === false)) { $sort = $value['value']; break; } // Sort on column with char '*' present
    $cmd = 'UPDATEDIALOG';
   }

// Process sort column event if exist
if (isset($pid) && array_search($pid, ['PID', 'Handler', 'Exe time', 'Initiator', 'Ip', 'Event', 'Database', 'View', 'OId', 'EId']) !== false)
if (str_replace('*', '', $sort) === $pid) $sort[0] === '*' ? $sort = substr($sort.'*', 1) : $sort = substr('*'.$sort, 0, -1);
 else $sort = $pid.'*';
 
// Mark sort column value by '*' char
$table['line0'][str_replace('*', '', $sort)]['value'] = $sort;
$sort[0] === '*' ? $desc = true : $desc = false;
$sort = str_replace('*', '', $sort);

// Get wrapper proceesses with next args: <PID> <wrapper> <uid> <start time> <ODid> <OVid> <object id> <element id> <event> <ip> <client json>
exec(WRAPPERPROCESSESCMD, $output);

$i = 0;
foreach ($output as $value)
	{
	 $process = explode(' ', trim($value));
	 if (($start = array_search('wrapper.php', $process)) === false) continue;
	 if (!isset($process[$start + 7]) || ($eventid = array_search($process[$start + 7], ['INIT', 'DBLCLICK', 'KEYPRESS', 'INS', 'DEL', 'F2', 'F12', 'CONFIRM', 'CONFIRMDIALOG', 'CHANGE', 'SCHEDULE'])) === false) continue;
	 $i++;
	 try {
	      // Calc user name
	      $user = getUserName($db, $process[$start + 1]);
	      // Get OD props to calc view/handler name
	      $view = $handler = '';
	      $query = $db->prepare("SELECT odname,JSON_EXTRACT(odprops, '$.dialog.View'),JSON_EXTRACT(odprops, '$.dialog.Element') FROM $ WHERE id=:id");
	      $query->execute(['id' => $process[$start + 3]]);
	      if (count($arr = $query->fetchAll(PDO::FETCH_NUM)) == 0) throw new Exception("Task manager: incorrect OD (id".$process[$start + 3].") props!");;
	      // Calc view name 
	      foreach (json_decode($arr[0][1], true) as $valeu)
	           if ($valeu['element1']['id'] === $process[$start + 4])
		      {
		       $view = $valeu['element1']['data'];
		       break;
		      }
	      // Calc handler name 
	      if ($process[$start + 7] === 'SCHEDULE') $handler = '_SCHEDULER_'; else
	      foreach (json_decode($arr[0][2], true) as $valeu)
	           if ($valeu['element1']['id'] === $process[$start + 6])
		      {
		       $handler = $valeu['element'.strval($eventid + 4)]['data'];
		       break;
		      }
	     }
	 catch (PDOException $e)
	     {
	      lg($e->getMessage());
	      exit;
	     }
	 $table["line$i"]  = [
	      		      "PID" => ['value' => $process[0]],
	      		      "Handler" => ['value' => $handler],		// Calc by event and eid
	      		      "Exe time" => ['value' => strval($now - intval($process[$start + 2]))],
	      		      "Initiator" => ['value' => $user],		// Calc by uid
	      		      "Ip" => ['value' => $process[$start + 8]],
	      		      "Event" => ['value' => $process[$start + 7]],
	      		      "Database" => ['value' => $arr[0][0]],		// Calc by ODid
	      		      "View" => ['value' => $view],			// Calc by OVid
	      		      "OId" => ['value' => $process[$start + 5]],
	      		      "EId" => ['value' => $process[$start + 6]],
			      " $process[0]" => ['value' => 'X', 'call' => ''], //"Kill$i" => ['value' => 'X'],
			     ];
	 // Sort table by putting new line to the previous position if needed
	 for($key = $i; $key > 1; $key --)
	    if (($table["line".strval($key)][$sort]['value'] > $table["line".strval($key - 1)][$sort]['value'] && $desc) ||
		($table["line".strval($key)][$sort]['value'] < $table["line".strval($key - 1)][$sort]['value'] && !$desc))
		Swap($table["line".strval($key)], $table["line".strval($key - 1)]);
	     else break;
	}

$dialog  = ['title'  => 'Task Manager',
	    'dialog' => ['pad' => ['profile' =>
			['element1' => ['head'=>''],
			 'element2' => ['type' => 'table', 'head' => '', 'data' => json_encode($table)]]]],
	    //'buttons'=> ['REFRESH' => ['value' => 'REFRESH', 'call' => 'Task Manager', 'interactive' => '', 'timer' => '500'], 'EXIT' => ['value' => 'EXIT', 'style' => 'background-color: red;', 'timer_' => '1500']],
	    'buttons'=> ['REFRESH' => ['value' => '', 'call' => 'Task Manager', 'interactive' => '', 'timer' => '1000'], 'EXIT' => ['value' => 'EXIT', 'style' => 'background-color: red;', 'timer_' => '1500']],
	    'flags'  => ['style' => 'width: 1000px; height: 500px;', 'esc' => '']];

if (count($table) < 2) $dialog['dialog']['pad']['profile']['element3'] = ['head'=>'                                                                                   No active tasks found..'];

try {
     $query = $db->prepare("INSERT INTO `$$` (client) VALUES (:client)");
     $query->execute([':client' => json_encode(['cmd' => $cmd, 'data' => $dialog] + CopyKeys($client, ['cid', 'uid', 'ODid', 'OVid', 'params']), JSON_HEX_APOS | JSON_HEX_QUOT)]);
    }
catch (PDOException $e)
    {
     lg($e->getMessage());
    }
