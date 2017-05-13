<?php
namespace CsrDelft\view\formulier\invoervelden;
use function CsrDelft\email_like;

/**
 * EmailField.class.php
 *
 * @author P.W.G. Brussee <brussee@live.nl>
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @date 30/03/2017
 */
class EmailField extends TextField {

	public function validate() {
		if (!parent::validate()) {
			return false;
		}
		// parent checks not null
		if ($this->value == '') {
			return true;
		}
		// check format
		if (!email_like($this->value)) {
			$this->error = 'Ongeldig e-mailadres';
		} // check dns record
		else {
			$parts = explode('@', $this->value, 2);
			if (!checkdnsrr($parts[1], 'A') AND !checkdnsrr($parts[1], 'MX')) {
				$this->error = 'E-mailadres bestaat niet';
			}
		}
		return $this->error === '';
	}

}