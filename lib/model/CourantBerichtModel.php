<?php


namespace CsrDelft\model;


use CsrDelft\model\entity\courant\CourantBericht;
use CsrDelft\model\security\LoginModel;
use CsrDelft\Orm\PersistenceModel;
use PDOStatement;

class CourantBerichtModel extends PersistenceModel {
	const ORM = CourantBericht::class;

	/**
	 * @return PDOStatement|CourantBericht[]
	 */
	public function getNieuweBerichten() {
		return $this->find('courantID IS NULL', [], null, 'volgorde ASC');
	}

	/**
	 * @param $id
	 * @return CourantBericht|false
	 */
	public function get($id) {
		return $this->retrieveByPrimaryKey([$id]);
	}

	public function getBerichtenVoorGebruiker() {
		//mods en bestuur zien alle berichten
		if ($this->magBeheren() || LoginModel::mag('bestuur')) {
			return $this->getNieuweBerichten();
		} else {
			return $this->find('courantID IS NULL AND uid = ?', [LoginModel::getUid()], null, 'volgorde ASC');
		}
	}

	public function magBeheren($uid = null) {
		return LoginModel::mag(P_MAIL_COMPOSE) || LoginModel::mag($uid);
	}
}
