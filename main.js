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
const EFFECTHELP = " effect appearance. Possible values:<br>'fade', 'grow', 'slideleft', 'slideright', 'slideup', 'slidedown', 'fall', 'rise' and 'none'.<br>Incorrect value makes 'none' effect."
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
let sidebar = {}, cursor = {}, oldcursor = {};
let uiProfile = {
		  // Body
		  "application": { "target": "body", "background-color": "#343E54;", "Force to use next user customization (empty or non-existent user - option is ignored)": "", "Editable content apply input key combination": "Ctrl+Enter", "_Editable content apply input key combination": "Available options: 'Ctrl+Enter', 'Alt+Enter', 'Shift+Enter' and 'Enter'.<br>Any other values set no way to apply content editable changes by key combination." },
		  // Sidebar
    		  "sidebar": { "target": ".sidebar", "background-color": "rgb(17,101,176);", "border-radius": "5px;", "color": "#9FBDDF;", "width": "13%;", "height": "90%;", "left": "4%;", "top": "5%;", "scrollbar-color": "#1E559D #266AC4;", "scrollbar-width": "thin;", "box-shadow": "4px 4px 5px #222;" },
		  "sidebar wrap icon": { "wrap": "&#9658;", "unwrap": "&#9660;" }, //{ "wrap": "+", "unwrap": "&#0150" }, "wrap": "&#9658;", "unwrap": "&#9660;"
		  "sidebar wrap cell": { "target": ".wrap", "font-size": "70%;", "padding": "3px 5px;" },
		  "sidebar item active": { "target": ".itemactive", "background-color": "#4578BF;", "color": "#FFFFFF;", "font": "1.1em Lato, Helvetica;" },
		  "sidebar item hover": { "target": ".sidebar tr:hover", "background-color": "#4578BF;", "cursor": "pointer;" },
		  "sidebar object database": { "target": ".sidebar-od", "padding": "3px 5px 3px 0px;", "margin": "0px;", "color": "", "width": "100%;", "font": "1.1em Lato, Helvetica;"  },
		  "sidebar object view": { "target": ".sidebar-ov", "padding": "2px 5px 2px 10px;", "margin": "0px;", "color": "", "font": "0.9em Lato, Helvetica;" },
		  // Main field
		  "main field": { "target": ".main", "width": "76%;", "height": "90%;", "left": "18%;", "top": "5%;", "border-radius": "5px;", "background-color": "#EEE;", "scrollbar-color": "#CCCCCC #FFFFFF;", "box-shadow": "4px 4px 5px #111;" },
		  "main field table": { "target": "table", "margin": "0px;" },
		  "main field table cursor cell": { "outline": "red solid 1px", "shadow": "0 0 5px rgba(100,0,0,0.5)", "clipboard outline": "red dashed 2px" },
		  "main field table title cell": { "target": ".titlecell", "padding": "10px;", "border": "1px solid #999;", "color": "black;", "background": "#CCC;", "font": "", "text-align": "center" },
		  "main field table newobject cell": { "target": ".newobjectcell", "padding": "10px;", "border": "1px solid #999;", "color": "black;", "background": "rgb(191,255,191);", "font": "", "text-align": "center" },
		  "main field table data cell": { "target": ".datacell", "padding": "10px;", "border": "1px solid #999;", "color": "black;", "background": "", "font": "12px/14px arial;", "text-align": "center" },
		  "main field table undefined cell": { "target": ".undefinedcell", "padding": "10px;", "border": "", "background": "" },
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
		  "hint": { "target": ".hint", "background-color": "#CAE4B6;", "color": "#7E5A1E;", "border": "none;", "border-radius": "3px;", "box-shadow": "2px 2px 4px #cfcfcf;", "padding": "5px;", "effect": "hotnews", "mouseover hint timer in msec": "1000", "_effect": "Hint " + EFFECTHELP },
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
		  "tree arrow stock": { target: ".treelinkstock", "flex-basis": "10px;", "box-sizing": "border-box;", "background-color": "rgb(17,101,176);", "border": "none;", "margin-left": "15px;", "margin-right": "15px;", "height": "100px;", },
		  "tree arrow down": { target: ".treelinkarrowdown", "flex-basis": "20px;", "box-sizing": "border-box;", "background-color": "transparent;", "border-top": "40px solid rgb(17,101,176);", "border-bottom": "0 solid transparent;", "border-left": "20px solid transparent;", "border-right": "20px solid transparent;", },
		  "tree arrow up": { target: ".treelinkarrowup", "flex-basis": "20px;", "box-sizing": "border-box;", "background-color": "transparent;", "border-top": "0 solid transparent;", "border-bottom": "40px solid rgb(17,101,176);", "border-left": "20px solid transparent;", "border-right": "20px solid transparent;", },
		  "tree element description": { target: ".treelinkdescription", "display": "flex;", "flex": "1 10px;", "background-color": "transparent;", "border": "none;", "padding": "5px;", "font": "10px/11px arial;", "overflow": "hidden;", },
		  };
/*---------------------------------------------------------------------------*/

const style = document.createElement('style');			// Create style DOM element
styleUI();							// Style default user inteface profile
document.head.appendChild(style);				// Append document style tag

window.onload = function()
{
 // Define document html and add appropriate event listeners for it
 document.body.innerHTML = '<div class="sidebar"></div><div class="main"></div><div class="contextmenu ' + uiProfile["context menu"]["effect"] + 'hide"></div><div class="hint ' + uiProfile["hint"]["effect"] + 'hide"></div><div class="box ' + uiProfile["dialog box"]["effect"] + 'hide"></div><div class="select expanded ' + uiProfile["dialog box select"]["effect"] + 'hide"></div>';
 document.addEventListener('mousedown', MouseEventHandler);
 document.addEventListener('mouseup', MouseEventHandler);
 document.addEventListener('keydown', KeyboardEventHandler);
 document.addEventListener('contextmenu', ContextEventHandler);
 
 // Define sidebar div
 sidebarDiv = document.querySelector('.sidebar');

 // Define main field div and add 'scroll' event for it
 mainDiv = document.querySelector('.main');
 mainDiv.addEventListener('scroll', () => { HideHint(); HideContextmenu(); });
 
 // Define context menu div and add some mouse events for it
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
 socket.onclose = () => { displayMainError("The server connection is down! Try again"); HideBox(); };
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
	      body: 	JSON.stringify(requestBody) }).then(function(response) {
			    if (response.ok) response.json().then(callback);
			     else displayMainError('Request failed with response ' + response.status + ': ' + response.statusText); })
							    .catch (function(error) { lg('Ajax request error: ', error); });
 return true;
}

function drawSidebar(data)
{
 if (typeof data != 'object') return;
 let text, ovlistHTML, sidebarHTML = '';
 
 for (let odid in data)
     {
      // Set wrap status (empty string key) to true for default or to old instance of sidebar OD wrap status
      (sidebar[odid] === undefined || sidebar[odid]['wrap'] === undefined) ? data[odid]['wrap'] = true : data[odid]['wrap'] = sidebar[odid]['wrap'];
      
      // Create OV names list with active OV check 
      ovlistHTML = '';
      for (let ovid in data[odid]['view'])
    	  {
	   text = '';
	   if (data[odid]['active'] === ovid)
	      {
	       text = ' class="itemactive"';
	       ODid = odid;
	       OVid = ovid;
	       OD = data[odid]['name'];
	       OV = data[odid]['view'][ovid];
	      }
	   if (data[odid]['view'][ovid].substr(0, 1) != '_')
	      ovlistHTML += `<tr${text}><td class="wrap"></td><td class="sidebar-ov" data-odid="${odid}" data-ovid="${ovid}" data-od="${data[odid]['name']}" data-ov="${data[odid]['view'][ovid]}">${data[odid]['view'][ovid]}</td></tr>`;
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

/*function SetOEPosition(props, oid, eid, n, q, object = {})
{
 let x, y, oe, cell, oidnum = Number(oid);
 
 // CHeck specified object element start event
 (eid != 'id' && eid != 'version' && eid != 'owner' && eid != 'datetime' && eid != 'lastversion') ? eidstr = 'eid' + eid : eidstr = eid;
 
 // Check props correctness
 if (object.lastversion != '1' || !props[eid][oid] || typeof props[eid][oid].x != 'string' || typeof props[eid][oid].y != 'string') 
    if (oidnum != TITLEOBJECTID && oidnum != NEWOBJECTID && props[eid]['0']) oid = '0';
 if (!props[eid][oid] || typeof props[eid][oid].x != 'string' || typeof props[eid][oid].y != 'string') return;
 
 // Fill main table cell with oid, eid and hint from props (for TITLEOBJECTID and NEWOBJECTID only)
 // mainTable[y][x] = { oId, eId, realobject, data, hint, description, collapse, style }
 // objectTable[oid][id|version|owner|datetime|lastversion|1|2..] = { x, y }
 // props[0][0] = { style, tablestyle, collapse }
 
 mainTable[y][x] = { oId: oidnum, eId: eid };
 cell = mainTable[y][x];
 if (oe['hint']) cell['hint'] = oe['hint'];
 
 // Fill main table cell with data 
 if (oidnum === TITLEOBJECTID) cell['data'] = oe['title'];
 if (oidnum === NEWOBJECTID) cell['data'] = '';
 if (oidnum >= STARTOBJECTID)
    {
     cell['version'] = object.version;
     (object.lastversion === '1' && object.version != '0') ? cell['realobject'] = true : cell['realobject'] = false;
     if (eid === eidstr) // If element id is 'id', 'version', 'owner', 'datetime' or 'lastversion'
         {
	  cell['data'] = object[eidstr];
	 }
      else
         {
	  //--------------Set object element collapse property-----------------
	  if ((props[eid][oidnum] && props[eid][oidnum].collapse != undefined) || (props[eid]['0'] && props[eid]['0'].collapse != undefined) ||
	      (props['0'] && props['0'][oidnum] && props['0'][oidnum].collapse != undefined)) mainTable[y][x]['collapse'] = '';
	  //--------------Parse object data to JSON and fetch data (from value), hint and description-------------------
	  cell['data'] = cell['style'] = cell['hint'] = cell['description'] = '';
	  if (object[eidstr + 'value']) cell['data'] = object[eidstr + 'value'];
	  if (object[eidstr + 'hint']) cell['hint'] = object[eidstr + 'hint'];
	  if (object[eidstr + 'description']) cell['description'] = object[eidstr + 'description'];
	  if (object[eidstr + 'style']) cell['style'] = ElementStyleFetch(props, eid, oidnum, {style: cell['style']});
	 }
    }
 if (oidnum === TITLEOBJECTID || oidnum === NEWOBJECTID || mainTable[y][x]['realobject'])
    {
     if (objectTable[oidnum] === undefined) objectTable[oidnum] = {};
     objectTable[oidnum][eid] = { x: x, y: y };
    }
 if (mainTable[y][x]['style'] === undefined) mainTable[y][x]['style'] = ElementStyleFetch(props, eid, oidnum);
}*/

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
{console.time('label');
 // Init some important vars such as tables, focus element and etc..
 mainTable = [];
 objectTable = {};
 mainTableWidth = mainTableHeight = 0;
 OVtype = 'Table';

 // Current view refresh? Remember cursor position and editable status. Then clear current cursor
 let obj, e, oldcursor = {};
 if (cursor.td && cursor.ODid === ODid && cursor.OVid === OVid) oldcursor = { x: cursor.x, y: cursor.y, oId: cursor.oId, eId: cursor.eId, contentEditable: cursor.td.contentEditable, data: htmlCharsConvert(cursor.td.innerHTML) };
 cursor = { ODid: ODid, OVid: OVid };

 // Get x,y coordinates (and other properties) from props elements array 
 let warningtext, pos, cell, hiderow = [], hidecol = [];
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

 // Create html table of mainTable array, props[0][0] = { style: , tablestyle: }
 const undefinedCell = '<td' + undefinedcellclass + (props[0]?.[0]?.['style'] ? `style="${props[0][0]['style']}"` : '') + '></td>';
 let rowHTML = props[0]?.[0]?.['tablestyle'] ? '<table style="' + props[0][0]['tablestyle'] + '"><tbody>' : '<table><tbody>';
 let x, y, disp = 0, undefinedRow = '<tr>';
 for (x = 0; x < mainTableWidth - hidecol.length; x++) undefinedRow += undefinedCell; // Create 'undefined' html tr element row
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
	   (cell = mainTable[y][x]) ? rowHTML += `<td${cell.style}>${toHTMLCharsConvert(cell.data)}</td>` : rowHTML += undefinedCell;
	   if ((cell.realobject || cell.oId === TITLEOBJECTID || cell.oId === NEWOBJECTID)) // objectTable[oid][id|version|owner|datetime|lastversion|1|2..] = { x: , y: }
	      objectTable[cell.oId] ? objectTable[cell.oId][cell.eId] = { x: x, y: y } : objectTable[cell.oId] = { [cell.eId]: { x: x, y: y } };
	  }
      rowHTML += '</tr>';
     }
 mainDiv.innerHTML = rowHTML + '</tbody></table>';
 mainTablediv = mainDiv.querySelector('table');
 mainTableAddEventListeners(); // Add current view event listeners
 clearTimeout(loadTimerId);

 // Restore cursor position on refreshed view and emulate mouse/keyboard start event if exist
 if (cursor.cmd && cursor.oId >= STARTOBJECTID && (cmd = cursor.cmd)) CallController(cursor.data);
 if (cursor.oId || ((cursor.oId = oldcursor.oId) && (cursor.eId = oldcursor.eId)))
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
     if (cursor.oId === NEWOBJECTID || oldcursor.contentEditable === EDITABLE) MakeCursorContentEditable(oldcursor.data);
     mainDiv.scrollTop = mainDiv.scrollHeight * cursor.y / mainTableHeight;
     mainDiv.scrollLeft = mainDiv.scrollWidth * cursor.x / mainTableWidth;
    }
console.timeEnd('label');
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
	     if (!mainTable[y][x]['content'][0] || !mainTable[y][x]['content'][0]['value'])
		value = "'&nbsp;&nbsp;'";
	      else
	        value = EllipsesClip(mainTable[y][x]['content'][0]['value'], uiProfile['tree element']['object element value max chars']);
	     if (!mainTable[y][x]['content'][0] || !mainTable[y][x]['content'][0]['title'])
		title = "'&nbsp;&nbsp;'";
	      else
	        title = EllipsesClip(mainTable[y][x]['content'][0]['title'], uiProfile['tree element']['object element title max chars']);
	     stockrow += '><div class="treelink"><div style="justify-content: flex-end; align-items: flex-' + (direction === 'up' ? 'end' : 'start') + ';" class="treelinkdescription"><span>' + title + '</span></div><div class="treelinkstock"></div><div style="justify-content: flex-start; align-items: flex-' + (direction === 'up' ? 'end' : 'start') + ';" class="treelinkdescription">' + value + '</div></div></td>';
	     //----------------------
	     if (content = mainTable[y][x]['content'][1])
	        { 
		 title = EllipsesClip(content['title'], uiProfile['tree element']['object element title max chars']);
		 value = EllipsesClip(content['value'], uiProfile['tree element']['object element value max chars']);
		 if (!title) title = "'&nbsp;&nbsp;'";
		 if (!value) value = "'&nbsp;&nbsp;'";
		 
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
 let add, data = '';

 for (let i = 2; i < content.length; i++)
     {
      if (add = content[i]['title'])
         {
	  if (add.length > Number(uiProfile['tree element']['object element title max chars'])) add = add.substr(0, Number(uiProfile['tree element']['object element title max chars']) - 2) + '..';
	  data += `<span class="underlined">${add}</span>: `;
         }
      if (add = content[i]['value'])
         {
	  if (add.length > Number(uiProfile['tree element']['object element value max chars']) && content[i]['title'] != undefined) add = add.substr(0, Number(uiProfile['tree element']['object element value max chars']) - 2) + '..';
	  data += add;
         }
      data += '<br>';
     }
 
 return data;
}

/*function ElementStyleFetch(props, eid, oid, oe = {})
{
 let style = '';
 if (typeof props[eid]['0'] === 'object') style = MergeStyleRules(props[eid]['0'].style);
 if (props['0'] && typeof props['0'][oid] === 'object') style = MergeStyleRules(style, props['0'][oid].style);
 if (typeof props[eid][oid] === 'object') style = MergeStyleRules(style, props[eid][oid].style);
 if (typeof oe.style === 'string') style = MergeStyleRules(style, oe.style);
 return style;
}

function MergeStyleRules(...styles)
{
 let resultStyle = '', styleObject = {}, rule, pos;
 styles.forEach((style) => {
			    if (style && typeof style === 'string') for (rule of style.split(';'))
			       {
			        rule = rule.trim();
				if ((pos = rule.indexOf(':')) > 0 && rule.length > pos + 1) // Some chars before and after ':'?
				styleObject[rule.substr(0, pos)] = rule.substr(pos + 1);
			       }
			   });
 for (rule in styleObject) resultStyle += rule + ': ' + styleObject[rule] + '; ';
 return resultStyle;
}
*/

function MainDivEventHandler(event)
{
 switch (event.type)
	{
	 case 'mouseleave':
	      if (!box) HideHint();
	      break;
	 case 'mousemove':
	      let x = event.target.cellIndex, y = event.target.parentNode.rowIndex;
	      if (x != undefined && y != undefined && mainTable[y]?.[x]?.hint && !box && !contextmenu)
	         {
		  if (!hint || hint.x != x || hint.y != y)
		     {
		      hint = { x: x, y: y };
		      clearTimeout(tooltipTimerId);
		      tooltipTimerId = setTimeout(() => ShowHint(mainTable[y][x].hint, getAbsoluteX(event.target, 'middle'), getAbsoluteY(event.target, 'end')), uiProfile['hint']['mouseover hint timer in msec']);
		     }
		  break;
		 }
	      HideHint();
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

 if (button['error']) displayMainError(button['error'], false);
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

 // Prevent default context menu while dialog box up, mouse click on already existed context menu or context key press
 if (box || event.target == contextmenuDiv || event.target.classList.contains('contextmenuItems') || (contextmenu && event.which === 0))
    {
     event.preventDefault();
     return;
    }

 // Is cursor element content editable? Apply changes in case of no event.target match
 if (cursor.td?.contentEditable === EDITABLE)
    {
     if (cursor.td != event.target)
	{
	 event.preventDefault();
	 cursor.td.contentEditable = NOTEDITABLE;
	 if (mainTable[cursor.y][cursor.x].oId != NEWOBJECTID && (cmd = 'CONFIRM')) CallController(htmlCharsConvert(cursor.td.innerHTML));
	  else mainTable[cursor.y][cursor.x].data = htmlCharsConvert(cursor.td.innerHTML);
	 // Main field table cell click?
	 if (event.target.tagName == 'TD' && !event.target.classList.contains('wrap') && !event.target.classList.contains('sidebar-od') && !event.target.classList.contains('sidebar-ov')) CellBorderToggleSelect(cursor.td, event.target);
	}
     return;
    }

 // Context event on wrap icon cell? Use next DOM element
 let inner, target = event.target;
 if (target.classList.contains('wrap')) target = target.nextSibling;
  else if (cursor.td && event.button === 0) target = cursor.td; // If cursor and context key?
  else if (target.tagName == 'SPAN' && target.parentNode.tagName == 'TD') target = target.parentNode;
 
 if (target.classList.contains('sidebar-od')) inner = ACTIVEITEM + 'New Object Database</div>' + ACTIVEITEM + 'Edit Database Structure</div>'; // Context event on OD
  else if (target.classList.contains('sidebar-ov') || target === sidebarDiv) inner = ACTIVEITEM + 'New Object Database</div>' + GREYITEM + 'Edit Database Structure</div>'; // Context event on OV
  else switch (OVtype)
    {
     case 'Table':
          if (target === mainDiv || target === mainTablediv) // Context event on main div with any OV displayed or on main table div in case od table edge click!
	     {
	      inner = ACTIVEITEM + 'Add Object</div>' + GREYITEM + 'Delete Object</div>' + ACTIVEITEM + 'Description</div>';
	      break;
	     }
	  if (target.tagName === 'TD')
	     {
	      CellBorderToggleSelect(cursor.td, target);
	      if (mainTable[cursor.y]?.[cursor.x]?.realobject) inner = ACTIVEITEM + 'Add Object</div>' + ACTIVEITEM + 'Delete Object</div>' + ACTIVEITEM + 'Description</div>';
	       else inner = ACTIVEITEM + 'Add Object</div>' + GREYITEM + 'Delete Object</div>' + ACTIVEITEM + 'Description</div>';
	      inner += ACTIVEITEM + 'Copy</div>';
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
     if (event.which === 0)
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

 // Return if mouse non left button click
 if (event.which != 1) return;

 // Dialog box is up? Process its mouse left button click
 if (box)
    {
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
 if (event.target.classList.contains('wrap')) next = next.nextSibling;
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
    {console.time('label1');
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
     displayMainError('Loading.', false);
     CallController();
     return;
    }

 // Table type view mouse click event?
 if (OVtype === 'Table')
 if ((event.target.tagName == 'TD' && (next = event.target)) || (event.target.tagName == 'SPAN' && (next = event.target.parentNode) && next.tagName == 'TD'))
    {
     CellBorderToggleSelect(cursor.td, next);
     if (mainTable[cursor.y]?.[cursor.x] && cursor.td.contentEditable != EDITABLE && !isNaN(cursor.eId) && cursor.oId === NEWOBJECTID) MakeCursorContentEditable(mainTable[cursor.y][cursor.x].data);
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
    		      if (button['error']) displayMainError(button['error'], false);
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
	      if (box || contextmenu || !cursor.td) break;
	      if (cursor.td?.contentEditable != EDITABLE && mainTable[cursor.y]?.[cursor.x]?.['realobject'] && !isNaN(cursor.eId))
	      if (mainTable[cursor.y][cursor.x].oId != NEWOBJECTID && (cmd = 'DEL'))
	         CallController({metakey: event.metaKey, altkey: event.altKey, shiftkey: event.shiftKey, ctrlkey: event.ctrlKey});
	       else
		 mainTable[cursor.y][cursor.x].data = cursor.td.innerHTML = '';
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
	      if (event.ctrlKey && event.keyCode == 67) CopyBuffer(event.shiftKey);
	      
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
	 case 'SIDEBAR':
	 case 'CALL':
	 case 'New Object Database':
	 case 'Edit Database Structure':
	      Hujax("view.php", FromController, input.data);
	      break;
	 case 'Table':
	      paramsOV = input.params;
	      drawMain(input.data, input.props);console.timeEnd('label1');
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
 if (input.error != undefined)	displayMainError(input.error, false);
 if (input.alert)		warning(input.alert);
}

function CallController(data)
{
 let object;

 switch (cmd)
	{
	 case 'New Object Database':
	 case 'Task Manager':
	      object = { "cmd": cmd };
	      if (typeof data != 'string') object.data = data;
	      break;
	 case 'Edit Database Structure':
	 case 'SIDEBAR':
	 case 'CALL':
	 case 'LOGIN':
	      object = { "cmd": cmd };
	      if (data != undefined) object.data = data;
	      break;
	 case 'Copy':
	      CopyBuffer();
	      break;
	 case 'Description':
	      let cell, hidden = msg = '';
	      //--------------Add object and element information to the result message---------------
	      if (cursor.td != undefined && mainTable[cursor.y] && mainTable[cursor.y][cursor.x] && (cell = mainTable[cursor.y][cursor.x]) && cell.oId)
	      switch (cell.oId)
	    	     {
		      case NEWOBJECTID:
		           if (Number(cell.eId) > 0) msg = 'Cursor table cell is input new object data for element id: ' + cell.eId;
			   break;
		      case TITLEOBJECTID:
			   msg = 'Cursor table cell is title for element id: ' + cell.eId;
			   break;
		      default:
			   msg = 'Cursor table cell object id: ' + cell.oId + '\nCursor table cell element id: ' + cell.eId;
			   if (cell.version != '0')
			      {
			       msg += '\nObject version: ' + cell.version + '\nActual version: ';
			       cell.realobject ? msg += 'yes' : msg += 'no';
			       break;
			      }
			   msg += '\nObject version: object has been deleted';
		     }
	      //--------------Add description to the result message---------------
	      if (cell && typeof cell.description === 'string') msg += '\n\nElement description property:\n' + cell.description;
	      //--------Add x and y coordinates to the result message-------------
	      if (cell) msg += `\n\nTable cell 'x' coordinate: ${cursor.x}\nTable cell 'y' coordinate: ${cursor.y}\n\n`;
	      //--------------------Add database and view info--------------------
	      if (OV.substr(0, 1) === '_') hidden = ' (hidden from sidebar)';
	      msg += `Object Database: ${OD}\nObject View${hidden}: ${OV} (${objectsOnThePage} objects)\nMain table columns: ${mainTableWidth}\nMain table rows: ${mainTableHeight}`;
	      //--------------Add part of sql string object selection-------------
	      let parammsg = '', count = 1;
	      for (cell in paramsOV) parammsg += `\n${count++}. ` + cell.substr(1).replace(/_/g, ' ') + ': ' + paramsOV[cell];
	      if (parammsg != '') msg += '\n\nObject View input parameters:' + parammsg;
	      //--------------Display result message in warning box---------------
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
		     object['data'][eid] = mainTable[objectTable[String(NEWOBJECTID)][eid].y][objectTable[String(NEWOBJECTID)][eid].x]['data'];
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

function displayMainError(errormsg, resetOV = true)
{
 clearTimeout(loadTimerId);

 if (errormsg.substr(0, 7) === 'Loading')
    {
     errormsg === 'Loading...' ? errormsg = 'Loading' : errormsg += '.';
     loadTimerId = setTimeout(displayMainError, 500, errormsg, false);
     errormsg = errormsg.replace(/Loading/, '').replace(/./g, '&nbsp;') + errormsg;
    }
 mainDiv.innerHTML = '<h1>' + errormsg + '</h1>';

 mainTableRemoveEventListeners();
 if (resetOV) OD = OV = ODid = OVid = OVtype = '';
}

function mainTableAddEventListeners()
{
 if (!mainTablediv) return;
 mainTablediv.addEventListener('dblclick', MainDivEventHandler);
 mainTablediv.addEventListener('mouseleave', MainDivEventHandler);
 mainTablediv.addEventListener('mousemove', MainDivEventHandler); 
 mainTablediv.addEventListener('paste', (event) => {});
}

function mainTableRemoveEventListeners()
{
 if (!mainTablediv) return;
 mainTablediv.removeEventListener('dblclick', MainDivEventHandler);
 mainTablediv.removeEventListener('mouseleave', MainDivEventHandler);
 mainTablediv.removeEventListener('mousemove', MainDivEventHandler); 
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

 //lg("$uiProfile = json_decode('" + JSON.stringify(uiProfile).replace(/'/g, "\\'") + "', true);"); // Output uiProfile array to te console to use it as a default customization configuration
}

const help = { title: 'Help', dialog: {

"System description": { profile: { element: { head:
`Tabels application is a set of custom data tables the user can interact many different ways.
Every table consists of identical objects, which, in turn, are set of bult-in and user defined elements.
Table data of itself is called Object Database (OD) and can be changed or created by
appropriate sidebar context menu. OD contains Object Views (OV). Views define what objects
(via 'object selection', see appropriate help section) and elements (via 'element layout',
see appropriate help section) should be displayed and how.

OV allows users to operate its objects many different ways, so to display its data 
generated by binded to elements appropriate handlers. Simple OV is a classic table with
object list in 'y' order and its elements in 'x' order, so Object Database is similar to
any SQL database, where objects are rows and elements are its fields.

As it was mentioned above each object is a ste of bult-in and user defined elements.
Bult-in elements represent service data which is set automatically:
-id 
-user
..
User defined element represents itself JSON data type and stored in SQL database with that type.
Each element JSON data can be managed by appropriate user defined element handlers (see 'handlers'
help section).`
}}},

"Object Selection": { profile: { element: { head:
`Object selection is a part of the sql query string that selects objects for the specified view.
Let's have a quickly look to the object structure stored in database to select
required objects effectively. Each object consists of next elements:
- id. Object identificator.
- lastversion. Element value can be 0 or 1 and indicates whether it is last object version or not. See 'version' field.
- version. Indicates object version number started from '1' value, so new object has first version. 
  After object any change, controller creates a new row with the changed object copy, increments its version and set lastversion flag to 1.
  This mechanism allows to store every object version, so user can trace object data changing and find out when, how and who object is changed by.
  Deleted objects are marked by zero version.
- owner. The user this object version was created by.
- datetime. Date and time object version was created at.
- eid<element id>. JSON type user-defined (via element handlers) data.

In case of empty string default object selection 'WHERE lastversion=1 AND version!=0'
is applied. Default object selection selects all relevant (lastversion=1) and non deleted objects (version!=0).
To select objects from database controller applies next query based on object selection string:
'SELECT <element layout selection> FROM data_<OD id> <object selection>'
This query format together with object structure provides effective selection of any sets
of objects via powerful SQL capabilities!

To make object selection process more flexible user can use some parameters in 
object selection string. These parameters should start from char ':' and finsh with space.
Parsed parameter name is set as a question (with chars '_' reaplced with spaces) in client side dialog box at the object view call.
Object selection string example for 'Users' object database:
'WHERE lastversion=1 AND version!=0 AND eid1->>'$.value'!=':Input_user'.
That selection example OV call will display dialog to get input and pass it to the controller to build result query.`
}}},

"Element layout": { profile: { element: { head:
`
Element layout is a JSON strings list. Each JSON defines element and its behaviour (table cell position, style attribute, OV start event, etc..)
JSON possible properties are:
- 'x','y'. Appropriate table cell coordinates defined by expression that may include two variables: 'n' (object serial number in the selection) and 'q' (total number of objects)
- 'oid','eid'. Object id, element id this behaviour is applied to. Real object identificators starts from 3. Title object id is 2.
  New object (cell to input text data for a new objects adding) id is 1. To match all real objects in the selection set oid property to 0.
  Note that element id are user-defined elements with identificators started from 1
  and built-in elements with identificators 'id', 'lastversion', 'version', 'owner' and 'datetime', see 'object selection' help section.
  In case of undefined eid/oid - zero values are set. Both zero oid and eid defines behaviour for undefined cell, that has no any object element in.
- 'event'. Mouse double click or key press emulation after OV call. Possible values are 'DBLCLICK' and 'KEYPRESS<any_string>', see 'handler' help section.
  Any other values - no event emulation, but cursor is set to the specified by 'x','y' props position anyway.
- 'collpase'. This property presence with any value - set collapse flag to the table cell. Any table column/row with empty cell and collapse flag set for each - will be collapsed.
- 'style'. HTML style attribute for specified element, see appropriate css documentaion.
- 'tablestyle'. HTML style attribute for tag <table>, can be defined only with undefined cell (oid=0, eid=0).

Let's parse 'All logs' OV element layout of 'Logs' database:
{"eid":"id", "oid":"2", "x":"0", "y":"0"}
{"eid":"id", "x":"0", "y":"n+1"}
{"eid":"datetime", "oid":"2", "x":"1", "y":"0"}
{"eid":"datetime", "x":"1", "y":"n+1"}
{"eid":"1", "oid":"2", "x":"2", "y":"0"}
{"eid":"1", "x":"2", "y":"n+1"}

First two JSONS display 'id' object element title (oid=2) and 'id' object element for real objects in the selection (oid=0). 
Title cell for 'id' is positioned to the first table column (x=0) and first table row (y=0)
Element 'id' for real objects is set to the  first column (x=0) and to the rows in order starting from second row (y=n+1).
First object in the selection with n=0 is set to the second row (y=1), second object in the selection with n=1 is set to the third row (y=2) and so on.
Similarly to the 'datetime' element and first user-defined element (eid=1) that consists of real system log data. See 'OV example' help section also.

In addition to JSONS above, element layout could be set to one of next values:
'' - Empty value selects all user-defined database elements and diplays them as a classic table with the title as a first row.
'*' - One asterisk behaves like empty value with one exception - 'new object' input row is added to the table just right after title row.
'**' - Two asterisks behaves like empty value, but built-in elements ('id', 'version', 'owner'..) are added.
'***' - Three asterisks behaves like one asterisk, but built-in elements ('id', 'version', 'owner'..) are added.
`
}}},

"Element handlers": { profile: { element: { head:
`
Element handler is any executable script or binary called by the contoller when specified event occurs.
Events occur on user interaction with real object element (mouse double clicking or keypressing), adding
new object, changing the object and other object processes:
- KEYPRESS. Event occurs when keyboard input for letters, digits and space is registered.
- INS,DEL,F2,F12. Event occurs when keyboard input for appropriate non symbol keys is registered.
- DBLCLICK. Left button mouse double click event.
- CONFIRM. Event occurs when cell content editable data returns to the handler to be confirmed after the user has applied editable content.
- CONFIRMDIALOG. Event occurs when dialog box data returns to the handler to be confirmed after the user has applied dialog.
- INIT. Event occurs when the new object has been created.
- CHANGE. Event occurs after one of elements have been changed by handler command SET or RESET.
- SCHEDULE. Event is generated by system scheduler.

For any of events above specified element handler is called.
Handler command line is defined in Object Database structure dialog (you can call it via appropriate context menu) on 'Element' tab.
There are some command line arguments enclosed by '<>' - they are replaced by the service data:
- <event>. Event name the handler is called on.
- <user>. User name the event was initiated by. Arg is qouted automatically. 
- <oid>. Object id the event was initiated on.
- <title>. Element id title the event was initiated on. Arg is qouted automatically. 
- <data>. Event data passed to the hanlder. Arg is qouted automatically. 
  For KEYPRESS it will be key char. In case of text paste operation - KEYPRESS event is also generated and <data> arg will be the pasted text.
  For INIT event <data> argument will be text in 'new object' table cells, for empty or undefined cells <data> arg value is ''.
  For CONFIRM event after html element <td> editable content apply  - <data> argument is that content text data.
  For CONFIRMDIALOG after dialog box apply - <data> argument is a JSON that represents dialog structure*
  For DBLCLICK, CHANGE and SCHEDULE events <data> argument is undefined.

Besides all above user can pass any strings as an arguments, but since they are in JSON format the specified property of specified object element is retrieved.
Format: {"ODid": "<OD id>", "OVid": "<OV id>", "oid": "<object id>", "eid": "<element id>", "prop": "<property name>"}
Next argument example will retrieve object id=4 and element id=1 property "password" value stored in current object database: '{"oid": "4", "eid": "1", "prop": "password"}'.
In case of ODid/OVid/oid/eid omitted, current ODid/OVid/oid/eid identificators are used, "prop" is mandatory, so empty string an is used as an arg in case of absent or nonexistent "prop".

Since handlers want to make some actions they should output string in JSON format to stdout:
- '{ "cmd": "EDIT", "data": "<text data>" }'. EDIT handler command makes element content editable.
- '{ "cmd": "ALERT", "data": "<text data>" }'. Command output alert box on the client (browser).
- '{ "cmd": "DIALOG", "data": <JSON dialog structure> }'. Command output dialog box on the client (browser).*
- '{ "cmd": "CALL", "data": {"ODid": "", "OD": "", "OVid": "", "OV": "", "params": ""} }'. Command calls specified object database view.
  In case OD/OV omitted current values are used. Property "params" is optional, its value is a JSON with object selection args list (as a properties) with its values. 
  Absent object selection args in "params" JSON will be requested via dialog box.
  Example: '{ "cmd": "CALL", "data": {"OD": "Users", "OV": "User", "params": {":Input_user": "root"} } }'.
  Let OV 'User' of OD 'Users' have next object selection: WHERE lastversion=1 AND version!=0 AND eid1->>'$.value'=':Input_user'.
  So example command above calls view 'User' that displays only root user object.
  In case of absent "params" property - example call will dislpay dialog box to input user to select it from db.
- '{ "cmd": "SET|RESET", .. }'. SET or RESET stores any JSON properties to the object database. RESET rewrites specified JSON instead current actual element JSON version, while SET adds specified properties to the current version only.
  These two commands can write any props, but setting of some reserved props causes element specific behaviour:
  - 'cmd'. SET or RESET.
  - 'value'. Object element visible text data displayed in OV output.
  - 'image'. Object element file name displayed in OV as an image. 
  - 'alert'. Alert text to inform the user after object change via 'SET|RESET' commands.
  - 'link'. 
  - 'location'. 
  - 'hint'. Element hint pops up after mouse cursor element navigation.
  - 'description'. Description information displayed in info-box via appropriate context menu item select.
  - 'style'. Object element visible text data css, see css documentation.
  
  Note that any non-JSON handler output doesn't cause an error, output data is set as a "value" property of element JSON, so handler command becomes look like that:
  '{ "cmd": "SET", "value": "non-JSON output" }'
  It is usefull for ordinary utils/scripts. For a example, using ping as a handler allows to store and display this diagnostic utility output.

* JSON dialog structure:
 '{ "title": "dialog box title",
    "dialog": { "pad name1": { "profile name1": { "element name1": { "type": "select|multiple|checkbox|radio|textarea|password|text",
								     "head": "<interface element head text>",
								     "data": "<interface element initial data>",
								     "help": "<help text displayed as a hint>",
								     "line": "",
								     "readonly": "",
								   }
						 "element name2": {}..
						},
			       "profile name2": {}..
			     },
		"pad name2": {}..
	      }
    "buttons": { "button text1": "", "button text2": "".. }, 
    "flags": { "style": "dialog box content html style attribute",
	       "pad": "active (current selected) dialog box pad (if exists)",
	       "profile": "active (current selected) dialog box profile (if exist)",
	       "display_single_pad": "display pad area flag",
	       "display_single_profile": "display profile area flag",
	     }
 }'
    
JSON dialog structure is a nested JSONs which draw dialog box with its interface elements and specific behaviour:
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

"OV example": { profile: { element: { head:
`
Let's have a look to the chat like OV create example.
First - create OD instance via sidebar context menu 'New Object Database'

Second - create element for 'INIT' event with next handler command line: /usr/local/bin/php /usr/local/apache2/htdocs/handlers/text.php INIT <data>
Handler text.php is a built-in script that implements text operation functions (much like excel cell :)

Move on. Let's create view with next object selection 'WHERE lastversion=1 AND version!=0 ORDER BY id DESC'
and next element layout:
{"eid":"datetime", "x":"0", "y":"q-n-1"}
{"eid":"owner", "x":"1", "y":"q-n-1"}
{"eid":"1", "x":"2", "y":"q-n-1"}
{"eid":"1", "oid":"1", "x":"2", "y":"q", "event":""}
New message coordinates to be at the bottom equal 'q' - total objects (messages) count.
Last message (but first message in query with n=0) goes one row above with y=q-n-1=q-0-1=q-1
Second last (n=1) goes two rows above new message cell - y=q-n-1=q-1-1=q-2. And so on.

Next - create object database rules (see 'Rules' tab in Object Database structure dialog).
OD rule of itself is a part of sql query string to apply to the obejct instance before (pre-rule) and 
after (post-rule) specified operation (object add/delete/chagne), so in case of no operation specified - post and pre rules are ignored. 
Controller check rules in rule names alphabetical order and when a match is found, the action (accept or reject)
corresponding to the matching rule is performed. The search terminates. No any rule match - default action (accept) is applied.
Match case occurs at successfull query select (at least one row selected) for both pre and post rules.
Empty rule - no selection made, but selection is considered successfull, so both pre and post rules empty case causes a match case.
Query format: SELECT * FROM data_<ODid> where id=<oid> AND version=<version_before|version_after> AND <pre-rule|post-rule>;
For a kind of chat OV some rules needed. First - disallow empty messages, so the post-rule for operation 'Add object' should be:
eid1->>'$.value'=''. Pre-rule for 'add' operation type is ignored.
Second OD rule - disallow to delete chat messages, so pre-rule for our case should be blank to match all objects.
Post-rule for 'delete' operation type is ignored (for 'change' operation type - both pre and post rules are checked).
And Of course, for both our chat restrictions action is set to 'reject'.
`
}}},

"Keyboard/Mouse": { profile: { element: { head:
`  - CTRL with left button click on any object element opens new browser tab with the element text as url*
  - CTRL with arrow left/right key set table cursor to the left/right end of the table*
  - CTRL with Home/End key set table cursor to the upper/lower end of the table
  - CTRL+C or CTRL+INS copy element text data to clipboard*
  - CTRL+Shift+C or CTRL+Shift+INS copy current object to clipboard*
  - CTRL+V pastes text data to the current via 'KEYPRESS' event (see handler section help) or
    clones clipboard object*
  - CTRL+Shift+F search on user input regular expression among current view object elements
  - CTRL+Z/Y usual undo actions are not implemented int the system, cos it is hard to undo element
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
flags:   { esc: "", style: "min-width: 700px; min-height: 600px; width: 860px; height: 720px;" }
};
