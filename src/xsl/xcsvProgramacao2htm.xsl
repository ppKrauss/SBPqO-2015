<?xml version="1.0" encoding="UTF-8"?>
<!-- Programação  -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	xmlns:mml="http://www.w3.org/1998/Math/MathML"
	xmlns:fn="http://php.net/xsl"
	exclude-result-prefixes="xlink mml fn" 
>


<xsl:output method="html" encoding="UTF-8" indent="yes" omit-xml-declaration="yes" standalone="yes" />

<xsl:template match="/">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pt-BR">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	    <link rel="stylesheet" type="text/css" href="src/css/main.css"/>
		<title>Programação</title>
	</head>

	<body>

	<div id="schedule">
	    <div class="container-fluid">
	        <div class="page-header">
	            <h1>Programação</h1>
	        </div>
	        <div class="panel-group" id="accordion">
				<xsl:apply-templates select="table/DIA"/>
			</div>
		</div><!-- container -->
	</div><!-- schedule -->

	</body>
	</html>
</xsl:template>

<!-- agrupar TRs por dia e local -->
<xsl:template match="DIA">
	<xsl:variable name="day" select="@id"/>

	<div class="panel panel-default menu gray-border-accordion">
	    <div class="remote list-group-item accordion-button" data-toggle="collapse" data-parent="#accordion" href="#{$day}">
	        <p class="panel-title"><xsl:value-of select="infoGrupo/DESCRICAO"/> 
	        	<!-- | <xsl:value-of select="data_br"/>-->
	    	</p>
	    </div>
	    <div id="day1" class="panel-collapse collapse gray-border-accordion-content">
	        <div class="panel-body">
	        	<xsl:apply-templates select="ITEM"/>
	        </div>
	    </div>
	</div>
</xsl:template>

<xsl:template match="ITEM">
    <section id="{@id}">
		<p><strong><xsl:value-of select="EVENTO"/></strong></p>
		<p><strong>Horário: </strong><xsl:value-of select="HORA_INI"/>h <xsl:if test="normalize-space(HORA_FIM)">- <xsl:value-of select="HORA_FIM"/>h</xsl:if></p>
		<p><strong>Local: </strong><span class="location1" idref="loc?"><xsl:value-of select="LOCAL"/></span></p>
        <hr/>
    </section>
</xsl:template>

</xsl:stylesheet>
