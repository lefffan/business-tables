/*------------------------------CONSTANTS------------------------------------*/
const TABLE_MAX_CELLS = 200000;
const NEWOBJECTID = 1;  
const TITLEOBJECTID = 2;
const STARTOBJECTID = 3;
const style = document.createElement('style');
const range = document.createRange();   
const selection = window.getSelection();
// User interface default profile
const uiProfile = {
		  // Body
		  "body": { "target": "body", "background-color": "#343E54;" },
		  // Sidebar
    		  "sidebar": { "target": ".sidebar", "background-color": "rgb(17,101,176);", "border-radius": "5px;", "color": "#9FBDDF;", "width": "13%;", "height": "90%;", "left": "4%;", "top": "5%;", "scrollbar-color": "#1E559D #266AC4;", "scrollbar-width": "thin;", "box-shadow": "4px 4px 5px #222;" },
		  "sidebar wrap icon": { "wrap": "&#9658;", "unwrap": "&#9660;" }, //{ "wrap": "+", "unwrap": "&#0150" }, "wrap": "&#9658;", "unwrap": "&#9660;"
		  "sidebar wrap cell": { "target": ".wrap", "font-size": "70%;", "padding": "3px 5px;" },
		  "sidebar item active": { "target": ".itemactive", "font-weight": "bolder;", "background-color": "#4578BF;", "color": "#FFFFFF;" },
		  "sidebar item hover": { "target": ".sidebar tr:hover", "background-color": "#4578BF;", "cursor": "pointer;" },
		  "sidebar object database": { "target": ".sidebar-od", "padding": "3px 5px 3px 0px;", "margin": "0px;", "color": "", "width": "100%;"  },
		  "sidebar object view": { "target": ".sidebar-ov", "padding": "2px 5px 2px 10px;", "margin": "0px;", "color": "" },		  
		  // Main field
		  "main field": { "target": ".main", "width": "76%;", "height": "90%;", "left": "18%;", "top": "5%;", "border-radius": "5px;", "background-color": "#EEE;", "scrollbar-color": "#CCCCCC #FFFFFF;", "box-shadow": "4px 4px 5px #111;" },
		  "main field table": { "target": "table", "margin": "0px;" },
		  "main field table cell": { "target": "td", "padding": "10px;", "border": "1px solid #999;", "white-space": "pre;", "text-overflow": "ellipsis;" },
		  "main field table active cell": { "outline": "red auto 1px", "shadow": "0 0 5px rgba(100,0,0,0.5)" },
		  "main field table cursor": { "target": ".main table tbody tr td:not([contenteditable=true])", "cursor": "cell;" },
		  "main message": { "target": ".main h1", "color": "#BBBBBB;" },
		  // Scrollbar
		  "scrollbar": { "target": "::-webkit-scrollbar", "width": "8px;", "height": "8px;" },
		  // Context Menu
		  "context menu": { "target": ".contextmenu", "width": "240px;", "background-color": "#F3F3F3;", "color": "#1166aa;", "border": "solid 1px #dfdfdf;", "box-shadow": "1px 1px 2px #cfcfcf;", "font-family": "sans-serif;", "font-size": "16px;", "font-weight": "300", "line-height": "1.5;", "padding": "12px 0;" },
		  "context menu item": { "target": ".contextmenuItems", "margin-bottom": "4px;", "padding-left": "10px;" },
		  "context menu item cursor": { "target": ".contextmenuItems:hover:not(.greyContextMenuItem)", "cursor": "pointer;" },
		  "context menu item active": { "target": ".activeContextMenuItem", "color": "#fff;", "background-color": "#0066aa;" },
		  "context menu item grey": { "target": ".greyContextMenuItem", "color": "#dddddd;" },
		  // Box types
		  "hint": { "target": ".hint", "background-color": "#CAE4B6;", "color": "#7E5A1E;", "border": "none;", "padding": "5px;" },
		  "box": { "target": ".box", "background-color": "#16272A;", "color": "#000;", "border-radius": "5px;", "border": "none;", "box-shadow": "none;" },
		  // Box interface elements
/*#404851;*/	  "dialog box title": { "target": ".title", "background-color": "transparent;", "color": "#AAA;", "border": "#000000;", "border-radius": "5px 5px 0 0;", "font": ".9em Lato, Helvetica;", "padding": "5px;" },
		  "dialog box pad": { "target": ".pad", "background-color": "#404851;", "border-left": "none;", "border-right": "none;", "border-top": "none;", "border-bottom": "none;", "padding": "5px;", "margin": "0;", "font": ".9em Lato, Helvetica;", "color": "#aaa;", "border-radius": "5px 5px 0 0;" },
		  "dialog box active pad": { "target": ".activepad", "background-color": "#17262B;", "border-left": "none;", "border-right": "none;", "border-top": "none;", "border-bottom": "none;", "padding": "5px;", "margin": "0;", "font": ".9em Lato, Helvetica;", "color": "#aaa;", "border-radius": "5px 5px 0 0;" },
		  "dialog box pad bar": { "target": ".padbar", "background-color": "transparent;", "border": "none;", "padding": "4px;", "margin": "10px 0 15px 0;" },
		  "dialog box divider": { "target": ".divider", "background-color": "transparent;", "margin": "5px 10px 5px 10px;", "height": "0px;", "border-bottom": "1px solid #4F4F4F;", "border-top-color": "transparent;", "border-left-color": "transparent;" , "border-right-color": "transparent;" },
		  "dialog box button": { "target": ".button", "background-color": "#13BB72;", "border": "none;", "padding": "10px;", "margin": "10px;", "border-radius": "5px;", "font": "bold 12px Lato, Helvetica;", "color": "white;" },
		  "dialog box button hover": { "target": ".button:hover", "cursor": "pointer;", "background": "", "color": "", "border": "" },
		  "dialog box element headers": { "target": ".element-headers", "margin": "5px;", "font": ".9em Lato, Helvetica;", "color": "#9A7900;", "text-shadow": "none;" },
		  "dialog box help icon": { "target": ".help-icon", "padding": "1px;", "font": ".9em Lato, Helvetica;", "color": "black;", "background": "#BB0;", "border-radius": "40%;" },
		  "dialog box help icon hover": { "target": ".help-icon:hover", "padding": "1px;", "font": "1em Lato, Helvetica;", "color": "black;", "background": "#880;", "cursor": "pointer;", "border-radius": "40%;" },
		  //
		  "dialog box select": { "target": ".select", "background-color": "#17262B;", "color": "#AAA;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 5px 10px;", "outline": "none;", "border": "1px solid #777;", "padding": "0px 0px 0px 0px;", "overflow": "auto;", "max-height": "10em;", "scrollbar-width": "thin;", "min-width": "10em;", "width": "auto;", "display": "inline-block;" },
		  "dialog box select option": { "target": ".select > div", "padding": "2px 20px 2px 5px;", "margin": "0px;" },
		  "dialog box select option hover": { "target": ".select:not([type*='o']) > div:hover", "background-color": "#404851;", "color": "" },
		  "dialog box select option selected": { "target": ".selected", "background-color": "#404851;", "color": "#fff;" },
		  "dialog box select option expanded": { "target": ".expanded", "margin": "0px !important;", "position": "absolute;" },
		  //
		  "dialog box radio": { "target": "input[type=radio]", "background": "transparent;", "border": "1px solid #777;", "font": ".8em/1 sans-serif;", "margin": "3px 5px 3px 10px;", "border-radius": "20%;", "width": "1.2em;", "height": "1.2em;" },
		  "dialog box radio checked" : { "target": "input[type=radio]:checked::after", "content": "", "color": "white;" },
		  "dialog box radio checked background" : { "target": "input[type=radio]:checked", "background": "#00a0df;", "border": "1px solid #00a0df;" },
		  "dialog box radio label" : { "target": "input[type=radio] + label", "color": "#AAA;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 0px 0px;" },
		  //
		  "dialog box checkbox": { "target": "input[type=checkbox]", "background": "transparent;", "border": "1px solid #777;", "font": ".8em/1 sans-serif;", "margin": "3px 5px 3px 10px;", "border-radius": "50%;", "width": "1.2em;", "height": "1.2em;" },
		  "dialog box checkbox checked" : { "target": "input[type=checkbox]:checked::after", "content": "", "color": "white;" },
		  "dialog box checkbox checked background" : { "target": "input[type=checkbox]:checked", "background": "#00609f;", "border": "1px solid #00609f;" },
		  "dialog box checkbox label" : { "target": "input[type=checkbox] + label", "color": "#CCC;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 0px 0px;" },
		  //
		  "dialog box input text": { "target": "input[type=text]", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background": "transparent;", "border": "1px solid #777;", "outline": "none;", "color": "#AAA;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  "dialog box input password": { "target": "input[type=password]", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background": "transparent;", "border": "1px solid #777;", "outline": "", "color": "#AAA;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  "dialog box input textarea": { "target": "textarea", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background": "transparent;", "border": "1px solid #777;", "outline": "", "color": "#AAA;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  // Effects and animation
		  "effects": { "hint": "hotnews", "contextmenu": "rise", "box": "slideup", "select": "rise", "box filter": "grayscale(0.5) blur(3px)" },
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
		  "none show": { "target": ".noneshow", "visibility": "visible;" }
		  };
const mainObjectContext = '<div class="contextmenuItems">New Object</div><div class="contextmenuItems">Delete Object</div><div class="contextmenuItems">Element description</div><div class="contextmenuItems">Help</div>';
const mainTitleObjectContext = '<div class="contextmenuItems">New Object</div><div class="contextmenuItems greyContextMenuItem">Delete Object</div><div class="contextmenuItems">Element description</div><div class="contextmenuItems">Help</div>';
const mainDefaultContext = '<div class="contextmenuItems">New Object</div><div class="contextmenuItems greyContextMenuItem">Delete Object</div><div class="contextmenuItems greyContextMenuItem">Element description</div><div class="contextmenuItems">Help</div>';
const sidebarOVContext = '<div class="contextmenuItems">New Object Database</div><div class="contextmenuItems greyContextMenuItem">Edit Database Structure</div>';
const sidebarODContext = '<div class="contextmenuItems">New Object Database</div><div class="contextmenuItems">Edit Database Structure</div>';
/*------------------------------VARIABLES------------------------------------*/
let contextmenu, contextmenuDiv;
let hint, hintDiv;
let box = selectExpandedDiv = null, boxDiv, expandedDiv;
let tooltipTimerId, undefinedcellRuleIndex;
let mainDiv, sidebarDiv, mainTablediv;
let mainTable, mainTableWidth, mainTableHeight, objectTable;
let cmd = activeOD = activeOV = '';
let sidebar = focusElement = {};
/*---------------------------------------------------------------------------*/

function loog(...data)
{
 data.forEach((value) => console.log(value));
}

function looog(...data)
{
 data.forEach((value) => console.dir(value));
}

function Hujax(url, callback, requestBody)
{
 fetch(url, { method: 'POST',  
		 headers: { 'Content-Type': 'application/json; charset=UTF-8'}, 
		 body: JSON.stringify(requestBody) }).then(function(response) {
			    if (response.ok) response.json().then(callback);
			     else displayMainError('Request failed with response ' + response.status + ': ' + response.statusText); })
							    .catch (function(error) { console.log('Ajax request error: ', error); });
 return true;
}

window.onload = function()
{
 // Append document style tag
 document.head.appendChild(style);
 
 // Get user inteface profile
 cmd = 'GETUI';
 callController();

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
 
 cmd = 'GETMENU';
 callController();
 cmd = 'OBTAINMAIN';
 callController();
}

function drawSidebar(data)
{
 if (typeof data != 'object') return;
 let ovlistHTML, sidebarHTML = '';
 
 for (let od in data)
     {
     // Set wrap status (empty string key) to true for default or to old instance of sidebar OD wrap status
     if (sidebar[od] === undefined || sidebar[od][''] === undefined) data[od][''] = true;
      else data[od][''] = sidebar[od][''];
      
     // Create OV names list with active OV check 
     ovlistHTML = '';
     for (let ov in data[od]) if (ov != '')
	 {
	  if (activeOD === od && activeOV === ov) ovlistHTML += '<tr class="itemactive"><td class="wrap"></td><td class="sidebar-ov">' + ov + '</td><td style="display: none;">' + od + '</td></tr>';
	   else ovlistHTML += '<tr><td class="wrap"></td><td class="sidebar-ov">' + ov + '</td><td style="display: none;">' + od + '</td></tr>';
	 }

     // Draw wrap icon
     if (ovlistHTML === '') sidebarHTML += '<tr><td class="wrap"></td>';  // Insert empty wrap icon
      else if (data[od][''] === false) sidebarHTML += '<tr><td class="wrap">' + uiProfile['sidebar wrap icon']['unwrap'] + '</td>'; // Insert unwrap icon
       else sidebarHTML += '<tr><td class="wrap">' + uiProfile['sidebar wrap icon']['wrap'] + '</td>'; // Insert wrap icon

     // Insert OD name
     sidebarHTML += '<td class="sidebar-od">' + od + '</td><td style="display: none;"></td></tr>';
     
     // Insert OV names list if OD is unwrapped
     if (data[od][''] === false) sidebarHTML += ovlistHTML;
    }

 // Push calculated html text to sidebar div
 sidebarHTML != '' ? sidebarDiv.innerHTML = '<table style="margin: 0px;"><tbody>' + sidebarHTML + '</tbody></table>' : sidebarDiv.innerHTML = '';
  
 // Reset sidebar with new data
 sidebar = data;
}	 

function drawMain()
{
 let oid, eid, obj, cell;
 let x, y, error, n = 1, q = 55;
 mainTableWidth = mainTableHeight = 0;
 mainTable = [];
 focusElement = {};
  
 // Fill mainTable tw dimension array with next format - mainTable[y][x]: { oid, eid, data, style, collapse}
 // Format of objectTable[oid][eid]: ['json': 'any element json data', 'props': 'oid, eid, eval(x), eval(y), style, collapse, startevent']
 for (oid in objectTable) if (oid != 0) // Iterate object identificators from objectTable
     {
      for (eid in objectTable[oid]) if (eid != 0) // Iterate element identificators from current object
          {
           cell = objectTable[oid][eid];
           try   { obj = JSON.parse(cell['json']); }
           catch { continue; }
           
           try	 {
		  x = Math.trunc(eval(cell['props']['x']));
        	  y = Math.trunc(eval(cell['props']['y']));
		 }
	   catch {
		  error = false;
		  continue;
		 }
		 
           if ((Math.max(mainTableWidth, x + 1) * Math.max(mainTableHeight, y + 1)) > TABLE_MAX_CELLS)
              {
	       error = true;
	       continue;
	      }
	      
           if (mainTable[y] === undefined) mainTable[y] = [];
           mainTable[y][x] = { 'oId': Number(oid), 'eId': Number(eid), 'data': '', 'style': cell['props']['style'] };
	   if (obj && typeof obj.value === 'string') mainTable[y][x]['data'] = obj.value;
	   if (obj && typeof obj.hint === 'string') mainTable[y][x]['hint'] = obj.hint;
	   if (obj && typeof obj.description === 'string') mainTable[y][x]['description'] = obj.description;
           if (cell['props']['collapse'] != undefined) mainTable[y][x]['collapse'] = '';

           mainTableWidth = Math.max(mainTableWidth, x + 1);
	   mainTableHeight = Math.max(mainTableHeight, y + 1);
	   cell['props']['x'] = x;
	   cell['props']['y'] = y;
	   if (cmd === 'OBTAINMAIN' && cell['props']['startevent'] && typeof cell['props']['startevent'] === 'string')
	   if (cell['props']['startevent'] === 'DBLCLICK') focusElement = { "x": x, "y": y, "cmd": 'DBLCLICK' };
	    else if (cell['props']['startevent'].substr(0, 8) === 'KEYPRESS' && cell['props']['startevent'].length > 8)
		    focusElement = { "x": x, "y": y, "cmd": 'KEYPRESS', "data": cell['props']['startevent'].substr(8) };
	  }
      n++;
     }
 if (!mainTableHeight)
    {
     if (n > 1) displayMainError("Specified view selection expression has some 'x','y'<br>incorrect coordinate definitions!<br><br>See element selection expression help section");
      else mainDiv.innerHTML = '<h1>Specified view has no objects defined!<br>Please add some objects</h1>';
     return;
    }
 if (error === true) alert('Some elements are out of range. Max table size allowed - ' + TABLE_MAX_CELLS + ' cells.'); // Set string 'warning' as box title
  else if (error === false) alert("Specified view selection expression has some 'x','y' incorrect coordinate definitions!\nSee element selection expression help section");
 
 // Remove previous view event listeners
 mainTableRemoveEventListeners();
    
 // Remove undefined (and 'collapse' property set) main table rows and columns
 collapseMainTable();
 
 // Drawing html table on main div and query table selector
 let rowHTML = '<table><tbody>';
 let undefinedCell = '<td></td>';
 let undefinedRow = '';
 
 // Create 'undefined' html td cell template
 if (objectTable[0] != undefined && objectTable[0][0] != undefined && objectTable[0][0]['style'])
    {
     if (undefinedcellRuleIndex != undefined) style.sheet.deleteRule(undefinedcellRuleIndex);
     undefinedcellRuleIndex = style.sheet.insertRule('.undefinedcell {' + objectTable[0][0]['style'] + '}');
     undefinedCell = '<td class="undefinedcell"></td>';
    }
    
 // Create 'undefined' html tr row
 for (x = 0; x < mainTableWidth; x++) undefinedRow += undefinedCell;
 // Create html table of mainTable array
 for (y = 0; y < mainTableHeight; y++)
     {
      rowHTML += '<tr>';
      if (mainTable[y] == undefined) rowHTML += undefinedRow;
       else for (x = 0; x < mainTableWidth; x++)
	 {
	  if (!(cell = mainTable[y][x])) rowHTML += undefinedCell;
	   else if (cell.style) rowHTML += '<td style="' + cell.style + '">' + toHTMLCharsConvert(cell.data) + '</td>';
	    else rowHTML += '<td>' + toHTMLCharsConvert(cell.data) + '</td>';
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
 if (focusElement.x != undefined)
    {
     CellBorderToggleSelect(null, mainTablediv.rows[focusElement.y].cells[focusElement.x]);
     focusElement.td.focus();
     cmd = focusElement.cmd;
     callController(focusElement.data);
    }
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
		      tooltipTimerId = setTimeout(() => ShowHint(mainTable[y][x].hint, getAbsoluteX(event.target, 'middle'), getAbsoluteY(event.target, 'end')), 1000);
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
	      if (mainTable[focusElement.y] && mainTable[focusElement.y][focusElement.x])
	      if (mainTable[focusElement.y][focusElement.x].oId >= STARTOBJECTID)
	    	 {
		  cmd = 'DBLCLICK';
		  callController();
		 }
	       else if (mainTable[focusElement.y][focusElement.x].oId === NEWOBJECTID)
		 {
	    	  focusElement.td.contentEditable = 'true';
		  focusElement.olddata = toHTMLCharsConvert(mainTable[focusElement.y][focusElement.x].data);
		  event.target.innerHTML = focusElement.olddata; // Fucking FF has bug inserting <br> to the empty content
	    	  focusElement.td.focus();
		  event.preventDefault();
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
		      saveDialogProfile(); // Save dialog box content and send it to the controller
		      cmd = 'CONFIRM';
		      callController(box);
		     }
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
		//--------------OD item mouse click? Wrap/unwrap OV list--------------
	      if (event.target.classList.contains('sidebar-od'))
		 {
		  if (Object.keys(sidebar[event.target.innerHTML]).length > 1)
		     {
		      sidebar[cmd = event.target.innerHTML][''] = !sidebar[cmd][''];
		      cmd = 'GETMENU';
		      callController();
		     }
		  break;
		}
		//--------------OV item mouse click? Open OV in main field--------------
	      if (event.target.classList.contains('sidebar-ov'))
		{
		 if (activeOD != event.target.nextSibling.innerHTML || activeOV != event.target.innerHTML)
		    {
		     activeOD = event.target.nextSibling.innerHTML;
		     activeOV = event.target.innerHTML;
		     drawSidebar(sidebar);
		    }
		 cmd = 'OBTAINMAIN';
		 callController();
		 break;
		}
		//--------------Mouse click on wrap icon? OD item sidebar line wraps/unwraps ov list, OV item sidebar line opens OV in main field--------------
	     if (event.target.classList.contains('wrap'))
		{
		 if (event.target.nextSibling.classList.contains('sidebar-od') && Object.keys(sidebar[event.target.nextSibling.innerHTML]).length > 1)
		    { 
		     sidebar[cmd = event.target.nextSibling.innerHTML][''] = !sidebar[cmd][''];
		     cmd = 'GETMENU';
		     callController();
		    }
		 if (event.target.nextSibling.classList.contains('sidebar-ov'))
		    {
		     if (activeOD != event.target.nextSibling.nextSibling.innerHTML || activeOV != event.target.nextSibling.innerHTML)
		        {
			 activeOD = event.target.nextSibling.nextSibling.innerHTML;
		         activeOV = event.target.nextSibling.innerHTML;
		         drawSidebar(sidebar);
			}
		     cmd = 'OBTAINMAIN';
		     callController();
		    }
		 break;
		}
	      //--------------Mouse click on main field table?--------------
	      if (event.target.tagName == 'TD') CellBorderToggleSelect(focusElement.td, event.target);
	      break;
	 case 'keydown':
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
			   if (mainTable[focusElement.y] && mainTable[focusElement.y][focusElement.x] && mainTable[focusElement.y][focusElement.x].oId >= STARTOBJECTID)
			   if (event.ctrlKey == false && event.altKey == false && event.metaKey == false)
		           if (rangeTest(event.keyCode, [113,113,123,123,45,46,65,90,48,57,96,107,109,111,186,192,219,222,32,32,59,59,61,61,173,173,226,226]))
			      {
			       cmd = 'KEYPRESS';
			       callController(event.key);
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
     alert('Browser report: undefined controller message!');
     loog('Browser report: undefined controller message!');
     return;
    }
 if (input.OV != undefined && input.OD != undefined && (input.OD != activeOD || input.OV != activeOV) && input.cmd != 'INFO') return;
 
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
		  // Fucking FF has bug inserting <br> in case of cursor at the end of content, so empty content automatically generates <br> tag, fuck!
		  if (input.data != undefined) focusElement.td.innerHTML = toHTMLCharsConvert(input.data);
		   else focusElement.td.innerHTML = focusElement.olddata;
		  if (focusElement.td.innerHTML.slice(-4) != '<br>') ContentEditableCursorSet(focusElement.td);
		  focusElement.td.focus();
		 }
	      break;
	 case 'SET':
	      let object;
	      for (let i in input.data)
		  if (objectTable[input.oId] && objectTable[input.oId][i] && (object = objectTable[input.oId][i]['props']))
		     {
		      let x = object['x'], y = object['y'];
		      if (input.data[i]['value'] != undefined) mainTablediv.rows[y].cells[x].innerHTML = toHTMLCharsConvert(input.data[i]['value']);
		       else mainTablediv.rows[y].cells[x].innerHTML = '';
		      mainTable[y][x].data = input.data[i]['value'];
		      mainTable[y][x].hint = input.data[i]['hint'];
		     }
	      if (input.alert) alert(input.alert);
	      break;
	 case 'REFRESH':
	      drawSidebar(input.data);
	      cmd = 'GETMAIN'
	      callController();
	      break;
	 case 'REFRESHMENU':
	      drawSidebar(input.data);
	      break;
	 case 'REFRESHMAIN':
	      objectTable = input.data;
	      drawMain();
	      break;
	 case 'INFO':
	      if (input.log) loog('Controller log message: ' + input.log);
	      if (input.alert)
	         {
		  loog('Controller alert message: ' + input.alert);
		  if (input.OV === undefined || input.OD === undefined || (input.OD === activeOD && input.OV === activeOV)) alert(input.alert);
		 }
	      if (input.error)
	         {
		  if (activeOD != '') loog('Controller error message: ' + input.error);
		  if (input.OV === undefined || input.OD === undefined || (input.OD === activeOD && input.OV === activeOV)) displayMainError(input.error);
		 }
	      break;
	 case '':
	      break;
	 default:
	      alert('Browser report: unknown controller message ' + input.cmd);
	      loog('Browser report: unknown controller message ' + input.cmd);
	}
}

function displayMainError(error)
{
 mainDiv.innerHTML = '<h1>' + error + '</h1>';
 activeOD = activeOV = '';
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
 string = string.replace(/\n/g, "<br>");
 return string.replace(/<br>$/g, "<br><br>");
}

function CellBorderToggleSelect(oldCell, newCell)
{
 if (oldCell)
    {
     oldCell.style.outline = "none";
     oldCell.style.boxShadow = "none";
    }
 if (uiProfile['main field table active cell']['outline'] != undefined) newCell.style.outline = uiProfile['main field table active cell']['outline'];
 if (uiProfile['main field table active cell']['shadow'] != undefined) newCell.style.boxShadow = uiProfile['main field table active cell']['shadow'];
 focusElement.td = newCell;
 focusElement.x = newCell.cellIndex;
 focusElement.y = newCell.parentNode.rowIndex;
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
 switch (cmd)
	{
	 case 'GETMENU':
	      object = { "cmd": cmd };
	      break;
	 case 'GETMAIN':
	 case 'OBTAINMAIN':
	      object = { "cmd": cmd };
	      break;
	 case 'Element description':
	      let msg = '';
	      if (typeof mainTable[focusElement.y][focusElement.x].description === 'string') msg = '\n\nElement description property:\n' + mainTable[focusElement.y][focusElement.x].description;
	      msg = `\n\nTable cell 'x' coordinate: ${focusElement.x}\nTable cell 'y' coordinate: ${focusElement.y}` + msg;
	      if (mainTable[focusElement.y][focusElement.x].oId === 1) msg = 'Table cell to input new ebject data for element id: ' + mainTable[focusElement.y][focusElement.x].eId + msg;
	       else if (mainTable[focusElement.y][focusElement.x].oId === 2) msg = 'Object title for element id: ' + mainTable[focusElement.y][focusElement.x].eId + msg;
	        else msg = 'Object id: ' + mainTable[focusElement.y][focusElement.x].oId + '\nElement id: ' + mainTable[focusElement.y][focusElement.x].eId + msg;
	      alert(msg);
	      break;
	 case 'New Object':
	      if (objectTable === undefined) break;
	      object = { "cmd": 'INIT', "data": {} };
	      if (objectTable[NEWOBJECTID] != undefined) for (let key in objectTable[NEWOBJECTID])
		 object['data'][key] = mainTable[objectTable[NEWOBJECTID][key]['props']['y']][objectTable[NEWOBJECTID][key]['props']['x']]['data'];
	      break;
	 case 'Delete Object':
	      if (mainTable[focusElement.y] && mainTable[focusElement.y][focusElement.x] && mainTable[focusElement.y][focusElement.x].oId >= STARTOBJECTID)
	         object = {"cmd": 'DELETEOBJECT', "oId": mainTable[focusElement.y][focusElement.x].oId };
	      break;
	 case 'New Object Database':
	 case 'Edit Database Structure':
	      object = { "cmd": cmd };
	      if (data != undefined) object.data = data;
	      break;
	 case 'CONFIRM':
	 case 'DBLCLICK':
	 case 'KEYPRESS':
	      object = { "cmd": cmd };
	      if (focusElement.td)
	         {
	          object["oId"] = mainTable[focusElement.y][focusElement.x].oId;
		  object["eId"] = mainTable[focusElement.y][focusElement.x].eId;
		 }
	      if (data != undefined) object.data = data;
	      break;
	 case 'GETUI':
	      let element, key, rule;
	      for (element in uiProfile)
	       if (uiProfile[element]["target"] != undefined)
		  {
		   rule = uiProfile[element]["target"] + " {";
		   for (key in uiProfile[element])
		       if (key != "target" && uiProfile[element][key] != "") rule += key + ": " + uiProfile[element][key];
		   style.sheet.insertRule(rule + "}"); //https://dev.to/karataev/set-css-styles-with-javascript-3nl5, https://professorweb.ru/my/javascript/js_theory/level2/2_4.php
		  }
	      break;
	 default:
	      alert("Undefined browser message: " + cmd + "!");
	      loog("Undefined browser message: " + cmd + "!");
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
 /*			  "_callback" - controller reserved property								*/
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
  if (box.dialog[box.flags.pad][box.flags.profile][name]["type"] != undefined)
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

function collapseMainTable() // Function removes collapse flag tagged rows and columns from main object table
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
	    for (col = 0; col < mainTableWidth; col++) if (mainTable[row][col] && mainTable[row][col].oid != TITLEOBJECTID)
		if (mainTable[row][col].collapse != undefined) collapse = true;
		 else { collapse = false; break; }
	   }
	 else if (objectTable[0] != undefined && objectTable[0][0] != undefined && objectTable[0][0]['collapse'] != undefined)
	   {
	    // Set collapse status to true if undefined row and collapse property for undefined cell (objectTable[0][0]['collaspe']) is true
	    collapse = true;
	   }
	   
	// Collapse main table row (remove it by splice), increase displacement and decrease main table height
	if (collapse === true)
	   {
	    mainTable.splice(row, 1);
	    disp++;
	    mainTableHeight--;
	   }
	 else // Otherwise (in case of no collpase) correct current row 'y' coordinate on displacement value and go to next row
	   {
	    if (disp > 0 && mainTable[row] != undefined) for (col = 0; col < mainTableWidth; col++)
	       if (mainTable[row][col] != undefined) objectTable[mainTable[row][col].oId][mainTable[row][col].eId].y -= disp;
	    row++;
	   }
       }
 
 // Second step - main table columns collpase status check
 col = disp = 0;
 while (col < mainTableWidth) // Parse main table columns one by one
       {
        // Set row default collapse status to false
	collapse = false;
	
	// If collapse property for undefined cell (objectTable[0][0]['collaspe']) is true, then check the whole column on undefined cells
	if (objectTable[0] != undefined && objectTable[0][0] != undefined && objectTable[0][0]['collapse'] != undefined)
	   {
	    collapse = true;
	    for (row = 0; row < mainTableHeight; row++)
		if (mainTable[row] != undefined && mainTable[row][col] != undefined) { collapse = false; break; }
	   }
	
	// Check the whole column (except undefined and titles) cell to be all collapsible
	if (collapse === false) for (row = 0; row < mainTableHeight; row++)
	if (mainTable[row] && mainTable[row][col] && mainTable[row][col].oid != TITLEOBJECTID)
	if (mainTable[row][col].collapse != undefined) collapse = true;
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
	       if (mainTable[row] != undefined && mainTable[row][col] != undefined) objectTable[mainTable[row][col].oId][mainTable[row][col].eId].x -= disp;
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
     data = event.target.nextSibling.innerHTML;
    }
 // Context event on OD item? Display OD context menu
  else if (event.target.classList.contains('sidebar-od'))
    { 
     innerHTML = sidebarODContext;
     data = event.target.innerHTML;
    }
 // Context event on OV item or wrap icon cell with OV item? Display OV context menu
  else if ((event.target.classList.contains('wrap') && event.target.nextSibling.classList.contains('sidebar-ov')) || event.target.classList.contains('sidebar-ov') || event.target.classList.contains('sidebar')) innerHTML = sidebarOVContext;
 // Application context menu on main field table? Display mainObjectContext or mainTitleObjectContext context menu
  else if (event.target.tagName == 'TD')
    {
     CellBorderToggleSelect(focusElement.td, event.target);
     if (!mainTable[focusElement.y] || !mainTable[focusElement.y][focusElement.x]) innerHTML = mainDefaultContext;
      else if (mainTable[focusElement.y][focusElement.x].oId >= STARTOBJECTID) innerHTML = mainObjectContext;
       else innerHTML = mainTitleObjectContext;
    }
 // Application context menu on main field empty area? Display mainDefaultContext context menu
  else if (event.target === mainDiv && activeOV != '') innerHTML = mainDefaultContext;
 // Context menu event has been generated by keyboard (event.which != 3) and any element is selected? Display mainObjectContext context menu
  else if (focusElement.td != undefined && event.which != 3) if (!mainTable[focusElement.y] || !mainTable[focusElement.y][focusElement.x]) innerHTML = mainDefaultContext;
  else if (mainTable[focusElement.y][focusElement.x].oId >= STARTOBJECTID) innerHTML = mainObjectContext;
  else innerHTML = mainTitleObjectContext;


 if (innerHTML != undefined)
    {
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
 return string.replace(/</g,"&lt;").replace(/"/g,"");
}
