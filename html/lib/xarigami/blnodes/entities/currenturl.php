<?php

/**
* xarTpl__XarCurrentUrlEntityNode
 *
 * wraps xarServer::getCurrentURL()
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarCurrenturlEntityNode extends xarTpl__EntityNode
{
    function render()
   {
       return "xarServer::getCurrentURL()";
   }

}
?>