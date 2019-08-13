<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\web\Html;

/**
 * Simple Turing-test control to test for a real human user. Currently this
 * implementation asks the user to reply to a simple arithmetic question;
 * the parse() method will throw exception if the reply is missing or invalid.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/05/09 10:10:49 $
 */
class TuringTest extends Line {
	
	/**
	 * The text of the challanging question, lazy initialized.
	 * @var string
	 */
	private $question;
	
	/**
	 * The text of the expected reply, lazy initialized.
	 * @var string
	 */
	private $reply;
	
	/**
	 * Generate a new question and reply if not already set.
	 */
	private function generateChallange()
	{
		if( strlen($this->question) > 0 )
			return;
		$a = mt_rand(0, 30);
		$b = mt_rand(0, 30);
		$this->question = "$a + $b = ";
		$this->reply = "".($a + $b);
	}
	
	function save()
	{
		parent::save();
		$this->_form->setData($this->_name .".question", $this->question);
		$this->_form->setData($this->_name .".reply", $this->reply);
	}
	
	function resume()
	{
		parent::resume();
		$this->question = (string) $this->_form->getData($this->_name .".question");
		$this->reply    = (string) $this->_form->getData($this->_name .".reply");
	}
	
	/**
	 * Parses and validates the reply to the question. This method is named
	 * "parse" only for uniformity with the other controls that have such a
	 * method, but applications should normally ignore the returned value; the
	 * only thing they should care about is the exception thrown if the reply
	 * is missing or invalid.
	 * @return string The reply given.
	 * @throws ParseException
	 */
	function parse()
	{
		$this->generateChallange();
		$s = $this->getValue();
		if( $s === NULL ){
			throw new ParseException($this, ParseException::REASON_MISSING);
		} else if( $s === "" ){
			throw new ParseException($this, ParseException::REASON_EMPTY);
		} else {
			if( $s !== $this->reply )
				throw new ParseException($this, ParseException::REASON_INVALID);
			return $this->reply;
		}
	}
	
	
	/**
	 * Send this control to the standard output.
	 * @return void
	 */
	function render()
	{
		$this->generateChallange();
		echo Html::text($this->question);
		echo "<input size=5 type=text name='", $this->_name,
				"' value='", Html::text($this->getValue()),
				"' ", $this->_add_attributes, ">";
	}
	
}
