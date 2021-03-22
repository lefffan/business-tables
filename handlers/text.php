<?php

if (!isset($_SERVER['argv'][1])) exit;
$event = $_SERVER['argv'][1];

switch($event)
      {
       case 'INIT':
    	    if (isset($_SERVER['argv'][2])) echo json_encode(['cmd' => 'SET', 'value' => $_SERVER['argv'][2], '_description' => 'HUI', '_hint' => 'FUCK OFF!', '_style' => 'color: red;']);
	    break;
       case 'DBLCLICK':
	    echo json_encode(['cmd' => 'EDIT']);
	    break;
       case 'KEYPRESS':
    	    if (isset($_SERVER['argv'][2])) echo json_encode(['cmd' => 'EDIT', 'data' => $_SERVER['argv'][2]]);
	    break;
       case 'INS':
    	    if (isset($_SERVER['argv'][2])) $link = $_SERVER['argv'][2]; else $link = '';
	    if (isset($_SERVER['argv'][3])) $linkoid = $_SERVER['argv'][3]; else $linkoid = '';
	    if (isset($_SERVER['argv'][4])) $linkeid = $_SERVER['argv'][4]; else $linkeid = '';
	    echo json_encode(
			 ['cmd' => 'DIALOG',
			  'data' => ['title' => 'User properties',
				     'dialog' => ['pad' => ['profile' => ['element1' => ['type' => 'text', 'head' => "\nLink type:", 'data' => $link, 'line' => ''],
				    					  'element2' => ['type' => 'text', 'head' => 'Remote object selection:', 'data' => $linkoid, 'line' => ''],
									  'element3' => ['type' => 'text', 'head' => 'Remote object element selection:', 'data' => $linkeid, 'line' => ''],
									 ]]],
				     'buttons' => ['SAVE' => ['value' => 'SAVE', 'call' => '', 'enterkey' => ''], 'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']],
				     'flags'  => ['style' => 'width: 500px; height: 500px;']]]);
	    
	    break;
       case 'DEL':
    	    echo json_encode(['cmd' => 'SET', 'value' => '']);
	    break;
       case 'F2':
	    echo json_encode(['cmd' => 'EDIT']);
	    break;
       case 'F12':
    	    //echo json_encode(['cmd' => 'CALL', 'data' => ['OD'=>'Operations', 'OV'=>'Operations', 'Params'=>[':input_user'=>'root']]]);
	    echo json_encode(['cmd' => 'CALL', 'data' => ['OD'=>'Users', 'OV'=>'_qq', 'Params'=>[':input_user'=>'']]]);
	    break;
       case 'CONFIRM':
	    if (!isset($_SERVER['argv'][2]) || gettype($_SERVER['argv'][2]) != 'string') break;
	    echo json_encode(['cmd' => 'SET', 'value' => $_SERVER['argv'][2], '_alert' => 'WTF????']);
	    break;
       case 'CONFIRMDIALOG':
    	    if (!isset($_SERVER['argv'][2]) || gettype($data = json_decode($_SERVER['argv'][2], true)) != 'array') break;
	    echo json_encode(['cmd' => 'SET', 
			      'link' => $data['dialog']['pad']['profile']['element1']['data'],
			      'linkoid' => $data['dialog']['pad']['profile']['element2']['data'],
			      'linkeid' => $data['dialog']['pad']['profile']['element3']['data']]);
	    break;
      }
