<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PujaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });



Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'authenticate']);

Route::get('categorias', [CategoriaController::class, 'index']);
Route::get('categorias/{id}/productos', [CategoriaController::class, 'productos']);

Route::get('productos/{id}', [ProductoController::class, 'getProducto']);


Route::group(['middleware' => ['jwt.verify']], function() {
    //Todo lo que este dentro de este grupo requiere verificación de usuario.
        Route::post('logout', [AuthController::class, 'logout']); 
        Route::post('usuario', [AuthController::class, 'getUser']);


        Route::post('categorias', [CategoriaController::class, 'store']);
        Route::delete('categorias/{id}', [CategoriaController::class, 'delete']);
        Route::put('categorias/{id}', [CategoriaController::class, 'update']);


        Route::put('productos/{id}', [ProductoController::class, 'update']);
        Route::delete('productos/{id}', [ProductoController::class, 'delete']);
        Route::post('productos', [ProductoController::class, 'store']);

        //pujas
        Route::post('pujas/productos/{id}', [PujaController::class, 'store']);
        Route::get('pujas/usuarios', [PujaController::class, 'getPujasUsuario']);
        Route::get('pujas/productos', [PujaController::class, 'getProductosUltimaPuja']);
        Route::get('pujas/productos/{id}/{num?}', [PujaController::class, 'getNumPujas']);


});


