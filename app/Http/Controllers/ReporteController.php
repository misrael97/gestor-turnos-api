<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turno;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReporteController extends Controller
{
    // 📅 Reporte: cantidad de turnos atendidos por día
    public function turnosPorDia()
    {
        $datos = Turno::selectRaw('DATE(hora_inicio) as fecha, COUNT(*) as total')
            ->where('estado', 'atendido')
            ->groupBy('fecha')
            ->get();

        return response()->json($datos);
    }

    // ⏱️ Reporte: tiempo promedio de espera
    public function tiempoPromedioEspera()
    {
        $turnos = Turno::whereNotNull('hora_inicio')->whereNotNull('hora_fin')->get();
        $promedio = $turnos->map(function ($t) {
            return Carbon::parse($t->hora_inicio)->diffInMinutes(Carbon::parse($t->hora_fin));
        })->avg();

        return response()->json(['promedio_minutos' => round($promedio, 2)]);
    }

    // 📈 Productividad de agentes
    public function productividadAgentes()
    {
        $data = Turno::selectRaw('usuario_id, COUNT(*) as total')
            ->where('estado', 'atendido')
            ->groupBy('usuario_id')
            ->with('usuario')
            ->get();

        return response()->json($data);
    }

    // 📄 Exportar reporte PDF
    public function exportarPDF($tipo)
    {
        switch ($tipo) {
            case 'dia':
                $datos = $this->turnosPorDia()->original;
                $titulo = 'Turnos por día';
                break;
            case 'espera':
                $datos = $this->tiempoPromedioEspera()->original;
                $titulo = 'Tiempo promedio de espera';
                break;
            default:
                $datos = [];
                $titulo = 'Reporte vacío';
        }

        $pdf = Pdf::loadView('reportes.base', compact('datos', 'titulo'));
        return $pdf->download("reporte_{$tipo}.pdf");
    }

    // 📊 Reportes globales para dashboard admin
    public function reportesGlobales()
    {
        return response()->json([
            'total_turnos' => Turno::count(),
            'turnos_pendientes' => Turno::where('estado', 'pendiente')->count(),
            'turnos_atendidos' => Turno::where('estado', 'atendido')->count(),
            'turnos_cancelados' => Turno::where('estado', 'cancelado')->count(),
            'promedio_espera' => $this->calcularPromedioEspera(),
            'turnos_hoy' => Turno::whereDate('created_at', today())->count(),
        ]);
    }

    private function calcularPromedioEspera()
    {
        $turnos = Turno::whereNotNull('hora_inicio')->whereNotNull('hora_fin')->get();
        if ($turnos->isEmpty()) return 0;
        
        $promedio = $turnos->map(function ($t) {
            return Carbon::parse($t->hora_inicio)->diffInMinutes(Carbon::parse($t->hora_fin));
        })->avg();

        return round($promedio, 2);
    }
}
