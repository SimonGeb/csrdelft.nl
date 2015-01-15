{getMelding()}
{strip}

	{$zoekform->view()}

	{capture name='kop'}
		<div class="forumheadbtn">
			{if $draad->isVerborgen()}
				<a href="/forum/tonen/{$draad->draad_id}" class="btn round post ReloadPage tonenAan" title="Onderwerp tonen in zijbalk"></a>
			{elseif $draad->magVerbergen()}
				<a href="/forum/verbergen/{$draad->draad_id}" class="btn round post ReloadPage tonenUit" title="Onderwerp verbergen in zijbalk"></a>
			{/if}
			&nbsp;&nbsp;&nbsp;
			{if $draad->isGevolgd()}
				<a href="/forum/volgenuit/{$draad->draad_id}" class="btn round post ReloadPage volgenUit" title="Onderwerp niet meer volgen per email"></a>
			{elseif $draad->magVolgen()}
				<a href="/forum/volgenaan/{$draad->draad_id}" class="btn round post ReloadPage volgenAan" title="Onderwerp volgen per email"></a>
			{/if}
			&nbsp;&nbsp;&nbsp;
			{if $draad->magModereren()}
				{if $draad->gesloten}
					<a href="/forum/wijzigen/{$draad->draad_id}/gesloten" class="btn round post ReloadPage slotjeUit" title="Openen (reactie mogelijk)"></a>
				{else}
					<a href="/forum/wijzigen/{$draad->draad_id}/gesloten" class="btn round post ReloadPage slotjeAan" title="Sluiten (geen reactie mogelijk)"></a>
				{/if}
				&nbsp;&nbsp;&nbsp;
				<a class="btn" title="Moderatie-functies weergeven" onclick="$('#forumtabel a.forummodknop').fadeIn();
						$('#modereren').slideDown();
						$(window).scrollTo('#modereren', 600, {
							easing: 'easeInOutCubic',
							offset: {
								top: -100,
								left: 0
							}
						});
				   ">{icon get="wrench"} Modereren</a>
			{/if}
		</div>

		<h1>
			{$draad->titel}
			{if isset($statistiek)}
				<span class="lichtgrijs small" title="Aantal lezers"> {$draad->getAantalLezers()}</span>
			{/if}
		</h1>
	{/capture}

	{$smarty.capture.kop}

	{if $draad->magModereren()}
		{include file='forum/draad_mod.tpl'}
	{/if}

	{capture name='magreageren'}
		{if $draad->verwijderd}
			<div class="draad-verwijderd">Dit onderwerp is verwijderd.</div>
		{elseif $draad->gesloten}
			<div class="draad-gesloten">
				U kunt hier niet meer reageren omdat dit onderwerp gesloten is.
				{if $draad->getForumDeel()->isOpenbaar() AND strtotime($draad->laatst_gewijzigd) < strtotime(Instellingen::get('forum', 'externen_geentoegang_gesloten'))}
					<div class="dikgedrukt">Dit externe onderwerp is niet meer toegankelijk voor externen en zoekmachines.</div>
				{/if}
			</div>
		{elseif !$draad->magPosten()}
			<div class="draad-readonly">U mag in dit deel van het forum niet reageren.</div>
		{/if}
	{/capture}

{/strip}

<table id="forumtabel">
	<tbody>

		{capture name='paginering'}
			<tr class="tussenschot">
				<td colspan="2"></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<div class="forum-paginering">
						{if $draad->pagina_per_post}
							Bericht:
						{else}
							Pagina:
						{/if}
						{if isset($statistiek)}
							{assign var="append" value="/statistiek"}
						{else}
							{assign var="append" value=""}
						{/if}
						{sliding_pager baseurl="/forum/onderwerp/"|cat:$draad->draad_id|cat:"/" url_append=$append
pagecount=ForumPostsModel::instance()->getAantalPaginas($draad->draad_id) curpage=ForumPostsModel::instance()->getHuidigePagina()}
					</div>
				</td>
			</tr>
		{/capture}

		{* Paginering boven eerste post op de pagina als de eerste post van het draadje niet plakkerig is of dit de eerste pagina is *}
		{if $paging AND (!$draad->eerste_post_plakkerig OR ForumPostsModel::instance()->getHuidigePagina() === 1)}
			{$smarty.capture.paginering}
		{/if}

		{assign var=vanaf value=false}
		{foreach from=$draad->getForumPosts() item=post name=posts}

			{* als posts gewijzigd zijn zonder draad gewijzigd te triggeren voorkomt $draad->onGelezen() dat de gele lijn wordt getoond *}
			{if !$vanaf AND $draad_ongelezen AND (!$gelezen_moment OR strtotime($post->laatst_gewijzigd) > $gelezen_moment)}
				{assign var=vanaf value=true}
				<tr class="tussenschot ongelezenvanaf" title="Ongelezen reacties vanaf hier">
					<td colspan="2">
						<a id="ongelezen">&nbsp;</a>
					</td>
				</tr>
			{else}
				<tr class="tussenschot">
					<td colspan="2"></td>
				</tr>
			{/if}

			{include file='forum/post_lijst.tpl'}

			{* Paginering onder eerste plakkerige post op alle pagina's behalve de eerste *}
			{if $paging AND $draad->eerste_post_plakkerig AND ForumPostsModel::instance()->getHuidigePagina() != 1 AND $smarty.foreach.posts.first}
				{$smarty.capture.paginering}
			{/if}

		{/foreach}

		{* Paginering onderaan pagina *}
		{if $paging}
			{$smarty.capture.paginering}
		{/if}

		{* Geen ongelezen berichten op de laatste pagina betekend in het geheel geen ongelezen berichten *}
		{if !$vanaf AND ForumPostsModel::instance()->getHuidigePagina() === ForumPostsModel::instance()->getAantalPaginas($draad->draad_id)}
			<tr class="tussenschot ongelezenvanaf" title="Geen ongelezen berichten">
				<td colspan="2">
					<a id="ongelezen">&nbsp;</a>
				</td>
			</tr>
		{else}
			<tr class="tussenschot">
				<td colspan="2"></td>
			</tr>
		{/if}

		<tr>
			<td>&nbsp;</td>
			<td class="magreageren">
				{$smarty.capture.magreageren}
			</td>
		</tr>

		<tr>
			<td colspan="2" class="forumfooter">
				<div class="breadcrumbs">{$breadcrumbs}</div>
				{$smarty.capture.kop}
			</td>
		</tr>

		{if $draad->magPosten()}
			{include file='forum/post_form.tpl' deel=$draad->getForumDeel()}
		{/if}

	</tbody>
</table>