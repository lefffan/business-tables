
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
		  if (box.flags?.esc !== undefined) // Box with esc flag set?
		     {
		      let button = SeekObjJSONProp(box.buttons, 'call');
		      if (button)
			 {
			  if (box.buttons[button]['error']) displayMainError(box.buttons[button]['error']);
			  if (box.buttons[button]['warning']) { warning(box.buttons[button]['warning']); break; }
			 }
		      HideBox();
		     }
		  break;
	     case 13: // Enter
		  if (event.target.tagName === 'INPUT' && (event.target.type === 'text' || event.target.type === 'password'))
		     BoxApply(SeekObjJSONProp(box.buttons, 'enterkey', true));
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

 // For estimated OV types (table, tree, map)
 if (event.ctrlKey && !event.shiftKey && event.altKey && !event.metaKey && cursor.td?.contentEditable !== EDITABLE)
    {
     switch (event.keyCode)
	    {
	     case 37: //Left
		  if (viewindex > 0)
		     {
		      cmd = 'CALLHISTORY';
		      viewindex--;
		      CallController();
		     }
		  return;;
	     case 39: //Right
		  if (viewindex < viewhistory.length - 1 && viewindex !== -1)
		     {
		      cmd = 'CALLHISTORY';
		      viewindex++;
		      CallController();
		     }
		  return;
	    }
    }

 if (OVtype === 'Table')
    {
     HideHint();

     if (cursor.td?.contentEditable !== EDITABLE && event.ctrlKey && event.shiftKey && !event.altKey && !event.metaKey && event.keyCode === 70)
	{
	 box = {title: 'Search',
		dialog: {pad: {profile: {element1: {head: '\nEnter regular expression to search:', type: 'text', data: ''},
					 //element2: {head: '', type: 'radio', data: '+Standart|Template|Regexp'},
					 element3: {lin: '', type: 'checkbox', data: 'Case sensitive'},
					}}},
		buttons: {PREV: {value: ' < ', interactive: '', call: 'SEARCHPREV'},
			  NEXT: {value: ' > ', interactive: '', call: 'SEARCHNEXT', enterkey: ''}},
		flags: {esc: '', style: "min-width: 400px; min-height: 80px;", nofilter: ''},
		search: []
	       };
	 ShowBox();
	 boxDiv.querySelector('input').oninput = () => { clearTimeout(searchTimerId); searchTimerId = setTimeout(NewSearch, 500); };
	 return;
	}

     if (!cursor.td) return;
     switch (event.keyCode)
	    {
	     case 36: // Home
		  moveCursor(cursor.x, 0, event);
		  break;
	     case 35: // End
		  moveCursor(cursor.x, mainTableHeight - 1, event);
		  break;
	     case 33: // PgUp
		  moveCursor(cursor.x, cursor.y - Math.trunc(mainDiv.clientHeight*mainTableHeight/mainDiv.scrollHeight), event);
		  break;
	     case 34: // PgDown
		  moveCursor(cursor.x, cursor.y + Math.trunc(mainDiv.clientHeight*mainTableHeight/mainDiv.scrollHeight), event);
		  break;
	     case 38: // Up
		  if (!event.ctrlKey && !event.shiftKey && event.altKey && !event.metaKey)
		     {
		      if (objectTable[cursor.oId - 1]?.[cursor.eId])
			 moveCursor(objectTable[cursor.oId - 1][cursor.eId].x, objectTable[cursor.oId - 1][cursor.eId].y, event);
		      break;
		     }
		  moveCursor(cursor.x, cursor.y - 1, event);
		  break;
	     case 40: // Down
		  if (!event.ctrlKey && !event.shiftKey && event.altKey && !event.metaKey)
		     {
		      if (objectTable[cursor.oId + 1]?.[cursor.eId])
			 moveCursor(objectTable[cursor.oId + 1][cursor.eId].x, objectTable[cursor.oId + 1][cursor.eId].y, event);
		      break;
		     }
		  moveCursor(cursor.x, cursor.y + 1, event);
		  break;
	     case 37: //Left
		  moveCursor(cursor.x - 1, cursor.y, event);
		  break;
	     case 39: //Right
		  moveCursor(cursor.x + 1, cursor.y, event);
		  break;
	     case 13: // Enter
		  if (cursor.td.contentEditable !== EDITABLE)
		     {
		      if (!event.ctrlKey && !event.altKey && !event.metaKey) moveCursor(cursor.x, cursor.y + (event.shiftKey ? -1 : 1), event);
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
                      document.execCommand('insertLineBreak', false, null);
		      break;
		     }
		  ConfirmEditableContent(true);
		  break;
	     case 27: // Esc
		  if (cursor.td.contentEditable === EDITABLE)
		     {
		      cursor.td.contentEditable = NOTEDITABLE;
		      cursor.td.innerHTML =  ToHTMLChars(mainTable[cursor.y][cursor.x].data);
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

function moveCursor(x, y, event)
{
 if (cursor.td.contentEditable === EDITABLE || event.getModifierState('ScrollLock')) return;
 event.preventDefault();
 x = Math.max(0, x); x = Math.min(x, mainTableWidth - 1);
 y = Math.max(0, y); y = Math.min(y, mainTableHeight - 1);

 if (cursor.x === x && cursor.y === y) return;

 if (!event.ctrlKey && event.shiftKey && !event.altKey && !event.metaKey && event.keyCode !== 13) // Cursor moving with shift
    {
     if (drag.x1 === undefined)
	{
	 drag.x1 = cursor.x;
	 drag.y1 = cursor.y;
	}
      else
	{
	 UnSelectTableArea(drag.x1, drag.y1, drag.x2, drag.y2);
	}
     drag.x2 = x;
     drag.y2 = y;
     SelectTableArea(drag.x1, drag.y1, drag.x2, drag.y2);
    }
  else if (drag.x1 !== undefined) // Unselect area if selected// Cursor moving without shift, so if drag area does exist - unselect it
    {
     UnSelectTableArea(drag.x1, drag.y1, drag.x2, drag.y2);
     delete drag.x1;
    }
 CellBorderToggleSelect(cursor.td, mainTablediv.rows[y].cells[x]);
}

function rangeTest(a, b)
{
 for (let i = 0; i < b.length; i += 2)
     if (a >= b[i] && a <= b[i+1]) return true;
 return false;
}
