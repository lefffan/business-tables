<?php

// Be aware of removing old ODs by using that script

require_once 'core.php';

// Old shit should be dropped
$query = $db->prepare("drop database ".DATABASENAME."; create database ".DATABASENAME."; use ".DATABASENAME);
$query->execute();

// Create OD list data sql table
$query = $db->prepare("CREATE TABLE `$` (id MEDIUMINT NOT NULL AUTO_INCREMENT, odname CHAR(64) NOT NULL, odprops JSON, UNIQUE(odname), PRIMARY KEY (id)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$query->execute();
 
// Create default OD 'Users'
initNewODDialogElements();
$newProperties['element1']['data'] = 'Users';
$userOD = ['title'  => 'New Object Database', 'dialog'  => ['Database' => ['Properties' => $newProperties, 'Permissions' => $newPermissions], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => ['SAVE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['cmd' => 'Edit Database Structure', 'style' => 'width: 760px; height: 720px;', 'esc' => '', 'display_single_profile' => '']];

$newView['element1']['data'] = 'All users';
$userOD['dialog']['View']['All users'] = $newView;

$newElement['element1']['data'] = 'User';
$newElement['element2']['data'] = "\nDouble click the username to change the password and other user properties";
$newElement['element3']['data'] = UNIQELEMENTTYPE;
$newElement['element3']['readonly'] = '';
$json1 = '{"prop": "value"}';
$json2 = '{"prop": "odaddperm"}';
$json3 = '{"prop": "groups"}';
$newElement['element4']['data'] = "/usr/local/bin/php /usr/local/apache2/htdocs/handlers/user.php DBLCLICK $json1 $json2 $json3";
$newElement['element5']['data'] = '';
$newElement['element6']['data'] = 'php /usr/local/apache2/htdocs/handlers/user.php INIT <data>';
$userOD['dialog']['Element']['User - element id1'] = $newElement;

$newElement['element1']['data'] = 'Name';
$newElement['element2']['data'] = '';
$newElement['element3']['data'] = 'unique';
$newElement['element3']['readonly'] = '';
$newElement['element4']['data'] = 'php /usr/local/apache2/htdocs/handlers/text.php DBLCLICK';
$newElement['element5']['data'] = 'php /usr/local/apache2/htdocs/handlers/text.php KEYPRESS <data>';
$newElement['element6']['data'] = 'php /usr/local/apache2/htdocs/handlers/text.php INIT <data>';
$userOD['dialog']['Element']['Name - element id2'] = $newElement;

$newElement['element1']['data'] = 'Telephone';
$newElement['element2']['data'] = '';
$newElement['element3']['data'] = 'unique';
$newElement['element3']['readonly'] = '';
$newElement['element4']['data'] = 'php /usr/local/apache2/htdocs/handlers/text.php DBLCLICK';
$newElement['element5']['data'] = 'php /usr/local/apache2/htdocs/handlers/text.php KEYPRESS <data>';
$newElement['element6']['data'] = 'php /usr/local/apache2/htdocs/handlers/text.php INIT <data>';
$userOD['dialog']['Element']['Telephone - element id3'] = $newElement;

$newElement['element1']['data'] = 'Email';
$newElement['element2']['data'] = '';
$newElement['element3']['data'] = 'unique';
$newElement['element3']['readonly'] = '';
$newElement['element4']['data'] = 'php /usr/local/apache2/htdocs/handlers/text.php DBLCLICK';
$newElement['element5']['data'] = 'php /usr/local/apache2/htdocs/handlers/text.php KEYPRESS <data>';
$newElement['element6']['data'] = 'php /usr/local/apache2/htdocs/handlers/text.php INIT <data>';
$userOD['dialog']['Element']['Email - element id4'] = $newElement;

$newElement['element1']['data'] = 'Comment';
$newElement['element2']['data'] = '';
$newElement['element3']['data'] = 'unique';
$newElement['element3']['readonly'] = '';
$newElement['element4']['data'] = 'php /usr/local/apache2/htdocs/handlers/text.php DBLCLICK';
$newElement['element5']['data'] = 'php /usr/local/apache2/htdocs/handlers/text.php KEYPRESS <data>';
$newElement['element6']['data'] = 'php /usr/local/apache2/htdocs/handlers/text.php INIT <data>';
$userOD['dialog']['Element']['Comment - element id5'] = $newElement;

$newElement['element1']['data'] = 'Customization';
$newElement['element2']['data'] = "\nDouble click appropriate cell to change color, font, background and other properties for the specified user";
$newElement['element3']['data'] = 'unique';
$newElement['element3']['readonly'] = '';
$json = '{"prop": "dialog"}';
$newElement['element4']['data'] = "php /usr/local/apache2/htdocs/handlers/customization.php DBLCLICK $json";
$newElement['element5']['data'] = '';
$newElement['element6']['data'] = 'php /usr/local/apache2/htdocs/handlers/customization.php INIT';
$userOD['dialog']['Element']['Customization - element id6'] = $newElement;

$query = $db->prepare("INSERT INTO `$` (odname,odprops) VALUES ('Users',:odprops)");
$query->execute([':odprops' => json_encode($userOD)]);
$query->closeCursor();

// Create Object Database (uniq instance)
$query = $db->prepare("create table `uniq_1` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) AUTO_INCREMENT=".strval(STARTOBJECTID)." ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$query->execute();
$query = $db->prepare("ALTER TABLE `uniq_1` ADD eid1 BLOB(65535), ADD UNIQUE(eid1(".USERSTRINGMAXCHAR."))");
$query->execute();
 
// Create Object Database (actual data instance)
$query = $db->prepare("create table `data_1` (id MEDIUMINT NOT NULL, lastversion BOOL DEFAULT 1, version MEDIUMINT NOT NULL, owner CHAR(64), datetime DATETIME DEFAULT NOW(), eid1 JSON, eid2 JSON, eid3 JSON, eid4 JSON, eid5 JSON, eid6 JSON, PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$query->execute();

// Insert two objects (two users) to OD with id=1
$odid = '1';
$allElementsArray = ['1' => '', '2' => '', '3' => '', '4' => '', '5' => '', '6' => ''];
$uniqElementsArray = ['1' => ''];
$output = ['1' => ['cmd' => 'RESET', 'value' => 'system'], '5' => ['cmd' => 'RESET', 'value' => 'System account']];
InsertObject($db, 'system');

$output = ['1' => ['cmd' => 'RESET', 'value' => DEFAULTUSER, 'odaddperm' => '+Allow user to add Object Databases|', 'password' => password_hash(DEFAULTPASSWORD, PASSWORD_DEFAULT), 'groups' => ''], '2' => ['cmd' => 'RESET', 'value' => 'Charlie'], '5' => ['cmd' => 'RESET', 'value' => 'Administrator'], '6' => ['cmd' => 'RESET', 'value' => 'User customization', 'dialog' => defaultCustomizationDialogJSON()]];
InsertObject($db, 'system');

// Create default OD 'Logs'
initNewODDialogElements();
$newProperties['element1']['data'] = 'Logs';
$newPermissions['element1']['data'] = $newPermissions['element3']['data'] = $newPermissions['element7']['data'] = '+allowed list (disallowed for others)|disallowed list (allowed for others)|';
$logOD = ['title'  => 'New Object Database', 'dialog'  => ['Database' => ['Properties' => $newProperties, 'Permissions' => $newPermissions], 'Element' => ['New element' => $newElement], 'View' => ['New view' => $newView], 'Rule' => ['New rule' => $newRule]], 'buttons' => ['SAVE' => ' ', 'CANCEL' => 'background-color: red;'], 'flags'  => ['cmd' => 'Edit Database Structure', 'style' => 'width: 760px; height: 720px;', 'esc' => '', 'display_single_profile' => '']];

$newView['element1']['data'] = 'All logs';
$newView['element5']['data'] = '{"eid":"id", "oid":"2", "x":"0", "y":"0"}'."\n".'{"eid":"id", "x":"0", "y":"n+1"}'."\n".'{"eid":"datetime", "oid":"2", "x":"1", "y":"0"}'."\n".'{"eid":"datetime", "x":"1", "y":"n+1"}'."\n".'{"eid":"1", "oid":"2", "x":"2", "y":"0"}'."\n".'{"eid":"1", "x":"2", "y":"n+1"}'."\n".'{"eid":"2", "oid":"2", "x":"3", "y":"0"}'."\n".'{"eid":"2", "x":"3", "y":"n+1"}';
$newView['element8']['data'] = '+allowed list (disallowed for others)|disallowed list (allowed for others)|';
$logOD['dialog']['View']['All logs'] = $newView;

$newElement['element1']['data'] = 'Message type';
$newElement['element2']['data'] = '';
$newElement['element3']['data'] = 'unique';
$newElement['element3']['readonly'] = '';
$logOD['dialog']['Element']['Message text - element id1'] = $newElement;

$newElement['element1']['data'] = 'Message text';
$newElement['element2']['data'] = '';
$newElement['element3']['data'] = 'unique';
$newElement['element3']['readonly'] = '';
$logOD['dialog']['Element']['Message type - element id2'] = $newElement;

$query = $db->prepare("INSERT INTO `$` (odname,odprops) VALUES ('Logs',:odprops)");
$query->execute([':odprops' => json_encode($logOD)]);

// Create Object Database (uniq instance)
$query = $db->prepare("create table `uniq_2` (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) AUTO_INCREMENT=".strval(STARTOBJECTID)." ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$query->execute();
 
// Create Object Database (actual data instance)
$query = $db->prepare("create table `data_2` (id MEDIUMINT NOT NULL, lastversion BOOL DEFAULT 1, version MEDIUMINT NOT NULL, owner CHAR(64), datetime DATETIME DEFAULT NOW(), eid1 JSON, eid2 JSON, PRIMARY KEY (id, version)) ENGINE InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$query->execute();
