<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            /* Margen inferior ajustado para dar máximo espacio al footer de texto */
            margin: 160px 50px 100px 50px;
        }

        body { 
            font-family: 'Inter', Arial, sans-serif; 
            color: #333; 
            margin: 0;
        }

        /* --- MARCA DE AGUA (Watermark) --- */
        #watermark {
            position: fixed;
            top: -25%;
            left: -25%;
            width: 160%; 
            height: 160%;
            z-index: -1000;
            opacity: 0.08;
        }
        #watermark img { 
            width: 100%; 
            height: 100%; 
            object-fit: contain; 
        }

        /* --- HEADER: BLOQUES SUPERIORES --- */
        header {
            position: fixed;
            top: -160px;
            left: -50px;
            right: -50px;
            height: 140px;
        }

        .block-blue-wide { width: 100%; height: 35px; background-color: #1e1e53ff; }
        .block-blue-thin { width: 100%; height: 5px; background-color: #312AFF; }
        .block-orange-diagonal {
            width: 30%;
            height: 0;
            margin-left: auto;
            border-top: 15px solid #FF8C00;
            border-left: 25px solid transparent;
            border-right: 0px solid transparent;
        }

        .logo-container { padding: 20px 0 0 50px; }
        .logo-container img { height: 55px; width: auto; }

        /* --- FOOTER: TEXTO PEGADO AL BORDE --- */
        footer {
            position: fixed;
            bottom: -85px; /* Más pegado al borde físico inferior */
            left: -50px;
            right: -50px;
            text-align: center;
        }

        .footer-line {
            width: 100%;
            height: 4px;
            background-color: #312AFF;
            margin-bottom: 10px;
        }

        .footer-text {
            width: 100%;
            color: #444;
            font-size: 9px;
            line-height: 1.3;
        }

        /* --- CONTENIDO Y TABLA --- */
        .report-title { text-align: center; margin-top: 10px; }
        .report-title h1 { color: #312AFF; margin: 0; font-size: 18px; text-transform: uppercase; }
        .report-info { text-align: center; font-size: 9px; color: #666; margin-bottom: 15px; margin-top:12px }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            border: 1px solid #ccc; /* Borde exterior */
        }
        th { 
            background-color: #312AFF; 
            color: white; 
            padding: 10px 8px; 
            text-align: left; 
            font-size: 12px; /* Encabezados a 12px */
            border: 1px solid #2520cc; 
        }
        td { 
            padding: 8px; 
            border: 1px solid #ddd; 
            font-size: 10px; /* Contenido a 10px */
        }
        tr:nth-child(even) { background-color: rgba(245, 245, 245, 0.7); }
        
        .stats { 
            margin-top: 12px; 
            font-weight: bold; 
            font-size: 10px; 
            color: #312AFF; 
            
        }
    </style>
</head>
<body>
    <div id="watermark">
        <img src="{{ public_path('images/watermark.png') }}">
    </div>

    <header>
        <div class="block-blue-wide"></div>
        <div class="block-blue-thin"></div>
        <div class="block-orange-diagonal"></div>
        <div class="logo-container">
            <img src="{{ public_path('images/logo.png') }}">
        </div>
    </header>

    <footer>
        <div class="footer-line"></div>
        <div class="footer-text">
            Calle: MUSICOS 714, COLONIA GAVIOTAS SUR, CP. 86090, VILLAHERMOSA TABASCO<br>
            RFC: AIN040211G2A | TELEFONO OFICINA 9933657804 | WHATSAPP 9932974913<br>
            ventas@arsite.com.mx | https://arsite.com.mx/
        </div>
    </footer>

    <div class="report-title pt-2">
        <h1> {{ $title }} </h1>
    </div>
    
    <div class="report-info">
        Reporte generado por el Sistema Gestor de contenido de Ar-Site Integradores a {{ now()->format('d/m/Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
            <tr>
                @foreach($row as $cell)
                    <td>{!! $cell !!}</td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="stats">
        Total de elementos: {{ count($data) }}
    </div>
</body>
</html>