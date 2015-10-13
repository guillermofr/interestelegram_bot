#Interestelegram

## ¿Qué es?

Es un proyecto para crear un juego conversacional a través de web y Telegram. Utiliza la API de bots de Telegram y una web hecha con Code Igniter.
Toda la documentación se puede encontrar aquí
https://docs.google.com/document/d/1dfeGdC2gUoB5qJ0LUF58SEu_IwiqckzxTgXPO3yhnBs/edit?usp=sharing

## Instalación

Renombra `config/bot.example.php` a `config/bot.php`.
Modifica el fichero `config/bot.php` para indicar el token de tu [bot de Telegram](https://core.telegram.org/bots/api).

Modifica el fichero `config/database.php` para indicar usuario y base de datos disponible para el proyecto.

Visita el controlador `/migrate` para ejecutar las migraciones existentes.

## Estado del proyecto

#### Controlador Webhook

Simula el punto en el que se recibirán los POST de Telegram. Abre en el navegador una pestaña apuntando a Webhook (`/index.php/webhook`) y el recibirá y procesará los mensajes de Telegram de uno en uno. Hace un dump en pantalla del mensaje que está procesando. Si va demasiado rápido aumenta `refreshMillis` a 10000 para tener 10 segundos para ver el mensaje, etc.

#### Librería Processor

Se encarga de procesar los mensajes recibidos y reaccionar según corresponda. 

#### Librería Commander

Lugar donde se implementan las operaciones.

#### Modelos

Extiende de [MY_Model](https://github.com/avenirer/CodeIgniter-MY_Model). Falta añadirle soporte para caché.

#### Comandos disponibles

pilotar - Toma el mando de una nave como capitán 
mover - Muevete por el espacio para buscar objetivos 
escanear - Busca y selecciona tu objetivo 
atacar - Ataca a tu objetivo 
a1 - Atajo para atacar con daño 1 
a3 - Atajo para atacar con daño 3 y 3 personas 
a5 - Atajo para atacar con daño 5 y 5 personas 
esquivar - Sal de los radares enemigos con una maniobra evasiva 
informe - Informe de situación de tu nave 
alistarse - Formar parte de la tripulación, por si estabas antes que el bot

