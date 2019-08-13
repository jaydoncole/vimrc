<?php

/**
 * Program that generates a wall calendar in PDF format, one page per month, with
 * large spaces for notes.
 * <p>Usage under Linux:
 * <pre>../php Calendar.php YEAR</pre>
 * <p>usage under Windows:
 * <pre>..\php.bat Calendar.php YEAR</pre>
 * where YEAR is the year. The <code>calendar-YEAR.pdf</code> is then generated
 * in the current directory, with pages in A4 format.
 * <p>Currently only the Italian calendar is supported, so month names,
 * days of week names and holidays are for the Italian calendar only. Sorry guys!
 * But you may easily customize the calendar for your language and country by
 * simply changing the list of month names and days names and adding your
 * specific holidays to the isHoliday() function.
 * @package Calendar
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2017/01/19 08:51:56 $
 */

require_once __DIR__ . '/../stdlib/all.php';
use it\icosaedro\utils\Date;
use it\icosaedro\utils\UString;
use org\fpdf\FPDF;
use org\fpdf\Font;
use org\fpdf\FontCore;

/**
 * Generates a Gregorian calendar for a given year, one page per month, in PDF format.
 * Holidays are marked with light gray.
 * Currently, only Italian holidays are supported.
 * Measures are adjusted for A4 paper.
 */
class Calendar
{
	/**
	 * Year for which the calendar has to be generated.
	 * @var int
	 */
	private $year = 0;
	
	/**
	 * Month names, UTF-8 encoded. First month must be January.
	 */
	private static $MONTH_NAMES = ["Gennaio", "Febbraio", "Marzo", "Aprile",
		"Maggio", "Giugno", "Luglio", "Agosto",
		"Settembre", "Ottobre", "Novembre", "Dicembre"];
	
	/**
	 * Week day names, UTF-8 encoded. Short names (here 3 letters) leaves more space
	 * for hand written notes in the resulting printed page. First day must be Monday.
	 */
	private static $DAY_NAMES = ["Lun", "Mar", "Mer", "Gio", "Ven", "Sab", "Dom"];
	
	/**
	 * Dumps PDF pages here.
	 * @var FPDF
	 */
	private $pdf;
	
	/**
	 * Font to be used.
	 * @var Font
	 */
	private $font;
	
	/**
	 * Constructor sets here the month of the Easter monday holiday, if possible,
	 * otherwise remains 0.
	 * @var int
	 */
	private $easter_monday_month = 0;
	
	/**
	 * Constructor sets here the day of the Easter monday holiday, if possible,
	 * otherwise remains 0.
	 * @var int
	 */
	private $easter_monday_day = 0;
	
	
	/**
	 * Calculates the Easter monday date using the Gauss method as per
	 * https://it.wikipedia.org/wiki/Calcolo_della_Pasqua
	 * and sets the $easter_monday_* properties accordingly.
	 * @return void
	 */
	private function setEasterMonday() {
		$a = $this->year % 19;
		$b = $this->year % 4;
		$c = $this->year % 7;
		if( $this->year <= 1699 ){
			$M = 22;  $N = 2;
		} else if( $this->year <= 1799 ){
			$M = 23;  $N = 3;
		} else if( $this->year <= 1899 ){
			$M = 23;  $N = 4;
		} else if( $this->year <= 2099 ){
			$M = 24;  $N = 5;
		} else if( $this->year <= 2199 ){
			$M = 24;  $N = 6;
		} else if( $this->year <= 2299 ){
			$M = 25;  $N = 0;
		} else if( $this->year <= 2399 ){
			$M = 26;  $N = 1;
		} else if( $this->year <= 2499 ){
			$M = 25;  $N = 1;
		} else {
			// Gauss does not help beyond 2500, sorry posterity!
			// BUG: fix Easter date within year 2500...
			return;
		}
		$d = (19 * $a + $M) % 30;
		$e = (2 * $b + 4 * $c + 6 * $d + $N) % 7;
		if( $d + $e < 10 ){
			$easter_month = 3;
			$easter_day = $d + $e + 22;
		} else {
			$easter_month = 4;
			$easter_day = $d + $e - 9;
		}
		if( $easter_month == 4 && $easter_day == 26 ){
			$easter_day = 19;
		} else if( $easter_month == 4 && $easter_day == 25 && $d == 28 && $e == 6 && $a > 10 ){
			$easter_day = 18;
		}
		$easter = new Date($this->year, $easter_month, $easter_day);
		$easter_monday = $easter->addDays(1);
		if( $easter_monday->dayOfWeek() != 0 )
			throw new RuntimeException("calculated Easter Monday $easter_monday... is not Monday!");
		$this->easter_monday_month = $easter_monday->getMonth();
		$this->easter_monday_day = $easter_monday->getDay();
	}
	
	
	/**
	 * Initializes the calendar generator and the PDF object.
	 * @param int $year Year for which the calendar has to be generated. The
	 * supported range is [1583,9999].
	 * @throws InvalidArgumentException
	 * @throws ErrorException
	 */
	private function __construct($year) {
		$this->year = $year;
		$this->pdf = new FPDF();
		$this->font = FontCore::factory('helvetica', false, false);
		$this->pdf->setMargins(0, 0);
		$this->pdf->setAutoPageBreak(false, 0);
		$this->setEasterMonday();
	}
	
	/**
	 * Returns true if the given month,day pair is holiday.
	 * @param int $month Month in the range [1,12].
	 * @param int $day Day of the month in the range [1,31].
	 * @return boolean True if the given month,day pair is holiday.
	 */
	private function isHoliday($month, $day){
		return $month == 1 && $day == 1
			|| $month == 1 && $day == 6
			|| $month == 4 && $day == 25
			|| $month == 5 && $day == 1
			|| $month == 6 && $day == 2
			|| $month == 8 && $day == 15
			|| $month == 11 && $day == 1
			|| $month == 12 && $day == 8
			|| $month == 12 && $day == 25
			|| $month == 12 && $day == 26
			|| $this->easter_monday_month > 0 && $this->easter_monday_month == $month
				&& $this->easter_monday_day == $day;
	}
	
	
	/**
	 * Generates the PDF for the given month.
	 * @param int $month Month in the range [1,12].
	 * @throws ErrorException
	 */
	private function generateMonthPage($month){
		// Measures are in mm units and adjusted for A4 page format.
		// Font sizes are in points (1/72 inch).
		$p = $this->pdf;
		$p->addPage();
		$x = 50;
		$y = 25;
		$s = self::$MONTH_NAMES[$month-1]." ".$this->year;
		$us = UString::fromUTF8($s);
		$p->setFont($this->font, '', 40);
		$p->setTextColor(0, 0, 0);
		$w = $p->widthOf($us);
		$p->setXY((int) ((210.0 - $w)/2), $y); // center month name on A4
		$p->write(0, $us);
		$gg = Date::daysInMonth($this->year, $month);
		$x = 18;
		$y = 45;
		$dy = 16;
		for($g = 1; $g <= $gg; $g++){
			$day_week = (new Date($this->year, $month, $g))->dayOfWeek();
			$day_name = self::$DAY_NAMES[$day_week];
			$s = "$g $day_name";
			$p->setXY($x, $y);
			$style = '';
			if( $day_week == 6 || $this->isHoliday($month, $g) )
				$p->setTextColor(128, 128, 128);
			else
				$p->setTextColor(0, 0, 0);
			$p->setFont($this->font, $style, 18);
			$p->write(0, UString::fromUTF8($s));
			$p->line($x, $y + 5, $x + 80, $y + 5);
			$y += $dy;
			if( $g == 16 ){
				// switch to second column
				$x = 109;
				$y = 45;
			}
		}
	}
	
	
	/**
	 * Generates a wall calendar in PDF format, one page per month, with
	 * large spaces for notes.
	 * @param int $year Year for which the calendar has to be generated, in the
	 * range [1583,9999].
	 * @param string $filename Destination file.
	 * @throws ErrorException
	 */
	static function main($year, $filename) {
		$cal = new self($year);
		for($month = 1; $month <= 12; $month++){
			$cal->generateMonthPage($month);
		}
		$cal->pdf->close();
		$cal->pdf->output($filename, "F");
	}
	
}

$year = (int) $argv[1];
if( $year <= 0 )
	throw new InvalidArgumentException("invalid or missing year");
Calendar::main($year, "calendar-$year.pdf");
