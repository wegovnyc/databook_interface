<?php
/**
* Class for parsing RRule and getting us the dates (v2)
*
* @package   awl
* @subpackage RRule
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

if ( !class_exists('DateTime') ) return;

/**
* Try and extract something like "Pacific/Auckland" or "America/Indiana/Indianapolis" if possible, given
* the VTIMEZONE component that is passed in.  This is much more complex than olson_from_tzstring since
* we start to examine the rules and work out what actual timezone this might be.
*/
function olson_from_vtimezone( vComponent $vtz ) {
  $tzid = $vtz->GetProperty('TZID');
  if ( empty($tzid) ) $tzid = $vtz->GetProperty('TZID');
  if ( !empty($tzid) ) {
    $result = olson_from_tzstring($tzid);
    if ( !empty($result) ) return $result;
  }

  /**
   * @todo: We'll do other stuff here, in due course...
   */
  return null;
}

// define( 'DEBUG_RRULE', true);
define( 'DEBUG_RRULE', false );

/**
* Wrap the DateTimeZone class to allow parsing some iCalendar TZID strangenesses
*/
class RepeatRuleTimeZone extends DateTimeZone {
  private $tz_defined;

  public function __construct($in_dtz = null) {
    $this->tz_defined = false;
    if ( !isset($in_dtz) ) return;

    $olson = olson_from_tzstring($in_dtz);
    if ( isset($olson) ) {
      try {
        parent::__construct($olson);
        $this->tz_defined = $olson;
      }
      catch (Exception $e) {
        dbg_error_log( 'ERROR', 'Could not handle timezone "%s" (%s) - will use floating time', $in_dtz, $olson );
        parent::__construct('UTC');
        $this->tz_defined = false;
      }
    }
    else {
      dbg_error_log( 'ERROR', 'Could not recognize timezone "%s" - will use floating time', $in_dtz );
      parent::__construct('UTC');
      $this->tz_defined = false;
    }
  }

  function tzid() {
    if ( $this->tz_defined === false ) return false;
    $tzid = $this->getName();
    if ( $tzid != 'UTC' ) return $tzid;
    return $this->tz_defined;
  }
}

/**
 * Provide a useful way of dealing with RFC5545 duration strings of the form
 * ^-?P(\dW)|((\dD)?(T(\dH)?(\dM)?(\dS)?)?)$
 */
class Rfc5545Duration {
  private $epoch_seconds = null;
  private $days = 0;
  private $secs = 0;
  private $as_text = '';

  /**
   * Construct a new Rfc5545Duration either from incoming seconds or a text string.
   * @param mixed $in_duration
   */
  function __construct( $in_duration ) {
    if ( is_integer($in_duration) ) {
      $this->epoch_seconds = $in_duration;
      $this->as_text = '';
    }
    else if ( gettype($in_duration) == 'string' ) {
//      preg_match('{^-?P(\dW)|((\dD)?(T(\dH)?(\dM)?(\dS)?)?)$}i', $in_duration, $matches)
      $this->as_text = $in_duration;
      $this->epoch_seconds = null;
    }
    else {
//      fatal('Passed duration is neither numeric nor string!');
    }
  }

  /**
   * Return true if $this and $other are equal, false otherwise.
   * @param Rfc5545Duration $other
   * @return boolean
   */
  function equals( $other ) {
    if ( $this == $other ) return true;
    if ( $this->asSeconds() == $other->asSeconds() ) return true;
    return false;
  }

  /**
   * Returns the duration as epoch seconds.
   */
  function asSeconds() {
    if ( !isset($this->epoch_seconds) ) {
      if ( preg_match('{^(-?)P(\d+W)|(?:(\d+)D?(?:T(\d+)H?(\d+)M?(\d+)S?)?)$}i', $this->as_text, $matches) ) {
        // @printf("%s - %s - %s - %s - %s - %s\n", $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]);
        $this->secs = 0;
        if ( !empty($matches[2]) ) {
          $this->days = (intval($matches[2]) * 7);
        }
        else {
          if ( !empty($matches[3]) ) $this->days = intval($matches[3]);
          if ( !empty($matches[4]) ) $this->secs += intval($matches[4]) * 3600;
          if ( !empty($matches[5]) ) $this->secs += intval($matches[5]) * 60;
          if ( !empty($matches[6]) ) $this->secs += intval($matches[6]);
        }
        if ( $matches[1] == '-' ) {
          $this->days *= -1;
          $this->secs *= -1;
        }
        $this->epoch_seconds = ($this->days * 86400) + $this->secs;
        // printf("Duration: %d days & %d seconds (%d epoch seconds)\n", $this->days, $this->secs, $this->epoch_seconds);
      }
      else {
        throw new Exception('Invalid epoch: "'+$this->as_text+"'");
      }
    }
    return $this->epoch_seconds;
  }


  /**
   * Returns the duration as a text string of the form ^(-?)P(\d+W)|((\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?)$
   * @return string The stringified stuff.
   */
  function __toString() {
    if ( empty($this->as_text) ) {
      $this->as_text = ($this->epoch_seconds < 0 ? '-P' : 'P');
      $in_duration = abs($this->epoch_seconds);
      if ( $in_duration >= 86400 ) {
        $this->days = floor($in_duration / 86400);
        $in_duration -= $this->days * 86400;
        if ( $in_duration == 0 && ($this->days / 7) == floor($this->days / 7) ) {
          $this->as_text .= ($this->days/7).'W';
          return $this->as_text;
        }
        $this->as_text .= $this->days.'D';
      }
      if ( $in_duration > 0 ) {
        $secs = $in_duration;
        $this->as_text .= 'T';
        $hours = floor($in_duration / 3600);
        if ( $hours > 0 ) $this->as_text .= $hours . 'H';
        $minutes = floor(($in_duration % 3600) / 60);
        if ( $minutes > 0 ) $this->as_text .= $minutes . 'M';
        $seconds = $in_duration % 60;
        if ( $seconds > 0 ) $this->as_text .= $seconds . 'S';
      }
    }
    return $this->as_text;
  }


  /**
   * Factory method to return an Rfc5545Duration object from the difference
   * between two dates.
   *
   * This is flawed, at present: we should really localise both dates and work
   * out the difference in days, then localise the times and work out the difference
   * between the clock times.  On the other hand we're replacing a quick and dirty
   * hack that did it exactly the same way in the past, so we're not making things
   * any *worse* and at least we're making it clear that it could be improved...
   *
   * The problem strikes (as they all do) across DST boundaries.
   *
   * @todo Improve this to calculate the days difference and then the clock time diff
   * and work from there.
   *
   * @param RepeatRuleDateTime $d1
   * @param RepeatRuleDateTime $d2
   * @return Rfc5545Duration
   */
  static function fromTwoDates( $d1, $d2 ) {
    $diff = $d2->epoch() - $d1->epoch();
    return new Rfc5545Duration($diff);
  }
}

/**
* Wrap the DateTime class to make it friendlier to passing in random strings from iCalendar
* objects, and especially the random stuff used to identify timezones.  We also add some
* utility methods and stuff too, in order to simplify some of the operations we need to do
* with dates.
*/
class RepeatRuleDateTime extends DateTime {
  // public static $Format = 'Y-m-d H:i:s';
  public static $Format = 'c';
  private static $UTCzone;
  private $tzid;
  private $is_date;

  public function __construct($date = null, $dtz = null, $is_date = null ) {
    if ( !isset(self::$UTCzone) ) self::$UTCzone = new RepeatRuleTimeZone('UTC');
    $this->is_date = false;
    if ( isset($is_date) ) $this->is_date = $is_date;
    if ( !isset($date) ) {
      $date = date('Ymd\THis');
      // Floating
      $dtz = self::$UTCzone;
    }
    $this->tzid = null;

    if ( is_object($date) && method_exists($date,'GetParameterValue') ) {
      $tzid = $date->GetParameterValue('TZID');
      $actual_date = $date->Value();
      if ( isset($tzid) ) {
        $dtz = new RepeatRuleTimeZone($tzid);
        $this->tzid = $dtz->tzid();
      }
      else {
        $dtz = self::$UTCzone;
        if ( substr($actual_date,-1) == 'Z' ) {
          $this->tzid = 'UTC';
          $actual_date = substr($actual_date, 0, strlen($actual_date) - 1);
        }
      }
      if ( strlen($actual_date) == 8 ) {
        // We allow dates without VALUE=DATE parameter, but we don't create them like that
        $this->is_date = true;
      }
//      $value_type = $date->GetParameterValue('VALUE');
//      if ( isset($value_type) && $value_type == 'DATE' ) $this->is_date = true;
      $date = $actual_date;
      if ( DEBUG_RRULE ) printf( "Date%s property%s: %s%s\n", ($this->is_date ? "" : "Time"),
              (isset($this->tzid) ? ' with timezone' : ''), $date,
              (isset($this->tzid) ? ' in '.$this->tzid : '') );
    }
    elseif (preg_match('/;TZID= ([^:;]+) (?: ;.* )? : ( \d{8} (?:T\d{6})? ) (Z)?/x', $date, $matches) ) {
      $date = $matches[2];
      $this->is_date = (strlen($date) == 8);
      if ( isset($matches[3]) && $matches[3] == 'Z' ) {
        $dtz = self::$UTCzone;
        $this->tzid = 'UTC';
      }
      else if ( isset($matches[1]) && $matches[1] != '' ) {
        $dtz = new RepeatRuleTimeZone($matches[1]);
        $this->tzid = $dtz->tzid();
      }
      else {
        $dtz = self::$UTCzone;
        $this->tzid = null;
      }
      if ( DEBUG_RRULE ) printf( "Date%s property%s: %s%s\n", ($this->is_date ? "" : "Time"),
              (isset($this->tzid) ? ' with timezone' : ''), $date,
              (isset($this->tzid) ? ' in '.$this->tzid : '') );
    }
    elseif ( ( $dtz === null || $dtz == '' )
             && preg_match('{;VALUE=DATE (?:;[^:]+) : ((?:[12]\d{3}) (?:0[1-9]|1[012]) (?:0[1-9]|[12]\d|3[01]Z?) )$}x', $date, $matches) ) {
      $this->is_date = true;
      $date = $matches[1];
      // Floating
      $dtz = self::$UTCzone;
      $this->tzid = null;
      if ( DEBUG_RRULE ) printf( "Floating Date value: %s\n", $date );
    }
    elseif ( $dtz === null || $dtz == '' ) {
      $dtz = self::$UTCzone;
      if ( preg_match('/(\d{8}(T\d{6})?)(Z?)/', $date, $matches) ) {
        $date = $matches[1];
        $this->tzid = ( $matches[3] == 'Z' ? 'UTC' : null );
      }
      $this->is_date = (strlen($date) == 8 );
      if ( DEBUG_RRULE ) printf( "Date%s value with timezone: %s in %s\n", ($this->is_date?"":"Time"), $date, $this->tzid );
    }
    elseif ( is_string($dtz) ) {
      $dtz = new RepeatRuleTimeZone($dtz);
      $this->tzid = $dtz->tzid();
      $type = gettype($date);
      if ( DEBUG_RRULE ) printf( "Date%s $type with timezone: %s in %s\n", ($this->is_date?"":"Time"), $date, $this->tzid );
    }
    else {
      $this->tzid = $dtz->getName();
      $type = gettype($date);
      if ( DEBUG_RRULE ) printf( "Date%s $type with timezone: %s in %s\n", ($this->is_date?"":"Time"), $date, $this->tzid );
    }

    parent::__construct($date, $dtz);
    if ( isset($is_date) ) $this->is_date = $is_date;

    return $this;
  }


  public function __toString() {
    return (string)parent::format(self::$Format) . ' ' . parent::getTimeZone()->getName();
  }


  public function AsDate() {
    return $this->format('Ymd');
  }


  public function setAsFloat() {
    unset($this->tzid);
  }


  public function isFloating() {
    return !isset($this->tzid);
  }

  public function isDate() {
    return $this->is_date;
  }


  public function setAsDate() {
    $this->is_date = true;
  }


  public function modify( $interval ) {
//    print ">>$interval<<\n";
    if ( preg_match('{^(-)?P(([0-9-]+)W)?(([0-9-]+)D)?T?(([0-9-]+)H)?(([0-9-]+)M)?(([0-9-]+)S)?$}', $interval, $matches) ) {
      $minus = (isset($matches[1])?$matches[1]:'');
      $interval = '';
      if ( isset($matches[2]) && $matches[2] != '' ) $interval .= $minus . $matches[3] . ' weeks ';
      if ( isset($matches[4]) && $matches[4] != '' ) $interval .= $minus . $matches[5] . ' days ';
      if ( isset($matches[6]) && $matches[6] != '' ) $interval .= $minus . $matches[7] . ' hours ';
      if ( isset($matches[8]) && $matches[8] != '' ) $interval .= $minus . $matches[9] . ' minutes ';
      if (isset($matches[10]) &&$matches[10] != '' ) $interval .= $minus . $matches[11] . ' seconds ';
    }
//    printf( "Modify '%s' by: >>%s<<\n", $this->__toString(), $interval );
//    print_r($this);
    if ( !isset($interval) || $interval == '' ) $interval = '1 day';
    if ( parent::format('d') > 28 && strstr($interval,'month') !== false ) {
      $this->setDate(null,null,28);
    }
    parent::modify($interval);
    return $this->__toString();
  }


  /**
   * Always returns a time localised to UTC.  Even floating times are converted to UTC
   * using the server's currently configured PHP timezone.  Even dates will include a
   * time, which will be non-zero if they were localised dates.
   *
   * @see RepeatRuleDateTime::FloatOrUTC()
   */
  public function UTC($fmt = 'Ymd\THis\Z' ) {
    $gmt = clone($this);
    if ( $this->tzid != 'UTC' ) {
      if ( isset($this->tzid)) {
        $dtz = parent::getTimezone();
      }
      else {
        $dtz = new DateTimeZone(date_default_timezone_get());
      }
      $offset = 0 - $dtz->getOffset($gmt);
      $gmt->modify( $offset . ' seconds' );
    }
    return $gmt->format($fmt);
  }


  /**
   * If this is a localised time then this will return the UTC equivalent.  If it is a
   * floating time, then you will just get the floating time.  If it is a date then it
   * will be returned as a date.  Note that if it is a *localised* date then the answer
   * will still be the UTC equivalent but only the date itself will be returned.
   *
   * If return_floating_times is true then all dates will be returned as floating times
   * and UTC will not be returned.
   *
   * @see RepeatRuleDateTime::UTC()
   */
  public function FloatOrUTC($return_floating_times = false) {
    $gmt = clone($this);
    if ( !$return_floating_times && isset($this->tzid) && $this->tzid != 'UTC' ) {
      $dtz = parent::getTimezone();
      $offset = 0 - $dtz->getOffset($gmt);
      $gmt->modify( $offset . ' seconds' );
    }
    if ( $this->is_date ) return $gmt->format('Ymd');
    if ( $return_floating_times ) return $gmt->format('Ymd\THis');
    return $gmt->format('Ymd\THis') . (!$return_floating_times && isset($this->tzid) ? 'Z' : '');
  }


  /**
   * Returns the string following a property name for an RFC5545 DATE-TIME value.
   */
  public function RFC5545($return_floating_times = false) {
    $result = '';
    if ( isset($this->tzid) && $this->tzid != 'UTC' ) {
      $result = ';TZID='.$this->tzid;
    }
    if ( $this->is_date ) {
      $result .= ';VALUE=DATE:' . $this->format('Ymd');
    }
    else {
      $result .= ':' . $this->format('Ymd\THis');
      if ( !$return_floating_times && isset($this->tzid) && $this->tzid == 'UTC' ) {
        $result .= 'Z';
      }
    }
    return $result;
  }


  public function setTimeZone( $tz ) {
    if ( is_string($tz) ) {
      $tz = new RepeatRuleTimeZone($tz);
      $this->tzid = $tz->tzid();
    }
    parent::setTimeZone( $tz );
    return $this;
  }


  public function getTimeZone() {
    return $this->tzid;
  }


  /**
   * Returns a 1 if this year is a leap year, otherwise a 0
   * @param int $year The year we are quizzical about.
   * @return 1 if this is a leap year, 0 otherwise
   */
  public static function hasLeapDay($year) {
    if ( ($year % 4) == 0 && (($year % 100) != 0 || ($year % 400) == 0) ) return 1;
    return 0;
  }

  /**
   * Returns the number of days in a year/month pair
   * @param int $year
   * @param int $month
   * @return int the number of days in the month
   */
  public static function daysInMonth( $year, $month ) {
    if ($month == 4 || $month == 6 || $month == 9 || $month == 11) return 30;
    else if ($month != 2) return 31;
    return 28 + RepeatRuleDateTime::hasLeapDay($year);
  }


  function setDate( $year=null, $month=null, $day=null ) {
    if ( !isset($year) )  $year  = parent::format('Y');
    if ( !isset($month) ) $month = parent::format('m');
    if ( !isset($day) )   $day   = parent::format('d');
    if ( $day < 0 ) {
      $day += RepeatRuleDateTime::daysInMonth($year, $month) + 1;
    }
    parent::setDate( $year , $month , $day );
    return $this;
  }

  function setYearDay( $yearday ) {
    if ( $yearday > 0 ) {
      $current_yearday = parent::format('z') + 1;
    }
    else {
      $current_yearday = (parent::format('z') - (365 + parent::format('L')));
    }
    $diff = $yearday - $current_yearday;
    if ( $diff < 0 ) $this->modify('-P'.-$diff.'D');
    else if ( $diff > 0 ) $this->modify('P'.$diff.'D');
//    printf( "Current: %d, Looking for: %d, Diff: %d, What we got: %s (%d,%d)\n", $current_yearday, $yearday, $diff,
//                 parent::format('Y-m-d'), (parent::format('z')+1), ((parent::format('z') - (365 + parent::format('L')))) );
    return $this;
  }

  function year() {
    return parent::format('Y');
  }

  function month() {
    return parent::format('m');
  }

  function day() {
    return parent::format('d');
  }

  function hour() {
    return parent::format('H');
  }

  function minute() {
    return parent::format('i');
  }

  function second() {
    return parent::format('s');
  }

  function epoch() {
    return parent::format('U');
  }
}


/**
 * This class is used to hold a pair of dates defining a range.  The range may be open-ended by including
 * a null for one end or the other, or both.
 *
 * @author Andrew McMillan <andrew@mcmillan.net.nz>
 */
class RepeatRuleDateRange {
  public $from;
  public $until;

  /**
   * Construct a new RepeatRuleDateRange which will be the range between $date1 and $date2. The earliest of the two
   * dates will be used as the start of the period, the latest as the end.  If one of the dates is null then the order
   * of the parameters is significant, with the null treated as -infinity if it is first, or +infinity if it is second.
   * If both parameters are null then the range is from -infinity to +infinity.
   *
   * @param RepeatRuleDateTime $date1
   * @param RepeatRuleDateTime $date2
   */
  function __construct( $date1, $date2 ) {
    if ( $date1 != null && $date2 != null && $date1 > $date2 )  {
      $this->from = $date2;
      $this->until = $date1;
    }
    else {
      $this->from = $date1;
      $this->until = $date2;
    }
  }

  /**
   * Assess whether this range overlaps the supplied range.  null values are treated as infinity.
   * @param RepeatRuleDateRange $other
   * @return boolean
   */
  function overlaps( RepeatRuleDateRange $other ) {
    if ( ($this->until == null && $this->from == null) || ($other->until == null && $other->from == null ) ) return true;
    if ( $this->until == null && $other->until == null ) return true;
    if ( $this->from == null && $other->from == null ) return true;

    if ( $this->until == null ) return ($other->until > $this->from);
    if ( $this->from == null ) return ($other->from < $this->until);
    if ( $other->until == null ) return ($this->until > $other->from);
    if ( $other->from == null ) return ($thi->from < $other->until);

    return !( $this->until < $other->from || $this->from > $other->until );
  }

  /**
   * Get an Rfc5545Duration from this date range.  If the from date is null it will be null.
   * If the until date is null the duration will either be 1 day (if the from is a date) or 0 otherwise.
   *
   * @return NULL|Rfc5545Duration
   */
  function getDuration() {
    if ( !isset($this->from) ) return null;
    if ( $this->from->isDate() && !isset($this->until) )
      $duration = 'P1D';
    else if ( !isset($this->until) )
      $duration = 'P0D';
    else
      $duration = ( $this->until->epoch() - $this->from->epoch() );
    return new Rfc5545Duration( $duration );
  }
}


/**
 * This class is an implementation of RRULE parsing and expansion, as per RFC5545.  It should be reasonably
 * complete, except that it does not handle changing the WKST - there may be a few errors in unusual rules
 * also, but all of the common cases should be handled correctly.
 *
 * @author Andrew McMillan <andrew@mcmillan.net.nz>
 */
class RepeatRule {

  private $base;
  private $until;
  private $freq;
  private $count;
  private $interval;
  private $bysecond;
  private $byminute;
  private $byhour;
  private $bymonthday;
  private $byyearday;
  private $byweekno;
  private $byday;
  private $bymonth;
  private $bysetpos;
  private $wkst;

  private $instances;
  private $position;
  private $finished;
  private $current_base;
  private $original_rule;


  public function __construct( $basedate, $rrule, $is_date=null, $return_floating_times=false ) {
    if ( $return_floating_times ) $basedate->setAsFloat();
    $this->base = (is_object($basedate) ? $basedate : new RepeatRuleDateTime($basedate) );
    $this->original_rule = $rrule;

    if ( DEBUG_RRULE ) {
      printf( "Constructing RRULE based on: '%s', rrule: '%s' (we float: %s)\n", $basedate, $rrule, ($return_floating_times?"yes":"no") );
    }

    if ( preg_match('{FREQ=([A-Z]+)(;|$)}', $rrule, $m) ) $this->freq = $m[1];

    if ( preg_match('{UNTIL=([0-9TZ]+)(;|$)}', $rrule, $m) )
      $this->until = new RepeatRuleDateTime($m[1],$this->base->getTimeZone(),$is_date);
    if ( preg_match('{COUNT=([0-9]+)(;|$)}', $rrule, $m) ) $this->count = $m[1];
    if ( preg_match('{INTERVAL=([0-9]+)(;|$)}', $rrule, $m) ) $this->interval = $m[1];

    if ( preg_match('{WKST=(MO|TU|WE|TH|FR|SA|SU)(;|$)}', $rrule, $m) ) $this->wkst = $m[1];

    if ( preg_match('{BYDAY=(([+-]?[0-9]{0,2}(MO|TU|WE|TH|FR|SA|SU),?)+)(;|$)}', $rrule, $m) )
      $this->byday = explode(',',$m[1]);

    if ( preg_match('{BYYEARDAY=([0-9,+-]+)(;|$)}', $rrule, $m) ) $this->byyearday = explode(',',$m[1]);
    if ( preg_match('{BYWEEKNO=([0-9,+-]+)(;|$)}', $rrule, $m) ) $this->byweekno = explode(',',$m[1]);
    if ( preg_match('{BYMONTHDAY=([0-9,+-]+)(;|$)}', $rrule, $m) ) $this->bymonthday = explode(',',$m[1]);
    if ( preg_match('{BYMONTH=(([+-]?[0-1]?[0-9],?)+)(;|$)}', $rrule, $m) ) $this->bymonth = explode(',',$m[1]);
    if ( preg_match('{BYSETPOS=(([+-]?[0-9]{1,3},?)+)(;|$)}', $rrule, $m) ) $this->bysetpos = explode(',',$m[1]);

    if ( preg_match('{BYSECOND=([0-9,]+)(;|$)}', $rrule, $m) ) $this->bysecond = explode(',',$m[1]);
    if ( preg_match('{BYMINUTE=([0-9,]+)(;|$)}', $rrule, $m) ) $this->byminute = explode(',',$m[1]);
    if ( preg_match('{BYHOUR=([0-9,]+)(;|$)}', $rrule, $m) ) $this->byhour = explode(',',$m[1]);

    if ( !isset($this->interval) ) $this->interval = 1;
    switch( $this->freq ) {
      case 'SECONDLY': $this->freq_name = 'second'; break;
      case 'MINUTELY': $this->freq_name = 'minute'; break;
      case 'HOURLY':   $this->freq_name = 'hour';   break;
      case 'DAILY':    $this->freq_name = 'day';    break;
      case 'WEEKLY':   $this->freq_name = 'week';   break;
      case 'MONTHLY':  $this->freq_name = 'month';  break;
      case 'YEARLY':   $this->freq_name = 'year';   break;
      default:
        /** need to handle the error, but FREQ is mandatory so unlikely */
    }
    $this->frequency_string = sprintf('+%d %s', $this->interval, $this->freq_name );
    if ( DEBUG_RRULE ) printf( "Frequency modify string is: '%s', base is: '%s'\n", $this->frequency_string, $this->base->format('c') );
    $this->Start($return_floating_times);
  }


  /**
   * If this repeat rule has an UNTIL= or COUNT= then we can know it will end. Eventually.
   * @return boolean Whether or not one of these properties is present.
   */
  public function hasLimitedOccurrences() {
    return ( isset($this->count) || isset($this->until) );
  }


  public function set_timezone( $tzstring ) {
    $this->base->setTimezone(new DateTimeZone($tzstring));
  }


  public function Start($return_floating_times=false) {
    $this->instances = array();
    $this->GetMoreInstances($return_floating_times);
    $this->rewind();
    $this->finished = false;
  }


  public function rewind() {
    $this->position = -1;
  }


  /**
   * Return the next date in the repeating series.
   * @param boolean $return_floating_times Whether to return dates as floating times.
   * @return vComponent The next instance.
   */
  public function next($return_floating_times=false) {
    $this->position++;
    return $this->current($return_floating_times);
  }


  public function current($return_floating_times=false) {
    if ( !$this->valid() ) return null;
    if ( !isset($this->instances[$this->position]) ) $this->GetMoreInstances($return_floating_times);
    if ( !$this->valid() ) return null;
    if ( DEBUG_RRULE ) printf( "Returning date from position %d: %s (%s)\n", $this->position,
                          $this->instances[$this->position]->format('c'), $this->instances[$this->position]->FloatOrUTC($return_floating_times) );
    return $this->instances[$this->position];
  }


  public function key($return_floating_times=false) {
    if ( !$this->valid() ) return null;
    if ( !isset($this->instances[$this->position]) ) $this->GetMoreInstances($return_floating_times);
    if ( !isset($this->keys[$this->position]) ) {
      $this->keys[$this->position] = $this->instances[$this->position];
    }
    return $this->keys[$this->position];
  }


  public function valid() {
    if ( isset($this->instances[$this->position]) || !$this->finished ) return true;
    return false;
  }

  /**
   * This function returns an array which lists the order of processing, and whether the processing is
   * to expand or limit based on this component.
   *
   * Note that yearly-byday and monthly-byday have special handling which is coded within the
   * expand_byday() method
   * @param $freq a string indicating the frequency.
   */
  private static function rrule_expand_limit( $freq ) {
    switch( $freq ) {
      case 'YEARLY':
        return array( 'bymonth' => 'expand', 'byweekno' => 'expand', 'byyearday' => 'expand', 'bymonthday' => 'expand',
                        'byday' => 'expand', 'byhour' => 'expand', 'byminute' => 'expand', 'bysecond' => 'expand' );
      case 'MONTHLY':
        return array( 'bymonth' => 'limit', 'bymonthday' => 'expand',
                        'byday' => 'expand', 'byhour' => 'expand', 'byminute' => 'expand', 'bysecond' => 'expand' );
      case 'WEEKLY':
        return array( 'bymonth' => 'limit',
                        'byday' => 'expand', 'byhour' => 'expand', 'byminute' => 'expand', 'bysecond' => 'expand' );
      case 'DAILY':
        return array( 'bymonth' => 'limit', 'bymonthday' => 'limit',
                        'byday' => 'limit', 'byhour' => 'expand', 'byminute' => 'expand', 'bysecond' => 'expand' );
      case 'HOURLY':
        return array( 'bymonth' => 'limit', 'bymonthday' => 'limit',
                        'byday' => 'limit', 'byhour' => 'limit', 'byminute' => 'expand', 'bysecond' => 'expand' );
      case 'MINUTELY':
        return array( 'bymonth' => 'limit', 'bymonthday' => 'limit',
                        'byday' => 'limit', 'byhour' => 'limit', 'byminute' => 'limit', 'bysecond' => 'expand' );
      case 'SECONDLY':
        return array( 'bymonth' => 'limit', 'bymonthday' => 'limit',
                        'byday' => 'limit', 'byhour' => 'limit', 'byminute' => 'limit', 'bysecond' => 'limit' );
    }
    dbg_error_log('ERROR','Invalid frequency code "%s" - pretending it is "DAILY"', $freq);
    return array( 'bymonth' => 'limit', 'bymonthday' => 'limit',
                        'byday' => 'limit', 'byhour' => 'expand', 'byminute' => 'expand', 'bysecond' => 'expand' );
  }

  private function GetMoreInstances($return_floating_times=false) {
    if ( $this->finished ) return;
    $got_more = false;
    $loop_limit = 10;
    $loops = 0;
    if ( $return_floating_times ) $this->base->setAsFloat();
    while( !$this->finished && !$got_more && $loops++ < $loop_limit ) {
      if ( !isset($this->current_base) ) {
        $this->current_base = clone($this->base);
      }
      else {
        $this->current_base->modify( $this->frequency_string );
      }
      if ( $return_floating_times ) $this->current_base->setAsFloat();
      if ( DEBUG_RRULE ) printf( "Getting more instances from: '%s' - %d\n", $this->current_base->format('c'), count($this->instances) );
      $this->current_set = array( clone($this->current_base) );
      foreach( self::rrule_expand_limit($this->freq) AS $bytype => $action ) {
        if ( isset($this->{$bytype}) ) {
          $this->{$action.'_'.$bytype}();
          if ( !isset($this->current_set[0]) ) break;
        }
      }

      sort($this->current_set);
      if ( isset($this->bysetpos) ) $this->limit_bysetpos();

      $position = count($this->instances) - 1;
      if ( DEBUG_RRULE ) printf( "Inserting %d from current_set into position %d\n", count($this->current_set), $position + 1 );
      foreach( $this->current_set AS $k => $instance ) {
        if ( $instance < $this->base ) continue;
        if ( isset($this->until) && $instance > $this->until ) {
          $this->finished = true;
          return;
        }
        if ( !isset($this->instances[$position]) || $instance != $this->instances[$position] ) {
          $got_more = true;
          $position++;
          $this->instances[$position] = $instance;
          if ( DEBUG_RRULE ) printf( "Added date %s into position %d in current set\n", $instance->format('c'), $position );
          if ( isset($this->count) && ($position + 1) >= $this->count ) {
            $this->finished = true;
            return;
          }
        }
      }
    }
  }


  public static function rrule_day_number( $day ) {
    switch( $day ) {
      case 'SU': return 0;
      case 'MO': return 1;
      case 'TU': return 2;
      case 'WE': return 3;
      case 'TH': return 4;
      case 'FR': return 5;
      case 'SA': return 6;
    }
    return false;
  }


  static public function date_mask( $date, $y, $mo, $d, $h, $mi, $s ) {
    $date_parts = explode(',',$date->format('Y,m,d,H,i,s'));

    if ( isset($y) || isset($mo) || isset($d) ) {
      if ( isset($y) ) $date_parts[0] = $y;
      if ( isset($mo) ) $date_parts[1] = $mo;
      if ( isset($d) ) $date_parts[2] = $d;
      $date->setDate( $date_parts[0], $date_parts[1], $date_parts[2] );
    }
    if ( isset($h) || isset($mi) || isset($s) ) {
      if ( isset($h) ) $date_parts[3] = $h;
      if ( isset($mi) ) $date_parts[4] = $mi;
      if ( isset($s) ) $date_parts[5] = $s;
      $date->setTime( $date_parts[3], $date_parts[4], $date_parts[5] );
    }
    return $date;
  }


  private function expand_bymonth() {
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->bymonth AS $k => $month ) {
        $expanded = $this->date_mask( clone($instance), null, $month, null, null, null, null);
        if ( DEBUG_RRULE ) printf( "Expanded BYMONTH $month into date %s\n", $expanded->format('c') );
        $this->current_set[] = $expanded;
      }
    }
  }

  private function expand_bymonthday() {
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->bymonthday AS $k => $monthday ) {
        $expanded = $this->date_mask( clone($instance), null, null, $monthday, null, null, null);
        if ( DEBUG_RRULE ) printf( "Expanded BYMONTHDAY $monthday into date %s from %s\n", $expanded->format('c'), $instance->format('c') );
        $this->current_set[] = $expanded;
      }
    }
  }

  private function expand_byyearday() {
    $instances = $this->current_set;
    $this->current_set = array();
    $days_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->byyearday AS $k => $yearday ) {
        $on_yearday = clone($instance);
        $on_yearday->setYearDay($yearday);
        if ( isset($days_set[$on_yearday->UTC()]) ) continue;
        $this->current_set[] = $on_yearday;
        $days_set[$on_yearday->UTC()] = true;
      }
    }
  }

  private function expand_byday_in_week( $day_in_week ) {

    /**
    * @todo This should really allow for WKST, since if we start a series
    * on (eg.) TH and interval > 1, a MO, TU, FR repeat will not be in the
    * same week with this code.
    */
    $dow_of_instance = $day_in_week->format('w'); // 0 == Sunday
    foreach( $this->byday AS $k => $weekday ) {
      $dow = self::rrule_day_number($weekday);
      $offset = $dow - $dow_of_instance;
      if ( $offset < 0 ) $offset += 7;
      $expanded = clone($day_in_week);
      $expanded->modify( sprintf('+%d day', $offset) );
      $this->current_set[] = $expanded;
      if ( DEBUG_RRULE ) printf( "Expanded BYDAY(W) $weekday into date %s\n", $expanded->format('c') );
    }
  }


  private function expand_byday_in_month( $day_in_month ) {

    $first_of_month = $this->date_mask( clone($day_in_month), null, null, 1, null, null, null);
    $dow_of_first = $first_of_month->format('w'); // 0 == Sunday
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $first_of_month->format('m'), $first_of_month->format('Y'));
    foreach( $this->byday AS $k => $weekday ) {
      if ( preg_match('{([+-])?(\d)?(MO|TU|WE|TH|FR|SA|SU)}', $weekday, $matches ) ) {
        $dow = self::rrule_day_number($matches[3]);
        $first_dom = 1 + $dow - $dow_of_first;  if ( $first_dom < 1 ) $first_dom +=7;  // e.g. 1st=WE, dow=MO => 1+1-3=-1 => MO is 6th, etc.
        $whichweek = intval($matches[2]);
        if ( DEBUG_RRULE ) printf( "Expanding BYDAY(M) $weekday in month of %s\n", $first_of_month->format('c') );
        if ( $whichweek > 0 ) {
          $whichweek--;
          $monthday = $first_dom;
          if ( $matches[1] == '-' ) {
            $monthday += 35;
            while( $monthday > $days_in_month ) $monthday -= 7;
            $monthday -= (7 * $whichweek);
          }
          else {
            $monthday += (7 * $whichweek);
          }
          if ( $monthday > 0 && $monthday <= $days_in_month ) {
            $expanded = $this->date_mask( clone($day_in_month), null, null, $monthday, null, null, null);
            if ( DEBUG_RRULE ) printf( "Expanded BYDAY(M) $weekday now $monthday into date %s\n", $expanded->format('c') );
            $this->current_set[] = $expanded;
          }
        }
        else {
          for( $monthday = $first_dom; $monthday <= $days_in_month; $monthday += 7 ) {
            $expanded = $this->date_mask( clone($day_in_month), null, null, $monthday, null, null, null);
            if ( DEBUG_RRULE ) printf( "Expanded BYDAY(M) $weekday now $monthday into date %s\n", $expanded->format('c') );
            $this->current_set[] = $expanded;
          }
        }
      }
    }
  }


  private function expand_byday_in_year( $day_in_year ) {

    $first_of_year = $this->date_mask( clone($day_in_year), null, 1, 1, null, null, null);
    $dow_of_first = $first_of_year->format('w'); // 0 == Sunday
    $days_in_year = 337 + cal_days_in_month(CAL_GREGORIAN, 2, $first_of_year->format('Y'));
    foreach( $this->byday AS $k => $weekday ) {
      if ( preg_match('{([+-])?(\d)?(MO|TU|WE|TH|FR|SA|SU)}', $weekday, $matches ) ) {
        $expanded = clone($first_of_year);
        $dow = self::rrule_day_number($matches[3]);
        $first_doy = 1 + $dow - $dow_of_first;  if ( $first_doy < 1 ) $first_doy +=7;  // e.g. 1st=WE, dow=MO => 1+1-3=-1 => MO is 6th, etc.
        $whichweek = intval($matches[2]);
        if ( DEBUG_RRULE ) printf( "Expanding BYDAY(Y) $weekday from date %s\n", $instance->format('c') );
        if ( $whichweek > 0 ) {
          $whichweek--;
          $yearday = $first_doy;
          if ( $matches[1] == '-' ) {
            $yearday += 371;
            while( $yearday > $days_in_year ) $yearday -= 7;
            $yearday -= (7 * $whichweek);
          }
          else {
            $yearday += (7 * $whichweek);
          }
          if ( $yearday > 0 && $yearday <= $days_in_year ) {
            $expanded->modify(sprintf('+%d day', $yearday - 1));
            if ( DEBUG_RRULE ) printf( "Expanded BYDAY(Y) $weekday now $yearday into date %s\n", $expanded->format('c') );
            $this->current_set[] = $expanded;
          }
        }
        else {
          $expanded->modify(sprintf('+%d day', $first_doy - 1));
          for( $yearday = $first_doy; $yearday <= $days_in_year; $yearday += 7 ) {
            if ( DEBUG_RRULE ) printf( "Expanded BYDAY(Y) $weekday now $yearday into date %s\n", $expanded->format('c') );
            $this->current_set[] = clone($expanded);
            $expanded->modify('+1 week');
          }
        }
      }
    }
  }


  private function expand_byday() {
    if ( !isset($this->current_set[0]) ) return;
    if ( $this->freq == 'MONTHLY' || $this->freq == 'YEARLY' ) {
      if ( isset($this->bymonthday) || isset($this->byyearday) ) {
        $this->limit_byday();  /** Per RFC5545 3.3.10 from note 1&2 to table */
        return;
      }
    }
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      if ( $this->freq == 'MONTHLY' ) {
        $this->expand_byday_in_month($instance);
      }
      else if ( $this->freq == 'WEEKLY' ) {
        $this->expand_byday_in_week($instance);
      }
      else { // YEARLY
        if ( isset($this->bymonth) ) {
          $this->expand_byday_in_month($instance);
        }
        else if ( isset($this->byweekno) ) {
          $this->expand_byday_in_week($instance);
        }
        else {
          $this->expand_byday_in_year($instance);
        }
      }

    }
  }

  private function expand_byhour() {
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->bymonth AS $k => $month ) {
        $this->current_set[] = $this->date_mask( clone($instance), null, null, null, $hour, null, null);
      }
    }
  }

  private function expand_byminute() {
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->bymonth AS $k => $month ) {
        $this->current_set[] = $this->date_mask( clone($instance), null, null, null, null, $minute, null);
      }
    }
  }

  private function expand_bysecond() {
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->bymonth AS $k => $second ) {
        $this->current_set[] = $this->date_mask( clone($instance), null, null, null, null, null, $second);
      }
    }
  }


  private function limit_generally( $fmt_char, $element_name ) {
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->{$element_name} AS $k => $element_value ) {
        if ( DEBUG_RRULE ) printf( "Limiting '$fmt_char' on '%s' => '%s' ?=? '%s' ? %s\n", $instance->format('c'), $instance->format($fmt_char), $element_value, ($instance->format($fmt_char) == $element_value ? 'Yes' : 'No') );
        if ( $instance->format($fmt_char) == $element_value ) $this->current_set[] = $instance;
      }
    }
  }

  private function limit_byday() {
    $fmt_char = 'w';
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $this->byday AS $k => $weekday ) {
      $dow = self::rrule_day_number($weekday);
      foreach( $instances AS $k => $instance ) {
        if ( DEBUG_RRULE ) printf( "Limiting '$fmt_char' on '%s' => '%s' ?=? '%s' (%d) ? %s\n", $instance->format('c'), $instance->format($fmt_char), $weekday, $dow, ($instance->format($fmt_char) == $dow ? 'Yes' : 'No') );
        if ( $instance->format($fmt_char) == $dow ) $this->current_set[] = $instance;
      }
    }
  }

  private function limit_bymonth()    {   $this->limit_generally( 'm', 'bymonth' );     }
  private function limit_byyearday()  {   $this->limit_generally( 'z', 'byyearday' );   }
  private function limit_bymonthday() {   $this->limit_generally( 'd', 'bymonthday' );  }
  private function limit_byhour()     {   $this->limit_generally( 'H', 'byhour' );      }
  private function limit_byminute()   {   $this->limit_generally( 'i', 'byminute' );    }
  private function limit_bysecond()   {   $this->limit_generally( 's', 'bysecond' );    }


  private function limit_bysetpos( ) {
    $instances = $this->current_set;
    $count = count($instances);
    $this->current_set = array();
    foreach( $this->bysetpos AS $k => $element_value ) {
      if ( DEBUG_RRULE ) printf( "Limiting bysetpos %s of %d instances\n", $element_value, $count );
      if ( $element_value > 0 ) {
        $this->current_set[] = $instances[$element_value - 1];
      }
      else if ( $element_value < 0 ) {
        $this->current_set[] = $instances[$count + $element_value];
      }
    }
  }


}



require_once("vComponent.php");

/**
* Expand the event instances for an RDATE or EXDATE property
*
* @param string $property RDATE or EXDATE, depending...
* @param array $component A vComponent which is a VEVENT, VTODO or VJOURNAL
* @param array $range_end A date after which we care less about expansion
*
* @return array An array keyed on the UTC dates, referring to the component
*/
function rdate_expand( $dtstart, $property, $component, $range_end = null, $is_date=null, $return_floating_times=false ) {
  $properties = $component->GetProperties($property);
  $expansion = array();
  foreach( $properties AS $p ) {
    $timezone = $p->GetParameterValue('TZID');
    $rdate = $p->Value();
    $rdates = explode( ',', $rdate );
    foreach( $rdates AS $k => $v ) {
      $rdate = new RepeatRuleDateTime( $v, $timezone, $is_date);
      if ( $return_floating_times ) $rdate->setAsFloat();
      $expansion[$rdate->FloatOrUTC($return_floating_times)] = $component;
      if ( $rdate > $range_end ) break;
    }
  }
  return $expansion;
}


/**
* Expand the event instances for an RRULE property
*
* @param object $dtstart A RepeatRuleDateTime which is the master dtstart
* @param string $property RDATE or EXDATE, depending...
* @param array $component A vComponent which is a VEVENT, VTODO or VJOURNAL
* @param array $range_end A date after which we care less about expansion
*
* @return array An array keyed on the UTC dates, referring to the component
*/
function rrule_expand( $dtstart, $property, $component, $range_end, $is_date=null, $return_floating_times=false ) {
  $expansion = array();

  $recur = $component->GetProperty($property);
  if ( !isset($recur) ) return $expansion;
  $recur = $recur->Value();

  $this_start = $component->GetProperty('DTSTART');
  if ( isset($this_start) ) {
    $this_start = new RepeatRuleDateTime($this_start);
  }
  else {
    $this_start = clone($dtstart);
  }
  if ( $return_floating_times ) $this_start->setAsFloat();

//  if ( DEBUG_RRULE ) print_r( $this_start );
  if ( DEBUG_RRULE ) printf( "RRULE: %s (floating: %s)\n", $recur, ($return_floating_times?"yes":"no") );
  $rule = new RepeatRule( $this_start, $recur, $is_date, $return_floating_times );
  $i = 0;
  $result_limit = 1000;
  while( $date = $rule->next($return_floating_times) ) {
//    if ( DEBUG_RRULE ) printf( "[%3d] %s\n", $i, $date->UTC() );
    $expansion[$date->FloatOrUTC($return_floating_times)] = $component;
    if ( $i++ >= $result_limit || $date > $range_end ) break;
  }
//  if ( DEBUG_RRULE ) print_r( $expansion );
  return $expansion;
}


/**
* Expand the event instances for an iCalendar VEVENT (or VTODO)
*
* Note: expansion here does not apply modifications to instances other than modifying start/end/due/duration.
*
* @param object $vResource A vComponent which is a VCALENDAR containing components needing expansion
* @param object $range_start A RepeatRuleDateTime which is the beginning of the range for events, default -6 weeks
* @param object $range_end A RepeatRuleDateTime which is the end of the range for events, default +6 weeks
*
* @return vComponent The original vComponent, with the instances of the internal components expanded.
*/
function expand_event_instances( vComponent $vResource, $range_start = null, $range_end = null, $return_floating_times=false ) {
    global $c;
    $components = $vResource->GetComponents();

    $clear_instance_props = array(
            'DTSTART' => true,
            'DUE' => true,
            'DTEND' => true
    );
    if ( empty( $c->expanded_instances_include_rrule ) ) {
        $clear_instance_props += array(
                'RRULE' => true,
                'RDATE' => true,
                'EXDATE' => true
        );
    }

  if ( empty($range_start) ) { $range_start = new RepeatRuleDateTime(); $range_start->modify('-6 weeks'); }
  if ( empty($range_end) )   {
      $range_end   = clone($range_start);
      $range_end->modify('+6 months');
  }

  $instances = array();
  $expand = false;
  $dtstart = null;
  $is_date = false;
  $has_repeats = false;
  $dtstart_type = 'DTSTART';
  foreach( $components AS $k => $comp ) {
    if ( $comp->GetType() != 'VEVENT' && $comp->GetType() != 'VTODO' && $comp->GetType() != 'VJOURNAL' ) {
      continue;
    }
    if ( !isset($dtstart) ) {
      $dtstart_prop = $comp->GetProperty($dtstart_type);
      if ( !isset($dtstart_prop) && $comp->GetType() != 'VTODO' ) {
        $dtstart_type = 'DUE';
        $dtstart_prop = $comp->GetProperty($dtstart_type);
      }
      if ( !isset($dtstart_prop) ) continue;
      $dtstart = new RepeatRuleDateTime( $dtstart_prop );
      if ( $return_floating_times ) $dtstart->setAsFloat();
      if ( DEBUG_RRULE ) printf( "Component is: %s (floating: %s)\n", $comp->GetType(), ($return_floating_times?"yes":"no") );
      $is_date = $dtstart->isDate();
      $instances[$dtstart->FloatOrUTC($return_floating_times)] = $comp;
      $rrule = $comp->GetProperty('RRULE');
      $has_repeats = isset($rrule);
    }
    $p = $comp->GetProperty('RECURRENCE-ID');
    if ( isset($p) && $p->Value() != '' ) {
      $range = $p->GetParameterValue('RANGE');
      $recur_utc = new RepeatRuleDateTime($p);
      if ( $is_date ) $recur_utc->setAsDate();
      $recur_utc = $recur_utc->FloatOrUTC($return_floating_times);
      if ( isset($range) && $range == 'THISANDFUTURE' ) {
        foreach( $instances AS $k => $v ) {
          if ( DEBUG_RRULE ) printf( "Removing overridden instance at: $k\n" );
          if ( $k >= $recur_utc ) unset($instances[$k]);
        }
      }
      else {
        unset($instances[$recur_utc]);
      }
    }
    else if ( DEBUG_RRULE ) {
      $p =  $comp->GetProperty('SUMMARY');
      $summary = ( isset($p) ? $p->Value() : 'not set');
      $p =  $comp->GetProperty('UID');
      $uid = ( isset($p) ? $p->Value() : 'not set');
      printf( "Processing event '%s' with UID '%s' starting on %s\n",
                 $summary, $uid, $dtstart->FloatOrUTC($return_floating_times) );
      print( "Instances at start");
      foreach( $instances AS $k => $v ) {
        print ' : '.$k;
      }
      print "\n";
    }
    $instances += rrule_expand($dtstart, 'RRULE', $comp, $range_end, null, $return_floating_times);
    if ( DEBUG_RRULE ) {
      print( "After rrule_expand");
      foreach( $instances AS $k => $v ) {
        print ' : '.$k;
      }
      print "\n";
    }
    $instances += rdate_expand($dtstart, 'RDATE', $comp, $range_end, null, $return_floating_times);
    if ( DEBUG_RRULE ) {
      print( "After rdate_expand");
      foreach( $instances AS $k => $v ) {
        print ' : '.$k;
      }
      print "\n";
    }
    foreach ( rdate_expand($dtstart, 'EXDATE', $comp, $range_end, null, $return_floating_times) AS $k => $v ) {
      unset($instances[$k]);
    }
    if ( DEBUG_RRULE ) {
      print( "After exdate_expand");
      foreach( $instances AS $k => $v ) {
        print ' : '.$k;
      }
      print "\n";
    }
  }

  $last_duration = null;
  $early_start = null;
  $new_components = array();
  $start_utc = $range_start->FloatOrUTC($return_floating_times);
  $end_utc = $range_end->FloatOrUTC($return_floating_times);
  foreach( $instances AS $utc => $comp ) {
    if ( $utc > $end_utc ) {
      if ( DEBUG_RRULE ) printf( "We're done: $utc is out of the range.\n");
      break;
    }

    $end_type = ($comp->GetType() == 'VTODO' ? 'DUE' : 'DTEND');
    $duration = $comp->GetProperty('DURATION');
    if ( !isset($duration) || $duration->Value() == '' ) {
      $instance_start = $comp->GetProperty($dtstart_type);
      $dtsrt = new RepeatRuleDateTime( $instance_start );
      if ( $return_floating_times ) $dtsrt->setAsFloat();
      $instance_end = $comp->GetProperty($end_type);
      if ( isset($instance_end) ) {
        $dtend = new RepeatRuleDateTime( $instance_end );
        $duration = Rfc5545Duration::fromTwoDates($dtsrt, $dtend);
      }
      else {
        if ( $instance_start->GetParameterValue('VALUE') == 'DATE' ) {
          $duration = new Rfc5545Duration('P1D');
        }
        else {
          $duration = new Rfc5545Duration(0);
        }
      }
    }
    else {
      $duration = new Rfc5545Duration($duration->Value());
    }

    if ( $utc < $start_utc ) {
      if ( isset($early_start) && isset($last_duration) && $duration->equals($last_duration) ) {
        if ( $utc < $early_start ) {
          if ( DEBUG_RRULE ) printf( "Next please: $utc is before $early_start and before $start_utc.\n");
          continue;
        }
      }
      else {
        /** Calculate the latest possible start date when this event would overlap our range start */
        $latest_start = clone($range_start);
        $latest_start->modify('-'.$duration);
        $early_start = $latest_start->FloatOrUTC($return_floating_times);
        $last_duration = $duration;
        if ( $utc < $early_start ) {
          if ( DEBUG_RRULE ) printf( "Another please: $utc is before $early_start and before $start_utc.\n");
          continue;
        }
      }
    }
    $component = clone($comp);
    $component->ClearProperties( $clear_instance_props );
    $component->AddProperty($dtstart_type, $utc, ($is_date ? array('VALUE' => 'DATE') : null) );
    $component->AddProperty('DURATION', $duration );
    if ( $has_repeats && $dtstart->FloatOrUTC($return_floating_times) != $utc )
      $component->AddProperty('RECURRENCE-ID', $utc, ($is_date ? array('VALUE' => 'DATE') : null) );
    $new_components[$utc] = $component;
  }

  // Add overriden instances
  foreach( $components AS $k => $comp ) {
    $p = $comp->GetProperty('RECURRENCE-ID');
    if ( isset($p) && $p->Value() != '') {
      $recurrence_id = $p->Value();
      if ( !isset($new_components[$recurrence_id]) ) {
        // The component we're replacing is outside the range.  Unless the replacement
        // is *in* the range we will move along to the next one.
        $dtstart_prop = $comp->GetProperty($dtstart_type);
        if ( !isset($dtstart_prop) ) continue;  // No start: no expansion.  Note that we consider 'DUE' to be a start if DTSTART is missing
        $dtstart = new RepeatRuleDateTime( $dtstart_prop );
        $is_date = $dtstart->isDate();
        if ( $return_floating_times ) $dtstart->setAsFloat();
        $dtstart = $dtstart->FloatOrUTC($return_floating_times);
        if ( $dtstart > $end_utc ) continue; // Start after end of range, skip it

        $end_type = ($comp->GetType() == 'VTODO' ? 'DUE' : 'DTEND');
        $duration = $comp->GetProperty('DURATION');
        if ( !isset($duration) || $duration->Value() == '' ) {
          $instance_end = $comp->GetProperty($end_type);
          if ( isset($instance_end) ) {
            $dtend = new RepeatRuleDateTime( $instance_end );
            if ( $return_floating_times ) $dtend->setAsFloat();
            $dtend = $dtend->FloatOrUTC($return_floating_times);
          }
          else {
            $dtend = $dtstart  + ($is_date ? $dtstart + 86400 : 0 );
          }
        }
        else {
          $duration = new Rfc5545Duration($duration->Value());
          $dtend = $dtstart + $duration->asSeconds();
        }
        if ( $dtend < $start_utc ) continue; // End before start of range: skip that too.
      }
      if ( DEBUG_RRULE ) printf( "Replacing overridden instance at %s\n", $recurrence_id);
      $new_components[$recurrence_id] = $comp;
    }
  }

  $vResource->SetComponents($new_components);

  return $vResource;
}


/**
 * Return a date range for this component.
 * @param vComponent $comp
 * @throws Exception (1) When DTSTART is not present but the RFC says MUST and (2) when we get an unsupported component
 * @return RepeatRuleDateRange
 */
function getComponentRange(vComponent $comp) {
  $dtstart_prop = $comp->GetProperty('DTSTART');
  $duration_prop = $comp->GetProperty('DURATION');
  if ( isset($duration_prop) ) {
    if ( !isset($dtstart_prop) ) throw new Exception('Invalid '.$comp->GetType().' containing DURATION without DTSTART', 0);
    $dtstart = new RepeatRuleDateTime($dtstart_prop);
    $dtend = clone($dtstart);
    $dtend->modify(new Rfc5545Duration($duration_prop->Value()));
  }
  else {
    $completed_prop = null;
    switch ( $comp->GetType() ) {
      case 'VEVENT':
        if ( !isset($dtstart_prop) ) throw new Exception('Invalid VEVENT without DTSTART', 0);
        $dtend_prop = $comp->GetProperty('DTEND');
        break;
      case 'VTODO':
        $completed_prop = $comp->GetProperty('COMPLETED');
        $dtend_prop = $comp->GetProperty('DUE');
        break;
      case 'VJOURNAL':
        if ( !isset($dtstart_prop) )
          $dtstart_prop = $comp->GetProperty('DTSTAMP');
        $dtend_prop = $dtstart_prop;
      default:
        throw new Exception('getComponentRange cannot handle "'.$comp->GetType().'" components', 0);
    }

    if ( isset($dtstart_prop) )
      $dtstart = new RepeatRuleDateTime($dtstart_prop);
    else
      $dtstart = null;

    if ( isset($dtend_prop) )
      $dtend = new RepeatRuleDateTime($dtend_prop);
    else
      $dtend = null;

    if ( isset($completed_prop) ) {
      $completed = new RepeatRuleDateTime($completed_prop);
      if ( !isset($dtstart) || (isset($dtstart) && $completed < $dtstart) ) $dtstart = $completed;
      if ( !isset($dtend) || (isset($dtend) && $completed > $dtend) ) $dtend = $completed;
    }
  }
  return new RepeatRuleDateRange($dtstart, $dtend);
}

/**
* Return a RepeatRuleDateRange from the earliest start to the latest end of the event.
*
* @todo: This should probably be made part of the VCalendar object when we move the RRule.php into AWL.
*
* @param object $vResource A vComponent which is a VCALENDAR containing components needing expansion
* @return RepeatRuleDateRange Representing the range of time covered by the event.
*/
function getVCalendarRange( $vResource ) {
  $components = $vResource->GetComponents();

  $dtstart = null;
  $duration = null;
  $earliest_start = null;
  $latest_end = null;
  $has_repeats = false;
  foreach( $components AS $k => $comp ) {
    if ( $comp->GetType() == 'VTIMEZONE' ) continue;
    $range = getComponentRange($comp);
    $dtstart = $range->from;
    if ( !isset($dtstart) ) continue;
    $duration = $range->getDuration();

    $rrule = $comp->GetProperty('RRULE');
    $limited_occurrences = true;
    if ( isset($rrule) ) {
      $rule = new RepeatRule($dtstart, $rrule);
      $limited_occurrences  = $rule->hasLimitedOccurrences();
    }

    if ( $limited_occurrences ) {
      $instances = array();
      $instances[$dtstart->FloatOrUTC()] = $dtstart;
      if ( !isset($range_end) ) {
        $range_end   = new RepeatRuleDateTime();
        $range_end->modify('+150 years');
      }
      $instances += rrule_expand($dtstart, 'RRULE', $comp, $range_end);
      $instances += rdate_expand($dtstart, 'RDATE', $comp, $range_end);
      foreach ( rdate_expand($dtstart, 'EXDATE', $comp, $range_end) AS $k => $v ) {
        unset($instances[$k]);
      }
      if ( count($instances) < 1 ) {
        if ( empty($earliest_start) || $dtstart < $earliest_start ) $earliest_start = $dtstart;
        $latest_end = null;
        break;
      }
      $instances = array_keys($instances);
      asort($instances);
      $first = new RepeatRuleDateTime($instances[0]);
      $last = new RepeatRuleDateTime($instances[count($instances)-1]);
      $last->modify($duration);
      if ( empty($earliest_start) || $first < $earliest_start )  $earliest_start = $first;
      if ( empty($latest_end) || $last > $latest_end )           $latest_end = $last;
    }
    else {
      if ( empty($earliest_start) || $dtstart < $earliest_start ) $earliest_start = $dtstart;
      $latest_end = null;
      break;
    }
  }

  return new RepeatRuleDateRange($earliest_start, $latest_end );
}
