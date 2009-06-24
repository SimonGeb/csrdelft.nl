<?php
/*
 * Peiling beheerpagina
 * 
 */
# instellingen & rommeltjes
require_once 'include.config.php';
require_once 'class.peilingcontent.php' ;

// if user has no permission
if(!$loginlid->hasPermission('P_LOGGED_IN') OR !Peiling::magBewerken()){
	$melding="Je hebt geen rechten om deze pagina te bekijken.";
	$pagina=new csrdelft(new Stringincluder($melding, 'Peilingbeheer'));
	$pagina->view();
	exit();	
}

require_once('class.peilingbeheercontent.php');
$beheer = new PeilingBeheerContent();

$resultaat ='';
if (isset($_POST)){
	if(isset($_POST['titel'])){
		//Process peilingbeheer.php POST.
		require_once('class.peiling.php');
		$titel = $_POST['titel'];
		$properties['titel'] = $titel;
		$verhaal = $_POST['verhaal'];
		$properties['verhaal'] = $verhaal;
		$optieid = 1;
		$opties=array();
		while(isset($_POST['optie'.$optieid])){
			$optietekst = $_POST['optie'.$optieid]; 				
			if($optietekst!=''){
				$opties[$optieid] = $optietekst;
			}
			$optieid++;
		}
		$properties['opties']=$opties;
	
		$peiling = new Peiling(0);
		$pid = $peiling->maakPeiling($properties);	
		$resultaat = 'De nieuwe peiling heeft id '.$pid.'.';			
	}
	
	//Process externe POST
	if( isset($_POST['actie']) && isset($_POST['id']) && is_numeric($_POST['id'])){
		$id = (int)$_POST['id'];
		$actie = $_POST['actie'];
		$peiling = new Peiling($id);
		switch ($actie) {
			case "stem":
				if(isset($_POST['optie']) && is_numeric($_POST['optie'])){						
					$optie = (int)$_POST['optie'];
					$r = $peiling->stem($optie);
				}
				break;
			case "verwijder":
				$r = $peiling->deletePeiling();
				break;
		}
		$ref = $_SERVER['HTTP_REFERER'];			
		header('location: '.$ref);
		exit();
	}
}
$beheer->setResultaat($resultaat);

$pagina=new csrdelft($beheer);
$pagina->view();
?>
