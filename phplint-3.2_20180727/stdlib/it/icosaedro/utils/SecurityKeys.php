<?php

namespace it\icosaedro\utils;

require_once __DIR__ . "/../../../all.php";

/*.
	require_module 'zlib';
	require_module 'hash';
	require_module 'posix';
.*/

use RuntimeException;
use ErrorException;

/**
 * Provides functions to sign and possibly also encrypt data so to protect
 * integrity and secrecy of informations that need to be temporarily stored
 * on a shared, insecure support. This is the case, for example, of an "hidden"
 * form field in an HTML web page. Two sets of keys are created: the current set
 * and the older set. Each set of keys covers a limited range of time since its
 * creation, then the older set is discarded, the curret set becomes the older
 * one, and a new current set is generated. Retrieved data that were signed and
 * possibly encrypted with an expired set of keys are stale and rejected.
 * Keys are saved on a file in the temporary directory. Under Linux that file
 * gets access permissions restricted to the current user. Under Windows the
 * temporary directory C:\Users\USERNAME\AppData\Local\Temp which already has
 * access permissions restricted to the current user, but in some other cases the
 * shared directory C:\windows\TEMP is used instead.
 * <p>BEWARE: on shared hosting environments, keys might be readable by any user
 * accessing the system.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/03/29 04:59:36 $
 */
class SecurityKeys {
	
	/*
	 * IMPLEMENTATION NOTES. Key 1, the current key, is used to sign, encrypt
	 * and verify. Key 2, the older key, is used to verify only.
	 * Key 1 lasts within EXPIRE seconds, then becomes key 2; key 2 lasts
	 * 2*EXPIRE seconds (keys rotation). This ensures that data can be validated
	 * for a period of time ranging from at least EXPIRE seconds and no more
	 * than 2*EXPIRE seconds.
	 */
	
	/**
	 * Keys espire period (s).
	 */
	const EXPIRE = 4 * 3600;
	
	/** @access private */
	const CIPHER = "aes-128-ctr";
//	const CIPHER = "aes-256-ctr";
	
	/** @access private */
	const HMAC_ALGO = 'sha1';
	
	/** @access private */
	const HMAC_LEN = 20;
	
	/**
	 * Timestamp last time keys have been read from file (s)
	 * @var int
	 */
	private static $last_update = 0;
	
	/**
	 * Current key generated timestamp (s).
	 * @var int
	 */
	private static $k1ts = 0;
	
	/**
	 * Current key value.
	 * @var string
	 */
	private static $k1v;
	
	/**
	 * Older key generated timestamp (s). Zero if not available.
	 * @var int
	 */
	private static $k2ts = 0;
	
	/**
	 * Older key value.
	 * @var string
	 */
	private static $k2v;
	
	/**
	 * Opens the keys file with exclusive lock.
	 * @return resource
	 * @throws ErrorException
	 * @throws RuntimeException
	 */
	private static function open()
	{
		if( PHP_OS === "Linux" )
			$user = "-" . posix_geteuid();
		else
			$user = "-" . rawurlencode(getenv('USERNAME'));
		$fn = sys_get_temp_dir() . "/SecurityKeys" . $user . ".txt";
		$f = fopen($fn, "c+");
		chmod($fn, 0600);
		$got_lock = FALSE;
		for($i = 5; $i > 0; $i--){
			$got_lock = flock($f, LOCK_EX);
			if( $got_lock )
				break;
			sleep(1);
		}
		if( ! $got_lock ){
			flock($f, LOCK_UN);
			fclose($f);
			throw new RuntimeException("failed acquiring lock on $fn");
		}
		return $f;
	}
	
	/**
	 * @param resource $f
	 * @throws ErrorException
	 */
	private static function write($f)
	{
		fseek($f, 0);
		fwrite($f, self::$k1ts . " ");
		fwrite($f, base64_encode(self::$k1v)." ");
		fwrite($f, self::$k2ts . " ");
		fwrite($f, base64_encode(self::$k2v));
	}
	
	/**
	 * Reads the keys from the keys file.
	 * @param int $now Current timestamp.
	 * @param resource $f Keys file.
	 * @return boolean True if read succeeded.
	 * @throws ErrorException
	 */
	private static function read($now, $f)
	{
		fseek($f, 0);
		$s = fgets($f);
		if( ! is_string($s) )
			return FALSE; // empty file
		$a = explode(" ", $s);
		if( count($a) != 4 )
			return FALSE; // invalid format
		self::$k1ts = (int) $a[0];
		self::$k1v  = base64_decode($a[1]);
		self::$k2ts = (int) $a[2];
		self::$k2v  = base64_decode($a[3]);
		return TRUE;
	}
	
	private static function generateKey()
	{
		// 20 bytes seem enought for typical applications using SHA1.
		return SecureRandom::randomBytes(20);
	}
	
	/**
	 * Updates the keys. Creates a new keys file if it does not already exist,
	 * and rotates the keys as required.
	 * @throws ErrorException
	 */
	private static function update()
	{
		$now = time();
		if( $now - self::$last_update < 10 )
			return; // avoid overloading
		$f = self::open();
		$must_write = FALSE;
		if( ! self::read($now, $f) ){
			// Both keys missing. Invalidate both:
			self::$k1ts = self::$k2ts = 0;
			self::$k1v = self::$k2v = "invalid";
			$must_write = TRUE;
		}
		if( self::$k2ts != 0 && self::$k2ts < $now - 2*self::EXPIRE ){
			// Older key expired. Invalidate:
			self::$k2ts = 0;
			self::$k2v = "invalid";
			$must_write = TRUE;
		}
		if( self::$k1ts < $now - 2*self::EXPIRE ){
			// Current key expired. Generate new current key:
			self::$k1ts = $now;
			self::$k1v = self::generateKey();
			$must_write = TRUE;
		} else if( self::$k1ts < $now - self::EXPIRE ){
			// Current key is stale; becomes the older one.
			self::$k2ts = self::$k1ts;
			self::$k2v = self::$k1v;
			// Generate new current key:
			self::$k1ts = $now;
			self::$k1v = self::generateKey();
			$must_write = TRUE;
		}
		if( $must_write )
			self::write($f);
		ftruncate($f, ftell($f));
		flock($f, LOCK_UN);
		fclose($f);
		self::$last_update = $now;
	}
	
	
	/**
	 * Returns the signed and possibly also encrypted data. Data are also
	 * compressed. The resulting value also carries a timestamp expected to last
	 * within EXPIRE and 2*EXPIRE seconds; beyond that time limit, decoding will
	 * fail.
	 * @param string $data Data to sign and encrypt.
	 * @param boolean $encrypt Whether to apply encryption.
	 * @return string Signed and possibly encrypted data.
	 */
	static function encode($data, $encrypt)
	{
		/*
		 * IMPLEMENTATION NOTES.
		 * The returned encoded block contains, in the order:
		 * - Timestamp of the key used (4 bytes).
		 * - HMAC of the (possibly encrypted) compressed data  (self::HMAC_LEN
		 *   bytes, depending on the algo).
		 * 
		 * Then, if not encrypted:
		 * - The compressed data.
		 * 
		 * Otherwise, if encrypted:
		 * - IV (length dependent on the algo).
		 * - Encrypted and compressed data.
		 */
		
		try {
			self::update();
			$data = gzcompress($data);
			if( $encrypt ){
				$iv_len = openssl_cipher_iv_length(self::CIPHER);
				$iv_len = is_int($iv_len)? $iv_len : 0;
				$iv = $iv_len == 0? /*. (string) .*/ NULL : SecureRandom::randomBytes($iv_len);
				$data = $iv . openssl_encrypt($data, self::CIPHER, self::$k1v, OPENSSL_RAW_DATA, $iv);
			}
		}
		catch( ErrorException $e ){
			throw new RuntimeException($e->getMessage(), 1, $e);
		}
		return pack("L", self::$k1ts) . hash_hmac(self::HMAC_ALGO, $data, self::$k1v, TRUE) . $data;
	}
	
	
	/**
	 * Check signature integrity and possibly also decrypts the data.
	 * @param string $data Signed and possibly encrypted data as generated by the
	 * companion encoding method.
	 * @param boolean $decrypt Whether data have to be decrypted. This option
	 * must match the choice that was made while encoding.
	 * @return string Original data.
	 * @throws SecurityException Timestamp of the signature expired or tampering
	 * attempt detected.
	 * @throws RuntimeException Internal error while decoding.
	 */
	static function decode($data, $decrypt)
	{
		try {
			self::update();
		}
		catch(ErrorException $e){
			throw new RuntimeException("failed updating keys", 1, $e);
		}
		
		// Check signature:
		if( strlen($data) < 4 + self::HMAC_LEN + 1 )
			// too short
			throw new SecurityException("invalid signed data");
		$ts = cast("int[int]", unpack("L", $data))[1];
		if( $ts == self::$k1ts )
			$key = self::$k1v;
		else if( self::$k2ts != 0 && $ts == self::$k2ts )
			$key = self::$k2v;
		else
			// expired timestamp, key not available anymore
			throw new SecurityException("invalid signed data");
		$data = substr($data, 4); // skip ts
		$hmac_exp = substr($data, 0, self::HMAC_LEN);
		$data = substr($data, self::HMAC_LEN); // skip hmac
		$hmac_got = hash_hmac(self::HMAC_ALGO, $data, $key, TRUE);
		if( $hmac_got !== $hmac_exp )
			// corrupted or tampered
			throw new SecurityException("invalid signed data");
		// Integrity check passed. Any further error is our fault, then
		// a RuntimeException.
		
		// Decryption:
		if( $decrypt ){
			try {
				$iv_len = openssl_cipher_iv_length(self::CIPHER);
			}
			catch( ErrorException $e ){
				throw new RuntimeException($e->getMessage());
			}
			$iv_len = is_int($iv_len)? $iv_len : 0;
			if( strlen($data) < $iv_len + 1 )
				throw new RuntimeException("encrypted data format error");
			if( $iv_len > 0 ){
				$iv = substr($data, 0, $iv_len);
				$data = substr($data, $iv_len);
			} else {
				$iv = NULL;
			}
			try {
				$data = openssl_decrypt($data, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
			}
			catch( ErrorException $e ){
				throw new RuntimeException($e->getMessage(), 1, $e);
			}
		}
		
		try {
			// Decompression:
			$data = gzuncompress($data);
		}
		catch(ErrorException $e){
			throw new RuntimeException($e->getMessage(), 1, $e);
		}
		
		return $data;
	}
	
}
