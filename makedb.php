<?php
// Be aware of removing old ODs by using that script

require_once 'core.php';
require_once HANDLERDIR.'customizationjson.php';

try {
     // Old shit should be dropped
     $query = $db->prepare("drop database ".DATABASENAME."; create database ".DATABASENAME."; use ".DATABASENAME);
     $query->execute();

     // Create OV request list sql table with next fields: id,time,ODid,OV
     $query = $db->prepare("CREATE TABLE `$$$` (id CHAR(".USERPASSMINLENGTH.") NOT NULL, time DATETIME DEFAULT NOW(), client MEDIUMTEXT, PRIMARY KEY (id)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
     $query->execute();

     // Create queue sql table with next fields: id,cid,ODid,OV,oid,eid,event
     $query = $db->prepare("CREATE TABLE `$$` (id MEDIUMINT NOT NULL AUTO_INCREMENT, client MEDIUMTEXT, PRIMARY KEY (id)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
     $query->execute();
 
     // Create OD list data sql table
     $query = $db->prepare("CREATE TABLE `$` (id MEDIUMINT NOT NULL AUTO_INCREMENT, odname CHAR(".strval(ODSTRINGMAXCHAR).") NOT NULL, odprops JSON, UNIQUE(odname), PRIMARY KEY (id)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
     $query->execute();
 
     //------------------------------------------Create default OD 'Users'------------------------------------------
     initNewODDialogElements();
     $newProperties['element1']['data'] = 'Users';
     $userOD = ['title'  => 'Edit Object Database Structure', 'dialog'  => ['Database' => ['Properties' => $newProperties], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => SAVECANCEL, 'flags'  => ['style' => 'width: 760px; height: 720px;', 'esc' => '', 'padprofilehead' => ['Element' => "Select element", 'View' => "Select view", 'Rule' => "Select rule"]]];
     $userOD['buttons']['SAVE']['call'] = 'Edit Database Structure';
     $userOD['dialog']['Element']['New element']['element1']['id'] = '7';
     $userOD['dialog']['View']['New view']['element1']['id'] = '2';

     $newView['element1']['id'] = '1';
     $newView['element1']['data'] = 'All users';
     $newView['element6']['data'] = '*';
     $userOD['dialog']['View']['All users'] = $newView;

     $newElement['element1']['id'] = '1';
     $newElement['element1']['data'] = 'User';
     $newElement['element2']['data'] = "\nDouble click the username to change the password and other user properties";
     $newElement['element3']['data'] = UNIQELEMENTTYPE;
     $newElement['element3']['readonly'] = '';
     $json = '{"props": {"value":"", "odaddperm":"", "groups":""}}';
     $newElement['element4']['data'] = PHPBINARY.' '.HANDLERDIR.'user.php <event> <data>';
     $newElement['element5']['data'] = PHPBINARY.' '.HANDLERDIR."user.php <event> '$json' <user>";
     $newElement['element12']['data'] = PHPBINARY.' '.HANDLERDIR.'user.php <event> <data>';
     $userOD['dialog']['Element']['User (id1)'] = $newElement;

     $json = '{"props": {"link":"", "linkoid":"", "linkeid":""}}';
     initNewODDialogElements();
     $newElement['element1']['id'] = '2';
     $newElement['element1']['data'] = 'Name';
     $newElement['element2']['data'] = '';
     $newElement['element3']['data'] = 'unique';
     $newElement['element3']['readonly'] = '';
     $newElement['element4']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element5']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT';
     $newElement['element6']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT <data>';
     $newElement['element7']['data'] = PHPBINARY.' '.HANDLERDIR."text.php SET '$json' <data>";
     $newElement['element8']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET';
     $newElement['element9']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT';
     $newElement['element11']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element12']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php CONFIRMDIALOG <data>';
     $userOD['dialog']['Element']['Name (id2)'] = $newElement;
     
     initNewODDialogElements();
     $newElement['element1']['id'] = '3';
     $newElement['element1']['data'] = 'Telephone';
     $newElement['element2']['data'] = '';
     $newElement['element3']['data'] = 'unique';
     $newElement['element3']['readonly'] = '';
     $newElement['element4']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element5']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT';
     $newElement['element6']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT <data>';
     $newElement['element7']['data'] = PHPBINARY.' '.HANDLERDIR."text.php SET '$json' <data>";
     $newElement['element8']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET';
     $newElement['element9']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT';
     $newElement['element11']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element12']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php CONFIRMDIALOG <data>';
     $userOD['dialog']['Element']['Telephone (id3)'] = $newElement;

     initNewODDialogElements();
     $newElement['element1']['id'] = '4';
     $newElement['element1']['data'] = 'Email';
     $newElement['element2']['data'] = '';
     $newElement['element3']['data'] = 'unique';
     $newElement['element3']['readonly'] = '';
     $newElement['element4']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element5']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT';
     $newElement['element6']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT <data>';
     $newElement['element7']['data'] = PHPBINARY.' '.HANDLERDIR."text.php SET '$json' <data>";
     $newElement['element8']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET';
     $newElement['element9']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT';
     $newElement['element11']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element12']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php CONFIRMDIALOG <data>';
     $userOD['dialog']['Element']['Email (id4)'] = $newElement;

     initNewODDialogElements();
     $newElement['element1']['id'] = '5';
     $newElement['element1']['data'] = 'Comment';
     $newElement['element2']['data'] = '';
     $newElement['element3']['data'] = 'unique';
     $newElement['element3']['readonly'] = '';
     $newElement['element4']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element5']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT';
     $newElement['element6']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT <data>';
     $newElement['element7']['data'] = PHPBINARY.' '.HANDLERDIR."text.php SET '$json' <data>";
     $newElement['element8']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET';
     $newElement['element9']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT';
     $newElement['element11']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element12']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php CONFIRMDIALOG <data>';
     $userOD['dialog']['Element']['Comment (id5)'] = $newElement;

     initNewODDialogElements();
     $newElement['element1']['id'] = '6';
     $newElement['element1']['data'] = 'Customization';
     $newElement['element2']['data'] = "\nDouble click appropriate cell to change color, font, background and other properties for the specified user";
     $newElement['element3']['data'] = 'unique';
     $newElement['element3']['readonly'] = '';
     $json = '{"props": "dialog"}';
     $newElement['element4']['data'] = PHPBINARY.' '.HANDLERDIR.'customization.php <event>';
     $newElement['element5']['data'] = PHPBINARY.' '.HANDLERDIR."customization.php <event> '$json'";
     $newElement['element12']['data'] = PHPBINARY.' '.HANDLERDIR.'customization.php <event> <data>';
     $userOD['dialog']['Element']['Customization (id6)'] = $newElement;

     $query = $db->prepare("INSERT INTO `$` (odname,odprops) VALUES ('Users',:odprops)");
     $query->execute([':odprops' => json_encode($userOD)]);
     $query->closeCursor();

     // Create Object Database (uniq instance)
     $query = $db->prepare("CREATE TABLE `uniq_1` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) AUTO_INCREMENT=".strval(STARTOBJECTID)." ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
     $query->execute();
     $query = $db->prepare("ALTER TABLE `uniq_1` ADD eid1 BLOB(65535), ADD UNIQUE(eid1(".USERSTRINGMAXCHAR."))");
     $query->execute();
 
     // Create Object Database (actual data instance)
     $query = $db->prepare("CREATE TABLE `data_1` (id MEDIUMINT NOT NULL, mask TEXT, lastversion BOOL DEFAULT 1, version MEDIUMINT NOT NULL, owner CHAR(64), datetime DATETIME DEFAULT NOW(), eid1 JSON, eid2 JSON, eid3 JSON, eid4 JSON, eid5 JSON, eid6 JSON, PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
     $query->execute();
     $query = $db->prepare("ALTER TABLE `data_1` ADD INDEX (`lastversion`)");
     $query->execute();
     
     // Insert two objects (two users) to OD with id=1
     $client['auth'] = 'system';
     $client['ODid'] = '1';
     $client['allelements'] = ['1' => '', '2' => '', '3' => '', '4' => '', '5' => '', '6' => ''];
     $client['uniqelements'] = ['1' => ''];
     $output = ['1' => ['cmd' => 'RESET', 'value' => 'system'] + DEFAULTELEMENTPROPS,
    		'2' => ['cmd' => 'RESET', 'value' => ''] + DEFAULTELEMENTPROPS,
		'3' => ['cmd' => 'RESET', 'value' => ''] + DEFAULTELEMENTPROPS,
		'4' => ['cmd' => 'RESET', 'value' => ''] + DEFAULTELEMENTPROPS,
		'5' => ['cmd' => 'RESET', 'value' => 'System account'] + DEFAULTELEMENTPROPS];
     AddObject($db, $client, $output);

     $output = ['1' => ['cmd' => 'RESET', 'value' => DEFAULTUSER, 'odaddperm' => '+Allow user to add Object Databases|', 'password' => password_hash(DEFAULTPASSWORD, PASSWORD_DEFAULT), 'groups' => ''] + DEFAULTELEMENTPROPS,
    		'2' => ['cmd' => 'RESET', 'value' => 'Charlie'] + DEFAULTELEMENTPROPS,
		'3' => ['cmd' => 'RESET', 'value' => ''] + DEFAULTELEMENTPROPS,
		'4' => ['cmd' => 'RESET', 'value' => ''] + DEFAULTELEMENTPROPS,
		'5' => ['cmd' => 'RESET', 'value' => 'Administrator'] + DEFAULTELEMENTPROPS,
		'6' => ['cmd' => 'RESET', 'value' => 'User customization', 'dialog' => defaultCustomizationDialogJSON()] + DEFAULTELEMENTPROPS];
     AddObject($db, $client, $output);

     //------------------------------------------Create default OD 'Logs'------------------------------------------
     initNewODDialogElements();
     $newProperties['element1']['data'] = 'Logs';
     $newPermissions['element6']['data'] = $newPermissions['element8']['data'] = $newPermissions['element12']['data'] = ALLOWEDLIST;
     $logOD = ['title'  => 'Edit Object Database Structure', 'dialog'  => ['Database' => ['Properties' => $newProperties], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => SAVECANCEL, 'flags'  => ['style' => 'width: 760px; height: 720px;', 'esc' => '', 'padprofilehead' => ['Element' => "Select element", 'View' => "Select view", 'Rule' => "Select rule"]]];
     $logOD['buttons']['SAVE']['call'] = 'Edit Database Structure';
     $logOD['dialog']['Element']['New element']['element1']['id'] = '2';
     $logOD['dialog']['View']['New view']['element1']['id'] = '2';

     $newView['element1']['id'] = '1';
     $newView['element1']['data'] = 'All logs';
     $newView['element6']['data'] = '{"eid":"id", "oid":"2", "x":"0", "y":"0"}'."\n".'{"eid":"id", "x":"0", "y":"n+1"}'."\n".'{"eid":"datetime", "oid":"2", "x":"1", "y":"0"}'."\n".'{"eid":"datetime", "x":"1", "y":"n+1"}'."\n".'{"eid":"1", "oid":"2", "x":"2", "y":"0"}'."\n".'{"eid":"1", "x":"2", "y":"n+1"}';
     $newView['element9']['data'] = ALLOWEDLIST;
     $newView['element10']['data'] = 'root';
     $logOD['dialog']['View']['All logs'] = $newView;

     $newElement['element1']['id'] = '1';
     $newElement['element1']['data'] = '            Log message            ';
     $newElement['element2']['data'] = '';
     $newElement['element3']['data'] = 'unique';
     $newElement['element3']['readonly'] = '';
     $logOD['dialog']['Element']['Log message (id1)'] = $newElement;

     $query = $db->prepare("INSERT INTO `$` (odname,odprops) VALUES ('Logs',:odprops)");
     $query->execute([':odprops' => json_encode($logOD)]);

     // Create Object Database (uniq instance)
     $query = $db->prepare("CREATE TABLE `uniq_2` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) AUTO_INCREMENT=".strval(STARTOBJECTID)." ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
     $query->execute();

     // Create Object Database (actual data instance)
     $query = $db->prepare("CREATE TABLE `data_2` (id MEDIUMINT NOT NULL, mask TEXT, lastversion BOOL DEFAULT 1, version MEDIUMINT NOT NULL, owner CHAR(64), datetime DATETIME DEFAULT NOW(), eid1 JSON, PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
     $query->execute();
     $query = $db->prepare("ALTER TABLE `data_2` ADD INDEX (`lastversion`)");
     $query->execute();
    }

catch (PDOException $e)
    {
     echo 'Failed to reset default sql tables: '."\n".$e->getMessage();
     exit;
    }

echo 'All sql tables reset to default successfully!'."\n";
