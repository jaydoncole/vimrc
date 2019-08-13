<?php

namespace it\icosaedro\web;

require_once __DIR__ . "/../../../all.php";

/*. require_module 'array'; .*/

use Exception;
use RuntimeException;
use ErrorException;
use CastException;
use InvalidArgumentException;
use it\icosaedro\utils\SecurityKeys;
use it\icosaedro\utils\SecurityException;
use it\icosaedro\web\controls\Control;
use it\icosaedro\web\controls\ContainerInterface;

/**
 * An object of this class represents an handler of a user's click on a button
 * or on a link. That handler must be an instance method of the form or an
 * instance method of a nested panel. Since object references cannot be serialized,
 * here we save the name of the nested panel.
 * @access private
 */
class CallForward {
	
	/**
	 * Name of the panel this method belongs to. If NULL, it is the Form itself.
	 * @var string
	 */
	public $panel;
	
	/**
	 * Name of the handler method.
	 * @var string
	 */
	public $method;
	
	/**
	 * Arguments of the handler method.
	 * @var mixed[int]
	 */
	public $arguments;
	
	/**
	 * Creates a new call-forward object.
	 * @param string $handler Name of the handler method. If method of the Form, it
	 * is the name of the instance method. If method of one of its nested Panel(s)it
	 * must be the name of the panel and the name of the method separated by
	 * a single white space.
	 * @param mixed[int] $arguments
	 */
	function __construct($handler, $arguments)
	{
		$a = explode(" ", $handler);
		if( count($a) == 1 ){
			$this->panel = NULL;
			$this->method = $a[0];
		} else if( count($a) == 2 ){
			$this->panel = $a[0];
			$this->method = $a[1];
		} else {
			throw new RuntimeException("too many white spaces in call-forward: $handler");
		}
		$this->arguments = $arguments;
	}
}


/**
 * Base class to implement a sticky form with events dispatching. This class
 * helps implementing the quite traditional "one PHP file per web page" style
 * of developing web sites by taking care to handle secure data storage in the
 * generated page, user's events dispatching to handler methods, and in generally
 * giving some defined structure to the code and to the usual input, validation
 * and feedback interaction cycle. A tutorial is also available at
 * {@link http://www.icosaedro.it/phplint/web}.
 * 
 * <h2>Basic usage</h2>
 * To use this class you must extends it, implement the render method that
 * displays the HTML page, create an instance of your class, and invoke the
 * method that processes the request:
 * 
 * <blockquote><pre>
 * &lt;php
 * require_once __DIR__ . "/../stdlib/all.php";
 * use it\icosaedro\web\Form;
 * 
 * class MyForm extends Form {
 * 
 *     function render()
 *     {
 *         header("Content-Type: text/html; charset=UTF-8");
 *         echo "&lt;html&gt;&lt;body&gt;";
 *         $this-&gt;open(); // opens the form element
 *         ...  // code of the page here
 *         $this-&gt;close(); // closes the form element
 *         echo "&lt;/body&gt;&lt;/html&gt;";
 *     }
 * 
 * }
 * 
 * (new MyForm(FALSE))-&gt;processRequest();
 * </pre></blockquote>
 * 
 * It is really important to note that:
 * <ul>
 * <li>Invoking the open() and close() methods is mandatory to put a form element
 * in the page, as this class relies on it to save its state between requests
 * to the same page. The action of the form, in fact, is set to "myself".</li>
 * <li>The code that shows the content of the page can now be added between the
 * open and close methods.</li>
 * </ul>
 * 
 * 
 * <h2>Events dispatching on postback</h2>
 * The anchor() and button() methods create controls that capture user's clicks
 * on the page; these events are automatically dispatched to your defined handler
 * methods.
 * The following example displays a form with three buttons that allows the user
 * to choose the background color of the page. By clicking any of these buttons,
 * the colorButton() handler is invoked with a specific color as argument:
 * 
 * <blockquote><pre>
 * &lt;php
 * require_once __DIR__ . "/../stdlib/all.php";
 * use it\icosaedro\web\Form;
 * 
 * class MyForm extends Form {
 * 
 *     <b>private $color = "#fff";</b>
 * 
 *     function render()
 *     {
 *         header("Content-Type: text/html; charset=UTF-8");
 *         echo "&lt;html&gt;&lt;body bgcolor='" . $this-&gt;color . "'&gt;";
 *         $this-&gt;open();
 *         echo "Select the background color of this page: ";
 *         <b>$this-&gt;button("White", "colorButton", "#fff");
 *         $this-&gt;button("Red",   "colorButton", "#f00");
 *         $this-&gt;button("Green", "colorButton", "#0f0");
 *         $this-&gt;button("Blue",  "colorButton", "#00f");</b>
 *         $this-&gt;close();
 *         echo "&lt;/body&gt;&lt;/html&gt;";
 *     }
 * 
 * 
 *     <b>/&#42;* @param string $color &#42;/
 *     function colorButton($color)
 *     {
 *          $this-&gt;color = $color;
 *          $this-&gt;render();
 *     }</b>
 * 
 * }
 * 
 * (new MyForm(FALSE))-&gt;processRequest();
 * </pre></blockquote>
 * 
 * In our example there is only one handler method and each button invokes that
 * handler with a different value for the argument, that is the chosen background
 * color, but in general a form may have any number of buttons and anchors and
 * any number of corresponding handler methods. Each handler method typically
 * performs the required processing and then either renders the form again by
 * invoking render() or it redirects the user to another page.
 * 
 * <h2>Retrieving form data</h2>
 * If the form contains input controls, these can be acquired by overriding the
 * retrieve method:
 *
 * <blockquote><pre>
 *     function retrieve()
 *     {
 *         parent::retrieve();
 *         // ... retrieve custom data from $_POST here ...
 *     }
 * </pre></blockquote>
 * 
 * The retrieve() method is invoked automatically on every postback detected
 * and before invoking the handler method. Several controls classes are also
 * available to make easier handling users' input: 1. Define a private property
 * in the class to store the control. 2. Crate a new instance of the control
 * in the constructor. 3. Displays the control in the page by invoking its
 * render() method. 4. Let the Form class to retrieve the current value of the
 * control from the postback; no need to override the retrieve() method anymore.
 * 
 * 
 * <h2>Storing data in the page</h2>
 * The setData() and getData() methods allow to store data between postbacks.
 * These data are serialized, compressed, signed and possibly encrypted, and
 * finally saved inside an hidden field, from which them can be recovered later
 * on postback. Note that the serialization process does not work with open
 * resources (these are recovered as int zero!), so be careful to to put in the
 * state of the page only those values that can be safely serialized.
 * 
 * 
 * <h2>Form initialization</h2>
 * In most cases a specific constructor should be defined; remember to invoke the
 * parent constructor too (although PHPLint already takes care to warn you about
 * that). This custom constructor might set the initial default state of the form
 * an create all the controls.
 * 
 * <p>Another method you may want to override is the entering() method, which is
 * called the first time the page is requested. Here, for example, you may retrieve
 * from the data base the specific record to modify. Do not forget to invoke
 * the render method at the end, or the resulting page will be empty.
 * 
 * <h2>Input validation cycle</h2>
 * Often a form has one "Save" button to save the changes of the form, typically
 * on a data base. But before doing that, entered data should be validated.
 * If this validation succeeds, data can be saved and then typically the browser
 * is redirected to another page.
 * 
 * <p>If the validation of the data entered by user fails, an error message should
 * be displayed and the form should be displayed again. A simple way to do that
 * is by adding an optional argument to the render method where to pass the error
 * message to display on the top of the page:
 * 
 * <blockquote><pre>
 *     function render($error_message = NULL){ ... }
 * 
 *     function saveButton()
 *     {
 *         if( retrieved data are ok ){
 *             // Save that entered data here.
 *             header("Location: <i>the next page</i>");
 *         } else {
 *             $this-&gt;render("Invalid data so and so...");
 *         }
 *     }
 * </pre></blockquote>
 * 
 * <h2>Expire timeout</h2>
 * The state of the form is signed and possibly encrypted along with a expire
 * timeout defined in the {@link it\icosaedro\utils\SecurityKeys} class. Once
 * that timeout expires, postbacks are rejected with SecurityException.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/05/15 04:22:20 $
 */
abstract class Form extends Control implements ContainerInterface {
	
	/** @var boolean */
	private $_encrypt = FALSE;
	
	/**
	 * Associative array of data to be saved to and retrieved from the page.
	 * Data are serialized, signed and possibly encrypted.
	 * @var mixed[string]
	 */
	private $_data;
	
	/**
	 * Nested controls.
	 * @var Control[string]
	 */
	private $_controls;
	
	/** @var CallForward[int] */
	private $_calls_forward;
	
	/**
	 * How many times the open() method has been invoked.
	 * @var int
	 */
	private $open_count = 0;
	
	/**
	 * How many times the close() method has been invoked.
	 * @var int
	 */
	private $close_count = 0;
	
	/**
	 * Creates a new, empty form. The state of the form (including the defined
	 * buttons and anchors and private data saved with setData()) will be saved
	 * in the form itself by the close() method. Data are signed to protect the
	 * application from tampering; data may also be encrypted to protect
	 * secret data. Moreover, a timestamp is added and data expire within a
	 * period of about 4 hours dependend on the
	 * {@link it\icosaedro\utils\SecurityKeys::EXPIRE} constant and are rejected
	 * with SecurityException beyond that range of time.
	 * @param boolean $encrypt Set to true to also encrypt data.
	 */
	function __construct($encrypt)
	{
		parent::__construct(NULL, "Form");
		$this->_encrypt = $encrypt;
		$this->_data = array();
		$this->_controls = array();
		$this->_calls_forward = array();
	}
	
	/**
	 * Stores a name/value pair to be saved along with the form state.
	 * @param string $name Name of the data to store.
	 * @param mixed $value Value to store. Only data that can be serialized
	 * should be put here; resources are not serializable, for example, and are
	 * retrieved as integer zero.
	 */
	function setData($name, $value)
	{
		$this->_data["*.$name"] = $value;
	}
	
	/**
	 * Retrieves the value of a name/value pair saved along the state of this form.
	 * @param string $name Name of the value.
	 * @param mixed $default_value Default value to return if missing.
	 * @return mixed
	 * @throws RuntimeException Missing data and no default value specified.
	 */
	function getData($name, $default_value = NULL)
	{
		$key = "*.$name";
		if(array_key_exists($key, $this->_data))
			return $this->_data[$key];
		if(func_num_args() == 1)
			throw new RuntimeException("no this data: $name");
		return $default_value;
	}
	
	/**
	 * Implements the container interface that allows nested controls to register
	 * themselves under this container. Client code does not have normally need
	 * to invoke this method.
	 * @param Control $control Nested control to register.
	 * @throws InvalidArgumentException Duplicated control name.
	 */
	function addControl($control)
	{
		$name = $control->getName();
		if( isset($this->_controls[$name]) )
			throw new InvalidArgumentException("duplicated control: $name");
		$this->_controls[$name] = $control;
	}
	
	/**
	 * Invoked by this class to save the state of the form before leaving the
	 * page. Client may override this method to save its private data using
	 * setData() for being preserved between requests, but remember to invoke
	 * parent::save() too. See also the resume() method.
	 */
	function save()
	{
		foreach($this->_controls as $c)
			$c->save();
	}
	
	/**
	 * Invoked by this class to resume the state of the form on a request.
	 * Client may override this method to resume its private data using getData(),
	 * but remember to invoke parent::resume() too. See also the save() method.
	 */
	function resume()
	{
		foreach($this->_controls as $c)
			$c->resume();
	}
	
	/**
	 * Invoked by this class to retrieve form data on a request. Client may
	 * override this method to customize data retrieval, but remember to invoke
	 * parent::retrieve() too.
	 */
	function retrieve()
	{
		foreach($this->_controls as $control)
			$control->retrieve();
	}
	
	/**
	 * Captures the "implicit submission mechanism" event, that is ENTER on a
	 * entry box. This event is captured by putting an invisibile button just
	 * next the form element, so it is the first button of the form; this button
	 * is marked with id="phplint_defaultButton".
	 * This implementation does nothing and simply re-display the form.
	 * If your form has a well defined default submit button, you may want to
	 * re-implement this method accordingly.
	 */
	function defaultButton()
	{
		$this->render();
	}
	
	/**
	 * Last event (button or anchor clicked) was delivered to a Panel.
	 * The implementation may reimplement this method to get notified about
	 * possible changes in nested panels. This implementation invokes the
	 * renderer method.
	 * @param Control $control Panel that triggered this event.
	 */
	function eventOnControl($control)
	{
		$this->render();
	}
	
	/**
	 * Sends to standard output a button. The name of the handling method and its
	 * arguments can be specified; arguments are serialized and added to the
	 * state of the form.
	 * @param string $text Caption of the button.
	 * @param string $handler Name of the method of this object to invoke if that
	 * button is clicked; arguments may follow.
	 * @throws InvalidArgumentException The handler method does not exist.
	 */
	function button($text, $handler /*. , args .*/)
	{
		$a = func_get_args();
		array_shift($a); // skip $text
		array_shift($a); // skip $handler
		$i = count($this->_calls_forward);
		$cf = new CallForward($handler, $a);
		
		// Check if $func refers to an existing instance (Form or Panel) and
		// the handler method does exit:
		if( $cf->panel === NULL )
			/*. Control .*/ $control = $this;
		else if( ! isset($this->_controls[$cf->panel]) )
			throw new InvalidArgumentException("this panel does not exist: " . $cf->panel);
		else
			$control = $this->_controls[$cf->panel];
		if( ! method_exists($control, $cf->method) )
			throw new InvalidArgumentException("method " . get_class($control) ."::". $cf->method . " does not exist");
		
		$this->_calls_forward[$i] = $cf;
		echo "<input type=submit name=Form_button_$i value='",
			htmlspecialchars($text), "' ", $this->_add_attributes, ">";
		$this->_add_attributes = NULL;
	}
	
	/**
	 * Sends to standard output an anchor. The name of the handling method and its
	 * arguments can be specified; arguments are serialized and added to the
	 * state of the form.
	 * @param string $text_html Link caption.
	 * @param string $handler Name of the method of this object to invoke if that
	 * button is clicked; arguments may follow.
	 * @throws InvalidArgumentException The handler method does not exist.
	 */
	function anchor($text_html, $handler /*. , args .*/)
	{
		// Create and anchors that clicks on an invisible button that performs
		// the POST.
		$i = count($this->_calls_forward);
		$button_id = "Form_anchor_click_$i";
		echo "<a href='#' ", $this->_add_attributes,
			" onclick=\"document.getElementById('$button_id').click();\">$text_html</a>";
		$this->_add_attributes = " id=$button_id style='display: none;'";
		$a = func_get_args();
		$a[0] = "";
		try {
			call_user_func_array(self::class . "::button", $a);
		} catch (Exception $e) {
			throw new RuntimeException($e->getMessage(), 1, $e);
		}
	}
	
	/**
	 * Sends to standard output the opening tag of the form element.
	 * No <tt>action</tt> attribute is set, which normally is interpreted by
	 * browsers as self-posting page, exactly what we want. Each page should
	 * contain exactly one invokation of this method and another for the
	 * companion close() method.
	 */
	function open()
	{
		$this->open_count++;
		if( $this->open_count > 1 )
			throw new RuntimeException("multiple open() in the same form");
		echo "<form enctype='multipart/form-data' method=post ",
			$this->_add_attributes, ">";
		$this->_add_attributes = NULL;
		
		// Capture the "implicit submission mechanism":
		$this->addAttributes("id=phplint_defaultButton style='display: none'");
		$this->button("", "defaultButton");
	}
	
	/**
	 * Sends to standard output the closing tag of the form element. The state
	 * of this form is also serialized and added as hidden field; data are
	 * serialized, compressed, signed and possibly encrypted.
	 */
	function close()
	{
		$this->close_count++;
		if( $this->close_count != $this->close_count )
			throw new RuntimeException("each render method must contain 1 open() and 1 close()");
		$this->save();
		$this->_data["Form.class"] = get_class($this);
		$this->_data["Form.calls_forward"] = $this->_calls_forward;
		$data = serialize($this->_data);
		$value = base64_encode( SecurityKeys::encode($data, $this->_encrypt) );
		echo "<input type=hidden name=Form_state value='$value'>";
		echo "</form>";
	}
	
	/**
	 * @throws SecurityException The state of the form cannot be recovered because
	 * either the signature timestamp expired (stale form) or a tampering attempt
	 * was detected.
	 * @throws RuntimeException The state of the form was not saved in the expected
	 * format or it cannot be unserialized anymore due to some change to the source
	 * in the meanwhile.
	 */
	private function retrieveNotOverridable()
	{
		if( ! isset($_POST["Form_state"]) )
			throw new RuntimeException("not a postback");
		$value = base64_decode( (string) $_POST["Form_state"] );
		$data = SecurityKeys::decode($value, $this->_encrypt);
		try {
			$d = cast("mixed[string]", unserialize($data));
		}
		catch(ErrorException $e){
			throw new RuntimeException("failed decoding", 1, $e);
		}
		catch(CastException $e){
			throw new RuntimeException("failed decoding", 1, $e);
		}
		if( $d["Form.class"] !== get_class($this) )
			throw new SecurityException("not a state of this form");
		unset($d["Form.class"]);
		$this->_calls_forward = cast(CallForward::class . "[int]", $d['Form.calls_forward']);
		unset($d['Form.calls_forward']);
		$this->_data = $d;
	}
	
	
	/**
	 * Invokes the handler routine bound to the clicked control as specified in
	 * the postback. Handler are put in the form by a button or anchor
	 * generated by this class.
	 * @throws RuntimeException Cannot recognize the request; no matching handler
	 * found.
	 */
	private function dispatch()
	{
		// Search button named "Form_button_N" where N is the number of the
		// call-forward in the calls forward array:
		$i = -1;
		foreach($_POST as $k => $v){
			if( strlen($k) > 12 && substr_compare($k, "Form_button_", 0, 12) == 0 ){
				$i = (int) substr($k, 12);
				break;
			}
		}
		if( ! isset($this->_calls_forward[$i]) )
			throw new RuntimeException("missing forward call no. $i");
		$cf = $this->_calls_forward[$i];
		
		// If no panel specified, the instance is this Form, otherwise search
		// the instance of the nested panel to invoke:
		if( $cf->panel === NULL )
			/*. Control .*/ $control = $this;
		else
			$control = $this->_controls[$cf->panel];
		$cb = /*. (mixed[int]) .*/ array();
		$cb[0] = $control;
		$cb[1] = $cf->method;
		try {
			call_user_func_array($cb, $cf->arguments);
		} catch (Exception $e) {
			throw new RuntimeException($e->getMessage(), 1, $e);
		}
		if( $control !== $this )
			$this->eventOnControl($control);
	}
	
	
	/**
	 * Handler routine for the first request of this form from some external page.
	 * For example, either the remote client requested this specific URL, or it
	 * whose redirected here by another page. Data can be exchanged among pages
	 * using several ways, for example: URL parameters, form fields, user session,
	 * data base, temporary files. This default implementation simply invokes
	 * the render routine, but you may re-implement if required to initialize
	 * the state of the form.
	 */
	function entering()
	{
		$this->render();
		if( $this->open_count != $this->close_count )
			throw new RuntimeException("unbalanced open()/close()");
	}
	
	
	/**
	 * Handles the request. If it is a post request from this same form, the
	 * retrieve() method is invoked, otherwise the entering() method is invoked.
	 * @throws SecurityException The state of the form cannot be recovered because
	 * either the signature timestamp expired (stale form) or a tampering attempt
	 * was detected.
	 */
	function processRequest()
	{
		if( isset($_POST["Form_state"]) ){
			$this->retrieveNotOverridable();
			$this->resume();
			$this->retrieve();
			$this->dispatch();
		} else {
			$this->entering();
		}
	}
	
}
