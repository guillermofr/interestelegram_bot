#Interestelegram

## Qué es?

Es un proyecto para crear un juego conversacional a través de web y Telegram. Utiliza la API de bots de Telegram y una web hecha con Code Igniter.

## Instalación

Modifica el fichero `config/bot.php` para indicar el token de tu [bot de Telegram](https://core.telegram.org/bots/api).

Modifica el fichero `config/database.php` para indicar usuario y base de datos disponible para el proyecto.

Visita el controlador `/migrate` para ejecutar las migraciones existentes.

## Estado del proyecto

#### Controlador Webhook

Simula el punto en el que se recibirán los POST de Telegram. Abre en el navegador una pestaña apuntando a Webhook (`/index.php/webhook`) y el recibirá y procesará los mensajes de Telegram de uno en uno. Hace un dump en pantalla del mensaje que está procesando. Si va demasiado rápido aumenta `refreshMillis` a 10000 para tener 10 segundos para ver el mensaje, etc.

#### Librería Processor

Se encarga de procesar los mensajes recibidos y reaccionar según corresponda. Ahora mismo interpreta `/ayuda` y `/pilotar`.

#### Modelo Ships

Se encarga de gestionar las naves en la correspondiente tabla de base de datos. Extiende de [MY_Model](https://github.com/avenirer/CodeIgniter-MY_Model). Falta añadirle soporte para caché.