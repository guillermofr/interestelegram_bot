#Interestelegram

## ¿Qué es?

Es una rama a parte para transformar el juego de Telegram a otro basado solo en Web. Utiliza una web hecha con Code Igniter.
Toda la documentación de la versión de telegram se puede encontrar aquí
https://docs.google.com/document/d/1dfeGdC2gUoB5qJ0LUF58SEu_IwiqckzxTgXPO3yhnBs/edit?usp=sharing 
La versión web será similar y los cambios los añadiremos en esta descripción.

## Instalación
#Instalación

Carga el dump de base de datos.
Monta un virtual host, por ejemplo:

```
<VirtualHost *:80>
	DocumentRoot "/www/inter"
	ServerName "inter.es"
	ServerAlias "www.inter.es"
       <Directory "/www/inter/">
		AllowOverride All
		Options FollowSymLinks Indexes 	
	</Directory>
</VirtualHost>
```

Instalar rabbitmq-server (si tienes ubuntu 12.04 sigue estos pasos: https://coderwall.com/p/rbahzg/installing-rabbitmq-in-ubuntu-12-04-lts)
Instalar nodejs 5.0+
Instala librerías vendor de composer ( "composer install" en el root del proyecto )

#Pruebas

Navega a 
* http://www.inter.es/canvas
* http://www.inter.es/api/test

El segundo enlace es el antiguo generador de imagenes, para debug.

#WIP

He mantenido el código viejo, cuando reemplazaba algún controlador o librería lo he renombrado a _old para tenerlo de referencia de consulta.

##Librerías

*Mapdrawercanvas*

Genera el array de imagenes y posiciones que tiene que representar el canvas respecto a la nave del jugador principal.

*RabbitConnector*

Capa de comunicación con rabbitmq, funciones básicas para poder establecer la comunicación como queramos.
La configuración de la librería se puede encontrar en /config/rabbitmq.php

*Communications*

Capa de comunicación entre core de interestelegram con clientes socket.io, hace uso de RabbitConnector.

* Tipos de naves

El tipo de nave es un entero de 1 a 4. El 0 está reservado para las naves NPC.
El tipo de nave ofensivo es el 10. Sus evoluciones son 11 y 12.
Los otros tipos de naves siguen el mismo sistema: 20, 21 y 22, y 30, 31 y 32. El canvas ya sabe dibujar la nave si esta tiene un tamaño mayor de 100x100.

*Movement*

Actualizada para mover una nave en función de su posición y su ángulo. Ahora recibe un parámetro que indica si el movimiento es izquierda, derecha, recto o giro de 180º en lugar del antiguo teclado de Telegram.

##Controladores

*Welcome/canvas*

Carga los datos de la nave 1 y dibuja el mapa relativo a lo que ve.

*Action*

Recibe los posts del canvas y realiza la acción. Está montado para que sea sencillo construír una respuesta para que el canvas dibuje el nuevo estado. Ahora mismo siempre recupera la nave 1.

La idea es que Action recibe los post, y luego delega cada tipo de acción a una librería. Por ejemplo, las acciones de movimiento se delegan a Movement. Si se implementa ataque, Action recibe el post y delegaría a una librería Attack los cálculos, etc.

##Vistas

*canvas*

La vista canvas lleva el javascript necesario para dibujar el mapa. Además lleva un evento click genérico para hacer post a un atributo data-action que llevan los botones y dibujar el nuevo mapa que llega como respuesta.

##Servicios

*socket server*

Se ha añadido un servidor nodejs con express y socket.io para establecer conexión con los clientes web y node.
El servidor de node tiene un consumidor de Rabbitmq, que coge los mensajes enviados a la cola por el core de PHP.
La configuración de la librería ampq se encuentra en /socket_server/config.
Es necesario instalar los paquetes necesarios ("npm install" desde /socket_server )
Ejecutar el servidor de node con "node index.js" desde /socket_server.
- falta añadir un forever para el servidor de node
