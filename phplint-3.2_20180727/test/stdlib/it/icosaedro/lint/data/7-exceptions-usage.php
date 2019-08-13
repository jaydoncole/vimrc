<?php

/*. require_module 'core'; .*/

error_reporting(-1);

/*
 * Classes in user's code cannot implement directly or indirectly Throwable
 * without also extending Error. Interfaces may "extend" Throwable.
 */

interface if1 extends Throwable{}

class c2 implements if1 {}
// --> PHP Fatal error:  Class c2 cannot implement interface Throwable, extend Exception or Error instead

class c3 implements Throwable {}
// --> PHP Fatal error:  Class c3 cannot implement interface Throwable, extend Exception or Error instead

class c4 extends Error implements if1 {}
// --> ok

class C5 extends Error {}
// ok