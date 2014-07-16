<?php
require 'common.php';

$Tables['Activity'] = [	'ActivityId'     => 'integer AUTO_INCREMENT PRIMARY KEY',
						'TimelineId'     => 'integer',
						'DisplayName'    => 'varchar(512)',
						'GroupId'        => 'integer',
						'StartLocalTime' => 'integer',
						'EndLocalTime'   => 'integer',
						'LocalDate'		 => 'integer',
						'Duration'       => 'integer',
						'Records'=> 'integer'
						 ];

$Tables['Group'] = [	'GroupId'        => 'integer  AUTO_INCREMENT PRIMARY KEY',
						'TimelineId'     => 'integer',
						'DisplayName'    => 'varchar(512)',
						'Icon32'         => 'text',
						'FolderId'       => 'integer',
						'TextData'       => 'text'
						];

$Tables['Timeline'] = [ 'TimelineId'     => 'integer  AUTO_INCREMENT PRIMARY KEY',
						'TypeName'       => 'varchar(255)',
						'SourceTypeName' => 'varchar(255)',
						];

$Indexes = ['TimelineId', 'DisplayName', 'GroupId', 'StartLocalTime', 'EndLocalTime', 'LocalDate'];


if (isset($_GET['refresh'])) {
	if (!isset($pc_list[$pc])) {
		$db->Exec('insert into pc_list VALUES(' . $db->quote($pc) . ', ' . $db->quote($_GET['pc']) . ')');
	}
	echo '<root>';
	foreach($Tables as $table => $fields) {
		try {
			$q = $db->Query(sprintf('select max(%s) from `%s_%s`', key($fields), $pc, $table));
			echo "<{$table}>0" . $q->fetchColumn() . "</{$table}>\n";
		} catch (PDOException $e) {
			$index = '';
			foreach($Tables[$table] as $k => $v) {
				$f[] = $k . ' ' . $v;
				if (in_array($k, $Indexes)) {
					$index .= ', INDEX(' . $k . ')';
				}
			}
			$db->Exec(sprintf('CREATE TABLE IF NOT EXISTS `%s_%s` (%s %s)', $pc, $table, implode(',', $f), $index));
		}
	}
	echo '</root>';
}
elseif ($postdata = gzinflate(substr(file_get_contents('php://input'), 10, -8))) {
	if ($xml = simplexml_load_string(preg_replace('/&#x[0-9A-F]+;/iu', '', $postdata))) {
		$table = $xml->getName();
		$fields = array_fill_keys(array_keys($Tables[$table]), null);
		foreach($xml->xpath('//Row') as $row) {
			$row = array_merge($fields, (array)$row);
			switch($table) {
				case 'Activity':
					$row['StartLocalTime'] = strtotime($row['StartLocalTime']);
					$row['EndLocalTime'] = strtotime($row['EndLocalTime']);
					$row['LocalDate'] = date('Ymd', intval(($row['StartLocalTime'] + $row['EndLocalTime']) / 2));
					$row['Duration'] = $row['EndLocalTime'] - $row['StartLocalTime'];
					$row['Records'] = 1;
					break;
				case 'Group':
					break;
				case 'Timeline':
					break;
			}
			$row = array_map(function($v) use ($db) {return $v === null ? 'NULL' : $db->quote($v);}, $row);
			$inserts[] = '(' . implode(',', $row) . ')';
		}
		for ($i = 0, $j = count($inserts); $i < $j; $i += 100) {
			$db->Exec(sprintf('insert into `%s_%s` (%s) VALUES %s', $pc, $table, implode(',', array_keys($fields)), 
						 implode(',', array_slice($inserts, $i, 100))));
		}
		echo $db->lastInsertId() ?: 'FAILURE';
	} else
		echo 'FAILURE';
}