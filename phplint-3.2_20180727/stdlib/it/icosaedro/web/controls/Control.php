<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'pcre'; .*/

use InvalidArgumentException;

/**
 * Base class for any HTML control element. A control has a name to be used for
 * the corresponding "name" attribute of its HTML element, has a render method
 * that sends this control to the standard output while composing the web page,
 * and has a retrieve method that parses the request to recover the current
 * value from the remote web page. Controls may also save and resume their state
 * between invocations of the page by implementing the respective methods.
 * 
 * <p>Currently available controls and their mapping into data base field types
 * are listed below:
 * 
 * <pre>
 * 
        PHP type         SQL type             See note
        ---------------  -------------------  --------
        boolean		     BOOLEAN
        int              INT
        float            DOUBLE               1
        string (UTF-8)   CHAR, VARCHAR, TEXT
        Date             DATE                 2
        DateTimeTZ       DATETIME             2, 3, 4
        BigInt           NUMERIC(P)           2
        BigFloat         NUMERIC(P,S)         2
        string (binary)  TEXT                 5

    1. It's named DOUBLE PRECISION under PostgreSQL.
    2. TEXT under SQLite; Date as YYYY-MM-DD, DateTime as YYYY-MM-DDThh:mm and
       DateTimeTZ as YYYY-MM-DDThh:mm:ss.sss UTC.
    3. It's named TIMESTAMP under PostgreSQL.
    4. Date-time is always converted to UTC time zone while translating to SQL
       and then retrieved with UTC time zone; the application may then convert
       back to the desired TZ with the toTZ() method: $dtz-&gt;toTZ($my_tz_minutes).
       Note that the new class DateTimeTZ (see below) replaces built-in \DateTime.
    5. Binary data are saved in Base64 encoding.
 * </pre>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/07/27 10:28:23 $
 */
abstract class Control {
	
	/** @var ContainerInterface */
	protected $_form;
	
	/**
	 * The "name" attribute of this control. Used to build the "name" attribute
	 * fo the corresponding HTML element. Some controls (Form) do not really
	 * use their name; others (Panel) use their name to build univocal names
	 * for their nested controls.
	 * @var string
	 */
	protected $_name;
	
	/**
	 * Further attributes of the control.
	 * @var string
	 */
	protected $_add_attributes;
	
	/**
	 * Returns the "name" attribute of this control. For nested controls the
	 * returned value is built upon the names of their container (Panel).
	 * @return string
	 */
	function getName()
	{
		return $this->_name;
	}
	
	/**
	 * Attributes to add to the rendered HTML element.
	 * @param string $value Verbatim string containing the attributes to add,
	 * for example <tt>"id='mycontrol' style='display: none;'"</tt>.
	 */
	function addAttributes($value)
	{
		$this->_add_attributes = $value;
	}

	/**
	 * Event invoked to store the state of the control by using whatever mechanism
	 * the specific implementation of the form currently provides.
	 */
	abstract function save();

	/**
	 * Event invoked to resume the state of the control by using whatever mechanism
	 * the specific implementation of the form currently provides.
	 */
	abstract function resume();
	
	/**
	 * Sends the HTML code of this control to the standard output.
	 * @return void
	 */
	abstract function render();
	
	/**
	 * Retrieves the value set for this control from the HTTP request.
	 * The implementation must be tolerant regarding the actual values available
	 * in the request avoiding to raise any error or exception.
	 * @return void
	 */
	abstract function retrieve();
	
	/*.
		# Get rid of the boring error messages PHPLint gives about the "missing"
		# method ContainerInterface::addControl while parsing that class:
		forward void function __construct(ContainerInterface $form,
			string $name) throws InvalidArgumentException;
		pragma 'suspend';
	.*/
	
	/**
	 * Builds a new control and registers to receive events.
	 * @param ContainerInterface $form Container form or panel. Here this new
	 * control registers itself to receive events later, and here it saves and
	 * resumes its state. If NULL, this is the form itself!
	 * @param string $name The "name" attribute of this control. Must be not empty;
	 * any PHP ID allowed, including fully qualified name with namespace, plus
	 * dot.
	 * @throws InvalidArgumentException Invalid name. Duplicated name.
	 */
	function __construct($form, $name)
	{
		if( preg_match("/^[_.0-9a-zA-Z\\\\x81-\\xff]+$/sD", $name) != 1 )
			throw new InvalidArgumentException("invalid characters in name: $name");
		$this->_form = $form;
		$this->_name = $name;
		if( $form !== NULL )
			// Register myself into Form.
			$form->addControl($this);
	}
	
}
