/*global define, Promise*/
define('ext.wikia.adEngine.video.player.porvata.googleIma', [
	'ext.wikia.adEngine.utils.scriptLoader',
	'ext.wikia.adEngine.video.player.porvata.googleImaPlayerFactory',
	'wikia.log',
	'wikia.window'
], function (scriptLoader, imaPlayerFactory, log, win) {
	'use strict';
	var imaLibraryUrl = '//imasdk.googleapis.com/js/sdkloader/ima3.js',
		logGroup = 'ext.wikia.adEngine.video.player.porvata.googleIma';

	function load(onLoad) {
		if (win.google && win.google.ima) {
			log('Google IMA library already loaded', log.levels.info, logGroup);
			onLoad();
			return;
		}

		return scriptLoader.loadScript(imaLibraryUrl, {onLoad: onLoad});
	}

	function getPlayer(videoSettings) {
		var params = videoSettings.getParams(),
			adDisplayContainer = new win.google.ima.AdDisplayContainer(params.container),
			adsLoader,
			iframe = params.container.querySelector('div > iframe');

		// Reload iframe in order to make IMA work when user is moving back/forward to the page with player
		// https://groups.google.com/forum/#!topic/ima-sdk/Q6Y56CcXkpk
		// https://github.com/googleads/videojs-ima/issues/110
		if (win.performance && win.performance.navigation.type === win.performance.navigation.TYPE_BACK_FORWARD) {
			iframe.contentWindow.location.href = iframe.src;
		}

		adsLoader = new win.google.ima.AdsLoader(adDisplayContainer);
		adsLoader.getSettings().setVpaidMode(videoSettings.getVpaidMode());

		return imaPlayerFactory.create(adDisplayContainer, adsLoader, videoSettings);
	}

	return {
		load: load,
		getPlayer: getPlayer
	};
});
