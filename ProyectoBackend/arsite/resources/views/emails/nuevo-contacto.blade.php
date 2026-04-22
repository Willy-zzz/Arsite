<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo mensaje de contacto</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h1 style="margin-bottom: 16px;">Nuevo mensaje desde la web</h1>

    <p>Se recibió un nuevo mensaje de contacto desde el formulario público.</p>

    <table cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; width: 100%; max-width: 720px;">
        <tr>
            <td style="font-weight: bold; width: 180px;">Nombre</td>
            <td>{{ $contacto->con_nombre }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Email</td>
            <td>{{ $contacto->con_email }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Teléfono</td>
            <td>{{ $contacto->con_telefono }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Empresa</td>
            <td>{{ $contacto->con_empresa ?: 'No especificada' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Asunto</td>
            <td>{{ $contacto->con_asunto }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">IP</td>
            <td>{{ $contacto->con_ip ?: 'No disponible' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; vertical-align: top;">Mensaje</td>
            <td>{!! nl2br(e($contacto->con_mensaje)) !!}</td>
        </tr>
    </table>
</body>
</html>
