<?php

namespace CsrDelft\entity\forum;

use CsrDelft\common\ContainerFacade;
use CsrDelft\model\security\LoginModel;
use CsrDelft\repository\forum\ForumDelenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ForumCategorie.class.php
 *
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 * Een forum categorie bevat deelfora.
 *
 * @ORM\Entity(repositoryClass="CsrDelft\repository\forum\ForumCategorieRepository")
 * @ORM\Table("forum_categorien")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class ForumCategorie {

	/**
	 * Primary key
	 * @var int
	 * @ORM\Column(type="integer")
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 */
	public $categorie_id;
	/**
	 * Titel
	 * @var string
	 * @ORM\Column(type="string")
	 */
	public $titel;
	/**
	 * Rechten benodigd voor bekijken
	 * @var string
	 * @ORM\Column(type="string")
	 */
	public $rechten_lezen;
	/**
	 * Weergave volgorde
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	public $volgorde;
	/**
	 * Forumdelen
	 * @var ForumDeel[]
	 */
	private $forum_delen;

	public function magLezen() {
		return LoginModel::mag($this->rechten_lezen);
	}

	/**
	 * Lazy loading by foreign key.
	 *
	 * @return ForumDeel[]
	 */
	public function getForumDelen() {
		if (!isset($this->forum_delen)) {
			$forumDelenRepository = ContainerFacade::getContainer()->get(ForumDelenRepository::class);
			$this->setForumDelen($forumDelenRepository->getForumDelenVoorCategorie($this));
		}
		return $this->forum_delen;
	}

	public function hasForumDelen() {
		$this->getForumDelen();
		return !empty($this->forum_delen);
	}

	/**
	 * Public for search results and all sorts of prefetching.
	 *
	 * @param array $forum_delen
	 */
	public function setForumDelen(array $forum_delen) {
		$this->forum_delen = $forum_delen;
	}

}