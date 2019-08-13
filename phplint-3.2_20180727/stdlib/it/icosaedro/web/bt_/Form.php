<?php

namespace it\icosaedro\web\bt_;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'array'; 
    require_module 'standard_reflection'; .*/

use Exception;
use RuntimeException;
use InvalidArgumentException;
use ReflectionClass;
use it\icosaedro\web\controls\Control;
use it\icosaedro\web\controls\ContainerInterface;

/**
 * Base class to implement sticky form based on the bt_ session management, also
 * referred as "bt form" for short. This implementation has the same interface
 * of the "sticky form" class it\icosaedro\web\Form, but rather than saving the
 * state of the form in the generated page, it uses conveniently the stack in the
 * user's session. Moreover, pages should be stored in the sources directory tree
 * rather than being files on the document root of the web server, as bt_ uses
 * class autoloading to retrieve classes. All the controls available under the
 * it\icosaedro\web\controls namespace can be used as well. A tutorial is also
 * available at {@link http://www.icosaedro.it/phplint/web}.
 * 
 * <h2>Basic usage</h2>
 * You "web pages" should be implemented as classes under the stdlib directory
 * tree. The namespace and the name of the class must match the directory path
 * and the file name of its source code. Each class must extend the Form class.
 * The minimum requirement is to implement the render() method that actually
 * displays the page; a static method, typically named enter(), can also be
 * defined to allow external code to invoke that form the first time:
 * 
 * <blockquote><pre>
 * &lt;php
 * // MyForm.php file under the stdlib directory.
 * require_once __DIR__ . "/../stdlib/all.php";
 * use it\icosaedro\web\bt_\Form;
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
 *     static function enter()
 *     {
 *         $f = new self();
 *         $f-&gt;render();
 *     }
 * 
 * }
 * </pre></blockquote>
 * 
 * It is really important to note that:
 * <ul>
 * <li>A "no store" caching directive is automatically sent to the browser to
 * prevent pagest from being cached and saved. This can be overridden by client
 * code with another header("Cache-Control: ...") directive.</li>
 * <li>Invoking the open() and close() methods is mandatory to put a form element
 * in the page, as this class relies on it to keep its state between requests
 * to the same page.</li>
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
 * // MyForm.php file under the stdlib directory.
 * require_once __DIR__ . "/../stdlib/all.php";
 * use it\icosaedro\web\bt_\Form;
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
 *     <b>/&#42;* @param string $color &#42;/
 *     function colorButton($color)
 *     {
 *          $this-&gt;color = $color;
 *          $this-&gt;render();
 *     }</b>
 * 
 * }
 * </pre></blockquote>
 * 
 * By clicking on any button, the corresponding handler method is invoked with
 * the specified values as arguments. In our example there is only one handler
 * method and each button invokes that handler with different value for the
 * argument, that is the chosen background color. Handler methods should perform
 * some action and then they may render the form again or invoke the function that
 * displays another form.
 * 
 * <h2>Retrieving form data</h2>
 * If the form contains input controls, these should be acquired by
 * <u>overriding</u> the retrieve method. It is important to remember to always
 * call the parent retrieve() method first to allow this class to recover the
 * state of the form:
 *
 * <blockquote><pre>
 *     function retrieve()
 *     {
 *         parent::retrieve();
 *         ... // your specific code here
 *     }
 * </pre></blockquote>
 * 
 * The retrieve() method is invoked automatically on every postback detected
 * and before invoking the handler method. Several controls classes are also
 * available to make easier handling users' input: 1. Define a private property
 * in the class to store the control. 2. Crate a new instance of the control
 * in the constructor. 3. Displays the control in the page by invoking its
 * render() method. 4. Let the Form class to retrieve the current value if the
 * control from the postback; no need to override the retrieve() method anymore.
 * 
 * 
 * <h2>Storing data in the page</h2>
 * The setData() and getData() methods allows to store data between postbacks.
 * These data are serialized and stored in the bt stack on the server, and then
 * recovered at any postback. Note that the serialization process does not work
 * with open resources (these are recovered as int zero!), so be careful to to
 * put in the state of the page only those values that can be safely serialized.
 * 
 * 
 * <h2>Form initialization</h2>
 * In most cases a specific constructor should be defined; remember to invoke
 * parent constructor too (although PHPLint already takes care to warn you about
 * that). This custom constructor might set the initial default state of the form,
 * typically empty.
 * 
 * <p>Another method you may want to override is the entering() method, which is
 * called the first time the page is requested. Here, for example, you may retrieve
 * from the data base the specific record to modify. Do not forget to invoke
 * the render method at the end, or the resulting page will be empty.
 * 
 * <h2>Input validation cycle</h2>
 * Often a form has one "Save" button to save the changes of the form, typically
 * on a data base. Once the entered data have been retrieved in the retrieve()
 * method, they should always be validated. If this validation succeeds, data
 * can be saved and then another page can be displayed.
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
 *             // Save that entered data here, then:
 *             AnotherForm::enter();
 *         } else {
 *             $this-&gt;render("Invalid data so and so...");
 *         }
 *     }
 * </pre></blockquote>
 * 
 * <h2>Invoking other forms: go-to and go-sub</h2>
 * Each form should implement some entry static method, for example enter(),
 * that can be invoked directly from PHP code or indirectly through bt_.
 * That method should then instantiate its form class and invoke the render()
 * method:
 * 
 * <blockquote><pre>
 *     function anotherFormButton()
 *     {
 *         // Go-to another page:
 *         AnotherForm::enter();
 *     }
 * </pre></blockquote>
 * 
 * By doing so we are performing a sorta of "go-to" from a page to another.
 * A sorta of "go-sub" can also be performed by invoking the returnTo() method
 * to set a return point in our class:
 * 
 * <blockquote><pre>
 *     function anotherFormButton()
 *     {
 *         // Go-sub to another page:
 *         $this-&gt;returnTo("render");
 *         AnotherForm::enter();
 *     }
 * </pre></blockquote>
 * 
 * In this example a return point is set to our render method, so that if the
 * destination page performs a UserSession::invokeCallBackward(), the state of
 * our form is resumed and our form is displayed back again. Note that all the
 * functions may take arguments: AnotherForm::enter() might take arguments to
 * initialize the new form properly; returnTo() may take arguments to be passed
 * to our call-backward method; and finally, UserSession::invokeCallBackward()
 * might take further arguments the other form may want to return to the
 * call-backward method as a result of its work.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/05/15 04:19:16 $
 */
abstract class Form extends Control implements ContainerInterface {
		
/*
 * IMPLEMENTATION NOTES. The state of this object is saved on the stack
 * as a call-backward to the back() method:
 * 
 * 0. "\it\icosaedro\web\bt_\Form::back"
 * 1. FQN of the actual class implementing this Form.
 * 2. Serialized state of the form ($this->_data).
 * 
 * This backward call must be invoked with these additional arguments:
 * 
 * 3. Boolean value indicating if the retrieve method must be invoked. Must be
 *    set to true for regular postbacks from the form mask. Must be set to false
 *    returning from another form and when capturing user's broser history
 *    navigation attempts (typically a "back" button).
 * 4. The name of the panel, or "*" for the form itself.
 * 5. The name of the handler method to be invoked.
 * 6. Possibly further arguments to pass to the handler method.
 * 
 * See also the back() method below.
 */
	
	/**
	 * Associative array of data to be saved to and retrieved from the page.
	 * @var mixed[string]
	 */
	private $_data;
	
	/**
	 * Nested controls.
	 * @var Control[string]
	 */
	private $_controls;
	
	/**
	 * ID for the next anchor.
	 * @var int
	 */
	private $_anchors_counter = 0;
	
	/**
	 * Creates a new, empty form.
	 */
	function __construct()
	{
		parent::__construct(NULL, "Form");
		$this->_data = array();
		$this->_controls = array();
	}
	
	/**
	 * Stores a name/value pair to be saved along with the form state.
	 * @param string $name Name of the data to store.
	 * @param mixed $value Value to store. Only data that can be serialized
	 * should be put here; resources are not serializable, for example, and are
	 * then retrieved as integer zero.
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
		foreach($this->_controls as $c)
			$c->retrieve();
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
	 * Event capturing user's navigation in browser history, typically a "back".
	 * The browser should had already warned the user that pages are not cached
	 * and history navigation is not supported by bt_. This implementation
	 * invokes the render method, but client code may re-implement in an attempt
	 * to meet user's expectation to leave the page in some way, for example
	 * performing the same action of the "Cancel", "Dismiss" or "Stop" button.
	 */
	function defaultEvent()
	{
		$this->render();
	}
	
	/**
	 * Perses the handler method and returns the panel and the method. For
	 * methods of this class, the handler is simply the name of the method.
	 * For methods of some sub-panel, it is the name of the panel + space
	 * + method name.
	 * @param string $handler Name of the handler method.
	 * @param string & $panel_name Parsed name of the panel, "*" for this class.
	 * @param string & $method Parsed name of the method.
	 * @throws InvalidArgumentException Panel or method does not exist.
	 */
	private function parseHandler($handler, /*. return .*/ & $panel_name, /*. return .*/ & $method)
	{
		$m = explode(" ", $handler);
		if( count($m) == 1 ){
			// The handler is a method of this object.
			$panel_name = "*";
			/*. Control .*/ $instance = $this;
			$method = $handler;
			
		} else if( count($m) == 2 ){
			// The handler is a method of some nested panel.
			$panel_name = $m[0];
			if( ! isset($this->_controls[$panel_name]) )
				throw new InvalidArgumentException("this panel does not exist: $panel_name");
			$instance = $this->_controls[$panel_name];
			$method = $m[1];
			
		} else {
			throw new RuntimeException("invalid handler syntax: $handler");
		}
		if( ! method_exists($instance, $method) )
			throw new InvalidArgumentException("method " . get_class($instance) ."::". $method . " does not exist");
	}
	
	/**
	 * Sets the return point on the stack before invoking an external form or
	 * any external bt function. This method pushes on the bt stack a backward
	 * call that restores the state of this form and then invokes the specified
	 * handler method. The external bt function shall invoke the top of the bt
	 * stack once finished its job, possibly adding more arguments to be passed
	 * to the handler method. Example:
	 * <pre>
	 * function deleteButton()
	 * {
	 *	// Invoke the confirmation dialog box with its own "Yes" and "No"
	 *	// buttons; this dialog in turn invokes the top of the bt stack adding
	 *	// respectively TRUE or FALSE to the argument:
	 *	$this-&gt;returnTo("deleteConfirm");
	 *	CommonDialogs::YesNo("Sure to delete?");
	 * }
	 * 
	 * function deleteConfirm($yes)
	 * {
	 *	if( $yes ){
	 *		// "Yes" pressed.
	 *		...
	 *	} else {
	 *		// "No" pressed. Do nothing.
	 *		$this-&gt;render();
	 *	}
	 * }
	 * </pre>
	 * @param string $handler Name of the method of this object to invoke once
	 * the state of the form has been restored from the bt stack.
	 */
	function returnTo($handler /*. , args .*/)
	{
		$this->parseHandler($handler, $panel_name, $method);
		$this->save();
		$a = /*. (mixed[int]) .*/ array();
		$a[0] = get_class($this);
		$a[1] = $this->_data;
		$a[2] = FALSE; // not a postback
		$a[3] = $panel_name;
		$a[4] = $method;
		$b = func_get_args();
		array_shift($b);
		foreach($b as $x)
			$a[] = $x;
		UserSession::stackPush(__CLASS__ . "::back", $a);
	}
	
	
	/**
	 * Sends to standard output a button. The name of the handling method and its
	 * arguments can be specified; arguments are serialized and added to the
	 * state of the form.
	 * @param string $text Caption of the button.
	 * @param string $handler Name of the method of this object to invoke if that
	 * button is clicked; arguments may follow. If this button is set from a
	 * nested panel, the name of the panel and the name of the method separated
	 * by a single white space must be provided.
	 * @throws InvalidArgumentException The panel does not exits. The handler
	 * method does not exist.
	 */
	function button($text, $handler /*. , args .*/)
	{
		$this->parseHandler($handler, $panel_name, $method);
		$b = /*. (mixed[int]) .*/ array();
		$b[0] = $text;
		$b[1] = UserSession::class . "::invokeCallBackward";
		$b[2] = TRUE; // actual postback, retrieve field data
		$b[3] = $panel_name;
		$b[4] = $method;
		$a = func_get_args();
		array_shift($a);
		array_shift($a);
		foreach($a as $x)
			$b[] = $x;
		UserSession::addAttributes($this->_add_attributes);
		$this->_add_attributes = NULL;
		try {
			call_user_func_array(UserSession::class . "::button", $b);
		}
		catch(Exception $e){
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
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
		$i = $this->_anchors_counter++;
		$button_id = "Form_anchor_click_$i";
		echo "<a href='#' ", $this->_add_attributes,
			" onclick=\"document.getElementById('$button_id').click();\">$text_html</a>";
		$this->_add_attributes = " id=$button_id style='display: none;'";
		$f = /*. (mixed[int]) .*/ array();
		$f[0] = $this;
		$f[1] = "button";
		$a = func_get_args();
		$a[0] = "";
		try {
			call_user_func_array($f, $a);
		} catch (Exception $e) {
			throw new RuntimeException($e->getMessage(), 1, $e);
		}
	}
	
	/**
	 * Sends to standard output the opening tag of the form element. Each page
	 * should contain exactly one invokation of this method and another for the
	 * companion close() method.
	 */
	function open()
	{
		UserSession::addAttributes($this->_add_attributes);
		$this->_add_attributes = NULL;
		UserSession::formOpen();
		
		// Capture the "implicit submission mechanism":
		$this->addAttributes("id=phplint_defaultButton style='display: none'");
		$this->button("", "defaultButton");
		
		// Put invisible button to capture browser history navigation event
		// to invoke the defaultEvent() handler:
		UserSession::setDefaultCallForward();
		$b = /*. (mixed[int]) .*/ array();
		$b[0] = "";
		$b[1] = UserSession::class . "::invokeCallBackward";
		$b[2] = FALSE; // not a postback, form fields not available
		$b[3] = "*";
		$b[4] = "defaultEvent";
		UserSession::addAttributes("style='display: none'");
		try {
			call_user_func_array(UserSession::class . "::button", $b);
		}
		catch(Exception $e){
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * Sends to standard output the closing tag of the form element. This also
	 * tells the form is finished and its state can be saved on the bt stack.
	 */
	function close()
	{
		$this->save();
		$a = /*. (mixed[int]) .*/ array();
		$a[0] = get_class($this);
		$a[1] = $this->_data;
		UserSession::stackPush(__CLASS__ . "::back", $a);
		UserSession::formClose();
	}
	
	
	/**
	 * Handler routine for the first request of this form from another page.
	 * This default implementation simply invokes the render routine, but you
	 * may re-implement if required to initialize the state of the form.
	 */
	function entering()
	{
		$this->render();
	}
	
	
	/**
	 * For internal use only of this class; client's code should never invoke
	 * this method.
	 * @param string $form_class Name of the actual implementing class.
	 * @param mixed[string] $form_data Form data.
	 * @param boolean $is_post If true it is a postback, then invoke retrieve().
	 * If false, it is not a regular postback and field data should not be
	 * retrieved (this may happen for a call-backward invokation from bt stack
	 * or the user attempt to navigate the browser history).
	 * @param string $panel_name Name of the panel the handler method belongs to.
	 * @param string $method Name of the handler method.
	 * @throws Exception
	 */
	static function back($form_class, $form_data, $is_post, $panel_name, $method /*. , args .*/)
	{
		// Create the Form object.
		$c = new ReflectionClass($form_class);
		$f = cast(__CLASS__, $c->newInstanceArgs(array()));
		
		$f->_data = $form_data;
		$f->resume();
		if( $is_post )
			$f->retrieve();
		
		// Invoke handler method:
		if( $panel_name === "*" )
			/*. Control .*/ $control = $f;
		else
			$control = $f->_controls[$panel_name];
		$cb = /*. (mixed[int]) .*/ array();
		$cb[0] = $control;
		$cb[1] = $method;
		$a = func_get_args();
		array_shift($a);
		array_shift($a);
		array_shift($a);
		array_shift($a);
		array_shift($a);
		call_user_func_array($cb, $a);
		if( $control !== $f )
			$f->eventOnControl($control);
	}
	
}
