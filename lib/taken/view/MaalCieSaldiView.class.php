<?php
namespace Taken\MLT;
/**
 * MaalCieSaldiView.class.php	| 	P.W.G. Brussee (brussee@live.nl)
 * 
 * Tonen van een upload tool voor het bijwerken de MaalCie saldi op de stek.
 * 
 */
class MaalCieSaldiView extends \SimpleHtml {
	
	public function getTitel() {
		return 'MaalCie-saldi uploaden met een CSV-bestand';
	}
	
	public function view() {
		$smarty= new \Smarty_csr();
		$smarty->assign('melding', $this->getMelding());
		$smarty->assign('kop', $this->getTitel());
		$smarty->display('taken/taken_menu.tpl');
		$smarty->display('taken/maalcie_saldi.tpl');
	}
}

?>