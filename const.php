<?php

/****************************Platform dependent constants*********************************/
const DATABASENAME			= 'OE9';
const DATABASEUSER			= 'tabel';
const DATABASEPASS			= '17MAy2001#';
const PHPBINARY				= 'php';
const WRAPPERBINARY			= PHPBINARY.' wrapper.php';
const SCHEDULERBINARY			= 'scheduler.php';
const HANDLERDIR			= '/usr/local/src/tabels/handlers/';
const APPDIR				= '/usr/local/src/tabels/';
const IP				= '195.208.152.8';
const PORT				= 7889;
const WRAPPERPROCESSESCMD		= "ps axww | grep wrapper.php";
const KILLWRAPPERPROCESSESCMD		= "kill -9";
const UPLOADDIR				= '/usr/local/lib/tabels/';
const MAXFILESIZE			= 157286400;
const MAXFILEUPLOADS			= 20;

/****************************Other constants**********************************************/
const MAXOBJECTS			= 100000;
const ODSTRINGMAXCHAR			= 64;
const OVSTRINGMAXCHAR			= 64;
const USERSTRINGMAXCHAR			= '64';
const USERPASSMINLENGTH			= '8';
const SOCKETREADMAXBYTES		= 15000000;
const SOCKETTIMEOUTUSEC			= 200000;
const ELEMENTDATAVALUEMAXCHAR		= 10240;
const ELEMENTPROFILENAMEMAXCHAR		= 32;
const ELEMENTPROFILENAMEADDSTRING	= ' (id';
const UNIQKEYCHARLENGTH			= 300;
const UNIQELEMENTTYPE			= '+unique|';
const TITLEOBJECTID			= 1;
const NEWOBJECTID			= 2;
const STARTOBJECTID			= 3;
const CHECK_OD_OV			= 0b00000001;
const GET_ELEMENTS			= 0b00000010;
const GET_VIEWS				= 0b00000100;
const CHECK_DATA			= 0b00001000;
const CHECK_OID				= 0b00010000;
const CHECK_EID				= 0b00100000;
const CHECK_ACCESS			= 0b01000000;
const DEFAULTUSER			= 'root';
const DEFAULTPASSWORD			= 'root';
const SESSIONLIFETIME			= 36000;
const CALLTIMEOUT			= 15;
const CALLFILEMNGTTIMEOUT		= 300;
const DEFAULTOBJECTSELECTION		= 'WHERE lastversion=1 AND version!=0';
const SERVICEELEMENTS			= ['id', 'version', 'owner', 'datetime', 'lastversion'];
const SERVICEELEMENTTITLES		= ['Id', 'Version', 'Owner', '   Date and time   ', 'Actual version'];
const SERVICEELEMENTHINTS		= ['Object identificator', 'Object version number', 'User created current object version', 'Date and time object version was created', 'Actual object version status'];
const SAVECANCEL			= ['SAVE' => ['value' => 'SAVE', 'call' => '', 'enterkey' => ''], 'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']];
const CREATECANCEL			= ['CREATE' => ['value' => 'CREATE', 'call' => '', 'enterkey' => ''], 'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']];
const OKCANCEL				= ['OK' => ['value' => 'OK', 'call' => '', 'enterkey' => ''], 'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']];
const MINBUTTONTIMERMSEC		= 500;
const MAXBUTTONTIMERMSEC		= 36000000;
const DEFAULTELEMENTPROPS		= ['value' => '', 'hint' => '', 'link' => '', 'style' => ''];
const ARGRECURSIONNUM			= 3;
const ARGRESULTLIMITNUM			= 256;
const USEREVENTCODES			= ['KeyA','KeyB','KeyC','KeyD','KeyE','KeyF','KeyG','KeyH','KeyI','KeyJ','KeyK','KeyL','KeyM','KeyN','KeyO','KeyP','KeyQ','KeyR','KeyS','KeyT','KeyU','KeyV','KeyW','KeyX','KeyY','KeyZ','Digit0','Digit1','Digit2','Digit3','Digit4','Digit5','Digit6','Digit7','Digit8','Digit9','F1','F2','F3','F4','F5','F6','F7','F8','F9','F10','F11','F12','Space','Insert','Delete','BracketLeft','BracketRight'];
