<?php

# C.S.R. Delft | pubcie@csrdelft.nl
# -------------------------------------------------------------------
# courant/class.courant.php
# -------------------------------------------------------------------
# Verzorgt het opvragen van courantgegevens
# -------------------------------------------------------------------

class Courant {

	//huidige courant, 0 is de nog niet verzonden cache.
	private $courantID = 0;
	private $berichten = array();
	private $sError = '';
	private $categorieen = array('bestuur', 'csr', 'overig', 'voorwoord', 'sponsor');
	private $catNames = array('Bestuur', 'C.S.R.', 'Overig', 'Voorwoord', 'Sponsor');

	//Constructor voor de courant
	public function __construct() {
		//de berichten uit de cache laden. Dit zal het meest gebeuren.
		$this->load(0);
	}

	/*
	 * Courant inladen uit de database.
	 */

	public function load($courantID) {
		$this->courantID = (int) $courantID;
		//leegmaken van de berichtenarray
		$this->berichten = array();
		if ($this->isCache()) {
			$sBerichtenQuery = "
				SELECT
					ID, titel, cat AS categorie, bericht, datumTijd, uid, volgorde
				FROM
					courantcache
				WHERE
					1
				ORDER BY
					cat, volgorde, datumTijd;";
		} else {
			$sBerichtenQuery = "
				SELECT
					courant.ID AS mailID,
					courant.verzendMoment AS verzendMoment,
					courant.verzender AS verzendUid,
					courant.template AS template,
					courantbericht.ID AS ID,
					titel,
					cat AS categorie,
					bericht,
					datumTijd,
					courantbericht.uid AS berichtUid,
					volgorde
				FROM
					courant, courantbericht
				WHERE
					courant.ID=" . $this->getID() . "
				AND
					courant.ID=courantbericht.courantID
				ORDER BY
					cat, volgorde, datumTijd;";
		}
		$db = MySql::instance();
		$rBerichten = $db->query($sBerichtenQuery);
		if ($db->numRows($rBerichten) >= 1) {
			while ($aBericht = $db->next($rBerichten)) {
				$this->berichten[$aBericht['ID']] = $aBericht;
			}
			return true;
		}
		return false;
	}

	public function getID() {
		return $this->courantID;
	}

	public function getError() {
		return $this->sError;
	}

	public function isCache() {
		return $this->courantID == 0;
	}

	public function getCats($nice = false) {
		if ($nice) {
			$return = $this->catNames;
		} else {
			$return = $this->categorieen;
		}
		//Voorwoord eruitgooien, behalve voor beheerders
		if (!$this->magBeheren()) {
			unset($return[3]);
		}
		//Sponsors eruitgooien, behalve voor beheerders en/of AcqCiee
		if (!$this->magBeheren() && !LoginLid::mag('groep:AcqCie')) {
			unset($return[4]);
		}
		return $return;
	}

	public function getNaam($uid) {
		return LidCache::getLid($uid)->getNaam();
	}

	public function getTemplatePath() {
		$return = SMARTY_TEMPLATE_DIR . 'courant/mail/';
		$firstbericht = array_slice($this->berichten, 0, 1);
		if (isset($firstbericht[0]['template']) AND file_exists($return . $firstbericht[0]['template'])) {
			$return.=$firstbericht[0]['template'];
		} else {
			$return.=COURANT_TEMPLATE;
		}
		return $return;
	}

	public static function magToevoegen() {
		return LoginLid::mag('P_MAIL_POST');
	}

	public static function magBeheren() {
		return LoginLid::mag('P_MAIL_COMPOSE');
	}

	public static function magVerzenden() {
		return LoginLid::mag('P_MAIL_SEND');
	}

	private function _isValideCategorie($categorie) {
		return in_array($categorie, $this->categorieen);
	}

	private function clearTitel($titel) {
		//titel escapen, eerste letter een hoofdletter maken, en de spaties wegkekken
		return ucfirst(MySql::instance()->escape(trim($titel)));
	}

	private function clearBericht($bericht) {
		//bericht escapen, eerste letter een hoofdletter maken, en de spaties wegkekken
		return ucfirst(MySql::instance()->escape(trim($bericht)));
	}

	private function clearCategorie($categorie) {
		if ($this->_isValideCategorie($categorie)) {
			return $categorie;
		} else {
			return 'overig';
		}
	}

	public function addBericht($titel, $categorie, $bericht) {
		//berichten invoeren mag enkel in de cache
		if (!$this->isCache()) {
			$this->sError = 'Berichten mogen enkel in de cache worden ingevoerd. (Courant::addBericht())';
			return false;
		}

		//volgorde van berichten bepalen:
		$volgorde = 0;
		//agenda altijd helemaal bovenaan
		if (strtolower(trim($titel)) == 'agenda') {
			$volgorde = -1000;
		}
		//andere dingen naar achteren
		if (preg_match('/kamer/i', $titel)) {
			$volgorde = 99;
		}
		if (preg_match('/ampel/i', $titel) OR preg_match('/ampel/i', $bericht)) {
			$volgorde = 999;
		}

		$sBerichtQuery = "
			INSERT INTO
				courantcache
			(
				uid, titel, cat, bericht, datumTijd, volgorde
			)VALUES(
				'" . LoginLid::instance()->getUid() . "', '" . $this->clearTitel($titel) . "',
				'" . $this->clearCategorie($categorie) . "', '" . $this->clearBericht($bericht) . "', '" . getDateTime() . "', " . $volgorde . "
			);";

		return MySql::instance()->query($sBerichtQuery);
	}

	public function isZichtbaar($iBerichtID) {
		$iBerichtID = (int) $iBerichtID;
		if ($this->isCache()) {
			if ($this->magBeheren()) {
				return true;
			}
			if (!isset($this->berichten[$iBerichtID])) {
				$this->sError = 'Bericht staat niet in cache (Courant::isBewerkbaar())';
			} else {
				if (!LoginLid::instance()->isSelf($this->berichten[$iBerichtID]['uid'])) {
					$this->sError = 'U mag geen berichten van anderen aanpassen. (Courant::isBewerkbaar())';
				} else {
					return true;
				}
			}
		} else {
			$this->sError = 'Berichten mogen enkel in de cache worden ingevoerd. (Courant::isBewerkbaar())';
		}
		return false;
	}

	public function bewerkBericht($iBerichtID, $titel, $categorie, $bericht) {
		$iBerichtID = (int) $iBerichtID;
		if (!$this->isZichtbaar($iBerichtID)) {
			return false;
		}
		$sBerichtQuery = "
			UPDATE
				courantcache
			SET
				titel='" . $this->clearTitel($titel) . "',
				cat='" . $this->clearCategorie($categorie) . "',
				bericht='" . $this->clearBericht($bericht) . "',
				datumTijd='" . getDateTime() . "'
			WHERE
				ID=" . $iBerichtID . "
			LIMIT 1;";
		return MySql::instance()->query($sBerichtQuery);
	}

	public function valideerBerichtInvoer() {
		$bValid = true;
		if (isset($_POST['titel']) AND isset($_POST['categorie']) AND isset($_POST['bericht'])) {
			//titel minimaal 2 tekens
			if (strlen(trim($_POST['titel'])) < 2) {
				$bValid = false;
				$this->sError.='Het veld <strong>titel</strong> moet minstens 2 tekens bevatten.<br />';
			}
			//titel niet langer dan 50 tekens
			if (strlen(trim($_POST['titel'])) > 50) {
				$bValid = false;
				$this->sError.='Het veld <strong>titel</strong> mag maximaal 30 tekens bevatten.<br />';
			}
			//bericht minstens 15 tekens.
			if (strlen(trim($_POST['bericht'])) < 15) {
				$bValid = false;
				$this->sError.='Het veld <strong>bericht</strong> moet minstens 15 tekens bevatten.<br />';
			}
		} else {
			$bValid = false;
			$this->sError.='Het formulier is niet compleet<br />';
		}
		return $bValid;
	}

	public function getVerzendmoment() {
		if (!$this->isCache()) {
			//beetje ranzige manier om het eerste element van de array aan te spreken
			$first = current($this->berichten);
			return $first['verzendMoment'];
		} else {
			$this->sError = 'De cache is nog niet verzonden, dus heeft geen verzendmoment (Courant::getVerzendMoment())';
			return false;
		}
	}

	public function getBerichten() {
		if (!is_array($this->berichten)) {
			$this->sError = 'Er zijn geen berichten ingeladen (Courant::getBerichten())';
			return false;
		}
		return $this->berichten;
	}

	public function getBerichtenCount() {
		return count($this->getBerichten());
	}

	/*
	 * Geef de berichten uit de cache terug die de huidige gebruiker mag zien.
	 * Als de gebruiker beheerder of bestuur is mag de gebruiker alle berichten zien.
	 */

	public function getBerichtenVoorGebruiker() {
		if ($this->isCache()) {
			$userCache = array();
			//mods en bestuur zien alle berichten
			if ($this->magBeheren() OR LoginLid::mag('groep:bestuur')) {
				return $this->berichten;
			} else {
				foreach ($this->berichten as $bericht) {
					if (LoginLid::instance()->isSelf($bericht['uid'])) {
						$userCache[] = $bericht;
					}
				}
				return $userCache;
			}
		} else {
			$this->sError = 'Buiten de cache kan niets bewerkt worden (Courant::getBerichtenVoorGebruiker()).';
			return false;
		}
	}

	public function getBericht($iBerichtID) {
		$iBerichtID = (int) $iBerichtID;
		if (!$this->isZichtbaar($iBerichtID)) {
			return false;
		}
		return $this->berichten[$iBerichtID];
	}

	public function verwijderBericht($iBerichtID) {
		$iBerichtID = (int) $iBerichtID;
		if (!$this->isZichtbaar($iBerichtID)) {
			return false;
		}
		$sBerichtVerwijderen = "
			DELETE FROM
				courantcache
			WHERE
				ID=" . $iBerichtID . "
			LIMIT 1;";
		$db = MySql::instance();
		$db->query($sBerichtVerwijderen);
		return $db->affected_rows() == 1;
	}

	/*
	 * functie rost alles vanuit de tabel courantcache naar de tabel
	 * courant en courantbericht, zodat ze daar bewaard kunnen worden ter archivering.
	 */

	public function leegCache() {
		if (count($this->getBerichten()) == 0) {
			$this->sError = 'Courant bevat helemaal geen berichten (Courant::leegCache())';
			return false;
		}
		$db = MySql::instance();
		$iCourantID = $this->createCourant();
		if (is_integer($iCourantID)) {
			//kopieren dan maar
			foreach ($this->getBerichten() as $aBericht) {
				$sMoveQuery = "
					INSERT INTO
						courantbericht
					(
						courantID, titel, cat, bericht, volgorde, uid, datumTijd
					)VALUES(
						" . $iCourantID . ",
						'" . $this->clearTitel($aBericht['titel']) . "',
						'" . $this->clearCategorie($aBericht['categorie']) . "',
						'" . $this->clearBericht($aBericht['bericht']) . "',
						'" . $aBericht['volgorde'] . "',
						'" . $aBericht['uid'] . "',
						'" . $aBericht['datumTijd'] . "'
					);";
				$db->query($sMoveQuery);
			}//einde foreach $aBerichten
			//cache leeggooien:
			$sClearCache = "TRUNCATE TABLE courantcache;";
			$db->query($sClearCache);
			return $iCourantID;
		} else {
			return false;
		}
	}

	private function createCourant() {
		$db = MySql::instance();

		$uid = LoginLid::instance()->getUid();
		$datumTijd = getDateTime();
		$sCreatecourantQuery = "
			INSERT INTO
				courant
			(
				verzendMoment, verzender, template
			) VALUES (
				'" . $datumTijd . "', '" . $uid . "', '" . COURANT_TEMPLATE . "'
			);";
		if ($db->query($sCreatecourantQuery)) {
			return $db->insert_id();
		} else {
			return false;
		}
	}

	################################################################
	###	Archief-methodes, heeft niets meer met de huidige instantie
	### te maken.
	################################################################

	public static function getArchiefmails() {
		$db = MySql::instance();

		$sArchiefQuery = "
			SELECT
				ID, verzendMoment, verzender, YEAR(verzendMoment) AS jaar
			FROM
				courant
			HAVING
				jaar >= YEAR(NOW())-9
			ORDER BY
				verzendMoment DESC;";
		$rArchief = $db->query($sArchiefQuery);

		if ($db->numRows($rArchief) == 0) {
			return false;
		} else {
			return $db->result2array($rArchief);
		}
	}

}

//einde classe Courant
?>
