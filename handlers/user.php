<?php

require_once 'const.php';

if (!isset($_SERVER['argv'][1])) exit;
$event = $_SERVER['argv'][1];         

switch ($event)
       {
	case 'INIT':
	     if (!isset($_SERVER['argv'][2])) break;
	        {
		 echo json_encode(['cmd' => 'ALERT', 'data' => 'Incorrect input data!']);
		 break;
	        }
	     echo json_encode(['cmd' => 'SET', 'value' => str_replace("\\", "", $_SERVER['argv'][2]), '_style' => 'color: green;', 'odaddperm' => '+Allow user to add Object Databases|', 'groups' => '', 'password' => '']);
	     break;
	case 'DBLCLICK':
	     if (!isset($_SERVER['argv'][2], $_SERVER['argv'][3], $_SERVER['argv'][4], $_SERVER['argv'][5])) break;
	     $user = $_SERVER['argv'][2];
	     $perm = $_SERVER['argv'][3];
	     $groups = $_SERVER['argv'][4];
	     $initiator = $_SERVER['argv'][5];
	     if ($user == 'system') echo json_encode(['cmd' => 'ALERT', 'data' => "You can't change system account properties!"]);
	      else if ($user == '') echo json_encode(['cmd' => 'DIALOG', 'data' => ['title' => 'User properties', 'dialog' => ['pad' => ['profile' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'User:', 'data' => $user, 'line' => ''], 'element2' => ['type' => 'password', 'head' => 'Password:', 'data' => '', 'line' => ''], 'element3' => ['type' => 'password', 'head' => 'Confirm password:', 'data' => '', 'line' => ''], 'element4' => ['type' => 'checkbox', 'data' => $perm, 'line' => ''], 'element5' => ['type' => 'textarea', 'head' => 'One by line group list the user is a member of:', 'data' => $groups, 'line' => '']]]], 'buttons' => ['SAVE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['style' => 'width: 500px; height: 500px;', 'esc' => '']]]);
	      else if ($initiator != $user) echo json_encode(['cmd' => 'DIALOG', 'data' => ['title' => 'User properties', 'dialog' => ['pad' => ['profile' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'User:', 'data' => $user, 'line' => '', 'readonly' => ''], 'element2' => ['type' => 'password', 'head' => 'Password:', 'data' => '', 'line' => ''], 'element3' => ['type' => 'password', 'head' => 'Confirm password:', 'data' => '', 'line' => ''], 'element4' => ['type' => 'checkbox', 'data' => $perm, 'line' => ''], 'element5' => ['type' => 'textarea', 'head' => 'One by line group list the user is a member of:', 'data' => $groups, 'line' => '']]]], 'buttons' => ['SAVE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['style' => 'width: 500px; height: 500px;', 'esc' => '']]]);
	      else echo json_encode(['cmd' => 'DIALOG', 'data' => ['title' => 'User properties', 'dialog' => ['pad' => ['profile' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'User:', 'data' => $user, 'line' => '', 'readonly' => ''], 'element2' => ['type' => 'password', 'head' => 'Password:', 'data' => '', 'line' => ''], 'element3' => ['type' => 'password', 'head' => 'Confirm password:', 'data' => '', 'line' => '']]]], 'buttons' => ['SAVE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['style' => 'width: 500px; height: 500px;', 'esc' => '']]]);
	     break;
	case 'CONFIRM':
	     // Check dialog data to be correct
	     if (isset($_SERVER['argv'][2])) $data = json_decode($_SERVER['argv'][2], true);
	     if (!isset($data, $data['dialog']['pad']['profile']['element1']['data'])) 
	        {
		 echo json_encode(['cmd' => 'ALERT', 'data' => 'Incorrect input data!']);
		 break;
	        }
	     $profile = $data['dialog']['pad']['profile'];
	     if (strlen($user = str_replace("\\", "", $profile['element1']['data'])) > USERSTRINGMAXCHAR) $user = substr($user, 0, USERSTRINGMAXCHAR);
	 
	     // Check the user to be not emtpy
	     if (!isset($profile['element1']['data']) || !$profile['element1']['data'])
	        {
		 echo json_encode(['cmd' => 'ALERT', 'data' => 'Username cannot be empty!']);
		 break;
	        }
	    
	     // Password match check
	     if (!isset($profile['element2']['data']) || !isset($profile['element3']['data']) || $profile['element2']['data'] != $profile['element3']['data'])
	        {
	         echo json_encode(['cmd' => 'ALERT', 'data' => "Confirm password doesn't match the password!"]);
	         break;
	        }
	    
	     // Password chars correctness check
	     if (($pass = $profile['element2']['data']) != '')
	     if (strlen($pass) < USERPASSMINLENGTH || !preg_match("/[0-9]/", $pass) || !preg_match("/[a-z]/", $pass) || !preg_match("/[A-Z]/", $pass))
	        {
	         echo json_encode(['cmd' => 'ALERT', 'data' => "User password must be min ".USERPASSMINLENGTH." chars length and contain at least one digit, capital and lowercase latin letter!"]);
	         break;
	        }
	    
	     // Applying changes
	     $output = ['cmd' => 'SET', 'value' => $user];
	     if (isset($profile['element4']['data'])) $output['odaddperm'] = $profile['element4']['data'];
	     if (isset($profile['element5']['data'])) $output['groups'] = $profile['element5']['data'];

	     // Setting password hash for non empty password field
	     if ($pass != '') $output['password'] = password_hash($pass, PASSWORD_DEFAULT);
	     echo json_encode($output);
	     break;
       }
