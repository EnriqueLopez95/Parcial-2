<?php  

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePedidoRequest;
use App\Http\Response\ApiResponse;
use App\Models\MntDetallePedidos;
use App\Models\MntPedidos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MntPedidosController extends Controller
{
    /**
     * Método para listar los pedidos del usuario autenticado.
     */
    public function listarPedidos(Request $solicitud)
    {
        try {
            $usuarioId = Auth::id(); // Obtener el ID del usuario autenticado
            
            // Filtrar solo los pedidos del usuario actual y aplicar filtros si se especifican
            $ordenes = MntPedidos::where('client_id', $usuarioId)
                ->when($solicitud->filled('categoria') || $solicitud->filled('producto'), function ($consulta) use ($solicitud) {
                    $consulta->whereHas('detallePedido.producto', function ($q) use ($solicitud) {
                        if ($solicitud->filled('categoria')) {
                            $q->where('categoria_id', $solicitud->categoria);
                        }
                        if ($solicitud->filled('producto')) {
                            $q->where('id', $solicitud->producto);
                        }
                    });
                })
                ->with(['detallePedido.producto' => function ($consulta) {
                    $consulta->select('id', 'nombre', 'categoria_id'); // Solo traer datos necesarios
                }])
                ->paginate(10); // Paginación de 10 pedidos por respuesta

            // Ocultar atributos innecesarios antes de devolver la respuesta
            $ordenes->each(function ($orden) {
                $orden->makeHidden(['created_at', 'updated_at']);
                $orden->detallePedido->each(function ($detalleItem) {
                    $detalleItem->makeHidden(['created_at', 'updated_at']);
                    $detalleItem->producto->makeHidden(['created_at', 'updated_at', 'activo']);
                });
            });

            return ApiResponse::success('Órdenes', 200, $ordenes);
        } catch (\Exception $e) {
            return ApiResponse::error('Error al traer las órdenes: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Método para guardar un nuevo pedido.
     */
    public function guardarPedido(Request $solicitud)
    {
        // Validación de la solicitud
        $validador = Validator::make($solicitud->all(), [
            "fecha_pedido" => "required|date",
            "client_id" => "required|exists:mnt_clientes,id", // Cliente debe existir en la BD
            "detalle" => "required|array|min:1", // Debe haber al menos un producto en el pedido
            "detalle.*.product_id" => "required|exists:ctl_productos,id",
            "detalle.*.precio" => "required|numeric|min:0",
            "detalle.*.cantidad" => "required|numeric|min:1",
        ], [
            "fecha_pedido.required" => "La fecha de pedido es obligatoria",
            "fecha_pedido.date" => "La fecha debe ser válida",
            "client_id.required" => "El cliente es requerido",
            "client_id.exists" => "El cliente debe estar registrado",
            "detalle.required" => "El pedido debe incluir al menos un producto",
            "detalle.*.product_id.required" => "El producto es obligatorio",
            "detalle.*.product_id.exists" => "Seleccione un producto existente",
            "detalle.*.cantidad.required" => "La cantidad es obligatoria",
            "detalle.*.cantidad.numeric" => "La cantidad debe ser un número",
            "detalle.*.precio.required" => "El precio es obligatorio",
            "detalle.*.precio.numeric" => "El precio debe ser un número"
        ]);

        // Si la validación falla, se devuelve un error
        if ($validador->fails()) {
            return response()->json(["errors" => $validador->errors()], 422);
        }

        try {
            DB::beginTransaction(); // Iniciar transacción para evitar inconsistencias en la BD

            // Crear el pedido inicial con total en 0 (se actualizará después)
            $orden = MntPedidos::create([
                'fecha_pedido' => $solicitud->fecha_pedido,
                'client_id' => $solicitud->client_id,
                'total' => 0
            ]);

            $totalFinal = 0;
            $itemsPedido = [];

            // Recorrer los productos del pedido y calcular el total
            foreach ($solicitud->detalle as $item) {
                $subTotal = $item['cantidad'] * $item['precio'];
                $itemsPedido[] = [
                    'pedido_id' => $orden->id,
                    'producto_id' => $item['product_id'],
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio'],
                    'sub_total' => $subTotal,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $totalFinal += $subTotal; // Sumar el subtotal al total del pedido
            }

            MntDetallePedidos::insert($itemsPedido); // Inserción masiva optimizada
            $orden->update(['total' => $totalFinal]); // Actualizar el total del pedido

            DB::commit(); // Confirmar transacción

            return ApiResponse::success('Orden creada', 200, $orden);
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir cambios en caso de error
            return ApiResponse::error($e->getMessage(), 422);
        }
    }
}
