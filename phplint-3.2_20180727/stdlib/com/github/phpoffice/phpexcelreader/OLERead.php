<?php

/**
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package    PHPExcelReader
 * @copyright  Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    $Date: 2016/01/26 12:26:56 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";
/*. require_module 'math'; .*/

/**
 * @access private
 */
class OLERead_Property {

	public $name = '';
	public $type = 0;
	public $startBlock = 0;
	public $size = 0;

}

class OLERead {

	private $data = '';

	// Size of a sector = 512 bytes
	const BIG_BLOCK_SIZE = 0x200;
	// Size of a short sector = 64 bytes
	const SMALL_BLOCK_SIZE = 0x40;
	// Size of a directory entry always = 128 bytes
	const PROPERTY_STORAGE_BLOCK_SIZE = 0x80;
	// Minimum size of a standard stream = 4096 bytes, streams smaller than this are stored as short streams
	const SMALL_BLOCK_THRESHOLD = 0x1000;
	// header offsets
	const NUM_BIG_BLOCK_DEPOT_BLOCKS_POS = 0x2c;
	const ROOT_START_BLOCK_POS = 0x30;
	const SMALL_BLOCK_DEPOT_BLOCK_POS = 0x3c;
	const EXTENSION_BLOCK_POS = 0x44;
	const NUM_EXTENSION_BLOCK_POS = 0x48;
	const BIG_BLOCK_DEPOT_BLOCKS_POS = 0x4c;
	// property storage offsets (directory offsets)
	const SIZE_OF_NAME_POS = 0x40;
	const TYPE_POS = 0x42;
	const START_BLOCK_POS = 0x74;
	const SIZE_POS = 0x78;

	private static $IDENTIFIER_OLE = '';

	public static function static_init() {
		self::$IDENTIFIER_OLE = pack("CCCCCCCC", 0xd0, 0xcf, 0x11, 0xe0, 0xa1, 0xb1, 0x1a, 0xe1);
	}

	/**
	 * @var int
	 */
	public $wrkbook = -1;

	/**
	 * @var int
	 */
	public $summaryInformation = -1;

	/**
	 * @var int
	 */
	public $documentSummaryInformation = -1;

	/**
	 * @var OLERead_Property[int]
	 */
	private $props = array();

	/**
	 * @var string
	 */
	private $bigBlockChain;

	/**
	 * @var int
	 */
	private $rootentry = -1;

	/**
	 * @var string
	 */
	private $smallBlockChain;

	/**
	 * @var string
	 */
	private $entry;

	/**
	 * Returns a timestamp from an OLE container's date.
	 * 
	 * The following license applies only to this function, that was part of the
	 * "OLE.php" file:
	 * 
	 * <pre>
	 * PHP Version 4
	 * 
	 * Copyright (c) 1997-2002 The PHP Group
	 * 
	 * This source file is subject to version 2.02 of the PHP license,
	 * that is bundled with this package in the file LICENSE, and is
	 * available at through the world-wide-web at
	 * http://www.php.net/license/2_02.txt.
	 * If you did not receive a copy of the PHP license and are unable to
	 * obtain it through the world-wide-web, please send a note to
	 * license@php.net so we can mail you a copy immediately.
	 * 
	 * Author: Xavier Noguer &lt;xnoguer@php.net&gt;
	 * Based on OLE::Storage_Lite by Kawai, Takanori
	 * </pre>
	 * 
	 * @param string $s A binary string with the encoded date, 8 bytes long.
	 * @return int The Unix timestamp corresponding to the string.
	 */
	public static function OLE2LocalDate($s) {
		if (strlen($s) != 8) {
			throw new \InvalidArgumentException("Expecting 8 byte string");
		}

		// factor used for separating numbers into 4 bytes parts
		$factor = pow(2, 32);
		$a = unpack('V', substr($s, 4, 4));
		$high_part = (int) $a[1];
		$a = unpack('V', substr($s, 0, 4));
		$low_part = (int) $a[1];

		$big_date = ($high_part * $factor) + $low_part;
		// translate to seconds
		$big_date /= 10000000;

		// days from 1-1-1601 until the beggining of UNIX era
		$days = 134774;

		// translate to seconds from beggining of UNIX era
		$big_date -= $days * 24 * 3600;
		return (int) floor($big_date);
	}

	/**
	 * Read 4 bytes of data at specified position
	 *
	 * @param string $data
	 * @param int $pos
	 * @return int
	 */
	private static function getInt4d($data, $pos) {
		// FIX: represent numbers correctly on 64-bit system
		// http://sourceforge.net/tracker/index.php?func=detail&aid=1487372&group_id=99160&atid=623334
		// Hacked by Andreas Rehm 2006 to ensure correct result of the <<24 block on 32 and 64bit systems
		// 2015-07-29 Added "(int)" cast to abs() to pass PHPLint's validation; still to check on 64-bits platform (Umberto Salsi).
		$_or_24 = ord($data[$pos + 3]);
		if ($_or_24 >= 128) {
			// negative number
			$_ord_24 = -(int) abs((256 - $_or_24) << 24);
		} else {
			$_ord_24 = $_or_24 << 24;
		}
		return ord($data[$pos]) | (ord($data[$pos + 1]) << 8) | (ord($data[$pos + 2]) << 16) | $_ord_24;
	}

	/**
	 * Read a standard stream (by joining sectors using information from SAT)
	 *
	 * @param int $bl Sector ID where the stream starts
	 * @return string Data for standard stream
	 */
	private function _readData($bl) {
		$block = $bl;
		$data = '';

		while ($block != -2) {
			$pos = ($block + 1) * self::BIG_BLOCK_SIZE;
			$data .= substr($this->data, $pos, self::BIG_BLOCK_SIZE);
			$block = self::getInt4d($this->bigBlockChain, $block * 4);
		}
		return $data;
	}

	/**
	 * Extract binary stream data
	 * @param int $stream
	 * @return string
	 */
	public function getStream($stream) {
		if ($stream < 0) {
			return null;
		}

		$streamData = '';

		if ($this->props[$stream]->size < self::SMALL_BLOCK_THRESHOLD) {
			$rootdata = $this->_readData($this->props[$this->rootentry]->startBlock);

			$block = $this->props[$stream]->startBlock;

			while ($block != -2) {
				$pos = $block * self::SMALL_BLOCK_SIZE;
				$streamData .= substr($rootdata, $pos, self::SMALL_BLOCK_SIZE);

				$block = self::getInt4d($this->smallBlockChain, $block * 4);
			}

			return $streamData;
		} else {
			$numBlocks = (int) ($this->props[$stream]->size / self::BIG_BLOCK_SIZE);
			if ($this->props[$stream]->size % self::BIG_BLOCK_SIZE != 0) {
				++$numBlocks;
			}

			if ($numBlocks == 0) {
				return '';
			}

			$block = $this->props[$stream]->startBlock;

			while ($block != -2) {
				$pos = ($block + 1) * self::BIG_BLOCK_SIZE;
				$streamData .= substr($this->data, $pos, self::BIG_BLOCK_SIZE);
				$block = self::getInt4d($this->bigBlockChain, $block * 4);
			}

			return $streamData;
		}
	}

	/**
	 * Read entries in the directory stream.
	 */
	private function readPropertySets() {
		$offset = 0;

		// loop through entires, each entry is 128 bytes
		$entryLen = strlen($this->entry);
		while ($offset < $entryLen) {
			// entry data (128 bytes)
			$d = substr($this->entry, $offset, self::PROPERTY_STORAGE_BLOCK_SIZE);

			// size in bytes of name
			$nameSize = ord($d[self::SIZE_OF_NAME_POS]) | (ord($d[self::SIZE_OF_NAME_POS + 1]) << 8);

			// type of entry
			$type = ord($d[self::TYPE_POS]);

			// sectorID of first sector or short sector, if this entry refers to a stream (the case with workbook)
			// sectorID of first sector of the short-stream container stream, if this entry is root entry
			$startBlock = self::getInt4d($d, self::START_BLOCK_POS);

			$size = self::getInt4d($d, self::SIZE_POS);

			$name = (string) str_replace("\x00", "", substr($d, 0, $nameSize));

			$p = new OLERead_Property();
			$p->name = $name;
			$p->type = $type;
			$p->size = $size;
			$p->startBlock = $startBlock;
			$this->props[] = $p;

			// tmp helper to simplify checks
			$upName = strtoupper($name);

			// Workbook directory entry (BIFF5 uses Book, BIFF8 uses Workbook)
			if (($upName === 'WORKBOOK') || ($upName === 'BOOK')) {
				$this->wrkbook = count($this->props) - 1;
			} elseif ($upName === 'ROOT ENTRY' || $upName === 'R') {
				// Root entry
				$this->rootentry = count($this->props) - 1;
			}

			// Summary information
			if ($name === chr(5) . 'SummaryInformation') {
//                echo 'Summary Information<br />';
				$this->summaryInformation = count($this->props) - 1;
			}

			// Additional Document Summary information
			if ($name === chr(5) . 'DocumentSummaryInformation') {
//                echo 'Document Summary Information<br />';
				$this->documentSummaryInformation = count($this->props) - 1;
			}

			$offset += self::PROPERTY_STORAGE_BLOCK_SIZE;
		}
	}

	/**
	 * Read the file
	 *
	 * @param string $sFileName Filename
	 * @throws PHPExcelException
	 * @throws \ErrorException Error reading the file.
	 */
	public function read($sFileName) {
		// Get the file identifier
		// Don't bother reading the whole file until we know it's a valid OLE file
		$this->data = file_get_contents($sFileName, false, null, 0, 8);

		// Check OLE identifier
		if ($this->data !== self::$IDENTIFIER_OLE) {
			throw new PHPExcelException('The filename ' . $sFileName . ' is not recognised as an OLE file');
		}

		// Get the file data
		$this->data = file_get_contents($sFileName);

		// Total number of sectors used for the SAT
		$numBigBlockDepotBlocks = self::getInt4d($this->data, self::NUM_BIG_BLOCK_DEPOT_BLOCKS_POS);

		// SecID of the first sector of the directory stream
		$rootStartBlock = self::getInt4d($this->data, self::ROOT_START_BLOCK_POS);

		// SecID of the first sector of the SSAT (or -2 if not extant)
		$sbdStartBlock = self::getInt4d($this->data, self::SMALL_BLOCK_DEPOT_BLOCK_POS);

		// SecID of the first sector of the MSAT (or -2 if no additional sectors are used)
		$extensionBlock = self::getInt4d($this->data, self::EXTENSION_BLOCK_POS);

		// Total number of sectors used by MSAT
		$numExtensionBlocks = self::getInt4d($this->data, self::NUM_EXTENSION_BLOCK_POS);

		$bigBlockDepotBlocks = /*. (int[int]) .*/ array();
		$pos = self::BIG_BLOCK_DEPOT_BLOCKS_POS;

		$bbdBlocks = $numBigBlockDepotBlocks;

		if ($numExtensionBlocks != 0) {
			$bbdBlocks = (int) (self::BIG_BLOCK_SIZE - self::BIG_BLOCK_DEPOT_BLOCKS_POS / 4);
		}

		for ($i = 0; $i < $bbdBlocks; ++$i) {
			$bigBlockDepotBlocks[$i] = self::getInt4d($this->data, $pos);
			$pos += 4;
		}

		for ($j = 0; $j < $numExtensionBlocks; ++$j) {
			$pos = ($extensionBlock + 1) * self::BIG_BLOCK_SIZE;
			$blocksToRead = (int) min($numBigBlockDepotBlocks - $bbdBlocks, (int) (self::BIG_BLOCK_SIZE / 4) - 1);

			for ($i = $bbdBlocks; $i < $bbdBlocks + $blocksToRead; ++$i) {
				$bigBlockDepotBlocks[$i] = self::getInt4d($this->data, $pos);
				$pos += 4;
			}

			$bbdBlocks += $blocksToRead;
			if ($bbdBlocks < $numBigBlockDepotBlocks) {
				$extensionBlock = self::getInt4d($this->data, $pos);
			}
		}

		$pos = 0;
		$this->bigBlockChain = '';
		$bbs = (int) (self::BIG_BLOCK_SIZE / 4);
		for ($i = 0; $i < $numBigBlockDepotBlocks; ++$i) {
			$pos = ($bigBlockDepotBlocks[$i] + 1) * self::BIG_BLOCK_SIZE;

			$this->bigBlockChain .= substr($this->data, $pos, 4 * $bbs);
			$pos += 4 * $bbs;
		}

		$pos = 0;
		$sbdBlock = $sbdStartBlock;
		$this->smallBlockChain = '';
		while ($sbdBlock != -2) {
			$pos = ($sbdBlock + 1) * self::BIG_BLOCK_SIZE;

			$this->smallBlockChain .= substr($this->data, $pos, 4 * $bbs);
			$pos += 4 * $bbs;

			$sbdBlock = self::getInt4d($this->bigBlockChain, $sbdBlock * 4);
		}

		// read the directory stream
		$block = $rootStartBlock;
		$this->entry = $this->_readData($block);

		$this->readPropertySets();
	}

}

OLERead::static_init();
