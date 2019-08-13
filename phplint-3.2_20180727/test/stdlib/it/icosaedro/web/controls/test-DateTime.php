<?php

namespace it\icosaedro\web\controls;

use it\icosaedro\utils\TestUnit as TU;
use it\icosaedro\utils\DateTimeTZ as DTZ;

require_once __DIR__ . "/../../../../../../stdlib/all.php";

$ctrl = new DateTime(NULL, "x");
$min = DTZ::parse("2000-01-01T00:00:00.000+00:00");
$max = DTZ::parse("2099-12-31T23:59:59.999+00:00");
$ctrl->setMinMaxStep($min, $max, 1);

// Setting date below min gives min:
$ctrl->setDateTimeTZ(DTZ::parse("1999-12-01T00:00:00.000+00:00"));
TU::test($ctrl->parse(), $min);

// Setting date above max gives max:
$ctrl->setDateTimeTZ(DTZ::parse("2100-01-01T00:00:00.000+00:00"));
TU::test($ctrl->parse(), $max);

// With 1 ms step, any date allowed between min,max:
$d = "2000-01-01T00:00:00.000+00:00";
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $d);

$d = "2000-01-01T00:00:00.001+00:00";
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $d);

$d = "2000-01-01T00:00:00.002+00:00";
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $d);

$d = "2000-01-01T00:00:00.999+00:00";
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $d);

$d = "2000-01-01T00:00:01.000+00:00";
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $d);

$d = "2000-01-01T00:00:59.999+00:00";
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $d);

$d = "2099-12-31T23:59:59.999+00:00";
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $d);

$d = "2099-12-31T23:59:59.998+00:00";
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $d);

$d = "2099-12-31T23:59:00.000+00:00";
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $d);

// With step 1 seconds same range, check adjust to nearest step:
$ctrl->setMinMaxStep($min, $max, 1000);

$d = "2000-01-01T00:00:00.499+00:00"; // value set
$a = "2000-01-01T00:00:00.000+00:00"; // expected adjusted value
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $a);

$d = "2000-01-01T00:00:00.500+00:00"; // value set
$a = "2000-01-01T00:00:01.000+00:00"; // expected adjusted value
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $a);

// With step 15 min same range, check adjust to nearest step:
$ctrl->setMinMaxStep($min, $max, 15*60*1000);

$d = "2000-01-01T00:07:29.999+00:00"; // value set
$a = "2000-01-01T00:00:00.000+00:00"; // expected adjusted value
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $a);

$d = "2000-01-01T00:07:30.000+00:00"; // value set
$a = "2000-01-01T00:15:00.000+00:00"; // expected adjusted value
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test("".$ctrl->parse(), $a);

$d = "2018-01-01T00:37:29.999-02:00"; // value set
$a = "2018-01-01T00:30:00.000-02:00"; // expected adjusted value
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test($ctrl->parse(), DTZ::parse($a));

$d = "2018-01-01T00:37:30.000-02:00"; // value set
$a = "2018-01-01T00:45:00.000-02:00"; // expected adjusted value
$ctrl->setDateTimeTZ(DTZ::parse($d));
TU::test($ctrl->parse(), DTZ::parse($a));
