<?php
/**
 * Xarigami Fail Safe Interface Entry Point
 *
 * Please do not modify this file: editing this file in any way will prevent it from working.
 * If you are having issues, please drop into #xarigami room at irc://talk.xarigami.com
 *
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Core package
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
// To compute the time to get a response we start to count clock ticks from the first line.
if (!class_exists('sys')) include_once (getcwd().DIRECTORY_SEPARATOR.'bootstrap.php');
if (!class_exists('sys')) die('could not load the bootstrap');
//get the file system layout
sys::init(sys::MODE_FAILSAFE);

class xarFailure
{
    static $message = 'The server is temporarily unable to service your request to this website. Please try again later.';
    static $title = 'Service temporarily unavailable';
    static $code = 503;

    public static function setMsg($title, $message, $code = 503)
    {
        self::$title = $title;
        self::$message = $message;
        self::$code = $code;
    }

    public static function render()
    {
        ob_start();
        ob_end_clean();
        xarResponseBone::sendStatusCode(self::$code);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <style>
    body {
        font-family:  Verdana, Helevetica, sans-serif;
        font-size: large;
        margin: 2em;
    }
    div#content {
        text-align: center;
        padding: 2em;
        background-color: #F0FFFF;
        border: solid 1px #C0FFFF;
        border-radius: 10px;
        box-shadow: rgba(0,0,0,0.4) 0px 10px 15px;
    }
    </style>
    <title><?php echo self::$title ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="Generator" content="Xarigami Cumulus" />
</head>
<body>
    <div id="content">
        <?php echo self::$message ?>
    </div>
</body>
</html>
<?php

    }
}

if (sys::isFromFail()) xarFailure::render();
?>