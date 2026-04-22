<!DOCTYPE html>

<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Confirmación de recepción</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: Arial, sans-serif; color:#1f2937;">

    ```
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 20px;">
        <tr>
            <td align="center">

                <!-- CONTENEDOR -->
                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.05);">

                    <!-- HEADER -->
                    <tr>
                        <td style="background:#111827; padding:20px; text-align:center;">
                            <h2 style="color:#ffffff; margin:0;">ARSITE INTEGRADORES</h2>
                        </td>
                    </tr>

                    <!-- BODY -->
                    <tr>
                        <td style="padding:30px;">

                            <h1 style="margin-top:0; font-size:22px;">Hemos recibido tu mensaje</h1>

                            <p>Hola <strong>{{ $contacto->con_nombre }}</strong>,</p>

                            <p>
                                Gracias por ponerte en contacto con nosotros.
                                Tu solicitud ha sido recibida correctamente y nuestro equipo la estará revisando en
                                breve.
                            </p>

                            <p style="margin:20px 0;">
                                <strong>Asunto:</strong><br>
                                {{ $contacto->con_asunto }}
                            </p>

                            <p>
                                Si tu solicitud es urgente, puedes comunicarte directamente con nosotros a través de
                                nuestros canales habituales.
                            </p>

                            <!-- BOTÓN -->
                            <div style="text-align:center; margin:30px 0;">
                                <a href="https://arsite.com.mx/"
                                    style="background:#2563eb; color:#ffffff; padding:12px 20px; text-decoration:none; border-radius:6px; font-weight:bold;">
                                    Visitar nuestro sitio web
                                </a>
                            </div>

                            <p>Saludos cordiales,<br>
                                <strong>Equipo de Arsite Integradores</strong>
                            </p>

                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background:#f9fafb; padding:20px; font-size:12px; text-align:center; color:#6b7280;">
                            © {{ date('Y') }} Arsite Integradores <br>
                            Este es un mensaje automático, por favor no respondas a este correo.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
    ```

</body>

</html>