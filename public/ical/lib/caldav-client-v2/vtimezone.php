<?php
/**
* Extend the vComponent to specifically handle VTIMEZONE resources
*/

require_once('AwlQuery.php');
require_once('vComponent.php');

class VTimezone extends vComponent {

  static function getInstance($name) {
    $qry = new AwlQuery('SELECT * FROM timezones WHERE tzid = ? ORDER BY active DESC', $name);
    if ( $qry->Exec('VTimezone',__LINE__,__FILE__) && $qry->rows() > 0 && $row = $qry->Fetch() ) {
      $vtz = new vComponent($row->vtimezone);
      if ( $vtz->GetType() == 'VTIMEZONE' ) return $vtz;
      $tmp = $vtz->GetComponents('VTIMEZONE');
      if ( count($tmp) < 1 || $tmp[0]->GetType() != 'VTIMEZONE' ) return null;
      $vtz = $tmp[0];
      return $vtz;
    }
    return null;
  }

  static function msCdoToOlson($tzcdo) {
    switch( $tzcdo ) {
      /**
       * List of Microsoft CDO Timezone IDs from here:
       * http://msdn.microsoft.com/en-us/library/aa563018%28loband%29.aspx
       */
      case 0:    return('UTC');
      case 1:    return('Europe/London');
      case 2:    return('Europe/Lisbon');
      case 3:    return('Europe/Paris');
      case 4:    return('Europe/Berlin');
      case 5:    return('Europe/Bucharest');
      case 6:    return('Europe/Prague');
      case 7:    return('Europe/Athens');
      case 8:    return('America/Brasilia');
      case 9:    return('America/Halifax');
      case 10:   return('America/New_York');
      case 11:   return('America/Chicago');
      case 12:   return('America/Denver');
      case 13:   return('America/Los_Angeles');
      case 14:   return('America/Anchorage');
      case 15:   return('Pacific/Honolulu');
      case 16:   return('Pacific/Apia');
      case 17:   return('Pacific/Auckland');
      case 18:   return('Australia/Brisbane');
      case 19:   return('Australia/Adelaide');
      case 20:   return('Asia/Tokyo');
      case 21:   return('Asia/Singapore');
      case 22:   return('Asia/Bangkok');
      case 23:   return('Asia/Kolkata');
      case 24:   return('Asia/Muscat');
      case 25:   return('Asia/Tehran');
      case 26:   return('Asia/Baghdad');
      case 27:   return('Asia/Jerusalem');
      case 28:   return('America/St_Johns');
      case 29:   return('Atlantic/Azores');
      case 30:   return('America/Noronha');
      case 31:   return('Africa/Casablanca');
      case 32:   return('America/Argentina/Buenos_Aires');
      case 33:   return('America/La_Paz');
      case 34:   return('America/Indiana/Indianapolis');
      case 35:   return('America/Bogota');
      case 36:   return('America/Regina');
      case 37:   return('America/Tegucigalpa');
      case 38:   return('America/Phoenix');
      case 39:   return('Pacific/Kwajalein');
      case 40:   return('Pacific/Fiji');
      case 41:   return('Asia/Magadan');
      case 42:   return('Australia/Hobart');
      case 43:   return('Pacific/Guam');
      case 44:   return('Australia/Darwin');
      case 45:   return('Asia/Shanghai');
      case 46:   return('Asia/Novosibirsk');
      case 47:   return('Asia/Karachi');
      case 48:   return('Asia/Kabul');
      case 49:   return('Africa/Cairo');
      case 50:   return('Africa/Harare');
      case 51:   return('Europe/Moscow');
      case 53:   return('Atlantic/Cape_Verde');
      case 54:   return('Asia/Yerevan');
      case 55:   return('America/Panama');
      case 56:   return('Africa/Nairobi');
      case 58:   return('Asia/Yekaterinburg');
      case 59:   return('Europe/Helsinki');
      case 60:   return('America/Godthab');
      case 61:   return('Asia/Rangoon');
      case 62:   return('Asia/Kathmandu');
      case 63:   return('Asia/Irkutsk');
      case 64:   return('Asia/Krasnoyarsk');
      case 65:   return('America/Santiago');
      case 66:   return('Asia/Colombo');
      case 67:   return('Pacific/Tongatapu');
      case 68:   return('Asia/Vladivostok');
      case 69:   return('Africa/Ndjamena');
      case 70:   return('Asia/Yakutsk');
      case 71:   return('Asia/Dhaka');
      case 72:   return('Asia/Seoul');
      case 73:   return('Australia/Perth');
      case 74:   return('Asia/Riyadh');
      case 75:   return('Asia/Taipei');
      case 76:   return('Australia/Sydney');

      case 57: // null
      case 52: // null
      default: // null
    }
    return null;
  }


}