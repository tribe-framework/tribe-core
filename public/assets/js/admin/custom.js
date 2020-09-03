$( document ).ready(function() {
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
		$('#title-slug').html(data.last_data[0].slug);
		$('.view_btn').removeClass('disabled').attr('href', data.last_data[0].url);
		$('#slug_update_div').addClass('d-block').removeClass('d-none');
		$('.save_btn').prop('disabled', false);
		$('.save_btn').html(btn_html);
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