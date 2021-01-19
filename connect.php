<?php

$db = new PDO('mysql:host=localhost;dbname='.DATABASENAME, DATABASEUSER, DATABASEPASS);
$db->exec("SET NAMES UTF8");
$db->exec("ALTER DATABASE ".DATABASENAME." CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
