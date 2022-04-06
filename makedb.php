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
     $newProperties['element2']['data'] = 'Application users database';
     $newProperties['element6']['data'] = $newProperties['element8']['data'] = "+User/groups allowed list to change 'Database' section|Disallowed list (allowed for others)|";
     $newProperties['element7']['data'] = $newProperties['element9']['data'] = 'root';
     $userOD = ['title'  => 'Edit Object Database Structure', 'dialog' => ['Database' => ['Properties' => $newProperties], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => SAVECANCEL, 'flags'  => ['style' => 'width: 760px; height: 720px;', 'esc' => '', 'profilehead' => ['Element' => "Select element", 'View' => "Select view", 'Rule' => "Select rule"]]];
     $userOD['buttons']['SAVE']['call'] = 'Database Configuration';
     $userOD['dialog']['Element']['New element']['element1']['id'] = '7';
     $userOD['dialog']['View']['New view']['element1']['id'] = '2';

     $newView['element1']['id'] = '1';
     $newView['element1']['head'] = "View (id$1) name";
     $newView['element1']['data'] = 'All users';
     $newView['element6']['data'] = ' *';
     $userOD['dialog']['View']['All users'] = $newView;

     $newElement['element1']['id'] = '1';
     $newElement['element1']['data'] = 'User';
     $newElement['element2']['data'] = "Double click the username to change the password and other user properties";
     $newElement['element3']['data'] = UNIQELEMENTTYPE;
     $newElement['element3']['readonly'] = '';
     $newElement['element4']['data'] = PHPBINARY.' '.HANDLERDIR.'user.php <event> <data>';
     $newElement['element5']['data'] = PHPBINARY.' '.HANDLERDIR.'user.php <event> <{}> <{"prop":"odaddperm"}> <{"prop":"groups"}> <user> <{"prop":"odvisible"}> <{"prop":"odvisiblelist"}> <{"prop":"odwrite"}> <{"prop":"odwritelist"}>';
     $newElement['element9']['data'] = PHPBINARY.' '.HANDLERDIR.'user.php <event> <{}> <{"prop":"odaddperm"}> <{"prop":"groups"}> <user> <{"prop":"odvisible"}> <{"prop":"odvisiblelist"}> <{"prop":"odwrite"}> <{"prop":"odwritelist"}>';
     //$newElement['element9']['data'] = PHPBINARY.' '.HANDLERDIR.'user.php <event> <{}> <{"prop":"odaddperm"}> <{"prop":"groups"}> <user>';
     $newElement['element12']['data'] = PHPBINARY.' '.HANDLERDIR.'user.php <event> <data>';
     $userOD['dialog']['Element']['User (id1)'] = $newElement;

     initNewODDialogElements();
     $newElement['element1']['id'] = '2';
     $newElement['element1']['data'] = 'Name';
     $newElement['element2']['data'] = '';
     $newElement['element3']['data'] = 'unique';
     $newElement['element3']['readonly'] = '';
     $newElement['element4']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element5']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php <event> <data>';
     $newElement['element6']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT <data>';
     $newElement['element7']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SETPROP link <{"prop":"link"}> hint <{"prop":"hint"}> style <{"prop":"style"}>';
     $newElement['element8']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET';
     $newElement['element9']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT';
     $newElement['element10']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php GALLERY';
     $newElement['element11']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element12']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php <event> <data>';
     $userOD['dialog']['Element']['Name (id2)'] = $newElement;

     initNewODDialogElements();
     $newElement['element1']['id'] = '3';
     $newElement['element1']['data'] = 'Contact';
     $newElement['element2']['data'] = '';
     $newElement['element3']['data'] = 'unique';
     $newElement['element3']['readonly'] = '';
     $newElement['element4']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element5']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php <event> <data>';
     $newElement['element6']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT <data>';
     $newElement['element7']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SETPROP link <{"prop":"link"}> hint <{"prop":"hint"}> style <{"prop":"style"}>';
     $newElement['element8']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET';
     $newElement['element9']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT';
     $newElement['element10']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php GALLERY';
     $newElement['element11']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element12']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php <event> <data>';
     $userOD['dialog']['Element']['Contact (id3)'] = $newElement;

     initNewODDialogElements();
     $newElement['element1']['id'] = '4';
     $newElement['element1']['data'] = 'Email';
     $newElement['element2']['data'] = '';
     $newElement['element3']['data'] = 'unique';
     $newElement['element3']['readonly'] = '';
     $newElement['element4']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element5']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php <event> <data>';
     $newElement['element6']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT <data>';
     $newElement['element7']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SETPROP link <{"prop":"link"}> hint <{"prop":"hint"}> style <{"prop":"style"}>';
     $newElement['element8']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET';
     $newElement['element9']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT';
     $newElement['element10']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php GALLERY';
     $newElement['element11']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element12']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php <event> <data>';
     $userOD['dialog']['Element']['Email (id4)'] = $newElement;

     initNewODDialogElements();
     $newElement['element1']['id'] = '5';
     $newElement['element1']['data'] = 'Comment';
     $newElement['element2']['data'] = '';
     $newElement['element3']['data'] = 'unique';
     $newElement['element3']['readonly'] = '';
     $newElement['element4']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element5']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php <event> <data>';
     $newElement['element6']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT <data>';
     $newElement['element7']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SETPROP link <{"prop":"link"}> hint <{"prop":"hint"}> style <{"prop":"style"}>';
     $newElement['element8']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET';
     $newElement['element9']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php EDIT';
     $newElement['element10']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php GALLERY';
     $newElement['element11']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php SET <data>';
     $newElement['element12']['data'] = PHPBINARY.' '.HANDLERDIR.'text.php <event> <data>';
     $userOD['dialog']['Element']['Comment (id5)'] = $newElement;

     initNewODDialogElements();
     $newElement['element1']['id'] = '6';
     $newElement['element1']['data'] = 'Customization';
     $newElement['element2']['data'] = "Double click appropriate cell to change color, font, background and other properties for the specified user";
     $newElement['element3']['data'] = 'unique';
     $newElement['element3']['readonly'] = '';
     $newElement['element4']['data'] = PHPBINARY.' '.HANDLERDIR.'customization.php <event>';
     $newElement['element5']['data'] = PHPBINARY.' '.HANDLERDIR.'customization.php <event> <{"prop": "dialog"}>';
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
     $output = ['1' => ['cmd' => 'RESET', 'value' => 'system', 'odvisible' => 'Visible DatabaseID:ViewID list for the user|+Hidden list for the user (others visible)', 'odvisiblelist' => '', 'odwrite' => 'Writable DatabaseID:ViewID list for the user|+Read-only list for the user (others writable)', 'odwritelist' => ''] + DEFAULTELEMENTPROPS,
		'2' => ['cmd' => 'RESET', 'value' => ''] + DEFAULTELEMENTPROPS,
		'3' => ['cmd' => 'RESET', 'value' => ''] + DEFAULTELEMENTPROPS,
		'4' => ['cmd' => 'RESET', 'value' => ''] + DEFAULTELEMENTPROPS,
		'5' => ['cmd' => 'RESET', 'value' => 'System account'] + DEFAULTELEMENTPROPS];
     AddObject($db, $client, $output);

     $output = ['1' => ['cmd' => 'RESET', 'value' => DEFAULTUSER, 'odaddperm' => '+Allow user to add Object Databases|', 'password' => password_hash(DEFAULTPASSWORD, PASSWORD_DEFAULT), 'groups' => '', 'odvisible' => 'Visible DatabaseID:ViewID list for the user|+Hidden list for the user (others visible)', 'odvisiblelist' => '', 'odwrite' => 'Writable DatabaseID:ViewID list for the user|+Read-only list for the user (others writable)', 'odwritelist' => ''] + DEFAULTELEMENTPROPS,
    		'2' => ['cmd' => 'RESET', 'value' => 'Charlie'] + DEFAULTELEMENTPROPS,
		'3' => ['cmd' => 'RESET', 'value' => ''] + DEFAULTELEMENTPROPS,
		'4' => ['cmd' => 'RESET', 'value' => ''] + DEFAULTELEMENTPROPS,
		'5' => ['cmd' => 'RESET', 'value' => 'Administrator'] + DEFAULTELEMENTPROPS,
		'6' => ['cmd' => 'RESET', 'value' => 'Customize', 'dialog' => defaultCustomizationDialogJSON()] + DEFAULTELEMENTPROPS];
     AddObject($db, $client, $output);

     //------------------------------------------Create default OD 'Logs'------------------------------------------
     initNewODDialogElements();
     $newProperties['element1']['data'] = 'Logs';
     $logOD = ['title'  => 'Edit Object Database Structure', 'dialog'  => ['Database' => ['Properties' => $newProperties], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => SAVECANCEL, 'flags'  => ['style' => 'width: 760px; height: 720px;', 'esc' => '', 'profilehead' => ['Element' => "Select element", 'View' => "Select view", 'Rule' => "Select rule"]]];
     $logOD['buttons']['SAVE']['call'] = 'Database Configuration';
     $logOD['dialog']['Element']['New element']['element1']['id'] = '2';
     $logOD['dialog']['View']['New view']['element1']['id'] = '2';

     $newView['element1']['id'] = '1';
     $newView['element1']['head'] = "View (id$1) name";
     $newView['element1']['data'] = 'All logs';
     $newView['element6']['data'] = 'id,datetime,1';
     $newView['element10']['data'] = '+User/groups list allowed to change this view objects|Disallowed list (allowed for others)|';
     $newView['element11']['data'] = 'root';
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
