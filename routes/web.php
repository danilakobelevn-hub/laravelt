<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    dump( 'Hello World');
    return view('welcome');
});
