<?php
/**
 * Parser para leitura, atualização e normalização do instrucoes_autores.htm.
 * @see https://github.com/ppKrauss/SBPqO-2015/tree/master/entregas/conteudoExtra/InstrucoesAutores
 * @param -r      roda (default é modo relat, apenas verifica se tudo consistente, NAO ALTERA).
 * @param -n      normaliza, ALTERA conforme dados atuais.
 * @param -w      mostra no terminal as falhas do HTML
 * @param -h      help
 Falta flag limpar para passar para Paula
 * USO:
 *  $ cd projeto
 *  $ php src/php/instrucoes_autores.php -r
 */

include('lib.php');
$showDomWarnings = isset($io_options['warnings']);

// var_dump($io_options); //params

$cmd = array_pop($io_options_cmd); // ignora demais se houver mais de um
if (!$cmd)
	die( "\nERRO, SEM COMANDO\n" );
elseif (count($io_options_cmd)) {
	die("\nERRO, MAIS DE UM COMANDO, SÓ PODE UM\n");
}
if ($cmd == 'help')
	die("\n  -r roda (default relat dumps), -w HTML warnings, -n normalizar (novo HTML!)\n");
elseif ($cmd=='version')
	die(" lib.php version $LIBVERS\n");

$fileIn = 'entregas/conteudoExtra/InstrucoesAutores/instrucoes_autores.html'; //'php://stdin';
$fileOut = 'php://stdout';

$doc = new DOMDocument();
if ($showDomWarnings)
	$doc->loadHTMLfile($fileIn, LIBXML_NOWARNING | LIBXML_NOERROR);
else
	@$doc->loadHTMLfile($fileIn, LIBXML_NOWARNING | LIBXML_NOERROR);
$doc->encoding = 'UTF-8';

$xp = new DOMXpath($doc);
$xq_sec = "//section"; //[.//p[@class='range-resumos'] and .//span[@class='location1']]
$xq_td = "//td";  //

if (isset($io_options['normaliza'])) {
	foreach($xp->query("//span[@class='location1']") as $node) {
		$span = $node;
		list($idloc,$name) = localValido($span->nodeValue,2);
		$span->setAttribute('idref',$idloc);
		if ($name) $span->nodeValue = $name;
	}
	foreach($xp->query("//td[@class='range-resumos']") as $node) {
		$val = $node->nodeValue;
		$val = preg_replace_callback(
			'/PN\s*(\d+)[\s\-a]+(?:PN)?\s*(\d+)/su'
			,function ($m){return sprintf("PN%04d - PN%04d",$m[1],$m[2]);}
			,$val
		);
		$node->nodeValue=$val;
	}
	$doc->encoding = 'UTF-8';
	print $doc->saveHTML();
}

?>
