
function keydownEventHandler(event)
{
 if (box)
    {
     switch (event.keyCode)
	    {
	     case 27: // Esc
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
	     case 13: // Enter
		  if (event.target.tagName === 'INPUT' && (event.target.type === 'text' || event.target.type === 'password'))
		     BoxApply(SeekObjJSONProp(box.buttons, 'enterkey', null));
		  break;
	    }
     return;
    }

 if (contextmenu)
    {
     switch (event.keyCode)
	    {
	     case 13: // Enter
		  if (!contextmenu.item) break;
		  cmd = contextmenu.item.innerHTML;
		  CallController(contextmenu.data);
		  HideContextmenu();
		  break;
	     case 38: // Up
		  SetContextmenuItem("UP");
		  break;
	     case 40: // Down
		  SetContextmenuItem("DOWN");
		  break;
	     case 27: // Esc
		  HideContextmenu();
		  break;
	    }
     return;
    }

 if (OVtype === 'Gallery')
    {
     switch (event.keyCode)
	    {
	     case 13: // Enter
		  imgwrapper.style.height = imgwrapper.style.height === 'auto' ? '100%' : 'auto';
		  imgwrapper.style.width = imgwrapper.style.width === 'auto' ? '100%' : 'auto';
		  break;
	     case 37: // Left
		  ShowImage(-1);
		  break;
	     case 39: // Right
		  ShowImage(1);
		  break;
	     case 27: // Esc
		  mainDiv.removeChild(imgwrapper);
		  mainTablediv.style.display = 'block';
		  OVtype = 'Table'
		  imgdesc.style.display = 'none';
		  break;
	     case 32: // Space
		  ShowImage(1);
		  break;
	    }
     return;
    }

 if (OVtype === 'Chart')
    {
     if (event.keyCode === 27) // Esc
	{
	 mainDiv.removeChild(canvas);
	 mainTablediv.style.display = 'block';
	 OVtype = 'Table'
	}
     return;
    }

 if (OVtype === 'Table')
    {
     HideHint();
     if (!cursor.td) return;
     switch (event.keyCode)
	    {
	     case 36: // Home
		  moveCursor(cursor.x, 0, true);
		  break;
	     case 35: // End
		  moveCursor(cursor.x, mainTableHeight - 1, true);
		  break;
	     case 33: // PgUp
		  moveCursor(cursor.x, Math.max(Math.trunc((mainDiv.scrollTop - 0.5*mainDiv.clientHeight)*mainTableHeight/mainDiv.scrollHeight), 0), true);
		  break;
	     case 34: // PgDown
		  moveCursor(cursor.x, Math.min(Math.trunc((mainDiv.scrollTop + 1.7*mainDiv.clientHeight)*mainTableHeight/mainDiv.scrollHeight), mainTableHeight - 1), true);
		  break;
	     case 38: // Up
		  moveCursor(0, -1);
		  break;
	     case 40: // Down
		  moveCursor(0, 1);
		  break;
	     case 37: //Left
		  moveCursor(-1, 0);
		  break;
	     case 39: //Right
		  moveCursor(1, 0);
		  break;
	     case 13: // Enter
		  if (cursor.td.contentEditable !== EDITABLE)
		     {
		      moveCursor(0, 1);
		      break;
		     }
		  let confirm, combinationKey = uiProfile['application']['Editable content apply input key combination'];
		  if (event.altKey && combinationKey === 'Alt+Enter') confirm = true;
		   else if (event.ctrlKey && combinationKey === 'Ctrl+Enter') confirm = true;
		   else if (event.shiftKey && combinationKey === 'Shift+Enter') confirm = true;
		   else if (!event.altKey && !event.ctrlKey && !event.shiftKey && combinationKey === 'Enter') confirm = true;
		  //--------------------
		  if (!confirm)
		     {
		      event.preventDefault();
		      document.execCommand('insertLineBreak', false, null); // "('insertHTML', false, '<br>')" doesn't work in fucking FF
		      break;
		     }
		  //--------------------
		  cursor.td.contentEditable = NOTEDITABLE;
		  if (mainTable[cursor.y][cursor.x].oId === NEWOBJECTID)
		     {
		      mainTable[cursor.y][cursor.x].data = htmlCharsConvert(cursor.td.innerHTML);
		      cmd = 'Add Object';
		      CallController();
		      break;
		     }
		  cmd = 'CONFIRM';
		  CallController(htmlCharsConvert(cursor.td.innerHTML));
		  break;
	     case 27: // Esc
		  if (cursor.td.contentEditable === EDITABLE)
		     {
		      cursor.td.contentEditable = NOTEDITABLE;
		      cursor.td.innerHTML = cursor.olddata;
		      break;
		     }
		  CellBorderToggleSelect(null, cursor.td, false); // Normilize cell outline off buffered dashed style cell
	          break;
	     case 45:  // Ins
	     case 46:  // Del
	     case 113: // F2
	     case 123: // F12
		  ProcessControllerEventKeys(event);
		  break;
	     case 65: // 'a'
		  if (cursor.td.contentEditable === EDITABLE) break;
		  if (event.ctrlKey && !event.shiftKey && !event.altKey && !event.metaKey)
		     {
		      SelectTableArea(drag.x1 = 0, drag.y1 = 0, drag.x2 = mainTableWidth - 1, drag.y2 = mainTableHeight - 1);
		      event.preventDefault();
		     }
		  ProcessControllerEventKeys(event);
		  break;
	     case 67: // 'c'
		  if (cursor.td.contentEditable === EDITABLE) break;
		  if (event.ctrlKey && !event.shiftKey && !event.altKey && !event.metaKey) CopyBuffer(event.shiftKey);
		  ProcessControllerEventKeys(event);
		  break;
	     default: // Space, letters, digits
		  if (rangeTest(event.keyCode, SPACELETTERSDIGITSRANGE)) ProcessControllerEventKeys(event);
	}
     return;
    }
}

function ProcessControllerEventKeys(event)
{
 if (cursor.td.contentEditable === EDITABLE) return;
 if (!mainTable[cursor.y] || !mainTable[cursor.y][cursor.x] || isNaN(cursor.eId)) return;
 let newcmd, object = { metakey: event.metaKey, altkey: event.altKey, shiftkey: event.shiftKey, ctrlkey: event.ctrlKey };

 if (event.keyCode === 45) newcmd = 'INS'; else if (event.keyCode === 46) newcmd = 'DEL'; else if (event.keyCode === 113) newcmd = 'F2'; else if (event.keyCode === 123) newcmd = 'F12'; else
    {
     newcmd = 'KEYPRESS';
     object['string'] = event.key;
     // Prevent default action 'page down' (via space) and 'quick search bar' (via keyboard|numpad forward slash) in Firefox browser
     if (event.keyCode == 32 || event.keyCode == 111 || event.keyCode == 191) event.preventDefault();
    }

 if (mainTable[cursor.y][cursor.x].oId === NEWOBJECTID)
    {
     switch (newcmd)
	    {
	     case 'F2':
		  if (!event.ctrlKey && !event.altKey && !event.metaKey && !event.shiftKey) MakeCursorContentEditable(mainTable[cursor.y][cursor.x].data);
		  break;
	     case 'DEL':
		  if (!event.ctrlKey && !event.altKey && !event.metaKey && !event.shiftKey) mainTable[cursor.y][cursor.x].data = cursor.td.innerHTML = '';
		  break;
	     case 'KEYPRESS':
		  if (!event.ctrlKey && !event.altKey && !event.metaKey) MakeCursorContentEditable(mainTable[cursor.y][cursor.x].data);
		  break;
	    }
     return;
    }

 if (!mainTable[cursor.y][cursor.x]['realobject']) return;
 cmd = newcmd;
 CallController(object);
}

function moveCursor(x, y, abs)
{
 if (cursor.td.contentEditable === EDITABLE || (abs && cursor.x == x && cursor.y == y)) return;

 if (drag.x1 !== undefined) // Unselect area if selected
    {
     UnSelectTableArea(drag.x1, drag.y1, drag.x2, drag.y2);
     delete drag.x1;
    }

 let a = x, b = y;
 if (!abs)
    {
     a += cursor.x;
     b += cursor.y;
    }
 if (a < 0 || a >= mainTableWidth || b < 0 || b >= mainTableHeight) return;

 const newTD = mainTablediv.rows[b].cells[a];
 if (abs || isVisible(newTD) || (!isVisible(cursor.td) && tdVisibleSquare(newTD) > tdVisibleSquare(cursor.td)) || (y == 0 && xAxisVisible(newTD)) || (x == 0 && yAxisVisible(newTD)))
    {
     if (!abs) event.preventDefault();
     CellBorderToggleSelect(cursor.td, newTD);
    }
}

function tdVisibleSquare(elem)
{
 const width = Math.min(elem.offsetLeft - mainDiv.scrollLeft + elem.offsetWidth, mainDiv.offsetWidth) - Math.max(elem.offsetLeft - mainDiv.scrollLeft, 0);
 const height = Math.min(elem.offsetTop - mainDiv.scrollTop + elem.offsetHeight, mainDiv.offsetHeight) - Math.max(elem.offsetTop - mainDiv.scrollTop, 0);
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
 for (let i = 0; i < b.length; i += 2)
     if (a >= b[i] && a <= b[i+1]) return true;
 return false;
}
