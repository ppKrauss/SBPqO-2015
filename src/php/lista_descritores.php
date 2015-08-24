<?php
/**
 * Gerador de indice descritores para alimentar MS-Word ou InDesign de Paula.
 * Ref. https://github.com/ppKrauss/SBPqO-2015/blob/master/amostras/indiceDescritores2014.pdf
 * @see indiceDescritores.csv
 * @param -r      roda gerando HTML.
 * @param -w      mostra no terminal as contagens
 * @param -h      help
 * USO:
 *  $ cd projeto
 *  $ php src/php/lista_descritores.php -r > lista_descritores.htm
 *  $ php src/php/lista_descritores.php | more 
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

// indiceDescritores.csv =  ($DESCRITORES[key] = resumos);
print $HEAD;
$idx = [];
$nsup = 0;
$sups = [];
$names = array_keys($DESCRITORES);
sort($names);
foreach ($names as $k) {
	$resumos = join(', ',$DESCRITORES[$k]);
	print "\n
	<p><span class='nome'>$k</span><span class='pontinhos'>...</span><span class='resumos'>$resumos</span></p>";
}

print "\n</body>\n</html>\n";
?>
