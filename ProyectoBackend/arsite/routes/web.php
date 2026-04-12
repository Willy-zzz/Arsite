<?php

use Illuminate\Support\Facades\Route;

// Ruta principal (la que ya tenías)
Route::get('/', function () {
    return view('welcome');
});