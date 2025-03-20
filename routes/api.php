<?php

use App\Http\Controllers\Api\CreatePermissionRolController;
use App\Http\Controllers\Api\CreatePrimissionRolController;
use App\Http\Controllers\Api\MntPedidosController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CtlCategoriaController;
use App\Http\Controllers\Api\CtlProductosController;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh-token', [AuthController::class, 'refresh']);
    Route::post('register', [AuthController::class, 'register']);
});

Route::middleware('auth:api')->prefix('usuarios')->group(function () {
    Route::get('/rol', [CreatePermissionRolController::class, 'getRole'])->middleware('rol:Super Admin');
    Route::post('/permisos', [CreatePermissionRolController::class, 'createPermissionsAction'])->middleware('rol:Super Admin,Admin');
    Route::post('/rol', [CreatePermissionRolController::class, 'store'])->middleware('rol:Super Admin');
});

Route::middleware('auth:api')->group(function () {
    Route::get('/admin-dashboard', function () {
        return response()->json(['message' => 'Bienvenido al panel de administración']);
    })->middleware('rol:Admin,Super Admin');
});

Route::middleware('auth:api')->prefix('usuarios')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:api')->prefix('administracion')->group(function () {
    Route::prefix('categoria')->group(function () {
        Route::post('/', [CtlCategoriaController::class, 'store'])->middleware('rol:Admin');
        Route::put('/{id}', [CtlCategoriaController::class, 'update'])->middleware('rol:Admin');
        Route::patch('/{id}', [CtlCategoriaController::class, 'deleteCategoria'])->middleware('rol:Admin');
    });
    Route::prefix('productos')->group(function () {
        Route::post('/', [CtlProductosController::class, 'store'])->middleware('rol:Admin');
        Route::put('/inventario/{id}', [CtlProductosController::class, 'updateInventario'])->middleware('rol:Admin');
        Route::patch('/{id}', [CtlProductosController::class, 'deleteProducto'])->middleware('rol:Admin');
    });
});

Route::prefix('catalogo')->group(function () {
    Route::get('categoria', [CtlCategoriaController::class, 'index']);
    Route::get('/productos', [CtlProductosController::class, 'index']);
});

Route::prefix('ordenes')->group(function () {
    Route::get('/', [MntPedidosController::class, 'listarPedidos']);
    Route::post('/', [MntPedidosController::class, 'guardarPedido']);
});
