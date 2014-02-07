<?php

/**
 * instellingencontent.class.php	| 	Jan Pieter Waagmeester (jieter@jpwaag.com)
 *
 * 
 * Instellingenketzerding.
 * 
 */
class LidInstellingenView extends TemplateView {

	public function __construct(LidInstellingen $model) {
		parent::__construct($model);
	}

	public function getTitel() {
		return 'Stekinstellingen';
	}

	public function view() {
		$this->smarty->display('MVC/instellingen/lidinstellingen_page.tpl');
		echo '<form id="form" action="/instellingen/opslaan" method="post"><div id="tabs" style="width: 700px;"><ul>';
		foreach (array_keys($this->model->getInstellingen()) as $module) {
			echo '<li><a href="#tabs-' . $module . '">' . ucfirst($module) . '</a></li>';
		}
		echo '</ul>';
		foreach ($this->model->getInstellingen() as $module => $instellingen) {
			echo '<div id="tabs-' . $module . '">';
			foreach ($instellingen as $key => $instelling) {
				$this->smarty->assign('module', $module);
				$this->smarty->assign('id', $key);
				$this->smarty->assign('type', $this->model->getType($module, $key));
				$this->smarty->assign('opties', $this->model->getTypeOptions($module, $key));
				$this->smarty->assign('label', $this->model->getDescription($module, $key));
				$this->smarty->assign('waarde', $this->model->getValue($module, $key));
				$this->smarty->assign('default', $this->model->getDefault($module, $key));
				$this->smarty->display('MVC/instellingen/lidinstelling.tpl');
			}
			echo '</div>';
		}
		echo '</div><br /><input type="submit" value="Opslaan" />&nbsp;<input type="button" value="Annuleren" onclick="location.href=\'/\';" /></form>';
	}

}
