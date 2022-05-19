
// Style default user inteface profile and append style DOM element to the document head
styleUI();
document.head.appendChild(style);

window.onload = function()
{
 // Define document html and add appropriate event listeners
 document.body.innerHTML = '<div class="sidebar"></div><div class="main"></div><div class="contextmenu ' + uiProfile["context menu"]["effect"] + 'hide"></div><div class="hint ' + uiProfile["hint"]["effect"] + 'hide"></div><div class="box ' + uiProfile["dialog box"]["effect"] + 'hide"></div><div class="select expanded ' + uiProfile["dialog box select"]["effect"] + 'hide"></div>';
 document.addEventListener('keydown', keydownEventHandler);
 document.addEventListener('contextmenu', contextmenuEventHandler);
 document.addEventListener('mousedown', mousedownEventHandler);
 document.addEventListener('mouseup', mouseupEventHandler);
 document.addEventListener('mousemove', mousemoveEventHandler);

 // Define sidebar div
 sidebarDiv = document.querySelector('.sidebar');

 // Define main field div and add 'scroll' event for it
 mainDiv = document.querySelector('.main');
 mainDiv.addEventListener('scroll', () => { HideHint(); HideContextmenu(); });
 mainDiv.addEventListener('dblclick', dblclickEventHandler);

 // Define context menu div and add some mouse events
 contextmenuDiv = document.querySelector('.contextmenu');
 contextmenuDiv.addEventListener('mouseover', event => { if (event.target.classList.contains('contextmenuItems') && !event.target.classList.contains('greyContextMenuItem')) SetContextmenuItem(event.target); });
 contextmenuDiv.addEventListener('mouseout', () => SetContextmenuItem(null));

 // Define interface divs
 hintDiv = document.querySelector('.hint');
 boxDiv = document.querySelector('.box');
 expandedDiv = document.querySelector('.expanded');

 // Create image wrapper (for user galleries) and canvas elements
 imgwrapper = document.createElement('div');
 imgwrapper.classList.add('imgwrapper');
 img = document.createElement('img');
 imgdesc = document.createElement('div');
 imgdesc.classList.add('imgdesc');

 // Other initialization
 cmd = 'CALL';
 CreateWebSocket();
 for (let tag of ALLOWEDTAGS) allowedtagsregexpstring += `<${tag} .*?>|<${tag} *>|<\/${tag} *>|`;
 allowedtagsregexp = new RegExp(allowedtagsregexpstring.substring(0, allowedtagsregexpstring.length - 1));
 allowedtagsregexpg = new RegExp(allowedtagsregexpstring.substring(0, allowedtagsregexpstring.length - 1), 'g');
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

async function ReadBuffer()
{
 let text;

 if (!window.navigator.clipboard)
    {
     warning("Your browser doesn't support clipboard read operations!");
     return '';
    }

 try {
      text = await navigator.clipboard.readText();
     }
 catch (error)
     {
      warning('Browser clipboard read operation error: ' + error);
      return '';
     }

 return text;
}

async function Hujax(url, callback, options)
{
 try {
      const response = await fetch(url, options);
      if (!response.ok)
	 {
	  displayMainError(`Request failed with response ${response.status}: ${response.statusText}`);
	  return;
	 }
      const contenttype = response.headers.get('Content-Type');
      if (contenttype.indexOf('text/html') === 0)
	 {
	  response.json().then(callback);
	  return;
	 }
      if (contenttype.indexOf('application/octet-stream') === 0)
	 {
	  response.blob().then(blob => callback.call(this, {cmd: 'SAVEFILE', data: blob, name: response.headers.get('Content-Disposition')}));
	  return;
	 }
     }
 catch (error)
     {
      lg('Ajax request error: ', error);
     }
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

      data[odid]['name'] = AdjustAttribute(data[odid]['name']);
      // Create OV names list with active OV check 
      ovlistHTML = '';
      for (let ovid in data[odid]['view'])
	  {
	   data[odid]['view'][ovid] = AdjustAttribute(data[odid]['view'][ovid]);
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
	       ovlistHTML += `<tr${text}><td class="emptywrap"></td><td class="sidebar-ov" data-odid="${odid}" data-ovid="${ovid}" data-od="${data[odid]['name']}" data-ov="${data[odid]['view'][ovid]}">${data[odid]['view'][ovid]}${count}</td></tr>`;
	      }
	  }

      // Draw wrap icon
      if (ovlistHTML === '') sidebarHTML += '<tr><td class="emptywrap"></td>';  // Insert empty wrap icon
       else if (data[odid]['wrap'] === false) sidebarHTML += '<tr><td class="wrap">' + uiProfile['sidebar wrap']['content'] + '</td>'; // Insert wrap icon
        else sidebarHTML += '<tr><td class="unwrap">' + uiProfile['sidebar unwrap']['content'] + '</td>'; // Insert unwrap icon

      // Insert OD name
      sidebarHTML += `<td class="sidebar-od" data-odid="${odid}">${AdjustAttribute(data[odid]['name'])}</td></tr>`;

      // Insert OV names list if OD is unwrapped
      if (data[odid]['wrap'] === false) sidebarHTML += ovlistHTML;
     }

 // Push calculated html text to sidebar div
 sidebarHTML != '' ? sidebarDiv.innerHTML = '<table style="margin: 0px;"><tbody>' + sidebarHTML + '</tbody></table>' : sidebarDiv.innerHTML = '';

 // Reset sidebar to the new data
 sidebar = data;
}

function GetLayoutProperties(eid, o, e, n, q)
{
 const arr = { style: '' }, style = {};
 let i, expression;

 if (o) for (i = 0; i < eid['expression'].length; i ++)
    {
     try { expression = eval(eid['expression'][i]['oid']); }
     catch { expression = false; }
     if (expression) break;
    }
 if (expression) expression = eid['expression'][i];

 for (let j of [expression, eid['*'], eid[o]]) if (j)
 for (let p in j)
  if (p === 'style')
     {
      if (!j[p]) continue;
      for (let rule of j[p].split(';'))
       if ((i = (rule = rule.trim()).indexOf(':')) > 0 && rule.length > i + 1)
	  style[rule.substr(0, i)] = rule.substr(i + 1); // Some chars before and after ':'?
     }
   else
     {
      arr[p] = j[p];
     }

 if (arr.x === undefined || arr.y === undefined) return;
 try { arr.x = Math.trunc(eval(arr.x)); arr.y = Math.trunc(eval(arr.y)); }
 catch { arr.x = undefined; }
 if (isNaN(arr.x) || isNaN(arr.y)) return `Specified view '${OV}' element layout has some 'x','y' incorrect coordinate definitions!\nSee element element layout help section`;
 if (arr.x < 0 || arr.y < 0) return `View '${OV} warning: element coordiantes (x=${arr.x}, y=${arr.y}) for object number ${n} are less than zero`;
 if ((Math.max(mainTableWidth, arr.x + 1) * Math.max(mainTableHeight, arr.y + 1)) > TABLE_MAX_CELLS) return `View '${OV} warning: element coordiantes (x=${arr.x}, y=${arr.y}) for object number ${n} are out of range. Max table size allowed - ${TABLE_MAX_CELLS} cells`;

 for (let rule in style) arr['style'] += `${rule}:${style[rule]}; `;
 return arr;
}

function SetCell(arr, obj, eid, hiderow, hidecol, attached)
{
 // Create main table row if doesn't exist
 if (mainTable[arr.y] === undefined) mainTable[arr.y] = [];

 // Virtual cell
 if (!eid)
    {
     mainTable[arr.y][arr.x] = { data: arr.value ? arr.value : '', style: arr.style };
     const cell = mainTable[arr.y][arr.x];
     cell.attr = `${datacellclass ? ' class="' + datacellclass + '"' : ''}${cell.style ? 'style="' + cell.style + '"' : ''}`;
     if (arr.hint) cell.hint = ToHTMLChars(arr.hint);
     // Calculate main table width and height
     mainTableWidth = Math.max(mainTableWidth, arr.x + 1);
     mainTableHeight = Math.max(mainTableHeight, arr.y + 1);
     return;
    }

 // Data cell
 const oidnum = +obj.id;
 mainTable[arr.y][arr.x] = { oId: oidnum, eId: eid, noteclassindex: 0, style: arr.style };
 const cell = mainTable[arr.y][arr.x];

 // Value, hint and link are different for service and user elements
 if (SERVICEELEMENTS.indexOf(eid) === -1)
    {
     cell.data = arr.value === undefined ? obj['eid' + eid + 'value'] : arr.value;
     if (cell.data === null) cell.data = '';
     cell.hint = arr.hint === undefined ? obj['eid' + eid + 'hint'] : arr.hint;
     if (obj['eid' + eid + 'link']) cell.noteclassindex += 2;
     if (obj.lastversion === '1' && obj.version != '0' && oidnum >= STARTOBJECTID && attached?.[oidnum]?.[eid]) cell.noteclassindex += 4;
     cell.style += obj['eid' + eid + 'style']
    }
  else
    {
     cell.data = arr.value === undefined ? obj[eid] : arr.value;
     if (cell.data === null) cell.data = '';
     if (arr.hint) cell.hint = arr.hint;
    }
 if (cell.hint) cell.noteclassindex += 1;
 const noteclass = cell.noteclassindex ? ' note' + cell.noteclassindex : '';

 // Add version and realobject flag to database (not virtual, title or new-input) objects
 if (oidnum >= STARTOBJECTID)
    {
     cell.attr = `${(datacellclass + noteclass) ? ' class="' + datacellclass + noteclass + '"' : ''}${cell.style ? 'style="' + cell.style + '"' : ''}`;
     cell.version = obj.version;
     cell.realobject = (obj.lastversion === '1' && obj.version != '0') ? true : false;
    }
 else if (oidnum === NEWOBJECTID) cell.attr = `${(newobjectcellclass + noteclass) ? ' class="' + newobjectcellclass + noteclass + '"' : ''}${cell.style ? 'style="' + cell.style + '"' : ''}`;
 else if (oidnum === TITLEOBJECTID) cell.attr = `${(titlecellclass + noteclass) ? ' class="' + titlecellclass + noteclass + '"' : ''}${cell.style ? 'style="' + cell.style + '"' : ''}`;

 // Fix matched 'hiderow'/'hidecol' rows/columns to collapse
 if (arr.hiderow !== undefined && cell.data === arr.hiderow) hiderow[arr.y] = true;
 if (arr.hidecol !== undefined && cell.data === arr.hidecol) hidecol[arr.x] = true;

 // Calculate main table width and height
 mainTableWidth = Math.max(mainTableWidth, arr.x + 1);
 mainTableHeight = Math.max(mainTableHeight, arr.y + 1);

 // Get start event at OV open (except add/remove operations). Using last found.
 if (arr.event !== undefined && cmd === 'CALL')
    {
     cursor.oId = oidnum; // Event does exist, so get its name and its object/elemnt ids
     cursor.eId = eid;
     cursor.x = arr.x;
     cursor.y = arr.y;
     cursor.cmd = arr.event.trimStart();
    }

 // Convert hint to html chars
 if (cell.hint) cell.hint = ToHTMLChars(cell.hint);
}

function drawMain(data, layout, attached)
{
 // Reset unread messages counter and clear selected area
 ResetUnreadMessages();
 delete drag.x1;

 // Add/delete operations? Leave cursor props unchanged, otherwise try to remember cursor position if exist for the current view call
 if (cmd === 'CALL')
    {
     if (cursor.td && cursor.ODid === ODid && cursor.OVid === OVid) cursor = { ODid: ODid, OVid: OVid, x: cursor.x, y: cursor.y };
      else cursor = { ODid: ODid, OVid: OVid };
    }
  else
    {
     if (cursor.td?.contentEditable === EDITABLE && cursor.oId !== NEWOBJECTID) cursor.edit = { data: FromHTMLChars(cursor.td.innerHTML), oId: cursor.oId,  eId: cursor.eId };
     cursor.newobject = {};
     for (let eid in objectTable[NEWOBJECTID])
	 {
	  const x = objectTable[NEWOBJECTID][eid].x;
	  const y = objectTable[NEWOBJECTID][eid].y;
	  cursor.newobject[eid] =  mainTablediv.rows[y].cells[x].innerHTML;
	 }
    }

 // Init some important vars such as tables, focus element and etc..
 mainTable = [];
 objectTable = {};
 mainTableWidth = mainTableHeight = 0;
 OVtype = 'Table';

 const eids = layout['elements'], hiderow = [], hidecol = [];
 let arr, obj, error, n;
 objectsOnThePage = data.length;
 VirtualElements = 0;

 for (let eid in eids)
     {
      const element = eids[eid];
      const e = element['order'];
      // Draw title object for the element
      let checktitle = false;
      if (element[TITLEOBJECTID])
	 {
	  arr = GetLayoutProperties(element, TITLEOBJECTID, e, 0, objectsOnThePage);
	  if (typeof arr === 'string') error = arr;
	  if (typeof arr === 'object') SetCell(arr, {id: TITLEOBJECTID}, eid, hiderow, hidecol);
	  // In case of constant x,y coordinates (no 'o|e|n' variables in x,y) check title only once here
	  checktitle = (/o|e|n/.test(element[TITLEOBJECTID].x) || /o|e|n/.test(element[TITLEOBJECTID].y)) ? true : false;
	 }
      // Draw new-input object for the element
      if (element[NEWOBJECTID])
	 {
	  arr = GetLayoutProperties(element, NEWOBJECTID, e, 0, objectsOnThePage);
	  if (typeof arr === 'string') error = arr;
	  if (typeof arr === 'object') SetCell(arr, {id: NEWOBJECTID}, eid, hiderow, hidecol);
	 }
      // Process OV objects
      for (n = 0; n < data.length; n++)
	  {
	   obj = data[n];
	   arr = GetLayoutProperties(element, +obj.id, e, n, objectsOnThePage);
	   if (typeof arr === 'string') error = arr;
	   if (typeof arr === 'object') SetCell(arr, obj, eid, hiderow, hidecol, attached);
	   if (!checktitle || !n) continue;
	   arr = GetLayoutProperties(element, TITLEOBJECTID, e, n, objectsOnThePage);
	   if (typeof arr === 'string') error = arr;
	   if (typeof arr === 'object') SetCell(arr, {id: TITLEOBJECTID}, eid, hiderow, hidecol);
	  }
     }

 for (let i = 0; i < layout['virtual'].length; i++, n++)
     {
      arr = GetLayoutProperties({ '*': layout['virtual'][i] }, undefined, undefined, n);
      if (typeof arr === 'string') error = arr;
      if (typeof arr === 'object') SetCell(arr, {});
      VirtualElements++;
     }
 perfomance.push({ time: new Date(), process: 'Table configuring: ' });

 // Handle some errors
 if (!mainTableWidth)
    {
     if (!error) error = `Specified view '${OV}' has no objects matched current layout!<br>Please change element layout to display some objects and its elements`;
     displayMainError(error, false);
     return;
    }
 if (error) warning(error);

 // Create html table of mainTable array, props[0][0] = { style: , table: }
 layout['undefined']['style'] = layout['undefined']['style'] ? ` style="${layout['undefined']['style']}"` : '';
 const undefinedCell = '<td' + undefinedcellclass + layout['undefined']['style'] + '></td>';

 // Rotate table on a layout['table']['rotate'] property set
 let x, y;
 if (layout['table']['rotate'] === '180')
    {
     const table = [];
     for (y = 0; y < mainTableHeight; y++)
	 if (mainTable[y]) table[mainTableHeight - 1 - y] = mainTable[y];
     mainTable = table;
    }
 else if (layout['table']['rotate'] === '90' || layout['table']['rotate'] === '270')
    {
     const table = [];
     let newx, newy;
     for (y = 0; y < mainTableHeight; y++) if (mainTable[y])
     for (x = 0; x < mainTableWidth; x++) if (mainTable[y][x])
	 {
	  newy = layout['table']['rotate'] === '270' ? mainTableWidth - 1 - x : x;
	  newx = layout['table']['rotate'] === '90' ? mainTableHeight - 1 - y : y;
	  if (!table[newy]) table[newy] = [];
	  table[newy][newx] = mainTable[y][x];
	 }
     newy = mainTableWidth;
     mainTableWidth = mainTableHeight;
     mainTableHeight = newy;
     mainTable = table;
    }
 perfomance.push({ time: new Date(), process: 'Table rotation: ' });

 // Add table attributes
 let rowHTML = '<table';
 for (let attr in layout['table']) if (attr !== 'rotate') rowHTML += ` ${attr}="${layout['table'][attr]}"`;
 rowHTML += '><tbody>';

 // Create 'undefined' html tr element row
 let disp = 0, undefinedRow = '<tr>';
 for (x = 0; x < mainTableWidth - hidecol.length; x++) undefinedRow += undefinedCell;
 undefinedRow += '</tr>';

 // Set inner html content for the table view and add event listeners
 const newtable = [];
 for (y = 0; y < mainTableHeight; y++)
     {
      if (hiderow[y] || (!mainTable[y] && layout['undefined']['hiderow'] !== undefined)) { disp++; continue; }
      if (!mainTable[y]) { rowHTML += undefinedRow; continue; }
      newtable[y - disp] = mainTable[y];
      rowHTML += '<tr>';
      for (x = 0; x < mainTableWidth; x++)
	  {
	   if (hidecol[x]) { mainTable[y].splice(x, 1); mainTableWidth--; x--; continue; }
	   if (!(cell = mainTable[y][x])) { rowHTML += undefinedCell; continue; }
	   if (cell.oId === NEWOBJECTID && cursor.newobject?.[cell.eId]) cell.data = cursor.newobject[cell.eId];
	   rowHTML += `<td${cell.attr}>${ToHTMLChars(cell.data)}</td>`;
	   // objectTable[oid][id|version|owner|datetime|lastversion|1|2..] = { x: , y: }
	   if (cell.realobject || cell.oId === TITLEOBJECTID || cell.oId === NEWOBJECTID)
	      objectTable[cell.oId] ? objectTable[cell.oId][cell.eId] = { x: x, y: y - disp } : objectTable[cell.oId] = { [cell.eId]: { x: x, y: y - disp } };
	  }
      rowHTML += '</tr>';
     }
 mainTable = newtable;
 mainTableHeight -= disp;
 perfomance.push({ time: new Date(), process: 'Preparing HTML data: ' });

 // Main table becomes empty due to hidden rows/columns?
 if (!mainTableWidth)
    {
     displayMainError('All table rows and columns are hidden!<br>Please change element layout to display some objects and its elements', false);
     return;
    }

 // Set main view HTML
 clearTimeout(loadTimerId);
 mainDiv.innerHTML = rowHTML + '</tbody></table>';
 mainTablediv = mainDiv.querySelector('table');

 // Cursor object/element id does exist? Calculate x and y position
 if (cursor.oId && cursor.eId && objectTable[cursor.oId]?.[cursor.eId])
    {
     cursor.x = objectTable[cursor.oId][cursor.eId].x;
     cursor.y = objectTable[cursor.oId][cursor.eId].y;
    }

 // Set cursor position on the table
 if (cursor.x !== undefined && cursor.y !== undefined)
    {
     cursor.y = Math.min(cursor.y, mainTableHeight - 1);
     cursor.x = Math.min(cursor.x, mainTableWidth - 1)
     CellBorderToggleSelect(null, mainTablediv.rows[cursor.y].cells[cursor.x], FOCUS_VERTICAL | FOCUS_HORIZONTAL | FOCUS_EDGE);
     if (cursor.oId === NEWOBJECTID) MakeCursorContentEditable();
     if (cursor.edit !== undefined && cursor.edit.oId === cursor.oId && cursor.edit.eId === cursor.eId) MakeCursorContentEditable(cursor.edit.data);
    }

 // Start event does exist? Process it
 if (cursor.cmd !== undefined)
    {
     if (['DBLCLICK', 'INS', 'DEL', 'F2', 'F12'].indexOf(cursor.cmd.trim()) !== -1)
	{
	 if (cursor.oId >= STARTOBJECTID && !isNaN(cursor.eId) && (cmd = cursor.cmd.trim())) CallController();
	}
      else if (cursor.cmd.match(/^KEYPRESS/))
	{
	 if (cursor.oId >= STARTOBJECTID && !isNaN(cursor.eId) && (cmd = 'KEYPRESS')) CallController(cursor.cmd.substr(8));
	}
      else if (cursor.cmd.match(/^CHART\(\d*,\d*,\d*,\d*\)/) || (cursor.cmd.trim() === 'CHART' && (cursor.cmd = `CHART(0,0,${mainTableWidth - 1},${mainTableHeight - 1})`)))
	{
	 cursor.cmd = cursor.cmd.split('(')[1].split(')')[0].split(',');
	}
    }

 // Draw chart in case of apprropriate start event
 if (Array.isArray(cursor.cmd)) DrawChart(+cursor.cmd[0], +cursor.cmd[1], +cursor.cmd[2], +cursor.cmd[3]);

 // Release command value
 cmd = '';
 delete cursor.cmd;
 delete cursor.edit;
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
 mainTable[y][x] = { colspan: tree['colspan'], content: tree['content'], oid: tree['oid']};
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

function DrawTree(tree, rotate)
{
 let x, y, stockrow, arrowrow, objectrow, content, title, value, trs = '';

 // Flush old data
 clearTimeout(loadTimerId);
 cursor = { ODid: ODid, OVid: OVid };

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
	     objectrow += mainTable[y][x]['class'] + ` data-x='${x}' data-y='${y}'>` + GetTreeElementContent(mainTable[y][x]['content']) + '</td>';
	     //----------------------
	     value = EllipsesClip(mainTable[y][x]['content'][0]['value'], uiProfile['tree element']['object element value max chars']);
	     title = EllipsesClip(mainTable[y][x]['content'][0]['title'], uiProfile['tree element']['object element title max chars']);
	     stockrow += '><div class="treelink"><div style="justify-content: flex-end; align-items: flex-' + (rotate === '180' ? 'end' : 'start') + ';" class="treelinkdescription"><span>' + title + '</span></div><div class="treelinkstock"></div><div style="justify-content: flex-start; align-items: flex-' + (rotate === '180' ? 'end' : 'start') + ';" class="treelinkdescription">' + value + '</div></div></td>';
	     //----------------------
	     if (content = mainTable[y][x]['content'][1])
	        {
		 title = EllipsesClip(content['title'], uiProfile['tree element']['object element title max chars']);
		 value = EllipsesClip(content['value'], uiProfile['tree element']['object element value max chars']);
		 arrowrow += '><div class="treelink"><div style="' + (content['title'] === undefined ? 'color: red; ' : '');
		 arrowrow += 'justify-content: flex-end; align-items: flex-' + (rotate === '180' ? 'start' : 'end') + ';" class="treelinkdescription">';
		 arrowrow += '<span>' + (content['title'] === undefined ? 'Unknown element:' : title) + '</span></div>';
		 arrowrow += '<div class="treelinkarrow' + (rotate === '180' ? 'up' : 'down') + '"></div>';
		 arrowrow += '<div style="' + (content['title'] === undefined ? 'color: red; ' : '');
		 arrowrow += 'justify-content: flex-start; align-items: flex-' + (rotate === '180' ? 'start' : 'end') + ';" class="treelinkdescription">';
		 arrowrow += '<span>' + (content['title'] === undefined ? EllipsesClip(content['id'], uiProfile['tree element']['object element value max chars']) : value) + '</span></div></td>';
		}
	     //----------------------
	     x += mainTable[y][x]['colspan'];
	    }
      if (rotate === '180')
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

function EllipsesClip(string, limit)
{
 if (typeof string === 'number') string = String(string);
 if (typeof string !== 'string') return '';
 if (typeof limit !== 'number') limit = Number(limit);
 if (limit < 3) limit = 3;

 let result, newstring = '';

 while (result = allowedtagsregexp.exec(string))
       {
	newstring += string.substr(0, result.index);
	string = string.substr(result.index + result[0].length);
       }

 newstring += string;
 newstring = newstring.replace(/\n/g, ' ');
 if (newstring.length > limit) newstring = newstring.substr(0, limit - 2) + '..';

 return EncodeHTMLSpecialChars(newstring);
}

function GetTreeElementContent(content)
{
 let data = '';
 for (let i = 2; i < content.length; i++)
     {
      if (content[i]['title']) data += `<span class="underlined">${EllipsesClip(content[i]['title'], uiProfile['tree element']['object element title max chars'])}</span>: `;
      data += EllipsesClip(content[i]['value'], uiProfile['tree element']['object element value max chars']);
      data += '<br>';
     }
 return data;
}

function ReselectTableArea(x1, y1, x2, y2, x3, y3)
{
 for (let y = Math.min(y1, y2, y3); y <= Math.max(y1, y2, y3); y++)
 for (let x = Math.min(x1, x2, x3); x <= Math.max(x1, x2, x3); x++)
     {
      if (y >= Math.min(y1, y2) && y <= Math.max(y1, y2) && x >= Math.min(x1, x2) && x <= Math.max(x1, x2) && (y < Math.min(y1, y3) || y > Math.max(y1, y3) || x < Math.min(x1, x3) || x > Math.max(x1, x3)))
	 mainTablediv.rows[y].cells[x].classList.remove('selectedcell');
       else if (y >= Math.min(y1, y3) && y <= Math.max(y1, y3) && x >= Math.min(x1, x3) && x <= Math.max(x1, x3))
	 mainTablediv.rows[y].cells[x].classList.add('selectedcell');
     }
}

function SelectTableArea(x1, y1, x2, y2)
{
 for (let y = Math.min(y1, y2); y <= Math.max(y1, y2); y++)
 for (let x = Math.min(x1, x2); x <= Math.max(x1, x2); x++)
     /*if (x != x1 || y != y1)*/ mainTablediv.rows[y].cells[x].classList.add('selectedcell');
}

function UnSelectTableArea(x1, y1, x2, y2)
{
 for (let y = Math.min(y1, y2); y <= Math.max(y1, y2); y++)
 for (let x = Math.min(x1, x2); x <= Math.max(x1, x2); x++)
     /*if (x != x1 || y != y1)*/ mainTablediv.rows[y].cells[x].classList.remove('selectedcell');
}

function SeekObjJSONProp(object, name, match) // Search property name in object properties, false match searches returns 1st found prop with nonexistent name
{
 for (let prop in object)
  if (object[prop][name] === undefined)
     {
      if (!match) return prop;
     }
   else
     {
      if (match) return prop;
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

function ShowImage(value)
{
 gallery.index += value;
 if (gallery.index === gallery.list.length) gallery.index = 0;
 if (gallery.index === -1) gallery.index = gallery.list.length - 1;

 img.src = `${window.location.href}file.php?id=${gallery.data}&img=${gallery.index}`;
 img.alt = gallery.list[gallery.index];
 imgdesc.innerHTML = `${gallery.index + 1}/${gallery.list.length} ${img.alt}`;
}

function FromController(json)
{
 let input;
 try { input = JSON.parse(json.data); }
 catch { input = json; }

 if (input.customization)	{ uiProfileSet(input.customization); styleUI(); }
 if (input.auth != undefined)	{ user = input.auth; }
 if (input.cmd === undefined)	{ warning('Undefined server message!'); return; }

 switch (input.cmd)
	{
	 case 'GALLERY':
	      if (box || (cursor.td && cursor.td.contentEditable === EDITABLE) || OVtype !== 'Table') break;
	      OVtype = 'Gallery';
	      gallery = { list: input.list, data: input.data, index: 0 };
	      // Hide main table div
	      mainTablediv.style.display = 'none';
	      // Append image wrapper div
	      mainDiv.appendChild(imgwrapper);
	      imgwrapper.appendChild(img);
	      // Append image description div
	      imgdesc.style.left = mainDiv.offsetLeft + 10 + "px";
	      imgdesc.style.top = mainDiv.offsetTop + 10 + "px";
	      imgdesc.style.display = 'block';
	      document.body.appendChild(imgdesc);
	      // Showing current image with description
	      ShowImage(0);
	      break;
	 case 'SAVEFILE':
	      let element = document.createElement('a');
	      element.href = URL.createObjectURL(input.data);
	      try { element.download = JSON.parse(input.name.substring(input.name.indexOf('=') + 1)).name; }
	      catch { element.download = ''; }
	      element.click();
	      URL.revokeObjectURL(element.href);
	      break;
	 case 'DIALOG':
	      if (cursor.td?.contentEditable === EDITABLE || (input.data.flags?.updateonly !== undefined && !box)) break;
	      let scrollLeft, scrollTop;
	      if (box?.contentDiv?.scrollLeft) scrollLeft = box.contentDiv.scrollLeft;
	      if (box?.contentDiv?.scrollTop) scrollTop = box.contentDiv.scrollTop;
	      box = input.data;
	      ShowBox(scrollLeft, scrollTop);
	      break;
	 case 'UPLOADDIALOG':
	      if (cursor.td?.contentEditable === EDITABLE) break;
	      if (browse) browse.remove();
	      browse = document.createElement('input');
	      browse.style.display = 'none';
	      browse.setAttribute('type', 'file');
	      browse.setAttribute('multiple', '');
	      browse.onchange = UploadDialog;
	      document.body.appendChild(browse);
	      UploadDialog();
	      box.flags.data = input.data;
	      break;
	 case 'DOWNLOADDIALOG':
	 case 'UNLOADDIALOG':
	      let list = '';
	      for (const i in input.list) if (typeof input.list[i] === 'string') list += input.list[i] + '|';
	      DownloadDialog(list, input.cmd);
	      box.flags.data = input.data;
	      box.flags.list = input.list;
	      break;
	 case 'EDIT':
	      if (box || (cursor.td && cursor.td.contentEditable === EDITABLE) || !objectTable[input.oId][input.eId]) break;
	      if (cursor && mainTable[cursor.y] && mainTable[cursor.y][cursor.x])
	      if (mainTable[cursor.y][cursor.x].oId === input.oId && mainTable[cursor.y][cursor.x].eId === input.eId)
	         MakeCursorContentEditable(input.data);
	      break;
	 case 'SET':
	      let x, y, value, cell;
	      if (!objectTable[input.oId]) break;
	      for (let eid in input.data) if (objectTable[input.oId][eid] && typeof input.data[eid] === 'object')
		  {
		   cell = mainTablediv.rows[y = objectTable[input.oId][eid].y].cells[x = objectTable[input.oId][eid].x];
		   if (input.data[eid]['value'] !== undefined)
		      {
		       mainTable[y][x].data = input.data[eid]['value'];
		       if (cell.contentEditable != EDITABLE) mainTablediv.rows[y].cells[x].innerHTML = ToHTMLChars(input.data[eid]['value']);
		      }
		   if (input.data[eid]['style'])
		      {
		       cell.setAttribute('style', mainTable[y][x].style + input.data[eid]['style']);
		      }
		   // Pictogram process
		   if (mainTable[y][x].noteclassindex) cell.classList.remove('note' + mainTable[y][x].noteclassindex);
		   if (input.data[eid]['hint'] !== undefined)
		      {
		       mainTable[y][x].noteclassindex &= 6;
		       if (mainTable[y][x].hint = input.data[eid]['hint']) mainTable[y][x].noteclassindex |= 1;
		      }
		   if (input.data[eid]['link'] !== undefined)
		      {
		       mainTable[y][x].noteclassindex &= 5;
		       if (input.data[eid]['link']) mainTable[y][x].noteclassindex |= 2;
		      }
		   if (input.data[eid]['attached'] !== undefined)
		      {
		       mainTable[y][x].noteclassindex &= 3;
		       if (input.data[eid]['attached']) mainTable[y][x].noteclassindex |= 4;
		      }
		   if (mainTable[y][x].noteclassindex) cell.classList.add('note' + mainTable[y][x].noteclassindex);
		  }
	      CellBorderToggleSelect(null, cursor.td, 0);
	      break;
	 case 'CALL':
	      imgdesc.style.display = 'none';
	      perfomance.push({ time: new Date(), process: 'User authorization: ' });
	      displayMainError('Loading');
	 case 'SIDEBAR':
	 case 'New Database':
	 case 'Database Configuration':
	      Hujax("view.php", FromController, { method: 'POST', body: JSON.stringify(input.data), headers: { 'Content-Type': 'application/json; charset=UTF-8'} });
	      break;
	 case 'Table':
	      perfomance.push({ time: new Date(), process: 'OV data server response: ' });
	      paramsOV = input.params;
	      drawMain(input.data, input.layout, input.attached);
	      break;
	 case 'Tree':
	      perfomance.push({ time: new Date(), process: 'OV data server response: ' });
	      DrawTree(input.data, input.rotate);
	      break;
	 case '':
	      break;
	 default:
	      input = { alert: `Unknown server message '${input.cmd}'!` };
	}
	
 if (input.sidebar)		drawSidebar(input.sidebar);
 if (input.log)			lg(input.log); 
 if (input.error !== undefined)	displayMainError(input.error);
 if (input.alert)		warning(input.alert);
 if (input.count)		IncreaseUnreadMessages(input.count.odid, input.count.ovid);
 if (input.cmd === 'Table' || input.cmd === 'Tree') setTimeout(0, perfomance.push({ time: new Date(), process: 'Layout and rendering: ' }));
}

function UploadDialog()
{
 let i, name, size, sizetext, list = '', uploadbtn = false;
 for (i = 0; i < browse.files.length; i++)
     {
      name = browse.files[i].name;
      size = browse.files[i].size;
      sizetext = ` (${(size/1024/1024).toFixed(2)}MB)`;
      if (name.charAt(0) == '+' || size > MAXFILESIZE)
	 {
	  list += '<span style="color: red;">' + `${i+1}. ` + name + `${sizetext}\n</span>`;
	 }
       else
	 {
	  list += `${i+1}. ` + name + `<span style="font: .8em/1 sans-serif; color: #aaa;">${sizetext}</span>\n`;
	  uploadbtn = true;
	 }
     }

 box = { title: 'Upload files',
	 dialog: { pad: {profile: {element: {head: `\nBrowse some files (count limit: ${MAXFILEUPLOADS}, file size limit: ${MAXFILESIZE/1024/1024}MB) to upload to the object element   \nNote: file names with the '+' as a 1st char cannot be uploaded!\n<span style="color: RGB(44,72,131); font-weight: bolder;">\nList of files to upload (${i} selected):\n\n</span>` + list}}} },
	 buttons: { BROWSE: {value: "BROWSE", call: "BROWSE", interactive: ''} },
	 flags: { data: box?.flags?.data ? box.flags.data : '', esc: "", style: "min-width: 400px; min-height: 200px; max-width: 1200px; max-height: 700px;"} };

 if (uploadbtn) box.buttons.UPLOAD = { value: "UPLOAD", call: "UPLOAD" };
 box.buttons.CANCEL = { value: "CANCEL", style: "background-color: red;" };
 ShowBox();
}

function DownloadDialog(list, cmd)
{
 box = { title: 'Download files',
	 dialog: { pad: {profile: {element: {head: `\nSelect files to download${cmd === 'UNLOADDIALOG' ? '/delete' : ''}:\n`, type: 'select-multiple', data: list, hel: 'Only one action via current dialog session can be perfomed' }}} },
	 buttons: { DOWNLOAD: {value: "DOWNLOAD", call: "DOWNLOAD" }},
	 flags: { data: box?.flags?.data ? box.flags.data : '', esc: "", style: "min-width: 400px; min-height: 200px; max-width: 1200px; max-height: 700px;"} };

 if (cmd === 'UNLOADDIALOG') box.buttons.DELETE = {value: "DELETE", call: "DELETE" };
 box.buttons.CANCEL = { value: "CANCEL", style: "background-color: red;" };
 ShowBox();
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

function CallController(data)
{
 let object, i;

 switch (cmd)
	{
	 case 'SEARCHPREV':
	 case 'SEARCHNEXT':
	      if (search.match.length < 2) return;
	      search.previndex = search.index;
	      cmd === 'SEARCHPREV' ? search.index -- : search.index ++;
	      if (search.index < 0) search.index = search.match.length - 1;
	       else if (search.index >= search.match.length) search.index = 0;
	      RegexRefresh();
	      return;
	 case 'BROWSE':
	      browse.click();
	      return;
	 case 'UPLOAD':
	      object = new FormData();
	      object.append('id', box.flags.data);
	      object.append('cmd', 'UPLOAD');
	      i = 0;
	      for (const file of browse.files)
		  {
		   if (file.name.charAt(0) !== '+') object.append('files[]', file, file.name);
		   i++;
		   if (i >= MAXFILEUPLOADS) break;
		  }
	      Hujax('file.php', FromController, { method: 'POST', body: object });
	      return;
	 case 'DOWNLOAD':
	      object = new FormData();
	      object.append('id', box.flags.data);
	      object.append('cmd', cmd);
	      i = -1;
	      for (let file of box.dialog.pad.profile.element.data.split('|'))
		  {
		   i++;
		   if (file.charAt(0) !== '+') continue;
		   object.append('fileindex', i);
		   Hujax('file.php', FromController, { method: 'POST', body: object });
		  }
	      return;
	 case 'DELETE':
	      object = new FormData();
	      object.append('id', box.flags.data);
	      object.append('cmd', cmd);
	      i = -1;
	      for (let file of box.dialog.pad.profile.element.data.split('|'))
		  {
		   i++;
		   if (file.charAt(0) === '+') object.append(i, '');
		  }
	      Hujax('file.php', FromController, { method: 'POST', body: object });
	      return;
	 case 'New Database':
	 case 'Task Manager':
	      object = { "cmd": cmd };
	      if (typeof data != 'string') object.data = data;
	      break;
	 case 'CALLHISTORY':
	      delete sidebar[ODid]['active'];
	      ODid = viewhistory[viewindex].ODid;
	      OVid = viewhistory[viewindex].OVid;
	      sidebar[ODid]['active'] = OVid;
	      drawSidebar(sidebar);
	      object = { cmd: 'CALL' };
	      perfomance = [{ time: new Date(), process: 'Start' }];
	      break;
	 case 'CALL':
	      if (ODid !== '' && (viewindex === -1 || viewhistory[viewindex].ODid !== ODid || viewhistory[viewindex].OVid !== OVid))
		 {
		  viewhistory[++viewindex] = {ODid: ODid, OVid: OVid};
	          viewhistory.splice(viewindex + 1);
		 }
	      perfomance = [{ time: new Date(), process: 'Start' }];
	 case 'Database Configuration':
	 case 'SIDEBAR':
	 case 'LOGIN':
	      object = { "cmd": cmd };
	      if (data != undefined) object.data = data;
	      break;
	 case 'Copy':
	      CopyBuffer();
	      break;
	 case 'Chart':
	      DrawChart(drag.x1, drag.y1, drag.x2, drag.y2);
	      break;
	 case 'Description':
	      let cell, msg = '', count = 1, greyspan = '<span style="color: #999;">';
	      if (cursor.td) // Cursor cell info
		 {
		  if (mainTable[cursor.y]?.[cursor.x]) cell = mainTable[cursor.y][cursor.x];
		  msg += '<span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Cursor cell</span>\n';
		  if (!cell) msg += 'Object id: undefined\nElement id: undefined';
		   else if (cell.oId === NEWOBJECTID && Number(cell.eId) > 0) msg += `Input new object data for element id: ${cell.eId}`;
		   else if (cell.oId === TITLEOBJECTID) msg += `Title for element id: ${cell.eId}`;
		   else if (cell.oId >= STARTOBJECTID) msg += `Object id: ${cell.oId}\nElement id: ${cell.eId}`;
		   else msg += `Object id: undefined\nElement id: virtual`;
		  msg += `\nPosition 'x': ${cursor.x}\nPosition 'y': ${cursor.y}\n\n`;
		 }
	      if (cell) // Object element info
		 {
		  let info = '';
	          if (cell.version) info = 'Object version: ' + (cell.version === '0' ? 'object has been deleted' : `${cell.version}\nActual version: ${cell.realobject ? 'yes' : 'no'}\n`);
		  if (cell.hint) info += `Element hint:\n<span style="color: #999;">${FromHTMLChars(cell.hint)}</span>\n`;
		  if (info) msg += '<span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Object element info</span>\n' + info + '\n';
		 }
	      if (true) // Database info
		 {
		  msg += '<span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Database</span>\n';
		  msg += `Object Database: ${OD}\nObject View${OV[0] === '_' ? ' (hidden from sidebar)' : ''}: ${OV} (${objectsOnThePage} objects)`;
		  msg += `\nVirtual elements: ${VirtualElements}`;
		  cell = '';
		  for (let param in paramsOV) cell += `\n  <span style="color: #999;">${count++}. ${param.substr(1).replace(/_/g, ' ')}: ${paramsOV[param]}</span>`;
		  if (cell) msg += `\nView input parameters:${cell}`;
		 }
	      if (true) // Table info
		 {
		  msg += '\n\n<span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">Table</span>\n';
		  msg += `Columns: ${mainTableWidth}\nRows: ${mainTableHeight}\nCells: ${mainTableWidth * mainTableHeight}`;
		  if (drag.x1 !== undefined && (drag.x1 != drag.x2 || drag.y1 != drag.y2))
		     {
		      count = new Set();
		      for (let y = Math.min(drag.y1, drag.y2); y <= Math.max(drag.y1, drag.y2); y++)
		      for (let x = Math.min(drag.x1, drag.x2); x <= Math.max(drag.x1, drag.x2); x++)
			  if (mainTable[y] && (cell = mainTable[y][x]) && cell.oId >= STARTOBJECTID && cell.realobject) count.add(cell.oId);
		      msg += `\nSelected area:\n  ${greyspan}Objects count: ${count.size}</span>`;
		      msg += `\n  ${greyspan}Width, cells: ${Math.abs(drag.x2 - drag.x1) + 1}</span>`;
		      msg += `\n  ${greyspan}Height, cells: ${Math.abs(drag.y2 - drag.y1) + 1}</span>`;
		     }
		 }
	      if (Array.isArray(perfomance) && perfomance.length > 1)
		 {
		  msg += '\n\n<span style="color: RGB(44,72,131); font-weight: bolder; font-size: larger;">View open process perfomance</span>';
		  for (let i = 1; i < perfomance.length; i ++)
		      msg += `\n${greyspan}${perfomance[i].process}</span>${ (perfomance[i].time - perfomance[i-1].time) / 1000} sec`;
		  msg += `\nOverall: ${ (perfomance[perfomance.length-1].time - perfomance[0].time) / 1000} sec`;
		 }
	      warning(msg, 'Description', false);
	      break;
	 case 'Help':
	      box = help;
	      ShowBox();
	      break;
	 case 'Add Object':
	      if (objectTable !== undefined) FillNewObjectArray(object = { "cmd": 'INIT', "data": {} }, NEWOBJECTID);
	      break;
	 case 'Clone Object':
	      if (objectTable !== undefined && mainTable[cursor.y]?.[cursor.x]?.realobject) FillNewObjectArray(object = { "cmd": 'INIT', "data": {} }, mainTable[cursor.y][cursor.x].oId);
	      break;
	 case 'Delete Object':
	      if (mainTable[cursor.y]?.[cursor.x]?.realobject) object = { "cmd": 'DELETEOBJECT', "oId": mainTable[cursor.y][cursor.x].oId };
	      break;
	 case 'View in a new tab':
	      const newwindow = window.open('about:blank', '_blank');
	      newwindow.document.write(mainDiv.innerHTML); // Should i call mywindow.focus() or mywindow.close()
	      newwindow.document.head.innerHTML = document.head.innerHTML;
	      newwindow.document.body.style.backgroundColor = window.getComputedStyle(mainDiv).getPropertyValue("background-color");
	      newwindow.document.body.style.overflow = 'auto';
	      break;
	 case 'CONFIRM':
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
	      user = OD = OV = ODid = OVid = OVtype = '';
	      viewindex = -1;
	      viewhistory = [];
	      cursor = {};
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

function FillNewObjectArray(object, oid)
{
 const clear = NEWOBJECTID === oid ? true : false;
 if (objectTable[oid = String(oid)] === undefined)
    {
     object = null;
     return;
    }
 oid = objectTable[oid];

 for (let eid in oid)
     {
      const x = oid[eid].x;
      const y = oid[eid].y;
      object['data'][eid] = mainTable[y][x]['data'];
      if (clear) mainTable[y][x]['data'] = mainTablediv.rows[y].cells[x].innerHTML = '';
     }
}

function displayMainError(errormsg, reset = true)
{
 clearTimeout(loadTimerId);

 if (errormsg.substr(0, 7) === 'Loading')
    {
     loadTimerId = setTimeout(displayMainError, 500, errormsg === 'Loading...' ? 'Loading' : errormsg + '.');
     errormsg = errormsg.replace(/Loading/, '').replace(/./g, '&nbsp;') + errormsg;
    }
 mainDiv.innerHTML = '<h1>' + errormsg + '</h1>';

 if (reset) OVtype = '';
}

function CellBorderToggleSelect(oldCell, newCell, focus = FOCUS_VERTICAL | FOCUS_HORIZONTAL)
{
 mainDiv.focus();
 if (oldCell)
    {
     oldCell.style.outline = "none";
     oldCell.style.boxShadow = "none";
    }
 if (newCell)
    {
     // Outline cursor cell
     if (uiProfile['main field table cursor cell']['outline'] != undefined) newCell.style.outline = uiProfile['main field table cursor cell']['outline'];
     if (uiProfile['main field table cursor cell']['shadow'] != undefined) newCell.style.boxShadow = uiProfile['main field table cursor cell']['shadow'];
     // Fill cursor object
     cursor.td = newCell;
     cursor.x = newCell.cellIndex;
     cursor.y = newCell.parentNode.rowIndex;
     cursor.oId = cursor.eId = 0;
     if (mainTable[cursor.y]?.[cursor.x])
	{
         cursor.oId = mainTable[cursor.y][cursor.x].oId;
         cursor.eId = mainTable[cursor.y][cursor.x].eId;
	}
     // Set cursor visible on horizontal
     if (focus & FOCUS_HORIZONTAL)
     if (newCell.offsetLeft <= mainDiv.scrollLeft + 1)
	{
	 mainDiv.scrollLeft = (focus & FOCUS_EDGE) ? newCell.offsetLeft - 1 + mainDiv.offsetWidth : newCell.offsetLeft - 1
        }
      else if (newCell.offsetLeft + newCell.offsetWidth > mainDiv.scrollLeft + mainDiv.offsetWidth)
        {
	 mainDiv.scrollLeft = (focus & FOCUS_EDGE) ? Math.max(newCell.offsetLeft, newCell.offsetLeft + newCell.offsetWidth - mainDiv.offsetWidth + 11) : Math.min(newCell.offsetLeft, newCell.offsetLeft + newCell.offsetWidth - mainDiv.offsetWidth + 11);
        }
     // Set cursor visible on horizontal
     if (focus & FOCUS_VERTICAL)
     if (newCell.offsetTop <= mainDiv.scrollTop + 1)
        {
         mainDiv.scrollTop = (focus & FOCUS_EDGE) ? newCell.offsetTop - 1  + mainDiv.offsetHeight : newCell.offsetTop - 1;
        }
      else if (newCell.offsetTop + newCell.offsetHeight > mainDiv.scrollTop + mainDiv.offsetHeight)
        {
         mainDiv.scrollTop = (focus & FOCUS_EDGE) ? Math.max(newCell.offsetTop, newCell.offsetTop + newCell.offsetHeight - mainDiv.offsetHeight + 11) : Math.min(newCell.offsetTop, newCell.offsetTop + newCell.offsetHeight - mainDiv.offsetHeight + 11);
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

function ShowBox(scrollLeft, scrollTop)
{
 let inner = getInnerDialog();
 if (!inner) // No content? Hide dialog and return;
    {
     HideBox();
     return;
    }

 HideContextmenu();
 if (typeof box.title === 'string') inner = '<div class="title">' + ToHTMLChars(box.title) + '</div>' + inner; // Add title
 inner += '<div class="footer">'; // Add 'footer' div and buttons to (if exist)
 for (let button in box.buttons)
     {
      if (box.buttons[button]['call']) box.cmd = box.buttons[button]['call'];
      if (box.buttons[button]['value'])
	 {
	  inner += '<div class="button" data-button="' + button + '"';
	  if (box.buttons[button]['style']) inner += ' style="' + AdjustAttribute(box.buttons[button]['style']) + '"';
	  inner += '>' + EncodeHTMLSpecialChars(box.buttons[button]['value']) + '</div>';
	 }
      if (box.buttons[button]['timer'])
	 {
	  clearTimeout(buttonTimerId);
	  buttonTimerId = setTimeout(BoxApply, box.buttons[button]['timer'], button);
	 }
     }
 if (!box.cmd) box.cmd = 'CONFIRMDIALOG';
 boxDiv.innerHTML = inner + '</div>'; // Finish 'footer' div

 boxDiv.style.left = Math.trunc((document.body.clientWidth - boxDiv.offsetWidth)*100/(2*document.body.clientWidth)) + "%"; // Calculate left box position
 boxDiv.style.top = Math.trunc((document.body.offsetHeight - boxDiv.offsetHeight)*100/(2*document.body.offsetHeight)) + "%"; // Calculate top box position
 boxDiv.className = 'box ' + uiProfile["dialog box"]["effect"] + 'show'; // Show box div

 if (uiProfile["dialog box"]["filter"] && box.flags.nofilter === undefined)
    sidebarDiv.style.filter = mainDiv.style.filter = uiProfile["dialog box"]["filter"]; // Apply filters if exist
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
 if (count > 1 || box.flags.showpad != undefined)
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
 // No match - assign first profile for default
 if (typeof data === 'string') box.flags.profile = data;
 // Profiles count more than one? Creating profile select DOM element.
 if (count > 1 || box.flags.profilehead?.[box.flags.pad] != undefined)
    {
     // Add profile head
     if (box.flags.profilehead != undefined && box.flags.profilehead[box.flags.pad] != undefined) inner += '<pre class="element-headers">' + box.flags.profilehead[box.flags.pad] + '</pre>';
     // In case of default first profile set zero value to use as a select attribute
     if (typeof data === 'string') data = 0;
     // Add header, select block and divider
     inner += '<div class="select" type="select-profile"><div value="' + data + '">' + AdjustAttribute(box.flags.profile) + '</div></div><div class="divider"></div>';
    }

 //------------------Parsing interface element in box.dialog.<current pad>.<current profile>------------------
 for (let name in box.dialog[box.flags.pad][box.flags.profile])
     {
      element = box.dialog[box.flags.pad][box.flags.profile][name];
      // Display element head
      if (element.head === undefined || typeof element.head !== "string")
	 {
	  inner += '<div></div>';
	 }
       else
	 {
	  inner += '<pre class="element-headers"';
	  if (element.style && typeof element.style === 'string') inner += ` style="${element.style}"`;
	  inner += '>' + ToHTMLChars(element.head);
	  if (element.help && typeof element.help == "string") inner += ' <span name="' + name + '" class="help-icon"> ? </span>';
	  inner += '</pre>';
	 }
      // Filling interface element data, leave empty string in case of undefined
      data = (typeof element.data === 'string' || typeof element.data === 'object') ? element.data : '';

      switch (element.type)
	     {
	      case 'table':
		   if (typeof data !== 'object') break;
		   let row, cell;
		   inner += `<table class="boxtable"><tbody>`;
		   for (row in data)
		       {
			inner += '<tr>';
			for (cell in data[row])
			    {
			     inner += '<td class="boxtablecell';
			     data[row][cell].call != undefined ? inner += ' boxtablecellpush" data-button="' + cell + '"' : inner += '"';
			     if (data[row][cell].style) inner += ` style="${AdjustAttribute(data[row][cell].style)}"`;
			     inner += '>' + EncodeHTMLSpecialChars(data[row][cell].value) + '</td>';
			    }
			inner += '</tr>';
		       }
		   inner += '</tbody></table>';
		   break;
	      case 'select-multiple':
		   if (typeof data !== 'string' || !data) break;
		   inner += `<div class="select" name="${name}" type="select-multiple">`;
		   for (data of data.split('|')) if (data)
		       {
			pos = data.search(/[^\+]/);
			inner += pos > 0 ? `<div class="selected">${data.substr(pos)}</div>` : `<div>${data}</div>`;
		       }
		   inner += '</div>';
		   break;
	      case 'select-one':
		   if (typeof data !== 'string' || !data) break;
		   count = 0;
		   data = element.data = setOptionSelected(data);
		   for (data of data.split('|'))	// Handle all option divided by '|'
		       {
			if (data[0] == '+')		// The option is selected (first char is '+')? Add it to the dialog interface
			   {
			    inner += `<div class="select" name="${name}" type="select-one"><div value="${count}">${AdjustAttribute(data.substr(1))}</div></div>`;
			    break;
			   }
			count ++;
		       }
	    	   break;
	      case 'checkbox':
	      case 'radio':
		   if (typeof data !== 'string' || !data) break;
		   element.readonly != undefined ? readonly = ' disabled' : readonly = '';
		   for (data of data.split('|')) if (data != '')
		       {
			checked = (count = data.search(/[^\+]/)) > 0 ? ' checked' : '';
			inner += `<input type="${element.type}" class="${element.type}" name="${name}"${checked}${readonly}><label for="${name}">${data.substr(count)}</label>`;
		       }
		   break;
	      case 'password':
	      case 'text':
		   if (element.label) inner += `<label for="${name}" class="element-headers">${element.label}</label>`;
		   readonly = element.readonly !== undefined ? ' readonly' : '';
		   inner += '<input type="' + element.type + '" class="' + element.type + '" name="' + name + '" value="' + AdjustAttribute(data) + '"' + readonly + '>';
		   break;
	      case 'textarea':
		   readonly = element.readonly !== undefined ? ' readonly' : '';
		   inner += '<textarea type="' + element.type + '" class="textarea" name="' + name + '"' + readonly + '>' + AdjustAttribute(data) + '</textarea>';
		   break;
	     }
      if (element.line != undefined) inner += '<div class="divider"></div>';
     }

 if (inner != '')
    {
     data = '';
     if (box.flags?.style && typeof box.flags.style === 'string') data = ' style ="' + AdjustAttribute(box.flags.style) + '"';
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
 let el, value;

 boxDiv.querySelectorAll('input, .select, textarea, .boxtable').forEach(function(element)
	{
	 if (element.attributes === undefined || element.attributes.name === undefined) return;
	 value = element.attributes.name.value;
	 el = box.dialog[box.flags.pad][box.flags.profile][value];
	 switch (element.attributes.type.value)
		{
		 case 'select-multiple':
		      el.data = '';
		      element.querySelectorAll('div').forEach(function(option)
			{
			 if (option.classList.contains('selected')) el.data += '+';
			 el.data += option.innerHTML + '|';
			});
		      if (el.data.length > 0) el.data = el.data.slice(0, -1);
		      break;
		 case 'checkbox':
		 case 'radio':
		      if (init[value] === undefined) init[value] = el.data = '';
		      if (element.checked) el.data += '+';
		      el.data += element.nextSibling.innerHTML + '|';
		      break;
		 case 'text':
		 case 'textarea':
		 case 'password':
		      el.data = element.value;
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
 return result;									// Return result string with last divided char '|'
}

function HideBox()
{
 clearTimeout(buttonTimerId);
 if (!box) return;

 if (uiProfile["dialog box"]["effect"] != 'none') boxDiv.removeEventListener('transitionend', SetFirstDialogElementFocus);
 boxDiv.className = 'box ' + uiProfile["dialog box"]["effect"] + 'hide';
 expandedDiv.className = 'select expanded ' + uiProfile["dialog box select"]["effect"] + 'hide';
 mainDiv.style.filter = 'none';
 sidebarDiv.style.filter = 'none';

 if (search.error === false)
    {
     clearTimeout(searchTimerId);
     RegexInit();
     search.error = true;
     setTimeout(RegexSearch, 0);
    }

 box = null;
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

function HideContextmenu()
{
 if (!contextmenu) return;
 contextmenuDiv.className = 'contextmenu ' + uiProfile["context menu"]["effect"] + 'hide';
 contextmenu = null;
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
 if (!hint) return;
 clearTimeout(tooltipTimerId);
 hintDiv.className = 'hint ' + uiProfile["hint"]["effect"] + 'hide';
 hint = null;
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

function warning(text, title, log = true)
{
 if (!text || typeof text != 'string') return;
 if (typeof title != 'string') title = 'Warning';
 box = { title: title, dialog: {pad: {profile: {element: {style: HELPHEADSTYLE, head: '\n' + text}}}}, buttons: {OK: {value: "    OK    "}}, flags: {esc: "", style: "min-width: 500px; min-height: 65px; max-width: 1500px; max-height: 700px;"} };
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

 // Define css classes attribute string for all table cell types
 titlecellclass = isObjectEmpty(uiProfile["main field table title cell"], 'target') ? '' : 'titlecell';
 newobjectcellclass = isObjectEmpty(uiProfile["main field table newobject cell"], 'target') ? '' : 'newobjectcell';
 datacellclass = isObjectEmpty(uiProfile["main field table data cell"], 'target') ? '' : 'datacell';
 undefinedcellclass = isObjectEmpty(uiProfile["main field table undefined cell"], 'target') ? '' : ' class="undefinedcell"';

 // Output uiProfile array to te console to use it as a default customization configuration
 //lg("$uiProfile = json_decode('" + JSON.stringify(uiProfile).replace(/'/g, "\\'") + "', true);");
}

function ConfirmEditableContent(addobject)
{
 cursor.td.contentEditable = NOTEDITABLE;
 mainTable[cursor.y][cursor.x].data = FromHTMLChars(cursor.td.innerHTML);
 cursor.td.innerHTML = ToHTMLChars(mainTable[cursor.y][cursor.x].data);

 if (mainTable[cursor.y][cursor.x].oId === NEWOBJECTID)
    {
     if (!addobject) return;
     cmd = 'Add Object';
     CallController();
    }
  else
    {
     cmd = 'CONFIRM';
     CallController(mainTable[cursor.y][cursor.x].data);
    }
}

function MakeCursorContentEditable(data)
{
 // Make cursor cell editable
 cursor.td.focus();
 try { cursor.td.contentEditable = EDITABLE; }
 catch { cursor.td.contentEditable = (EDITABLE = 'true'); } // Fucking FF doesn't support 'plaintext' contentEditable type

 // Adjust the content
 if (typeof data !== 'string') data = mainTable[cursor.y][cursor.x].data;
 cursor.td.innerHTML = EncodeHTMLSpecialChars(data);

 // Set cursor at the end of the content
 range.selectNodeContents(cursor.td);
 range.collapse(false);
 selection.removeAllRanges();
 selection.addRange(range);
}

////////////
function DecodeHTMLSpecialChars(string)
{
 if (typeof string !== 'string' || !string) return '';
 for (let i = 0; i < HTMLSPECIALCHARS.length; i ++) string = string.replace(new RegExp(HTMLSPECIALCHARS[i], 'g'), HTMLUSUALCHARS[i]);
 return string;
}

function EncodeHTMLSpecialChars(string)
{
 if (typeof string !== 'string' || !string) return '';
 for (let i = 0; i < HTMLSPECIALCHARS.length; i ++) string = string.replace(new RegExp(HTMLUSUALCHARS[i], 'g'), HTMLSPECIALCHARS[i]);
 return string;
}
////////////

function FromHTMLChars(string)
{
 return DecodeHTMLSpecialChars(string);
}

function ToHTMLChars(string)
{
 if (typeof string !== 'string' || !string) return '';
 let result, newstring = '';

 while (result = allowedtagsregexp.exec(string))
       {
	newstring += EncodeHTMLSpecialChars(string.substr(0, result.index)) + result[0];
	string = string.substr(result.index + result[0].length);
       }

 return newstring + EncodeHTMLSpecialChars(string);
}

function RegexGetHighlightedString(matches, allowedtagsmatches, innerText, x, y)
{
 let index = 0, length = 0, newstring = '';

 for (let tag of allowedtagsmatches) // Get through all found span tags, start: index; end: tag[1] - length;
     {
      let j = search.match.length;
      for (let i = 0; i < matches.length; i++) if (matches[i][1] >= index && matches[i][1] < tag[1] - length) // Match start index is in the range
	  {
	   if (!search.match[j]) search.match[j] = [];
	   if (matches[i][1] + matches[i][0].length > tag[1] - length) // Match end index is out of range
	      {
	       newstring += innerText.substr(index, matches[i][1] - index);
	       search.match[j].push({x: x, y: y, pos: newstring.length + 18}); ///
	       newstring += SPANHIGHLIGHT + innerText.substr(matches[i][1], tag[1] - length - matches[i][1]).replace(/\n/g, ' \n') + '</span>';
	       matches[i][0] = matches[i][0].substr(tag[1] - length - matches[i][1]);
	       matches[i][1] = tag[1] - length;
	       index = tag[1] - length;
	       break;
	      }
	    else // Match end index is in range
	      {
	       newstring += innerText.substr(index, matches[i][1] - index);
	       search.match[j++].push({x: x, y: y, pos: newstring.length + 18}); ///
	       newstring += SPANHIGHLIGHT + innerText.substr(matches[i][1], matches[i][0].length).replace(/\n/g, ' \n') + '</span>';
	       index = matches[i][1] + matches[i][0].length;
	      }
	  }
      newstring += innerText.substr(index, tag[1] - length - index);
      index = tag[1] - length;
      length += tag[0].length;
      newstring += tag[0];
     }
 return newstring;
}

function RegexSearch()
{
 let x, y, count = 100;

 while (search.y < search.y2)
       {
	if (mainTable[y = search.y]) while (search.x < search.x2)
	   {
	    // Current cell is not empty?
	    if (!mainTable[y][x = search.x] || !mainTable[y][x].data)
	       {
		search.x++;
		continue;
	       }

	    // Init vars
	    const cell = mainTablediv.rows[y].cells[x];
	    let innerText = cell.innerText, matches, allowedtagsmatches, string = mainTable[y][x].data;
	    count --;

	    // See old cell highlighting and remove it if exist
	    if (mainTable[y][x].matched !== undefined)
	       {
		delete mainTable[y][x].matched;
		cell.innerHTML = ToHTMLChars(string);
		innerText = cell.innerText;
		count -= 10;
	       }

	    // Search current cell on user input regexp and make highlighted cell in case of successful
	    matches = (search.error || !search.input.value) ? [] : Array.from(innerText.matchAll(search.regexp), m => [m[0], m.index])
	    if (matches.length)
	       {
		// Search span tags
		allowedtagsmatches = Array.from(string.matchAll(allowedtagsregexpg), m => [m[0], m.index]) // Make allowed tags match array
		allowedtagsmatches.push(['', string.length]); // and add fake element to finish the string
		cell.innerHTML = ToHTMLChars(mainTable[y][x].matched = RegexGetHighlightedString(matches, allowedtagsmatches, innerText, x, y));
		count -= 10;
	       }

	    // Cell is processed, goto next cell
	    search.x++;

	    // Count is exceeded? Set next search via setTimeout
	    if (count < 1)
	       {
		searchTimerId = setTimeout(RegexSearch, 0);
		RegexRefresh();
		return;
	       }
	   }
      search.y++;
      search.x = search.x1;
     }
 RegexRefresh(true);
}

function RegexOninput(caseinput)
{
 if (search.error && caseinput) return; // Incorrect regex with case-sensitive button change? Return with no action
 RegexInit();

 try { search.regexp = RegExp(search.input.value, `g${search.casesensitive.checked ? '' : 'i'}`); }
 catch { search.error = true; RegexRefresh(true); }

 clearTimeout(searchTimerId);
 searchTimerId = setTimeout(RegexSearch, 350);
}

function RegexRefresh(searchend)
{
 let string, newcell;
 const regexpsearchhint = '<span name="element1" class="help-icon"> ? </span>';

 // Unhighlight previous match
 if (search.previndex !== -1 && search.previndex !== search.index)
    {
     string = mainTable[search.match[search.previndex][0].y][search.match[search.previndex][0].x].matched; // and its matched srting with non active yellow highlighting
     for (let j of search.match[search.previndex]) string = string.substr(0, j.pos) + 'b' + string.substr(j.pos + 1) // Is it needable?
     cursor.td.innerHTML = ToHTMLChars(string);
    }

 // No search index is selected by the user and any match does exist? Set start index
 if (search.index === -1 && search.match.length) search.index = 0;

 // Refresh search dialog
 if (search.index === -1) // No match found
    {
     search.title.innerHTML = search.input.value ? 'Searching ' + String(Math.min(100, (100 * (((search.x2 - search.x1)*(search.y - search.y1)) + search.x)/((search.x2 - search.x1)*(search.y2 - search.y1)))).toFixed(2)) + '%' : REGEXSEARCHTITLE;
     search.header.innerHTML = `<br>Enter regular expression to search: ${regexpsearchhint}`; // Make default header
     searchend && search.input.value !== '' ? search.input.classList.add('matchn') : search.input.classList.remove('matchn'); // Set red background of input for non empty input value
    }
  else
    {
     search.input.classList.remove('matchn'); // Remove no-match red background
     search.title.innerHTML = 'Searching ' + String(Math.min(100, (100 * (((search.x2 - search.x1)*(search.y - search.y1)) + search.x)/((search.x2 - search.x1)*(search.y2 - search.y1)))).toFixed(2)) + '%';
     search.header.innerHTML = `<br>Enter regular expression to search (${search.index + 1} of ${search.match.length}): ${regexpsearchhint}`; // Set matches count
    }

 // Highlight new match in case of search index change
 if (search.index !== -1 && search.previndex !== search.index)
    {
     search.previndex = search.index;
     newcell = mainTablediv.rows[search.match[search.index][0].y].cells[search.match[search.index][0].x];
     string = mainTable[search.match[search.index][0].y][search.match[search.index][0].x].matched;
     for (let j of search.match[search.index]) string = string.substr(0, j.pos) + 'a' + string.substr(j.pos + 1)
     newcell.innerHTML = ToHTMLChars(string);
     CellBorderToggleSelect(cursor.td, newcell); // Put cursor on a matched position
    }

 // Focus search dialog input
 search.input.focus();
}

function RegexInit(firstinit)
{
 // Init these regex search vars anyway
 search.match = [];
 search.index = -1;
 search.previndex = -1;
 search.error = false;
 search.x = search.x1;
 search.y = search.y1;
 if (!firstinit) return;

 // Init these regex search vars only once
 search.input = boxDiv.querySelector('input');
 search.casesensitive = boxDiv.querySelector('.checkbox');
 search.header = boxDiv.querySelector('pre');
 search.title = boxDiv.querySelector('.title');
 search.input.oninput = () => { RegexOninput(); };
 search.casesensitive.oninput = () => { RegexOninput(true); };

 // Define search area and start position
 if (drag.x1 !== undefined && (drag.x1 != drag.x2 || drag.y1 != drag.y2))
    {
     search.x1 = Math.min(drag.x1, drag.x2);
     search.y1 = Math.min(drag.y1, drag.y2);
     search.x2 = Math.max(drag.x1, drag.x2) + 1;
     search.y2 = Math.max(drag.y1, drag.y2) + 1;
    }
  else
    {
     search.x1 = search.y1 = 0;
     search.x2 = mainTableWidth;
     search.y2 = mainTableHeight;
    }
 search.x = search.x1;
 search.y = search.y1;
}

function AdjustAttribute(string)
{
 if (typeof string !== 'string' || !string) return '';
 return string.replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
 //return string.trim().replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
 //return string.trim().replace(/<|>|"/g, '');
}
