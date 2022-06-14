<?php

namespace CsrDelft\repository\groepen;

use CsrDelft\entity\groepen\Bestuur;
use CsrDelft\entity\groepen\enum\GroepStatus;
use CsrDelft\repository\GroepRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

class BesturenRepository extends GroepRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Bestuur::class);
	}

	public function nieuw($soort = null)
	{
		/** @var Bestuur $bestuur */
		$bestuur = parent::nieuw();
		$bestuur->bijbeltekst = '';
		return $bestuur;
	}

	/**
	 * Bestuur heeft de vorm:
	 *
	 * bestuur:<ht|ot|ft>:<praeses|abactis|...>
	 * bestuur:<praeses|abactis|...>
	 *
	 * @param UserInterface $user
	 * @param $familie
	 * @param $status
	 * @param $role
	 * @return bool
	 */
	public function isLid(
		UserInterface $user,
		$familie,
		$status = 'ht',
		$role = null
	) {
		if (GroepStatus::isValidValue(strtolower($familie))) {
			return parent::isLid($user, 'bestuur', $familie, $status);
		} else {
			return parent::isLid($user, 'bestuur', 'ht', $familie);
		}
	}
}
