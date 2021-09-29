/*------------------------------CONSTANTS------------------------------------*/
const TABLE_MAX_CELLS = 200000;
const NEWOBJECTID = 1;  
const TITLEOBJECTID = 2;
const STARTOBJECTID = 3;
const DEFAULTOBJECTSPERPAGE = 50;
const range = document.createRange();   
const selection = window.getSelection();
const GREYITEM = '<div class="contextmenuItems greyContextMenuItem">';
const ACTIVEITEM = '<div class="contextmenuItems">';
const BASECONTEXT = ACTIVEITEM + 'Task Manager</div>' + ACTIVEITEM + 'Help</div>';
const CONTEXTITEMUSERNAMEMAXCHAR = 12;
const SOCKETADDR = 'wss://tabels.app:7889';
const EFFECTHELP = "effect appearance. Possible values:<br>'fade', 'grow', 'slideleft', 'slideright', 'slideup', 'slidedown', 'fall', 'rise' and 'none'.<br>Undefined or empty value - 'none' effect is used."
const NOTARGETUIPROFILEPROPS = ['Editable content apply input key combination', 'target', 'effect' , 'filter', 'Force to use next user customization (empty or non-existent user - option is ignored)', 'mouseover hint timer in msec', 'object element value max chars', 'object element title max chars'];
const SPACELETTERSDIGITSRANGE = [65,90,48,57,96,107,109,111,186,192,219,222,32,32,59,59,61,61,173,173,226,226];
const HTMLSPECIALCHARS = ['&amp;', '&lt;', '&gt;', '<br>', '&nbsp;'];
const HTMLUSUALCHARS = ['&', '<', '>', '\n', ' '];
const SERVICEELEMENTS = ['id', 'version', 'owner', 'datetime', 'lastversion'];
/*------------------------------VARIABLES------------------------------------*/
let EDITABLE = 'plaintext-only';
let NOTEDITABLE = 'false';
let box = selectExpandedDiv = null, boxDiv, expandedDiv, contextmenu, contextmenuDiv, hint, hintDiv, mainDiv, sidebarDiv, mainTablediv;
let loadTimerId, tooltipTimerId, buttonTimerId, undefinedcellRuleIndex, socket;
let mainTable, mainTableWidth, mainTableHeight, objectTable, objectsOnThePage, paramsOV;
let user = cmd = OD = OV = ODid = OVid = OVtype = '';
let undefinedcellclass, titlecellclass, newobjectcellclass, datacellclass;
let sidebar = {}, cursor = {}, oldcursor = {}, drag = {};
let uiProfile = {
		  // Body
		  "application": { "target": "body", "background-color": "#343E54;", "Force to use next user customization (empty or non-existent user - option is ignored)": "", "Editable content apply input key combination": "Ctrl+Enter", "_Editable content apply input key combination": "Available options: 'Ctrl+Enter', 'Alt+Enter', 'Shift+Enter' and 'Enter'.<br>Any other values do set no way to apply content editable changes by key combination." },
		  // Sidebar
    		  "sidebar": { "target": ".sidebar", "background-color": "rgb(17,101,176);", "border-radius": "5px;", "color": "#9FBDDF;", "width": "13%;", "height": "90%;", "left": "4%;", "top": "5%;", "scrollbar-color": "#1E559D #266AC4;", "scrollbar-width": "thin;", "box-shadow": "4px 4px 5px #222;" },
		  "sidebar wrap icon": { "wrap": "&#9658;", "unwrap": "&#9660;" }, //{ "wrap": "+", "unwrap": "&#0150" }, "wrap": "&#9658;", "unwrap": "&#9660;"
		  "sidebar wrap cell": { "target": ".wrap", "font-size": "70%;", "padding": "3px 5px;" },
		  "sidebar item active": { "target": ".itemactive", "background-color": "#4578BF;", "color": "#FFFFFF;", "font": "1.1em Lato, Helvetica;" },
		  "sidebar item hover": { "target": ".sidebar tr:hover", "background-color": "#4578BF;", "cursor": "pointer;" },
		  "sidebar object database": { "target": ".sidebar-od", "padding": "3px 5px 3px 0px;", "margin": "0px;", "color": "", "width": "100%;", "font": "1.1em Lato, Helvetica;"  },
		  "sidebar object view": { "target": ".sidebar-ov", "padding": "2px 5px 2px 10px;", "margin": "0px;", "color": "", "font": "0.9em Lato, Helvetica;" },
		  "sidebar view changes count": { "target": ".changescount", "vertical-align": "super;", "padding": "2px 3px 2px 3px;", "color": "rgb(232,187,174);", "font": "0.6em Lato, Helvetica;", "background-color": "rgb(251,11,22);", "border-radius": "35%"},
		  // Main field
		  "main field": { "target": ".main", "width": "76%;", "height": "90%;", "left": "18%;", "top": "5%;", "border-radius": "5px;", "background-color": "#EEE;", "scrollbar-color": "#CCCCCC #FFFFFF;", "box-shadow": "4px 4px 5px #111;" },
		  "main field table": { "target": "table", "margin": "0px;" },
		  "main field table title cell": { "target": ".titlecell", "padding": "10px;", "border": "1px solid #999;", "color": "black;", "background": "#CCC;", "font": "", "text-align": "center" },
		  "main field table newobject cell": { "target": ".newobjectcell", "padding": "10px;", "border": "1px solid #999;", "color": "black;", "background": "rgb(191,255,191);", "font": "", "text-align": "center" },
		  "main field table data cell": { "target": ".datacell", "padding": "10px;", "border": "1px solid #999;", "color": "black;", "background": "", "font": "12px/14px arial;", "text-align": "center" },
		  "main field table undefined cell": { "target": ".undefinedcell", "padding": "10px;", "border": "", "background": "" },
		  "main field table cursor cell": { "outline": "red solid 1px", "shadow": "0 0 5px rgba(100,0,0,0.5)", "clipboard outline": "red dashed 2px" },
		  "main field table selected cell": { "target": ".selectedcell", "background-color": "#ddd;" },
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
		  "dialog box": { "target": ".box", "background-color": "rgb(233,233,233);", "color": "#1166aa;", "border-radius": "5px;", "border": "solid 1px #dfdfdf;", "box-shadow": "2px 2px 4px #cfcfcf;", "effect": "slideleft", "_effect": "Dialog box " + EFFECTHELP, "filter": "grayscale(0.5)", "_filter": "Application css style filter applied to the sidebar and main field.<br>For a example: 'grayscale(0.5)' or 'blur(3px)'. See appropriate css documentaion." },
		  "dialog box title": { "target": ".title", "background-color": "rgb(209,209,209);", "color": "#555;", "border": "#000000;", "border-radius": "5px 5px 0 0;", "font": "bold .9em Lato, Helvetica;", "padding": "5px;" },
		  "dialog box pad": { "target": ".pad", "background-color": "rgb(223,223,223);", "border-left": "none;", "border-right": "none;", "border-top": "none;", "border-bottom": "none;", "padding": "5px;", "margin": "0;", "font": ".9em Lato, Helvetica;", "color": "#57C;", "border-radius": "5px 5px 0 0;" },
		  "dialog box active pad": { "target": ".activepad", "background-color": "rgb(209,209,209);", "border-left": "none;", "border-right": "none;", "border-top": "none;", "border-bottom": "none;", "padding": "5px;", "margin": "0;", "font": "bold .9em Lato, Helvetica;", "color": "#57C;", "border-radius": "5px 5px 0 0;" },
		  "dialog box pad bar": { "target": ".padbar", "background-color": "transparent;", "border": "none;", "padding": "4px;", "margin": "10px 0 15px 0;" },
		  "dialog box divider": { "target": ".divider", "background-color": "transparent;", "margin": "5px 10px 5px 10px;", "height": "0px;", "border-bottom": "1px solid #CCC;", "border-top-color": "transparent;", "border-left-color": "transparent;" , "border-right-color": "transparent;" },
		  "dialog box button": { "target": ".button", "background-color": "#13BB72;", "border": "none;", "padding": "10px;", "margin": "10px;", "border-radius": "5px;", "font": "bold 12px Lato, Helvetica;", "color": "white;" },
		  "dialog box button push": { "target": ".buttonpush", "transform": "translate(3%, 3%);" },
		  "dialog box button and pad hover": { "target": ".button:hover, .pad:hover", "cursor": "pointer;", "background": "", "color": "", "border": "" },
		  "dialog box element headers": { "target": ".element-headers", "margin": "5px 5px 5px 5px;", "font": ".9em Lato, Helvetica;", "color": "#555;", "text-shadow": "none;" },
		  "dialog box help icon": { "target": ".help-icon", "padding": "1px;", "font": ".9em Lato, Helvetica;", "color": "#555;", "background": "#FF0;", "border-radius": "40%;" },
		  "dialog box help icon hover": { "target": ".help-icon:hover", "padding": "1px;", "font": "bold 1em Lato, Helvetica;", "color": "black;", "background": "#E8E800;", "cursor": "pointer;", "border-radius": "40%;" },
		  "dialog box table": { "target": ".boxtable", "font": ".8em Lato, Helvetica;", "color": "black;", "background": "transparent;", "margin": "0px;", "width": "100%;", "box-sizing": "border-box;" },
		  "dialog box table cell": { "target": ".boxtablecell", "padding": "7px;", "border": "1px solid #999;", "text-align": "center" },
		  "dialog box pushable table cell hover": { "target": ".boxtablecellpush:hover", "cursor": "pointer;" }, 
		  //
		  "dialog box select": { "target": ".select", "background-color": "rgb(243,243,243);", "color": "#57C;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 5px 10px;", "outline": "none;", "border": "1px solid #777;", "padding": "0px 0px 0px 0px;", "overflow": "auto;", "max-height": "10em;", "scrollbar-width": "thin;", "min-width": "10em;", "width": "auto;", "display": "inline-block;", "effect": "rise", "_effect": "Select fall-down option list  " + EFFECTHELP },
		  "dialog box select option": { "target": ".select > div", "padding": "2px 20px 2px 5px;", "margin": "0px;" },
		  "dialog box select option hover": { "target": ".select:not([type*='o']) > div:hover", "background-color": "rgb(209,209,209);", "color": "" },
		  "dialog box select option selected": { "target": ".selected", "background-color": "rgb(209,209,209);", "color": "#fff;" },
		  "dialog box select option expanded": { "target": ".expanded", "margin": "0px !important;", "position": "absolute;" },
		  //
		  "dialog box radio": { "target": "input[type=radio]", "background": "transparent;", "border": "1px solid #777;", "font": ".8em/1 sans-serif;", "margin": "3px 5px 6px 10px;", "border-radius": "20%;", "width": "1.2em;", "height": "1.2em;" },
		  "dialog box radio checked" : { "target": "input[type=radio]:checked::after", "content": "", "color": "white;" },
		  "dialog box radio checked background" : { "target": "input[type=radio]:checked", "background": "#00a0df;", "border": "1px solid #00a0df;" },
		  "dialog box radio label" : { "target": "input[type=radio] + label", "color": "#57C;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 0px 0px;" },
		  //
		  "dialog box checkbox": { "target": "input[type=checkbox]", "background": "#f3f3f3;", "border": "1px solid #777;", "font": ".8em/1 sans-serif;", "margin": "3px 5px 6px 10px;", "border-radius": "50%;", "width": "1.2em;", "height": "1.2em;" },
		  "dialog box checkbox checked" : { "target": "input[type=checkbox]:checked::after", "content": "", "color": "white;" },
		  "dialog box checkbox checked background" : { "target": "input[type=checkbox]:checked", "background": "#00a0df;", "border": "1px solid #00a0df;" },
		  "dialog box checkbox label" : { "target": "input[type=checkbox] + label", "color": "#57C;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 0px 0px;" },
		  //
		  "dialog box input text": { "target": "input[type=text]", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background": "#f3f3f3;", "border": "1px solid #777;", "outline": "none;", "color": "#57C;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  "dialog box input password": { "target": "input[type=password]", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background": "#f3f3f3;", "border": "1px solid #777;", "outline": "", "color": "#57C;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  "dialog box input textarea": { "target": "textarea", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background": "#f3f3f3;", "border": "1px solid #777;", "outline": "", "color": "#57C;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  // Tree
		  "tree table": { target: ".treetable", "border-spacing": "20px 0px;", "border-collapse": "separate;", "margin-top": "10px;", },
		  "tree error element": { target: ".treeerror", "background-color": "#eb8b9c;", "border": "1px solid black;", "padding": "7px !important;", "border-radius": "5px;", "text-align": "center;", "box-shadow": "2px 2px 4px #888;", "font": "12px/14px arial;", },
		  "tree element": { target: ".treeelement", "background-color": "#ccc;", "border": "1px solid black;", "padding": "7px !important;", "border-radius": "5px;", "text-align": "left;", "box-shadow": "2px 2px 4px #888;", "font": "12px/14px arial;", "object element value max chars": "60", "object element title max chars": "15", },
		  "tree arrow stock": { target: ".treelinkstock", "flex-basis": "10px;", "box-sizing": "border-box;", "background-color": "rgb(17,101,176);", "border": "none;", "margin-left": "15px;", "margin-right": "15px;", "height": "60px;", },
		  "tree arrow down": { target: ".treelinkarrowdown", "flex-basis": "20px;", "box-sizing": "border-box;", "background-color": "transparent;", "border-top": "40px solid rgb(17,101,176);", "border-bottom": "0 solid transparent;", "border-left": "20px solid transparent;", "border-right": "20px solid transparent;", },
		  "tree arrow up": { target: ".treelinkarrowup", "flex-basis": "20px;", "box-sizing": "border-box;", "background-color": "transparent;", "border-top": "0 solid transparent;", "border-bottom": "40px solid rgb(17,101,176);", "border-left": "20px solid transparent;", "border-right": "20px solid transparent;", },
		  "tree element description": { target: ".treelinkdescription", "display": "flex;", "flex": "1 10px;", "background-color": "transparent;", "border": "none;", "padding": "5px;", "font": "10px/11px arial;", "overflow": "hidden;", },
		  // Misc
		  "chart colors": { "Color #1": "#4CAF50", "Color #2": "#00BCD4", "Color #3": "#E91E63", "Color #4": "#FFC107", "Color #5": "#9E9E9E", "Color #6": "#FFFF00", "Color #7": "#E32DF2", "Color #8": "#BDDDFD", "Color #9": "#BCF11B", "Color #10": "#DBDBDB", "Color #11": "#343E54", "Color #12": "#1465B0" },
		  };
/*---------------------------------------------------------------------------*/

const style = document.createElement('style');			// Create style DOM element
styleUI();							// Style default user inteface profile
document.head.appendChild(style);				// Append document style tag

window.onload = function()
{
 // Define document html and add appropriate event listeners
 document.body.innerHTML = '<div class="sidebar"></div><div class="main"></div><div class="contextmenu ' + uiProfile["context menu"]["effect"] + 'hide"></div><div class="hint ' + uiProfile["hint"]["effect"] + 'hide"></div><div class="box ' + uiProfile["dialog box"]["effect"] + 'hide"></div><div class="select expanded ' + uiProfile["dialog box select"]["effect"] + 'hide"></div>';
 document.addEventListener('mousedown', MouseEventHandler);
 document.addEventListener('mouseup', MouseEventHandler);
 document.addEventListener('keydown', KeyboardEventHandler);
 document.addEventListener('contextmenu', ContextEventHandler);
 document.addEventListener('mousemove', MouseMoveEventHandler);

 // Define sidebar div
 sidebarDiv = document.querySelector('.sidebar');

 // Define main field div and add 'scroll' event for it
 mainDiv = document.querySelector('.main');
 mainDiv.addEventListener('scroll', () => { HideHint(); HideContextmenu(); });

 // Define context menu div and add some mouse events
 contextmenuDiv = document.querySelector('.contextmenu');
 contextmenuDiv.addEventListener('mouseover', event => { if (event.target.classList.contains('contextmenuItems') && !event.target.classList.contains('greyContextMenuItem')) SetContextmenuItem(event.target); });
 contextmenuDiv.addEventListener('mouseout', () => { SetContextmenuItem(null); });

 // Define interface divs 
 hintDiv = document.querySelector('.hint');
 boxDiv = document.querySelector('.box');
 expandedDiv = document.querySelector('.expanded');

 cmd = 'CALL';
 CreateWebSocket();
}

function CreateWebSocket()
{
 socket = new WebSocket(SOCKETADDR);
 socket.onmessage = FromController;
 socket.onopen	= CallController;
 socket.onclose = () => { displayMainError("The server connection is down! Try again"); OD = OV = ODid = OVid = OVtype = ''; HideBox(); };
 socket.onerror = () => socket.onclose();
}

function lg(...data)
{
 data.forEach(value => console.log(value));
}

function loog(...data)
{
 let add = new Date().toLocaleString() + ':';
 if (user) add = "Account '" + user + "', " + add;
 lg(add);
 data.forEach((value) => console.dir(value));
}

function Hujax(url, callback, requestBody)
{
 fetch(url, { method:	'POST',
	      headers: 	{ 'Content-Type': 'application/json; charset=UTF-8'},
	      body: 	JSON.stringify(requestBody) }).then(function(response)
			{
			 response.ok ? response.json().then(callback) : displayMainError(`Request failed with response ${response.status}: ${response.statusText}`);
			}).catch (function(error) { lg('Ajax request error: ', error);
	    });
 return true;
}

function drawSidebar(data)
{
 if (typeof data != 'object') return;
 let text, count, ovlistHTML, sidebarHTML = '';

 for (let odid in data)
     {
      // Set wrap status (empty string key) to true for default or to old instance of sidebar OD wrap status
      (sidebar[odid] === undefined || sidebar[odid]['wrap'] === undefined) ? data[odid]['wrap'] = true : data[odid]['wrap'] = sidebar[odid]['wrap'];
       if (!data[odid]['count']) data[odid]['count'] = {};

      // Create OV names list with active OV check 
      ovlistHTML = '';
      for (let ovid in data[odid]['view'])
    	  {
	   count = text = '';
	   if (data[odid]['active'] === ovid)
	      {
	       text = ' class="itemactive"';
	       ODid = odid;
	       OVid = ovid;
	       OD = data[odid]['name'];
	       OV = data[odid]['view'][ovid];
	      }
	   if (data[odid]['view'][ovid].substr(0, 1) != '_')
	      {
	       if (sidebar[odid]?.['count']?.[ovid] && (data[odid]['count'][ovid] = sidebar[odid]['count'][ovid])) count =  ' <span class="changescount">'+ sidebar[odid]['count'][ovid] + '</span>';
	       ovlistHTML += `<tr${text}><td class="wrap"></td><td class="sidebar-ov" data-odid="${odid}" data-ovid="${ovid}" data-od="${data[odid]['name']}" data-ov="${data[odid]['view'][ovid]}">${data[odid]['view'][ovid]}${count}</td></tr>`;
	      }
	  }

      // Draw wrap icon
      if (ovlistHTML === '') sidebarHTML += '<tr><td class="wrap"></td>';  // Insert empty wrap icon
       else if (data[odid]['wrap'] === false) sidebarHTML += '<tr><td class="wrap">' + uiProfile['sidebar wrap icon']['unwrap'] + '</td>'; // Insert unwrap icon
        else sidebarHTML += '<tr><td class="wrap">' + uiProfile['sidebar wrap icon']['wrap'] + '</td>'; // Insert wrap icon

      // Insert OD name
      sidebarHTML += `<td class="sidebar-od" data-odid="${odid}">${data[odid]['name']}</td></tr>`;

      // Insert OV names list if OD is unwrapped
      if (data[odid]['wrap'] === false) sidebarHTML += ovlistHTML;
     }

 // Push calculated html text to sidebar div
 sidebarHTML != '' ? sidebarDiv.innerHTML = '<table style="margin: 0px;"><tbody>' + sidebarHTML + '</tbody></table>' : sidebarDiv.innerHTML = '';

 // Reset sidebar to the new data
 sidebar = data;
}

function MergeStyles(...styles)
{
 let result, object = {};

 styles.forEach((style) => {
			    if (style && typeof style === 'string')
			       for (let rule of style.split(';'))
				   if ((result = (rule = rule.trim()).indexOf(':')) > 0 && rule.length > result + 1)
				      object[rule.substr(0, result)] = rule.substr(result + 1); // Some chars before and after ':'?
			   });

 result = '';
 for (let rule in object) result += `${rule}: ${object[rule]}; `;
 return result;
}

function GetCoordinates(props, e, o, n)
{
 let oid;			// object id for current object (var o) or default object id='0'
 let pos = {};			// return result, oid is object id for current object (var o) or default object id='0'
 let q = objectsOnThePage;	// OV objects quantity. Variables n,q participate in x,y expression eval

 // Take current object x,y (table coordiantes) props as more specific, then the default object (id=0) and return if failed. So for other props.
 if (!((props[e][(oid = o)]?.x && props[e][oid].y) || (o >= STARTOBJECTID && props[e][(oid = '0')]?.x && props[e][oid].y))) return null;
 try { pos.x = Math.trunc(eval(props[e][oid].x)); pos.y = Math.trunc(eval(props[e][oid].y)); } catch { pos = {}; }
 if (isNaN(pos.x) || isNaN(pos.y))
    return `Specified view '${OV}' element layout has some 'x','y' incorrect coordinate definitions!\nSee element element layout help section`;
 if ((Math.max(mainTableWidth, pos.x + 1) * Math.max(mainTableHeight, pos.y + 1)) > TABLE_MAX_CELLS || pos.x < 0 || pos.y < 0)
    return `Some elements coordiantes (view '${OV}') are out of range. Max table size allowed - ` + TABLE_MAX_CELLS + " cells";

 // Get hidecol, hiderow and style props
 if (props[e][(oid = o)]?.hidecol != undefined || (o >= STARTOBJECTID && props[e][(oid = '0')]?.hidecol != undefined)) pos.hidecol = props[e][oid].hidecol;
 if (props[e][(oid = o)]?.hiderow != undefined || (o >= STARTOBJECTID && props[e][(oid = '0')]?.hiderow != undefined)) pos.hiderow = props[e][oid].hiderow;
 props[0] ? pos.style = MergeStyles(props[0][o]) : pos.style = '';
 if (props[e][(oid = o)]?.style != undefined || (o >= STARTOBJECTID && props[e][(oid = '0')]?.style != undefined)) pos.style = MergeStyles(pos.style, props[e][oid].style);

 // Get event prop
 if (cmd === 'CALL' && !cursor.oId) // If OV display call (not add/remove operations that also refresh OV) and event yet is undefined
 if (props[e][(oid = o)]?.event != undefined || (o >= STARTOBJECTID && props[e][(oid = '0')]?.event != undefined))
    {
     cursor.oId = Number(o); // Event does exist, so get its object/elemnt ids
     cursor.eId = Number(e);
     if (props[e][oid]['event'].substr(0, 8) === 'KEYPRESS' && (cursor.cmd = 'KEYPRESS')) cursor.data = props[e][oid]['event'].substr(8);
      else if (['DBLCLICK', 'INS', 'DEL', 'F2', 'F12'].indexOf(props[e][oid]['event']) !== -1) cursor.cmd = props[e][oid]['event'];
    }

 // Calculate main table width and height
 mainTableWidth = Math.max(mainTableWidth, pos.x + 1);
 mainTableHeight = Math.max(mainTableHeight, pos.y + 1);

 // Create main table row if exist and return
 if (mainTable[pos.y] === undefined) mainTable[pos.y] = [];
 return pos;
}

function drawMain(data, props)
{
 ResetUnreadMessages();
 delete drag.x1;

 // Current view refresh? Remember cursor position and editable status.
 let oldcursor;
 if (cursor.td && cursor.ODid === ODid && cursor.OVid === OVid)
    {
     oldcursor = { x: cursor.x, y: cursor.y, oId: cursor.oId, eId: cursor.eId, contentEditable: cursor.td.contentEditable, data: htmlCharsConvert(cursor.td.innerHTML) };
     if (objectTable[NEWOBJECTID])
	{
	 oldcursor.newobject = [];
	 for (let eid in objectTable[NEWOBJECTID]) oldcursor.newobject[eid] = mainTable[objectTable[NEWOBJECTID][eid].y][objectTable[NEWOBJECTID][eid].x].data;
	}
    }
  else
    {
     oldcursor = {};
    }

 // Init some important vars such as tables, focus element and etc..
 cursor = { ODid: ODid, OVid: OVid };
 mainTable = [];
 objectTable = {};
 mainTableWidth = mainTableHeight = 0;
 OVtype = 'Table';

 // Get x,y coordinates (and other properties) from props elements array 
 let obj, e, warningtext, pos, cell, hiderow = [], hidecol = [];
 if (!(objectsOnThePage = data.length)) data = [{}];
 for (let n in data) 	if (obj = data[n])
 for (e in props)	if (e !== '0')
     {
      if (n === '0' && (pos = GetCoordinates(props, e, NEWOBJECTID, +n)) && (typeof pos !== 'string' || !(warningtext = pos))) // Place 'add-new-object' object only once (when n==0) for each element
	 {
	  mainTable[pos.y][pos.x] = { oId: NEWOBJECTID, eId: e, data: props[e][NEWOBJECTID]['value'], hint: props[e][NEWOBJECTID]['hint'], style: newobjectcellclass + (pos.style ? ` style="${pos.style}"` : '') };
	 }
      if (props[e][TITLEOBJECTID] && (pos = GetCoordinates(props, e, TITLEOBJECTID, +n)) && (typeof pos !== 'string' || !(warningtext = pos)))
	 {
	  mainTable[pos.y][pos.x] = { oId: TITLEOBJECTID, eId: e, data: props[e][TITLEOBJECTID]['value'], hint: props[e][TITLEOBJECTID]['hint'], style: titlecellclass + (pos.style ? ` style="${pos.style}"` : '') };
	  if (n === '0' && !(/n|q/.test(props[e][TITLEOBJECTID].x)) && !(/n|q/.test(props[e][TITLEOBJECTID].y))) delete props[e][TITLEOBJECTID]; // In case of constant x,y coordinates (no 'n','q' variables) remove specified element title object prop to make it used only once
	 }
      if ('id' in obj && (pos = GetCoordinates(props, e, obj.id, +n)) && (typeof pos !== 'string' || !(warningtext = pos)))
	 {
	  mainTable[pos.y][pos.x] = { oId: +obj.id, eId: e, version: obj.version, realobject: ((obj.lastversion === '1' && obj.version != '0') ? true : false) };
	  cell = mainTable[pos.y][pos.x];
	  if (SERVICEELEMENTS.indexOf(e) !== -1)
	     {
	      cell.data = obj[e];
	      cell.style = datacellclass + (pos.style ? ` style="${pos.style}"` : '');
	      continue;
	     }
	  cell.data		= obj['eid' + e + 'value'];
	  if (cell.data === pos.hiderow) hiderow[pos.y] = true;
	  if (cell.data === pos.hidecol) hidecol[pos.x] = true;
	  cell.hint		= obj['eid' + e + 'hint'];
	  cell.description	= obj['eid' + e + 'description'];
	  cell.style		= datacellclass + ((pos.style = MergeStyles(pos.style, obj['eid' + e + 'style'])) ? ` style="${pos.style}"` : '');
	 }
     }

 // Handle some errors
 if (!mainTableHeight)
    {
     if (!warningtext) warningtext = `Specified view '${OV}' has no objects matched current selection!<br>Please add some objects`;
     displayMainError(warningtext, false);
     return;
    }
 if (warningtext) warning(warningtext);

 // Create html table of mainTable array, props[0][0] = { style: , table: }
 const undefinedCell = '<td' + undefinedcellclass + (props[0]?.[0]?.['style'] ? `style="${props[0][0]['style']}"` : '') + '></td>';

 // Add table attributes
 let rowHTML = '<table';
 if (props[0]?.[0]?.['table']) for (let attr in props[0][0]['table']) rowHTML += ` ${attr}="${props[0][0]['table'][attr]}"`;
 rowHTML += '><tbody>';

 // Create 'undefined' html tr element row
 let x, y, disp = 0, undefinedRow = '<tr>';
 for (x = 0; x < mainTableWidth - hidecol.length; x++) undefinedRow += undefinedCell;
 undefinedRow += '</tr>';
 //
 mainTableRemoveEventListeners(); // Remove previous view event listeners
 for (y = 0; y < mainTableHeight; y++)
     {
      if (hiderow[y + disp]) { mainTable.splice(y, 1); mainTableHeight--; y--; disp++; continue; }
      if (!mainTable[y] && (rowHTML += undefinedRow)) continue;
      rowHTML += '<tr>';
      for (x = 0; x < mainTableWidth; x++)
	  {
	   if (hidecol[x]) { mainTable[y].splice(x, 1); mainTableWidth--; x--; continue; }
	   if (cell = mainTable[y][x])
	      {
	       if (cell.oId === NEWOBJECTID && oldcursor.newobject?.[cell.eId]) cell.data = oldcursor.newobject[cell.eId];
	       rowHTML += `<td${cell.style}>${toHTMLCharsConvert(cell.data)}</td>`;
	       // objectTable[oid][id|version|owner|datetime|lastversion|1|2..] = { x: , y: }
	       if ((cell.realobject || cell.oId === TITLEOBJECTID || cell.oId === NEWOBJECTID))
		  objectTable[cell.oId] ? objectTable[cell.oId][cell.eId] = { x: x, y: y } : objectTable[cell.oId] = { [cell.eId]: { x: x, y: y } };
	      }
	    else
	      {
	       rowHTML += undefinedCell;
	      }
	  }
      rowHTML += '</tr>';
     }
 mainDiv.innerHTML = rowHTML + '</tbody></table>';
 mainTablediv = mainDiv.querySelector('table');
 mainTableAddEventListeners(); // Add current view event listeners
 clearTimeout(loadTimerId);

 // Start event?
 if (cursor.oId)
    {
     if (cursor.cmd && cursor.oId >= STARTOBJECTID && (cmd = cursor.cmd)) CallController(cursor.data);
     SetInitialCursorPosition(cursor);
    }
 // Otherwise restore cursor (if exist) position on refreshed view
 else if (oldcursor.oId)
    {
     SetInitialCursorPosition(oldcursor);
    }
 cmd = '';
}

function SetInitialCursorPosition(cursor)
{
 if (objectTable[cursor.oId]?.[cursor.eId])
    {
     cursor.x = objectTable[cursor.oId][cursor.eId].x;
     cursor.y = objectTable[cursor.oId][cursor.eId].y;
    }
  else
    {
     cursor.oId = mainTable[cursor.y = Math.min(oldcursor.y, mainTableHeight - 1)][cursor.x = Math.min(oldcursor.x, mainTableWidth - 1)].oId;
     cursor.eId = mainTable[cursor.y][cursor.x].eId;
    }
 CellBorderToggleSelect(null, (cursor.td = mainTablediv.rows[cursor.y].cells[cursor.x]));
 if (cursor.contentEditable === EDITABLE || cursor.oId === NEWOBJECTID) MakeCursorContentEditable(cursor.data);
 mainDiv.scrollTop = mainDiv.scrollHeight * cursor.y / mainTableHeight;
 mainDiv.scrollLeft = mainDiv.scrollWidth * cursor.x / mainTableWidth;
}

function CalcTree(tree)
{
 if (!tree.link || !tree.link.length) return (tree['colspan'] = 1);

 tree['colspan'] = 0;
 for (let i in tree.link) tree['colspan'] += CalcTree(tree.link[i]);
 return tree['colspan'];
}

function BuildTree(tree, y, x)
{
 if (!mainTable[y]) mainTable[y] = [];
 mainTable[y][x] = { colspan: tree['colspan'], content: tree['content']};
 tree['class'] ? mainTable[y][x]['class'] = ' class="' + tree['class'] + '"' : mainTable[y][x]['class'] = '';

 if (tree.link && tree.link.length)
    {
     y++;
     for (let i in tree.link)
         {
          BuildTree(tree.link[i], y, x);
	  x += tree.link[i]['colspan'];
	 }
    }
}

function DrawTree(tree, direction)
{
 let x, y, stockrow, arrowrow, objectrow, content, title, value, trs = '';

 // Flush old data
 mainTableRemoveEventListeners();
 clearTimeout(loadTimerId);

 // Calculate and build object tree
 mainTable = [];
 OVtype = 'Tree';
 cursor = { ODid: ODid, OVid: OVid };
 CalcTree(tree);
 BuildTree(tree, 0, 0);

 // Create html table of mainTable array
 mainTableHeight = mainTable.length;
 mainTableWidth = tree.colspan;
 for (y = 0; y < mainTableHeight; y++)
     {
      x = 0;
      stockrow = arrowrow = objectrow = '';
      while (x < mainTable[y].length)
	    {
	     if (!mainTable[y][x])
		{
		 x++;
		 stockrow += '<td></td>';
		 arrowrow += '<td></td>';
		 objectrow += '<td></td>';
		 continue;
		}
	     //----------------------
	     stockrow += '<td';
	     arrowrow += '<td';
	     objectrow += '<td';
	     //----------------------
	     if (mainTable[y][x]['colspan'] > 1)
	        {
		 stockrow += ' colspan=' + mainTable[y][x]['colspan'];
		 arrowrow += ' colspan=' + mainTable[y][x]['colspan'];
		 objectrow += ' colspan=' + mainTable[y][x]['colspan'];
		}
	     //----------------------
	     objectrow += mainTable[y][x]['class'] + '>' + GetTreeElementContent(mainTable[y][x]['content']) + '</td>';
	     //----------------------
	     if (!(value = '') && mainTable[y][x]['content'][0]['value']) value = EllipsesClip(mainTable[y][x]['content'][0]['value'], uiProfile['tree element']['object element value max chars']);
	     title = EllipsesClip(mainTable[y][x]['content'][0]['title'], uiProfile['tree element']['object element title max chars']);
	     stockrow += '><div class="treelink"><div style="justify-content: flex-end; align-items: flex-' + (direction === 'up' ? 'end' : 'start') + ';" class="treelinkdescription"><span>' + title + '</span></div><div class="treelinkstock"></div><div style="justify-content: flex-start; align-items: flex-' + (direction === 'up' ? 'end' : 'start') + ';" class="treelinkdescription">' + value + '</div></div></td>';
	     //----------------------
	     if (content = mainTable[y][x]['content'][1])
	        {
		 title = EllipsesClip(content['title'], uiProfile['tree element']['object element title max chars']);
		 value = EllipsesClip(content['value'], uiProfile['tree element']['object element value max chars']);
		 
		 arrowrow += '><div class="treelink"><div style="' + (content['title'] === undefined ? 'color: red; ' : '');
	         arrowrow += 'justify-content: flex-end; align-items: flex-' + (direction === 'up' ? 'start' : 'end') + ';" class="treelinkdescription">';
		 arrowrow += '<span>' + (content['title'] === undefined ? 'Unknown element:' : title) + '</span></div>';
		 arrowrow += '<div class="treelinkarrow' + direction + '"></div>';
		 arrowrow += '<div style="' + (content['title'] === undefined ? 'color: red; ' : '');
		 arrowrow += 'justify-content: flex-start; align-items: flex-' + (direction === 'up' ? 'start' : 'end') + ';" class="treelinkdescription">';
		 arrowrow += '<span>' + (content['title'] === undefined ? EllipsesClip(content['id'], uiProfile['tree element']['object element value max chars']) : value) + '</span></div></td>';
		}
	     //----------------------
	     x += mainTable[y][x]['colspan'];
	    }
      if (direction === 'up')
         {
          if (y > 0) trs = '<tr>' + arrowrow + '</tr><tr>' + stockrow + '</tr>' + trs;
          trs = '<tr>' + objectrow + '</tr>' + trs;
	 }
       else
         {
          if (y > 0) trs += '<tr>' + stockrow + '</tr><tr>' + arrowrow + '</tr>';
          trs += '<tr>' + objectrow + '</tr>';
	 }
     }

 mainDiv.innerHTML = '<table class="treetable"><tbody>' + trs + '</tbody></table>';
}

function GetTreeElementContent(content)
{
 let title, value, data = '';
 for (let i = 2; i < content.length; i++)
     {
      if (title = content[i]['title'])
	 data += `<span class="underlined">${EllipsesClip(title, uiProfile['tree element']['object element title max chars'])}</span>: `;
      if (value = content[i]['value'])
	 data += title === undefined ? value : EllipsesClip(value, uiProfile['tree element']['object element value max chars']);
      data += '<br>';
     }
 return data;
}

function DrawBoxDiv()
{
 boxDiv.style.left = drag.left;
 boxDiv.style.top = drag.top;
}

function SelectTableArea(x1, y1, x2, y2)
{
 for (let y = Math.min(y1, y2); y <= Math.max(y1, y2); y++)
 for (let x = Math.min(x1, x2); x <= Math.max(x1, x2); x++)
     if (x != x1 || y != y1) mainTablediv.rows[y].cells[x].classList.add('selectedcell');
}

function UnSelectTableArea(x1, y1, x2, y2)
{
 for (let y = Math.min(y1, y2); y <= Math.max(y1, y2); y++)
 for (let x = Math.min(x1, x2); x <= Math.max(x1, x2); x++)
     if (x != x1 || y != y1) mainTablediv.rows[y].cells[x].classList.remove('selectedcell');
}

function MouseMoveEventHandler(event)
{
 if (drag.element)
 if (drag.element === boxDiv)
    {
     drag.left = String(event.clientX - drag.dispx) + 'px';
     drag.top = String(event.clientY - drag.dispy) + 'px';
     window.requestAnimationFrame(DrawBoxDiv);
     return;
    }
  else if (OVtype === 'Table')
    {
     const next = event.target.tagName === 'TD' ? event.target : event.target.parentNode;
     if (next.tagName !== 'TD') return;
     if (next.classList.contains('datacell') || next.classList.contains('titlecell') || next.classList.contains('newobjectcell') || next.classList.contains('undefinedcell'))
	{
	 UnSelectTableArea(drag.x1, drag.y1, drag.x2, drag.y2);
	 SelectTableArea(drag.x1, drag.y1, drag.x2 = next.cellIndex, drag.y2 = next.parentNode.rowIndex);
	}
    }

 if (!box && !contextmenu)
    {
     let x, y;
     const next = event.target.tagName === 'TD' ? event.target : event.target.parentNode;
     if (next.tagName !== 'TD') return;
     if (next.classList.contains('datacell') || next.classList.contains('titlecell') || next.classList.contains('newobjectcell'))
     if (mainTable[y = next.parentNode.rowIndex][x = next.cellIndex].hint)
	{
	 if (!hint || hint.x != x || hint.y != y) // No current hint or position is changed? Set timeout to new hint display
	    {
	     hint = { x: x, y: y };
	     clearTimeout(tooltipTimerId);
	     tooltipTimerId = setTimeout(() => ShowHint(mainTable[y][x].hint, getAbsoluteX(next, 'middle'), getAbsoluteY(next, 'end')), uiProfile['hint']['mouseover hint timer in msec']);
	    }
	 return;
	}
    }

 HideHint();
}

function MainDivEventHandler(event)
{
 switch (event.type)
	{
	 case 'mouseleave':
	      if (!box) HideHint();
	      break;
	 case 'dblclick':
	      if (!box && event.target.contentEditable != EDITABLE && mainTable[cursor.y]?.[cursor.x])
	      if (Number(mainTable[cursor.y][cursor.x].eId) > 0 && mainTable[cursor.y][cursor.x].realobject && (cmd = 'DBLCLICK'))
	         CallController({metakey: event.metaKey, altkey: event.altKey, shiftkey: event.shiftKey, ctrlkey: event.ctrlKey});
	      break;
	}
}

function SeekObjJSONProp(object, name, value)
{
 // Undefined value - search for non-existent json prop, null - any existing json prop, otherwise specified value json prop
 for (let prop in object)
     {
      if (object[prop] === undefined) if (value === undefined) return prop; else continue;
      if (value === null || object[prop][name] === value) return prop;
     }
}

function BoxApply(buttonprop)
{
 if (!box || typeof buttonprop != 'string' || typeof box.buttons[buttonprop] != 'object') return;
 const button = box.buttons[buttonprop];
 clearTimeout(buttonTimerId);

 if (button['call'])
    {
     saveDialogProfile(); // Save dialog box content and send it to the controller
     box.flags['event'] = buttonprop;
     cmd = button['call'];
     CallController(box);
     if (button['interactive'] === undefined) HideBox();
     return;
    }

 if (button['error']) displayMainError(button['error']);
 button['warning'] ? warning(button['warning']) : HideBox();
}

function BoxEventHandler(event)
{
 // Mouse up with any button already pushed? Release button element
 if (event.type === 'mouseup' && box.flags.buttonpush)
    {
     box.flags.buttonpush.classList.remove("buttonpush");
     delete box.flags.buttonpush;
    }

 // Dialog 'hint icon' event? Display element hint
 if (event.target.classList.contains('help-icon'))
    {
     hint = { x: event.x, y: event.y };
     ShowHint(box.dialog[box.flags.pad][box.flags.profile][event.target.attributes.name.value]["help"], hint.x, hint.y);
     return;
    }

 // Any dialog button event? Existing dataset-call attribute calls the controller, otherwise do nothing and hide dialog box
 if (event.target.classList.contains('button'))
    {
     event.type === 'mouseup' ? BoxApply(event.target.dataset.button) : (box.flags.buttonpush = event.target).classList.add("buttonpush");
     return;
    }

 // Mouse up event for a dialog box interface element except buttons? No actions left, so return
 if (event.type != 'mousedown') return;

 if (event.target.classList.contains('boxtablecellpush'))
    {
     clearTimeout(buttonTimerId);
     saveDialogProfile(); // Save dialog box content and send it to the controller
     box.flags['event'] = event.target.dataset.button;
     cmd = box.cmd;
     CallController(box);
     return;
    }

 // Dialog expanded div mousedown event?
 if (event.target.parentNode.classList && event.target.parentNode.classList.contains('expanded'))
    {
     if (selectExpandedDiv.firstChild.attributes.value.value != event.target.attributes.value.value) // Selected option differs from the current?
     if (selectExpandedDiv.attributes.type.value === 'select-profile')	// Select element is a profile select?
	{
	 saveDialogProfile();
	 box.flags.profile = event.target.innerHTML;		// Set event.target.innerHTML as a current profile
	 ShowBox();						// Redraw dialog box
	}
      else // Selected element is usual option select? // Set selected option as a current
	{
	 selectExpandedDiv.innerHTML = '<div value="' + event.target.attributes.value.value + '">' + event.target.innerHTML + '</div>';
	 box.dialog[box.flags.pad][box.flags.profile][selectExpandedDiv.attributes.name.value]["data"] = setOptionSelected(box.dialog[box.flags.pad][box.flags.profile][selectExpandedDiv.attributes.name.value]["data"], event.target.attributes.value.value);
	}
     expandedDiv.className = 'select expanded ' + uiProfile["dialog box select"]["effect"] + 'hide'; // Hide expanded div and break;
     return;
    }

 // Dialog box 'select' interface element mouse down event?
 if (event.target.parentNode.classList && event.target.parentNode.classList.contains('select') && (event.target.parentNode.attributes.name === undefined || box.dialog[box.flags.pad][box.flags.profile][event.target.parentNode.attributes.name.value]['readonly'] === undefined))
    {
     switch (event.target.parentNode.attributes.type.value)
	    {
	     case 'select-profile':
	     case 'select-one':
		  if ((/hide$/).test(expandedDiv.classList[2]) === false) // Expanded div visible? Hide it.
		     {
		      expandedDiv.className = 'select expanded ' + uiProfile["dialog box select"]["effect"] + 'hide';
		      break;
		     }
		  let data, inner = '', count = 0;
		  selectExpandedDiv = event.target.parentNode; // Set current select div that expanded div belongs to
		  if (selectExpandedDiv.attributes.type.value === 'select-one') // Define expandedDiv innerHTML for usual select, otherwise for profile select
		     {
		      if (typeof (data = box.dialog[box.flags.pad][box.flags.profile][selectExpandedDiv.attributes.name.value]["data"]) === 'string')
		      for (data of data.split('|'))
			  //if (data.length > 0 && (data[0] != '+' || data.length > 1)) // Check non empty options
			  if (data[0] == '+') inner += '<div class="selected" value="' + (count++) + '">' + data.substr(1) + '</div>'; // Current option
			   else inner += '<div value="' + (count++) + '">' + data + '</div>'; // Other options
		     }
		   else
		     {
		      for (data in box.dialog[box.flags.pad]) if (typeof box.dialog[box.flags.pad][data] === "object")
			  if (data === box.flags.profile) inner += '<div class="selected" value="' + (count++) + '">' + data + '</div>'; // Current option
			   else inner += '<div value="' + (count++) + '">' + data + '</div>'; // Other options
		     }
		  expandedDiv.innerHTML  = inner; // Fill expandedDiv with innerHTML
		  expandedDiv.style.top  = selectExpandedDiv.offsetTop + boxDiv.offsetTop + selectExpandedDiv.offsetHeight + 'px'; // Place expandedDiv top position
		  expandedDiv.style.left = selectExpandedDiv.offsetLeft + boxDiv.offsetLeft + 'px'; // Place expandedDiv left position
		  expandedDiv.className  = 'select expanded ' + uiProfile["dialog box select"]["effect"] + 'show'; // Show expandedDiv
		  break;
	     case 'select-multiple':
		  event.target.classList.toggle("selected");
		  break;
	    }
     return;
    }

 // Expanded div still visible and non expanded div mouse click?
 if ((/show$/).test(expandedDiv.classList[2]) === true && !event.target.classList.contains('expanded'))
    {
     expandedDiv.className = 'select expanded ' + uiProfile["dialog box select"]["effect"] + 'hide';
     return;
    }

 // Non active pad is selected?
 if (event.target.classList.contains('pad'))
    {
     saveDialogProfile();
     box.flags.pad = event.target.innerHTML; // Set event.target.innerHTML as a current pad
     ShowBox(); // Redraw dialog
     return;
    }
}

function ContextEventHandler(event)
{
 HideHint();

 // Prevent default context menu while dialog box up, mouse click on already existed context menu or context key press, 0 - no mouse button pushed, 1 - left button, 2 - middle button, 3 - right (context) button
 if (box || event.target == contextmenuDiv || event.target.classList.contains('contextmenuItems') || (contextmenu && event.which === 0))
    {
     event.preventDefault();
     return;
    }

 // Is cursor element content editable? Apply changes in case of no event.target match
 if (cursor.td?.contentEditable === EDITABLE)
    {
     if (cursor.td == event.target) return;
     cursor.td.contentEditable = NOTEDITABLE;
     if (mainTable[cursor.y][cursor.x].oId != NEWOBJECTID && (cmd = 'CONFIRM')) CallController(htmlCharsConvert(cursor.td.innerHTML));
      else mainTable[cursor.y][cursor.x].data = htmlCharsConvert(cursor.td.innerHTML);
     // Main field table cell click?
     if (event.target.tagName == 'TD' && !event.target.classList.contains('wrap') && !event.target.classList.contains('sidebar-od') && !event.target.classList.contains('sidebar-ov') && !event.target.classList.contains('changescount')) CellBorderToggleSelect(cursor.td, event.target);
    }

 // Context event on wrap icon cell? Use next DOM element
 let inner, target = event.target;
 if (target.classList.contains('wrap')) target = target.nextSibling;
  else if (cursor.td && event.which === 0) target = cursor.td; // If cursor and context key?
  else if (target.tagName == 'SPAN' && target.parentNode.tagName == 'TD') target = target.parentNode;

 if (target.classList.contains('sidebar-od')) inner = ACTIVEITEM + 'New Database</div>' + ACTIVEITEM + 'Database Configuration</div>'; // Context event on OD
  else if (target.classList.contains('sidebar-ov') || target === sidebarDiv) inner = ACTIVEITEM + 'New Database</div>' + GREYITEM + 'Database Configuration</div>'; // Context event on OV
  else switch (OVtype)
    {
     case 'Table':
          if (target === mainDiv || target === mainTablediv) // Context event on main div with any OV displayed or on main table div in case od table edge click!
	     {
	      inner = ACTIVEITEM + 'Add Object</div>' + GREYITEM + 'Delete Object</div>' + GREYITEM + 'Description</div>';
	      break;
	     }
	  if (target.tagName === 'TD')
	     {
	      let chart = '';
	      if (drag.x1 !== undefined)
		 {
		  const x = target.cellIndex, y = target.parentNode.rowIndex;
		  if (!(x >= Math.min(drag.x1, drag.x2) && x <= Math.max(drag.x1, drag.x2) && y >= Math.min(drag.y1, drag.y2) && y <= Math.max(drag.y1, drag.y2)))
		     {
		      UnSelectTableArea(drag.x1, drag.y1, drag.x2, drag.y2);
		      delete drag.x1;
		      CellBorderToggleSelect(cursor.td, target);
		     }
		  if (drag.x1 !== undefined && (drag.x1 != drag.x2 || drag.y1 != drag.y2)) chart = ACTIVEITEM + 'Chart</div>';
		 }
	       else
		 {
		  CellBorderToggleSelect(cursor.td, target);
		 }
	      if (mainTable[cursor.y]?.[cursor.x]?.realobject) inner = ACTIVEITEM + 'Add Object</div>' + ACTIVEITEM + 'Delete Object</div>' + ACTIVEITEM + 'Description</div>';
	       else inner = ACTIVEITEM + 'Add Object</div>' + GREYITEM + 'Delete Object</div>' + ACTIVEITEM + 'Description</div>';
	      inner += ACTIVEITEM + 'Copy</div>' + chart;
	      break;
	     }
          break;
     case 'Tree':
          if (target === mainDiv || target === mainTablediv || target.tagName === 'TD') inner = GREYITEM + 'Hide Object</div>' + ACTIVEITEM + 'Description</div>';
          break;
     default:
          if (target === mainDiv) inner = '';
    }

 if (inner != undefined)
    {
     event.preventDefault();
     contextmenu = { item : null };
     if (target.dataset?.odid) contextmenu.data = target.dataset.odid;

     inner += BASECONTEXT;
     user.length > CONTEXTITEMUSERNAMEMAXCHAR ? inner += ACTIVEITEM + 'Logout '+ user.substr(0, CONTEXTITEMUSERNAMEMAXCHAR - 2) + '..</div>' : inner += ACTIVEITEM + 'Logout '+ user + '</div>';
     contextmenuDiv.innerHTML = inner;

     // Context menu div left/top calculating
     if (event.which === 0) // Context key? 0 - no mouse button pushed, 1 - left button, 2 - middle button, 3 - right (context) button
        {
	 target = cursor.td;
	 const left = target.offsetLeft - mainDiv.scrollLeft;
	 const top = target.offsetTop - mainDiv.scrollTop;
	 if (!contextFitMainDiv(left + target.offsetWidth, top + target.offsetHeight) &&
	     !contextFitMainDiv(left - contextmenuDiv.offsetWidth, top + target.offsetHeight) &&
	     !contextFitMainDiv(left - contextmenuDiv.offsetWidth, top - contextmenuDiv.offsetHeight) &&
	     !contextFitMainDiv(left + target.offsetWidth, top - contextmenuDiv.offsetHeight) &&
	     !contextFitMainDiv(left + target.offsetWidth - contextmenuDiv.offsetWidth, top + target.offsetHeight) &&
	     !contextFitMainDiv(left, top + target.offsetHeight) &&
	     !contextFitMainDiv(left - contextmenuDiv.offsetWidth, top) &&
	     !contextFitMainDiv(left, top - contextmenuDiv.offsetHeight) &&
	     !contextFitMainDiv(left + target.offsetWidth, top))
	    {
	     contextmenuDiv.style.left = (mainDiv.offsetLeft + mainDiv.offsetWidth - contextmenuDiv.offsetWidth) + "px";
	     contextmenuDiv.style.top = (mainDiv.offsetTop + mainDiv.offsetHeight - contextmenuDiv.offsetHeight) + "px";
	    }
	}
      else
        {
	 if (mainDiv.offsetWidth + mainDiv.offsetLeft > contextmenuDiv.offsetWidth + event.clientX) contextmenuDiv.style.left = event.clientX + "px";
	  else contextmenuDiv.style.left = event.clientX - contextmenuDiv.clientWidth + "px";
	 if (mainDiv.offsetHeight + mainDiv.offsetTop > contextmenuDiv.offsetHeight + event.clientY) contextmenuDiv.style.top = event.clientY + "px";
	  else contextmenuDiv.style.top = event.clientY - contextmenuDiv.clientHeight + "px";
	}
     // Show context menu
     contextmenuDiv.className = 'contextmenu ' + uiProfile["context menu"]["effect"] + 'show';
     return;
    }

 HideContextmenu();
}

function MouseEventHandler(event)
{
 HideHint();

 // Return if mouse non left button click, 0 - no mouse button pushed, 1 - left button, 2 - middle button, 3 - right (context) button
 if (event.which != 1) return;

 if (event.type === 'mouseup') drag.element = null;

 // Dialog box is up? Process its mouse left button click
 if (box)
    {
     if (event.type === 'mousedown' && event.target.classList.contains('title'))
	{
	 drag.element = boxDiv;
	 drag.dispx = event.clientX - boxDiv.offsetLeft;
	 drag.dispy = event.clientY - boxDiv.offsetTop;
	}
     BoxEventHandler(event);
     return;
    }
 
 // Non mouse down event for a document with no dialog box? Return!
 if (event.type != 'mousedown') return;
 
 // Mouse clilck out of main field content editable table cell? Save cell inner html for a new element, otherwise send it to the controller
 if (cursor.td?.contentEditable === EDITABLE && cursor.td != event.target)
 if (mainTable[cursor.y][cursor.x].oId != NEWOBJECTID)
    {
     cmd = 'CONFIRM';
     CallController(htmlCharsConvert(cursor.td.innerHTML));
     cursor.td.contentEditable = NOTEDITABLE;
    }
  else
    {
     mainTable[cursor.y][cursor.x].data = htmlCharsConvert(cursor.td.innerHTML);
     cursor.td.innerHTML = toHTMLCharsConvert(mainTable[cursor.y][cursor.x].data);
     cursor.td.contentEditable = NOTEDITABLE;
    }

 // Mouse click on grey menu item or on context menu? Do nothing and return
 if (event.target.classList.contains('greyContextMenuItem') || event.target.classList.contains('contextmenu')) return; 

 // Mouse click on context menu item? Call controller with appropriate context menu item as a command. Else hide context menu and go on
 if (event.target.classList.contains('contextmenuItems'))
    {
     cmd = event.target.innerHTML;
     CallController(contextmenu.data);
     HideContextmenu();
     return;
    }
 HideContextmenu();

 // OD item (or its wrap icon before) mouse click? Wrap/unwrap OV list
 let next = event.target;
 if (next.classList.contains('wrap')) next = next.nextSibling;
  else if (next.classList.contains('changescount')) next = next.parentNode;

 // OD item (or its wrap icon before) mouse click? Refresh sidebar and wrap/unwrap database view list
 if (next.classList.contains('sidebar-od'))
    {
     /*if (Object.keys(sidebar[next.dataset.odid]['view']).length < 1) return;
     sidebar[next.dataset.odid]['wrap'] = !sidebar[next.dataset.odid]['wrap'];*/
     if (Object.keys(sidebar[next.dataset.odid]['view']).length > 0) sidebar[next.dataset.odid]['wrap'] = !sidebar[next.dataset.odid]['wrap'];
     cmd = 'SIDEBAR';
     CallController();
     return;
    }

 // OV item (or its wrap icon before) mouse click? Open OV in main field
 if (next.classList.contains('sidebar-ov'))
    {
     if (ODid != next.dataset.odid || OVid != next.dataset.ovid)
        {
	 if (sidebar[ODid]?.['active']) delete sidebar[ODid]['active'];
         sidebar[next.dataset.odid]['active'] = next.dataset.ovid;
	 drawSidebar(sidebar);
	}
     ODid = next.dataset.odid;
     OVid = next.dataset.ovid;
     OD = next.dataset.od;
     OV = next.dataset.ov;
     cmd = 'CALL';
     displayMainError('Loading...');
     CallController();
     return;
    }

 // Table type view mouse click event?
 if (OVtype === 'Table')
 if ((event.target.tagName == 'TD' && (next = event.target)) || (event.target.tagName == 'SPAN' && (next = event.target.parentNode) && next.tagName == 'TD'))
    {
     ResetUnreadMessages();
     if (drag.x1 !== undefined)
	{
	 UnSelectTableArea(drag.x1, drag.y1, drag.x2, drag.y2);
	 delete drag.x1;
	}
     CellBorderToggleSelect(cursor.td, next);
     if (mainTable[cursor.y]?.[cursor.x] && cursor.td.contentEditable != EDITABLE && !isNaN(cursor.eId) && cursor.oId === NEWOBJECTID)
	{
	 MakeCursorContentEditable(mainTable[cursor.y][cursor.x].data);
	}
      else
	{
	 drag.element = next;
	 drag.x1 = drag.x2 = cursor.x;
	 drag.y1 = drag.y2 = cursor.y;
	}
     return;
    }
}

function KeyboardEventHandler(event)
{
 HideHint();
 switch (event.keyCode)
	{
	 case 36: //Home
	      moveCursor(cursor.x, 0, true);
	      break;
	 case 35: //End
	      moveCursor(cursor.x, mainTableHeight - 1, true);
	      break;
	 case 33: //PgUp
	      moveCursor(cursor.x, Math.max(Math.trunc((mainDiv.scrollTop - 0.5*mainDiv.clientHeight)*mainTableHeight/mainDiv.scrollHeight), 0), true);
	      break;
	 case 34: //PgDown
	      moveCursor(cursor.x, Math.min(Math.trunc((mainDiv.scrollTop + 1.7*mainDiv.clientHeight)*mainTableHeight/mainDiv.scrollHeight), mainTableHeight - 1), true);
	      break;
	 case 38: //Up
	      SetContextmenuItem("UP");
	      moveCursor(0, -1, false);
	      break;
	 case 40: //Down
	      SetContextmenuItem("DOWN");
	      moveCursor(0, 1, false);
	      break;
	 case 13: //Enter
	      if (box)
	         {
		  if (event.target.tagName === 'INPUT' && (event.target.type === 'text' || event.target.type === 'password')) BoxApply(SeekObjJSONProp(box.buttons, 'enterkey', null));
		  break;
		 }
	      if (contextmenu) 
	         {
		  if (contextmenu.item)
		     {
		      cmd = contextmenu.item.innerHTML;
		      CallController(contextmenu.data);
		      HideContextmenu();
		     }
		  break;
		 }
	      if (cursor.td?.contentEditable === EDITABLE)
		 {
		  //--------------------
		  let confirm;
		  const combinationKey = uiProfile['application']['Editable content apply input key combination'];
		  //--------------------
		  if (event.altKey && combinationKey === 'Alt+Enter') confirm = true;
		  if (event.ctrlKey && combinationKey === 'Ctrl+Enter') confirm = true;
		  if (event.shiftKey && combinationKey === 'Shift+Enter') confirm = true;
		  if (!event.altKey && !event.ctrlKey && !event.shiftKey && combinationKey === 'Enter') confirm = true;
		  //--------------------		   
		  if (confirm)
		     {
		      cursor.td.contentEditable = NOTEDITABLE;
		      if (mainTable[cursor.y][cursor.x].oId != NEWOBJECTID)
			 {
			  cmd = 'CONFIRM';
			  CallController(htmlCharsConvert(cursor.td.innerHTML));
			 }
		       else
			 {
			  mainTable[cursor.y][cursor.x].data = htmlCharsConvert(cursor.td.innerHTML);
			  cmd = 'Add Object';
			  CallController();
			 }
		      break;
		     }
		  //-------------------- 
		  event.preventDefault();
		  document.execCommand('insertLineBreak', false, null); // "('insertHTML', false, '<br>')" doesn't work in fucking FF
		  break;
		 }
	      moveCursor(0, 1, false);
	      break;
	 case 37: //Left
	      moveCursor(-1, 0, false);
	      break;
	 case 39: //Right
	      moveCursor(1, 0, false);
	      break;
	 case 27: //Esc
	      if (box)
		 {
		  if ((/show$/).test(expandedDiv.classList[2])) // Expanded div visible? Hide it, otherwise hide dialog box
		     {
		      expandedDiv.className = 'select expanded ' + uiProfile["dialog box select"]["effect"] + 'hide';
		      break;
		     }
		  if (box.flags?.esc != undefined) // Box with esc flag set?
		     {
		      let button = SeekObjJSONProp(box.buttons, 'call');
		      if (!button || !(button = box.buttons[button])) break;
    		      if (button['error']) displayMainError(button['error']);
    		      button['warning'] ? warning(button['warning']) : HideBox();
		     }
		  break;
		 }
	      if (cursor.td?.contentEditable === EDITABLE)
		 {
		  cursor.td.contentEditable = NOTEDITABLE;
		  cursor.td.innerHTML = cursor.olddata;
		  break;
		 }
	      CellBorderToggleSelect(null, cursor.td, false); // Normilize cell outline off buffered dashed style cell
	      HideContextmenu();
	      break;
	 case 45: //Ins
	      if (box || contextmenu || !cursor.td || cursor.td.contentEditable === EDITABLE) break;
	      if (event.ctrlKey) CopyBuffer(event.shiftKey);
	      if (mainTable[cursor.y]?.[cursor.x]?.['realobject'] && !isNaN(cursor.eId) && (cmd = 'INS'))
		 CallController({metakey: event.metaKey, altkey: event.altKey, shiftkey: event.shiftKey, ctrlkey: event.ctrlKey});
	      break;
	 case 46: //Del
	      if (box || contextmenu || !cursor.td || cursor.td.contentEditable === EDITABLE || isNaN(cursor.eId)) break;
	      if (mainTable[cursor.y][cursor.x].oId === NEWOBJECTID && !(mainTable[cursor.y][cursor.x].data = cursor.td.innerHTML = '')) break;
	      if (mainTable[cursor.y]?.[cursor.x]?.['realobject'] && (cmd = 'DEL')) CallController({metakey: event.metaKey, altkey: event.altKey, shiftkey: event.shiftKey, ctrlkey: event.ctrlKey});
	      break;
	 case 113: //F2
	      if (box || contextmenu || !cursor.td) break;
	      if (cursor.td?.contentEditable != EDITABLE && mainTable[cursor.y]?.[cursor.x]?.['realobject'] && !isNaN(cursor.eId))
	      if (mainTable[cursor.y][cursor.x].oId != NEWOBJECTID && (cmd = 'F2'))
		 CallController({metakey: event.metaKey, altkey: event.altKey, shiftkey: event.shiftKey, ctrlkey: event.ctrlKey});
	       else
		 MakeCursorContentEditable(mainTable[cursor.y][cursor.x].data);
	      break;
	 case 123: //F12
	      if (box || contextmenu || !cursor.td) break;
	      if (cursor.td?.contentEditable != EDITABLE && mainTable[cursor.y]?.[cursor.x]?.['realobject'] && !isNaN(cursor.eId) && (cmd = 'F12'))
	         CallController({metakey: event.metaKey, altkey: event.altKey, shiftkey: event.shiftKey, ctrlkey: event.ctrlKey});
	      break;
	 default: // space, letters, digits
	      if (box || contextmenu || !cursor.td || cursor.td.contentEditable === EDITABLE) break;
	      if (event.ctrlKey)
		 {
		  if (event.keyCode == 65 && OVtype === 'Table') SelectTableArea(drag.x1 = 0, drag.y1 = 0, drag.x2 = mainTableWidth - 1, drag.y2 = mainTableHeight - 1);
		  if (event.keyCode == 67) CopyBuffer(event.shiftKey);
		 }

	      if (!mainTable[cursor.y] || !mainTable[cursor.y][cursor.x] || isNaN(cursor.eId) || !rangeTest(event.keyCode, SPACELETTERSDIGITSRANGE)) break;
	      if (mainTable[cursor.y][cursor.x].oId === NEWOBJECTID && !event.ctrlKey && !event.altKey && !event.metaKey)
	         {
		  MakeCursorContentEditable(mainTable[cursor.y][cursor.x].data);
		  break;
		 }
	      if (mainTable[cursor.y]?.[cursor.x]?.['realobject'] && (cmd = 'KEYPRESS'))
	         {
		  CallController({string: event.key, metakey: event.metaKey, altkey: event.altKey, shiftkey: event.shiftKey, ctrlkey: event.ctrlKey});
		  if (event.keyCode == 32 || event.keyCode == 111 || event.keyCode == 191) event.preventDefault(); // Prevent default action - page down (space) and quick search bar in Firefox browser (keyboard and numpad forward slash)
		 }
	}
}

function MakeCursorContentEditable(data)
{
 try { cursor.td.contentEditable = EDITABLE; }
 catch { cursor.td.contentEditable = (EDITABLE = 'true'); } // Fucking FF doesn't support 'plaintext' contentEditable type
 cursor.olddata = toHTMLCharsConvert(mainTable[cursor.y][cursor.x].data);
 // Fucking FF has bug inserting <br> in case of cursor at the end of content, so empty content automatically generates <br> tag! Fuck!
 typeof data === 'string' ? cursor.td.innerHTML = toHTMLCharsConvert(data, false) : cursor.td.innerHTML = cursor.olddata;
 if (cursor.td.innerHTML.slice(-4) != '<br>') ContentEditableCursorSet(cursor.td);
 cursor.td.focus();
}

function FromController(json)
{
 try { input = JSON.parse(json.data); }
 catch { input = json; }
 
 if (input.customization)	{ uiProfileSet(input.customization); styleUI(); }
 if (input.auth != undefined)	{ user = input.auth; }
 if (input.cmd === undefined)	{ warning('Undefined server message!'); return; }

 switch (input.cmd)
	{
	 case 'DIALOG':
	 case 'UPDATEDIALOG':
	      if ((cursor.td && cursor.td.contentEditable === EDITABLE) || (input.cmd === 'UPDATEDIALOG' && !box)) break;
	      let scrollLeft, scrollTop;
	      if (box?.contentDiv?.scrollLeft) scrollLeft = box.contentDiv.scrollLeft;
	      if (box?.contentDiv?.scrollTop) scrollTop = box.contentDiv.scrollTop;
	      box = input.data;
	      ShowBox(scrollLeft, scrollTop);
	      break;
	 case 'EDIT':
	      if (box || (cursor.td && cursor.td.contentEditable === EDITABLE) || !objectTable[input.oId][input.eId]) break;
	      if (cursor && mainTable[cursor.y] && mainTable[cursor.y][cursor.x])
	      if (mainTable[cursor.y][cursor.x].oId === input.oId && mainTable[cursor.y][cursor.x].eId === input.eId)
	         MakeCursorContentEditable(input.data);
	      break;
	 case 'SET':
	      let x, y, value;
	      if (objectTable[input.oId])
	         for (let eid in input.data)
		     if (objectTable[input.oId][eid])
		        {
			 x = objectTable[input.oId][eid].x;
			 y = objectTable[input.oId][eid].y;
			 if (typeof input.data[eid] === 'object')
			    {
			     input.data[eid]['value'] ? value = toHTMLCharsConvert(input.data[eid]['value']) : value = '';
			     mainTablediv.rows[y].cells[x].contentEditable != EDITABLE ? mainTablediv.rows[y].cells[x].innerHTML = value : cursor.olddata = value;
			     mainTablediv.rows[y].cells[x].setAttribute('style', input.data[eid]['style']);
			     mainTable[y][x].data = input.data[eid]['value'];
			     mainTable[y][x].hint = input.data[eid]['hint'];
			     mainTable[y][x].description = input.data[eid]['description'];
			    }
			 CellBorderToggleSelect(null, cursor.td, false);
		        }
	      break;
	 case 'CALL':
	 case 'SIDEBAR':
	 case 'New Database':
	 case 'Database Configuration':
	      Hujax("view.php", FromController, input.data);
	      break;
	 case 'Table':
	      paramsOV = input.params;
	      drawMain(input.data, input.props);
	      break;
	 case 'Tree':
	      DrawTree(input.tree, input.direction);
	      break;
	 case '':
	      break;
	 default:
	      input = { alert: "Unknown server message '" + input.cmd + "'!" };
	}
	
 if (input.sidebar)		drawSidebar(input.sidebar);
 if (input.log)			lg(input.log); 
 if (input.error != undefined)	displayMainError(input.error);
 if (input.alert)		warning(input.alert);
 if (input.count)		IncreaseUnreadMessages(input.count.odid, input.count.ovid);
}

function IncreaseUnreadMessages(odid, ovid)
{
 if (!sidebar[odid]) return;
 if (!sidebar[odid]['count'][ovid]) sidebar[odid]['count'][ovid] = 0;
 sidebar[odid]['count'][ovid] ++;
 drawSidebar(sidebar);
}

function ResetUnreadMessages()
{
 if (!sidebar[ODid]['count'][OVid]) return;
 sidebar[ODid]['count'][OVid] = 0;
 drawSidebar(sidebar);
}

function DrawPie(ctx, centr, beginAngle, endAngle, color)
{
 if (beginAngle == endAngle) return;
 ctx.beginPath();
 ctx.fillStyle = color;
 ctx.moveTo(centr, centr);
 ctx.arc(centr, centr, centr * 0.8, beginAngle, endAngle);
 ctx.lineTo(centr, centr);
 ctx.stroke();
 ctx.fill();
}

function CallController(data)
{
 let object;

 switch (cmd)
	{
	 case 'New Database':
	 case 'Task Manager':
	      object = { "cmd": cmd };
	      if (typeof data != 'string') object.data = data;
	      break;
	 case 'Database Configuration':
	 case 'SIDEBAR':
	 case 'CALL':
	 case 'LOGIN':
	      object = { "cmd": cmd };
	      if (data != undefined) object.data = data;
	      break;
	 case 'Copy':
	      CopyBuffer();
	      break;
	 case 'Chart':
	      if (drag.x1 === undefined) break;
	      let sum = 0, key, value, chart = {};
	      const horizontal = drag.x1 === drag.x2 ? false : true;				// X-axis is horiszontal?
	      if (drag.x1 === drag.x2) drag.x2++; else if (drag.y1 === drag.y2) drag.y2++;	// Extend selected area

	      for (let y = Math.min(drag.y1, drag.y2); y <= Math.max(drag.y1, drag.y2); y++)
	      for (let x = Math.min(drag.x1, drag.x2); x <= Math.max(drag.x1, drag.x2); x++)
		  {
		   if ((horizontal && y === Math.min(drag.y1, drag.y2)) || (!horizontal && x === Math.min(drag.x1, drag.x2)))
		      {
		       if (mainTable[y]?.[x]) key = mainTable[y][x].data; else key = '';
		       continue;
		      }
		   if (horizontal) if (mainTable[Math.min(drag.y1, drag.y2)]?.[x]) key = mainTable[Math.min(drag.y1, drag.y2)][x].data; else key = '';
		   if (mainTable[y]?.[x]) value = Math.trunc(Number(mainTable[y][x].data)); else value = 0;
		   if (typeof key !== 'string') key = '';
		   if (typeof value !== 'number' || isNaN(value)) value = 0;
		   if (chart[key] === undefined) chart[key] = 0;
		   chart[key] += value;
		   sum += value;
		  }
	      if (!sum)
		 {
		  warning("No numerical data found!");
		  break;
		 }

	      mainDiv.innerHTML = '<canvas id="chart"><h1>Please update your browser! Canvas is not supported</h1></canvas>';
	      const canvas = document.getElementById('chart');
	      canvas.width = Math.trunc(mainDiv.offsetWidth * 0.9);
	      canvas.height = Math.trunc(mainDiv.offsetHeight * 0.9);
	      const ctx = canvas.getContext('2d');
	      /*ctx.mozImageSmoothingEnabled = false;
	      ctx.webkitImageSmoothingEnabled = false;
	      ctx.msImageSmoothingEnabled = false;
	      ctx.imageSmoothingEnabled = false;*/

	      let endAngle = beginAngle = Math.PI * 1.5, pies = [];
	      const centr = Math.min(canvas.width, canvas.height) * 0.45;
	      for (key in chart) pies.push({name: key, angle: (chart[key]/sum) * Math.PI * 2});
	      pies.sort(function(a, b) { return b.angle - a.angle; });
	      value = 0;
	      for (key in uiProfile['chart colors']) if (pies[value]) pies[value++]['color'] = uiProfile['chart colors'][key];
	      sum = 0;
	      for (let pie of pies)
		  {
		   if (pie['color'] === undefined)
		      {
		       sum += pie.angle;
		       value = true;
		       continue;
		      }
		   beginAngle = endAngle;
		   endAngle += pie.angle;
		   DrawPie(ctx, centr, beginAngle, endAngle, pie['color'])
		  }
	      break;
	 case 'Description':
	      let cell, msg = '', count = 1;
	      if (cursor.td && mainTable[cursor.y]?.[cursor.x] && (cell = mainTable[cursor.y][cursor.x]) && cell.oId) // Cursor cell info
		 {
		  msg += '<span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Cursor cell:</span>\n';
		  if (cell.oId === NEWOBJECTID && Number(cell.eId) > 0) msg += `Input new object data for element id: ${cell.eId}`;
		   else if (cell.oId === TITLEOBJECTID) msg += `Title for element id: ${cell.eId}`;
		   else msg += `Object id: ${cell.oId}\nElement id: ${cell.eId}`;
		  msg += `\nPosition 'x': ${cursor.x}\nPosition 'y': ${cursor.y}`;
		 }
	      if (cell && cell.oId >= STARTOBJECTID) // Object element info
		 {
		  msg += '\n\n<span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Object element:</span>\n';
	          if (cell.version) cell.version === '0' ? msg += 'Object version: object has been deleted' : msg += `Object version: ${cell.version}\nActual version: ${cell.realobject ? 'yes' : 'no'}`;
		  if (cell.description) msg += `Element description property:\n${cell.description}`;
		 }
	      if (true) // Database info
		 {
		  msg += '\n\n<span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Database:</span>\n';
		  msg += `Object Database: ${OD}\nObject View${OV[0] === '_' ? ' (hidden from sidebar)' : ''}: ${OV} (${objectsOnThePage} objects)`;
	          cell = '';
		  for (let param in paramsOV) cell += `\n  <span style="color: #999;">${count++}. ${param.substr(1).replace(/_/g, ' ')}: ${paramsOV[param]}</span>`;
		  if (cell) msg += `\nView input parameters:${cell}`;
		 }
	      if (true) // Table info
		 {
		  msg += '\n\n<span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Table:</span>\n';
		  msg += `Columns: ${mainTableWidth}\nRows: ${mainTableHeight}`;
		  if (drag.x1 !== undefined)
		     {
		      count = new Set();
		      for (let y = Math.min(drag.y1, drag.y2); y <= Math.max(drag.y1, drag.y2); y++)
		      for (let x = Math.min(drag.x1, drag.x2); x <= Math.max(drag.x1, drag.x2); x++)
			  if ((cell = mainTable[y][x]) && cell.oId >= STARTOBJECTID && cell.realobject) count.add(cell.oId);
		      msg += `\nSelected area:\n  <span style="color: #999;">Objects count: ${count.size}</span>`;
		      msg += `\n  <span style="color: #999;">Width, cells: ${Math.abs(drag.x2 - drag.x1) + 1}</span>`;
		      msg += `\n  <span style="color: #999;">Height, cells: ${Math.abs(drag.y2 - drag.y1) + 1}</span>`;
		     }
		 }
	      warning(msg, 'Description', false);
	      break;
	 case 'Help':
	      box = help;
	      ShowBox();
	      break;
	 case 'Add Object':
	      if (objectTable === undefined) break;
	      object = { "cmd": 'INIT', "data": {} };
	      if (objectTable[String(NEWOBJECTID)] != undefined)
	         for (let eid in objectTable[String(NEWOBJECTID)])
		     {
		      object['data'][eid] = mainTable[objectTable[String(NEWOBJECTID)][eid].y][objectTable[String(NEWOBJECTID)][eid].x]['data'];
		      mainTable[objectTable[String(NEWOBJECTID)][eid].y][objectTable[String(NEWOBJECTID)][eid].x]['data'] = '';
		      mainTablediv.rows[objectTable[String(NEWOBJECTID)][eid].y].cells[objectTable[String(NEWOBJECTID)][eid].x].innerHTML = '';
		     }
	      break;
	 case 'Delete Object':
	      if (mainTable[cursor.y] && mainTable[cursor.y][cursor.x] && mainTable[cursor.y][cursor.x].realobject)
		 object = { "cmd": 'DELETEOBJECT', "oId": mainTable[cursor.y][cursor.x].oId };
	      break;
	 case 'CONFIRM':
	       cursor.td.innerHTML = toHTMLCharsConvert(htmlCharsConvert(cursor.td.innerHTML));
	 case 'CONFIRMDIALOG':
	 case 'DBLCLICK':
	 case 'KEYPRESS':
	 case 'INS':
	 case 'DEL':
	 case 'F2':
	 case 'F12':
	      object = { cmd: cmd };
	      if (cursor.td && mainTable[cursor.y]?.[cursor.x])
	         {
	          object.oId = mainTable[cursor.y][cursor.x].oId;
		  object.eId = mainTable[cursor.y][cursor.x].eId;
		 }
	      if (data != undefined) object.data = data;
	      break;
	 case '':
	      break;
	 default:
	      if (cmd.substr(0, 7) != 'Logout ')
		 {
		  warning("Undefined application message: '" + cmd + "'!");
		  return;
		 }
	      object = { cmd: 'LOGOUT' };
	}
	
 if (object)
    {
     object.OD = OD;
     object.OV = OV;
     object.ODid = ODid;
     object.OVid = OVid;

     try { socket.send(JSON.stringify(object)); }
     catch {}
     if (socket.readyState === 3) CreateWebSocket();
    }
}

function displayMainError(errormsg, reset = true)
{
 clearTimeout(loadTimerId);

 if (errormsg.substr(0, 7) === 'Loading')
    {
     errormsg === 'Loading...' ? errormsg = 'Loading' : errormsg += '.';
     loadTimerId = setTimeout(displayMainError, 500, errormsg);
     errormsg = errormsg.replace(/Loading/, '').replace(/./g, '&nbsp;') + errormsg;
    }
 if (errormsg !== 'Loading') mainDiv.innerHTML = '<h1>' + errormsg + '</h1>';

 mainTableRemoveEventListeners();
 if (reset) OVtype = '';
}

function mainTableAddEventListeners()
{
 if (!mainTablediv) return;
 mainTablediv.addEventListener('dblclick', MainDivEventHandler);
 mainTablediv.addEventListener('mouseleave', MainDivEventHandler);
 mainTablediv.addEventListener('paste', (event) => {});
}

function mainTableRemoveEventListeners()
{
 if (!mainTablediv) return;
 mainTablediv.removeEventListener('dblclick', MainDivEventHandler);
 mainTablediv.removeEventListener('mouseleave', MainDivEventHandler);
 mainTablediv.removeEventListener('paste', MainDivEventHandler); 
}

function htmlCharsConvert(string)
{
 if (!string) return '';
 for (let i = 0; i < HTMLSPECIALCHARS.length; i ++)
     string = string.replace(new RegExp(HTMLSPECIALCHARS[i], 'g'), HTMLUSUALCHARS[i]);

 if (string.charCodeAt(string.length - 1) === 10) return string.slice(0, -1); else return string; // Last char is '\n' (ASCII code 0x0A)? Remove it.
}

function EncodeHTMLSpecialChars(string)
{
 if (!string) return '';
 //if (brtagonly) return string.replace(new RegExp('\n', 'g'), '<br>');
 for (let i = 0; i < HTMLSPECIALCHARS.length; i ++) string = string.replace(new RegExp(HTMLUSUALCHARS[i], 'g'), HTMLSPECIALCHARS[i]);
 return string;
}

function EncodeSpanTag(string)
{
 const start = string.indexOf('>') + 1;
 const length = string.lastIndexOf('<') - start;
 return string.substr(0, start) + EncodeHTMLSpecialChars(string.substr(start, length)) + string.substr(start + length);
}

function toHTMLCharsConvert(string, spantag = true)
{
 if (!string) return '';

 if (!spantag)
    {
     string = EncodeHTMLSpecialChars(string);
     return string.replace(/<br>$/g, "<br><br>"); // FF fucking bug
    }

 let result, newstring = '';
 while (true)
    if (result = /< *span[ .*?>|>](.|\n)*?< *\/span *>/.exec(string))
       {
	newstring += EncodeHTMLSpecialChars(string.substr(0, result.index)) + EncodeSpanTag(result[0]);
	string = string.substr(result.index + result[0].length);
       }
     else
       {
	newstring += EncodeHTMLSpecialChars(string);
	break;
       }
 return newstring.replace(/<br>$/g, "<br><br>"); // FF fucking bug
}

function CellBorderToggleSelect(oldCell, newCell, setFocusElement = true)
{
 mainDiv.focus();
 if (oldCell)
    {
     oldCell.style.outline = "none";
     oldCell.style.boxShadow = "none";
    }
 if (!newCell) return;
 if (uiProfile['main field table cursor cell']['outline'] != undefined) newCell.style.outline = uiProfile['main field table cursor cell']['outline'];
 if (uiProfile['main field table cursor cell']['shadow'] != undefined) newCell.style.boxShadow = uiProfile['main field table cursor cell']['shadow'];
 if (setFocusElement)
    {
     cursor.td = newCell;
     cursor.x = newCell.cellIndex;
     cursor.y = newCell.parentNode.rowIndex;
     cursor.oId = cursor.eId = 0;
     if (mainTable[cursor.y] && mainTable[cursor.y][cursor.x])
        {
	 cursor.oId = mainTable[cursor.y][cursor.x].oId;
	 cursor.eId = mainTable[cursor.y][cursor.x].eId;
	}
    }
}

function contextFitMainDiv(x, y)
{
 if (mainDiv.offsetWidth < x + contextmenuDiv.offsetWidth || mainDiv.offsetHeight < y + contextmenuDiv.offsetHeight || x < 0 || y < 0) return false;
 contextmenuDiv.style.left = mainDiv.offsetLeft + x + "px";
 contextmenuDiv.style.top = mainDiv.offsetTop + y + "px";
 return true;
}

function moveCursor(x, y, abs)
{
 if (box || !cursor.td || cursor.td.contentEditable === EDITABLE || contextmenu || (abs && cursor.x == x && cursor.y == y)) return;
 
 let a, b, newTD;
 if (abs)
    {
     a = x;
     b = y;
    }
  else 
    {
     a = cursor.x + x;
     b = cursor.y + y;
    }
    
 if (a < 0 || a >= mainTableWidth || b < 0 || b >= mainTableHeight) return;
 
 newTD = mainTablediv.rows[b].cells[a];
 if (abs || isVisible(newTD) || (!isVisible(cursor.td) && tdVisibleSquare(newTD) > tdVisibleSquare(cursor.td)) || (y == 0 && xAxisVisible(newTD)) || (x == 0 && yAxisVisible(newTD)))
    {
     if (!abs) event.preventDefault();
     CellBorderToggleSelect(cursor.td, newTD);
    }
}

function tdVisibleSquare(elem)
{
 let width = Math.min(elem.offsetLeft - mainDiv.scrollLeft + elem.offsetWidth, mainDiv.offsetWidth) - Math.max(elem.offsetLeft - mainDiv.scrollLeft, 0);
 let height = Math.min(elem.offsetTop - mainDiv.scrollTop + elem.offsetHeight, mainDiv.offsetHeight) - Math.max(elem.offsetTop - mainDiv.scrollTop, 0); 
 return width * height;
}

function isVisible(e)
{
 if (xAxisVisible(e) && yAxisVisible(e)) return true;
 return false;
}

function xAxisVisible(e)
{
 if (e.offsetLeft >= mainDiv.scrollLeft && e.offsetLeft - mainDiv.scrollLeft + e.offsetWidth <= mainDiv.offsetWidth + 1) return true;
 return false;
}

function yAxisVisible(e)
{
 if (e.offsetTop >= mainDiv.scrollTop && e.offsetTop - mainDiv.scrollTop + e.offsetHeight <= mainDiv.offsetHeight + 1) return true;
 return false;
}

function rangeTest(a, b)
{
 let l = b.length;
 for (let i = 0; i < l; i += 2)
     if (a >= b[i] && a <= b[i+1]) return true;
 return false;
}

function ShowBox(scrollLeft, scrollTop)
{
 let inner = getInnerDialog();
 if (!inner) // No content? Hide dialog and return;
    {
     HideBox();
     return;
    }

 HideContextmenu();
 if (typeof box.title === 'string') inner = '<div class="title">' + toHTMLCharsConvert(box.title) + '</div>' + inner; // Add title
     
 inner += '<div class="footer">'; // Add 'footer' div and buttons to (if exist)
 for (let button in box.buttons)
     {
      if (box.buttons[button]['call']) box.cmd = box.buttons[button]['call'];
      if (box.buttons[button]['value'])
	 {
	  inner += '<div class="button" data-button="' + button + '"';
	  if (box.buttons[button]['style']) inner += ' style="' + escapeHTMLTags(box.buttons[button]['style'].trim()) + '"';
	  inner += '>' + escapeHTMLTags(box.buttons[button]['value']) + '</div>';
	 }
      if (box.buttons[button]['timer'])
	 {
	  clearTimeout(buttonTimerId);
	  buttonTimerId = setTimeout(BoxApply, box.buttons[button]['timer'], button);
	 }
     }
 boxDiv.innerHTML = inner + '</div>'; // Finish 'footer' div

 boxDiv.style.left = Math.trunc((document.body.clientWidth - boxDiv.offsetWidth)*100/(2*document.body.clientWidth)) + "%"; // Calculate left box position
 boxDiv.style.top = Math.trunc((document.body.offsetHeight - boxDiv.offsetHeight)*100/(2*document.body.offsetHeight)) + "%"; // Calculate top box position
 boxDiv.className = 'box ' + uiProfile["dialog box"]["effect"] + 'show'; // Show box div
 if (uiProfile["dialog box"]["filter"]) sidebarDiv.style.filter = mainDiv.style.filter = uiProfile["dialog box"]["filter"]; // Apply filters if exist
 uiProfile["dialog box"]["effect"] === 'none' ? SetFirstDialogElementFocus() : boxDiv.addEventListener('transitionend', SetFirstDialogElementFocus); // Set focus on first text-input element
 
 box.contentDiv = boxDiv.querySelector('.boxcontentwrapper');
 if (scrollLeft) box.contentDiv.scrollLeft = scrollLeft;
 if (scrollTop) box.contentDiv.scrollTop = scrollTop;
}

function getInnerDialog()
{
 if (!box || !box.dialog || typeof box.dialog != 'object') return;
 let element, data, count = 0, readonly, checked, inner = '';
 
 //------------------Creating current pad and profile if not exist------------------
 if (!box.flags) box.flags = {};
 if (!box.flags.pad) box.flags.pad = "";
 if (!box.flags.profile) box.flags.profile = "";
    
 //------------------Checking current pad. First step - seeking current pad match------------------
 for (element in box.dialog) if (typeof box.dialog[element] === "object")
     {
      if (count === 0) data = element; // Remember first pad as a current pad for default
      if (element === box.flags.pad) data = count; // Match case? Assign current 'element' for current pad
      count++;
     }
 // Empty dialog with zero pads number? Return empty html.
 if (count === 0) return '';
 // No match - assing first pad for default
 if (typeof data === 'string') box.flags.pad = data;
 // Pads count more than one? Creating pad block DOM element.
 if (count > 1 || box.flags.display_single_pad != undefined)
    {
     // Creating pad wrapper
     inner = '<div class="padbar" style="display: flex; flex-direction: row; justify-content: flex-start;">';
     // Inserting pad divs
     for (element in box.dialog) if (typeof box.dialog[element] === "object")
      if (element === box.flags.pad) inner += '<div class="activepad">' + element + '</div>';
       else inner += '<div class="pad">' + element + '</div>';
     // Closing pad wrapper tag
     inner += '</div>';
    }

 //------------------Checking current profile in current pad. First step - initiate variables------------------
 count = 0;
 // Seeking current profile match.
 for (element in box.dialog[box.flags.pad]) if (typeof box.dialog[box.flags.pad][element] === "object")
     {
      if (count === 0) data = element; // Remember first profile as a current profile for default
      if (element === box.flags.profile) data = count; // Match case? Assign current 'element' for current profile
      count++;
     }
 // Empty dialog[<current_pad>] with zero profiles number? Return current pad empty content.
 if (count === 0) return inner;
 // No match - assing first profile for default
 if (typeof data === 'string') box.flags.profile = data;
 // Profiles count more than one? Creating profile select DOM element.
 if (count > 1 || box.flags.padprofilehead?.[box.flags.pad] != undefined)
    {
     // Add profile head
     if (box.flags.padprofilehead != undefined && box.flags.padprofilehead[box.flags.pad] != undefined) inner += '<pre class="element-headers">' + box.flags.padprofilehead[box.flags.pad] + '</pre>';
     // In case of default first profile set zero value to use as a select attribute
     if (typeof data === 'string') data = 0;
     // Add header, select block and divider
     inner += '<div class="select" type="select-profile"><div value="' + data + '">' + box.flags.profile + '</div></div><div class="divider"></div>';
    }
    
 //------------------Parsing interface element in box.dialog.<current pad>.<current profile>------------------
 for (let name in box.dialog[box.flags.pad][box.flags.profile])
     {
      element = box.dialog[box.flags.pad][box.flags.profile][name];
      // Display element hint icon
      //if (element.help != undefined && typeof element.help == "string") data = ' <span name="' + name + '" class="help-icon"> ? </span>'; else data = '';
      // Display element head
      if (element.head === undefined || typeof element.head !== "string")
	 {
	  inner += '<div></div>';
	 }
       else
	 {
	  inner += '<pre class="element-headers"';
	  if (element.style && typeof element.style === 'string') inner += ` style="${element.style}"`;
	  inner += '>' + toHTMLCharsConvert(element.head);
	  if (element.help && typeof element.help == "string") inner += ' <span name="' + name + '" class="help-icon"> ? </span>';
	  inner += '</pre>';
	 }
      // Filling interface element data, leave empty string in case of undefined
      if (element.data != undefined && typeof element.data === "string") data = element.data; else data = '';
      switch (element.type)
	     {
	      case 'table':
		   if (data != '')
		      {
		       try   { data = JSON.parse(data); }
		       catch { break; }
		       let row, cell;
		       inner += `<table class="boxtable"><tbody>`;
		       for (row in data)
		    	   {
			    inner += '<tr>';
			    for (cell in data[row])
				{
				 inner += '<td class="boxtablecell';
				 data[row][cell].call != undefined ? inner += ' boxtablecellpush" data-button="' + cell + '"' : inner += '"';
				 if (data[row][cell].style)  inner += ` style="${escapeHTMLTags(data[row][cell].style)}"`;
				 inner += '>' + escapeHTMLTags(data[row][cell].value) + '</td>';
				}
			    inner += '</tr>';
			   }
		       inner += '</tbody></table>';
		      }
	    	   break;
	      case 'select-multiple':
		   if (data != '')
		      {
		       inner += `<div class="select" name="${name}" type="select-multiple">`;
		       for (data of data.split('|'))
		    	   if (data != '')
			      {
			       pos = data.search(/[^\+]/);
			       if (pos > 0) inner += '<div class="selected">' + data.substr(pos) + '</div>';
			        else inner += '<div>' + data + '</div>';
			      }
		       inner += '</div>';
		      }
	    	   break;
	      case 'select-one':
		   if (data != '')
		      {
		       count = 0;
		       data = element.data = setOptionSelected(data);
		       for (data of data.split('|'))	// Handle all option divided by '|'
		    	   {
			    if (data[0] == '+')		// The option is selected (first char is '+')? Add it to the dialog interface
			       {
			        inner += `<div class="select" name="${name}" type="select-one"><div value="${count}">${data.substr(1)}</div></div>`;
			        break;
			       }
			    count ++;
			   }
		      }
	    	   break;
	      case 'checkbox':
	      case 'radio':
		   if (data != '')
		      {
		       element.readonly != undefined ? readonly = ' disabled' : readonly = '';
		       for (data of data.split('|')) if (data != '')
			  {
			   (count = data.search(/[^\+]/)) > 0 ? checked = ' checked' : checked = '';
			   inner += `<input type="${element.type}" class="${element.type}" name="${name}"${checked}${readonly}><label for="${name}">${data.substr(count)}</label>`;
			  }
		      }
		   break;
	      case 'password':
	      case 'text':
		   if (element.label) inner += `<label for="${name}" class="element-headers">${element.label}</label>`;
	    	   element.readonly != undefined ? readonly = ' readonly' : readonly = '';
		   inner += '<input type="' + element.type + '" class="' + element.type + '" name="' + name + '" value="' + escapeDoubleQuotes(data) + '"' + readonly + '>';
		   break;
	      case 'textarea':
		   element.readonly != undefined ? readonly = ' readonly' : readonly = '';
		   inner += '<textarea type="' + element.type + '" class="textarea" name="' + name + '"' + readonly + '>' + data + '</textarea>';
		   break;
	     }
      if (element.line != undefined) inner += '<div class="divider"></div>';
     }
     
 if (inner != '')
    {
     data = '';
     if (box.flags?.style && typeof box.flags.style === 'string') data = ' style ="' + escapeHTMLTags(box.flags.style) + '"';
     return '<div class="boxcontentwrapper"'+ data +'>' + inner + '</div>';
    }
}

function SetFirstDialogElementFocus()
{
 for (let element of boxDiv.querySelectorAll('input, textarea'))
  if (element.attributes.type.value === 'password' || element.attributes.type.value === 'text' || element.attributes.type.value === 'textarea')
  if (!element.readOnly)
     {
      element.focus();
      break;
     }
}

function saveDialogProfile()
{
 const init = {};
 boxDiv.querySelectorAll('input, .select, textarea').forEach(function(element)
			   {
			    switch (element.attributes.type.value)
				   {
				    case 'select-multiple':
					 const el = box.dialog[box.flags.pad][box.flags.profile][element.attributes.name.value];
					 el.data = '';
					 element.querySelectorAll('div').forEach(function(option)
								{
								 if (option.classList.contains('selected')) el.data += '+' + option.innerHTML + '|';
								  else el.data += option.innerHTML + '|';
								});
					 if (el.data.length > 0) el.data = el.data.slice(0, -1);
					 break;
				    case 'checkbox':
				    case 'radio':
					 if (init[element.attributes.name.value] === undefined) init[element.attributes.name.value] = box.dialog[box.flags.pad][box.flags.profile][element.attributes.name.value]["data"] = '';
					 if (element.checked) box.dialog[box.flags.pad][box.flags.profile][element.attributes.name.value]["data"] += '+' + element.nextSibling.innerHTML + '|';
					  else box.dialog[box.flags.pad][box.flags.profile][element.attributes.name.value]["data"] += element.nextSibling.innerHTML + '|';
					 break;
				    case 'password':
				    case 'text':
				    case 'textarea':
					 box.dialog[box.flags.pad][box.flags.profile][element.attributes.name.value]["data"] = element.value;
					 break;
				   }
			   });
}

function setOptionSelected(data, value) // Function selects option (by setting '+' char before the option) by pointed value and return result data with options divided by '|'
{
 if (typeof data !== 'string' || data === '') return '';			// Undefined or empty data? Return empty string 
 if (typeof value == 'string') value = Number(value);
 
 let option, pos, result = '', count = 0;
 for (option of data.split('|'))						// Handle all option divided by '|'
     if (option.length > 0 && (option[0] != '+' || option.length > 1))		// Check non empty options
        {
	 pos = option.search(/[^\+]/);						// Calculate pos of first non '+' char
	 if (value === count)							// Match value to set
	    {
	     option = '+' + option.substr(pos);
	     value = true;
	    }
	  else
	    {
	     option = option.substr(pos);					// Remove first '+' chars
	     if (pos > 0 && value === undefined)				// Option has some first '+' and no value to set?
	        {
		 value = true;
		 option = '+' + option;
		}
	    }
	 if (option != '') result += option + '|';				// Add non empty option to the result string
	 count ++;
	}
 if (value === undefined || value !== true) result = '+' + result;		// No selected option at all? Use first option for default
 //return result.slice(0, -1);							// Return result string without last divided char '|'
 return result;									// Return result string with last divided char '|'
}
		       
function HideBox()
{
 clearTimeout(buttonTimerId);
 if (box)
    {
     box = null;
     if (uiProfile["dialog box"]["effect"] != 'none') boxDiv.removeEventListener('transitionend', SetFirstDialogElementFocus);
     boxDiv.className = 'box ' + uiProfile["dialog box"]["effect"] + 'hide';
     expandedDiv.className = 'select expanded ' + uiProfile["dialog box select"]["effect"] + 'hide';
     mainDiv.style.filter = 'none';
     sidebarDiv.style.filter = 'none';
    }
}

function getAbsoluteX(element, flag = '')
{
 let disp = 0;								// Select element left position
 if (flag == 'end') disp = element.offsetWidth;				// Select element right position
 if (flag == 'middle') disp = Math.trunc(element.offsetWidth/2);	// Select element middle position
 
 return element.offsetLeft - element.scrollLeft + mainDiv.offsetLeft - mainDiv.scrollLeft + mainTablediv.offsetLeft - mainTablediv.scrollLeft + disp;
}

function getAbsoluteY(element, flag = '')
{
 let disp = 0;								// Select element top position
 if (flag == 'end') disp = element.offsetHeight;			// Select element bottom position
 if (flag == 'middle') disp = Math.trunc(element.offsetHeight/2);	// Select element middle position
 
 return element.offsetTop - element.scrollTop + mainDiv.offsetTop - mainDiv.scrollTop + mainTablediv.offsetTop - mainTablediv.scrollTop + disp;
}

function collapseMainTable(undefinedCellCollapse) // Function removes collapse flag tagged rows and columns from main object table
{
 let row, col, disp, collapse;
 
 // Fisrt step - main table rows collpase status check 
 row = disp = 0;
 while (row < mainTableHeight) // Parse main table rows one by one
       {
        // Set row default collapse status to false
        collapse = false;
	
	// Current row exist? Check all its columns (except undefined and titles) to be collapsible
        if (mainTable[row])
	   {
	    for (col = 0; col < mainTableWidth; col++) if (mainTable[row][col] && mainTable[row][col].oId != TITLEOBJECTID && mainTable[row][col].oId != NEWOBJECTID)
		if (mainTable[row][col].collapse != undefined && mainTable[row][col].data === '') collapse = true;
		 else { collapse = false; break; }
	   }
	 else if (undefinedCellCollapse) collapse = true; // Set collapse status to true if undefined row and collapse property for undefined cell (undefinedCellCollapse) is true
	   
	// Collapse main table row (remove it by splice), increase displacement and decrease main table height
	if (collapse === true)
	   {
	    mainTable.splice(row, 1);
	    disp++;
	    mainTableHeight--;
	   }
	 else // Otherwise (in case of no collpase) correct current row 'y' coordinate on displacement value and go to next row
	   {
	    if (disp > 0 && mainTable[row] != undefined)
	       for (col = 0; col < mainTableWidth; col++)
		   if (mainTable[row][col] != undefined && mainTable[row][col].realobject)
		      objectTable[mainTable[row][col].oId][mainTable[row][col].eId].y -= disp;
	    row++;
	   }
       }
 
 // Second step - main table columns collpase status check
 col = disp = 0;
 while (col < mainTableWidth) // Parse main table columns one by one
       {
        // Set row default collapse status to false
	collapse = false;
	
	// If collapse property for undefined cell (undefinedCellCollapse) is true, then check the whole column on undefined cells
	if (undefinedCellCollapse)
	   {
	    collapse = true;
	    for (row = 0; row < mainTableHeight; row++)
		if (mainTable[row] != undefined && mainTable[row][col] != undefined) { collapse = false; break; }
	   }
	
	// Check the whole column (except undefined and titles) cell to be all collapsible
	if (collapse === false) for (row = 0; row < mainTableHeight; row++)
	    if (mainTable[row] && mainTable[row][col] && mainTable[row][col].oId != TITLEOBJECTID && mainTable[row][col].oId != NEWOBJECTID)
	       if (mainTable[row][col].collapse != undefined && mainTable[row][col].data === '') collapse = true;
		else { collapse = false; break; }
	 
	// Collapse main table column (remove it by splice), increase displacement and decrease main table width
	if (collapse === true)
	   {
	    for (row = 0; row < mainTableHeight; row++) if (mainTable[row] != undefined) mainTable[row].splice(col, 1);
	    disp++;
	    mainTableWidth--;
	   }
	 else // Otherwise (in case of no collpase) correct current column 'x' coordinate on displacement value and go to next column
	   {
	    if (disp > 0) for (row = 0; row < mainTableHeight; row++)
	       if (mainTable[row] != undefined && mainTable[row][col] != undefined && mainTable[row][col].realobject)
		  objectTable[mainTable[row][col].oId][mainTable[row][col].eId].x -= disp;
	    col++;
	   }
       }
}

function HideContextmenu()
{
 if (contextmenu)
    {
     contextmenuDiv.className = 'contextmenu ' + uiProfile["context menu"]["effect"] + 'hide';
     contextmenu = null;
    }
}

function SetContextmenuItem(newItem)
{
 if (!contextmenu || box) return;
 
 if (typeof newItem === 'string')
    {
     const direction = newItem;
     if (!contextmenu.item)
     if (direction === "UP") contextmenu.item = contextmenuDiv.firstChild; 	// Set start item position in case of absent current active item)
      else contextmenu.item = contextmenuDiv.lastChild;				// In case of down direction start item is last item
     newItem = contextmenu.item;						// Assign new item to current active item
     do 
       {
        if (direction === "UP")
	   {
	    newItem = newItem.previousElementSibling;				// Take previous element as context menu item
	    if (!newItem) newItem = contextmenuDiv.lastChild;			// if previous element is null, take last element as context menu item
	   }
	else
	   {
	    newItem = newItem.nextElementSibling;				// Take previous element as context menu item for 'down' direction
	    if (!newItem) newItem = contextmenuDiv.firstChild;			// if previous element is null, take last element as context menu item
	   }
       }
     while (newItem != contextmenu.item && newItem.classList.contains('greyContextMenuItem'));
     if (newItem.classList.contains('greyContextMenuItem')) newItem = contextmenu.item = null;
    }
 
 if (contextmenu.item) contextmenu.item.classList.remove('activeContextMenuItem'); 
 if (newItem) newItem.classList.add('activeContextMenuItem');
 contextmenu.item = newItem;
}

function ShowHint(content, x, y)
{
 hintDiv.innerHTML = '<pre>' + content + '</pre>'; // Add content
 hintDiv.style.left = x + "px";
 hintDiv.style.top = y + "px";
 hintDiv.className = 'hint ' + uiProfile["hint"]["effect"] + 'show';
}

function HideHint()
{
 if (hint)
    {
     clearTimeout(tooltipTimerId);                                              
     hintDiv.className = 'hint ' + uiProfile["hint"]["effect"] + 'hide';
     hint = null;
    }
}

function ContentEditableCursorSet(element)
{
 range.selectNodeContents(element);
 range.collapse(false);
 selection.removeAllRanges();
 selection.addRange(range);
}

function EllipsesClip(string, limit)
{
 if (typeof limit === 'string') limit = Number(limit);
 if (!string || typeof string !== 'string' || typeof limit !== 'number') return '';
 if (limit < 3) limit = 3;

 if (string.length > limit) return string.substr(0, limit - 2) + '..';
 return string;
}

function CopyBuffer(plaintext)
{
 const textarea = document.createElement('textarea');
 if (plaintext)
    {
     cursor.td.innerText ? textarea.value = cursor.td.innerText : textarea.value = '\u0000';
    }
  else
    {
     mainTable[cursor.y]?.[cursor.x]?.['data'] ? textarea.value = mainTable[cursor.y][cursor.x]['data'] : textarea.value = '\u0000';
    }
 document.body.appendChild(textarea);
 textarea.select();
 
 try { document.execCommand('copy'); }
 catch { document.body.removeChild(textarea); return; }

 document.body.removeChild(textarea);
 cursor.td.style.outline = uiProfile['main field table cursor cell']['clipboard outline'];
}

function escapeDoubleQuotes(string)
{
 return string.replace(/"/g,"&quot;");
}

function escapeHTMLTags(string)
{
 if (string) return string.replace(/</g,"&lt;").replace(/"/g,"&quot;");
 return '';
}

function warning(text, title, log = true)
{
 if (!text || typeof text != 'string') return;
 if (typeof title != 'string') title = 'Warning';
 box = { title: title, dialog: {pad: {profile: {element: {head: '\n' + text}}}}, buttons: {OK: {value: "&nbsp;   OK   &nbsp;"}}, flags: {esc: "", style: "min-width: 500px; min-height: 65px; max-width: 1500px; max-height: 500px;"} };
 ShowBox();
 if (log) lg(text);
}

function isObjectEmpty(object, excludeProp)
{
 if (typeof object != 'object') return false;
 for (let element in object) if (!(object[element] === '' || element === excludeProp)) return false;
 return true;
}

function uiProfileSet(customization)
{
 let selector, property;
 customization = customization.pad;

 // Fill uiProfile from customization dialog
 for (selector in customization)
     {
      for (property in customization[selector]) if (property != 'element0' && property != 'element1')
	  uiProfile[selector][customization[selector][property]['head'].slice(0, -1)] = customization[selector][property]['data'];
      if (customization[selector]['element0'] != undefined && customization[selector]['element0']['target'] != undefined)
         uiProfile[selector]['target'] = customization[selector]['element0']['target'];
     }

 // Define css classes attribute string for all table cell types
 isObjectEmpty(uiProfile["main field table title cell"], 'target')	? titlecellclass = '' : titlecellclass = ' class="titlecell"';
 isObjectEmpty(uiProfile["main field table newobject cell"], 'target')	? newobjectcellclass = '' : newobjectcellclass = ' class="newobjectcell"';
 isObjectEmpty(uiProfile["main field table data cell"], 'target')	? datacellclass = '' : datacellclass = ' class="datacell"';
 isObjectEmpty(uiProfile["main field table undefined cell"], 'target')	? undefinedcellclass = '' : undefinedcellclass = ' class="undefinedcell"';
}

function styleUI()
{
 let element, key, inner = '';
 
 for (element in uiProfile)
  if (uiProfile[element]["target"] != undefined)
     {
      inner += uiProfile[element]["target"] + " {";
      for (key in uiProfile[element])
	  if (NOTARGETUIPROFILEPROPS.indexOf(key) === -1 && key.substr(0, 1) != '_' && uiProfile[element][key] != '') inner += key + ": " + uiProfile[element][key];
      inner += '}'; //https://dev.to/karataev/set-css-styles-with-javascript-3nl5, https://professorweb.ru/my/javascript/js_theory/level2/2_4.php
     }
 style.innerHTML = inner;
 // Output uiProfile array to te console to use it as a default customization configuration
 // lg("$uiProfile = json_decode('" + JSON.stringify(uiProfile).replace(/'/g, "\\'") + "', true);");
}

const help = { title: 'Help', dialog: {

"System description": { profile: { element: { line: '', style: 'font-family: monospace, sans-serif;', head:
`Tabels application is a new generation system to display, store and manage its data by lots of ways. Application data
is a set of custom data tables, each table consists of identical objects, which, in turn, are set of service and user
defined elements.

Data tables of itself is called Object Database (OD) and can be changed or created by appropriate sidebar context menu.
OD contains Object Views (OV). Views define what objects (via 'object selection') and elements (via 'element layout')
should be displayed and how. See appropriate help sections.

As it was mentioned above - each object is a set of service and user-defined elements.
Five bult-in service elements (id, owner, datetime, version, lastversion) represent service data,
which is set automatically while object is changed or created. Each custom user-defined
object element (with eid1, eid2.. as a column names in database structure) is created by the user and may have some
handlers (any script or binary) to create and manage element data, see 'handler' help section. User-defined element data
is a JSON, that may consist of any defined properties, but some of them have special assingment:
- 'value' is displayed in a table cell as a main element text
- 'hint' is displayed as element hint text on mouse cursor cell navigation
- 'description' is element description displayed on context menu description click
- 'style' is a css style attribute value applied to html table <td> tag.
- 'link' is element connection list one by line, each connection is a link name and remote object and its element
  selections,  all three divided by '|'.
- 'alert' property is reserved by the controller to send alert messages to the client side
- 'cmd' property is reserved by the controller to identify handler command
Other element properties are custom and used to store additional element data, see example below.

Lets have a look to the simple OD example with only two elements - Name and Phone number.
OD name will be 'Users', OV name - 'Address book', OV template - 'table', OV object selection - 
all objects, OV element layout - default. See OD configuration help section.
Additionally, first object element (Name) stores user password in a 'pass' JSON property.

OV display will look like:
+------+--------------+
| Name | Phone number |
+------+--------------+
| Mary | +1 555 11111 |
+------+--------------+
| John | +1 555 22222 |
+------+--------------+

And internal object database structure will be:
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+
| id | owner | datetime            | version | lastversion | eid1                                   | eid2                      |
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+
| 3  | root  | 1970-07-26 17:48:01 | 1       | 1           | '{"value": "Mary", "pass": "$6$WF.."}' | '{"value": "+1 555 111"}' |
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+
| 4  | root  | 1970-07-26 17:49:33 | 1       | 1           | '{"value": "John", "pass": "$6$GH.."}' | '{"value": "+1 555 222"}' |
+----+-------+---------------------+---------+-------------+----------------------------------------+---------------------------+

After the 1st object (Mary) phone number change and the 2nd (John) remove the structure will be:
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

John user record after phone number change by user 'root' has version 2 value and lastversion flag set, while object version 1
has unset lastversion flag. Note that previous object version values for non-changed user-defined elements are set to NULL,
so John phone number next change creates object version 3 with previous object version 2 element 'eid1' value set to NULL.
Other words - just to save some disk space non actual object versions consist of changed elements only.
As object versions are object data instnces - deleted objects are not removed from database, but marked by zero version only.
All previous versions object data is available in that case, but cannot be changed at all. Considering all of this, all object
history is transparent and available and all data is native. This is a global application conception - all functionality is
documented and clear.

Go on. Authentication and authorization are password based. Usernames and their passwords are stores in 'Users' OD.
Only one user instance can be logged in, so logged in instance automatically logs out another instance via other host/browser.
To add new user click context menu 'Add Object' on any 'Users' view (on default view 'All users' for example), then double 
click just-added 'user' element to call user properties (such as password, OD add permission and group membership) dialog box.
User 'name' cannot be changed after creation. Also user cannot change his OD add permissions and group membership
to avoid all priveleges granting by himself to himself. Note that users and groups must be all uniq, so user and group with
one name 'root' is not allowed. Groups cannot be directly created, any group name in a user group membership list is
considered as an existing group. So all user/group permission lists in a Database configuration (see appropriate help section)
are treated as user name list, but in case of non-existent username - the name is treated as a name of a group.`
}}},

"Database Configuration": { profile: { element: { line: '', style: 'font-family: monospace, sans-serif;', head:
`To create Object Database (OD) just enter its name in the dialog box called via 'New Database' sidebar context menu.
Other database configuration can be continued here or later via 'Database Configuration' sidebar context menu call.
Let's have a look at database configuration dialog box and its features.

First is 'Database' tab. This configuration section sets up general database features (name, description, limits) and its permission.
Database name can be changed after creation or removed (via empty name and description values set).
Database permissions represent itself four user/group (one by line) list input text areas, one list for each configuration
section which changes are restricted by that user/group list. Lists can be of two types,
'allowed' type allowes changes for specified users and groups in the list and disallowed for others, thereby 'disallowed'
type disallows changes for specified users and groups and allows for others. Be aware of empty 'allowed' lists - this setting
'freezes' the tab, so any changes will not be allowed for any user. Also note that 'Database' section tab empty 'allowed'
list blocks any permission changes for other database configuration sections ('Element', 'View' and 'Rule') for any user forever.

Second configuration section is 'Element'. Each object consists of builtin service elements and custom user elements.
To add any custom element select 'New element' profile and fill at least one field - name, description or any event handler.
So all of them in an element profile should be set empty in order to remove element. Element name is used 
as a default element header text and specified description text is displayed as a hint on object view element header
navigation. See 'Element layout' help section for details. Other element options are 'Element type'
and event 'Handlers'. Unique element type sets element JSON property 'value' unuqueness among all objects in OD.
For a example, first element (username) of builtin OD 'Users' defined as an uniq type, so duplicated names are excluded.
Next - event handlers. Handler is a command line script or binary called on specified event occur. Handlers are optional
and defined for necessary events only. Events are occured on object processes ('INIT' event at object creation, 'CHANGE'
at object element data change), keyboard/mouse element push/click ('DBLCLICK', 'KEYPRESS', 'F2', 'F12', 'INS', 'DEL') and
handler feedback ('CONFIRM', 'CONFIRMDIALOG'). See 'Handlers' help section for details.

Third configuration section - 'View'. The same way for add/delete operations is used - empty name removes the view, 'New view'
option with any name specified creates it. View name first char '_' hides unnecessary views from user sidebar, so the view can
be called from element handlers only (see 'Handler' help section for details).
The object view (OV) of itself is a mechanism to output selective object data on specified template. Objects for the view are
obtained from the selection process (see 'Object Selection' for details), their elements - from 'Element layout' (see
appropriate help section for details).
Next view configuration is a scheduler. It is a *nix like cron service which executes specified command line for specified
element for all objects of the view. Scheduler field is an instructions list (one by line) of the general form:
'run this command at this time on this date for this element id'.
Blank lines and leading spaces and tabs are ignored. Instruction is an entry list separated by spaces with next format:
<minute 0-59> <hour 0-23> <day of month 1-31> <month 1-12> <day of week 0-7> <element id number> <command line>
First five entries are datetime parameters, sixth - element id number from 1 and the rest of the line is treated as a 
command line to execute. Datetime parameters may be set to asterisk (*), which always stands for 'first-last'. Ranges of numbers
are allowed. Ranges are two numbers separated with a hyphen. The specified range is inclusive. For example, 8-11 for an 'hours'
entry specifies execution at hours 8, 9, 10 and 11. Lists are allowed. A list is a set of numbers (or ranges) separated by
commas. Examples: '1,2,5,9', '0-4,8-12'. Step values can be used in conjunction with ranges. Following a range with '/<number>'
specifies skips of the number's value through the range. For example, '0-3/2' can be used in the hours field to specify 0,2
hours execution. Zero or 7 day of week values are Sunday.
So the service tries to check all view schedules of all databases each minute. In case of datetime parameters match - the
specified command line (handler) for specified element id is exexcuted for every object of the view. Next instruction or view
schedule is not checked until the previous job finished. Next example clears (by setting empty value) element2 value every
day at 3:00:
0 3 * * * 2 php text.php SET
And at the end - two text areas at the bottom are view permissions for read/write operations. 'Disallowed list' restricts
specified users/groups and allows others, while 'Allowed list' allows specified and restricts others.

Last configuration section is 'Rule'. Object instances before and after CRUD operations (add, delete, change) are passed to the
analyzer and tested on all rule profiles in alphabetical order until the match is found for both pre and post rules. When a
match is found, the action corresponding to the matching rule profile is performed. Default action is accept.
Accept action applies changes, while reject action cancels all changes made by the operation. Rule test is a simple SQL query
selection, so non empty result of that selection - match is found, empty result - no match. Query format:
SELECT .. FROM 'OD' WHERE id=<object id> AND version=<version number> AND <(pre|post)-processing rule>
Version number defines object instance version before (for pre-processing rule) and after (for post-processing rule) operation.
Also both rules may contain a parameter :user, that is replaced with the actual username (initiated the operation) in the query
string. Note that pre-processing rule for 'add object' operation is ignored - no object before operation, so nothing to check.
Empty or error rules are match case, but error rule displays error message instead of a rule message.
Simple example: pre-processing rule "JSON_EXTRACT(eid1, '$.value')='root'" with the action 'reject' and rule apply operation
'delete object' prevents root user removal. Example query will look like:
SELECT .. FROM data_1 WHERE id='4' AND version='1' AND JSON_EXTRACT(eid1, '$.value')='root'
The query above is successfull, so perfomed action ('reject') disallowes user removal.
Next example with both rules empty and reject action for all operations freezes the database, so all changes are rejected.
Another example: first profile with action accept preprocessing rule "owner=':user'" and second profile reject
action with both empty rules - allowes to change self-created objects only.`
}}},

"Object Selection": { profile: { element: { style: 'font-family: monospace, sans-serif;', head:
`Object selection is a part of the sql query string that selects objects for the specified view. Let's have a look to the
object structure stored in database to select required objects effectively. Each object consists of next SQL table columns:
- id. Object identificator.
- lastversion. Boolean value 0 or 1 indicates whether it is last object version or not. See 'version' field.
- version. Indicates object version number started from '1', that is assinged to just created new objects.
  After object any change, controller creates a new database record with the changed object copy, increments its version 
  and set lastversion flag to 1. This mechanism allows to store every object version, so user can trace object data changing 
  and find out when, how and who object was changed by. Deleted objects are marked by zero version and lastversion flag set.
- owner. The user this object version was created by.
- datetime. Date and time this object version was created at.
- eid<element id>. JSON type element data. 

Error object selection string selects no objects, empty string - all actual objects.
Controller selects objects via database query with next format:
'SELECT <element layout selection> FROM data_<OD id> <object selection>'
Default object selection (empty string) selects all relevant (lastversion=1) and non deleted objects (version!=0), so
selection string 'WHERE lastversion=1 AND version!=0' is applied and result query is:
'SELECT .. FROM data_<OD id> WHERE lastversion=1 AND version!=0'

This format together with object structure provides effective selection of object sets via powerful SQL capabilities!
To make object selection process more flexible user can use some parameters in object selection string. These parameters
should start from char ':' and finish with space, single or double qoutes, backslash or another ':'. Parsed parameter
name is set as a question (with chars '_' replaced with spaces) in client side dialog box at the object view call open.
Parameter name :user is reserved and replaced with the username the specified view is called by.

Object selection string example for 'Logs' object database:
'WHERE lastversion=1 AND version!=0 AND JSON_EXTRACT(eid1, '$.value')=':Select_log_string_to_search'.
The selection example displays dialog with input question 'Select log string to search' and takes input data to pass it
to the controller to build the result query that selects all objects (log messages) with .

Another object selection option is a link name. The option of itself represents one or multiple names divided
by '|' or '/'. With that option specified the selection process takes only first selected/found object (others are
ignored) and builds the tree (based on object elements 'link' property matched link-names) from that head object. Result
selection is that tree object list. The tree for link names divided by '|' is built on all specified names, while for
names divided by '/' - only for the first matched per object. Only one delimiter can be used for the view, so name list
'name1|name2/name3' will be divided into two names: name1 and 'name2/name3'.
See 'Element layout' help section for the tree template.`
}}},

"Element layout": { profile: { element1: { line: '', style: 'font-family: monospace, sans-serif;', head:
`Element layout is a JSON strings list. JSON format depends on specified OV template. Let's first consider table template.
It is is a main way to display and manage OD data and allows to format data many different ways - from classic tables
to public chats, see examples below. Each JSON for a table template element layout defines elements and their behaviour -
such as table cell position, style attribute, OV start event and etc.. JSON possible properties are:

- 'oid'. Object id number in range from 0. Attributes (x, y, style, ..) are applied to the specified object id together
  with element id. New object (input text data to add new objects) id is 1. Header (title) object id is 2. Database object
  identificators starts from 3. Absent 'oid' property is treated as property with zero value. Zero 'oid' attributes are
  applied to all objects in the selection, while specific 'oid' numbers to the specified object only.
- 'eid'. Built-in service elements (id, version, owner, datetime, lastversion) or user defined element id number started
  from 1. Attributes (x, y, style, ..) are applied to the specified element id together with object id. Absent 'eid' property
  is treated as property with zero value. Zero 'eid' attributes are applied to all elememnts of defined object - for zero
  'oid' allowed properties are 'style' (css style attribute for <td> cell with no object element placed in) and table (css
  attributes for <table> html element), for specific 'oid' - only css style for <td> cell with specific object element.
  See table below for 'oid'/'eid' combinations allowed properties.
- 'x','y'. Object element position is defined by table cell x,y coordinates. These properties are arithmetic expressions
  that may include two variables: 'n' (object serial number in the selection) and 'q' (total number of objects). For a
  example, "y": "n+1" will place first object in the selection to the second row (y=1), second object - to the  third
  row (y=2). Note that column/row numeration starts from 0. See layout examples below. 
- 'event'. Mouse double click (DBLCLICK) or key press (F2, F12, INS, DEL, KEYPRESS) event emulation after OV has been opened.
  Symbol key push event 'KEYPRESS' should be specified with the additional string to be passed to the handler as an input arg.
  For example, "event": "KEYPRESSa" will emulate key 'a' pushed at the object view open. See 'Handler' help section for
  details. Incorrect event value - no emulation, but cursor is set to the position specified by 'x','y' properties.
  Only first event entry is emalated, all others are ignored.
- 'hidecol', 'hiderow'. These properties collapse (hide) table columns or rows with appropriate element 'hidecol'/'hiderow'
  values. For example, "hiderow": "" will hide all empty table rows.
- 'style'. HTML css style attribute (see appropriate css documentaion) for <td> tag the specified object element is placed in.
  Zero 'eid' style for non zero 'oid' defines styles for all <td> cells specified object is placed.
  Zero 'eid'/'oid' style defines style for undefined cell (no object element placed).
- 'table'. JSON HTML css attributes for tag <table> with attribute names as properties, for zero 'oid' and 'eid' only.
- 'value'. Table cell element main text. For new/title objects only.
- 'hint'. Table cell element hint, displayed as a hint on a table cell cursor navigation. For new/title objects only.

  'oid'/'eid' combinations properties table:
  +---------+--------------------------------+----------------------------------------------+
  |   \\ eid |                                |                                              |
  |    \\    | 0                              | id,version,owner,datetime,lastversion,1,2,.. |
  | oid \\   |                                |                                              |
  +---------+--------------------------------+----------------------------------------------+
  | 0       | style (undefined cell style)   | x, y, style                                  |
  |selection| table (html table attributes)  | event, hiderow, hidecol                      |
  +---------+--------------------------------+----------------------------------------------+
  | 1       | style                          | x, y, style                                  |
  | new     |                                | event, hiderow, hidecol, value, hint         |
  +---------+--------------------------------+----------------------------------------------+
  | 2       | style                          | x, y, style                                  |
  | title   |                                | event, hiderow, hidecol, value*, hint*       |
  +---------+--------------------------------+----------------------------------------------+
  | 3..     | style                          | x, y, style                                  |
  |         |                                | event, hiderow, hidecol                      |
  +---------+--------------------------------+----------------------------------------------+
  *Properties are set automatically (value is element name, hint is element description) if not exist

Let's parse 'All logs' OV element layout of 'Logs' database:
{"eid":"id", "oid":"2", "x":"0", "y":"0"}
{"eid":"id", "x":"0", "y":"n+1"}
{"eid":"datetime", "oid":"2", "x":"1", "y":"0"}
{"eid":"datetime", "x":"1", "y":"n+1"}
{"eid":"1", "oid":"2", "x":"2", "y":"0"}
{"eid":"1", "x":"2", "y":"n+1"}

First JSON defines 'id' element for title object (oid=2), it will be on the top left corner of the table (x=0, y=o).
Second JSON defines 'id' element for database objects (oid=0) in the selection, all objects are placed to the first
table column (y=0) and to the rows in order starting from second row (y=n+1) - first object (n=0) goes to the second
row (y=1), second object (n=1) goes to the third row (y=2) and so on.
Similarly for two next elements datetime and log message element #1 (eid=1), except that column ('x' coordinate)
position for datetime is x=1 (second column) and for log message is x=2 (third column).

Element layout could be set to one of predefined templates, which are converted to JSONs anyway. Possible values are:
'' - Empty layout value selects all user-defined elements and displays them as a classic table with the title first.
'*' - One asterisk behaves like empty value, but 'new' object is added to the table just right after 'title' object.
'**' - Two asterisks behaves like empty value, but built-in service elements ('id', 'version', 'owner'..) are added.
'***' - Three asterisks behaves like one asterisk, but built-in service elements ('id', 'version', 'owner'..) are added.`
},

element2: { line: '', style: 'font-family: monospace, sans-serif;', head:
`
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
correct JSON is used for the template only. The JSON should contain element identificators (id,version,date,owner..1,2..)
as a JSON property names (property values are ignored) plus 'direction' tree property 'up' or 'down' (for default).
Empty layout field - all user defined elements plus 'id' with 'down' direction are used.
Correct empty JSON '{}' as an 'element layout' displays no content, while all error JSONs - only 'id' element.`
}}},

"Handlers": { profile: { element: { line: '', style: 'font-family: monospace, sans-serif;', head:
`
Element handler is any executable script or binary called by the contoller when specified event occurs. Events occur on
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

					    USER EVENT.
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
    INIT (user adds/create new object via context menu or new object input)
 - delete object:
    DELETEOBJECT (user removes the object via context menu, the only event which is not passed to the handler - controller
		  just removes the object from DB (via zero marked version) with no any handler call)

Also there are some events generated by the controller only, they are:
    CHANGE (after one element data is changed via SET/RESET handler commands the event 'CHANGE' occurs for other elements
	    of the object. It is necessary for these other elements to react on any element change, so one handler changes
	    its element data, other elements of the object receives 'CHANGE' event)
    SCHEDULE (event is generated by system scheduler, handler command line executed for that event is retrieved from
	      appropriate field in a 'View' section of Database configuration)

					    HANDLER CALL.
User events above are received from the client side by the controller and passed to the handler for the initiated element
(or all elements) via executing specified handler command line (set in database configuration). Note, that 'INIT' client event
(new object creation) is passed to all elements of the object, while others events - to initiated element only. 
Handler command line is executed as it is specified in a database configuration ('Elemnt' tab) with one moment - angle
brackets quoted arguments are parsed to be replaced by the next values:
 - <user> is replaced by user name the specified event was initiated by. 'SCHEDULER' event is initiated by built-in 'system' user.
 - <oid> is replaced by object id the event was initiated on.
 - <event> is replaced by event name (DBLCLICK, KEYPRESS, CHANGE..) the handler is called on.
 - <title> is replaced by element title (element name in Database configuration) the event was initiated on.
 - <datetime> is replaced by date and time in format 'Y-m-d H:i:s'.
 - <data> is replaced by event data.
    For KEYPRESS event data is a JSON string with next format:
    {"string": "<key char>", "altkey": "",  "ctrlkey": "", "metakey": "shiftkey", "": ""}
    Property "string" is one key char, other properties do exist only in case of appropriate key pushed. Meta key for Mac OS
    is 'Cmd' key, for Window OS - 'Window' key.
    For INIT event data argument text content in 'new object' element table cells if exist, otherwise <data> is empty string ''.
    For CONFIRM event after html element <td> editable content apply  - <data> argument is that content text data.
    For CONFIRMDIALOG after dialog box apply - <data> argument is a JSON that represents dialog structure*
    For DBLCLICK, CHANGE and SCHEDULE events <data> argument is undefined.
 - <JSON argument> is replaced by retrieved element data and should be in next format:
    {"ODid": .., "OD": .., "OVid": .., "OV": .., "selection": .., "element": .., "prop": .., "limit": .., ":..": ..}
    First four properties identify database view. In case of database/view identificators ("ODid"/"OVid") omitted 
    datavase/view names ("OD"/"OV") is used. Both identificator and name omitted - current database or view is used.
    Specified view must have 'Table' template.
    Next two properties "selection" and "limit" are SQL query parts to select necessary objects which element data need to be
    retrieved: SELECT .. FROM .. WHERE "selection" LIMIT "limit". Omitted "selection" property - current object is used,
    omitted "limit" - number is set to 1.
    Last two properties "element" and "prop" select element of the selected object. Omitted "element" current element (the
    event was initiated on) is used. Omitted "prop" - property "value" of element JSON data is used. Property "element"
    is exact element id (user element id number or service element name - id,owner,datetime..) or regular expression ("/../")
    to search first match among all elements specified in database view 'Element layout'. <JSON argument> may consist of some
    replacements - property names with first char ':'. So these properties act as another JSON argument, so its values are
    retrieved and replaced in "element" regular expressions and "selection". It is a kind of a recursion, max recursive calls
    number is 3. See 'Examples' help section. In case of exact element id and multiple objects selection - final result is 
    element JSON property of all selected objects ((each of new line).
    Therefore, 'JSON argument' selects database view objects (based on "selection" and "limit"), takes element and its property
    (based on "element" and "prop") or searches necessary element via first match "element" regular expression and then
    replaces <JSON argument> with the retrieved value. All properties are optional, so any JSON (event empty <{}>) is treated
    as a correct argument to be replaced. Empty (or with unknown props) JSON is replaced by the current database view object
    element value. 
Not listed above argument cases remain untouched, but passed without angle brackets to avoid stdin/stdout redirections, so any
single angle brackets are truncated in a result command line.

					    HANDLER COMMAND.
To make database changes or client side actions user handlers should return (output to stdout) some commands in JSON
format: {"cmd": "<command>", "<prop1>": "<value1>",.., "<propN>": "<valueN>"}
Empty output or unknown commands (see available command list below) are ignored and make no actions. Output in non-JSON
format is automatically converted to the next command to be set as an element value (see 'SET' command description below):
{"cmd": "SET", "value": "<handler output>"}

Available handler commands are:
 - 'EDIT'. Format: '{"cmd": "EDIT", "data": "<some text>"}'. The command makes the client side table cell content be editable.
   Property 'data' is optional and set as an editable content. No 'data' property - current table cell content (element value)
   is used as an editable content. For example, 'mouse double click' calls the handler, which response is 'EDIT' command.
   Just like in Excel :) Handler command is ignored for 'CHANGE', 'INIT' and 'SCHEDULE' user/controller events.
 - 'ALERT'. Format: '{"cmd": "ALERT", "data": "<some text>"}'. The command displays client side warning dialog box with
   <some text> as a warning/info text and 'OK' button. No 'data' property - the command is ignored. Handler command is ignored
   for 'CHANGE', 'INIT' and 'SCHEDULE' user/controller events.
 - 'DIALOG'. Format: '{"cmd": "DIALOG", "data": {<JSON dialog>}}'. The command displays client side dialog box based on 
   <JSON dialog>* format, which allows to generate 'powerful' dialog boxes with any combination of text input, text areas,
   multiple/single select, radio-buttons, check-boxes, interface buttons.. No 'data' property - the command is ignored.
   Handler command is ignored for 'CHANGE', 'INIT' and 'SCHEDULE' user/controller events.
 - 'CALL'. Format: '{"cmd": "CALL", "ODid": "<database id>", "OVid": "<view id>", "params": {<JSON params>}}'. The command
   calls specified by OD/OV identificators database view as if the user clicks specified view on the sidebar. It is useful
   for some views to be called from a handler as a responce on user events (mouse or keyboard, for a example) and according
   to specific handler behaviour. <JSON params> is a JSON formatted object selection parameters, see 'Object Selection' help
   section for details. For a example, some object element mouse double click displays the view, which searches objects
   matched the clicked element value, that is passed in a "params" property. Handler command is ignored for 'CHANGE', 'INIT'
   and 'SCHEDULE' user/controller events.
 - 'SET'/'RESET'. Object element data set. 'SET' command updates all specified element JSON properties only. 'RESET' command
   does the same, but additionally removes all other (not specified) properties, so in case of RESET command element JSON
   data is just replaced by handler output JSON.

Some handlers may take long time for a execution, so to avoid any script/binary freezing or everlasting runtime - user
can manage handler processes via 'Task Manager' (context menu). Its table columns are PID (process identificator), Handler
(handler command line), Exe time (handler running time in sec), Initiator (user name initiated event for handler call),
Ip (client ip address), Event (user event name), Database/view (database/view names), OId/Eid (object and element
identificators) and Kill (column with buttons 'X' to kill appropriate handler process). Task manager info is refreshed
automatically every second. Any column header mouse click (except 'Kill') sorts handler process list in ascending or
descending order.

					    CONTROLLER COMMAND.
Handler commands 'EDIT', 'ALERT', 'DIALOG' and 'CALL' are passed from the handler by the controller directly to the client
with no changes. These commands are client side specific and execute client (browser) actions such as edit content, alert
message, dialog box and specified view open/call.
Another handler commands 'SET' and 'RESET' makes the controller to do some database operations (new object version create,
write initiated element data from handler command, process 'CHANGE' event for other elemens of the object, check result object
version on database rules) and then to pass object changed data to the client.
Two user events 'INIT' (passed to the handler for all elements of the new object) and 'DELETEOBJECT' (no handler call) are
processed by the controller (create new object and remove specified object(s) respectively), which then calls client side
to refresh the current view.

*JSON dialog structure is a nested JSONs which draw dialog box with its interface elements and specific behaviour:
- "title" property is a dialog box text title, empty or undefined title - no box title area drawn.
- "dialog" property is a dialog content of itself with pads, profiles for every pad and input interface elements for every profile.
  Pads, profiles and element names are arbitrary. See OD structure dialog with pads and its profiles as an example.
  Every profile is an input elements list. Each element must have one of the folowing types:
  - select. Dropdown list with one possible option to select
  - multiple. Dropdown list with more than one possible options to select
  - radio|checkbox. HTML input tag with radio or checkbox type. Selects one or multiple options respectively.
  - textarea. Multiple lines text input.
  - text. Single line text input.
  - password. Single line hidden text input.
  "head" value is a text to be drawn in the upper element area.
  "data" value is an initial text for text-input element types and options separated by '|' with selected option marked with '+' (example: "option1|+option2|option3|") for 'select' element types.
  "help" value is a text to be drawn on element head 'question' button.
  Any value "line" property draws shadowed line at the bottom element area.
  Any value "readonly" property make input element to be read only.
- "buttons" property is a JSON with property name is a button text. One property - one content bottom area button.
  Property text value is a html style attribute applied for specified button element.
  No first space char in that text destroys dialog with no action made (just like cancel button behaviour).
  Staring one space char also destroys dialog, but the controller is called on specified button click event with 'CONFIRMDIALOG' as event name.
  Two spaces at the begining of the possible style string are like 'one space char', but dialog box is not destroyed and remains on the client side.
  Example: "buttons": {"OK": " background-color: green;", "CANCEL": "background-color: red;", "APPLY": "  "}.
  Possibility to cancel the box is provided by ESC key for any dialog box.
- "flags" property is a JSON with props to style dialog box.
  Property "style" value is a dialog box content html style attribute inserted to the box content wrapper.
  Property "pad" and "profile" values select active pad and profiles to be displayed after dialog call.
  In case of single pad or/and profile its area can be shown or hidden via appropriate flags.
`
}}},

"Examples": { profile: { element1: { line: '', style: 'font-family: monospace, sans-serif;', head:
`Example 1 - simple corporate chat.
First step - create database and 'Table' templated view.
Second - all database actual objects (user messages) should be selected (default behaviour), so 'Object selection' should be empty.
Third - 'Element layout' should display messages in descending order with old messages on the top and new object (new message input)
on the bottom, plus some cell spacing and cell highlighting. Input three JSONs in element layout field:
{"table": {"style":"width: 96%; margin: 10px; border-collapse: separate;", "cellspacing":"15"}}
{"eid":"1", "x":"0", "y":"n", "style":"text-align: left; border: none; border-radius: 5px; background-color: #DDD;"}
{"eid":"1", "oid":"1", "x":"0", "y":"q", "event":"", "style":"width: 1400px; text-align: left; border-radius: 7px;"}

First JSON is for zero object (oid) and element (eid) ids. Absent (zero) oid and eid describes html table attributes and undefined
cell css style. Our case property "table" is an attribute list for <table> tag: width value set is a necessary condition to set
table cells width in pixels (in <td> tag). Border-collapse separate set allows cell spacing of 15 pixels between chat messages.
Second JSON describes all chat messages (all objects in object selection [oid=0] for element id 1 [eid=1]). All these cells are
styled via 'style' property with left text align, rounded border (5px) and light grey background color (#DDD). Object element
horizontal position is 'x=0' (first column) and vertical is 'n' - sequence number in a selection - first object (first message)
is placed in a fist row (n=0), second object in a second row (n=1) and so on. Variable 'q' is an object selection count number,
so 'input' object (third JSON for a new message input [oid=1]) goes to row number 'q'. For example - ten chat messages layout
is first 10 rows (0-9) for messages and next row number 10 (eleventh row) for new message input.

Next step - to be able to create chat messages object element should be created in a 'Element' tab of database configuration.
Just for 'INIT' event (add new object) only. Enter next handler command line for that event:
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
element ("element" property). Retrieved construction (user@firstname) is styled by <span> tag: deep blue color (RGB(44,72,131)
and bold font. After user@firstname - space (' ') and light grey color styled datetime (<datetime>) on the same line.
Then - user chat message text of itself (<data>) on the next line (<br>).

Last step - to block empty messages and/or already posted messages removal create two rules. First create rule profile for
'Delete object' operation with 'reject' action and  both empty pre- and post- processing rules (empty rule is a match case),
so any delete operation will be blocked. To allow user to delete his own messages just add preprocessing rule: owner!=':user'.
Second rule profile is a little bit more complicated - 'Add operation' with 'reject' action and next postprocessing rule:
JSON_UNQUOTE(JSON_EXTRACT(eid1, '$.value')) NOT REGEXP '\\\\n.'
'Empty' message in our chat consists of 'user@name datetime\\n' string anyway. Non empty message matches '\\n.' regular
expression, so no match (NOT REGEXP) of non empty message equals 'match empty'.
Since the chat message (JSON_UNQUOTE(JSON_EXTRACT(eid1, '$.value'))) matches empty - the rule profile blocks (rejects) the
operation - new message post in our case.

That's all. As a result we have a nice chat with no much efforts for customization!`},
element2: { line: '', style: 'font-family: monospace, sans-serif;', head: 
`
Example 2 - host alive diagnostic. 
Create database with two elements - one for host names or ip addresses, second for ping result of appropriate hosts in 1st
element. To input element id 1 text data (host, ip) add some handlers for 'DBLCLICK' event (edit content on double click):
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
set as an element value, so ping loss redsults will be displayed in a table every 10 minutes. 
To check hosts on demand - set handler 'ping -c 1 <{"element":"1"}>' for 'CHANGE' event for element id2. The handler will be
called just right after element id 1 data (host/ip) is changed.

Describe host names fetch from other OD (like setki.xls)
`},
element3: { line: '', style: 'font-family: monospace, sans-serif;', head: 
`
Example 3 - Group users list.
php text.php SET <{"ODid":"1", "OVid":"1","selection":"lastversion=1 and version!=0 and (JSON_UNQUOTE(JSON_EXTRACT(eid1, '$.groups')) regexp '^:group\\n' OR JSON_UNQUOTE(JSON_EXTRACT(eid1, '$.groups')) regexp '\\n:group\\n')", "limit": "100","element":"1",":group": {}}>
`},
element4: { line: '', style: 'font-family: monospace, sans-serif;', head: 
`
Example 4 - echo '{"cmd":"SET", "style":"background-color:red;"}'
`},
element5: { line: '', style: 'font-family: monospace, sans-serif;', head: 
`
Example 5 - tip tap toe
`},
element6: { line: '', style: 'font-family: monospace, sans-serif;', head: 
`
Example 6 - helpdesk, jira
`}}},

"Keyboard/Mouse": { profile: { element: { line: '', style: 'font-family: monospace, sans-serif;', head:
`  - CTRL with left button click on any object element opens new browser tab with the element text as url*
  - CTRL with arrow left/right key set table cursor to the left/right end of the table*
  - CTRL with Home/End key set table cursor to the upper/lower end of the table
  - CTRL+C or CTRL+INS copy element text data to clipboard*
  - CTRL+Shift+C or CTRL+Shift+INS copy current object to clipboard*
  - CTRL+V pastes text data to the current via 'KEYPRESS' event (see handler section help) or
    clones clipboard object*
  - CTRL+Shift+F search on user input regular expression among current view object elements
  - CTRL+Z/Y usual undo actions are not implemented, cos it is hard to undo element
    handlers action due to its complicated and unique behaviour. To see previous element values
    use object older versions selection feature
  - Left/right/up/down arrow keys move cursor to appropriate direction
  - 'Shift+Enter' and 'Enter' move cursor up and down
  - Arrow keys with Scroll-Lock will move the page instead of cursor*
  - ESC in element contenteditable mode cancels all changes
  - Mouse right button on sidebar, main field or main table area calls appropriate context menu
  - Any element 'mouseover' event for some time (default 1 sec) displays appropriate hint message if exist
  - Excel like mouse pointer table cells resizing are not implemented due to multiuser complicated cells
    width/height values change. Use element layout (see appropriate help section) feature to set
    initial width/height values. By default, widths and heights of the table and its cells are adjusted
    to fit the content.
  
* will be available in a future releases`
}}},
},

buttons: { OK: {value: "&nbsp;   OK   &nbsp;"}},
flags:   { esc: "", style: "min-width: 1100px; min-height: 600px; width: 1100px; height: 720px;" }
};
