<?php

//function lg($arg) { file_put_contents('/usr/local/src/tabels/error.log', var_export($arg, true), FILE_APPEND); }
//sleep(10);

if (!isset($_SERVER['argv'][1])) $_SERVER['argv'][1] = '';

switch ($_SERVER['argv'][1])
       {
	case 'UPLOADDIALOG': // File management - upload files from client
	case 'DOWNLOADDIALOG': // File management - download files from server to client
	case 'UNLOADDIALOG': // File management - same as DOWNLOADDIALOG, but file deletion option is added
	case 'GALLERY': // Gallery mode for an element attached images
	     echo json_encode(['cmd' => $_SERVER['argv'][1]]);
	     break;

	case 'SET': // Set content concatenated from specified command line args, no args - set empty content (clear cell text)
	     $out = ['cmd' => 'SET', 'value' => ''];
	     foreach ($_SERVER['argv'] as $key => $value) if ($key > 1) $out['value'] .= $value;
	     $out['value'] = str_ireplace('<br>', "\n", $out['value']);
	     echo json_encode($out);
	     break;

	case 'EDIT': // Edit content concatenated from specified command line args, no args - edit current cell content
	     $out = ['cmd' => 'EDIT'];
	     foreach ($_SERVER['argv'] as $key => $value) if ($key > 1) isset($out['data']) ? $out['data'] .= $value : $out['data'] = $value;
	     echo json_encode($out);
	     break;

	case 'CALL': // Call current database view in 2nd arg ($_SERVER['argv'][2]) with 3rd arg ($_SERVER['argv'][3]) as id parameter for the object selection string. Example views: 'Tree Up', 'Tree Down', 'Map Tree Up', 'Map Tree Down', '_History'
	     if (!isset($_SERVER['argv'][2]) || !isset($_SERVER['argv'][3])) echo json_encode(['cmd' => '']);
	      else echo json_encode(['cmd' => 'CALL', 'OV' => $_SERVER['argv'][2], 'params' => [':id'=>$_SERVER['argv'][3]]]);
	     break;

	case 'SETPROP': // Set element properties with property name as a 2nd arg, property initial value as 3rd arg and so on..
	     if (($len = count($_SERVER['argv'])) < 3)
		{
		 echo json_encode(['cmd' => '']);
		 break;
		}
	     $profile = [];
	     $margin = "\n";
	     for ($i = 2; $i < $len; $i += 2)
		 {
		  $prop = $_SERVER['argv'][$i];
		  $value = isset($_SERVER['argv'][$i + 1]) ? $_SERVER['argv'][$i + 1] : '';
		  $profile[$prop] = ['type' => 'textarea', 'head' => $margin."Enter element '$prop' property value:", 'data' => $value, 'line' => ''];
		  $margin = '';
		 }
	     echo json_encode(['cmd' => 'DIALOG',
			       'data' => ['title' => 'Element properties',
			       'dialog' => ['pad' => ['profile' => $profile]],
			       'buttons' => ['SAVE' => ['value' => 'SAVE', 'call' => '', 'enterkey' => ''],
					     'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']],
			       'flags'  => ['style' => 'width: 500px; height: 500px;']]]);
	     break;

	case 'SELECT': // Set one of predefined element values divided via '|', see json dialog box format
	     if (!isset($_SERVER['argv'][2]))
		{
		 echo json_encode(['cmd' => '']);
		 break;
		}
	     echo json_encode(['cmd' => 'DIALOG',
			       'data' => ['title' => 'Element value',
			       'dialog' => ['pad' => ['profile' => ['element' => ['head' => "\nSelect value:", 'type' => 'select-one', 'line' => '', 'data' => $_SERVER['argv'][2]]]]],
			       'buttons' => ['SAVE' => ['value' => 'SAVE', 'call' => '', 'enterkey' => ''],
					     'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']],
			       'flags'  => ['style' => 'width: 500px; height: 300px;']]]);
	     break;

	case 'CONFIRMDIALOG': // Confirm callback dialog data from element props set and element predefined values selection
	     if (!isset($_SERVER['argv'][2]) || gettype($arr = json_decode($_SERVER['argv'][2], true)) != 'array')
		{
		 echo json_encode(['cmd' => '']);
		 break;
		}
	     if (isset($arr['title']) && $arr['title'] === 'Element value') 
		{
	         foreach (preg_split("/\|/", $arr['dialog']['pad']['profile']['element']['data']) as $value)
			 if ($value[0] === '+' && ($data = ['value' => substr($value, 1)])) break;
		 echo json_encode(['cmd' => 'SET'] + $data);
		 break;
		}
	     if (isset($arr['title']) && $arr['title'] === 'Element properties') 
		{
		 $data = [];
		 foreach($arr['dialog']['pad']['profile'] as $key => $value) $data[$key] = $value['data'];
		 echo json_encode(['cmd' => 'SET'] + $data);
		 break;
		}
	     echo json_encode(['cmd' => '']);
	     break;

	default:
	     echo json_encode(['cmd' => '']);
       }
