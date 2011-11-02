<?php

class ContributeMenuModule extends Module {

	var $dropdownItems = array();
	var $content_actions;

	public function executeIndex() {
		// add "edit this page" item
		if (isset($this->content_actions['edit'])) {
			$this->dropdownItems['edit'] = array(
				'text' => wfMsg('oasis-navigation-v2-edit-page'),
				'href' => $this->content_actions['edit']['href']
			);
		}

		// menu items linking to special pages
		$specialPagesLinks = array(
			'Upload' => 'oasis-navigation-v2-add-photo',
			'CreatePage' => 'oasis-navigation-v2-create-page',
			'WikiActivity' => 'oasis-button-wiki-activity',
		);

		foreach ($specialPagesLinks as $specialPageName => $linkMessage) {
			$specialPageTitle = SpecialPage::getTitleFor( $specialPageName );
			if (!$specialPageTitle instanceof Title) {
				continue;
			}

			$this->dropdownItems[strtolower($specialPageName)] = array(
				'text' => wfMsg($linkMessage),
				'href' =>  $specialPageTitle->getLocalURL(),
			);
		}

		// show menu edit links
		$wgUser = F::app()->wg->User;

		if($wgUser->isAllowed('editinterface')) {
			$this->dropdownItems['wikinavedit'] = array(
				'text' => wfMsg('oasis-navigation-v2-edit-this-menu'),
				'href' => Title::newFromText(WikiNavigationModule::WIKI_LOCAL_MESSAGE, NS_MEDIAWIKI)->getLocalURL('action=edit'),
			);
		}
	}
}