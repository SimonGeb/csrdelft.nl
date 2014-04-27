<tr>
	<td class="auteur">
		<a class="forumpostlink" id="nieuwonderwerp">Nieuw onderwerp</a><br />
		{if !LoginLid::mag('P_LOGGED_IN')}
			<label for="email" class="externeemail">Email-adres</label>
		{/if}
		<label for="titel">Titel</label>
		<label for="forumBericht">Bericht</label>
	</td>
	<td colspan="4" class="forumtekst">
		<form id="forumForm" action="/forum/posten/{$deel->forum_id}" method="post">
			<fieldset>
				{if LoginLid::mag('P_LOGGED_IN')}
					Hier kunt u een onderwerp toevoegen in deze categorie van het forum. Kijkt u vooraf goed of het
					onderwerp waarover u post hier wel thuishoort.<br /><br />
				{else}
					{*	melding voor niet ingelogde gebruikers die toch willen posten. Ze worden 'gemodereerd', dat
					wil zeggen, de topics zijn nog niet direct zichtbaar. *}
					Hier kunt u een bericht toevoegen aan het forum. Het zal echter niet direct zichtbaar worden, maar
					&eacute;&eacute;rst door de PubCie worden goedgekeurd. Zoekmachines nemen berichten van dit openbare
					forumdeel op in hun zoekresultaten.<br />
					<span style="text-decoration: underline;">Het is hierbij verplicht om uw naam in het bericht te plaatsen.</span><br /><br />
					<input type="text" id="email" name="email" /><br /><br />
					{* spam trap, must be kept empty! *}
					<input type="text" name="firstname" value="" class="verborgen" />
				{/if}
				<input type="text" name="titel" id="titel" value="" class="tekst"/><br /><br />
				<div id="berichtPreviewContainer" class="previewContainer"><div id="berichtPreview" class="preview"></div></div>
				<textarea name="bericht" id="forumBericht" class="forumBericht{if $deel->isOpenbaar()} extern{/if}" rows="12">{$post_form_tekst}</textarea>
				<div class="butn">
					<a style="float: right;" class="knop" onclick="$('#ubbhulpverhaal').toggle();" title="Opmaakhulp weergeven">Opmaak</a>
					<a style="float: right; margin-right: 3px;" class="knop" onclick="vergrootTextarea('forumBericht', 10)" title="Vergroot het invoerveld"><div class="arrows">&uarr;&darr;</div></a>

					<input type="submit" name="submit" value="Opslaan" />
					<input type="button" value="Voorbeeld" id="forumVoorbeeld" onclick="previewPost('forumBericht', 'berichtPreview')"/>
				</div>
			</fieldset>
		</form>
	</td>
</tr>