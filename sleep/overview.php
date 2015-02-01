<?php

require __DIR__.'/../common.php';

$query = $db->query('SELECT * FROM `'.$pc.'_Activity` WHERE GroupId = 4 ORDER BY ActivityId');

$results = [];
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
	$timezoneOffset = -5*60*60;

	$date = $row['LocalDate'];
	$date = mktime(0, 0, 0, substr($date, 4, 2), substr($date, 6, 2), substr($date, 0, 4));

	$startTime = $row['StartLocalTime'] + $timezoneOffset;
	$startTime = gmdate('G', $startTime)*60*60+gmdate('i', $startTime)*60;

	$endTime = (double)$row['EndLocalTime'] + $timezoneOffset;
	$endTime = gmdate('G', $endTime)*60*60+gmdate('i', $endTime)*60;

	$results[] = [
		'StartLocalTime' => (double)$startTime*1000,
		'EndLocalTime' => (double)$endTime*1000,
		'LocalDate' => (double)$date*1000,
	];
}

header('Content-Type: application/json');
echo json_encode($results);
