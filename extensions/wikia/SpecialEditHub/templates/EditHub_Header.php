<header class="EditHubHeader">
	<div class="EditHubTitle">
		<h1><a href="<?=$dashboardHref;?>"><?= wfMessage('edit-hub-header-dashboard')->escaped(); ?></a></h1>
		<? if (isset($moduleName)): ?>
			<h2><?=$moduleName;?></h2>
		<? endif ?>

		<? if (isset($date)): ?>
			<p><strong><?=$wg->lang->date($date);?></strong></p>
		<? endif?>
		<? if (isset($regionName) && isset($sectionName) && isset($verticalName)): ?>
			<p class="alternative"><?=$regionName?> / <?=$sectionName ?> / <?=$verticalName?></p>
		<? endif ?>
	</div>

	<aside class="right">
		<? if (isset($lastEditTime)): ?>
		<p><strong><?= wfMessage('edit-hub-header-right-last-saved')->escaped(); ?></strong> <?=$wg->lang->timeanddate($lastEditTime, true);?></p>
		<? endif ?>

		<? if (isset($lastEditor)): ?>
		<p><strong><?= wfMessage('edit-hub-header-right-by')->escaped(); ?></strong> <?=$lastEditor?></p>
		<? endif ?>
	</aside>
</header>
<div class="EditHubHeaderGradient"></div>
