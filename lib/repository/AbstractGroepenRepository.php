<?php

namespace CsrDelft\repository;

use CsrDelft\common\ContainerFacade;
use CsrDelft\entity\groepen\AbstractGroep;
use CsrDelft\entity\groepen\GroepStatus;
use CsrDelft\model\security\AccessModel;
use CsrDelft\model\security\LoginModel;
use CsrDelft\Orm\Entity\PersistentEntity;
use CsrDelft\Orm\Persistence\Database;
use Doctrine\Persistence\ManagerRegistry;
use PDO;
use ReflectionClass;
use ReflectionProperty;

/**
 * AbstractGroepenModel.class.php
 *
 * @author P.W.G. Brussee <brussee@live.nl>
 * @method AbstractGroep|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractGroep|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractGroep[]    findAll()
 * @method AbstractGroep[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
abstract class AbstractGroepenRepository extends AbstractRepository {
	/**
	 * Default ORDER BY
	 * @var string
	 */
	protected $default_order = 'begin_moment DESC';
	/**
	 * @var AccessModel
	 */
	private $accessModel;
	private $entityClass;
	/**
	 * @var Database
	 */
	private $database;

	/**
	 * AbstractGroepenModel constructor.
	 * @param AccessModel $accessModel
	 * @param ManagerRegistry $managerRegistry
	 * @param $entityClass
	 */
	public function __construct(AccessModel $accessModel, ManagerRegistry $managerRegistry, $entityClass) {
		parent::__construct($managerRegistry, $entityClass);

		$this->accessModel = $accessModel;
		$this->entityClass = $entityClass;

		$this->database = ContainerFacade::getContainer()->get(Database::class);
	}

	public static function getUrl() {
		return '/groepen/' . static::getNaam();
	}

	public static function getNaam() {
		return strtolower(str_replace('Repository', '', classNameZonderNamespace(get_called_class())));
	}

	/**
	 * @param $id
	 * @return AbstractGroep|false
	 */
	public function get($id) {
		if (is_numeric($id)) {
			return $this->find($id);
		}
		return $this->findOneBy(['familie' => $id, 'status' => GroepStatus::HT()]);
	}

	/**
	 * Set primary key.
	 *
	 * @param PersistentEntity|AbstractGroep $groep
	 * @return void
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function create(PersistentEntity $groep) {
		$this->_em->persist($groep);
		$this->_em->flush();
	}

	/**
	 * Converteer groep inclusief leden van klasse.
	 *
	 * @param AbstractGroep $oldgroep
	 * @param AbstractGroepenRepository $oldmodel
	 * @param string $soort
	 * @return AbstractGroep|bool
	 */
	public function converteer(AbstractGroep $oldgroep, AbstractGroepenRepository $oldmodel, $soort = null) {
		try {
			return $this->_em->transactional(function () use ($oldgroep, $oldmodel, $soort) {
				// groep converteren
				$newgroep = $this->nieuw($soort);
				$rc = new ReflectionClass($newgroep);
				foreach ($rc->getProperties(ReflectionProperty::IS_PUBLIC) as $attribute => $value) {
					if (property_exists($newgroep, $value->getName())) {
						$newgroep->{$value->getName()} = $oldgroep->{$value->getName()};
					}
				}
				$newgroep->id = null;
				$this->_em->persist($newgroep);

				// leden converteren
				$ledenmodel = $newgroep::getLedenModel();
				foreach ($oldgroep->getLeden() as $oldlid) {
					$newlid = $ledenmodel->nieuw($newgroep, $oldlid->uid);
					$oldlidRc = new ReflectionClass($oldlid);
					foreach ($oldlidRc->getProperties(ReflectionProperty::IS_PUBLIC) as $attribute => $value) {
						if (property_exists($newlid, $value->getName())) {
							$newlid->{$value->getName()} = $oldgroep->{$value->getName()};
						}
					}
					$newlid->groep_id = $newgroep->id;
					$this->_em->persist($newlid);
				}

				// leden verwijderen
				foreach ($oldgroep->getLeden() as $oldlid) {
					$this->_em->remove($oldlid);
				}

				// groep verwijderen
				$this->_em->remove($oldgroep);
				$this->_em->flush();

				return $newgroep;
			});
		} catch (\Throwable $ex) {
			setMelding($ex->getMessage(), -1);
			return false;
		}
	}

	/**
	 * @param null $soort
	 * @return AbstractGroep
	 */
	public function nieuw(/* @noinspection PhpUnusedParameterInspection */ $soort = null) {
		$orm = $this->entityClass;
		$groep = new $orm();
		$groep->naam = null;
		$groep->familie = null;
		$groep->status = GroepStatus::HT;
		$groep->samenvatting = '';
		$groep->omschrijving = null;
		$groep->begin_moment = null;
		$groep->eind_moment = null;
		$groep->website = null;
		$groep->maker_uid = LoginModel::getUid();
		return $groep;
	}

	/**
	 * Return groepen by GroepStatus voor lid.
	 *
	 * @param string $uid
	 * @param GroepStatus|array $status
	 * @return AbstractGroep[]
	 */
	public function getGroepenVoorLid($uid, $status = null) {

		$em = ContainerFacade::getContainer()->get('doctrine.orm.entity_manager');
		/** @var AbstractGroepLedenRepository $ledenModel */
		$ledenModel = ContainerFacade::getContainer()->get($this->entityClass::LEDEN);

		$ids = $this->database->sqlSelect(['DISTINCT groep_id'], $em->getClassMetadata($ledenModel->entityClass)->getTableName(), 'uid = ?', [$uid])->fetchAll(PDO::FETCH_COLUMN);
		if (empty($ids)) {
			return [];
		}

		$qb = $this->createQueryBuilder('ag')
			->where('ag.id in (:ids)')
			->setParameter('ids', $ids);

		if (is_array($status)) {
			$qb->andWhere('ag.status in (:status)')
				->setParameter('status', $status);
		} elseif ($status) {
			$qb->andWhere('ag.status = :status')
				->setParameter('status', $status);
		}

		return $qb->getQuery()->getResult();
	}

}
