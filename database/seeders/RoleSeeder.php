<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'nombre' => 'Administrador'],  // Super Admin - Gestiona panel admin
            ['id' => 2, 'nombre' => 'Agente'],         // Admin de Sucursal - Gestiona panel sucursal
            ['id' => 3, 'nombre' => 'Cliente'],        // Cliente - Solicita turnos en PWA
            ['id' => 4, 'nombre' => 'Empleado'],       // Empleado - Atiende turnos en PWA
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['id' => $role['id']], $role);
        }
    }
}
