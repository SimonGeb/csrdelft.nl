<?php

namespace CsrDelft\service\forum;

use CsrDelft\common\Mail;
use CsrDelft\entity\forum\ForumDeel;
use CsrDelft\entity\forum\ForumDraad;
use CsrDelft\entity\forum\ForumDraadMeldingNiveau;
use CsrDelft\entity\forum\ForumPost;
use CsrDelft\entity\profiel\Profiel;
use CsrDelft\entity\security\Account;
use CsrDelft\repository\forum\ForumDelenMeldingRepository;
use CsrDelft\repository\forum\ForumDradenMeldingRepository;
use CsrDelft\repository\instellingen\LidInstellingenRepository;
use CsrDelft\repository\ProfielRepository;
use CsrDelft\repository\WebPushRepository;
use CsrDelft\service\MailService;
use CsrDelft\service\security\SuService;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ForumMeldingenService
{
	/**
	 * @var SuService
	 */
	private $suService;
	/**
	 * @var ForumDradenMeldingRepository
	 */
	private $forumDradenMeldingRepository;
	/**
	 * @var Environment
	 */
	private $twig;
	/**
	 * @var MailService
	 */
	private $mailService;
	/**
	 * @var ForumDelenMeldingRepository
	 */
	private $forumDelenMeldingRepository;
	/**
	 * @var LidInstellingenRepository
	 */
	private $lidInstellingenRepository;
	/**
	 * @var ProfielRepository
	 */
	private $profielRepository;
	/**
	 * @var Security
	 */
	private $security;
	/**
	 * @var WebPush
	 */
	private $webPush;
	/**
	 * @var WebPushRepository
	 */
	private $webPushRepository;

	/**
	 * @var String
	 */
	private $applicationServerKey = 'BK6nL-UD-kjzpFWXJ6NFkiPEzUEH4diS2BkXBr4ctRz2NU4nyUWZzxLTF2Dulf5spE4EEYVMY2jNmkXhUBTFz2k';

	public function __construct(
		Environment $twig,
		Security $security,
		MailService $mailService,
		SuService $suService,
		ProfielRepository $profielRepository,
		LidInstellingenRepository $lidInstellingenRepository,
		ForumDradenMeldingRepository $forumDradenMeldingRepository,
		ForumDelenMeldingRepository $forumDelenMeldingRepository,
		WebPushRepository $webPushRepository
	) {
		$this->suService = $suService;
		$this->forumDradenMeldingRepository = $forumDradenMeldingRepository;
		$this->twig = $twig;
		$this->mailService = $mailService;
		$this->forumDelenMeldingRepository = $forumDelenMeldingRepository;
		$this->lidInstellingenRepository = $lidInstellingenRepository;
		$this->profielRepository = $profielRepository;
		$this->webPushRepository = $webPushRepository;
		$this->security = $security;

		$auth = [
			'VAPID' => [
				'subject' => 'mailto:pubcie@csrdelft.nl',
				'publicKey' => file_get_contents(
					__DIR__ . '/../../../data/vapid_public_key.txt'
				),
				'privateKey' => file_get_contents(
					__DIR__ . '/../../../data/vapid_private_key.txt'
				),
			],
		];
		$this->webPush = new WebPush($auth);
	}

	public function stuurDraadMeldingen(ForumPost $post)
	{
		$this->stuurDraadMeldingenNaarVolgers($post);
		$this->stuurDraadMeldingenNaarGenoemden($post);
	}

	/**
	 * Stuurt meldingen van nieuw bericht naar leden met meldingsniveau op altijd
	 *
	 * @param ForumPost $post
	 */
	private function stuurDraadMeldingenNaarVolgers(ForumPost $post)
	{
		$auteur = $this->profielRepository->find($post->uid);
		// Laad meldingsbericht in
		foreach (
			$this->forumDradenMeldingRepository->getAltijdMeldingVoorDraad(
				$post->draad
			)
			as $volger
		) {
			$volgerProfiel = $this->profielRepository->find($volger->uid);

			// Stuur geen meldingen als lid niet gevonden is of lid de auteur
			if (!$volgerProfiel || $volgerProfiel->uid === $post->uid) {
				continue;
			}

			$account = $volgerProfiel->account;

			if (!$account) {
				$this->forumDradenMeldingRepository->remove($volger);
			} else {
				$this->stuurDraadMelding(
					$account,
					$auteur,
					$post,
					$post->draad,
					'mail/bericht/forumaltijdmelding.mail.twig'
				);
			}
		}

		/**
		 * Verstuur alle pushberichten in de wachtrij
		 * @var MessageSentReport $report
		 */
		foreach ($this->webPush->flush() as $report) { continue; }
	}

	/**
	 * Stuurt meldingen van nieuw bericht naar leden die genoemd / geciteerd worden in bericht
	 *
	 * @param ForumPost $post
	 */
	public function stuurDraadMeldingenNaarGenoemden(ForumPost $post)
	{
		$auteur = $this->profielRepository->find($post->uid);
		$draad = $post->draad;

		// Laad meldingsbericht in
		$genoemden = $this->zoekGenoemdeLeden($post->tekst);
		foreach ($genoemden as $uid) {
			$genoemde = $this->profielRepository->find($uid);

			// Stuur geen meldingen als lid niet gevonden is, lid de auteur is of als lid geen meldingen wil voor draadje
			// Met laatste voorwaarde worden ook leden afgevangen die sowieso al een melding zouden ontvangen
			if (
				!$genoemde ||
				!$genoemde->account ||
				$genoemde->uid === $post->uid ||
				!ForumDraadMeldingNiveau::isVERMELDING(
					$this->getDraadMeldingNiveauVoorLid($post->draad, $genoemde->uid)
				)
			) {
				continue;
			}

			$magMeldingKrijgen = $this->suService->alsLid(
				$genoemde->account,
				function () use ($draad) {
					return $draad->magMeldingKrijgen();
				}
			);

			if (!$magMeldingKrijgen) {
				continue;
			}

			$this->stuurDraadMelding(
				$genoemde->account,
				$auteur,
				$post,
				$post->draad,
				'mail/bericht/forumvermeldingmelding.mail.twig'
			);
		}

		/**
		 * Verstuur alle pushberichten in de wachtrij
		 * @var MessageSentReport $report
		 */
		foreach ($this->webPush->flush() as $report) { continue; }
	}

	/**
	 * Zoek genoemde leden in gegeven bericht
	 *
	 * @param string $bericht
	 * @return string[]
	 */
	public function zoekGenoemdeLeden($bericht)
	{
		$regex = '/\[(?:lid|citaat)=?\s*]?\s*([[:alnum:]]+)\s*[\[\]]/';
		preg_match_all($regex, $bericht, $leden);

		return array_unique($leden[1]);
	}

	public function getDraadMeldingNiveauVoorLid(ForumDraad $draad, $uid = null)
	{
		if ($uid === null && $this->security->getUser()) {
			$uid = $this->security->getUser()->getUserIdentifier();
		}

		$voorkeur = $this->forumDradenMeldingRepository->find([
			'draad_id' => $draad->draad_id,
			'uid' => $uid,
		]);
		if ($voorkeur) {
			return $voorkeur->niveau;
		} else {
			$wilMeldingBijVermelding = $this->lidInstellingenRepository->getInstellingVoorLid(
				'forum',
				'meldingStandaard',
				$uid
			);
			return $wilMeldingBijVermelding === 'ja'
				? ForumDraadMeldingNiveau::VERMELDING()
				: ForumDraadMeldingNiveau::NOOIT();
		}
	}

	/**
	 * Laad push bericht
	 * 
	 * @param Account $ontvanger
	 * @param Profiel $auteur
	 * @param ForumPost $post
	 * @param ForumDraad $draad
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	 private function laadPushBericht(
		Account $ontvanger,
		Profiel $auteur,
		ForumPost $post,
		ForumDraad $draad
	) {
		$subscription = $this->webPushRepository->findOneBy([
			'uid' => $ontvanger->getUserIdentifier(),
		]);
		$keys = json_decode($subscription->clientKeys);

		$this->webPush->queueNotification(
			Subscription::create([
				'endpoint' => $subscription->clientEndpoint,
				'publicKey' => $this->applicationServerKey,
				'keys' => [
					'p256dh' => $keys->p256dh,
					'auth' => $keys->auth,
				],
			]),
			json_encode([
				'tag' => 'csr-' . $post->post_id,
				'title' => $draad->titel,
				'body' =>
					$auteur->getNaam('civitas') .
					': ' .
					str_replace('\r\n', "\n", $post->tekst),
				'icon' => '/favicon.ico',
				'url' => $post->getLink(true),
			])
		);
	}

	/**
	 * Verzendt mail
	 *
	 * @param Account $ontvanger
	 * @param Profiel $auteur
	 * @param ForumPost $post
	 * @param ForumDraad $draad
	 * @param $template
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	private function stuurDraadMelding(
		Account $ontvanger,
		Profiel $auteur,
		ForumPost $post,
		ForumDraad $draad,
		$template
	) {
		// Stel huidig UID in op ontvanger om te voorkomen dat ontvanger privé of andere persoonlijke info te zien krijgt
		$this->suService->alsLid($ontvanger, function () use (
			$ontvanger,
			$auteur,
			$post,
			$draad,
			$template
		) {
			$wilMeldingViaEmail = $this->lidInstellingenRepository->getInstellingVoorLid(
				'forum',
				'meldingEmail',
				$ontvanger->getUserIdentifier()
			);
			if ($wilMeldingViaEmail === 'ja') {
				$bericht = $this->twig->render($template, [
					'naam' => $ontvanger->profiel->getNaam('civitas'),
					'auteur' => $auteur->getNaam('civitas'),
					'postlink' => $post->getLink(true),
					'titel' => $draad->titel,
					'tekst' => str_replace('\r\n', "\n", $post->tekst),
				]);

				$mail = new Mail(
					$ontvanger->profiel->getEmailOntvanger(),
					'C.S.R. Forum: nieuwe reactie op ' . $draad->titel,
					$bericht
				);
				$this->mailService->send($mail);
			}

			$wilMeldingViaPush = $this->lidInstellingenRepository->getInstellingVoorLid(
				'forum',
				'meldingPush',
				$ontvanger->getUserIdentifier()
			);
			if ($wilMeldingViaPush === 'ja') {
				$this->laadPushBericht($ontvanger, $auteur, $post, $draad);
			}
		});
	}

	/**
	 * Stuur alle meldingen rondom forumdelen.
	 * @param ForumPost $post
	 */
	public function stuurDeelMeldingen(ForumPost $post)
	{
		$this->stuurDeelMeldingenNaarVolgers($post);
	}

	/**
	 * Verzendt mail
	 *
	 * @param Account $ontvanger
	 * @param Profiel $auteur
	 * @param ForumPost $post
	 * @param ForumDraad $draad
	 * @param ForumDeel $deel
	 */
	private function stuurDeelMelding(
		Account $ontvanger,
		Profiel $auteur,
		ForumPost $post,
		ForumDraad $draad,
		ForumDeel $deel
	) {
		// Stel huidig UID in op ontvanger om te voorkomen dat ontvanger privé of andere persoonlijke info te zien krijgt
		$this->suService->alsLid($ontvanger, function () use (
			$draad,
			$deel,
			$ontvanger,
			$auteur,
			$post
		) {
			if (!$draad->magMeldingKrijgen()) { return; }

			$wilMeldingViaEmail = $this->lidInstellingenRepository->getInstellingVoorLid(
				'forum',
				'meldingEmail',
				$ontvanger->getUserIdentifier()
			);
			if ($wilMeldingViaEmail === 'ja') {
				$bericht = $this->twig->render(
					'mail/bericht/forumdeelmelding.mail.twig',
					[
						'naam' => $ontvanger->profiel->getNaam('civitas'),
						'auteur' => $auteur->getNaam('civitas'),
						'postlink' => $post->getLink(true),
						'titel' => $draad->titel,
						'forumdeel' => $deel->titel,
						'tekst' => str_replace('\r\n', "\n", $post->tekst),
					]
				);

				$mail = new Mail(
					$ontvanger->profiel->getEmailOntvanger(),
					'C.S.R. Forum: nieuw draadje in ' .
						$deel->titel .
						': ' .
						$draad->titel,
					$bericht
				);
				$this->mailService->send($mail);
			}

			$wilMeldingViaPush = $this->lidInstellingenRepository->getInstellingVoorLid(
				'forum',
				'meldingPush',
				$ontvanger->getUserIdentifier()
			);
			if ($wilMeldingViaPush === 'ja') {
				$this->laadPushBericht($ontvanger, $auteur, $post, $draad);
			}
		});
	}

	/**
	 * Stuurt meldingen van nieuw bericht naar leden die forumdeel volgen.
	 *
	 * @param ForumPost $post
	 */
	private function stuurDeelMeldingenNaarVolgers(ForumPost $post)
	{
		$auteur = ProfielRepository::get($post->uid);
		$draad = $post->draad;
		$deel = $draad->deel;

		foreach ($deel->meldingen as $volger) {
			$volgerProfiel = ProfielRepository::get($volger->uid);

			// Stuur geen meldingen als lid niet gevonden is of lid de auteur
			if (!$volgerProfiel || $volgerProfiel->uid === $post->uid) {
				continue;
			}

			$account = $volgerProfiel->account;

			// Als dit lid geen account meer heeft, volgt dit lid niet meer deze post
			if (!$account) {
				$this->forumDelenMeldingRepository->remove($volger);
			} else {
				$this->stuurDeelMelding($account, $auteur, $post, $draad, $deel);
			}
		}

		/**
		 * Verstuur alle pushberichten in de wachtrij
		 * @var MessageSentReport $report
		 */
		foreach ($this->webPush->flush() as $report) { continue; }
	}
}
