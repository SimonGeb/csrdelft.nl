/**
 * csrdelft.nl javascript libje...
 */

var http = new XMLHttpRequest(); //DEPRECATED

function preload(arrayOfImages) {
	$(arrayOfImages).each(function() {
		$('<img/>')[0].src = this;
	});
}

preload([
	'http://plaetjes.csrdelft.nl/layout/loading-arrows.gif',
	'http://plaetjes.csrdelft.nl/layout/loading_bar_black.gif'
]);

$(document).ready(function() {
	init_links();
	init_buttons();
	init_forms();
	init_hoverIntents();
	init_groepen();
});

function page_reload() {
	location.reload();
}

function isShiftKeyDown(event) {
	if ((window.event && window.event.shiftKey) || event.shiftKey) {
		return true;
	}
	return false;
}
function isCtrlKeyDown(event) {
	if ((window.event && window.event.ctrlKey) || event.ctrlKey) {
		return true;
	}
	return false;
}

function init_buttons() {
	$('button.spoiler').unbind('click.spoiler');
	$('button.spoiler').bind('click.spoiler', function(event) {
		event.preventDefault();
		var button = $(this);
		var content = button.next('div.spoiler-content');
		content.toggle(800, 'easeInOutCubic', function() {
			if (content.is(':visible')) {
				button.html('Verberg verklapper');
			} else {
				button.html('Toon verklapper');
			}
		});
	});
	$('button.popup').unbind('click.popup');
	$('button.popup').bind('click.popup', function(event) {
		popup_open();
	});
	$('button.post').unbind('click.post');
	$('button.post').bind('click.post', knop_post);
	$('button.get').unbind('click.get');
	$('button.get').bind('click.get', knop_get);
}

function init_hoverIntents() {
	$('.hoverIntent').hoverIntent({
		over: function() {
			$(this).find('.hoverIntentContent').fadeIn();
		},
		out: function() {
			$(this).find('.hoverIntentContent').fadeOut();
		},
		timeout: 250
	});
}

function init_links() {
	$('a.popup').unbind('click.popup');
	$('a.popup').bind('click.popup', popup_open);
	$('a.post').unbind('click.post');
	$('a.post').bind('click.post', knop_post);
	$('a.get').unbind('click.get');
	$('a.get').bind('click.get', knop_get);
}

function knop_ajax(knop, type) {
	if (knop.hasClass('confirm') && !confirm(knop.attr('title') + '.\n\nWeet u het zeker?')) {
		popup_close();
		return false;
	}
	if (knop.hasClass('prompt')) {
		var data = knop.attr('postdata');
		data = data.split('=');
		var val = prompt(data[0], data[1]);
		if (!val) {
			return false;
		}
		knop.attr('postdata', encodeURIComponent(data[0]) + '=' + encodeURIComponent(val));
	}
	var source = knop;
	var done = dom_update;
	if (knop.hasClass('popup')) {
		source = false;
		done = popup_open;
	}
	if (knop.hasClass('ReloadPage')) {
		done = page_reload;
	}
	ajax_request(type, knop.attr('href'), knop.attr('postdata'), source, done, alert);
}

function knop_post(event) {
	event.preventDefault();
	if ($(this).hasClass('range')) {
		if (event.target.tagName.toUpperCase() === 'INPUT') {
			taken_select_range(event);
		}
		else {
			taken_submit_range(event);
		}
		return false;
	}
	knop_ajax($(this), 'POST');
	return false;
}

function knop_get(event) {
	event.preventDefault();
	knop_ajax($(this), 'GET');
	return false;
}

function popup_open(htmlString) {
	if (htmlString) {
		$('#popup').html(htmlString);
		init_links();
		init_buttons();
		init_forms();
		init_hoverIntents();
		$('#popup').show();
		$('#popup-background').css('background-image', 'none');
		$('#popup input:visible:first').focus();
	}
	else {
		$('#popup-background').css('background-image', 'url("http://plaetjes.csrdelft.nl/layout/loading_bar_black.gif")');
		$('#popup').hide();
		$('#popup').html('');
	}
	$('#popup-background').fadeIn();
}

function popup_close() {
	$('#popup').hide();
	$('#popup').html('');
	$('#popup-background').fadeOut();
}

function init_forms() {
	$('.Formulier').each(function() {
		var formId = $(this).attr('id');
		if (formId) {
			var init = 'form_ready_' + formId;
			init = init.split('-').join('_');
			if (typeof window[init] === 'function') {
				window[init]();
			}
		}
		$(this).unbind('submit.enter');
		$(this).bind('submit.enter', form_submit);
		$(this).unbind('keyup.esc');
		$(this).bind('keyup.esc', form_esc);
	});
	$('.submit').unbind('click.submit');
	$('.submit').bind('click.submit', form_submit);
	$('.reset').unbind('click.reset');
	$('.reset').bind('click.reset', form_reset);
	$('.cancel').unbind('click.cancel');
	$('.cancel').bind('click.cancel', form_cancel);
	$('.InlineFormToggle').unbind('click.toggle');
	$('.InlineFormToggle').bind('click.toggle', form_toggle);
	$('.SubmitChange').unbind('change.change');
	$('.SubmitChange').bind('change.change', form_submit);
	// Resize popup to width of textarea
	$('#popup .TextareaField').unbind('mousedown.resize');
	$('#popup .TextareaField').bind('mousedown.resize', function() {
		$(this).unbind('mousemove.resize');
		$(this).bind('mousemove.resize', function() {
			var width = 7 + parseInt($(this).css('width'));
			$('#popup').css('min-width', width);
			$('#popup .InputField').css('min-width', width);
		});
	});
	$('#popup .TextareaField').unbind('mouseup.resize');
	$('#popup .TextareaField').bind('mouseup.resize', function() {
		$(this).trigger('mousemove.resize');
		$(this).unbind('mousemove.resize');
	});
}

function form_ischanged(form) {
	var changed = false;
	$(form).find('.FormField').each(function() {
		if ($(this).is('input:radio')) {
			if ($(this).is(':checked') && $(this).attr('origvalue') !== $(this).val()) {
				changed = true;
				return false; // break each
			}
		}
		else if ($(this).is('input:checkbox')) {
			if ($(this).is(':checked') !== ($(this).attr('origvalue') === '1')) {
				changed = true;
				return false; // break each
			}
		}
		else if ($(this).val() !== $(this).attr('origvalue')) {
			changed = true;
			return false; // break each
		}
	});
	return changed;
}

function form_replace_action(event) {
	var form = $(event.target).closest('form');
	var url = $(event.target).closest('a.knop').attr('href');
	form.attr('action', url);
}

function toggle_inline_none(elmnt) {
	$(elmnt).css('display', $(elmnt).css('display') === 'none' ? 'inline' : 'none');
}

function form_inline_toggle(form) {
	$(form).find('.InputField').each(function() {
		toggle_inline_none($(this));
	});
	$(form).find('.FormButtons').each(function() {
		toggle_inline_none($(this));
	});
	$(form).find('.InlineFormToggle').toggle();
	$(form).find('.FormField').focus();
}

function form_toggle(event) {
	var form = $(this).closest('form');
	event.preventDefault();
	form_inline_toggle(form);
	return false;
}

function form_esc(event) {
	if (event.keyCode === 27) {
		form_cancel(event);
	}
}

function form_submit(event) {
	var form = $(this).closest('form');
	if (form.hasClass('PreventUnchanged') && !form_ischanged(form)) {
		event.preventDefault();
		alert('Geen wijzigingen');
		return false;
	}
	if (form.hasClass('popup') || form.hasClass('InlineForm')) {
		event.preventDefault();
		var source = false;
		if (form.hasClass('InlineForm')) {
			source = form;
		}
		var done = dom_update;
		if (form.hasClass('ReloadPage')) {
			done = page_reload;
		}
		var formData = new FormData(form.get(0));
		ajax_request('POST', form.attr('action'), formData, source, done, alert, function() {
			if (form.hasClass('SubmitReset')) {
				form_reset(event, form);
			}
		});
		return false;
	}
	form.unbind('submit');
	form.submit();
	return true;
}

function form_reset(event, form) {
	if (!form) {
		form = $(this).closest('form');
		event.preventDefault();
	}
	form.find('.FormField').each(function() {
		var orig = $(this).attr('origvalue');
		if (orig) {
			$(this).val(orig);
		}
	});
	return false;
}

function form_cancel(event) {
	var form = $(this).closest('form');
	if (form.hasClass('InlineForm')) {
		event.preventDefault();
		form_inline_toggle(form);
		return false;
	}
	if ($(this).hasClass('post')) {
		event.preventDefault();
		knop_post(event);
		return false;
	}
	if (form.hasClass('popup')) {
		event.preventDefault();
		if (!form_ischanged(form) || confirm('Sluiten zonder wijzigingen op te slaan?')) {
			popup_close();
		}
		return false;
	}
	return true;
}

function dom_update(htmlString) {
	htmlString = $.trim(htmlString);
	if (htmlString.substring(0, 9) === '<!DOCTYPE') {
		alert('response error');
		document.write(htmlString);
	}
	var html = $.parseHTML(htmlString);
	$(html).each(function() {
		var id = $(this).attr('id');
		if (id === 'popup-content') {
			popup_open(htmlString);
		}
		else {
			popup_close();
		}
		var elmnt = $('#' + id);
		if (elmnt.length === 1) {
			if ($(this).hasClass('remove')) {
				elmnt.effect('puff', {}, 400, remove);
			}
			else {
				elmnt.replaceWith($(this)).effect('highlight');
			}
		}
		else {
			var parentid = $(this).attr('parentid');
			if (parentid) {
				$(this).prependTo('#' + parentid).effect('highlight');
			}
			else {
				$(this).prependTo('#maalcie-tabel tbody:visible:first').effect('highlight'); //FIXME: make generic
			}
		}
		init_links();
		init_buttons();
		init_forms();
		init_hoverIntents();
	});
}

function remove() {
	$(this).remove();
}

function ajax_request(type, url, data, source, onsuccess, onerror, onfinish) {
	if (source) {
		$(source).replaceWith('<img title="' + url + '" src="http://plaetjes.csrdelft.nl/layout/loading-arrows.gif" />');
		source = 'img[title="' + url + '"]';
	}
	else {
		popup_open();
	}
	var contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
	var processData = true;
	if (data instanceof FormData) {
		contentType = false;
		processData = false;
	}
	var jqXHR = $.ajax({
		type: type,
		dataType: false,
		contentType: contentType,
		processData: processData,
		url: url,
		cache: false,
		data: data
	});
	jqXHR.done(function(data, textStatus, jqXHR) {
		onsuccess(data);
	});
	jqXHR.fail(function(jqXHR, textStatus, errorThrown) {
		if (errorThrown === '') {
			errorThrown = 'Nog bezig met laden!';
		}
		if (source) {
			$(source).replaceWith('<img title="' + errorThrown + '" src="http://plaetjes.csrdelft.nl/famfamfam/cancel.png" />');
		}
		else {
			popup_close();
		}
		if (onerror) {
			onerror(errorThrown);
		}
	});
	jqXHR.always(function() {
		if (onfinish) {
			onfinish();
		}
	});
}

function ketzer_ajax(url, ketzer) {
	$(ketzer + ' .aanmelddata').html('Aangemeld:<br /><img src="http://plaetjes.csrdelft.nl/layout/loading-arrows.gif" />');
	var jqXHR = $.ajax({
		type: 'GET',
		cache: false,
		url: url,
		data: ''
	});
	jqXHR.done(function(data, textStatus, jqXHR) {
		var html = $.parseHTML(data);
		$('.ubb_maaltijd').each(function() {
			if ($(this).attr('id') === $(html).attr('id')) {
				$(this).replaceWith(data);
			}
		});
	});
	jqXHR.fail(function(jqXHR, textStatus, errorThrown) {
		$(ketzer + ' .aanmelddata').html('<span style="color: red; font-weight: bold;">Error: </span>' + errorThrown);
		alert(errorThrown);
	});
	return true;
}

function peiling_bevestig_stem(peiling) {
	var id = $('input[name=optie]:checked', peiling).val();
	var waarde = $('#label' + id).text();
	if (confirm('Bevestig uw stem:\n\n' + waarde + '\n\n')) {
		$(peiling).submit();
	}
}
/**
 * Selecteer de tekst van een DOM-element
 * http://stackoverflow.com/questions/985272/jquery-selecting-text-in-an-element-akin-to-highlighting-with-your-mouse/987376#987376
 * 
 * @param id DOM-object
 */
function selectText(id) {
	var doc = document;
	var text = doc.getElementById(id);
	var range;
	var selection;
	if (doc.body.createTextRange) { //ms
		range = doc.body.createTextRange();
		range.moveToElementText(text);
		range.select();
	} else if (window.getSelection) { //all others
		selection = window.getSelection();
		range = doc.createRange();
		range.selectNodeContents(text);
		selection.removeAllRanges();
		selection.addRange(range);
	}
}

function vergrootTextarea(id, rows) {
	jQuery('#' + id).animate({'height': '+=' + rows * 30}, 800, function() {
	});
}

function applyUBB(string, div) {
	var jqXHR = $.ajax({
		type: 'POST',
		cache: false,
		url: '/tools/ubb.php',
		data: 'string=' + encodeURIComponent(string)
	});
	jqXHR.done(function(data, textStatus, jqXHR) {
		$(div).html(data);
		init_links();
		init_buttons();
		init_forms();
		init_hoverIntents();
	});
}

function youtubeDisplay(ytID) {
	$('#youtube' + ytID).html('<object width="640" height="480">' +
			'<param name="movie" value="http://www.youtube.com/v/' + ytID + '&autoplay=1&fs=1"></param><param name="allowFullScreen" value="true"></param>' +
			'<embed src="http://www.youtube.com/v/' + ytID + '&autoplay=1&fs=1" type="application/x-shockwave-flash" wmode="transparent" width="640" height="480" allowfullscreen="true"></embed></object>');
	return false;
}

function readableFileSize(size) {
	var units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
	var i = 0;
	while (size >= 1024) {
		size /= 1024;
		++i;
	}
	size = size / 1;
	return size.toFixed(1) + ' ' + units[i];
}

function importAgenda(id) {
	var jqXHR = $.ajax({
		type: 'POST',
		cache: false,
		url: '/agenda/courant/',
		data: ''
	});
	jqXHR.done(function(data, textStatus, jqXHR) {
		document.getElementById(id).value += "\n" + data;
	});
}

function ubbPreview(source, dest) {
	var ubb = document.getElementById(source).value;
	if (ubb.length !== '') {
		var previewDiv = document.getElementById(dest);
		applyUBB(ubb, previewDiv);
		previewDiv.style.display = 'block';
	}
}