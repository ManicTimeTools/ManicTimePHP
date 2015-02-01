$('.date').datetimepicker({
	timepicker:false,
	format:'Y-m-d',
	closeOnDateSelect:true,
	onSelectDate: function(a,b) {b.blur();}
});
$('table.sort').dataTable({
	pageLength: 15,
	lengthMenu: [[15, 50, 100, 250, -1], [15, 50, 100, 250, 'All']],
	aaSorting: [[1, 'desc']],
	autoWidth: false,
});

$('form').submit(function () {
	$('form select[name^=DataTables_Table]').removeAttr('name');
});

$('select#pc-selector').change(function(e) {
	$('form').submit();
});
$('.application img').click(function() {
	$(this).parent().dblclick();
});
$(document).on('dblclick', '.group', function() {
	window.getSelection().removeAllRanges();
	$('form').find('input[name=groupid]').remove();
	$('form').append('<input type="hidden" name="groupid" value="' + $(this).attr('data-group') + '">').submit();
});
$('#wait').fadeOut('slow');
$('#content').fadeIn('fast');
