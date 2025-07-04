<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Su vehículo está listo para recoger</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #28a745;
            color: white;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
        .btn {
            display: inline-block;
            background-color: #28a745;
            color: white !important;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .info {
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>¡Reparación Completada!</h1>
    </div>
    
    <div class="content">
        <p>Estimado/a <strong>{{ $repair->vehicle->client->name }}</strong>,</p>
        
        <p>Nos complace informarle que la reparación de su vehículo <strong>{{ $repair->vehicle->brand->name }} {{ $repair->vehicle->model->name }}</strong> con matrícula <strong>{{ $repair->vehicle->plate_number }}</strong> ha sido completada satisfactoriamente.</p>
        
        <div class="info">
            <p><strong>Tipo de reparación:</strong> {{ $repair->repairType->name }}</p>
            <p><strong>Fecha de inicio:</strong> {{ $repair->started_at->format('d/m/Y') }}</p>
            <p><strong>Fecha de finalización:</strong> {{ $repair->completed_at->format('d/m/Y') }}</p>
            @if($repair->observations)
            <p><strong>Observaciones:</strong> {{ $repair->observations }}</p>
            @endif
        </div>
        
        <p>Su vehículo está listo para recoger en nuestro taller en el horario habitual de atención:</p>
        <p style="text-align: center;"><strong>Lunes a Viernes: 9:00 - 18:00<br>Sábados: 9:00 - 13:00</strong></p>
        
        <p>Puede ver los detalles completos de la reparación a través del siguiente enlace:</p>
        
        <div style="text-align: center;">
            @if($repair->tracking_token)
                <a href="{{ route('repairs.track', $repair->tracking_token) }}" class="btn">Ver detalles de la reparación</a>
            @else
                <p><strong>No está disponible el enlace de seguimiento para esta reparación.</strong></p>
            @endif
        </div>
        
        <p>Agradecemos su confianza en nuestros servicios.</p>
    </div>
    
    <div class="footer">
        <p>Este es un mensaje automático, por favor no responda a este correo.</p>
        <p>&copy; {{ date('Y') }} NTC Car Service. Todos los derechos reservados.</p>
    </div>
</body>
</html> 