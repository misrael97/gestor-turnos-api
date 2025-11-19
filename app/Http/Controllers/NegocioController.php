<?php

namespace App\Http\Controllers;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Http\Request;

class NegocioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {
        return Negocio::with('agente')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'horario' => 'nullable|string|max:100',
            'agente_id' => 'nullable|exists:users,id', // Agente responsable
        ]);

        $negocio = Negocio::create($validated);
        
        // Si se asignó un agente, actualizar su sucursal_id
        if ($validated['agente_id'] ?? null) {
            User::where('id', $validated['agente_id'])->update(['sucursal_id' => $negocio->id]);
        }
        
        $negocio->load('agente');
        return response()->json($negocio, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $negocio = Negocio::with('agente')->findOrFail($id);
        return response()->json($negocio);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Negocio $negocio) {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'horario' => 'nullable|string|max:100',
            'agente_id' => 'nullable|exists:users,id',
        ]);

        // Si cambió el agente
        $agenteAnterior = $negocio->agente_id;
        $agenteNuevo = $validated['agente_id'] ?? null;

        $negocio->update($validated);

        // Actualizar sucursal del agente anterior (quitarla)
        if ($agenteAnterior && $agenteAnterior != $agenteNuevo) {
            User::where('id', $agenteAnterior)->update(['sucursal_id' => null]);
        }

        // Asignar sucursal al nuevo agente
        if ($agenteNuevo) {
            User::where('id', $agenteNuevo)->update(['sucursal_id' => $negocio->id]);
        }

        $negocio->load('agente');
        return response()->json($negocio);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Negocio $negocio) {
        $negocio->delete();
        return response()->json(['message' => 'Negocio eliminado correctamente']);
    }
}
