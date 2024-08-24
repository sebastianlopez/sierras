<?php

use App\Http\Controllers\integrateController;
use Illuminate\Support\Facades\Route;

Route::get('/test', [integrateController::class, 'test']);

//newquote
Route::post('/newquote', [integrateController::class, 'createPotential']);

Route::get('/allproducts', [integrateController::class, 'loadallProducts']);
Route::get('/allcompanies', [integrateController::class, 'companiestoCrmAll']);

Route::post('/company-update', [integrateController::class, 'updateCompany']);
Route::post('/contact-update', [integrateController::class, 'updateContact']);

Route::post('/update-qoute',[integrateController::class,'updateQuote']);