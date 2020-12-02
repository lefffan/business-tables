/*------------------------------CONSTANTS------------------------------------*/
const TABLE_MAX_CELLS = 200000;
const NEWOBJECTID = 1;  
const TITLEOBJECTID = 2;
const STARTOBJECTID = 3;
const DEFAULTOBJECTSPERPAGE = 50;
const range = document.createRange();   
const selection = window.getSelection();
const mainObjectContext = '<div class="contextmenuItems">New Object</div><div class="contextmenuItems">Delete Object</div><div class="contextmenuItems">Description</div><div class="contextmenuItems">Help</div>';
const mainContext = '<div class="contextmenuItems">New Object</div><div class="contextmenuItems greyContextMenuItem">Delete Object</div><div class="contextmenuItems">Description</div><div class="contextmenuItems">Help</div>';
const sidebarOVContext = '<div class="contextmenuItems">New Object Database</div><div class="contextmenuItems greyContextMenuItem">Edit Database Structure</div><div class="contextmenuItems">Help</div>';
const sidebarODContext = '<div class="contextmenuItems">New Object Database</div><div class="contextmenuItems">Edit Database Structure</div><div class="contextmenuItems">Help</div>';
/*------------------------------VARIABLES------------------------------------*/
let contextmenu, contextmenuDiv;
let hint, hintDiv;
let box = selectExpandedDiv = null, boxDiv, expandedDiv;
let tooltipTimerId, undefinedcellRuleIndex;
let mainDiv, sidebarDiv, mainTablediv;
let mainTable, mainTableWidth, mainTableHeight, objectTable;
let user = cmd = activeOD = activeOV = '';
let objectsOnThePage, paramsOV;
let sidebar = {};
let focusElement = {};
/*---------------------------------------------------------------------------*/
// User interface default profile
const uiProfile = {
		  // Body
		  "body": { "target": "body", "background-color": "#343E54;" },
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
		  "main field table cursor cell": { "outline": "red auto 1px", "shadow": "0 0 5px rgba(100,0,0,0.5)" },
		  "main field table title cell": { "target": ".titlecell", "padding": "10px;", "border": "1px solid #999;", "color": "black;", "background": "#CCC;", "font": "", "text-align": "center" },
		  "main field table newobject cell": { "target": ".newobjectcell", "padding": "10px;", "border": "1px solid #999;", "color": "black;", "background": "rgb(191,255,191);", "font": "", "text-align": "center" },
		  "main field table data cell": { "target": ".datacell", "padding": "10px;", "border": "1px solid #999;", "color": "black;", "background": "", "font": "12px/14px arial;", "text-align": "center" },
		  "main field table undefined cell": { "target": ".undefinedcell", "padding": "10px;", "border": "1px solid #999;", "background": "rgb(255,235,235);" },
		  "main field table mouse pointer": { "target": ".main table tbody tr td:not([contenteditable=true])", "cursor": "cell;" },
		  "main field message": { "target": ".main h1", "color": "#BBBBBB;" },
		  // Scrollbar
		  "scrollbar": { "target": "::-webkit-scrollbar", "width": "8px;", "height": "8px;" },
		  // Context Menu
		  "context menu": { "target": ".contextmenu", "width": "240px;", "background-color": "#F3F3F3;", "color": "#1166aa;", "border": "solid 1px #dfdfdf;", "box-shadow": "1px 1px 2px #cfcfcf;", "font-family": "sans-serif;", "font-size": "16px;", "font-weight": "300;", "line-height": "1.5;", "padding": "12px 0;" },
		  "context menu item": { "target": ".contextmenuItems", "margin-bottom": "4px;", "padding-left": "10px;" },
		  "context menu item cursor": { "target": ".contextmenuItems:hover:not(.greyContextMenuItem)", "cursor": "pointer;" },
		  "context menu item active": { "target": ".activeContextMenuItem", "color": "#fff;", "background-color": "#0066aa;" },
		  "context menu item grey": { "target": ".greyContextMenuItem", "color": "#dddddd;" },
		  // Box types
		  "hint": { "target": ".hint", "background-color": "#CAE4B6;", "color": "#7E5A1E;", "border": "none;", "padding": "5px;" },
		  "box": { "target": ".box", "background-color": "rgb(233,233,233);", "color": "#1166aa;", "border-radius": "5px;", "border": "solid 1px #dfdfdf;", "box-shadow": "2px 2px 4px #cfcfcf;" },
		  // Box interface elements
		  "dialog box title": { "target": ".title", "background-color": "rgb(209,209,209);", "color": "#555;", "border": "#000000;", "border-radius": "5px 5px 0 0;", "font": "bold .9em Lato, Helvetica;", "padding": "5px;" },
		  "dialog box pad": { "target": ".pad", "background-color": "rgb(223,223,223);", "border-left": "none;", "border-right": "none;", "border-top": "none;", "border-bottom": "none;", "padding": "5px;", "margin": "0;", "font": ".9em Lato, Helvetica;", "color": "#57C;", "border-radius": "5px 5px 0 0;" },
		  "dialog box active pad": { "target": ".activepad", "background-color": "rgb(209,209,209);", "border-left": "none;", "border-right": "none;", "border-top": "none;", "border-bottom": "none;", "padding": "5px;", "margin": "0;", "font": "bold .9em Lato, Helvetica;", "color": "#57C;", "border-radius": "5px 5px 0 0;" },
		  "dialog box pad bar": { "target": ".padbar", "background-color": "transparent;", "border": "none;", "padding": "4px;", "margin": "10px 0 15px 0;" },
		  "dialog box divider": { "target": ".divider", "background-color": "transparent;", "margin": "5px 10px 5px 10px;", "height": "0px;", "border-bottom": "1px solid #CCC;", "border-top-color": "transparent;", "border-left-color": "transparent;" , "border-right-color": "transparent;" },
		  "dialog box button": { "target": ".button", "background-color": "#13BB72;", "border": "none;", "padding": "10px;", "margin": "10px;", "border-radius": "5px;", "font": "bold 12px Lato, Helvetica;", "color": "white;" },
		  "dialog box button and pad hover": { "target": ".button:hover, .pad:hover", "cursor": "pointer;", "background": "", "color": "", "border": "" },
		  "dialog box element headers": { "target": ".element-headers", "margin": "5px 5px 5px 5px;", "font": ".9em Lato, Helvetica;", "color": "#555;", "text-shadow": "none;" },
		  "dialog box help icon": { "target": ".help-icon", "padding": "1px;", "font": ".9em Lato, Helvetica;", "color": "#555;", "background": "#FF0;", "border-radius": "40%;" },
		  "dialog box help icon hover": { "target": ".help-icon:hover", "padding": "1px;", "font": "bold 1em Lato, Helvetica;", "color": "black;", "background": "#E8E800;", "cursor": "pointer;", "border-radius": "40%;" },
		  //
		  "dialog box select": { "target": ".select", "background-color": "rgb(243,243,243);", "color": "#57C;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 5px 10px;", "outline": "none;", "border": "1px solid #777;", "padding": "0px 0px 0px 0px;", "overflow": "auto;", "max-height": "10em;", "scrollbar-width": "thin;", "min-width": "10em;", "width": "auto;", "display": "inline-block;" },
		  "dialog box select option": { "target": ".select > div", "padding": "2px 20px 2px 5px;", "margin": "0px;" },
		  "dialog box select option hover": { "target": ".select:not([type*='o']) > div:hover", "background-color": "rgb(209,209,209);", "color": "" },
		  "dialog box select option selected": { "target": ".selected", "background-color": "rgb(209,209,209);", "color": "#fff;" },
		  "dialog box select option expanded": { "target": ".expanded", "margin": "0px !important;", "position": "absolute;" },
		  //
		  "dialog box radio": { "target": "input[type=radio]", "background": "transparent;", "border": "1px solid #777;", "font": ".8em/1 sans-serif;", "margin": "3px 5px 3px 10px;", "border-radius": "20%;", "width": "1.2em;", "height": "1.2em;" },
		  "dialog box radio checked" : { "target": "input[type=radio]:checked::after", "content": "", "color": "white;" },
		  "dialog box radio checked background" : { "target": "input[type=radio]:checked", "background": "#00a0df;", "border": "1px solid #00a0df;" },
		  "dialog box radio label" : { "target": "input[type=radio] + label", "color": "#57C;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 0px 0px;" },
		  //
		  "dialog box checkbox": { "target": "input[type=checkbox]", "background": "#f3f3f3;", "border": "1px solid #777;", "font": ".8em/1 sans-serif;", "margin": "3px 5px 3px 10px;", "border-radius": "50%;", "width": "1.2em;", "height": "1.2em;" },
		  "dialog box checkbox checked" : { "target": "input[type=checkbox]:checked::after", "content": "", "color": "white;" },
		  "dialog box checkbox checked background" : { "target": "input[type=checkbox]:checked", "background": "#00a0df;", "border": "1px solid #00a0df;" },
		  "dialog box checkbox label" : { "target": "input[type=checkbox] + label", "color": "#57C;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 0px 0px;" },
		  //
		  "dialog box input text": { "target": "input[type=text]", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background": "#f3f3f3;", "border": "1px solid #777;", "outline": "none;", "color": "#57C;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  "dialog box input password": { "target": "input[type=password]", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background": "#f3f3f3;", "border": "1px solid #777;", "outline": "", "color": "#57C;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  "dialog box input textarea": { "target": "textarea", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background": "#f3f3f3;", "border": "1px solid #777;", "outline": "", "color": "#57C;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  // Effects and animation
		  "effects": { "hint": "hotnews", "contextmenu": "rise", "box": "slideup", "select": "rise", "box filter": "grayscale(0.5)" }, // or blur(3px)
		  "hotnews hide": { "target": ".hotnewshide", "visibility": "hidden;", "transform": "scale(0) rotate(0deg);", "opacity": "0;", "transition": "all .4s;", "-webkit-transition": "all .4s;" },
		  "hotnews show": { "target": ".hotnewsshow", "visibility": "visible;", "transform": "scale(1) rotate(720deg);", "opacity": "1;", "transition": ".4s;", "-webkit-transition": ".4s;", "-webkit-transition-property": "transform, opacity", "transition-property": "transform, opacity" },
		  "fade hide": { "target": ".fadehide", "visibility": "hidden;", "opacity": "0;", "transition": "all .5s;", "-webkit-transition": "all .5s;" },
		  "fade show": { "target": ".fadeshow", "visibility": "visible;", "opacity": "1;", "transition": "opacity .5s;", "-webkit-transition": "opacity .5s;" },
		  "grow hide": { "target": ".growhide", "visibility": "hidden;", "transform": "scale(0);", "transition": "all .4s;", "-webkit-transition": "all .4s;" },
		  "grow show": { "target": ".growshow", "visibility": "visible;", "transform": "scale(1);", "transition": "transform .4s;", "-webkit-transition": "transform .4s;" },
		  "slideleft hide": { "target": ".slidelefthide", "visibility": "hidden;", "transform": "translate(1000%);", "transition": "all .4s cubic-bezier(1,-0.01,1,-0.09);", "-webkit-transition": "all .4s cubic-bezier(1,-0.01,1,-0.09);" },
		  "slideleft show": { "target": ".slideleftshow", "visibility": "visible;", "transform": "translate(0%);", "transition": "all .4s cubic-bezier(.06,1.24,0,.98);", "-webkit-transition": "all .4s cubic-bezier(.06,1.24,0,.98);" },
		  "slideright hide": { "target": ".sliderighthide", "visibility": "hidden;", "transform": "translate(-1000%);", "transition": "all .4s cubic-bezier(1,-0.01,1,-0.09);", "-webkit-transition": "all .4s cubic-bezier(1,-0.01,1,-0.09);" },
		  "slideright show": { "target": ".sliderightshow", "visibility": "visible;", "transform": "translate(0%);", "transition": "all .4s cubic-bezier(.06,1.24,0,.98);", "-webkit-transition": "transform .4s cubic-bezier(.06,1.24,0,.98);" },
		  "slideup hide": { "target": ".slideuphide", "visibility": "hidden;", "transform": "translate(0%, 1000%);", "transition": "all .4s cubic-bezier(1,-0.01,1,-0.09);", "-webkit-transition": "all .4s cubic-bezier(1,-0.01,1,-0.09);" },
		  "slideup show": { "target": ".slideupshow", "visibility": "visible;", "transform": "translate(0%, 0%);", "transition": "all .4s cubic-bezier(.06,1.24,0,.98);", "-webkit-transition": "transform .4s cubic-bezier(.06,1.24,0,.98);" },
		  "slidedown hide": { "target": ".slidedownhide", "visibility": "hidden;", "transform": "translate(0%, 1000%);", "transition": "all .4s cubic-bezier(1,-0.01,1,-0.09);", "-webkit-transition": "all .4s cubic-bezier(1,-0.01,1,-0.09);" },
		  "slidedown show": { "target": ".slidedownshow", "visibility": "visible;", "transform": "translate(0%, 0%);", "transition": "all .4s cubic-bezier(.06,1.24,0,.98);", "-webkit-transition": "transform .4s cubic-bezier(.06,1.24,0,.98);" },
		  "fall hide": { "target": ".fallhide", "visibility": "hidden;", "transform-origin": "left top;", "transform": "scale(2);", "opacity": "0;", "transition": "all .4s;", "-webkit-transition": "all .4s;" },
		  "fall show": { "target": ".fallshow", "visibility": "visible;", "transform-origin": "left top;", "transform": "scale(1);", "opacity": "1;", "transition": ".4s;", "-webkit-transition": ".4s;", "-webkit-transition-property": "transform, opacity", "transition-property": "transform, opacity" },
		  "rise hide": { "target": ".risehide", "visibility": "hidden;", "transform-origin": "left top;", "transform": "scale(0);", "transition": "all .2s cubic-bezier(.38,1.02,.69,.97);", "-webkit-transition": "all .2s cubic-bezier(.38,1.02,.69,.97);" },
		  "rise show": { "target": ".riseshow", "visibility": "visible;", "transform-origin": "left top;", "transform": "scale(1);", "transition": "transform .4s cubic-bezier(.06,1.24,0,.98);", "-webkit-transition": "transform .4s cubic-bezier(.06,1.24,0,.98);" },
		  "none hide": { "target": ".nonehide", "visibility": "hidden;" },
		  "none show": { "target": ".noneshow", "visibility": "visible;" },
		  // Misc
		  "misc customization": { "objects per page": String(DEFAULTOBJECTSPERPAGE), "next page bottom reach": "", "previous page top reach": "", "Force to use next user customization (empty or non-existent user - current is used)": "", "mouseover hint timer in msec": "1000" }
		  };
		  
//lg(JSON.stringify(uiProfile));		// Output uiProfile array to te console to use it as a default customization configuration
const style = document.createElement('style');	// Create style DOM element
styleUI();					// Style default user inteface profile
document.head.appendChild(style);		// Append document style tag
/*---------------------------------------------------------------------------*/

function lg(...data)
{
 data.forEach((value) => console.log(value));
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
 fetch(url, { method: 'POST',  
		 headers: { 'Content-Type': 'application/json; charset=UTF-8'}, 
		 body: JSON.stringify(requestBody) }).then(function(response) {
			    if (response.ok) response.json().then(callback);
			     else displayMainError('Request failed with response ' + response.status + ': ' + response.statusText); })
							    .catch (function(error) { lg('Ajax request error: ', error); });
 return true;
}

window.onload = function()
{
 // Define document html and add appropriate event listeners for it
 document.body.innerHTML = '<div class="sidebar"></div><div class="main"></div><div class="contextmenu ' + uiProfile["effects"]["contextmenu"] + 'hide"></div><div class="hint ' + uiProfile["effects"]["hint"] + 'hide"></div><div class="box ' + uiProfile["effects"]["box"] + 'hide"></div><div class="select expanded ' + uiProfile["effects"]["select"] + 'hide"></div>';
 document.addEventListener('keydown', eventHandler);
 document.addEventListener('mousedown', eventHandler);
 document.addEventListener('contextmenu', eventHandler);
 
 // Define sidebar div
 sidebarDiv = document.querySelector('.sidebar');

 // Define main field div and add 'scroll' event for it
 mainDiv = document.querySelector('.main');
 mainDiv.addEventListener('scroll', eventHandler);
 
 // Define context menu div and add some mouse events for it
 contextmenuDiv = document.querySelector('.contextmenu');
 contextmenuDiv.addEventListener('mouseover', eventHandler);
 contextmenuDiv.addEventListener('mouseout', eventHandler);

 // Define interface divs 
 hintDiv = document.querySelector('.hint');
 boxDiv = document.querySelector('.box');
 expandedDiv = document.querySelector('.expanded');
 
 if (!navigator.cookieEnabled)
    {
     warning('To make application work properly please enable Cookies!');
     return;
    }
 if (document.cookie != '')
    {
     warning("Cookie flag 'httponly' should be set!");
     return;
    }
 cmd = 'GETMAINSTART';
 callController();
}

function drawSidebar(data)
{
 if (typeof data != 'object') return;
 let text, ovlistHTML, sidebarHTML = '';
 
 for (let od in data)
     {
      // Set wrap status (empty string key) to true for default or to old instance of sidebar OD wrap status
      (sidebar[od] === undefined || sidebar[od][''] === undefined) ? data[od][''] = true : data[od][''] = sidebar[od][''];
      
      // Create OV names list with active OV check 
      ovlistHTML = '';
      for (let ov in data[od]) if (ov != '' && ov.substr(0, 1) != '_')
    	  {
	   (activeOD === od && activeOV === ov) ? text = ' class="itemactive"' : text = '';
	   ovlistHTML += `<tr${text}><td class="wrap"></td><td class="sidebar-ov" data-od="${od}" data-ov="${ov}">${ov}</td></tr>`;
	  }

      // Draw wrap icon
      if (ovlistHTML === '') sidebarHTML += '<tr><td class="wrap"></td>';  // Insert empty wrap icon
       else if (data[od][''] === false) sidebarHTML += '<tr><td class="wrap">' + uiProfile['sidebar wrap icon']['unwrap'] + '</td>'; // Insert unwrap icon
        else sidebarHTML += '<tr><td class="wrap">' + uiProfile['sidebar wrap icon']['wrap'] + '</td>'; // Insert wrap icon

      // Insert OD name
      sidebarHTML += `<td class="sidebar-od" data-od="${od}">${od}</td></tr>`;
     
      // Insert OV names list if OD is unwrapped
      if (data[od][''] === false) sidebarHTML += ovlistHTML;
     }

 // Push calculated html text to sidebar div
 sidebarHTML != '' ? sidebarDiv.innerHTML = '<table style="margin: 0px;"><tbody>' + sidebarHTML + '</tbody></table>' : sidebarDiv.innerHTML = '';
  
 // Reset sidebar with new data
 sidebar = data;
}	 

function SetOEPosition(props, oid, eid, n, q, object = {})
{
 let x, y, oe, oidnum = Number(oid);
 
 // CHeck specified object element start event
 if (eid != 'id' && eid != 'version' && eid != 'owner' && eid != 'datetime' && eid != 'lastversion') eidstr = 'eid' + eid;
  else eidstr = eid;
 if ((oidnum === NEWOBJECTID || oidnum >= STARTOBJECTID) && props[eid][oid] && eid != eidstr && cmd === 'GETMAIN')
 if (props[eid][oid]['startevent'] === 'DBLCLICK') focusElement = { oId: oid, eId: eid, cmd: 'DBLCLICK' };
  else if (oidnum >= STARTOBJECTID && props[eid][oid]['startevent'].substr(0, 8) === 'KEYPRESS' && props[eid][oid]['startevent'].length > 8)
	  focusElement = { oId: oid, eId: eid, cmd: 'KEYPRESS', data: props[eid][oid]['startevent'].substr(8) };

 // Check props correctness
 if (object.lastversion != '1' || !props[eid][oid] || typeof props[eid][oid].x != 'string' || typeof props[eid][oid].y != 'string') 
    if (oidnum != TITLEOBJECTID && oid != NEWOBJECTID && props[eid]['0']) oid = '0';
 if (!props[eid][oid] || typeof props[eid][oid].x != 'string' || typeof props[eid][oid].y != 'string') return;
 oe = props[eid][oid];
 
 // Calculate specified object element x,y table coordinates
 try { x = Math.trunc(eval(oe.x)); y = Math.trunc(eval(oe.y)); }
 catch { return `Specified view '${activeOV}' selection expression has some 'x','y' incorrect coordinate definitions!\nSee element selection expression help section`; }
 if (isNaN(x) || isNaN(y)) return `Specified view '${activeOV}' selection expression has some 'x','y' incorrect coordinate definitions!\nSee element selection expression help section`;
 if ((Math.max(mainTableWidth, x + 1) * Math.max(mainTableHeight, y + 1)) > TABLE_MAX_CELLS || x < 0 || y < 0)
    return `Some elements coordiantes (view '${activeOV}') are out of range. Max table size allowed - ` + TABLE_MAX_CELLS + " cells";
    
 // Calculate main table width and height
 mainTableWidth = Math.max(mainTableWidth, x + 1);
 mainTableHeight = Math.max(mainTableHeight, y + 1);
 
 // Fill main table cell with oid, eid and hint from props (for TITLEOBJECTID and NEWOBJECTID only)
 // mainTable[y][x] = { oId, eId, realobject, data, hint, description, collapse, style }
 // objectTable[oid][id|version|owner|datetime|lastversion|1|2..] = { x, y }
 // props[0][0] = { style, tablestyle, collapse }
 if (mainTable[y] === undefined) mainTable[y] = [];
 mainTable[y][x] = { oId: oidnum, eId: eid };
 if (oe['hint']) mainTable[y][x]['hint'] = oe['hint'];
 
 // Fill main table cell with data 
 if (oidnum === TITLEOBJECTID) mainTable[y][x]['data'] = oe['title'];
 if (oidnum === NEWOBJECTID) mainTable[y][x]['data'] = '';
 if (oidnum >= STARTOBJECTID)
    {
     mainTable[y][x]['version'] = object.version;
     (object.lastversion === '1' && object.version != '0') ? mainTable[y][x]['realobject'] = true : mainTable[y][x]['realobject'] = false;
     if (eid === eidstr) // If element id is 'id', 'version', 'owner', 'datetime' or 'lastversion'
         {
	  mainTable[y][x]['data'] = object[eidstr];
	 }
      else
         {
	  //--------------Set object element collapse property-----------------
	  if ((props[eid][oidnum] && props[eid][oidnum].collapse != undefined) || (props[eid]['0'] && props[eid]['0'].collapse != undefined) ||
	      (props['0'] && props['0'][oidnum] && props['0'][oidnum].collapse != undefined)) mainTable[y][x]['collapse'] = '';
	  //--------------Parse object data to JSON and fetch data (from value), hint and description-------------------
	  try   { object = JSON.parse(object[eidstr]); }
	  catch { object = {}; }
	  if (object === null || object === undefined) object = {};
	  typeof object.value === 'string' ? mainTable[y][x]['data'] = object.value : mainTable[y][x]['data'] = '';
	  if (typeof object.hint === 'string') mainTable[y][x]['hint'] = object.hint;
	  if (typeof object.description === 'string') mainTable[y][x]['description'] = object.description;
	  mainTable[y][x]['style'] =  ElementStyleFetch(props, eid, oidnum, object);
	 }
    }
 if (oidnum === NEWOBJECTID || mainTable[y][x]['realobject'])
    {
     if (objectTable[oidnum] === undefined) objectTable[oidnum] = {};
     objectTable[oidnum][eid] = { x: x, y: y };
    }
 if (mainTable[y][x]['style'] === undefined) mainTable[y][x]['style'] = ElementStyleFetch(props, eid, oidnum);
}

function drawMain(data, props)
{
 let oid, eid, n, q = data.length, warningtext, cell, result, attributes;
 let undefinedcellclass = titlecellclass = newobjectcellclass = datacellclass = undefinedRow = '', rowHTML = '<table><tbody>';
 
 // Init some important vars such as tables, focus element and etc..
 mainTable = [];
 objectTable = {};
 focusElement = {};
 mainTableWidth = mainTableHeight = 0;
 objectsOnThePage = q;
 
 // Parse all props elements, to place all objects of that elements
 for (eid in props)
     {
      oid = String(NEWOBJECTID);
      if (result = SetOEPosition(props, oid, eid, 0, q)) warningtext = result;
      props[eid][oid] = undefined; // Remove specified element new object prop, should be used only once
	 
      oid = String(TITLEOBJECTID);
      if (result = SetOEPosition(props, oid, eid, 0, q)) warningtext = result;
      if (props[eid][oid] && !(/n|q/.test(props[eid][oid].x)) && !(/n|q/.test(props[eid][oid].y))) props[eid][oid] = undefined; // Remove specified element title object prop, in case of absent 'n' and 'q' variables this object element placement is used only once
	 
      for (n in data)
	  {
	   if (n != '0' && (result = SetOEPosition(props, oid, eid, Number(n), q))) warningtext = result;
	   if (result = SetOEPosition(props, data[n].id, eid, Number(n), q, data[n])) warningtext = result;
	  }
     }
     
 // Handle some errors
 if (!mainTableHeight)
    {
     if (!warningtext) warningtext = `Specified view '${activeOV}' has no objects defined!<br>Please add some objects`;
     displayMainError(warningtext, false);
     return;
    }
 if (warningtext) warning(warningtext);

 // Remove previous view event listeners
 mainTableRemoveEventListeners();

 // Define attribute class strings for default, undefined, title, newobject and data td cells
 if (!isObjectEmpty(uiProfile["main field table title cell"], 'target')) titlecellclass = ' class="titlecell"';
 if (!isObjectEmpty(uiProfile["main field table newobject cell"], 'target')) newobjectcellclass = ' class="newobjectcell"';
 if (!isObjectEmpty(uiProfile["main field table data cell"], 'target')) datacellclass = ' class="datacell"';
 if (!isObjectEmpty(uiProfile["main field table undefined cell"], 'target')) undefinedcellclass = ' class="undefinedcell"';
 if (props[0] != undefined && props[0][0] != undefined)
    {
     if (props[0][0]['style']) undefinedcellclass += ' style="' + props[0][0]['style'] + '"';
     if (props[0][0]['tablestyle']) rowHTML = '<table style="' + props[0][0]['tablestyle'] + '"><tbody>';
     // Remove 'collapse' property set main table rows and columns
     if (props['0']['0']['collapse'] != undefined) collapseMainTable(true);
      else collapseMainTable(false);
    }
  else collapseMainTable(false);
 
 // Create 'undefined' html tr row
 const undefinedCell = '<td' + undefinedcellclass + '></td>';
 for (x = 0; x < mainTableWidth; x++) undefinedRow += undefinedCell;
 
 // Create html table of mainTable array
 for (y = 0; y < mainTableHeight; y++)
     {
      rowHTML += '<tr>';
      if (mainTable[y] === undefined) rowHTML += undefinedRow;
       else for (x = 0; x < mainTableWidth; x++)
	     if (!(cell = mainTable[y][x]))
		{
		 rowHTML += undefinedCell;
		}
	      else
	        {
	         if (cell.oId === TITLEOBJECTID) attributes = titlecellclass;
	          else if (cell.oId === NEWOBJECTID) attributes = newobjectcellclass;
	           else attributes = datacellclass;
	         if (cell.style) attributes += ' style="' + cell.style + '"';
	         rowHTML += '<td' + attributes + '>' + toHTMLCharsConvert(cell.data) + '</td>';
	        }
      rowHTML += '</tr>';
     }
 mainDiv.innerHTML = rowHTML + '</tbody></table>';
 mainTablediv = mainDiv.querySelector('table');

 // Add current view event listeners
 mainTablediv.addEventListener('dblclick', eventHandler);
 mainTablediv.addEventListener('mouseleave', eventHandler);
 mainTablediv.addEventListener('mousemove', eventHandler); 
 
 // Focus element is not empty? Emulate mouse/keyboard start event!
 if (focusElement.oId === NEWOBJECTID)
    {
     focusElement.td = mainTablediv.rows[objectTable[focusElement.oId][focusElement.eId].y].cells[objectTable[focusElement.oId][focusElement.eId].x];
     focusElement.td.contentEditable = 'true';
     focusElement.olddata = '';
     focusElement.td.innerHTML = focusElement.olddata; // Fucking FF has bug inserting <br> to the empty content
     focusElement.td.focus();
     CellBorderToggleSelect(null, focusElement.td);
    }
  else if (focusElement.oId != undefined)
    {
     focusElement.x = objectTable[focusElement.oId][focusElement.eId].x;
     focusElement.y = objectTable[focusElement.oId][focusElement.eId].y;
     delete focusElement.oId;
     delete focusElement.eId;
     CellBorderToggleSelect(null, mainTablediv.rows[focusElement.y].cells[focusElement.x]);
     focusElement.td.focus();
     cmd = focusElement.cmd;
     callController(focusElement.data);
    }
}

function ElementStyleFetch(props, eid, oid, oe = {})
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

function eventHandler(event)
{
 switch (event.type)
	{
	 case 'mouseleave':
	      if (!box) HideHint();
	      break;
	 case 'mousemove':
	      let x = event.target.cellIndex, y = event.target.parentNode.rowIndex;
	      if (x != undefined && y != undefined && mainTable[y] && mainTable[y][x] && mainTable[y][x].hint != undefined && !box && !contextmenu)
	         {
		  if (!hint || hint.x != x || hint.y != y)
		     {
		      hint = { x: x, y: y };
		      clearTimeout(tooltipTimerId);
		      tooltipTimerId = setTimeout(() => ShowHint(mainTable[y][x].hint, getAbsoluteX(event.target, 'middle'), getAbsoluteY(event.target, 'end')), uiProfile['misc customization']['mouseover hint timer in msec']);
		     }
		 }
	       else HideHint();
	      break;
	 case 'mouseover': // Mouse over non grey context menu item? Set current menu item to call appropriate menu action by 'enter' key
	      if (event.target.classList.contains('contextmenuItems') && !event.target.classList.contains('greyContextMenuItem')) SetContextmenuItem(event.target);
	      break;
	 case 'mouseout': // Mouse out if the context menu? Set current menu item to null
	      SetContextmenuItem(null);
	      break;
	 case 'scroll':
	      HideContextmenu();
	      break;
	 case 'contextmenu':
	      //--------------Do nothing in case of dialog box or contextmenu event on context menu div area--------------
	      if (event.target == contextmenuDiv || event.target.classList.contains('contextmenuItems') || box || (contextmenu && event.which != 3)) event.preventDefault();
	      //--------------Is any element content editable? Apply changes in case of no event.target match--------------
	       else if (focusElement.td && focusElement.td.contentEditable === 'true')
	         {
		  if (focusElement.td != event.target)
		     {
		      event.preventDefault();
		      focusElement.td.contentEditable = 'false';
		      if (mainTable[focusElement.y][focusElement.x].oId == NEWOBJECTID)
		         {
			  mainTable[focusElement.y][focusElement.x].data = htmlCharsConvert(focusElement.td.innerHTML);
			 }
		       else
		         {
			  cmd = 'CONFIRM';
			  callController(htmlCharsConvert(focusElement.td.innerHTML));
			 }
		      // Main field table cell click?
		      if (event.target.tagName == 'TD' && !event.target.classList.contains('wrap') && !event.target.classList.contains('sidebar-od') && !event.target.classList.contains('sidebar-ov')) CellBorderToggleSelect(focusElement.td, event.target);
		     }
		 }
	       else ShowContextmenu(event);
	      break;
	 case 'dblclick':
	      if (!box && event.target.contentEditable != 'true')
	      if (mainTable[focusElement.y] && mainTable[focusElement.y][focusElement.x] && Number(mainTable[focusElement.y][focusElement.x].eId) > 0)
	      if (mainTable[focusElement.y][focusElement.x].oId === NEWOBJECTID)
		 {
	    	  focusElement.td.contentEditable = 'true';
		  focusElement.olddata = toHTMLCharsConvert(mainTable[focusElement.y][focusElement.x].data);
		  event.target.innerHTML = focusElement.olddata; // Fucking FF has bug inserting <br> to the empty content
	    	  focusElement.td.focus();
		  event.preventDefault();
		 }
	       else if (mainTable[focusElement.y][focusElement.x].realobject)
	    	 {
		  cmd = 'DBLCLICK';
		  callController();
		 }
	      break;
	 case 'mousedown':
	      HideHint();
	      if (event.which != 1) break;
	      //--------------Dialog 'hint icon' event? Display element hint--------------
	      if (event.target.classList.contains('help-icon'))
	         {
		  hint = { x: event.target.offsetLeft - event.target.scrollLeft + boxDiv.offsetLeft - boxDiv.scrollLeft + event.target.offsetWidth, y: event.target.offsetTop - event.target.scrollTop + boxDiv.offsetTop - boxDiv.scrollTop + event.target.offsetHeight };
		  ShowHint(box.dialog[box.flags.pad][box.flags.profile][event.target.attributes.name.value]["help"], hint.x, hint.y);
		  break;
		 }
	      //--------------Any dialog button event? Non empty button property value calls controller, then hide box anyway--------------
	      if (event.target.classList.contains('button'))
	         {
		  if (typeof box.buttons[event.target.innerHTML] === 'string' && box.buttons[event.target.innerHTML] != '' && box.buttons[event.target.innerHTML].charCodeAt(0) === 32)
		     {
		      box.buttons = {};
		      box.buttons[event.target.innerHTML] = '';
		      saveDialogProfile(); // Save dialog box content and send it to the controller
		      if (box['flags'] && box['flags']['cmd']) cmd = box['flags']['cmd'];
		       else cmd = 'CONFIRM';
		      callController(box);
		     }
		   else if (box['flags'] && box['flags']['cmd'] === 'GETMAIN') displayMainError(`View '${activeOV}' output was canceled`);
		  HideBox();
		  break;
		 }
	      //--------------Dialog expanded div mousedown event?--------------
	      if (event.target.parentNode.classList && event.target.parentNode.classList.contains('expanded'))
	         {
		  if (selectExpandedDiv.firstChild.attributes.value.value != event.target.attributes.value.value) // Selected option differs from the current?
		  if (selectExpandedDiv.attributes.type.value === 'select-profile')	// Select element is a profile select?
		     {
		      saveDialogProfile();
		      box.flags.profile = event.target.innerHTML;		// Set event.target.innerHTML as a current profile
		      ShowBox();						// Redraw dialog box
		     }
		   else // Select element is usual option select?
		     {
		      // Set selected option as a current
		      selectExpandedDiv.innerHTML = '<div value="' + event.target.attributes.value.value + '">' + event.target.innerHTML + '</div>';
		      box.dialog[box.flags.pad][box.flags.profile][selectExpandedDiv.attributes.name.value]["data"] = setOptionSelected(box.dialog[box.flags.pad][box.flags.profile][selectExpandedDiv.attributes.name.value]["data"], event.target.attributes.value.value);
		     }
		  // Hide expanded div and break;
		  expandedDiv.className = 'select expanded ' + uiProfile["effects"]["select"] + 'hide';
		  break;
		 }
	      //--------------Dialog box select interface element mouse down event?--------------
	      if (event.target.parentNode.classList && event.target.parentNode.classList.contains('select') && (event.target.parentNode.attributes.name === undefined || box.dialog[box.flags.pad][box.flags.profile][event.target.parentNode.attributes.name.value]['readonly'] === undefined))
	         {
		  switch (event.target.parentNode.attributes.type.value)
			 {
			  case 'select-profile':
			  case 'select-one':
			       if ((/hide$/).test(expandedDiv.classList[2]) === false) // Expanded div visible? Hide it.
				  {
				   expandedDiv.className = 'select expanded ' + uiProfile["effects"]["select"] + 'hide';
				   break;
				  }
			       let data, inner = '', count = 0;
			       selectExpandedDiv = event.target.parentNode; // Set current select div that expanded div belongs to
			       if (selectExpandedDiv.attributes.type.value === 'select-one') // Define expandedDiv innerHTML for usual select, otherwise for profile select
				  {
			    	   if (typeof (data = box.dialog[box.flags.pad][box.flags.profile][selectExpandedDiv.attributes.name.value]["data"]) === 'string')
				   for (data of data.split('|')) // Split data by '|'
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
			       expandedDiv.className  = 'select expanded ' + uiProfile["effects"]["select"] + 'show'; // Show expandedDiv
			       break;
			  case 'select-multiple':
			       event.target.classList.toggle("selected");
			       break;
			 }
		  break;
		 }
	      //--------------Expanded div still visible and non expanded div mouse click?--------------
	      if ((/show$/).test(expandedDiv.classList[2]) === true && !event.target.classList.contains('expanded'))
	         {
		  expandedDiv.className = 'select expanded ' + uiProfile["effects"]["select"] + 'hide';
		  break;
		 }
	      //--------------Non active pad is selected?--------------
	      if (event.target.classList.contains('pad'))
		 {
		  saveDialogProfile();
		  box.flags.pad = event.target.innerHTML; // Set event.target.innerHTML as a current pad
		  ShowBox();			 // Redraw dialog
		  break;
		 }
	      //--------------Dialog box events are processed and mouse click on grey menu item or on context menu but not menu item? Break!----------
	      if (box || event.target.classList.contains('greyContextMenuItem') || event.target.classList.contains('contextmenu')) break;
	      //--------------Mouse clilck out of main field content editable table cell? Save cell inner html as a new element, otherwise send it to the controller--------------
	     if (focusElement && focusElement.td && focusElement.td != event.target && focusElement.td.contentEditable === 'true')
	     if (mainTable[focusElement.y][focusElement.x].oId === NEWOBJECTID)
		 {
		  focusElement.td.contentEditable = 'false';
		  mainTable[focusElement.y][focusElement.x].data = htmlCharsConvert(focusElement.td.innerHTML);
		  break;
		 }
	      else
		 {
		  focusElement.td.contentEditable = 'false';
		  cmd = 'CONFIRM';
		  callController(htmlCharsConvert(focusElement.td.innerHTML));
		  break;
		 }
	      //--------------Mouse click on context menu item? Call controller with appropriate context menu item as a command--------------
	      if (event.target.classList.contains('contextmenuItems'))
		 {
		  cmd = event.target.innerHTML;
		  callController(contextmenu.data);
		  HideContextmenu();
		  break;
		 }
	      HideContextmenu();
	      //--------------OD item (or wrap icon before) mouse click? Wrap/unwrap OV list--------------
	      let next = event.target;
	      if (event.target.classList.contains('wrap')) next = next.nextSibling;
	       else next = event.target;
	      if (event.target.classList.contains('sidebar-od') || next.classList.contains('sidebar-od'))
		 {
		  if (event.target.classList.contains('sidebar-od')) next = event.target;
		  if (Object.keys(sidebar[next.dataset.od]).length < 2) break;
		  if (sidebar[cmd = next.dataset.od][''] != undefined) sidebar[cmd][''] = !sidebar[cmd][''];
		  cmd = 'GETMENU';
		  callController();
		  break;
		 }
	      //------------OV item (or wrap icon before) mouse click? Open OV in main field------------
	      if (event.target.classList.contains('sidebar-ov') || next.classList.contains('sidebar-ov'))
		 {
		  if (event.target.classList.contains('sidebar-ov')) next = event.target;
		  activeOD = next.dataset.od;
		  activeOV = next.dataset.ov;
		  cmd = 'GETMAIN';
		  callController();
		  break;
		 }
	      //--------------Mouse click on main field table?--------------
	      if (event.target.tagName == 'TD') CellBorderToggleSelect(focusElement.td, event.target);
	      break;
	 case 'keydown':
	      if (box && event.which === 13 && event.target.tagName === 'INPUT' && (event.target.type === 'text' || event.target.type === 'password'))
	         for (let btn in box.buttons) if (box.buttons[btn][0] === ' ')
		     {
		      box.buttons = {};
		      box.buttons[btn] = '';
		      saveDialogProfile(); // Save dialog box content and send it to the controller
		      if (box['flags'] && box['flags']['cmd']) cmd = box['flags']['cmd'];
		       else cmd = 'CONFIRM';                                             
		      callController(box);
		      HideBox();
		      return;
		     }

	      HideHint();
	      if ((box && event.which != 27) || (focusElement.td != undefined && focusElement.td.contentEditable === 'true' && event.which != 27 && event.which != 13)) break;
	      switch (event.which)
		     {
		      case 36: //Home
		           moveCursor(focusElement.x, 0, true);
			   break;
		      case 35: //End
		           moveCursor(focusElement.x, mainTableHeight - 1, true);
			   break;
		      case 33: //PgUp
			   moveCursor(focusElement.x, Math.max(Math.trunc((mainDiv.scrollTop - 0.5*mainDiv.clientHeight)*mainTableHeight/mainDiv.scrollHeight), 0), true);
			   break;
		      case 34: //PgDown
		           moveCursor(focusElement.x, Math.min(Math.trunc((mainDiv.scrollTop + 1.7*mainDiv.clientHeight)*mainTableHeight/mainDiv.scrollHeight), mainTableHeight - 1), true);
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
		           if (!contextmenu) // If context menu is not active,  try to move cursor down
			      {
			       if (focusElement.td != undefined && focusElement.td.contentEditable === 'true')
			          {
				   event.preventDefault();
				   document.execCommand('insertLineBreak', false, null); // "('insertHTML', false, '<br>')" doesn't work in FF
				  }
			       else moveCursor(0, 1, false);
			      }
			    else if (contextmenu.item) // If context menu item is active
			      {
			       cmd = contextmenu.item.innerHTML;
			       callController(contextmenu.data);
			       HideContextmenu();
			      }
			   break;
		      case 37: //Left
		           moveCursor(-1, 0, false);
			   break;
		      case 39: //Right
		           moveCursor(1, 0, false);
			   break;
		      case 27: //Esc
		           if (box && box.flags.esc != undefined) // Any modal with esc flag set?
			      {
			       // Expanded div visible? Hide it, otherwise hide dialog box
			       if ((/show$/).test(expandedDiv.classList[2]) != true) HideBox();
			        else expandedDiv.className = 'select expanded ' + uiProfile["effects"]["select"] + 'hide';
			      }
			    else if (focusElement.td != undefined && focusElement.td.contentEditable === 'true')
			      {
			       focusElement.td.contentEditable = 'false';
			       focusElement.td.innerHTML = focusElement.olddata;
			      }
			    else HideContextmenu();
			   break;
		      default: // space, letters, digits, plus functional keys: F2 (113), F12 (123), INS (45), DEL (46)
		    	   if (focusElement.td && focusElement.td.contentEditable != 'true')
			   if (mainTable[focusElement.y] && mainTable[focusElement.y][focusElement.x] && mainTable[focusElement.y][focusElement.x].realobject && Number(mainTable[focusElement.y][focusElement.x].eId) > 0)
			   if (event.ctrlKey == false && event.altKey == false && event.metaKey == false)
		           if (rangeTest(event.keyCode, [113,113,123,123,45,46,65,90,48,57,96,107,109,111,186,192,219,222,32,32,59,59,61,61,173,173,226,226]))
			      {
			       cmd = 'KEYPRESS';
			       callController({string: event.key, code: event.keyCode});
			       // Prevent default action - page down (space) and quick search bar in Firefox browser (keyboard and numpad forward slash)
			       if (event.keyCode == 32 || event.keyCode == 111 || event.keyCode == 191) event.preventDefault();
			      }
		     }
	      break;
	}
}

function controllerCmdHandler(input)
{
 if (input.cmd === undefined)
    {
     warning('Undefined server message!');
     lg('Undefined server message!');
     return;
    }

 if (input.customization)
    {
     uiProfileSet(input.customization);
     styleUI();
    }
 if (input.OD != undefined && input.OV != undefined)
    {
     activeOD = input.OD;
     activeOV = input.OV;
    }
 if (input.sidebar) drawSidebar(input.sidebar);
 if (input.log) lg(input.log); 
 if (input.user) user = input.user; else user = '';

 switch (input.cmd)
	{
	 case 'DIALOG':
	      box = input.data;
	      ShowBox();
	      break;
	 case 'EDIT':
	      if (focusElement && mainTable[focusElement.y] && mainTable[focusElement.y][focusElement.x])
	      if (mainTable[focusElement.y][focusElement.x].oId === input.oId && mainTable[focusElement.y][focusElement.x].eId === input.eId)
	         {
	          focusElement.td.contentEditable = 'true';
		  focusElement.olddata = toHTMLCharsConvert(mainTable[focusElement.y][focusElement.x].data);
		  // Fucking FF has bug inserting <br> in case of cursor at the end of content, so empty content automatically generates <br> tag! Fuck!
		  if (input.data != undefined) focusElement.td.innerHTML = toHTMLCharsConvert(input.data);
		   else focusElement.td.innerHTML = focusElement.olddata;
		  if (focusElement.td.innerHTML.slice(-4) != '<br>') ContentEditableCursorSet(focusElement.td);
		  focusElement.td.focus();
		 }
	      break;
	 case 'SET':
	      let x, y;
	      if (objectTable[input.oId])
	         for (let eid in input.data)
		     if (objectTable[input.oId][eid])
		        {
			 x = objectTable[input.oId][eid].x;
			 y = objectTable[input.oId][eid].y;
			 if (typeof input.data[eid] === 'object')
			    {
			     input.data[eid]['value'] ? mainTablediv.rows[y].cells[x].innerHTML = toHTMLCharsConvert(input.data[eid]['value']) : mainTablediv.rows[y].cells[x].innerHTML = '';
			     mainTablediv.rows[y].cells[x].setAttribute('style', input.data[eid]['style']);
			     mainTable[y][x].data = input.data[eid]['value'];
			     mainTable[y][x].hint = input.data[eid]['hint'];
			     mainTable[y][x].description = input.data[eid]['description'];
			    }
			  else mainTablediv.rows[y].cells[x].innerHTML = input.data[eid];
			 CellBorderToggleSelect(null, focusElement.td, false);
		        }
	      if (input.alert) warning(input.alert);
	      break;
	 case 'REFRESH':
	      paramsOV = input.paramsOV;
	      drawMain(input.data, input.props);
	      break;
	 case 'INFO':
	      if (input.alert)
	         {
		  lg(input.alert);
		  if (input.OV === undefined || input.OD === undefined || (input.OD === activeOD && input.OV === activeOV)) warning(input.alert);
		 }
	      if (input.error)
	         {
		  if (activeOD != '') lg(input.error);
		  if (input.OV === undefined || input.OD === undefined || (input.OD === activeOD && input.OV === activeOV)) displayMainError(input.error);
		 }
	      break;
	 case '':
	      break;
	 default:
	      lg("Unknown server message '" + input.cmd + "'!");
	      warning("Unknown server message '" + input.cmd + "'!");
	}
}

function displayMainError(errormsg, resetOV = true)
{
 mainDiv.innerHTML = '<h1>' + errormsg + '</h1>';
 if (resetOV) activeOD = activeOV = '';
 mainTableRemoveEventListeners();
}

function mainTableRemoveEventListeners()
{
 if (mainTablediv)
    {
     mainTablediv.removeEventListener('dblclick', eventHandler);
     mainTablediv.removeEventListener('mouseleave', eventHandler);
     mainTablediv.removeEventListener('mousemove', eventHandler); 
    }
}

function htmlCharsConvert(string)
{
 if (string === undefined || string === null || string === '') return '';
 // To prevent any html tag to be in contentEditable - first replace the tag <br> by LF special char '\n' (ASCII code 0x0A)
 string = string.replace(/<br>/g, "\n");
 // Last char is '\n' (ASCII code 0x0A)? Remove it.
 if (string.charCodeAt(string.length - 1) === 10) return string.slice(0, -1);
 return string;
}

function toHTMLCharsConvert(string)
{
 if (string == undefined || string == null) return "";
 string = string.replace(/</g,"&lt;").replace(/\n/g, "<br>");
 return string.replace(/<br>$/g, "<br><br>");
}

function CellBorderToggleSelect(oldCell, newCell, setFocusElement = true)
{
 if (oldCell)
    {
     oldCell.style.outline = "none";
     oldCell.style.boxShadow = "none";
    }
 if (uiProfile['main field table cursor cell']['outline'] != undefined) newCell.style.outline = uiProfile['main field table cursor cell']['outline'];
 if (uiProfile['main field table cursor cell']['shadow'] != undefined) newCell.style.boxShadow = uiProfile['main field table cursor cell']['shadow'];
 if (setFocusElement)
    {
     focusElement.td = newCell;
     focusElement.x = newCell.cellIndex;
     focusElement.y = newCell.parentNode.rowIndex;
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
 if (!focusElement.td || focusElement.td.contentEditable === 'true' || contextmenu || (abs && focusElement.x == x && focusElement.y == y)) return;
 
 let a, b, newTD;
 if (abs)
    {
     a = x;
     b = y;
    }
  else 
    {
     a = focusElement.x + x;
     b = focusElement.y + y;
    }
    
 if (a < 0 || a >= mainTableWidth || b < 0 || b >= mainTableHeight) return;
 
 newTD = mainTablediv.rows[b].cells[a];
 if (abs || isVisible(newTD) || (!isVisible(focusElement.td) && tdVisibleSquare(newTD) > tdVisibleSquare(focusElement.td)) || (y == 0 && xAxisVisible(newTD)) || (x == 0 && yAxisVisible(newTD)))
    {
     if (!abs) event.preventDefault();
     CellBorderToggleSelect(focusElement.td, newTD);
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
 if (e.offsetLeft >= mainDiv.scrollLeft && e.offsetLeft - mainDiv.scrollLeft + e.offsetWidth < mainDiv.offsetWidth) return true;
 return false;
}

function yAxisVisible(e)
{
 if (e.offsetTop >= mainDiv.scrollTop && e.offsetTop - mainDiv.scrollTop + e.offsetHeight < mainDiv.offsetHeight) return true;
 return false;
}

function rangeTest(a, b)
{
 let l = b.length;
 for (let i = 0; i < l; i += 2)
     if (a >= b[i] && a <= b[i+1]) return true;
 return false;
}

function callController(data)
{
 let object;
 lg(cmd);
 
 switch (cmd)
	{
	 case 'New Object Database':
	 case 'Edit Database Structure':
	 case 'GETMENU':
	 case 'GETMAINSTART':
	 case 'GETMAIN':
	      object = { "cmd": cmd };
	      if (data != undefined) object.data = data;
	      break;
	 case 'Description':
	      let cell, hidden = msg = '';
	      //--------------Add object and element information to the result message---------------
	      if (focusElement.td != undefined && mainTable[focusElement.y] && mainTable[focusElement.y][focusElement.x] && (cell = mainTable[focusElement.y][focusElement.x]) && cell.oId)
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
	      if (cell) msg += `\n\nTable cell 'x' coordinate: ${focusElement.x}\nTable cell 'y' coordinate: ${focusElement.y}\n\n`;
	      //--------------------Add database and view info--------------------
	      if (activeOV.substr(0, 1) === '_') hidden = ' (hidden from sidebar)';
	      msg += `Object Database: ${activeOD}\nObject View${hidden}: ${activeOV}\nMain table columns: ${mainTableWidth}\nMain table rows: ${mainTableHeight}\nObjects on the page: ${objectsOnThePage}`;
	      //--------------Add part of sql string object selection-------------
	      let parammsg = '', count = 1;
	      for (cell in paramsOV) parammsg += `\n${count++}. ` + cell.substr(1).replace(/_/g, ' ') + ': ' + paramsOV[cell];
	      if (parammsg != '') msg += '\n\nObject View input parameters:' + parammsg;
	      //--------------Display result message in warning box---------------
	      warning(msg, 'Description');
	      break;
	 case 'Help':
	      box = help;
	      ShowBox();
	      break;
	 case 'New Object':
	      if (objectTable === undefined) break;
	      object = { "cmd": 'INIT', "data": {}, 'paramsOV': paramsOV };
	      if (objectTable[String(NEWOBJECTID)] != undefined)
	         for (let eid in objectTable[String(NEWOBJECTID)])
		     object['data'][eid] = mainTable[objectTable[String(NEWOBJECTID)][eid].y][objectTable[String(NEWOBJECTID)][eid].x]['data'];
	      break;
	 case 'Delete Object':
	      if (mainTable[focusElement.y] && mainTable[focusElement.y][focusElement.x] && mainTable[focusElement.y][focusElement.x].realobject)
		 object = { "cmd": 'DELETEOBJECT', "oId": mainTable[focusElement.y][focusElement.x].oId, 'paramsOV': paramsOV };
	      break;
	 case 'LOGIN':
	 case 'CONFIRM':
	 case 'DBLCLICK':
	 case 'KEYPRESS':
	 case 'CUSTOMIZATION':
	      object = { "cmd": cmd };
	      if (focusElement.td && mainTable[focusElement.y] && mainTable[focusElement.y][focusElement.x])
	         {
	          object["oId"] = mainTable[focusElement.y][focusElement.x].oId;
		  object["eId"] = mainTable[focusElement.y][focusElement.x].eId;
		 }
	      if (data != undefined) object.data = data;
	      break;
	 default:
	      if (cmd.substr(0, 7) != 'Logout ')
		 {
		  lg("Undefined application message: '" + cmd + "'!");
		  warning("Undefined application message: '" + cmd + "'!");
		  return;
		 }
	      object = { cmd: 'LOGOUT' };
	}
	
 if (object)
    {
     object.OD = activeOD;
     object.OV = activeOV;
     Hujax("main.php", controllerCmdHandler, object);
    }
}

function ShowBox()
{
 /*******************************************************************************************************************************/
 /* box.title		= box title												*/
 /*																*/
 /* box.dialog		= JSON with properties as tabs, each tab represents JSON with properties as profiles			*/
 /*			  Each profile represents JSON with properties as interface elements with next format:			*/
 /*		   	  "element_name":											*/
 /*						{										*/
 /*				      	  	 "type"      : select|multiple|checkbox|radio|textarea|password|text		*/
 /*				      	  	 "head"      : "<any text>"							*/
 /*				      	  	 "data"      : "+text1|text2|text3"						*/
 /*		  		      	  	 "help"	     : "<any text>"							*/
 /*		  		      	  	 "line"	     : ""								*/
 /*		  		      	  	 "readonly"  : ""								*/
 /*				     	 	}										*/
 /*																*/
 /* box.buttons		= JSON with properties as buttons where property name is a button text					*/
 /*			  Non empty values make th system to call the controller on specified button click event		*/
 /*																*/
 /* box.flags		= JSON with next properties:										*/
 /*			  "esc" - property lets user to cancel dialog box by esc button 					*/
 /*			  "style" - dialog box content wrapper style attribute							*/
 /*			  "pad" - active (current) dialog box pad (if exists)							*/
 /*			  "profile" - active (current) dialog box profile (if exist)						*/
 /*			  "display_single_pad" - set this flag to display pad block in case of single one			*/
 /*			  "display_single_profile" - set this flag to display profile select in case of single one		*/
 /*			  "callback" - any callback string element handler to pass without changes at CONFIRM event		*/
 /*			  "cmd" - initial command to return to the controller							*/
 /*******************************************************************************************************************************/
 if (typeof box !== 'object') return;
 let inner = getInnerDialog();
 HideHint();
 HideContextmenu();

 //---------------Any content?---------------
 if (inner)
    {
     let buttonStyle;
     // Add title
     if (typeof box.title === 'string') inner = '<div class="title">' + toHTMLCharsConvert(box.title) + '</div>' + inner;
     // Add buttons
     inner += '<div class="footer">';
     for (let button in box.buttons)
         {
	  buttonStyle = '';
	  if (typeof box.buttons[button] === 'string' && box.buttons[button].trim() != '') buttonStyle = ' style ="' + escapeHTMLTags(box.buttons[button].trim()) + '"';
	  inner += '<div class="button"' + buttonStyle + '>' + button + '</div>';
	 }
     boxDiv.innerHTML = inner + '</div>';
     // Calculate left/top box position
     boxDiv.style.left = Math.trunc((document.body.clientWidth - boxDiv.offsetWidth)*100/(2*document.body.clientWidth)) + "%";
     boxDiv.style.top = Math.trunc((document.body.offsetHeight - boxDiv.offsetHeight)*100/(2*document.body.offsetHeight)) + "%";
     // Show box div
     boxDiv.className = 'box ' + uiProfile["effects"]['box'] + 'show';
     // Apply filters if exist
     if (uiProfile["effects"]["box filter"])
        {
	 mainDiv.style.filter = uiProfile["effects"]["box filter"];
	 sidebarDiv.style.filter = uiProfile["effects"]["box filter"];
	}
    }
 else {box = null; }
}

function getInnerDialog()
{
 if (typeof box.dialog !== 'object') return '';
 let element, data, count = 0, inner = '';
 
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
 if (count > 1 || box.flags.display_single_profile != undefined)
    {
     // Add profile head
     if (box.flags.padprofilehead != undefined && box.flags.padprofilehead[box.flags.pad] != undefined) inner += '<pre class="element-headers">' + box.flags.padprofilehead[box.flags.pad] + '</pre>';
     // In case of default first profile set zero value to use as a select attribute
     if (typeof data === 'string') data = 0;
     // Add select block and divider
     inner += '<div class="select" type="select-profile"><div value="' + data + '">' + box.flags.profile + '</div></div><div class="divider"></div>';
    }
    
 //------------------Parsing interface element in box.dialog.<current pad>.<current profile>------------------
 for (let name in box.dialog[box.flags.pad][box.flags.profile])
     {
      element = box.dialog[box.flags.pad][box.flags.profile][name];
      // Display element hint icon
      data = '';
      if (element.help != undefined && typeof element.help == "string") data = '<span name="' + name + '" class="help-icon"> ? </span>'
      // Display element head
      if (element.head != undefined && typeof element.head == "string") inner += '<pre class="element-headers">' + toHTMLCharsConvert(element.head) + ' ' + data + '</pre>';
      // Filling interface element data, leave empty string in case of undefined
      data = '';
      if (element.data != undefined && typeof element.data == "string") data = element.data;
      switch (element.type)
	     {
	      case 'select-multiple':
		   if (data != '')
		      {
		       inner += '<div class="select" name="' + name + '" type="select-multiple">';
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
			        inner += '<div class="select" name="' + name + '" type="select-one"><div value="' + count + '">' + data.substr(1) + '</div></div>';
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
		       let readonly = '';
		       if (element.readonly != undefined) readonly = ' disabled';
		       for (data of data.split('|')) if (data != '')
			  {
			   const pos = data.search(/[^\+]/);
			   if (pos > 0) inner += '<input type="' + element.type + '" class="' + element.type + '" name="' + name + '" checked' + readonly + '><label for="' + name + '">' + data.substr(pos) + '</label>';
			    else inner += '<input type="' + element.type + '" class="' + element.type + '" name="' + name + '"' + readonly + '><label for="' + name + '">' + data + '</label>';
			  }
		      }
		   break;
	      case 'password':
	      case 'text':
	           if (element.readonly != undefined) inner += '<input type="' + element.type + '" class="' + element.type + '" name="' + name + '" value="' + escapeDoubleQuotes(data) + '" readonly>';
		    else inner += '<input type="' + element.type + '" class="' + element.type + '" name="' + name + '" value="' + escapeDoubleQuotes(data) + '">';
		   break;
	      case 'textarea':
		   if (element.readonly != undefined) inner += '<textarea type="' + element.type + '" class="textarea" name="' + name + '" readonly>' + data + '</textarea>';
		    else inner += '<textarea type="' + element.type + '" class="textarea" name="' + name + '">' + data + '</textarea>';
		   break;
	     }
      if (element.line != undefined) inner += '<div class="divider"></div>';
     }
     
 if (inner != '')
    {
     let contentStyle = '';
     if (box.flags && box.flags.style && typeof box.flags.style === 'string') contentStyle = ' style ="' + escapeHTMLTags(box.flags.style) + '"';
     return '<div class="boxcontentwrapper"'+ contentStyle +'>' + inner + '</div>';
    }
 return '';
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
 if (box)
    {
     boxDiv.className = 'box ' + uiProfile["effects"]["box"] + 'hide';
     expandedDiv.className = 'select expanded ' + uiProfile["effects"]["select"] + 'hide';
     box = null;
     mainDiv.style.filter = 'none';
     sidebarDiv.style.filter = 'none';
    }
}

function getAbsoluteX(element, flag = '')
{
 let disp = 0;								// Select element left position
 if (flag == 'end') disp = element.offsetWidth;				// Select element right position
 if (flag == 'middle') disp = Math.trunc(element.offsetWidth/2);	// Select element middle position
 
 return element.offsetLeft - element.scrollLeft + mainDiv.offsetLeft - mainDiv.scrollLeft + disp;
}

function getAbsoluteY(element, flag = '')
{
 let disp = 0;								// Select element top position
 if (flag == 'end') disp = element.offsetHeight;			// Select element bottom position
 if (flag == 'middle') disp = Math.trunc(element.offsetHeight/2);	// Select element middle position
 
 return element.offsetTop - element.scrollTop + mainDiv.offsetTop - mainDiv.scrollTop + disp;
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

function ShowContextmenu(event)
{
 let innerHTML, data;
 
 // Context event on wrap icon cell with OD item? Display OD context menu
 if (event.target.classList.contains('wrap') && event.target.nextSibling.classList.contains('sidebar-od'))
    {
     innerHTML = sidebarODContext;
     data = event.target.nextSibling.dataset.od;
    }
 // Context event on OD item? Display OD context menu
  else if (event.target.classList.contains('sidebar-od'))
    { 
     innerHTML = sidebarODContext;
     data = event.target.dataset.od;
    }
 // Context event on OV item, on wrap icon cell with OV item or on sidebar empty area? Display OV context menu
  else if ((event.target.classList.contains('wrap') && event.target.nextSibling.classList.contains('sidebar-ov')) || event.target.classList.contains('sidebar-ov') || event.target.classList.contains('sidebar')) innerHTML = sidebarOVContext;
 // Application context menu on main field empty area? Display mainContext context menu
  else if ((event.target === mainDiv && activeOV != '') || event.target === mainTablediv) innerHTML = mainContext;
 // Application context menu on main field table or has been generated by keyboard (event.which != 3) and any element is selected? Display appropriate context menu
  else if (event.target.tagName === 'TD' || (focusElement.td != undefined && event.which != 3))
    {
     if (event.target.tagName === 'TD') CellBorderToggleSelect(focusElement.td, event.target);
     if (mainTable[focusElement.y] && mainTable[focusElement.y][focusElement.x] && mainTable[focusElement.y][focusElement.x].realobject) innerHTML = mainObjectContext;
      else innerHTML = mainContext;
    }
    
 if (innerHTML != undefined)
    {
     if (user.length > 12) innerHTML += '<div class="contextmenuItems">Logout '+ user.substr(0, 10) +'..</div>';
      else innerHTML += '<div class="contextmenuItems">Logout '+ user +'</div>';
     event.preventDefault();
     contextmenuDiv.innerHTML = innerHTML;
     contextmenu = { item : null };
     if (data) contextmenu.data = data;
     // Context menu div left/top calculating
     if (event.which != 3)
        {
	 data = focusElement.td;
	 if (!contextFitMainDiv(data.offsetLeft - mainDiv.scrollLeft + data.offsetWidth, data.offsetTop - mainDiv.scrollTop + data.offsetHeight) &&
	     !contextFitMainDiv(data.offsetLeft - mainDiv.scrollLeft - contextmenuDiv.offsetWidth, data.offsetTop - mainDiv.scrollTop + data.offsetHeight) &&
	     !contextFitMainDiv(data.offsetLeft - mainDiv.scrollLeft - contextmenuDiv.offsetWidth, data.offsetTop - mainDiv.scrollTop - contextmenuDiv.offsetHeight) &&
	     !contextFitMainDiv(data.offsetLeft - mainDiv.scrollLeft + data.offsetWidth, data.offsetTop - mainDiv.scrollTop - contextmenuDiv.offsetHeight) &&
	     !contextFitMainDiv(data.offsetLeft - mainDiv.scrollLeft + data.offsetWidth - contextmenuDiv.offsetWidth, data.offsetTop - mainDiv.scrollTop + data.offsetHeight) &&
	     !contextFitMainDiv(data.offsetLeft - mainDiv.scrollLeft, data.offsetTop - mainDiv.scrollTop + data.offsetHeight) &&
	     !contextFitMainDiv(data.offsetLeft - mainDiv.scrollLeft - contextmenuDiv.offsetWidth, data.offsetTop - mainDiv.scrollTop) &&
	     !contextFitMainDiv(data.offsetLeft - mainDiv.scrollLeft, data.offsetTop - mainDiv.scrollTop - contextmenuDiv.offsetHeight) &&
	     !contextFitMainDiv(data.offsetLeft - mainDiv.scrollLeft + data.offsetWidth, data.offsetTop - mainDiv.scrollTop))
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
     contextmenuDiv.className = 'contextmenu ' + uiProfile["effects"]["contextmenu"] + 'show';
    }
  else
    {
     HideContextmenu();
    }
}

function HideContextmenu()
{
 if (contextmenu)
    {
     contextmenuDiv.className = 'contextmenu ' + uiProfile["effects"]["contextmenu"] + 'hide';
     contextmenu = null;
    }
}

function SetContextmenuItem(newItem)
{
 if (!contextmenu) return;
 
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
 hintDiv.className = 'hint ' + uiProfile["effects"]["hint"] + 'show';
}

function HideHint()
{
 if (hint)
    {
     clearTimeout(tooltipTimerId);                                              
     hintDiv.className = 'hint ' + uiProfile["effects"]["hint"] + 'hide';
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

function escapeDoubleQuotes(string)
{ 
 return string.replace(/"/g,"&quot;");
}

function escapeHTMLTags(string)
{
 return string.replace(/</g,"&lt;").replace(/"/g,"&quot;");
}

function warning(text, title)
{
 if (typeof text != 'string') text = 'Undefined warning message!';
 if (typeof title != 'string') title = 'Warning';
 box = { title: title, dialog: {pad: {profile: {element: {head: '\n' + text}}}}, buttons: {"&nbsp;   OK   &nbsp;": ""}, flags: {esc: "", style: "min-width: 500px; min-height: 65px; max-width: 1500px; max-height: 500px;"} };
 ShowBox();
}

function isObjectEmpty(object, excludeProp)
{
 if (typeof object != 'object') return false;
 
 for (let element in object) if (!(object[element] === '' || element === excludeProp)) return false;
 return true;
}

function styleUI()
{
 let element, key, inner = '';
 
 for (element in uiProfile)
  if (uiProfile[element]["target"] != undefined)
     {
      inner += uiProfile[element]["target"] + " {";
      for (key in uiProfile[element]) if (key != "target" && uiProfile[element][key] != "")
          inner += key + ": " + uiProfile[element][key];
      inner += '}'; //https://dev.to/karataev/set-css-styles-with-javascript-3nl5, https://professorweb.ru/my/javascript/js_theory/level2/2_4.php
     }
 style.innerHTML = inner;
}

function uiProfileSet(customization)
{
 let selector, property;
 customization = customization.pad;
 
 for (selector in customization) if (selector != 'Scheme')
     {
      for (property in customization[selector]) if (property != 'element0' && property != 'element1')
	  uiProfile[selector][customization[selector][property]['head'].slice(0, -1)] = customization[selector][property]['data'];
      if (customization[selector]['element0'] != undefined && customization[selector]['element0']['target'] != undefined)
         uiProfile[selector]['target'] = customization[selector]['element0']['target'];
     }
 //if (!(objectsPerPage = Number(uiProfile['misc customization']['objects per page']))) objectsPerPage = DEFAULTOBJECTSPERPAGE;
 // else if (objectsPerPage > MAXOBJECTSPERPAGE) objectsPerPage = MAXOBJECTSPERPAGE;
}

const help = { title: 'Help', dialog:  { "System description": { profile: { element: { head:
`Tabels application is a set of custom data tables the user can interact many different ways.
Every table consists of identical objects, which, in turn, are set of user defined elements.
Table data of itself is called Object Database (OD) and can be changed or created by
appropriate sidebar context menu. Every OD should contain some Object Views (OV), that
define which object of the OD (see object selection help section) and what kind of element
should be displayed and how (see element selection help section).

OV allows users to operate specified objects many different ways and display its data 
generated by binded to elements appropriate handlers. Simple OV is a classic table with
object list in 'y' order and its elements in 'x' order, so Object Database is similar to
any SQL database, where objects are rows and elements are fields.

Element data represents itself JSON data type and stored in SQL database with that type.
Element JSON data can be managed by appropriate built-in or user defined element
handlers (see element handler help section).`
	    		  }}},
			  "Object Selection": { profile: { element: { head:
`Logical expression based on elements and its values is used to match the given object. Expression format:
    (<id[ver]>|user|<string>[<operator>]..)..
    id            Object element id (format $id) or its title (format $"my_title" or $'my_title').
    user          Username/group selection function will be applied to.
    string        Any text in double quotes. Single quotes interpret string as a regular expression.
	          For case sensitive value use char '_' after quoted string. Additionally, this field
		  first char '@' before the quoted string makes the system to retrieve text or regular
		  expression from dialog box user input with the <string> text comment.
		  Also no qouted predefined strings such as #user (determines username that selection
		  function applying to) or #undef (id, ver or user/group doesn't exist; string of itself
		  has false logical value) can be used.
    operator  	  Compare operations: =  !=  ==  !==  =>  <=  <  >. Double char '==' construction means
		  exact match, whereas single '=' matches "consists of" case.
                  Element versions compare:  logical OR applied for default, char '&' before operator -
		  logical AND applied. Element versions on both sides of the expression are compared
		  one by one until the last match or one to any.
                  Arithmetic operations: +  -  \  *. For digital operands only. All arithmetic operations
		  on non digital operands leads to undefined result.
                  String operations: Single point '.' concatenates strings.
		  Logical operations: '!', 'AND', 'OR'.
		  Link operations: 'uplink', 'downlink'. Selects appropriate object tree based on 'link'
		  property (see appropriate tag) from the first matched object.
       ver        Version expression is a logical expression in round brackets and without quotes (match
    		  last selected object version), with single quotes (match first selected object version)
		  or with double quotes (match all selected object versions). Format:
		  (<id>|<string>|<operator> ..) ..
		  Absent field or blank expression selects last available version, any digit value -
		  exact version number.`
	    		  }}},
			  "Keyboard/Mouse": { profile: { element: { head:
`  - CTRL with left button click on any object element opens new browser tab with the element text as url*
  - CTRL with arrow left/right key set table cursor to the left/right end of the table*
  - CTRL with Home/End key set table cursor to the upper/lower end of the table
  - CTRL+C or CTRL+INS copy element text data to clipboard*
  - CTRL+Shift+C or CTRL+Shift+INS copy current object to clipboard*
  - CTRL+V pastes text data to the current via 'KEYPRESS' event (see event section help) or
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
    width/height values change. Use element selection (see appropriate help section) feature to set
    initial width/height values. By default, widths and heights of the table and its cells are adjusted
    to fit the content.
  
* will be available in a future releases`
	    		  }}},
			  "Element events": { profile: { element: { head:
`JSON strings (one by line) to pass to the element handler when specified event (in self-titled property) occurs. Format:
{"event": "<event name>", "data": "<event data>", "oid": "<object id>", "user": "<username>", "header":"<header>", "<property>": "<user string|json string>"}
<event name> - property is mandatory and represents external event such as:
  KEYPRESS (occurs when the keyboard input is registered for letters, digits, space and non symbol keys: F2, F12, INS, DEL),
    DBLCLICK (left button mouse double click),
      CONFIRM (callback event occurs when dialog box or cell content editable data returns to the handler to be confirmed after the user has finished
        dialog/edit process. Event is sent automatically with no args by default),
	  INIT (object event occurs when the new object is being created),
	    CHANGE (object event occurs after one of object elements has been changed by handler command SET or RESET, see handler section help).
	      Error strings or JSON strings with undefined <event name> will be ignored.
	      <event data> - property is set automatically by the controller with specified event data.
	        For KEYPRESS it will be the key code or the string in case of text paste operation.
		  For CONFIRM it will be editable text data or DIALOG handler command format json data, see handler section help.
		    For INIT it will be new element cell text from OV new object table cells.
		      For two other events DBLCLICK and CHANGE this property is undefined.
		      <oid>, <user> and <header> properties are object id the specified event occurs on, user initiated the event and element header respectively.
		        Properties are set automatically by the controller. Two events (KEYPRESS, DBLCLICK) that can be emulated by scheduler are initiated by 'system' user.
			<any property> is any user defined properties that serve to pass any user defined string to the handler with one exception below.
			  In case of json formated string - controller interprets this string as a certain object element property value that should be drawn and passed to the handler.
			    JSON string format: {"OD": "<OD name>", "OV": "<OV name>", "oid": "<object id>", "eid": "<element id>", "prop": "<element JSON data property name>"}
			      In case of "OD", "OV", "oId" or "eId" omitted - current Object Database/View and object/element id values are used. Property "prop" is mandatory.
			        Object element JSON data should contain "prop" property, otherwise empty string value to pass to the handler is used.`
	    		  }}}
	    	        },
	       buttons: { "&nbsp;   OK   &nbsp;": "" },
	       flags:   { esc: "", style: "min-width: 700px; min-height: 600px;" }
	     };
