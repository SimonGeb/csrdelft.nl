<?php

require_once 'model/maalcie/CorveeVrijstellingenModel.class.php';
require_once 'model/maalcie/CorveeTakenModel.class.php';

/**
 * CorveePuntenModel.class.php	| 	P.W.G. Brussee (brussee@live.nl)
 * 
 */
class CorveePuntenModel {

	public static function resetCorveejaar() {
		$aantal = 0;
		$errors = array();
		$vrijstellingen = CorveeVrijstellingenModel::getAlleVrijstellingen(true); // grouped by uid
		$matrix = self::loadPuntenTotaalVoorAlleLeden();
		foreach ($matrix as $uid => $totalen) {
			try {
				$profiel = ProfielModel::get($uid); // false if lid does not exist
				if (!$profiel) {
					throw new Exception('Lid bestaat niet: $uid =' . $uid);
				}
				$punten = $totalen['puntenTotaal'];
				$punten += $totalen['bonusTotaal'];
				$vrijstelling = null;
				if (array_key_exists($uid, $vrijstellingen) && time() > strtotime($vrijstellingen[$uid]->getBeginDatum())) {
					$vrijstelling = $vrijstellingen[$uid];
					$punten += $vrijstelling->getPunten();
					if (time() > strtotime($vrijstelling->getEindDatum())) {
						CorveeVrijstellingenModel::verwijderVrijstelling($vrijstelling->getUid());
						$aantal++;
					} else { // niet dubbel toekennen
						$vrijstelling->setPercentage(0);
						CorveeVrijstellingenModel::saveVrijstelling($vrijstelling->getUid(), $vrijstelling->getBeginDatum(), $vrijstelling->getEindDatum(), $vrijstelling->getPercentage());
					}
				}
				$punten -= intval(Instellingen::get('corvee', 'punten_per_jaar'));
				self::savePuntenVoorLid($profiel, $punten, 0);
			} catch (\Exception $e) {
				$errors[] = $e;
			}
		}
		$taken = CorveeTakenModel::verwijderOudeTaken();
		return array($aantal, $taken, $errors);
	}

	public static function puntenToekennen($uid, $punten, $bonus_malus) {
		if (!is_int($punten) || !is_int($bonus_malus)) {
			throw new Exception('Punten toekennen faalt: geen integer');
		}
		$profiel = ProfielModel::get($uid); // false if lid does not exist
		if (!$profiel) {
			throw new Exception('Lid bestaat niet: $uid =' . $uid);
		}
		if ($punten !== 0 OR $bonus_malus !== 0) {
			self::savePuntenVoorLid($profiel, (int) $profiel->corvee_punten + $punten, (int) $profiel->corvee_punten_bonus + $bonus_malus);
		}
	}

    public static function puntenIntrekken($uid, $punten, $bonus_malus) {
        if (!is_int($punten) || !is_int($bonus_malus)) {
            throw new Exception('Punten intrekken faalt: geen integer');
        }
        self::puntenToekennen($uid, -$punten, -$bonus_malus);
    }

	public static function savePuntenVoorLid(Profiel $profiel, $punten = null, $bonus_malus = null) {
		if (!is_int($punten) && !is_int($bonus_malus)) {
			throw new Exception('Save punten voor lid faalt: geen integer');
		}
		if (is_int($punten)) {
			$profiel->corvee_punten = $punten;
		}
		if (is_int($bonus_malus)) {
			$profiel->corvee_punten_bonus = $bonus_malus;
		}
		if (ProfielModel::instance()->update($profiel) !== 1) {
			throw new Exception('Save punten voor lid faalt: opslaan mislukt');
		}
	}

	public static function loadPuntenTotaalVoorAlleLeden() {
		return self::loadPuntenTotaal('status IN("S_LID", "S_GASTLID", "S_NOVIET")');
	}

	private static function loadPuntenTotaal($where = null, $values = array(), $limit = null) {
		$sql = 'SELECT uid, corvee_punten, corvee_punten_bonus';
		$sql.= ' FROM profielen';
		if ($where !== null) {
			$sql.= ' WHERE ' . $where;
		}
		$sql.= ' ORDER BY achternaam, voornaam ASC';
		if (is_int($limit) && $limit > 0) {
			$sql.= ' LIMIT ' . $limit;
		}
		$db = \Database::instance();
		$query = $db->prepare($sql);
		$query->execute($values);
		$result = $query->fetchAll();
		$totalen = array();
		foreach ($result as $row) {
			$totalen[$row['uid']] = array(
				'puntenTotaal'	 => (int) $row['corvee_punten'],
				'bonusTotaal'	 => (int) $row['corvee_punten_bonus']
			);
		}
		return $totalen;
	}

	public static function loadPuntenVoorAlleLeden($functies = null) {
		$taken = CorveeTakenModel::getAlleTaken(true); // grouped by uid
		$vrijstellingen = CorveeVrijstellingenModel::getAlleVrijstellingen(true); // grouped by uid
		$matrix = self::loadPuntenTotaalVoorAlleLeden();
		foreach ($matrix as $uid => $totalen) {
			$profiel = ProfielModel::get($uid); // false if lid does not exist
			if (!$profiel) {
				throw new Exception('Lid bestaat niet: $uid =' . $uid);
			}
			$lidtaken = array();
			if (array_key_exists($uid, $taken)) {
				$lidtaken = $taken[$uid];
			}
			$vrijstelling = null;
			if (array_key_exists($uid, $vrijstellingen)) {
				$vrijstelling = $vrijstellingen[$uid];
			}
			$matrix[$uid] = self::loadPuntenVoorLid($profiel, $functies, $lidtaken, $vrijstelling);
		}
		return $matrix;
	}

	public static function loadPuntenVoorLid(Profiel $profiel, $functies = null, $lidtaken = null, $vrijstelling = null) {
		if ($lidtaken === null) {
			$lidtaken = CorveeTakenModel::getTakenVoorLid($profiel->uid);
			$vrijstelling = CorveeVrijstellingenModel::getVrijstelling($profiel->uid);
		}
		if ($functies === null) { // niet per functie sommeren
			$lijst = array();
			$lijst['prognose'] = 0;
			foreach ($lidtaken as $taak) {
				$lijst['prognose'] += $taak->getPuntenPrognose();
			}
		} else {
			$lijst = self::sumPuntenPerFunctie($functies, $lidtaken);
		}
		if ($vrijstelling === null) {
			$lijst['vrijstelling'] = false;
		} else { // bij suggestielijst wordt de prognose gecorrigeerd voor beginDatum van vrijstelling
			$lijst['vrijstelling'] = $vrijstelling;
			$lijst['prognose'] += $vrijstelling->getPunten();
		}

		$lijst['lid'] = $profiel;
		$lijst['puntenTotaal'] = (int) $profiel->corvee_punten;
		$lijst['bonusTotaal'] = (int) $profiel->corvee_punten_bonus;
		$lijst['prognose'] += $lijst['puntenTotaal'] + $lijst['bonusTotaal'];
		$lijst['prognoseColor'] = self::rgbCalculate($lijst['prognose']);
		if ($profiel->isLid()) {
			$lijst['tekort'] = Instellingen::get('corvee', 'punten_per_jaar') - $lijst['prognose'];
		} else {
			$lijst['tekort'] = 0 - $lijst['prognose'];
		}
		if ($lijst['tekort'] < 0) {
			$lijst['tekort'] = 0;
		}
		$lijst['tekortColor'] = self::rgbCalculate($lijst['tekort'], true);
		return $lijst;
	}

	private static function sumPuntenPerFunctie($functies, $taken) {
		$sumAantal = array();
		$sumPunten = array();
		$sumBonus = array();
		$sumPrognose = 0;
		foreach ($functies as $fid => $functie) {
			$sumAantal[$fid] = 0;
			$sumPunten[$fid] = 0;
			$sumBonus[$fid] = 0;
		}
		foreach ($taken as $taak) {
			$fid = $taak->getFunctieId();
			if (array_key_exists($fid, $functies)) {
				$sumAantal[$fid] += 1;
				$sumPunten[$fid] += $taak->getPuntenToegekend();
				$sumBonus[$fid] += $taak->getBonusToegekend();
			}
			$sumPrognose += $taak->getPuntenPrognose();
		}
		return array('aantal' => $sumAantal, 'punten' => $sumPunten, 'bonus' => $sumBonus, 'prognose' => $sumPrognose, 'prognoseColor' => self::rgbCalculate($sumPrognose));
	}

	/**
	 * RGB kleurovergang berekenen
	 */
	private static function rgbCalculate($punten, $tekort = false) {
		$perjaar = intval(Instellingen::get('corvee', 'punten_per_jaar'));
		if (!$tekort) {
			$punten = $perjaar - $punten;
		}
		$verhouding = $punten / $perjaar;

		$r = 2 * $verhouding;
		$g = 2 * (1 - $verhouding);

		if ($r < 0)
			$r = 0;
		if ($r > 1)
			$r = 1;
		if ($g < 0)
			$g = 0;
		if ($g > 1)
			$g = 1;

		return dechex(8 + round($r * 6)) . dechex(8 + round($g * 6)) . dechex(8);
	}

}

?>