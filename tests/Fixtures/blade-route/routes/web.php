<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/about', 'about', ['key' => 'value'])->name('about');
