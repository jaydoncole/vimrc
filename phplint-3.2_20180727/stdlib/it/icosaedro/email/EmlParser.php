<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

use it\icosaedro\io\IOException;
use it\icosaedro\io\File;
use it\icosaedro\io\StringInputStream;
use it\icosaedro\io\FileInputStream;
use it\icosaedro\utils\Strings;
use it\icosaedro\utils\StringBuffer;

/*. require_module 'pcre'; .*/

/**
 * Email parse routines. This class provides functions to parse an RFC 822 email
 * messages from different sources. Full email or header only can be parsed.
 * The base parse methods all require a reader object implementing the
 * ReaderInterface; two implementations are available that provide this
 * interface: ReaderFromStream to read from and abstract source of bytes
 * InputStream, and ReaderFromMbox to read from a mbox formatted digest of
 * messages. The simplest method accepts a file name to parse an EML file.
 * A tutorial about EmlParser with examples and references is
 * available at {@link http://www.icosaedro.it/phplint/mailer-tutorial}.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/23 12:37:42 $
 */
class EmlParser {
	
	/*. forward static MIMEAbstractPart function parse(ReaderInterface $in,
			string $parent_boundary) throws IOException, EmlFormatException; .*/
	
	/**
	 * Parse the body of this part. Entering this method, the current line is
	 * expected to be the header/body empty separation line. The content is
	 * parsed according to the header specifications of this object.
	 * @param ReaderInterface $in
	 * @param Header $header
	 * @param string $boundary Boundary of the parent part, or NULL if no
	 * parent part.
	 * @return MIMEPartMemory
	 * @throws IOException
	 * @throws EmlFormatException
	 */
	private static function parseSingleBody($in, $header, $boundary)
	{
		$encoding = $header->getEncoding();
		if( $boundary !== NULL ){
			$boundary_next = "--$boundary";
			$boundary_end  = "--$boundary--";
		} else {
			$boundary_next = NULL;
			$boundary_end = NULL;
		}
		$content = new StringBuffer();
		do {
			$in->readLine();
			$line = $in->getLine();
			if( $line === NULL ){
				if( $boundary !== NULL )
					throw new EmlFormatException("$in: missing boundary");
				break;
			} else if( $line === $boundary_next || $line === $boundary_end ){
				break;
			}
			switch($encoding){
				case Header::ENCODING_7BIT:
					$chunk = preg_replace("/[\x80-\xff]/", "", $line) . "\n";
					break;
				case Header::ENCODING_8BIT:
					$chunk = "$line\n";
					break;
				case Header::ENCODING_QUOTED_PRINTABLE:
					$chunk = quoted_printable_decode($line);
					if( ! Strings::endsWith($line, "=") )
						$chunk .= "\n";
					break;
				case Header::ENCODING_BASE64:
					$chunk = base64_decode($line);
					break;
				default:
					throw new EmlFormatException("$in: unknown or unsupported encoding: $encoding");
			}
			$content->append($chunk);
		} while(TRUE);
		$part = new MIMEPartMemory($header);
		$part->content = $content->__toString();
		return $part;
	}
	
	/**
	 * @param ReaderInterface $in
	 * @param Header $header
	 * @return MIMEMultiPart
	 * @throws IOException
	 * @throws EmlFormatException
	 */
	private static function parseMultipartBody($in, $header)
	{
		$multi = new MIMEMultiPart($header);
		
		$boundary = $header->getBoundary(); // caller already checked not empty
		$boundary_next = "--$boundary";
		$boundary_end  = "--$boundary--";
		
		// Skip "preamble" ignoring any text up to the boundary:
		do {
			$in->readLine();
			$line = $in->getLine();
			if( $line === NULL )
				throw new EmlFormatException("$in: missing end boundary $boundary_end");
			else if( $line === $boundary_end )
				return $multi; // empty multi-part
			else if( $line === $boundary_next )
				break;
		} while(TRUE);
		
		// Found next boundary; read parts:
		do {
			$part = self::parse($in, $boundary);
			$multi->appendPart($part);
			$line = $in->getLine();
			if( $line === NULL )
				throw new EmlFormatException("$in: missing end boundary");
			else if( $line === $boundary_end )
				break;
			else if( $line === $boundary_next )
				continue;
			else
				throw new EmlFormatException("$in: unexpected line: $line"); // FIXME: ????
		} while(TRUE);
		
		// Skip "epilogue" of empty lines:
		do {
			$in->readLine();
			$line = $in->getLine();
			if( $line === NULL || strlen( trim($line) ) > 0 )
				return $multi;
		} while(TRUE);
	}
	
	/**
	 * Parse the header of the email or MIME part, depending on the boundary.
	 * @param ReaderInterface $in Source of lines. Starts reading from the next
	 * line.
	 * @param string $parent_boundary If NULL, read up to the end of the source.
	 * If not NULL, reads up to the boundary (excluded).
	 * @return Header Parsed header.
	 * @throws IOException
	 * @throws EmlFormatException
	 */
	static function parseHeader($in, $parent_boundary)
	{
		if( $parent_boundary !== NULL ){
			$parent_boundary_next = "--$parent_boundary";
			$parent_boundary_end  = "--$parent_boundary--";
		} else {
			$parent_boundary_next = NULL;
			$parent_boundary_end = NULL;
		}
		
		$header = new Header();
		$field = /*. (string) .*/ NULL;
		do {
			$in->readLine();
			$line = $in->getLine();
			if( $line === NULL || $line === "" ){
				if( $field !== NULL )
					$header->addLine($field);
				break;
			} else if( $line === $parent_boundary_next ){
				throw new EmlFormatException("$in: missing body part (next boundary marker found)");
			} else if( $line === $parent_boundary_end ){
				throw new EmlFormatException("$in: missing body part (end boundary marker found)");
			} else if( strlen($line) > 0 && ($line[0] === " " || $line[0] === "\t") ){
				// Field continuation line detected.
				if( $field === NULL )
					throw new EmlFormatException("$in: first line starts with white space");
				$field .= $line;
			} else {
				// Beginning of new field.
				if( $field !== NULL ){
					$header->addLine($field);
					$field = NULL;
				}
				if( preg_match("/^[!-9;-~]+[ \t]*:/sD", $line) !== 1 )
					throw new EmlFormatException("$in: invalid syntax for header field line: $line");
				$field = $line;
			}
		} while(TRUE);
		return $header;
	}
	
	/**
	 * Parse the body of the email or MIME part, depending on the boundary.
	 * @param ReaderInterface $in Source of lines. Starts reading from the next
	 * line.
	 * @param Header $header Header of this email.
	 * @param string $parent_boundary If NULL, read up to the end of the source.
	 * If not NULL, reads up to the boundary (excluded).
	 * @return MIMEAbstractPart Parsed email.
	 * @throws IOException
	 * @throws EmlFormatException
	 */
	static function parseBody($in, $header, $parent_boundary)
	{
		if( Strings::startsWith($header->getType(), "multipart/")
		&& $header->getBoundary() !== NULL )
			return self::parseMultipartBody($in, $header);
		else
			return self::parseSingleBody($in, $header, $parent_boundary);
	}
	
	/**
	 * Parse the email or MIME part, depending on the boundary.
	 * @param ReaderInterface $in Source of lines. Starts reading from the next
	 * line.
	 * @param string $parent_boundary If NULL, read up to the end of the source.
	 * If not NULL, reads up to the boundary (excluded).
	 * @return MIMEAbstractPart Parsed email.
	 * @throws IOException
	 * @throws EmlFormatException
	 */
	static function parse($in, $parent_boundary)
	{
		$header = self::parseHeader($in, $parent_boundary);
		return self::parseBody($in, $header, $parent_boundary);
	}
	
	/**
	 * Parse an EML file.
	 * @param string $path Path to the file, system locale encoding.
	 * @return MIMEAbstractPart
	 * @throws IOException
	 * @throws EmlFormatException
	 */
	static function parseFile($path)
	{
		$in = new ReaderFromStream( new FileInputStream( File::fromLocaleEncoded($path) ) );
		$email = self::parse($in, NULL);
		$in->close();
		return $email;
	}
	
	/**
	 * Parse an EML string.
	 * @param string $email Email given as a string.
	 * @return MIMEAbstractPart
	 * @throws IOException
	 * @throws EmlFormatException
	 */
	static function parseString($email)
	{
		$in = new ReaderFromStream( new StringInputStream($email) );
		return self::parse($in, NULL);
	}
	
}
