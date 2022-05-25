<?php

function lg($arg) { file_put_contents('/usr/local/src/tabels/error.log', var_export($arg, true), FILE_APPEND); }

const USERSTRINGMAXCHAR	= '64';
const USERPASSMINLENGTH	= '8';
const DIALOG = [
		'title' => 'User properties', 
		'buttons' => ['SAVE' => ['value' => 'SAVE', 'call' => '', 'enterkey' => ''],
			      'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']],
		'flags'  => ['style' => 'width: 600px; height: 700px;', 'esc' => '']
	       ];

if (!isset($_SERVER['argv'][1])) $_SERVER['argv'][1] = '';

switch ($_SERVER['argv'][1])
       {
	case 'INIT':
	     if (!isset($_SERVER['argv'][2])) $_SERVER['argv'][2] = '';
	     echo json_encode(['cmd' => 'SET', 'value' => str_replace("\\", "", $_SERVER['argv'][2]), 'odaddperm' => '+Allow user to add Object Databases|', 'groups' => '', 'password' => '',  'odvisible' => 'Visible DatabaseID:ViewID list for the user|+Hidden list for the user (others visible)', 'odvisiblelist' => '', 'odwrite' => 'Writable DatabaseID:ViewID list for the user|+Read-only list for the user (others writable)', 'odwritelist' => '']);
	     break;

	case 'EDIT':
	     if (!isset($_SERVER['argv'][2], $_SERVER['argv'][3], $_SERVER['argv'][4], $_SERVER['argv'][5], $_SERVER['argv'][6], $_SERVER['argv'][7], $_SERVER['argv'][8], $_SERVER['argv'][9]))
		{
		 echo json_encode(['cmd' => '']);
		 break;
		}
	     $user = $_SERVER['argv'][2];
	     $perm = $_SERVER['argv'][3];
	     $groups = $_SERVER['argv'][4];
	     $initiator = $_SERVER['argv'][5];
	     $visible = $_SERVER['argv'][6];
	     $visiblelist = $_SERVER['argv'][7];
	     $writable = $_SERVER['argv'][8];
	     $writablelist = $_SERVER['argv'][9];
	     if ($user == 'system')
		{
		 echo json_encode(['cmd' => 'ALERT', 'data' => "You can't change system account properties!"]);
		}
	      else if ($user == '')
		{
		 echo json_encode(['cmd' => 'DIALOG', 'data' => DIALOG + ['dialog' => ['pad' => ['profile' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'User:', 'data' => $user, 'line' => ''], 'element2' => ['type' => 'password', 'head' => 'Password:', 'data' => '', 'line' => ''], 'element3' => ['type' => 'password', 'head' => 'Confirm password:', 'data' => '', 'line' => ''], 'element4' => ['type' => 'checkbox', 'data' => $perm, 'line' => ''], 'element5' => ['type' => 'textarea', 'head' => 'One by line group list the user is a member of:', 'data' => $groups, 'line' => '']]]]]]);
		}
	      else if ($initiator != $user)
		{
		 echo json_encode(['cmd' => 'DIALOG',
				   'data' => DIALOG + ['dialog' => ['pad' => ['profile' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'User:', 'data' => $user, 'line' => '', 'readonly' => ''], 'element2' => ['type' => 'password', 'head' => 'Password:', 'data' => '', 'line' => ''], 'element3' => ['type' => 'password', 'head' => 'Confirm password:', 'data' => '', 'line' => ''], 'element4' => ['type' => 'checkbox', 'data' => $perm, 'line' => ''], 'element5' => ['type' => 'textarea', 'head' => 'One by line group list the user is a member of:', 'data' => $groups, 'line' => ''],
											    'element6' => ['type' => 'radio', 'data' => $visible, 'head' => "Input colon divided database:view identificator combinations one by line.\nOmitted view id - restriction is applied for all views of specified database.\nNon digit chars at the end of the line are ignored and can be used as a\ncomment for the specified id combination.", 'help' => "Examples. '1:2' will restrict view id2 of database id1 for the user,\n'1' or '1:' will restrict database id1 all views. So hidden list of 1:2\nwill hide the specified view from the user with no read/write access,\nwhile visible list will hide all databases and views, except '1:2'."],
											    'element7' => ['type' => 'textarea', 'data' => $visiblelist],
											    'element8' => ['type' => 'radio', 'data' => $writable],
											    'element9' => ['type' => 'textarea', 'data' => $writablelist]]]]]]);
		}
	      else
		{
		 echo json_encode(['cmd' => 'DIALOG', 'data' => DIALOG + ['dialog' => ['pad' => ['profile' => ['element0' => ['head'=>''], 'element1' => ['type' => 'text', 'head' => 'User:', 'data' => $user, 'line' => '', 'readonly' => ''], 'element2' => ['type' => 'password', 'head' => 'Password:', 'data' => '', 'line' => ''], 'element3' => ['type' => 'password', 'head' => 'Confirm password:', 'data' => '', 'line' => '']]]]]]);
		}
	     break;

	case 'CONFIRMDIALOG':
	     // Check dialog data to be correct
	     if (!isset($_SERVER['argv'][2]) || gettype($data = json_decode($_SERVER['argv'][2], true)) != 'array')
		{
		 echo json_encode(['cmd' => '']);
		 break;
		}
	     $profile = $data['dialog']['pad']['profile'];
	     if ($data['title'] === 'User properties')
	        {
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
		 if (isset($profile['element5']['data']))
		    {
		     $output['groups'] = '';
		     foreach (preg_split("/\n/", $profile['element5']['data']) as $group)
			     if (trim($group)) $output['groups'] .= trim($group)."\n";
		    }
		 if (isset($profile['element6']['data'])) $output['odvisible'] = $profile['element6']['data'];
		 if (isset($profile['element7']['data'])) $output['odvisiblelist'] = $profile['element7']['data'];
		 if (isset($profile['element8']['data'])) $output['odwrite'] = $profile['element8']['data'];
		 if (isset($profile['element9']['data'])) $output['odwritelist'] = $profile['element9']['data'];
		 // Setting password hash for non empty password field
		 if ($pass) $output['password'] = password_hash($pass, PASSWORD_DEFAULT);
		 echo json_encode($output);
		 break;
		}
	     break;

	default:
	     echo json_encode(['cmd' => '']);
       }
