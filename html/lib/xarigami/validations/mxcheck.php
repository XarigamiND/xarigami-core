<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
*/


// Taken from http://www.zend.com/codex.php?id=449&single=1

/* =======================================================================

ifsnow's email valid check function SnowCheckMail Ver 0.1

funtion SnowCheckMail ($Email,$Debug=false)

$Email : E-Mail address to check.
$Debug : Variable for debugging.

* Can use everybody if use without changing the name of function.

Reference : O'REILLY - Internet Email Programming

HOMEPAGE : http://www.hellophp.com

ifsnow is korean phper. Is sorry to be unskillful to English. *^^*;;

========================================================================= */

/**
 * validate email
 * @return bool true if a valid domain is found, false if not
 */

class MxCheckValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {

        global $HTTP_HOST;

        // E-Mail @ by 2 by standard divide. if it is $Email this "lsm@ebeecomm.com"..
        // $Username : lsm
        // $Domain : ebeecomm.com
        // list function reference : http://www.php.net/manual/en/function.list.php
        // split function reference : http://www.php.net/manual/en/function.split.php
        // explode function reference: https://www.php.net/manual/en/function.explode.php
        list ( $Username, $Domain ) = explode ("@", $subject);

        // That MX(mail exchanger) record exists in domain check .
        // checkdnsrr function reference : http://www.php.net/manual/en/function.checkdnsrr.php
        if ( checkdnsrr ( $Domain, "MX" ) )  {

            // If MX record exists, save MX record address.
            // getmxrr function reference : http://www.php.net/manual/en/function.getmxrr.php
            getmxrr ($Domain, $MXHost);

            // Getmxrr function does to store MX record address about $Domain in arrangement form to $MXHost.
            // $ConnectAddress socket connection address.
            $ConnectAddress = $MXHost[0];
        }
        else {
            // If there is no MX record simply @ to next time address socket connection do .
            $ConnectAddress = $Domain;
        }

        // fsockopen function reference : http://www.php.net/manual/en/function.fsockopen.php
        $Connect = fsockopen ( $ConnectAddress, 25 );

        // Success in socket connection
        if ($Connect)
        {
            // Judgment is that service is preparing though begin by 220 getting string after connection .
            // fgets function reference : http://www.php.net/manual/en/function.fgets.php
            if ( mb_ereg ( "^220", $Out = fgets ( $Connect, 1024 ) ) ) {

                $Email = $Username."@".$Domain;

                // Inform client's reaching to server who connect.
                fputs ( $Connect, "HELO $HTTP_HOST\r\n" );
                $Out = fgets ( $Connect, 1024 ); // Receive server's answering cord.

                // Inform sender's address to server.
                fputs ( $Connect, "MAIL FROM: <{$Email}>\r\n" );
                $From = fgets ( $Connect, 1024 ); // Receive server's answering cord.

                // Inform listener's address to server.
                fputs ( $Connect, "RCPT TO: <{$Email}>\r\n" );
                $To = fgets ( $Connect, 1024 ); // Receive server's answering cord.

                // Finish connection.
                fputs ( $Connect, "QUIT\r\n");

                fclose($Connect);

                    // Server's answering cord about MAIL and TO command checks.
                    // Server about listener's address reacts to 550 codes if there does not exist
                    // checking that mailbox is in own E-Mail account.
                    if ( !mb_ereg ( "^250", $From ) || !mb_ereg ( "^250", $To )) {
                        //We should add some caching for these cases to avoid an excessive
                        // hardware consumption exploit thru sending many of these e-mails to be checked
                        $msg = xarML("Invalid e-mail #(1), the mail server doesnt recognize it.",$subject);
                        throw new VariableValidationException(null,$msg);
                    }
            }
        } else { // Failure in socket connection
            // @todo use try catch here
            // @todo CHECK: is this considered to be a validation exception?
            $msg = xarML("Unable to connect to the mail server #(1) for e-mail #(2).",$ConnectAddress, $subject);
            throw new VariableValidationException(null,$msg);
        }

        return true;
    }
}
?>
