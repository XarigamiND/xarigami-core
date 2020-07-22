<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xarstyles_navbar">

    <xsl:message>       * xarstyles/navbar.css</xsl:message>

<xsl:document href="{$output}/xarstyles/navbar.css" format="text" omit-xml-declaration="yes" xml:space="preserve">
/* category and status navigation tabs - similar to roles, but nicely wrappable and made out of list */

.tabnav {
	margin: 0;
	padding: 2px 2px 1px 2px;
	position: relative;
	background-color: #efefef;
	border: 1px solid #aaaaaa;
	margin-bottom: 2px;
}

.navhelp {
	margin-bottom: .5em;
    float:left;
    padding: 0 2px;
}

.tabnav ul {
	margin: 0;
	padding:0;
	float: left;
	width:100%;
	list-style-type: none;
	text-align: left;
}

.tabnav ul li {
	display: block;
	float: left;
	text-align: center;
	padding: 0;
	width: 12em;
	margin: 0 0 0 -1px;
}

.tabnav ul li a {
	background: #ffffff;
	height: 18px;
	border: 1px solid #aaaaaa;
	padding: 0 .7em;
	margin: -1px 0 0 0;
	text-decoration: none;
	display: block;
	text-align: center;
}

.tabnav ul li a:hover {
	color: #ffffff;
	background-color: #bbbbbb;
	text-decoration: none;
}

.tabnav a:active {
	color: #ffffff;
	background-color: #eeeeee;
}

.tabnav li.active a {
	color: #990000;
	background-color: white;
	border: 1px solid #aaaaaa;
	padding-top: 3px;
	padding-bottom: 0px;
	margin: -4px 1px 0 1px;
	position:relative;
	z-index:10000;
}

.tabnav li.active a:hover {
	color: #990000;
	background-color: white;
	text-decoration: none;
}

.tabnav div.tabnav-hairline {
    clear:both;
}

/* use same font? */
.navhelp, .tabnav ul li, .tabnav ul li a {
    font: bold 10px/18px "Lucida Grande", "Lucida Sans Unicode", lucida, verdana, sans-serif;
}

/* ie-win is never happy about webstandards - redefine accordingly with pixel precision */
* html .tabnav {
    float:left;
}

* html .tabnav ul {
    padding:1px 0 0 2px;
    margin:0;
    width:99.33333%;
}

* html .tabnav ul li a {
	display: inline;
	white-space: nowrap;
	margin:0;
	padding: 2px .7em;
	border:0 none;
}

* html .tabnav ul li {
	line-height:0px;
	margin-bottom:0;
	margin-top:-1px;
	padding:0;
	border: 1px solid #aaaaaa;
}

* html .tabnav ul li.active a {
    position:static;
    padding-top: 6px;
    border:0 none;
}

* html .tabnav ul li.active {
    position:relative;
	border: 1px solid #aaaaaa;
	height:21px;
	margin: -4px 1px 0 0;
	background-color: white;

}

/* hide from ie5mac, because it's a bit more standards compliant, but still needs FIXME a bit \ */
.tabnav ul li {
	width:auto;
}
/* end hide */
</xsl:document>
</xsl:template>
</xsl:stylesheet>
