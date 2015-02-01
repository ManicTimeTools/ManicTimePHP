<?php
	require 'common.php';
?>
<html>
<head>
	<meta charset="UTF-8">
	<title>Sleep Chart</title>
	<style>
		.graph {
			width: 100%;
			height: 100%;
		}
	</style>
</head>
<body>

	<div class="row">
		<div class="sleep-overview graph" data-url="sleep/overview.php"></div>
	</div>

	<script src="assets/js/jquery.js"></script>
	<script src="assets/js/lodash.js"></script>
	<script src="assets/js/highcharts.js"></script>
	<script src="assets/js/app/sleep.js"></script>
</body>
</html>
