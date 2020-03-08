<?php

namespace CsrDelft\repository\forum;

use CsrDelft\entity\forum\ForumDraadGelezen;
use CsrDelft\model\entity\forum\ForumDraad;
use CsrDelft\model\security\LoginModel;
use CsrDelft\repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ForumDradenGelezenModel.class.php
 *
 * @author P.W.G. Brussee <brussee@live.nl>
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @date 30/03/2017
 * @method ForumDraadGelezen|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForumDraadGelezen|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForumDraadGelezen[]    findAll()
 * @method ForumDraadGelezen[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForumDradenGelezenRepository extends AbstractRepository {
	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, ForumDraadGelezen::class);
	}

	protected function maakForumDraadGelezen($draad_id) {
		$gelezen = new ForumDraadGelezen();
		$gelezen->draad_id = $draad_id;
		$gelezen->uid = LoginModel::getUid();
		$gelezen->datum_tijd = date_create();
		$this->getEntityManager()->persist($gelezen);
		$this->getEntityManager()->flush();
		return $gelezen;
	}

	/**
	 * @param ForumDraad $draad
	 * @return ForumDraadGelezen|null
	 */
	public function getWanneerGelezenDoorLid(ForumDraad $draad) {
		return $this->find(['draad_id' => $draad->draad_id, 'uid'=>LoginModel::getUid()]);
	}

	/**
	 * Ga na welke posts op de huidige pagina het laatst is geplaatst of gewijzigd.
	 *
	 * @param ForumDraad $draad
	 * @param int $timestamp
	 * @return int number of rows affected
	 */
	public function setWanneerGelezenDoorLid(ForumDraad $draad, $timestamp = null) {
		$gelezen = $this->getWanneerGelezenDoorLid($draad);
		if (!$gelezen) {
			$gelezen = $this->maakForumDraadGelezen($draad->draad_id);
		}
		if (is_int($timestamp)) {
			$gelezen->datum_tijd = date_create($timestamp);
		} else {
			foreach ($draad->getForumPosts() as $post) {
				if (strtotime($post->laatst_gewijzigd) > $gelezen->datum_tijd->getTimestamp()) {
					$gelezen->datum_tijd = date_create($post->laatst_gewijzigd);
				}
			}
		}

		$this->getEntityManager()->persist($gelezen);
		$this->getEntityManager()->flush();
	}

	public function getLezersVanDraad(ForumDraad $draad) {
		return $this->findBy(['draad_id' => $draad->draad_id]);
	}

	public function verwijderDraadGelezen(ForumDraad $draad) {
		$manager = $this->getEntityManager();
		foreach ($this->findBy(['draad_id' => $draad->draad_id]) as $gelezen) {
			$manager->remove($gelezen);
		}
		$manager->flush();
	}

	public function verwijderDraadGelezenVoorLid($uid) {
		$manager = $this->getEntityManager();
		foreach ($this->findBy(['uid' => $uid]) as $gelezen) {
			$manager->remove($gelezen);
		}
		$manager->flush();
	}

}