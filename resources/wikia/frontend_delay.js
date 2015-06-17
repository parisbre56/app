(function(window,$){

	function sleep(ms) {
		var start = new Date().getTime(), expire = start + ms;
		while (new Date().getTime() < expire) { }
	}

	$(function(){
		sleep(200);
	});

})(window,jQuery);
