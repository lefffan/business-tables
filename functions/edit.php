<?php

/*
  Controller commands to the element handler:
    {
     "cmd":            "NEWOBJECT|DBLCLICK|F2|F12|INS|DEL|KEYPRESS|CONFIRM|OBJCHANGE"
     "user":           "<username initiated the process>"
     "data":           "<key code for KEYPRESS>|<element value for CONFIRM or NEWOBJECT>|<dialog json data for CONFIRM>"
     "<any property>": "<string>|<[OD:oId:]eId:[property]>"		// eId with no property returns eId title
    }
*/

/*
  Element handler commands to the controller:
    {
     "cmd":		"NEWOD|NEWOV|NEWELEMENT"			// System commands
     "data":		"<OD|OV|ELEMENT name"
     ---------------------------------------      
     "cmd":		"EDIT[<LINES_NUM>]|DIALOG"			// Callback data commands
     "data":		"<text data for EDIT>|<json data for DIALOG>"
     "alert":		"<alert message>"
     "log":		"<browser console log message>"
     ---------------------------------------      
     "cmd":		"SET|RESET|INFO"				// Other commands
     "alert":		"<alert message>"
     "log":		"<browser console log message>"
     "callbackcmd":	"REFRESHMENU" 
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
*/

switch ($in[0])
       {
        case 'DBLCLICK':
	     $out = array('cmd' => 'EDIT');
	     break;
        case 'KEYPRESS':
	     $out = array('cmd' => 'EDIT', 'data' => $in['data']);
	     break;
        case 'F2':
	     $out = array('cmd' => 'EDIT');
	     break;
        case 'CONFIRM':
	     $out = array('cmd' => 'SET', 'data' => $in['data']);
	     loog(json_decode('{"value":"1", "data":"hui"}', true));
	     break;
        case 'NEWOBJECT':
	     $out = array('cmd' => 'SET', 'data' => $in['data']);
	     break;
        case 'DEL':
	     $out = array('cmd' => 'SET', 'data' => '');
	     break;
       }
