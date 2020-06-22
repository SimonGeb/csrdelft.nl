<?php

namespace CsrDelft\service\corvee;

use CsrDelft\common\CsrGebruikerException;
use CsrDelft\common\Mail;
use CsrDelft\entity\corvee\CorveeTaak;
use CsrDelft\repository\corvee\CorveeTakenRepository;
use CsrDelft\repository\maalcie\MaaltijdAanmeldingenRepository;
use CsrDelft\repository\ProfielRepository;
use DateInterval;

/**
 * CorveeHerinneringenModel.class.php
 *
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 */
class CorveeHerinneringService {

	/**
	 * @var MaaltijdAanmeldingenRepository
	 */
	private $maaltijdAanmeldingenRepository;
	/**
	 * @var CorveeTakenRepository
	 */
	private $corveeTakenRepository;
	/**
	 * @var ProfielRepository
	 */
	private $profielRepository;

	public function __construct(MaaltijdAanmeldingenRepository $maaltijdAanmeldingenRepository, CorveeTakenRepository $corveeTakenRepository, ProfielRepository $profielRepository) {
		$this->maaltijdAanmeldingenRepository = $maaltijdAanmeldingenRepository;
		$this->corveeTakenRepository = $corveeTakenRepository;
		$this->profielRepository = $profielRepository;
	}

	public function stuurHerinnering(CorveeTaak $taak) {
		$datum = date_format_intl($taak->datum, DATE_FORMAT);
		if (!$taak->profiel) {
			throw new CsrGebruikerException($datum . ' ' . $taak->corveeFunctie->naam . ' niet toegewezen!');
		}
		$lidnaam = $taak->profiel->getNaam('civitas');
		$to = array($taak->profiel->getPrimaryEmail() => $lidnaam);
		$from = env('EMAIL_CC');
		$onderwerp = 'C.S.R. Delft corvee ' . $datum;
		$bericht = $taak->corveeFunctie->email_bericht;
		$eten = '';
		if ($taak->maaltijd !== null) {
			$aangemeld = $this->maaltijdAanmeldingenRepository->getIsAangemeld($taak->maaltijd->maaltijd_id, $taak->profiel->uid);
			if ($aangemeld) {
				$eten = instelling('corvee', 'mail_wel_meeeten');
			} else {
				$eten = instelling('corvee', 'mail_niet_meeeten');
			}
		}
		$mail = new Mail($to, $onderwerp, $bericht);
		$mail->setFrom($from);
		$mail->setPlaceholders(array('LIDNAAM' => $lidnaam, 'DATUM' => $datum, 'MEEETEN' => $eten));
		if ($mail->send()) { // false if failed
			if (!$mail->inDebugMode()) {
				$this->corveeTakenRepository->updateGemaild($taak);
			}
			return $datum . ' ' . $taak->corveeFunctie->naam . ' verstuurd! (' . $lidnaam . ')';
		} else {
			throw new CsrGebruikerException($datum . ' ' . $taak->corveeFunctie->naam . ' faalt! (' . $lidnaam . ')');
		}
	}

	public function stuurHerinneringen() {
		$vooraf = str_replace('-', '+', instelling('corvee', 'herinnering_1e_mail'));
		$van = date_create();
		$tot = date_create_immutable()->add(DateInterval::createFromDateString($vooraf));
		$taken = $this->corveeTakenRepository->getTakenVoorAgenda($van, $tot, true);
		$verzonden = array();
		$errors = array();
		foreach ($taken as $taak) {
			if ($taak->getMoetHerinneren()) {
				try {
					$verzonden[] = $this->stuurHerinnering($taak);
				} catch (CsrGebruikerException $e) {
					$errors[] = $e;
				}
			}
		}
		return array($verzonden, $errors);
	}

}
