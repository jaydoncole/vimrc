<?php

namespace org\fpdf;

require_once __DIR__ . "/../../all.php";
use it\icosaedro\containers\Hashable;
use it\icosaedro\containers\Printable;
use it\icosaedro\containers\UPrintable;
use it\icosaedro\utils\UString;

/**
 * Basic properties of a font as required by FPDF. More specialized classes
 * represent PDF's core fonts, see FontCore and FontTrueType.
 * Objects of this class must be comparable so that the main class FPDF can
 * avoid to add to the final document redundant copies of the same font.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2015/03/16 01:47:14 $
 */
abstract class Font implements Hashable, Printable, UPrintable {
	
	const NAME = __CLASS__;
	
	/**
	 * Base font name of this font.
	 * @var string
	 */
	public $name;
	
	/**
	 * Underline position (pt/1000).
	 * @var double
	 */
	public $underlinePosition = 0.0;
	
	/**
	 * Underline thickness (pt/1000).
	 * @var double
	 */
	public $underlineThickness = 0.0;
	
	/**
	 * Maps codepoint [0,65535] to glyph width (units of 1/1000 of the font size).
	 * Values are sorted by codepoint.
	 * @var int[int]
	 */
	public $charWidths;
	
	/**
	 * Default width for undefined characters (pt/1000).
	 * @var int
	 */
	public $missingWidth = 0;
	
	/**
	 * Object number for this font in the PDF document.
	 * @var int
	 */
	public $n = 0;
	
	/**
	 * Font number for this font in the PDF document.
	 * @var int
	 */
	public $i = 0;
	
	/**
	 * Set of characters actually used in the document. Defined entries have
	 * the codepoint as index; the value is always set to TRUE, but it does
	 * not care.
	 * @var boolean[int]
	 */
	public $subset = array();
	
	/**
	 * Encodes a string of text to be put in a PDF page according to the charset
	 * implemented by this font. For example, core fonts might need a 1 byte per
	 * character Windows-1252 encoding, while TrueType fonts need UCS-2BE.
	 * The result is not PDF escaped.
	 * @param UString $s
	 * @return string Encoded string.
	 */
	public abstract function encode($s);
	
	/**
	 * Writes this font into the PDF document and sets the $n property.
	 * @param PdfObjWriterInterface $w
	 * @return void
	 * @throws \ErrorException
	 */
	public abstract function put($w);
}
