<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JWTAuth;
use App\Models\Producto;
use App\Models\User;
use App\Models\Puja;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;



class PujaController extends Controller {
    
    protected $user;
    public function __construct(Request $request){

        $token = $request->header('Authorization');
        if($token != ''){
        
            $this->user = JWTAuth::parseToken()->authenticate();
        }
    }


    // Listado de los productos con la última puja. En este listado también se tiene
    // que devolver el JSON (una nueva clave) indicando si la puja está cerrada, no
    // se puede pujar más, o está abierta, aún se puede pujar


    public function getProductosUltimaPuja(){

        $grupo = Puja::all()->groupBy('producto_id');
       
        foreach($grupo as $pujas){
            $maximas[] = $pujas->last();
        }
        if(count($maximas) > 0){
            foreach($maximas as $puja){
                $producto_id = $puja->producto_id;
                $producto = Producto::find($producto_id);
                $pujas_producto = $producto->pujas()->count();
                $estado = $producto->limite_pujas > $pujas_producto ? 'abierta': 'cerrada';
                $puja->estado = $estado;
            }
            $mensaje = $maximas;
            $codigo = 200;
        }
        else {
            $mensaje =["mensaje" => "Sin pujas"];
            $codigo = 200;
        } 
        return response()->json($mensaje, $codigo);
    }   


    // Listado de pujas de un determinado usuario. Este listado sólo se podrá
    // mostrar si el usuario del que se quieren mostrar las pujas coincide con el que
    // está logueado actualmente.

    /**
     * Muestra las pujas del usuario logado
     * o un mensaje informando que no existen pujas activas
     */
    public function getPujasUsuario(){

        $mensaje = $this->user->pujas;
        $mensaje = $mensaje->count() > 0 ? $mensaje : "El usuario no tiene pujas activas";
        return response()->json($mensaje, 200);
    }
    


// Listado de pujas de un determinado producto de la más reciente a la más
// antigua. La petición tendrá un parámetro opcional que nos permita obtener
// un número determinado de pujas de ese producto.

/**
 * Parámetros => $id es el producto del que se desean saber las pujas
 *               $num es el número de pujas cuya información se requiere.
 *                    Por defecto toma un valor null lo que devolverá todas
 *                    las pujas asociadas al poducto.
 * Si no existe el product, un ModelNotFoundExcepction maneja el resultado.
 * Si los datos son adecuados, se busca el productos y sus pujas y se ordenan.
 * 
 */
    public function getNumPujas($id, $num = null){

        $data = [$id, $num];
        $validator = Validator::make($data, [ 
            $id => 'required|numeric',
            $num => 'nullable'
        ]);

        if($validator->fails()){
            $mensaje = $validator -> errors();
            $codigo = 400;
        }
        else{
            try{
                $pujas = Producto::findOrFail($id)
                ->pujas  //ME LO EXPLIQUEN
                ->sortByDesc('created_at')
                ->take($num);
                if($pujas->count() > 0){
                    $mensaje = $pujas;
                    $codigo = 200;
                }
                else{
                    $mensaje = ["mensaje" => "No hay pujas en ese producto"];
                    $codigo = 200;
                }
            }
            catch(ModelNotFoundException $e){
                $mensaje = "No existe ese producto";
                $codigo = 404;
            }
        }
        return response()->json($mensaje, $codigo);
    }

    /** 
     * Para realizar (insertar) una puja:
     * 0-> se valida el formato de la puja
     * 1 -> se comprueba que el producto existe.
     * Si no existe un ModelNotFoundException informa de ello
     * 2 -> se comprueba que el dueño del  producto no es el
     * usuario que intenta pujar por el. Si es el  mismo se informa (Puja no admitida...)
     * 3 -> Comprobamos que la puja esté abierta contando todas las pujas
     *  de ese producto y restándoselas al límite de pujas del producto
     * 4 -> Comprobamos que el usuario pueda pujar (10% del límite de pujas)
     * 5-> Comporbamos que la puja supera a la puja actual
     * 6->Si todo se cumple insertamos  la puja
     */
    public function store(Request $request, $id){

        $validator = Validator::make($request->all(),[
            'actual' => 'required|numeric',
            'producto_id' =>'required'
        ]);
        if($validator->fails()){
            $mensaje = $validator->errors();
            $codigo = 400;
        }
        else {
            try{
                $producto = Producto::findOrFail($request->producto_id);
                $subastador = $producto->user;
                if($id != $subastador->id){
             
                    $pujas = $producto
                                ->pujas()
                                ->count();
                    $pujas_disponibles = $producto->limite_pujas - $pujas;

                    if($pujas_disponibles > 0){

                        $pujador = User::find($id);
                        $pujas_permitidas = $producto->limite_pujas*0.10;
                        $pujas_pujador = $pujador->pujas()->where('producto_id', $request->producto_id)->count();
                   
                        if($pujas_permitidas > $pujas_pujador){

                            $ultima_puja = $producto->pujas()->max('actual');

                            if($request->actual > $ultima_puja){

                                $nueva_puja = new Puja;
                                $nueva_puja-> user_id = $id;
                                $nueva_puja-> producto_id = $request->producto_id;
                                $nueva_puja-> actual = $request->actual;
                                $nueva_puja->save();

                                $mensaje = $nueva_puja;
                                $codigo = 200;
                            
                            }
                            else{
                                $mensaje = ['mensaje' => "Debe superar la última puja -> ". $ultima_puja];
                                $codigo = 406;
                            }
                        }
                        else{
                            $mensaje = ['mensaje' => "Ha alcanzado el 10% del total de pujas"];
                            $codigo = 406;
                        }
                    }
                    else{
                        $mensaje = ['mensaje' => "El producto no admite más pujas"];
                        $codigo = 406;
                    }
                }
                else{
                    $mensaje = ['mensaje' => "Puja no admitida. El producto ya es suyo"];
                    $codigo = 400;
                }
            }
            catch(ModelNotFoundException $e){
                    $mensaje = ['mensaje' => "No existe el producto"];
                    $codigo = 404;
            }
        }
        return response()->json($mensaje, $codigo);
    }
    
}
