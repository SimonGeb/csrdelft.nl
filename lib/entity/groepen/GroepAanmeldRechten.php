<?php

namespace CsrDelft\entity\groepen;

use CsrDelft\entity\groepen\interfaces\HeeftAanmeldRechten;
use CsrDelft\entity\security\enum\AccessAction;
use CsrDelft\service\security\LoginService;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * Implementeerd @see HeeftAanmeldRechten
 */
trait GroepAanmeldRechten
{
	/**
	 * Rechten benodigd voor aanmelden
	 * @var string|null
	 * @ORM\Column(type="string", nullable=true)
	 * @Serializer\Groups("datatable")
	 */
	public $rechtenAanmelden;

	public function getAanmeldRechten()
	{
		return $this->rechtenAanmelden;
	}

	public function setAanmeldRechten($rechten)
	{
		$this->rechtenAanmelden = $rechten;
	}

	/**
	 * Has permission for action?
	 *
	 * @param AccessAction $action
	 * @return boolean
	 */
	public function magAanmeldRechten($action)
	{
		$beschermdeActies = [
			AccessAction::Bekijken(),
			AccessAction::Aanmelden(),
			AccessAction::Bewerken(),
			AccessAction::Afmelden(),
		];

		if (
			in_array($action, $beschermdeActies) &&
			!LoginService::mag($this->rechtenAanmelden)
		) {
			return false;
		}

		return true;
	}
}
