<?xml version="1.0" encoding="UTF-8"?>
<!-- resumosHTML FORMATO 1day -->
<!-- CONVERTE XML_STANDARD_S1 DE SECAO DE RESUMOS EM HTML_STANDARD_F1 (PARA RENDERIZAR COM CSS) -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	xmlns:mml="http://www.w3.org/1998/Math/MathML"
	xmlns:fn="http://php.net/xsl"
	exclude-result-prefixes="xlink mml fn"  
>

<xsl:template match="/">
	<center>
		<h1>Proceedings of the 31th SBPqO Annual Meeting</h1>
		<br/>
		<h2><xsl:value-of select="//days/day"/></h2>
		<p>&#160;</p>
		<p>&#160;</p>
		<p>&#160;</p>
		<img src="../../tools/css/qrcode_app.png"/>
		<br/><small>Point your iPhone or Android to obtain the Proceedings's app</small>
		<p class="pula1aPagina">&#160;</p>
		<xsl:apply-templates />
	</center>
</xsl:template>

<xsl:template match="sec|subsec">
	<xsl:variable name="locais"><xsl:for-each select="./locations/location">
		<xsl:value-of select="."/><xsl:if test="position()!=last()"><xsl:text>; </xsl:text></xsl:if>
	</xsl:for-each></xsl:variable>

	<xsl:variable name="dias"><xsl:for-each select="./days/day">
		<xsl:value-of select="."/><xsl:if test="position()!=last()"><xsl:text>; </xsl:text></xsl:if>
	</xsl:for-each></xsl:variable>

	<xsl:if test="normalize-space(title) and .//article">
	<div class="{name(.)}">

		<p class="secTitle">
			<xsl:value-of select="title"/><!-- melhorar pois poderia ter tags i,sup, etc. -->
			<xsl:if test="count(./locations/location)=1 and count(./days/day)=1"><br/><small> 
				<xsl:value-of select="$dias"/>  – <xsl:value-of select="$locais"/>
			</small></xsl:if>
		</p>
		<xsl:if test="not(count(./locations/location)=1 and count(./days/day)=1)">
			<p class="locais"><xsl:value-of select="$dias"/><!-- sempre 1 dia só nesse template-->
				&#160; Locais: <xsl:value-of select="$locais"/>.
			</p>
		</xsl:if>

		<div class="tocByKeys"><table border="0"><!-- somente aqui, sem dara nao usa -->
			<xsl:for-each select="keys/key">
			  <tr class="alt{position() mod 2}">
			   	<td valign="top"><div><xsl:value-of select="."/>
			   </div><div><xsl:value-of select="@pubid-list"/></div></td>
			  </tr>
			</xsl:for-each>
		</table></div>
		<xsl:apply-templates select="article"/>
	</div>
    </xsl:if>
</xsl:template>

<xsl:template name="contrib" match="contrib" priority="6">
	<span class="contrib">
		<xsl:value-of select="surname"/><xsl:text>♣</xsl:text>
		<xsl:value-of select="given-names"/><xsl:if test="@corresp">*</xsl:if>
	</span>
</xsl:template>

<xsl:template match="aff|abstract|conclusion">
	<p class="{name(.)}"><xsl:apply-templates/></p>
</xsl:template>

<xsl:template match="article">
	<div class="article" id="{pubid}">
		<hr/>
		<span class="pubid"><xsl:value-of select="pubid"/></span>
        <div class="title">
			<xsl:apply-templates select="title"/>
        </div>
		<div class="contrib-group">
			<xsl:for-each select="contrib-group/node()">
				<xsl:choose>
					<xsl:when test="self::contrib"><xsl:call-template name="contrib"/></xsl:when>
					<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise> 
				</xsl:choose>									
			</xsl:for-each>
		</div>
		<div class="aff-group">
			<xsl:apply-templates select=".//aff"/>
		</div>
		<div class="corresp-group">
			<p class="corresp"><xsl:copy-of select="corresp/node()"/></p>
		</div>
		<xsl:apply-templates select="abstract"/> 
		<xsl:apply-templates select="conclusion"/> 
	</div>
</xsl:template>


</xsl:stylesheet>
