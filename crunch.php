<?php
require 'common.php';

ignore_user_abort(1);

if ($_POST) {
	$q = $db->Query("SELECT ActivityId, GroupId, TimelineId, DisplayName, MIN(StartLocalTime) as StartLocalTime, MAX(EndLocalTime) as EndLocalTime, SUM(Duration) as Duration, LocalDate, COUNT(*) as Records
					 FROM `{$current_pc}_Activity`
					 WHERE FROM_UNIXTIME(`StartLocalTime`, '%Y%m%d') = FROM_UNIXTIME(`EndLocalTime`, '%Y%m%d')
					 GROUP BY CONCAT(LocalDate, '-', DisplayName, '-', TimelineId, '-', GroupId)");
	$total = 0;
	while($r = $q->fetch(PDO::FETCH_ASSOC)) {
		if ($r['Records'] == 1) continue;
		$db->Exec(sprintf("DELETE FROM `{$current_pc}_Activity` WHERE LocalDate = %s AND DisplayName = %s AND GroupId = %s AND TimelineId = %s AND FROM_UNIXTIME(`StartLocalTime`, '%%Y%%m%%d') = FROM_UNIXTIME(`EndLocalTime`, '%%Y%%m%%d')",
						  $r['LocalDate'], $db->quote($r['DisplayName']), $r['GroupId'], $r['TimelineId']));
		$db->Exec("INSERT INTO `{$current_pc}_Activity` (ActivityId, TimelineId, GroupId, StartLocalTime, EndLocalTime, LocalDate, Duration, Records, DisplayName)
				   VALUES({$r['ActivityId']}, {$r['TimelineId']}, {$r['GroupId']}, {$r['StartLocalTime']}, {$r['EndLocalTime']}, {$r['LocalDate']}, {$r['Duration']}, {$r['Records']}, ". $db->quote($r['DisplayName']) .')');
		$last_crunched_id = $db->lastInsertId();
		$total += $r['Records'] - 1;
		echo "Crunched {$r['Records']} records into one...<br>";
	}
	
	if ($last_crunched_id)
		$db->Exec("UPDATE computers SET last_crunched_id = $last_crunched_id WHERE pc = '$current_pc'");
		
	die("Freed $total records!<br>");
}
?>
<p>
	ManicTime creates periodic records even if you use the same application for a while.<br>
	This script will find the first record and the last record of each session and delete anything between them.<br>
	There is no data loss as the start and end time (and duration) of the session are kept.<br>
</p>
<p>
	There could be a valid reason for ManicTime to create those records and in the future we may want to use them, <br>
	I suggest you use this script only if ManicTimeWeb is unbearably slow.
</p>
<form method="post">This might take a while and don't worry if the page times out... <input name="go" type="submit" value="Crunch database now!"></form>
