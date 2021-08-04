<?php


namespace CsrDelft\common\Security;


use CsrDelft\common\CsrException;
use Trikoder\Bundle\OAuth2Bundle\Model\Scope;

class OAuth2Scope
{
	const PROFIEL_EMAIL = "PROFIEL:EMAIL";
	const BAR_NORMAAL = "BAR:NORMAAL";
	const BAR_BEHEER = "BAR:BEHEER";
	const BAR_TRUST = "BAR:TRUST";

	const BESCHRIJVING = [
		self::PROFIEL_EMAIL => 'Lezen van primair emailadres',
		self::BAR_NORMAAL => 'Het bar systeem gebruiken om drankjes te strepen.',
		self::BAR_BEHEER => 'Het bar systeem gebruiken om in te leggen en bijnamen aan te passen.',
		self::BAR_TRUST => 'Een bar systeem installeren.',
	];

	const MAG = [
		self::PROFIEL_EMAIL => P_LOGGED_IN,
		self::BAR_NORMAAL => 'P_ADMIN',
		self::BAR_BEHEER => 'P_ADMIN',
		self::BAR_TRUST => 'P_ADMIN',
	];

	// Optionele scopes
	const OPTIONAL = [
		self::BAR_BEHEER => true,
		self::BAR_TRUST => true,
	];

	/**
	 * @param Scope|string $scope
	 * @return mixed
	 */
	public static function magScope($scope)
	{
		if (isset(self::MAG[(string)$scope])) {
			return self::MAG[(string)$scope];
		}

		throw new CsrException("Scope $scope heeft geen rechten gedefinieerd");
	}

	public static function isOptioneel($scope) {
		if (isset(self::OPTIONAL[(string)$scope])) {
			return true;
		}

		return false;
	}

	/**
	 * @param Scope|string $scope
	 * @return string
	 */
	public static function getBeschrijving($scope)
	{
		if (isset(self::BESCHRIJVING[(string)$scope])) {
			return self::BESCHRIJVING[(string)$scope];
		}

		throw new CsrException("Scope $scope heeft geen beschrijving");
	}
}
