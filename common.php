<?php
set_time_limit(0);

$config = require 'config.php';

$pdo = sprintf('mysql:host=%s;charset=utf8;dbname=%s', $config['pdo']['hostname'], $config['pdo']['database']);

$db = new PDO($pdo, $config['pdo']['username'], $config['pdo']['password']);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$current_pc = '';
$computers  = [];

try {
	$q = $db->query('select * from computers');
	while($r = $q->fetch(PDO::FETCH_ASSOC)) {
		$computers[$r['pc']] = $r['pc_orig'];
	}
} catch (PDOException $e) {
	$db->Exec('CREATE TABLE computers (pc varchar(64) PRIMARY KEY, pc_orig varchar(64), last_crunched_id integer)');
}

if (!empty($_GET['pc'])) {
	$current_pc = $_GET['pc'];
} elseif(!empty($_COOKIE['manictime_pc'])) {
	$current_pc = $_COOKIE['manictime_pc'];
}

$current_pc = preg_replace('/[^a-z0-9]/', '_', strtolower($current_pc));

if (!isset($_GET['refresh']) && !isset($computers[$current_pc])) {
	$current_pc = key($computers);
}

setcookie('manictime_pc', $current_pc, time() + 86400 * 365);

function short($text, $length, $middle = true) {
	if (strlen($text) - 3 > $length) {
		if ($middle)
			$text = substr($text, 0, ceil($length/2)) . '...' . substr($text, -ceil($length/2));
		else
			$text = substr($text, 0, $length-3) . '...';
	}
	return $text;
}

function hm($sec) {
	$hours = intval($sec/3600);
	if ($hours) {
		$sec -= $hours * 3600;
	}
	$min = intval($sec/60);

	return sprintf('%00.0fh %02.0fm', $hours, $min);
}

function build_query(array $values) {
	return http_build_query($values + $_GET);
}
