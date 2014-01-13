<?php


require_once 'taken/model/FunctiesModel.class.php';
require_once 'taken/model/KwalificatiesModel.class.php';
require_once 'taken/view/BeheerFunctiesView.class.php';
require_once 'taken/view/forms/FunctieFormView.class.php';
require_once 'taken/view/forms/KwalificatieFormView.class.php';

/**
 * BeheerFunctiesController.class.php	| 	P.W.G. Brussee (brussee@live.nl)
 * 
 */
class BeheerFunctiesController extends \AclController {

	public function __construct($query) {
		parent::__construct($query);
		if (!parent::isPOSTed()) {
			$this->acl = array(
				'beheer' => 'P_CORVEE_MOD'
			);
		}
		else {
			$this->acl = array(
				'nieuw' => 'P_CORVEE_MOD',
				'bewerk' => 'P_CORVEE_MOD',
				'opslaan' => 'P_CORVEE_MOD',
				'verwijder' => 'P_CORVEE_MOD',
				'kwalificeer' => 'P_CORVEE_MOD',
				'dekwalificeer' => 'P_CORVEE_MOD'
			);
		}
		$this->action = 'beheer';
		if ($this->hasParam(2)) {
			$this->action = $this->getParam(2);
		}
		$fid = null;
		if ($this->hasParam(3)) {
			$fid = intval($this->getParam(3));
		}
		$this->performAction(array($fid));
	}
	
	public function beheer($fid=null) {
		if (is_int($fid) && $fid > 0) {
			$this->bewerk($fid);
		}
		$functies = FunctiesModel::getAlleFuncties();
		KwalificatiesModel::loadKwalificatiesVoorFuncties($functies);
		$this->view = new BeheerFunctiesView($functies, $this->getContent());
		$this->view = new csrdelft($this->getContent());
		$this->view->addStylesheet('js/autocomplete/jquery.autocomplete.css');
		$this->view->addStylesheet('taken.css');
		$this->view->addScript('autocomplete/jquery.autocomplete.min.js');
		$this->view->addScript('taken.js');
	}
	
	public function nieuw() {
		$functie = new CorveeFunctie();
		$this->view = new FunctieFormView($functie->getFunctieId(), $functie->getNaam(), $functie->getAfkorting(), $functie->getEmailBericht(), $functie->getStandaardPunten(), $functie->getIsKwalificatieBenodigd()); // fetches POST values itself
	}
	
	public function bewerk($fid) {
		$functie = FunctiesModel::getFunctie($fid);
		$this->view = new FunctieFormView($functie->getFunctieId(), $functie->getNaam(), $functie->getAfkorting(), $functie->getEmailBericht(), $functie->getStandaardPunten(), $functie->getIsKwalificatieBenodigd()); // fetches POST values itself
	}
	
	public function opslaan($fid) {
		if ($fid > 0) {
			$this->bewerk($fid);
		}
		else {
			$this->view = new FunctieFormView($fid); // fetches POST values itself
		}
		if ($this->view->validate()) {
			$values = $this->view->getValues();
			$functie = FunctiesModel::saveFunctie($fid, $values['naam'], $values['afkorting'], $values['email_bericht'], $values['standaard_punten'], $values['kwalificatie_benodigd']);
			$functie->setGekwalificeerden(KwalificatiesModel::getKwalificatiesVoorFunctie($functie));
			$this->view = new BeheerFunctiesView($functie);
		}
	}
	
	public function verwijder($fid) {
		FunctiesModel::verwijderFunctie($fid);
		$this->view = new BeheerFunctiesView($fid);
	}
	
	public function kwalificeer($fid) {
		$form = new KwalificatieFormView($fid); // fetches POST values itself
		if ($form->validate()) {
			$values = $form->getValues();
			KwalificatiesModel::kwalificatieToewijzen($fid, $values['voor_lid']);
			$functie = FunctiesModel::getFunctie($fid);
			$functie->setGekwalificeerden(KwalificatiesModel::getKwalificatiesVoorFunctie($functie));
			$this->view = new BeheerFunctiesView($functie);
		}
		else {
			$this->view = $form;
		}
	}
	
	public function dekwalificeer($fid) {
		$uid = filter_input(INPUT_POST, 'voor_lid', FILTER_SANITIZE_STRING);
		if (!\Lid::exists($uid)) {
			throw new Exception('Lid bestaat niet: $uid ='. $uid);
		}
		KwalificatiesModel::kwalificatieTerugtrekken($fid, $uid);
		$functie = FunctiesModel::getFunctie($fid);
		$functie->setGekwalificeerden(KwalificatiesModel::getKwalificatiesVoorFunctie($functie));
		$this->view = new BeheerFunctiesView($functie);
	}
}

?>