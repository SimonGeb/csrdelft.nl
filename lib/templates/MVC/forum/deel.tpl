{$view->getMelding()}

<form id="forum_zoeken" action="/forum/zoeken" method="post"><fieldset><input type="text" name="zoeken" value="zoeken in forum" onfocus="this.value = '';" /></fieldset></form>

{capture name='navlinks'}
	<div class="forumNavigatie">
		<a href="/forum" class="forumGrootlink">Forum</a>
		{if $categorien}
			&raquo;
			<select name="forum_id" style="padding: 0px;" onchange="document.location.href = '/forum/' + this.value;">
				<option value="recent"{if 0 === $deel->forum_id} selected="selected"{/if}>Recent gewijzigd</option>
				{foreach from=$categorien item=cat}
					<optgroup label="{$cat->titel}">
						{foreach from=$cat->getForumDelen() item=newDeel}
							<option value="deel/{$newDeel->forum_id}"{if $newDeel->forum_id === $deel->forum_id} selected="selected"{/if}>{$newDeel->titel}</option>
						{/foreach}
					</optgroup>
				{/foreach}
			</select>
		{/if}
	</div>
{/capture}

{$smarty.capture.navlinks}
<h1>{$deel->titel}</h1>

<table id="forumtabel">
	<thead>
		<tr>
			<th>Titel</th>
			<th>Reacties</th>
			<th>Auteur</th>
			<th>Recente wijziging</th>
		</tr>
	</thead>
	<tbody>
		{if !$deel->hasForumDraden()}
			<tr>
				<td colspan="4">Dit forum is nog leeg.</td>
			</tr>
		{/if}
		{foreach from=$deel->getForumDraden() item=draad}
			{include file='MVC/forum/draad_lijst.tpl'}
		{/foreach}
	</tbody>
	<thead>
		<tr>
			<th colspan="4">
				{if $deel->forum_id === 0}
					{sliding_pager baseurl="/forum/recent/"
					pagecount=ForumDradenModel::instance()->getHuidigePagina() curpage=ForumDradenModel::instance()->getHuidigePagina()
					separator=" &nbsp;"}
					&nbsp;<a href="/forum/recent/{ForumDradenModel::instance()->getAantalPaginas($deel->forum_id)}">verder terug</a>
				{else}
					{sliding_pager baseurl="/forum/deel/"|cat:$deel->forum_id|cat:"/"
					pagecount=ForumDradenModel::instance()->getAantalPaginas($deel->forum_id) curpage=ForumDradenModel::instance()->getHuidigePagina()
					separator=" &nbsp;" show_prev_next=true}
				{/if}
			</th>
		</tr>
	</thead>
	<tbody>
		{if $deel->magPosten()}
			{include file='MVC/forum/draad_form.tpl'}
		{/if}
	</tbody>
</table>

<h1>{$deel->titel}</h1>
{$smarty.capture.navlinks}