<?php

require_once 'core.php';

function GetObjectSelection($db, $objectSelection, $params, $user)
{
 // Check input paramValues array and add reserved :user parameter value
 if (gettype($objectSelection) != 'string' || ($objectSelection = trim($objectSelection)) === '') return DEFAULTOBJECTSELECTION;
 $i = -1;
 $len = strlen($objectSelection);
 if (gettype($params) != 'array') $params = [];
 $params[':user'] = $user;
 $isDialog = false;
 $objectSelectionNew = '';
 
 // Check $objectSelection every char and retrieve params in non-quoted substrings started with ':' and finished with space or another ':'
 while  (++$i <= $len)
     if ($i === $len || $objectSelection[$i] === '"' || $objectSelection[$i] === "'" || $objectSelection[$i] === ':' || $objectSelection[$i] === ' ')
	{
	 if (isset($newparam))
	 if (isset($params[$newparam]))
	    {
	     $objectSelectionParamsDialogProfiles[$newparam] = ['head' => "\n".str_replace('_', ' ', substr($newparam, 1)).':', 'type' => 'text', 'data' => $params[$newparam]];
	     if (!$isDialog) $objectSelectionNew .= $params[$newparam];
	    }
	  else
	    {
	     $objectSelectionParamsDialogProfiles[$newparam] = ['head' => "\n".str_replace('_', ' ', substr($newparam, 1)).':', 'type' => 'text', 'data' => ''];
	     $isDialog = true;
	    }
	 if ($i === $len) break;
	 $newparam = NULL;
	 if ($objectSelection[$i] === ':') $newparam = ':';
	  else $objectSelectionNew .= $objectSelection[$i];
	}
      else if (isset($newparam)) $newparam .= $objectSelection[$i];
      else $objectSelectionNew .= $objectSelection[$i];

 //  In case of no dialog - return object selection string
 unset($params[':user']); // Is it needable?
 if (!$isDialog) return $objectSelectionNew;
 
 // Otherwise return dialog array
 return [
	 'title'   => 'Object View parameters',
	 'dialog'  => ['pad' => ['profile' => $objectSelectionParamsDialogProfiles]],
	 'buttons' => ['OK' => ' ', 'CANCEL' => 'background-color: red;'],
	 'flags'   => ['cmd' => 'CALL',
		       'style' => 'min-width: 350px; min-height: 140px; max-width: 1500px; max-height: 500px;']
	];
}

function CheckODString($odname)
{
 return substr(str_replace("'", '', str_replace('"', '', trim(str_replace("\\", '', $odname)))), 0, ODSTRINGMAXCHAR);
}

function NewOD($db, &$client)
{
 global $id;
 
 // Get dialog OD name, cut it and check
 $odname = CheckODString($client['data']['dialog']['Database']['Properties']['element1']['data']);
 if ($odname === '') return ['cmd' => '', 'alert' => 'Object Database name cannot be empty!'];
 $client['data']['dialog']['Database']['Properties']['element1']['data'] = $odname;

 // Inserting new OD name
 $query = $db->prepare("INSERT INTO `$` (odname) VALUES (:odname)");
 $query->execute([':odname' => $odname]);

 // Getting created properties id
 $query = $db->prepare("SELECT LAST_INSERT_ID()");
 $query->execute();
 $id = $query->fetchAll(PDO::FETCH_NUM)[0][0];
 
 // Creating instance of Object Database (OD) for json "value" property (for 'uniq' object elements only)
 $query = $db->prepare("create table `uniq_$id` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) AUTO_INCREMENT=".strval(STARTOBJECTID)." ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $query->execute();                                                                                                                                   
 
 // Creating 'Object Database' (OD), consists of actual multiple object versions and its elements json data
 $query = $db->prepare("create table `data_$id` (id MEDIUMINT NOT NULL, lastversion BOOL DEFAULT 1, version MEDIUMINT NOT NULL, owner CHAR(64), datetime DATETIME DEFAULT NOW(), PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $query->execute();
 
 // Insert new OD properties
 $query = $db->prepare("UPDATE `$` SET odprops=:odprops WHERE id=$id");
 $query->execute([':odprops' => json_encode(adjustODProperties($db, $client['data'], $id))]);
		    
 return GetSidebar($db, $client['uid'], $client['ODid'], $client['OVid'], $client['OD'], $client['OV']);
}
		
function EditOD($db, &$client)
{
 // Get dialog old and new OD name
 $newodname = CheckODString($client['data']['dialog']['Database']['Properties']['element1']['data']);
 $client['data']['dialog']['Database']['Properties']['element1']['data'] = $newodname;
 $id = intval($client['data']['flags']['callback']);
 
 // Getting old OD name in `$`
 $query = $db->prepare("SELECT odname,odprops FROM `$` WHERE id=:id");
 $query->execute([':id' => $id]);
 $oldodname = $query->fetchAll(PDO::FETCH_NUM);
 
 if (!isset($oldodname[0][0], $oldodname[0][1])) return ['cmd' => '', 'alert' => "Object Database has already been removed!"];
 $odprops = $oldodname[0][1];
 $oldodname = $oldodname[0][0];
 
 // In case of empty OD name string try to remove current OD from the system
 if ($newodname === '')
    {
     if ($client['data']['dialog']['Database']['Properties']['element2']['data'] != '' || count($client['data']['dialog']['View']) != 1) return ['cmd' => '', 'alert' => "To remove Object Database (OD) - remove all views first, then empty 'name' and 'description' OD fields!"];
     $query = $db->prepare("DELETE FROM `$` WHERE id=:id");
     $query->execute([':id' => $id]);
     $query = $db->prepare("DROP TABLE IF EXISTS `uniq_$id`");
     $query->execute();
     $query = $db->prepare("DROP TABLE IF EXISTS `data_$id`");
     $query->execute();
     //$query->closeCursor();
     return GetSidebar($db, $client['uid'], $client['ODid'], $client['OVid'], $client['OD'], $client['OV']);
    }

 // Decode current OD props
 $odprops = json_decode($odprops, true);
 if (isset($odprops['dialog']['Database']['Permissions'])) $dbPermissions = $odprops['dialog']['Database']['Permissions'];
  else return ['cmd' => '', 'alert' => "Failed to get Object Database '$oldodname' properties!"];
  
 // Check current OD permissions to fetch new OD data from dialog box - $client['data']['dialog']['Database']['Permissions'])..
 $groups = getUserGroups($db, $client['uid']); // Get current user group list
 $groups[] = $client['auth']; // and add username at the end of array
 
 // Check 'Database' pad change permissions
 if ($client['data']['dialog']['Database'] != $odprops['dialog']['Database'])
 if (count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $dbPermissions['element2']['data'])), "strcmp")))
    {
     if ($dbPermissions['element1']['data'] === 'allowed list (disallowed for others)|+disallowed list (allowed for others)|')
	{
	 $alertstring .= "'Database', ";
	 $client['data']['dialog']['Database'] = $odprops['dialog']['Database'];
	}
    }
  else
    {
     if ($dbPermissions['element1']['data'] === '+allowed list (disallowed for others)|disallowed list (allowed for others)|')
	{
	 $alertstring .= "'Database', ";
	 $client['data']['dialog']['Database'] = $odprops['dialog']['Database'];
	}
    }
 // Check 'Element' pad change permissions
 if ($client['data']['dialog']['Element'] != $odprops['dialog']['Element'])
 if (count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $dbPermissions['element4']['data'])), "strcmp")))
    {
     if ($dbPermissions['element3']['data'] === 'allowed list (disallowed for others)|+disallowed list (allowed for others)|')
	{
	 $alertstring .= "'Element', ";
	 $client['data']['dialog']['Element'] = $odprops['dialog']['Element'];
	}
    }
  else
    {
     if ($dbPermissions['element3']['data'] === '+allowed list (disallowed for others)|disallowed list (allowed for others)|')
	{
	 $alertstring .= "'Element', ";
	 $client['data']['dialog']['Element'] = $odprops['dialog']['Element'];
	}
    }
 // Check 'View' pad change permissions
 if ($client['data']['dialog']['View'] != $odprops['dialog']['View'])
 if (count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $dbPermissions['element6']['data'])), "strcmp")))
    {
     if ($dbPermissions['element5']['data'] === 'allowed list (disallowed for others)|+disallowed list (allowed for others)|')
	{
	 $alertstring .= "'View', ";
	 $client['data']['dialog']['View'] = $odprops['dialog']['View'];
	}
    }
  else
    {
     if ($dbPermissions['element5']['data'] === '+allowed list (disallowed for others)|disallowed list (allowed for others)|')
	{
	 $alertstring .= "'View', ";
	 $client['data']['dialog']['View'] = $odprops['dialog']['View'];
	}
    }
 // Check 'Rule' pad change permissions
 if ($client['data']['dialog']['Rule'] != $odprops['dialog']['Rule'])
 if (count(array_uintersect($groups, UnsetEmptyArrayElements(explode("\n", $dbPermissions['element8']['data'])), "strcmp")))
    {
     if ($dbPermissions['element7']['data'] === 'allowed list (disallowed for others)|+disallowed list (allowed for others)|')
	{
	 $alertstring .= "'Rule', ";
	 $client['data']['dialog']['Rule'] = $odprops['dialog']['Rule'];
	}
    }
  else
    {
     if ($dbPermissions['element7']['data'] === '+allowed list (disallowed for others)|disallowed list (allowed for others)|')
	{
	 $alertstring .= "'Rule', ";
	 $client['data']['dialog']['Rule'] = $odprops['dialog']['Rule'];
	}
    }

 // Writing new properties
 $query = $db->prepare("UPDATE `$` SET odname=:odname,odprops=:odprops WHERE id=:id");
 $query->execute([':odname' => $newodname, ':odprops' => json_encode(adjustODProperties($db, $client['data'], $id)), ':id' => $id]);

 // Return result
 $output = GetSidebar($db, $client['uid'], $client['ODid'], $client['OVid'], $client['OD'], $client['OV']);
 if (isset($alertstring)) $output['alert'] = "You're not allowed to change ".substr($alertstring, 0, -2)." properties!";
 return $output;
}

try {
     $output = ['cmd' => ''];
     $input = json_decode(file_get_contents("php://input"), true);
     $query = $db->prepare("SELECT now()-time,client FROM `$$$` WHERE id='$input'");
     $query->execute();
     $client = $query->fetchAll(PDO::FETCH_NUM)[0];
     $query = $db->prepare("DELETE FROM `$$$` WHERE id='$input'");
     $query->execute();
    }
catch (PDOException $e)
    {
     exit;
    }    

if (intval($client[0]) > CALLTIMEOUT) { echo json_encode(['cmd' => '', 'alert' => 'Server call request timeout, please try again!']); exit; }
$client = json_decode($client[1], true);

try {
     switch ($client['cmd'])
       {
        case 'CALL':
	     if (!Check($db, GET_ELEMENTS | GET_VIEWS | CHECK_ACCESS, $client, $client, $output)) break;
	     // Get object selection query string, in case of array as a return result send dialog to the client to fetch up object selection params
	     $client['objectselection'] = GetObjectSelection($db, $client['objectselection'], $client['params'], $client['auth']);
	     if (gettype($client['objectselection']) === 'array')
	        {
		 $output = ['cmd' => 'DIALOG', 'data' => $client['objectselection']];
		 break;
		}
	     // Get element selection query string, in case of empty result return no element message as an error
	     $elementQueryString = '';
	     $props = setElementSelectionIds($client);

	     foreach ($props as $key => $value) if (intval($key) > 0) $elementQueryString .= ',eid'.$key;
	     if ($elementQueryString === '')
	        {
		 $output = ['cmd' => '', 'error' => "Database '$client[OD]' Object View '$client[OV]' has no elements defined!"];
		 break;
		}
	     // Return OV refresh command to the client with object selection sql query result as a main field data
	     $query = $db->prepare("SELECT id,version,owner,datetime,lastversion$elementQueryString FROM `data_$client[ODid]` $client[objectselection]");
	     $query->execute();
	     $output = ['cmd' => 'DRAW', 'data' => $query->fetchAll(PDO::FETCH_ASSOC), 'props' => $props];
	     break;
        case 'New Object Database':
	     if (!Check($db, CHECK_ACCESS, $client, $client, $output)) break;
	     $output = NewOD($db, $client);
	     break;
        case 'Edit Database Structure':
	     $output = EditOD($db, $client);
	     break;
       }
    }
catch (PDOException $e)
    {
     lg($e);
     $msg = $e->getMessage();
     switch ($client['cmd'])
    	    {
    	     case 'CALL':
	          $output = ['cmd' => '', 'alert' => "Failed to get Object View: $msg"];
	    	  break;
    	     case 'New Object Database':
	    	  if (isset($id))
		     {
		      $query = $db->prepare("DELETE FROM `$` WHERE id=$id");
		      $query->execute();
		      $query = $db->prepare("DROP TABLE IF EXISTS `data_$id`");
		      $query->execute();
		      $query = $db->prepare("DROP TABLE IF EXISTS `uniq_$id`");
		      $query->execute();
		     }                                                                                                                         
		  if (preg_match("/Duplicate entry/", $msg) === 1)
		     $output = ['cmd' => '', 'alert' => 'Failed to add new Object Database: its name or tables already exist!'];
		   else
		     $output = ['cmd' => '', 'alert' => "Failed to add new Object Database: $msg"];
	    	  break;
    	     case 'Edit Database Structure':
	          $output = ['cmd' => '', 'alert' => "Failed to write Object Database properties: $msg"];
	    	  break;
	    }                                                                            	     
    }    
    
// Echo output result      
echo json_encode($output);
