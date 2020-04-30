<?php

namespace CsrDelft\entity\groepen;

use CsrDelft\entity\groepen\AbstractGroep;
use CsrDelft\repository\groepen\leden\BestuursLedenRepository;
use CsrDelft\Orm\Entity\T;
use Doctrine\ORM\Mapping as ORM;

/**
 * Bestuur.class.php
 *
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 * @ORM\Entity(repositoryClass="BesturenRepository")
 * @ORM\Table("besturen")
 */
class Bestuur extends AbstractGroep {

	const LEDEN = BestuursLedenRepository::class;

	/**
	 * Bestuurstekst
	 * @var string
	 */
	public $bijbeltekst;
	/**
	 * Database table columns
	 * @var array
	 */
	protected static $persistent_attributes = [
		'bijbeltekst' => [T::Text]
	];
	/**
	 * Database table name
	 * @var string
	 */
	protected static $table_name = 'besturen';

	public function getUrl() {
		return '/groepen/besturen/' . $this->id;
	}

}
