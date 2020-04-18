$( document ).ready(function() {

	update_textarea($('#typeout-content').data('input-slug'));
	$('#typeout-content').keyup(function() {update_textarea($('#typeout-content').data('input-slug'));});
	$(document).on('blur', '#typeout-content', function() {update_textarea($('#typeout-content').data('input-slug'));});

	$(document).on('submit', '.edit_form', function(e) {
		e.preventDefault();
		$.post('json.php', $(this).serialize(), function(data) {
			process_json_out(data);
		}, 'json');
	});

	$(document).on('click', '.multi_add_btn', function(e) {
		$(this).closest('.url-group .input-group').first().clone().appendTo('.url-group');
		$('.url-group .input-group:last input').val('');
	});

	$('.datatable').DataTable({"order": [[ 0, "desc" ]]});

    $('#edit_form input[type=file]').fileupload({
        dataType: 'json',
		add: function(e, data) {
			$('#progress').parent().removeClass('d-none');
		    data.context = $('<p class="file">')
		      .append($('<span>').text(data.files[0].name))
		      .appendTo('#'+$(this).attr('id')+'_fileuploads');
		    data.submit();
		},
		progress: function(e, data) {
		    var progress = parseInt((data.loaded / data.total) * 100, 10);
		    $('#progress .bar').css('width', progress + '%');
		},
		done: function(e, data) {
		    data.context
		      .append('&nbsp;&nbsp;<span class="text-primary copy_btn" data-clipboard-text="'+data.result.files[0].url+'"><span class="fas fa-link"></span></span>&nbsp;&nbsp;<a style="display: inline;" class="text-primary" href="'+data.result.files[0].url+'" target="new"><span class="fas fa-external-link-alt"></span></a>')
		      .addClass("done");
		}
    });

    new ClipboardJS('.copy_btn');
});

function process_json_out (data) {
	if (data.last_error) {
		$('#errors').removeClass('d-none').addClass('d-block').html(data.last_error);
	}
	if (data.last_info) {
		if (!($('input[name="id"]').val()))
			$('input[name="id"]').val(data.last_data[0].id);
		$('input[name="slug"]').val(data.last_data[0].slug);
		$('#slug_update_div').addClass('d-block').removeClass('d-none');
		$('#infos').removeClass('d-none').addClass('d-block').html(data.last_info);
		scroll_to_anchor('infos');
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
	$('#edit_form input[name="'+typeout_slug+'"]').val($('#typeout-content').html());
	//.find('script, link, html, head, meta, title, body').remove()
}

function scroll_to_anchor (aid) {
    var aTag = $("a[name='"+ aid +"']");
    $('html,body').animate({scrollTop: aTag.offset().top},'slow');
}