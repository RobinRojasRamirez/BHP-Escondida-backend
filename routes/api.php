<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\InstructionsController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('verificar-codigo', [AuthController::class, 'verifyCode']);

Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('users/table', [UsersController::class, 'getUsersTable']);
    Route::get('lists-roles', [UsersController::class, 'getListsRoles']);
    Route::post('save-user', [UsersController::class, 'saveUser']);
    Route::get('users/{id}', [UsersController::class, 'getUserById']);
    Route::delete('/users/{id}', [UsersController::class, 'deleteUser']);

    Route::post('instructions/table', [InstructionsController::class, 'getInstructionsTable']);
    Route::post('save-instruction', [InstructionsController::class, 'saveInstruction']);
    Route::get('instruction/{id}', [InstructionsController::class, 'getInstructionById']);
    Route::delete('/instruction/{id}', [InstructionsController::class, 'deleteInstruction']);

});

Route::middleware('auth:api')->prefix('dashboard')->group(function () {
    
});