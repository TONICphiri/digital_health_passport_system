<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$u = User::where('email', 'admin@healthpassport.mw')->first();
var_dump($u ? true : false);
if ($u) {
    var_dump($u->must_change_password);
    var_dump(Hash::check('Tonicadam1', $u->password));
}
