<?php

/*****************************************************************************************************************
Element handler JSON to the controller stored in $output variable.

Content edit, dialog call and alert message (non string "data" property is ignored):
    { 
     "cmd":		"EDIT[<LINES_NUM>]|DIALOG|ALERT"
     "data":		"<text data for EDIT or ALERT>|<JSON for DIALOG>"
    }

Element data set and reset. 'SET' command sets defined properties, additionally 'RESET' removes all other.
"image" and "<any property>" unlike other properties may consist of non string data types.
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
     "<any property>":	{ "eId": "", "property": ""}|<string>'
    }
*****************************************************************************************************************/

$input = json_decode($input, true);

if (isset($input['event'])) switch($input['event'])
   {
    case 'INIT':
	 $output = json_encode(['cmd' => 'SET', 'value' => $input['data'].'hui', 'description' => 'HUI', 'hint' => 'FUCK OFF!']);
	 sleep(10);
	 break;
    case 'DBLCLICK':
	 $output = json_encode(['cmd' => 'EDIT']);
	 break;
    case 'KEYPRESS':
	 $output = json_encode(['cmd' => 'EDIT', 'data' => $input['data']]);
	 break;
    case 'CONFIRM':
	 if (isset($input['data']))  $output = json_encode(['cmd' => 'SET', 'value' => $input['data']]);
	 break;
   }
