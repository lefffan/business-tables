<?php

if (!isset($_SERVER['argv'][1])) exit;
$event = $_SERVER['argv'][1];

switch($event)
      {
       case 'INIT':
    	    if (isset($_SERVER['argv'][2]))
	       json_encode(['cmd' => 'SET', 'value' => $_SERVER['argv'][2], '_description' => 'HUI', '_hint' => 'FUCK OFF!', '_style' => 'color: red;']);
	    break;
       case 'DBLCLICK':
	    echo json_encode(['cmd' => 'EDIT']);
	    break;
       case 'KEYPRESS':
    	    if (!isset($_SERVER['argv'][2]) || !($data = json_decode($_SERVER['argv'][2], true))) break;
	    switch ($data['code'])
		   {
		    case 46: // Delete key
		         echo json_encode(['cmd' => 'SET', 'value' => '']);
		         break;
		    case 113: // F2 key
		         echo json_encode(['cmd' => 'EDIT']);
		         break;
		    case 123: // F12 key
		         //echo json_encode(['cmd' => 'CALL', 'data' => ['OD'=>'Operations', 'OV'=>'Operations', 'Params'=>[':input_user'=>'root']]]);
		         echo json_encode(['cmd' => 'CALL', 'data' => ['OD'=>'Users', 'OV'=>'_qq', 'Params'=>[':input_user'=>'']]]);
		         break;
		    default:
		         echo json_encode(['cmd' => 'EDIT', 'data' => $data['string']]);
		   }
	    break;
       case 'CONFIRM':
	    if (!isset($_SERVER['argv'][2])) break;
	    echo json_encode(['cmd' => 'SET', 'value' => $_SERVER['argv'][2], '_alert' => 'WTF????']);
	    break;
      }
