<?php
	require 'common.php';
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Where is your time now?</title>
<script src="assets/js/jquery.js"></script>
<link rel="stylesheet" type="text/css" href="assets/css/jquery.datetimepicker.css">
<link rel="stylesheet" type="text/css" href="assets/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="assets/css/style.css">
<link href="assets/images/time.png" rel="shortcut icon">
</head>
<body>
<div id="wait"><h1>Loading...</h1></div>
<?php
	if (empty($computers)) {
		die('You need to run the client before something appears here!');
	}

	// We force the web server to flush the output to the client so that our loading screen appears.
	// That's the only reason the PHP can't be put before the HTML.
	echo str_repeat(' ', 1024 * 128);

	$first = time(); $last = 0;
	$programs = $groups = [];
	$records = 0;

	$min = isset($_GET['min']) ? intval($_GET['min']) : 15;
	$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

	$time_start = isset($_GET['start']) ? strtotime($_GET['start']) + 0 : strtotime('-1month');
	$time_end = isset($_GET['end']) ? strtotime($_GET['end']) + 86340 : time();

	$folders = [
			null => ['name' => 'Computer Usage', 'sum' => 0, 'entries' => []],
			3    => ['name' => 'Applications', 'sum' => 0, 'entries' => []],
			1    => ['name' => 'Websites', 'sum' => 0, 'entries' => []],
			2    => ['name' => 'Documents', 'sum' => 0, 'entries' => []],
			12   => ['name' => 'Grouped by window title', 'sum' => 0, 'entries' => []],
		];

	$computer_usage = [
			1 => 'Active',
			2 => 'Away',
			3 => 'Session lock',
			4 => 'Power off'
		];

	$name_filter = ['C:\Users\alex\AppData\Roaming\Notepad++\plugins\config\NppFTP\Cache\\'];

	$program_groups = [
			'Internet'         => ['Firefox', 'Aurora', 'Google Chrome', 'TorBrowser', 'Internet Explorer'],
			'Messaging'        => ['HexChat', 'mIRC', 'Skype', 'Miranda NG', 'Steam Client Bootstrapper', 'Steam',
								   'Mumble'],
			'Video Playback'   => ['MPC-HC', 'GOM Player', 'VLC media player'],
			'Audio Playback'   => ['foobar2000 Application'],
			'Office + Reading' => ['Microsoft Office 2010', 'Microsoft Excel', 'Microsoft PowerPoint', 'Foxit Reader'],
			'Development'      => ['Notepad++', 'PureBasic', 'Microsoft? Visual Studio? 2010', 'WinCacheGrind',
								   'Microsoft (R) Visual Studio (R) 2010', 'Visual Basic', 'PhpStorm',
								   'Sublime Text', 'Sublime Text 2', 'TortoiseGit', 'TortoiseHg', 'SQL Compact Query Analyzer',
								   'FormDesigner', 'qtcreator', 'Notepad', 'Komodo', 'GitExtensions',
								   'TortoiseSVN', 'Araxis Merge','MySQL Workbench', 'NetBeans IDE 8.0'],
			'Terminal'         => ['mintty', 'PuTTY suite', 'Windows Command Processor', 'Windows PowerShell',
								   'Console', 'Launchy', 'Remote Desktop Connection'],
			'Gaming'           => ['League of Legends (TM) Client', 'StarCraft II (Retail)', 'Torchlight II', 'Diablo III',
								   'Tropico4', 'World of Warcraft', 'hl2', 'PVP.net Patcher', 'FTLGame', 'Blizzard Launcher',
								   'portal2']
		];

	is_dir($icons_dir = 'cache/' . $current_pc . '/icons/') or mkdir($icons_dir , 0755, true);


	if (isset($_GET['groupid'])) {
		$r = $db->Query ('SELECT DisplayName, GroupId, 2 as FolderId, 4 as TimelineId, SUM(duration) as duration, MAX(EndLocalTime) as EndLocalTime, MIN(StartLocalTime) as StartLocalTime, SUM(Records) as Records
						  FROM `'.$current_pc.'_Activity`
						  WHERE GroupId = ' . intval($_GET['groupid']) . ' AND EndLocalTime <= ' . $time_end . ' AND StartLocalTime >= ' . $time_start . '
						  GROUP BY DisplayName
						  UNION
						  SELECT DisplayName, GroupId, 2 as FolderId, TimelineId, 0 as duration, 0 as EndLocalTime, 99999999999 as StartLocalTime, 0 as Records
						  FROM `'.$current_pc.'_Group`
						  WHERE GroupId = ' . intval($_GET['groupid']) . ' AND TimelineId = 3
						  ORDER BY TimelineId ASC, duration DESC
						  LIMIT ' . ($limit * 1000 + 1)
						);
	} else {
		$r = $db->Query ('SELECT g.*, sub.duration, sub.EndLocalTime, sub.StartLocalTime, SUM(sub.Records) as Records
						  FROM `'.$current_pc.'_Group` as g
						  INNER JOIN (SELECT GroupID, SUM(duration) as duration, MIN(StartLocalTime) as StartLocalTime, MAX(EndLocalTime) as EndLocalTime, SUM(Records) as Records
									  FROM `'.$current_pc.'_Activity`
									  WHERE EndLocalTime <= ' . $time_end . ' AND StartLocalTime >= ' . $time_start . '
									  GROUP BY GroupID) as sub
								USING (GroupID)
						  WHERE sub.duration >= ' . strval($min * 60) . '
						  GROUP BY GroupID
						  ORDER by duration DESC'
						);
	}

	while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
		switch ($row['TimelineId']) {
			case 3: //Applications
				if (!file_exists($icons_dir . $row['GroupId'] . '.png')) {
					file_put_contents($icons_dir .  $row['GroupId'] . '.png', base64_decode($row['Icon32']));
				}
				foreach($program_groups as $group => &$p) {
					if (in_array($row['DisplayName'], $p)) {
						if (!isset($groups[$group])) {
							$groups[$group]['DisplayName'] = $group;
							$groups[$group]['GroupId']  = $row['GroupId'];
							$groups[$group]['duration'] = 0;
						}
						$groups[$group]['duration'] += $row['duration'];
						break;
					}
				}
				$programs[] = $row;
				break;

			case 2: // Computer usage
				$row['DisplayName'] = $computer_usage[$row['GroupId']];

			default: // 1 = Tags or 4 = documents/websites
				$row['DisplayName'] = str_replace($name_filter, '', $row['DisplayName']);
				$folders[$row['FolderId']]['items'][] = $row;
				$folders[$row['FolderId']]['sum'] += $row['duration'];
				$records += $row['Records'];
		}
		if ($first > $row['StartLocalTime']) $first = $row['StartLocalTime'];
		if ($last < $row['EndLocalTime']) $last = $row['EndLocalTime'];
	}

	$last_update = $db->Query('SELECT MAX(EndLocalTime) FROM `'.$current_pc.'_Activity`')->fetchColumn();

	$sql_time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];

	$items = $r->rowCount();

	usort($groups, function($a, $b) {
		return $a['duration'] < $b['duration'] ? 1 : -1;
	});
?>
<form method="get" autocomplete="off" id="content">
	<div class="header">
		<select id="pc-selector" name="pc">
		<?php
			foreach($computers as $pc => $label) {
				echo '<option value="' . $pc . '"' . ($pc === $current_pc ? ' selected':'') . '>' . $label . '</option>';
			}
		?>
		</select>
		<div class="right">
			Display <input type="text" name="limit" value="<?=$limit?>" class="number-input"><big>k</big>
			entries with at least
			<input type="text" name="min" value="<?=$min?>" class="number-input"> minutes usage.
			&nbsp;&nbsp;
			From
			<input type="text" name="start" class="date" value="<?=date('Y-m-d', $time_start)?>" size="9">
			to
			<input type="text" name="end" class="date" value="<?=date('Y-m-d', $time_end)?>" size="9">
			<input type="submit" value="Go">
		</div>
	</div>
	<div class="range">
		<div class="right">Updated: <?php echo '<strong>' . date('Y-m-d \a\t H:i', $last_update) . '</strong>' ?></div>
		<small class="left"><a href="crunch.php?pc=<?=$current_pc?>">Compact records</a> SQL: <strong><?php echo round($sql_time, 4) . ' sec.';  ?></strong></small>
		<?php echo '<strong>' . $items . '</strong> items (<strong>' . $records . '</strong> records) in actual date range: <strong>' . date('Y-m-d', $first) . '</strong> to <strong>' . date('Y-m-d', $last) . '</strong>'; ?>
	</div>
	<div class="wrapper <?=isset($_GET['groupid'])?'smaller':''?>">
		<?php if (empty($programs) && empty($folders)) { echo 'Nothing to see'; } ?>
		<div class="applications">
			<?php if (isset($_GET['groupid'])) { ?>
				<button type="submit" style="width:100%;text-align:center">
					<img src="assets/images/arrow-back-128.png" style="width:32px;"><span style="position:relative;top:-8px;font-size: big"><strong>Go back</strong></span>
				</button>
				<hr>
			<?php } ?>

			<?php
				foreach([$groups, $programs] as &$set) {
					if ($set) {
						echo '<table>';
						foreach($set as $row) {
							//<a href="?'.build_query(['groupid' => $row['GroupId']]).'">
							echo '<tr title="'.htmlentities(@$row['TextData']).'" class="group" data-group="'.$row['GroupId'].'"><td class="icon"><img src="' . $icons_dir . $row['GroupId'] . '.png"></td>';
							echo '<td><span class="clickable">'.short($row['DisplayName'], 34, false) . '</span></td><td style="text-align:right;">' . hm($row['duration']) . '</td></tr>';
						}
						echo '</table><hr>';
					}
				}
			?>
		</div>
		<div class="entries">
		<?php
			foreach($folders as $id => $folder) {
				if (empty($folder['items'])) continue;
				echo '<h1>' . $folder['name'] . ' <small>(' . count($folder['items']) . ' items for ' . round($folder['sum']/3600) . ' hours)</small></h1>';

				if (count($folder['items']) > 10)
					echo '<table class="sort"><thead><tr><th>Document</th><th>Time</th><th>Percent</th></tr></thead><tbody>';
				else
					echo '<table><tbody>';

				foreach($folder['items'] as $i => $row) {
					echo '<tr class="group" data-group="'.$row['GroupId'].'">
								 <td title="This entry was present from  ' . date('Y-m-d H:i', $row['StartLocalTime']) . '  to  ' . date('Y-m-d H:i', $row['EndLocalTime']) . '"
									 class="name"><span class="clickable">' . htmlentities(short($row['DisplayName'], 120)) . '</span></td>'.
								'<td data-order="'.$row['duration'].'">' . hm($row['duration']) . '</td>'.
								'<td data-order="'.$row['duration'].'">' . sprintf('%.2f', $row['duration']/$folder['sum'] * 100) .'%</td></tr>';
				}
				echo '</tbody></table>';
			}
		?>
		</div>
	</div>
</form>
<div style="text-align:center; clear:both;">
	<hr>
	ManicTimeWeb
</div>
<script src="assets/js/jquery.datetimepicker.js"></script>
<script src="assets/js/jquery.dataTables.min.js"></script>
<script src="assets/js/app/index.js"></script>
</body>
</html>