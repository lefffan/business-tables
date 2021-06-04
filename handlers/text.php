<?php

//sleep(3);
if (!isset($_SERVER['argv'][1])) exit;
$event = $_SERVER['argv'][1];

switch($event)
      {
       case 'EDIT':
	    $out = ['cmd' => 'EDIT'];
	    if (isset($_SERVER['argv'][2]) && ($data = json_decode($_SERVER['argv'][2], true)))
	    if ($data['altkey'] || $data['ctrlkey'] || $data['metakey']) $out = ['cmd' => '']; else $out['data'] = $data['string'];
	    echo json_encode($out);
	    break;
       case 'CALL':
	    echo json_encode(['cmd' => 'CALL', 'data' => ['OD'=>'Users', 'OV'=>'_qq', 'Params'=>[':input_user'=>'']]]);
	    break;
       case 'SET':
	    if (isset($_SERVER['argv'][3]) && ($data = json_decode($_SERVER['argv'][3], true)) && ($data['altkey'] || $data['ctrlkey'] || $data['metakey'] || $data['shiftkey']))
	    if (!isset($_SERVER['argv'][2]) || !$_SERVER['argv'][2]) 
	       {
		echo json_encode(['cmd' => 'SET', 'value' => '']);
		break;
	       }





echo $_SERVER['argv'][2]; else 
	    break;
       case 'DIALOG':
	    if (!isset($_SERVER['argv'][2]) || !($arr = json_decode($_SERVER['argv'][2], true)) || )
	       {
	        echo json_encode(['cmd' => '']);
	        break;
	       }
	    $profile = [];
	    $margin = "\n";
	    foreach($arr as $key => $value)
		   {
		    if (!isset($value) || gettype($value) != 'string') $arr[$key] = '';
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
       case 'CONFIRMDIALOG':
    	    if (!isset($_SERVER['argv'][2]) || gettype($arr = json_decode($_SERVER['argv'][2], true)) != 'array') break;
	    $data = [];
	    if (isset($arr['dialog']['pad']['profile'])) 
	       foreach($arr['dialog']['pad']['profile'] as $key => $value) $data[$key] = $value['data'];
	    echo json_encode(['cmd' => 'SET'] + $data);
	    break;
      }
