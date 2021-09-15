<?php

function lg($arg)
{
 file_put_contents('/usr/local/src/tabels/error.log', var_export($arg, true), FILE_APPEND);
}

const SAVECANCEL	= ['SAVE' => ['value' => 'SAVE', 'call' => '', 'enterkey' => ''], 'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']];
const USERSTRINGMAXCHAR	= '64';
const USERPASSMINLENGTH	= '8';

if (!isset($_SERVER['argv'][1])) exit;

switch ($_SERVER['argv'][1])
       {
	case 'INIT':
	     if (isset($_SERVER['argv'][2])) echo json_encode(['cmd' => 'SET', 'value' => str_replace("\\", "", $_SERVER['argv'][2]), '_style' => 'color: green;', 'odaddperm' => '+Allow user to add Object Databases|', 'groups' => '', 'password' => '']);
	     break;
	case 'F2':
	case 'DBLCLICK':
	     if (!isset($_SERVER['argv'][2], $_SERVER['argv'][3], $_SERVER['argv'][4], $_SERVER['argv'][5])) break;
	     $user = $_SERVER['argv'][2];
	     $perm = $_SERVER['argv'][3];
	     $groups = $_SERVER['argv'][4];
	     $initiator = $_SERVER['argv'][5];

	     if ($user == 'system') echo json_encode(['cmd' => 'ALERT', 'data' => "You can't change system account properties!"]);
	      else if ($user == '') echo json_encode(['cmd' => 'DIALOG', 'data' => ['title' => 'User properties', 'dialog' => ['pad' => ['profile' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'User:', 'data' => $user, 'line' => ''], 'element2' => ['type' => 'password', 'head' => 'Password:', 'data' => '', 'line' => ''], 'element3' => ['type' => 'password', 'head' => 'Confirm password:', 'data' => '', 'line' => ''], 'element4' => ['type' => 'checkbox', 'data' => $perm, 'line' => ''], 'element5' => ['type' => 'textarea', 'head' => 'One by line group list the user is a member of:', 'data' => $groups, 'line' => ''] ]]], 'buttons' => SAVECANCEL, 'flags'  => ['style' => 'width: 500px; height: 500px;', 'esc' => '']]]);
	      else if ($initiator != $user) echo json_encode(['cmd' => 'DIALOG', 'data' => ['title' => 'User properties', 'dialog' => ['pad' => ['profile' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'User:', 'data' => $user, 'line' => '', 'readonly' => ''], 'element2' => ['type' => 'password', 'head' => 'Password:', 'data' => '', 'line' => ''], 'element3' => ['type' => 'password', 'head' => 'Confirm password:', 'data' => '', 'line' => ''], 'element4' => ['type' => 'checkbox', 'data' => $perm, 'line' => ''], 'element5' => ['type' => 'textarea', 'head' => 'One by line group list the user is a member of:', 'data' => $groups, 'line' => '']]]], 'buttons' => SAVECANCEL, 'flags'  => ['style' => 'width: 500px; height: 500px;', 'esc' => '']]]);
	      else echo json_encode(['cmd' => 'DIALOG', 'data' => ['title' => 'User properties', 'dialog' => ['pad' => ['profile' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'User:', 'data' => $user, 'line' => '', 'readonly' => ''], 'element2' => ['type' => 'password', 'head' => 'Password:', 'data' => '', 'line' => ''], 'element3' => ['type' => 'password', 'head' => 'Confirm password:', 'data' => '', 'line' => '']]]], 'buttons' => SAVECANCEL, 'flags'  => ['style' => 'width: 500px; height: 500px;', 'esc' => '']]]);
	     break;

	case 'F12':
	     echo json_encode(['cmd' => 'DIALOG',
			       'data' => ['title' => 'Group user list',
			       'dialog' => ['pad' => ['profile' => ['element' => ['type' => 'text', 'head' => 'Enter group name to find its user members:', 'data' => '', 'line' => ''],]]],
								    'buttons' => ['OK' => ['value' => 'FIND', 'call' => '', 'enterkey' => ''], 'CANCEL' => ['value' => 'EXIT', 'style' => 'background-color: red;']],
								    'flags'  => ['style' => 'width: 500px; height: 500px;', 'esc' => '']]]);
	     break;

	case 'CONFIRMDIALOG':
	     // Check dialog data to be correct
	     if (!isset($_SERVER['argv'][2]) || gettype($data = json_decode($_SERVER['argv'][2], true)) != 'array') break;
	     $profile = $data['dialog']['pad']['profile'];

	     if ($data['title'] === 'User properties')
	        {
	         if (strlen($user = str_replace("\\", "", $profile['element1']['data'])) > USERSTRINGMAXCHAR) $user = substr($user, 0, USERSTRINGMAXCHAR);

	         // Check the user to be not emtpy
	         if (!isset($profile['element1']['data']) || !$profile['element1']['data']) { echo json_encode(['cmd' => 'ALERT', 'data' => 'Username cannot be empty!']); break; }

	         // Password match check
	         if (!isset($profile['element2']['data']) || !isset($profile['element3']['data']) || $profile['element2']['data'] != $profile['element3']['data']) { echo json_encode(['cmd' => 'ALERT', 'data' => "Confirm password doesn't match the password!"]); break; }

	         // Password chars correctness check
	         if (($pass = $profile['element2']['data']) != '')
	         if (strlen($pass) < USERPASSMINLENGTH || !preg_match("/[0-9]/", $pass) || !preg_match("/[a-z]/", $pass) || !preg_match("/[A-Z]/", $pass)) { echo json_encode(['cmd' => 'ALERT', 'data' => "User password must be min ".USERPASSMINLENGTH." chars length and contain at least one digit, capital and lowercase latin letter!"]); break; }

	         // Applying changes
		 $output = ['cmd' => 'SET', 'value' => $user];
		 if (isset($profile['element4']['data'])) $output['odaddperm'] = $profile['element4']['data'];
		 if (isset($profile['element5']['data']))
		    {
		     $output['groups'] = '';
		     foreach (preg_split("/\n/", $profile['element5']['data']) as $group)
			     if (trim($group)) $output['groups'] .= trim($group)."\n";
		    }

		 // Setting password hash for non empty password field
		 if ($pass) $output['password'] = password_hash($pass, PASSWORD_DEFAULT);
		 echo json_encode($output);
		 break;
		}

	     if ($data['title'] === 'Group user list' && isset($profile['element']['data']) && $profile['element']['data'])
	        {
		}
	     break;
       }
