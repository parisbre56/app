(function(window,$){

	function sleep(ms) {
		var start = new Date().getTime(), expire = start + ms;
		while (new Date().getTime() < expire) { }
	}

	sleep(100);

})(window,jQuery);
