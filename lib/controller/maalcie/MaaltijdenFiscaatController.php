<?php

namespace CsrDelft\controller\maalcie;

use CsrDelft\common\CsrGebruikerException;
use CsrDelft\entity\fiscaat\CiviBestelling;
use CsrDelft\entity\maalcie\Maaltijd;
use CsrDelft\repository\fiscaat\CiviBestellingRepository;
use CsrDelft\repository\fiscaat\CiviSaldoRepository;
use CsrDelft\repository\maalcie\MaaltijdAanmeldingenRepository;
use CsrDelft\repository\maalcie\MaaltijdenRepository;
use CsrDelft\view\datatable\RemoveRowsResponse;
use CsrDelft\view\maalcie\beheer\FiscaatMaaltijdenOverzichtResponse;
use CsrDelft\view\maalcie\beheer\FiscaatMaaltijdenOverzichtTable;
use CsrDelft\view\maalcie\beheer\OnverwerkteMaaltijdenTable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * MaaltijdenFiscaatController.class.php
 *
 * @author P.W.G. Brussee <brussee@live.nl>
 */
class MaaltijdenFiscaatController {
	/**
	 * @var MaaltijdenRepository
	 */
	private $maaltijdenRepository;
	/**
	 * @var MaaltijdAanmeldingenRepository
	 */
	private $maaltijdAanmeldingenRepository;
	/**
	 * @var CiviBestellingRepository
	 */
	private $civiBestellingRepository;
	/**
	 * @var CiviSaldoRepository
	 */
	private $civiSaldoRepository;

	public function __construct(
		MaaltijdenRepository $maaltijdenRepository,
		MaaltijdAanmeldingenRepository $maaltijdAanmeldingenRepository,
		CiviBestellingRepository $civiBestellingRepository,
		CiviSaldoRepository $civiSaldoRepository
	) {
		$this->maaltijdenRepository = $maaltijdenRepository;
		$this->maaltijdAanmeldingenRepository = $maaltijdAanmeldingenRepository;
		$this->civiBestellingRepository = $civiBestellingRepository;
		$this->civiSaldoRepository = $civiSaldoRepository;
	}

	public function GET_overzicht() {
		return view('maaltijden.pagina', [
			'titel' => 'Overzicht verwerkte maaltijden',
			'content' => new FiscaatMaaltijdenOverzichtTable(),
		]);
	}

	public function POST_overzicht() {
		$data = $this->maaltijdenRepository->findBy(['verwerkt' => true]);
		return new FiscaatMaaltijdenOverzichtResponse($data);
	}

	public function GET_onverwerkt() {
		return view('maaltijden.pagina', [
			'titel' => 'Onverwerkte Maaltijden',
			'content' => new OnverwerkteMaaltijdenTable(),
		]);
	}

	public function POST_verwerk(EntityManagerInterface $em) {
		# Haal maaltijd op
		$selection = filter_input(INPUT_POST, 'DataTableSelection', FILTER_SANITIZE_STRING, FILTER_FORCE_ARRAY);
		/** @var Maaltijd $maaltijd */
		$maaltijd = $this->maaltijdenRepository->retrieveByUUID($selection[0]);

		# Controleer of de maaltijd gesloten is en geweest is
		if ($maaltijd->gesloten == false OR $maaltijd->getMoment() >= date_create_immutable("now")) {
			throw new CsrGebruikerException("Maaltijd nog niet geweest");
		}

		# Controleer of maaltijd niet al verwerkt is
		if ($maaltijd->verwerkt) {
			throw new CsrGebruikerException("Maaltijd is al verwerkt");
		}

		$maaltijden = $em->transactional(function () use ($maaltijd) {
			# Ga alle personen in de maaltijd af
			$aanmeldingen = $this->maaltijdAanmeldingenRepository->findBy(['maaltijd_id' => $maaltijd->maaltijd_id]);

			/** @var Civibestelling[] $bestellingen */
			$bestellingen = array();
			# Maak een bestelling voor deze persoon
			foreach ($aanmeldingen as $aanmelding) {
				$bestellingen[] = $this->maaltijdAanmeldingenRepository->maakCiviBestelling($aanmelding);
			}

			# Reken de bestelling af
			foreach ($bestellingen as $bestelling) {
				$this->civiBestellingRepository->create($bestelling);
				$this->civiSaldoRepository->verlagen($bestelling->uid, $bestelling->totaal);
			}

			# Zet de maaltijd op verwerkt
			$maaltijd->verwerkt = true;

			$this->maaltijdenRepository->update($maaltijd);

			return [$maaltijd];
		});

		return new RemoveRowsResponse($maaltijden);
	}

}
