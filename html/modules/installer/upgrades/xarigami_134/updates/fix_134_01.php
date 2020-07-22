<?php
// This checks to see if there is a system configuration line with DB.Charset eg
//$systemConfiguration['DB.Charset']   =  'utf8'; //the default character set of your database
function fix_134_01()
{
    // Define parameters
    // Define the task and result
    //This is a long shot - we don't know what's in their current config.system.php file or write state
    $data['success'] = true;
    $data['task'] = xarML("Trying to update DB.Charset entry in config.system.php");
    $data['reply'] = xarML("Done!");
    $configfile = sys::varpath() . '/config.system.php';
    $testfile = file($configfile);
    $testvars = array('DB.Charset');
    $checkarray = array();
    foreach ($testfile as $dataline) {
        foreach ($testvars as $var) {
            $checkvar = preg_match('/\[\''.$var.'\'\]/', $dataline, $matches);
            if (isset($matches[0]) && !empty($matches[0])) {
                $checkarray[$var] = true;
                break;
            }
        }
    }
    $fixinfo = '
// Database Charset: the charset of the database.
    ';
    $fixarray = array(
        'DB.Charset'=>"\$systemConfiguration['DB.Charset']   =  'utf8'; //the default character set of your database",
        );

    if (count($checkarray)< 1) {
        $configlines = '';
    //ok we'll try and add the lines
        foreach ($testvars as $var) {
            if (isset($checkarray[$var]) && $checkarrag[$var] ==1) {
                break; //it's already there
            } else {
                //collect lines
                $configlines .= $fixarray[$var]."\n";
            }
        }
        if (!empty($configlines)) { //try and write to config
            try {
                $configdata = join('', $testfile);
                $newdata = '<?php'."\n".$fixinfo."\n".$configlines;
                //we need to insert this at a known place .. perhaps after the <?php
                $configdata = preg_replace('/\<\?php/',$newdata,$configdata);
                $fp = fopen ($configfile, 'wb');
                fwrite ($fp,$configdata);
                fclose($fp);
            } catch (Exception $e) {
                $data['success'] = false;
                $data['reply'] = xarML("Failed!");
            }
        }
    }
    return $data;
}

?>