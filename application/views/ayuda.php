<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> 
		<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
		<title>INTERESTELEGRAM</title>
		<meta name="description" content="Juega con tus amigos y conquista la galaxia." />
		<meta name="keywords" content="app, showcase, css transitions, perspective, 3d, grid, overlay, css3, jquery" />
		<meta name="author" content="Codrops" />
		<link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon">
		<link rel="icon" href="/images/favicon.ico" type="image/x-icon">
		<link rel="stylesheet" type="text/css" href="/css/default.css" />
		<link rel="stylesheet" type="text/css" href="/css/landing.css" />
		<link href="/css/bootstrap.min.css" rel="stylesheet">
		<link href="/css/styles.css" rel="stylesheet">
		<!--[if lt IE 9]>
			<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
		<link href='https://fonts.googleapis.com/css?family=Orbitron|Audiowide' rel='stylesheet' type='text/css'>
		<link href='https://fonts.googleapis.com/css?family=Roboto:400,100,300' rel='stylesheet' type='text/css'>
		<style>

		img {
			margin: 23px 0px;
		}

		h2 img {
			margin: 0px;
			width: 78px;
		}

		*{
			font-family: 'Roboto', sans-serif;
		}

		h2 {
		    border-bottom: 2px solid #AA83BB;
    		margin-bottom: 34px;	
    		font-family: 'Audiowide', cursive;
    		color: #AA83BB;
    		font-size: 40px;
		
		}
		a:hover, a:focus {
		    color: #AA83BB;
		    text-decoration: underline;
		}
		</style>
		<script src="js/modernizr.custom.js"></script>
	</head>
	<body>
			<div id="google_translate_element"></div><script type="text/javascript">
			function googleTranslateElementInit() {
			  new google.translate.TranslateElement({pageLanguage: 'es', includedLanguages: 'en,es,fr,it,ru', layout: google.translate.TranslateElement.InlineLayout.SIMPLE}, 'google_translate_element');
			}
			</script><script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
			        
			<!-- Codrops top bar -->
			<div class="codrops-top clearfix">
				
			</div><!--/ Codrops top bar -->
			<header class="clearfix">
				<div class="inner">
					<!--h1><span></span></h1-->
					<nav class="codrops-demos">
						<a href="/">Inicio</a>
						<a class="current-demo" href="/index.php/help">Jugar</a>
						<a href="/index.php/ranking">Ranking</a>
						<a href="/index.php/contacto">Contacto</a>
					</nav>
				</div>
			</header>
		<div class="container">	
			<div class="main row-fluid">
				<div class="col-sm-offset-2 col-sm-8">



					<h2>Inicio del juego: <img src="/imgs/map/ship_type1.png"/> </h2> 
					<p>Para iniciar una partida hay que crear un grupo e invitar al bot al grupo. </p>
						<center><p><a href="https://telegram.me/interestelegram_bot"><img src="/images/help/logo.png"/></a></p></center>
					<p>El bot en ese momento te explicará ciertas cosas sobre la dinámica del juego y esperará que el capitán tome el control.</p>
					<p>El primero que activa el bot con el comando “Pilotar” se convierte en Capitán.</p>
					<p>El capitán puede ceder permanentemente el mando a otra persona con otro comando.</p>


					<h2>Crecimiento del juego: <img src="/imgs/map/ship_type3.png"/></h2>
					<p>Los grupos no se limitan en cantidad de gente. Cuanta más gente, más poderosas serán sus armas. (Límite 200 por telegram) </p>
					<p>El bot será el que informe a la tripulación de impactos, comunicaciones, disparos, y estado de la nave (Vida, escudo y dinero). </p>
					<p>Cuando una nave muere, el bot te informa que la nave está inutilizable y que lo mejor será abandonar la nave. La nave que te ha matado será bonificada con puntos.</p>

					<h2>Dinero <img src="/imgs/map/ship_type4.png"/></h2>
					<p>Se podrá minar en zonas de minería para obtener créditos interplanetarios. Este dinero servirá para comprar módulos nuevos en la nave, reparaciones, escudos.</p>

					<h2>Jugabilidad <img src="/imgs/map/ship_type0.png"/></h2>
					<p>El capitán de la nave dará órdenes vía comandos para ejecutar acciones. Cada acción será votada por la tripulación para realizarse mediante SI o NO. Cada tipo de acción requerirá un % de votos según el tamaño de la tripulación.</p>

					<p>Cuanto mayor sea una nave, mayor será sus estadísticas, más vida y menos agilidad para esquivar. </p>

					<h3>Stats de la nave</h3>
					<p>La nave tendrá vida y escudo.
					La vida será:
					5 + (tripulación * 5)</p>

					<p>El escudo será un consumible que absorberá daño.</p>

					<p>Un ataque de 5 quita 5 puntos de vida o 5 puntos de escudo.</p>
					<p>Si una nave se queda sin puntos de vida el bot queda desactivado en ese canal. Mostrará un mensaje diciendo que la nave hay que evacuarla porque todos los controles han sido destrozados, foto y algún detalle fotográfico para indicar fin de partida.</p>

					<p>Aumentar el tamaño de la nave aumentar su vida y poder hacer ataques mayores, minar más recursos, etc, pero complica el éxito en la coordinación para las acciones y reduce la evasión.</p>


					<h2>Combate y movimiento <img src="/imgs/map/target.png"/></h2>
					<p>Las naves pueden moverse solo hacia adelante: de frente izquierda, de frente y de frente derecha. Además tendrán disponible una maniobra para girar 180 grados que avanzará dos casillas. Este último movimiento tendrá un % de posibilidades de fallar, convirtiéndose en un movimiento recto básico en caso de pifia.</p>
					
					</div>
					<center>
						<div class="col-sm-6 text-right">
							<img src="/images/help/image04.png"/>
						</div>
						<div class="col-sm-6 text-left">
							<img src="/images/help/image07.png"/>
						</div>
					</center>
					<div class="col-sm-offset-2 col-sm-8">

					<p>Estos movimientos implican una planificación mayor. Un giro de 90 grados a la derecha por ejemplo, implica encadenar dos movimientos consecutivos.</p>

					</div>
					<center>
						<div class="col-sm-6 text-right">
							<img src="/images/help/image05.png"/>
						</div>
						<div class="col-sm-6 text-left" style="max-width: 340px;margin-top: 49px;">
							<p>Una nave podrá fijar en el blanco a cualquier nave en su área de visión, pero solo podrá disparar a naves que se encuentren en su arco de fuego.</p>
						</div>
					</center>
					<div class="col-sm-offset-2 col-sm-8">



					</div>
					<center>
						<div class="col-sm-6 text-right" >
							<p style="max-width: 340px;margin-top: 49px;float:right;">El arco de fuego en diagonal es distinto.</p>
						</div>
						<div class="col-sm-6 text-left">
							<img src="/images/help/image02.png"/>
						</div>
					</center>
					<div class="col-sm-offset-2 col-sm-8">

					

					</div>
					<center>
						<div class="col-sm-6 text-right">
							<img src="/images/help/image06.png"/>
						</div>
						<div class="col-sm-6 text-left" style="max-width: 340px;margin-top: 49px;">
							

							<p>En la imagen, la nave azul tiene la posibilidad de fijar en el blanco a las naves roja y verde, mientras que la nave negra está fuera de alcance.</p>

							<p>Cuando una nave (A) fija en el blanco a otra nave (B) la nave A podrá realizar ataques sobre B.</p>

							
						</div>
					</center>
					<div class="col-sm-offset-2 col-sm-8">



					</div>
					<center>
						<div class="col-sm-6 text-right">
							<p style="max-width: 340px;margin-top: 49px;float:right;">La nave A tendrá un indicador visual de la posición de la nave B mientras esta permanezca fijada, incluso si se sale de su rango de visión.</p>
						</div>
						<div class="col-sm-6 text-left">
							
							<img src="/images/help/image08.jpg"/>

						</div>
					</center>
					<div class="col-sm-offset-2 col-sm-8">


					</div>
					<center>
						<div class="col-sm-6 text-right">
							<img src="/images/help/image09.jpg"/>
						</div>
						<div class="col-sm-6 text-left" style="max-width: 340px;margin-top: 49px;">
							


							<p>La nave B podrá ejecutar una acción con gran posibilidad de fallo para evadir y eliminar el blanco fijado.</p>

						</div>
					</center>
					<div class="col-sm-offset-2 col-sm-8">


					<p>La combinación de estas mecánicas favorece a la nave atacante: </p>
					<p>Dado que una nave que te toma la cola tiene ventaja ofensiva ya que no va a recibir fuego enemigo hasta que la otra nave gire (con la posibilidad de fallar la maniobra y permanecer en arco del enemigo).
					dado que el blanco fijado le otorga información adicional sobre su objetivo, incluso cuando está fuera de su vista.</p>
					<p>Obligar al jugador a pensar su siguiente movimiento:</p>
					<p>El atacante deberá predecir la posición de su objetivo para seguirlo con éxito y seguir disparándole.</p>
					<p>El defensor deberá decidir entre huír para salir de arco de fuego o intentar la acción de evasión para librarse del blanco fijado.</p>


				</div>
					<center>
						<div class="col-sm-6 text-right">
							<img src="/images/help/hidden.jpg"/>
						</div>
						<div class="col-sm-6 text-left" style="max-width: 340px;margin-top: 49px;">
				
							<p>Los enemigos se pueden ocultar detras de las rocas y no verse a simple vista. Tendremos que escanear el sector para detectar a los que se esconden.</p>
						</div>
					</center>
				<div class="col-sm-offset-2 col-sm-8">

				</div>
					<center>
						<div class="col-sm-6 text-right">
							<img src="/images/help/hiddenlock.jpg"/>
						</div>
						<div class="col-sm-6 text-left" style="max-width: 340px;margin-top: 49px;">
				
							<p>Una vez seleccionado nuestro enemigo ya no se podrá esconder en las rocas.
						</div>
					</center>
				<div class="col-sm-offset-2 col-sm-8">


				</div>
				
			</div><!-- /main -->
		</div><!-- /container -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
		<script src="js/bootstrap.min.js"></script>
		<script src="js/scripts.js"></script>
	</body>
</html>

