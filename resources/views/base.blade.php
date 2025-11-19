<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>{{ $titulo }}</title>
  <style>
    body { font-family: Arial, sans-serif; }
    h1 { color: #2563eb; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
  </style>
</head>
<body>
  <h1>{{ $titulo }}</h1>
  <table>
    <thead>
      <tr>
        @foreach(array_keys((array) $datos[0] ?? []) as $col)
          <th>{{ $col }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($datos as $fila)
        <tr>
          @foreach((array)$fila as $valor)
            <td>{{ $valor }}</td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
