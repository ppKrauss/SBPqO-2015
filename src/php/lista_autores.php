<?php
/**
 * Gerador de indice autores para alimentar MS-Word ou InDesign de Paula. 
 * Ref. https://github.com/ppKrauss/SBPqO-2015/blob/master/amostras/indiceAutores2014.pdf
 * @see https://github.com/ppKrauss/SBPqO-2015/tree/master/entregas/conteudoExtra/InstrucoesAutores
 * @param -r      roda gerando HTML.
 * @param -w      mostra no terminal as contagens
 * @param -h      help
 * USO:
 *  $ cd projeto
 *  $ php src/php/lista_autores.php -r > lista_autores.htm
 *  $ php src/php/lista_autores.php | more 
 */

// CONFIGS:
$fileOut     = 'php://stdout';


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


print $HEAD;
$auts = $csvFiles_rowByKey['autores'][2];
$idx = [];
$nsup = 0;
$sups = [];
foreach ($auts as $id => $r) if ($r[2]!='#ERROR!' && $id!='CSV_HEAD') {
	if ($r[1]){
		$nsup++;
		$idx["$r[2]<sup>$nsup</sup>"] = $r;
		$sups[$nsup] = $r[3];
	} else
		$idx[$r[2]] = $r;
} elseif ($id!='CSV_HEAD') {
	print "\n--- err com $r[0].";
}
$names = array_keys($idx);
sort($names);
foreach ($names as $name) { // conforme espec. Paula
	print "
	<p><span class='nome'>$name</span>\t<span class='resumos'>{$idx[$name][4]}</span></p>";
}
print "\n\n<p>---------FOOTNOTES (desambiguação de homônimos)-----</p>";
foreach ($sups as $nsup=>$name)
	print "\n<p><sup>$nsup</sup> $name</p>";

print "\n</body>\n</html>\n";
?>
