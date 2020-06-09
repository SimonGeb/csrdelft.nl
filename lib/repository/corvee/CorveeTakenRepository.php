<?php

namespace CsrDelft\repository\corvee;

use CsrDelft\common\ContainerFacade;
use CsrDelft\common\CsrException;
use CsrDelft\common\CsrGebruikerException;
use CsrDelft\entity\corvee\CorveeFunctie;
use CsrDelft\entity\corvee\CorveeRepetitie;
use CsrDelft\entity\corvee\RepetitieTakenUpdateDTO;
use CsrDelft\entity\corvee\CorveeTaak;
use CsrDelft\entity\maalcie\Maaltijd;
use CsrDelft\entity\profiel\Profiel;
use CsrDelft\repository\AbstractRepository;
use CsrDelft\repository\maalcie\MaaltijdenRepository;
use CsrDelft\repository\ProfielRepository;
use CsrDelft\service\corvee\CorveePuntenService;
use CsrDelft\service\security\LoginService;
use DateInterval;
use DateTimeInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use PDOStatement;
use Throwable;

/**
 * @author P.W.G. Brussee (brussee@live.nl)
 *
 * @method CorveeTaak|null find($id, $lockMode = null, $lockVersion = null)
 * @method CorveeTaak|null findOneBy(array $criteria, array $orderBy = null)
 * @method CorveeTaak[]    findAll()
 * @method CorveeTaak[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CorveeTakenRepository extends AbstractRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, CorveeTaak::class);
	}

	/**
	 * @param CorveeTaak $taak
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function updateGemaild(CorveeTaak $taak) {
		$taak->setWanneerGemaild(date_format_intl(date_create(), DATETIME_FORMAT));
		$this->_em->persist($taak);
		$this->_em->flush();
	}

	/**
	 * @param CorveeTaak $taak
	 * @param Profiel|null $vorigProfiel
	 * @param Profiel|null $profiel
	 * @return bool
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function taakToewijzenAanLid(CorveeTaak $taak, Profiel $vorigProfiel = null, Profiel $profiel = null) {
		if ($taak->profiel && $taak->profiel->uid === $profiel) {
			return false;
		}
		$puntenruilen = false;
		if ($taak->wanneer_toegekend !== null) {
			$puntenruilen = true;
		}
		$taak->wanneer_gemaild = null;
		if ($puntenruilen && $vorigProfiel !== null) {
			$this->puntenIntrekken($taak, $vorigProfiel);
		}
		if ($puntenruilen && $profiel != null) {
			$this->puntenToekennen($taak, $profiel);
		} else {
			$this->_em->persist($taak);
			$this->_em->flush();
		}
		return true;
	}

	/**
	 * @param CorveeTaak $taak
	 * @param Profiel $profiel
	 */
	public function puntenToekennen(CorveeTaak $taak, Profiel $profiel) {
		ContainerFacade::getContainer()->get(CorveePuntenService::class)->puntenToekennen($profiel, $taak->punten, $taak->bonus_malus);
		$taak->punten_toegekend = $taak->punten_toegekend + $taak->punten;
		$taak->bonus_toegekend = $taak->bonus_toegekend + $taak->bonus_malus;
		$taak->wanneer_toegekend = date_create_immutable();
	}

	/**
	 * @param CorveeTaak $taak
	 * @param Profiel $profiel
	 */
	public function puntenIntrekken(CorveeTaak $taak, Profiel $profiel) {
		ContainerFacade::getContainer()->get(CorveePuntenService::class)->puntenIntrekken($profiel, $taak->punten, $taak->bonus_malus);
		$taak->punten_toegekend = $taak->punten_toegekend - $taak->punten;
		$taak->bonus_toegekend = $taak->bonus_toegekend - $taak->bonus_malus;
		$taak->wanneer_toegekend = null;
	}

	/**
	 * @param array $taken
	 * @return array
	 */
	public function getRoosterMatrix(array $taken) {
		$matrix = array();
		foreach ($taken as $taak) {
			$datum = strtotime($taak->datum);
			$week = date('W', $datum);
			$matrix[$week][$datum][$taak->functie_id][] = $taak;
		}
		return $matrix;
	}

	/**
	 * @return CorveeTaak[]
	 */
	public function getKomendeTaken() {
		return $this->createQueryBuilder('ct')
			->where('ct.verwijderd = false and ct.datum >= :datum')
			->setParameter('datum', date_create())
			->orderBy('ct.datum', 'ASC')
			->getQuery()->getResult();
	}

	/**
	 * @return CorveeTaak[]
	 */
	public function getVerledenTaken() {
		return $this->createQueryBuilder('ct')
			->where('ct.verwijderd = false and ct.datum < :datum')
			->setParameter('datum', date_create())
			->orderBy('ct.datum', 'ASC')
			->getQuery()->getResult();
	}

	public function getAlleTaken($groupByUid = false) {
		$taken = $this->findBy(['verwijderd' => false], ['datum' => 'ASC']);
		if ($groupByUid) {
			$takenByUid = array();
			foreach ($taken as $taak) {
				$uid = $taak->uid;
				if ($uid !== null) {
					$takenByUid[$uid][] = $taak;
				}
			}
			return $takenByUid;
		}
		return $taken;
	}

	public function getVerwijderdeTaken() {
		return $this->findBy(['verwijderd' => true], ['datum' => 'ASC']);

	}

	/**
	 * Haalt de taken op voor het ingelode lid of alle leden tussen de opgegeven data.
	 *
	 * @param DateTimeInterface $van Timestamp
	 * @param DateTimeInterface $tot Timestamp
	 * @param bool $iedereen
	 * @return CorveeTaak[]
	 * @throws CsrException
	 */
	public function getTakenVoorAgenda(DateTimeInterface $van, DateTimeInterface $tot, $iedereen = false) {
		$qb = $this->createQueryBuilder('ct');
		$qb->where('ct.verwijderd = false and ct.datum >= :van_datum and ct.datum <= :tot_datum');
		$qb->setParameter('van_datum', $van);
		$qb->setParameter('tot_datum', $tot);
		if (!$iedereen) {
			$qb->andWhere('ct.uid = :uid');
			$qb->setParameter('uid', LoginService::getUid());
		}
		return $qb->getQuery()->getResult();
	}

	/**
	 * Haalt de taken op voor een lid.
	 *
	 * @param string $uid
	 * @return PDOStatement|CorveeTaak[]
	 */
	public function getTakenVoorLid($uid) {
		return $this->findBy(['verwijderd' => false, 'uid' => $uid], ['datum' => 'ASC']);
	}

	/**
	 * Zoekt de laatste taak op van een lid.
	 *
	 * @param string $uid
	 * @return CorveeTaak
	 */
	public function getLaatsteTaakVanLid($uid) {
		return $this->findOneBy(['verwijderd' => false, 'uid' => $uid], ['datum' => 'DESC']);
	}

	/**
	 * Haalt de komende taken op waarvoor een lid is ingedeeld.
	 *
	 * @param string $uid
	 * @return CorveeTaak[]
	 */
	public function getKomendeTakenVoorLid($uid) {
		return $this->createQueryBuilder('ct')
			->where('ct.verwijderd = false and ct.uid = :uid and ct.datum >= :datum')
			->setParameter('uid', $uid)
			->setParameter('datum', date_create_immutable())
			->orderBy('ct.datum', 'ASC')
			->getQuery()->getResult();
	}

	/**
	 * @param CorveeTaak $taak
	 * @return CorveeTaak
	 * @throws Throwable
	 */
	public function saveTaak(CorveeTaak $taak) {
		return $this->_em->transactional(function () use ($taak) {
			if ($taak->taak_id === null) {
				$taak = $this->newTaak($taak);
			} else {
				$oldTaak = $this->getEntityManager()->getUnitOfWork()->getOriginalEntityData($taak);

				if ($oldTaak['functie_id'] != $taak->functie_id) {
					$taak->corveeRepetitie = null;
				}

				$this->taakToewijzenAanLid($taak, $oldTaak['profiel'], $taak->profiel);

				$this->_em->persist($taak);
				$this->_em->flush();
			}

			return $taak;
		});
	}

	/**
	 * @return int
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function prullenbakLeegmaken() {
		$taken = $this->findBy(['verwijderd' => true]);
		foreach ($taken as $taak) {
			$this->_em->remove($taak);
		}
		$this->_em->flush();
		return count($taken);
	}

	/**
	 * @return int
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function verwijderOudeTaken() {
		/** @var CorveeTaak[] $taken */
		$taken = $this->createQueryBuilder('ct')
			->where('ct.datum < :datum')
			->setParameter('datum', date_create_immutable())
			->getQuery()->getResult();
		foreach ($taken as $taak) {
			$taak->verwijderd = true;
			$this->_em->persist($taak);
		}
		$this->_em->flush();
		return count($taken);
	}

	/**
	 * @param $uid
	 * @return mixed
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function verwijderTakenVoorLid($uid) {
		/** @var CorveeTaak[] $taken */
		$taken = $this->createQueryBuilder('ct')
		->where('ct.uid = :uid and ct.datum >= :datum')
		->setParameter('uid', $uid)
			->setParameter('datum', date_create_immutable())
			->getQuery()->getResult();
		foreach ($taken as $taak) {
			$this->_em->remove($taak);
		}
		$this->_em->flush();
		return count($taken);
	}

	/**
	 * @param CorveeRepetitie $repetitie
	 * @param DateTimeInterface $datum
	 * @param Maaltijd|null $maaltijd
	 * @param string|null $uid
	 * @param int $bonus_malus
	 * @return CorveeTaak
	 */
	public function vanRepetitie(CorveeRepetitie $repetitie, $datum, $maaltijd = null, $uid = null, $bonus_malus = 0) {
		$taak = new CorveeTaak();
		$taak->taak_id = null;
		$taak->functie_id = $repetitie->corveeFunctie->functie_id;
		$taak->uid = $uid;
		$taak->profiel = ProfielRepository::get($uid);
		$taak->corveeRepetitie = $repetitie;
		$taak->maaltijd = $maaltijd;
		$taak->datum = $datum;
		$taak->bonus_malus = $bonus_malus;
		$taak->punten = $repetitie->standaard_punten;
		$taak->punten_toegekend = 0;
		$taak->bonus_toegekend = 0;
		$taak->wanneer_toegekend = null;
		$taak->wanneer_gemaild = '';
		$taak->verwijderd = false;
		return $taak;

	}

	/**
	 * @param CorveeTaak $taak
	 * @return CorveeTaak
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	private function newTaak(CorveeTaak $taak) {
		$taak->punten_toegekend = 0;
		$taak->bonus_toegekend = 0;
		$taak->wanneer_toegekend = null;
		$taak->wanneer_gemaild = '';
		$taak->verwijderd = false;
		$taak->corveeFunctie = $this->_em->getReference(CorveeFunctie::class, $taak->functie_id);

		$this->_em->persist($taak);
		$this->_em->flush();

		return $taak;
	}

	// Maaltijd-Corvee ############################################################

	/**
	 * Haalt de taken op die gekoppeld zijn aan een maaltijd.
	 * Eventueel ook alle verwijderde taken.
	 *
	 * @param int $mid
	 * @param bool $verwijderd
	 * @return PDOStatement|CorveeTaak[]
	 * @throws CsrGebruikerException
	 */
	public function getTakenVoorMaaltijd($mid, $verwijderd = false) {
		if ($mid <= 0) {
			throw new CsrGebruikerException('Load taken voor maaltijd faalt: Invalid $mid =' . $mid);
		}
		if ($verwijderd) {
			return $this->findBy(['maaltijd_id' => $mid], ['datum' => 'ASC']);
		}
		return $this->findBy(['verwijderd' => false, 'maaltijd_id' => $mid], ['datum' => 'ASC']);
	}

	/**
	 * Called when a Maaltijd is going to be deleted.
	 *
	 * @param int $mid
	 * @return bool
	 */
	public function existMaaltijdCorvee($mid) {
		return count($this->findBy(['maaltijd_id' => $mid])) > 0;
	}

	/**
	 * Called when a Maaltijd is going to be deleted.
	 *
	 * @param int $mid
	 * @return int
	 * @throws ORMException
	 */
	public function verwijderMaaltijdCorvee($mid) {
		$taken = $this->findBy(['maaltijd_id' => $mid]);
		foreach ($taken as $taak) {
			$taak->verwijderd = true;
			$this->_em->persist($taak);
		}
		$this->_em->flush();
		return count($taken);
	}

	// Functie-Taken ############################################################

	/**
	 * Called when a CorveeFunctie is going to be deleted.
	 *
	 * @param int $fid
	 * @return bool
	 */
	public function existFunctieTaken($fid) {
		return count($this->findBy(['functie_id' => $fid])) > 0;
	}

	// Repetitie-Taken ############################################################

	/**
	 * @param CorveeRepetitie $repetitie
	 * @param $beginDatum
	 * @param $eindDatum
	 * @param Maaltijd|null $maaltijd
	 * @return bool|mixed
	 * @throws Throwable
	 */
	public function maakRepetitieTaken(CorveeRepetitie $repetitie, $beginDatum, $eindDatum, $maaltijd = null) {
		if ($repetitie->periode_in_dagen < 1) {
			throw new CsrGebruikerException('New repetitie-taken faalt: $periode =' . $repetitie->periode_in_dagen);
		}

		return $this->_em->transactional(function () use ($repetitie, $beginDatum, $eindDatum, $maaltijd) {
			return $this->newRepetitieTaken($repetitie, strtotime($beginDatum), strtotime($eindDatum), $maaltijd);
		});
	}

	/**
	 * @param CorveeRepetitie $repetitie
	 * @param $beginDatum
	 * @param $eindDatum
	 * @param Maaltijd|null $maaltijd
	 * @return array
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function newRepetitieTaken(CorveeRepetitie $repetitie, $beginDatum, $eindDatum, $maaltijd = null) {
		// start at first occurence
		$shift = $repetitie->dag_vd_week - date('w', $beginDatum) + 7;
		$shift %= 7;
		if ($shift > 0) {
			$beginDatum = strtotime('+' . $shift . ' days', $beginDatum);
		}
		$datum = $beginDatum;
		$taken = array();
		while ($datum <= $eindDatum) { // break after one
			for ($i = $repetitie->standaard_aantal; $i > 0; $i--) {
				$taak = $this->vanRepetitie($repetitie, date_create_immutable("@$datum"), $maaltijd, null, 0);
				$this->_em->persist($taak);
				$taken[] = $taak;
			}
			if ($repetitie->periode_in_dagen < 1) {
				break;
			}
			$datum = strtotime('+' . $repetitie->periode_in_dagen . ' days', $datum);
		}

		$this->_em->flush();

		return $taken;
	}

	/**
	 * @param $crid
	 * @return int
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function verwijderRepetitieTaken($crid) {
		$taken = $this->findBy(['crv_repetitie_id' => $crid]);
		foreach ($taken as $taak) {
			$taak->verwijderd = true;
			$this->_em->persist($taak);
		}

		$this->_em->flush();

		return count($taken);
	}

	/**
	 * Called when a CorveeRepetitie is updated or is going to be deleted.
	 *
	 * @param int $crid
	 * @return bool
	 */
	public function existRepetitieTaken($crid) {
		return count($this->findBy(['crv_repetitie_id' => $crid])) > 0;
	}

	/**
	 * @param CorveeRepetitie $repetitie
	 * @param $verplaats
	 * @return RepetitieTakenUpdateDTO
	 * @throws Throwable
	 */
	public function updateRepetitieTaken(CorveeRepetitie $repetitie, $verplaats) {
		return $this->_em->transactional(function () use ($repetitie, $verplaats) {
			$taken = $this->findBy(['verwijderd' => false, 'crv_repetitie_id' => $repetitie->crv_repetitie_id]);

			foreach ($taken as $taak) {
				$taak->functie_id = $repetitie->corveeFunctie->functie_id;
				$taak->punten = $repetitie->standaard_punten;

				$this->_em->persist($taak);
			}

			$this->_em->flush();
			$updatecount = count($taken);

			$taken = $this->findBy(['verwijderd' => false, 'crv_repetitie_id' => $repetitie->crv_repetitie_id]);
			$takenPerDatum = array(); // taken per datum indien geen maaltijd
			$takenPerMaaltijd = array(); // taken per maaltijd
			$maaltijden = ContainerFacade::getContainer()->get(MaaltijdenRepository::class)->getKomendeRepetitieMaaltijden($repetitie->mlt_repetitie_id);
			/** @var Maaltijd[] $maaltijdenById */
			$maaltijdenById = array();
			foreach ($maaltijden as $maaltijd) {
				$takenPerMaaltijd[$maaltijd->maaltijd_id] = array();
				$maaltijdenById[$maaltijd->maaltijd_id] = $maaltijd;
			}
			// update day of the week
			$daycount = 0;
			foreach ($taken as $taak) {
				$datum = $taak->datum;
				if ($verplaats) {
					$shift = $repetitie->dag_vd_week - $datum->format('w');
					if ($shift > 0) {
						$datum = $datum->add(DateInterval::createFromDateString('+' . $shift . ' days'));
					} elseif ($shift < 0) {
						$datum = $datum->add(DateInterval::createFromDateString($shift . ' days'));
					}
					if ($shift !== 0) {
						$taak->datum = $datum;
						$this->_em->persist($taak);
						$daycount++;
					}
				}
				if ($taak->maaltijd) {
					if (array_key_exists($taak->maaltijd->maaltijd_id, $maaltijdenById)) { // do not change if not komende repetitie maaltijd
						$takenPerMaaltijd[$taak->maaltijd->maaltijd_id][] = $taak;
					}
				} else {
					$takenPerDatum[date_format_intl($datum, DATE_FORMAT)][] = $taak;
				}
			}
			// standaard aantal aanvullen
			$datumcount = 0;
			foreach ($takenPerDatum as $datum => $taken) {
				$verschil = $repetitie->standaard_aantal - sizeof($taken);
				for ($i = $verschil; $i > 0; $i--) {
					$taak = $this->vanRepetitie($repetitie, $taken[0]->datum, null, null, 0);
					$this->_em->persist($taak);
				}
				$datumcount += $verschil;
			}
			$maaltijdcount = 0;
			foreach ($takenPerMaaltijd as $mid => $taken) {
				$verschil = $repetitie->standaard_aantal - sizeof($taken);
				for ($i = $verschil; $i > 0; $i--) {
					$taak = $this->vanRepetitie($repetitie, $maaltijdenById[$mid]->datum, $maaltijdenById[$mid], null, 0);
					$this->_em->persist($taak);
				}
				$maaltijdcount += $verschil;
			}
			$this->_em->flush();
			return new RepetitieTakenUpdateDTO($updatecount, $daycount, $datumcount, $maaltijdcount);
		});
	}

}
