<?php
require_once __DIR__ . "/../../../../../stdlib/autoload.php";

use it\icosaedro\bignumbers\ExprEval;
use it\icosaedro\utils\TestUnit as TU;

ini_set("precision", "12");

$v = ExprEval::value("3.14", -2);
TU::test($v->__toString(), "3.14");

$v = ExprEval::value("2*(3 + 4)", -2);
TU::test($v->__toString(), "14");

$v = ExprEval::value("1/3", -5);
TU::test($v->__toString(), "0.33333");

$v = ExprEval::value("2 * (0.57 - 0.56)", -2);
TU::test($v->__toString(), "0.02");

$v = ExprEval::value("1e3 + 1e-3", -2);
TU::test($v->__toString(), "1000.001");
