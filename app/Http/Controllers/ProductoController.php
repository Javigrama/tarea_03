<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use App\Models\Categoria;
use App\Models\Puja;
use App\Models\User;
use JWTAuth;
use Illuminate\Database\Eloquent\Collection;


class ProductoController extends Controller{


    protected $user;
    public function __construct(Request $request){

        $token = $request->header('Authorization');
        if($token != ''){
       
            $this->user = JWTAuth::parseToken()->authenticate();
        }
    }


    // If you do not need to add additional constraints to an Eloquent relationship query,
    //  you may access the relationship as if it were a property.
    /**
     * retorna el producto con id
     *  y los datos de su categoria relacionada
     */
    public function getProducto($id){

        try {
            $producto = Producto::findOrFail($id);
            $producto->categoria; //=>>>>> MELOXPLIQUEN!!!
            $mensaje = $producto;
            // $mensaje = collect($producto);
            // $mensaje = $mensaje->put('cat', $producto->categoria); 
            $codigo = 200;
        }
        catch(ModelNotFoundException $e){
            $mensaje = ["mensaje" => "No existe el producto" ];
            $codigo = 404;
        }
        // return response()->json($mensaje, $codigo);
        return response()->json($mensaje, $codigo);
    }


    /**
     * Para subir un producto se valida. Si no pasa la validación
     * un error informa de ellos. Si se valida, se busca en primer lugar
     * si la categoría a la que pertenece el producto existe. Si no existe
     * se informa de ello y no hay inserción. Si existe, se sube un
     * nuevo producto. No hay restricciones en el sentido de que puede
     * haber prorductos con los mismos atributos, a excepción de la primary
     * y los timeStamp: Además como para subir un producto hay que estar
     * logado, no se comprueba aquí si el id del usuario existe
     */
    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:5|max:100',
            'description' => 'required',
            'precio_salida' => 'gt:0|regex:/^[\d]{0,12}(\.[\d]{2})$/u',
            'limite_pujas' => 'required|gte:10|lte:1000',
            'categoria_id'=> 'required'
         ]);
        if($validator->fails()){
            $mensaje = ['error' => $validator -> errors()];
            $codigo = 400;
        }
        else {
            try{
                Categoria::findOrFail($request->categoria_id);
                $producto = Producto::Create(['name' => $request->name,
                                              'description' => $request->description,
                                              'precio_salida' => $request->precio_salida,
                                              'limite_pujas' => $request->limite_pujas,
                                              'user_id' => $this->user->id,
                                              'categoria_id' => $request->categoria_id]);
                $mensaje = $producto;
                $codigo = 200;
            }
            catch(ModelNotFoundException $e) {
                $mensaje = ["mensaje" => "No existe ese id de categoría. Créela primero"];
                $codigo = 404;
            }         
        }
        return response()->json($mensaje, $codigo);
    }   
    

    /**
     * Primero se valilda el producto. Si pasamos la validación
     * se busca el producto y la categoría. Si existen se comprueba que la persona
     * que trata de actualizar, el logado, es el dueño del producto.
     * Si es así se actualiza
     */
    public function update(Request $request, $id){

        $validator = Validator::make($request->all(),[
            'name' => 'required|string|min:5|max:100',
            'description' => 'required',
            'precio_salida' => 'gt:0|regex:/^[\d]{0,12}(\.[\d]{2})$/u',
            'limite_pujas' => 'required|gte:10|lte:1000',
            'categoria_id' => 'required'
        ]);
    
        if($validator->fails()){
            $mensaje = ['error' => $validator -> errors()];
            $codigo = 400;
        }
        else{
            try{
                $producto = Producto::findOrFail($id);
                $pujas = $producto->pujas()->count();
                $categoria = Categoria::findOrFail($request->categoria_id);
                if($producto->user_id == $this->user->id){
                    if($pujas == 0){
                    
                        $producto->update(['name' => $request->name,
                              'description' => $request->description,
                              'precio_salida' => $request->precio_salida,
                              'limite_pujas' => $request->limite_pujas,
                              'user_id' => $this->user->id,
                              'categoria_id' => $categoria->id]);

                        $mensaje = $producto;
                        $codigo = 200;
                    }
                    else {
                        $mensaje =  ["mensaje" => 'El producto tiene pujas. No se puede modificar'];
                        $codigo = 409;                     
                    }
                }
                else{                 
                    $mensaje =  ["mensaje" => "No autorizado"];
                    $codigo = 401;
                }
            }
            catch(ModelNotFoundException $e) {
                $mensaje = ["mensaje" => 'No existe el id del producto y/o de la categoría'];
                $codigo = 404;
            }
        }
        return response()->json($mensaje, $codigo);
    }

    /**
     * Para borrar un producto, lo buscamos y si existe
     * comprobamos que no tiene pujas y que el login que
     * pretende borrar es el dueño del producto. Si todo
     *  va bien borramos.
     */
    public function delete($id){

        try{
            $producto = Producto::findOrFail($id);
            $pujas = $producto->pujas()->count();
            if($producto->user_id == $this->user->id){
                if($pujas == 0 ){

                    $producto->delete();
                    $mensaje = ["mensaje" => "Producto '".$id."' borrado"];
                    $codigo = 200;
                }
                else {
                    $mensaje =  ["mensaje" => 'El producto tiene pujas. No se puede borrar'];
                    $codigo = 409;
                }
            }
            else{
                $mensaje =  ["mensaje" => "No autorizado. Ese producto no es de usted"];
                $codigo = 401;
            }
        }
        catch(ModelNotFoundException $e){
            $mensaje = ["mensaje" => "No existe el producto"]; 
            $codigo = 404;
        }
        finally{
            return response()->json($mensaje, $codigo);
        }
    }
}


