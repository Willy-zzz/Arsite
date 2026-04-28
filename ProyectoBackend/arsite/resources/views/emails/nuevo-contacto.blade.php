<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nuevo mensaje de contacto</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: Arial, sans-serif; color:#1f2937;">

    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 24px 0;">
        <tr>
            <td align="center">

                <!-- CONTENEDOR -->
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="max-width: 720px; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.05);">

                    <!-- HEADER -->
                    <tr>
                        <td style="background:#111827; color:#ffffff; padding:20px;">
                            <h2 style="margin:0;">ARSITE INTEGRADORES</h2>
                            <p style="margin:4px 0 0; font-size:13px; opacity:0.8;">
                                Notificación de nuevo contacto
                            </p>
                        </td>
                    </tr>

                    <!-- BODY -->
                    <tr>
                        <td style="padding:24px;">
                            <h3 style="margin-top:0;">Nuevo mensaje recibido</h3>

                            <p style="margin-bottom:20px;">
                                Se ha registrado un nuevo mensaje desde el formulario público del sitio web.
                            </p>

                            <!-- TABLA -->
                            <table width="100%" cellpadding="10" cellspacing="0"
                                style="border-collapse: collapse; font-size:14px;">
                                <tr style="background:#f9fafb;">
                                    <td style="font-weight:bold; width:180px;">Nombre</td>
                                    <td>{{ $contacto->con_nombre ?: 'No proporcionado' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold;">Email</td>
                                    <td>{{ $contacto->con_email }}</td>
                                </tr>
                                <tr style="background:#f9fafb;">
                                    <td style="font-weight:bold;">Teléfono</td>
                                    <td>{{ $contacto->con_telefono ?: 'No proporcionado' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold;">Empresa</td>
                                    <td>{{ $contacto->con_empresa ?: 'No especificada' }}</td>
                                </tr>
                                <tr style="background:#f9fafb;">
                                    <td style="font-weight:bold;">Asunto</td>
                                    <td>{{ $contacto->con_asunto }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold;">IP</td>
                                    <td>{{ $contacto->con_ip ?: 'No disponible' }}</td>
                                </tr>
                                <tr style="background:#f9fafb;">
                                    <td style="font-weight:bold; vertical-align: top;">Mensaje</td>
                                    <td style="white-space: pre-line;">
                                        {!! nl2br(e($contacto->con_mensaje)) !!}
                                    </td>
                                </tr>
                            </table>

                            <!-- ALERTA -->
                            <div
                                style="margin-top:20px; padding:12px; background:#fef3c7; border-radius:6px; font-size:13px;">
                                ⚠️ Este mensaje proviene del formulario público. Verificar contenido antes de responder.
                            </div>
                            <div
                                style="margin-top:20px; padding:12px; background:#ff0000; color: #FFFFFF; border-radius:6px; font-size:13px;">
                                ⚠️ Responder en el CMS para mantener un registro centralizado de las comunicaciones.
                            </div>

                            <!-- BOTONES -->
                            <div style="margin-top:24px; text-align:center;">

                                <!-- VER EN CMS -->
                                <!-- <a href="{{ config('app.url') }}/contactos/{{ $contacto->con_id }}" -->
                                <a href="http://localhost:5173/contact"
                                    style="display:inline-block; margin:6px; padding:12px 18px; background:#2563eb; color:#ffffff; text-decoration:none; border-radius:6px; font-size:14px;">
                                    Ver en CMS
                                </a>

                                <!-- RESPONDER -->
                                <!-- <a href="mailto:{{ $contacto->con_email }}?subject=Re: {{ urlencode($contacto->con_asunto) }}"
                                    style="display:inline-block; margin:6px; padding:12px 18px; background:#10b981; color:#ffffff; text-decoration:none; border-radius:6px; font-size:14px;">
                                    Responder cliente
                                </a> -->

                            </div>

                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background:#f9fafb; padding:16px; font-size:12px; color:#6b7280; text-align:center;">
                            © {{ date('Y') }} Arsite Integradores<br>
                            Sistema automático de notificaciones
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>