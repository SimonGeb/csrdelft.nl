<?php
/*
 * class.groepen.php	| 	Jan Pieter Waagmeester (jieter@jpwaag.com)
 * 
 * Groepen zijn als volgt in de db opgeslagen:
 * groeptype:	Verschillende 'soorten' groepen: commissies, woonoorden, etc.
 * groep:		De daadwerkelijke groepen.
 * groeplid:	De leden van verschilllende groepen.
 * 
 * leden kunnen uiteraard lid zijn van verschillende groepen, maar niet meer 
 * dan één keer in een bepaalde groep zitten.
 *  
 * Deze klasse is een verzameling van groepobjecten van een bepaald type. Standaard 
 * worden alleen de h.t.-groepen opgehaald.
 */
 
class Groepen{
	
	private $type;
	
	private $groepen=array();
	
	/*
	 * Constructor voor Groepen.
	 * 
	 * @param	$groeptype		Welke groepen moeten geladen worden?
	 * @return 	void
	 */
	public function __construct($groeptype){
		$db=MySql::get_MySql();
		
		//we laden eerst de gegevens over de groep op
		$qGroeptype="
			SELECT id, naam, beschrijving
			FROM groeptype
			WHERE groeptype.naam='".$db->escape($groeptype)."'
			LIMIT 1;";
		$rGroeptype=$db->query($qGroeptype);
		if($rGroeptype!==false AND $db->numRows($rGroeptype)==1){
			$this->type=$db->next($rGroeptype);
		}else{
			//TODO: dit netjes doen. Exception gooien ofzo
			die('FATALE FEUT: Groeptype bestaat niet! Groepen::__construct()');
		}

		//Vervolgens de groepen van het gegeven type ophalen:
		$this->load();
	}
	
	/*
	 * Laten we de gegevens van het groeptype ophalen, met de bekende groepen voor
	 * het type.
	 */
	private function load(){
		$db=MySql::get_MySql();
			
		$qGroepen="
			SELECT 
				groep.id AS groepId, groep.snaam AS snaam, groep.naam AS naam,
				groep.sbeschrijving AS sbeschrijving, groep.beschrijving AS beschrijving, groep.zichtbaar AS zichtbaar,
				groep.status AS status, groep.installatie AS installatie, groep.aanmeldbaar AS aanmeldbaar,
				groeplid.uid AS uid, groeplid.op AS op, groeplid.functie AS functie, groeplid.prioriteit AS prioriteit 
			FROM groep
			LEFT JOIN groeplid ON(groep.id=groeplid.groepid) 
			WHERE groep.gtype=".$this->getId()."
			  AND groep.zichtbaar='zichtbaar'
			  AND groep.status='ht'
			ORDER BY groep.snaam ASC, groeplid.prioriteit ASC, groeplid.uid ASC;";
		$rGroepen=$db->query($qGroepen);
		
		//nu een beetje magic om een stapeltje groepobjecten te genereren:
		$currentGroepId=null;
		$aGroep=array();
		while($aGroepraw=$db->next($rGroepen)){
			//eerste groepid in de huidige groep stoppen
			if($currentGroepId==null){ $currentGroepId=$aGroepraw['groepId']; }
			
			//zijn we bij een volgende groep aangekomen?
			if($currentGroepId!=$aGroepraw['groepId']){
				//groepobject maken en aan de array toevoegen
				$this->groepen[$aGroep[0]['groepId']]=new Groep($aGroep);
				
				//tenslotte nieuwe groep als huidige kiezen en groeparray leegmikken
				$currentGroepId=$aGroepraw['groepId'];
				$aGroep=array();
				
			}
			$aGroep[]=$aGroepraw;
		}
		if(isset($aGroep[0])){
			//tot slot de laatste groep ook toevoegen
			$this->groepen[$aGroep[0]['groepId']]=new Groep($aGroep);
		}
	}
	/*
	 * Sla de huidige toestand van de groep op in de database.
	 */
	public function save(){
		$db=MySql::get_MySql();
		$qSave="
			UPDATE groeptype 
			SET beschrijving='".$db->escape($this->getBeschrijving())."'
			WHERE id=".$this->getId()."
			LIMIT 1;";
		return $db->query($qSave);
	}
	
	public function getId(){			return $this->type['id']; }
	public function getNaam(){ 			return $this->type['naam']; }
	public function getBeschrijving(){	return $this->type['beschrijving']; }
	public static function isAdmin(){		
		$lid=Lid::get_lid();
		return $lid->hasPermission('P_LEDEN_MOD');
	}
	
	public function getGroep($groepId){
		if(isset($this->groepen[$groepId])){
			return $this->groepen[$groepId];
		}
		return false;
	}
	public function getGroepen(){		return $this->groepen; }
	
	/*
	 * statische functie om de groepen bij een gebruiker te zoeken.
	 * 
	 * @param	$uid	Gebruiker waarvoor groepen moeten worden opgezocht
	 * @return			Array met groepen
	 */
	public static function getGroepenByUid($uid){
		$db=MySql::get_MySql();
		$lid=Lid::get_lid();
		
		$groepen=array();
		if($lid->isValidUid($uid)){
			$qGroepen="
				SELECT 
					groep.id AS id, groep.snaam AS snaam, groep.naam AS naam, groep.status AS status,
					groeptype.naam AS gtype
				FROM groep
				INNER JOIN groeptype ON(groep.gtype=groeptype.id)
				WHERE groep.id IN ( 
					SELECT groepid FROM groeplid WHERE uid = '".$uid."'
				)
				ORDER BY groep.status, groeptype.prioriteit, groep.naam;";
				
			$rGroepen=$db->query($qGroepen);
			if ($rGroepen !== false and $db->numRows($rGroepen) > 0){
				$groepen=$db->result2array($rGroepen);
			}
		}
		return $groepen;
	}
	/*
	 * Statische functie om een verzameling van groeptypes terug te geven
	 * 
	 * @return		Array met groeptypes
	 */
	public static function getGroeptypes(){
		$db=MySql::get_MySql();
		$groeptypes=array();
		$qGroeptypen="
			SELECT id, naam
			FROM groeptype
			WHERE zichtbaar=1
			ORDER BY prioriteit ASC, naam ASC;";
		$rGroeptypen=$db->query($qGroeptypen);
		return $db->result2array($rGroeptypen);
	}
	
	public static function isValidGtype($gtypetotest){
		foreach(Groepen::getGroeptypes() as $gtype){
			if($gtype['id']==$gtypetotest OR $gtype['naam']==$gtypetotest){
				return true;
			}
		}
		return false;
	}
}

?>
