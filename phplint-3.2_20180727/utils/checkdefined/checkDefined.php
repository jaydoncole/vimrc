<?php

require_once __DIR__."/../../stdlib/all.php";
use it\icosaedro\utils\Strings;
use it\icosaedro\utils\TestUnit as TU;


/**
 * Sends to stdout a list of require_module statements for all the available
 * modules.
 */
function generateAllRequireModule()
{
	$d = opendir(SRC_BASE_DIR . "/../modules");
	$modules = /*. (string[int]) .*/ array();
	while( is_string($f = readdir($d)) ){
		if( Strings::endsWith($f, ".php") )
			$modules[] = $f;
	}
	sort($modules);
	echo "/*.\n";
	foreach($modules as $module){
		$bare_name = basename($module, ".php");
		echo "\trequire_module '$bare_name';\n";
	}
	echo ".*/\n";
}


/**
 * Generates on stdout a PHP source that refers all the constants defined in the
 * corrent running instance of PHP.
 */
function generateAllConstants()
{
	$cos = get_defined_constants();
	ksort($cos);
	echo "<?php\n";
	generateAllRequireModule();

	foreach($cos as $k => $v){
		echo "if($k === " . TU::dump($v). ") \$unknown_$k = false;\n";
		echo "echo \$unknown_$k; // define(\"$k\", " . TU::dump($v) . ");\n";
	}
}


function filterDefinedConstants($leading)
{
	$cos = get_defined_constants();
	ksort($cos);

	foreach($cos as $k => $v){
		if( Strings::startsWith($k, $leading))
			echo "define('$k', ".TU::dump($v).");\n";
	}
}


/**
 * Generates on stdout a PHP source that refers all the functions defined in the
 * corrent running instance of PHP.
 */
function generateAllFunctions() {
	$funcs = get_defined_functions()['internal'];
	sort($funcs);
//	var_dump($funcs);
	echo "<?php\n";
	generateAllRequireModule();

	foreach($funcs as $func){
		echo "$func();\n";
	}
}


/**
 * Generates on stdout a PHP source that refers all the classes defined in the
 * corrent running instance of PHP.
 */
function generateAllClasses() {
	$classes = get_declared_classes();
	sort($classes);
//	var_dump($classes);
	echo "<?php\n";
	generateAllRequireModule();

	foreach($classes as $c){
		if( Strings::startsWith($c, "it\\icosaedro\\") )
			continue;
		echo "echo $c::class;\n";
	}
}


/**
 * Generates on stdout a PHP source that refers all the classes defined in the
 * corrent running instance of PHP.
 */
function deepClassesIntrospection() {
	$classes = get_declared_interfaces();
//	$classes = get_declared_classes(); // concrete and abstract classes
	sort($classes);
//	var_dump($classes);
	echo "<?php\n";
	generateAllRequireModule();
	echo <<< EOT
	/**
	 * @triggers E_NOTICE
	 * @triggers E_WARNING
	 * @throws Exception
	 */
	function main() {
		\$unknown = f(); // creates unknown value to be used as mandatory param of func
	
EOT;

	foreach($classes as $c){
		// List constants of a specific class:
//		if( $c !== 'SplPriorityQueue' )
//			continue;
//		$r = new ReflectionClass($c);
//		$constants = $r->getConstants();
//		ksort($constants);
//		foreach($constants as $name => $value){
//			echo "$name = ", TU::dump($value), ",\n";
//		}
		
//		// Check class consts existance and value:
//		if( Strings::startsWith($c, "it\\icosaedro\\") )
//			continue;
//		$r = new ReflectionClass($c);
//		
//		// Includes inherited constants:
//		$constants = $r->getConstants();
//		foreach($constants as $name => $value){
//			echo "if($c::$name === " . TU::dump($value). ") \$unknown_$c"."_$name = false;\n";
//			echo "echo \$unknown_$c"."_$name; // const $c::$name = " . TU::dump($value) . ";\n";
//		}

//		// Check class methods existance and value:
		if( Strings::startsWith($c, "it\\icosaedro\\") )
			continue;
		$r = new ReflectionClass($c);
		
		// Create instance variable to access methods:
		$obj = "\$obj_$c";
		// Check if abstract or interface:
		if( $r->isInterface() ){
			// gives error if $c is abstract or concrete under PHPLint module:
			echo "interface __$c extends $c {} // $c is interface XXX\n";
			echo "/*. $c .*/ $obj = NULL;\n";
		} else if( $r->isAbstract() ){
			// gives error if $c is interface or concrete under PHPLint module:
			echo "new $c(); // expected error because $c is abstract YYY\n";
			echo "/*. $c .*/ $obj = NULL;\n";
		} else {
			echo "$obj = new $c(); // $c is concrete ZZZ\n";
		}

		// Includes inherited methods:
		$methods = $r->getMethods();
		foreach($methods as $m){
			if( $m->getName() === "throw" || $m->getName() === "__wakeup" )
				continue;
			
//			$mm = new ReflectionMethod();
			
			if( $m->isPrivate() )
				continue;
				//echo "/* cannot access private method - expected */ ";
			else if( $m->isProtected() )
				echo "/* cannot access protected method - expected */ ";
//			else
//				echo "public ";
//			if( $m->isStatic() )
//				echo "static ";
//			if( $m->isAbstract() )
//				echo "abstract "; // FIXME: interface method is abs?
			$params = "(";
			$mandatory = $m->getNumberOfRequiredParameters();
			if( $mandatory > 0 ){
				$params .= "\$unknown";
				$mandatory--;
				while($mandatory > 0){
					$params .= ", \$unknown";
					$mandatory--;
				}
			}
			$params .= ")";
//			$params = $m->getParameters();
//			foreach($params as $p){
//				if( strlen($s) > 0 )
//					$s .= ", ";
//				$s .= "\$" . $p->getName();
//				if( $p->isDefaultValueAvailable() ){
//					try {
//						$s .= " = " . TestUnit::dump($p->getDefaultValue());
//					}
//					catch(ReflectionException $e){
//						throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
//					}
//				}
//			}
			if($m->isConstructor())
				continue;
			if( $m->isStatic() ){
				echo "$c::", $m->getName(), "$params;\n";
			} else {
				echo "$obj->", $m->getName(), "$params;\n";
			}
		}

	}
	echo "} /* close main() */\n";
}

//generateAllConstants();
//filterDefinedConstants("T_");

//generateAllFunctions();

generateAllClasses();

//deepClassesIntrospection();
