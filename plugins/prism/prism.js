$.getScript('/plugins/timeme.min.js', function() {

	TimeMe.initialize({
	    idleTimeoutInSeconds: 60
	});

	$(document).ready(function() {
		
		//initialize common prism_visit_id
		var prism_visit_id;
		
		//add device details, screen size and other one-time page-load details
		var load_data = {
			"timeOpened": new Date(),
			"timeZone": (new Date()).getTimezoneOffset()/60,
			"pageOn": window.location.pathname,
			"referrer": document.referrer,
			"previousSites": history.length,
			"browserName": navigator.appName,
			"browserEngine": navigator.product,
			"browserVersion1a": navigator.appVersion,
			"browserVersion1b": navigator.userAgent,
			"browserLanguage": navigator.language,
			"browserOnline": navigator.onLine,
			"browserPlatform": navigator.platform,
			"javaEnabled": navigator.javaEnabled(),
			"dataCookiesEnabled": navigator.cookieEnabled,
			"dataCookies1": document.cookie,
			"dataCookies2": decodeURIComponent(document.cookie.split(";")),
			"dataStorage": localStorage,
			"sizeScreenW": screen.width,
			"sizeScreenH": screen.height,
			"sizeDocW": document.width,
			"sizeDocH": document.height,
			"sizeInW": innerWidth,
			"sizeInH": innerHeight,
			"sizeAvailW": screen.availWidth,
			"sizeAvailH": screen.availHeight,
			"scrColorDepth": screen.colorDepth,
			"scrPixelDepth": screen.pixelDepth
		};

		//first push data when document is ready, saves all the PHP server details
		//returns prism_visit_id that can be used later
		$.post('/plugins/prism/trac', load_data, function(output) {
			prism_visit_id = output.prism_visit_id;
		}, 'json');

		//capture any click and associated href + text
		$(document).on('click', 'a, button', function() {
			click_data={'action': 'click', 'prism_visit_id': prism_visit_id, 'href':$(this).attr('href'), 'text':$(this).text()};
			$.post('/plugins/prism/trac', click_data, function(output) {}, 'json');
		});

		//update time every 10 seconds (not ideal, but good for now)
		setInterval(function() {
			time_spent = TimeMe.getTimeOnCurrentPageInSeconds();
			time_data={'unload': 1, 'prism_visit_id': prism_visit_id, 'time_spent': time_spent};
			$.post('/plugins/prism/trac', time_data, function(output) {}, 'json');
		}, 10000);

	});
	
});