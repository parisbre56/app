require([
	'wikia.window', 'jquery'
], function(win, $) {
	'use strict';

	var delayHoverParams = {
			onActivate: show,
			onDeactivate: hide,
			activateOnClick: false
		},
		isTouchScreen = win.Wikia.isTouchScreen(),
		$parent = $('.rwa-item');


	/**
	 * @desc shows dropdown
	 * @param {Event=} event
	 */
	function show(event) {
		$('.article-navigation > ul > li.active').removeClass('active');

		$parent.addClass('active');

		// handle touch interactions
		if (event) {
			event.stopPropagation();
		}

		$('body').one('click', hide);
	}

	/**
	 * @desc hides dropdown
	 */
	function hide() {
		$parent.removeClass('active');
	}

	if (isTouchScreen) {
		$parent.on('click', function(e) {
			e.stopPropagation();

			if($parent.hasClass('active')) {
				hide();
			} else {
				show();
			}
		});
	} else {
		win.delayedHover($parent[0], delayHoverParams);
	}
});
