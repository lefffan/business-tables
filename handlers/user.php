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

$input = json_decode($input, true);

if (isset($input['event'])) switch($input['event'])
   {
    case 'INIT':
	 $output = json_encode(['cmd' => 'SET', 'value' => str_replace("\\", "", $input['data']), '_style' => 'color: green;', 'odaddperm' => '+Allow user to add Object Databases|', 'groups' => '', 'password' => '']);
	 break;
    case 'DBLCLICK':
	 if ($input['account'] == 'system') $output = json_encode(['cmd' => 'ALERT', 'data' => "You can't change system account properties!"]);
	  else if ($input['account'] == '') $output = json_encode(['cmd' => 'DIALOG', 'data' => ['title' => 'User properties', 'dialog' => ['pad' => ['profile' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'User:', 'data' => $input['account'], 'line' => ''], 'element2' => ['type' => 'password', 'head' => 'Password:', 'data' => '', 'line' => ''], 'element3' => ['type' => 'password', 'head' => 'Confirm password:', 'data' => '', 'line' => ''], 'element4' => ['type' => 'checkbox', 'data' => $input['odaddperm'], 'line' => ''], 'element5' => ['type' => 'textarea', 'head' => 'One by line group list the user is a member of:', 'data' => $input['groups'], 'line' => '']]]], 'buttons' => ['SAVE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['style' => 'width: 500px; height: 500px;', 'esc' => '']]]);
	  else if ($input['user'] != $input['account']) $output = json_encode(['cmd' => 'DIALOG', 'data' => ['title' => 'User properties', 'dialog' => ['pad' => ['profile' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'User:', 'data' => $input['account'], 'line' => '', 'readonly' => ''], 'element2' => ['type' => 'password', 'head' => 'Password:', 'data' => '', 'line' => ''], 'element3' => ['type' => 'password', 'head' => 'Confirm password:', 'data' => '', 'line' => ''], 'element4' => ['type' => 'checkbox', 'data' => $input['odaddperm'], 'line' => ''], 'element5' => ['type' => 'textarea', 'head' => 'One by line group list the user is a member of:', 'data' => $input['groups'], 'line' => '']]]], 'buttons' => ['SAVE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['style' => 'width: 500px; height: 500px;', 'esc' => '']]]);
	  else $output = json_encode(['cmd' => 'DIALOG', 'data' => ['title' => 'User properties', 'dialog' => ['pad' => ['profile' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'User:', 'data' => $input['account'], 'line' => '', 'readonly' => ''], 'element2' => ['type' => 'password', 'head' => 'Password:', 'data' => '', 'line' => ''], 'element3' => ['type' => 'password', 'head' => 'Confirm password:', 'data' => '', 'line' => '']]]], 'buttons' => ['SAVE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['style' => 'width: 500px; height: 500px;', 'esc' => '']]]);
	 break;
    case 'CONFIRM':
	 // Check dialog data to be correct
	 if (!isset($input['data']['dialog']['pad']['profile']['element1']['data'])) break;
	 $profile = $input['data']['dialog']['pad']['profile'];
	 $user = str_replace("\\", "", $profile['element1']['data']);
	 if (strlen($user) > USERSTRINGMAXCHAR) $user = substr($user, 0, USERSTRINGMAXCHAR);
	 
	 // Check the user to be not emtpy
	 if (!isset($profile['element1']['data']) || !$profile['element1']['data'])
	    {
	     $output = json_encode(['cmd' => 'ALERT', 'data' => 'Username cannot be empty!']);
	     break;
	    }
	    
	 // Password match check
	 if (!isset($profile['element2']['data']) || !isset($profile['element3']['data']) || $profile['element2']['data'] != $profile['element3']['data'])
	    {
	     $output = json_encode(['cmd' => 'ALERT', 'data' => "Confirm password doesn't match the password!"]);
	     break;
	    }
	    
	 // Password chars correctness check
	 if (($pass = $profile['element2']['data']) != '')
	 if (strlen($pass) < USERPASSMINLENGTH || !preg_match("/[0-9]/", $pass) || !preg_match("/[a-z]/", $pass) || !preg_match("/[A-Z]/", $pass))
	    {
	     $output = json_encode(['cmd' => 'ALERT', 'data' => "User password must be min ".USERPASSMINLENGTH." chars length and contain at least one digit, capital and lowercase Latin letter!"]);
	     break;
	    }
	    
	 // Applying changes
	 $output = ['cmd' => 'SET', 'value' => $user];
	 if (isset($profile['element4']['data'])) $output['odaddperm'] = $profile['element4']['data'];
	 if (isset($profile['element5']['data'])) $output['groups'] = $profile['element5']['data'];

	 // Setting password hash for non empty password field
	 if ($pass != '') $output['password'] = password_hash($pass, PASSWORD_DEFAULT);
	 $output = json_encode($output);
	 break;
   }
