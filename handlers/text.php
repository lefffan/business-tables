<?php

/*****************************************************************************************************************
Element handler JSON to the controller stored in $input variable:

    { 
     "cmd":		"EDIT[<LINES_NUM>]|DIALOG|ALERT"
     "data":		"<text data for EDIT|ALERT>|<json data for DIALOG>"
    }

    {
     "cmd":		"SET|RESET"
     "alert":		"<alert message>"
     "value":		"view cell data" 
     "image":		"image to display instead of value text"
     "link":		"" 
     "location":	"" 
     "hint":		"" 
     "fonts":		"" 
     "color":		"" 
     "background":	"" 
     "<other css>":	"" 
     "<any property>":	"<any value>"
    }

Controller JSON to the element handler should be put into $output variable:
    
    {
     "event":		"INIT|DBLCLICK|KEYPRESS|CONFIRM|ONCHANGE"
     "user":		"<username initiated the process>"
     "title":		"element title"
     "data":		"<key code for KEYPRESS>|<element value for CONFIRM or NEWOBJECT>|<dialog json data for CONFIRM>"
     "<any property>":	'<json {"OD": "", "oId": "", "eId": "", "property": ""}|<string>'
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