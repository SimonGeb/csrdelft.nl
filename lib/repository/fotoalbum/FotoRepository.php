<?php

namespace CsrDelft\repository\fotoalbum;

use CsrDelft\common\CsrException;
use CsrDelft\entity\fotoalbum\Foto;
use CsrDelft\model\RetrieveByUuidTrait;
use CsrDelft\model\security\LoginModel;
use CsrDelft\repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

class FotoRepository extends AbstractRepository {
	use RetrieveByUuidTrait;

	/**
	 * @var FotoTagsRepository
	 */
	private $fotoTagsRepository;

	public function __construct(ManagerRegistry $registry, FotoTagsRepository $fotoTagsRepository) {
		parent::__construct($registry, Foto::class);

		$this->fotoTagsRepository = $fotoTagsRepository;
	}

	/**
	 * @override parent::retrieveByUUID($UUID)
	 */
	public function retrieveByUUID($UUID) {
		$parts = explode('@', $UUID, 2);
		$path = explode('/', $parts[0]);
		$filename = array_pop($path);
		$subdir = implode('/', $path);
		return $this->find(['subdir' => $subdir, 'filename' => $filename]);
	}

	/**
	 * @param Foto $foto
	 */
	public function create(Foto $foto) {
		$foto->owner = LoginModel::getUid();
		$foto->rotation = 0;

		$this->getEntityManager()->persist($foto);
		$this->getEntityManager()->flush();
	}

	public function delete(Foto $foto) {
		$this->getEntityManager()->remove($foto);
		$this->getEntityManager()->flush();
	}

	/**
	 * @param Foto $foto
	 * @throws CsrException
	 */
	public function verwerkFoto(Foto $foto) {
		if (!$this->find(['subdir' => $foto->subdir, 'filename' => $foto->filename])) {
			$this->create($foto);
			if (false === @chmod($foto->getFullPath(), 0644)) {
				throw new CsrException('Geen eigenaar van foto: ' . htmlspecialchars($foto->getFullPath()));
			}
		}
		if (!$foto->hasThumb()) {
			$foto->createThumb();
		}
		if (!$foto->hasResized()) {
			$foto->createResized();
		}
	}

	/**
	 * @param Foto $foto
	 * @return bool
	 */
	public function verwijderFoto(Foto $foto) {
		$ret = true;
		$ret &= unlink($foto->getFullPath());
		if ($foto->hasResized()) {
			$ret &= unlink($foto->getResizedPath());
		}
		if ($foto->hasThumb()) {
			$ret &= unlink($foto->getThumbPath());
		}
		if ($ret) {
			$this->getEntityManager()->remove($foto);
			$this->getEntityManager()->flush();
			$this->fotoTagsRepository->verwijderFotoTags($foto);
		}
		return $ret;
	}

	/**
	 * Rotate resized & thumb for prettyPhoto to show the right way up.
	 *
	 * @param Foto $foto
	 * @param int $degrees
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function rotate(Foto $foto, $degrees) {
		$foto->rotation += $degrees;
		$foto->rotation %= 360;
		$this->getEntityManager()->persist($foto);
		$this->getEntityManager()->flush();

		if ($foto->hasThumb()) {
			unlink($foto->getThumbPath());
		}
		$foto->createThumb();

		if ($foto->hasResized()) {
			unlink($foto->getResizedPath());
		}
		$foto->createResized();
	}

}
