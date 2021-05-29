<?php

namespace CsrDelft\repository\civimelder;

use CsrDelft\common\CsrGebruikerException;
use CsrDelft\entity\civimelder\Activiteit;
use CsrDelft\entity\civimelder\Deelnemer;
use CsrDelft\entity\profiel\Profiel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Deelnemer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Deelnemer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Deelnemer[]    findAll()
 * @method Deelnemer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeelnemerRepository extends ServiceEntityRepository {
	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, Deelnemer::class);
	}

	public function getAantalAanmeldingen(Activiteit $activiteit): int {
		$q = $this->createQueryBuilder('a')
			->select('SUM(a.aantal)')
			->where('a.activiteit = :activiteit')
			->setParameter('activiteit', $activiteit)
			->getQuery();

		try {
			return $q->getSingleScalarResult() ?? 0;
		} catch (NoResultException $e) {
			return 0;
		} catch (NonUniqueResultException $e) {
			return 0;
		}
	}

	public function isAangemeld(Activiteit $activiteit, Profiel $profiel): bool {
		return $this->getDeelnemer($activiteit, $profiel) !== null;
	}

	public function getAantalGasten(Activiteit $activiteit, Profiel $profiel): int {
		if (!$this->isAangemeld($activiteit, $profiel)) return 0;
		return $this->getDeelnemer($activiteit, $profiel)->getAantal() - 1;
	}

	public function getDeelnemer(Activiteit $activiteit, Profiel $profiel): ?Deelnemer {
		return $this->findOneBy(['activiteit' => $activiteit, 'lid' => $profiel]);
	}

	/**
	 * @param Activiteit $activiteit
	 * @param Profiel $lid
	 * @param int $aantal
	 * @return Deelnemer
	 * @throws ORMException
	 */
	public function aanmelden(Activiteit $activiteit, Profiel $lid, int $aantal): Deelnemer {
		$reden = '';
		if (!$activiteit->magAanmelden($aantal, $reden)) {
			throw new CsrGebruikerException("Aanmelden mislukt: {$reden}.");
		} elseif ($this->isAangemeld($activiteit, $lid)) {
			throw new CsrGebruikerException("Aanmelden mislukt: al aangemeld.");
		} elseif ($aantal < 1) {
			throw new CsrGebruikerException("Aanmelden mislukt: aantal moet minimaal 1 zijn.");
		} elseif ($aantal > $activiteit->getMaxAantal() && !$activiteit->magLijstBeheren()) {
			throw new CsrGebruikerException("Aanmelden mislukt: niet meer dan {$activiteit->getMaxGasten()} gasten.");
		}

		$deelnemer = new Deelnemer($activiteit, $lid, $aantal);

		$this->getEntityManager()->persist($deelnemer);
		$this->getEntityManager()->flush();
		return $deelnemer;
	}

	/**
	 * @param Activiteit $activiteit
	 * @param Profiel $lid
	 * @throws ORMException
	 */
	public function afmelden(Activiteit $activiteit, Profiel $lid): void {
		$reden = '';
		if (!$this->isAangemeld($activiteit, $lid)) {
			throw new CsrGebruikerException("Afmelden mislukt: niet aangemeld.");
		} elseif (!$activiteit->magAfmelden($reden)) {
			throw new CsrGebruikerException("Afmelden mislukt: {$reden}.");
		}

		$deelnemer = $this->getDeelnemer($activiteit, $lid);
		$this->getEntityManager()->remove($deelnemer);
		$this->getEntityManager()->flush();
	}

	/**
	 * @param Activiteit $activiteit
	 * @param Profiel $lid
	 * @param int $aantal
	 * @throws ORMException
	 */
	public function aantalAanpassen(Activiteit $activiteit, Profiel $lid, int $aantal): void {
		if (!$this->isAangemeld($activiteit, $lid)) {
			throw new CsrGebruikerException("Gasten aanpassen mislukt: niet aangemeld.");
		} elseif ($aantal < 1) {
			throw new CsrGebruikerException("Aanmelden mislukt: aantal moet minimaal 1 zijn.");
		} elseif ($aantal > $activiteit->getMaxAantal() && !$activiteit->magLijstBeheren()) {
			throw new CsrGebruikerException("Aanmelden mislukt: niet meer dan {$activiteit->getMaxGasten()} gasten.");
		}

		$deelnemer = $this->getDeelnemer($activiteit, $lid);
		$reden = '';
		if ($deelnemer->getAantal() > $aantal) {
			$extra = $aantal - $deelnemer->getAantal();
			if (!$activiteit->magAanmelden($extra, $reden)) {
				throw new CsrGebruikerException("Gasten aanpassen mislukt: {$reden}.");
			}
		} elseif ($deelnemer->getAantal() < $aantal) {
			if (!$activiteit->magAfmelden($reden)) {
				throw new CsrGebruikerException("Gasten aanpassen mislukt: {$reden}.");
			}
		} else {
			return;
		}

		$deelnemer->setAantal($aantal);
		$this->getEntityManager()->flush();
	}
}
