<?php

/*****************************************************************************************************************
Element handler JSON to the controller stored in $output variable.

Cell content edit, dialog call and alert message (non string "data" property is ignored):
    { 
     "cmd":		"EDIT[<LINES_NUM>]|DIALOG|ALERT"
     "data":		"<text data for EDIT or ALERT>|<JSON for DIALOG>"
    }

User handler specified OD/OV call, in case of absent OD/OV - current values are used:
    { 
     "cmd":		"CALL"
     "data":		JSON with "OD", "OV" and "Params" properties.
    }
"Params" value is a JSON with object selection args list (as a properties) with its values, see appropriate section help.
Absent args will be requested via dialog box.
 
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
     "data":		"<key code or pasted data for KEYPRESS>|<element value (table cell innerHTML) for CONFIRM or NEWOBJECT>|<dialog JSON for CONFIRM>"
     "<any property>":	{ "eId": "", "property": ""}|<string>'
    }
*****************************************************************************************************************/

require_once 'customizationjson.php';

if (!isset($_SERVER['argv'][1])) exit;
$event = $_SERVER['argv'][1];

switch ($event)
       {
	case 'INIT':
	     echo json_encode(['cmd' => 'RESET', 'value' => 'User customization', 'dialog' => defaultCustomizationDialogJSON()]);
	     break;
	case 'DBLCLICK':
	     if (!isset($_SERVER['argv'][2]) || !($data = json_decode($_SERVER['argv'][2], true)))
		{
	         echo json_encode(['cmd' => 'ALERT', 'data' => "You can't change system account customization!"]);
	         break;
		}
	     $dialog = ['title' => 'User customization',
			'dialog' => $data,
			'buttons' => ['SAVE' => ' ', 'CANCEL' => 'background-color: red;'],
			'flags'  => ['style' => 'width: 600px; height: 600px;', 'esc' => '', 'padprofilehead' => ['pad' => "\n\nSelect customization"]]];

	     echo json_encode(['cmd' => 'DIALOG', 'data' => $dialog]);
	     break;
	case 'CONFIRM':
	     if (!isset($_SERVER['argv'][2]) || !($data = json_decode($_SERVER['argv'][2], true))) break;
	     echo json_encode(['cmd' => 'SET', 'dialog' => json_encode($data['dialog'])]);
	     break;
       }
