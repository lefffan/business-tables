<?php

$input = json_decode($input, true);

if (isset($input['event'])) switch($input['event'])
   {
    case 'INIT':
	 $output = json_encode(['cmd' => 'SET', 'value' => $input['data'], '_description' => 'HUI', '_hint' => 'FUCK OFF!', '_style' => 'color: red;']);
	 break;
    case 'DBLCLICK':
	 $output = json_encode(['cmd' => 'EDIT']);
	 break;
    case 'KEYPRESS':
	 switch ($input['data']['code'])
		{
		 case 46: // Delete key
		      $output = json_encode(['cmd' => 'RESET', 'value' => '']);
		      break;
		 case 113: // F2 key
		      $output = json_encode(['cmd' => 'EDIT']);
		      break;
		 case 123: // F12 key
		      //$output = json_encode(['cmd' => 'CALL', 'data' => ['OD'=>'Operations', 'OV'=>'Operations', 'Params'=>[':input_user'=>'root']]]);
		      $output = json_encode(['cmd' => 'CALL', 'data' => ['OD'=>'Users', 'OV'=>'_qq', 'Params'=>[':input_user'=>'']]]);
		      break;
		 default:
		      $output = json_encode(['cmd' => 'EDIT', 'data' => $input['data']['string']]);
		}
	 break;
    case 'CONFIRM':
	 if (isset($input['data']))  $output = json_encode(['cmd' => 'SET', 'value' => $input['data'], '_alert' => 'WTF????']);
	 break;
   }
