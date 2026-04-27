<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Respuesta enviada al contacto</title>
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
                                Confirmación de respuesta enviada
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <h3 style="margin-top:0;">Se envió una respuesta al contacto</h3>

                            <table width="100%" cellpadding="10" cellspacing="0"
                                style="border-collapse: collapse; font-size:14px; margin-bottom:20px;">
                                <tr style="background:#f9fafb;">
                                    <td style="font-weight:bold; width:180px;">Cliente</td>
                                    <td>{{ $contacto->con_nombre }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold;">Email</td>
                                    <td>{{ $contacto->con_email }}</td>
                                </tr>
                                <tr style="background:#f9fafb;">
                                    <td style="font-weight:bold;">Asunto</td>
                                    <td>{{ $contacto->con_asunto }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold;">Respondido por</td>
                                    <td>{{ $adminUser?->usu_nombre ?? 'Administrador' }}</td>
                                </tr>
                            </table>

                            <h4 style="margin:0 0 12px; color:#111827;">Respuesta enviada:</h4>
                            <div
                                style="margin-bottom:24px; padding:16px; background:#eff6ff; border-left:4px solid #2563eb; border-radius:6px;">
                                <p style="margin:0; white-space:pre-line; line-height:1.7;">
                                    {!! nl2br(e($respuesta->cor_mensaje)) !!}
                                </p>
                            </div>

                            <h4 style="margin:0 0 12px; color:#111827;">Mensaje original:</h4>
                            <div
                                style="padding:16px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px;">
                                <p style="margin:0 0 8px; font-size:13px;"><strong>Asunto:</strong> {{ $contacto->con_asunto }}</p>
                                <p style="margin:0; white-space:pre-line; line-height:1.7;">
                                    {!! nl2br(e($contacto->con_mensaje)) !!}
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f9fafb; padding:16px; font-size:12px; color:#6b7280; text-align:center;">
                            © {{ date('Y') }} Arsite Integradores<br>
                            Registro automático de seguimiento
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
