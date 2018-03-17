<?php

namespace CsrDelft\view\commissievoorkeuren;

use CsrDelft\model\commissievoorkeuren\CommissieVoorkeurenModel;
use CsrDelft\model\commissievoorkeuren\CommissieVoorkeurModel;
use CsrDelft\model\commissievoorkeuren\VoorkeurCommissieCategorieModel;
use CsrDelft\model\commissievoorkeuren\VoorkeurCommissieModel;
use CsrDelft\model\commissievoorkeuren\VoorkeurOpmerkingModel;
use CsrDelft\model\entity\commissievoorkeuren\VoorkeurCommissie;
use CsrDelft\model\entity\commissievoorkeuren\VoorkeurVoorkeur;
use CsrDelft\model\entity\Profiel;
use CsrDelft\view\formulier\elementen\HtmlComment;
use CsrDelft\view\formulier\elementen\Subkopje;
use CsrDelft\view\formulier\Formulier;
use CsrDelft\view\formulier\invoervelden\TextareaField;
use CsrDelft\view\formulier\keuzevelden\SelectField;
use CsrDelft\view\formulier\knoppen\FormDefaultKnoppen;

class CommissieVoorkeurenForm extends Formulier {

	public function getBreadcrumbs() {
		return '<a href="/ledenlijst" title="Ledenlijst"><span class="fa fa-user module-icon"></span></a> » ' . $this->profiel->getLink('civitas') . ' » <span class="active">' . $this->titel . '</span>';
	}

	private $voorkeuren = array();
	private $opmerking;
    private $profiel;
    private $voorkeurVelden = array();
    private $opmerkingVeld = array();
    private $categorieMap = array();
	public function __construct(Profiel $profiel) {
		parent::__construct(null, '/profiel/' . $profiel->uid . '/voorkeuren', 'Commissie-voorkeuren');
        $this->profiel = $profiel;

        $this->addFields([new HtmlComment('<p>Hier kunt u per commissie opgeven of u daar interesse in heeft!</p>')]);

        $categorieCommissie = $this->getCategorieCommissieMap();

        foreach ($categorieCommissie as $cid => $commissies) {
            $cat = $this->categorieMap[$cid];
            $this->addFields([new Subkopje($cat->naam)]);
            foreach ($commissies as $commissie) {
                $this->addVoorkeurVeld($commissie);
            }
        }

        $this->opmerking = VoorkeurOpmerkingModel::instance()->getOpmerkingVoorLid($profiel);

        $fields[] = new Subkopje("Extra opmerkingen");
		$opmerkingVeld = new TextareaField('lidOpmerking', $this->opmerking->lidOpmerking, 'Vul hier je eventuele voorkeur voor functie in, of andere opmerkingen');
		$this->opmerking->lidOpmerking = $opmerkingVeld->getValue();
		$fields[] = $opmerkingVeld;

		$fields[] = new FormDefaultKnoppen('/profiel/' . $profiel->uid);

		$this->addFields($fields);
	}

	private function addVoorkeurVeld(VoorkeurCommissie $commissie) {
        $opties = array(1 => 'nee', 2 => 'misschien', 3 => 'ja');
        $voorkeur = CommissieVoorkeurModel::instance()->getVoorkeur($this->profiel, $commissie);
        $this->voorkeuren[] = $voorkeur;
	    $field = new SelectField('comm' . $commissie->id, $voorkeur->voorkeur, $commissie->naam, $opties);
	    $this->addFields([$field]);
		$voorkeur->voorkeur = $field;
	}

    public function getVoorkeuren() : array
    {
        return $this->voorkeuren;
    }

    public function getOpmerking()
    {
        return $this->opmerking;
    }

    private function getCategorieCommissieMap() : array
    {
        $categorieCommissie = array();
        $categorien = VoorkeurCommissieCategorieModel::instance()->find()->fetchAll();
        foreach ($categorien as $cat) {
            $this->categorieCommissie[$cat->id] = [];
            $this->categorieMap[$cat->id] = $cat;
        }
        $commissies = VoorkeurCommissieModel::instance()->find("zichtbaar = 1");
        foreach ($commissies as $commissie) {
            $categorieCommissie[$commissie->categorie_id][] = $commissie;
        }
        return $categorieCommissie;
    }

}
