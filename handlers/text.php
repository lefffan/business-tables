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
	     if (!$data['altkey'] && $data['shiftkey'] && !$data['ctrlkey']) echo json_encode(['cmd' => 'UPLOADDIALOG']);
	      else if (!$data['altkey'] && !$data['shiftkey'] && $data['ctrlkey']) echo json_encode(['cmd' => 'DOWNLOADDIALOG']);
	      else if ($data['altkey'] && !$data['shiftkey'] && !$data['ctrlkey']) echo json_encode(['cmd' => 'UNLOADDIALOG']);
	      else if ($data['altkey'] && !$data['shiftkey'] && $data['ctrlkey']) echo json_encode(['cmd' => 'CALL', 'OV' => '_History', 'ODid' => '1', '' => '', ]);
	     break;
	    }
    case 'HUI':
	 echo json_encode(['cmd' => 'CALL', 'OV' => '_History', 'params' => [':id'=>$_SERVER['argv'][2]]]);
	 break;
    case 'GALLERY':
	 echo json_encode(['cmd' => 'GALLERY']);
	 break;
    case 'EDIT':
	 if (!isset($_SERVER['argv'][2]))
	    {
	     echo json_encode(['cmd' => 'EDIT']);
	     break;
	    }
	 $out = ['cmd' => 'EDIT', 'data' => ''];
	 foreach ($_SERVER['argv'] as $key => $value) if ($key > 1)
		 if (gettype($data = json_decode($value, true)) === 'array')
		    {
		     if ($data['altkey'] || $data['ctrlkey'] || $data['metakey'])
			{
			 $out = ['cmd' => ''];
			 break;
			}
		     $out['data'] .= $data['string'];
		    }
		  else
		    {
		     $out['data'] .= $value;
		    }
	 if (isset($out['data'])) $out['data'] = str_ireplace('<br>', "\n", $out['data']);
	 echo json_encode($out);
	 break;
	 //
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
    case 'SETa':
    case 'SETTEXTa':
	 $out = ['cmd' => 'EDIT', 'value' => ''];
	 foreach ($_SERVER['argv'] as $key => $value) if ($key > 1)
		 if (gettype($data = json_decode($value, true)) === 'array')
		    {
		     if ($data['altkey'] || $data['ctrlkey'] || $data['metakey']) continue;
		     $out['value'] .= $data['string'];
		    }
		  else
		    {
		     $out['value'] .= $value;
		    }
	 $out['value'] = str_ireplace('<br>', "\n", $out['value']);
	 echo json_encode($out);
	 break;
	 //
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
