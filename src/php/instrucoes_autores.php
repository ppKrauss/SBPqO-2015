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
 *  $ php src/php/instrucoes_autores.php -r > entregas/conteudoExtra/InstrucoesAutores/instrucoes_dumps.txt
 */

// CONFIGS:
$fileIn      = 'entregas/conteudoExtra/InstrucoesAutores/instrucoes_autores.html'; //'php://stdin';
$fileOut     = 'php://stdout';
$anyID_REGEX = '(PE|PO|HA|COL|JL|AO|FC|PI|PN)\s*(\d{1,5})';
$stdID_REGEX = '(?:PE|PO|HA|COL|JL|AO|FC|PI|PN)\d{4,4}';


include('lib.php');
$showDomWarnings = isset($io_options['warnings']);


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

$HEAD = "<!DOCTYPE HTML>\n<html>\n<head>\n\t<meta charset=\"UTF-8\">\n</head>\n<body>";
$htmlIn = file_get_contents($fileIn);
if (!strpos('<html',$htmlIn))
	$htmlIn = $HEAD.$htmlIn."</body></html>";

$doc = new DOMDocument();
if ($showDomWarnings)
	$doc->loadHTML($htmlIn, LIBXML_NOWARNING | LIBXML_NOERROR);
else
	@$doc->loadHTML($htmlIn, LIBXML_NOWARNING | LIBXML_NOERROR);
$doc->encoding = 'UTF-8';
$xp = new DOMXpath($doc);

if (isset($io_options['normaliza'])) {  // modifica HTML
	foreach($xp->query("//span[@class='location1']") as $node) { // mudou de span para a
		$span = $node;
		list($idloc,$name) = localValido($span->nodeValue,2);
		$span->setAttribute('idref',$idloc);
		if ($name) $span->nodeValue = $name;
	}
	foreach($xp->query("//td[@class='range-resumos']") as $node)
		$node->nodeValue = IDnormalize($node->nodeValue);
	$doc->encoding = 'UTF-8';
	print $doc->saveHTML();

} else {  // apenas relatorio
	print "\n== RELATÓRIO DE DADOS NO HTML ==\n arquivo $fileIn\n";
	$lastLocID = '';
	$ranges = [];
	foreach($xp->query("//section | //td") as $node) 
		if (  preg_match("/$anyID_REGEX/s",$node->textContent,$m) ) {
			$sec0 = $m[1];
			$id0 = sprintf("$sec0%04d", $m[2]);
			$line = $node->getLineNo();
			$tipo = $node->nodeName;

			$hasLocationClass = $xp->evaluate("boolean(.//a[@class='location1'])",$node);
			$hasRangeClass = ($node->getAttribute('class') == 'range-resumos');
			if (!$hasRangeClass)
				$hasRangeClass = $xp->evaluate("boolean(.//*[@class='range-resumos'])",$node);

			$dump = preg_replace('/\n\s+/',"\n\t",$node->textContent);
			list($dump,$hasRange) = IDnormalize($dump,TRUE);

			if ($hasLocationClass)
				//print "  (debug ".$xp->query(".//a[@class='location1']",$node)->item(0)->nodeValue.") ";
				$lastLocID = localValido(
					$xp->query(".//a[@class='location1']",$node)->item(0)->nodeValue
					,TRUE
				);
			$thisRanges = [];
			$ERRO='';
			$isGrupo = preg_match('/\sgrupos?[\.\s]/i',$dump); // usa no if
			if ($hasRange)
				preg_replace_callback(  // scan ranges
					"/($stdID_REGEX) \- ($stdID_REGEX)/s"
					,function ($m) use (&$ranges,&$instrucoes_autores,&$idLoc,$lastLocID,&$ERRO,$isGrupo,&$thisRanges) {
						$err='';
						if (isset($instrucoes_autores["$m[1] grupo"])){
							$i = $instrucoes_autores["$m[1] grupo"];
							if ($isGrupo && $i['r1']==$m[2] && ($xx=$idLoc[ $i['local'] ])!=$lastLocID)
								$err=$ERRO = "CONFERIR: deveria ser $xx = $i[local].";
							elseif ($isGrupo && $i['r1']!=$m[2]) //r0 ok
								$err=$ERRO = "ERRO: certo seria $i[r0]-$i[r1].";
						} else {
							$err=$ERRO = "ERRO: intervalo $m[1]-$m[2] desconhecido.";
							foreach($instrucoes_autores as $k=>$r)
								if ($m[1]==$r['r0'] && $m[2]==$r['r1']){
									$err=$ERRO='';
									break;
								}
						}
						$r = [$m[1],$m[2],$lastLocID,$err];
						$ranges[]=$r;
						$thisRanges[]=$r;
					}
					,$dump
				);
			$dump = "\n\t".trim($dump);
			$h1 = $hasRangeClass?    'tem range':    'SEM range';			
			$h2 = $hasLocationClass? 'tem location': 'SEM location';			
			print "\n--- $tipo(linha $line)-dump($id0 | $h1 | $h2) $dump";
			if ($ERRO) foreach($thisRanges as $r)
				print "\n\t * ".join(' | ',$r);

		} // for if
} // fim relatorio

function IDnormalize($val,$retC=FALSE) {
	global $anyID_REGEX;
	global $stdID_REGEX;
	$val = preg_replace_callback(
		"/$anyID_REGEX/su"
		,function ($m){return sprintf("%s%04d",$m[1],$m[2]);}
		,$val
	);
	$C = 0;
	$val = preg_replace("/(\()?\\s*($stdID_REGEX)[\\s\\-a]+($stdID_REGEX)/su",'$1$2 - $3', $val, -1, $C);
	return $retC? array($val,$C): $val;
}


?>
