<?php


namespace CsrDelft\common\Doctrine\Type;


use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class LongTextType extends Type {
	/**
	 * @inheritDoc
	 */
	public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform) {
		return 'LONGTEXT';
	}

	/**
	 * @inheritDoc
	 */
	public function getName() {
		return 'longtext';
	}
}
