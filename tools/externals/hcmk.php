<?php
    // Quick way to determine the root
    $__path__ = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : $_ENV['DOCUMENT_ROOT'];
    if (DIRECTORY_SEPARATOR === '\\') $__path__ = str_replace('\\', '/', $__path__);
    $__path__ = rtrim($__path__, '/').'/';
  
    include_once $__path__.DIRECTORY_SEPARATOR.'bootstrap.php';
    // Just in case the file is missing for some reason
    if (!class_exists('sys')) die('could not load the bootstrap');
    sys::init(sys::MODE_HCMK);
    
    // Starts cache
    sys::import('xarigami.xarCache');
    xarCache::init();

    // Load the Xarigami core
    sys::import('xarigami.xarCore');
    sys::import('xarigami.xarUser');
    xarCoreInit(xarCore::BIT_DATABASE | xarCore::BIT_CONFIGURATION | xarCore::BIT_MODULES | xarCore::BIT_SESSION | xarCore::BIT_USER | xarCore::BIT_HOOKS);
    

    
    if (!xarUserIsLoggedIn() && !xarSecurityCheck('Adminbase', 0)) {
        echo xarResponse::Forbidden();
        exit();
    }
    
    if (!xarVarFetch('dir', 'str:1:',$dir, '', XARVAR_NOT_REQUIRED)) return;
    
    $fm = sys::getfm(); extract($fm);
    $output = '';
    $k = '\'';
    $c = count($fId);
    for ($i=0; $i < $c; $i++) {
        $file = new xarFileSigned($mId[$i] === sys::MODE_HCMK ? xarPath::make(getcwd(), xarPath::MODE_ABSOLUTE) : sys::$pathWebRoot, $fId[$i]);
        $k .= $file->check($mId[$i], $fm, NULL);
    }
    $k .= '\';';
    $output .= '<label>Main key: </label><input type="text" value="'.$k.'" name="core" id="core" />';
    $output .= "\n";
    $output .= '<br /><br /><hr /><br />';
    $output .= "\n";
    $oPath = xarPath::makeFromWeb($dir);
    $dir = $oPath->forWeb();
    $output .= 'Your web root is: '.sys::$pathWeb->getAbs(TRUE). '<br />';
    $output .= "\n";
    $output .= '<em>All directories below are relative to this web root</em><br /><br />';
    $output .= '<label>Directory: </label><input type="text" size="64" value="'.$dir.'" name="dir" id="dir" /><input type="submit" />';
    
    $ext = array();
    if (!is_dir(($oPath->getAbs()))) {
        $output .= '<br /><span style="color: red" >Directory is invalid or cannot be found!</span>';
    } else {
        // $output .= '<br /><br /><b>Files found:</b><br />';
        // $output .= "\n";
        if ($handle = opendir($oPath->getAbs())) {
            while (FALSE !== ($file = readdir($handle))) {
                if ($file === '.' || $file === '..' || is_dir($oPath->getAbs().$file)) continue;
                $oFile = new xarFileSigned($oPath, $file);
                if ($oFile->getExtension() !== 'php') continue;
                $chk = $oFile->check(sys::MODE_EXTERNAL, $fm, NULL);
                // $output .=  $oFile->getWebUrl() . ' ' .$chk . '<br />';
                // $output .= "\n";
                $ext[$chk] = $oFile->getWebUrl();
            }
            closedir($handle);
        }
        $output .= "\n";
        $output .= '<br /><br />';
        $output .= "\n";
        $strExt =  "<?php\n".'$extConfiguration = '.var_export($ext, TRUE)."\n?>\n";
        $output .= '<textarea cols="150" rows="15">' .$strExt. '</textarea>';
    }
    

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>Xarigami HCMK tool</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="Generator" content="Xarigami Cumulus" />
</head>
<body>
    <div>
        <form action="<?php echo xarRequestBone::getWebUrl() ?>" method="POST">
            <?php echo $output; ?>
        </form>
    </div>
</body>
</html>
<?php // to pass QA test ?>