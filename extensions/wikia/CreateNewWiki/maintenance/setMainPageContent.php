<?php

/**
 * This script updates the main page content adding wiki name and description provided by the founder.
 */

require_once( __DIR__ . '/../../../../maintenance/Maintenance.php' );

class SetMainPageContent extends Maintenance {

	public function execute() {
		global $wgSitename, $wgWikiDescription;

		$this->output( "SetMainPageContent: Started" );
		$this->output( "SetMainPageContent: sitename is " . $wgSitename );
		$this->output( "SetMainPageContent: description is " . $wgWikiDescription );
		if ( empty( $wgWikiDescription ) ) {
			$this->output( "SetMainPageContent: Empty wiki description, skipping" );
			return;
		}
		// set description on main page

		$mainTitle = Title::newFromText( $wgSitename );
		$mainId = $mainTitle->getArticleID();
		$mainArticle = Article::newFromID( $mainId );

		if ( empty( $mainArticle ) ) {
			$this->error( "SetMainPageContent: Cannot find the main article!" );
			return;
		}

		$newMainPageText = $this->getClassicMainPage( $mainArticle, $wgWikiDescription );
		$mainArticle->doEdit( $newMainPageText, '' );

		$this->output( "SetMainPageContent: Done" );
	}

	/**
	 * setup main page article content for classic main page
	 * @param $mainArticle Article
	 * @param $description Description text
	 * @return string - main page article wiki text
	 */
	private function getClassicMainPage( $mainArticle, $description ) {
		global $wgParser, $wgSitename, $wgWikiDescription;

		$mainPageText = $mainArticle->getRawText();
		$matches = [];

		if ( preg_match( '/={2,3}[^=]+={2,3}/', $mainPageText, $matches ) ) {
			$newSectionTitle = str_replace( 'Wiki', $wgSitename, $matches[ 0 ] );
			$description = "{$newSectionTitle}\n{$description}";
		}

		$newMainPageText = $wgParser->replaceSection( $mainPageText, 1, $description );

		return $newMainPageText;
	}
}

$maintClass = "SetMainPageContent";
require_once( RUN_MAINTENANCE_IF_MAIN );
