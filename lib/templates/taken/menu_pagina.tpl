{*
	menu_pagina.tpl	|	P.W.G. Brussee (brussee@live.nl)
*}
{include file='taken/menu_beheer.tpl'}
<div id="taken-popup-background"{if isset($popup)} style="display: block;"{/if}></div>{if isset($popup)}{$popup->view()}{/if}
<div id="taken-menu">
	<ul class="horizontal">
		{assign var="link" value="/maaltijdenketzer"}
		<li{if $instellingen->get('taken', 'url') === $link} class="active"{/if}>
			<a href="{$link}" title="Maaltijdenketzer">Maaltijdenketzer</a>
		</li>
		{assign var="link" value="/maaltijdenabonnementen"}
		<li{if $instellingen->get('taken', 'url') === $link} class="active"{/if}>
			<a href="{$link}" title="Mijn abonnementen">Mijn abonnementen</a>
		</li>
		{assign var="link" value="/corveerooster"}
		<li{if $instellingen->get('taken', 'url') === $link} class="active"{/if}>
			<a href="{$link}" title="Corveerooster">Corveerooster</a>
		</li>
		{assign var="link" value="/corvee"}
		<li{if $instellingen->get('taken', 'url') === $link} class="active"{/if}>
			<a href="{$link}" title="Mijn corveeoverzicht">Mijn corveeoverzicht</a>
		</li>
		{assign var="link" value="/corveevoorkeuren"}
		<li{if $instellingen->get('taken', 'url') === $link} class="active"{/if}>
			<a href="{$link}" title="Mijn voorkeuren">Mijn voorkeuren</a>
		</li>
	</ul>
</div>
<hr/>
<table style="width: 100%;"><tr id="taken-melding"><td id="taken-melding-veld">{$view->getMelding()}</td></tr></table>
<h1>{$view->getTitel()}</h1>