<?php
set_time_limit(0);

$db = new PDO('mysql:host=localhost;charset=utf8;dbname=manictime', 'manictime', 'manictime');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pc_list = array();

try {
	$q = $db->query('select * from pc_list');
	while($r = $q->fetch(PDO::FETCH_ASSOC)) {
		$pc_list[$r['pc']] = $r['pc_orig'];
	}
} catch (PDOException $e) {
	$db->Exec('CREATE TABLE pc_list (pc varchar(64) PRIMARY KEY, pc_orig varchar(64))');
}

$pc = preg_replace('/[^a-z0-9]/', '_', strtolower(@$_GET['pc'] ?: @$_COOKIE['manictime_pc']));

if (!isset($pc_list[$pc])) $pc = key($pc_list);
setcookie('manictime_pc', $pc, time() + 86400 * 365);