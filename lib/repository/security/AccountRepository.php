<?php

namespace CsrDelft\repository\security;

use CsrDelft\common\ContainerFacade;
use CsrDelft\common\CsrGebruikerException;
use CsrDelft\entity\security\Account;
use CsrDelft\model\entity\security\AccessRole;
use CsrDelft\model\fiscaat\CiviSaldoModel;
use CsrDelft\repository\AbstractRepository;
use CsrDelft\repository\ProfielRepository;
use CsrDelft\service\AccessService;
use Doctrine\Persistence\ManagerRegistry;

/**
 * AccountRepository
 *
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 * Wachtwoord en login timeout management.
 * @method Account|null find($id, $lockMode = null, $lockVersion = null)
 * @method Account|null findOneBy(array $criteria, array $orderBy = null)
 * @method Account[]    findAll()
 * @method Account[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountRepository extends AbstractRepository {

	/**
	 * @var AccessService
	 */
	private $accessService;

	public function __construct(ManagerRegistry $registry, AccessService $accessService) {
		parent::__construct($registry, Account::class);
		$this->accessService = $accessService;
	}

	const ORM = Account::class;
	const PASSWORD_HASH_ALGORITHM = PASSWORD_DEFAULT;

	/**
	 * @param $uid
	 * @return Account|null
	 */
	public static function get($uid) {
		$accountRepository = ContainerFacade::getContainer()->get(AccountRepository::class);
		return $accountRepository->find($uid);
	}

	/**
	 * Dit zegt niet in dat een account of profiel ook werkelijk bestaat!
	 * @param $uid
	 * @return bool
	 */
	public static function isValidUid($uid) {
		return is_string($uid) AND preg_match('/^[a-z0-9]{4}$/', $uid);
	}

	/**
	 * @param string $uid
	 *
	 * @return bool
	 */
	public static function existsUid($uid) {
		return ContainerFacade::getContainer()->get(AccountRepository::class)->find($uid) != null;
	}

	/**
	 * @param $email
	 * @return Account|null
	 */
	public function findOneByEmail($email) {
		if (empty($email)) {
			return null;
		}

		return $this->findOneBy(['email' => $email]);
	}

	public function findOneByUsername($username) {
		return $this->findOneBy(['username' => $username]);
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function existsUsername($name) {
		return $this->findOneBy(['username' => $name]) != null;
	}

	public function findAdmins() {
		return $this->createQueryBuilder('a')
			->where('a.perm_role NOT IN (:admin_perm_roles)')
			->setParameter('admin_perm_roles', [AccessRole::Lid, AccessRole::Nobody, AccessRole::Eter, AccessRole::Oudlid])
			->getQuery()->getResult();
	}

	/**
	 * @param string $uid
	 *
	 * @return Account
	 * @throws CsrGebruikerException
	 */
	public function maakAccount($uid) {
		$profiel = ProfielRepository::get($uid);
		if (!$profiel) {
			throw new CsrGebruikerException('Profiel bestaat niet');
		}
		if (ContainerFacade::getContainer()->get(CiviSaldoModel::class)->find('uid = ?', array($uid))->rowCount() === 0){
			// Maak een CiviSaldo voor dit account
			ContainerFacade::getContainer()->get(CiviSaldoModel::class)->maakSaldo($uid);
		}

		$account = new Account();
		$account->uid = $uid;
		$account->username = $uid;
		$account->email = $profiel->email;
		$account->pass_hash = '';
		$account->pass_since = date_create_immutable();
		$account->failed_login_attempts = 0;
		$account->perm_role = $this->accessService->getDefaultPermissionRole($profiel->status);
		$this->_em->persist($account);
		$this->_em->flush();
		return $account;
	}

	/**
	 * Verify SSHA hash.
	 *
	 * @param Account $account
	 * @param string $passPlain
	 * @return boolean
	 */
	public function controleerWachtwoord(Account $account, $passPlain) {
		// Controleer of het wachtwoord klopt
		$hash = $account->pass_hash;
		if (startsWith($hash, "{SSHA}")) {
			$valid = $this->checkLegacyPasswordHash($passPlain, $hash);
		} else {
			$valid = password_verify($passPlain, $hash);
		}

		// Rehash wachtwoord als de hash niet aan de eisen voldoet
		if ($valid && password_needs_rehash($hash, AccountRepository::PASSWORD_HASH_ALGORITHM)) {
			$this->wijzigWachtwoord($account, $passPlain, false);
		}

		return $valid === true;
	}

	private function checkLegacyPasswordHash($passPlain, $hash) {
		$ohash = base64_decode(substr($hash, 6));
		$osalt = substr($ohash, 20);
		$ohash = substr($ohash, 0, 20);
		$nhash = pack("H*", sha1($passPlain . $osalt));
		return hash_equals($ohash, $nhash);
	}

	/**
	 * Create SSH hash.
	 *
	 * @param string $passPlain
	 * @return string
	 */
	public function maakWachtwoord($passPlain) {
		return password_hash($passPlain, AccountRepository::PASSWORD_HASH_ALGORITHM);
	}

	/**
	 * Reset het wachtwoord van de gebruiker.
	 *  - Controleert GEEN eisen aan wachtwoord
	 *  - Wordt NIET gelogged in de changelog van het profiel
	 * @param Account $account
	 * @param $passPlain
	 * @param bool $isVeranderd
	 * @return bool
	 */
	public function wijzigWachtwoord(Account $account, $passPlain, bool $isVeranderd = true) {
		if ($passPlain != '') {
			$account->pass_hash = $this->maakWachtwoord($passPlain);
			if ($isVeranderd) {
				$account->pass_since = date_create_immutable();
			}
		}
		$this->_em->persist($account);
		$this->_em->flush();

		if ($isVeranderd) {
			// Sync LDAP
			$profiel = $account->profiel;
			if ($profiel) {
				$profiel->email = $account->email;
				ContainerFacade::getContainer()->get(ProfielRepository::class)->update($profiel);
			}
		}
		return true;
	}

	/**
	 * @param Account $account
	 */
	public function resetPrivateToken(Account $account) {
		$account->private_token = crypto_rand_token(150);
		$account->private_token_since = date_create_immutable();
		$this->_em->persist($account);
		$this->_em->flush();
	}

	/**
	 * @param Account $account
	 *
	 * @return int
	 */
	public function moetWachten(Account $account) {
		/**
		 * @source OWASP best-practice
		 */
		switch ($account->failed_login_attempts) {
			case 0:
				$wacht = 0;
				break;
			case 1:
				$wacht = 5;
				break;
			case 2:
				$wacht = 15;
				break;
			default:
				$wacht = 45;
				break;
		}
		if ($account->last_login_attempt == null) {
			return 0;
		}
		$diff = $account->last_login_attempt->getTimestamp() + $wacht - time();
		if ($diff > 0) {
			return $diff;
		}
		return 0;
	}

	/**
	 * @param Account $account
	 */
	public function failedLoginAttempt(Account $account) {
		$account->failed_login_attempts++;
		$account->last_login_attempt = date_create_immutable();
		$this->_em->persist($account);
		$this->_em->flush();
	}

	/**
	 * @param Account $account
	 */
	public function successfulLoginAttempt(Account $account) {
		$account->failed_login_attempts = 0;
		$account->last_login_attempt = date_create_immutable();
		$account->last_login_success = date_create_immutable();
		$this->_em->persist($account);
		$this->_em->flush();
	}

	public function delete(Account $account) {
		$this->_em->remove($account);
		$this->_em->flush();
	}
}
