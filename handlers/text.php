<?php

//sleep(3);
//require_once 'core.php';

if (!isset($_SERVER['argv'][1])) exit;
$event = $_SERVER['argv'][1];

switch($event)
      {
       case 'EDIT':
	    $out = ['cmd' => 'EDIT'];
	    if (isset($_SERVER['argv'][2]) && gettype($data = json_decode($_SERVER['argv'][2], true)) === 'array')
	    if ($data['altkey'] || $data['ctrlkey'] || $data['metakey']) $out = ['cmd' => '']; else $out['data'] = $data['string'];
	    echo json_encode($out);
	    break;
       case 'CALL':
	    echo json_encode(['cmd' => 'CALL', 'data' => ['OD'=>'Users', 'OV'=>'_qq', 'Params'=>[':input_user'=>'']]]);
	    break;
       case 'SET':
	    if (isset($_SERVER['argv'][3]) && ($data = json_decode($_SERVER['argv'][3], true)) && ($data['altkey'] || $data['ctrlkey'] || $data['metakey'] || $data['shiftkey']))
	       {
	        echo json_encode(['cmd' => '']);
	        break;
	       }
	    if (!isset($_SERVER['argv'][2]) || !$_SERVER['argv'][2]) 
	       {
		echo json_encode(['cmd' => 'SET', 'value' => '']);
		break;
	       }
	    if (!($arr = json_decode($_SERVER['argv'][2], true)) || gettype($arr) != 'array')
	       {
		echo json_encode(['cmd' => 'SET', 'value' => $_SERVER['argv'][2]]);
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
			      'data' => ['title' => 'Element properties',
					 'dialog' => ['pad' => ['profile' => $profile]],
				         'buttons' => ['SAVE' => ['value' => 'SAVE', 'call' => '', 'enterkey' => ''],
						       'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']],
				         'flags'  => ['style' => 'width: 500px; height: 500px;']]]);
	    break;
       case 'CONFIRMDIALOG':
	    if (!isset($_SERVER['argv'][2]) || gettype($arr = json_decode($_SERVER['argv'][2], true)) != 'array') break;
	    if (isset($arr['title']) && $arr['title'] === 'Element value') 
	       {
		foreach (preg_split("/\|/", $arr['dialog']['pad']['profile']['element']['data']) as $value) if ($value[0] === '+')
			{
			 $data = ['value' => substr($value, 1)];
			 break;
			}
	       }
	    else if (isset($arr['title']) && $arr['title'] === 'Element properties') 
	       {
	        $data = [];
	        foreach($arr['dialog']['pad']['profile'] as $key => $value) $data[$key] = $value['data'];
	       }
	    echo json_encode(['cmd' => 'SET'] + $data);
	    break;
       case 'SELECT':
	    if (!isset($_SERVER['argv'][2])) break;
	    echo json_encode(['cmd' => 'DIALOG',
			      'data' => ['title' => 'Element value',
					 'dialog' => ['pad' => ['profile' => ['element' => ['head' => "\nSelect value:", 'type' => 'select-one', 'line' => '', 'data' => $_SERVER['argv'][2]]]]],
				         'buttons' => ['SAVE' => ['value' => 'SAVE', 'call' => '', 'enterkey' => ''],
						       'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']],
				         'flags'  => ['style' => 'width: 500px; height: 300px;']]]);
	    break;
       case 'SEARCH':
	    echo json_encode(['cmd' => 'SET', 'value' => ['ODid'=>'2','OVid'=>'1','earchelements'=>'1','operator'=>"REGEXP 'hui'",'element'=>'1']]);
	    break;
      }
