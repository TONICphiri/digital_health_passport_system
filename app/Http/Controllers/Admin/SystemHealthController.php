<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function __invoke(SystemHealthService $health): View
    {
        return view('admin.system-health', [
            'checks' => $health->checks(),
            'totals' => $health->totals(),
        ]);
    }
}
