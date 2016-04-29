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
						<a href="/">Home</a>
						<a class="current-demo" href="/index.php/help">How to play</a>
						<a href="/index.php/ranking">Ranking</a>
						<a href="/index.php/contacto">Contact</a>
					</nav>
				</div>
			</header>
		<div class="container">	
			<div class="main row-fluid">
				<div class="col-sm-offset-2 col-sm-8">



					<h2>Start of the game: <img src="/imgs/map/ship_type1.png"/> </h2> 
					<p>To start a game you have to create a group and invite the bot to the group.</p>
						<center><p><a href="https://telegram.me/interestelegram_bot"><img src="/images/help/logo.png"/></a></p></center>
					<p>The bot then will explain certain things about the dynamics of the game and wait for the captain to take control.</p>
					<p>The first activates the bot with the "Piloting" command becomes Captain.</p>
					<p>The captain can permanently cede control to another person with another command.</p>


					<h2>Growth of the game: <img src="/imgs/map/ship_type3.png"/></h2>
					<p>The groups are not limited in number of people. The more people, the more powerful their weapons. </p>
					<p>The bot will inform the crew information about impacts, communications, fire, and state of the ship (Life, shield and money).</p>
					<p>When a ship dies, the bot informs you that the ship is unusable and we'd better abandon ship. The ship that killed you will be subsidized with points. (You can respawn the same ship while game is in beta)</p>

					<h2>Money <img src="/imgs/map/ship_type4.png"/></h2>
					<p>The way you obtain money is mining on gray asteroids and selling at shop. The shop is located at left bottom of Map. You can buy ship models with that money.</p>

					<h2>Playability  <img src="/imgs/map/ship_type0.png"/></h2>
					<p>The captain will command via commands to perform actions. Each action will be voted by the crew with YES or NO. Each type of action will require a % of votes according to the size of the crew.</p>

					<p>The higher a ship, the greater their statistics, more life and less agility to dodge.</p>

					<h3>Ship Stats</h3>
					<p>The ship will have life and shield. Life will be 5 + (crew * 5)</p>

					<p>The shield will absorb a consumable damage.</p>

					<p>An attack of 5 removes 5 points of life or 5 points of shield.</p>
					<p>If a ship runs out of life the bot will disable the ship. It will display a message saying that the ship must evacuate because all the controls have been destroyed, photo and some photographic detail to indicate endgame.</p>

					<p>Increase the size of the ship, will increase its life and to make stronger attacks, also mine more resources, etc, but complicates success in coordinating actions and reduce evasion.</p>


					<h2>Combat and movement <img src="/imgs/map/target.png"/></h2>
					<p>Ships can only move forward: front left, front and right front. They will also have available a maneuver to rotate 180 degrees and advance two squares. This latest move will have a % chance to miss, becoming a basic straight movement if fail.</p>
					
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

					<p>These movements involve more planning. Rotated 90 degrees clockwise for example, involves stringing two consecutive movements.</p>

					</div>
					<center>
						<div class="col-sm-6 text-right">
							<img src="/images/help/image05.png"/>
						</div>
						<div class="col-sm-6 text-left" style="max-width: 340px;margin-top: 49px;">
							<p>A ship may target any ship in its viewing area, but can only shoot ships within its arc of fire.</p>
						</div>
					</center>
					<div class="col-sm-offset-2 col-sm-8">



					</div>
					<center>
						<div class="col-sm-6 text-right" >
							<p style="max-width: 340px;margin-top: 49px;float:right;">The arc of fire in diagonal is different</p>
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
							

							<p>In the image, the blue ship has the ability to target red and green ships, while the black ship is out of reach.</p>

							<p>When a ship (A) target another ship (B) (A) ship can attack B.</p>

							
						</div>
					</center>
					<div class="col-sm-offset-2 col-sm-8">



					</div>
					<center>
						<div class="col-sm-6 text-right">
							<p style="max-width: 340px;margin-top: 49px;float:right;">A ship will have a visual indicator of the position of the enemy B while this remains targeted, even if out of his range of vision.</p>
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
							


							<p>The ship B can perform an action with high miss chance to evade and remove the target.</p>

						</div>
					</center>
					<div class="col-sm-offset-2 col-sm-8">


					<p>The combination of these mechanical favors the attacking ship: </p>
	
					<p>The attacker must predict the position of your target to follow him successfully and continue shooting.</p>
					<p>The defender must decide whether to flee the arc of fire or attempt evasive action to get rid of the target.</p>


				</div>
					<center>
						<div class="col-sm-6 text-right">
							<img src="/images/help/hidden.jpg"/>
						</div>
						<div class="col-sm-6 text-left" style="max-width: 340px;margin-top: 49px;">
				
							<p>The enemies can hide behind rocks and become invisible to other players. We'll have to scan the sector to detect those hiding ships. But carefull when you are hidding, because asteroids move!</p>
						</div>
					</center>
				<div class="col-sm-offset-2 col-sm-8">

				</div>
					<center>
						<div class="col-sm-6 text-right">
							<img src="/images/help/hiddenlock.jpg"/>
						</div>
						<div class="col-sm-6 text-left" style="max-width: 340px;margin-top: 49px;">
				
							<p>Once selected our enemy can no longer hide in the rocks.</p>
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

