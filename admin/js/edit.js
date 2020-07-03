$( document ).ready(function() {
	$('.packery-table').DataTable({
		"dom": '<"top"f>rt<"bottom">',
		"pageLength":50,
		"order": [[ 0, "desc" ]]
	});
});