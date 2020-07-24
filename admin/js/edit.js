key('⌘+s, ctrl+s', function(e){$('.save_btn').trigger('click'); e.preventDefault();});
key('⌘+b, ctrl+b', function(e){$('.typeout-bold').trigger('click'); e.preventDefault();});
key('⌘+i, ctrl+i', function(e){$('.typeout-italic').trigger('click'); e.preventDefault();});

$( document ).ready(function() {
	$('.multi_drop_select_table').DataTable({
		"dom": '<"top"f>rt<"bottom">',
		"pageLength":50,
		"order": [[ 0, "desc" ]]
	});

	$('.typeout-content').each(function() {update_textarea($(this).data('input-slug'));});

	$(document).on('keyup', '.typeout-content', function() {update_textarea($(this).data('input-slug'));});
	$(document).on('blur', '.typeout-content', function() {update_textarea($(this).data('input-slug'));});

	$(document).on('submit', '.edit_form', function(e) {
		e.preventDefault();
		var btn_html=$('.save_btn').html();
		$('.save_btn').html('<div class="spinner-border spinner-border-sm mb-1" role="status"><span class="sr-only">Loading...</span></div>&nbsp;Save');
		$('.save_btn').prop('disabled', true);
		$.post('json.php', $(this).serialize(), function(data) {
			process_json_out(data, btn_html);
		}, 'json');
	});

	$(document).on('click', '.multi_add_btn', function(e) {
		$(this).closest('#'+$(this).data('group-class')+'-'+$(this).data('input-slug')+' .input-group').first().clone().appendTo('#'+$(this).data('group-class')+'-'+$(this).data('input-slug'));
		$('#'+$(this).data('group-class')+'-'+$(this).data('input-slug')+' .input-group:last input').val('');
	});

	$(document).on('click', '.remove_multi_drop_option', function(e) {
		e.preventDefault();
		$(this).closest('.grid-item').remove();
	});

	$(document).on('click', '.select_multi_drop_option', function(e) {
		e.preventDefault();
		$('#'+$(this).data('multi_drop_filled_table')+' .grid').append('<div class="bg-light grid-item p-3">'+$('#'+$(this).data('multi_drop_option_text')).text()+' <a href="#" class="float-right remove_multi_drop_option"><span class="fas fa-minus-circle"></span></a><input type="hidden" name="'+$(this).parent().data('name')+'" value="'+$(this).parent().data('value')+'"></div>');
	});

	$('.grid').packery({'itemSelector': '.grid-item'});

	$(document).on('click', '.delete_btn', function(e) {
		$(this).closest('p.file').remove();
	});

	var sli=0;
    $('.edit_form input[type=file]').fileupload({
        dataType: 'json',
		add: function(e, data) {
			$('#progress').parent().removeClass('d-none');
		    data.context = $('<p class="mt-2 file">')
		      .append($('<span>').text(data.files[0].name))
		      .appendTo('#'+$(this).attr('id')+'_fileuploads');
		    data.submit();
		},
		progress: function(e, data) {
		    var progress = parseInt((data.loaded / data.total) * 100, 10);
		    $('#progress .bar').css('width', progress + '%');
		},
		done: function(e, data) {
			sli++;
			slvl='';
			if ($(this).data('bunching')) {
				slvl+='&nbsp;&nbsp;<select class="btn btn-sm btn-outline-primary" name="'+$(this).attr('id')+'_bunching[]'+'">';
				slvl+='<option value="">file option</option>';
				$.each($(this).data('bunching'), function(i, item) {
					slvl+='<option value="'+item.slug+'">'+item.title+'</option>';
				});
				slvl+='</select>';
			}
			if ($(this).data('descriptor')) {
				slvl+='&nbsp;&nbsp;<button type="button" class="btn btn-sm btn-outline-primary m-1" data-toggle="modal" data-target="#'+$(this).attr('id')+'_descriptor_'+sli+'">descriptor</button><div class="modal fade" id="'+$(this).attr('id')+'_descriptor_'+sli+'" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">add file descriptor</h5><button type="button" class="close" data-dismiss="modal" aria-label="close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><textarea name="'+$(this).attr('id')+'_descriptor[]'+'" class="form-control" placeholder="enter file descriptor"></textarea><input name="'+$(this).attr('id')+'_descriptor_date[]'+'" type="date" class="form-control" placeholder="enter file date"></div><div class="modal-footer"><button type="button" class="btn btn-sm btn-outline-primary" data-dismiss="modal">save</button></div></div></div></div>';
			}
		    data.context
		      .append('&nbsp;&nbsp;<span class="delete_btn btn btn-sm btn-outline-danger"><span class="fas fa-trash-alt"></span></span><input type="hidden" name="'+$(this).attr('id')+'[]'+'" value="'+data.result.files[0].url+'">&nbsp;&nbsp;<span class="copy_btn btn btn-sm btn-outline-primary" data-clipboard-text="'+data.result.files[0].url+'"><span class="fas fa-link"></span>&nbsp;copy URL</span>&nbsp;&nbsp;<a style="display: inline-block;" class="btn btn-sm btn-outline-primary" href="'+data.result.files[0].url+'" target="new"><span class="fas fa-external-link-alt"></span>&nbsp;view</a>'+slvl)
		      .addClass("done");
		}

    });
});