<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $usuario = Auth::user();
        return view('home', compact('usuario'));
    }
}
