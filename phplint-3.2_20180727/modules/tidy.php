<?php
/** Tidy Functions.

See: {@link http://www.php.net/manual/en/ref.tidy.php}
@package tidy
*/

# These values are alla dummy:
define("TIDY_TAG_UNKNOWN", 1);
define("TIDY_TAG_A", 2);
define("TIDY_TAG_ABBR", 3);
define("TIDY_TAG_ACRONYM", 4);
define("TIDY_TAG_ALIGN", 5);
define("TIDY_TAG_APPLET", 6);
define("TIDY_TAG_AREA", 7);
define("TIDY_TAG_B", 8);
define("TIDY_TAG_BASE", 9);
define("TIDY_TAG_BASEFONT", 10);
define("TIDY_TAG_BDO", 11);
define("TIDY_TAG_BGSOUND", 12);
define("TIDY_TAG_BIG", 13);
define("TIDY_TAG_BLINK", 14);
define("TIDY_TAG_BLOCKQUOTE", 15);
define("TIDY_TAG_BODY", 16);
define("TIDY_TAG_BR", 17);
define("TIDY_TAG_BUTTON", 18);
define("TIDY_TAG_CAPTION", 19);
define("TIDY_TAG_CENTER", 20);
define("TIDY_TAG_CITE", 21);
define("TIDY_TAG_CODE", 22);
define("TIDY_TAG_COL", 23);
define("TIDY_TAG_COLGROUP", 24);
define("TIDY_TAG_COMMENT", 25);
define("TIDY_TAG_DD", 26);
define("TIDY_TAG_DEL", 27);
define("TIDY_TAG_DFN", 28);
define("TIDY_TAG_DIR", 29);
define("TIDY_TAG_DIV", 30);
define("TIDY_TAG_DL", 31);
define("TIDY_TAG_DT", 32);
define("TIDY_TAG_EM", 33);
define("TIDY_TAG_EMBED", 34);
define("TIDY_TAG_FIELDSET", 35);
define("TIDY_TAG_FONT", 36);
define("TIDY_TAG_FORM", 37);
define("TIDY_TAG_FRAME", 38);
define("TIDY_TAG_FRAMESET", 39);
define("TIDY_TAG_H1", 40);
define("TIDY_TAG_H2", 41);
define("TIDY_TAG_H3", 42);
define("TIDY_TAG_H4", 43);
define("TIDY_TAG_H5", 44);
define("TIDY_TAG_H6", 45);
define("TIDY_TAG_HEAD", 46);
define("TIDY_TAG_HR", 47);
define("TIDY_TAG_HTML", 48);
define("TIDY_TAG_I", 49);
define("TIDY_TAG_IFRAME", 50);
define("TIDY_TAG_ILAYER", 51);
define("TIDY_TAG_IMG", 52);
define("TIDY_TAG_INPUT", 53);
define("TIDY_TAG_INS", 54);
define("TIDY_TAG_ISINDEX", 55);
define("TIDY_TAG_KBD", 56);
define("TIDY_TAG_KEYGEN", 57);
define("TIDY_TAG_LABEL", 58);
define("TIDY_TAG_LAYER", 59);
define("TIDY_TAG_LEGEND", 60);
define("TIDY_TAG_LI", 61);
define("TIDY_TAG_LINK", 62);
define("TIDY_TAG_LISTING", 63);
define("TIDY_TAG_MAP", 64);
define("TIDY_TAG_MARQUEE", 65);
define("TIDY_TAG_MENU", 66);
define("TIDY_TAG_META", 67);
define("TIDY_TAG_MULTICOL", 68);
define("TIDY_TAG_NOBR", 69);
define("TIDY_TAG_NOEMBED", 70);
define("TIDY_TAG_NOFRAMES", 71);
define("TIDY_TAG_NOLAYER", 72);
define("TIDY_TAG_NOSAVE", 73);
define("TIDY_TAG_NOSCRIPT", 74);
define("TIDY_TAG_OBJECT", 75);
define("TIDY_TAG_OL", 76);
define("TIDY_TAG_OPTGROUP", 77);
define("TIDY_TAG_OPTION", 78);
define("TIDY_TAG_P", 79);
define("TIDY_TAG_PARAM", 80);
define("TIDY_TAG_PLAINTEXT", 81);
define("TIDY_TAG_PRE", 82);
define("TIDY_TAG_Q", 83);
define("TIDY_TAG_RP", 84);
define("TIDY_TAG_RT", 85);
define("TIDY_TAG_RTC", 86);
define("TIDY_TAG_RUBY", 87);
define("TIDY_TAG_S", 88);
define("TIDY_TAG_SAMP", 89);
define("TIDY_TAG_SCRIPT", 90);
define("TIDY_TAG_SELECT", 91);
define("TIDY_TAG_SERVER", 92);
define("TIDY_TAG_SERVLET", 93);
define("TIDY_TAG_SMALL", 94);
define("TIDY_TAG_SPACER", 95);
define("TIDY_TAG_SPAN", 96);
define("TIDY_TAG_STRIKE", 97);
define("TIDY_TAG_STRONG", 98);
define("TIDY_TAG_STYLE", 99);
define("TIDY_TAG_SUB", 100);
define("TIDY_TAG_TABLE", 101);
define("TIDY_TAG_TBODY", 102);
define("TIDY_TAG_TD", 103);
define("TIDY_TAG_TEXTAREA", 104);
define("TIDY_TAG_TFOOT", 105);
define("TIDY_TAG_TH", 106);
define("TIDY_TAG_THEAD", 107);
define("TIDY_TAG_TITLE", 108);
define("TIDY_TAG_TR", 109);
define("TIDY_TAG_TT", 110);
define("TIDY_TAG_U", 111);
define("TIDY_TAG_UL", 112);
define("TIDY_TAG_VAR", 113);
define("TIDY_TAG_WBR", 114);
define("TIDY_TAG_XMP", 115);
define("TIDY_ATTR_UNKNOWN", 116);
define("TIDY_ATTR_ABBR", 117);
define("TIDY_ATTR_ACCEPT", 118);
define("TIDY_ATTR_ACCEPT_CHARSET", 119);
define("TIDY_ATTR_ACCESSKEY", 120);
define("TIDY_ATTR_ACTION", 121);
define("TIDY_ATTR_ADD_DATE", 122);
define("TIDY_ATTR_ALIGN", 123);
define("TIDY_ATTR_ALINK", 124);
define("TIDY_ATTR_ALT", 125);
define("TIDY_ATTR_ARCHIVE", 126);
define("TIDY_ATTR_AXIS", 127);
define("TIDY_ATTR_BACKGROUND", 128);
define("TIDY_ATTR_BGCOLOR", 129);
define("TIDY_ATTR_BGPROPERTIES", 130);
define("TIDY_ATTR_BORDER", 131);
define("TIDY_ATTR_BORDERCOLOR", 132);
define("TIDY_ATTR_BOTTOMMARGIN", 133);
define("TIDY_ATTR_CELLPADDING", 134);
define("TIDY_ATTR_CELLSPACING", 135);
define("TIDY_ATTR_CHAR", 136);
define("TIDY_ATTR_CHAROFF", 137);
define("TIDY_ATTR_CHARSET", 138);
define("TIDY_ATTR_CHECKED", 139);
define("TIDY_ATTR_CITE", 140);
define("TIDY_ATTR_CLASS", 141);
define("TIDY_ATTR_CLASSID", 142);
define("TIDY_ATTR_CLEAR", 143);
define("TIDY_ATTR_CODE", 144);
define("TIDY_ATTR_CODEBASE", 145);
define("TIDY_ATTR_CODETYPE", 146);
define("TIDY_ATTR_COLOR", 147);
define("TIDY_ATTR_COLS", 148);
define("TIDY_ATTR_COLSPAN", 149);
define("TIDY_ATTR_COMPACT", 150);
define("TIDY_ATTR_CONTENT", 151);
define("TIDY_ATTR_COORDS", 152);
define("TIDY_ATTR_DATA", 153);
define("TIDY_ATTR_DATAFLD", 154);
define("TIDY_ATTR_DATAPAGESIZE", 155);
define("TIDY_ATTR_DATASRC", 156);
define("TIDY_ATTR_DATETIME", 157);
define("TIDY_ATTR_DECLARE", 158);
define("TIDY_ATTR_DEFER", 159);
define("TIDY_ATTR_DIR", 160);
define("TIDY_ATTR_DISABLED", 161);
define("TIDY_ATTR_ENCODING", 162);
define("TIDY_ATTR_ENCTYPE", 163);
define("TIDY_ATTR_FACE", 164);
define("TIDY_ATTR_FOR", 165);
define("TIDY_ATTR_FRAME", 166);
define("TIDY_ATTR_FRAMEBORDER", 167);
define("TIDY_ATTR_FRAMESPACING", 168);
define("TIDY_ATTR_GRIDX", 169);
define("TIDY_ATTR_GRIDY", 170);
define("TIDY_ATTR_HEADERS", 171);
define("TIDY_ATTR_HEIGHT", 172);
define("TIDY_ATTR_HREF", 173);
define("TIDY_ATTR_HREFLANG", 174);
define("TIDY_ATTR_HSPACE", 175);
define("TIDY_ATTR_HTTP_EQUIV", 176);
define("TIDY_ATTR_ID", 177);
define("TIDY_ATTR_ISMAP", 178);
define("TIDY_ATTR_LABEL", 179);
define("TIDY_ATTR_LANG", 180);
define("TIDY_ATTR_LANGUAGE", 181);
define("TIDY_ATTR_LAST_MODIFIED", 182);
define("TIDY_ATTR_LAST_VISIT", 183);
define("TIDY_ATTR_LEFTMARGIN", 184);
define("TIDY_ATTR_LINK", 185);
define("TIDY_ATTR_LONGDESC", 186);
define("TIDY_ATTR_LOWSRC", 187);
define("TIDY_ATTR_MARGINHEIGHT", 188);
define("TIDY_ATTR_MARGINWIDTH", 189);
define("TIDY_ATTR_MAXLENGTH", 190);
define("TIDY_ATTR_MEDIA", 191);
define("TIDY_ATTR_METHOD", 192);
define("TIDY_ATTR_MULTIPLE", 193);
define("TIDY_ATTR_NAME", 194);
define("TIDY_ATTR_NOHREF", 195);
define("TIDY_ATTR_NORESIZE", 196);
define("TIDY_ATTR_NOSHADE", 197);
define("TIDY_ATTR_NOWRAP", 198);
define("TIDY_ATTR_OBJECT", 199);
define("TIDY_ATTR_OnAFTERUPDATE", 200);
define("TIDY_ATTR_OnBEFOREUNLOAD", 201);
define("TIDY_ATTR_OnBEFOREUPDATE", 202);
define("TIDY_ATTR_OnBLUR", 203);
define("TIDY_ATTR_OnCHANGE", 204);
define("TIDY_ATTR_OnCLICK", 205);
define("TIDY_ATTR_OnDATAAVAILABLE", 206);
define("TIDY_ATTR_OnDATASETCHANGED", 207);
define("TIDY_ATTR_OnDATASETCOMPLETE", 208);
define("TIDY_ATTR_OnDBLCLICK", 209);
define("TIDY_ATTR_OnERRORUPDATE", 210);
define("TIDY_ATTR_OnFOCUS", 211);
define("TIDY_ATTR_OnKEYDOWN", 212);
define("TIDY_ATTR_OnKEYPRESS", 213);
define("TIDY_ATTR_OnKEYUP", 214);
define("TIDY_ATTR_OnLOAD", 215);
define("TIDY_ATTR_OnMOUSEDOWN", 216);
define("TIDY_ATTR_OnMOUSEMOVE", 217);
define("TIDY_ATTR_OnMOUSEOUT", 218);
define("TIDY_ATTR_OnMOUSEOVER", 219);
define("TIDY_ATTR_OnMOUSEUP", 220);
define("TIDY_ATTR_OnRESET", 221);
define("TIDY_ATTR_OnROWENTER", 222);
define("TIDY_ATTR_OnROWEXIT", 223);
define("TIDY_ATTR_OnSELECT", 224);
define("TIDY_ATTR_OnSUBMIT", 225);
define("TIDY_ATTR_OnUNLOAD", 226);
define("TIDY_ATTR_PROFILE", 227);
define("TIDY_ATTR_PROMPT", 228);
define("TIDY_ATTR_RBSPAN", 229);
define("TIDY_ATTR_READONLY", 230);
define("TIDY_ATTR_REL", 231);
define("TIDY_ATTR_REV", 232);
define("TIDY_ATTR_RIGHTMARGIN", 233);
define("TIDY_ATTR_ROWS", 234);
define("TIDY_ATTR_ROWSPAN", 235);
define("TIDY_ATTR_RULES", 236);
define("TIDY_ATTR_SCHEME", 237);
define("TIDY_ATTR_SCOPE", 238);
define("TIDY_ATTR_SCROLLING", 239);
define("TIDY_ATTR_SELECTED", 240);
define("TIDY_ATTR_SHAPE", 241);
define("TIDY_ATTR_SHOWGRID", 242);
define("TIDY_ATTR_SHOWGRIDX", 243);
define("TIDY_ATTR_SHOWGRIDY", 244);
define("TIDY_ATTR_SIZE", 245);
define("TIDY_ATTR_SPAN", 246);
define("TIDY_ATTR_SRC", 247);
define("TIDY_ATTR_STANDBY", 248);
define("TIDY_ATTR_START", 249);
define("TIDY_ATTR_STYLE", 250);
define("TIDY_ATTR_SUMMARY", 251);
define("TIDY_ATTR_TABINDEX", 252);
define("TIDY_ATTR_TARGET", 253);
define("TIDY_ATTR_TEXT", 254);
define("TIDY_ATTR_TITLE", 255);
define("TIDY_ATTR_TOPMARGIN", 256);
define("TIDY_ATTR_TYPE", 257);
define("TIDY_ATTR_USEMAP", 258);
define("TIDY_ATTR_VALIGN", 259);
define("TIDY_ATTR_VALUE", 260);
define("TIDY_ATTR_VALUETYPE", 261);
define("TIDY_ATTR_VERSION", 262);
define("TIDY_ATTR_VLINK", 263);
define("TIDY_ATTR_VSPACE", 264);
define("TIDY_ATTR_WIDTH", 265);
define("TIDY_ATTR_WRAP", 266);
define("TIDY_ATTR_XML_LANG", 267);
define("TIDY_ATTR_XML_SPACE", 268);
define("TIDY_ATTR_XMLNS", 269);
define("TIDY_NODETYPE_ROOT", 270);
define("TIDY_NODETYPE_DOCTYPE", 271);
define("TIDY_NODETYPE_COMMENT", 272);
define("TIDY_NODETYPE_PROCINS", 273);
define("TIDY_NODETYPE_TEXT", 274);
define("TIDY_NODETYPE_START", 275);
define("TIDY_NODETYPE_END", 276);
define("TIDY_NODETYPE_STARTEND", 277);
define("TIDY_NODETYPE_CDATA", 278);
define("TIDY_NODETYPE_SECTION", 279);
define("TIDY_NODETYPE_ASP", 280);
define("TIDY_NODETYPE_JSTE", 281);
define("TIDY_NODETYPE_PHP", 282);
define("TIDY_NODETYPE_XMLDECL", 283);

class tidyNode
{
	public /*. string .*/ $value;
	public /*. string .*/ $name;
	public /*. int .*/ $type = 0; # dummy initial value
	public /*. int .*/ $line = 0; # dummy initial value
	public /*. int .*/ $column = 0; # dummy initial value
	public /*. boolean .*/ $proprietary = false; # dummy initial value
	public /*. int .*/ $id = 0; # dummy initial value
	public $attribute = /*. (string[string]) .*/ NULL;
	public $child = /*. (tidyNode[int]) .*/ NULL;
	public /*. tidyNode .*/ function getParent(){}
	public /*. boolean .*/ function hasChildren(){}
	public /*. boolean .*/ function hasSiblings(){}
	public /*. boolean .*/ function isAsp(){}
	public /*. boolean .*/ function isComment(){}
	public /*. boolean .*/ function isHtml(){}
	public /*. boolean .*/ function isJste(){}
	public /*. boolean .*/ function isPhp(){}
	public /*. boolean .*/ function isText(){}
}

/**
 * Tidy class.
 * 
 * <p>The supported encodings are (case-insensitive): ascii, latin0, latin1, raw,
 * utf8, iso2022, mac, win1252, ibm858, utf16, utf16le, utf16be, big5, shiftjis.
 * Note that "UTF-8" is not a recognized encoding; only "UTF8" is.
 * 
 * <p>Errors are reported quite randomly among the methods, and sometimes
 * notices and warning could be reported on methods apparently returning success;
 * so it is advised to always turn "on" the errors-to-exception mapping while
 * using this class by including the stdlib/errors.php module.
 */
class tidy
{
	/**
	 * Informational, warning and error messages collected while parsing.
	 * Lines are separated by '\r\n'; a trailing '\r' is also added for some
	 * reason and may confuse the output if sent verbatim to the terminal as it
	 * moves the cursor to the left margin.
	 * The NULL value means either no parsing has still being performed or the
	 * parsing succeeded.
	 * @var string 
	 */
	public $errorBuffer;

	/**
	 * Initializes a new parser and possibly also parse the specified file.
	 * Hint: to parse a string, do not specify any argument and then use the
	 * parseString() method instead.
	 * @param string $filename File to parse. If this argument is provided, it
	 * must be a valid existing file name.
	 * @param mixed $config String of the configuration options or associative
	 * array of the options. Set the empty array here if you want to set the
	 * following arguments too.
	 * @param string $encoding Encoding for input and output documents.
	 * Note that the tidy library still can't parse a possible HTML META element
	 * specifying the encoding, so setting this argument is advised.
	 * Default input encoding: unspecified, possibly unexpected.
	 * Default output encoding: UTF8.
	 * @param boolean $use_include_path If true, a relative $filename path is
	 * resolved first against the directories listed in the include_path php.ini
	 * directive, and then search in the current directory. Default: FALSE.
	 * @return void
	 * @triggers E_WARNING Failed reading $filename. Failed reading the $config
	 * file when this argument is a string. Invalid $encoding.
	 * @triggers E_NOTICE Unknown or invalid $config when this argument is an
	 * associative array.
	 */
	function __construct($filename = NULL, $config = NULL, $encoding = NULL,
			$use_include_path = FALSE){}

	/**
	 * Returns the node starting with the BODY element.
	 * @return tidyNode The BODY element, either parsed or added by this library.
	 * Returns NULL if still no parsing performed.
	 */
	function body(){}
	
	/*. boolean .*/ function cleanRepair(){}
	/*. boolean .*/ function diagnose(){}
	/*. mixed[string] .*/ function getConfig(){}
	/*. int .*/ function getHtmlVer(){}

	/**
	 * Returns the value of the specified option.
	 * @param string $option Name of the option to retrieve.
	 * @return mixed Value of the option.
	 * @triggers E_WARNING No this option.
	 */
	function getOpt(/*. string .*/ $option){}
	
	/**
	 * Returns the documentation for the given option name.
	 * @param string $optname
	 * @return string
	 * @deprecated This method is not available; getRelease() gives 2017/03/01
	 * on Wamp Server with PHP 7.1.9.
	 */
	function getOptDoc($optname){}
	
	/**
	 * Return the release date of the tidy library.
	 * @return string
	 */
	function getRelease(){}
	
	/**
	 * Returns the status of the document.
	 * @return int
	 */
	function getStatus(){}
	
	/**
	 * Returns the HEAD element.
	 * @return tidyNode The HEAD element, or NULL if not available.
	 */
	function head(){}
	
	/**
	 * Returns the HTML element.
	 * @return tidyNode The HTML element, or NULL if not available.
	 */
	function html(){}
	
	/**
	 * Tells if the document is XHTML.
	 * @deprecated This method is not implemented yet; it always returns false.
	 * @return boolean
	 */
	function isXhtml(){}
	
	/**
	 * Tells if the document is XML.
	 * @return boolean
	 */
	function isXml(){}
	
	/**
	 * Parse the given file.
	 * @param string $filename File to parse.
	 * @param mixed $config String of the configuration options or associative
	 * array of the options. Set the empty array here if you want to set the
	 * following arguments too.
	 * @param string $encoding Encoding for input and output documents.
	 * Note that the tidy library still can't parse a possible HTML META element
	 * specifying the encoding, so setting this argument is advised.
	 * Default input encoding: unspecified, possibly unexpected.
	 * Default output encoding: UTF8.
	 * @param boolean $use_include_path If true, a relative $filename path is
	 * resolved first against the directories listed in the include_path php.ini
	 * directive, and then search in the current directory. Default: FALSE.
	 * @return boolean FALSE on E_WARNING, TRUE otherwise.
	 * @triggers E_WARNING Failed reading $filename. Failed reading the $config
	 * file when this argument is a string. Invalid $encoding.
	 * @triggers E_NOTICE Unknown or invalid $config when this argument is an
	 * associative array.
	 */
	function parseFile($filename, $config = NULL, $encoding = NULL, $use_include_path = FALSE){}
	
	/**
	 * Parse the given string.
	 * @param string $input Text to parse.
	 * @param mixed $config String of the configuration options or associative
	 * array of the options. Set the empty array here if you want to set the
	 * following arguments too.
	 * @param string $encoding Encoding for input and output documents.
	 * Note that the tidy library still can't parse a possible HTML META element
	 * specifying the encoding, so setting this argument is advised.
	 * Default input encoding: unspecified, possibly unexpected.
	 * Default output encoding: UTF8.
	 * @return boolean FALSE on E_WARNING for invalid encoding, TRUE in any other
	 * case (success, E_WARNING or E_NOTICE for any other reason).
	 * @triggers E_WARNING Failed reading the $config file when this argument is
	 * a string. Invalid $encoding.
	 * @triggers E_NOTICE Unknown or invalid $config when this argument is an
	 * associative array.
	 */
	function parseString($input, $config = NULL, $encoding = NULL){}
	
	/**
	 * Parse the given file and returns the repaired result.
	 * @param string $filename File to parse. If this argument is provided, it
	 * must be a valid existing file name.
	 * @param mixed $config String of the configuration options or associative
	 * array of the options. Set the empty array here if you want to set the
	 * following arguments too.
	 * @param string $encoding Encoding for input and output documents.
	 * Note that the tidy library still can't parse a possible HTML META element
	 * specifying the encoding, so setting this argument is advised.
	 * Default input encoding: unspecified, possibly unexpected.
	 * Default output encoding: UTF8.
	 * @param boolean $use_include_path If true, a relative $filename path is
	 * resolved first against the directories listed in the include_path php.ini
	 * directive, and then search in the current directory. Default: FALSE.
	 * @return mixed A string containing the repaired contents of the file.
	 * Returns FALSE only if it fails accessing the file, but no error is signaled;
	 * the other errors cannot be detected from the returned value.
	 * @triggers E_WARNING Failed reading the $config file when this argument is
	 * a string. Invalid $encoding.
	 * @triggers E_NOTICE Unknown or invalid $config when this argument is an
	 * associative array.
	 */
	function repairFile($filename, $config = NULL, $encoding = NULL, $use_include_path = FALSE){}
	
	/**
	 * Parse the given string and returns the repaired result.
	 * @param string $input Text to parse.
	 * @param mixed $config String of the configuration options or associative
	 * array of the options. Set the empty array here if you want to set the
	 * following arguments too.
	 * @param string $encoding Encoding for input and output documents.
	 * Note that the tidy library still can't parse a possible HTML META element
	 * specifying the encoding, so setting this argument is advised.
	 * Default input encoding: unspecified, possibly unexpected.
	 * Default output encoding: UTF8.
	 * @return string Repaired contents of the string. Errors cannot be detected
	 * by the returned value.
	 * @triggers E_WARNING Failed reading the $config file when this argument is
	 * a string. Invalid $encoding.
	 * @triggers E_NOTICE Unknown or invalid $config when this argument is an
	 * associative array.
	 */
	function repairString($input, $config = NULL, $encoding = NULL){}
	
	/**
	 * Returns the root node of the parsed tree.
	 * @return tidyNode Root note of the parsed tree, possibly a tidyNode with
	 * empty name property if nothing has been parsed yet.
	 */
	function root(){}
}


/*. bool .*/ function tidy_parse_string(/*. string .*/ $input /*., args .*/){}
/*. string .*/ function tidy_get_error_buffer(/*. tidy .*/ $obj){}
/*. string .*/ function tidy_get_output(){}
/*. boolean .*/ function tidy_parse_file(/*. string .*/ $file /*., args .*/){}
/*. boolean .*/ function tidy_clean_repair(){}
/*. string .*/ function tidy_repair_string(/*. string .*/ $data /*., args .*/){}
/*. boolean .*/ function tidy_repair_file(/*. string .*/ $filename /*., args .*/){}
/*. boolean .*/ function tidy_diagnose(){}
/*. string .*/ function tidy_get_release(){}
/*. array .*/ function tidy_get_config(){}
/*. int .*/ function tidy_get_status(){}
/*. int .*/ function tidy_get_html_ver(){}
/*. boolean .*/ function tidy_is_xhtml(){}
/*. int .*/ function tidy_error_count(){}
/*. int .*/ function tidy_warning_count(){}
/*. int .*/ function tidy_access_count(){}
/*. int .*/ function tidy_config_count(){}
/*. mixed .*/ function tidy_getopt(/*. string .*/ $option){}
/*. tidyNode .*/ function tidy_get_root(){}
/*. tidyNode .*/ function tidy_get_html(){}
/*. tidyNode .*/ function tidy_get_head(){}
/*. tidyNode .*/ function tidy_get_body(/*. resource .*/ $tidy){}
