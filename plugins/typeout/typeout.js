$( document ).ready(function() {
	$('#typeout').focus();
	
	document.execCommand('enableObjectResizing');
	document.execCommand('enableInlineTableEditing');

	$('.typeout-exec').on("click", function() {
		document.execCommand($(this).data('typeout-command'));
	});

	$('.typeout-input').on("click", function() {
		var savedSel = saveSelection();
		var inputData = prompt($(this).data('typeout-info'), '');
		restoreSelection(savedSel);
		if ($(this).data('typeout-command')=='insertPDF') {
			if(inputData)
				inputData='<iframe border="0" width="100%" height="600px" src="https://drive.google.com/viewer?embedded=true&url='+inputData+'"></iframe>';
			command='insertHTML';
		}
		else
			command=$(this).data('typeout-command');
		if (inputData)
			document.execCommand(command, '', inputData);
	});

    $(".typeout-content").focusout(function(){
        var element = $(this);        
        if (!element.text().replace(" ", "").length) {
            element.empty();
        }
    });
});

function restoreSelection (savedSel) {
    if (savedSel) {
        if (window.getSelection) {
            sel = window.getSelection();
            sel.removeAllRanges();
            for (var i = 0, len = savedSel.length; i < len; ++i) {
                sel.addRange(savedSel[i]);
            }
        } else if (document.selection && savedSel.select) {
            savedSel.select();
        }
    }
}

function saveSelection () {
	sel = window.getSelection();
	if (sel && sel.getRangeAt && sel.rangeCount) {
        var ranges = [];
        for (var i = 0, len = sel.rangeCount; i < len; ++i) {
            ranges.push(sel.getRangeAt(i));
        }
        return ranges;
    }
    else if (document.selection && document.selection.createRange) {
        return document.selection.createRange();
    }
    else {
	    return 0;
    }
}