{**
 * templates/frontend/components/authors.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Display authors of book or chapter
 *
 * @uses $monograph Monograph The monograph to be displayed
 * @uses $authors Array List of authors associated with this monograph or chapter
 * @uses $editors Array List of editors for this monograph if this is an edited
 *       volume. Otherwise empty.
 * @uses $isChapterRequest bool Is true, if a chapter landing page is requested and not a monograph landing page
 *}

{**
 * Concat authors' name and affiliation 
 * Filters texts using function filterPubPropValue
 * @param array $authors, Required
 * @param array $localeOrder, Required, From $pubLocaleData.localeOrder
 * @param array $identifyAsEditors, Required
 * @param array $filters, Optional, E.g. ['escape']
 * @return array, As varaible $authorsWithAffiliationData
 *}
{function concatAuthorsWithAffiliationData}
	{$authorsWithAffiliationData=[]}
	{foreach from=$localeOrder item=$locale}
		{$nameAffiliations=[]}
		{foreach from=$authors item=$author}
			{* Author's name *}
			{$authorName=$author->getFullName()}
			{filterPubPropValue value=$authorName filters=$filters}
			{$authorName=$filteredPubPropValue}
			{if $identifyAsEditors}
				{capture assign="authorName"}{translate key="submission.editorName" editorName=$authorName}{/capture}
			{/if}
			{capture assign="name"}<span class="label">{$authorName}</span>{/capture}
			{$nameAffiliation=$name}
			{* Author's affiliation *}
			{$authorAffiliation=$author->getData('affiliation')}
			{if isset($authorAffiliation[$locale])}
				{filterPubPropValue value=$authorAffiliation[$locale] filters=$filters}
				{capture assign="affiliation"}<span class="value">{$filteredPubPropValue}</span>{/capture}
				{capture assign="nameAffiliation"}{translate key="submission.authorWithAffiliation" name=$name affiliation=$affiliation}{/capture}
			{/if}
			{$nameAffiliations[]=$nameAffiliation}
		{/foreach}
		{$authorsWithAffiliationData[$locale]=$nameAffiliations}
	{/foreach}
	{assign "authorsWithAffiliationData" value=$authorsWithAffiliationData scope="parent"}
{/function}

<div class="item authors">
	<h2 class="pkp_screen_reader">
		{translate key="submission.authors"}
	</h2>

	{* Only show editors for edited volumes *}
	{if $monograph->getData('workType') == $monograph::WORK_TYPE_EDITED_VOLUME && $editors|@count && !$isChapterRequest}
		{assign var="authors" value=$editors}
		{assign var="identifyAsEditors" value=true}
	{/if}

	{* Show short author lists on multiple lines *}
	{if $authors|@count < 5}
		{foreach from=$authors item=author}
			<div class="sub_item">
				<div class="label name">
					{if $identifyAsEditors}
						{translate key="submission.editorName" editorName=$author->getFullName()|escape}
					{else}
						{$author->getFullName()|escape}
					{/if}
					{* Author switcher *}
					{if isset($pubLocaleData.opts.author)}
						<span aria-label="{translate key="plugins.themes.default.languageSwitcher.ariaDescription.author"}" role="none" aria-orientation="horizontal" aria-expanded="false" data-pkp-switcher="author{$author@index}"></span>
					{/if}
				</div>
				{if $author->getData('affiliation')}
					{* Publication author for json *}
					{wrapPubPropData switcher="author{$author@index}" data=$author->getData('affiliation') localeOrder=$pubLocaleData.localeOrder filters=['escape']}
					{$pubLocaleData["author{$author@index}"]=$wrappedPubPropData}
					<span
						class="affiliation"
						data-pkp-switcher-data="author{$author@index}"
						lang="{$pubLocaleData.langAttrs[$pubLocaleData["author{$author@index}"].defaultLocale]}"
					>
						{$pubLocaleData["author{$author@index}"].data[$pubLocaleData["author{$author@index}"].defaultLocale]}
					</span>
				{/if}
				{if $author->getOrcid()}
					<span class="orcid">
						{if $author->hasVerifiedOrcid()}
							{$orcidIcon}
						{else}
							{$orcidUnauthenticatedIcon}
						{/if}
						<a href="{$author->getOrcid()|escape}" target="_blank">
							{$author->getOrcidDisplayValue()|escape}
						</a>
					</span>
				{/if}
			</div>
		{/foreach}

		{* Show long author lists on one line *}
	{else}
		{* Publication authors for json *}
		{concatAuthorsWithAffiliationData authors=$authors localeOrder=$pubLocaleData.localeOrder identifyAsEditors=$identifyAsEditors filters=['escape']}
		{capture assign="authorSeparator"}{translate key="submission.authorListSeparator"}{/capture}
		{wrapPubPropData switcher="authors" data=$authorsWithAffiliationData localeOrder=$pubLocaleData.localeOrder separator=$authorSeparator}
		{$pubLocaleData["authors"]=$wrappedPubPropData}
		<span data-pkp-switcher-data="authors" lang="{$pubLocaleData.langAttrs[$pubLocaleData["authors"].defaultLocale]}">
			{$pubLocaleData["authors"].data[$pubLocaleData["authors"].defaultLocale]}
		</span>
		{* Authors switcher *}
		{if isset($pubLocaleData.opts.author)}
			<span aria-label="{translate key="plugins.themes.default.languageSwitcher.ariaDescription.author"}" role="none" aria-orientation="horizontal" aria-expanded="false" data-pkp-switcher="authors"></span>
		{/if}
	{/if}
</div>
