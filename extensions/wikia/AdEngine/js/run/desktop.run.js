/*global require*/
/*jshint camelcase:false*/
require([
	'ext.wikia.adEngine.adEngine',
	'ext.wikia.adEngine.adLogicHighValueCountry',
	'ext.wikia.adEngine.adTracker',
	'ext.wikia.adEngine.config.desktop',
	'ext.wikia.adEngine.customAdsLoader',
	'ext.wikia.adEngine.dartHelper',
	'ext.wikia.adEngine.messageListener',
	'ext.wikia.adEngine.provider.evolve',
	'ext.wikia.adEngine.slotTracker',
	'ext.wikia.adEngine.slotTweaker',
	'wikia.krux',
	'wikia.window'
], function (
	adEngine,
	adLogicHighValueCountry,
	adTracker,
	adConfigDesktop,
	customAdsLoader,
	dartHelper,
	messageListener,
	providerEvolve,
	slotTracker,
	slotTweaker,
	krux,
	win
) {
	'use strict';

	var kruxSiteId = 'JU3_GW1b';

	win.AdEngine_getTrackerStats = slotTracker.getStats;

	// DART API for Liftium
	win.LiftiumDART = {
		getUrl: function (slotname, slotsize) {
			if (slotsize) {
				slotsize += ',1x1';
			}
			return dartHelper.getUrl({
				slotname: slotname,
				slotsize: slotsize,
				adType: 'adi',
				src: 'liftium'
			});
		}
	};

	messageListener.init();

	// Register Evolve hop
	win.evolve_hop = providerEvolve.hop;

	// Register window.wikiaDartHelper so jwplayer can use it
	win.wikiaDartHelper = dartHelper;

	// Register adLogicHighValueCountry as so Liftium can use it
	win.adLogicHighValueCountry = adLogicHighValueCountry;

	// Register adSlotTweaker so DART creatives can use it
	// https://www.google.com/dfp/5441#delivery/CreateCreativeTemplate/creativeTemplateId=10017012
	win.adSlotTweaker = slotTweaker;

	// Custom ads (skins, footer, etc)
	win.loadCustomAd = customAdsLoader.loadCustomAd;

	// Everything starts after content and JS
	win.wgAfterContentAndJS.push(function () {
		// Ads
		adTracker.measureTime('adengine.init', 'queue.desktop').track();
		win.adslots2 = win.adslots2 || [];
		adEngine.run(adConfigDesktop, win.adslots2, 'queue.desktop');

		// Krux
		krux.load(kruxSiteId);
	});
});

// Inject extra slots
require([
	'ext.wikia.adEngine.slot.inContentPlayer',
	'ext.wikia.adEngine.slot.skyScraper3',
	'wikia.document',
	'wikia.window',
	require.optional('ext.wikia.adEngine.slot.exitstitial'),
	require.optional('ext.wikia.adEngine.slot.inContentDesktop')
], function (inContentPlayer, skyScraper3, doc, win, exitstitial, inContentDesktop) {
	'use strict';

	function initDesktopSlots() {
		inContentPlayer.init();
		skyScraper3.init();

		if (inContentDesktop) {
			inContentDesktop.init();
		}

		if (exitstitial) {
			exitstitial.init();
		}
	}

	if (doc.readyState === 'complete') {
		initDesktopSlots();
	} else {
		win.addEventListener('load', initDesktopSlots);
	}
});

// FPS meter
require(['wikia.querystring', 'wikia.document'], function (qs, doc) {
	'use strict';
	if (qs().getVal('fps')) {
		var s = doc.createElement('script');
		s.src = 'https://raw.githubusercontent.com/Wikia/fps-meter/master/fps-meter.js';
		doc.body.appendChild(s);
	}
});
