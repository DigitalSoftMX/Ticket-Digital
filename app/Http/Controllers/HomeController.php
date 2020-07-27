<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        /* Roles autorizados para el dashboard */
        $request->user()->authorizeRoles(['admin_master', 'admin_eucomb', 'admin_estacion', 'usuario']);
        return view('dashboard');
    }
}
