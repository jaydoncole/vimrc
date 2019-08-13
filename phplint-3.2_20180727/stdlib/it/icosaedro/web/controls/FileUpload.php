<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'pcre'; .*/

use RuntimeException;
use ErrorException;
use it\icosaedro\web\Log;
use it\icosaedro\web\Input;

/**
 * HTML file upload control. If the persistent flag is set in the constructor,
 * this control saves and resumes its state (including the file itself) between
 * pages postbacks; the page should take care to explicitly move() or delete()
 * the temporary file before leaving for another page.
 * This implementation is quite basic: the standard file upload control is
 * displayed and the latest uploaded file replaces the current one. Several
 * improvements are possible. For example, adding some feedback about the file
 * currently uploaded, and adding a button to delete that file if the user
 * changes its mind.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/06/06 09:58:46 $
 */
class FileUpload extends Control {
	
	/** @var string */
	private $file_tmp;
	
	/** @var string */
	private $file_name;
	
	/** @var string */
	private $file_type;
	
	/** @var int */
	private $file_size = 0;
	
	/** @var boolean */
	private $persistent = FALSE;
	
	/**
	 * Status code after the last file upload retrieval. The value is one of the
	 * UPLOAD_ERR_* PHP constants. UPLOAD_ERR_OK means upload succeeded and a new
	 * file is available. UPLOAD_ERR_NO_FILE means the user left the control empty.
	 * Only if a new file has been successfully uploaded the current file this
	 * object hold is replaced with the new one; a failed retrieval does not
	 * replace the current file.
	 * @var int
	 */
	public $status_code = UPLOAD_ERR_NO_FILE;
	
	/**
	 * Status description after the last file upload retrieval.
	 * @var string
	 */
	public $status_description = "no file";
	
	/**
	 * Tells if a file is currently available.
	 * @return boolean
	 */
	function isAvailable()
	{
		return $this->file_tmp !== NULL;
	}
	
	/**
	 * Returns the name of the file as retrieved from the remote host.
	 * @return string Name of the file, UTF-8 encoded.
	 */
	function getFilename()
	{
		return $this->file_name;
	}
	
	/**
	 * Returns the MIME type of the file as retrieved from the remote host.
	 * @return string MIME type of the file, sanitized to printable ASCII
	 * characters only without spaces.
	 */
	function getType()
	{
		return $this->file_type;
	}
	
	/**
	 * Returns the size of the file.
	 * @return int Size of the file (B).
	 */
	function getSize()
	{
		return $this->file_size;
	}
	
	/**
	 * Returns the temporary file name. This file should not be renamed or
	 * deleted by the client application to preserve the consistency of this
	 * object; use the specific methods to delete, move or copy the file.
	 * @return string
	 */
	function getTemporaryFilename()
	{
		return $this->file_tmp;
	}
	
	/**
	 * Deletes the current file this object holds and resets its state.
	 */
	function delete()
	{
		if( file_exists($this->file_tmp) ){
			try {
				unlink($this->file_tmp);
			}
			catch(ErrorException $e){
				Log::error("$e");
			}
		}
		$this->file_name = NULL;
		$this->file_size = 0;
		$this->file_tmp = NULL;
		$this->file_type = NULL;
		$this->status_code = UPLOAD_ERR_NO_FILE;
		$this->status_description = "no file";
	}
	
	function save()
	{
		if( ! $this->persistent )
			$this->delete();
		$this->_form->setData($this->_name .".file_name", $this->file_name);
		$this->_form->setData($this->_name .".file_size", $this->file_size);
		$this->_form->setData($this->_name .".file_tmp", $this->file_tmp);
		$this->_form->setData($this->_name .".file_type", $this->file_type);
		$this->_form->setData($this->_name .".status_code", $this->status_code);
		$this->_form->setData($this->_name .".status_description", $this->status_description);
	}
	
	function resume()
	{
		// Note: cast("string", ...) passes NULL verbatim; (string) does not.
		$this->file_name = cast("string", $this->_form->getData($this->_name .".file_name"));
		$this->file_size = (int) $this->_form->getData($this->_name .".file_size");
		$this->file_tmp = cast("string", $this->_form->getData($this->_name .".file_tmp"));
		$this->file_type = cast("string", $this->_form->getData($this->_name .".file_type"));
		$this->status_code = (int) $this->_form->getData($this->_name .".status_code");
		$this->status_description = (string) $this->_form->getData($this->_name .".status_description");
		if( $this->file_tmp !== NULL && !file_exists($this->file_tmp) )
			// Temp. file is gone!
			$this->delete();
	}
	
	/**
	 * Displays the standard file upload control. Latest upload replaces the
	 * current one. You may want to add some feedback about the current file
	 * and a delete button to delete the current file.
	 * @return void
	 */
	function render()
	{
		echo "<input type=file name='", $this->_name, "' ",
				$this->_add_attributes, ">";
	}
	
	/**
	 * Tries to retrieve the file from the request. New uploaded file replaces
	 * the current one. If no file or any error happened, keeps the current file.
	 * Always sets the status properties of this object with the outcome of this
	 * last retrieval.
	 * @return void
	 */
	function retrieve()
	{
		$this->status_code = UPLOAD_ERR_NO_FILE;
		$this->status_description = "no file";
		
		if( ! isset($_FILES[$this->_name]) )
			return;
			
		$status_code = cast("int", $_FILES[$this->_name]['error']);
		$name     = cast("string", $_FILES[$this->_name]['name']);
		$type     = cast("string", $_FILES[$this->_name]['type']);
		$size     = cast("int",    $_FILES[$this->_name]['size']);
		$tmp_name = cast("string", $_FILES[$this->_name]['tmp_name']);
		
		// Check status:
		$status_to_description = array(
			UPLOAD_ERR_OK => "file upload succeeded",
			UPLOAD_ERR_INI_SIZE => "file too big ($size B), check upload_max_filesize in php.ini",
			UPLOAD_ERR_FORM_SIZE => "file too big ($size B), check hidden field MAX_FILE_SIZE",
			UPLOAD_ERR_PARTIAL => "partial upload",
			UPLOAD_ERR_NO_FILE => "no file uploaded or name missing",
			UPLOAD_ERR_NO_TMP_DIR => "missing temporary destination directory or other server configuration problem",
			UPLOAD_ERR_CANT_WRITE => "failed writing temporary file",
			UPLOAD_ERR_EXTENSION => "upload interrupted by extension"
		);
		$status_description = "";
		if( isset($status_to_description[$status_code]) )
			$status_description = $status_to_description[$status_code];
		else
			$status_description = "failed upload, code $status_code";
		
		$this->status_code = $status_code;
		$this->status_description = $status_description;
		
		if( $status_code == UPLOAD_ERR_OK ){
			// We have a file.
		} else if( $status_code == UPLOAD_ERR_NO_FILE ){
			return;  // No upload. Keep current file.
		} else {
			Log::error("file upload: $status_description");
			return;
		}
		
		// Check name:
		$name = Input::sanitizeLine($name);
		if( strlen($name) == 0 ){
			$this->status_code = UPLOAD_ERR_NO_FILE;
			$this->status_description = $status_to_description[$this->status_code];
			return;
		}
		
		// Check type basic syntax (see RFC 6838 par. 4.2):
		$type = strtolower($type);
		$restricted_name_chars = "[-+!#\$&^_.a-z0-9]";
		if( preg_match("/^$restricted_name_chars+\\/$restricted_name_chars+\$/sD", $type) != 1 ){
			// Invalid MIME type.
			$this->status_code = UPLOAD_ERR_NO_FILE;
			$this->status_description = $status_to_description[$this->status_code];
			return;
		}
		
		// Save tmp file:
		$my_tmp_name = $tmp_name . "x";
		try {
			move_uploaded_file($tmp_name, $my_tmp_name);
		}
		catch(ErrorException $e){
			throw new RuntimeException("failed renaming uploaded file", $e->getCode(), $e);
		}
		
		// Replace in this object:
		$this->delete();
		$this->file_name = $name;
		$this->file_type = $type;
		$this->file_size = $size;
		$this->file_tmp  = $my_tmp_name;
	}
	
	/**
	 * Copies the current file.
	 * @param string $destination Destination file name.
	 * @throws RuntimeException No file available.
	 * @throws ErrorException Failed copying the file.
	 */
	function copyTo($destination)
	{
		if( ! $this->isAvailable() )
			throw new RuntimeException("no file available");
		copy($this->file_tmp, $destination);
	}
	
	/**
	 * Moves the current file and deletes the state of this object.
	 * @param string $destination
	 * @throws RuntimeException No file available.
	 * @throws ErrorException Failed moving the file.
	 */
	function moveTo($destination)
	{
		if( ! $this->isAvailable() )
			throw new RuntimeException("no file available");
		move_uploaded_file($this->file_tmp, $destination);
		$this->delete();
	}
	
	/**
	 * Create a new, empty file upload control.
	 * @param ContainerInterface $form  Container form or panel.
	 * @param string $name Name attribute.
	 * @param boolean $persistent Sets the persistency of the temporary file.
	 * See the description of this class for more.
	 */
	function __construct($form, $name, $persistent)
	{
		parent::__construct($form, $name);
		$this->persistent = $persistent;
	}
	
	function __destruct()
	{
		if( ! $this->persistent )
			$this->delete();
	}
	
	/**
	 * Invoked after the unserialize to check if the temporary file is still there.
	 * If the temporary file is gone, resets the internal state.
	 * Client code should not invoke directly this function.
	 */
	function __wakeup()
	{
		if( $this->file_tmp !== NULL && !file_exists($this->file_tmp) )
			$this->delete();
	}


	/**
	 * @param string $s
	 * @return int
	 */
	private static function parse2PowerFactor($s)
	{
		$v = (int) $s;
		$last = strtolower($s[strlen($s)-1]);
		switch($last) {
			case 'g': $v *= 1024;  /*. missing_break; .*/
			case 'm': $v *= 1024;  /*. missing_break; .*/
			case 'k': $v *= 1024;  /*. missing_break; .*/
			/*. missing_default: .*/
		}
		if( is_float($v) ) // cope with overflow on 32-bits systems
			$v = PHP_INT_MAX;
		return $v;
	}
	
	
	/**
	 * Returns the maximum allowed time to upload the request to the server
	 * as configured in the current php.ini. Then, for example, to upload a single
	 * file long as the maximum allowed length, the minimum required upload
	 * bandwidth is maxFileSize() / maxUploadTime() B/s, value that must multiplied
	 * by the total number of file upload controls the form contains.
	 * 
	 * <p>Note that the web server may also set a lower timeout limit (see the
	 * Timeout directive under Apache). If the request exceeds that limit:
	 * - The browser receives the 408 "Request Timeout" HTTP status code.
	 * - Apache error log reports "Request body read timeout".
	 * - The PHP script is not executed at all.
	 * There is no way to detect this event from the PHP code.
	 * 
	 * @return int Maximum allowed time to upload the request as per the current
	 * php.ini (s).
	 */
	public static function maxUploadTime()
	{
		$m = (int) trim( ini_get("max_input_time") );
		return ( $m >= 0 )? $m : PHP_INT_MAX;
	}
	
	
	/**
	 * Returns the maximum allowed length for each upload file as configured in
	 * the current php.ini. If the user tries to upload a file larger than that,
	 * a UPLOAD_ERR_INI_SIZE is generated and this control returns no file.
	 * 
	 * <p>Applications may also set a specific maximum file length for each file
	 * by adding an hidden field like <tt>"&lt;INPUT type=hidden
	 * name=MAX_FILE_SIZE value=512000&gt;"</tt> before any file upload control.
	 * Uploaded files larger than that are then rejected by PHP with error
	 * UPLOAD_ERR_FORM_SIZE.
	 * 
	 * <p>Note that the web server may also set a lower limit to the whole size of
	 * the request (see the LimitRequestBody directive under Apache, zero meaning
	 * infinite). If the request exceeds that limit:
	 * - The browser receives the 413 "Request Entity Too Large" HTTP status code.
	 * - Apache error log reports "Request content-length of 85592334 is larger
	 *   than the configured limit of 1000000" (numbers varies).
	 * - The PHP script is not executed at all.
	 * There is no way to detect this event from PHP code.
	 * 
	 * @return int Maximum upload file size as per the current php.ini (B).
	 * Zero means file upload is not allowed.
	 */
	public static function maxUploadFileSize()
	{
		if( ini_get("file_uploads") !== "1" )
			return 0;
		$max_post = self::parse2PowerFactor( trim( ini_get("post_max_size") ) );
		$max_per_file = self::parse2PowerFactor( trim( ini_get("upload_max_filesize") ) );
		return (int) min($max_post, $max_per_file);
	}
	
	
	/**
	 * Returns the maximum number of uploaded files allowed per request.
	 * That is, the maximum number of file upload controls per single form.
	 * 
	 * @return int Maximum number of uploaded files allowed per request.
	 * Zero means file upload is not allowed.
	 */
	public static function maxNumberOfUploadFiles()
	{
		if( ini_get("file_uploads") !== "1" )
			return 0;
		$m = (int) trim( ini_get("max_file_uploads") );
		return ( $m >= 0 )? $m : 0;
	}
	
}
