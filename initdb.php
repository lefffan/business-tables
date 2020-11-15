<?php

require_once 'eroc.php';

global $newProperties, $newPermissions, $newElement, $newView, $newRule, $odid, $allElementsArray, $uniqElementsArray, $output;

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
$newElement['element4']['data'] = 'user.php';
$newElement['element5']['data'] = '{"event":"INIT"}'."\n".'{"event": "DBLCLICK", "account": {"prop": "value"}, "odaddperm": {"prop": "odaddperm"}, "groups": {"prop": "groups"} }';
$userOD['dialog']['Element']['User - element id1'] = $newElement;

$newElement['element1']['data'] = 'Name';
$newElement['element2']['data'] = '';
$newElement['element3']['data'] = 'unique';
$newElement['element3']['readonly'] = '';
$newElement['element4']['data'] = 'text.php';
$newElement['element5']['data'] = '{"event":"INIT"}'."\n".'{"event": "DBLCLICK"}'."\n".'{"event": "KEYPRESS"}';
$userOD['dialog']['Element']['Name - element id2'] = $newElement;

$newElement['element1']['data'] = 'Telephone';
$newElement['element2']['data'] = '';
$newElement['element3']['data'] = 'unique';
$newElement['element3']['readonly'] = '';
$newElement['element4']['data'] = 'text.php';
$newElement['element5']['data'] = '{"event":"INIT"}'."\n".'{"event": "DBLCLICK"}'."\n".'{"event": "KEYPRESS"}';
$userOD['dialog']['Element']['Telephone - element id3'] = $newElement;

$newElement['element1']['data'] = 'Email';
$newElement['element2']['data'] = '';
$newElement['element3']['data'] = 'unique';
$newElement['element3']['readonly'] = '';
$newElement['element4']['data'] = 'text.php';
$newElement['element5']['data'] = '{"event":"INIT"}'."\n".'{"event": "DBLCLICK"}'."\n".'{"event": "KEYPRESS"}';
$userOD['dialog']['Element']['Email - element id4'] = $newElement;

$newElement['element1']['data'] = 'Comment';
$newElement['element2']['data'] = '';
$newElement['element3']['data'] = 'unique';
$newElement['element3']['readonly'] = '';
$newElement['element4']['data'] = 'text.php';
$newElement['element5']['data'] = '{"event":"INIT"}'."\n".'{"event": "DBLCLICK"}'."\n".'{"event": "KEYPRESS"}';
$userOD['dialog']['Element']['Comment - element id5'] = $newElement;

$newElement['element1']['data'] = 'Customization';
$newElement['element2']['data'] = "\nDouble click appropriate cell to change color, font, background and other properties for the specified user";
$newElement['element3']['data'] = 'unique';
$newElement['element3']['readonly'] = '';
$newElement['element4']['data'] = 'customization.php';
$newElement['element5']['data'] = '{"event":"INIT"}'."\n".'{"event": "DBLCLICK", "dialog": {"prop": "dialog"}}';
$userOD['dialog']['Element']['Customization - element id6'] = $newElement;

$query = $db->prepare("INSERT INTO `$` (odname,odprops) VALUES ('Users',:odprops)");
$query->execute([':odprops' => json_encode($userOD)]);

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
