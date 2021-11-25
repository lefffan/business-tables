<?php

require_once 'core.php';

$id = substr($_POST['id'], 1, -1);
$cmd = $_POST['cmd'];

try {
     $output = ['cmd' => ''];
     $query = $db->prepare("SELECT now()-time,client FROM `$$$` WHERE id='$id'");
     $query->execute();
     $client = $query->fetchAll(PDO::FETCH_NUM)[0];
     $query = $db->prepare("DELETE FROM `$$$` WHERE id='$id'");
     $query->execute();
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
$filecount = count($_FILES['files']['name']);
$successfilecount = 0;

try {
     switch ($cmd)
	    {
	     case 'UPLOAD':
		  if (gettype($_FILES['files']['name']) !== 'array' || !$filecount) break;
		  $prefix = UPLOADDIR."$client[ODid]/$client[oId]/$client[eId]/";
		  if (!is_dir($prefix)) if (!mkdir($prefix, 0700, true)) break;

		  foreach ($_FILES['files']['name'] as $i => $name)
			  if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $prefix.$name)) $successfilecount++;
		  $output += ['alert' => strval($successfilecount).' file(-s) of '.strval($filecount).' uploaded successfully!'];
		  break;
	    }
    }
catch (PDOException $e)
    {
     lg($e);
     $output = ['cmd' => '', 'alert' => $e->getMessage()];
    }

// Echo output result
echo json_encode($output);
