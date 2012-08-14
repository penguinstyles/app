<?php $app = F::app(); ?>
<div id="mw-content-text" lang="<?= $app->wg->contLang->getCode(); ?>">
	<script>var wgWikiaHubType = '<?= htmlspecialchars($wgWikiaHubType); ?>' || '';</script>

	<div class="WikiaGrid WikiaHubs" id="WikiaHubs">
		<div class="grid-3 alpha">
			<section class="grid-3 alpha wikiahubs-slider wikiahubs-module">
				<?= $app->renderView('SpecialWikiaHubsV2', 'slider', array()); ?>
			</section>
			<section class="grid-3 alpha wikiahubs-newstabs wikiahubs-module">
				<?= $app->renderView('SpecialWikiaHubsV2', 'tabber', array()); ?>
			</section>
		</div>
		<section class="grid-3 wikiahubs-rail wikiahubs-pulse wikiahubs-module" >
			<?= $app->renderView('SpecialWikiaHubsV2', 'pulse', array()); ?>
		</section>
		<section class="grid-1 plainlinks wikiahubs-explore wikiahubs-module">
			<?= $app->renderView('SpecialWikiaHubsV2', 'explore', array()); ?>
		</section>
		<div class="grid-2 alpha" style="float:right">
			<section class="grid-2 alpha wikiahubs-featured-video wikiahubs-module">
				<?= $app->renderView('SpecialWikiaHubsV2', 'featuredvideo', array()); ?>
			</section>

			<section class="grid-2 alpha wikiahubs-wikitext-module wikiahubs-module">
				<?= $app->renderView('SpecialWikiaHubsV2', 'wikitextmodule', array()); ?>
			</section">

			<section class="grid-2 alpha wikiahubs-top-wikis wikiahubs-module">
				<?= $app->renderView('SpecialWikiaHubsV2', 'topwikis', array()); ?>
			</section>
		</div>
		<div class="grid-4 alpha wikiahubs-popular-videos wikiahubs-module">
			<?= $app->renderView('SpecialWikiaHubsV2', 'popularvideos', array()); ?>
		</div>
		<div class="grid-4 alpha wikiahubs-from-the-community wikiahubs-module ">
			<?= $app->renderView('SpecialWikiaHubsV2', 'fromthecommunity', array()); ?>
		</div>
	</div>
</div>
