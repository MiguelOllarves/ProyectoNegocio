<?php

class EmailTemplates {
    
    private static function getBaseTemplate($title, $headerSubtitle, $contentHtml) {
        $year = date('Y');
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 30px; text-align: center; color: white; }
                .header h1 { margin: 0; font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
                .header p { margin: 10px 0 0 0; font-size: 16px; opacity: 0.9; }
                .content { padding: 40px 30px; color: #374151; line-height: 1.6; }
                .content h2 { color: #111827; font-size: 22px; margin-top: 0; font-weight: 700; }
                .content p { margin-bottom: 20px; font-size: 16px; color: #4b5563; }
                .details-box { background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 30px; }
                .details-box p { margin: 8px 0; font-size: 15px; }
                .details-box strong { color: #111827; display: inline-block; width: 140px; }
                .details-box a { color: #10b981; text-decoration: none; font-weight: 600; }
                .btn-container { text-align: center; margin: 40px 0 20px 0; }
                .btn { display: inline-block; background-color: #10b981; color: #ffffff !important; text-decoration: none; padding: 16px 32px; border-radius: 8px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); transition: all 0.3s; }
                .footer { background-color: #f9fafb; padding: 24px; text-align: center; font-size: 14px; color: #6b7280; border-top: 1px solid #f3f4f6; }
                .footer-brand { font-weight: bold; color: #111827; margin-top: 10px; display: block; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$title}</h1>
                    <p>{$headerSubtitle}</p>
                </div>
                <div class='content'>
                    {$contentHtml}
                </div>
                <div class='footer'>
                    &copy; {$year} tu inventario.app — Todos los derechos reservados.<br>
                    <span class='footer-brand'>La empresa Videocode RIF J-182247576 le da la bienvenida.</span>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    public static function getWelcomeEmail($ownerName, $businessName, $loginUrl, $storeUrl) {
        $content = "
            <h2>¡Hola {$ownerName}!</h2>
            <p>Queremos darte una cálida bienvenida. Tu espacio de trabajo para <strong>{$businessName}</strong> ha sido creado y configurado exitosamente.</p>
            
            <div class='details-box'>
                <p><strong>Tu enlace de acceso:</strong> <a href='{$loginUrl}'>Iniciar Sesión</a></p>
                <p><strong>Tienda Pública:</strong> <a href='{$storeUrl}'>Ver mi Catálogo</a></p>
            </div>
            
            <p>Ya puedes empezar a cargar tus productos, registrar ventas, administrar tu inventario y llevar el control total de tu negocio desde cualquier lugar.</p>
            
            <div class='btn-container'>
                <a href='{$loginUrl}' class='btn'>Acceder a mi panel</a>
            </div>
            
            <p>Si tienes alguna duda o necesitas ayuda, responde a este correo y nuestro equipo de soporte estará encantado de asistirte.</p>
            <p>¡Mucho éxito con tus ventas!</p>
        ";
        return self::getBaseTemplate('tu inventario.app', 'Toma el control absoluto de tu negocio', $content);
    }

    public static function getStoreOrderEmail($customerName, $customerPhone, $totalUsd, $orderNotes, $paymentMethod, $loginUrl) {
        $content = "
            <h2>¡Tienes un Nuevo Pedido!</h2>
            <p>Se ha registrado un nuevo pedido en tu tienda online.</p>
            
            <div class='details-box'>
                <p><strong>Cliente:</strong> {$customerName}</p>
                <p><strong>Teléfono:</strong> {$customerPhone}</p>
                <p><strong>Monto Total:</strong> $" . number_format((float)$totalUsd, 2) . "</p>
                <p><strong>Método de Pago:</strong> {$paymentMethod}</p>
                <p><strong>Notas:</strong> {$orderNotes}</p>
            </div>
            
            <p>Ingresa a tu panel administrativo para ver los detalles completos de los artículos y procesar el pedido.</p>
            
            <div class='btn-container'>
                <a href='{$loginUrl}' class='btn'>Ver Pedido</a>
            </div>
        ";
        return self::getBaseTemplate('Nuevo Pedido Recibido', 'Notificación de Tienda Online', $content);
    }

    public static function getCreditRequestEmail($clientName, $clientPhone, $loginUrl) {
        $content = "
            <h2>¡Nueva Solicitud de Crédito!</h2>
            <p>El cliente <strong>{$clientName}</strong> ha solicitado registrarse para obtener crédito (fiado) en tu negocio.</p>
            
            <div class='details-box'>
                <p><strong>Cliente:</strong> {$clientName}</p>
                <p><strong>Teléfono:</strong> {$clientPhone}</p>
            </div>
            
            <p>Por favor ingresa a tu panel administrativo, revisa la sección de clientes y créditos para aprobar o rechazar esta solicitud.</p>
            
            <div class='btn-container'>
                <a href='{$loginUrl}' class='btn'>Revisar Solicitud</a>
            </div>
        ";
        return self::getBaseTemplate('Solicitud de Crédito', 'Alerta de Financiamiento', $content);
    }

    public static function getSaleEmail($saleId, $totalUsd, $loginUrl) {
        $content = "
            <h2>¡Venta Registrada!</h2>
            <p>Se acaba de registrar exitosamente una nueva venta en tu sistema de punto de venta.</p>
            
            <div class='details-box'>
                <p><strong>Recibo Nro:</strong> #{$saleId}</p>
                <p><strong>Total de Venta:</strong> $" . number_format((float)$totalUsd, 2) . "</p>
            </div>
            
            <p>El inventario ha sido actualizado automáticamente. Puedes revisar el detalle de la factura en el módulo de ventas.</p>
            
            <div class='btn-container'>
                <a href='{$loginUrl}' class='btn'>Ver Detalle de Venta</a>
            </div>
        ";
        return self::getBaseTemplate('Nueva Venta Procesada', 'Resumen de Punto de Venta', $content);
    }
}
