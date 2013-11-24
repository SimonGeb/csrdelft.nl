<?php
namespace Taken\MLT;
/**
 * MaaltijdLijstView.class.php	| 	P.W.G. Brussee (brussee@live.nl)
 * 
 * Tonen van de lijst van aanmeldingen, betaalmogelijkheden en maaltijdgegevens.
 * 
 */
class MaaltijdLijstView extends \SimpleHtml {

	private $_maaltijd;
	private $_aanmeldingen;
	private $_corvee;
	private $_fiscaal;
	
	public function __construct($maaltijd, $aanmeldingen, $corvee, $fiscaal=false) {
		$this->_maaltijd = $maaltijd;
		$this->_aanmeldingen = $aanmeldingen;
		$this->_corvee = $corvee;
		$this->_fiscaal = $fiscaal;
	}
	
	public function getTitel() {
		return $this->_maaltijd->getTitel();
	}
	
	public function view() {
		$smarty = new \Smarty_csr();
		$smarty->assign('kop', $this->getTitel());
		$smarty->assign('maaltijd', $this->_maaltijd);
		$smarty->assign('header', $GLOBALS['maaltijdlijst_tekst']);
		
		if (!$this->_fiscaal) {
			for ($i = $this->_maaltijd->getMarge(); $i > 0; $i--) {
				$this->_aanmeldingen[] = new MaaltijdAanmelding();
			}
			$smarty->assign('eterstotaal', $this->_maaltijd->getAantalAanmeldingen() + $this->_maaltijd->getMarge());
			$smarty->assign('corveetaken', $this->_corvee);
		}
		$smarty->assign('aanmeldingen', $this->_aanmeldingen);
		
		if ($this->_fiscaal) {
			$smarty->display('taken/maaltijd/maaltijd_lijst_fiscaal.tpl');
		}
		else {
			$smarty->display('taken/maaltijd/maaltijd_lijst.tpl');
		}
	}
}

?>