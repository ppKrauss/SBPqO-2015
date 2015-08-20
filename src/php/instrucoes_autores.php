<?php
/**
 * Parser para leitura, atualização e normalização do instrucoes_autores.htm.
 * @see https://github.com/ppKrauss/SBPqO-2015/tree/master/entregas/conteudoExtra/InstrucoesAutores
 * @param -r      roda (default é modo relat, apenas verifica se tudo consistente, NAO ALTERA).
 * @param -n      normaliza, ALTERA conforme dados atuais.
 * @param -w      mostra no terminal as falhas do HTML
 * @param -h      help
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
foreach($xp->query("$xq_sec | $xq_td") as $node) if (  preg_match('/PN\s*(\d{1,4})/s',$node->textContent,$m) ) {
		$id0 = sprintf("PN%04d", $m[1]);
		$tipo = $node->nodeName;

		if ($tipo=='td')
			$hasRangeClass = ($node->getAttribute('class') == 'range-resumos');
		else
			$hasRangeClass = $xp->evaluate("boolean(.//*[@class='range-resumos'])",$node);
		$hasLocationClass = $xp->evaluate("boolean(.//span[@class='location1'])",$node);

		$dump = preg_replace('/\n\s+/',"\n\t",$node->textContent);
		$dump = "\n\t".trim($dump);
		if (isset($io_options['normaliza'])){
			if ($hasRangeClass) {
				if ($tipo=='td')
					$node->nodeValue='#MUDARIA1_RANGE_AQUI#';
				else
					$xp->query(".//*[@class='range-resumos']",$node)->item(0)->nodeValue='#MUDARIA2_RANGE_AQUI#';
			}
			if ($hasLocationClass) //&& $spanode->length)
				$xp->query(".//span[@class='location1']",$node)->item(0)->nodeValue='#MUDARIA3_LOCAL_AQUI#';

		} else {
			$h1 = $hasRangeClass?    'tem range':    'SEM range';			
			$h2 = $hasLocationClass? 'tem location': 'SEM location';			
			print "\n--- $tipo-dump($id0 | $h1 | $h2) $dump";
		}
	}
if (isset($io_options['normaliza'])) {
	$doc->encoding = 'UTF-8';
	print $doc->saveHTML();
}

?>
