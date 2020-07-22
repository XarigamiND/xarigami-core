<?php
/**
 * Simplified Timezone List
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Simplified timezone list (based on modules/timezone/tzdata.php)
 * (cfr. modules/timezone/xaradmin/regenerate.php)
 *
 * @param $args['timezone'] string timezone we're looking for (default all)
 * @param $args['time'] integer timestamp for the period we're interested in (unsupported)
 * @author the Base module development team
 * @return array containing the different timezones
 */
function base_userapi_timezones($args)
{
/*
    if (isset($time) && xarMod::isAvailable('timezone')) {
        // get time-dependent timezone information from the timezone module
        ...
        return $Zones;
    }
*/
    //Ok, we have some discrepancy with usage of timezone in the system and PHP and so on 0.o
    //Let's put this in as a double check and convert any UTC lying about
    if ($args['timezone'] = 'UTC') $args['timezone'] = 'Etc/UTC';
    $Zones = array();

    // Zone    NAME    GMTOFF    RULES    FORMAT    [UNTIL]

    $Zones['Africa/Abidjan'] = array('0:00', '-', 'GMT');
    $Zones['Africa/Accra'] = array('0:00', 'Ghana', '%s');
    $Zones['Africa/Addis_Ababa'] = array('3:00', '-', 'EAT');
    $Zones['Africa/Algiers'] = array('1:00', '-', 'CET');
    $Zones['Africa/Asmera'] = array('3:00', '-', 'EAT');
    $Zones['Africa/Bamako'] = array('0:00', '-', 'GMT');
    $Zones['Africa/Bangui'] = array('1:00', '-', 'WAT');
    $Zones['Africa/Banjul'] = array('0:00', '-', 'GMT');
    $Zones['Africa/Bissau'] = array('0:00', '-', 'GMT');
    $Zones['Africa/Blantyre'] = array('2:00', '-', 'CAT');
    $Zones['Africa/Brazzaville'] = array('1:00', '-', 'WAT');
    $Zones['Africa/Bujumbura'] = array('2:00', '-', 'CAT');
    $Zones['Africa/Cairo'] = array('2:00', 'Egypt', 'EE%sT');
    $Zones['Africa/Casablanca'] = array('0:00', '-', 'WET');
    $Zones['Africa/Ceuta'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Africa/Conakry'] = array('0:00', '-', 'GMT');
    $Zones['Africa/Dakar'] = array('0:00', '-', 'GMT');
    $Zones['Africa/Dar_es_Salaam'] = array('3:00', '-', 'EAT');
    $Zones['Africa/Djibouti'] = array('3:00', '-', 'EAT');
    $Zones['Africa/Douala'] = array('1:00', '-', 'WAT');
    $Zones['Africa/El_Aaiun'] = array('0:00', '-', 'WET');
    $Zones['Africa/Freetown'] = array('0:00', 'SL', '%s');
    $Zones['Africa/Gaborone'] = array('2:00', '-', 'CAT');
    $Zones['Africa/Harare'] = array('2:00', '-', 'CAT');
    $Zones['Africa/Johannesburg'] = array('2:00', 'SA', 'SAST');
    $Zones['Africa/Kampala'] = array('3:00', '-', 'EAT');
    $Zones['Africa/Khartoum'] = array('3:00', '-', 'EAT');
    $Zones['Africa/Kigali'] = array('2:00', '-', 'CAT');
    $Zones['Africa/Kinshasa'] = array('1:00', '-', 'WAT');
    $Zones['Africa/Lagos'] = array('1:00', '-', 'WAT');
    $Zones['Africa/Libreville'] = array('1:00', '-', 'WAT');
    $Zones['Africa/Lome'] = array('0:00', '-', 'GMT');
    $Zones['Africa/Luanda'] = array('1:00', '-', 'WAT');
    $Zones['Africa/Lubumbashi'] = array('2:00', '-', 'CAT');
    $Zones['Africa/Lusaka'] = array('2:00', '-', 'CAT');
    $Zones['Africa/Malabo'] = array('1:00', '-', 'WAT');
    $Zones['Africa/Maputo'] = array('2:00', '-', 'CAT');
    $Zones['Africa/Maseru'] = array('2:00', '-', 'SAST');
    $Zones['Africa/Mbabane'] = array('2:00', '-', 'SAST');
    $Zones['Africa/Mogadishu'] = array('3:00', '-', 'EAT');
    $Zones['Africa/Monrovia'] = array('0:00', '-', 'GMT');
    $Zones['Africa/Nairobi'] = array('3:00', '-', 'EAT');
    $Zones['Africa/Ndjamena'] = array('1:00', '-', 'WAT');
    $Zones['Africa/Niamey'] = array('1:00', '-', 'WAT');
    $Zones['Africa/Nouakchott'] = array('0:00', '-', 'GMT');
    $Zones['Africa/Ouagadougou'] = array('0:00', '-', 'GMT');
    $Zones['Africa/Porto-Novo'] = array('1:00', '-', 'WAT');
    $Zones['Africa/Sao_Tome'] = array('0:00', '-', 'GMT');
    $Zones['Africa/Timbuktu'] = array('0:00', '-', 'GMT');
    $Zones['Africa/Tripoli'] = array('2:00', '-', 'EET');
    $Zones['Africa/Tunis'] = array('1:00', 'Tunisia', 'CE%sT');
    $Zones['Africa/Windhoek'] = array('1:00', 'Namibia', 'WA%sT');
    $Zones['America/Adak'] = array('-10:00', 'US', 'HA%sT');
    $Zones['America/Anchorage'] = array('-9:00', 'US', 'AK%sT');
    $Zones['America/Anguilla'] = array('-4:00', '-', 'AST');
    $Zones['America/Antigua'] = array('-4:00', '-', 'AST');
    $Zones['America/Araguaina'] = array('-3:00', '-', 'BRT');
    $Zones['America/Aruba'] = array('-4:00', '-', 'AST');
    $Zones['America/Asuncion'] = array('-4:00', 'Para', 'PY%sT');
    $Zones['America/Bahia'] = array('-3:00', '-', 'BRT');
    $Zones['America/Barbados'] = array('-4:00', 'Barb', 'A%sT');
    $Zones['America/Belem'] = array('-3:00', '-', 'BRT');
    $Zones['America/Belize'] = array('-6:00', 'Belize', 'C%sT');
    $Zones['America/Boa_Vista'] = array('-4:00', '-', 'AMT');
    $Zones['America/Bogota'] = array('-5:00', 'CO', 'CO%sT');
    $Zones['America/Boise'] = array('-7:00', 'US', 'M%sT');
// alias of Sao_Paulo for bug 4156 - re-apply when this gets regenerated by the timezone module
    $Zones['America/Brasilia'] = array('-3:00', 'Brazil', 'BR%sT');
    $Zones['America/Buenos_Aires'] = array('-3:00', '-', 'ART');
    $Zones['America/Cambridge_Bay'] = array('-7:00', 'Canada', 'M%sT');
    $Zones['America/Campo_Grande'] = array('-4:00', 'Brazil', 'AM%sT');
    $Zones['America/Cancun'] = array('-6:00', 'Mexico', 'C%sT');
    $Zones['America/Caracas'] = array('-4:00', '-', 'VET');
    $Zones['America/Catamarca'] = array('-3:00', '-', 'ART');
    $Zones['America/Cayenne'] = array('-3:00', '-', 'GFT');
    $Zones['America/Cayman'] = array('-5:00', '-', 'EST');
    $Zones['America/Chicago'] = array('-6:00', 'US', 'C%sT');
    $Zones['America/Chihuahua'] = array('-7:00', 'Mexico', 'M%sT');
    $Zones['America/Cordoba'] = array('-3:00', '-', 'ART');
    $Zones['America/Costa_Rica'] = array('-6:00', 'CR', 'C%sT');
    $Zones['America/Cuiaba'] = array('-4:00', '-', 'AMT');
    $Zones['America/Curacao'] = array('-4:00', '-', 'AST');
    $Zones['America/Danmarkshavn'] = array('0:00', '-', 'GMT');
    $Zones['America/Dawson'] = array('-8:00', 'NT_YK', 'P%sT');
    $Zones['America/Dawson_Creek'] = array('-7:00', '-', 'MST');
    $Zones['America/Denver'] = array('-7:00', 'US', 'M%sT');
    $Zones['America/Detroit'] = array('-5:00', 'US', 'E%sT');
    $Zones['America/Dominica'] = array('-4:00', '-', 'AST');
    $Zones['America/Edmonton'] = array('-7:00', 'Edm', 'M%sT');
    $Zones['America/Eirunepe'] = array('-5:00', '-', 'ACT');
    $Zones['America/El_Salvador'] = array('-6:00', 'Salv', 'C%sT');
    $Zones['America/Fortaleza'] = array('-3:00', '-', 'BRT');
    $Zones['America/Glace_Bay'] = array('-4:00', 'Canada', 'A%sT');
    $Zones['America/Godthab'] = array('-3:00', 'EU', 'WG%sT');
    $Zones['America/Goose_Bay'] = array('-4:00', 'StJohns', 'A%sT');
    $Zones['America/Grand_Turk'] = array('-5:00', 'TC', 'E%sT');
    $Zones['America/Grenada'] = array('-4:00', '-', 'AST');
    $Zones['America/Guadeloupe'] = array('-4:00', '-', 'AST');
    $Zones['America/Guatemala'] = array('-6:00', 'Guat', 'C%sT');
    $Zones['America/Guayaquil'] = array('-5:00', '-', 'ECT');
    $Zones['America/Guyana'] = array('-4:00', '-', 'GYT');
    $Zones['America/Halifax'] = array('-4:00', 'Canada', 'A%sT');
    $Zones['America/Havana'] = array('-5:00', 'Cuba', 'C%sT');
    $Zones['America/Hermosillo'] = array('-7:00', '-', 'MST');
    $Zones['America/Indiana/Knox'] = array('-5:00', '-', 'EST');
    $Zones['America/Indiana/Marengo'] = array('-5:00', '-', 'EST');
    $Zones['America/Indiana/Vevay'] = array('-5:00', '-', 'EST');
    $Zones['America/Indianapolis'] = array('-5:00', '-', 'EST');
    $Zones['America/Inuvik'] = array('-7:00', 'NT_YK', 'M%sT');
    $Zones['America/Iqaluit'] = array('-5:00', 'Canada', 'E%sT');
    $Zones['America/Jamaica'] = array('-5:00', '-', 'EST');
    $Zones['America/Jujuy'] = array('-3:00', '-', 'ART');
    $Zones['America/Juneau'] = array('-9:00', 'US', 'AK%sT');
    $Zones['America/Kentucky/Monticello'] = array('-5:00', 'US', 'E%sT');
    $Zones['America/La_Paz'] = array('-4:00', '-', 'BOT');
    $Zones['America/Lima'] = array('-5:00', 'Peru', 'PE%sT');
    $Zones['America/Los_Angeles'] = array('-8:00', 'US', 'P%sT');
    $Zones['America/Louisville'] = array('-5:00', 'US', 'E%sT');
    $Zones['America/Maceio'] = array('-3:00', '-', 'BRT');
    $Zones['America/Managua'] = array('-6:00', '-', 'CST');
    $Zones['America/Manaus'] = array('-4:00', '-', 'AMT');
    $Zones['America/Martinique'] = array('-4:00', '-', 'AST');
    $Zones['America/Mazatlan'] = array('-7:00', 'Mexico', 'M%sT');
    $Zones['America/Mendoza'] = array('-3:00', '-', 'ART');
    $Zones['America/Menominee'] = array('-6:00', 'US', 'C%sT');
    $Zones['America/Merida'] = array('-6:00', 'Mexico', 'C%sT');
    $Zones['America/Mexico_City'] = array('-6:00', 'Mexico', 'C%sT');
    $Zones['America/Miquelon'] = array('-3:00', 'Canada', 'PM%sT');
    $Zones['America/Monterrey'] = array('-6:00', 'Mexico', 'C%sT');
    $Zones['America/Montevideo'] = array('-3:00', 'Uruguay', 'UY%sT');
    $Zones['America/Montreal'] = array('-5:00', 'Canada', 'E%sT');
    $Zones['America/Montserrat'] = array('-4:00', '-', 'AST');
    $Zones['America/Nassau'] = array('-5:00', 'Bahamas', 'E%sT');
    $Zones['America/New_York'] = array('-5:00', 'US', 'E%sT');
    $Zones['America/Nipigon'] = array('-5:00', 'Canada', 'E%sT');
    $Zones['America/Nome'] = array('-9:00', 'US', 'AK%sT');
    $Zones['America/Noronha'] = array('-2:00', '-', 'FNT');
    $Zones['America/North_Dakota/Center'] = array('-6:00', 'US', 'C%sT');
    $Zones['America/Panama'] = array('-5:00', '-', 'EST');
    $Zones['America/Pangnirtung'] = array('-5:00', 'Canada', 'E%sT');
    $Zones['America/Paramaribo'] = array('-3:00', '-', 'SRT');
    $Zones['America/Phoenix'] = array('-7:00', '-', 'MST');
    $Zones['America/Port-au-Prince'] = array('-5:00', 'Haiti', 'E%sT');
    $Zones['America/Port_of_Spain'] = array('-4:00', '-', 'AST');
    $Zones['America/Porto_Velho'] = array('-4:00', '-', 'AMT');
    $Zones['America/Puerto_Rico'] = array('-4:00', '-', 'AST');
    $Zones['America/Rainy_River'] = array('-6:00', 'Canada', 'C%sT');
    $Zones['America/Rankin_Inlet'] = array('-6:00', 'Canada', 'C%sT');
    $Zones['America/Recife'] = array('-3:00', '-', 'BRT');
    $Zones['America/Regina'] = array('-6:00', '-', 'CST');
    $Zones['America/Rio_Branco'] = array('-5:00', '-', 'ACT');
    $Zones['America/Santiago'] = array('-4:00', 'Chile', 'CL%sT');
    $Zones['America/Santo_Domingo'] = array('-4:00', '-', 'AST');
    $Zones['America/Sao_Paulo'] = array('-3:00', 'Brazil', 'BR%sT');
    $Zones['America/Scoresbysund'] = array('-1:00', 'EU', 'EG%sT');
    $Zones['America/St_Johns'] = array('-3:30', 'StJohns', 'N%sT');
    $Zones['America/St_Kitts'] = array('-4:00', '-', 'AST');
    $Zones['America/St_Lucia'] = array('-4:00', '-', 'AST');
    $Zones['America/St_Thomas'] = array('-4:00', '-', 'AST');
    $Zones['America/St_Vincent'] = array('-4:00', '-', 'AST');
    $Zones['America/Swift_Current'] = array('-6:00', '-', 'CST');
    $Zones['America/Tegucigalpa'] = array('-6:00', 'Salv', 'C%sT');
    $Zones['America/Thule'] = array('-4:00', 'Thule', 'A%sT');
    $Zones['America/Thunder_Bay'] = array('-5:00', 'Canada', 'E%sT');
    $Zones['America/Tijuana'] = array('-8:00', 'Mexico', 'P%sT');
    $Zones['America/Toronto'] = array('-5:00', 'Canada', 'E%sT');
    $Zones['America/Tortola'] = array('-4:00', '-', 'AST');
    $Zones['America/Vancouver'] = array('-8:00', 'Vanc', 'P%sT');
    $Zones['America/Whitehorse'] = array('-8:00', 'NT_YK', 'P%sT');
    $Zones['America/Winnipeg'] = array('-6:00', 'Winn', 'C%sT');
    $Zones['America/Yakutat'] = array('-9:00', 'US', 'AK%sT');
    $Zones['America/Yellowknife'] = array('-7:00', 'NT_YK', 'M%sT');
    $Zones['Antarctica/Casey'] = array('8:00', '-', 'WST');
    $Zones['Antarctica/Davis'] = array('7:00', '-', 'DAVT');
    $Zones['Antarctica/DumontDUrville'] = array('10:00', '-', 'DDUT');
    $Zones['Antarctica/Mawson'] = array('6:00', '-', 'MAWT');
    $Zones['Antarctica/McMurdo'] = array('12:00', 'NZAQ', 'NZ%sT');
    $Zones['Antarctica/Palmer'] = array('-4:00', 'ChileAQ', 'CL%sT');
    $Zones['Antarctica/Rothera'] = array('-3:00', '-', 'ROTT');
    $Zones['Antarctica/Syowa'] = array('3:00', '-', 'SYOT');
    $Zones['Antarctica/Vostok'] = array('6:00', '-', 'VOST');
    $Zones['Asia/Aden'] = array('3:00', '-', 'AST');
    $Zones['Asia/Almaty'] = array('6:00', '-', 'ALMT');
    $Zones['Asia/Amman'] = array('2:00', 'Jordan', 'EE%sT');
    $Zones['Asia/Anadyr'] = array('12:00', 'Russia', 'ANA%sT');
    $Zones['Asia/Aqtau'] = array('5:00', '-', 'AQTT');
    $Zones['Asia/Aqtobe'] = array('5:00', '-', 'AQTT');
    $Zones['Asia/Ashgabat'] = array('5:00', '-', 'TMT');
    $Zones['Asia/Baghdad'] = array('3:00', 'Iraq', 'A%sT');
    $Zones['Asia/Bahrain'] = array('3:00', '-', 'AST');
    $Zones['Asia/Baku'] = array('4:00', 'Azer', 'AZ%sT');
    $Zones['Asia/Bangkok'] = array('7:00', '-', 'ICT');
    $Zones['Asia/Beirut'] = array('2:00', 'Lebanon', 'EE%sT');
    $Zones['Asia/Bishkek'] = array('5:00', 'Kirgiz', 'KG%sT');
    $Zones['Asia/Brunei'] = array('8:00', '-', 'BNT');
    $Zones['Asia/Calcutta'] = array('5:30', '-', 'IST');
    $Zones['Asia/Choibalsan'] = array('9:00', 'Mongol', 'CHO%sT');
    $Zones['Asia/Chongqing'] = array('8:00', 'PRC', 'C%sT');
    $Zones['Asia/Colombo'] = array('6:00', '-', 'LKT');
    $Zones['Asia/Damascus'] = array('2:00', 'Syria', 'EE%sT');
    $Zones['Asia/Dhaka'] = array('6:00', '-', 'BDT');
    $Zones['Asia/Dili'] = array('9:00', '-', 'TPT');
    $Zones['Asia/Dubai'] = array('4:00', '-', 'GST');
    $Zones['Asia/Dushanbe'] = array('5:00', '-', 'TJT');
    $Zones['Asia/Gaza'] = array('2:00', 'Palestine', 'EE%sT');
    $Zones['Asia/Harbin'] = array('8:00', 'PRC', 'C%sT');
    $Zones['Asia/Hong_Kong'] = array('8:00', 'HK', 'HK%sT');
    $Zones['Asia/Hovd'] = array('7:00', 'Mongol', 'HOV%sT');
    $Zones['Asia/Irkutsk'] = array('8:00', 'Russia', 'IRK%sT');
    $Zones['Asia/Jakarta'] = array('7:00', '-', 'WIT');
    $Zones['Asia/Jayapura'] = array('9:00', '-', 'EIT');
    $Zones['Asia/Jerusalem'] = array('2:00', 'Zion', 'I%sT');
    $Zones['Asia/Kabul'] = array('4:30', '-', 'AFT');
    $Zones['Asia/Kamchatka'] = array('12:00', 'Russia', 'PET%sT');
    $Zones['Asia/Karachi'] = array('5:00', 'Pakistan', 'PK%sT');
    $Zones['Asia/Kashgar'] = array('8:00', 'PRC', 'C%sT');
    $Zones['Asia/Katmandu'] = array('5:45', '-', 'NPT');
    $Zones['Asia/Krasnoyarsk'] = array('7:00', 'Russia', 'KRA%sT');
    $Zones['Asia/Kuala_Lumpur'] = array('8:00', '-', 'MYT');
    $Zones['Asia/Kuching'] = array('8:00', '-', 'MYT');
    $Zones['Asia/Kuwait'] = array('3:00', '-', 'AST');
    $Zones['Asia/Macau'] = array('8:00', 'PRC', 'C%sT');
    $Zones['Asia/Magadan'] = array('11:00', 'Russia', 'MAG%sT');
    $Zones['Asia/Makassar'] = array('8:00', '-', 'CIT');
    $Zones['Asia/Manila'] = array('8:00', 'Phil', 'PH%sT');
    $Zones['Asia/Muscat'] = array('4:00', '-', 'GST');
    $Zones['Asia/Nicosia'] = array('2:00', 'EUAsia', 'EE%sT');
    $Zones['Asia/Novosibirsk'] = array('6:00', 'Russia', 'NOV%sT');
    $Zones['Asia/Omsk'] = array('6:00', 'Russia', 'OMS%sT');
    $Zones['Asia/Oral'] = array('5:00', '-', 'ORAT');
    $Zones['Asia/Phnom_Penh'] = array('7:00', '-', 'ICT');
    $Zones['Asia/Pontianak'] = array('7:00', '-', 'WIT');
    $Zones['Asia/Pyongyang'] = array('9:00', '-', 'KST');
    $Zones['Asia/Qatar'] = array('3:00', '-', 'AST');
    $Zones['Asia/Qyzylorda'] = array('6:00', '-', 'QYZT');
    $Zones['Asia/Rangoon'] = array('6:30', '-', 'MMT');
    $Zones['Asia/Riyadh'] = array('3:00', '-', 'AST');
    $Zones['Asia/Saigon'] = array('7:00', '-', 'ICT');
    $Zones['Asia/Sakhalin'] = array('10:00', 'Russia', 'SAK%sT');
    $Zones['Asia/Samarkand'] = array('5:00', '-', 'UZT');
    $Zones['Asia/Seoul'] = array('9:00', 'ROK', 'K%sT');
    $Zones['Asia/Shanghai'] = array('8:00', 'PRC', 'C%sT');
    $Zones['Asia/Singapore'] = array('8:00', '-', 'SGT');
    $Zones['Asia/Taipei'] = array('8:00', 'Taiwan', 'C%sT');
    $Zones['Asia/Tashkent'] = array('5:00', '-', 'UZT');
    $Zones['Asia/Tbilisi'] = array('4:00', 'E-EurAsia', 'GE%sT');
    $Zones['Asia/Tehran'] = array('3:30', 'Iran', 'IR%sT');
    $Zones['Asia/Thimphu'] = array('6:00', '-', 'BTT');
    $Zones['Asia/Tokyo'] = array('9:00', '-', 'JST');
    $Zones['Asia/Ulaanbaatar'] = array('8:00', 'Mongol', 'ULA%sT');
    $Zones['Asia/Urumqi'] = array('8:00', 'PRC', 'C%sT');
    $Zones['Asia/Vientiane'] = array('7:00', '-', 'ICT');
    $Zones['Asia/Vladivostok'] = array('10:00', 'Russia', 'VLA%sT');
    $Zones['Asia/Yakutsk'] = array('9:00', 'Russia', 'YAK%sT');
    $Zones['Asia/Yekaterinburg'] = array('5:00', 'Russia', 'YEK%sT');
    $Zones['Asia/Yerevan'] = array('4:00', 'RussiaAsia', 'AM%sT');
    $Zones['Atlantic/Azores'] = array('-1:00', 'EU', 'AZO%sT');
    $Zones['Atlantic/Bermuda'] = array('-4:00', 'Bahamas', 'A%sT');
    $Zones['Atlantic/Canary'] = array('0:00', 'EU', 'WE%sT');
    $Zones['Atlantic/Cape_Verde'] = array('-1:00', '-', 'CVT');
    $Zones['Atlantic/Faeroe'] = array('0:00', 'EU', 'WE%sT');
    $Zones['Atlantic/Madeira'] = array('0:00', 'EU', 'WE%sT');
    $Zones['Atlantic/Reykjavik'] = array('0:00', '-', 'GMT');
    $Zones['Atlantic/South_Georgia'] = array('-2:00', '-', 'GST');
    $Zones['Atlantic/St_Helena'] = array('0:00', '-', 'GMT');
    $Zones['Atlantic/Stanley'] = array('-4:00', 'Falk', 'FK%sT');
    $Zones['Australia/Adelaide'] = array('9:30', 'AS', 'CST');
    $Zones['Australia/Brisbane'] = array('10:00', 'AQ', 'EST');
    $Zones['Australia/Broken_Hill'] = array('9:30', 'AS', 'CST');
    $Zones['Australia/Darwin'] = array('9:30', 'Aus', 'CST');
    $Zones['Australia/Hobart'] = array('10:00', 'AT', 'EST');
    $Zones['Australia/Lindeman'] = array('10:00', 'Holiday', 'EST');
    $Zones['Australia/Lord_Howe'] = array('10:30', 'LH', 'LHST');
    $Zones['Australia/Melbourne'] = array('10:00', 'AV', 'EST');
    $Zones['Australia/Perth'] = array('8:00', '-', 'WST');
    $Zones['Australia/Sydney'] = array('10:00', 'AN', 'EST');
    $Zones['Etc/UTC'] =  array('00:00', '-', 'UTC');
    $Zones['Etc/GMT'] =  array('00:00', '-', 'GMT');
    $Zones['Etc/Universal']= array('00:00', '-', 'GMT');
    $Zones['Europe/Amsterdam'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Andorra'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Athens'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Belfast'] = array('0:00', 'EU', 'GMT/BST');
    $Zones['Europe/Belgrade'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Berlin'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Brussels'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Bucharest'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Budapest'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Chisinau'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Copenhagen'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Dublin'] = array('0:00', 'EU', 'GMT/IST');
    $Zones['Europe/Gibraltar'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Helsinki'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Istanbul'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Kaliningrad'] = array('2:00', 'Russia', 'EE%sT');
    $Zones['Europe/Kiev'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Lisbon'] = array('0:00', 'EU', 'WE%sT');
    $Zones['Europe/London'] = array('0:00', 'EU', 'GMT/BST');
    $Zones['Europe/Luxembourg'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Madrid'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Malta'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Minsk'] = array('2:00', 'Russia', 'EE%sT');
    $Zones['Europe/Monaco'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Moscow'] = array('3:00', 'Russia', 'MSK/MSD');
    $Zones['Europe/Oslo'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Paris'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Prague'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Riga'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Rome'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Samara'] = array('4:00', 'Russia', 'SAM%sT');
    $Zones['Europe/Simferopol'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Sofia'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Stockholm'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Tallinn'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Tirane'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Uzhgorod'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Vaduz'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Vienna'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Vilnius'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Warsaw'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Europe/Zaporozhye'] = array('2:00', 'EU', 'EE%sT');
    $Zones['Europe/Zurich'] = array('1:00', 'EU', 'CE%sT');
    $Zones['Indian/Antananarivo'] = array('3:00', '-', 'EAT');
    $Zones['Indian/Chagos'] = array('6:00', '-', 'IOT');
    $Zones['Indian/Christmas'] = array('7:00', '-', 'CXT');
    $Zones['Indian/Cocos'] = array('6:30', '-', 'CCT');
    $Zones['Indian/Comoro'] = array('3:00', '-', 'EAT');
    $Zones['Indian/Kerguelen'] = array('5:00', '-', 'TFT');
    $Zones['Indian/Mahe'] = array('4:00', '-', 'SCT');
    $Zones['Indian/Maldives'] = array('5:00', '-', 'MVT');
    $Zones['Indian/Mauritius'] = array('4:00', '-', 'MUT');
    $Zones['Indian/Mayotte'] = array('3:00', '-', 'EAT');
    $Zones['Indian/Reunion'] = array('4:00', '-', 'RET');
    $Zones['Pacific/Apia'] = array('-11:00', '-', 'WST');
    $Zones['Pacific/Auckland'] = array('12:00', 'NZ', 'NZ%sT');
    $Zones['Pacific/Chatham'] = array('12:45', 'Chatham', 'CHA%sT');
    $Zones['Pacific/Easter'] = array('-6:00', 'Chile', 'EAS%sT');
    $Zones['Pacific/Efate'] = array('11:00', 'Vanuatu', 'VU%sT');
    $Zones['Pacific/Enderbury'] = array('13:00', '-', 'PHOT');
    $Zones['Pacific/Fakaofo'] = array('-10:00', '-', 'TKT');
    $Zones['Pacific/Fiji'] = array('12:00', 'Fiji', 'FJ%sT');
    $Zones['Pacific/Funafuti'] = array('12:00', '-', 'TVT');
    $Zones['Pacific/Galapagos'] = array('-6:00', '-', 'GALT');
    $Zones['Pacific/Gambier'] = array('-9:00', '-', 'GAMT');
    $Zones['Pacific/Guadalcanal'] = array('11:00', '-', 'SBT');
    $Zones['Pacific/Guam'] = array('10:00', '-', 'ChST');
    $Zones['Pacific/Honolulu'] = array('-10:00', '-', 'HST');
    $Zones['Pacific/Johnston'] = array('-10:00', '-', 'HST');
    $Zones['Pacific/Kiritimati'] = array('14:00', '-', 'LINT');
    $Zones['Pacific/Kosrae'] = array('11:00', '-', 'KOST');
    $Zones['Pacific/Kwajalein'] = array('12:00', '-', 'MHT');
    $Zones['Pacific/Majuro'] = array('12:00', '-', 'MHT');
    $Zones['Pacific/Marquesas'] = array('-9:30', '-', 'MART');
    $Zones['Pacific/Midway'] = array('-11:00', '-', 'SST');
    $Zones['Pacific/Nauru'] = array('12:00', '-', 'NRT');
    $Zones['Pacific/Niue'] = array('-11:00', '-', 'NUT');
    $Zones['Pacific/Norfolk'] = array('11:30', '-', 'NFT');
    $Zones['Pacific/Noumea'] = array('11:00', 'NC', 'NC%sT');
    $Zones['Pacific/Pago_Pago'] = array('-11:00', '-', 'SST');
    $Zones['Pacific/Palau'] = array('9:00', '-', 'PWT');
    $Zones['Pacific/Pitcairn'] = array('-8:00', '-', 'PST');
    $Zones['Pacific/Ponape'] = array('11:00', '-', 'PONT');
    $Zones['Pacific/Port_Moresby'] = array('10:00', '-', 'PGT');
    $Zones['Pacific/Rarotonga'] = array('-10:00', 'Cook', 'CK%sT');
    $Zones['Pacific/Saipan'] = array('10:00', '-', 'ChST');
    $Zones['Pacific/Tahiti'] = array('-10:00', '-', 'TAHT');
    $Zones['Pacific/Tarawa'] = array('12:00', '-', 'GILT');
    $Zones['Pacific/Tongatapu'] = array('13:00', 'Tonga', 'TO%sT');
    $Zones['Pacific/Truk'] = array('10:00', '-', 'TRUT');
    $Zones['Pacific/Wake'] = array('12:00', '-', 'WAKT');
    $Zones['Pacific/Wallis'] = array('12:00', '-', 'WFT');
    $Zones['Pacific/Yap'] = array('10:00', '-', 'YAPT');

    if (isset($args['timezone'])) {
        if (!empty($args['timezone']) && isset($Zones[$args['timezone']])) {
            return $Zones[$args['timezone']];
        } else {
            return array();
        }
    } else {
        return $Zones;
    }
}
?>