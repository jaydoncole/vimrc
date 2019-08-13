<?php
// PHP 7 functions and method signature.

class MyClass {
	function m(): self { return $this; }
}

function f0(): void { return; }
function f1(): bool { return FALSE; }
// "boolean" not allowed by PHP.
//function f2(): boolean { return FALSE; }
function f3(): int { return 123; }
// "integer" not allowed by PHP.
//function f4(): integer { return 123; }
function f5(): float { return 0.0; }
// "double" not allowed by PHP.
//function f6(): double { return 0.0; }
// "real" not allowed by PHP.
//function f7(): real { return 0.0; }
function f8(): string { return ""; }
function f9(): array { return []; }
// "object" not allowed by PHP.
//function f10(): object { return new MyClass(); }
function f11(): MyClass { return new MyClass(); }

// PHP only checks returned type at runtime, so lets invoke all functions:
f0();
f1();
//f2();
f3();
//f4();
f5();
//f6();
//f7();
f8();
f9();
//f10();
f11();

(new MyClass())->m();
