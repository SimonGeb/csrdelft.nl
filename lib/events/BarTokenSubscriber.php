<?php


namespace CsrDelft\events;


use CsrDelft\controller\api\v3\BarSysteemController;
use CsrDelft\entity\bar\BarLocatie;
use CsrDelft\service\AccessService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Uid\Uuid;

class BarTokenSubscriber implements EventSubscriberInterface
{
	/**
	 * @var Security
	 */
	private $security;
	/**
	 * @var AccessService
	 */
	private $accessService;
	/**
	 * @var ManagerRegistry
	 */
	private $manager;

	public function __construct(Security $security, AccessService $accessService, ManagerRegistry $manager)
	{

		$this->security = $security;
		$this->accessService = $accessService;
		$this->manager = $manager;
	}

	public static function getSubscribedEvents()
	{
		return [
			KernelEvents::CONTROLLER
		];
	}

	public function onKernelController(ControllerEvent $event)
	{
		$controller = $event->getController();
		$request = $event->getRequest();

		if (is_array($controller)) {
			$controller = $controller[0];
		}

		if ($controller instanceof BarSysteemController) {
			if ($this->accessService->mag($this->security->getUser(), P_FISCAAT_MOD)) {
				return;
			}

			$token = $request->headers->get('X-Bar-Token');

			if (!$token) {
				throw new AccessDeniedException();
			}

			$barLocatie = $this->manager
				->getRepository(BarLocatie::class)
				->findBy(['uuid' => Uuid::fromString($token)]);

			if (!$barLocatie) {
				throw new AccessDeniedException();
			}

			if ($barLocatie->ip != $request->getClientIp()) {
				throw new AccessDeniedException();
			}
		}
	}
}