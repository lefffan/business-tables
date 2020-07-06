/*------------------------------CONSTANTS------------------------------------*/
const TABLE_MAX_CELLS = 200000;
const NEWOBJECTID = 1;  
const TITLEOBJECTID = 2;
const STARTOBJECTID = 3;
const range = document.createRange();
const selection = window.getSelection();
const style = document.createElement('style');

const mainObjectContext = '<div class="contextMenuItems">New Object</div><div class="contextMenuItems">Delete Object</div><div class="contextMenuItems">Element description</div><div class="contextMenuItems">Help</div>';
const mainTitleObjectContext = '<div class="contextMenuItems">New Object</div><div class="contextMenuItems greyContextMenuItem">Delete Object</div><div class="contextMenuItems">Element description</div><div class="contextMenuItems">Help</div>';
const mainDefaultContext = '<div class="contextMenuItems">New Object</div><div class="contextMenuItems greyContextMenuItem">Delete Object</div><div class="contextMenuItems greyContextMenuItem">Element description</div><div class="contextMenuItems">Help</div>';
const sidebarOVContext = '<div class="contextMenuItems">New Object Database</div><div class="contextMenuItems greyContextMenuItem">Edit Database Properties</div>';
const sidebarODContext = '<div class="contextMenuItems">New Object Database</div><div class="contextMenuItems">Edit Database Structure</div>';
// User interface default profile
const uiProfile = {
		  // Body
		  "body": { "target": "body", "background-color": "#343E54;" },
		  //"wait cursor": { "target": ".waitcursor", "cursor": "wait" },
		  // Main field
		  "main field": { "target": ".main", "width": "76%;", "height": "90%;", "left": "18%;", "top": "5%;", "border-radius": "5px;", "background-color": "#EEE;", "scrollbar-color": "#CCCCCC #FFFFFF;", "box-shadow": "4px 4px 5px #111;" },
		  "main field table": { "target": "table", "margin": "0px;" },
		  "main field table cell": { "target": "td", "padding": "10px;", "border": "1px solid #999;", "white-space": "pre;", "text-overflow": "ellipsis;" },
		  "main field table active cell": { "outline": "red auto 1px", "shadow": "0 0 5px rgba(100,0,0,0.5)" },
		  "main field table cursor": { "target": ".main table", "cursor": "cell;" },
		  "main message": { "target": ".main h1", "color": "#BBBBBB;" },
		  // Scrollbar
		  "scrollbar": { "target": "::-webkit-scrollbar", "width": "8px;", "height": "8px;" },
		  // Context Menu
		  "context menu": { "target": ".contextMenu", "width": "240px;", "background-color": "#F3F3F3;", "color": "#1166aa;", "border": "solid 1px #dfdfdf;", "box-shadow": "1px 1px 2px #cfcfcf;", "font-family": "sans-serif;", "font-size": "16px;", "font-weight": "300", "line-height": "1.5;", "padding": "12px 0;" },
		  "context menu item": { "target": ".contextMenuItems", "margin-bottom": "4px;", "padding-left": "10px;" },
		  "context menu item cursor": { "target": ".contextMenuItems:hover:not(.greyContextMenuItem)", "cursor": "pointer;" },
		  "context menu item active": { "target": ".activeContextMenuItem", "color": "#fff;", "background-color": "#0066aa;" },
		  "context menu item grey": { "target": ".greyContextMenuItem", "color": "#dddddd;" },
		  // Sidebar
    		  "sidebar": { "target": ".menu", "background-color": "rgb(17,101,176);", "border-radius": "5px;", "color": "#9FBDDF;", "width": "13%;", "height": "90%;", "left": "4%;", "top": "5%;", "scrollbar-color": "#1E559D #266AC4;", "scrollbar-width": "thin;", "box-shadow": "4px 4px 5px #222;" },
		  "sidebar wrap icon": { "wrap": "&#9658;", "unwrap": "&#9660;" }, //{ "wrap": "+", "unwrap": "&#0150" }, "wrap": "&#9658;", "unwrap": "&#9660;"
		  "sidebar wrap cell": { "target": ".wrap", "font-size": "70%;", "padding": "3px 5px;" },
		  "sidebar item active": { "target": ".itemactive", "font-weight": "bolder;", "background-color": "#4578BF;", "color": "#FFFFFF;" },
		  "sidebar item hover": { "target": ".menu tr:hover", "background-color": "#4578BF;", "cursor": "pointer;" },
		  "sidebar object database": { "target": ".sidebar-od", "padding": "3px 5px 3px 0px;", "margin": "0px;", "color": "", "width": "100%;"  },
		  "sidebar object view": { "target": ".sidebar-ov", "padding": "2px 5px 2px 10px;", "margin": "0px;", "color": "" },		  
		  // Box types
		  "hint": { "target": ".hint", "background-color": "#CAE4B6;", "color": "#7E5A1E;", "border": "none;", "padding": "5px;" },
		  "alert": { "target": ".alert", "background-color": "rgb(115,163,181);", "color": "#000000;", "border-radius": "5px;", "border": "none;", "min-width": "20%;" },
		  "confirm": { "target": ".confirm", "background-color": "#FFF;", "color": "#000;", "border-radius": "5px;", "border": "1px solid #DDD;", "min-width": "20%;", "max-height": "100%;", "scrollbar-width": "thin;", "box-shadow": "3px 3px 5px 0px #DDD;" },
		  "dialog": { "target": ".dialog", "background-color": "#17262B;", "color": "#000;", "border-radius": "5px;", "border": "none;", "min-width": "20%;", "max-height": "100%;", "scrollbar-width": "thin;", "box-shadow": "none;" },
		  // Box interface elements
/*#404851;*/	  "box title": { "target": ".title", "background-color": "transparent;", "color": "#AAA;", "border": "#000000;", "border-radius": "5px 5px 0 0;", "font": ".9em Lato, Helvetica;", "padding": "5px;" },
		  "box pad": { "target": ".pad", "background-color": "#404851;", "border-left": "none;", "border-right": "none;", "border-top": "none;", "border-bottom": "none;", "padding": "5px;", "margin": "0;", "font": ".9em Lato, Helvetica;", "color": "#aaa;", "border-radius": "5px 5px 0 0;" },
		  "box active pad": { "target": ".activepad", "background-color": "#17262B;", "border-left": "none;", "border-right": "none;", "border-top": "none;", "border-bottom": "none;", "padding": "5px;", "margin": "0;", "font": ".9em Lato, Helvetica;", "color": "#aaa;", "border-radius": "5px 5px 0 0;" },
		  "box pad bar": { "target": ".padbar", "background-color": "transparent;", "border": "none;", "padding": "4px;", "margin": "10px 0 15px 0;" },
		  "box divider": { "target": ".divider", "background-color": "transparent;", "margin": "5px 10px 5px 10px;", "height": "0px;", "border-bottom": "1px solid #4F4F4F;", "border-top-color": "transparent;", "border-left-color": "transparent;" , "border-right-color": "transparent;" },
		  "box ok": { "target": ".ok", "background-color": "#13BB72;", "border": "none;", "padding": "10px;", "margin": "10px;", "border-radius": "5px;", "font": "bold 12px Lato, Helvetica;", "color": "white;" },
		  "box ok hover": { "target": ".ok:hover", "cursor": "pointer;", "background": "", "color": "" },
		  "box cancel": { "target": ".cancel", "background-color": "#FF3516;", "border": "none;", "padding": "10px;", "margin": "10px;", "border-radius": "5px;", "font": "bold 12px Lato, Helvetica;", "color": "white;" },
		  "box cancel hover": { "target": ".cancel:hover", "cursor": "pointer;", "background": "", "color": "" },
		  "box element headers": { "target": ".element-headers", "margin": "5px;", "font": ".9em Lato, Helvetica;", "color": "#9A7900;", "text-shadow": "none;" },
		  "box help icon": { "target": ".help-icon", "padding": "1px;", "font": ".9em Lato, Helvetica;", "color": "black;", "background": "#BB0;", "border-radius": "40%;" },
		  "box help icon hover": { "target": ".help-icon:hover", "padding": "1px;", "font": "1em Lato, Helvetica;", "color": "black;", "background": "#880;", "cursor": "pointer;", "border-radius": "40%;" },
		  //
		  "box select": { "target": ".select", "background-color": "#17262B;", "color": "#AAA;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 5px 10px;", "outline": "none;", "border": "1px solid #777;", "padding": "0px 0px 0px 0px;", "overflow": "auto;", "max-height": "10em;", "scrollbar-width": "thin;", "min-width": "10em;", "width": "auto;", "display": "inline-block;" },
		  "box select option": { "target": ".select > div", "padding": "2px 20px 2px 5px;", "margin": "0px;" },
		  "box select option hover": { "target": ".select:not([type*='o']) > div:hover", "background-color": "#404851;", "color": "" },
		  "box select option selected": { "target": ".selected", "background-color": "#404851;", "color": "#fff;" },
		  "box select option expanded": { "target": ".expanded", "margin": "0px !important;", "position": "absolute;" },
		  //
		  "box radio": { "target": "input[type=radio]", "background": "transparent;", "border": "1px solid #777;", "font": ".8em/1 sans-serif;", "margin": "3px 5px 3px 10px;", "border-radius": "20%;", "width": "1.2em;", "height": "1.2em;" },
		  "box radio checked" : { "target": "input[type=radio]:checked::after", "content": "", "color": "white;" },
		  "box radio checked background" : { "target": "input[type=radio]:checked", "background": "#00a0df;", "border": "1px solid #00a0df;" },
		  "box radio label" : { "target": "input[type=radio] + label", "color": "#AAA;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 0px 0px;" },
		  //
		  "box checkbox": { "target": "input[type=checkbox]", "background": "transparent;", "border": "1px solid #777;", "font": ".8em/1 sans-serif;", "margin": "3px 5px 3px 10px;", "border-radius": "50%;", "width": "1.2em;", "height": "1.2em;" },
		  "box checkbox checked" : { "target": "input[type=checkbox]:checked::after", "content": "", "color": "white;" },
		  "box checkbox checked background" : { "target": "input[type=checkbox]:checked", "background": "#00609f;", "border": "1px solid #00609f;" },
		  "box checkbox label" : { "target": "input[type=checkbox] + label", "color": "#CCC;", "font": ".8em Lato, Helvetica;", "margin": "0px 10px 0px 0px;" },
		  //
		  "box input text": { "target": "input[type=text]", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background": "transparent;", "border": "1px solid #777;", "outline": "none;", "color": "#AAA;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  "box input password": { "target": "input[type=password]", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background": "transparent;", "border": "1px solid #777;", "outline": "", "color": "#AAA;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  "box input textarea": { "target": "textarea", "margin": "0px 10px 5px 10px;", "padding": "2px 5px;", "background": "transparent;", "border": "1px solid #777;", "outline": "", "color": "#AAA;", "border-radius": "5%;", "font": ".9em Lato, Helvetica;", "width": "300px;" },
		  // Box animation
		  "box effect": { "hint": "grow", "alert": "fall", "confirm": "slideleft", "dialog": "rise", "context": "rise", "select": "rise", "dialog filter": "grayscale(0.5)", "confirm filter": "blur(3px)", "alert filter": "blur(3px)", "Ok button default text": "OK",  "Cancel button default text": "CANCEL" },
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

/*------------------------------VARIABLES------------------------------------*/
let tooltipTimerId, undefinedcellRuleIndex;
let mainDiv, menuDiv, mainTablediv, contextMenuDiv;
let hintDiv, alertDiv, confirmDiv, dialogDiv, expandedDiv;
let mainTable, mainTableWidth, mainTableHeight, objectTable;
let cmd = activeOD = activeOV = '';
let sidebar = focusElement = {};
let selectExpandedDiv = contextMenu = boxContent = null, modalVisible = "";
let tdhintDiv = [];
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
			     else mainDiv.innerHTML = '<h1>Request failed with response ' + response.status + ': ' + response.statusText + '</h1>'; })
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
 document.body.innerHTML = '<div class="menu"></div><div class="main"></div><div class="contextMenu ' + uiProfile["box effect"]["context"] + 'hide"></div><div class="box hint ' + uiProfile["box effect"]["hint"] + 'hide"></div><div class="box alert ' + uiProfile["box effect"]["alert"] + 'hide"></div><div class="box confirm ' + uiProfile["box effect"]["confirm"] + 'hide"></div><div class="box dialog ' + uiProfile["box effect"]["dialog"] + 'hide"></div><div class="select expanded ' + uiProfile["box effect"]["select"] + 'hide"></div>';
 document.addEventListener('keydown', eventHandler);
 document.addEventListener('mousedown', eventHandler);
 document.addEventListener('contextmenu', eventHandler);
 
 // Define sidebar div
 menuDiv = document.querySelector('.menu');

 // Define main field div and add 'scroll' event for it
 mainDiv = document.querySelector('.main');
 mainDiv.addEventListener('scroll', eventHandler);
 
 // Define context menu div and add some mouse events for it
 contextMenuDiv = document.querySelector('.contextMenu');
 contextMenuDiv.addEventListener('mouseover', eventHandler);
 contextMenuDiv.addEventListener('mouseout', eventHandler);

 // Define interface divs 
 hintDiv = document.querySelector('.hint');
 alertDiv = document.querySelector('.alert');
 confirmDiv = document.querySelector('.confirm');
 dialogDiv = document.querySelector('.dialog');
 expandedDiv = document.querySelector('.expanded');
 
 cmd = 'GETMENU';
 callController();
 cmd = 'GETMAIN';
 callController();
}

function drawMenu(data)
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
 sidebarHTML != '' ? menuDiv.innerHTML = '<table style="margin: 0px;"><tbody>' + sidebarHTML + '</tbody></table>' : menuDiv.innerHTML = '';
  
 // Reset sidebar with new data
 sidebar = data;
}	 

function drawMain()
{
 let oid, eid, obj, cell;
 let x, y, error, n = 1, q = 55;
 let reg = new RegExp('^\\*|^\\/|\\*$|\\/$|\\+$|-$|[nq]\\d|\\d[nq]|\\*\\*|\\*\\/|\\*\\+|\\*-|\\/\\*|\\/\\/|\\/\\+|\\/-|\\+\\*|\\+\\/|\\+\\+|\\+-|-\\*|-\\/|-\\+|--');
 mainTableWidth = mainTableHeight = 0;
 mainTable = [];
 focusElement = {};
  
 // Remove previous view event listeners
 mainTableRemoveEventListeners();

 // Fill mainTable tw dimension array with next format - mainTable[y][x]: { oid, eid, data, style, collapse}
 // Format of objectTable[oid][eid]: ['json': 'any element json data', 'props': 'oid, eid, eval(x), eval(y), style, collapse, startevent']
 for (oid in objectTable) if (oid != 0) // Iterate object identificators from objectTable
     {
      for (eid in objectTable[oid]) if (eid != 0) // Iterate element identificators from current object
          {
           cell = objectTable[oid][eid];
           try   { obj = JSON.parse(cell['json']); }
           catch { continue; }
    	   if (reg.test(cell['props']['x']) != false || reg.test(cell['props']['y']) != false)
              {
	       error = false;
	       continue;
	      }
           
           x = Math.trunc(eval(cell['props']['x']));
           y = Math.trunc(eval(cell['props']['y']));
           if ((Math.max(mainTableWidth, x + 1) * Math.max(mainTableHeight, y + 1)) > TABLE_MAX_CELLS)
              {
	       error = true;
	       continue;
	      }
	      
           if (mainTable[y] == undefined) mainTable[y] = [];
           mainTable[y][x] = { 'oId': Number(oid), 'eId': Number(eid), 'data': '', 'style': cell['props']['style'] };
	   if (obj && obj.value != undefined && obj.value != null) mainTable[y][x]['data'] = obj.value;
           if (cell['props']['collapse'] != undefined) mainTable[y][x]['collapse'] = '';

           mainTableWidth = Math.max(mainTableWidth, x + 1);
	   mainTableHeight = Math.max(mainTableHeight, y + 1);
	   cell['props']['x'] = x;
	   cell['props']['y'] = y;
	  }
      n++;
     }
 if (!mainTableHeight)
    {
     mainDiv.innerHTML = "<h1>Specified view has some x,y definition errors!<br>See element selection expression</h1>";
     return;
    }
 if (error === true) alert('Some elements are out of range. Max table size allowed - ' + TABLE_MAX_CELLS + ' cells.'); // Set string 'warning' as box title
  else if (error === false) alert('Specified view has some x,y definition errors! See element selection expression'); // Set string 'warning' as box title
 
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

 // Add current view event listeners and set default cursor  
 mainTableAddEventListeners();
}

function eventHandler(event)
{
 switch (event.type)
	{
	 case 'mouseenter':
	      if (boxContent == null) tooltipTimerId = setTimeout(() => { createBox({ "hint": mainTable[event.target.parentNode.rowIndex][event.target.cellIndex].hint }, getAbsoluteX(event.target, 'middle'), getAbsoluteY(event.target, 'end')); }, 1000);
	      break;
	 case 'mouseleave':
	      if (boxContent == null)
	         {
	          clearTimeout(tooltipTimerId);
	          hintDiv.className = 'box hint ' + uiProfile["box effect"]["hint"] + 'hide';
	         }
	      break;
	 case 'mousemove':
	      if (boxContent == null)
	         {
		  clearTimeout(tooltipTimerId);
		  tooltipTimerId = setTimeout(() => { createBox({ "hint": mainTable[event.target.parentNode.rowIndex][event.target.cellIndex].hint }, getAbsoluteX(event.target, 'middle'), getAbsoluteY(event.target, 'end')); }, 1000);
		 }
	      break;
	 case 'mouseover':
	      // Mouse over non grey context menu item? Set current menu item to call appropriate menu action by 'enter' key
	      if (event.target.classList.contains('contextMenuItems') && !event.target.classList.contains('greyContextMenuItem'))
	         setContextMenuItem(event.target);
	      break;
	 case 'mouseout':
	      setContextMenuItem(null);
	      break;
	 case 'scroll':
	      rmContextMenu();
	      break;
	 case 'contextmenu':
	      //--------------Prevent default context menu (do nothing) on context menu or modal window click--------------
	      if (event.target == contextMenuDiv || event.target.classList.contains('contextMenuItems') || (modalVisible != "" && modalVisible != "hint"))
	         {
		  event.preventDefault();
		  break;
		 }
	      //--------------Is any element content editable? Apply changes in case of no event.target match--------------
	      if (focusElement.td && focusElement.td.contentEditable === 'true')
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
		      if (event.target.tagName == 'TD' && !event.target.classList.contains('wrap') && !event.target.classList.contains('sidebar-od') && !event.target.classList.contains('sidebar-ov')) // Main field table cell click?
		         {
			  cellBorderToggleSelect(focusElement.td, event.target);       
			  focusElement.td = event.target;
	    		  focusElement.x = event.target.cellIndex;
			  focusElement.y = event.target.parentNode.rowIndex;
			 }
		     }
		  break;
		 }
	      //--------------Context menu event has been generated by keyboard (event.which != 3) and any element is selected? Display main context menu for tha element--------------
	      if (event.target.tagName == 'HTML' && focusElement.td != undefined && event.which != 3)
		 {
		  event.preventDefault();
		  if (contextMenu != null) break;
		  contextMenu = { 'x': focusElement.x, 'y': focusElement.y, 'e': null };
		  contextMenuDiv.innerHTML = mainObjectContext; 
		  contextMenuDiv.className = 'contextMenu ' + uiProfile["box effect"]["context"] + 'show';
		  // Computing context menu position, trying left-upper, middle-upper, right-upper etc.. side of <td> cell. In case of fail position is right-lower
		  const foc = focusElement.td;
		  if (!contextFitMainDiv(foc.offsetLeft - mainDiv.scrollLeft + foc.offsetWidth, foc.offsetTop - mainDiv.scrollTop + foc.offsetHeight) &&
		      !contextFitMainDiv(foc.offsetLeft - mainDiv.scrollLeft - contextMenuDiv.offsetWidth, foc.offsetTop - mainDiv.scrollTop + foc.offsetHeight) &&
		      !contextFitMainDiv(foc.offsetLeft - mainDiv.scrollLeft - contextMenuDiv.offsetWidth, foc.offsetTop - mainDiv.scrollTop - contextMenuDiv.offsetHeight) &&
		      !contextFitMainDiv(foc.offsetLeft - mainDiv.scrollLeft + foc.offsetWidth, foc.offsetTop - mainDiv.scrollTop - contextMenuDiv.offsetHeight) &&
		      !contextFitMainDiv(foc.offsetLeft - mainDiv.scrollLeft + foc.offsetWidth - contextMenuDiv.offsetWidth, foc.offsetTop - mainDiv.scrollTop + foc.offsetHeight) &&
		      !contextFitMainDiv(foc.offsetLeft - mainDiv.scrollLeft, foc.offsetTop - mainDiv.scrollTop + foc.offsetHeight) &&
		      !contextFitMainDiv(foc.offsetLeft - mainDiv.scrollLeft - contextMenuDiv.offsetWidth, foc.offsetTop - mainDiv.scrollTop) &&
		      !contextFitMainDiv(foc.offsetLeft - mainDiv.scrollLeft, foc.offsetTop - mainDiv.scrollTop - contextMenuDiv.offsetHeight) &&
		      !contextFitMainDiv(foc.offsetLeft - mainDiv.scrollLeft + foc.offsetWidth, foc.offsetTop - mainDiv.scrollTop))
		     {
		      contextMenuDiv.style.left = (mainDiv.offsetLeft + mainDiv.offsetWidth - contextMenuDiv.offsetWidth) + "px";
		      contextMenuDiv.style.top = (mainDiv.offsetTop + mainDiv.offsetHeight - contextMenuDiv.offsetHeight) + "px";
		     }
		  break;
		 }
		 //--------------Application context menu (main field or sidebar) event? Display appropriate context menu--------------
		 if (event.target.tagName == 'TD' || event.target.classList.contains('menu') || (event.target === mainDiv && activeOV != ''))
		 {
		  event.preventDefault();
		  // Context event on wrap icon cell with OD item? Display OD context
		  if (event.target.classList.contains('wrap') && event.target.nextSibling.classList.contains('sidebar-od'))
		  	 { 
			  contextMenuDiv.innerHTML = sidebarODContext;
			  contextMenu = { 'e': null, 'data': event.target.nextSibling.innerHTML };
			 }
		  // Context event on OD item? Display OD context
		  else if (event.target.classList.contains('sidebar-od'))
			 { 
			  contextMenuDiv.innerHTML = sidebarODContext;
			  contextMenu = { 'e': null, 'data': event.target.innerHTML };
			 }
		  // Context event on OV item or wrap icon cell with OV item? Display OV context
	          else if ((event.target.classList.contains('wrap') && event.target.nextSibling.classList.contains('sidebar-ov')) || event.target.classList.contains('sidebar-ov') || event.target.classList.contains('menu'))
		   	  {
			   contextMenuDiv.innerHTML = sidebarOVContext;
			   contextMenu = { 'e': null };
 			  }
		  else
		   {
		    if (event.target.tagName == 'TD' && mainTable[event.target.parentNode.rowIndex] && mainTable[event.target.parentNode.rowIndex][event.target.cellIndex] && mainTable[event.target.parentNode.rowIndex][event.target.cellIndex].oId > 0)
		       if (mainTable[event.target.parentNode.rowIndex][event.target.cellIndex].oId < STARTOBJECTID) contextMenuDiv.innerHTML = mainTitleObjectContext;
			else contextMenuDiv.innerHTML = mainObjectContext;
			 else contextMenuDiv.innerHTML = mainDefaultContext;
		    if (event.target.tagName == 'TD')
		       {
			cellBorderToggleSelect(focusElement.td, event.target);
			focusElement.td = event.target;
			focusElement.x = event.target.cellIndex;
			focusElement.y = event.target.parentNode.rowIndex;
		       }
		    contextMenu = { 'e': null };
		   }
		  // Show context menu
		  contextMenuDiv.className = 'contextMenu ' + uiProfile["box effect"]["context"] + 'show';
		  // Computing context menu position
		  if (mainDiv.offsetWidth + mainDiv.offsetLeft > contextMenuDiv.offsetWidth + event.clientX) contextMenuDiv.style.left = event.clientX + "px";
		   else contextMenuDiv.style.left = event.clientX - contextMenuDiv.clientWidth + "px";
		  if (mainDiv.offsetHeight + mainDiv.offsetTop > contextMenuDiv.offsetHeight + event.clientY) contextMenuDiv.style.top = event.clientY + "px";
		   else contextMenuDiv.style.top = event.clientY - contextMenuDiv.clientHeight + "px";
		  break;
		 }
	      break;
	 case 'dblclick':
	      if (modalVisible === "" ||  modalVisible === "hint")
	      if (event.target.contentEditable != 'true')
		 {
		  focusElement.x = event.target.cellIndex;
		  focusElement.y = event.target.parentNode.rowIndex;
		  focusElement.td = event.target;
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
		 }
	      break;
	 case 'mousedown':
	      if (modalVisible === 'help')
	         {
		  hintDiv.className = 'box hint ' + uiProfile["box effect"]["hint"] + 'hide';
		  modalVisible = 'dialog';
		  break;
		 }
	      //--------------Dialog 'hint icon' event? Display element hint--------------
	      if (event.target.classList.contains('help-icon'))
	         {
		  createBox({ "hint": boxContent.dialog[boxContent.flags.pad][boxContent.flags.profile][event.target.attributes.name.value]["help"] }, event.target.offsetLeft - event.target.scrollLeft + dialogDiv.offsetLeft - dialogDiv.scrollLeft + event.target.offsetWidth, event.target.offsetTop - event.target.scrollTop + dialogDiv.offsetTop - dialogDiv.scrollTop + event.target.offsetHeight);
		  modalVisible = 'help';
		  break;
		 }
	      //--------------Dialog 'cancel' button event? Only remove dialog or confirm box--------------
	      if (event.target.classList.contains('cancel'))
	         {
		  cmd = '';
		  rmBox();
		  break;
		 }
	      //--------------Dialog 'ok' button event? Only remove dialog--------------
	      if (event.target.classList.contains('ok'))
	         {
		  if (modalVisible === "dialog") saveDialogProfile(); // Get content data for dialog box
		  cmd === 'New Object Database' ? cmd = 'NEWOD' : cmd === 'Edit Database Structure' ? cmd = 'EDITOD' : cmd = 'CONFIRM';
		  callController(boxContent);
		  rmBox();
		  break;
		 }
	      //--------------Dialog expanded div mousedown event?--------------
	      if (event.target.parentNode.classList && event.target.parentNode.classList.contains('expanded'))
	         {
		  if (selectExpandedDiv.firstChild.attributes.value.value != event.target.attributes.value.value) // Selected option differs from the current?
		  if (selectExpandedDiv.attributes.type.value === 'select-profile')	// Select element is a profile select?
		     {
		      saveDialogProfile();
		      boxContent.flags.profile = event.target.innerHTML;		// Set event.target.innerHTML as a current profile
		      createBox(boxContent);						// Redraw dialog
		     }
		   else // Select element is usual option select?
		     {
		      // Set selected option as a current
		      selectExpandedDiv.innerHTML = '<div value="' + event.target.attributes.value.value + '">' + event.target.innerHTML + '</div>';
		      boxContent.dialog[boxContent.flags.pad][boxContent.flags.profile][selectExpandedDiv.attributes.name.value]["data"] = setOptionSelected(boxContent.dialog[boxContent.flags.pad][boxContent.flags.profile][selectExpandedDiv.attributes.name.value]["data"], event.target.attributes.value.value);
		     }
		  // Hide expanded div and break;
		  expandedDiv.className = 'select expanded ' + uiProfile["box effect"]["select"] + 'hide';
		  break;
		 }
	      //--------------Dialog select mouse down event?--------------
	      if (event.target.parentNode.classList && event.target.parentNode.classList.contains('select') && (event.target.parentNode.attributes.name === undefined || boxContent.dialog[boxContent.flags.pad][boxContent.flags.profile][event.target.parentNode.attributes.name.value]['readonly'] === undefined))
	         {
		  switch (event.target.parentNode.attributes.type.value)
			 {
			  case 'select-profile':
			  case 'select-one':
			       if ((/hide$/).test(expandedDiv.classList[2]) === false) // Expanded div visible? Hide it.
				  {
				   expandedDiv.className = 'select expanded ' + uiProfile["box effect"]["select"] + 'hide';
				   break;
				  }
			       let data, inner = '', count = 0;
			       selectExpandedDiv = event.target.parentNode; // Set current select div that expanded div belongs to
			       if (selectExpandedDiv.attributes.type.value === 'select-one') // Define expandedDiv innerHTML for usual select, otherwise for profile select
				  {
			    	   if (typeof (data = boxContent.dialog[boxContent.flags.pad][boxContent.flags.profile][selectExpandedDiv.attributes.name.value]["data"]) === 'string')
				   for (data of data.split('|')) // Split data by '|'
			    	   //if (data.length > 0 && (data[0] != '+' || data.length > 1)) // Check non empty options
				   if (data[0] == '+') inner += '<div class="selected" value="' + (count++) + '">' + data.substr(1) + '</div>'; // Current option
				    else inner += '<div value="' + (count++) + '">' + data + '</div>'; // Other options
				  }
				else
				  {
				   for (data in boxContent.dialog[boxContent.flags.pad]) if (typeof boxContent.dialog[boxContent.flags.pad][data] === "object")
				   if (data === boxContent.flags.profile) inner += '<div class="selected" value="' + (count++) + '">' + data + '</div>'; // Current option
				    else inner += '<div value="' + (count++) + '">' + data + '</div>'; // Other options
				  }
			       expandedDiv.innerHTML  = inner; // Fill expandedDiv with innerHTML
			       expandedDiv.style.top  = selectExpandedDiv.offsetTop + dialogDiv.offsetTop + selectExpandedDiv.offsetHeight + 'px'; // Place expandedDiv top position
			       expandedDiv.style.left = selectExpandedDiv.offsetLeft + dialogDiv.offsetLeft + 'px'; // Place expandedDiv left position
			       expandedDiv.className  = 'select expanded ' + uiProfile["box effect"]["select"] + 'show'; // Show expandedDiv
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
		  expandedDiv.className = 'select expanded ' + uiProfile["box effect"]["select"] + 'hide';
		  break;
		 }
	      //--------------Non active pad is selected?--------------
	      if (event.target.classList.contains('pad'))
		 {
		  saveDialogProfile();
		  boxContent.flags.pad = event.target.innerHTML; // Set event.target.innerHTML as a current pad
		  createBox(boxContent);			 // Redraw dialog
		  break;
		 }
		 if (modalVisible != '') break;
	      //--------------Mouse middle button (1-left, 2- middle, 3-right button) click? Break anyway. Also remove context menu in case of non context menu area click--------------      
	      if (event.which != 1)
	         {
		  if (event.target != contextMenuDiv && !event.target.classList.contains('contextMenuItems')) rmContextMenu();
		  break;
		 }
	      //--------------Mouse click on grey menu item or mouse click on context but not menu item? Do nohing and break;--------------
	      if (event.target.classList.contains('greyContextMenuItem') || event.target.classList.contains('contextMenu'))
	         {
		  break;
		 }
	      //--------------Mouse click on context menu item? Call controller with appropriate context menu as a command--------------
	      if (event.target.classList.contains('contextMenuItems'))
		 {
		  cmd = event.target.innerHTML;
		  callController(contextMenu.data);
		  rmContextMenu();
		  break;
		 }
		//--------------OD item mouse click? Wrap/unwrap OV list--------------
	      if (event.target.classList.contains('sidebar-od'))
		 {
		  rmContextMenu();
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
		 rmContextMenu();
		 if (activeOD != event.target.nextSibling.innerHTML || activeOV != event.target.innerHTML)
		    {
		     activeOD = event.target.nextSibling.innerHTML;
		     activeOV = event.target.innerHTML;
		     drawMenu(sidebar);
		    }
		 cmd = 'GETMAIN';
		 callController();
		 break;
		}
		//--------------Mouse click on wrap icon? OD item sidebar line wraps/unwraps ov list, OV item sidebar line opens OV in main field--------------
	     if (event.target.classList.contains('wrap'))
		{
		 rmContextMenu();
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
		         drawMenu(sidebar);
			}
		     cmd = 'GETMAIN';
		     callController();
		    }
		 break;
		}
	      //--------------Mouse clilck out of main field content editable table cell? Save cell inner html as a new element, otherwise send it to the controller--------------
	     if (focusElement && focusElement.td && focusElement.td != event.target && focusElement.td.contentEditable === 'true')
	     if (mainTable[focusElement.y][focusElement.x].oId === NEWOBJECTID)
		 {
		  focusElement.td.contentEditable = 'false';
		  mainTable[focusElement.y][focusElement.x].data = htmlCharsConvert(focusElement.td.innerHTML);
		 }
	      else
		 {
		  focusElement.td.contentEditable = 'false';
		  cmd = 'CONFIRM';
		  callController(htmlCharsConvert(focusElement.td.innerHTML));
		 }
	      //--------------Mouse click on main field table?--------------
	      if (event.target.tagName == 'TD')
	         {
		  cellBorderToggleSelect(focusElement.td, event.target);
		  focusElement.td = event.target;
	          focusElement.x = event.target.cellIndex;
	          focusElement.y = event.target.parentNode.rowIndex;
		 }
	     //--------------Remove context menu for no sidebar and main field events--------------
		 rmContextMenu();
		 break;
	 case 'keydown':
	      //if (event.which == 45) createBox({"title":"Alert", "confirm": "The Object Database cannot be deleted!", "flags": {"ok": "&nbsp&nbsp&nbsp&nbspOK&nbsp&nbsp&nbsp&nbsp"}});
	      if (modalVisible === 'help')
	         {
		  hintDiv.className = 'box hint ' + uiProfile["box effect"]["hint"] + 'hide';
		  modalVisible = 'dialog';
		  break;
		 }
	      if (modalVisible != "" && event.which != 27) break;
	      if (focusElement.td != undefined && focusElement.td.contentEditable === 'true' && event.which != 27 && event.which != 13) break;
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
			   setContextMenuItem("UP");
			   moveCursor(0, -1, false);
			   break;
		      case 40: //Down
			   setContextMenuItem("DOWN");
			   moveCursor(0, 1, false);
			   break;
		      case 13: //Enter
		           if (!contextMenu) // If context menu is not active,  try to move cursor down
			      {
			       if (focusElement.td != undefined && focusElement.td.contentEditable === 'true')
			          {
				   event.preventDefault();
				   document.execCommand('insertLineBreak', false, null); // "('insertHTML', false, '<br>')" doesn't work in FF
				  }
			       else moveCursor(0, 1, false);
			      }
			    else if (contextMenu.e) // If context menu item is active
			      {
			       cmd = contextMenu.e.innerHTML;
			       callController(contextMenu.data);
			       rmContextMenu();
			      }
			   break;
		      case 37: //Left
		           moveCursor(-1, 0, false);
			   break;
		      case 39: //Right
		           moveCursor(1, 0, false);
			   break;
		      case 27: //Esc
		           if (modalVisible != "" && boxContent.flags.esc != undefined) // Any modal with esc flag set?
			   if ((/show$/).test(expandedDiv.classList[2]) === true) // Expanded div visible? Hide it, otherwise hide modal
			      {
			       expandedDiv.className = 'select expanded ' + uiProfile["box effect"]["select"] + 'hide';
			       break;
			      }
			    else
			      {
			       cmd = '';
			       rmBox();
			       break;
			      }
			   if (focusElement.td != undefined && focusElement.td.contentEditable === 'true')
			      {
			       cmd = '';
			       focusElement.td.contentEditable = 'false';
			       focusElement.td.innerHTML = focusElement.olddata;
			      }
			    else
			      {
			       rmContextMenu();
			      }
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

function commandHandler(input)
{
 if (input.cmd === undefined)
    {
     alert('Unknown controller message!');
     return;
    }
    
 switch (input.cmd)
	{
	 case 'DIALOG':
	      createBox(boxContent = input.data);
	      break;
	 case 'EDIT':
	      if (focusElement && mainTable[focusElement.y] && mainTable[focusElement.y][focusElement.x])
	      if (mainTable[focusElement.y][focusElement.x].oId === input.oId && mainTable[focusElement.y][focusElement.x].eId === input.eId)
	         {
	          focusElement.td.contentEditable = 'true';
		  loog(focusElement.olddata = toHTMLCharsConvert(mainTable[focusElement.y][focusElement.x].data));
		  if (input.data != undefined) focusElement.td.innerHTML = toHTMLCharsConvert(input.data);
		   else focusElement.td.innerHTML = focusElement.olddata; // Fucking FF has bug inserting <br> to the empty content
		  focusElement.td.focus();
		  /* Set cursor at the end of the text. Decided not to use it cause fuckin FF inserting new line in that case.
		  range.selectNodeContents(focusElement.td);
		  range.collapse(false);
		  selection.removeAllRanges();
		  selection.addRange(range);*/
		 }
	      break;
	 case 'SET':
	      let object;
	      for (let i in input.data)
		  if (object = objectTable[input.oId][i]['props'])
		     {
		      let x = object['x'], y = object['y'];
		      mainTablediv.rows[y].cells[x].innerHTML = toHTMLCharsConvert(input.data[i]['value']);
		      mainTable[y][x].data = input.data[i]['value'];
		     }
	      if (input.alert) alert(input.alert);
	      break;
	 case 'REFRESH':
	      drawMenu(input.data);
	      cmd = 'GETMAIN'
	      callController();
	      break;
	 case 'REFRESHMENU':
	      drawMenu(input.data);
	      break;
	 case 'REFRESHMAIN':
	      objectTable = input.data;
	      drawMain();
	      break;
	 case 'INFO':
	      if (input.log) loog('Log controller message: ' + input.log);
	      if (input.alert) alert(input.alert);
	      if (input.error)
	         {
		  mainDiv.innerHTML = '<h1>' + input.error + '</h1>';
		  activeOD = activeOV = '';
		 }
	      break;
	 case '':
	      break;
	 default:
	      alert('Browser report: unknown controller message ' + input.cmd);
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

function cellBorderToggleSelect(oldCell, newCell)
{
 if (oldCell)
    {
     oldCell.style.outline = "none";
     oldCell.style.boxShadow = "none";
    }
 if (uiProfile['main field table active cell']['outline'] != undefined) newCell.style.outline = uiProfile['main field table active cell']['outline'];
 if (uiProfile['main field table active cell']['shadow'] != undefined) newCell.style.boxShadow = uiProfile['main field table active cell']['shadow'];
}

function contextFitMainDiv(x, y)
{
 if (mainDiv.offsetWidth < x + contextMenuDiv.offsetWidth || mainDiv.offsetHeight < y + contextMenuDiv.offsetHeight || x < 0 || y < 0) return false;
 contextMenuDiv.style.left = mainDiv.offsetLeft + x + "px";
 contextMenuDiv.style.top = mainDiv.offsetTop + y + "px";
 return true;
}

function rmContextMenu()
{
 if (contextMenu)
    {
     contextMenuDiv.className = 'contextMenu ' + uiProfile["box effect"]["context"] + 'hide';
     contextMenu = null;
    }
}

function setContextMenuItem(newItem)
{
 if (!contextMenu) return;
 
 if (typeof newItem === 'string')
    {
     const direction = newItem;
     if (!contextMenu.e)
     if (direction === "UP") contextMenu.e = contextMenuDiv.firstChild;	// Set start item position in case of null contextMenu.e (current active item)
      else contextMenu.e = contextMenuDiv.lastChild;			// In case of down direction start item is last item
     newItem = contextMenu.e;						// Assign new item to current active item
     do 
       {
        if (direction === "UP")
	   {
	    newItem = newItem.previousElementSibling;			// Take previous element as context menu item
	    if (!newItem) newItem = contextMenuDiv.lastChild;		// if previous element is null, take last element as context menu item
	   }
	else
	   {
	    newItem = newItem.nextElementSibling;			// Take previous element as context menu item for 'down' direction
	    if (!newItem) newItem = contextMenuDiv.firstChild;		// if previous element is null, take last element as context menu item
	   }
       }
     while (newItem != contextMenu.e && newItem.classList.contains('greyContextMenuItem'));
     if (newItem.classList.contains('greyContextMenuItem')) newItem = contextMenu.e = null;
    }
 
 if (contextMenu.e) contextMenu.e.classList.remove('activeContextMenuItem'); 
 if (newItem) newItem.classList.add('activeContextMenuItem');
 contextMenu.e = newItem;
}

function moveCursor(x, y, abs)
{
 if (!focusElement.td || focusElement.td.contentEditable == 'true' || contextMenu || (abs && focusElement.x == x && focusElement.y == y)) return;
 
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
    
 if (a >= 0 && a < mainTableWidth && b >= 0 && b < mainTableHeight) newTD = mainTablediv.rows[b].cells[a];
  else return;
  
 if (abs || isVisible(newTD) || (!isVisible(focusElement.td) && tdVisibleSquare(newTD) > tdVisibleSquare(focusElement.td)) || (y == 0 && xAxisVisible(newTD)) || (x == 0 && yAxisVisible(newTD)))
    {
     if (!abs) event.preventDefault();
     focusElement.x = a;
     focusElement.y = b;
     cellBorderToggleSelect(focusElement.td, newTD);
     focusElement.td = newTD;
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
 /*
 //BROWSER[cmd "DBLCLICK","KEYPRESS"] -> HANDLER[cmd "DIALOG"] -> BROWSER[at the end of dialog box - cmd "CONFIRM"] -> CONTROLLER[action "object database cration"]
 BROWSER[cmd "New Object Database"] -> CONTROLLER[cmd "DIALOG"] -> BROWSER[at the end of dialog box - cmd "NEWOD"] -> CONTROLLER[action "object database cration"]
 */
 /*
 "cmd":    "GETMENU|GETMAIN|GETUI|NEWOBJECT|DELETEOBJECT|DBLCLICK|KEYPRESS|CONFIRM"
 "OD":     "<OD name>"
 "OV":     "<OV name>"
 "data":   "[eId=>data, eId=>data..] for NEWOBJECT|<key code> for KEYPRESS|<cell data> or <dialog json data> for CONFIRM"
 "oId":    "<object id>"
 "eId":    "<element id>"
 "sId":    "<session id>"
 */
 let object;
 switch (cmd)
	{
	 case 'GETMENU':
	      object = { "cmd": cmd };
	      break;
	 case 'GETMAIN':
	      object = { "cmd": cmd, "OD": activeOD, "OV": activeOV };
	      break;
	 case 'Element description':
	      let msg = objectTable[mainTable[focusElement.y][focusElement.x].oId][mainTable[focusElement.y][focusElement.x].eId];
	      try   { msg = JSON.parse(msg['json']); }
	      catch { msg = null; }
	      if (msg === null || msg['description'] === undefined) msg = '';
	       else msg = '\n\nElement description property:\n' + String(msg['description']);
	       
	      msg = `\n\nTable cell 'x' coordinate: ${focusElement.x}\nTable cell 'y' coordinate: ${focusElement.y}` + msg;
	      if (mainTable[focusElement.y][focusElement.x].oId === 1) msg = 'Table cell to input new ebject data for element id: ' + mainTable[focusElement.y][focusElement.x].eId + msg;
	       else if (mainTable[focusElement.y][focusElement.x].oId === 2) msg = 'Object title for element id: ' + mainTable[focusElement.y][focusElement.x].eId + msg;
	        else msg = 'Object id: ' + mainTable[focusElement.y][focusElement.x].oId + '\nElement id: ' + mainTable[focusElement.y][focusElement.x].eId + msg;
	      alert(msg);
	      break;
	 case 'New Object':
	      if (objectTable === undefined) break;
	      object = { "cmd": 'INIT', "OD": activeOD, "OV": activeOV, "data": {} };
	      if (objectTable[NEWOBJECTID] != undefined) for (let key in objectTable[NEWOBJECTID])
		 object['data'][key] = mainTable[objectTable[NEWOBJECTID][key]['props']['y']][objectTable[NEWOBJECTID][key]['props']['x']]['data'];
	      break;
	 case 'Delete Object':
	      if (mainTable[focusElement.y] && mainTable[focusElement.y][focusElement.x] && mainTable[focusElement.y][focusElement.x].oId >= STARTOBJECTID)
	         object = {"cmd": 'DELETEOBJECT', "OD": activeOD, "OV": activeOV, "oId": mainTable[focusElement.y][focusElement.x].oId, "eId": mainTable[focusElement.y][focusElement.x].eId };
	      break;
	 case 'New Object Database':
	      object = { "cmd": cmd };
	      break;
	 case 'Edit Database Structure':
	      object = { "cmd": cmd };
	      if (data != undefined) object.data = data;
	      break;
	 case 'NEWOD':
 	 case 'EDITOD':
	      object = { "cmd": cmd, "OD": activeOD, "OV": activeOV };
	      if (data != undefined) object.data = data;
	      break;
	 case 'CONFIRM':
	 case 'DBLCLICK':
	 case 'KEYPRESS':
	      object = {"cmd": cmd, "OD": activeOD, "OV": activeOV, "oId": mainTable[focusElement.y][focusElement.x].oId, "eId": mainTable[focusElement.y][focusElement.x].eId };
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
	}
	
 if (object) Hujax("main.php", commandHandler, object);
}

function createBox(content, x, y)
{
 /*******************************************************************************************************************************/
 /* content.title		= box title											*/
 /* content.hint		= box text with no button									*/
 /* content.alert		= box text with 'ok' button only								*/
 /* content.confirm		= box text with 'ok' and 'cancel' buttons							*/
 /* content.dialog		= object with properties as tabs, each tab is an object with properties as profiles		*/
 /*				  Each profile property is an interface element with the format below.				*/
 /*		   		  "element_name":										*/
 /*						{										*/
 /*				      	  	 "type"      : select|multiple|checkbox|radio|textarea|password|text		*/
 /*				      	  	 "head"      : "<any text>"							*/
 /*				      	  	 "data"      : "{text1}|text2|text3"						*/
 /*		  		      	  	 "help"	     : "<any text>"							*/
 /*		  		      	  	 "line"	     : ""								*/
 /*		  		      	  	 "minheight" : ""								*/
 /*		  		      	  	 "readonly"  : ""								*/
 /*				     	 	}										*/
 /* content.flags		= object with properties:									*/
 /*				  "esc" - any value cancels the box 								*/
 /*				  "profile_head" - json with profile names as a properties and head text as values		*/
 /*				  "ok" - confirm action button text, default is corresponding uiProfile property		*/
 /*				  "cancel" - cancel action button text, default is corresponding uiProfile property		*/
 /*				  "pad" - active (current) pad (if exists) for dialog box					*/
 /*				  "profile" - active (current) profile (if exist) for dialog box				*/
 /*				  "minwidth" - box min width in px								*/
 /*				  "minheight" - box min height in px								*/
 /*				  "display_single_pad" - set this flag to display pad block in case of single one		*/
 /*				  "display_single_profile" - set this flag to display profile select in case of single one	*/
 /*				  <any prop> - any callback data element handler can be used					*/
 /*******************************************************************************************************************************/
 if (typeof content !== 'object') return;
 let div, inner = "";
 
 //---------------Content is alert, confirm or dialog? Do some actions for all these content types---------------
 if (content.alert != undefined || content.confirm != undefined || content.dialog != undefined)
    {
     // Remove hint if visible
     if (modalVisible == 'hint')
        {
	 clearTimeout(tooltipTimerId);                                              
	 hintDiv.className = 'box hint ' + uiProfile["box effect"]["hint"] + 'hide';
	}
     // Add title
     if (content.title != undefined && typeof content.title == 'string') inner = '<div class="title">' + toHTMLCharsConvert(content.title) + '</div>';
    }
    
 //---------------Check content types to fill corresponding html data---------------
 let footer1, footer2 = '<div style="display: flex; flex-direction: row; justify-content: space-evenly;">';
 (content.flags != undefined && content.flags.ok != undefined) ? footer1 = footer2 += '<div class="ok">' + content.flags.ok + '</div>' : footer1 = footer2 += '<div class="ok">' + uiProfile["box effect"]["Ok button default text"] + '</div>';
 (content.flags != undefined && content.flags.cancel != undefined) ? footer2 += '<div class="cancel">' + content.flags.cancel + '</div>' : footer2 += '<div class="cancel">' + uiProfile["box effect"]["Cancel button default text"] + '</div>';
 footer1 += '</div>';
 footer2 += '</div>';
 
 if (content.alert != undefined) // Content is an alert box?
    {
     if (typeof content.alert == 'string') inner += '<pre style="text-align: center;">' + content.alert + '</pre>'; // Add content
     inner += footer1; // Add 'ok' button
     modalVisible = 'alert'; // Setting _modalVisible_ global var string to current state
     div = alertDiv;
    }
  else if (content.confirm != undefined) // Content is a confirm?
    {
     if (typeof content.confirm == 'string') inner += '<pre style="text-align: center;">' + content.confirm + '</pre>'; // Add content
     inner += footer2; // Add 'ok' and 'cancel' buttons
     modalVisible = 'confirm'; // Setting _modalVisible_ global var string to current stat
     div = confirmDiv;
    }
  else if (content.dialog != undefined) // Content is a dialog box?
    {
     inner += getInnerDialog(content); // Add content
     inner += footer2; // Add 'ok' and 'cancel' buttons
     modalVisible = 'dialog'; // Setting _modalVisible_ global var string to current state
     div = dialogDiv;
    }
  else if (content.hint && typeof content.hint == 'string') // Content is a hint with non empty text?
    {
     inner = '<pre>' + content.hint + '</pre>'; // Add content
     modalVisible = 'hint'; // Setting _modalVisible_ global var string to current state
     div = hintDiv;
    }

 //---------------Any content?---------------
 if (inner)
    {
     div.innerHTML = inner; // Filling the div with the inner html
     if (typeof x === 'number' && typeof y === 'number') // Set modal window left and top position
	{
	 div.style.left = x + "px";
	 div.style.top = y + "px";
	}
      else // In case of incorrect x or y, calculate them to place modal in a center position
        {
	 x = Math.trunc((document.body.clientWidth - div.offsetWidth)*100/(2*document.body.clientWidth));
	 y = Math.trunc((document.body.offsetHeight - div.offsetHeight)*100/(2*document.body.offsetHeight));
	 div.style.left = x + "%";
	 div.style.top = y + "%";
	}
     // Showing the div
     div.className = 'box ' + modalVisible + ' ' + uiProfile["box effect"][modalVisible] + 'show';
     // Applying filters if exist
     if (uiProfile["box effect"][modalVisible + " filter"])
        {
	 mainDiv.style.filter = uiProfile["box effect"][modalVisible + " filter"];
	 menuDiv.style.filter = uiProfile["box effect"][modalVisible + " filter"];
	}
    }
}

function getInnerDialog(content)
{
 if (typeof content.dialog !== "object") return '';
 let element, data, count = 0, inner = '';
 
 //------------------Creating current pad and profile if not exist------------------
 if (!content.flags)
    {
     content.flags = { "pad": "", "profile": "" };
    }
  else
    {
     if (!content.flags.pad) content.flags.pad = "";
     if (!content.flags.profile) content.flags.profile = "";
    }
    
 //------------------Checking current pad. First step - seeking current pad match------------------
 for (element in content.dialog) if (typeof content.dialog[element] === "object")
     {
      if (count === 0) data = element; // Remember first pad as a current pad for default
      if (element === content.flags.pad) data = count; // Match case? Assign current 'element' for current pad
      count++;
     }
 // Empty dialog with zero pads number? Return empty html.
 if (count === 0) return '';
 // No match - assing first pad for default
 if (typeof data === 'string') content.flags.pad = data;
 // Pads count more than one? Creating pad block DOM element.
 if (count > 1 || content.flags.display_single_pad != undefined)
    {
     // Creating pad wrapper
     inner = '<div class="padbar" style="display: flex; flex-direction: row; justify-content: flex-start;">';
     // Inserting pad divs
     for (element in content.dialog) if (typeof content.dialog[element] === "object")
      if (element === content.flags.pad) inner += '<div class="activepad">' + element + '</div>';
       else inner += '<div class="pad">' + element + '</div>';
     // Closing pad wrapper tag
     inner += '</div>';
    }

 //------------------Checking current profile in current pad. First step - initiate variables------------------
 count = 0;
 // Seeking current profile match.
 for (element in content.dialog[content.flags.pad]) if (typeof content.dialog[content.flags.pad][element] === "object")
     {
      if (count === 0) data = element; // Remember first profile as a current profile for default
      if (element === content.flags.profile) data = count; // Match case? Assign current 'element' for current profile
      count++;
     }
 // Empty dialog[<current_pad>] with zero profiles number? Return current pad empty content.
 if (count === 0) return inner;
 // No match - assing first profile for default
 if (typeof data === 'string') content.flags.profile = data;
 // Profiles count more than one? Creating profile select DOM element.
 if (count > 1 || content.flags.display_single_profile != undefined)
    {
     // Add profile head
     if (content.flags.padprofilehead != undefined && content.flags.padprofilehead[content.flags.pad] != undefined) inner += '<pre class="element-headers">' + content.flags.padprofilehead[content.flags.pad] + '</pre>';
     // In case of default first profile set zero value to use as a select attribute
     if (typeof data === 'string') data = 0;
     // Add select block and divider
     inner += '<div class="select" type="select-profile"><div value="' + data + '">' + content.flags.profile + '</div></div><div class="divider"></div>';
    }
    
 //------------------Parsing interface element in content.dialog.<current pad>.<current profile>------------------
 for (let name in content.dialog[content.flags.pad][content.flags.profile])
  if (content.dialog[content.flags.pad][content.flags.profile][name]["type"] != undefined)
     {
      element = content.dialog[content.flags.pad][content.flags.profile][name];
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
 return inner;
}

function saveDialogProfile()
{
 const init = {};
 dialogDiv.querySelectorAll('input, .select, textarea').forEach(function(element)
			   {
			    switch (element.attributes.type.value)
				   {
				    case 'select-multiple':
					 const el = boxContent.dialog[boxContent.flags.pad][boxContent.flags.profile][element.attributes.name.value];
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
					 if (init[element.attributes.name.value] === undefined) init[element.attributes.name.value] = boxContent.dialog[boxContent.flags.pad][boxContent.flags.profile][element.attributes.name.value]["data"] = '';
					 if (element.checked) boxContent.dialog[boxContent.flags.pad][boxContent.flags.profile][element.attributes.name.value]["data"] += '+' + element.nextSibling.innerHTML + '|';
					  else boxContent.dialog[boxContent.flags.pad][boxContent.flags.profile][element.attributes.name.value]["data"] += element.nextSibling.innerHTML + '|';
					 break;
				    case 'password':
				    case 'text':
				    case 'textarea':
					 boxContent.dialog[boxContent.flags.pad][boxContent.flags.profile][element.attributes.name.value]["data"] = element.value;
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
		       
function rmBox()
{
 switch (modalVisible)
	{
	 case 'alert':
	      alertDiv.className = 'box alert ' + uiProfile["box effect"]["alert"] + 'hide';
	      break;
	 case 'confirm':
	      confirmDiv.className = 'box confirm ' + uiProfile["box effect"]["confirm"] + 'hide';
	      break;
	 case 'dialog':
	      dialogDiv.className = 'box dialog ' + uiProfile["box effect"]["dialog"] + 'hide';
	      expandedDiv.className = 'select expanded ' + uiProfile["box effect"]["select"] + 'hide';
	      break;
	}
 modalVisible = "";
 boxContent = null;
 mainDiv.style.filter = "none";
 menuDiv.style.filter = "none";
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

function mainTableAddEventListeners()
{
 if (mainTablediv) mainTablediv.addEventListener('dblclick', eventHandler);

 // Add current table hint div tags event listeners
 let cell;
 tdhintDiv = [];
 for (let r = 0; r < mainTableHeight; r++)
  if (mainTable[r] != undefined)
     for (let i = 0; i < mainTableWidth; i++)
         if ((cell = mainTable[r][i]) && cell.hint)
    	    {
             tdhintDiv.push(mainTablediv.rows[r].cells[i]);
	     mainTablediv.rows[r].cells[i].addEventListener('mouseenter', eventHandler);
	     mainTablediv.rows[r].cells[i].addEventListener('mouseleave', eventHandler);
	     mainTablediv.rows[r].cells[i].addEventListener('mousemove', eventHandler);
    	    }
}

function mainTableRemoveEventListeners()
{
 if (mainTablediv) mainTablediv.removeEventListener('dblclick', eventHandler);
    
 // Remove current table hint div tags event listeners
 tdhintDiv.forEach((el) => {
			    el.removeEventListener('mouseenter', eventHandler);
			    el.removeEventListener('mouseleave', eventHandler);
			    el.removeEventListener('mousemove', eventHandler);
			   });
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

function escapeDoubleQuotes(data)
{ 
 return data.replace(/"/g,"&quot;");
}