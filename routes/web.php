<?php

use Illuminate\Support\Facades\Route;

// Home route - returns the React app view
Route::get('/', function () {
    return 'React should be here';
});

// Catch-all route for React Router - all routes return the React app view
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

