<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WorkOrderWebService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly WorkOrderWebService $web) {}

    public function __invoke(Request $request): View
    {
        return view('dashboard', $this->web->dashboard($request->user()));
    }
}
