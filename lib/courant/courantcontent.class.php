<?php

# C.S.R. Delft | pubcie@csrdelft.nl
# -------------------------------------------------------------------
# courant/class.courantcontent.php
# -------------------------------------------------------------------
# Verzorgt het in elkaar zetten van de c.s.r.-courant
# -------------------------------------------------------------------

class CourantContent {

	private $courant;
	private $instellingen;

	public function __construct(&$courant) {
		setlocale(LC_ALL, 'nl_NL@euro');
		$this->courant = $courant;
		$this->instellingen = parse_ini_file(ETC_PATH . '/csrmail.ini');
	}

	function zend($sEmailAan) {
		$sMail = $this->getMail(true);

		$smtp = fsockopen('localhost', 25, $feut, $fout);
		echo 'Zo, mail verzenden naar ' . $sEmailAan . '.<pre>';
		echo fread($smtp, 1024);
		fwrite($smtp, "HELO localhost\r\n");
		echo "HELO localhost\r\n";
		echo fread($smtp, 1024);
		fwrite($smtp, "MAIL FROM:<pubcie@csrdelft.nl>\r\n");
		echo htmlspecialchars("MAIL FROM:<pubcie@csrdelft.nl>\r\n");
		echo fread($smtp, 1024);
		fwrite($smtp, "RCPT TO:<" . $sEmailAan . ">\r\n");
		echo htmlspecialchars("RCPT TO:<" . $sEmailAan . ">\r\n");
		echo fread($smtp, 1024);
		fwrite($smtp, "DATA\r\n");
		echo htmlspecialchars("DATA\r\n");
		echo fread($smtp, 1024);
		// de mail...
		fwrite($smtp, $sMail . "\r\n");
		echo htmlspecialchars("[mail hier]\r\n");
		fwrite($smtp, "\r\n.\r\n");
		echo htmlspecialchars("\r\n.\r\n");
		echo fread($smtp, 1024);
		echo '</pre>';
	}

	function getMail($headers = false) {

		$this->assign('instellingen', $this->instellingen);
		$this->assignByRef('courant', $this->courant);

		$this->assign('indexCats', $this->courant->getCats());
		$this->assign('catNames', $this->courant->getCats(true));

		$this->assign('headers', $headers);

		return $this->fetch($this->courant->getTemplatePath());
	}

	function view() {

		echo $this->getMail();
	}

}

//einde classe
?>
