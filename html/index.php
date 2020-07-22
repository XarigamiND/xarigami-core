<?php
/**
 * Xarigami Web Interface Entry Point
 *
 * Please DO NOT MODIFY THIS FILE: editing this file in any way will prevent it from working.
 * If you are having issues, please drop into #xarigami room at irc://talk.xarigami.com
 *
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Core package
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 *
 */
// To compute the time to get a response we start to count clock ticks from the first line.
$mt = microtime();
include_once (getcwd().DIRECTORY_SEPARATOR.'bootstrap.php');
if (!class_exists('sys')) die('could not load the bootstrap');
//get the file system layout
sys::init(sys::MODE_OPERATION);
// Store the clock start time
xarDebug::startTime($mt);

/**
 * Set up output caching if enabled
 * Note: this happens first so we can serve cached pages to first-time visitors
 *       without loading the core
 */
sys::import('xarigami.xarCache');
xarCache::init();

/**
 * Load the Xarigami core
 */
sys::import('xarigami.xarCore');

/**
 * Initialize the core and run the main method.
 */
xarCore::main();

?>