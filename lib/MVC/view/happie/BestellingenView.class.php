<?php

require_once 'MVC/model/entity/happie/HappieGang.enum.php';

/**
 * BestellingenView.class.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Tonen van alle bestellingen om te beheren.
 * 
 */
class HappieBestellingView extends JsonResponse {

	public function __construct(HappieBestelling $bestelling) {
		parent::__construct($bestelling);
	}

	public function view() {
		$this->model->menu_item = $this->model->getItem()->naam;
		parent::view();
	}

}

class HappieBestellingenView extends DataTable {

	public function __construct() {
		parent::__construct(HappieBestellingenModel::orm, get_class($this), 'Overzicht actueel', 'tafel');
		$this->dataSource = happieUrl . '/overzicht/data';

		$toolbar = new DataTableToolbar();
		$fields[] = $toolbar;
		$this->addFields($fields);

		$knop = new DataTableToolbarKnop('>= 0', null, 'rowcount', 'Count', 'Count selected rows', null);
		$knop->onclick = "alert($('#" . $this->tableId . " tbody tr.selected').length + ' row(s) selected');";
		$toolbar->addKnop($knop);

		$knop = new DataTableToolbarKnop('== 1', null, 'debugprint', 'Print', 'Debugprint row', null);
		$knop->onclick = "console.log($('#" . $this->tableId . " tbody tr.selected'));";
		$toolbar->addKnop($knop);
	}

}

class HappieKeukenView extends DataTable {

	public function __construct() {
		parent::__construct(HappieBestellingenModel::orm, get_class($this), 'Keuken actueel', 'tafel');
		$this->dataSource = happieUrl . '/keuken/data';

		$toolbar = new DataTableToolbar();
		$fields[] = $toolbar;
		$this->addFields($fields);
	}

	protected function getColumnsDef() {
		$def = parent::getColumnsDef();
		unset($def['bestelling_id'], $def['wijzig_historie']);
		return $def;
	}

}

class HappieServeerView extends DataTable {

	public function __construct() {
		parent::__construct(HappieBestellingenModel::orm, get_class($this), 'Actuele bestellingen', 'tafel');
		$this->dataSource = happieUrl . '/serveer/data';

		$toolbar = new DataTableToolbar();
		$fields[] = $toolbar;
		$this->addFields($fields);

		$toolbar->addKnop(new DataTableToolbarKnop('>= 0', happieUrl . '/nieuw', '', 'Nieuw', 'Nieuwe bestelling', '/famfamfam/add.png'));
	}

	protected function getColumnsDef() {
		$def = parent::getColumnsDef();
		unset($def['bestelling_id'], $def['wijzig_historie']);
		return $def;
	}

}

class HappieBarView extends DataTable {

	public function __construct() {
		parent::__construct(HappieBestellingenModel::orm, get_class($this), 'Bar actueel', 'tafel');
		$this->dataSource = happieUrl . '/bar/data';

		$toolbar = new DataTableToolbar();
		$fields[] = $toolbar;
		$this->addFields($fields);

		$toolbar->addKnop(new DataTableToolbarKnop('>= 0', happieUrl . '/nieuw', '', 'Nieuw', 'Nieuwe bestelling', '/famfamfam/add.png'));
	}

	protected function getColumnsDef() {
		$def = parent::getColumnsDef();
		unset($def['bestelling_id'], $def['wijzig_historie']);
		return $def;
	}

}

class HappieBestellingWijzigenForm extends Formulier {

	public function __construct(Bestelling $bestelling) {
		parent::__construct($bestelling, get_class($this), happieUrl . '/wijzigen/' . $bestelling->bestelling_id, 'Bestelling wijzigen');
		$this->generateFields();
	}

}

class HappieBestelForm extends TabsForm {

	public function __construct() {
		parent::__construct(null, get_class($this), happieUrl . '/nieuw', 'Nieuwe bestelling');

		// tafel invoer
		$fields[] = new SelectField('tafel', null, 'Tafel', range(1, 100));
		$this->addFields($fields);

		$groepen = HappieMenukaartItemsModel::instance()->getMenukaart();

		// maak invoerveld voor elk item
		foreach ($groepen as $groep) {

			// groepeer items
			$fields = array();
			$fields[] = new Subkopje($groep->titel);

			foreach ($groep->getItems() as $item) {

				// preload bestelling aantal
				if (isset($bestellingen[$item->item_id])) {
					$aantal = $bestellingen[$item->item_id]->aantal;
					$allergie = $bestellingen[$item->item_id]->klant_allergie;
				} else {
					$aantal = 0;
					$allergie = '';
				}

				$fields[] = new IntField('item' . $item->item_id, $aantal, $item->naam, 0, $item->aantal_beschikbaar);
				$fields[] = new HtmlComment(<<<HTML
<div onclick="$(this).slideUp();$('#expand_{$item->item_id}').slideDown();">beschrijving & klant allergie</div>
<div id="expand_{$item->item_id}" class="hidden">
HTML
				);
				$fields[] = new TextField('allergie' . $item->item_id, $allergie, 'Allergie');
				$fields[] = new HtmlComment('</div>');
			}

			// voeg groep toe aan tab en maak tab voor elke gang
			$this->addFields($fields, $groep->gang);
		}

		$fields = array();
		$fields[] = new FormDefaultKnoppen(happieUrl . '/bestel/nieuw');
		$this->addFields($fields);
	}

	/**
	 * Groepeer de waarden per item.
	 */
	public function getValues() {
		$tafel = (int) $this->findByName('tafel')->getValue();
		$values = array();
		foreach ($this->getFields() as $field) {
			// aantal veld
			if ($field instanceof IntField) {
				$item_id = (int) substr($field->getName(), 4);
				$values[$item_id]['aantal'] = $field->getValue();
				$values[$item_id]['tafel'] = $tafel;
			}
			// allergie veld
			elseif ($field instanceof TextField) {
				$item_id = (int) substr($field->getName(), 8);
				$field->empty_null = true;
				$values[$item_id]['klant_allergie'] = $field->getValue();
			}
		}
		return $values;
	}

}
