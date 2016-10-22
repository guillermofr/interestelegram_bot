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

#Pruebas

Navega a 
* http://www.inter.es/canvas
* http://www.inter.es/api/test

#WIP

He mantenido el código viejo, cuando reemplazaba algún controlador o librería lo he renombrado a _old para tenerlo de referencia de consulta.

##Librerías

*Mapdrawercanvas*

Genera el array de imagenes y posiciones que tiene que representar el canvas respecto a la nave del jugador principal.

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