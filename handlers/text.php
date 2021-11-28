<?php

function lg($arg)
{
 file_put_contents('/usr/local/src/tabels/error.log', var_export($arg, true), FILE_APPEND);
}

//sleep(23);

if (isset($_SERVER['argv'][1])) switch($_SERVER['argv'][1])
   {
    case 'DBLCLICK':
	 if (!isset($_SERVER['argv'][2]) || gettype($data = json_decode($_SERVER['argv'][2], true)) !== 'array' || (!$data['altkey'] && !$data['shiftkey'] && !$data['ctrlkey']))
	    {
	     echo json_encode(['cmd' => 'EDIT']);
	     break;
	    }
	  else
	    {
	     if ($data['shiftkey']) echo json_encode(['cmd' => 'UPLOADDIALOG']);
	      else if ($data['ctrlkey']) echo json_encode(['cmd' => 'DOWNLOADDIALOG']);
	      else if ($data['altkey']) echo json_encode(['cmd' => 'UNLOADDIALOG']);
	     break;
	    }
    case 'EDIT':
	 $out = ['cmd' => 'EDIT'];
	 if (isset($_SERVER['argv'][2]))
	 if (gettype($data = json_decode($_SERVER['argv'][2], true)) === 'array')
	    {
	     if ($data['altkey'] || $data['ctrlkey'] || $data['metakey']) break;
	     $out += ['data' => $data['string']];
	    }
	  else
	    {
	     $out += ['data' => $_SERVER['argv'][2]];
	    }
	 echo json_encode($out);
	 break;
    case 'SET':
    case 'SETTEXT':
	 $string = '';
	 foreach ($_SERVER['argv'] as $key => $value) if ($key !== 0 && $key !== 1) $string .= $value;
	 echo json_encode(['cmd' => 'SET', 'value' => str_ireplace('<br>', "\n", $string)]);
	 break;
    case 'SETPROP':
	 if (($len = count($_SERVER['argv'])) < 3) break;
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
    case 'SELECT':
	 if (!isset($_SERVER['argv'][2])) break;
	 echo json_encode(['cmd' => 'DIALOG',
			   'data' => ['title' => 'Element value',
				      'dialog' => ['pad' => ['profile' => ['element' => ['head' => "\nSelect value:", 'type' => 'select-one', 'line' => '', 'data' => $_SERVER['argv'][2]]]]],
				      'buttons' => ['SAVE' => ['value' => 'SAVE', 'call' => '', 'enterkey' => ''],
						    'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']],
				      'flags'  => ['style' => 'width: 500px; height: 300px;']]]);
	 break;
    case 'CONFIRMDIALOG':
	 if (!isset($_SERVER['argv'][2]) || gettype($arr = json_decode($_SERVER['argv'][2], true)) != 'array') break;
	 if (isset($arr['title']) && $arr['title'] === 'Element value') 
	    {
	     foreach (preg_split("/\|/", $arr['dialog']['pad']['profile']['element']['data']) as $value)
		     if ($value[0] === '+' && ($data = ['value' => substr($value, 1)])) break;
	    }
	  elseif (isset($arr['title']) && $arr['title'] === 'Element properties') 
	    {
	     $data = [];
	     foreach($arr['dialog']['pad']['profile'] as $key => $value) $data[$key] = $value['data'];
	    }
	 echo json_encode(['cmd' => 'SET'] + $data);
	 break;
   }
