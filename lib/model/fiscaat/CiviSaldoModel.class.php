<?php

use CsrDelft\Orm\Entity\PersistentEntity;

require_once 'model/entity/fiscaat/CiviSaldo.class.php';
require_once 'model/entity/fiscaat/CiviSaldoLogEnum.class.php';
require_once 'model/fiscaat/CiviSaldoLogModel.class.php';

class CiviSaldoModel extends \CsrDelft\Orm\PersistenceModel {
	const ORM = CiviSaldo::class;

	protected static $instance;

	/**
	 * @param $uid
	 * @param int $bedrag
	 * @return int Nieuwe saldo
	 * @throws Exception
	 */
	public function ophogen($uid, $bedrag) {
		if ($bedrag < 0) {
			throw new Exception( 'Kan niet ophogen met een negatief bedrag');
		}

		/** @var CiviSaldo $saldo */
		$saldo = $this->retrieveByPrimaryKey(array($uid));

		if (!$saldo) {
			throw new Exception('Lid heeft geen CiviSaldo');
		}

		$saldo->saldo += $bedrag;
		$saldo->laatst_veranderd = date_create();
		$this->update($saldo);

		return $saldo->saldo;
	}

	/**
	 * @param $uid
	 * @param int $bedrag
	 * @return int Nieuwe saldo
	 * @throws Exception
	 */
	public function verlagen($uid, $bedrag) {
		if ($bedrag < 0) {
			throw new Exception('Kan niet verlagen met een negatief bedrag');
		}

		/** @var CiviSaldo $saldo */
		$saldo = $this->retrieveByPrimaryKey(array($uid));

		if (!$saldo) {
			throw new Exception('Lid heeft geen Civisaldo');
		}

		$saldo->saldo -= $bedrag;
		$saldo->laatst_veranderd = date_create();
		$this->update($saldo);

		return $saldo->saldo;
	}

	public function create(PersistentEntity $entity) {
		CiviSaldoLogModel::instance()->log(CiviSaldoLogEnum::INSERT, $entity);
		return parent::create($entity);
	}

	public function update(PersistentEntity $entity) {
		CiviSaldoLogModel::instance()->log(CiviSaldoLogEnum::UPDATE, $entity);
		return parent::update($entity);
	}
}