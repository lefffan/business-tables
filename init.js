
const TABLE_MAX_CELLS = 300000;
const TITLEOBJECTID = 1;
const NEWOBJECTID = 2;
const STARTOBJECTID = 3;
const DEFAULTOBJECTSPERPAGE = 50;
const range = document.createRange();   
const selection = window.getSelection();
const GREYITEM = '<div class="contextmenuItems greyContextMenuItem">';
const ACTIVEITEM = '<div class="contextmenuItems">';
const BASECONTEXT = ACTIVEITEM + 'Task Manager</div>' + ACTIVEITEM + 'Help</div>';
const CONTEXTITEMUSERNAMEMAXCHAR = 12;
const SOCKETADDR = 'wss://tabels.app:7889';
const EFFECTHELP = "effect appearance. Possible values:<br>'fade', 'grow', 'slideleft', 'slideright', 'slideup', 'slidedown', 'fall', 'rise' and 'none'.<br>Undefined or empty value - 'none' effect is used (no effect)."
const NOTARGETUIPROFILEPROPS = ['Editable content apply input key combination', 'target', 'effect' , 'filter', 'Force to use next user customization (empty or non-existent user - option is ignored)', 'mouseover hint timer in msec', 'object element value max chars', 'object element title max chars'];
const SPACELETTERSDIGITSRANGE = [65,90,48,57,96,107,109,111,186,192,219,222,32,32,59,59,61,61,173,173,226,226];
const HTMLSPECIALCHARS = ['&amp;', '&lt;', '&gt;', '<br>', '&nbsp;'];
const HTMLUSUALCHARS = ['&', '<', '>', '\n', ' '];
const SERVICEELEMENTS = ['id', 'version', 'owner', 'datetime', 'lastversion'];
const MAXFILESIZE = 157286400;
const MAXFILEUPLOADS = 20;
const HELPHEADSTYLE = 'font-family: monospace, sans-serif; -webkit-user-select: text; -moz-user-select: text; -ms-user-select: text; user-select: text;';

let viewindex = -1, viewhistory = [];
let EDITABLE = 'plaintext-only';
let NOTEDITABLE = 'false';
let selectExpandedDiv = null, boxDiv, expandedDiv, contextmenu, contextmenuDiv, hint, hintDiv, mainDiv, sidebarDiv, mainTablediv;
let loadTimerId, tooltipTimerId, buttonTimerId, searchTimerId, undefinedcellRuleIndex, socket;
let mainTable, mainTableWidth, mainTableHeight, objectTable, paramsOV;
let objectsOnThePage, VirtualElements;
let user = '', cmd = '', OD = '', OV = '', ODid = '', OVid = '', OVtype = '';
let undefinedcellclass, titlecellclass, newobjectcellclass, datacellclass;
let box = null, sidebar = {}, cursor = {}, oldcursor = {}, drag = {};
let browse, imgwrapper, img, gallery, imgdesc, canvas;
let uiProfile = {
		  // Body
		  "application": { "target": "body", "background-color": "#343E54;", "Force to use next user customization (empty or non-existent user - option is ignored)": "", "Editable content apply input key combination": "Ctrl+Enter", "_Editable content apply input key combination": "Available options: 'Ctrl+Enter', 'Alt+Enter', 'Shift+Enter' and 'Enter'.<br>Any other values do set no way to apply content editable changes by key combination." },
		  // Sidebar
		  "sidebar": { "target": ".sidebar", "border": "none;", "background-color": "rgb(16,91,160);", "border-radius": "5px;", "color": "#9FBDDF;", "width": "13%;", "height": "90%;", "left": "4%;", "top": "5%;", "scrollbar-color": "#1E559D #266AC4;", "scrollbar-width": "thin;", "box-shadow": "4px 4px 5px #222;" },
		  "sidebar unwrap": { "target": ".unwrap", "font-size": "70%;", "padding": "3px 8px;", "content": "", "background-repeat": "no-repeat !important;", "background-position": "center;", "background-size": "70% 70%;", "background-image": `url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M14 5L21 12M21 12L14 19M21 12L3 12' stroke='green' stroke-width='6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E%0A");` },
		  "sidebar wrap": { "target": ".wrap", "font-size": "70%;", "padding": "3px 8px;", "content": "", "background-repeat": "no-repeat !important;", "background-position": "center;", "background-size": "70% 70%;", "background-image": `url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M5 14L12 21M12 21L19 14M12 21L12 3' stroke='green' stroke-width='6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E%0A");` },
		  "sidebar item active": { "target": ".itemactive", "background-color": "#4578BF;", "color": "#FFFFFF;", "font": "1.1em Lato, Helvetica;" },
		  "sidebar item hover": { "target": ".sidebar tr:hover", "background-color": "#3568AF;", "cursor": "pointer;" },
		  "sidebar object database": { "target": ".sidebar-od", "padding": "3px 5px 3px 0px;", "margin": "0px;", "color": "", "width": "100%;", "font": "1.1em Lato, Helvetica;"  },
		  "sidebar object view": { "target": ".sidebar-ov", "padding": "2px 5px 2px 10px;", "margin": "0px;", "color": "", "font": "0.9em Lato, Helvetica;" },
		  "sidebar view changes count": { "target": ".changescount", "vertical-align": "super;", "padding": "2px 3px 2px 3px;", "color": "rgb(232,187,174);", "font": "0.6em Lato, Helvetica;", "background-color": "rgb(251,11,22);", "border-radius": "35%"},
		  // Main field
		  "main field": { "target": ".main", "border": "none;", "width": "76%;", "height": "90%;", "left": "18%;", "top": "5%;", "border-radius": "5px;", "background-color": "#EEE;", "scrollbar-color": "#CCCCCC #FFFFFF;", "box-shadow": "4px 4px 5px #111;" },
		  "main field table": { "target": "table", "margin": "0px;" },
		  "main field table title cell": { "target": ".titlecell", "padding": "10px;", "border": "1px solid #999;", "color": "black;", "background-color": "#CCC;", "font": "", "text-align": "center" },
		  "main field table newobject cell": { "target": ".newobjectcell", "padding": "10px;", "border": "1px solid #999;", "color": "black;", "background-color": "rgb(232,255,232);", "font": "", "text-align": "center" },
		  "main field table data cell": { "target": ".datacell", "padding": "10px;", "border": "1px solid #999;", "color": "black;", "background-color": "", "font": "12px/14px arial;", "text-align": "center" },
		  "main field table undefined cell": { "target": ".undefinedcell", "padding": "10px;", "border": "", "background-color": "" },
		  "main field table cursor cell": { "outline": "red solid 1px", "shadow": "0 0 5px rgba(100,0,0,0.5)", "clipboard outline": "red dashed 2px" },
		  "main field table selected cell": { "target": ".selectedcell", "background-color": "rgb(189,200,203) !important;" },
		  "main field table mouse pointer": { "target": ".main table tbody tr td:not([contenteditable=" + EDITABLE + "])", "cursor": "cell;" },
		  "main field message": { "target": ".main h1", "color": "#BBBBBB;" },
		  // Scrollbar
		  "scrollbar": { "target": "::-webkit-scrollbar", "width": "8px;", "height": "8px;" },
		  // Context Menu
		  "context menu": { "target": ".contextmenu", "width": "240px;", "background-color": "#F3F3F3;", "color": "#1166aa;", "border": "solid 1px #dfdfdf;", "box-shadow": "1px 1px 2px #cfcfcf;", "font-family": "sans-serif;", "font-size": "16px;", "font-weight": "300;", "line-height": "1.5;", "padding": "12px 0;", "effect": "rise", "_effect": "Context menu " + EFFECTHELP },
		  "context menu item": { "target": ".contextmenuItems", "margin-bottom": "4px;", "padding-left": "10px;" },
		  "context menu item cursor": { "target": ".contextmenuItems:hover:not(.greyContextMenuItem)", "cursor": "pointer;" },
		  "context menu item active": { "target": ".activeContextMenuItem", "color": "#fff;", "background-color": "#0066aa;" },
		  "context menu item grey": { "target": ".greyContextMenuItem", "color": "#dddddd;" },
		  // Hint
		  "hint": { "target": ".hint", "background-color": "#CAE4B6;", "color": "#7E5A1E;", "border": "none;", "border-radius": "3px;", "box-shadow": "2px 2px 4px #cfcfcf;", "padding": "3px;", "font": "11px sans-serif;", "effect": "hotnews", "mouseover hint timer in msec": "1000", "_effect": "Hint " + EFFECTHELP },
		  // Box interface elements
		  "dialog box": { "target": ".box", "background-color": "rgb(233,233,233);", "color": "#1166aa;", "border-radius": "5px;", "border": "solid 1px #dfdfdf;", "box-shadow": "2px 2px 4px #cfcfcf;", "effect": "slideleft", "_effect": "Dialog box " + EFFECTHELP, "filter": "grayscale(0.5)", "_filter": "Application css style filter applied to the sidebar and main field.<br>For a example: 'grayscale(0.5)' or/and 'blur(3px)'. See appropriate css documentaion." },
		  "dialog box title": { "target": ".title", "background-color": "rgb(209,209,209);", "color": "#555;", "border": "#000000;", "border-radius": "5px 5px 0 0;", "font": "bold .9em Lato, Helvetica;", "padding": "5px;" },
		  "dialog box pad": { "target": ".pad", "background-color": "rgb(223,223,223);", "border-left": "none;", "border-right": "none;", "border-top": "none;", "border-bottom": "none;", "padding": "5px;", "margin": "0;", "font": ".9em Lato, Helvetica;", "color": "#57C;", "border-radius": "5px 5px 0 0;" },
		  "dialog box active pad": { "target": ".activepad", "background-color": "rgb(209,209,209);", "border-left": "none;", "border-right": "none;", "border-top": "none;", "border-bottom": "none;", "padding": "5px;", "margin": "0;", "font": "bold .9em Lato, Helvetica;", "color": "#57C;", "border-radius": "5px 5px 0 0;" },
		  "dialog box pad bar": { "target": ".padbar", "background-color": "transparent;", "border": "none;", "padding": "4px;", "margin": "10px 0 15px 0;" },
		  "dialog box divider": { "target": ".divider", "background-color": "transparent;", "margin": "5px 10px 5px 10px;", "height": "0px;", "border-bottom": "1px solid #CCC;", "border-top-color": "transparent;", "border-left-color": "transparent;" , "border-right-color": "transparent;" },
		  "dialog box button": { "target": ".button", "background-color": "#13BB72;", "border": "none;", "padding": "10px;", "margin": "10px;", "border-radius": "5px;", "font": "bold 12px Lato, Helvetica;", "color": "white;" },
		  "dialog box button push": { "target": ".buttonpush", "transform": "translate(3%, 3%);" },
		  "dialog box button and pad hover": { "target": ".button:hover, .pad:hover", "cursor": "pointer;", "background-color": "", "color": "", "border": "" },
		  "dialog box element headers": { "target": ".element-headers", "margin": "5px 5px 5px 5px;", "font": ".9em Lato, Helvetica;", "color": "#555;", "text-shadow": "none;" },
		  "dialog box help icon": { "target": ".help-icon", "padding": "1px;", "font": ".9em Lato, Helvetica;", "color": "#555;", "background-color": "#FF0;", "border-radius": "40%;" },
		  "dialog box help icon hover": { "target": ".help-icon:hover", "padding": "1px;", "font": "bold 1em Lato, Helvetica;", "color": "black;", "background-color": "#E8E800;", "cursor": "pointer;", "border-radius": "40%;" },
		  "dialog box table": { "target": ".boxtable", "font": ".8em Lato, Helvetica;", "color": "black;", "background-color": "transparent;", "margin": "0px;", "width": "100%;", "box-sizing": "border-box;" },
		  "dialog box table cell": { "target": ".boxtablecell", "padding": "7px;", "border": "1px solid #999;", "text-align": "center" },
		  "dialog box pushable table cell hover": { "target": ".boxtablecellpush:hover", "cursor": "pointer;" },
		  //
		  "dialog box select": { "target": ".select", "background-color": "rgb(243,243,243);", "color": "#57C;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 5px 10px;", "outline": "none;", "border": "1px solid #777;", "padding": "0px 0px 0px 0px;", "overflow": "auto;", "max-height": "30em;", "scrollbar-width": "thin;", "min-width": "10em;", "width": "auto;", "display": "inline-block;", "effect": "rise", "_effect": "Select fall-down option list  " + EFFECTHELP },
		  "dialog box select option": { "target": ".select > div", "padding": "2px 20px 2px 5px;", "margin": "0px;" },
		  "dialog box select option hover": { "target": ".select:not([type*='o']) > div:hover", "background-color": "rgb(209,209,209);", "color": "" },
		  "dialog box select option selected": { "target": ".selected", "background-color": "rgb(209,209,209);", "color": "#fff;" },
		  "dialog box select option expanded": { "target": ".expanded", "margin": "0px !important;", "position": "absolute;" },
		  //
		  "dialog box radio": { "target": "input[type=radio]", "background-color": "transparent;", "border": "1px solid #777;", "font": ".8em/1 sans-serif;", "margin": "3px 5px 6px 10px;", "border-radius": "20%;", "width": "1.2em;", "height": "1.2em;" },
		  "dialog box radio checked" : { "target": "input[type=radio]:checked::after", "content": "", "color": "white;" },
		  "dialog box radio checked background" : { "target": "input[type=radio]:checked", "background-color": "#00a0df;", "border": "1px solid #00a0df;" },
		  "dialog box radio label" : { "target": "input[type=radio] + label", "color": "#57C;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 0px 0px;" },
		  //
		  "dialog box checkbox": { "target": "input[type=checkbox]", "background-color": "#f3f3f3;", "border": "1px solid #777;", "font": ".8em/1 sans-serif;", "margin": "3px 5px 6px 10px;", "border-radius": "50%;", "width": "1.2em;", "height": "1.2em;" },
		  "dialog box checkbox checked" : { "target": "input[type=checkbox]:checked::after", "content": "", "color": "white;" },
		  "dialog box checkbox checked background" : { "target": "input[type=checkbox]:checked", "background-color": "#00a0df;", "border": "1px solid #00a0df;" },
		  "dialog box checkbox label" : { "target": "input[type=checkbox] + label", "color": "#57C;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 0px 0px;" },
		  //
		  "dialog box input text": { "target": "input[type=text]", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background-color": "#f3f3f3;", "border": "1px solid #777;", "outline": "none;", "color": "#57C;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  "dialog box input password": { "target": "input[type=password]", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background-color": "#f3f3f3;", "border": "1px solid #777;", "outline": "", "color": "#57C;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  "dialog box input textarea": { "target": "textarea", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background-color": "#f3f3f3;", "border": "1px solid #777;", "outline": "", "color": "#57C;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  // Tree
		  "tree table": { "target": ".treetable", "border-spacing": "20px 0px;", "border-collapse": "separate;", "margin-top": "10px;", },
		  "tree error element": { "target": ".treeerror", "background-color": "#eb8b9c;", "border": "1px solid black;", "padding": "7px !important;", "border-radius": "5px;", "text-align": "center;", "box-shadow": "2px 2px 4px #888;", "font": "12px/14px arial;", },
		  "tree element": { "target": ".treeelement", "background-color": "#ccc;", "border": "1px solid black;", "padding": "7px !important;", "border-radius": "5px;", "text-align": "left;", "box-shadow": "2px 2px 4px #888;", "font": "12px/14px arial;", "object element value max chars": "60", "object element title max chars": "15", },
		  "tree arrow stock": { "target": ".treelinkstock", "flex-basis": "10px;", "box-sizing": "border-box;", "background-color": "rgb(17,101,176);", "border": "none;", "margin-left": "15px;", "margin-right": "15px;", "height": "60px;", },
		  "tree arrow down": { "target": ".treelinkarrowdown", "flex-basis": "20px;", "box-sizing": "border-box;", "background-color": "transparent;", "border-top": "40px solid rgb(17,101,176);", "border-bottom": "0 solid transparent;", "border-left": "20px solid transparent;", "border-right": "20px solid transparent;", },
		  "tree arrow up": { "target": ".treelinkarrowup", "flex-basis": "20px;", "box-sizing": "border-box;", "background-color": "transparent;", "border-top": "0 solid transparent;", "border-bottom": "40px solid rgb(17,101,176);", "border-left": "20px solid transparent;", "border-right": "20px solid transparent;", },
		  "tree element description": { "target": ".treelinkdescription", "display": "flex;", "flex": "1 10px;", "background-color": "transparent;", "border": "none;", "padding": "5px;", "font": "10px/11px arial;", "overflow": "hidden;" },
		  // Misc
		  "chart colors": { "Color #1": "#4CAF50", "Color #2": "#00BCD4", "Color #3": "#E91E63", "Color #4": "#FFC107", "Color #5": "#9E9E9E", "Color #6": "#FFFF00", "Color #7": "#E32DF2", "Color #8": "#BDDDFD", "Color #9": "#BCF11B", "Color #10": "#DBDBDB", "Color #11": "#343E54", "Color #12": "#1465B0" },
		  "gallery image footnote": { "target": ".imgdesc", "background-color": "transparent;", "font": "11px sans-serif;", "color": "RGB(56,124,213);" },
		  };

const style = document.createElement('style');
const help = { title: 'Help', dialog: {

"System description": { profile: { element: { line: '', style: HELPHEADSTYLE, head:
`Tabels application is a new generation system to display, store and manage its data by lots of ways. Application data
is a set of custom data tables, each table consists of identical objects, which, in turn, are set of service and user
defined elements.

Data tables of itself is called Object Database (OD) and can be changed or created by appropriate sidebar context menu.
OD contains Object Views (OV). Views define what objects (via 'object selection') and elements (via 'element layout')
should be displayed and how. See appropriate help sections.

As it was mentioned above - each object is a set of service and user-defined elements. Five built-in service elements
(id, owner, datetime, version, lastversion) represent service data, which is set automatically while object is changed
or created. Each custom user-defined element is numbered (with eid1, eid2.. as a column names in database structure)
and may have some handlers (any script or binary) to create/manage element data, see 'Handler' help section. User-defined
element data is a JSON, that may consist of any defined properties. Some properties are reserved for the special assingment:
- 'value' is displayed in a table cell as a main element text (max 10K chars)
- 'hint' is displayed as element hint text on mouse cursor cell navigation
- 'style' is a css style attribute value applied to html table <td> tag.
- 'link' is element connection list one by line, each connection is a link name, remote object selection and its element
  selection, all three divided by '|'.
- 'alert' property is reserved by the controller to send alert messages to the client side
- 'cmd' property is reserved by the controller to identify handler command
Other element properties are custom and used to store additional element data, see example below.

Lets have a look to the simple OD example with only two elements - Name and Phone number.
OD name will be 'Clients', OV name - 'Address book', OV template - 'table', OV object selection - empty (all objects),
OV element layout - empty (default). See OD configuration help section.
Additionally, first object element (Name) stores user password in a 'pass' JSON property.

OV display will look like:
+------+--------------+
| Name | Phone number |
+------+--------------+
| Mary | +1 555 11111 |
+------+--------------+
| John | +1 555 22222 |
+------+--------------+

Internal object database structure will be:
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+
| id | owner | datetime            | version | lastversion | eid1                                   | eid2                      |
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+
| 3  | root  | 1970-07-26 17:48:01 | 1       | 1           | '{"value": "Mary", "pass": "$6$WF.."}' | '{"value": "+1 555 111"}' |
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+
| 4  | root  | 1970-07-26 17:49:33 | 1       | 1           | '{"value": "John", "pass": "$6$GH.."}' | '{"value": "+1 555 222"}' |
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+

After the 1st object (Mary) phone number change and the 2nd (John) delete - the structure will be:
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+
| id | owner | datetime            | version | lastversion | eid1                                   | eid2                      |
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+
| 3  | root  | 1970-07-26 17:48:01 | 1       | 0           | '{"value": "Mary", "pass": "$6$WF.."}' | '{"value": "+1 555 111"}' |
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+
| 3  | root  | 1970-07-26 17:49:42 | 2       | 1           | '{"value": "Mary", "pass": "$6$WF.."}' | '{"value": "+1 555 333"}' |
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+
| 4  | root  | 1970-07-26 17:48:33 | 1       | 0           | '{"value": "John", "pass": "$6$GH.."}' | '{"value": "+1 555 222"}' |
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+
| 4  | root  | 1970-07-26 17:49:54 | 0       | 1           | ''                                     | ''                        |
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+

Client Mary record after phone number change by user 'root' has version 2 value and lastversion flag set, while object previous
version 1 has unset lastversion flag. Note that previous object version values for non-changed user-defined elements are set to
NULL, so Mary phone number next change creates object version 3 with previous object version 2 element 'eid1' set to NULL.
In other words - just to save some disk space object versions history consists of changed elements only.
As object versions are object data instnces - deleted objects are not removed from database, but marked by zero version only.
All previous versions object data is available in that case, but cannot be changed at all. Considering all of this, all object
history is transparent and available and all data is native. This is a global application conception - all functionality is
documented and clear. The application acts as a kind of platform the users can develop their projects with custom data behaviour
and layout.

Go on. Application authentication is password based. Usernames and their passwords are stored in 'Users' OD.
Initial username/password: root/root. Only one user instance can be logged in, so logged in instance automatically logs out
another instance via other host/browser. Session lifetime is 10 hours (36000 sec).
To add new user click context menu 'Add Object' on any 'Users' view (on default view 'All users' for example), then double 
click just-added 'user' element to call user properties (such as password, OD add permission, group membership..) dialog box.
User 'name' cannot be changed after creation, user password password must be min 8 chars length and contain at least one digit,
capital and lowercase latin letter. Also user cannot change his properties (except the password)  to avoid all priveleges
granting by himself to himself. Note that users and groups must be all uniq, so user and group with one name 'root' is not
allowed. Groups cannot be directly created, so any group name in a user group membership list is considered as an existing group.
So all user/group permission lists in a Database configuration (see appropriate help section) are treated as an user name list,
but in case of non-existent username - the name is treated as a name of a group.`
}}},

"Database Configuration": { profile: { element: { line: '', style: HELPHEADSTYLE, head:
`To create Object Database (OD) just enter its name (max 64 chars) in the dialog box called via 'New Database' sidebar context
menu. Other database configuration can be continued here or later via 'Database Configuration' sidebar context menu call.
Let's have a look at database configuration dialog box and its features.

First is 'Database' tab. This configuration section sets up database name, description and permissions. Database name can be
changed after creation or removed (via empty name and description). Database permissions represent itself five user/group
(one by line) list input text areas, first list for the object database visibility restriction, other four lists - for each
configuraion section (tab). Lists can be of two types - 'allowed' type allowes changes for specified users and groups in the
list and disallowed for others, thereby 'disallowed' type disallows changes for specified users and groups and allows for others.
Be aware of empty 'allowed' lists - the setting restricts all users, e.g. 'Database' section tab empty 'allowed' list blocks any
changes (such as name, description, permissions) for any user forever.

Second configuration section is 'Element'. Each object consists of builtin service elements and custom user elements.
To add any custom element select 'New element' profile and fill at least one field - name, description or any event handler.
So all of them in an element profile should be set empty in order to remove element. Element name is used  as a default
element header text and specified description text is displayed as a hint on object view element header navigation.
See 'Element layout' help section for details. Other element options are 'Element type' and event 'Handlers'. Unique element
type sets element JSON property 'value' unuqueness among all objects in OD.
For a example, first element (username) of builtin OD 'Users' defined as an uniq type, so duplicated names are excluded.
Next - event handlers. Handler is a command line script or binary called on specified event occur. Handlers are optional
and defined for necessary events only. Events are occured on object processes ('INIT' event at object creation, 'CHANGE'
at object element data change), keyboard/mouse element push/click ('DBLCLICK', 'KEYPRESS', 'F2', 'F12', 'INS', 'DEL') and
handler feedback ('CONFIRM', 'CONFIRMDIALOG'). See 'Handlers' help section for details.

Third configuration section - 'View'. The same way for add/delete operations is used - empty name removes the view, 'New view'
option with any name specified - creates it. View name is 64 chars max, first char '_' hides unnecessary views from user
sidebar, so the view can be called from element handlers only (see 'Handler' help section for details).
The object view (OV) of itself is a mechanism to output selective object data on specified template. Objects for the view are
obtained from the selection process (see 'Object Selection' for details), their elements - from 'Element layout' (see
appropriate help section for details).
Next view configuration is a scheduler. It is a *nix like cron service which executes specified command line for specified
element for all objects of the view. Scheduler field is an instructions list (one by line) of the general form:
'run this command at this time on this date for this element id'.
Blank lines and leading spaces and tabs are ignored. Instruction is an entry list separated by spaces with next format:
<minute 0-59> <hour 0-23> <day of month 1-31> <month 1-12> <day of week 0-7> <element id number> <command line>
First five entries are datetime parameters, sixth - element id number from 1, and the rest of the line is treated as a 
command line to execute. Datetime parameters may be set to asterisk (*), which always stands for 'first-last'. Ranges of numbers
are allowed. Ranges are two numbers separated with a hyphen. The specified range is inclusive. For example, 8-11 for an 'hours'
entry specifies execution at hours 8, 9, 10 and 11. Lists are allowed. A list is a set of numbers (or ranges) separated by
commas. Examples: '1,2,5,9', '0-4,8-12'. Step values can be used in conjunction with ranges. Following a range with '/<number>'
specifies skips of the number's value through the range. For example, '0-3/2' can be used in the hours field to specify 0,2
hours execution. Zero or 7 day of week values are Sunday.
So the cron service tries to check all view schedules of all databases each minute. In case of datetime parameters match - the
specified command line (handler) for specified element id is exexcuted for every object of the view. Next instruction or view
schedule is not checked until the previous job finished. Next example clears (by setting empty value) element2 value every
day at 3:00 (see 'Handlers' help section for args of text.php regular handler):
0 3 * * * 2 php text.php SET
And at the end - two text areas at the bottom are view permissions for read/write operations. 'Disallowed list' restricts
specified users/groups and allows others, while 'Allowed list' allows specified and restricts others.

Last configuration section is 'Rule'. Any mouse/keyboard client side event or object add/delete/change operation is passed to
the analyzer to test on all rule profiles in alphabetical order (for the specified event or/and operation) until the match is
found. Rule query is a list of SQL query strings (one by line). Non-empty and non-zero result of all query strings - match case,
any empty, error or zero char '0' result - no match.
When a match is found, the action corresponding to the matching rule profile is performed, otherwise default action 'accept'
is applied. Accept action agrees specified event or operation, while reject action cancels event or changes made by the operation.
Here some examples:
1. SELECT 'root'=':user'
2. SELECT 1 FROM :odtable WHERE id=:oid and version=:preversion and JSON_UNQUOTE(JSON_EXTRACT(eid2, '$.value'))='John'
   SELECT 1 FROM :odtable WHERE id=:oid and version=:postversion and JSON_UNQUOTE(JSON_EXTRACT(eid2, '$.value'))='Mary'
3. SELECT COUNT(version)>2 FROM data_1 WHERE id=:oid
4. SELECT owner!=':user' from :odtable where id=:oid and version=1

First example query with reject action for F2 event is called when the user presses F2 key and is true when user root presses
F2. Therefore the rule cancels F2 event for the root.
Second example with reject action for 'Delete Object' operation disallows element #2 value change from John to Mary, any other
changes, for a example, from Mary to John are applied.
Third example with reject action for 'Change Object' operation allows to change object only once. New object has one version
(instance), after object is changed at the 1st time - it has two versions, so second change will have three versions and will
be blocked (expression 'COUNT(version)>2' (3>2) is true).
And the last example with reject action for change - allows to change self-created objects only via comparing created object
(version=1) owner with the user the object is being changed by.

Query strings may have some :keys (started with the colon) which are replaced with next values:
:user - the user initiated event/operation
:preversion - object instance version number before rule apply operation/event, unusable for 'Add Object', but equals :postversion
:postversion - object instance version number after rule apply operation, unusable for events, but equals :preversion
:oid - object id number operation/event is applied to
:odtable - application object database sql table name

When a match is found the rule message is displayed on the client side dialog box, except the events with 'accept' action - they
have its own client side beahviour. Set 'Log rule message' to save it to 'Logs' database. 
To remove the rule - set its name empty, to disable the rule - uncheck all rule apply events/operations.`
}}},

"Object Selection": { profile: { element: { line: '', style: HELPHEADSTYLE, head:
`Object selection is a part of the sql query string that selects objects for the specified view. Let's have a look to the
object structure stored in database to select required objects effectively. Each object consists of next SQL table columns:
- id. Object identificator.
- lastversion. Boolean value 0 or 1 indicates whether it is last object version or not. See 'version' field.
- version. Indicates object version number started from '1', that is assinged to just created new objects.
  After object any change, controller creates a new database record with the changed object copy, increments its version
  and set lastversion flag to 1. This mechanism allows to store every object version, so user can trace object data changing
  and find out when, how and who object was changed by. Deleted objects are marked by zero version and lastversion flag set.
- owner. The user this object version (object instance) was created by.
- datetime. Date and time this object version was created at.
- eid<element id>. JSON type element data. 

Error object selection string selects no objects, empty string - all actual objects.
Controller selects objects via database query with next format:
'SELECT <element layout selection> FROM data_<OD id> <object selection>'
Default object selection (empty string) selects all relevant (lastversion=1) and non deleted objects (version!=0), so
selection string 'WHERE lastversion=1 AND version!=0' is applied and result query is:
'SELECT .. FROM data_<OD id> WHERE lastversion=1 AND version!=0'

This format together with object structure provides native and effective selection of object sets via powerful SQL
capabilities! To make object selection process more flexible user can use some parameters in object selection string. These
parameters should start from char ':' and finish with space, single or double qoutes, backslash or another ':'. Parsed
parameter name is set as a question (with chars '_' replaced with spaces) in client side dialog box at the object view call
open. Parameter name :user is reserved and replaced with the username the specified view is called by.

Object selection string example for 'Logs' object database:
WHERE lastversion=1 AND version!=0 AND JSON_UNQUOTE(JSON_EXTRACT(eid1, '$.value')) regexp ':Select_log_string_to_search'
The selection example displays dialog with input question 'Select log string to search'.
Parameter ':Select_log_string_to_search' is replaced by input string to build result query, so the controller
selects objects (log messages) which match specified input string regular expression. Note that JSON_EXTRACT function
extracts quoted value, so to unquote the result JSON_UNQUOTE function is used.

Another object selection option is a link name. The option of itself represents one or multiple names divided
by '|' or '/'. With that option specified the selection process takes only first selected/found object (others are
ignored) and builds the tree (based on object elements 'link' property matched link-names) from that head object. Result
selection is that tree object list. The tree for link names divided by '|' is built on all specified names, while for
names divided by '/' - only for the first matched per object. Only one delimiter can be used for the view, so name list
'name1|name2/name3' will be divided into two names: name1 and 'name2/name3'.
See 'Element layout' help section for the tree template.`
}}},

"Element layout": { profile: { element1: { line: '', style: HELPHEADSTYLE, head:
`Element layout is applied to the view (see 'View' of Database Configuration' help section) and defines what elements
should be displayed and how for the selected template. Element layout is a JSON strings list one by line. JSON format depends
on the selected template.
Let's first consider 'Table' template - it is the main way to output and manage OD data. Table emplate element layout
allows to format data many different ways - from classic tables to public chats, see examples below and 'Examples' help
section. Each layout JSON for a table template defines object element and its behaviour - such as table cell position, style
attribute, event and etc, for a example - '{"oid": "..", "eid": "..", "x": "..", "y": ".."}'. JSON possible properties are:

- 'oid'. Property 'oid' defines object id and can take next values:
	exact object id number (starts from 1, where ${TITLEOBJECTID} - title object, ${TITLEOBJECTID} - new object, 3.. - database objects)
	asterisk (*)
	expression (with four possible vars: o, e, n, q)
  HTML style (style), position (x, y) and other attributes are applied to the specified object element defined by oid/eid combination,
  see oid/eid table below. All actual objects in database have their unique identificators (starts from ${STARTOBJECTID}). Every database has
  two service objects: header (title) object with id ${TITLEOBJECTID} and new object (object to input text data to add new objects) with id ${NEWOBJECTID}.
  In case of exact object id number in 'oid' - attributes (x, y, style..) are applied to that specified object id with the highest
  priority as more specific. Attributes of JSON with asterisk (*) 'oid' are applied to all objects of the selection (see 'Object
  Selection' help section) with lower priority. All other 'oid' values are treated as a javascript expressions. True expressions match
  the object, false expressions - doesn't. JSONs properties with 'oid' as an expression are applied with the lowest priority.
  Expression example: "o%2===1" matches objects with odd identificators, so JSON
  '{"eid":"*", "style":"background-color: #000;", "oid":"o%2===1 && o>2"}' will paint all odd (o%2===1) and actual (o>2) objects with
  the black background color. Expressions may contain next vars:
	'o' is an object id number in the selection
	'e' is element id number (undefined for service elements)
	'n' is object number in the selection (for the first object n=0, for the second n=1 and so on)
	'q' is a total object count.
  Empty 'oid' property ("oid": "") defines 'style' and 'hiderow' attributes for undefined cell that has no object element placed
  (via x, y coordinates) in. Property 'eid' is ignored.
  Unset 'oid' property defines virtual element or html tag <table> attribute list. Property 'eid' is also ignored.
  Virtual elements are not stored in object database and have its own value stored in JSON 'value' property that is treated as a
  clear text except the cases started from 'SELECT ' string. In that case 'value' text is an SQL statement that is executed to retreive
  the data to be used as a virtual element value. In case of a error 'value' property remains unchanged. Virtual elements are useful to
  output some total/summary data ('SELECT SUM|COUNT|AVG.. FROM data_<OD id>..') to build related graphs and charts.
  Well, JSON with unset 'oid' is treated as a virtual element, but with unset x, y or value all JSON properties are treated as HTML
  table tag attributes, see some layouts in 'Examples' help section. Besides table attributes 'direction' word ca be used as a property
  to set table rotation. Possible values: 90, 180 and 270. These are the angles the HTML table should be rotate at. Unknown 'direction'
  value makes no effect.
- 'eid'. Property 'eid' is an element id and can take next values:
	exact element id number (starts from 1)
	service element names (id, version, owner, datetime, lastversion)
	asterisk (*)
  Similar to 'oid' property 'eid' defines exact element (via its identificator or name) or all elements (*) of the specified object
  x, y, style and other properties should be applied to.
- 'x','y'. Object element position on HTML table is defined by table cell x,y coordinates. These properties are arithmetic expressions
  that may include four variables (see 'oid' property description). For a example, "y": "n+1" will place first object in the selection
  with n=0 to the second row (y=1), second object (n=1) - to the third row (y=2) and so on (note that column/row numeration starts
  from 0). Properties are mandatory. See layout examples below.
- 'event'. Mouse double click (DBLCLICK), key press (F2, F12, INS, DEL, KEYPRESS) or chart (CHART) events to emulate at OV open.
  Symbol key push event 'KEYPRESS' should be specified with the additional string to be passed to the handler as an input arg.
  For example, "event": "KEYPRESSa" will emulate key 'a' pushed at the view open. See 'Handlers' help section for details.
  Chart event emulates context menu 'Chart' call of the table seleceted cells. It is useful to display the chart just right after
  the view open. Chart event may have four args - "event": "CHART(0,0,3,5)". Arguments (x1,y1,x2,y2) define table selected area from
  top left to right lower corner. No args ("event": "CHART") - whole table as a selected area is used.
  Incorrect event value - no emulation, but cursor is set to the position specified by 'x','y' coordinates anyway.
  Only one event is generated at the view open, so last matched is used.
- 'hidecol'/'hiderow'. These properties collapse (hide) table columns/rows containing at least one cell with 'hidecol'/'hiderow'
  attribute value. Strict comparison is used. For example, JSON '{"eid": "1", "oid": "*", "hiderow": ""}' will hide all table rows
  containing empty cell ("") of any object element id#1.
  For undefined cell (see 'oid' empty case above) property 'hidecol' is not supported, while 'hiderow' with any value collapses
  the row only in case of all undefined cells in a row.
- 'style'. HTML css style attribute  for <td> tag the specified object element (or virtual element) is placed in. See appropriate
  css documentation.
- 'value'. Table cell element main text.
- 'hint'. Table cell element hint displayed as a hint on a table cell mouse cursor navigation.

As it was mentioned above element layout is a JSON list. But for the convenience it is possible to use comma separated element list
instead of JSON. Element list is extracted to the JSONs anyway and places elements one by one with the first row as a title and second
row (in case of a line leading space) as a new object input. Also empty or all-spaces layout is treated as '*'.
Example: layout 'id,datetime,1,2' formats the table with the 1st row as a title and database objects then, where 1st column is
object id, 2nd column - object version creation timestamp, 3rd column - object elements id1 and 4th column - object elements id2.
Another example: layout ' *' is a simple table with the title at the 1st row, new object input (leading space) at the 2nd row and all
objects of the selection (starting from the 3rd row) with all (asterisk *) user-defined elements one by one starting from the first
column.

  Properties 'oid'/'eid' combinations description:
  +-----------+-------------------------+------------------+----------------------------+
  |   \\       |                         |                  |                            |
  |    \\ oid  | 1|2|3|4..|*|            |      empty       |          unset             |
  |     \\     | expression (o, e, n, q) | (eid is ignored) |      (eid is ignored)      |
  |  eid \\    |                         |                  |                            |
  +-----------+-------------------------+------------------+----------------------------+
  |           |                         |                  |                            |
  |id         |  x (o, e, n, q),        |                  | table attributes           |
  |owner      |  y (o, e, n, q),        | [style, hiderow] | and direction              |
  |datetime   |  [value,                |                  | or                         |
  |version    |  style,                 | (for undefined   | virtual elements:          |
  |lastversion|  hint,                  | cell that has    | x (n),                     |
  |1,2..      |  event,                 | no any object    | y (n),                     |
  |*          |  hidecol, hiderow]      | element in)      | value,                     |
  |           |                         |                  | [style, hint]              |
  |           |                         |                  |                            |
  +-----------+-------------------------+------------------+----------------------------+

Let's parse next element layout (OV 'All logs', OD 'Logs'): id,datetime,1.
First element in the list is 'id', it is extracted to next two JSONs:
{"eid":"id", "oid":"${TITLEOBJECTID}", "x":"0", "y":"0"}
{"eid":"id", "oid":"*", "x":"0", "y":"n+1"}
These JSONs describes 'id' service element layout:
 - title object (oid=${TITLEOBJECTID}) for 'id' element will be on the top left corner of the table (x=0, y=0).
 - each object of the selection (oid=*) is placed to the first table column (x=0) and to the row 'n+1' (y=n+1),
   where 'n' is object serial number in the selection: first object in the selection (n=0) goes to the second
   row (y=0+1=1), second object in the selection goes to the 3rd row (y=1+1=2) and so on.

Similarly for two next elements datetime and log message (eid=1). They are extracted to:
{"eid":"datetime", "oid":"${TITLEOBJECTID}", "x":"1", "y":"0"}
{"eid":"datetime", "oid":"*", "x":"1", "y":"n+1"}
{"eid":"1", "oid":"${TITLEOBJECTID}", "x":"2", "y":"0"}
{"eid":"1", "oid":"*", "x":"2", "y":"n+1"}
Column ('x' coordinate) position for datetime is x=1 (second column) and for log message is x=2 (third column).`
},

element2: { line: '', style: HELPHEADSTYLE, head: `
Next - 'Tree' template, it builds the tree from head object ('object selection' first found) to other objects based
on their element link properties. Each link property is one or multiple (one by line) connections. Each connection
has its link name, remote 'element' and remote object (tree node) 'selection' the connection links to.
All three values are divided by '|'. Connection format: <link name>|<remote element>|<remote object selection>
Remote object selection is a part of a query that calculates next object/node on the tree.
Query format: SELECT id FROM <OD> WHERE lastversion=1 AND version!=0 AND <remote object selection> LIMIT 1

Example: five objects are linked with each other via next connections:

      +-----------------------------------+
      |            object  id7            |
      |                                   |
      |             element10             |
      +-----------------------------------+
                        ^
                        |
                        |l1
                        |
      +-----------------------------------+
      |             element9              |
      |            object id6             |
      | element7                element8  |
      +-----------------------------------+
            ^                       ^
            |                       |
            |l1                     |l1
            |                       |
    +---------------+       +---------------+
    |   element4    |       |   element6    |
    |  object id4   |       |  object id5   |
    |   element3    |       |   element5    |
    +---------------+       +---------------+
            ^                       ^
            |                       |
            |l1                     |l2
            |                       |
      +-----------------------------------+
      | element1                element2  |
      |         head object id3           |
      |                                   |
      +-----------------------------------+

Head object3 has two routes to object7, first route - via object4, second - via object5.
Let's create some views to display first route, second route and both routes.
First view properties:
- 'Name' = 'Main route'
- 'Template' = 'Tree'
- 'Object selection' = 'WHERE id=3' (head object #3 selection)
- 'Link name' = 'l1'
Second view properties:
- 'Name' = 'Alternative route'
- 'Template' = 'Tree'
- 'Object selection' = 'WHERE id=3' (head object #3 selection)
- 'Link name' = 'l2/l1'
Third view properties:
- 'Name' = 'Main and alternative routes'
- 'Template' = 'Tree'
- 'Object selection' = 'WHERE id=3' (head object #3 selection)
- 'Link name' = 'l1|l2'

First view 'Link name' is 'l1', so the tree is built on object elements links property containing connections with link name
'l1', so object list will be routed via "object3 -> object4 -> object6 -> object7". Similarly for the second view, but link
names 'l2/l1' will route via object5 instead of object4 (for the whole object3 first found 'l2' is used as one possible link
name only), so result route will be "object3 -> object5 -> object6 -> object7".
The third view link name list is 'l1|l2', so both names ('l1' and 'l2') are considered in a tree building process and the
view will be displayed as on the scheme above, but with one feature - object6 will be shown as a looped tree node with the
red color highlighted content background.

Also object elements for our example must have next link property values:
object3, element1: 'l1|3|id=4'  (link name 'l1', remote element id 3, remote object_selection 'id=4' selects object id 4)
object3, element2: 'l2|5|id=5'  (link name 'l2', remote element id 5, remote object_selection 'id=5' selects object id 5)
object4, element4: 'l1|7|id=6'  (..)
object5, element6: 'l1|8|id=6'  (..)
object6, element9: 'l1|10|id=7' (..)

In addition to the tree template settings - view 'element layout' field defines tree node content and tree scheme direction.
The object node content is a simple list of element titles and its values. Layout is a JSON list field generally, so first
correct JSON is used for the template only. The JSON should contain element identificators (id, datetime, owner.. 1, 2..)
as a JSON property names (property values are ignored) plus 'direction' tree property 'up' or 'down' (for default).
Empty layout field - all user defined elements plus 'id' with 'down' direction are used.
Correct empty JSON '{}' as an 'element layout' displays no content, while all error JSONs - only 'id' element.`
}}},

"Handlers": { profile: { element1: { line: '', style: HELPHEADSTYLE, head:
`Element handler is any executable script or binary called by the contoller when specified event occurs. Events occur on
user interaction with actual objects (mouse double click or keypress), new objects add, object data change and other object
or database processes. Client-server interaction model represents next scheme: client (browser) generates event which is
passed to the server (controller). Controller accepts the event and processes it either by itself (new database, object
delete..) or by calling/executing appropriate handler, which output result is parsed by the controller and passed to the
client:

+--------------+                                   +--------------+                                   +--------------+
|              |                                   |              |                                   |              |
|              |            USER EVENT             |              |            HANDLER CALL           |              |
|              |      ---------------------->      |              |      ---------------------->      |              |
|   Client     |                                   |    Server    |                                   |   Handler    |
|  (browser)   |                                   | (controller) |                                   |              |
|              |        CONTROLLER COMMAND         |              |          HANDLER COMMAND          |              |
|              |      <----------------------      |              |      <----------------------      |              |
|              |                                   |              |                                   |              |
+--------------+                                   +--------------+                                   +--------------+
                                                          ^
                                                          |
                                                          | CRUD operations
                                                          |
                                                          ∨
                                              +------------------------+
                                              |                        |
                                              |                        |
                                              |        Database        |
                                              |                        |
                                              |                        |
                                              +------------------------+

					    <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">USER EVENT</span>
As it was mentioned above user events are generated after database object manipulations, so they are:
 - mouse event:
    DBLCLICK (user double clicks object element by left mouse button)
 - keyboard events:
    KEYPRESS (user presses symbol keys such as space, digits or letters on object element)
    F2 (user presses F2 key on object element)
    F12 (user presses F12 key on object element)
    INS (user presses insert key on object element)
    DEL (user presses delete key on object element)
 - feedback events:
    CONFIRM (editable content after edit finish returns back to the controller and then to the handler to be processed)
    CONFIRMDIALOG (dialog box data after apply returns back to the controller and then to the handler to be processed)
 - new object:
    INIT (user adds/creates new object via context menu or new object input). Context menu 'Add Object' creates new object
    with parameters in a new object input table cells, while 'Clone Object' takes parameters from contexted object table cells.
    These parameters are used as a <data> args (see hadler call section below) for the handlers called at new object creation
    event via add/clone context menu.
 - delete object:
    DELETEOBJECT (user removes the object via context menu, the only event which is not passed to the handler - controller
		  just removes the object from DB (creates new object empty instance with zero version and lastversion flag
		  set) with no any handler call)

Also there are some events generated by the controller only, they are:
    CHANGE (after one element data is changed (via SET/RESET handler commands, see below) the event 'CHANGE' occurs for other
	    elements of the object, so these other elements can react on any element change - one element handler changes
	    its element data, others receives 'CHANGE' event)
    SCHEDULE (event is generated by system scheduler, handler command line to execute for that event is retrieved from
	      appropriate field in a 'View' section of Database configuration)

					    <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">HANDLER CALL</span>
User events above are received from the client side by the controller and passed to the handler for the initiated element
(or all elements) via executing specified handler command line configured in a 'Element' section of Database configuration.
Note, that 'INIT' client event (new object creation) is passed to all elements of the object, while others events - to
initiated element only. Handler command line is executed as it is specified, but with one moment - angle brackets quoted
arguments are parsed to be replaced by the next values:
 - <user> is replaced by user name the specified event was initiated by. 'SCHEDULER' event is initiated by built-in 'system' user.
 - <oid> is replaced by the object id the event was initiated on.
 - <event> is replaced by event name (DBLCLICK, KEYPRESS, CHANGE..) the handler is called on.
 - <title> is replaced by element title (element name in Database configuration) the event was initiated on.
 - <datetime> is replaced by date and time in format 'Y-m-d H:i:s'.
 - <data> is replaced by event data.
    For KEYPRESS event data is a JSON string with next format:
    {"string": "<key char>", "altkey": "",  "ctrlkey": "", "metakey": "", "shiftkey": ""}
    Property "string" is one key char, other properties do exist only in case of appropriate key pushed. Meta key for Mac OS
    is 'Cmd' key, for Window OS - 'Window' key.
    For DBLCLICK, INS, DEL, F2, F12 data arg is the same except the "string" property is undefined.
    For INIT event data argument text content in 'new object' or 'cloned object' element table cells if exist, otherwise <data>
    is empty string ''.
    For CONFIRM event after html element <td> editable content apply  - <data> argument is that content text data.
    For CONFIRMDIALOG after dialog box apply - <data> argument is a JSON that represents dialog structure*
    For CHANGE and SCHEDULE events <data> argument is undefined.
 - <JSON> is a special argument that is replaced by retrieved element data and should be in next format:
    {"ODid": .., "OD": .., "OVid": .., "OV": .., "selection": .., "element": .., "prop": .., "limit": .., ":..": ..}
    First four properties identify database view. In case of database/view identificators ("ODid"/"OVid") omitted
    datavase/view names ("OD"/"OV") are used. Both identificator and name omitted - current database or view is used.
    Specified view must have 'Table' template.
    Next two properties "selection" and "limit" are SQL query parts to select necessary objects which element data need to be
    retrieved: SELECT .. FROM .. WHERE "selection" LIMIT "limit". Omitted "selection" property - current object is used,
    omitted "limit" - number is set to 1.
    Last two properties "element" and "prop" select element of the selected object. Omitted "element" current element (the
    event was initiated on) is used. Omitted "prop" - property "value" of element JSON data is used. Property "element"
    is exact element id (user element id number or service element name - id,owner,datetime..) or regular expression ("/../")
    to search first match among all elements specified in database view 'Element layout'. 
    <JSON> may consist of some additional (nested) JSONS (with property names starting from ':'), which values are retrived
    the same way. These retrieved values then are used as a replacements in current JSON "element" regular expressions and
    "selection" properties:
    <{ .. "element": "/:arg/", ":arg": {..} }>
    Max 'nesting' levels number is 3, see 'Examples' help section for extra info.
    In case of multiple objects as a selection result - <JSON> argument will be replaced by property of specified element
    or found (via regular expression) elements of all selected objects (max 256, each in a new line).
    Therefore, <JSON> argument selects objects of the view (based on "selection" and "limit"), takes element (or elements)
    and optionally its property (based on "element" and "prop") and then replaces <JSON argument> with the retrieved value.

All properties of <JSON> argument are optional, so any JSON (even empty <{}>) is treated as a correct one. Empty (or with
unknown properties) JSON is replaced by the current object element value.
Not listed above argument cases remain untouched, but passed without angle brackets to avoid stdin/stdout redirections, so any
single angle brackets are truncated in a result command line.

					    <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">HANDLER COMMAND</span>
To make database changes or some client side actions - user handlers should return (output to stdout) some commands in JSON
format:
{"cmd": "<command>", "<prop1>": "<value1>",.., "<propN>": "<valueN>"}
Empty output or unknown commands (see available command list below) are ignored and make no actions. Output in non-JSON
format is automatically converted to the 'SET' command to be set as an element value (see 'SET' command description below):
{"cmd": "SET", "value": "<non-JSON handler output>"}

Available handler commands are:
 - 'EDIT'. Format: '{"cmd": "EDIT", "data": "<some text>"}'. The command makes the client side table cell content be editable.
   Property 'data' is optional and set as an editable content. No 'data' property - current table cell content (element value)
   is used as an editable content. For example, 'mouse double click' calls the handler, which response is 'EDIT' command - 
   just like in Excel :) Handler command 'EDIT' is ignored for 'CHANGE', 'INIT' and 'SCHEDULE' user/controller events.
 - 'ALERT'. Format: '{"cmd": "ALERT", "data": "<some text>"}'. The command displays client side warning dialog box with
   <some text> as a warning/info text and 'OK' button. No 'data' property - the command is ignored. Handler command 'ALERT' is
   ignored for 'CHANGE', 'INIT' and 'SCHEDULE' user/controller events.
 - 'DIALOG'. Format: '{"cmd": "DIALOG", "data": {<JSON dialog>}}'. The command displays client side dialog box based on
   <JSON dialog> format*, which allows to generate 'powerful' dialog boxes with any combination of text input, text areas,
   multiple/single selects, radio-buttons, check-boxes, interface buttons.. No 'data' property - the command is ignored.
   Handler command 'DIALOG' is ignored for 'CHANGE', 'INIT' and 'SCHEDULE' user/controller events. Dialog box in general
   consists of title area, pad content and footer. Each pad has one or more profiles and each profile has its uniq content
   with specified interface elements (check-boxed, radio-buttons, text areas, inputs and etc..). Footer is a button area to
   apply or cancel content changed data, see JSON dialog format below*
 - 'CALL'. Format: '{"cmd": "CALL", "ODid": "<database id>", "OVid": "<view id>", "params": {<JSON params>}}'. The command
   calls specified by OD/OV identificators database view as if the user clicks specified view on the sidebar. It is useful
   for some views to be called from a handler as a responce on some user events (mouse or keyboard, for a example) and
   according to the specific handler behaviour. <JSON params> is a JSON formatted object selection parameters,
   see 'Object Selection' help section for details. For a example, some object element mouse double click displays the view,
   which displays objects matched the clicked element value, that is passed in a "params" property. Handler command 'CALL' is
   ignored for 'CHANGE', 'INIT' and 'SCHEDULE' user/controller events.
 - 'SET'/'RESET'. Object element data set. Format: '{"cmd": "SET/RESET", "<prop1>": "<value1>", .., "<propN>": "<valueN>"}'.
   'SET' command updates all specified element JSON properties only. 'RESET' command does the same, but additionally removes
   all not specified properties. In fact, 'RESET' replaces element data with the handler output JSON.
   Note that element data property 'value' is displayed as a main element table cell text, property 'hint' is displayed as a
   footnote on element table cell mouse cursor navigation, upward triangle pictogram at the cell top right corner indicates
   elements with the hint. Also downward triangle pictogram at the cell top right corner indicates elements with the 'link'
   property and square pictogram indicates elements with files attached.
 - 'UPLOADDIALOG'. Format: '{"cmd": "UPLOADDIALOG"}'. The command makes the controller to call client side for the dialog box
   to upload/attach files to the object element.
 - 'DOWNLOADDIALOG'. Format: '{"cmd": "DOWNLOADDIALOG"}'. The command makes the controller to call client side for the dialog
   box to download files from the object element to the client.
 - 'UNLOADDIALOG'. Format: '{"cmd": "UNLOADDIALOG"}'. Similar to the 'DOWNLOADDIALOG', but with the option to delete attached
   files.
 - 'GALLERY'. Format: '{"cmd": "GALLERY"}'. The command makes the controller to call client side for the gallery mode to view
   images (.jpg .png .gif .bmp) among element attached files.
Some handlers may take long time for a execution, so to avoid any script/binary freezing or everlasting runtime - user
can manage handler processes via 'Task Manager' (context menu). Its table columns are PID (process identificator), Handler
(handler command line), Exe time (handler running time in sec), Initiator (user name initiated event for the handler call),
Ip (client ip address), Event (user event name), Database/view (database/view names), OId/Eid (object and element
identificators) and Kill (column with buttons 'X' to kill appropriate handler process). Task manager info is refreshed
automatically every second. Any column header mouse click (except 'Kill') sorts handler process list in ascending or
descending order.

					    <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">CONTROLLER COMMAND</span>
Handler commands 'EDIT', 'ALERT', 'DIALOG' and 'CALL' are passed from the handler by the controller directly to the client
without modification. These commands are client side specific and execute client (browser) actions such as edit content, alert
message, dialog box and specified view open/call.
Another handler commands 'SET' and 'RESET' makes the controller to do some database operations (new object instance (version)
create or new object create), process 'CHANGE' event and then check result object version on database rules.
Two user events 'INIT' (passed to the handler for all elements of the new object) and 'DELETEOBJECT' (no handler call) are
processed by the controller (create new object and remove specified object(s) respectively), which then calls client side
to refresh the current view.

*JSON dialog structure is a nested JSONs which describe dialog box and its interface elements:

{ "title": "dialog box title",
  "dialog": { "pad1": { "profile1": { "element1": { "type":	"select|multiple|checkbox|radio|textarea|text|password|table",
						    "head":	"<element title>",
						    "data":	"<element data>",
						    "help":	"<hint>",
						    "line":	"",
						    "readonly": ""
						  }
				      "element2": {..}
				    },
			"profile2": {..}
		      },
	      "pad2": {..}
	    },
  "buttons": { "button1": {"call": "", "value": "", "timer": "", "interactive": "", "error": "", "warning": "", "enterkey": "" },
	       "button2": {..}
	     },
  "flags": { "style": "dialog box content html style attribute",
	     "pad": "active (current selected) dialog box pad if exist",
	     "profile": "active (current selected) dialog box profile if exist",
	     "showpad": "",
	     "profilehead": { "pad-name1": "header1", "pad-name2": "header2", ..},
	     "updateonly": ""
	   }
}

- "title" is a dialog box text title, empty or undefined title - no box title area drawn.

- "dialog" property is a dialog content of itself with pads, profiles for every pad and some interface elements for each
  profile. Pads, profiles and elements are arbitrary. See 'Database configuration' dialog with pads and its profiles as
  an example. Each element must have at least 'type' property to be identified, so elements with unknown type are ignored.
  Element format:
    type: select. Dropdown list with one possible option to select
	  multiple. Dropdown list with more than one possible options to select
	  radio|checkbox. HTML input tag with radio or checkbox type. Selects one or multiple options respectively.
	  textarea. Multiple lines text input.
	  text. Single line text input.
	  password. Single line hidden text input.
	  table. Classic table with some text data, see "data" property.
    head: title/header text that is displayed as an interface element header.
    help: hint text that is displayed on a question mark button click at the end of a header text.
    data: initial data for interface element at dialog box initialization or changed dialog data after apply to return
	  to the handler.
	  For text-input element types "data" is an arbitrary text,
	  for 'select' types - options separated by '|' with selected option marked by '+' ("option1|+option2|option3|"),
	  for 'table' element type 'data' property is a JSON with properties as table rows. Each row property is a JSON
	       with properties as table cells. Each cell, in turn,  is a JSON with three properties:
		    value (cell text),
		    style (css style for the current html <td> tag) and
		    call (this property set calls initiated handler with changed dialog data and flags.event set to JSON
			 cell property name). See 'simple calculator' php code in a 'Examples' help section.
    line: this property set draws dividing shadowed line at the bootom of interface element area.
    readonly: this property set makes element to be read only.

- "buttons" is a JSON that describes box content apply/cancel actions via button list: one property - one button.
  Button property name is button id that is passed in a flags.event property (see flags description below) from client
  side to the handler to identify pushed button.
  Button property value is a JSON that describes button behaviour with next properties (all are optional):
    value: button text in dialog interface.
    call: this property set makes the controller to call the handler with changed dialog data on a button click event.
	  So the handler can process changed dialog data - controller command 'CONFIRMDIALOG' is sent to the initiated
	  handler. Buttons with non-existent 'call'/'timer' properties just remove dialog with no actions,
	  cancel button for a example.
    timer: box content apply timer in msec, min value is 500 msec, max - 36000000 msec (10 hours).
	   Controller command 'CONFIRMDIALOG' is sent to the initiated handler automatically after the timer has been
	   exceeded. Useful for automatic refresh (ie handler call) of the dialog box content.
    enterkey: this property set makes any one-line input interface element ('text' or 'password' type) emulate
	      button click on enter key push. In other words: enter key push on any one-line input element "clicks" the
	      button with 'enterkey' property set. Only one button can have 'enterkey' property set.
    interactive: this property set keeps dialog box active after button click event. For buttons with 'call' property only.
		 No 'call' buttons click event removes dialog anyway.
    error: message to be displayed as an error text in a 'View' main field area. For buttons with no 'call' property only.
    warning: message in warning dialog box. For buttons with no 'call' property only.

- "flags" is a JSON with some properties to style dialog box (all are optional):
    style: dialog box content html style attribute for the content wrapper div.
    pad: active (current selected) dialog box pad name that is set at the dialog box open.
    profile: active (current selected) dialog box profile name that is set at the dialog box open.
    showpad: pad navigation bar (for one single pad exist) is hidden for default, to display it - just set this
	     "showpad" property.
    profilehead: JSON with pad names as a property names which values are set as a header text (title) for specified pad
		 profile selection. Header text is displayed at the top of the pad content area above profile selection.
		 Used to describe pad and/or its profiles and allows to display profile selection interface element with
		 one single option (profile). In case of two or more profiles - it is displayed anyway.
    event: dialog apply event (clicked button or table cell identificator) to pass back to the handler to process changed
	   dialog content data. Property is set automatically.
    updateonly: dialog box update flag. Set it to indicate client side to only update existing dialog and don't create if
		it doesn't exist. Useful for autorefresh dialogs (see 'timer' button property) to exit properly - after
		dialog box exit (for a example via ESC button) client side ignores dialog box creation that was requested
		(via timer button) before exit.`
},
element2: { line: '', style: HELPHEADSTYLE, head: `
Application has some regular php handlers to manage user database, customization and element data.
Fisrt - user.php, see element #1 of 'User' database. The script creates users, changes their passwords, group membership and
user permissions via dialog box on F2 or DBLCLICK element #1 event. Group membership is a list of the groups (one per
line) the user is a member of. LINE FEED char is inserted at the end of the list automatically (if necessary) for the last
line (last group name in the list) to be correct.
User permissions represent two lists (for the read and write permission). Each list is a colon divided combination
(one by line) of database:view identificators. Omitted view id - restriction is applied for all views of specified database.
Non digit chars at the end of the line are ignored and can be used as a comment for the specified id combination.
For a example - combination '1:2' will restrict view id2 of database id1 for the user, '1' or '1:' will restrict database id1
all views. So hidden list of '1:2' will hide the specified view from the user with no read/write access, while visible list of
'1:2' will hide all databases and views for the user, except database id1 view id2. Empty visible list for the user disable
visibility of all views absolutely, so user can do nothing except reading this help:)
Per user restrictions are applied together with view specific restrictions listed in database view configuration.

Second - customization.php in 'User' database for the element #6. The script customizes user interface via css
properties for css selectors shown as dialog box profiles. All users (except system account) are created with default
customization, Default user 'root' preferably may not be used or changed in order to apply his customization (via
'force-user' option in customization dialog) and restore some other users props.

Another one - text.php - a kind of excel cell behaviour: F2 or double click makes cell content editable, DEL clears cell
text, CTRL|ALT|SHIFT + Enter combination (see customization 'Application' profile) applies content changes, ESC exits
editable mode with no changes. Handler supports next commands (as a first argument):
- SET or SETTEXT sets all input args concatenated to one string as an element main text (element 'value' property).
  For a example, handler command line 'php text.php SET Alex is 20 years old' will set next cell content/text with no
  spaces: 'Alexis20yearsold'. Handler 'php text.php SET "Alex is 20 years old"' will set 'Alex is 20 years old'.
- EDIT makes controller to call client side to edit element main text (element 'value' property). Format:
  php text.php EDIT arg
  Cell content (element main text) is set to arg and becomes editable, omitted arg - current cell content becomes
  editable. To apply changed content after edit - set next handler for the CONFIRM event: php text.php SET <data>
  All other arguments after 'arg' are ignored, so qoute arg text to be treated as a single one.
- SETPROP allows to edit any element property via dialog box, command line format:
  php text.php SETPROP prop1 <{"prop":"prop1"}> prop2 <{"prop":"prop2"}>..
  where 2nd arg prop1 is a first property name to edit, 3rd arg is JSON to retrieve prop1 property value, similarly for
  the prop2 (4th and 5th args) and others.
  To save dialog data with the new property values - set next handler for the CONFIRMDIALOG event:
  php text.php CONFIRMDIALOG <data>
  Good practice for most elements is interface to edit some service props ('link', 'hint' and 'style')
  via dialog on INS event (for a example), so command line for that INS event ('insert' key push) will be:
  php text.php SETPROP link <{"prop":"link"}> hint <{"prop":"hint"}> style <{"prop":"style"}>
- SELECT allows to select one element value among predefined values separated via '|' in one arg passed to handler :
  php text.php SELECT 'value1|value2|value3..'
  Handler will call dialog box with select interface element with specified options value1, value, value3..
  To save dialog data - set next handler for the CONFIRMDIALOG event: php text.php CONFIRMDIALOG <data>
- DBLCLICK together with double click event data. As you know - event data (for mouse and keyboard events) are CTRL|ALT|SHIFT keys 
  status, so command line 'text.php <event> <data>' for double click event will do next:
    1. Double click without CTRL, ALT, SHIFT or meta key - makes content editable (similar to 'EDIT' arg above)
    2. Double click with SHIFT - calls dialog box to upload/attach files to the appropriate object element
    3. Double click with CTRL - calls dialog box to download files from the appropriate object element
    4. Double click with ALT - calls dialog box to download/delete files from the appropriate object element.
- GALLERY calls client side gallery mode to view images attached to the object element.
`
}}},

"Examples": { profile: { element1: { line: '', style: HELPHEADSTYLE, head:
`Example 1 - simple corporate chat.
First step - create database and 'Table' templated view.
Second - all database actual objects (user messages) should be selected (default behaviour), so leave 'Object selection' empty.
Third - 'Element layout' should display messages in descending order with old messages on the top and new object (new message input)
on the bottom, plus some cell spacing and cell highlighting. Input next JSONs in element layout field:
{"style":"width: 96%; margin: 10px; border-collapse: separate;", "cellspacing":"15"}
{"eid":"1", "oid":"*", "x":"0", "y":"n", "style":"text-align: left; border: none; border-radius: 5px; background-color: #DDD;"}
{"eid":"1", "oid":"${NEWOBJECTID}", "x":"0", "y":"q", "event":"", "style":"width: 1400px; text-align: left; border-radius: 7px;"}
{"eid":"1", "oid":"${NEWOBJECTID}", "style": "background-color: transparent; border: 2px solid #d1d8df;"}

First JSON describes HTML tag <table> attributes (unset 'oid' case, see 'Element layout' help section): width attribue is a
necessary condition to allow table cells width (in pixels) setting. Border-collapse separate value set allows cell spacing
of 15 pixels between chat messages.
Second JSON describes all chat messages (all objects in object selection [oid=0] for element id 1 [eid=1]). All these cells are
styled via 'style' property with left text align, rounded border (5px) and light grey background color (#DDD). Object element
horizontal position is 'x=0' (first column) and vertical is 'n' - sequence number in a selection - first object (first message)
is placed in a fist row (n=0), second object in a second row (n=1) and so on. Variable 'q' is an object selection count number,
so 'input' object (third JSON for a new message input [oid=1]) goes to row number 'q'. For example - ten chat messages layout
is first 10 rows (0-9) for messages and next row number 10 (eleventh row) for new message input. To highlight new message input
field you may input last JSON to set transparent background color and grey border.

Next step - chat database consists of one user-defined element (for chat messages), so create it in a 'Element' tab of
'Database configuration' dialog - just enter next handler command line (to fit the page some input args are moved to a new line,
so don't forget the args to be divided by space chars) for INIT event to process new chat messages:
php text.php SETTEXT
<span><</span>span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">
<user>@
<{"ODid":"1", "OVid":"1", "selection":"lastversion=1 and version!=0 and JSON_EXTRACT(eid1, '$.value')=':user'", "element":"2"}>
</span>
' '
<span><</span>span style="color: #999;"><datetime></span><br>
<data>

Script 'text.php' is a regular application handler for text and other operations, its behaviour and input args are described in
a 'Handlers' help section. First input arg (SETTEXT) is for setting element text data of all remaining input args concatenated
to one string. As it was mentioned in a 'Handler' help section - every angle brackets quoted string is parsed for JSON or service
strings such as <user> (our case), <datetime>, <data>  and others. String <user> is replaced by the username the handler is called
from, in our chat context - the user the message is posted by. Next arg is user first (last) name as it is in OD 'Users' (ODid=1)
and OV 'All users' (OVid=1). This arg is retrieved via JSON that searches user object ("selection" property) and takes its second 
element ("element" property) - first/last name. Retrieved construction (user@firstname) is styled by <span> tag: deep blue color
(RGB(44,72,131) and bold font. After user@firstname - single space (' ') and light grey color styled datetime (<datetime>).
Then - user chat message text of itself (<data>) on the next line (<br>).

Last step - some chat restrictions for message removal and empty messages. See 'Database Configuration' rule section help.
Create rule profile for 'Delete object' operation with 'reject' action and query rule 'SELECT 1' to match any delete operation,
so any message delete operation will be blocked due to 'reject' action. To allow user to delete only his own messages input next
query: SELECT id FROM :odtable WHERE owner!=':user' AND id=':oid'
Second rule profile is a little bit more complicated - 'Add operation' with 'reject' action and next query rule:
SELECT id FROM :odtable WHERE id=:oid AND JSON_UNQUOTE(JSON_EXTRACT(eid1, '$.value')) NOT REGEXP '\\\\n.'
Empty message in our chat is 'user@name datetime\\\\n' (due to <br>), minimal non empty message - 'user@name datetime\\\\n.',
where '.' matches any char. To identify empty messages - match all except '\\\\n.', in other words, message text shouldn't match
regular expression (NOT REGEXP) string '\\\\n.'. Since the chat message (JSON_UNQUOTE(JSON_EXTRACT(eid1, '$.value'))) matches
empty message - the rule profile blocks (rejects) the operation (new message post in our case).

That's all. As a result we have a nice chat with no much efforts for customization!`},
element2: { line: '', style: HELPHEADSTYLE, head: `
Example 2 - host alive diagnostic.
Create database with two elements - one for host names or ip addresses, second for ping result of appropriate hosts in 1st
element. To input element id 1 text data (host/ip) add some handlers for 'DBLCLICK' event (edit content on double click):
php text.php EDIT
for 'CONFIRM' event (confirm content after edit fininshed):
php text.php SET <data>
and may be for 'DEL' event (content clear on del key press):
php text.php SET

No handler needed for the second element. To check continuously element id 1 hosts via ping utility create the view -
just set its name and one scheduler line: */10 * * * * 2 ping -c 1 <{"element":"1"}> | grep loss
First field (*/10) and next four asterisks makes scheduler execute specified handler (ping .. grep loss)
for specified element id (2) every ten minutes. Ping utiltiy sends one icmp request packet (-c 1) to retrieved hostname/ip
(JSON <{"element":"1"}>) and output 'loss' result to stdout. Non JSON handler output result is automatically converted to be
set as an element value, so ping loss results will be displayed in a table every 10 minutes. 
To check hosts on demand - set handler 'ping -c 1 <{"element":"1"}>' for 'CHANGE' event for element id2. The handler will be
called just right after element id 1 data (host/ip) is changed.`
},
element3: { line: '', style: HELPHEADSTYLE, head:`
Example 3 - Group users list. Each user group-membership is stored in system 'Users' database (ODid=1, OVid=1) in property
'groups' of 1st element 'User' (eid1). The property consists of group names (one per line), so we have to search specified
group name among all users and output the result.

Use qouted JSON argument in a handler command line (see 'Handlers' help section for details) for element to retrieve users
of the group specified, for a example, in the current element value (":group": {}) or explicitly (":group": "wheel"):
php text.php SET <{"ODid":"1", "OVid":"1","selection":"lastversion=1 and version!=0 and (JSON_UNQUOTE(JSON_EXTRACT(eid1, '$.groups'))
regexp '^:group\\\\n' OR JSON_UNQUOTE(JSON_EXTRACT(eid1, '$.groups')) regexp '\\\\n:group\\\\n')", "limit": "100","element":"1",":group": {}}>

Property "selection" is a SQL 'WHERE' operator expression to select 1st element ("element":"1") 'value' property (username).
First condition (regexp '^:group\\\\n') matches first line group names, second condition (regexp '\\\\n:group\\\\n')) -
all other group names from the second line with the symbol LINE FEED (\\n) before. Double slash escapes single slash for the correct
line feed char. Request result is limited to 100 records.`
},
element4: { line: '', style: HELPHEADSTYLE, head: `
Example 4 - style element cell. Set next handler for event (for a example 'F12') to paint cell by red color (for *nix OS only):
echo '{"cmd":"SET", "style":"background-color:red;"}'`
},
element5: { line: '', style: HELPHEADSTYLE, head: `
Example 5 - dialog box simple calculator. First create database and any view with default properties.
Then create one element with the handler (without args) for DBLCLICK event: 'php calc.php', and for CONFIRMDIALOG event:
'php calc.php <event> <data>' - 1st arg is event name ('CONFIRMDIALOG'), 2nd is dialog data structure called back on
calculator table click.

Here is calc.php code:

1 <?php
2
3 $calc = ['1' => ['11' => ['value'=>'7', 'call'=>''], '12' => ['value'=>'8', 'call'=>''], '13' => ['value'=>'9', 'call'=>''],
4	   	   '14' => ['value'=>'/', 'call'=>''], '15' => ['value'=>'C', 'call'=>'']
5		  ],
6	   '2' => ['21' => ['value'=>'4', 'call'=>''], '22' => ['value'=>'5', 'call'=>''], '23' => ['value'=>'6', 'call'=>''],
7		   '24' => ['value'=>'*', 'call'=>''], '25' => ['value'=>'<', 'call'=>'']
8		  ],
9	   '3' => ['31' => ['value'=>'1', 'call'=>''], '32' => ['value'=>'2', 'call'=>''], '33' => ['value'=>'3', 'call'=>''],
10		   '34' => ['value'=>'-', 'call'=>''], '35' => ['value'=>'']
11		  ],
12	   '4' => ['41' => ['value'=>''], '42' => ['value'=>'0', 'call'=>''], '43' => ['value'=>'.', 'call'=>''],
13		   '44' => ['value'=>'+', 'call'=>''], '45' => ['value'=>'=', 'call'=>'']
14		  ],
15	  ];
16 $dialog = ['title' => 'Calculator',
17	      'dialog' => ['pad' => ['profile' => ['element' => ['head' => ' ', 'type' => 'table', 'data' => $calc]]]],
18	      'buttons' => [ 'EXIT' => ['value' => 'EXIT', 'style' => 'background-color: red;']],
19	      'flags'  => ['style' => 'width: 250px; height: 200px; margin: 20px;']
20	     ];
21
22 if (!isset($_SERVER['argv'][1]) || $_SERVER['argv'][1] !== 'CONFIRMDIALOG')
23    {
24     echo json_encode(['cmd' => 'DIALOG', 'data' => $dialog]);
25     exit;
26    }
27 if (!isset($_SERVER['argv'][2])) exit;
28 if (gettype($dialog = json_decode($_SERVER['argv'][2], true)) != 'array') exit;
29 if (!isset($dialog['flags']['event'])) exit;
30
31 $key = $dialog['flags']['event'];
32 $value = $calc[$key[0]][$key]['value'];
33 switch ($value)
34 {
35  case '<':
36	 $dialog['dialog']['pad']['profile']['element']['head'] = substr($dialog['dialog']['pad']['profile']['element']['head'], 0, -1);
37	 break;
38  case 'C':
39	 $dialog['dialog']['pad']['profile']['element']['head'] = '';
40	 break;
41  case '=':
42	 $result = $dialog['dialog']['pad']['profile']['element']['head'];
43	 $dialog['dialog']['pad']['profile']['element']['head'] = strval(eval('return '.$result.';'));
44	 break;
45  default:
46	 $dialog['dialog']['pad']['profile']['element']['head'] .= $value;
47 }
48
49 if ($dialog['dialog']['pad']['profile']['element']['head'] === '') $dialog['dialog']['pad']['profile']['element']['head'] = ' ';
50 echo json_encode(['cmd' => 'DIALOG', 'data' => $dialog]);


Lines 3-15. Create calculator table via 5x4 array in $calc var:
	     --- --- --- --- ---
	    | 7 | 8 | 9 | / | C |
	     --- --- --- --- ---
	    | 4 | 5 | 6 | * | < |
	     --- --- --- --- ---
	    | 1 | 2 | 3 | - |   |
	     --- --- --- --- ---
	    |   | 0 | , | + | = |
	     --- --- --- --- ---
	    Each array key is an table event that will be passed in a dialog data flags.
	    Array elements without 'call' key are empty and do not initiate handler call.

Lines 16-20. Initial dialog strcuture array to pass to the controller via 'DIALOG' command (line 50).
	     See 'Handlers' help section for dialog fromat.

Lines 22-26. No script args exist or arg is not 'CONFIRMDIALOG'? Pass initial dialog.

Lines 27-29. First arg is 'CONFIRMDIALOG', so decode the arg to the dialog array and check correctness.

Line 31. Store table user click (table array $calc key) in $key var.

Line 32. Store clicked value in $value var.

Lines 33-47. Parse table array key value.  For backspace ('<') cut last expression char. For clear button ('C') empty the
	     expression. For calculation (=) evaluate expession strored in a table header. For other chars ('1', '2'..) -
	     concatenation with current expression is made.

Line 49. Empty headers ('') are not displayed before interface element, so set one space char for empty expression to be
	 displayed before calculator table.

Line 50. Pass dialog to the controller.`
}}},

"Keyboard/Mouse": { profile: { element: { style: HELPHEADSTYLE, head:
`  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Home</span> moves cursor to the top of a table
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">End</span> moves cursor to the bottom
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">PageUp</span> moves cursor one page down
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">PageDown</span> moves cursor one page up
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;"><, >, ^, v</span> arrow keys move cursor to appropriate direction
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Enter + [Shift]</span> moves cursor down [up]
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Enter + [Shift|Ctrl|Alt]</span> applies content changes in content-editable mode. Key combination depends on user
    cusomization 'application' property. New-object input content 'apply' creates new object
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">ScrollLock on</span> scrolls the entire table instead of cursor navigating cells
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Alt + ^|v</span>: previous|next object cursor navigation
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Ctrl + Alt + <|></span> previous|next view navigation
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Ctrl + c|INS</span> copies element formatted text to the clipboard
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Ctrl + Shift+c|Shift+INS</span> copies element clear text to the clipboard
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Ctrl + Shift + f</span>: regular expression search, see search dialog hint for brief regexp syntax
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Ctrl + right button single click</span> on any table cell opens new browser tab with that cell text as url
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Ctrl + a</span> selects entire table area
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Shift + <|>|^|v|Home|End|PageUp|PageDown</span> selects appropriate table area
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">ESC</span> cancels all changes and exits content editable mode or dialog box with no changes
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">INS, DEL, F2, F12, letters, digits, space or left button mouse double click</span>: cursor element handler call
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Mouse right button click</span> calls sidebar, main field or table area appropriate context menu
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Mouse over</span> event on any element for some time (default 1 sec) displays appropriate hint message if exist
  - <span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Mouse drag</span> operation on table cell selects and highlight table area. Selected area then can be processed to draw
    the chart via appropriate context menu. Two or more columns width area draws a piechart with an area top row as a
    pie names and its per-column summarized values as a percent of a total amount. To use selected area column for the
    pie names - area should be one column width, for the pie persentage - per row values of the next (non-selected)
    column are used.

Note that usual do/undo actions (Ctrl+y|z) are not implemented, cause of impossible multiuser database actions rollback.
To see user object database changes use object versions (instances) history via appropriate 'object selection'.
Row/column resizing operation like in 'excel' are not implemented also, use element layout (see appropriate help section)
properties to set initial table column width. By default, table column width are adjusted to fit the content.`
}}},
},

buttons: { OK: {value: "   OK    "}},
flags:   { esc: "", style: "min-width: 1100px; min-height: 600px; width: 1100px; height: 720px;" }
};


const regexphint = `<span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">                             Brief RegEx syntax</span>

\\ Marks the next character as either a special character or a literal.
  For example: "\\n" matches a newline character.The sequence \\\\ matches \\ and \\( matches (.
^ Matches the beginning of input.
$ Matches the end of input.
* Matches the preceding character zero or more times. For example, "zo*" matches either z or zoo.
+ Matches the preceding character one or more times. For example, "zo+" matches zoo but not z.
? Matches the preceding character zero or one time. For example, a?ve? matches the ve in never.
. Matches any single character except a newline character.
(subexpression) Matches subexpression and remembers the match. If a part of a regular expression is
  enclosed in parentheses, that part of the regular expression is grouped together.
  Thus a regex operator can be applied to the entire group.
x|y Matches either x or y. For example, z|wood matches z or wood. (z|w)oo matches zoo or wood.
{n} n is a non negative integer. Matches exactly n times.
  For example, o{2} does not match the o in Bob, but matches the first two o's in foooood.
{n,} n is a non negative integer. Matches at least n times.
  For example, o{2,} does not match the o in Bob and matches all the o's in "foooood."
o{1,} is equivalent to o+. o{0,} is equivalent to o*.
{n,m} m and n are nonnegative integers. Matches at leastn and at mostm times.
  For example, o{1,3} matches the first three o's in "fooooood." o{0,1} is equivalent to o?.
[xyz] A character set. Matches any one of the enclosed characters. For example, [abc] matches the a in plain.
[^xyz] A negative character set. Matches any character not enclosed. For example, [^abc] matches the p in plain.
[a-z] A range of characters. Matches any character in the specified range.
  For example, "[a-z]" matches any lowercase alphabetic character in the range a through z.
[^m-z] A negative range characters. Matches any character not in the specified range.
  For example, [^m-z] matches any character not in the range m through z.
\\b Matches a word boundary, that is, the position between a word and a space.
  For example, er\\b matches the er in never but not the er in verb.
\\B Matches a non-word boundary. ea*r\\B matches the ear in never early.
\\d Matches a digit character. Equivalent to [0-9].
\\D Matches a non-digit character. Equivalent to [^0-9].
\\r Matches a carriage return character.
\\s Matches any white space including space, tab, form-feed, and so on. Equivalent to [ \\f\\n\\r\\t\\v].
\\S Matches any nonwhite space character. Equivalent to [^ \\f\\n\\r\\t\\v].
\\t Matches a tab character.
\\v Matches a vertical tab character.
\\w Matches any word character including underscore. Equivalent to [A-Za-z0 -9_]. Use it in the search field.
\\W Matches any non-word character. Equivalent to [^A-Za-z0-9_].
\\num Matches num, where num is a positive integer, denoting a reference back to remembered matches.
  For example, (.)\\1 matches two consecutive identical characters.
\\xn Matches hexadecimal n escape value - \\x41 matches A. Allows ASCII codes to be used in regular expressions.`;
