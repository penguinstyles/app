define('wikia.cmp', [
	'wikia.consentStringLibrary',
	'wikia.cookies',
	'wikia.geo',
	'wikia.instantGlobals',
	'wikia.log',
	'wikia.trackingOptIn',
	'wikia.window'
], function (
	consentStringLibrary,
	cookies,
	geo,
	instantGlobals,
	log,
	trackingOptIn,
	win
) {
	var isModuleEnabled = geo.isProperGeo(instantGlobals.wgEnableCMPCountries),
		logGroup = 'wikia.cmp',
		consentStringCookie = 'consent-string',
		cookieExpireDays = 604800000, // 7 days in milliseconds
		purposesList = [1, 2, 3, 4, 5],
		vendorsList = [
			10, // Index Exchange, Inc.
			11, // Quantcast International Limited
			32, // AppNexus Inc.
			52, // The Rubicon Project, Limited
			69, // OpenX Software Ltd. and its affiliates
			76, // PubMatic, Inc.
		],
		// Downloaded from: https://vendorlist.consensu.org/vendorlist.json
		vendorsListGlobal = {
			"vendorListVersion": 33,
			"lastUpdated": "2018-05-27T16:00:14Z",
			"purposes": [
				{
					"id": 1,
					"name": "Information storage and access",
					"description": "The storage of information, or access to information that is already stored, on your device such as advertising identifiers, device identifiers, cookies, and similar technologies."
				},
				{
					"id": 2,
					"name": "Personalisation",
					"description": "The collection and processing of information about your use of this service to subsequently personalise advertising and/or content for you in other contexts, such as on other websites or apps, over time. Typically, the content of the site or app is used to make inferences about your interests, which inform future selection of advertising and/or content."
				},
				{
					"id": 3,
					"name": "Ad selection, delivery, reporting",
					"description": "The collection of information, and combination with previously collected information, to select and deliver advertisements for you, and to measure the delivery and effectiveness of such advertisements. This includes using previously collected information about your interests to select ads, processing data about what advertisements were shown, how often they were shown, when and where they were shown, and whether you took any action related to the advertisement, including for example clicking an ad or making a purchase. This does not include personalisation, which is the collection and processing of information about your use of this service to subsequently personalise advertising and/or content for you in other contexts, such as websites or apps, over time."
				},
				{
					"id": 4,
					"name": "Content selection, delivery, reporting",
					"description": "The collection of information, and combination with previously collected information, to select and deliver content for you, and to measure the delivery and effectiveness of such content. This includes using previously collected information about your interests to select content, processing data about what content was shown, how often or how long it was shown, when and where it was shown, and whether the you took any action related to the content, including for example clicking on content. This does not include personalisation, which is the collection and processing of information about your use of this service to subsequently personalise content and/or advertising for you in other contexts, such as websites or apps, over time."
				},
				{
					"id": 5,
					"name": "Measurement",
					"description": "The collection of information about your use of the content, and combination with previously collected information, used to measure, understand, and report on your usage of the service. This does not include personalisation, the collection of information about your use of this service to subsequently personalise content and/or advertising for you in other contexts, i.e. on other service, such as websites or apps, over time."
				}
			],
			"features": [
				{
					"id": 1,
					"name": "Matching Data to Offline Sources",
					"description": "Combining data from offline sources that were initially collected in other contexts."
				},
				{
					"id": 2,
					"name": "Linking Devices",
					"description": "Allow processing of a user's data to connect such user across multiple devices."
				},
				{
					"id": 3,
					"name": "Precise Geographic Location Data",
					"description": "Allow processing of a user's precise geographic location data in support of a purpose for which that certain third party has consent."
				}
			],
			"vendors": [
				{
					"id": 10,
					"name": "Index Exchange, Inc. ",
					"policyUrl": "www.indexexchange.com/privacy",
					"purposeIds": [
						1
					],
					"legIntPurposeIds": [],
					"featureIds": [
						2,
						3
					]
				},
				{
					"id": 11,
					"name": "Quantcast International Limited",
					"policyUrl": "https://www.quantcast.com/privacy/",
					"purposeIds": [
					  1
					],
					"legIntPurposeIds": [
					  2,
					  3,
					  4,
					  5
					],
					"featureIds": [
					  1,
					  2
					]
				},
				{
					"id": 32,
					"name": "AppNexus Inc.",
					"policyUrl": "https://www.appnexus.com/en/company/platform-privacy-policy",
					"purposeIds": [
						1
					],
					"legIntPurposeIds": [
						3
					],
					"featureIds": [
						2,
						3
					]
				},
				{
					"id": 52,
					"name": "The Rubicon Project, Limited",
					"policyUrl": "http://rubiconproject.com/rubicon-project-yield-optimization-privacy-policy/",
					"purposeIds": [
						1
					],
					"legIntPurposeIds": [
						2,
						3,
						4,
						5
					],
					"featureIds": [
						3
					]
				},
				{
					"id": 69,
					"name": "OpenX Software Ltd. and its affiliates",
					"policyUrl": "https://www.openx.com/legal/privacy-policy/",
					"purposeIds": [
						1,
						2,
						3
					],
					"legIntPurposeIds": [],
					"featureIds": [
						1,
						2,
						3
					]
				},
				{
					"id": 76,
					"name": "PubMatic, Inc.",
					"policyUrl": "https://pubmatic.com/privacy-policy/",
					"purposeIds": [
						1,
						2
					],
					"legIntPurposeIds": [
						3,
						4,
						5
					],
					"featureIds": []
				}
			]
		};

	function getConsentString(optIn) {
		var cookie = cookies.get(consentStringCookie);

		cookie = cookie ? cookie.split('...') : cookie;

		if (cookie && (cookie[0] === '1') === optIn && cookie[1] !== undefined) {
			log('Serving consent string from cookie', log.levels.debug, logGroup);

			return cookie[1];
		}

		var consentString,
			consentData = new consentStringLibrary.ConsentString();

		consentData.setGlobalVendorList(vendorsListGlobal);
		consentData.setPurposesAllowed(optIn ? purposesList : []);
		consentData.setVendorsAllowed(optIn ? vendorsList : []);

		consentString = consentData.getConsentString();

		cookies.set(consentStringCookie, (optIn ? '1...' : '0...') + consentString, {
			path: '/',
			domain: window.wgCookieDomain || '.wikia.com',
			expires: cookieExpireDays
		});

		log('Saving consent string to cookie', log.levels.debug, logGroup);

		return consentString;
	}

	function getGdprApplies() {
		return trackingOptIn.geoRequiresTrackingConsent();
	}

	function init(optIn) {
		log('Initializing module', log.levels.debug, logGroup);

		win.__cmp = function __cmp(command, parameter, callback) {
			var iabConsentData = getConsentString(optIn),
				gdprApplies = getGdprApplies(),
				success,
				ret;

			switch (true) {
				case (command === 'getConsentData'):
					ret = {
						consentData: iabConsentData,
						gdprApplies: gdprApplies
					};
					success = true;
					break;
				case (command === 'getVendorConsents'):
					ret = {
						metadata: iabConsentData,
						gdprApplies: gdprApplies
					};
					success = true;
					break;
				default:
					log('Unknown command ' + command, log.levels.debug, logGroup);
					ret = {};
					success = false;
			}

			log(
				[
					'__cmp call',
					'command: ' + command,
					'parameter: ' + parameter,
					'return object: ' + JSON.stringify(ret),
					'success: ' + success
				],
				log.levels.debug,
				logGroup
			);

			callback(ret, success);
		};
	}

	if (isModuleEnabled) {
		win.__cmp = function __cmp(command, version, callback) {
			log(['__cmp call', 'CMP module is not initialized'], log.levels.debug, logGroup);
			callback({}, false);
		};
		win.addEventListener('message', function (event) {
			try {
				var call = event.data.__cmpCall;

				if (call) {
					win.__cmp(call.command, call.parameter, function(retValue, success) {
						var returnMsg = {
							__cmpReturn: {
								returnValue: retValue, success: success, callId: call.callId
							}
						};
						event.source.postMessage(returnMsg, '*');
					});
				}
			} catch (e) {} // do nothing
		});
		trackingOptIn.pushToUserConsentQueue(init);
	}

	return {
		getConsentString: getConsentString,
		getGdprApplies: getGdprApplies
	};
});
