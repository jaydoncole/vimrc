<?php

namespace org\fpdf;

require_once __DIR__ . "/../../all.php";
use InvalidArgumentException;
use it\icosaedro\containers\Hash;
use it\icosaedro\utils\UString;
use ErrorException;
use RuntimeException;

/**
 * Builds and represents a single Adobe PDF core font.
 * Core fonts are available under any PDF reader and do not need to be
 * embedded in the generated document. The standard names of these 14 core
 * fonts are also available as constants. Note that Courier, Helvetica and
 * Times have the alternate bold, italic and bold + italic variants, while
 * Symbol and ZapfDingbats do not. Example:
 * <pre>$font = new FontCore(FontCore::HELVETICA_BOLD);</pre>
 * <p>This source is an excerpt from the original FPDF 1.7 program of the author.
 * @version $Date: 2016/02/22 09:19:00 $
 * @author Olivier Plathey
 * @author Umberto Salsi <salsi@icosaedro.it> (porting to PHPLint)
 * @license Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software to use, copy, modify, distribute,
 * sublicense, and/or sell copies of the software, and to permit persons to
 * whom the software is furnished to do so.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED.
 */
class FontCore extends Font {
	
	const
		COURIER = 'Courier',
		COURIER_BOLD = 'Courier-Bold',
		COURIER_BOLD_OBLIQUE = 'Courier-BoldOblique',
		COURIER_OBLIQUE = 'Courier-Oblique',
		HELVETICA = 'Helvetica',
		HELVETICA_BOLD = 'Helvetica-Bold',
		HELVETICA_OBLIQUE = 'Helvetica-Oblique',
		HELVETICA_BOLD_OBLIQUE = 'Helvetica-BoldOblique',
		SYMBOL = 'Symbol',
		TIMES_ROMAN = 'Times-Roman',
		TIMES_BOLD = 'Times-Bold',
		TIMES_BOLD_ITALIC = 'Times-BoldItalic',
		TIMES_ITALIC = 'Times-Italic',
		ZAPFDINGBATS = 'ZapfDingbats';
	
	private $hash = 0;
	
	/**
	 * Maps codepoint to font index (that is, CID).
	 * @var int[int]
	 */
	private $indeces;
	
	
	/**
	 * Returns a list of the PDF core fonts.
	 * @return string[int] List of the PDF core font names.
	 */
	public static function getList()
	{
		return array(
			self::COURIER,
			self::COURIER_BOLD,
			self::COURIER_BOLD_OBLIQUE,
			self::COURIER_OBLIQUE,
			self::HELVETICA,
			self::HELVETICA_BOLD,
			self::HELVETICA_OBLIQUE,
			self::HELVETICA_BOLD_OBLIQUE,
			self::SYMBOL,
			self::TIMES_ROMAN,
			self::TIMES_BOLD,
			self::TIMES_BOLD_ITALIC,
			self::TIMES_ITALIC,
			self::ZAPFDINGBATS
		);
	}
	
	
	/**
	 * Returns true if this object represents the same core font of the other.
	 * @param object $other
	 * @return boolean
	 */
	function equals($other)
	{
		if( $other === NULL )
			return FALSE;
		if( $this === $other )
			return TRUE;
		if( get_class($other) !== __CLASS__ )
			return FALSE;
		$other2 = cast(__CLASS__, $other);
		return $this->name === $other2->name;
	}
	
	
	/**
	 * Returns an hash of this core font.
	 * return int
	 */
	function getHash() {
		if( $this->hash == 0 )
			$this->hash = Hash::hashOfString($this->name);
		return $this->hash;
	}
	
	
	/**
	 * Returns the canonical name of this core font.
	 * @return string
	 */
	function __toString() {
		return $this->name;
	}
	
	
	/**
	 * Returns the canonical name of this core font.
	 * @return UString
	 */
	function toUString() {
		return UString::fromASCII($this->name);
	}
	

	/**
	 * Builds a PDF core font.
	 * @param string $fontname Name of the standard PDF's core font.
	 * @throws InvalidArgumentException Invalid core font name.
	 */
	public function __construct($fontname)
	{
		if( !in_array($fontname, self::getList()) )
			throw new InvalidArgumentException("no this core font: $fontname. Valid names are: "
					. implode(", ", self::getList()));
		$this->name = $fontname;
		$this->underlinePosition = -100;
		$this->underlineThickness = 50;
		$this->missingWidth = 600; // FIXME: ok?
		try {
			$charWidths = /*. (int[int]) .*/ array();
			$indeces = /*. (int[int]) .*/ array();
			$f = fopen(__DIR__ . "/fontcoretables/" . $this->name . ".txt", "rb");
			$codepoint = 0;
			$index = 0;
			$width = 0;
			while( ($line = fgets($f)) !== FALSE ){
				if( sscanf($line, "%x %x %x", $codepoint, $index, $width) === 3 ){
					$charWidths[$codepoint] = $width;
					$indeces[$codepoint] = $index;
				}
			}
			fclose($f);
		}
		catch(ErrorException $e){
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
		$this->charWidths = $charWidths;
		$this->indeces = $indeces;
	}
	
	
	/**
	 * Returns a core font given family, bold, italic style. Convenience method
	 * that may help in simpler applications. Convenience method that may be
	 * used in place of the constructor to build styled core fonts.
	 * @param string $family Requested font family: Courier, Helvetica or Times
	 * (case insensitive).
	 * @param boolean $bold
	 * @param boolean $italic
	 * @throws InvalidArgumentException No this font family.
	 */
	public static function factory($family, $bold, $italic) {
		$a = array(
			"courier" => array(self::COURIER, self::COURIER_BOLD, self::COURIER_OBLIQUE, self::COURIER_BOLD_OBLIQUE),
			"helvetica" => array(self::HELVETICA, self::HELVETICA_BOLD, self::HELVETICA_OBLIQUE, self::HELVETICA_BOLD_OBLIQUE),
			"times" => array(self::TIMES_ROMAN, self::TIMES_BOLD, self::TIMES_ITALIC, self::TIMES_BOLD_ITALIC)
		);
		if( ! isset($a[$family]) )
			throw new InvalidArgumentException("undefined core font family: $family");
		$name = $a[$family][($bold? 1 : 0) + ($italic? 2 : 0)];
		return new self($name);
	}
	
	
	/**
	 * @param UString $s
	 * @return string
	 */
	public function encode($s)
	{
		if( $s === NULL )
			return "";
		
		$indeces = $this->indeces;
		$res = "";
		$s_len = $s->length();
		for($i = 0; $i < $s_len; $i++){
			$codepoint = $s->codepointAt($i);
			if( array_key_exists($codepoint, $indeces) )
				$res .= chr($indeces[$codepoint]);
			else
				$res .= "?";
		}
		return $res;
	}
	
	
	/**
	 * Writes this font into the PDF document and sets the $n property.
	 * @param PdfObjWriterInterface $w
	 */
	public function put($w) {
		$this->n = $w->addObj();
		$w->put('<</Type /Font');
		$w->put('/BaseFont /'.$this->name);
		$w->put('/Subtype /Type1');
		if($this->name !== self::SYMBOL && $this->name !== self::ZAPFDINGBATS)
			$w->put('/Encoding /WinAnsiEncoding');
		$w->put('>>');
		$w->put('endobj');
	}
	
}
