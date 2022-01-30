<?php

namespace App\Http\Controllers;
use App\Models\Categoria;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;


class CategoriaController extends Controller{

    /**
     * Retorna todos los productos de una categoría.
     * Si no existe la categoría informa de ello y si
     * existe pero no hay productos asociados también
     * informa
     */
    public function productos($id){

        try{
            $productos = Categoria::findOrFail($id)->productos;
            $mensaje = $productos->isEmpty() ? ['mensaje' => 'Categoría sin productos']: $productos;
            $codigo = 200;
        }
        catch(ModelNotFoundException $e){
            $mensaje = ['mensaje' => 'No existe esa categoría'];
            $codigo = 404;
        }
        return response()->json($mensaje, $codigo);
    }



    /**
     * Devolución del listado de categorías
     */
    public function index(){

        $categorias = Categoria::all();
        $mensaje  = $categorias->isEmpty() ? ['mensaje'=> 'No hay categorías']: $categorias;
        return response()->json($mensaje, 200);

    }

    /** 
     * El método valida en primera instancia lo que llega 
     * en el request. Si no es válido, devuelve el error y si
     * lo es, busca en la tabla categorías si ya existe esa categoría.
     * Si no existe la crea y si existe informa de ello
     */
    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'required'
        
        ]);
        if($validator->fails()){
            $mensaje = ['error' => $validator->errors()];
            $codigo = 400;
        }
        else {
            $categoria = Categoria::where('name', $request->name)->get();
       
            if($categoria->isEmpty()){
                $validated = $validator->validated();
                $categoria = Categoria::create($validated);
                $mensaje = $categoria;
                $codigo = 200 ;
            }
            else{
                $mensaje = ['error' => "Ya existe esa categoría"];
                $codigo = 422;
            }
        }
        return response()->json($mensaje, $codigo);
    }


    /**
     * LA función intenta borrar una categoría. Si el id recibido
     * por parámetro uan excepción ModelNotFoundException informa
     * de ello. Si la categoría tiene productos asociados, un QueryException
     * informa de ello
     */
    public function delete($id){

        try{
            $categoria = Categoria::findOrFail($id);
            $categoria->delete();
            $mensaje = ['mensaje'=> "Categoría eliminada"];
            $codigo = 200;
        }
        catch(ModelNotFoundException $e) {
            $mensaje = ['mensaje' =>"No existe ese id de categoría."];
            $codigo = 404;
        }
        catch(QueryException $e) {
            $mensaje = ['mensaje' => "No puede borrar una categoría que tiene productos asociados"];
            $codigo = 400;
        }
        return response()->json($mensaje, $codigo);
    }

    /**
     * La función busca el id de la  categoría que se desea actualizar.
     * Si no existe, una excepcion ModelNotFoundException informa de ello
     * Si existe y se la quiere nombrar con un nombre de otra categoría,
     * un QueryException informa de ello (he seteado que los nombres de 
     * categoria sean unique) Si no,  se actualiza
     */
    public function update(Request $request, $id){

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'required'
        
        ]);
        if($validator->fails()){
            $mensaje = ['error' => $validator->errors()];
            $codigo = 400;
        }
        else{
            try {
                $categoria = Categoria::findOrFail($id);
                $categoria->update(['name' => $request->name,
                                    'description'=> $request->description]);
                $mensaje  = $categoria;
                $codigo = 200;

            }
            catch(ModelNotFoundException $e){
                $mensaje  = ['mensaje' => "No existe ese id de categoría."];
                $codigo = 404;

            }
            catch(QueryException $e) {
                $mensaje  = ['mensaje' => "No puede usar ese nombre. La categoría '".$request->name."' ya existe"];
                $codigo = 400;

            }
        }
        return response()->json($mensaje, $codigo);
    }

}


