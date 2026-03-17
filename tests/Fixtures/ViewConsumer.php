<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\View;

class ViewConsumer
{
    public function index()
    {
        return view('welcome');
    }

    public function show()
    {
        return View::make('layouts.app');
    }

    public function fallback()
    {
        return View::first(['custom.page', 'default.page']);
    }
}
