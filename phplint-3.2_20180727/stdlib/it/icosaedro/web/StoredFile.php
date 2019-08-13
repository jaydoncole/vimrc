<?php
namespace it\icosaedro\web;

require_once __DIR__ . "/../../../all.php";
/*. require_module 'spl'; .*/
/*. require_module 'pgsql'; .*/

use RuntimeException;
use ErrorException;

/**
 * Stores a user file, typically uploaded via WEB, saving its original name, type,
 * size and content. Each file gets its unique ID number that allows to retrieve
 * the file. Files can be temporary, becoming "stale" after a given amount of
 * time, and then can be removed.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/03/07 10:37:26 $
 */
class StoredFile {
	
//FIXME: support stream reading/writing of file from InputStreamAsResource etc.
//FIXME: explain how to use method deleteStale() ok make all automatic, for ex. call after N confirmed uploads.
	
	/**
	 * Store directory. Must be set to some existing, accessible, initially empty directory.
	 */
	public static $STORE = "/";
	
	/**
	 * Columns of the table (Postgresql):
	 * 
create table file_store (
id serial,
in_use smallint default 0,
confirmed smallint default 0,
type text,
name text,
size integer,
upload_timestamp integer);
	 * @access private
	 */
	const TABLE = "file_store";
	
	/**
	 * ID number in the file store.
	 * @var int
	 */
	private $id = 0;
	
	/**
	 * Confirmed files are stored permanently; non-confirmed, stale files are
	 * periodically removed.
	 * @var boolean
	 */
	private $confirmed = false;
	
	/**
	 * MIME type.
	 * @var string
	 */
	private $type;
	
	/**
	 * Original file name of the file, UTF-8.
	 * @var string
	 */
	private $name;
	
	/**
	 * Length of the file, bytes.
	 * @var int
	 */
	private $size = 0;
	
	/**
	 * Upload timestamp, seconds since Unix epoch.
	 * @var int
	 */
	private $upload_timestamp = 0;
	
	
	/**
	 * 
	 * @param boolean $b
	 * @return string
	 */
	private static function encodeBoolean($b)
	{
		return $b? "1" : "0";
	}
	
	
	/**
	 * 
	 * @param string $s
	 * @return boolean
	 * @throws RuntimeException
	 */
	private static function decodeBoolean($s)
	{
		if( $s === "0" )
			return FALSE;
		else if( $s === "1" )
			return TRUE;
		else
			throw new RuntimeException("invalid boolean value: $s");
	}
	

	public function __toString() {
		return __CLASS__ . ": id=" . $this->id
			.", confirmed=" . ($this->confirmed? "true":"false")
			.", type=" . $this->type
			.", name=" . $this->name
			.", size=" . $this->size;
	}
	
	
	/**
	 * @param int $id
	 * @return string
	 */
	private static function idToPath($id)
	{
		return self::$STORE . sprintf("/%02d/%02d/%02d", (int)($id/10000), (int)($id/100)%100, $id%100);
	}
	
	
	/**
	 * @param string $filepath
	 * @param string $type MIME type. If NULL, it is guessed.
	 * @param string $name Name of the file, UTF-8. If NULL, uses
	 * the basename of $filepath.
	 * @return self
	 * @throws ErrorException
	 */
	public static function fromFile($filepath, $type = null, $name = null)
	{
		# FIXME: guess file MIME type if not specified
		# FIXME: save info in DB and return ID
		# FIXME: save file content in the store
		$sf = new self();
		# FIXME: set object's properties
		return $sf;
	}
	
	
	/**
	 * @param resource $db
	 * @param int $id
	 * @return void
	 * @throws ErrorException
	 */
	private static function markUnused($db, $id)
	{
		pg_query($db, "update ".self::TABLE
		." set in_use=".self::encodeBoolean(FALSE)
		.", confirmed=".self::encodeBoolean(FALSE)
		.", type=''"
		.", name=''"
		.", size=0"
		.", upload_timestamp=0"
		." where id=$id");
	}
	
	
	/**
	 * 
	 * @param resource $db
	 * @param string $type
	 * @param string $name
	 * @param int $size
	 * @param int $upload_timestamp
	 * @return int ID del record.
	 * @throws ErrorException
	 */
	private static function add($db, $type, $name, $size, $upload_timestamp)
	{
		pg_query($db, "begin;");
		try {
			$r = pg_query($db, "select id from ".self::TABLE
				." where in_use=".self::encodeBoolean(FALSE)." limit 1");
			if( pg_num_rows($r) > 0 ){
				$id = (int) pg_fetch_assoc($r, 0)['id'];
				pg_query($db, "update ".self::TABLE
				." set in_use=".self::encodeBoolean(TRUE)
				.", confirmed=".self::encodeBoolean(FALSE)
				.", type=". pg_escape_literal($db, $type)
				.", name=". pg_escape_literal($db, $name)
				.", size=$size"
				.", upload_timestamp=$upload_timestamp"
				." where id=$id");
			} else {
				$r = pg_query($db, "insert into ".self::TABLE
				." (in_use, confirmed, type, name, size, upload_timestamp)"
				." values("
				.self::encodeBoolean(TRUE)
				.", ". self::encodeBoolean(FALSE)
				.", ". pg_escape_literal($db, $type)
				.", ". pg_escape_literal($db, $name)
				.", $size"
				.", $upload_timestamp"
				.") returning id"
				);
				$id = (int) pg_fetch_assoc($r, 0)['id'];
			}
		}
		catch(ErrorException $e){
			pg_query($db, "rollback;");
			throw $e;
		}
		pg_query($db, "commit;");
		return $id;
	}
	
	
	/**
	 * @param string $data
	 * @param string $type MIME type. If NULL, it is guessed.
	 * @param string $name Name of the file, UTF-8. If NULL, uses
	 * the basename of $filepath.
	 * @return self
	 * @throws ErrorException
	 */
	public static function fromData($data, $type = null, $name = null)
	{
		# FIXME: guess type, if not specified
		if( $type === null )
			// FIXME: do some guess!
			$type = "application/octet-stream";
		$size = strlen($data);
		$upload_timestamp = time();
		$db = pg_connect("dbname=icodb");
		$id = self::add($db, $type, $name, $size, $upload_timestamp);
		
		$filepath = self::idToPath($id);
		$dir = dirname($filepath);
		try {
			if( !file_exists($dir) )
				mkdir($dir, 0760, true);
			$f = fopen($filepath, "w");
			fwrite($f, $data);
			fclose($f);
		}
		catch(ErrorException $e){
			self::markUnused($db, $id);
			pg_close($db);
			throw $e;
		}
		pg_close($db);

		$sf = new self();
		$sf->id = $id;
		$sf->confirmed = FALSE;
		$sf->type = $type;
		$sf->name = $name;
		$sf->size = $size;
		$sf->upload_timestamp = $upload_timestamp;
		return $sf;
	}
	
	/**
	 * Acquires the file just uploaded. The file is saved in non-confirmed state.
	 * @param string $fieldName Name of the PHP $_FILE[] entry.
	 * @return self Object describing the file just uploaded, or null if no file.
	 * @throws ErrorException FIXME
	 */
	public static function acquire($fieldName)
	{
		# FIXME: grab file from $_FILE[]
		$sf = new self();
		return $sf;
	}
	
	/**
	 * Loads the file from the file store given its ID number.
	 * @param int $id
	 * @return self Possibly NULL if this file does not exist in the store.
	 * @throws ErrorException
	 */
	public static function get($id)
	{
		$db = pg_connect("dbname=icodb");
		$r = pg_query($db, "select * from ".self::TABLE." where id=$id");
		$n = pg_num_rows($r);
		if( $n < 1 )
			return NULL;
		$record = pg_fetch_assoc($r, 0);
		pg_close($db);
		$sf = new self();
		$sf->id = (int) $record['id'];
		$sf->confirmed = self::decodeBoolean($record['confirmed']);
		$sf->type = $record['type'];
		$sf->name = $record['name'];
		$sf->size = (int) $record['size'];
		$sf->upload_timestamp = (int) $record['upload_timestamp'];
		return $sf;
	}
	
	/**
	 * Return the store's ID of this file.
	 * @return int
	 */
	public function getId(){ return $this->id; }
	
	/**
	 * Tells if this file is confirmed.
	 * @return boolean
	 */
	public function isConfirmed(){ return $this->confirmed; }
	
	/**
	 * Sets the confirmed flag.
	 * @param boolean $value
	 * @throws ErrorException
	 */
	public function setConfirmed($value = true)
	{
		if( $value === $this->confirmed )
			return;
		$db = pg_connect("dbname=icodb");
		$r = pg_query($db, "update ".self::TABLE
			." set confirmed=". self::encodeBoolean($value)
			." where id=" . $this->id);
		pg_close($db);
		$this->confirmed = $value;
	}
	
	/**
	 * Returns the MIME type of this file.
	 * @return string
	 */
	public function getType(){ return $this->type; }
	
	/**
	 * Returns the name of this file.
	 * @return string
	 */
	public function getName(){ return $this->name; }
	
	/**
	 * Returns the size of this file (bytes).
	 * @return int
	 */
	public function getSize(){ return $this->size; }
	
	/**
	 * Returns the content of this file.
	 * @return string
	 * @throws ErrorException
	 */
	public function getContent()
	{
		$filepath = self::idToPath($this->id);
		return file_get_contents($filepath);
	}
		
	/**
	 * Open the file for read and returns the resource handle.
	 * @return resource
	 * @throws ErrorException
	 */
	public function open()
	{
		return fopen(self::idToPath($this->id), "rb");
	}
	
	/**
	 * Returns the timestamp this file was uploaded in the store.
	 * @return int
	 */
	public function getUploadTimestamp(){ return $this->upload_timestamp; }
	
	
	/**
	 * Deletes not confirmed, stale files from the store. A not confirmed file is
	 * stale if its upload timestamp is older than $timeout seconds. This function
	 * should be called periodically to clean the file store.
	 * @param int $timeout Stale timeout, seconds.
	 * @return void
	 * @throws ErrorException
	 */
	public static function deleteStale($timeout = 86400)
	{
		$db = pg_connect("dbname=icodb");
		$r = pg_query($db, "select id from ".self::TABLE
			." where"
			." in_use=".self::encodeBoolean(TRUE)
			." and confirmed = " . self::encodeBoolean(FALSE)
			." and upload_timestamp < " . (time() - $timeout)
		);
		$n = pg_num_rows($r);
		for($i = 0; $i < $n; $i++){
			$id = (int) pg_fetch_assoc($r, $i)['id'];
			self::markUnused($db, $id);
			$filepath = self::idToPath($id);
			try {
				unlink($filepath);
			}
			catch(ErrorException $e){
				error_log("$e");
			}
		}
		pg_close($db);
	}
	
	
	/**
	 * 
	 * @param boolean[int] $ids
	 * @param string $dir
	 * @return int
	 * @throws ErrorException
	 */
	private static function reportRecurse($ids, $dir)
	{
		$err = 0;
		$d = opendir($dir);
		while (($file = readdir($d)) !== false) {
			if( $file === "." or $file === ".." )
				continue;
			$type = filetype("$dir/$file");
			if( $type === "file" ){
				$filepath = "$dir/$file";
				// extract ID: first remove file store path:
				$s = substr($filepath, strlen(self::$STORE) + 1);
				// ...then remove slashes:
				$s = (string) str_replace("/", "", $s);
				$id = (int) $s;
				$exp_filepath = self::idToPath($id);
				if( $exp_filepath !== $filepath ){
					error_log("$filepath: the path of this file does not look like a valid encoded ID\n");
					$err++;
				} else if( ! array_key_exists($id, $ids) ){
					error_log("$filepath: this file is not in the DB table `".self::TABLE."'\n");
					$err++;
				}
			} else if( $type === "dir" ){
				$err += self::reportRecurse($ids, "$dir/$file");
			} else {
				error_log("$dir/$file: unexpected file of type $type\n");
				$err++;
			}
        }
        closedir($d);
		return $err;
	}


	/**
	 * Performs a cross check between the file system part of the store and the
	 * DB part and reports issues to stderr.
	 * @return int
	 * @throws ErrorException
	 */
	public static function report()
	{
		// FIXME: should check file permissions and owner
		$db = pg_connect("dbname=icodb");
		
		// summary of used entries:
//		$r = pg_query($db, "select count(*) as n from ".self::TABLE
//			." where in_use=".self::encodeBoolean(TRUE));
//		$total_in_use = (int) pg_fetch_assoc($r, 0)['n'];
//		$r = pg_query($db, "select count(*) as n from ".self::TABLE
//			." where in_use=".self::encodeBoolean(FALSE));
//		$total_not_in_use = (int) pg_fetch_assoc($r, 0)['n'];
//		$total = $total_in_use + $total_not_in_use;
//		echo "$total entries, $total_in_use in use, $total_not_in_use spare.\n";
		
		$err = 0;
		
		$r = pg_query($db, "select id, confirmed, size from ".self::TABLE
			." where in_use=".self::encodeBoolean(TRUE));
		$n = pg_num_rows($r);
		$ids = /*. (boolean[int]) .*/ array();
		for($i = 0; $i < $n; $i++){
			$record = pg_fetch_assoc($r, $i);
			$id = (int) $record['id'];
			$ids[$id] = FALSE;
			$confirmed = self::decodeBoolean($record['confirmed']);
			$size = (int) $record['size'];
			$filepath = self::idToPath($id);
			if( ! file_exists($filepath) ){
				error_log("id=$id, path=$filepath: file does not exist\n");
				$err++;
			} else if( filesize($filepath) != $size ){
				error_log("id=$id, path=$filepath: expected size $size but found "
					. filesize($filepath) ."\n");
				$err++;
			}
		}
		$err += self::reportRecurse($ids, self::$STORE);
		return $err;
	}
	
}

