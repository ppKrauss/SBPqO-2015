<?php
/**
 * RESUMOS, parser e analisador.
 * USO:
 *  $ cd projeto
 *  $ php src/php/main.php  --raw --in=entregas/HTM1 > entregas/HTM2/resumosOriginais.html
 *  $ php src/php/main.php  --finalXml --multisec < entregas/HTM2/resumosOriginais.html > all1.xml
 *  $ php src/php/main.php  --finalHtml < all1.xml > all1.htm
 *  $ php tools/main.php --finalXml --day=2014-09-03  --in=material/originais-UTF8/ > SBPqO_dia09-03.xml
 *  $ php tools/main.php -h
 *  $ php src/php/main.php --tpl1 --in=entregas/conteudoExtra/programacaoEventos.csv --xsltFile=src/xsl/xcsvProgramacao2htm.xsl
 */

include('lib.php');

$cmd = array_pop($io_options_cmd); // ignora demais se houver mais de um
if (!$cmd)
	die( "\nERRO, SEM COMANDO\n" );
elseif (count($io_options_cmd)) {
	die("\nERRO, MAIS DE UM COMANDO, SÓ PODE UM\n");
}
if ($cmd == 'help')
	die($io_usage);
elseif ($cmd=='version')
	die(" lib.php version $LIBVERS\n");


$isXML = ($cmd=='xml' || $cmd=='finalXml' || $cmd=='raw');
$isRELAT = (substr($cmd,0,5)=='relat');
$isMultiSec = isset($io_options['multisec']);
$showDomWarnings = isset($io_options['warnings']);

$file = 'php://stdin';

if ( isset($io_options['tpl1']) ) {   // cmd tpl1
	$xml = csv2xmlByHead(isset($io_options['in'])? $io_options['in']: $file, 'EVENTO',1);
	$xml = XML_HEADER1.str_replace('<br>','<br/>',$xml);
	//die($xml);
	if ( isset($io_options['xsltFile']) ) {
		$xsl2 = $io_options['xsltFile'];
		$xsl1 = str_replace('.xsl','_pre.xsl',$xsl2);
		$sdoc = new SimpleXMLElement($xml);

		/// GAMBI pelo bug de XSLT com xsl_getCsvRow()
		$sdoc = transformToDom($xsl1, $sdoc);
		$tmp = $sdoc->saveXML();
		$tmp = preg_replace_callback('#<DIA id="([^"]+)">#s', function ($m) {
			return $m[0]."<infoGrupo>".gambi_getCsvRow('programacaoGrupoDia',$m[1])."</infoGrupo>";
		}, $tmp);
		$sdoc = new SimpleXMLElement($tmp);
		$xml  = transformToXML( $xsl2, $sdoc );		
	}
	die("$xml\n");

} elseif ( isset($io_options['convCsv']) ) {   // cmd convCsv
	convCsv($io_options['in']);
	die("\n");

} elseif ( isset($io_options['in']) ) {    // demais cmd's
	$file = $io_options['in'];
	if (is_dir($file)) {
		$file = trim($file);
		if (substr($file,-1,1)!='/') 
			$file.='/';
		$OUT = '';
		$LINE = ($isXML || $isRELAT)? "\n": '';
		foreach(scandir($file) as $f) if ( preg_match('/\.html?$/i',$f) )
			$OUT .= preg_replace( '|</?html>|','',  exec_cmd($cmd,"$file$f",$isRELAT,$isMultiSec) ).$LINE ;
		$OUT = "\n<html>\n$OUT\n</html>";
		$file ='';
	} // if
} // if
if ($file)
	$OUT = exec_cmd($cmd,$file,$isRELAT,$isMultiSec);

print_byTPL($OUT,$isRELAT,$isXML);
if ($isRELAT)
	print "\n\nTOTAL GERAL: $NTOTAL\n";



/////////

function print_byTPL($OUT,$isRELAT,$isXML,$tag='html') {
	global $cmd;
	global $io_options;	
	if ($isXML) {
		if ($cmd == 'xml')
			print XML_HEADER1."\n<$tag>$OUT\n</$tag>\n";
		else
			print XML_HEADER1.$OUT;
	} elseif (!$isRELAT) {
		$FP = (isset($io_options['firstpage']))? "body {counter-reset: page {$io_options['firstpage']};}": '';
		$TPL = file_get_contents('src/xsl/HTML_STANDARD_F1.html');
		$TPL = str_replace(array('{_ALL_SECTIONS_}','{_FIRSTPAGE_}'), array($OUT,$FP), $TPL);
		print $TPL;
	} else
		print $OUT;
}

function exec_cmd($cmd,$file,$isRELAT,$isMultiSec=FALSE,$rmHeader=1,$finalUTF8=TRUE) {
	global $io_options;
	global $dayFilter;

	file_put_contents('php://stderr', "\n -- ($cmd) $file\n");
	if ($isRELAT)	
		print "\n=== $cmd  $file ===";
	$dayFilter = isset($io_options['day'])? $io_options['day']: '';	
	$doc = new domParser();

	// FALTA usar a  $io_options['normaliza'] pro XML na lib.php 

	$doc->getHtmlBody($file, isset($io_options['utf8']) && $io_options['utf8']);
	$out = $doc->output($cmd,$finalUTF8,$dayFilter,$isMultiSec);
	
	if (!$isRELAT)	{
		if ($rmHeader) $out = str_replace(XML_HEADER1,'',$out);
		$out = trim($out);
	}

	if (!isset($io_options['breaklines'])) { // na verdade no-breaklines
		$out = str_replace(
			['<p',  '<div',  '<article',  '<sec',  '<keys', '<days'], 
			["\n\n\n<p","\n\n<div","\n\n<article","\n\n<sec","\n<keys","\n<days"],
			$out
		);
		$out = preg_replace("/[ \\t]*\n[ \\t]*/s","\n",$out); // trim nas quebras de linha 
	}
	$out = rmClosedFormatters($out); // repete limpeza já realizada no raw
	if (isset($io_options['entnum']))
		$out = utf2html($out);	
	return "$out\n";
}

?>
