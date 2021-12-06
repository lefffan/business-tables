
function mousemoveEventHandler(event)
{
 if (box)
    {
     if (drag.element !== boxDiv) return;
     drag.left = String(event.clientX - drag.dispx) + 'px';
     drag.top = String(event.clientY - drag.dispy) + 'px';
     window.requestAnimationFrame(() => { boxDiv.style.left = drag.left; boxDiv.style.top = drag.top; });
     return;
    }

 if (contextmenu)
    {
     return;
    }

 if (OVtype === 'Table')
    {
     const target = IsTableTemplateCell(event.target);
     if (drag.element) // Process table selecting
	{
         if (target)
	    {
	     UnSelectTableArea(drag.x1, drag.y1, drag.x2, drag.y2);
	     SelectTableArea(drag.x1, drag.y1, drag.x2 = target.cellIndex, drag.y2 = target.parentNode.rowIndex);
	    }
	  else
	    {
	     // Add non table area selecting
	    }
	 return;
	}
     let x, y;
     if (target && mainTable[y = target.parentNode.rowIndex]?.[x = target.cellIndex]?.hint) // Process table cell hint event
	{
	 if (hint && hint.x === x && hint.y === y) return; // Hint already exist for the mouse cursor cell
	 hint = { x: x, y: y };
	 clearTimeout(tooltipTimerId);
	 tooltipTimerId = setTimeout(() => ShowHint(mainTable[y][x].hint, getAbsoluteX(target, 'middle'), getAbsoluteY(target, 'end')), uiProfile['hint']['mouseover hint timer in msec']);
	 return;
	}
     HideHint();
     return;
    }
}

function dblclickEventHandler(event)
{
 if (box)
    {
     return;
    }

 if (OVtype === 'Gallery')
    {
     imgwrapper.style.height = imgwrapper.style.height === 'auto' ? '100%' : 'auto';
     imgwrapper.style.width = imgwrapper.style.width === 'auto' ? '100%' : 'auto';
     return;
    }

 if (OVtype === 'Table')
    {
     const target = IsTableTemplateCell(event.target);
     if (target && target.contentEditable != EDITABLE && mainTable[cursor.y]?.[cursor.x]?.realobject && Number(cursor.eId) > 0)
	{
	 cmd = 'DBLCLICK';
	 CallController({metakey: event.metaKey, altkey: event.altKey, shiftkey: event.shiftKey, ctrlkey: event.ctrlKey});
	}
     return;
    }
}

function mouseupEventHandler(event)
{
 HideHint();

 // Return for non left-button event, 0 - no mouse button pushed, 1 - left button, 2 - middle button, 3 - right (context) button
 if (event.which != 1)
    {
     return;
    }

 // Release drag element (box title or table cell)
 drag.element = null;

 // Dialog box is on? Process its mouse left button release
 if (box)
    {
     BoxEventHandler(event);
     return;
    }
}

function mousedownEventHandler(event)
{
 HideHint();

 // Return for non left-button event, 0 - no mouse button pushed, 1 - left button, 2 - middle button, 3 - right (context) button
 if (event.which != 1)
    {
     return;
    }

 // Dialog box is on? Process its mouse left button down
 if (box)
    {
     if (event.target.classList.contains('title'))
	{
	 drag.element = boxDiv;
	 drag.dispx = event.clientX - boxDiv.offsetLeft;
	 drag.dispy = event.clientY - boxDiv.offsetTop;
	 expandedDiv.className = 'select expanded ' + uiProfile["dialog box select"]["effect"] + 'hide';
	 return;
	}
     BoxEventHandler(event);
     return;
    }

 // Context menu is on? Process its events
 if (contextmenu)
    {
     // Mouse click on grey menu item or on context menu? Do nothing and return
     if (event.target.classList.contains('greyContextMenuItem') || event.target.classList.contains('contextmenu'))
	{
	 return;
	}
     // Mouse click on context menu item? Call controller with appropriate context menu item as a command.
     if (event.target.classList.contains('contextmenuItems'))
	{
	 cmd = event.target.innerHTML;
	 CallController(contextmenu.data);
	 HideContextmenu();
	 return;
	}
     // Click is out of context menu, hide it
     HideContextmenu();
    }

 // Prevent default behaviour to exclude default drag operation
 if (event.target === document.body)
    {
     event.preventDefault();
     return;
    }

 // Mouse clilck out of main field content editable table cell? Save cell inner for a new element, otherwise send it to the controller
 if (cursor.td?.contentEditable === EDITABLE && cursor.td != event.target)
    {
     if (mainTable[cursor.y][cursor.x].oId === NEWOBJECTID)
	{
         mainTable[cursor.y][cursor.x].data = htmlCharsConvert(cursor.td.innerHTML);
         cursor.td.innerHTML = toHTMLCharsConvert(mainTable[cursor.y][cursor.x].data);
	}
      else
	{
         cmd = 'CONFIRM';
         CallController(htmlCharsConvert(cursor.td.innerHTML));
	}
     cursor.td.contentEditable = NOTEDITABLE;
    }

 // Target is an element the mousedown event occured on, so adjust it to select proper element
 let target = event.target;
 if (target.classList.contains('wrap')) target = target.nextSibling;
  else if (target.classList.contains('changescount')) target = target.parentNode;

 // OD item mouse click? Refresh sidebar and wrap/unwrap database view list
 if (target.classList.contains('sidebar-od'))
    {
     if (Object.keys(sidebar[target.dataset.odid]['view']).length > 0)
	sidebar[target.dataset.odid]['wrap'] = !sidebar[target.dataset.odid]['wrap'];
     cmd = 'SIDEBAR';
     CallController();
     return;
    }

 // OV item mouse click? Open OV in main field
 if (target.classList.contains('sidebar-ov'))
    {
     if (ODid != target.dataset.odid || OVid != target.dataset.ovid)
        {
	 if (sidebar[ODid]?.['active']) delete sidebar[ODid]['active'];
         sidebar[target.dataset.odid]['active'] = target.dataset.ovid;
	 drawSidebar(sidebar);
	}
     ODid = target.dataset.odid;
     OVid = target.dataset.ovid;
     OD = target.dataset.od;
     OV = target.dataset.ov;
     cmd = 'CALL';
     displayMainError('Loading...');
     CallController();
     return;
    }

 // Table template view mouse click event?
 if (OVtype === 'Table')
    {
     if (!(target = IsTableTemplateCell(target))) return;
     ResetUnreadMessages(); // Reset the counter
     CellBorderToggleSelect(cursor.td, target); // Highlight cursor

     if (drag.x1 !== undefined) // Unselect area if selected
	{
	 UnSelectTableArea(drag.x1, drag.y1, drag.x2, drag.y2);
	 delete drag.x1;
	}

     if (cursor.td.contentEditable != EDITABLE && !isNaN(cursor.eId) && cursor.oId === NEWOBJECTID)
	{
	 MakeCursorContentEditable(mainTable[cursor.y][cursor.x].data); // Set new object input editable
	 return;
	}

     drag.element = target; // Set new drag area and its start coordinates below
     drag.x1 = drag.x2 = cursor.x;
     drag.y1 = drag.y2 = cursor.y;
     return;
    }
}

function contextmenuEventHandler(event)
{
 let target = event.target;
 HideHint();

 // Prevent default context menu while dialog box up, right mouse click or context key press on already existed context menu
 // event.which values: 0 - no mouse button pushed, 1 - left button, 2 - middle button, 3 - right (context) button
 if (box || target == contextmenuDiv || target.classList.contains('contextmenuItems') || (contextmenu && event.which === 0))
    {
     event.preventDefault();
     return;
    }

 // Is cursor element content editable? Apply changes in case of no event.target match
 if (cursor.td?.contentEditable === EDITABLE)
    {
     if (cursor.td === target) return;
     cursor.td.contentEditable = NOTEDITABLE;
     if (mainTable[cursor.y][cursor.x].oId === NEWOBJECTID)
	{
	 mainTable[cursor.y][cursor.x].data = htmlCharsConvert(cursor.td.innerHTML);
	}
      else
	{
	 cmd = 'CONFIRM';
	 CallController(htmlCharsConvert(cursor.td.innerHTML));
	}
     // Main field table cell click?
     if (IsTableTemplateCell(target)) CellBorderToggleSelect(cursor.td, target);
    }

 // Target is an element the context event occured on, so adjust it to select proper element
 if (target.classList.contains('wrap')) target = target.nextSibling; // Wrap icon click? Use next sibling (OD/OV) as a target
  else if (target.classList.contains('changescount')) target = target.parentNode; // Footnote count click? Use parent node (OD/OV) as a target
  else if (cursor.td && event.which === 0) target = cursor.td; // Context key on active cursor? Use cursor.td
  else if (!(target = IsTableTemplateCell(target))) target = event.target; // Adjust to the table template cell, otherwise leave it unchanged

 // Context event on OD
 if (target.classList.contains('sidebar-od'))
    {
     DrawContext(ACTIVEITEM + 'New Database</div>' + ACTIVEITEM + 'Database Configuration</div>', target, event);
     return;
    }

 // Context event on OV
 if (target.classList.contains('sidebar-ov') || target === sidebarDiv) 
    {
     DrawContext(ACTIVEITEM + 'New Database</div>' + GREYITEM + 'Database Configuration</div>', target, event);
     return;
    }

 // Context event on main div with any OV displayed or on main table div in case of table edge click!
 if (OVtype === 'Table' && (target === mainDiv || target === mainTablediv))
    {
     DrawContext(ACTIVEITEM + 'Add Object</div>' + GREYITEM + 'Delete Object</div>' + ACTIVEITEM + 'Description</div>', target, event);
     return;
    }

 // Context event on table template cell
 if (OVtype === 'Table' && IsTableTemplateCell(target))
    {
     const chart = GetChartItem(target);
     if (!chart) CellBorderToggleSelect(cursor.td, target);
     const DELETEITEM = mainTable[cursor.y]?.[cursor.x]?.realobject ? ACTIVEITEM + 'Delete Object</div>' : GREYITEM + 'Delete Object</div>';
     DrawContext(ACTIVEITEM + 'Add Object</div>' + DELETEITEM + ACTIVEITEM + 'Description</div>' + ACTIVEITEM + 'Copy</div>' + chart, target, event);
     return;
    }

 // Context event tree template
 if (OVtype === 'Tree' && (target === mainDiv || target === mainTablediv || target.tagName === 'TD'))
    {
     DrawContext(GREYITEM + 'Hide Object</div>', target, event);
     return;
    }

 // COntext event on main div with unknown template or error message on
 if (target === mainDiv)
    {
     DrawContext('', target, event);
     return;
    }

 // Hide context menu for default
 HideContextmenu();
}

function GetChartItem(target)
{
 if (drag.x1 === undefined) return '';
 const x = target.cellIndex, y = target.parentNode.rowIndex;

 // Selected area does exist and click is in the selected area
 if (x >= Math.min(drag.x1, drag.x2) && x <= Math.max(drag.x1, drag.x2) && y >= Math.min(drag.y1, drag.y2) && y <= Math.max(drag.y1, drag.y2)) return ACTIVEITEM + 'Chart</div>';

 UnSelectTableArea(drag.x1, drag.y1, drag.x2, drag.y2);
 delete drag.x1;
 return '';
}

function DrawContext(inner, target, event)
{
 // Prevent default context and init context menu object
 event.preventDefault();
 contextmenu = { item : null };
 if (target.dataset?.odid) contextmenu.data = target.dataset.odid;

 // Add default items such as 'Task Manager', 'Help' and 'Logout'
 inner += BASECONTEXT;
 user.length > CONTEXTITEMUSERNAMEMAXCHAR ? inner += ACTIVEITEM + 'Logout '+ user.substr(0, CONTEXTITEMUSERNAMEMAXCHAR - 2) + '..</div>' : inner += ACTIVEITEM + 'Logout '+ user + '</div>';
 contextmenuDiv.innerHTML = inner;

 // Calculate context menu div left/top position for key event (which = 0)
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
	 contextmenuDiv.style.left = (mainDiv.offsetLeft + mainDiv.offsetWidth - contextmenuDiv.offsetWidth) + 'px';
	 contextmenuDiv.style.top = (mainDiv.offsetTop + mainDiv.offsetHeight - contextmenuDiv.offsetHeight) + 'px';
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
}

function contextFitMainDiv(x, y)
{
 if (mainDiv.offsetWidth < x + contextmenuDiv.offsetWidth || mainDiv.offsetHeight < y + contextmenuDiv.offsetHeight || x < 0 || y < 0) return false;
 contextmenuDiv.style.left = mainDiv.offsetLeft + x + "px";
 contextmenuDiv.style.top = mainDiv.offsetTop + y + "px";
 return true;
}

function IsTableTemplateCell(element)
{
 if (OVtype !== 'Table') return;
 let target = element.tagName === 'TD' ? element : element.parentNode;
 if (target.tagName !== 'TD') return;

 list = target.classList;
 if (list.contains('datacell') || list.contains('titlecell') || list.contains('newobjectcell') || list.contains('undefinedcell')) return target;
}

function boxEventHandler(event)
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

 // Pass dialog box table cell event to the controller
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
