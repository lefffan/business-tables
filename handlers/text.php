<?php

//sleep(13);
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
    	    if (!isset($_SERVER['argv'][2]) || !($data = json_decode($_SERVER['argv'][2], true))) break;
	    if ($data['altkey'] || $data['ctrlkey'] || $data['metakey'])
	       {
	        echo json_encode(['cmd' => '']);
	        break;
	       }

	    echo json_encode(['cmd' => 'EDIT', 'data' => $data['string']]);
	    break;
       case 'INS':
    	    if (!isset($_SERVER['argv'][2], $_SERVER['argv'][3]) || !($arr = json_decode($_SERVER['argv'][2], true)) || !($data = json_decode($_SERVER['argv'][3], true))) break;
	    if ($data['altkey'] || $data['ctrlkey'] || $data['metakey'] || $data['shiftkey'])
	       {
	        echo json_encode(['cmd' => '']);
	        break;
	       }

	    $profile = [];
	    $margin = "\n";
	    foreach($arr as $key => $value)
		   {
		    if (!isset($value)) $arr[$key] = '';
		    $profile[$key] = ['type' => 'text', 'head' => $margin."Enter element '$key' property value:", 'data' => $arr[$key], 'line' => ''];
		    $margin = '';
		   }
	    echo json_encode(['cmd' => 'DIALOG',
			      'data' => ['title' => 'User properties',
					 'dialog' => ['pad' => ['profile' => $profile]],
				         'buttons' => ['SAVE' => ['value' => 'SAVE', 'call' => '', 'enterkey' => ''],
						       'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']],
				         'flags'  => ['style' => 'width: 500px; height: 500px;']]]);
	    
	    break;
       case 'DEL':
    	    echo json_encode(['cmd' => 'SET', 'value' => '']);
	    break;
       case 'SCHEDULE':
    	    echo "PIZ";
	    break;
       case 'F2':
    	    if (!isset($_SERVER['argv'][2]) || !($data = json_decode($_SERVER['argv'][2], true))) break;
	    if ($data['altkey'] || $data['ctrlkey'] || $data['metakey'] || $data['shiftkey'])
	       {
	        echo json_encode(['cmd' => '']);
	        break;
	       }
	       
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
    	    if (!isset($_SERVER['argv'][2]) || gettype($arr = json_decode($_SERVER['argv'][2], true)) != 'array') break;
	    $data = [];
	    if (isset($arr['dialog']['pad']['profile'])) 
	       foreach($arr['dialog']['pad']['profile'] as $key => $value) $data[$key] = $value['data'];
	    echo json_encode(['cmd' => 'SET'] + $data);
	    break;
      }
