<?php

namespace CsrDelft\service\maalcie;

use CsrDelft\common\CsrGebruikerException;
use CsrDelft\entity\maalcie\Maaltijd;
use CsrDelft\entity\maalcie\MaaltijdRepetitie;
use CsrDelft\repository\corvee\CorveeRepetitiesRepository;
use CsrDelft\repository\corvee\CorveeTakenRepository;
use CsrDelft\repository\maalcie\MaaltijdAanmeldingenRepository;
use CsrDelft\repository\maalcie\MaaltijdenRepository;
use DateInterval;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class MaaltijdRepetitiesService
{
	/**
	 * @var EntityManagerInterface
	 */
	private $entityManager;
	/**
	 * @var MaaltijdenRepository
	 */
	private $maaltijdenRepository;
	/**
	 * @var CorveeRepetitiesRepository
	 */
	private $corveeRepetitiesRepository;
	/**
	 * @var CorveeTakenRepository
	 */
	private $corveeTakenRepository;
	/**
	 * @var MaaltijdAanmeldingenRepository
	 */
	private $maaltijdAanmeldingenRepository;
	/**
	 * @var MaaltijdAbonnementenService
	 */
	private $maaltijdAbonnementenService;

	public function __construct(
		EntityManagerInterface $entityManager,
		MaaltijdenRepository $maaltijdenRepository,
		MaaltijdAanmeldingenRepository $maaltijdAanmeldingenRepository,
		MaaltijdAbonnementenService $maaltijdAbonnementenService,
		CorveeRepetitiesRepository $corveeRepetitiesRepository,
		CorveeTakenRepository $corveeTakenRepository
	) {
		$this->entityManager = $entityManager;
		$this->maaltijdenRepository = $maaltijdenRepository;
		$this->corveeRepetitiesRepository = $corveeRepetitiesRepository;
		$this->corveeTakenRepository = $corveeTakenRepository;
		$this->maaltijdAanmeldingenRepository = $maaltijdAanmeldingenRepository;
		$this->maaltijdAbonnementenService = $maaltijdAbonnementenService;
	}

	/**
	 * Maakt nieuwe maaltijden aan volgens de definitie van de maaltijd-repetitie.
	 * Alle leden met een abonnement hierop worden automatisch aangemeld.
	 *
	 * @param MaaltijdRepetitie $repetitie
	 * @param DateTimeInterface $beginDatum
	 * @param DateTimeInterface $eindDatum
	 *
	 * @return Maaltijd[]
	 */
	public function maakRepetitieMaaltijden(
		MaaltijdRepetitie $repetitie,
		DateTimeInterface $beginDatum,
		DateTimeInterface $eindDatum
	) {
		return $this->entityManager->wrapInTransaction(function () use (
			$repetitie,
			$beginDatum,
			$eindDatum
		) {
			if ($repetitie->periode_in_dagen < 1) {
				throw new CsrGebruikerException(
					'New repetitie-maaltijden faalt: $periode =' .
						$repetitie->periode_in_dagen
				);
			}

			// start at first occurence
			$shift = $repetitie->dag_vd_week - $beginDatum->format('w') + 7;
			$shift %= 7;
			if ($shift > 0) {
				$beginDatum = $beginDatum->add(
					DateInterval::createFromDateString("+{$shift} days")
				);
			}
			$datum = $beginDatum;
			$corveerepetities = $this->corveeRepetitiesRepository->getRepetitiesVoorMaaltijdRepetitie(
				$repetitie->mlt_repetitie_id
			);
			$maaltijden = [];
			while ($datum <= $eindDatum) {
				// break after one

				$maaltijd = $this->maaltijdenRepository->vanRepetitie(
					$repetitie,
					$datum
				);
				$this->entityManager->persist($maaltijd);
				$this->entityManager->flush();
				$this->maaltijdAbonnementenService->meldAboAan($maaltijd);

				foreach ($corveerepetities as $corveerepetitie) {
					// do not repeat within maaltijd period
					$this->corveeTakenRepository->newRepetitieTaken(
						$corveerepetitie,
						$datum,
						$datum,
						$maaltijd
					);
				}
				$maaltijden[] = $maaltijd;
				if ($repetitie->periode_in_dagen < 1) {
					break;
				}
				$datum = $datum->add(
					DateInterval::createFromDateString(
						'+' . $repetitie->periode_in_dagen . ' days'
					)
				);
			}
			return $maaltijden;
		});
	}

	/**
	 * @param MaaltijdRepetitie $repetitie
	 * @param $verplaats
	 * @return bool|mixed
	 */
	public function updateRepetitieMaaltijden(
		MaaltijdRepetitie $repetitie,
		$verplaats
	) {
		return $this->entityManager->wrapInTransaction(function () use (
			$repetitie,
			$verplaats
		) {
			// update day of the week & check filter
			$updated = 0;
			$aanmeldingen = 0;
			$maaltijden = $this->maaltijdenRepository->findBy([
				'verwijderd' => false,
				'mlt_repetitie_id' => $repetitie->mlt_repetitie_id,
			]);
			$filter = $repetitie->abonnement_filter;
			if (!empty($filter)) {
				$aanmeldingen = $this->maaltijdAanmeldingenRepository->checkAanmeldingenFilter(
					$filter,
					$maaltijden
				);
			}
			foreach ($maaltijden as $maaltijd) {
				if ($verplaats) {
					$shift = $repetitie->dag_vd_week - $maaltijd->datum->format('w');
					if ($shift > 0) {
						$maaltijd->datum = $maaltijd->datum->add(
							DateInterval::createFromDateString('+' . $shift . 'days')
						);
					} elseif ($shift < 0) {
						$maaltijd->datum = $maaltijd->datum->add(
							DateInterval::createFromDateString($shift . ' days')
						);
					}
				}
				$maaltijd->titel = $repetitie->standaard_titel;
				$maaltijd->aanmeld_limiet = $repetitie->standaard_limiet;
				$maaltijd->tijd = $repetitie->standaard_tijd;
				$maaltijd->product = $repetitie->product;
				$maaltijd->aanmeld_filter = $filter;
				try {
					$this->entityManager->persist($maaltijd);
					$this->entityManager->flush();
					$updated++;
				} catch (Exception $e) {
				}
			}
			return [$updated, $aanmeldingen];
		});
	}
}
