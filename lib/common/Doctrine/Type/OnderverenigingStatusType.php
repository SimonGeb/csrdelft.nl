<?php


namespace CsrDelft\common\Doctrine\Type;


use CsrDelft\entity\groepen\enum\OnderverenigingStatus;

class OnderverenigingStatusType extends EnumType
{
	public function getEnumClass() {
		return OnderverenigingStatus::class;
	}

	public function getName() {
		return 'enumOnderverenigingStatus';
	}
}
