key('⌘+s, ctrl+s', function(e){$('.save_btn').trigger('click'); e.preventDefault();});
key('⌘+b, ctrl+b', function(e){$('.typeout-bold').trigger('click'); e.preventDefault();});
key('⌘+i, ctrl+i', function(e){$('.typeout-italic').trigger('click'); e.preventDefault();});

$( document ).ready(function() {
	$('.typeout-content').each(function() {update_textarea($(this).data('input-slug'));});
	$(document).on('keyup', '.typeout-content', function() {update_textarea($(this).data('input-slug'));});
	$(document).on('blur', '.typeout-content', function() {update_textarea($(this).data('input-slug'));});

	$(document).on('submit', '.edit_form', function(e) {
		e.preventDefault();
		btn_html=$(this).closest('.save_btn').html();
		$(this).closest('.save_btn').html('<div class="spinner-grow" role="status"><span class="sr-only">Loading...</span></div>');
		$.post('json.php', $(this).serialize(), function(data) {
			process_json_out(data, 'Save');
		}, 'json');
	});

	$(document).on('click', '.multi_add_btn', function(e) {
		$(this).closest('#url-group-'+$(this).data('input-slug')+' .input-group').first().clone().appendTo('#url-group-'+$(this).data('input-slug'));
		$('#url-group-'+$(this).data('input-slug')+' .input-group:last input').val('');
	});

	$(document).on('click', '.delete_btn', function(e) {
		$(this).closest('p.file').remove();
	});

	$('.datatable').DataTable({"pageLength":50,"order": [[ 0, "desc" ]]});

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

    new ClipboardJS('.copy_btn');
});

function process_json_out (data, btn_html='') {
	if (data.last_error) {
		$('#errors').removeClass('d-none').addClass('d-block').html(data.last_error);
	}
	if (data.last_info) {
		if (!($('input[name="id"]').val()))
			$('input[name="id"]').val(data.last_data[0].id);
		$('input[name="slug"]').val(data.last_data[0].slug);
		$('.view_btn').removeClass('disabled').attr('href', data.last_data[0].url);
		$('#slug_update_div').addClass('d-block').removeClass('d-none');
		alert(btn_html);
		$(this).closest('.save_btn').html(btn_html);
		//$('#infos').removeClass('d-none').addClass('d-block').html(data.last_info);
		//scroll_to_anchor('infos');
	}
	if (data.last_redirect) {
		$(location).attr('href', data.last_redirect);
	}
}

function spinner_start (btn) {
	btn_txt = $(btn).html();
	$(btn).html('<span class="fas fa-sm fa-spinner"></span>');
	return btn_txt;
}

function spinner_stop (btn, btn_txt) {
	$(btn).html(btn_txt);
}

function parseGoogleResponse (components) {
    var newComponents = {}, type;
    $.each(components, function(i, component) {
      type = component.types[0];
      newComponents[type] = {
        long_name: component.long_name,
        short_name: component.short_name
      }
    });
    return JSON.stringify(newComponents);
}

function update_textarea (typeout_slug) {
	$('.edit_form input[name="'+typeout_slug+'"]').val($('#typeout-'+typeout_slug).html());
	//.find('script, link, html, head, meta, title, body').remove()
}

function scroll_to_anchor (aid) {
    var aTag = $("a[name='"+ aid +"']");
    $('html,body').animate({scrollTop: aTag.offset().top},'slow');
}