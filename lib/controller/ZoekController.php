<?php


namespace CsrDelft\controller;


use CsrDelft\common\Annotation\Auth;
use CsrDelft\controller\forum\ForumController;
use CsrDelft\controller\groepen\CommissiesController;
use CsrDelft\controller\groepen\KringenController;
use CsrDelft\controller\groepen\OnderverenigingenController;
use CsrDelft\controller\groepen\RechtengroepenController;
use CsrDelft\controller\groepen\WerkgroepenController;
use CsrDelft\controller\groepen\WoonoordenController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ZoekController extends AbstractController {
	/**
	 * @return JsonResponse
	 * @Route("/zoeken", methods={"GET", "POST"})
	 * @Auth(P_LOGGED_IN)
	 */
	public function zoeken(Request $request) {
		$zoekterm = $request->query->get('q');
		$resultaat = [];

		$instelling = lid_instelling('zoeken', 'leden');
		if ($instelling !== 'nee') {
			$resultaat[] = $this->forward(ToolsController::class . '::naamsuggesties', ['zoekin' => 'leden', 'zoekterm' => $zoekterm]);
		}
		if (lid_instelling('zoeken', 'commissies') === 'ja') {
			$resultaat[] = $this->forward(CommissiesController::class . '::zoeken', ['zoekterm' => $zoekterm]);
		}
		if (lid_instelling('zoeken', 'kringen') === 'ja') {
			$resultaat[] = $this->forward(KringenController::class . '::zoeken', ['zoekterm' => $zoekterm]);
		}
		if (lid_instelling('zoeken', 'onderverenigingen') === 'ja') {
			$resultaat[] = $this->forward(OnderverenigingenController::class . '::zoeken', ['zoekterm' => $zoekterm]);
		}
		if (lid_instelling('zoeken', 'werkgroepen') === 'ja') {
			$resultaat[] = $this->forward(WerkgroepenController::class . '::zoeken', ['zoekterm' => $zoekterm]);
		}
		if (lid_instelling('zoeken', 'woonoorden') === 'ja') {
			$resultaat[] = $this->forward(WoonoordenController::class . '::zoeken', ['zoekterm' => $zoekterm]);
		}
		if (lid_instelling('zoeken', 'groepen') === 'ja') {
			$resultaat[] = $this->forward(RechtengroepenController::class . '::zoeken', ['zoekterm' => $zoekterm]);
		}
		if (lid_instelling('zoeken', 'forum') === 'ja') {
			$resultaat[] = $this->forward(ForumController::class . '::titelzoeken', ['zoekterm' => $zoekterm]);
		}
		if (lid_instelling('zoeken', 'fotoalbum') === 'ja') {
			$resultaat[] = $this->forward(FotoAlbumController::class . '::zoeken', ['zoekterm' => $zoekterm]);
		}
		if (lid_instelling('zoeken', 'agenda') === 'ja') {
			$resultaat[] = $this->forward(AgendaController::class . '::zoeken', ['zoekterm' => $zoekterm]);
		}
		if (lid_instelling('zoeken', 'documenten') === 'ja') {
			$resultaat[] = $this->forward(DocumentenController::class . '::zoeken', ['zoekterm' => $zoekterm]);
		}
		if (lid_instelling('zoeken', 'boeken') === 'ja') {
			$resultaat[] = $this->forward(BibliotheekController::class . '::zoeken', ['zoekterm' => $zoekterm]);
		}

		return new JsonResponse(array_merge(...array_values(array_map(function ($response) {
			return json_decode($response->getContent());
		}, $resultaat))));
	}
}
