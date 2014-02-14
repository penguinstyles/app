<?php

class MarketingToolboxV3Model extends AbstractMarketingToolboxModel {

	public function __construct($app = null) {
		parent::__construct($app);
	}

	public function getCalendarData($cityId, $beginTimestamp, $endTimestamp) {
		$sdb = wfGetDB(DB_SLAVE, array(), $this->wg->ExternalSharedDB);
		$conds = array(
			'city_id' => $cityId,
		);

		$conds .= ' AND hub_date >= ' . $sdb->timestamp($beginTimestamp)
			. ' AND hub_date <= ' . $sdb->timestamp($endTimestamp);

		$table = $this->getTablesBySectionId(self::SECTION_HUBS);
		$fields = array('hub_date', 'module_status');

		$options = array(
			'GROUP BY' => array(
				'hub_date',
				'module_status'
			)
		);

		$results = $sdb->select($table, $fields, $conds, __METHOD__, $options);

		$out = array();
		while ($row = $sdb->fetchRow($results)) {
			$out[$row['hub_date']] = $row['module_status'];
		}

		return $out;

	}

	/**
	 * Get list of modules for selected lang/vertical/date
	 * applying translation for module name
	 *
	 * @param string $langCode
	 * @param int $sectionId
	 * @param int $verticalId
	 * @param int $timestamp
	 * @param int $activeModule
	 *
	 * @return array
	 */
	public function getModulesData($cityId, $timestamp, $activeModule = MarketingToolboxModuleSliderService::MODULE_ID) {
		$moduleList = $this->getModuleList($cityId, $timestamp);

		$modulesData = array(
			'lastEditor' => null,
			'moduleList' => array()
		);

		$userClass = $this->getUserClass();
		foreach ($moduleList as $moduleId => &$module) {
			if ($moduleId == $activeModule) {
				$userName = null;
				if ($module['lastEditorId']) {
					$user = $userClass::newFromId($module['lastEditorId']);
					if ($user instanceof User) {
						$userName = $user->getName();
					}
				}
				$modulesData['lastEditor'] = $userName;
				$modulesData['lastEditTime'] = $module['lastEditTime'];
				$modulesData['activeModuleName'] = $this->getModuleName($moduleId);
			}
			$module['name'] = $this->getModuleName($moduleId);
			$module['href'] = $this->getModuleUrl($cityId, $timestamp, $moduleId);
		}
		$modulesData['moduleList'] = $moduleList;

		return $modulesData;
	}

	/**
	 * Get modules data for last published hub before selected timestamp
	 *
	 * @param string $langCode
	 * @param int    $sectionId
	 * @param int    $verticalId
	 * @param int    $timestamp
	 * @param int    $moduleId
	 *
	 * @return array
	 */
	public function getPublishedData($cityId, $timestamp = null, $moduleId = null) {
		$lastPublishTimestamp = $this->getLastPublishedTimestamp($cityId, $timestamp);
		return $this->getModulesDataFromDb($cityId, $lastPublishTimestamp, $moduleId);
	}

	// TODO: check is it a propoer way to do this
	public function getModuleUrl($cityId, $timestamp, $moduleId) {
		$specialPage = $this->getSpecialPageClass();
		return $specialPage::getTitleFor('MarketingToolbox', 'editHub')->getLocalURL(
			array(
				'moduleId' => $moduleId,
				'date' => $timestamp,
				'cityId' => $cityId,
			)
		);
	}

	/**
	 * Get list of modules for selected lang/vertical/timestamp
	 *
	 * @param string $langCode
	 * @param int $sectionId
	 * @param int $verticalId
	 * @param int $timestamp
	 *
	 * @return array
	 */
	protected function getModuleList($cityId, $timestamp) {
		$lastPublishTimestamp = $this->getLastPublishedTimestamp($cityId, $timestamp);

		if ($lastPublishTimestamp) {
			$out = $this->getModulesDataFromDb($cityId, $lastPublishTimestamp);
		} else {
			$out = $this->getDefaultModuleList();
		}

		$actualData = $this->getModulesDataFromDb($cityId, $timestamp);
		$out = $actualData + $out;
		ksort($out);

		return $out;
	}

	/**
	 * TODO: confirm this is UNUSED
	 *
	 * @param $moduleId
	 * @param $langCode
	 * @param $sectionId
	 * @param $verticalId
	 * @param $timestamp
	 * 
	 * @return array
	 */
	public function getModuleDataFromDb($cityId, $timestamp, $moduleId) {
		$data = $this->getModulesDataFromDb($cityId, $timestamp, $moduleId);
		return isset($data[$moduleId]) ? $data[$moduleId] : array();
	}

	/**
	 * Check if all modules in current hub (lang, vertical and date) are filled and saved
	 *
	 * @param string $langCode
	 * @param int $verticalId
	 * @param int $timestamp
	 */
	public function checkModulesSaved($cityId, $timestamp) {
		$sdb = wfGetDB(DB_SLAVE, array(), $this->wg->ExternalSharedDB);

		$hubDate = date('Y-m-d', $timestamp);

		$fields = array('count(module_id)');
		$conds = array(
			'city_id' => $cityId,
			'hub_date' => $hubDate
		);

		$result = $sdb->select(self::HUBS_TABLE_NAME, $fields, $conds);

		$row = $sdb->fetchRow($result);

		return ($row[0] == $this->modulesCount) ? true : false;
	}

	/**
	 * @desc Main method to publish hub page of specific vertical in specific language and on specific day
	 * 
	 * @param $langCode
	 * @param $sectionId
	 * @param $verticalId
	 * @param $timestamp
	 * 
	 * @return stdClass (properties: boolean $success, string $errorMsg)
	 */
	public function publish($cityId, $timestamp) {
		wfProfileIn(__METHOD__);
		
		$results = new stdClass();
		$results->success = null;
		$results->errorMsg = null;
		
		if( wfReadOnly() ) {
			$results->success = false;
			$results->errorMsg = wfMsg('marketing-toolbox-module-publish-error-read-only');

			wfProfileOut(__METHOD__);
			return $results;
		}
		

		$this->publishHub($cityId, $timestamp, $results);

		wfProfileOut(__METHOD__);
		return $results;
	}

	/**
	 * @param $langCode
	 * @param $verticalId
	 * @param $timestamp
	 * @param stdClass $results
	 * 
	 * @return stdClass (properties: boolean $success, string $errorMsg)
	 */
	protected function publishHub($cityId, $timestamp, &$results) {
		wfProfileIn(__METHOD__);
		if( !$this->checkModulesSaved($cityId, $timestamp) ) {
			$results->success = false;
			$results->errorMsg = wfMsg('marketing-toolbox-module-publish-error-modules-not-saved');

			wfProfileOut(__METHOD__);
			return;
		}

		$mdb = wfGetDB(DB_MASTER, array(), $this->wg->ExternalSharedDB);
		$hubDate = date('Y-m-d', $timestamp);

		$changes = array(
			'module_status' => $this->statuses['PUBLISHED']
		);

		$conditions = array(
			'city_id' => $cityId,
			'hub_date' => $hubDate
		);

		$dbSuccess = $mdb->update(self::HUBS_TABLE_NAME, $changes, $conditions, __METHOD__);

		if( $dbSuccess ) {
			$mdb->commit(__METHOD__);
			$results->success = true;
		} else {
			$results->success = false;
			$results->errorMsg = wfMsg('marketing-toolbox-module-publish-error-db-error');
		}

		$actualPublishedTimestamp = $this->getLastPublishedTimestamp($cityId);
		if ($actualPublishedTimestamp < $timestamp && $timestamp < time()) {
			$this->purgeLastPublishedTimestampCache($cityId);
		}

		wfProfileOut(__METHOD__);
	}

	/**
	 * Get data for module list from DB
	 *
	 * @param string $langCode
	 * @param int $sectionId
	 * @param int $verticalId
	 * @param int $timestamp
	 * @param int $moduleId (optional) returns data only for specified module
	 *
	 * @return array
	 */
	protected function getModulesDataFromDb($cityId, $timestamp, $moduleId = null) {
		$sdb = wfGetDB(DB_SLAVE, array(), $this->wg->ExternalSharedDB);
		$conds = array(
			'city_id' => $cityId,
			'hub_date' => $sdb->timestamp($timestamp),
		);
		
		if( is_int($moduleId) ) {
			$conds['module_id'] = $moduleId; 
		}
		
		$fields = array('module_id', 'module_status', 'module_data', 'last_edit_timestamp', 'last_editor_id');

		$results = $sdb->select(self::HUBS_TABLE_NAME, $fields, $conds, __METHOD__);

		$out = array();
		while ($row = $sdb->fetchRow($results)) {
			$out[$row['module_id']] = array(
				'status' => $row['module_status'],
				'lastEditTime' => $row['last_edit_timestamp'],
				'lastEditorId' => $row['last_editor_id'],
				'data' => json_decode($row['module_data'], true)
			);
		}
		
		return $out;
	}

	/**
	 * Save module
	 *
	 * @param string $langCode
	 * @param int $sectionId
	 * @param int $verticalId
	 * @param int $timestamp
	 * @param int $moduleId
	 * @param array $data
	 * @param int $editorId
	 *
	 */
	public function saveModule($langCode, $sectionId, $verticalId, $timestamp, $moduleId, $data, $editorId) {
		$mdb = wfGetDB(DB_MASTER, array(), $this->wg->ExternalSharedDB);
		$sdb = wfGetDB(DB_SLAVE, array(), $this->wg->ExternalSharedDB);

		$updateData = array(
			'module_data' => json_encode($data),
			'last_editor_id' => $editorId,
		);

		$table = $this->getTablesBySectionId($sectionId);

		$conds = array(
			'lang_code' => $langCode,
			'vertical_id' => $verticalId,
			'module_id' => $moduleId,
			'hub_date' => $mdb->timestamp($timestamp)
		);

		$result = $sdb->selectField($table, 'count(1)', $conds, __METHOD__);

		if ($result) {
			$mdb->update($table, $updateData, $conds, __METHOD__);
		} else {
			$insertData = array_merge($updateData, $conds);

			$mdb->insert($table, $insertData, __METHOD__);
		}

		$mdb->commit();
	}

	/**
	 * Get last timestamp when vertical was published (before selected timestamp)
	 *
	 * @param string $langCode
	 * @param int $sectionId
	 * @param int $verticalId
	 * @param int $timestamp - max timestamp that we should search for published hub
	 *
	 * @return int timestamp
	 */
	public function getLastPublishedTimestamp($cityId, $timestamp = null, $useMaster = false) {
		if ($timestamp === null) {
			$timestamp = time();
		}
		$timestamp = strtotime(self::STRTOTIME_MIDNIGHT, $timestamp);

		if ($timestamp == strtotime(self::STRTOTIME_MIDNIGHT)) {
			$lastPublishedTimestamp = WikiaDataAccess::cache(
				$this->getMKeyForLastPublishedTimestamp($cityId, $timestamp),
				6 * 60 * 60,
				function () use ($cityId, $timestamp, $useMaster) {
					return $this->getLastPublishedTimestampFromDB($cityId, $timestamp, $useMaster);
				}
			);
		} else {
			$lastPublishedTimestamp = $this->getLastPublishedTimestampFromDB($cityId, $timestamp, $useMaster);
		}

		return $lastPublishedTimestamp;
	}

	public function getLastPublishedTimestampFromDB($cityId, $timestamp, $useMaster = false) {
		$sdb = wfGetDB(
			($useMaster) ? DB_MASTER : DB_SLAVE,
			array(),
			$this->wg->ExternalSharedDB
		);

		$conds = array(
			'city_id' => $cityId,
			'module_status' => $this->statuses['PUBLISHED']
		);

		$conds = $sdb->makeList($conds, LIST_AND);
		$conds .= ' AND hub_date <= ' . $sdb->timestamp($timestamp);

		$result = $sdb->selectField(self::HUBS_TABLE_NAME, 'unix_timestamp(max(hub_date))', $conds, __METHOD__);

		return $result;
	}

	protected function getMKeyForLastPublishedTimestamp($cityId, $timestamp) {
		return wfSharedMemcKey(
			self::CACHE_KEY,
			$cityId,
			$timestamp,
			self::CACHE_KEY_LAST_PUBLISHED_TIMESTAMP
		);
	}
}
