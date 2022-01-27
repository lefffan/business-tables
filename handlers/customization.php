<?php

require_once 'customizationjson.php';

function lg($arg)
{
 file_put_contents('error.log', var_export($arg, true), FILE_APPEND);
 file_put_contents('error.log', "\n-------------------------------END LOG-------------------------------\n", FILE_APPEND);
}

function CheckEffect(&$effect)
{
 if (array_search($effect, ['hotnews', 'fade', 'grow', 'slideleft', 'slideright', 'slideup', 'slidedown', 'fall', 'rise'], true) === false) $effect = 'none';
}

if (!isset($_SERVER['argv'][1])) exit;
switch ($_SERVER['argv'][1])
       {
	case 'INIT':
	     echo json_encode(['cmd' => 'RESET', 'value' => 'Customize', 'dialog' => defaultCustomizationDialogJSON()]);
	     break;
	case 'DBLCLICK':
	     if (!isset($_SERVER['argv'][2]) || !($data = json_decode($_SERVER['argv'][2], true)))
		{
	         echo json_encode(['cmd' => 'ALERT', 'data' => "You can't change system account customization!"]);
	         break;
		}
	     $dialog = ['title' => 'User customization',
			'dialog' => $data,
			'buttons' => ['SAVE' => ['value' => 'SAVE', 'call' => '', 'enterkey' => ''], 'CANCEL' => ['value' => 'CANCEL', 'style' => 'background-color: red;']],
			'flags'  => ['style' => 'width: 600px; height: 600px;', 'profilehead' => ['pad' => "\n\nSelect customization"]]];

	     echo json_encode(['cmd' => 'DIALOG', 'data' => $dialog]);
	     break;
	case 'CONFIRMDIALOG':
	     if (!isset($_SERVER['argv'][2]) || !($data = json_decode($_SERVER['argv'][2], true))) break;

	     CheckEffect($data['dialog']['pad']['context menu']['element12']['data']);
	     CheckEffect($data['dialog']['pad']['hint']['element9']['data']);
	     CheckEffect($data['dialog']['pad']['dialog box']['element7']['data']);
	     CheckEffect($data['dialog']['pad']['dialog box select']['element15']['data']);

	     echo json_encode(['cmd' => 'SET', 'dialog' => json_encode($data['dialog'], JSON_HEX_APOS | JSON_HEX_QUOT)]);
	     break;
       }
