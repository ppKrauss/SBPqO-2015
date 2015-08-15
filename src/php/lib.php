<?php
/**
 * RESUMOS, parser e analisador.
 * Uso:
 *  $ cd pdfGenerator/clientes/BOR/SBPqO_resumos
 *  $ php tools/main.php --help
 * FUTURO: armazenar itens no PostgreSQL 9.3+ (JSON!) e gerencia-los por lá.
 *   Por hora fazendo o possível com xsl_nRegister().
 */
$LIBVERS = '1.5.3'; // v1.5.2 de 2015-08; v1.5.0 de 2015-07; v1.4 de 2014-08-24; v1.3 de 2014-08-12; v1.2 de 2014-08-03; v1.1 de 2014-08-02; v1.0 de 2014-08-01.

///////  ///////  ///////  /////// 
/////// I/O INICIALIZATION ///////
$io_baseDir = realpath( dirname(__FILE__).'/..' );  // não usar getcwd();

// CONFIGS:
$modoAmostra   = FALSE;
$pastaDados    = $modoAmostra? 'amostras': 'entregas';
$fileAutores   = "$io_baseDir/../$pastaDados/CSV1-3/indiceAutores.csv"; // fora de uso, falta usar com resoluçao de homonimos!
$fileDescr     = "$io_baseDir/../$pastaDados/CSV1-3/indiceDescritores.csv";
$fileLocalHora = "$io_baseDir/../$pastaDados/CSV1-3/localHorario.csv";
$CSV_SEP=',';
$CSV_SEPaux = '/[\s,]+/s'; // sub-separador (auxiliar), ignorando subcampos vazios 
$fileFieldAu   = 'COD_AUTOR';
$fileField00   = 'COD_CHAVE';
$buffsize      = 3000;
$MODO          = 'extract';
$finalUTF8     = TRUE;

$SECAO         = array( // na ordem
	'PE'=> 'Pesquisa em Ensino',
	'PO'=> 'PROJETO POAC (Projeto de Pesquisa Odontológica de Ação Coletiva)',
	'PR'=> 'PRONAC - Prêmio Incentivo A Pesquisa - Produtos Nacionais',	
	'HA'=> 'UNILEVER Travel Award (Hatton)',
	'COL'=>'Prêmio COLGATE Odontologia Preventiva',
    'JL'=> 'Prêmio Joseph Lister',	
	'AO'=> 'Apresentação Oral',
	'FC'=> 'Fórum Científico',
	'PI'=> 'Painel Iniciante (prêmio Miyaki Issao)',	
	'PN'=> 'Painel Aspirante e Efetivo',
);

$FILTRO= [];
$FILTRO['regrasDefault'] = [ // CONFIGURAÇÃO DAS REGRAS DE NORMALIZAÇÃO:
	'NFC'=>TRUE, // por hora usar http://minaret.info/test/normalize.msp
	'eq1'=>TRUE, 'fmt1'=>TRUE, 'pm1'=>TRUE, 'pm2'=>TRUE, 'nbhy1'=>TRUE, 
	'perc1'=>TRUE, 'SBPqO-raiosx'=>TRUE, 'norm-sps'=>TRUE, 'SBPqO-apoio'=>1,
	'SBPqO-bugs1'=>TRUE,  // bugs de UTF8 remanescentes, e da fonte no PDF!
	'SBPqO-bugs2'=>FALSE, // bug do "Apoio:..." duplicado
];
/**$symbol_PDFbugs = array_map('html_entity_decode', [ // relatório PDF com hexadecimais. 
	// cairam apenas os diacrilicos, incluindo &#x0327;="̧"
 '&#x2082;', '&#x2070;', '&#x2076;', '&#xF0B0;', '&#x035E;', '&#;', '&#xF0D4;',
 '&#xF067;', '&#xF062;', '&#x0190;', '&#x0263;', '&#x1D43;', '&#x1D47;', '&#x1D9C;', '&#x1D52;', 
 '&#xF063;', '&#x03F0;', '&#x025B;', ''
]);  // ... eliminar diacrilicos (ver NFC), deixar apenas falhas de fonte do PDF/CSS atual.
*/


/**
 * Filtros para normalizar o conteudo do resumo e outros campos. Preferir uso no DOM sobfre #text.
 * @param $regrasTroca NULL ou array com regras a serem trocadas (ver FILTRO_regrasDefault). 
 *         acrescentar campo 'reset'=>1 para ter efeito de complemento.
 * @param $utfEncode=TRUE, não alterar, ficando só na filtragem de nodeValue DOM.
 * PS: um parâmetro "campo" (titulo, resumo, conclusao, key, local) poderia tb filtrar as regras.
 */
$FILTRO['func'] = function ($out,$regrasTroca=NULL,$utfEncode=TRUE) use (&$FILTRO) { 
	$RGA = $FILTRO['regrasDefault'];
	if ($regrasTroca!==NULL) {
		if (isset($regrasTroca['reset'])) foreach(array_keys($RGA) as $k) $RGA[$k]=FALSE;
		$RGA = array_merge($RGA,$regrasTroca);
		//var_dump($regrasTroca);
	}
	if ($RGA['NFC']) $out =  Normalizer::normalize($out); // normalize UTF8 diacrilics ("c+̧." to "ç")

	if ($RGA['norm-sps']) $out = preg_replace('/\s+/us',' ',$out); // normalize spaces

	if ($utfEncode) { // só funciona com tudo já convertido em UTF8, e #text sem tags XML
		$NBHYP = html_entity_decode('&#8209;'); // no-breaking hyphen
		$HNBSP = html_entity_decode('&#8239;'); // half NBSP
		$NBSP  = html_entity_decode('&nbsp;');

		if ($RGA['SBPqO-bugs1']) $out = str_replace( // trocas para limpar falhas da submissão SBPqO
			['', '˂', '˃', 'CO₂',           'ᵒC', 'C', '⁰',           'µƐ', 'µɛ',
			 '⁶',           'ᵃ',           'ᵇ',            'ᶜ', 
			 'ϰ',                  'ɛ',                  'Ɛ',               'ɣ'
			],
			['µ', '<', '>', 'CO<sub>2</sub>','°C', '°C', '<sup>0</sup>','µε', 'µε',
			 '<sup>6</sup>','<sup>a</sup>','<sup>b</sup>','<sup>c</sup>',
			 'χ','<SYMBOL>ɛ</SYMBOL>','<SYMBOL>Ɛ</SYMBOL>','<SYMBOL>ɣ</SYMBOL>'
			],  //  &gamma;==γ não ɣ
			$out
		);

		if ($RGA['eq1']) $out = preg_replace('/([\dpn])\s*(<|>|=)\s*([\dpn])/uis',"\$1$HNBSP\$2$HNBSP\$3",$out);
	} else {// (!$utfEncode), só funciona com XML, APOSENTAR!
		$NBHYP = '&#8209;'; // no-breaking hyphen
		$HNBSP = '&#8239;'; // half NBSP
		$NBSP  = '&nbsp;';		
		if ($RGA['fmt1']) $out = preg_replace('|<(su[bp])>(\s*)(.+?)(\s*)</\1>|is','$2<$1>$3</$1>$4', $out); // ex. <sub>10 </sub>
		if ($RGA['eq1'])  $out = preg_replace('/([\dpn])\s*(&lt;|&gt;|=)\s*([\dpn])/uis',"\$1$HNBSP\$2$HNBSP\$3",$out);
	}

	if ($RGA['pm1'])   $out = preg_replace('/(\d)(?:\s*±\s+|\s+±\s*)(\d)/us','$1±$2',$out); // sem &#8239;
	if ($RGA['pm2'])   $out = preg_replace('/±\s+/us','±', $out); // gruda a dirieta em "resultou em ± 2,5mm" ou "valores médios ± dp"
	if ($RGA['nbhy1']) $out = preg_replace('/(\d[º%]?)(?:\s*\-\s*)(\d)/us',"\$1$NBHYP\$2", $out);  // no break hyphen ERRO=MINUS SIGN ("−"=&#8722; não é "-")
	if ($RGA['perc1']) $out = preg_replace('/(\d)\s+%\s+/us','$1% ', $out); // ex "entre 32,7 % e 33,5%"
	//$out = preg_replace('/(?<=R)aios[\-\s]X(?=[\s\.,;])/is',"aios{$HNBSP}X",$out); // normalização do termo "Raios X"
	if ($RGA['SBPqO-raiosx']) $out = preg_replace('/(?<=R)aios[\-\s]X(?=[\s\.,;]|$)/uis',"aios{$NBSP}X",$out); // normalização do termo "Raios X"
	if ($RGA['SBPqO-bugs2'])  $out = preg_replace('/(?<=[\s\.])Apoio\s?:/is', ' ERRO-CLAUDIO-VER-AQUI:', $out);
		//  ex. Apoio: FAPESP (2013/12547‑4) e CNPq (444195/2014‑9). (Apoio: FAPESP - 2013/12547‑4) 
	if ($RGA['SBPqO-apoio'])  $out = preg_replace('/\( ?Apoio ?:(.+?)\)\s*$/is', '###funding-source: $1#_##', $out);
	// destaca da conclusao ou do abstract.
	return $out;
};

define ('XML_HEADER1', '<?xml version="1.0" encoding="UTF-8"?>');
//setlocale (LC_COLLATE, 'pt_br');
date_default_timezone_set('Brazil/East');
setlocale(LC_ALL,'pt_BR.UTF8'); // ou setlocale( LC_ALL, 'pt_BR.utf-8', 'pt_BR', 'Portuguese_Brazil');
mb_internal_encoding('UTF8'); 
mb_regex_encoding('UTF8');




$NTOTAL        = 0;
$dayFilter     = $dayLocais = ''; // para secao corrente
// REVISAR NECESSIDADE DESSAS GLOBAIS E SUA UTILIZACAO
$DESCRITORES      = array();
$DESCR_byResumo   = array();
$DESCR_bySec      = array();
$DESCR_resumoList = array();
$LocHora_byResumo = array();
$Resumos_byDia    = array();
$dayLocais_bySec  = array();
$ctrl_idnames     = array(); // cria e controla IDs
$Locs 			  = array();

//////////////

/**
 *  CARGA DOS DESCRITORES DE CADA RESUMO:	
 */
if (csv_get(
	$fileDescr
	,'COD_CHAVE'
	,function ($tmp) use (&$DESCRITORES,&$DESCR_byResumo,$CSV_SEPaux) {
    	$resumos = preg_split($CSV_SEPaux, $tmp[2]);
    	$DESCRITORES[$tmp[1]] = $resumos;
    	foreach ($resumos as $rid) {
    		if (isset($DESCR_byResumo[$rid]))
    			$DESCR_byResumo[$rid][] = $tmp[1];
    		else
    			$DESCR_byResumo[$rid] = array($tmp[1]);
    	}
	} // func
)) {
    foreach ($DESCR_byResumo as $rid => $lst) { // ordena itens
    	$lst = array_unique($lst);
    	usort($lst,'strcoll');
    	$DESCR_byResumo[$rid] = $lst; // join('; ',$lst);
    }
}

/**
 * CARGA DE LOCAL E HORA DE CADA RESUMO:	
 */
if (csv_get(
	$fileLocalHora
	,'SIGLA'
	,function ($tmp) use (&$Locs,&$LocHora_byResumo,&$Resumos_byDia,&$dayLocais_bySec) {
    	//  0=rid; 1=dia;     2=intervalo;    3=local		
    	//Exemplo:
    	// AO0002,9/4/2015,8:00 - 11:30 h,Sala Amoreira I
    	// PR0001;2014-09-03;8:30 às 12:00 h;Sala Novara – 10º andar

    	$rid = $tmp[0]; // 2015 requer padrã de entrada;  preg_replace('/0(\d\d\d)/','$1',$tmp[0],1); // remove 0s em excesso    	
    	$hini = $hfim = $hini2 = $hfim2 ='';
    	if (preg_match('#^\s*([\d:]+)[^\d:]+([\d:]+)[\shs]*/\s*([\d:]+)[^\d:]+([\d:]+)[\shs]*$#su', $tmp[2], $m)) {
			// 9:00 – 12:00 h / 13:30 - 17:30 (caso exótico!)
    		list($hini,$hfim,$hini2,$hfim2) = array($m[1],$m[2],$m[3],$m[4]);
			$hini = "$hini;$hini2";
			$hfim = "$hfim;$hfim2";
    	} elseif (preg_match('/^\s*([\d:]+)[^\d:]+([\d:]+)[ hs]*$/su', $tmp[2], $m))
    		list($hini,$hfim) = array($m[1],$m[2]);  // caso usual
    		$local = preg_replace('/([\-–])/u', ' $1 ', $tmp[3]);
    		$local = trim(preg_replace('/\s+/', ' ', $local));
    	$Locs[$local]=1;
		list($dayISO,$dayOrig)=dayFormat($tmp[1]);    		
    	$LocHora_byResumo[$rid] = array($dayISO,$hini,$hfim,$local); // dia, hora-inicial, final, local
    	if (!isset($Resumos_byDia[$dayISO]))
    		$Resumos_byDia[$dayISO]=array();
    	$Resumos_byDia[$dayISO][] = $rid;

    	$sec = xsl_splitSecao($rid,1);
    	$daysec = "$dayISO#$sec";
    	if (!isset($dayLocais_bySec[$daysec]))
    		$dayLocais_bySec[$daysec]=array();
    	$dayLocais_bySec[$daysec][$local] = 1;
	} // func
	,2
	,TRUE
)) {
    foreach($dayLocais_bySec as $daysec=>$a)
    	$dayLocais_bySec[$daysec] = join('; ',array_keys($a));
}

/**
 * CARGA DE OPTIONS:	
 */
list($io_options,$io_usage,$io_options_cmd,$io_params) = getopt_FULLCONFIG(
	array(
	    "1|relat1*"=>	'shows a input analysis partial relatory',
	    "2|relat2*"=>	'shows a input analysis complete relatory, listing elements',
	    "3|relat3*"=>	'shows a ID list',
	    "l|local"=>     'shows all local ids',

	    "r|raw*"=>     	 'outputs RAW input HTML',	// use http://www.w3.org/TR/html-polyglot/
	    "x|xml*"=>     	 '(default) outputs a raw (non-standard) XML format, for debug',	
	    "m|finalXml*"=>	 'outputs a final (standard) XML JATS-like format',	
	    "l|finalHtml*"=> 'outputs a final (standard) XHTML format',	

	    "o|out:file"=>		'output file (default STDOUT)',
	    "f|in:file_dir"=> 	'input file or directory (default file STDIN)',
	    "e|entnum"=>    	'outputs special characters as numeric entity',
	    "u|utf8"=>    		'check and convert input to UTF-8 encode',
	    "k|breaklines"=>  	'(finalHtml) outputs without default filter of breaking lines',
	    "n|normaliza"=>  	'(finalHtml) outputs with units normalization',
	    "p|firstpage:value"=>  	'(finalHtml) first page (default 1)',
	    "d|day:value"=>  	'to select only data for one day (see xml). YYYY-MM-DD format.',

	    "v|version*"=>	'show versions',
	    "h|help*"=>   	'show help message',
	),

	"\nUsage: 
   php main.php [options] [--] [args...] [-f] {\$file} [-o] {\$file} 
   php assert.php [options] [--] [args...] [-f] {\$file} 
   php main.php [rexml] < {\$file_input} > {\$file_output}
   php assert.php [rexml] < {\$file_input}

   -f <file>
   --in=<file>  _MSG_
   -o <file>
   --out=<file>	_MSG_
   _DESCR_OPTIONS_

   -v
   --version	_MSG_
   -h
   --help   	_MSG_
   \n"
);

/**
 * Extensão da função getopt() para configuração integral das mensagens e validações. 
 * @param io_longopts_full array(opt=>descricao). Cada opt respeitando a seguinte sintaxe,
 *   	  <opt> "|" <longopt> [":"<obj>|"::"<obj>] ["*"]
 *        Onde <opt> é uma letra, <longopt> uma palavra, <obj> um indicador "file" ou "dir", 
 *        "::" opcional, ":" obrigatorio, "*" indica que é um comando (nao apenas opt de io).
 */
function getopt_FULLCONFIG(
	$io_longopts_full, // array
	$io_usage    // string
) {
	$io_isCmd    = array();
	$io_longopts = array();
	$io_params   = array();
	$io_stropts = '';
	foreach($io_longopts_full as $k=>$v) {
		if (preg_match('/^([a-z0-9])\|([^:\*]+)(:[^\*]+)?(\*)?$/i',$k,$m)) {  	// ex. "o|out:file"
			$k2 = $m[2];
			$io_optmap[$m[1]] = $k2;
			if (isset($m[3]) && $m[3]) {
				$flag = (substr($m[3],0,2)=='::')? '::': ((substr($m[3],0,1)==':')? ':': '');
				$opt = $flag? str_replace($flag,'',$m[3]): '';
				$io_params[$k2]=$m[3];
			} else
				$flag = '';
			$io_stropts .= "$m[1]$flag";
			if (isset($m[4])) // indicador de comando
				$io_isCmd[]=$k2;
		} else  // CUIDADO, não esta tratando  ex. "out:file"
			$k2=$k;  // $io_params[$k] = '';
		$io_longopts["$k2$flag"]=$v;
	}
	$io_usage   = getopt_msg($io_usage,$io_longopts);
	$io_options = getopt($io_stropts, array_keys($io_longopts)); // is for terminal; for http uses $_GET of the same optes
	$io_options_cmd = array();
	foreach ($io_options as $k=>$v) {
		if ($v===false)  // facilitador de pesquisa	
			$io_options[$k]=true;
		if ( isset($io_optmap[$k]) ) {   // normalizador (para long-options)
			unset($io_options[$k]);
			if ( !isset($io_options[$io_optmap[$k]]) )
				$io_options[$io_optmap[$k]]=true;
		}
	}
	foreach ($io_options as $k=>$v) // preserva apenas os comandos
		if (in_array($k,$io_isCmd))
			$io_options_cmd[] = $k;	
	return array($io_options,$io_usage,$io_options_cmd,$io_params);
}


$io_isTerminal =  (isset($argv[0]) && isset($argv[1]));
if (!$io_isTerminal) {
	die("GET options em construção");
}

/////// END I/O INICIALIZATION ///////
///////   ///////   ///////   /////// 


/**
 * Parsem, replacing placeholder template ($tpl) _MSG_ variables by respective's line --KEY $longopts VALUES.
 * Make a internal copy of $longopts and changes it... Replaces also the $descNameRef placeholder.
 * @param $tpl string template
 * @param $longopts array options
 * @param $descNameRef internal definition for full-description's placeholder.
 */
function getopt_msg($tpl,$longopts,$sp1='   ',$descNameRef='_DESCR_OPTIONS_') {
	$tpl = preg_replace_callback(
		'/\-\-([a-zA-Z_0-9]+)(.+?)(_MSG_)/s',
		function($m) use (&$longopts) {
			if (isset($longopts[$m[1]])) {
				$s = $longopts[$m[1]];
				unset($longopts[$m[1]]);
			} elseif (isset($longopts["$m[1]:"])) {
				$s = $longopts["$m[1]:"];
				unset($longopts["$m[1]:"]);				
			}
			if ($s)
				return "--$m[1]$m[2]$s";			
			else
				return $m[1].$m[0];
		},
		$tpl
	);  // if remain longopts, continue...
	if ( strpos ($tpl,$descNameRef)!==false ){ // placeholder exists?
		$DESCR = '';
		$fill = 16;
		foreach ($longopts as $k=>$v) if ($k) {
			//if (preg_match('/^([a-z0-9])\|([^:\*]+)(:[^\*]+)?(\*)?$/i',$k,$m)) {
			// $DESCR.="\n$sp1-$m[1]\n\t--".str_pad($m[2],$fill ,' ');
			$DESCR.="\n$sp1--".str_pad($k,$fill ,' ');
			$DESCR.=$v;
		}
		$tpl = str_replace($descNameRef,$DESCR,$tpl);
	}
	return $tpl;
}
/*
	 OUTROS SUBSIDIOS PARA O "PROJETO GETOPT-WSDL":
	 Revisar: 
	  https://github.com/ulrichsg/getopt-php
	  http://pear.php.net/package/Console_GetoptPlus  
	  https://github.com/hguenot/GWebService/blob/master/command/WsdlCommand.php
	    https://github.com/hguenot/GWebService/blob/master/command/wsdl/assets/Getopt.php
	  Outras refs:
	    http://stackoverflow.com/a/1023142/287948
	    https://github.com/jcomellas/getopt
*/


//////// LIB ////////

/**
 * DOMDocument parser, converts to XML SJATS format.
 */
class domParser extends DOMDocument { // refazer separando DOM como no RapiDOM!
	public $contentArray=array();
	public $css;
	public $newDom = NULL;
	public $isXML_step1 = false; // se true significa que não é HTML

	private $nodePathes = array(
		'BRs'=>'//br',
		'paragrafos P'=>'//p',
		'  vazios'=>'//p[not(normalize-space(.))]',		
		'  não-vazios'=>'//p[string-length(string(.))>0]',
		'    com 200+ letras'=>'//p[string-length(string(.))>200]',
		'    com 1800+ letras'=>'//p[string-length(string(.))>1800]',
		'    com 2500+ letras'=>'//p[string-length(string(.))>2500]',
		'  com BR'=>'//p[.//br]',
		'    com 5 BRs'=>'//p[count(.//br)=5]',
		'    com 6 BRs'=>'//p[count(.//br)=6]',
		'    com 7 BRs'=>'//p[count(.//br)=7]',
		'    com 8 BRs'=>'//p[count(.//br)=8]',
		'  com span'=>'//p[.//span]',
		'    com span.class'=>'//p[.//span/@class]',
		'  com formatação (I,B,etc.)'=>'//p[.//i or .//em or .//b or .//strong or .//sup or .//sub]',
		'itens (volume em nodes)'=>'//node()',
	);
	private $CRLFs = "\n\n";

	/**
	 * Get HTML body from any HTML file. Use show_*() methods to check it before to use. 
	 */
	function getHtmlBody($fileOrString, $enforceUtf8=FALSE, $rmLF=TRUE) {
		$this->resolveExternals = true;
		$this->preserveWhiteSpace = false;  // com false baixou de 1510 nodes para 1101, ou seja ~25% num JATS tipico.
		// ver também DTD HTML e  xml:space="preserve"

	  	if ((strlen($fileOrString) < 300) && (strpos($fileOrString,'<') === false))
	  		$fileOrString = file_get_contents($fileOrString);
if (0 && $enforceUtf8) {
			$enc = mb_detect_encoding($fileOrString,'ASCII,UTF-8,UTF-16,ISO-8859-1,ISO-8859-5,ISO-8859-15,Windows-1251,Windows-1252,ISO-8859-2');
			if ($enc!='UTF-8') // ex. ISO-8859-1  Windows-1251,Windows-1252
				$fileOrString = mb_convert_encoding($fileOrString,'UTF-8',$enc); //$enc);
		}
		$this->encoding = 'UTF-8';
		if ($rmLF) $fileOrString = str_replace (array("\r\n","\r"), "\n", $fileOrString);
		if (!preg_match('/^\s*<\?xml\s/',$fileOrString)) {
			$cc=0;// IMPORTANTE O META, SENAO loadHTML IGNORA!!
			$fileOrString = preg_replace(
				'/(<meta)/is', 
				'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />$1', 
				$fileOrString, 1, $cc
			);  // garante UTF8 do XHTML. Cuidado, vale apenas (X)HTML, não outros como body isolado. 
			if (!$cc)
			    $fileOrString = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'.$fileOrString;
			$this->recover =true;
			$fileOrString = str_replace('<0','&lt;0',$fileOrString); // GAMBI! Tidy!
			// tratar demais casos de lt gt incorretos!		
			@$this->loadHTML($fileOrString, LIBXML_NOWARNING | LIBXML_NOERROR);
			$this->encoding = 'UTF-8';

			$this->css  =  	$this->getElementsByTagName('style')->length? 
								$this->getElementsByTagName('style')->item(0)->textContent: 
								'';
			if ($this->getElementsByTagName('body')->length) {
				$XML = 	$this->saveXML( $this->getElementsByTagName('body')->item(0) );
				$XML = preg_replace('#^\s*<body[^>]*>|</body>\s*$#','',$XML);
				$XML = "<html>$XML</html>";
			} else
				$XML = 	$this->saveXML($this->documentElement); // html root?					
		} else {
			$XML = &$fileOrString; // veio mesmo XML
			$this->isXML_step1 = true;
		}
		if (trim($XML)) {
			$this->recover =true;
			$this->loadXML($XML);
			$this->encoding = 'UTF-8';
			$this->normalizeDocument();
			return true;
		} else
			return false;
	}

	function setItem(&$list,$p) {
		if (!isset($list[$p])) $list[$p]=0; 
		$list[$p]++;
	}

	function show_nodePathes() {
		print "{$this->CRLFs}---- show_nodePathes nodePathes and attribs: ---";
		$list = array();
		$attr = array();
		$xp = new DOMXpath($this);
		foreach($xp->query("//*") as $node) {
			$p = preg_replace('/\[\d+\]/','',$node->getNodePath());
			$this->setItem($list,$p);
			$s = $node->getAttribute('class');
			if ($s) $this->setItem($list,"$p.class($s)");
		}
		foreach($list as $k=>$v)
			print "\n\t$k=$v";
	}

	function show_xpathes() {
		print "{$this->CRLFs}---- show_xpathes count types: ---";
		$xp = new DOMXpath($this);
		foreach($this->nodePathes as $rotulo=>$path)
			print "\n\t$rotulo: ".$xp->evaluate("count($path)");
	}

	function show_cssParts() {
		print "{$this->CRLFs}---- show_cssParts relevant lines: ---";
		foreach (explode("\n",$this->css) as $line)
		  if (preg_match('/font\-style|vertical\-align|font\-family\s*:[^\}]*SYMBOL/is',$line))
			print "\n\t".trim($line);
	}

	function show_extractionSumary($onlyIds=false) {
		$IDs = array();
		$RELAT = "{$this->CRLFs}---- show_extractionSumary: ---";
		$xp = new DOMXpath($this);
		$n=0;
		foreach($xp->query('//p') as $node) {
			$id='';
			$n++;
			$txt = $node->nodeValue;
			if (preg_match('/^\s*([A-Z]+\-?[a-z]?[0-9]{1,4})/su',$txt,$m) && $id=$m[1]) {
				$ntype = array();
				$first=''; // can be mixed with some tag like bolds, when fails
				foreach($xp->query('.//node()',$node) as $subnode) { // ERRO, não é um taverse, requer recorrencia.
					$this->setItem( $ntype, $subnode->nodeName ); // .{$subnode->nodeType}
					if (!$first && '#text'==$subnode->nodeName) $first=trim($subnode->nodeValue);
				}
				$RELAT.= "\n\tparagraph $n, pubid $first: ";
				// if (!$first || substr($id,0,strlen($first))!=$first) // side effect by title's digits
				if ($id!=$first) {
					$dump = mb_substr($txt,0,25,'UTF-8');
					$RELAT.= " !diff id $first!=$id! (dump: $dump)";
				}
				$id=$first;
				$IDs[]=$id;
				foreach ($ntype as $k=>$v) $RELAT.= "$k=$v; ";
			} else 
				$RELAT.= "\n\t!paragraph $n ERROR! content of 30 firsts: '".substr($txt,0,30)."'";
		}
		print $onlyIds? ("\n".join('; ',$IDs)."\n TOTAL $n\n") : $RELAT;
		global $NTOTAL;
		$NTOTAL+=$n;
		return '';
	}

	function setIdname($idname,$item,$withPrefix=TRUE) {
		global $ctrl_idnames;
		if (!array_key_exists($idname,$ctrl_idnames))
			$ctrl_idnames[$idname] = array(1,array()); // contador e lista de ocorrências
		if (isset($ctrl_idnames[$idname][1][$item])) 
			$id = $ctrl_idnames[$idname][1][$item];
		else {
			$id = $ctrl_idnames[$idname][1][$item]=$ctrl_idnames[$idname][0];
			$ctrl_idnames[$idname][0]++;
		}
		return $withPrefix? "$idname$id": $id;
	}

	function joinMarkId($lst, $idname='loc', $elename='location', $errUse=TRUE, $SEP='', $errTag='error') {
		global $ctrl_idnames;
		$outLst = array();
		if (count($lst)) {
			foreach($lst as $item) {
				$idr = domParser::setIdname($idname,$item,TRUE);
				if ($item=='err') showerr(32,"joinMarkId() recebeu erro em idr=$idr.");
				$out[] = "<$elename idref='$idr'>$item</$elename>";
			}
			return join($SEP,$out);
		} else {
			return $errUse? "<$errTag/>": '';
		}
	}


	// gera step1
	// NAO USA MAIS dayFilter, arrumar
// PERIGO, tem dados de 2014 forçados (ver sec=='PN')
	function asXML($dayFilter='') {
		global $Resumos_byDia;
		global $DESCR_byResumo;
		global $LocHora_byResumo;
		global $FILTRO;
		if (!$this->isXML_step1) {
			$XML = "\n";
			// DEPOIS AINDA PASSAR POR UM NORMALIZADOR XSLT!
			$replacElements = array('pubid','title','contribs','aff','corresp','abstract','conclusion','ERRO');
			$replacElements_n = count($replacElements);
			$this->newDom = new DOMDocument();
			$root = $this->newDom->createElement('root');
			$this->preserveWhiteSpace = false; // XML_PARSE_NOBLANKS
			$xp = new DOMXpath($this);
			$n=0;
			$nOk=0;
			$secs = array();
			$subsecs = array();
			$dias = array();
			$locais = array();		
			foreach($xp->query('//p') as $node) {
				$id='';
				$n++;
				if (  preg_match('/^\s*(([A-Z]+)(\-?[a-z]?)[0-9]{1,5})/su',$node->nodeValue,$m) 
					  && ($id=$m[1])
					) {
					// fora de uso && (!$dayFilter || in_array($id,$Resumos_byDia[$dayFilter]))
					$sec = $m[2];
					$secs[$sec] = 1;
					$subsecs[$m[3]] = 1;
					$nOk++;
					$DESCR = isset($DESCR_byResumo[$id])? $DESCR_byResumo[$id]:  array("(sem descritor de assunto)");
					if (!isset($LocHora_byResumo[$id]))
						list($dia,$hini,$hfim,$local) =  array('err','err','err','err');
					else
						list($dia,$hini,$hfim,$local) = $LocHora_byResumo[$id]; // 0=dia, 1=hora-inicial, 2=final, 3=local
					$dias[$dia] = 1;
					$locais[$local] = 1;
					$nEle = $ntexts = 0;
					$nEle_name = $replacElements[0];

					$auxDom = new DOMDocument();
					$art = $auxDom->createElement('article');
					$ele = $auxDom->createElement($nEle_name);
					$art->appendChild($ele); // o primeiro já é iniciado

					$ele2 = $auxDom->createDocumentFragment();
					$per2 = '';
					if (strpos($hini,';')!==false) {
						list($hini,$hini2) = explode(';',$hini);
						list($hfim,$hfim2) = explode(';',$hfim);

						$per2 = "<period><start day=\"$dia\">$hini2</start><end>$hfim2</end></period>";
					}
					$event2='';
					$idloc = domParser::setIdname('loc',$local,TRUE);
					if 	($sec=='PN') { // faz uso de dois locais!
						$event2 = "<event2><summary>Reunião de Grupo</summary><period><start day=\"$dia\">17:30</start><end>19:00</end></period><location idref='$idloc'>$local</location></event2>";
						$local = "Salão Monumentalle";
						$idloc = domParser::setIdname('loc',$local,TRUE);	
					}
					$ele2->appendXML(
						"<vcalendar><components>"
						."<period><start day=\"$dia\">$hini</start><end>$hfim</end></period>"
						.$per2
						."<location idref='$idloc'>$local</location>"
						.$event2
						."</components></vcalendar>"
					);
					$art->appendChild($ele2); // o primeiro já é iniciado

					$ele2 = $auxDom->createDocumentFragment();
					$ele2->appendXML( '<keys>'.domParser::joinMarkId($DESCR,'k','key',0).'</keys>' );
					$art->appendChild($ele2);

					foreach ($node->childNodes as $subnode) {
						// PARSER: split by BR, analyse and add elements
						$nname = $subnode->nodeName;
						if ($nname!='br') {
							if ($nname=='#text') { // text-node
								$text = $subnode->nodeValue; // normalize spaces
								if ( in_array($nEle_name,['abstract','conclusion']) )
									$text = $FILTRO['func'](
										$text
										,($nEle_name=='conclusion')? // Decide tipo de normalização:
											['SBPqO-bugs2'=>1]:	// tudo+bugs2.
											NULL    			// tudo.
										,1 // text é UTF8 direto e sem tags.
									);
								elseif ($nEle_name=='title')
									$text = $FILTRO['func']($text, ['SBPqO-raiosx'=>1,'norm-sps'=>1,'reset'=>1], 1);

								if ($ntexts) // second text or more:
									$ele->appendChild( $auxDom->createTextNode($text) );								
								else {		// first text:
									$ntexts=1;
									$ele->appendChild( $auxDom->createTextNode(rtrim($text)) );
								}
							} else {  // demais nodes
								$imp = $auxDom->importNode($subnode, true);
								$ele->appendChild($imp);							
							}
						} else {
							$nEle++; // next nEle_name (may use array funcs)
							$ntexts=0;
							if (!isset($replacElements[$nEle]) || $replacElements[$nEle]=='ERRO') {
								$nEle_name = 'ERRO';
								$ele = $auxDom->createElement($nEle_name);														
								$ele->setAttribute('linha',$n);
								$ele->setAttribute('tipo',"BR $nEle imprevisto");
							} else{
								$nEle_name = $replacElements[$nEle];								
								$ele = $auxDom->createElement($nEle_name);
							}
							$art->appendChild($ele);
						} // else
					} // for childNodes
					$P = $auxDom->saveXML($art);
					if ($FILTRO['regrasDefault']['SBPqO-apoio'] && preg_match('/###funding-source: (.+?)#_##/s',$P,$m)) {  
						$fund = trim($m[1]);
						$P = preg_replace('/###funding-source: .+?#_##/s','',$P);
						$P = str_replace('</article>',"<funding-source>$fund</funding-source></article>",$P);
					}
					$XML.= "\n\n$P";
				} // if
			} //for

			$locais = array_keys($locais);
			$local = domParser::joinMarkId($locais,'loc','location',1); // xml

			$dias = array_keys($dias);
			$ndias = count($dias);
			$dia = domParser::joinMarkId($dias,'d','day',1); // nao precisa id-sequencial (!) pois iso é ref. 

			$secs = array_keys($secs);
			$sec = (!count($secs) || count($secs)>1)? 'ERROR': $secs[0];
			$subsecs = array_keys($subsecs);		
			$subsec = (count($subsecs))? $subsecs[0]: '';	
			global $SECAO;
			$SECAO_ordem=array_flip(array_keys($SECAO)); // ex. $SECAO_ordem['PR']==2.
			$title = isset($SECAO[$sec])? $SECAO[$sec]: 'ERROR';			
			$sord = isset($SECAO_ordem[$sec])? $SECAO_ordem[$sec]: 'ERROR';
			//if ($subsec) $title.=", Parte \"$subsec\"";
			$err = ($n!=$nOk)? "<ERRO-GRAVE>lidos $n paragrafos, usados $nOk!</ERRO>":'';
			$XML = "<sec id=\"$sec$subsec\" label=\"$sec\" sec-order=\"$sord\" subsec=\"$subsec\" sec-type=\"modalidade\">
				$err
				<title>$sec$subsec - $title</title>
				<days n='$ndias'>$dia</days>
				<locations>$local</locations>
				\n$XML\n</sec>\n";
			return $XML;

		//} elseif ($dayFilter) { // já é XML, falta só grep por dia
			// nao precisou pois finalXml faz grep!
		} else
			return $this->saveXML();

	} // func


	/**
	 * Saída XML-padrão (campos normalizados, tags e atributos padronizados).
	 * Traduz a saída do método asXML() para quase que um JATS.
	 * @param $MODO string 'dom' ou 'xml', designa o tipo de retorno.
	 * @param $p_dayFilter string empty or selected day (ISO format?).
	 */
	function asStdXML($MODO='dom',$p_dayFilter='') {
		// criar boolean para match="article[fn:useThisId(string(@id),local,dia, keys)]"
		// vai listar em array apenas os resumos e seu local e dia
		$XSL = "<xsl:param name=\"dayFilter\">$p_dayFilter</xsl:param>\n";
		$XSL .= <<<'EOD'
			<xsl:template match="ERRO[@tipo='BR 7 imprevisto']" /><!-- limpa warnings -->

			<xsl:template match="/">
				<html>
				<xsl:apply-templates select=".//sec">
					<xsl:sort select="@sec-order" data-type="number" />
					<xsl:sort select="@subsec" />
				</xsl:apply-templates>
				</html>
			</xsl:template>

			<xsl:template match="sec">
				<xsl:if test="$dayFilter='' or ./days/day=$dayFilter">
				  <xsl:copy><xsl:copy-of select="@*"/>
				  	<xsl:apply-templates select="title"/>
					<xsl:choose>
						<xsl:when test="$dayFilter=''">
						    <xsl:apply-templates select="days|locations"/>
						    <keys todo="1"/>		<!-- must be unique and ordered -->						    
					    	<xsl:apply-templates select=".//article"/>
						</xsl:when>
						<xsl:otherwise>
						    <days><day><xsl:value-of select="$dayFilter" /></day></days>
						    <locations todo="1"/>	<!-- must be unique and ordered -->
						    <keys todo="1"/>		<!-- must be unique and ordered -->
						    <xsl:apply-templates select=".//article[.//start/@day=$dayFilter]"/>
						</xsl:otherwise>
					</xsl:choose>
				  </xsl:copy>
				</xsl:if><!-- else discard -->
			</xsl:template>

			<!-- some article elements: -->

			<xsl:template match="article">
				<xsl:copy>
					<xsl:copy-of select="@*"/>
					<xsl:attribute name="id"><xsl:value-of select="./pubid" /></xsl:attribute>
					<xsl:attribute name="secid">
						<xsl:value-of select="fn:function('xsl_splitSecao',string(./pubid),-1)" />
					</xsl:attribute>
					<xsl:apply-templates/>
				</xsl:copy>
				<!-- unique registering: -->
				<xsl:copy-of select="fn:function('xsl_nRegister', 'ev2', string(./pubid), .//event2/location)" />				
				<xsl:copy-of select="fn:function('xsl_nRegister', 'loc', string(./pubid), .//components/location)" />
				<xsl:copy-of select="fn:function('xsl_nRegister', 'key', string(./pubid), .//key)" />				
			</xsl:template>

			<xsl:template match="aff"><!-- perigo, agrupar sob artigo -->
				<aff-group><xsl:copy-of select="." /></aff-group>
			</xsl:template>

			<xsl:template match="contribs">
				<xsl:copy-of select="fn:function('xsl_splitContrib',.)" />
			</xsl:template>

			<xsl:template match="corresp">
				<xsl:copy-of select="fn:function('xsl_markCorresp',.)" />
			</xsl:template>
EOD;
		// <vcalendar xmlns='urn:ietf:params:xml:ns:xcal'>  ver https://tools.ietf.org/html/rfc6321 
		$xmlDom = new DOMDocument('1.0', 'UTF-8');
		$xmlDom->loadXML( $this->asXML($p_dayFilter) );  // redundancia se já no this.
		$xmlDom->encoding = 'UTF-8';
		$xmlDom = transformId_ToDom($XSL,$xmlDom);
		$xmlDom->encoding = 'UTF-8';

		$XSL= '
			<xsl:template match="sec[not(.//article)]|subsec[not(.//article)]" priority="9"/>

			<xsl:template match="sec/keys[@todo]">
				<xsl:copy-of select="fn:function(\'xsl_regRestore\',\'key\', string(../@id))" />
			</xsl:template>
			<xsl:template match="sec/locations[@todo]">
				<xsl:copy-of select="fn:function(\'xsl_regRestore\',\'loc\', string(../@id))" />
			</xsl:template>
			<xsl:template match="sec/days/day">
				<xsl:copy-of select="fn:function(\'xsl_dayFormat\',string(.))" />
			</xsl:template>			

		';
		$xmlDom = transformId_ToDom($XSL,$xmlDom);
		$xmlDom->encoding = 'UTF-8';
		//global $registerLists; var_dump($registerLists); 
		//die('DEBU11:'.$xmlDom->saveXML());

		return ($MODO=='dom')? $xmlDom: $xmlDom->saveXML();
	}



	/**
	 * Saída XHTML controlada.
	 * @param $MODO string 'dom' ou 'xml', designa o tipo de retorno.
	 * @param $xmlDom DOMDocument, reusa a saida de asStdXML('dom'). 
	 */
	function asStdHtml($MODO='xml', $xmlDom=NULL, $allFlor=true, $dayFilter='') {

		if ($MODO=='dom')
			die("ERRO3844: modo DOM desativado.");
		if ($xmlDom===NULL)
			$xmlDom = $this->asStdXML('dom',$dayFilter);
		$XSLfile = $dayFilter? 'resumosS1_toHtmlF1day': 'resumosS1_toHtmlF2all';
// PERIGO, REVISAR PATH com configs
		$xmlDom = transformToDom("src/xsl/$XSLfile.xsl",$xmlDom); // transformId_ToDom($XSL,$xmlDom);
		$xmlDom->encoding = 'UTF-8'; // importante para saveXML nao usar entidades.
		if ($MODO=='xml'){
			// GAMBI1: com o XSLT transformando &#160; em branco comum, foi preciso gambiarra! ver ♣.
			// GAMBI2: o certo era percorrer elementos e alterar textos por DOM... string XML mais facil. 
			$xml = $xmlDom->saveXML();
			if ($allFlor)
				$xml = str_replace('♣','&#160;',$xml);
			else // evita risco de remover flor que realmente era flor, verifica apenas entre-nomes.
				$xml = preg_replace('/(?<=\p{L})♣(?=\p{L})/us', '&#160;', $xml);
			return $xml;
		} else
			$xmlDom;
	}


	function output($MODO,$finalUTF8=true,$dayFilter) {
		$MODO = strtolower($MODO);
		if ($MODO=='relat3')
			return $this->show_extractionSumary(true); // faz print

		elseif ($MODO=='relat1' || $MODO=='relat2') {
			$this->show_nodePathes();
			$this->show_cssParts();
			$this->show_xpathes();
			if ($MODO=='relat1')
				$this->show_extractionSumary(); 
			return ''; // já foi por print

		} elseif ($MODO=='raw') {
			$this->preserveWhiteSpace=FALSE;
			$this->formatOutput=TRUE;
			$this->encoding = 'UTF-8';
			// falta template HTML5 http://www.w3.org/TR/html-polyglot/
			return $this->saveXML();

		} elseif ($MODO=='xml')
			return $this->asXML($dayFilter);

		else {
			$xmlDom = $this->asStdXML('dom',$dayFilter);

			if ($MODO=='finalhtml') {
				return $this->asStdHtml('xml',$xmlDom, true, $dayFilter);

			} elseif ($MODO=='finalxml'){
				if ($finalUTF8)
					$xmlDom->encoding = 'UTF-8';
				return str_replace(  // GAMBI!! mas ok
					['&lt;SYMBOL&gt;','&lt;/SYMBOL&gt;'],
					['<SYMBOL>','</SYMBOL>'],
					$xmlDom->saveXML()
				);

			} else 
				die("\nERRO2: MODO $MODO DESCONHECIDO\n");
		}
	} // func

} // class


/////////  // APOIO XSL:

/**
 * Retorna parte desejada do ID de resumo.
 * @param $idx -1=sec+subsec, 1=sec, 2=subsec, 3=locid, 4=nome completo da sec.
 * Dependences: xsl_getSecao().
 */
function xsl_splitSecao($s,$idx) {
	if (preg_match('/^\s*([A-Z]+)\-?([a-z]?)([0-9]{1,5})\s*$/su',$s,$m)) {
		if ($idx>3)
			return xsl_getSecao($secid);
		elseif ($idx<0)
			return $m[1].$m[2];	
		else
			return $m[$idx];
	} else
		return $s;
}

function xsl_getSecao($secid) {
	global $SECAO;
	return isset($SECAO[$secid])? $SECAO[$secid]: '';
}


/**
 * Registra unique-string em array, para elementos válidos como string.
 * Uso em location e key.
 */
function xsl_nRegister($type,$pubid,$items) {
	// falta o ID no caso de keys
	global $registerLists;
	$tcExp = '';
	if ($type=='ev2') $tcExp = $type = 'loc';
	$secid = xsl_splitSecao($pubid,-1);
	$tsid = "$type#$secid";
    if (!isset($registerLists[$tsid]))
		$registerLists[$tsid]=array();
	foreach($items as $ele) {
		$tc=$ele->textContent;
		if ($tcExp)
			$tc = "(reunião de grupo 17:30h) $tc";
	    if (!isset($registerLists[$tsid][$tc]))
			$registerLists[$tsid][$tc]=array();
		$registerLists[$tsid][$tc][]=$pubid;
	}
    return NULL;//$ele;
}
/**
 * Restaura tag com itens registrados por xsl_nRegister().
 */
function xsl_regRestore($type,$secid){
	global $registerLists;
	$tsid = "$type#$secid";	
	$keys = array_unique( array_keys($registerLists[$tsid]) );
	usort($keys,'strcoll');
	$k= array();
	switch ($type) {
	case 'key': // keys
		foreach ($keys as $t) {
			$idk = domParser::setIdname('k',$t,TRUE);
			$s = join(', ',$registerLists[$tsid][$t]);
			$k[] = "<key pubid-list=\"$s\" id=\"$idk\">$t</key>";			
		}
		return DOMDocument::loadXML( '<keys>'.join('',$k).'</keys>' );
		break;
	case 'loc': // locations
		$s = join('</location><location>',$keys);
		return DOMDocument::loadXML( "<locations><location>$s</location></locations>" );
		break;
	default:
		return NULL;
	}
}

function dayFormat($s){
	$s2='';
	if (preg_match('/(\d\d+)\-(\d*)\-(\d*)/',$s,$m))
		$s2 = "$m[3]/$m[2]/$m[1]";
	elseif (preg_match('|(\d)(\d?)/(\d)(\d?)/(\d\d+)|',$s,$m)) {
		$s2 = $s;
		$dia = $m[2]? "$m[1]$m[2]": "0$m[1]";
		$mes = $m[4]? "$m[3]$m[4]": "0$m[3]";
		$s = "$m[5]-$mes-$dia";
	}
    return array($s,$s2);
}

function xsl_dayFormat($s){
	list($s,$s2) = dayFormat($s);
    return DOMDocument::loadXML("<day iso='$s'>$s2</day>");
}


function xsl_splitContrib($m) {
	$SEP = ',';
	$dom = new DOMDocument();
	$root = $dom->createElement('contrib-group');
	$txt = $m[0]->textContent;  // contribs não tem tags, ok txt!
	//foreach ($m[0]->childNodes as $node) $txt .= $node->nodeValue;
	$lst = explode($SEP,$txt);
	//for($i=count($lst)-1; $i+1; $i--) {
	$ni = count($lst) -1;
	for($i=0; $i<=$ni; $i++) {
		$name = trim($lst[$i]);
		$isCorresp = 0;
		$name = preg_replace('/\*$/','',$name,1,$isCorresp);
		$node = $dom->createElement('contrib'); // ,$name
		$node->setAttribute('contrib-type','author');

		if ($isCorresp)
			$node->setAttribute('corresp','yes');

		if (preg_match('/^(.+?)\s+(.+?)$/',$name,$m)) {
			$surname = str_replace('-','‑',$m[1]); //'&#8209;' copiado como  "―"
			$given =   str_replace('-','‑',$m[2]);
			$node->appendChild( $dom->createElement('surname',$surname) );
			//$node->appendChild( $dom->createTextNode(' ') ); // cuidado é NBSP dá pau! enquanto createTextNode('♣') funciona!
			// ver meu comentario em http://stackoverflow.com/a/8867502/287948 (mas resolvido por hora)
			$node->appendChild( $dom->createEntityReference('nbsp') );
			$node->appendChild( $dom->createElement('given-names',$given) ); // initials
		} else
			$node->appendChild( $dom->createElement('surname',$name) );

		$root->appendChild($node);
		if ($i<$ni) // not last 
			$root->appendChild( $dom->createTextNode("$SEP ") );	 // menos no last!
	}
    return $root; 
}

function xsl_markCorresp($m) {
	$txt = $m[0]->textContent;
	$txt = preg_replace_callback(
		'/([ :])([^@ :]+@[^ :;,]+)/us', 
		function ($s) {
			return "$s[1]<a href=\"$s[2]\">".strtolower($s[2]).'</a>';
		},   //old '$1<a href="$2">$2</a>', 
		$txt
	);
	return DOMDocument::loadXML("<corresp>$txt</corresp>");
}


/////////////// LIB ///////////////

/**
 * Abre arquivo CSV e trata array:
 */
function csv_get($fileDescr,$fileField00,$funcGet,$testaLen=0,$check00=false) {
    global $buffsize;
    global $CSV_SEP;
	if (($handle = fopen($fileDescr, "r")) !== FALSE) {
		$tmp = fgetcsv( $handle, $buffsize, $CSV_SEP);
		if ( $tmp[0]!=$fileField00 )
			die("\nERRO343 em $fileDescr, campo nao esperasdo {$tmp[0]}\n");
	    while (($tmp = fgetcsv($handle, $buffsize, $CSV_SEP)) !== FALSE)
	    	if ( (!$testaLen ||strlen($tmp[0])>$testaLen) && (!$check00 || $tmp[0]!=$fileField00) )
	    		$funcGet($tmp);
	    fclose($handle);
		return 1;
	} else
		die("\n ERRO na leitura de $fileDescr\n"); //return 0;
}


function showDOMNode(DOMNode $domNode,$level=0) {
	$n=0;
    foreach ($domNode->childNodes as $node) {
    	$n++;
        print "\n\t [$level.$n] ".$node->nodeName.':'.$node->nodeValue;
        if($node->hasChildNodes()) {
        	print "\n ----";
            showDOMNode($node,$level+1);
        }
    }    
} // func


function transformToDom($xsl, &$dom, $enforceUtf8=false) {
  	global $dayFilter; // da sec corrente
  	global $dayLocais; // idem

  	$xsldom = new DOMDocument('1.0','UTF-8');
  	if ( strlen($xsl)<300 && strpos($xsl,'<')===FALSE )
  		$xsl = file_get_contents($xsl); // por hora nao precisa converter para UTF8, sempre estará!
	$xsldom->loadXML($xsl);
	$xsldom->encoding = 'UTF-8';
	$xproc = new XSLTProcessor();
	$xproc->registerPHPFunctions(); // custom
	$xproc->importStylesheet($xsldom);
	if ($dayFilter) {
		preg_match('/\d+\-(\d+)\-(\d+)/', $dayFilter, $m);
		$setDia = "$m[2]/$m[1]";
		$xproc->setParameter('', 'dia', $setDia);
		$xproc->setParameter('', 'local', $dayLocais);
	}
	return  $xproc->transformToDoc($dom);
	// return $this; 
}


 /**
  * Wrap for transformToDom() adding a standard XSLT-header.
  * @param $xsl string a filename or XSLT-block string.
  * @param $dom DOMDocument input object.
  * @param $fnNamespace string not empty (with name-space) when using register-functions.
  * @return DOMDocument transformed.
  */
  function transformFrag_ToDom($xsl,&$dom,$fnNamespace='fn') {
  	if ( strlen($xsl)<300 && strpos($xsl,'<')===FALSE )
  		$xsl = file_get_contents($xsl);
	$xmlnsFn = $fnNamespace? "xmlns:$fnNamespace=\"http://php.net/xsl\"": '';
	$s = '<?xml version="1.0" encoding="UTF-8"?>
			<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:mml="http://www.w3.org/1998/Math/MathML"
				'."$xmlnsFn
				exclude-result-prefixes=\"xlink mml $fnNamespace\"  
			>
		$xsl
		</xsl:stylesheet>
	";
	return transformToDom($s,$dom);
  }

 /**
  * XSLT-identity-transform with $xslFilter.
  * @param $xslFilter string a filename or XSLT-block string.
  * @param $dom DOMDocument input object.
  * @return DOMDocument transformed.
  */
  function transformId_ToDom($xslFilter,&$dom,$fnNamespace='fn') {
  	if ((strlen($xslFilter) < 300) && (strpos($xslFilter,'<') === false))
  		$xslFilter = file_get_contents($xslFilter);
	$xslComplete = '
		<xsl:template match="@*|node()">
		  <xsl:copy>
		    <xsl:apply-templates select="@*|node()"/>
		  </xsl:copy>
		</xsl:template>
		'.$xslFilter;
    return transformFrag_ToDom($xslComplete,$dom,$fnNamespace);
  }

/// out

/**
 * Converte de UTF8 para entidades numéricas XML visíveis em navegadores HTML.
 * PS: dada a independencia com DTD, é preferivel à conversão para entidades não-numericas.
 * FALTA HOMOLOGAR com caracteres de ptBR_UTF8_desacentAll.
 * 
 * @param string $utf8_string texto txt-utf-8 a ser convertido;
 * @return string com caracteres especiais convertidos para entidades numéricas.
 */
function utf2html($utf8_string) {
    return mb_encode_numericentity ($utf8_string, array (160,  9999, 0, 0xffff), 'UTF-8');
}

function showerr($cod,$msg=''){
	return file_put_contents('php://stderr', "\n\tERR-$cod".($msg? ": $msg": ''),FILE_APPEND);
}
?>
