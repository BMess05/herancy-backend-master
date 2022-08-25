<?php

namespace App\Http\Controllers;
/**
 * @OA\Info(
 *    title="Harency ApplicationAPI",
 *    version="1.0.0",
 * )
 */
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
    public function index()
    {
        return view('dashboard');
    }
}
