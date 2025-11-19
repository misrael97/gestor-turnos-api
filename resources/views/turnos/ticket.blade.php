<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket Turno #{{ $turno->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .ticket {
            border: 2px solid #000;
            padding: 20px;
            max-width: 400px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .turno-numero {
            font-size: 48px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .info {
            margin: 10px 0;
        }
        .qr {
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>{{ $turno->negocio->nombre }}</h1>
            <p>{{ $turno->negocio->direccion }}</p>
        </div>

        <div class="turno-numero">
            TURNO #{{ $turno->id }}
        </div>

        <div class="info">
            <strong>Cliente:</strong> {{ $turno->usuario->name }}
        </div>
        <div class="info">
            <strong>Fecha:</strong> {{ $turno->created_at->format('d/m/Y H:i') }}
        </div>
        <div class="info">
            <strong>Estado:</strong> {{ ucfirst($turno->estado) }}
        </div>

        <div class="qr">
            <p>Código QR para validación:</p>
            <p style="font-size: 12px;">ID: {{ $turno->id }}</p>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 12px;">
            <p>Presente este ticket al ser llamado</p>
        </div>
    </div>
</body>
</html>
