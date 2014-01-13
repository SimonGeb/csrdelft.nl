<?php

class IsHetAlContent extends TemplateView {

	private $ishetal = null;
	private $opties = array('jarig', 'vrijdag', 'donderdag', 'zondag', 'borrel', 'lezing', 'lunch', 'avond');
	private $ja = false; //ja of nee.

	public function __construct($ishetal) {
		parent::__construct();
		if ($ishetal == 'willekeurig') {
			$this->ishetal = $this->opties[array_rand($this->opties)];
		} else {
			$this->ishetal = Instellingen::get('zijbalk_ishetal');
		}
		switch ($this->ishetal) {
			case 'jarig': $this->ja = LoginLid::instance()->getLid()->getJarigOver();
				break;
			case 'lunch': $this->ja = (date('Hi') > '1245' AND date('Hi') < '1345');
				break;
			case 'avond': $this->ja = (date('Hi') > '1700');
				break;
			case 'vrijdag': $this->ja = (date('w') == 5);
				break;
			case 'donderdag': $this->ja = (date('w') == 4);
				break;
			case 'zondag': $this->ja = (date('w') == 0);
				break;
			case 'borrel':
				require_once 'agenda/agenda.class.php';
				$agenda = new Agenda();
				$vandaag = $agenda->isActiviteitGaande($ishetal);
				if ($vandaag instanceof AgendaItem) {
					if ($ishetal == 'borrel') {
						$this->ja = time() > $vandaag->getBeginMoment();
					} else {
						$this->ja = time() > $vandaag->getBeginMoment() AND time() < $vandaag->getEindMoment();
					}
				}
				break;
			case 'studeren':
				if (isset($_COOKIE['studeren'])) {
					$this->ja = (time() > ($_COOKIE['studeren'] + 5 * 60) AND date('w') != 0);
					$tijd = $_COOKIE['studeren'];
				} else {
					$tijd = time();
				}
				setcookie('studeren', $tijd, time() + 30 * 60);
				break;
		}
	}

	public function view() {
		switch ($this->ishetal) {
			case 'jarig':
				echo '<div id="ishetalvrijdag">Ben ik al jarig?<br />';
				break;
			case 'studeren':
				echo '<div id="ishetalvrijdag">Moet ik alweer studeren?<br />';
				break;
			case 'borrel':
			case 'lezing':
				echo '<div id="ishetalvrijdag">Is er een ' . $this->ishetal . '?<br />';
				break;
			default:
				echo '<div id="ishetalvrijdag">Is het al ' . $this->ishetal . '?<br />';
				break;
		}

		if ($this->ja === true) {
			echo '<div class="ja">JA!</div>';
		} else {
			if ($this->ishetal == 'jarig') {
				echo '<div class="nee">OVER ' . $this->ja . ' DAGEN!</div>';
			} else {
				echo '<div class="nee">NEE.</div>';
			}
		}
		echo '</div><br />';
	}

}
