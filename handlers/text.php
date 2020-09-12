<?php

/*****************************************************************************************************************
Element handler JSON to the controller stored in $output variable.

Cell content edit, dialog call and alert message (non string "data" property is ignored):
    { 
     "cmd":		"EDIT[<LINES_NUM>]|DIALOG|ALERT"
     "data":		"<text data for EDIT or ALERT>|<JSON for DIALOG>"
    }

Element data set and reset. 'SET' command sets defined properties, additionally 'RESET' removes all other.
"image" and "<any property>" unlike other properties may contain non string data types.
    {
     "cmd":		"SET|RESET"
     "alert":		"<alert message>"
     "value":		"visible cell data" 
     "image":		"image to display instead of value text"
     "link":		""
     "location":	""
     "hint":		""
     "description":	""
     "style":		""
     "<any property>":	""
    }

Controller JSON to the element handler is in $intput variable:
    
    {
     "event":		"INIT|DBLCLICK|KEYPRESS|CONFIRM|ONCHANGE"
     "user":		"<username initiated the process>"
     "title":		"element title"
     "data":		"<JSON (with 'string' and 'code' props) for KEYPRESS>|<element value (table cell innerHTML) for CONFIRM or NEWOBJECT>|<dialog JSON for CONFIRM>"
     "<any property>":	{ "eId": "", "property": ""}|<string>'
    }
*****************************************************************************************************************/

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
		      break;
		 default:
		      $output = json_encode(['cmd' => 'EDIT', 'data' => $input['data']['string']]);
		}
	 break;
    case 'CONFIRM':
	 if (isset($input['data']))  $output = json_encode(['cmd' => 'RESET', 'value' => $input['data'], '_alert' => 'WTF????']);
	 break;
   }
