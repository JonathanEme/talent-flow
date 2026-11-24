<?php

namespace App\Http\Controllers;

use App\Support\TenantScope;

abstract class Controller
{
    use TenantScope;
}
