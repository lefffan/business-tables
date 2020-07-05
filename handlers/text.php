<?php

/*****************************************************************************************************************
Element handler JSON to the controller stored in $output variable.

Content edit, dialog call and alert message:
    { 
     "cmd":		"EDIT[<LINES_NUM>]|DIALOG|ALERT"
     "data":		"<text data for EDIT or ALERT>|<JSON for DIALOG>"
    }

Element data set and reset. RESET command sets defined properties and removes all other:
    {
     "cmd":		"SET|RESET"
     "alert":		"<alert message>"
     "value":		"visible cell data" 
     "image":		"image to display instead of value text"
     "link":		""
     "location":	""
     "hint":		""
     "fonts":		""
     "color":		""
     "background":	"" 
     "<other css>":	"" 
     "<any property>":	""
    }

Controller JSON to the element handler is in $intput variable:
    
    {
     "event":		"INIT|DBLCLICK|KEYPRESS|CONFIRM|ONCHANGE"
     "user":		"<username initiated the process>"
     "title":		"element title"
     "data":		"<key code or pasted data for KEYPRESS>|<element value (table cell innerHTML) for CONFIRM or NEWOBJECT>|<dialog JSON for CONFIRM>"
     "<any property>":	{ "OD": "", "oId": "", "eId": "", "property": ""}|<string>'
    }
*****************************************************************************************************************/

$input = json_decode($input, true);

if (isset($input['event'])) switch($input['event'])
   {
    case 'INIT':
	 $output = json_encode(['cmd' => 'SET', 'value' => $input['data'].'hui', 'password' => 'HUI']);
	 break;
    case 'DBLCLICK':
	 $output = json_encode(['cmd' => 'EDIT']);
	 break;
    case 'CONFIRM':
	 if (isset($input['data']))  $output = json_encode(['cmd' => 'RESET', 'value' => $input['data']]);
	 break;
   }