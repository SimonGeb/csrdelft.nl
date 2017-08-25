<?php
namespace CsrDelft\view\formulier\invoervelden;
use function CsrDelft\classNameZonderNamespace;
use CsrDelft\Icon;
use function CsrDelft\in_array_i;
use CsrDelft\model\security\LoginModel;
use function CsrDelft\valid_filename;
use CsrDelft\view\formulier\elementen\FormElement;
use CsrDelft\view\formulier\uploadvelden\BestandBehouden;
use CsrDelft\view\Validator;
use Exception;

/**
 * InputField.abstract.php
 *
 * @author Jan Pieter Waagmeester <jieter@jpwaag.com>
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 *
 * De uitbreidingen van InputField:
 *
 *    - TextField                        Simpele input
 *        * DateTimeField                Datum & tijdstip
 *        * RechtenField                Rechten, zie AccessModel
 *        * LandField                    Landen
 *        * StudieField                Opleidingen
 *        * EmailField                Email adressen
 *        * UrlField                    Url's
 *        * TextareaField                Textarea die automagisch uitbreidt bij typen
 *            - BBCodeField        Textarea met bbcode voorbeeld
 *    * NickField                    Nicknames
 *        * LidField                    Leden selecteren
 *    - WachtwoordWijzigenField        Wachtwoorden (oude, nieuwe, nieuwe ter bevestiging)
 *  - EntityField                    PersistentEntity primary key values array
 *
 *
 * Meer uitbreidingen van InputField:
 * @see GetalVelden.class.php
 * @see KeuzeVelden.class.php
 *
 * InputField is de base class van alle FormElements die data leveren,
 * behalve FileField zelf die wel meerdere InputFields bevat.
 */
abstract class InputField implements FormElement, Validator {

	private $id; // unique id
	protected $model; // model voor remote data source en validatie
	protected $name; // naam van het veld in POST
	protected $value; // welke initiele waarde heeft het veld?
	protected $origvalue; // welke originele waarde had het veld?
	protected $empty_null = true; // lege waarden teruggeven als null (SET BEFORE getValue() call in constructor!)
	public $type = 'text'; // input type
	public $title; // omschrijving bij mouseover title
	public $description; // omschrijving in label
	public $hidden = false; // veld onzichtbaar voor gebruiker?
	public $readonly = false; // veld mag niet worden aangepast door client?
	public $required = false; // mag het veld leeg zijn?
	public $enter_submit = false; // bij op enter drukken form submitten
	public $escape_cancel = false; // bij op escape drukken form annuleren
	public $preview = true; // preview tonen? (waar van toepassing)
	public $leden_mod = false; // uitzondering leeg verplicht veld voor LEDEN_MOD
	public $autocomplete = true; // browser laten autoaanvullen?
	public $placeholder = null; // plaats een grijze placeholdertekst in leeg veld
	public $error = ''; // foutmelding van dit veld
	public $onchange = null; // callback on change of value
	public $onchange_submit = false; // bij change of value form submitten
	public $onclick = null; // callback on click
	public $onkeydown = null; // prevent illegal character from being entered
	public $onkeyup = null; // respond to keyboard strokes
	public $typeahead_selected = null; // callback gekozen suggestie
	public $max_len = null; // maximale lengte van de invoer
	public $min_len = null; // minimale lengte van de invoer
	public $rows = 0; // aantal rijen van textarea
	public $css_classes = array('FormElement'); // array met classnames die later in de class-tag komen
	public $suggestions = array(); // lijst van search providers
	public $blacklist = null; // array met niet tegestane waarden
	public $whitelist = null; // array met exclusief toegestane waarden
	public $pattern = null; // html5 input validation pattern

	public function __construct($name, $value, $description, $model = null) {
		$this->id = uniqid('field_');
		$this->model = $model;
		$this->name = $name;
		$this->origvalue = $value;
		if ($this->isPosted()) {
			$this->value = $this->getValue();
		} else {
			$this->value = $value;
		}
		$this->description = $description;
		// add *Field classname to css_classes
		$this->css_classes[] = classNameZonderNamespace(get_class($this));
	}

	public function getType() {
		return $this->type;
	}

	public function getModel() {
		return $this->model;
	}

	public function getBreadcrumbs() {
		return null;
	}

	public function getTitel() {
		return $this->description;
	}

	public function getName() {
		return $this->name;
	}

	public function getId() {
		return $this->id;
	}

	public function isPosted() {
		return isset($_POST[$this->name]);
	}

	public function getOrigValue() {
		return $this->origvalue;
	}

	public function getValue() {
		if ($this->isPosted()) {
			$this->value = filter_input(INPUT_POST, $this->name, FILTER_UNSAFE_RAW);
		}
		return $this->value;
	}

	/**
	 * Is de invoer voor het veld correct?
	 * standaard krijgt deze functie de huidige waarde mee als argument
	 *
	 * Kindertjes van deze classe kunnen deze methode overloaden om specifiekere
	 * testen mogelijk te maken.
	 */
	public function validate() {
		if (!$this->isPosted()) {
			$this->error = 'Veld is niet gepost';
		} elseif ($this->readonly AND $this->value !== $this->origvalue) {
			$this->error = 'Dit veld mag niet worden aangepast';
		} elseif ($this->value == '' AND $this->required) {
			// vallen over lege velden als dat aangezet is voor het veld
			if ($this->leden_mod AND LoginModel::mag('P_LEDEN_MOD')) {
				// tenzij gebruiker P_LEDEN_MOD heeft en deze optie aan staat voor dit veld
			} else {
				$this->error = 'Dit is een verplicht veld';
			}
		}
		// als max_len is gezet dan checken of de lengte er niet boven zit
		if (is_int($this->max_len) AND strlen($this->value) > $this->max_len) {
			$this->error = 'Dit veld mag maximaal ' . $this->max_len . ' tekens lang zijn';
		}
		// als min_len is gezet dan checken of de lengte er niet onder zit
		if (is_int($this->min_len) AND strlen($this->value) < $this->min_len) {
			$this->error = 'Dit veld moet minimaal ' . $this->min_len . ' tekens lang zijn';
		}
		// als blacklist is gezet dan controleren
		if (is_array($this->blacklist) AND in_array_i($this->value, $this->blacklist)) {
			$this->error = 'Deze waarde is niet toegestaan: ' . htmlspecialchars($this->value);
		}
		// als whitelist is gezet dan controleren
		if (is_array($this->whitelist) AND !in_array_i($this->value, $this->whitelist)) {
			$this->error = 'Deze waarde is niet toegestaan: ' . htmlspecialchars($this->value);
		}
		return $this->error === '';
	}

	/**
	 * Bestand opslaan op de juiste plek.
	 *
	 * @param string $directory fully qualified path with trailing slash
	 * @param string $filename filename with extension
	 * @param boolean $overwrite allowed to overwrite existing file
	 * @throws Exception Ongeldige bestandsnaam, doelmap niet schrijfbaar of naam ingebruik
	 */
	public function opslaan($directory, $filename, $overwrite = false) {
		if (!$this->isAvailable()) {
			throw new Exception('Uploadmethode niet beschikbaar: ' . get_class($this));
		}
		if (!$this->validate()) {
			throw new Exception($this->getError());
		}
		if (!valid_filename($filename)) {
			throw new Exception('Ongeldige bestandsnaam: ' . htmlspecialchars($filename));
		}
		if (!file_exists($directory)) {
			mkdir($directory);
		}
		if (false === @chmod($directory, 0755)) {
			throw new Exception('Geen eigenaar van map: ' . htmlspecialchars($directory));
		}
		if (!is_writable($directory)) {
			throw new Exception('Doelmap is niet beschrijfbaar: ' . htmlspecialchars($directory));
		}
		if (file_exists($directory . $filename)) {
			if ($overwrite) {
				if (!unlink($directory . $filename)) {
					throw new Exception('Overschrijven mislukt: ' . htmlspecialchars($directory . $filename));
				}
			} elseif (!$this instanceof BestandBehouden) {
				throw new Exception('Bestandsnaam al in gebruik: ' . htmlspecialchars($directory . $filename));
			}
		}
	}

	/**
	 * Elk veld staat in een div, geef de html terug voor de openingstag van die div.
	 */
	public function getDiv() {
		$cssclass = 'InputField';
		if ($this->hidden) {
			$cssclass .= ' verborgen';
		}
		if ($this->title) {
			$cssclass .= ' hoverIntent';
		}
		if ($this->getError() !== '') {
			$cssclass .= ' metFouten';
		}
		return '<div id="wrapper_' . $this->getId() . '" class="' . $cssclass . '" ' . $this->getInputAttribute('title') . '>';
	}

	/**
	 * Elk veld heeft een label, geef de html voor het label
	 */
	public function getLabel() {
		if (!empty($this->description)) {
			$required = '';
			if ($this->required) {
				if ($this->leden_mod AND LoginModel::mag('P_LEDEN_MOD')) {
					// exception for leden mod
				} else {
					$required = '<span class="required"> *</span>';
				}
			}
			$help = '';
			if ($this->title) {
				$help = '<div class="help" onclick="alert(\'' . addslashes($this->title) . '\');">' . Icon::getTag('help', null, null, 'icon hoverIntentContent') . '</div>';
			}
			return '<label for="' . $this->getId() . '">' . $help . $this->description . $required . '</label>';
		}
		return '';
	}

	/**
	 * Geef de foutmelding voor dit veld terug.
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Geef een div met de foutmelding voor dit veld terug.
	 */
	public function getErrorDiv() {
		if ($this->getError() != '') {
			return '<div class="waarschuwing">' . $this->getError() . '</div>';
		}
		return '';
	}

	public function getPreviewDiv() {
		return '';
	}

	/**
	 * Geef lijst van allerlei CSS-classes voor dit veld terug.
	 */
	protected function getCssClasses() {
		if ($this->required) {
			if ($this->leden_mod AND LoginModel::mag('P_LEDEN_MOD')) {
				// exception for leden mod
			} else {
				$this->css_classes[] = 'required';
			}
		}
		if ($this->readonly) {
			$this->css_classes[] = 'readonly';
		}
		return $this->css_classes;
	}

	/**
	 * Gecentraliseerde genereermethode voor de attributen van de
	 * input-tag.
	 * Dit is bij veel dingen het zelfde, en het is niet zo handig om in
	 * elke instantie dan bijvoorbeeld de prefix van het id-veld te
	 * moeten aanpassen. Niet meer nodig dus.
	 */
	protected function getInputAttribute($attribute) {
		if (is_array($attribute)) {
			$return = '';
			foreach ($attribute as $a) {
				$return .= ' ' . $this->getInputAttribute($a);
			}
			return $return;
		}
		switch ($attribute) {
			case 'id':
				return 'id="' . $this->getId() . '"';
			case 'class':
				return 'class="' . implode(' ', $this->getCssClasses()) . '"';
			case 'value':
				return 'value="' . htmlspecialchars($this->value) . '"';
			case 'origvalue':
				return 'origvalue="' . htmlspecialchars($this->origvalue) . '"';
			case 'name':
				return 'name="' . $this->name . '"';
			case 'type':
				if ($this->hidden) {
					$type = 'hidden';
				} else {
					$type = $this->type;
				}
				return 'type="' . $type . '"';
			case 'title':
				if ($this->title) {
					return 'title="' . htmlspecialchars($this->title) . '"';
				}
				break;
			case 'readonly':
				if ($this->readonly) {
					return 'readonly';
				}
				break;
			case 'placeholder':
				if ($this->placeholder != null) {
					return 'placeholder="' . $this->placeholder . '"';
				}
				break;
			case 'maxlength':
				if (is_int($this->max_len)) {
					return 'maxlength="' . $this->max_len . '"';
				}
				break;
			case 'rows':
				if (is_int($this->rows)) {
					return 'rows="' . $this->rows . '"';
				}
				break;

			case 'autocomplete':
				if (!$this->autocomplete OR !empty($this->suggestions)) {
					return 'autocomplete="off"'; // browser autocompete
				}
				break;
			case 'pattern':
				if ($this->pattern) {
					return 'pattern="' . $this->pattern . '"';
				}
				break;
			case 'step':
				if ($this->step > 0) {
					return 'step="' . $this->step . '"';
				}
				break;
			case 'min':
				if ($this->min !== null) {
					return 'min="' . $this->min . '"';
				}
				break;
			case 'max':
				if ($this->max !== null) {
					return 'max="' . $this->max . '"';
				}
				break;
		}
		return '';
	}

	public function getHtml() {
		return '<input ' . $this->getInputAttribute(array('type', 'id', 'name', 'class', 'value', 'origvalue', 'disabled', 'readonly', 'maxlength', 'placeholder', 'autocomplete')) . ' />';
	}

	/**
	 * View die zou moeten werken voor veel velden.
	 */
	public function view() {
		echo $this->getDiv();
		echo $this->getLabel();
		echo $this->getErrorDiv();
		echo $this->getHtml();
		if ($this->preview) {
			echo $this->getPreviewDiv();
		}
		echo '</div>';
	}

	/**
	 * Javascript nodig voor dit *Field. Dit wordt één keer per *Field
	 * geprint door het Formulier-object.
	 *
	 * TODO: client side validation
	 *
	 * Toelichting op options voor RemoteSuggestions:
	 * result = array(
	 *        array(data:array(..,..,..), value: "string", result:"string"),
	 *        array(... )
	 * )
	 * formatItem geneert html-items voor de suggestielijst, afstemmen op data-array
	 */
	public function getJavascript() {
		$js = <<<JS

/* {$this->name} */
JS;
		if ($this->readonly) {
			return $js;
		}
		if ($this->onchange_submit) {
			$this->onchange .= <<<JS

	form_submit(event);
JS;
		}
		if ($this->enter_submit) {
			$this->onkeydown .= <<<JS

	if (event.keyCode === 13) {
		event.preventDefault();
	}
JS;
			$this->onkeyup .= <<<JS

	if (event.keyCode === 13) {
		form_submit(event);
	}
JS;
		}
		if ($this->escape_cancel) {
			$this->onkeydown .= <<<JS

	if (event.keyCode === 27) {
		form_cancel(event);
	}
JS;
		}
		if ($this->onchange !== null) {
			$js .= <<<JS

$('#{$this->getId()}').change(function(event) {
	{$this->onchange}
});
JS;
		}
		if ($this->onclick !== null) {
			$js .= <<<JS

$('#{$this->getId()}').click(function(event) {
	{$this->onclick}
});
JS;
		}
		if ($this->onkeydown !== null) {
			$js .= <<<JS

$('#{$this->getId()}').keydown(function(event) {
	{$this->onkeydown}
});
JS;
		}
		if ($this->onkeyup !== null) {
			$js .= <<<JS

$('#{$this->getId()}').keyup(function(event) {
	{$this->onkeyup}
});
JS;
		}
		$dataset = array();
		foreach ($this->suggestions as $name => $source) {
			$dataset[$name] = uniqid($this->name);

			$js .= <<<JS

var {$dataset[$name]} = new Bloodhound({
	datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
	queryTokenizer: Bloodhound.tokenizers.whitespace,
	limit: 5,
JS;
			if (is_array($source)) {
				$suggestions = array_values($source);
				foreach ($suggestions as $i => $suggestion) {
					if (!is_array($suggestion)) {
						$suggestions[$i] = array('value' => $suggestion);
					}
				}
				$json = json_encode($suggestions);
				$js .= <<<JS

	local: {$json}

JS;
			} else {
				$js .= <<<JS

	remote: "{$source}%QUERY"

JS;
			}
			$js .= <<<JS
});
{$dataset[$name]}.initialize();
JS;
		}
		if (!empty($this->suggestions)) {
			$js .= <<<JS

$('#{$this->getId()}').typeahead({
	autoselect: true,
	hint: true,
	highlight: true,
	minLength: 1
}
JS;
		}
		foreach ($this->suggestions as $name => $source) {
			if (is_int($name)) {
				$header = '';
			} else {
				$header = 'header: "<h3>' . $name . '</h3>",';
			}
			if (array_search('clicktogo', $this->css_classes)) {
				$clicktogo = '';
			} else {
				$clicktogo = ' onclick="event.preventDefault();return false;"';
			}
			$js .= <<<JS
, {
	name: "{$dataset[$name]}",
	displayKey: "value",
	source: {$dataset[$name]}.ttAdapter(),
	templates: {
		{$header}
		suggestion: function (suggestion) {
			var html = '<p';
			if (suggestion.title) {
				html += ' title="' + suggestion.title + '"';
			}
			html += '><a class="suggestionUrl" href="' + suggestion . url + '"{$clicktogo}>';
			if (suggestion.icon) {
				html += suggestion.icon;
			}
			html += suggestion.value;
			if (suggestion.label) {
				html += '<span class="lichtgrijs"> - ' + suggestion.label + '</span>';
			}
			return html + '</a></p>';
		}
	}
}
JS;
		}
		if (!empty($this->suggestions)) {
			$js .= <<<JS
);
JS;
			$this->typeahead_selected .= <<<JS

$(this).trigger('change');
JS;
		}
		if ($this->typeahead_selected !== null) {
			$js .= <<<JS

$('#{$this->getId()}').on('typeahead:selected', function (event, suggestion, dataset) {
	{$this->typeahead_selected}
});
JS;
		}
		return $js;
	}

}
