$( document ).ready(function() {
	$('.datatable').DataTable({
		"dom": '<"top"ifl>rt<"bottom"Bp>',
		"pageLength":50,
		"order": [[ 0, "desc" ]],
        "buttons": [{
            extend: 'collection',
            text: '<span class="fas fa-file-export"></span>&nbsp;&nbsp;Export data',
            buttons: [
	        	{"extend": 'excel', "text": '<span class="fas fa-file-excel"></span>&nbsp;&nbsp;.xlsx', "title": 'data'},
	        	{"extend": 'pdf', "text": '<span class="fas fa-file-pdf"></span>&nbsp;&nbsp;.pdf', "title": 'data'}
        	]
        }]
	});
});