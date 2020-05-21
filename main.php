<?php

try {
     require_once 'eroc.php';
     createDefaultDatabases($db);
    }
catch (PDOException $e)
    {
     loog($e);
     echo json_encode(['cmd' => 'INFO', 'error' => $e->getMessage()]);
     exit;
    }

try {
     $input = json_decode(file_get_contents("php://input"), true);
     
     switch ($input['cmd'])
	    {
	    case 'New Object Database':
	          initNewODDialogElements();
			  $output = ['cmd' => 'DIALOG', 'data' => ['title'  => 'New Object Database', 'dialog'  => ['Database' => ['Properties' => $newProperties], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'flags'  => ['esc' => '', 'ok' => 'Create']]];
		  break;
	    case 'Object Database Properties':
			if (isset($input['data']))
				{
				 initNewODDialogElements();
 				 $query = $db->prepare("SELECT odprops FROM `$` WHERE odname=:odname");
				 $query->execute([':odname' => $input['data']]);
				 $odprops = json_decode($query->fetch(PDO::FETCH_NUM)[0], true);
				 $odprops['flags']['callbackData'] = $input['data'];
				 $output = ['cmd' => 'DIALOG', 'data' => $odprops];
				}
		break;
	    case 'NEWOD':
		if (is_array($input['data']))
		   {
		    $odname = $input['data']['dialog']['Database']['Properties']['element1']['data'];
		    // Creating instance of Object Database (OD) for , consists of primary identificator and uniq elements                                                        
		    $query = $db->prepare("create table `$odname` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		    $query->execute();                                                                                                                                   
		    // Creating 'Object Database' (OD), consists of primary identificator and actual data with its versions                                              
		    $query = $db->prepare("create table `$odname\$` (id MEDIUMINT NOT NULL, last BOOL DEFAULT 1, version MEDIUMINT NOT NULL, date DATE, time TIME, user CHAR, PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		    $query->execute();                                                                                                                                   
		initNewODDialogElements();
			$query = $db->prepare("INSERT INTO `$` (odname, odprops) VALUES (:odname, :odprops)");
			$query->execute([':odname' => $odname, ':odprops' => json_encode(adjustODProperties($input['data']))]);
		   }
		   $output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		break;
		case 'EDITOD':
			if (is_array($input['data']))
		   {
			initNewODDialogElements();
			$query = $db->prepare("UPDATE `$` SET odprops=:odprops WHERE odname=:odname");
			$query->execute([':odprops' => json_encode(adjustODProperties($input['data'])), ':odname' => $input['data']['dialog']['Database']['Properties']['element1']['data']]);
			loog($input['data']['dialog']['Database']['Properties']['element1']['data']);
		   }
		   $output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		break;
		case 'GETMENU':
		   $output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		break;
	 default:
	          $output = ['cmd' => 'INFO', 'alert' => 'Unknown event "'.$input['cmd'].'" received from the browser!'];
		}
		
	 if (!isset($output)) $output = ['cmd' => 'INFO', 'alert' => 'Undefined controller message!'];
     echo json_encode($output);
    }
     
catch (PDOException $e)
    {
     loog($e);
     switch ($input['cmd'])
    	    {
	     case 'CONFIRM':
	          echo json_encode(['cmd' => 'INFO', 'alert' => 'Some text: '.$e->getMessage()]);
		  break;
		 case 'NEWOD':
			if (preg_match("/Duplicate entry/", $e->getMessage()) === 1) echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to add new object database: OD name already exists!']);
			 else echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to add new object database: '.$e->getMessage()]);
		  break;
		case 'GETMENU':
			 echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to get sidebar OD/OV list: '.$e->getMessage()]);
		  break;
	     default:
		 echo json_encode(['cmd' => 'INFO', 'alert' => 'Unknown error: '.$e->getMessage()]);
	    }
    }
