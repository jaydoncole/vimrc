<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\sql\SQLDriverInterface as SQLDriver;
use it\icosaedro\utils\UTF8;
use it\icosaedro\web\AcceptLanguage;
use it\icosaedro\web\Http;
use it\icosaedro\web\bt_\UserSession;

const HSPACE = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
const VSPACE = "<pre>\n</pre>";
const CENTER = "<center>";
const CENTER_ = "</center>";

/**
 * Icosaedro.it web commenting system - Common definitions and tools.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/05/15 04:12:46 $
 */
class Common {
	
	/** Landing page function after login. */
	const DASHBOARD_FUNCTION = "it\\icosaedro\\www\\PageComments::enter";
	
	/** Landing page function after login. */
	const GUEST_DASHBOARD_FUNCTION = "it\\icosaedro\\www\\Login::guestLogin";
	
	const FALLBACK_FUNCTION = "it\\icosaedro\\www\\Pages::enter";
	
	/**
	 * The administrator may access the users' table, may remove any message.
	 */
	const PERMISSION_IS_ADMIN = 0;
	
	/**
	 * The user may access its preferences.
	 */
	const PERMISSION_PREFS = 1;
	
	/**
	 * The user may post new messages.
	 */
	const PERMISSION_MAY_POST = 2;
	
	/**
	 * The user may remove its own messages.
	 */
	const PERMISSION_MAY_DELETE = 3;
	
	/**
	 *
	 * @var string
	 */
	private static $cached_language;

	/**
	 * 
	 * @param string $s
	 * @param int $max
	 * @return string
	 */
	static function short($s, $max)
	{
		$len = UTF8::length($s);
		if( $len <= $max )
			return $s;
		return substr($s, 0, UTF8::byteIndex($s, $max)) . "[...]";
	}
	
	
	/**
	 * @return string
	 */
	static function getLanguage()
	{
		if( self::$cached_language === NULL )
			self::$cached_language = AcceptLanguage::bestSupportedLanguageFromRequest("en, it")->language;
		return self::$cached_language;
	}


	static function isGuest()
	{
		return strcmp(UserSession::getSessionParameter('name'), 'guest') == 0;
	}
	
	/**
	 * Ritorna true se l'utente proprietario della sessione corrente e' abilitato
	 * alla funzionalita' n. $n ($n &ge; 0).
	 * Ritorna false in tutti gli altri casi.
	 * Il file contenente le permissions DEVE essere creato all'atto del login,
	 * altrimenti questa funzione ritorna sempre FALSE.
	 * @param int $n
	 * @return boolean
	 */
	static function checkPermission($n)
	{
		if( $n < 0 )
			return FALSE;
		$permissions = UserSession::getSessionParameter("permissions");
		if( strlen($permissions) < $n+1 )
			return FALSE;
		return $permissions[$n] === '1';
	}
	
	
//	static function setPermission($s, $n, $value)
//	{
//		$len = 4;
//		if( !(0 <= $n && $n < $len) )
//			throw new RuntimeException("no this permission: $n");
//		$s = trim($s);
//		if( strlen($s) < 4 )
//			$s .= str_repeat ("0", $len - strlen($s));
//		else if( strlen($s) > $len )
//			$s = substr($s, 0, $len);
//		return substr($s, 0, $n) . ($value? "1":"0") . substr($s, $n+1);
//	}
	
	
	static function echoPageHeader()
	{
		$disable = "?????"; // FIXME
		Http::headerContentTypeHtmlUTF8();
		echo <<< EOT
<html>
<head>
<title>WEB Pages Commenting System</title>
<style>
h1 {
	margin-top: 0;
	margin-bottom: 0;
}
</style>
</head>
<body bgcolor='#ffffff' link='#5555ff' alink='#5555ff' vlink='#5555ff'>
<img src='/img/icosaedro-16x16.png'> <b>icosaedro.it</b>
EOT;
		if( Common::isGuest() ){
			echo HSPACE, 'Guest user';
			echo HSPACE;
			UserSession::setCallBackward(Common::FALLBACK_FUNCTION);
			UserSession::anchor('Registration', "it\\icosaedro\\www\\Registration::enter", NULL);
			echo HSPACE;
			UserSession::setCallBackward(Common::FALLBACK_FUNCTION);
			UserSession::anchor('Login', "it\\icosaedro\\www\\Login::enter", "");
		} else {
			echo HSPACE, 'User ';
			echo '<b>', UserSession::getSessionParameter('name'), '</b>';
		}
		echo HSPACE;
		UserSession::anchor('Pages', Common::FALLBACK_FUNCTION);
		
		if( self::checkPermission(self::PERMISSION_IS_ADMIN) ){
			echo HSPACE;
			UserSession::anchor("Users", "it\\icosaedro\\www\\UsersMask::enter");
		}

		if( Common::checkPermission(Common::PERMISSION_PREFS)	){
			echo HSPACE;
			UserSession::setCallBackward(Common::FALLBACK_FUNCTION);
			UserSession::anchor("Preferences", "it\\icosaedro\\www\\Preferences::enter");
		}
		
		if( ! self::isGuest() ){
			echo HSPACE;
			UserSession::anchor('Logout', "it\\icosaedro\\www\\Login::logout");
		}

		echo "<hr>\n";
	}
	
	
	static function echoPageFooter()
	{
		echo VSPACE, '<hr><small>',
			"<p>This is a statefull WEB application with a modal interface:",
			" always use the anchors and the buttons provided by the application;",
			" never try to use the back button or the history of the browser",
			" since this might give unexpected results.</p>";
		echo 'Go back to <a href="http://www.icosaedro.it/">',
			'<code>www.icosaedro.it</code></a>';
//		$b = UserSession::getSessionParameter('back-to');
//		if( strlen($b) > 0 ){
//			$r = preg_replace("@^http://@", "", $b);
//			echo ' - Back to <a href=', Html::text($b."#comments"), '><code>',
//				Html::text($r), '</code></a>.';
//		}
		echo ' - Send email to <a href="mailto:salsi@icosaedro.it">',
			'Umberto Salsi</a>';
		echo '</small></BODY></HTML>';
	}

	/**
	 * 
	 * @param string $icon_resource
	 * @param string $title_html
	 * @param string $msg_html
	 */
	static function info_box($icon_resource, $title_html, $msg_html)
	{
		echo
			"<div style='border: 0.2em solid black; margin: 1em; padding: 0em;'>",
			"<table cellpadding=5><tr>",
			"<td valign=top><img src='$icon_resource'></td>",
			"<td valogn=top><big><b>$title_html</b></big><p>$msg_html</td>",
			"</tr></table>",
			"</div>";
	}

	/**
	 * 
	 * @param string $icon
	 * @param string $title
	 * @param string $msg
	 */
	static function information($icon, $title, $msg)
	{
		self::echoPageHeader();
		self::info_box($icon, $title, $msg);
		UserSession::formOpen();
		echo CENTER;
		UserSession::button("OK", UserSession::class . "::invokeCallBackward");
		echo CENTER_;
		UserSession::formClose();
		self::echoPageFooter();
	}

	/**
	 * 
	 * @param string $msg
	 */
	static function error($msg)
	{
		self::information("/img/error.png", "Error", $msg);
	}

	/**
	 * 
	 * @param string $msg
	 */
	static function warning($msg)
	{
		self::information("/img/warning.png", "Warning", $msg);
	}

	/**
	 * 
	 * @param string $msg
	 */
	static function notice($msg)
	{
		self::information("/img/notice.png", "Notice", $msg);
	}
	
	/**
	 * Confirmation dialog with false/no/cancel and true/yes/ok button.
	 * When the user chooses the button, the false or true value is passed
	 * as additional argument to the backward call in the bt stack.
	 * @param string $title Title of the dialog box, HTML encoded.
	 * @param string $msg Operaion requiring confirmation, HTML encoded.
	 * @param string $false_button False/no/cancel button caption.
	 * @param string $true_button True/yes/ok button caption.
	 */
	static function confirm($title, $msg, $false_button, $true_button)
	{
		self::echoPageHeader();
		UserSession::formOpen();
		self::info_box("/img/warning.png", $title, $msg);
		echo VSPACE;
		UserSession::button($false_button, UserSession::class . "::invokeCallBackward", FALSE);
		echo HSPACE;
		UserSession::button($true_button, UserSession::class . "::invokeCallBackward", TRUE);
		UserSession::formClose();
		self::echoPageFooter();
	}


}
