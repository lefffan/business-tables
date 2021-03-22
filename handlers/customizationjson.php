<?php

function defaultCustomizationDialogJSON()
{
 // To transfer uiProfile from main.js: get uiProfile JSON from console by "console.log(JSON.stringify(uiProfile))" and put it json_decode below. Don't forget to escape single quotes by "\'"
 // $uiProfile = json_decode('{"application":{"target":"body","background-color":"#343E54;","Force to use next user customization (empty or non-existent user - option is ignored)":"","Editable content apply input key combination":"Ctrl+Enter","_Editable content apply input key combination":"Available options: \'Ctrl+Enter\', \'Alt+Enter\', \'Shift+Enter\' and \'Enter\'.<br>Any other values set no way to apply content editable changes by key combination."},"sidebar":{"target":".sidebar","background-color":"rgb(17,101,176);","border-radius":"5px;","color":"#9FBDDF;","width":"13%;","height":"90%;","left":"4%;","top":"5%;","scrollbar-color":"#1E559D #266AC4;","scrollbar-width":"thin;","box-shadow":"4px 4px 5px #222;"},"sidebar wrap icon":{"wrap":"&#9658;","unwrap":"&#9660;"},"sidebar wrap cell":{"target":".wrap","font-size":"70%;","padding":"3px 5px;"},"sidebar item active":{"target":".itemactive","background-color":"#4578BF;","color":"#FFFFFF;","font":"1.1em Lato, Helvetica;"},"sidebar item hover":{"target":".sidebar tr:hover","background-color":"#4578BF;","cursor":"pointer;"},"sidebar object database":{"target":".sidebar-od","padding":"3px 5px 3px 0px;","margin":"0px;","color":"","width":"100%;","font":"1.1em Lato, Helvetica;"},"sidebar object view":{"target":".sidebar-ov","padding":"2px 5px 2px 10px;","margin":"0px;","color":"","font":"0.9em Lato, Helvetica;"},"main field":{"target":".main","width":"76%;","height":"90%;","left":"18%;","top":"5%;","border-radius":"5px;","background-color":"#EEE;","scrollbar-color":"#CCCCCC #FFFFFF;","box-shadow":"4px 4px 5px #111;"},"main field table":{"target":"table","margin":"0px;"},"main field table cursor cell":{"outline":"red auto 1px","shadow":"0 0 5px rgba(100,0,0,0.5)"},"main field table title cell":{"target":".titlecell","padding":"10px;","border":"1px solid #999;","color":"black;","background":"#CCC;","font":"","text-align":"center"},"main field table newobject cell":{"target":".newobjectcell","padding":"10px;","border":"1px solid #999;","color":"black;","background":"rgb(191,255,191);","font":"","text-align":"center"},"main field table data cell":{"target":".datacell","padding":"10px;","border":"1px solid #999;","color":"black;","background":"","font":"12px/14px arial;","text-align":"center"},"main field table undefined cell":{"target":".undefinedcell","padding":"10px;","border":"","background":""},"main field table mouse pointer":{"target":".main table tbody tr td:not([contenteditable=true])","cursor":"cell;"},"main field message":{"target":".main h1","color":"#BBBBBB;"},"scrollbar":{"target":"::-webkit-scrollbar","width":"8px;","height":"8px;"},"context menu":{"target":".contextmenu","width":"240px;","background-color":"#F3F3F3;","color":"#1166aa;","border":"solid 1px #dfdfdf;","box-shadow":"1px 1px 2px #cfcfcf;","font-family":"sans-serif;","font-size":"16px;","font-weight":"300;","line-height":"1.5;","padding":"12px 0;","effect":"rise","_effect":"Context menu  effect appearance. Possible values:<br>\'fade\', \'grow\', \'slideleft\', \'slideright\', \'slideup\', \'slidedown\', \'fall\', \'rise\' and \'none\'.<br>Incorrect value makes \'none\' effect."},"context menu item":{"target":".contextmenuItems","margin-bottom":"4px;","padding-left":"10px;"},"context menu item cursor":{"target":".contextmenuItems:hover:not(.greyContextMenuItem)","cursor":"pointer;"},"context menu item active":{"target":".activeContextMenuItem","color":"#fff;","background-color":"#0066aa;"},"context menu item grey":{"target":".greyContextMenuItem","color":"#dddddd;"},"hint":{"target":".hint","background-color":"#CAE4B6;","color":"#7E5A1E;","border":"none;","border-radius":"3px;","box-shadow":"2px 2px 4px #cfcfcf;","padding":"5px;","effect":"hotnews","mouseover hint timer in msec":"1000","_effect":"Hint  effect appearance. Possible values:<br>\'fade\', \'grow\', \'slideleft\', \'slideright\', \'slideup\', \'slidedown\', \'fall\', \'rise\' and \'none\'.<br>Incorrect value makes \'none\' effect."},"dialog box":{"target":".box","background-color":"rgb(233,233,233);","color":"#1166aa;","border-radius":"5px;","border":"solid 1px #dfdfdf;","box-shadow":"2px 2px 4px #cfcfcf;","effect":"slideleft","_effect":"Dialog box  effect appearance. Possible values:<br>\'fade\', \'grow\', \'slideleft\', \'slideright\', \'slideup\', \'slidedown\', \'fall\', \'rise\' and \'none\'.<br>Incorrect value makes \'none\' effect.","filter":"grayscale(0.5)","_filter":"Application css style filter applied to the sidebar and main field.<br>For a example: \'grayscale(0.5)\' or \'blur(3px)\'. See appropriate css documentaion."},"dialog box title":{"target":".title","background-color":"rgb(209,209,209);","color":"#555;","border":"#000000;","border-radius":"5px 5px 0 0;","font":"bold .9em Lato, Helvetica;","padding":"5px;"},"dialog box pad":{"target":".pad","background-color":"rgb(223,223,223);","border-left":"none;","border-right":"none;","border-top":"none;","border-bottom":"none;","padding":"5px;","margin":"0;","font":".9em Lato, Helvetica;","color":"#57C;","border-radius":"5px 5px 0 0;"},"dialog box active pad":{"target":".activepad","background-color":"rgb(209,209,209);","border-left":"none;","border-right":"none;","border-top":"none;","border-bottom":"none;","padding":"5px;","margin":"0;","font":"bold .9em Lato, Helvetica;","color":"#57C;","border-radius":"5px 5px 0 0;"},"dialog box pad bar":{"target":".padbar","background-color":"transparent;","border":"none;","padding":"4px;","margin":"10px 0 15px 0;"},"dialog box divider":{"target":".divider","background-color":"transparent;","margin":"5px 10px 5px 10px;","height":"0px;","border-bottom":"1px solid #CCC;","border-top-color":"transparent;","border-left-color":"transparent;","border-right-color":"transparent;"},"dialog box button":{"target":".button","background-color":"#13BB72;","border":"none;","padding":"10px;","margin":"10px;","border-radius":"5px;","font":"bold 12px Lato, Helvetica;","color":"white;"},"dialog box button and pad hover":{"target":".button:hover, .pad:hover","cursor":"pointer;","background":"","color":"","border":""},"dialog box element headers":{"target":".element-headers","margin":"5px 5px 5px 5px;","font":".9em Lato, Helvetica;","color":"#555;","text-shadow":"none;"},"dialog box help icon":{"target":".help-icon","padding":"1px;","font":".9em Lato, Helvetica;","color":"#555;","background":"#FF0;","border-radius":"40%;"},"dialog box help icon hover":{"target":".help-icon:hover","padding":"1px;","font":"bold 1em Lato, Helvetica;","color":"black;","background":"#E8E800;","cursor":"pointer;","border-radius":"40%;"},"dialog box table":{"target":".tabel","font":".8em Lato, Helvetica;","color":"black;","background":"transparent;","margin":"10px;"},"dialog box table cell":{"target":".tabel td","padding":"7px;","border":"1px solid #999;"},"dialog box select":{"target":".select","background-color":"rgb(243,243,243);","color":"#57C;","font":".8em Lato, Helvetica;","margin":"0px 10px 5px 10px;","outline":"none;","border":"1px solid #777;","padding":"0px 0px 0px 0px;","overflow":"auto;","max-height":"10em;","scrollbar-width":"thin;","min-width":"10em;","width":"auto;","display":"inline-block;","effect":"rise","_effect":"Select fall-down option list   effect appearance. Possible values:<br>\'fade\', \'grow\', \'slideleft\', \'slideright\', \'slideup\', \'slidedown\', \'fall\', \'rise\' and \'none\'.<br>Incorrect value makes \'none\' effect."},"dialog box select option":{"target":".select > div","padding":"2px 20px 2px 5px;","margin":"0px;"},"dialog box select option hover":{"target":".select:not([type*=\'o\']) > div:hover","background-color":"rgb(209,209,209);","color":""},"dialog box select option selected":{"target":".selected","background-color":"rgb(209,209,209);","color":"#fff;"},"dialog box select option expanded":{"target":".expanded","margin":"0px !important;","position":"absolute;"},"dialog box radio":{"target":"input[type=radio]","background":"transparent;","border":"1px solid #777;","font":".8em/1 sans-serif;","margin":"3px 5px 3px 10px;","border-radius":"20%;","width":"1.2em;","height":"1.2em;"},"dialog box radio checked":{"target":"input[type=radio]:checked::after","content":"","color":"white;"},"dialog box radio checked background":{"target":"input[type=radio]:checked","background":"#00a0df;","border":"1px solid #00a0df;"},"dialog box radio label":{"target":"input[type=radio] + label","color":"#57C;","font":".8em Lato, Helvetica;","margin":"0px 10px 0px 0px;"},"dialog box checkbox":{"target":"input[type=checkbox]","background":"#f3f3f3;","border":"1px solid #777;","font":".8em/1 sans-serif;","margin":"3px 5px 3px 10px;","border-radius":"50%;","width":"1.2em;","height":"1.2em;"},"dialog box checkbox checked":{"target":"input[type=checkbox]:checked::after","content":"","color":"white;"},"dialog box checkbox checked background":{"target":"input[type=checkbox]:checked","background":"#00a0df;","border":"1px solid #00a0df;"},"dialog box checkbox label":{"target":"input[type=checkbox] + label","color":"#57C;","font":".8em Lato, Helvetica;","margin":"0px 10px 0px 0px;"},"dialog box input text":{"target":"input[type=text]","margin":"0px 10px 5px 10px;","padding":"2px 5px;","background":"#f3f3f3;","border":"1px solid #777;","outline":"none;","color":"#57C;","border-radius":"5%;","font":".9em Lato, Helvetica;","width":"300px;"},"dialog box input password":{"target":"input[type=password]","margin":"0px 10px 5px 10px;","padding":"2px 5px;","background":"#f3f3f3;","border":"1px solid #777;","outline":"","color":"#57C;","border-radius":"5%;","font":".9em Lato, Helvetica;","width":"300px;"},"dialog box input textarea":{"target":"textarea","margin":"0px 10px 5px 10px;","padding":"2px 5px;","background":"#f3f3f3;","border":"1px solid #777;","outline":"","color":"#57C;","border-radius":"5%;","font":".9em Lato, Helvetica;","width":"300px;"},"tree table":{"target":".treetable","border-spacing":"20px 0px;","border-collapse":"separate;","margin-top":"10px;"},"tree error element":{"target":".treeerror","background-color":"#eb8b9c;","border":"1px solid black;","padding":"7px !important;","border-radius":"5px;","text-align":"center;","box-shadow":"2px 2px 4px #888;","font":"12px/14px arial;"},"tree element":{"target":".treeelement","background-color":"#ccc;","border":"1px solid black;","padding":"7px !important;","border-radius":"5px;","text-align":"left;","box-shadow":"2px 2px 4px #888;","font":"12px/14px arial;"},"tree arrow stock":{"target":".treelinkstock","flex-basis":"10px;","box-sizing":"border-box;","background-color":"rgb(17,101,176);","border":"none;","margin-left":"15px;","margin-right":"15px;","height":"100px;"},"tree arrow down":{"target":".treelinkarrowdown","flex-basis":"20px;","box-sizing":"border-box;","background-color":"transparent;","border-top":"40px solid rgb(17,101,176);","border-bottom":"0 solid transparent;","border-left":"20px solid transparent;","border-right":"20px solid transparent;"},"tree arrow up":{"target":".treelinkarrowup","flex-basis":"20px;","box-sizing":"border-box;","background-color":"transparent;","border-top":"0 solid transparent;","border-bottom":"40px solid rgb(17,101,176);","border-left":"20px solid transparent;","border-right":"20px solid transparent;"},"tree element description":{"target":".treelinkdescription","display":"flex;","flex":"1 10px;","background-color":"transparent;","border":"none;","padding":"5px;","font":"10px/11px arial;","overflow":"hidden;"}}', true);
 $uiProfile = json_decode('{"application":{"target":"body","background-color":"#343E54;","Force to use next user customization (empty or non-existent user - option is ignored)":"","Editable content apply input key combination":"Ctrl+Enter","_Editable content apply input key combination":"Available options: \'Ctrl+Enter\', \'Alt+Enter\', \'Shift+Enter\' and \'Enter\'.<br>Any other values set no way to apply content editable changes by key combination."},"sidebar":{"target":".sidebar","background-color":"rgb(17,101,176);","border-radius":"5px;","color":"#9FBDDF;","width":"13%;","height":"90%;","left":"4%;","top":"5%;","scrollbar-color":"#1E559D #266AC4;","scrollbar-width":"thin;","box-shadow":"4px 4px 5px #222;"},"sidebar wrap icon":{"wrap":"&#9658;","unwrap":"&#9660;"},"sidebar wrap cell":{"target":".wrap","font-size":"70%;","padding":"3px 5px;"},"sidebar item active":{"target":".itemactive","background-color":"#4578BF;","color":"#FFFFFF;","font":"1.1em Lato, Helvetica;"},"sidebar item hover":{"target":".sidebar tr:hover","background-color":"#4578BF;","cursor":"pointer;"},"sidebar object database":{"target":".sidebar-od","padding":"3px 5px 3px 0px;","margin":"0px;","color":"","width":"100%;","font":"1.1em Lato, Helvetica;"},"sidebar object view":{"target":".sidebar-ov","padding":"2px 5px 2px 10px;","margin":"0px;","color":"","font":"0.9em Lato, Helvetica;"},"main field":{"target":".main","width":"76%;","height":"90%;","left":"18%;","top":"5%;","border-radius":"5px;","background-color":"#EEE;","scrollbar-color":"#CCCCCC #FFFFFF;","box-shadow":"4px 4px 5px #111;"},"main field table":{"target":"table","margin":"0px;"},"main field table cursor cell":{"outline":"red auto 1px","shadow":"0 0 5px rgba(100,0,0,0.5)"},"main field table title cell":{"target":".titlecell","padding":"10px;","border":"1px solid #999;","color":"black;","background":"#CCC;","font":"","text-align":"center"},"main field table newobject cell":{"target":".newobjectcell","padding":"10px;","border":"1px solid #999;","color":"black;","background":"rgb(191,255,191);","font":"","text-align":"center"},"main field table data cell":{"target":".datacell","padding":"10px;","border":"1px solid #999;","color":"black;","background":"","font":"12px/14px arial;","text-align":"center"},"main field table undefined cell":{"target":".undefinedcell","padding":"10px;","border":"","background":""},"main field table mouse pointer":{"target":".main table tbody tr td:not([contenteditable=true])","cursor":"cell;"},"main field message":{"target":".main h1","color":"#BBBBBB;"},"scrollbar":{"target":"::-webkit-scrollbar","width":"8px;","height":"8px;"},"context menu":{"target":".contextmenu","width":"240px;","background-color":"#F3F3F3;","color":"#1166aa;","border":"solid 1px #dfdfdf;","box-shadow":"1px 1px 2px #cfcfcf;","font-family":"sans-serif;","font-size":"16px;","font-weight":"300;","line-height":"1.5;","padding":"12px 0;","effect":"rise","_effect":"Context menu  effect appearance. Possible values:<br>\'fade\', \'grow\', \'slideleft\', \'slideright\', \'slideup\', \'slidedown\', \'fall\', \'rise\' and \'none\'.<br>Incorrect value makes \'none\' effect."},"context menu item":{"target":".contextmenuItems","margin-bottom":"4px;","padding-left":"10px;"},"context menu item cursor":{"target":".contextmenuItems:hover:not(.greyContextMenuItem)","cursor":"pointer;"},"context menu item active":{"target":".activeContextMenuItem","color":"#fff;","background-color":"#0066aa;"},"context menu item grey":{"target":".greyContextMenuItem","color":"#dddddd;"},"hint":{"target":".hint","background-color":"#CAE4B6;","color":"#7E5A1E;","border":"none;","border-radius":"3px;","box-shadow":"2px 2px 4px #cfcfcf;","padding":"5px;","effect":"hotnews","mouseover hint timer in msec":"1000","_effect":"Hint  effect appearance. Possible values:<br>\'fade\', \'grow\', \'slideleft\', \'slideright\', \'slideup\', \'slidedown\', \'fall\', \'rise\' and \'none\'.<br>Incorrect value makes \'none\' effect."},"dialog box":{"target":".box","background-color":"rgb(233,233,233);","color":"#1166aa;","border-radius":"5px;","border":"solid 1px #dfdfdf;","box-shadow":"2px 2px 4px #cfcfcf;","effect":"slideleft","_effect":"Dialog box  effect appearance. Possible values:<br>\'fade\', \'grow\', \'slideleft\', \'slideright\', \'slideup\', \'slidedown\', \'fall\', \'rise\' and \'none\'.<br>Incorrect value makes \'none\' effect.","filter":"grayscale(0.5)","_filter":"Application css style filter applied to the sidebar and main field.<br>For a example: \'grayscale(0.5)\' or \'blur(3px)\'. See appropriate css documentaion."},"dialog box title":{"target":".title","background-color":"rgb(209,209,209);","color":"#555;","border":"#000000;","border-radius":"5px 5px 0 0;","font":"bold .9em Lato, Helvetica;","padding":"5px;"},"dialog box pad":{"target":".pad","background-color":"rgb(223,223,223);","border-left":"none;","border-right":"none;","border-top":"none;","border-bottom":"none;","padding":"5px;","margin":"0;","font":".9em Lato, Helvetica;","color":"#57C;","border-radius":"5px 5px 0 0;"},"dialog box active pad":{"target":".activepad","background-color":"rgb(209,209,209);","border-left":"none;","border-right":"none;","border-top":"none;","border-bottom":"none;","padding":"5px;","margin":"0;","font":"bold .9em Lato, Helvetica;","color":"#57C;","border-radius":"5px 5px 0 0;"},"dialog box pad bar":{"target":".padbar","background-color":"transparent;","border":"none;","padding":"4px;","margin":"10px 0 15px 0;"},"dialog box divider":{"target":".divider","background-color":"transparent;","margin":"5px 10px 5px 10px;","height":"0px;","border-bottom":"1px solid #CCC;","border-top-color":"transparent;","border-left-color":"transparent;","border-right-color":"transparent;"},"dialog box button":{"target":".button","background-color":"#13BB72;","border":"none;","padding":"10px;","margin":"10px;","border-radius":"5px;","font":"bold 12px Lato, Helvetica;","color":"white;"},"dialog box button and pad hover":{"target":".button:hover, .pad:hover","cursor":"pointer;","background":"","color":"","border":""},"dialog box element headers":{"target":".element-headers","margin":"5px 5px 5px 5px;","font":".9em Lato, Helvetica;","color":"#555;","text-shadow":"none;"},"dialog box help icon":{"target":".help-icon","padding":"1px;","font":".9em Lato, Helvetica;","color":"#555;","background":"#FF0;","border-radius":"40%;"},"dialog box help icon hover":{"target":".help-icon:hover","padding":"1px;","font":"bold 1em Lato, Helvetica;","color":"black;","background":"#E8E800;","cursor":"pointer;","border-radius":"40%;"},"dialog box table":{"target":".boxtable","font":".8em Lato, Helvetica;","color":"black;","background":"transparent;","margin":"10px;"},"dialog box table cell":{"target":".boxtablecell","padding":"7px;","border":"1px solid #999;"},"dialog box select":{"target":".select","background-color":"rgb(243,243,243);","color":"#57C;","font":".8em Lato, Helvetica;","margin":"0px 10px 5px 10px;","outline":"none;","border":"1px solid #777;","padding":"0px 0px 0px 0px;","overflow":"auto;","max-height":"10em;","scrollbar-width":"thin;","min-width":"10em;","width":"auto;","display":"inline-block;","effect":"rise","_effect":"Select fall-down option list   effect appearance. Possible values:<br>\'fade\', \'grow\', \'slideleft\', \'slideright\', \'slideup\', \'slidedown\', \'fall\', \'rise\' and \'none\'.<br>Incorrect value makes \'none\' effect."},"dialog box select option":{"target":".select > div","padding":"2px 20px 2px 5px;","margin":"0px;"},"dialog box select option hover":{"target":".select:not([type*=\'o\']) > div:hover","background-color":"rgb(209,209,209);","color":""},"dialog box select option selected":{"target":".selected","background-color":"rgb(209,209,209);","color":"#fff;"},"dialog box select option expanded":{"target":".expanded","margin":"0px !important;","position":"absolute;"},"dialog box radio":{"target":"input[type=radio]","background":"transparent;","border":"1px solid #777;","font":".8em/1 sans-serif;","margin":"3px 5px 3px 10px;","border-radius":"20%;","width":"1.2em;","height":"1.2em;"},"dialog box radio checked":{"target":"input[type=radio]:checked::after","content":"","color":"white;"},"dialog box radio checked background":{"target":"input[type=radio]:checked","background":"#00a0df;","border":"1px solid #00a0df;"},"dialog box radio label":{"target":"input[type=radio] + label","color":"#57C;","font":".8em Lato, Helvetica;","margin":"0px 10px 0px 0px;"},"dialog box checkbox":{"target":"input[type=checkbox]","background":"#f3f3f3;","border":"1px solid #777;","font":".8em/1 sans-serif;","margin":"3px 5px 3px 10px;","border-radius":"50%;","width":"1.2em;","height":"1.2em;"},"dialog box checkbox checked":{"target":"input[type=checkbox]:checked::after","content":"","color":"white;"},"dialog box checkbox checked background":{"target":"input[type=checkbox]:checked","background":"#00a0df;","border":"1px solid #00a0df;"},"dialog box checkbox label":{"target":"input[type=checkbox] + label","color":"#57C;","font":".8em Lato, Helvetica;","margin":"0px 10px 0px 0px;"},"dialog box input text":{"target":"input[type=text]","margin":"0px 10px 5px 10px;","padding":"2px 5px;","background":"#f3f3f3;","border":"1px solid #777;","outline":"none;","color":"#57C;","border-radius":"5%;","font":".9em Lato, Helvetica;","width":"300px;"},"dialog box input password":{"target":"input[type=password]","margin":"0px 10px 5px 10px;","padding":"2px 5px;","background":"#f3f3f3;","border":"1px solid #777;","outline":"","color":"#57C;","border-radius":"5%;","font":".9em Lato, Helvetica;","width":"300px;"},"dialog box input textarea":{"target":"textarea","margin":"0px 10px 5px 10px;","padding":"2px 5px;","background":"#f3f3f3;","border":"1px solid #777;","outline":"","color":"#57C;","border-radius":"5%;","font":".9em Lato, Helvetica;","width":"300px;"},"tree table":{"target":".treetable","border-spacing":"20px 0px;","border-collapse":"separate;","margin-top":"10px;"},"tree error element":{"target":".treeerror","background-color":"#eb8b9c;","border":"1px solid black;","padding":"7px !important;","border-radius":"5px;","text-align":"center;","box-shadow":"2px 2px 4px #888;","font":"12px/14px arial;"},"tree element":{"target":".treeelement","background-color":"#ccc;","border":"1px solid black;","padding":"7px !important;","border-radius":"5px;","text-align":"left;","box-shadow":"2px 2px 4px #888;","font":"12px/14px arial;"},"tree arrow stock":{"target":".treelinkstock","flex-basis":"10px;","box-sizing":"border-box;","background-color":"rgb(17,101,176);","border":"none;","margin-left":"15px;","margin-right":"15px;","height":"100px;"},"tree arrow down":{"target":".treelinkarrowdown","flex-basis":"20px;","box-sizing":"border-box;","background-color":"transparent;","border-top":"40px solid rgb(17,101,176);","border-bottom":"0 solid transparent;","border-left":"20px solid transparent;","border-right":"20px solid transparent;"},"tree arrow up":{"target":".treelinkarrowup","flex-basis":"20px;","box-sizing":"border-box;","background-color":"transparent;","border-top":"0 solid transparent;","border-bottom":"40px solid rgb(17,101,176);","border-left":"20px solid transparent;","border-right":"20px solid transparent;"},"tree element description":{"target":".treelinkdescription","display":"flex;","flex":"1 10px;","background-color":"transparent;","border":"none;","padding":"5px;","font":"10px/11px arial;","overflow":"hidden;"}}', true); 
 
 $dialog = ['pad' => []];
 
 foreach ($uiProfile as $profile => $value)
	 {
	  $i = 1;
	  $dialog['pad'][$profile] = [];
	  if (isset($value['target']))
	     {
	      $dialog['pad'][$profile]['element0'] = ['head' => "CSS selector: '".$value['target']."'. Customize css selector properties below:", 'target' => $value['target']];
	      $dialog['pad'][$profile]['element1'] = ['head' => ''];
	     }
	  foreach ($value as $key => $val) if ($key != 'target' && substr($key, 0, 1) != '_')
		  {
		   $i++;
		   $dialog['pad'][$profile]['element'.strval($i)] = ['type' => 'text', 'head' => $key.':', 'data' => $val, 'line' => ''];
		   if (isset($value['_'.$key])) $dialog['pad'][$profile]['element'.strval($i)]['help'] = $value['_'.$key];
		  }
	 }
 return json_encode($dialog);
}
