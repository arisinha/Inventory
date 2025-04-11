<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = env('PRODUCT_API_URL');
    }

    public function index()
    {
        $response = Http::get($this->apiBaseUrl);

        if ($response->successful()) {
            // Mapea los productos para que usen las claves 'nombre' y 'precio' en lugar de 'name' y 'price'
            $products = collect($response->json())->map(function ($product) {
                return [
                    'id' => $product['id'],
                    'nombre' => $product['name'], // 'name' se convierte en 'nombre'
                    'precio' => $product['price'], // 'price' se convierte en 'precio'
                ];
            });

            return Inertia::render('Products/index', [
                'products' => $products
            ]);
        }

        return back()->withErrors('No se pudieron obtener los productos.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validación básica
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0.01',
        ], [
            'nombre.required' => 'El nombre del producto es obligatorio',
            'precio.required' => 'El precio es obligatorio',
            'precio.numeric' => 'El precio debe ser un número',
            'precio.min' => 'El precio debe ser mayor que cero',
        ]);

        $payload = [
            'name' => $request->input('nombre'),
            'price' => (float)$request->input('precio'), // Asegurar que se envía como número
        ];

        $response = Http::post($this->apiBaseUrl, $payload);

        if ($response->successful()) {
            return redirect()->route('products.index')->with('success', 'Producto creado correctamente');
        }

        return back()->withErrors('Error al crear el producto');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Log para debugging
        Log::info('Solicitud de actualización recibida', [
            'id' => $id,
            'datos' => $request->all()
        ]);

        // Validación básica
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0.01',
        ], [
            'nombre.required' => 'El nombre del producto es obligatorio',
            'precio.required' => 'El precio es obligatorio',
            'precio.numeric' => 'El precio debe ser un número',
            'precio.min' => 'El precio debe ser mayor que cero',
        ]);

        // Convertir los campos de español a inglés para la API
        $payload = [
            'id' => (int)$id, // Añadir el ID al payload y asegurar que sea entero
            'name' => $request->input('nombre'),
            'price' => (float)$request->input('precio'), // Convertir explícitamente a float
        ];

        // Log del payload que vamos a enviar
        Log::info('Enviando datos a API', [
            'url' => "{$this->apiBaseUrl}/{$id}",
            'payload' => $payload
        ]);

        // Intentar primero con PUT
        $response = Http::acceptJson()
            ->contentType('application/json')
            ->put("{$this->apiBaseUrl}/{$id}", $payload);

        // Si PUT falla, intentar con método PATCH (algunas APIs lo prefieren)
        if (!$response->successful() && $response->status() >= 400) {
            Log::info('PUT falló, intentando con PATCH', ['status' => $response->status()]);
            $response = Http::acceptJson()
                ->contentType('application/json')
                ->patch("{$this->apiBaseUrl}/{$id}", $payload);
        }

        // Registrar la respuesta para debugging
        Log::info('Respuesta de la API', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if ($response->successful()) {
            return redirect()->route('products.index')->with('success', 'Producto actualizado correctamente');
        }

        // Devolver mensaje de error con más detalles
        $errorMsg = 'Error al actualizar el producto. Código: ' . $response->status();
        return back()->withErrors($errorMsg);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $response = Http::delete("{$this->apiBaseUrl}/{$id}");

        if ($response->successful()) {
            return redirect()->route('products.index')->with('success', 'Producto eliminado correctamente');
        }

        return back()->withErrors('Error al eliminar el producto');
    }
}
