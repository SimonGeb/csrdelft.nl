<?php

namespace CsrDelft\view\formulier\invoervelden;

/**
 * @author P.W.G. Brussee <brussee@live.nl>
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 30/03/2017
 *
 * Een TextField is een elementaire input-tag en heeft een maximale lengte.
 * HTML wordt ge-escaped.
 * Uiteraard kunnen er suggesties worden opgegeven.
 */
class TextField extends InputField
{
	public $max_len = null; // maximale lengte van de invoer
	public $min_len = null; // minimale lengte van de invoer

	public function __construct(
		$name,
		$value,
		$description,
		$max_len = 255,
		$min_len = 0,
		$model = null
	) {
		parent::__construct(
			$name,
			$value === null ? $value : htmlspecialchars_decode($value),
			$description,
			$model
		);
		if (is_int($max_len)) {
			$this->max_len = $max_len;
		}
		if (is_int($min_len)) {
			$this->min_len = $min_len;
		}
		if ($this->isPosted()) {
			// reverse InputField constructor $this->getValue()
			$this->value = htmlspecialchars_decode($this->value);
		}
	}

	protected function getInputAttribute($attribute)
	{
		if ($attribute == 'maxlength' && is_int($this->max_len)) {
			return 'maxlength="' . $this->max_len . '"';
		}
		return parent::getInputAttribute($attribute); // TODO: Change the autogenerated stub
	}

	public function validate()
	{
		if (!parent::validate()) {
			return false;
		}
		// als max_len is gezet dan checken of de lengte er niet boven zit
		if (is_int($this->max_len) and strlen($this->value) > $this->max_len) {
			$this->error =
				'Dit veld mag maximaal ' . $this->max_len . ' tekens lang zijn';
		}
		// als min_len is gezet dan checken of de lengte er niet onder zit
		if (is_int($this->min_len) and strlen($this->value) < $this->min_len) {
			$this->error =
				'Dit veld moet minimaal ' . $this->min_len . ' tekens lang zijn';
		}
		if ($this->value !== null and !is_utf8($this->value)) {
			$this->error = 'Ongeldige karakters, gebruik reguliere tekst';
		}
		return $this->error === '';
	}

	public function getValue()
	{
		$this->value = parent::getValue();
		if ($this->empty_null and $this->value == '') {
			return null;
		}
		return htmlspecialchars($this->value);
	}
}
