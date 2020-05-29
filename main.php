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
		  $output = ['cmd' => 'DIALOG', 'data' => ['title'  => 'New Object Database', 'dialog'  => ['Database' => ['Properties' => $newProperties], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'flags'  => ['esc' => '', 'ok' => 'CREATE']]];
		  break;
	    case 'Edit Database Structure':
			if (isset($input['data']))
				{
				 initNewODDialogElements();
 				 $query = $db->prepare("SELECT odprops FROM `$` WHERE odname=:odname");
				 $query->execute([':odname' => $input['data']]);
				 $odprops = json_decode($query->fetch(PDO::FETCH_NUM)[0], true);
				 if ($odprops)
				    {
				     $odprops['flags']['callbackData'] = $input['data'];
				     $output = ['cmd' => 'DIALOG', 'data' => $odprops];
				    }
				 else $output = ['cmd' => 'INFO', 'alert' => "Unable to get '$input[data]' Object Database properties!"];
				}
		break;
	    case 'NEWOD':
		if (is_array($input['data']))
		   {
		    // Get dialog OD name, cut it and check
		    $odname = $input['data']['dialog']['Database']['Properties']['element1']['data'] = substr(trim($input['data']['dialog']['Database']['Properties']['element1']['data']), 0, ODSTRINGMAXCHAR);
		    if ($odname === '')
		       {
		        $output = ['cmd' => 'INFO', 'alert' => 'Please input Object Database name!'];
		        break;
		       }
		    initNewODDialogElements();
		    // Inserting new OD name
		    $query = $db->prepare("INSERT INTO `$` (odname) VALUES (:odname)");
		    $query->execute([':odname' => $odname]);
		    // Getting created properties id
		    $query = $db->prepare("SELECT LAST_INSERT_ID()");
		    $query->execute();
		    $id = $query->fetch(PDO::FETCH_NUM)[0];
		    // Creating instance of Object Database (OD) for json "value" property (for 'uniq' object elements only)
		    $query = $db->prepare("create table `uniq_$id` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		    $query->execute();                                                                                                                                   
		    // Creating 'Object Database' (OD), consists of actual multiple object versions and its elements json data
		    $query = $db->prepare("create table `data_$id` (id MEDIUMINT NOT NULL, last BOOL DEFAULT 1, version MEDIUMINT NOT NULL, date DATE, time TIME, user CHAR, PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		    $query->execute();
		    // Insert new OD properties
		    $query = $db->prepare("UPDATE `$` SET odprops=:odprops WHERE id=$id");
		    $query->execute([':odprops' => json_encode(adjustODProperties($input['data'], $db, $id))]);
		    
		    
		    /*// Inserting new OD properties
		    $query = $db->prepare("INSERT INTO `$` (odname, odprops) VALUES (:odname, :odprops)");
		    $query->execute([':odname' => $odname, ':odprops' => json_encode(adjustODProperties($input['data']))]);
		    // Getting created properties id
		    $query = $db->prepare("SELECT LAST_INSERT_ID()");
		    $query->execute();
		    $id = $query->fetch(PDO::FETCH_NUM)[0];
		    // Creating instance of Object Database (OD) for json "value" property (for 'uniq' object elements only)
		    $element = $input['data']['dialog']['Element']['New element'];
		    if ($element['element3']['data'] === 'standart|static|+unique|') $query = $db->prepare("create table `uniq_$id` (id MEDIUMINT NOT NULL AUTO_INCREMENT, eid1 TEXT, UNIQUE(eid1(".UNIQKEYCHARLENGTH.")), PRIMARY KEY (id)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		     else $query = $db->prepare("create table `uniq_$id` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		    $query->execute();                                                                                                                                   
		    // Creating 'Object Database' (OD), consists of actual multiple object versions and its elements json data
		    $elementQuery = '';
		    if ($element['element1']['data'] != '' || $element['element2']['data'] != '' || $element['element4']['data'] != '') $elementQuery = 'eid1 JSON, ';
		    $query = $db->prepare("create table `data_$id` (id MEDIUMINT NOT NULL, last BOOL DEFAULT 1, version MEDIUMINT NOT NULL, date DATE, time TIME, user CHAR, ".$elementQuery."PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		    $query->execute();*/
		    //-------------------------------------------------------------------------------------
		   }
		$output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		break;
	    case 'EDITOD':
		if (is_array($input['data']))
		   {
		    // Get dialog old and new OD name
		    $newodname = $input['data']['dialog']['Database']['Properties']['element1']['data'] = substr($input['data']['dialog']['Database']['Properties']['element1']['data'], 0, ODSTRINGMAXCHAR);
		    $oldodname = $input['data']['flags']['callbackData'] = substr($input['data']['flags']['callbackData'], 0, ODSTRINGMAXCHAR);
		    // Getting old OD name id in `$`
		    $query = $db->prepare("SELECT id FROM `$` WHERE odname=:odname");
		    $query->execute([':odname' => $oldodname]);
		    $id = $query->fetch(PDO::FETCH_NUM)[0];
		    // In case of empty OD name string try to remove current OD from the system
		    if ($newodname === '')
		    if ($input['data']['dialog']['Database']['Properties']['element2']['data'] === '')
		       {
		        $query = $db->prepare("DELETE FROM `$` WHERE id=$id");
			$query->execute();
			$output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		        $query = $db->prepare("DROP TABLE `uniq_$id`; DROP TABLE `data_$id`");
			$query->execute();
			break;
		       }
		     else
		       {
		        $output = ['cmd' => 'INFO', 'alert' => 'Both fields (OD name and description) should be empty to remove Object Database!'];
			break;
		       }
			// Writing new properties
			initNewODDialogElements();
			$query = $db->prepare("UPDATE `$` SET odname=:odname,odprops=:odprops WHERE id=$id");
			$query->execute([':odname' => $newodname, ':odprops' => json_encode(adjustODProperties($input['data'], $db, $id))]);
		   }
		   $output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		break;
		case 'GETMENU':
		   $output = ['cmd' => 'REFRESHMENU', 'data' => getODVNamesForSidebar($db)];
		break;
		case 'GETMAIN':
		   $output = ['cmd' => 'INFO', 'error' => 'Please select Object View'];
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
			if (isset($id))
			    {
			     $query = $db->prepare("DELETE FROM `$` WHERE id=$id");
			     $query->execute();
			     $query = $db->prepare("DROP TABLE IF EXISTS `data_$id`; DROP TABLE IF EXISTS `uniq_$id`");
			     $query->execute();
			    }
			if (preg_match("/already exist/", $e->getMessage()) === 1 || preg_match("/Duplicate entry/", $e->getMessage()) === 1)
			      echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to add new object database: OD name or data tables already exist!']);
			 else echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to add new object database: '.$e->getMessage()]);
		  break;
		case 'EDITOD':
			 echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to write OD properties: '.$e->getMessage()]);
		  break;
		case 'GETMENU':
			 echo json_encode(['cmd' => 'INFO', 'alert' => 'Failed to get sidebar OD/OV list: '.$e->getMessage()]);
		  break;
	     default:
		 echo json_encode(['cmd' => 'INFO', 'alert' => 'Unknown error: '.$e->getMessage()]);
	    }
    }
