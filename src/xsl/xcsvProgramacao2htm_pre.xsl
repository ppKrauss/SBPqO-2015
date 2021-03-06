<?xml version="1.0" encoding="utf-8"?>
<!-- xcsvProgramacao2group, agrupa dias  -->

<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:fn="http://php.net/xsl"
  exclude-result-prefixes="fn" 
>



  <xsl:output method="xml" indent="yes" encoding="UTF-8" />

  <xsl:key name="groups" match="/table/tr" use="DIA" />

  <xsl:template match="/table">
    <table>
      <xsl:apply-templates select="tr[generate-id() = generate-id(key('groups', DIA)[1])]"/>
    </table>
  </xsl:template>

  <xsl:template match="tr">
    <DIA id="{ID}"><!-- bug infoGrupo,
      xsl:copy-of select="fn:function('xsl_getCsvRow','programacaoGrupoDia',string(ID))/node()"/ -->
      <data_br iso="{fn:function('dayIso',string(DIA))}"><xsl:value-of select="DIA"/></data_br>
      <xsl:for-each select="key('groups', DIA)">
        <ITEM id="i{ID}_{position()}" idref-loc="{fn:function('localValido',string(LOCAL),1)}">
          <xsl:copy-of select="EVENTO|HORA_INI|HORA_FIM|LOCAL"/>
        </ITEM>
      </xsl:for-each>

    </DIA>
  </xsl:template>
</xsl:stylesheet>
