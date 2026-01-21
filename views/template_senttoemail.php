<?php 
function class_templateSentoemail(){

    /* HTML Template */
    $logoPath = "assets/images/logo.png"; // Cambia esto por la ruta correcta a tu logo

    $body = "
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
            }
            .container {
                width: 100%;
                margin: 0 auto;
                padding: 20px;
            }
            .header, .footer {
                color: #000;
                text-align: center;
                padding: 20px 0;
            }
            .header img {
                max-width: 80px;
            }
            .content {
                padding: 20px;
                background-color: #ccc;
                border-radius: 8px;
                margin-top: 20px;
            }
            .footer-text {
                font-size: 12px;
            }
            h1{
                font-size: 20px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <!-- Encabezado -->
            <div class='header'>
                <img src='$logoPath' alt='Logo Phoenix'>
                <h1>Sistema Automatizado de Phoenix</h1>
            </div>

            <!-- Contenido del correo -->
            <div class='content'>
                <p>Estimado/a,</p>
                <p>Le adjuntamos el reporte programado o solicitado.</p>
            </div>

            <!-- Pie de página -->
            <div class='footer'>
                <p class='footer-text'>Este es un sistema automatizado de Phoenix. Si no solicitó este reporte, por favor ignore este mensaje.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    return $body;
}