/*global describe, it, expect, modules, spyOn, beforeEach*/
describe('ext.wikia.adEngine.provider.gpt.helper', function () {
	'use strict';

	function noop() {
	}

	var AdElement,
		callbacks = {},
		mocks = {
			context: {
				opts: {
					premiumOnly: false
				},
				targeting: {
					hasFeaturedVideo: false,
					skin: 'oasis'
				}
			},
			adContext: {
				addCallback: noop,
				get: noop,
				getContext: function () {
					return mocks.context;
				}
			},
			adDetect: {},
			adLogicPageParams: {
				getPageLevelParams: function () {
					return [];
				}
			},
			slotTweaker: {
				show: noop,
				hide: noop,
				removeDefaultHeight: noop
			},
			slotElement: {
				appendChild: noop
			},
			slotTargetingData: {},
			sraHelper: {
				shouldFlush: function () {
					return true;
				}
			},
			uapContext: {
				getUapId: noop
			},
			passbackHandler: {
				get: function () {
					return 'unknown';
				}
			},
			srcProvider: {
				get: function () {
					return 'gpt';
				}
			},
			slotTargetingHelper: {
				getAbTestId: noop,
				getOutstreamData: noop,
				getWikiaSlotId: noop
			},
			googleTag: {
				isInitialized: function () {
					return true;
				},
				init: noop,
				registerCallback: function (id, callback) {
					callbacks.push(callback);
				},
				push: function (callback) {
					callback();
				},
				addSlot: noop,
				flush: noop,
				setPageLevelParams: noop
			},
			googleSlots: {
				getSlotByName: function () {
					return {};
				}
			},
			gptTargeting: {
				getSlotLevelTargeting: function () {
					return {};
				}
			},
			doc: {
				getElementById: function () {
					return {
						getAttribute: function () {
							return '{uap: "none"}';
						}
					};
				}
			},
			log: noop
		};

	mocks.log.levels = {};

	function getModule() {
		return modules['ext.wikia.adEngine.provider.gpt.helper'](
			mocks.adContext,
			mocks.adLogicPageParams,
			mocks.uapContext,
			mocks.adDetect,
			AdElement,
			mocks.googleTag,
			mocks.googleSlots,
			mocks.gptTargeting,
			mocks.passbackHandler,
			mocks.srcProvider,
			mocks.slotTargetingHelper,
			mocks.slotTweaker,
			mocks.doc,
			mocks.log,
			mocks.sraHelper
		);
	}

	function createSlot(slotName, disabled) {
		return {
			name: slotName,
			container: mocks.slotElement,
			success: noop,
			collapse: noop,
			pre: function (methodName, callback) {
				callbacks[methodName] = [];
				callbacks[methodName].push(callback);
			},
			isEnabled: function () {
				return !disabled;
			}
		};
	}

	beforeEach(function () {
		AdElement = function (slotName, slotPath, slotTargetingData) {
			mocks.slotTargetingData = slotTargetingData;
		};

		AdElement.prototype.getId = function () {
			return 'TOP_LEADERBOARD';
		};

		AdElement.prototype.getNode = function () {
			return {};
		};

		AdElement.prototype.updateDataParams = noop;

		callbacks = {};

		mocks.context = {
			opts: {},
			targeting: {
				skin: 'oasis'
			}
		};
	});

	it('Initialize googletag when module is not initialized yet', function () {
		spyOn(mocks.googleTag, 'isInitialized').and.returnValue(false);
		spyOn(mocks.googleTag, 'init');

		getModule().pushAd(createSlot('TOP_LEADERBOARD'), '/foo/slot/path', {}, {});

		expect(mocks.googleTag.init).toHaveBeenCalled();
	});

	it('Prevent initializing googletag if module is already initialized', function () {
		spyOn(mocks.googleTag, 'init');

		getModule().pushAd(createSlot('TOP_LEADERBOARD'), '/foo/slot/path', {}, {});

		expect(mocks.googleTag.init).not.toHaveBeenCalled();
	});

	it('Push and flush ATF slot when SRA is not enabled', function () {
		spyOn(mocks.googleTag, 'push');
		spyOn(mocks.googleTag, 'flush');

		getModule().pushAd(createSlot('TOP_LEADERBOARD'), '/foo/slot/path', {}, {});

		expect(mocks.googleTag.push).toHaveBeenCalled();
		expect(mocks.googleTag.flush).toHaveBeenCalled();
	});

	it('Only push ATF slot when SRA is enabled', function () {
		spyOn(mocks.googleTag, 'push');
		spyOn(mocks.googleTag, 'flush');
		spyOn(mocks.sraHelper, 'shouldFlush').and.returnValue(false);

		getModule().pushAd(createSlot('TOP_LEADERBOARD'), '/foo/slot/path', {}, {sraEnabled: true});

		expect(mocks.googleTag.push).toHaveBeenCalled();
		expect(mocks.googleTag.flush).not.toHaveBeenCalled();
	});

	it('Always push and flush BTF slot even if SRA is enabled', function () {
		spyOn(mocks.googleTag, 'push');
		spyOn(mocks.googleTag, 'flush');

		getModule().pushAd(createSlot('TOP_RIGHT_BOXAD'), '/foo/slot/path', {}, {sraEnabled: true});

		expect(mocks.googleTag.push).toHaveBeenCalled();
		expect(mocks.googleTag.flush).toHaveBeenCalled();
	});

	it('Prevent push when given slot is flushOnly', function () {
		spyOn(mocks.googleTag, 'push');
		spyOn(mocks.googleTag, 'flush');

		getModule().pushAd(createSlot('GPT_FLUSH'), '/foo/slot/path', {flushOnly: true}, {});

		expect(mocks.googleTag.push).not.toHaveBeenCalled();
		expect(mocks.googleTag.flush).toHaveBeenCalled();
	});

	it('Prevent push when given slot is disabled', function () {
		spyOn(mocks.googleTag, 'push');
		spyOn(mocks.googleTag, 'flush');

		getModule().pushAd(createSlot('TOP_LEADERBOARD', true), '/foo/slot/path', {}, {});

		expect(mocks.googleTag.push).not.toHaveBeenCalled();
	});

	it('Register slot callback on push', function () {
		getModule().pushAd(createSlot('TOP_RIGHT_BOXAD'), '/foo/slot/path', {}, {});

		expect(callbacks.renderEnded.length).toEqual(1);
	});
});
