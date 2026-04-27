<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Respuesta a tu solicitud</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: Arial, sans-serif; color:#1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 24px 0;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="max-width: 720px; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                    <tr>
                        <td style="background:#111827; color:#ffffff; padding:20px;">
                            <h2 style="margin:0;">ARSITE INTEGRADORES</h2>
                            <p style="margin:4px 0 0; font-size:13px; opacity:0.8;">
                                Respuesta a tu solicitud
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <h3 style="margin-top:0;">Hola {{ $contacto->con_nombre }},</h3>

                            <p style="margin-bottom:20px;">
                                Nuestro equipo revisó tu mensaje y te comparte la siguiente respuesta:
                            </p>

                            <div
                                style="margin-bottom:24px; padding:16px; background:#eff6ff; border-left:4px solid #2563eb; border-radius:6px;">
                                <p style="margin:0; white-space:pre-line; line-height:1.7;">
                                    {!! nl2br(e($respuesta->cor_mensaje)) !!}
                                </p>
                            </div>

                            <h4 style="margin:0 0 12px; color:#111827;">Mensaje original:</h4>

                            <div
                                style="padding:16px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; margin-bottom:20px;">
                                <p style="margin:0 0 8px; font-size:13px;"><strong>Asunto:</strong> {{ $contacto->con_asunto }}</p>
                                <p style="margin:0; white-space:pre-line; line-height:1.7;">
                                    {!! nl2br(e($contacto->con_mensaje)) !!}
                                </p>
                            </div>

                            <p style="margin-bottom:0;">
                                Si necesitas más apoyo, puedes responder a este correo y con gusto daremos seguimiento.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f9fafb; padding:16px; font-size:12px; color:#6b7280; text-align:center;">
                            © {{ date('Y') }} Arsite Integradores<br>
                            Equipo de atención a clientes
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
