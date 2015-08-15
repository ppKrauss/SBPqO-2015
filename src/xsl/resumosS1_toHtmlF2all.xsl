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


<xsl:template match="sec|subsec">
	<xsl:if test="normalize-space(title) and .//article">
	<div class="{name(.)}">
		<p class="secTitle">
			<xsl:value-of select="title"/>
		</p>
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

<xsl:template match="aff|abstract">
	<p class="{name(.)}"><xsl:apply-templates/></p>
</xsl:template>
<xsl:template match="conclusion"><xsl:apply-templates/></xsl:template>
<xsl:template match="funding-source"><span class="{name(.)}">(Apoio: <xsl:apply-templates/>)</span></xsl:template>

<xsl:template match="SYMBOL"><span style="font-family:Verdana"><xsl:apply-templates/></span></xsl:template>
<!--Times New Roman -->

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
		<xsl:if test="conclusion or funding-source">
			<p class="conclusion">
				<xsl:if test="conclusion">
					<xsl:apply-templates select="conclusion"/>
					<xsl:text> </xsl:text>
				</xsl:if>
				<xsl:apply-templates select="funding-source"/>	
			</p>
		</xsl:if>
	</div>
</xsl:template>


</xsl:stylesheet>
