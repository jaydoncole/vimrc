<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\www\Common;
use it\icosaedro\sql\SQLException;
use it\icosaedro\web\Html;

/*. require_module 'hash'; .*/

/**
 * Provides a function to generate a summary of the latest messages added to the
 * current web page.
 */
class Summary {

	/**
	 * Max no. of latest commments to this page to show.
	 */
	const LIMIT = 99;

	/**
	 * Max length of the body abstract.
	 */
	const ABS_MAX_SIZE = 1000;
	
	
	private static function getResource()
	{
		return $_SERVER['SCRIPT_NAME'];
	}
	
	/**
	 * @param string $fragment Fragment part of the URL to generate. Either the
	 * empty string or "#123" where 123 is  the PK of the specific message.
	 * @param string $caption
	 */
	static function echoAnchorToComments($fragment, $caption)
	{
		$path = self::getResource();
		echo "<script>document.write(\"<a target=blank h\" + \"ref=\\\""
			. SiteSpecific::DISPATCHER_URL
			. "?resource=" . urlencode( $path )
			. "&cod=" . substr( md5( $path . SiteSpecific::HMAC_KEY ), 0, 8 ) . "$fragment\\\">"
			. "$caption</a>\");</script>";
	}

	/**
	 * Sends on output a summary of the latest messages added to the current page.
	 * @throws SQLException
	 */
	static function showSummary()
	{
		$path = self::getResource();

		$ps = SiteSpecific::getDB()->prepareStatement("SELECT pk FROM comments WHERE path=? ORDER BY time DESC LIMIT ?");
		$ps->setString(0, $path);
		$ps->setInt(1, self::LIMIT);
		$res = $ps->query();

		$n = $res->getRowCount();

		echo "<a name=comments></a><blockquote><i>";
		
		if( $n == 0 ){
			if( Common::getLanguage() === "it" ){
				echo "Non ci sono ancora commenti a questa pagina. Usare il link </i>Commenti<i> qui sopra per aggiungere il tuo contributo.";
			} else {
				echo "Still no comments to this page. Use the </i>Comments<i> link above to add your contribute.";
			}
			
		} else {
			if( Common::getLanguage() === "it" ){
				echo "Segue estratto degli ultimi commenti lasciati dai visitatori di questa pagina WEB.  Usare il link </i>Commenti<i> qui sopra per leggere tutti i messaggi o per aggiungere il tuo contributo.";
			} else {
				echo "An abstract of the lastest comments from the visitors of this page follows. Please, use the </i>Comments<i> link above to read all the messages or to add your contribute.";
			}
		}
		echo "</i> </blockquote>\n";

		for($i = 0; $i < $n; $i++ ){
			$res->moveToRow($i);
			$pk = $res->getIntByName("pk");
			$m = Message::fromPk($pk);
			echo
				"<p>",
				'<code><b>', date("Y-m-d", $m->time),
				'</b></code>',
				' by ', Html::text($m->current_name) . '<br>',
				'<b>', Html::text($m->subject), '</b><br>';
			$b = $m->body;
			# Remove quoted part:
			$b = preg_replace("/(^|\n)(>[^\n]*\n)+/", " [...] ", $b);
			$b = preg_replace("/[ \n\t]+/", " ", $b);
			$b = Common::short($b, self::ABS_MAX_SIZE);
			echo Html::text($b), "[";
			self::echoAnchorToComments("#$pk", "more...");
			echo ']<p>';
		}

		if( $n >= self::LIMIT ){
			if( Common::getLanguage() === "it" )
				$caption = "altri commenti";
			else
				$caption = "more comments";
			self::echoAnchorToComments("", $caption);
		}
	}

}
