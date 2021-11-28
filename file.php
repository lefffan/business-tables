<?php

require_once 'core.php';
//lg($_POST);
//lg($_GET);

$id = substr($_POST['id'], 1, -1);
$cmd = $_POST['cmd'];

try {
     $output = ['cmd' => ''];
     $query = $db->prepare("SELECT now()-time,client FROM `$$$` WHERE id='$id'");
     $query->execute();
     $client = $query->fetchAll(PDO::FETCH_NUM)[0];
     //$query = $db->prepare("DELETE FROM `$$$` WHERE id='$id'");
     //$query->execute();
    }
catch (PDOException $e)
    {
     lg($e, 'View.php PDO exception');
     echo json_encode(['cmd' => '', 'error' => 'PDO driver exception error!']);
     exit;
    }

if (intval($client[0]) > CALLFILEMNGTTIMEOUT)
   {
    echo json_encode(['cmd' => '', 'error' => "File management dialog timeout with $client[0]sec, please try again!"]);
    exit;
   }
$client = json_decode($client[1], true);

switch ($cmd)
       {
	case 'UPLOAD':
	     if (gettype($_FILES['files']['name']) !== 'array' || !($filecount = count($_FILES['files']['name']))) break;
	     $successfilecount = 0;
	     $prefix = UPLOADDIR."$client[ODid]/$client[oId]/$client[eId]/";
	     if (!is_dir($prefix)) if (!mkdir($prefix, 0700, true)) break;
	     foreach ($_FILES['files']['name'] as $i => $name)
		     if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $prefix.$name)) $successfilecount++;
	     $output += ['alert' => $successfilecount.' of '.$filecount.' file(-s) uploaded successfully!'];
	     echo json_encode($output); // Echo output result
	     break;
	case 'DOWNLOAD':
	     $file = ['name' => basename(UPLOADDIR."$client[ODid]/$client[oId]/$client[eId]/".$client['list'][$_POST['fileindex']])];
	     header('Content-Description: File Transfer');
	     header('Content-Type: application/octet-stream');
	     header('Content-Disposition: attachment; filename="'.json_encode($file).'"');
	     header('Expires: 0');
	     header('Cache-Control: must-revalidate');
	     header('Pragma: public');
	     header('Content-Length: '.filesize($file));
	     ob_clean();
	     flush();
	     readfile($file);
	     break;
	case 'DELETE':
	     $successfilecount = $filecount = 0;
	     foreach ($_POST as $i => $value)
		  if ($value === '' && gettype($i) === 'integer')
		     {
		      $filecount++;
		      if (unlink(UPLOADDIR."$client[ODid]/$client[oId]/$client[eId]/".$client['list'][$i])) $successfilecount++;
		     }
	     echo json_encode(['cmd' => '', 'alert' => $successfilecount.' of '.$filecount.' file(-s) deleted successfully!']); // Echo output result
	     break;
       }
