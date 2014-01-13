<?php

/**
 * BeheerVrijstellingenView.class.php	| 	P.W.G. Brussee (brussee@live.nl)
 * 
 * Tonen van alle vrijstellingen om te beheren.
 * 
 */
class BeheerVrijstellingenView extends TemplateView {

	private $_vrijstellingen;

	public function __construct($vrijstellingen) {
		parent::__construct();
		$this->_vrijstellingen = $vrijstellingen;
	}

	public function getTitel() {
		return 'Beheer vrijstellingen';
	}

	public function view() {
		if (is_array($this->_vrijstellingen)) {
			$this->assign('melding', $this->getMelding());
			$this->assign('kop', $this->getTitel());
			$this->display('taken/menu_pagina.tpl');

			$this->assign('vrijstellingen', $this->_vrijstellingen);
			$this->display('taken/vrijstelling/beheer_vrijstellingen.tpl');
		} elseif (is_string($this->_vrijstellingen)) { // id of deleted corveefunctie
			echo '<tr id="vrijstelling-row-' . $this->_vrijstellingen . '" class="remove"></tr>';
		} else {
			$this->assign('vrijstelling', $this->_vrijstellingen);
			$this->display('taken/vrijstelling/beheer_vrijstelling_lijst.tpl');
		}
	}

}

?>