<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:include href="xarhookapi/item_create.xsl" />
<xsl:include href="xarhookapi/item_delete.xsl" />
<xsl:include href="xarhookapi/item_update.xsl" />

<xsl:include href="xarhookapi/item_transforminput.xsl" />
<xsl:include href="xarhookapi/item_transformoutput.xsl" />

<xsl:include href="xarhookapi/module_remove.xsl" />
<xsl:include href="xarhookapi/module_updateconfig.xsl" />


<xsl:template match="xaraya_module" mode="xarhookapi">

        <!-- MODULE HOOKS -->
        <xsl:if test="configuration/capabilities/item_hooks/text() = 'yes'
                   or configuration/capabilities/transform_hooks/text() = 'yes'" >

        <xsl:message>
### Generating hook API</xsl:message>

            <xsl:apply-templates mode="xarhookapi_module_updateconfig" select="." />
            <xsl:apply-templates mode="xarhookapi_module_remove" select="." />
        </xsl:if>

        <!-- ITEM HOOKS -->
        <xsl:if test="configuration/capabilities/item_hooks/text() = 'yes'" >
            <xsl:apply-templates mode="xarhookapi_item_create"  select="." />
            <xsl:apply-templates mode="xarhookapi_item_delete"  select="." />
            <xsl:apply-templates mode="xarhookapi_item_update"  select="." />
        </xsl:if>

        <!-- TRANSFORM HOOKS -->
        <xsl:if test="configuration/capabilities/transform_hooks/text() = 'yes'" >
            <xsl:apply-templates mode="xarhookapi_item_transforminput"  select="." />
            <xsl:apply-templates mode="xarhookapi_item_transformoutput" select="." />
        </xsl:if>


</xsl:template>


</xsl:stylesheet>
