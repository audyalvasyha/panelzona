<?php

date_default_timezone_set('Asia/Jakarta');
ini_set('memory_limit', '128M');

/* CONFIG */
$config['web'] = array(
	'maintenance' => 0, // 1 = yes, 0 = no
	'title' => 'Zona Modz VVIP',// ini buat ganti nama panel
	'meta' => array(
		'description' => 'Zona Modz VVIP Safe Feature',
		'keywords' => 'cheat panel',
		'author' => 'Zona Modz'
	),
	'base_url' => 'https://storezonamodz.my.id/api' // domain website	
);

$config['db'] = array(
	'host' => 'localhost',  //isi aja localhost
	'name' => 'bbakkhiz_panelkeygen',     // nama database
	'username' => 'bbakkhiz', // username database
	'password' => 'Lupasandi@123'       // password database
);
/* END - CONFIG */

require 'lib/db.php';
require 'lib/model.php';
require 'lib/function.php';

session_start();
$model = new Model();
