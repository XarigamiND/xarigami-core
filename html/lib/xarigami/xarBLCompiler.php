<?php
/**
 * BlockLayout Template Engine Compiler
 *
 * @package core
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Core
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Defines for token handling
 *
 */
// Tags
define('XAR_TOKEN_TAG_START'         , '<'      ); // Opening a tag
define('XAR_TOKEN_TAG_END'           , '>'      ); // Closing a tag
define('XAR_TOKEN_ENDTAG_START'      , '/'      ); // Start of an end tag

// Entities
define('XAR_TOKEN_ENTITY_START'      , '&'      ); // Start of an entity
define('XAR_TOKEN_ENTITY_END'        , ';'      ); // End of an entity
define('XAR_TOKEN_ENTITY_SEP'        , '-'      ); // separates the different parts of xar entities

// Tag tokens
define('XAR_TOKEN_NONMARKUP_START'   , '!'      ); // Start of non markup inside tag
define('XAR_TOKEN_PI_DELIM'          , '?'      ); // Processing instruction delimiter inside tag
define('XAR_TOKEN_NS_DELIM'          , ':'      ); // Namespace delimiter
define('XAR_TOKEN_HTMLCOMMENT_DELIM' , '--'     ); // HTML comment

define('XAR_TOKEN_DOCTYPE_START'     , 'DOCTYPE'); // DOCTYPE start inside non markup section
define('XAR_TOKEN_DOCTYPE_END'       , '>'      ); // DOCTYPE end marker

define('XAR_TOKEN_CDATA_START'       , '[CDATA['); // CDATA start inside non markup section
define('XAR_TOKEN_CDATA_END'         , ']]'     ); // CDATA end marker

// Other
define('XAR_TOKEN_VAR_START'         , '$'    );          // Start of a variable
define('XAR_TOKEN_CI_DELIM'          , '#'    );          // Delimiter for variables, functions and other the CI stands for Code Item
define('XAR_TOKEN_MLVAR_START'       , '('    );          // Start of MLS placeholders like #(1)
define('XAR_TOKEN_MLVAR_END'         , ')'    );          // End of MLS placeholders like #(1)
define('XAR_TOKEN_EQUAL_SIGN'        , '='    );
define('XAR_TOKEN_APOS'              , "'"    );
define('XAR_TOKEN_QUOTE'             , '"'    );
define('XAR_TOKEN_SPACE'             , ' '    );
define('XAR_TOKEN_CR'                , "\n"   );
define('XAR_NAMESPACE_PREFIX'        , 'xar'  );          // Our own default namespace prefix
define('XAR_FUNCTION_PREFIX'         , 'xar'  );          // Function prefix (used in check for allowed functions)
define('XAR_ROOTTAG_NAME'            , 'blocklayout');    // Default name of the root tag
$nodesdir = dirname(__FILE__).'/blnodes/';
define('XAR_NODES_LOCATION'          ,$nodesdir); // Where do we keep our nodes classes


/**
 * Defines for errors
 *
 */
define('XAR_BL_INVALID_TAG','INVALID_TAG');
define('XAR_BL_INVALID_ATTRIBUTE','INVALID_ATTRIBUTE');
define('XAR_BL_INVALID_SYNTAX','INVALID_SYNTAX');
define('XAR_BL_INVALID_ENTITY','INVALID_ENTITY');
define('XAR_BL_INVALID_FILE','INVALID_FILE');
define('XAR_BL_INVALID_INSTRUCTION','INVALID_INSTRUCTION');

define('XAR_BL_MISSING_ATTRIBUTE','MISSING_ATTRIBUTE');
define('XAR_BL_MISSING_PARAMETER','MISSING_PARAMETER');

define('XAR_BL_DEPRECATED_ATTRIBUTE','DEPRECATED_ATTRIBUTE');

/**
 * DTD identifiers
 *
 * @package core
 * @todo in php5 make this class constants
 * @todo move this to somewhere editable
 */
class DTDIdentifiers extends xarObject
{
    // List taken from : http://www.w3.org/QA/2002/04/valid-dtd-list.html
    static function get($key)
    {
        $dtds = array
        (
         'html2'                => '<!DOCTYPE html PUBLIC "-//IETF//DTD HTML 2.0//EN">',
         'html32'               => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">',
         'html401-strict'       => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"  "http://www.w3.org/TR/html4/strict.dtd">',
         'html401-transitional' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"  "http://www.w3.org/TR/html4/loose.dtd">',
         'html401-frameset'     => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"  "http://www.w3.org/TR/html4/frameset.dtd">',
         'html5'                => '<!DOCTYPE html>',
         'xhtml1-strict'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
         'xhtml1-transitional'  => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
         'xhtml1-frameset'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
         'xhtml11'              => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
         'mathml101'            => '<!DOCTYPE math SYSTEM "http://www.w3.org/Math/DTD/mathml1/mathml.dtd">',
         'mathml2'              => '<!DOCTYPE math PUBLIC "-//W3C//DTD MathML 2.0//EN" "http://www.w3.org/TR/MathML2/dtd/mathml2.dtd">',
         'svg10'                => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">',
         'svg11'                => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">',
         'svg11-basic'          => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1 Basic//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11-basic.dtd">',
         'svg11-tiny'           => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1 Tiny//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11-tiny.dtd">',
         'xhtml-math-svg'       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN" "http://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd">',
         'svg-xhtml-math'       => '<!DOCTYPE svg:svg PUBLIC  "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN" "http://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd">',
         'rss'                  => '<!DOCTYPE rss PUBLIC "-//Netscape Communications//DTD RSS 0.91//EN"   "http://my.netscape.com/publish/formats/rss-0.91.dtd">',
         'none'                 => ''
        );
        if(isset($dtds[$key])) {
            return $dtds[$key];
        }
        return '';
    }
}

sys::import('xarigami.xarException');

/**
 * Exceptions raised by this subsystem
 *
 * @package compiler
 */
class BLCompilerException extends xarExceptions
{
    protected $message = "Cannot open template file '#(1)'";
}

class BLParserException extends BLCompilerException
{
}

/**
 * ParserError
 *
 * class to hold parser errors
 *
 * @package blocklayout
 * @access private
 * @throws BLParserException
 * @todo ML for the error message?
 */
class ParserError extends Exception
{
    function raiseError($type, $msg)
    {
        $out  = "Template error in file '#(1)' at line #(2), column #(3):\n\n";
        $out .= $msg."\n\n";
        $out .= "Line contents before the parsing error occurred:\n";
        $out .= "#(4) <== Error position\n";
        $vars = array($this->fileName,$this->line,$this->column,$this->lineText);
        // throw a generic exception for now, this probably should not do this, but i dunno yet
        throw new BLParserException($vars,$out);
    }
}
/**
 *  Interface definition for the blocklayout compiler, these are the things
 *  it offers, no more, no less
 *
 */
interface IxarTPLCompiler
{
    static function instance();        // Get an instance of the compiler
    function compileFile($fileName);    // compile a file
    function compileString($data);     // compile a string
}

/**
 * xarTpl__Compiler - the abstraction of the BL compiler
 *
 * The compiler holds the parser and the code generator as objects
 *
 * @package core
 * @subpackage blocklayout
 * @access private
 */
class xarTpl__Compiler extends xarObject implements IxarTPLCompiler
{
    private static $instance = null;
    public $parser;
    public $codeGenerator;

    /*
     * Private constructor for Singleton
     */
     private function __construct()
    {
        $this->parser = new xarTpl__Parser();
        $this->codeGenerator = new xarTpl__CodeGenerator();
    }
    /*
     * Interface implementation
     */
    public static function instance()
    {
        if(self::$instance == null) {
            self::$instance =  new xarTpl__Compiler();
        }
        return self::$instance;
    }

    public function compileString($data)
    {
        return $this->compile($data);
    }

    public function compileFile($fileName)
    {
        // The @ makes the code better to handle, leave it.
        if (!($fp = @fopen($fileName, 'r'))) {
            throw new BLCompilerException($fileName,"Cannot open template file '#(1)'");
        }

        if ($fsize = filesize($fileName)) {
            $templateSource = fread($fp, $fsize);
        } else {
            $templateSource = '';
            while (!feof($fp)) {
                $templateSource .= fread($fp, 4096);
            }
        }

        fclose($fp);
        if (!function_exists('xarLogMessage')) {
            sys::import('xarigami.xarLog');
        }
        xarLogMessage("BL: compiling $fileName");

        $this->parser->setFileName($fileName);
        $ret = $this->compile($templateSource);
        return $ret;
    }
    /*
     * Private methods
     */
    private function compile($templateSource)
    {
        $documentTree = $this->parser->parse($templateSource);
        if (!isset($documentTree)) return; // throw back
        $res = $this->codeGenerator->generate($documentTree);
        return $res;
    }
}

/**
* xarTpl_PositionInfo
 *
 * Instance of this class record where we are doing what in the templates
 *
 * @package core
 * @subpackage blocklayout
 * @access private
 */
class xarTpl__PositionInfo extends ParserError
{
    public $fileName = '';
    public $line = 1;
    public $column = 1;
    public $lineText = '';

    function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }
    function getFileName()
    {
        return $this->fileName;
    }
}

/**
 * xarTpl__CodeGenerator
 *
 * part of the compiler, this generates the code for each tag found
 *
 * @package core
 * @subpackage blocklayout
 * @access private
 */
class xarTpl__CodeGenerator extends xarTpl__PositionInfo
{
    public $isPHPBlock = false;
    public $code;

    function isPHPBlock()
    {
        return $this->isPHPBlock;
    }

    function setPHPBlock($isPHPBlock)
    {
        $code = '';
        // Only change when needed
        if($this->isPHPBlock != $isPHPBlock) {
            $this->isPHPBlock = $isPHPBlock;
            $code = ($isPHPBlock)? '<?php ' : '?>';
        }
        return $code;
    }

    function generate($documentTree)
    {
        // Start the code generation
        $this->code = '';
        $this->code = $this->generateNode($documentTree);
        if (!isset($this->code)) return; // throw back

        // This seems a bit strange, but we always want to end with return
        // true at then end, even if we're not in a php block
        $this->code .= $this->setPHPBlock(true);
        $this->code .= " return true;" . $this->setPHPBlock(false);
        return ltrim($this->code);
    }

    function generateNode($node)
    {
        // Generating the code for a node consists of 3 parts in a recursive loop:
        // 1. render the begin tag
        // 2. render the children
        // 3. render the end tag.
        // If there are no children, we call the render method on the tag.
        if ($node->hasChildren() && isset($node->children) /*|| $node->hasText()*/) {
            //
            // PART 1: Handle the beginning of the node itself, start a php section if needed.
            //
            $startcode = $node->renderBeginTag();
            if (!isset($startcode)) return; // throw back
            $code = $startcode;

            //
            // PART 2: Handle each child below it.
            //
            foreach ($node->children as $child) {
                if ($child->isPHPCode()) {
                    $code .= $this->setPHPBlock(true);
                } elseif (!$node->needAssignment()) {
                    $code .= $this->setPHPBlock(false);
                }
                if ($node->needAssignment() || $node->needParameter()) {
                    if (!$child->isAssignable() && $child->tagName != 'TextNode') {
                        $child->raiseError(XAR_BL_INVALID_TAG,"The '".$node->tagName."' tag cannot have children of type '".$child->tagName."'.");
                    }

                    if ($node->needAssignment()) {
                        $code .= ' = ';
                    }
                } elseif ($child->isAssignable()) {
                    $code .= 'echo ';
                }

                // Recursively do the children
                $childCode = $this->generateNode($child);
                assert('isset($childCode); /* The rendering code for a node is not working properly */');

                $code .= $childCode;

                // This is in the outer level of the current node, see what kind of node we're dealing with
                if ($child->isAssignable() && !($node->needParameter()) || $node->needAssignment()) {
                    $code .= "; ";
                }
            }

            //
            // PART 3: Handle the end rendering of the node
            //
            if ($node->isPHPCode()) {
                $code .= $this->setPHPBlock(true);
            }
            $endCode = $node->renderEndTag();
            assert('isset($endCode); /* The end rendering code for a node is not working properly */');

            $code .= $endCode;

        } else {
            // If there are no children or no text, we can render it as is.
            // Recursion end condition as well.
            $code = $node->render();
            if(!isset($code)) ;//xarLogVariable('offending node:', $node);
            // Either code must have a value
            assert('isset($code); /* The rendering code for a node is not working properly */');
        }
        return $code;
    }
}

/**
 * xarTpl__Parser - the BL parser
 *
 * modelled as extension to the position info class,
 * parses a template source file and constructs a document tree
 *
 * @package blocklayout
 * @access private
 * @todo this is an xml parser type functionality, can't we use an xml parser for this?
 */
class xarTpl__Parser extends xarTpl__PositionInfo
{
    public $tagNamesStack;
    public $tagIds;
    public $tagRootSeen;

    function parse($templateSource)
    {
        // <make sure we only have to deal with \n as LF tokens, replace \r\n and \r
        // Macintosh: \r, Unix: \n, Windows: \r\n
        $this->templateSource = str_replace(array('\r\n','\r'),'\n',$templateSource);

        // Initializing parse trace variables
        $this->line = 1; $this->column = 1; $this->pos = 0; $this->lineText = '';
        $this->tagNamesStack = array();  $this->tagIds = array(); $this->tagRootSeen=false;

        // Initializing the containers for template variables and the doctree
        $this->tplVars = new xarTpl__TemplateVariables();
        $documentTree = xarTpl__NodesFactory::createDocumentNode($this);

        // Parse the document tree
        $res = $this->parseNode($documentTree);
        if (!isset($res)) return; // throw back

        // Fill the tree with the parsed result and its variables and return
        $documentTree->children = $res;
        $documentTree->variables = $this->tplVars;

        return $documentTree;
    }

    /**
     * parseProcessingInstruction
     *
     * We've just identified a target for a processing instruction, handle it here.
     *
     * @access private
     * @todo deprecate the strange <?xar type PI over time, there are better ways for tpl vars
     */
    function parseProcessingInstruction($target)
    {
        $result = '';
        switch($target) {
            case 'xar': // <?xar processing instruction
                $variables = $this->parseHeaderTag();
                if (!isset($variables))  return; // throw back

                foreach ($variables as $name => $value) $this->tplVars->set($name, $value);
                break;
            case 'xml': // <?xml header tag
                // Wind forward to first > and copy to output if we have seen the root tag, otherwise, just wind forward
                $between = $this->windTo(XAR_TOKEN_TAG_END);
                if(!isset($between)) return; // throw back

                if(substr($between,-1) == XAR_TOKEN_PI_DELIM) { // ?
                    $output = XAR_TOKEN_TAG_START . XAR_TOKEN_PI_DELIM . $target . $between . $this->getNextToken();
                } else {
                    // Template error, found a > before the end
                    $this->raiseError(XAR_BL_INVALID_TAG,"The XML header ended prematurely, check the syntax");
                }

                // We do the exception check after parsing it, so we get usefull info in the error
                if($this->tagRootSeen) {
                    $this->raiseError(XAR_BL_INVALID_SYNTAX,'XML headers must occur before the root tag');
                }

                // Copy the header to the output
                if(!$this->tagRootSeen) {
                    if(ini_get('short_open_tag')) $output = "<?php echo '$output';?>";
                    $result .= $output."\n";
                }
                break;
            case 'php':
                // Do a specific error for php processing instruction
                $this->raiseError(XAR_BL_INVALID_TAG,"PHP code detected outside allowed syntax ");
                break;
            default:
                // Anything else leads to an error, that includes the short form of the php tag (empty target)
                $this->raiseError(XAR_BL_INVALID_TAG,"Unknown processing instruction '<?$target' found");
                break;
        }
        return $result;
    }

    function canBeChild($node)
    {
        if (!$node->hasChildren()) {
            $node->raiseError(XAR_BL_INVALID_TAG,"The '".$node->tagName."' tag cannot have children.");
        }
        return true;
    }

    function canHaveText($node)
    {
        if(!$node->hasText()) {
            $node->raiseError(XAR_BL_INVALID_TAG,"The '".$node->tagName."' tag cannot have text.");
        }
        return true;
    }

    function reverseXMLEntities($content)
    {
        return   str_replace(
                             array('&amp;', '&gt;', '&lt;', '&quot;'),
                             array('&', '>', '<', '"'),
                             $content);
    }


    /**
     * parseNode
     *
     * top level parse function for a node
     *
     * @todo why only allow PI targets of size 3?
     */
    function parseNode($parent)
    {
        // Start of parsing a node, initialize our result variables
        $text = ''; $children = array();
        // Get the first character, if source happens to be empty, dont except
        $token = $this->getNextToken(1,true);
        // Main parse loop
        while (isset($token)) {
            // At the start of parsing we can have:
            // <  ==> opening tag,
            // &  ==> entity
            // #  ==> replacement (variable or function)
            switch ($token) {
                case XAR_TOKEN_TAG_START: // <
                    $nextToken = $this->getNextToken();
                    switch($nextToken) {
                        case XAR_TOKEN_PI_DELIM: // < ?
                            $res = $this->parseProcessingInstruction($this->getNextToken(3));
                            if(!isset($res)) return; //throw back
                            $token = $res;
                            break 2;
                        case 'x': // <x
                            if ($nextToken . $this->peek(3) == XAR_NAMESPACE_PREFIX . XAR_TOKEN_NS_DELIM) {
                                $xarToken = $this->getNextToken(3);
                                if(!isset($xarToken)) return;
                                // <xar: tag
                                if(!$this->canbeChild($parent)) return;

                                // Situation: [...text...]<xar:...
                                $trimmer='xmltrim';
                                // If we're in native php tags which always have xar children, trim it
                                $natives = array('set','ml','blockgroup');
                                if(in_array($parent->tagName, $natives,true)) $trimmer='trim';
                                if ($trimmer($text) != '') {
                                    if(!$this->canHaveText($parent)) return;
                                    $children[] = xarTpl__NodesFactory::createTextNode($trimmer($text), $this);
                                    $text = '';
                                }

                                // Handle Begin Tag
                                $res = $this->parseBeginTag();
                                if (!isset($res)) return; // throw back

                                list($tagName, $attributes, $closed) = $res;
                                // Check for uniqueness of id attribute
                                if (isset($attributes['id'])) {
                                    if (isset($this->tagIds[$attributes['id']])) {
                                       $this->raiseError(XAR_BL_INVALID_TAG,"Not unique id in '".$tagName."' tag.");
                                    }
                                    if ($attributes['id'] == '') {
                                        $this->raiseError(XAR_BL_INVALID_TAG,"Empty id in '".$tagName."' tag.");
                                    }
                                    $this->tagIds[$attributes['id']] = true;
                                }

                                $tplType = $this->tplVars->get('type');
                                if($tplType == 'module' && $tagName == XAR_ROOTTAG_NAME) {
                                    // root tag found in module template
                                    $this->raiseError(XAR_BL_INVALID_SYNTAX,
                                              'Root tag found in module template or before <?xar type="page" ?> instruction');
                                }

                                if($tplType == 'page' && $tagName != XAR_ROOTTAG_NAME && !$this->tagRootSeen) {
                                    $this->raiseError(XAR_BL_INVALID_SYNTAX,"Found a  xar:$tagName tag before the xar:blocklayout tag, this is invalid");
                                }

                                // Create the node we parsed.
                                $node = xarTpl__NodesFactory::createTplTagNode($tagName, $attributes, $parent->tagName, $this);
                                if (!isset($node)) return; // throw back

                                if (!$closed) {
                                    $this->tagNamesStack[] = $tagName;
                                    $res = $this->parseNode($node);
                                    if (!isset($res)) return; // throw back
                                    $node->children = $res;
                                }
                                $children[] = $node;
                                // Here we set token to an empty string so that $text .= $token will result in $text
                                $token = '';
                                break 2;
                            }
                            break;
                        case XAR_TOKEN_ENDTAG_START:
                            // Check for xar end tag
                            $nsLength = strlen(XAR_NAMESPACE_PREFIX . XAR_TOKEN_NS_DELIM);
                            if ($this->peek($nsLength) == XAR_NAMESPACE_PREFIX . XAR_TOKEN_NS_DELIM) {
                                $xarToken = $this->getNextToken($nsLength);
                                if(!isset($xarToken)) return;
                                // Situation: [...text...]</xar:...
                                $trimmer='xmltrim';
                                $natives = array('set', 'ml', 'mlvar','blockgroup');
                                if(in_array($parent->tagName, $natives,true)) $trimmer='trim';
                                if ($trimmer($text) != '') {
                                    if(!$this->canHaveText($parent)) return;
                                    $children[] = xarTpl__NodesFactory::createTextNode($trimmer($text), $this);
                                    $text = '';
                                }
                                // Handle End Tag
                                $tagName = $this->parseEndTag();
                                if (!isset($tagName)) return; // throw back

                                $stackTagName = array_pop($this->tagNamesStack);
                                if ($tagName != $stackTagName) {
                                    $this->raiseError(XAR_BL_INVALID_TAG,"Found closed '$tagName' tag where closed '$stackTagName' was expected.");
                                }
                                return $children;
                            }
                            break;
                        case XAR_TOKEN_NONMARKUP_START:
                            $token .= $nextToken; // <!
                            $buildup=''; unset($identifier); unset($remember);
                            // Get all tokens till the first whitespace char, and check whether we found any tokens
                            $nextChar = $this->getNextToken();
                            while(trim($nextChar)) {
                                $buildup .= $nextChar;
                                switch(strtoupper($buildup)) {
                                    case XAR_TOKEN_HTMLCOMMENT_DELIM:
                                        $identifier = XAR_TOKEN_HTMLCOMMENT_DELIM;
                                        break 2; // we found the delimiter, carry on
                                    case XAR_TOKEN_DOCTYPE_START:
                                        // doctype before root tag isnt ours to process, we skip that completely
                                        if(!$this->tagRootSeen) {
                                            // While we are not using an XML parser do this very simplistic, just wind to the end and skip the
                                            // whole she bang. When an internal subset is specified this may go bang-bang
                                            $between = $this->windTo(XAR_TOKEN_TAG_END);
                                            $this->getNextToken(); // eat the '>'
                                            $text = ''; $token = '';
                                            break 4; // Start all over, leaving nothing open
                                        }
                                        // doctype after root tag is invalid, but we allow it for now
                                        break;
                                    case XAR_TOKEN_CDATA_START:
                                        // Treat it as text
                                        // FIXME: CDATA should really be skipped, but our RSS theme depends on the resolving inside
                                        // See also bug #3111
                                        $token = XAR_TOKEN_TAG_START . XAR_TOKEN_NONMARKUP_START .  $buildup;
                                        break 4; // continue parsing the content
                                }
                                $nextChar = $this->getNextToken();
                            }
                            if(!isset($identifier)) {
                                // Remember what was after the buildup
                                $remember = $nextChar;
                            }
                            // identifier is now a token or free form (in our case  -- or the first whitespace char)

                            // Get the rest of the non markup tag, recording along the way
                            $matchToken=''; $match = '';
                            $nextChar = $this->getNextToken();
                            if(isset($identifier)) {
                                while(isset($nextChar) && $matchToken . $nextChar != $identifier . XAR_TOKEN_TAG_END){
                                    $match .= $nextChar;
                                    // Match on the length of the identifier
                                    $nextChar = $this->getNextToken();
                                    $matchToken = substr($match,-1 * strlen($identifier));
                                }
                            }
                            // Forward to the end token
                            while(isset($nextChar) && $nextChar != XAR_TOKEN_TAG_END) {
                                $match .= $nextChar;
                                $nextChar = $this->getNextToken();
                            }

                            if(isset($identifier)) {
                                $tagrest = substr($match,0,-1 * strlen($identifier));
                            } else {
                                $tagrest = $match;
                                $matchToken = $remember;
                                $identifier = $remember;
                            }

                            // Was it properly ended?
                            if($matchToken == $identifier && $nextChar == XAR_TOKEN_TAG_END) {
                                // the tag was properly ended.
                                $invalid = strpos($tagrest,$matchToken);
                                switch($identifier) {
                                    case XAR_TOKEN_HTMLCOMMENT_DELIM:
                                        // <!-- HTML comment, copy to output
                                        $token .= $identifier . $tagrest . $matchToken . $nextChar;
                                        break;
                                    default:
                                        // <!WHATEVER Something else ( <!DOCTYPE for example ) as long as it ends properly, we're happy
                                        $invalid = false;
                                        // Take the $tagrest and resolve stuff #...#
                                        $token .= $buildup . $identifier . $tagrest . $nextChar;
                                }
                                if($invalid) {
                                    $this->raiseError(XAR_BL_INVALID_TAG,
                                              "A non-markup tag (probably a comment) contains its identifier (".
                                              $matchToken.") in its contents. This is invalid XML syntax");
                                }
                            } else {
                                xarLogMessage("[$token][$buildup][$identifier][$tagrest][$matchToken][$nextChar]");
                                $this->raiseError(XAR_BL_INVALID_TAG,
                                          "A non-markup tag (probably a comment) wasn't properly matched ('".
                                          $identifier."' vs. '". $matchToken ."') This is invalid XML syntax");
                            }
                            break 2;
                    } // end case

                    //<Dracos>  Stop tag embedding, ie <a href="<xar
                    // FIXME: does this still go bonkers on embedded javascript?
                    $between = $this->peekTo(XAR_TOKEN_TAG_END);
                    if(!isset($between)) return;
                    if(strpos($between, XAR_TOKEN_TAG_START)) {
                        // There is a < in there
                        $this->windTo(XAR_TOKEN_TAG_END);
                        $this->raiseError(XAR_BL_INVALID_TAG,__LINE__ .": Found open tag before close tag.");
                    }
                    $token.=$nextToken;
                    break;
                case XAR_TOKEN_ENTITY_START:
                    // Check for xar entity
                    if ($this->peek(4) == 'xar-') {
                        $nextToken = $this->getNextToken(4);
                        if(!isset($nextToken)) return;
                        if(!$this->canbeChild($parent)) return;

                        // Situation: [...text...]&xar-...
                        if (trim($text) != '') {
                            if(!$this->canHaveText($parent)) return;
                            $children[] = xarTpl__NodesFactory::createTextNode(xmltrim($text), $this);
                            $text = '';
                        }
                        // Handle Entity
                        $res = $this->parseEntity();
                        if (!isset($res)) return; // throw back

                        list($entityType, $parameters) = $res;
                        $node =  xarTpl__NodesFactory::createTplEntityNode($entityType, $parameters, $this);
                        if (!isset($node)) return; // throw back

                        $children[] = $node;
                        $token = '';
                        break;
                    }
                    // No XAR entity, but might be another one (we do this here for &#xBA type entities, which would other wise get caught in an instruction)
                    $between = $this->peekTo(XAR_TOKEN_ENTITY_END);
                    if(!isset($between) or strpos($between,XAR_TOKEN_TAG_START)) {
                        // set an exception and return
                        $this->raiseError(XAR_BL_INVALID_SYNTAX,"Entity isn't closed properly.");
                    }
                    // Otherwise just pass the entity to the outputtext and clear the token to start over
                    $text.=XAR_TOKEN_ENTITY_START.$this->windTo(XAR_TOKEN_ENTITY_END).$this->getNextToken();$token='';
                    break;
                case XAR_TOKEN_CI_DELIM:
                    // Take a peek what comes after the # and deal with the special situations
                    $peek = $this->peek();

                    // Break out of processing if # is escaped as ##, by eating the second one
                    if ($peek == XAR_TOKEN_CI_DELIM) {$this->getNextToken();break;}

                    // Break out of processing if nextToken is (, because #(.) is used by MLS
                    if ($peek == XAR_TOKEN_MLVAR_START) {
                        $token .= $this->getNextToken();
                        break;
                    }

                    // Get what what is between #.....#
                    if ($peek == XAR_TOKEN_VAR_START || $peek == 'x') { // for href="#" for example
                        $between = $this->windTo(XAR_TOKEN_CI_DELIM);
                        if(!isset($between)) return;
                        $instruction = $between;
                        $this->getNextToken(); // eat the matching #

                        if(!$this->canbeChild($parent)) return;

                        // Add text to parent, if applicable
                        // Situation: [...text...]#$....# or [...text...]#xarFunction()#
                        $trimmer='noop';
                        // FIXME: The above is wrong, should be xmltrim,
                        // but otherwise the export of DD objects will look really ugly
                        $natives = array('set','ml','mlvar');
                        if(in_array($parent->tagName,$natives,true)) $trimmer='trim';
                        if ($trimmer($text) != '') {
                            if(!$this->canHaveText($parent) && trim($text) != '') return;
                            $children[] =  xarTpl__NodesFactory::createTextNode($trimmer($text), $this);
                            $text = '';
                        }

                        // Replace XML entities with their ASCII equivalents.
                        // An XML parser would do this for us automatically.
                        $instruction = $this->reverseXMLEntities($instruction);

                        // The following is a bit of a sledge-hammer approach. See bug 1273.
                        // TODO: parse the PHP so the semi-colon can be tested in context.
                        if (strpos($instruction, ';')) {
                            $this->raiseError(XAR_BL_INVALID_TAG, "Possible injected PHP detected in: $instruction");
                        }

                        // Instruction is now set to $varname or xarFunction(.....)

                        $node = xarTpl__NodesFactory::createTplInstructionNode($instruction, $this);
                        if (!isset($node)) return; // throw back

                        $children[] = $node;
                        $token = '';
                    }
                    break;
            } // end switch
            // Once we get here, nothing in the switch caught the token, we copy verbatim to output.
            $text .= $token;
            // and get a new one, but dont except on it
            $token = $this->getNextToken(1,true);
        } // end while

        // Add the final text as a text node
        $trimmer = 'xmltrim';
        if ($trimmer($text) != '') {
            if(!$this->canHaveText($parent)) return;
            $children[] = xarTpl__NodesFactory::createTextNode($trimmer($text),$this);
        }
        // Check if there is something left at the stack
        $stackTagName = array_pop($this->tagNamesStack);
        if(!empty($stackTagName)) {
            $this->raiseError(XAR_BL_INVALID_SYNTAX,"Reached end of file while tag '$stackTagName' was open");
        }
        return $children;
    }

    function parseHeaderTag()
    {
        $variables = array();
        while (true) {
            $variable = $this->parseTagAttribute();
            if (!isset($variable)) return; // throw back

            if (is_string($variable)) {
                $exitToken = $variable;
                break;
            }
            $variables[$variable[0]] = $variable[1];
        }
        if ($exitToken != XAR_TOKEN_PI_DELIM) {
            $this->raiseError(XAR_BL_INVALID_TAG,"Invalid '$exitToken' character in header tag.");
        }
        // Must parse the entire tag, we want to find > character
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) return;

            if ($token == XAR_TOKEN_TAG_START) {
                $this->raiseError(XAR_BL_INVALID_TAG,"Unclosed tag.");
            }
            if ($token == XAR_TOKEN_TAG_END) {
                break;
            }
        }
        return $variables;
    }

    function parseBeginTag()
    {
        // Parse the name of the tag
        $tagName = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) return;

            if ($token == XAR_TOKEN_TAG_START) {
                $this->raiseError(XAR_BL_INVALID_TAG,"Unclosed tag.");
            }
            if ($token == XAR_TOKEN_SPACE || $token == XAR_TOKEN_CR || $token == XAR_TOKEN_TAG_END || $token == XAR_TOKEN_ENDTAG_START) {
                break;
            }
            $tagName .= $token;
        }
        if ($tagName == '') {
            $this->raiseError(XAR_BL_INVALID_TAG,"Unnamed tag.");
        }

        // Parse the attributes
        $attributes = array();
        if ($token == XAR_TOKEN_SPACE || $token == XAR_TOKEN_CR) {
            while (true) {
                $attribute = $this->parseTagAttribute();
                if (!isset($attribute)) return; // throw back

                if (is_string($attribute)) {
                    $exitToken = $attribute;
                    break;
                }
                $attributes[$attribute[0]] = $attribute[1];
            }
        } else {
            $exitToken = $token;
        }
        if ($exitToken != XAR_TOKEN_TAG_END) {
            // Must parse the entire tag, we want to find > character
            while (true) {
                $token = $this->getNextToken();
                if (!isset($token)) return;

                if ($token == XAR_TOKEN_TAG_START) {
                    $this->raiseError(XAR_BL_INVALID_TAG,"Unclosed tag.");
                }
                if ($token == XAR_TOKEN_TAG_END) {
                    break;
                }
            }
        }
        return array($tagName, $attributes, ($exitToken == XAR_TOKEN_ENDTAG_START) ? true : false);
    }

    function parseTagAttribute()
    {
        // First parse the name part
        $name = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) return;

            switch($token) {
                case XAR_TOKEN_QUOTE:
                case XAR_TOKEN_APOS:
                    $this->raiseError(XAR_BL_INVALID_TAG,"Invalid '$token' character in attribute name.");
                case  XAR_TOKEN_TAG_START:
                    $this->raiseError(XAR_BL_INVALID_TAG,"Unclosed tag.");
                case XAR_TOKEN_TAG_END:
                case XAR_TOKEN_ENDTAG_START:
                case XAR_TOKEN_PI_DELIM:
                    if (trim($name) != '') {
                        $this->raiseError(XAR_BL_INVALID_TAG,"Invalid '$name' attribute.");
                    }
                    return $token;
                case XAR_TOKEN_EQUAL_SIGN:
                    break 2;
            }
            $name .= $token;
        }

        $name = trim($name);
        if ($name == '') {
            $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,"Unnamed attribute.");
        }

        // Then the value part
        $value = '';  $quote = '';  $ok = false;
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) return;

            if($token == XAR_TOKEN_TAG_END) {
                $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,"Unclosed '$name' attribute.");
            } elseif ($token == $quote) {
                break;
            }
            if ($ok) {
                $value .= $token;
            } elseif ($token == XAR_TOKEN_QUOTE) {
                $quote = XAR_TOKEN_QUOTE;
                $ok = true;
            } elseif ($token == XAR_TOKEN_APOS) {
                $quote = XAR_TOKEN_APOS;
                $ok = true;
            }
        }
        // Replace XML entities with their ASCII equivalents.
        // An XML parser would do this for us automatically.
        $value = $this->reverseXMLEntities($value);
        return array($name, $value);
    }

    function parseEndTag()
    {
        // Tag name
        $tagName = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) return;

            if($token == XAR_TOKEN_TAG_START) {
                $this->raiseError(XAR_BL_INVALID_TAG,"Unclosed tag.");
            } elseif ($token == XAR_TOKEN_TAG_END) {
                break;
            }
            $tagName .= $token;
        }
        $tagName = rtrim($tagName);
        if ($tagName == '') {
            $this->raiseError(XAR_BL_INVALID_TAG,"Unnamed tag.");
        }
        return $tagName;
    }

    function parseEntity()
    {
        // Entity type
        $entityType = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) return;

            if($token == XAR_TOKEN_ENTITY_SEP || $token == XAR_TOKEN_ENTITY_END) {
                break;
            }
            $entityType .= $token;
        }
        if ($entityType == '') {
            $this->raiseError(XAR_BL_INVALID_ENTITY,"Untyped entity.");
        }
        $parameters = array();
        if ($token == XAR_TOKEN_ENTITY_SEP) {
            $parameter = '';
            while (true) {
                $token = $this->getNextToken();
                if (!isset($token)) return;

                if($token == XAR_TOKEN_ENTITY_END) {
                    if ($parameter == '') {
                        $this->raiseError(XAR_BL_INVALID_ENTITY,"Empty parameter.");
                    }
                    $parameters[] = $parameter;
                    break;
                } elseif ($token == XAR_TOKEN_ENTITY_SEP) {
                    $parameters[] = $parameter;
                    $parameter = '';
                } else {
                    $parameter .= $token;
                }
            }
        }
        return array($entityType, $parameters);
    }

    function getNextToken($len = 1,$dontExcept = false)
    {
        $result = '';
        while($len >= 1) {
            $token = substr($this->templateSource, $this->pos, 1);
            // FIXME: We compare to 0 because substr() with "mbstring.func_overload = 7" settings
            // returns not false but 0 at the end of a template
            if ($token === false || $token == null) {
                // This line fixes a bug that happen when $len is > 1
                // and the file ends before the token has been read
                $this->pos += $len;
                if(!$dontExcept) {
                    $this->raiseError(XAR_BL_INVALID_FILE,"Unexpected end of the file.");
                }
                return;
            }
            $this->lineText .= $token;

            $this->pos++; $this->column++;
            if ($token == "\n") {
                $this->line++;
                $this->column = 1;
                $this->lineText = '';
            }
            $result .= $token;
            $len--;
        }
        return $result;
    }

    /**
     * Seek a certain marker and move the parse pointer
     * to just before it.
     * If the marker isn't found, the pointer isnt updated
     * and null is returned
     *
     * @todo this does a literal search on the needle, no smart finding of end tags
     * @todo investigate border situation when needle is found but between text is empty (e.g. an empty entity (&;)
     */
    function windTo($needle)
    {
       // assert('strlen($needle) > 0; /* The search needle in parser->windTo has zero length */');

        // Take a peek first, raise exception explicitly, cos peek* doesnt
        $peek = $this->peekTo($needle);
        if(!isset($peek)) {
            $this->raiseError(XAR_BL_INVALID_FILE,"Unexpected end of the file.");
        }

        // We found sumtin, advance the pointer, cos we can
        return $this->getNextToken(strlen($peek));
    }

    /**
     * Peek ahead in search for a certain marker
     * return what was in between if we found it, but
     * do not advance the parse pointer. If the marker
     * is not found, or the end of the file was reached, return null
     *
     * @todo see windTo method
     */
    function peekTo($needle)
    {
        assert('strlen($needle) > 0; /* The search needle in parser->peekTo has zero length */');

        // Get a buffer of the size of what we are searching
        $offset = $this->pos; $needleSize = strlen($needle);
        $buffer = ''; $wound='';

        // fifo the buffer in each iteration and check for the needle
        while($buffer != $needle) {
            $wound.= substr($buffer,0,1); // fifo the first char out
            $buffer = $this->peek($needleSize,$offset);
            if(!isset($buffer)) return; // throw back
            $offset++;
        }
        // We found the needle, return what we wound over
        return $wound;
    }

    function peek($len = 1, $start = 0)
    {
       // assert('$start >= 0; /* The start position for peeking needs to be zero or greater, a call to parser->peek was wrong */');
        if($start == 0) $start = $this->pos; // can't do this in param init

        $token = substr($this->templateSource, $start, $len);
        if ($token === false) return;
        return $token;
    }
}

/**
 * xarTpl__NodesFactory - class which constructs nodes in the document tree
 *
 * @package core
 * @subpackage blocklayout
 * @access private
 */
class xarTpl__NodesFactory extends ParserError
{

    static function createTplTagNode($tagName, $attributes, $parentTagName, $parser)
    {
        // If the root tag comes along, check if we already have it
        if($tagName == XAR_ROOTTAG_NAME && $parser->tagRootSeen) {
            $parser->raiseError(XAR_BL_INVALID_SYNTAX,"The root tag can only occur once.");
        }

        // Otherwise we instantiate the right class
        $tagClass ='xarTpl__Xar' .$tagName.'Node';
        $tagfile = XAR_NODES_LOCATION . 'tags/' .strtolower($tagName) .'.php';

        // FIXME: sync the implementation of core / custom tags, handle them the same way
        if(file_exists($tagfile)) {
            sys::import('xarigami.blnodes.tags.'.strtolower($tagName));
            $node = new $tagClass($parser, $tagName, $parentTagName, $attributes);
        } else {

            //include_once(XAR_NODES_LOCATION .'tags/other.php');
            sys::import('xarigami.blnodes.tags.other');
            $node = new xarTpl__XarOtherNode($parser, $tagName, $parentTagName, $attributes);
            if(!isset($node->tagobject)) {
                $parser->raiseError(XAR_BL_INVALID_TAG,"Cannot instantiate nonexistent tag '$tagName'");
            }
        }
        return $node;
    }

    // Deprecated. @todo is it used somewhere else?
    static function class_exists($classname)
    {
      // In >= PHP 5 we want to prevent __autoload to kick in.
      return (version_compare('5.0.0',phpversion(),'le')) ? class_exists($classname,false) : class_exists($classname);
    }

    static function createTplEntityNode($entityType, $parameters, $parser)
    {
        $entityClass = 'xarTpl__Xar'.$entityType.'EntityNode';
        $entityFile = XAR_NODES_LOCATION . 'entities/' .strtolower($entityType) . '.php';
        if (!class_exists($entityClass)) {
            if(!file_exists($entityFile)) {
                $parser->raiseError(XAR_BL_INVALID_ENTITY,"Cannot instantiate nonexistent entity '$entityType'");
            }
            sys::import('xarigami.blnodes.entities.'.strtolower($entityType));
        }
        $node = new $entityClass($parser,'EntityNode', $entityType, $parameters);
        return $node;
    }

    static function createTplInstructionNode($instruction, $parser)
    {
        $instructionClass = 'xarTpl__XarApiInstructionNode';
        $instructionFile = XAR_NODES_LOCATION . 'instructions/api.php';
        $instructionType = 'api';
        if ($instruction[0] == XAR_TOKEN_VAR_START) {
            $instructionClass = 'xarTpl__XarVarInstructionNode';
            $instructionFile = XAR_NODES_LOCATION . 'instructions/var.php';
            $instructionType = 'var';
        }

        if(!self::class_exists($instructionClass)) {

            if(!file_exists($instructionFile)) {
                $parser->raiseError(XAR_BL_INVALID_INSTRUCTION,"Cannot instantiate nonexistent instruction '$instruction'");
            }
            sys::import('xarigami.blnodes.instructions.'.$instructionType);
        }
        $node = new $instructionClass($parser, 'InstructionNode', $instruction);
        return $node;
    }

    static function createTextNode($content, &$parser)
    {
        $node = new xarTpl__TextNode($parser, 'TextNode', $content);
        return $node;
    }

    static function createDocumentNode($parser)
    {
        $node = new xarTpl__DocumentNode($parser,'DocumentNode');
        return $node;
    }
}

/**
 * xarTpl__TemplateVariables
 *
 * Handle template variables
 *
 * @package core
 * @subpackage blocklayout
 * @access private
 * @todo code the version number somewhere more central
 * @todo is the encoding fixed?
 *
 */
class xarTpl__TemplateVariables
{
    private $__tplVars = array();
    private static $instance = null;

    function __construct()
    {
        // Fill defaults
        $this->__tplVars['version'] = '1.0';
        $this->__tplVars['encoding'] = 'us-ascii';
        $this->__tplVars['type'] = 'module';
    }
    
    static function instance()
    {
        if(self::$instance == null) {
            self::$instance =  new xarTpl__TemplateVariables();
        }
        return self::$instance;   
    }

    function get($name)
    {
        if (isset($this->__tplVars[$name])) {
            return $this->__tplVars[$name];
        }
        return '';
    }

    function set($name, $value)
    {
        $this->__tplVars[$name] = $value;
    }
}

/**
 * xarTpl__ExpressionTransformer
 *
 * Transforms BL and php expressions from templates.
 *
 * @package core
 * @subpackage blocklayout
 * @access private
 */
class xarTpl__ExpressionTransformer extends xarObject
{
    /**
     * Replace the array and object notation.
     * This is the BLExpression grammar:
     * BLExpression ::= Variable | Variable '.' ArrayKey | Variable ':' Property
     * Variable ::= [a-zA-Z_] ([0-9a-zA-Z_])*
     * ArrayKey ::= Name | Name '.' ArrayKey | Name ':' Property
     * Property ::= Name | Name '.' ArrayKey | Name ':' Property
     * Name     ::= ([0-9a-zA-Z_])+
     */
    static function transformBLExpression($blExpression)
    {
        //$expressionTransformer =  new xarTpl__ExpressionTransformer();
        $blExpression = self::normalize($blExpression);

        // 'resolve' the dot and colon notation
        $subparts = preg_split('/[\[|\]]/', $blExpression);
        if(count($subparts) > 1) {
            foreach($subparts as $subpart) {
                // Resolve the subpart
                $blExpression = str_replace($subpart, self::transformBLExpression($subpart), $blExpression);
            }
            return $blExpression;
        }

        $identifiers = preg_split('/[.|:]/',$blExpression);
        $operators = preg_split('/[^.|^:]/',$blExpression,-1,PREG_SPLIT_NO_EMPTY);

        $numIdentifiers = count($identifiers);

        $expression = $identifiers[0];
        for ($i = 1; $i < $numIdentifiers; $i++) {
            if($operators[$i - 1] == '.') {
                if((substr($identifiers[$i],0,1) == XAR_TOKEN_VAR_START) || is_numeric($identifiers[$i])) {
                    $expression .= "[".$identifiers[$i]."]";
                } else {
                    $expression .= "['".$identifiers[$i]."']";
                }
            } elseif($operators[$i - 1] == ':') {
                $expression .= '->'.$identifiers[$i];
            }
        }
        return $expression;
    }

    /**
     * Transform a PHP expression from a template to a valid piece of PHP code
     *
     * @return string Valid PHP expression
     * @todo if expressions were always between #...# this would be easier
     * @todo if the key / objectmember is a variable, make sure it fits the regex for a valid variable name
     * @todo the convenience operators may conflict in some situations with the MLS ( like 'le' for french)
     **/
    static function transformPHPExpression($phpExpression)
    {
        //$expressionTransformer = new xarTpl__ExpressionTransformer();
        $phpExpression =self::normalize($phpExpression);
        // This regular expression matches variables in their notation as
        // supported by php  and according to the dot/colon grammar in the
        // method above. These expressions are matched and passed on to the BL
        // expression resolver above which resolves them into php variables notation.
        // The resolved names are replaced in the original expression

        // Let's dissect the expression so it's a bit more clear:
        //  1. /..../i            => we're matching in a case - insensitive  way what's between the /-es (FIXME: KEEP AN EYE ON THIS)
        //  2. \\\$               => matches \$ which is an escaped $ in the string to match
        //  3. (                  => this starts a captured subpattern
        //  4.  [a-z_]            => matches a letter or underscore, which is wat vars need to start with
        //  5.  [0-9a-z_\[\]\$]*  => matches the rest of the variables which might be present, while preserving [ and ]
        //  6.  (                 => start property / array access subpattern
        //  7.   :|\\.            => matches the colon or the dot notation
        //  8.   [$]{0,1}         => the array key or object member may be a variable
        //  9.   [0-9a-z_\]\[\$]+ => matches number,letter or underscore, one or more occurrences
        // 10.  )                 => matches right brace
        // 11.  *                 => match zero or more occurences of the property access / array key notation (colon notation)
        // 12. )                  => ends the current pattern
        // NOTE: The behaviour of this method along with the BLExpression method above CHANGED. Part
        //       of the resolving is now done by the previous method (i.e. a complete expression is passed into it)

        $regex = "/((\\\$[a-z_][a-z0-9_\[\]\$]*)([:|\.][$]{0,1}[0-9a-z_\]\[\$]+)*)/i";
        if (preg_match_all($regex, $phpExpression,$matches)) {
            // Resolve BL expressions inside the php Expressions

            // To prevent overlap as much as we can we sort descending by length
            usort($matches[0], array('xarTpl__ExpressionTransformer','rlensort'));
            $numMatches = count($matches[0]);
            for ($i = 0; $i < $numMatches; $i++) {
                //$resolvedexpressionTransformer = new xarTpl__ExpressionTransformer();
                $resolvedName = self::transformBLExpression($matches[0][$i]);
                if (!isset($resolvedName)) return; // throw back

                // CHECK: Does it matter if there is overlap in the matches?
                $phpExpression = str_replace($matches[0][$i], $resolvedName, $phpExpression);
            }
        }

        $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');
        $replaceLogic   = array(' == ', ' != ',  ' < ',  ' > ', ' === ', ' !== ', ' <= ', ' >= ');

        $phpExpression = str_replace($findLogic, $replaceLogic, $phpExpression);

        return $phpExpression;
    }

    static function rlensort($a, $b)
    {
        $sa = (int)strlen($a);
        $sb = (int)strlen($b);
        if( $a === $b) {
            return 0;
        }
        return ($a < $b) ? 1 : -1;
    }

    static function normalize($expr)
    {
        /* If the expression is enclosed in # s, ignore them */
        if(empty($expr)) return $expr;
        if( $expr{0} == XAR_TOKEN_CI_DELIM &&
            $expr{strlen($expr)-1} == XAR_TOKEN_CI_DELIM) {
            $expr = substr($expr,1,-1);
        }
        return $expr;
    }
}

/**
 * xarTpl__Node
 *
 * Base class for all nodes, sets the base properties, methods are
 * abstract and should be overridden by each specific node class
 *
 * @package core
 * @subpackage blocklayout
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> true
 * isPHPCode -> false
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 */
abstract class xarTpl__Node extends xarTpl__PositionInfo
{
    public $tagName;   // This is an internal name of the node, not the actual tag name
    protected $isPHPCode = true;
    protected $hasChildren = false;
    protected $hasText = false;
    protected $isAssignable = true;
    protected $needAssignment = false;
    protected $needParameter = false;

    function __construct($parser, $nodeName)
    {
        $this->tagName  = $nodeName;
        $this->fileName = $parser->fileName;
        $this->line     = $parser->line;
        $this->column   = $parser->column;
        $this->lineText = $parser->lineText;
    }

    // These methods should be implemented by child classes
    abstract function render();

    function hasChildren()
    { return $this->hasChildren; }

    function hasText()
    { return $this->hasText; }

    function isAssignable()
    { return $this->isAssignable; }

    function isPHPCode()
    { return $this->isPHPCode; }

    function needAssignment()
    { return $this->needAssignment; }

    function needParameter()
    { return $this->needParameter; }
}

/*
    Interfaces to distinguish tags which implement open/closed forms
    of tags. Taking the w3 terminology, not really happy with it though.
*/
interface ElementTag
{
    function renderBeginTag();
    function renderEndTag();
}

interface EmptyElementTag
{
    function render();
}

/**
* xarTpl__TplTagNode
 *
 * Base class for tag nodes
 *
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> true
 * isPHPCode -> true
 * needAssignment -> false
 * needParameter -> false
 * @package blocklayout
 * @todo look at the signature, it's redundant.
 * @todo attributes can be dealt with more centrally, to make it easier for the nodes. (like id, class or other common attributes )
 */
abstract class xarTpl__TplTagNode extends xarTpl__Node
{
    protected $attributes;
    protected $parentTagName;
    public    $children;

    // Do the same here as we do in tplnode class
    function __construct($parser, $tagName, $parentTagName, $attributes)
    {
        parent::__construct($parser, $tagName);
        $this->isPHPCode = true;
        $this->parentTagName = $parentTagName;
        $this->attributes = $attributes;
    }


    /**
     * Render a closed tag, abstract catcher
     *
     * If we get here, the render method was called but not implemented in the tag,
     * which means the user specified it as <xar:tag ..../>
     * We (try to) treat this like <xar:tag></xar:tag> which is effectively the same.
     *
     * @return void
     * @todo   refactor the classes so this method cannot be called directly (i.e. protected)
     **/
    public function render()
    {
        return $this->renderBeginTag() . $this->renderEndTag();
    }

    /**
     * Render the begin tag, abstract catcher
     *
     * Similarly if we get here, the renderBeginTag and renderEndTag method were not
     * implemented by the tag, either by mistake, or it just has a render method.
     * In both cases, we should error out with an explanatory message
     * @return void
     * @throws BLParserException
     **/
    function renderBeginTag()
    {
        $msg = "The tag 'xar:#(1)' implementation is incomplete (render or renderBegintag is missing), or the tag does not support the open form.";
        throw new BLParserException($this->tagName,$msg);
    }


    /**
     * End tag rendering, abstract catcher
     *
     * We probably never reach this, but it balances out nicely. (hint for refactoring there though)
     * @return void
     * @throws BLParserException
    **/
    function renderEndTag()
    {
        $msg = "The tag 'xar:#(1)' implementation is incomplete (render or renderEndtag is missing), or the tag does not support the open form.";
        throw new BLParserException($this->tagName,$msg);
    }

}

/**
 * xarTpl__EntityNode
 *
 * Base class for entity nodes
 *
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> true
 * isPHPCode -> true
 * needAssignment -> false
 * needParameter -> false
 * @package blocklayout
 */
abstract class xarTpl__EntityNode extends xarTpl__Node
{
    private   $entityType;
    protected $parameters;
    protected $hasExtras = false;

    /**
     * Constructor for entity nodes
     *
     * @return void
     * @todo   centralize the hasExtras in xarModUrl, i.e. dont hack it in here (see bug 3603)
     **/
    function __construct($parser, $tagName, $entityType, $parameters)
    {
        parent::__construct($parser, $tagName);
        // Register whether the entity is followed by extra params
        $this->hasExtras = $parser->peek(5) == '&amp;';
        $this->isPHPCode = true;
        $this->entityType = $entityType;
        $this->parameters = $parameters;
    }

    /**
     * Render the code, catcher
     *
     * @return void
     * @throws BLParserException
     **/
    function render()
    {
        $msg = "The entity '#(1)' did not implement a render method!!";
        throw new BLParserException($this->tagName,$msg);
    }
}

/**
 * xarTpl__InstructionNode
 *
 * Base class for instruction nodes
 *
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> true
 * isPHPCode -> true
 * needAssignment -> false
 * needParameter -> false
 * @package blocklayout
 */
class xarTpl__InstructionNode extends xarTpl__Node
{
    protected $instruction;

    function __construct($parser, $tagName, $instruction)
    {
        parent::__construct($parser,$tagName);
        $this->instruction = $instruction;
        $this->isPHPCode = true;

    }

    /**
     * Render the instruction code, abstrac catcher
     *
     * @return void
     * @throws BLParserException
     **/
    function render()
    {
        $msg = "The instruction '#(1)' did not implement a render method!!";
        throw new BLParserException($this->tagName,$msg);
    }
}
/**
 * xarTpl__DocumentNode
 *
 *
 * @package core
 * @subpackage blocklayout
 * hasChildren -> true
 * hasText -> true
 * isAssignable -> false
 * isPHPCode -> false
 * needAssignment -> false
 * needParameter -> false
 */
class xarTpl__DocumentNode extends xarTpl__Node
{
    public $children;
    public $variables;

    function __construct($parser, $nodeName)
    {
        parent::__construct($parser, $nodeName);
        $this->hasChildren = true;
        $this->hasText = true;
        $this->isAssignable = false;
        $this->isPHPCode = false;
    }

    // These 3 methods here are kinda weird.
    function render()
    { return ''; }

    function renderBeginTag()
    { return ''; }

    function renderEndTag()
    { return ''; }
}

/**
 * xarTpl__TextNode
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> false
 * isPHPCode -> false
 * needAssignment -> false
 * needParameter -> false
 * @package blocklayout
 */
class xarTpl__TextNode extends xarTpl__Node
{
    private $content;

    function __construct($parser, $tagName, $content)
    {
        parent::__construct($parser, $tagName);
        $this->content = $content;
        $this->isAssignable = false;
        $this->isPHPCode = false;
    }

    function render()
    {
        return $this->content;
    }
}


/**
 * Compresses space for output generation
 *
 * A helper function which compresses space around an input string.
 * This function regards 'space' in the xml sense i.e.:
 * - multiple spaces are equivalent to one
 * - only 'outside space' is considered, not space 'inside' the input
 * - when multiple whitespace chars are found, the first is returned
 * - cr's are preserved
 *
 * As the 'whitespace' problem is really unsolvable (by me) isolate it
 * here. If someone finds a solution, here's where it should happen
 *
 * @access  protected
 * @param   string $input String for which to compress space
*/
function xmltrim($input='')
{
    // Let's first determine if there is space at all.
    $hasleftspace = (strlen(ltrim($input)) != strlen($input));
    $hasrightspace = (strlen(rtrim($input)) != strlen($input));
    if($hasleftspace && $hasrightspace && trim($input) =='') {
        // There was more than one space, but only space, only return the first and
        // the carriage returns
        $hasleftspace = true;
        $hasrightspace= false;
    }
    // Isolate the left and the right space
    $leftspace  = $hasleftspace  ? substr($input,0,1) : '';
    $rightspace = $hasrightspace ? substr($input,-1) : '';

    // Make sure we consider the right rest of the input string
    if($hasleftspace) $input = substr($input,1);
    if($hasrightspace) $input = substr($input,0,-1);

    // Make 'almost right'
    $input = $leftspace . trim($input,' ') . $rightspace;
    // Finish it
    $input = str_replace(array(" \n","\n "),array("\n","\n"),$input);

    return $input;
}

/**
 * This doesn't do anything on purpose, please leave it in
 *
 */
function noop($input)
{
    return $input;
}
?>
