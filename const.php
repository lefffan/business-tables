<?php

/****************************Platform dependent constants*********************************/
const DATABASENAME			= 'OE9';
const DATABASEUSER			= 'root';
const DATABASEPASS			= '123';
const PHPBINARY				= 'php';
const WRAPPERBINARY			= PHPBINARY.' wrapper.php';
const HANDLERDIR			= '/usr/local/apache2/htdocs/handlers/';
const IP				= '192.168.9.39';
const PORT				= 7889;
const WRAPPERPROCESSESCMD		= "ps axww | grep wrapper.php";
const KILLWRAPPERPROCESSESCMD		= "kill -9";

/****************************Other constants**********************************************/
const MAXOBJECTS			= 100000;
const ODSTRINGMAXCHAR			= 64;
const OVSTRINGMAXCHAR			= 64;
const USERSTRINGMAXCHAR			= '64';
const USERPASSMINLENGTH			= '8';
const SOCKETREADMAXBYTES		= 150000;
const SOCKETTIMEOUTUSEC			= 50000;
const ELEMENTDATAVALUEMAXCHAR		= 10000;
const ELEMENTPROFILENAMEMAXCHAR		= 32;
const ELEMENTPROFILENAMEADDSTRING	= ' (id';
const UNIQKEYCHARLENGTH			= 300;
const UNIQELEMENTTYPE			= '+unique|';
const NEWOBJECTID			= 1;
const TITLEOBJECTID			= 2;
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
const SESSIONLIFETIME			= 360000;
const CALLTIMEOUT			= 15;
const DEFAULTOBJECTSELECTION		= 'WHERE lastversion=1 AND version!=0';
const SERVICEELEMENTS			= ['id', 'version', 'owner', 'datetime', 'lastversion']; 
const SAVECANCEL			= ['SAVE' => ['value' => 'SAVE', 'call' => '', 'enterkey' => ''], 'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']];
const CREATECANCEL			= ['CREATE' => ['value' => 'CREATE', 'call' => '', 'enterkey' => ''], 'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']];
const OKCANCEL				= ['OK' => ['value' => 'OK', 'call' => '', 'enterkey' => ''], 'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']];
const MINBUTTONTIMERMSEC		= 500;
const MAXBUTTONTIMERMSEC		= 36000000;
const ALLOWEDLIST			= '+allowed list (disallowed for others)|disallowed list (allowed for others)|';   
const DISALLOWEDLIST			= 'allowed list (disallowed for others)|+disallowed list (allowed for others)|';
