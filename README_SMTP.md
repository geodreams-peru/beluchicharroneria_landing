Instrucciones rápidas para habilitar envío SMTP en `contact.php`

Resumen
- El script `contact.php` ahora usa PHPMailer para enviar correos vía SMTP.
- Debes instalar PHPMailer con Composer y configurar variables de entorno con tus credenciales SMTP.

1) Instalar Composer (si no lo tienes)
- Descarga e instala Composer: https://getcomposer.org/download/

2) Instalar PHPMailer en el proyecto
- Abre PowerShell en la carpeta del proyecto (la misma carpeta donde está `contact.php`)

```powershell
cd "g:\Otros ordenadores\Mi PC\GOOGLE DRIVE\EMPRESAS\GEODREAMS\2025 sitios webs\beluchicharroneria"
composer require phpmailer/phpmailer
```

(Nota: ajusta la ruta si tu carpeta tiene una ruta distinta)

3) Configurar variables de entorno
- Define las siguientes variables de entorno en tu sistema (o en el hosting). Ejemplo para Windows PowerShell (sesión actual):

```powershell
$env:SMTP_HOST = 'smtp.gmail.com'
$env:SMTP_PORT = '587'
$env:SMTP_USER = 'tu_usuario@gmail.com'
$env:SMTP_PASS = 'tu_contraseña_o_app_password'
$env:SMTP_FROM = 'no-reply@tudominio.com'
$env:SMTP_FROM_NAME = 'BELU Chicharroneria'
$env:SMTP_SECURE = 'tls'
```

- Para producción, configura estas variables en el panel del hosting o en el archivo de entorno seguro.

4) Prueba local (servidor embebido PHP)
- Inicia el servidor PHP en la carpeta del proyecto:

```powershell
php -S 127.0.0.1:5501 -t .
```

- Abre en el navegador: `http://127.0.0.1:5501/` y envía el formulario.

5) Logs
- El script escribe entradas en `logs/mail.log` con éxito o errores. Revisa ese archivo si algo falla.

6) Notas sobre Gmail
- Si usas Gmail, es recomendable crear una contraseña de aplicación (App Password) y habilitar MFA.
- Para cuentas Google Workspace, habilita acceso SMTP en la consola.

Si quieres, puedo:
- Integrar PHPMailer automáticamente si me confirmas que tienes Composer instalado (yo puedo editar el repo para usar vendor/autoload.php ya incluido).
- Preparar un pequeño script `test_smtp.php` para validar conexión SMTP con las credenciales antes de enviar desde el formulario.
