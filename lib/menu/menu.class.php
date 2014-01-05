<?php
require_once 'menu/beheer/MenusModel.class.php';
/**
 * menu.class.php	| 	P.W.G. Brussee (brussee@live.nl)
 * 
 * Een menu incl. permissies uit de database trekken.
 * De menuopties die niet overeenkomen met de permissies die de
 * gebruiker heeft worden niet getoond.
 */
class Menu extends SimpleHTML {

	/**
	 * unique short name of the menu
	 */
	private $_menu;
	
	/**
	 * 0: main
	 * 1: sub
	 * 2: page
	 * 3: block
	 */
	private $_level;
	
	/**
	 * Requested url
	 */
	private $_path;
	
	/**
	 * Root MenuItem of menu tree
	 */
	private $_tree_root;
	
	/**
	 * MenuItem of the current page
	 */
	private $_active_item;
	
	public function __construct($menu, $level=0) {
		$this->_menu = $menu;
		$this->_level = $level;
		
		$path = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL);
		
		//echo $path .'<br />'; //DEBUG
		
		$pos = strpos($path, '.php');
		if ($pos !== false) {
			$path = substr($path, 0, $pos);
		}
		$pos = strpos($path, '#');
		if ($pos !== false) {
			$path = substr($path, 0, $pos);
		}
		$this->_path = $path;
		
		//echo $path .'<br />'; //DEBUG
		
		$items = MenusModel::getMenuItemsVoorLid($menu);
		foreach ($items as $item) {
			
			//echo $item->getLink() .'<br />'; //DEBUG
			
			if ($path === $item->getLink()) {
				$this->_active_item = $item;
			}
		}
		if ($this->_active_item === null) {
			$this->_active_item = new MenuItem();
		}
		
		$this->_tree_root = MenusModel::getMenuTree($menu, $items);
	}
	
	public function view() {
		$smarty = new \Smarty_csr();
		$smarty->assign('root', $this->_tree_root);
		$smarty->assign('huidig', $this->_active_item);
		
		if ($this->_level === 0) {
			if(Loginlid::instance()->hasPermission('P_ADMIN')){
				require_once 'savedquery.class.php';
				$smarty->assign('queues', array(
					'forum' => new SavedQuery(ROWID_QUEUE_FORUM),
					'meded' => new SavedQuery(ROWID_QUEUE_MEDEDELINGEN)
				));
			}
			$smarty->display('menu/menu.tpl');
		}
		else if ($this->_level === 3) {
			$smarty->display('menu/menu_block.tpl');
		}
	}
}

?>