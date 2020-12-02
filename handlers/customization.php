<?php

$input = json_decode($input, true);

if (isset($input['event'])) switch($input['event'])
   {
    case 'INIT':
	 $output = json_encode(['cmd' => 'RESET', 'value' => 'User customization', 'dialog' => defaultCustomizationDialogJSON()]);
	 break;
    case 'DBLCLICK':
	 if (!isset($input['dialog']))
	    {
	     $output = json_encode(['cmd' => 'ALERT', 'data' => "You can't change system account customization!"]);
	     break;
	    }
	 $dialog = ['title' => 'User customization',
		    'dialog' => json_decode($input['dialog'], true),
		    'buttons' => ['SAVE' => ' ', 'CANCEL' => 'background-color: red;'],
		    'flags'  => ['cmd' => 'CUSTOMIZATION', 'style' => 'width: 600px; height: 600px;', 'esc' => '', 'padprofilehead' => ['pad' => "\n\nSelect customization"]]];
	 $output = json_encode(['cmd' => 'DIALOG', 'data' => $dialog]);
	 break;
    case 'CONFIRM':
	 $output = json_encode(['cmd' => 'SET', 'dialog' => json_encode($input['data']['dialog'])]);
	 break;
   }
