<?php
/* ================================================================================
 * Web App to collect booking seats with smartphones for Campus Happening Live 2019
 * --------------------------------------------------------------------------------
 * This script is the result of workgroup in the Area Campus Opzionale, "Laboratory for Image & Web Editing (LIWE)" in the school year 2018-2019
 * The workgroup partecipants are: Sofia Musicco, Alessandro Polotti, Luca Federico Consoli, Riccardo Gualeni and Giovanni Lodi.
 * The project include a Wordpress site, with SQLite database used also for booking and users. The Wordpress content consist of posts and graphics about Live 2019.
 * Posts contain links and information about iOS and Android app to check QR codes.
 * Near about every lines of code has a comment to explain his scope, on the right.
 * TO DO: price is hard encoded in some lines (Javascript), need to be read from DB, may be useful to check existance of recipient email before send confirmation
 * --------------------------------------------------------------------------------
 * CPSoft, 1989-2019. - ocdl.it/cw - Released 01/05/2019 - Updated 17.25 15/05/2019
 * Licenza software GNU/GPL 3.0 - Licenza documentazione Creative Commons BY-SA 2.5
 * ============================================================================= */
	$db = new SQLite3('../database/wordpress.sqlite'); // open SQLite database (DB)
	include('./phpqrcode/qrlib.php'); // Include QR decode library
	if (isset($_GET['logout'])) { // If user asked for logout then destroy Session
		session_unset();
		if ($_SESSION['valid']) { session_destroy(); }
	} else {
		session_start(); // Enable Session
	}
	$main_folder = str_replace('\\','/',dirname(__FILE__) ); // Path with script filename included
	$document_root = str_replace('\\','/',$_SERVER['DOCUMENT_ROOT'] ); // Host domain name
	$main_folder = str_replace( $document_root, '', $main_folder);
	if( $main_folder ) { // To obtain the full URL, without script, but with current protocol and ending with '/'
	    $current_url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME']. '/' . ltrim( $main_folder, '/' ) . '/';
	} else {
	    $current_url = $_SERVER['REQUEST_SCHEME'].'://'.rtrim( $_SERVER['SERVER_NAME'], '/'). '/';
	}
	if (isset($_GET['md5']) ) { // to encode with md5...
		echo md5($_POST['md5']);
		exit;
	}
	/* ########## Collect every useful data and store in the Log table ########## */
	$now = DateTime::createFromFormat('U.u', microtime(true))->setTimezone(new DateTimeZone('Europe/Rome'));
	$dtm = $now->format("Y-m-d H.i.s.uP");
	$typ = (isset($_POST['username']) ? "accesso" : (isset($_POST['prenota']) ? "prenota" : "lettura"));
	$req = (isset($_POST['username']) ? $_POST['username'] : (isset($_POST['prenota']) ? $_POST['email'] : ""));
	$ipa = $_SERVER['REMOTE_ADDR'];
	$ref = $_SERVER['HTTPS_REFERER'];
	$bua = $_SERVER['HTTPS_USER_AGENT'];
	$lng = $_SERVER['HTTPS_ACCEPT_LANGUAGE'];
	$ref = (isset($ref) ? $ref : $_SERVER['HTTP_REFERER'] );
	$bua = (isset($bua) ? $bua : $_SERVER['HTTP_USER_AGENT'] );
	$lng = (isset($bua) ? $lng : $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
	$log = $dtm."\t"."\t".$ipa."\t".$ref."\t".$bua."\t".$lng."\r\n";
	$sql = "INSERT INTO live_log (datetime, log_type, req_data, ipaddress, referrer, useragent, language) 
				 VALUES ('$dtm', '$typ', '$req', '$ipa', '$ref', '$bua', '$lng')"; // Creating a query to store log data
	$db->exec($sql); // Executing the query
	$g = ""; // $g contain optional message about invalid username or password
	/* ########## Verify login credential for authorized user ########## */
	if (isset($_POST['username']) && isset($_POST['password']) && !empty($_POST['username']) && !empty($_POST['password'])) { // if submited username and password...
		$sql = "SELECT rowid, *
				  FROM 'live_uid'
				 WHERE username = '".$_POST['username']."' AND password = '".md5($_POST['password'])."'"; // Create a query for identical username and md5 of password
		$query = $db->query($sql); // Executing the query...
		$row = $query->fetchArray(); // Collects every resulting records in $row
		if ($row > 0) { // if found at last a single record... (that's unique cause the username is unique in DB)
			$_SESSION['valid']=true; // Validate Session
			$_SESSION['username']=$row['username']; // Keep Username in Session
			$_SESSION['rule']=$row['rule']; // Keep user's Rule in Session
			?><script type="text/javascript">
				window.top.location=window.self.location; // Reload Web App outside Wordpress...
			</script><?php
			exit; // ...exit (because the page was just reloaded)
		} else {
			$g="Nome utente o password non corretti."; // ...submited username and password are invalid, alert message in $g
			mail("login.live2019@canossacampus.it", "LIVE2019, ".$g." ".$_POST['username'], $log);
		}
	}
	/* ########## Starting HTML document (meta, title, CSS external and internal, JS) ########## */
?>
<!DOCTYPE html>
<html lang="it">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0"><!-- For mobile... -->
		<title>Campus Happening Live, 2019 - Teatro Sociale. venerdi 7 giugno 2019, ore 20.45</title>
		<link href="css/bootstrap.min.css" rel="stylesheet"><!-- These three lines for Botstrap framework... -->
		<link href="css/bootstrap-responsive.min.css" rel="stylesheet">
		<script src="js/bootstrap.min.js"></script>
		<!-- May be useful an external CSS file...? --><style>
			#header-logo { height: 6em !important; }
			#header-live { height: 6em !important; margin-bottom: 2em; text-align: center; background-size: contain !important; background-color: #000 }
			@media( min-width: 520px ){ #header-live { background-image: url('img/canossacampus.png'), url('img/pappagallo.jpg'); background-position: top left, bottom right; background-repeat: no-repeat, no-repeat; } }
			@media( max-width: 519px ){ #header-live { background-image: url('img/canossacampus_logo.png'); background-position: top left; background-repeat: no-repeat; text-align: right; } }
			#smartphone { padding: 0.5em; max-width: 20em; margin: 0 auto; box-shadow: 0.4em 0.6em 1.2em #999; border-radius: 1.2em;
				border-top: 30px solid #333; border-bottom: 60px solid #333; border-left: 10px solid #333; border-right: 10px solid #333;
				background: #fff url('img/_camera.png') no-repeat top left; background-size: cover; }
			#photo { float: inline-start; color: #fff; font-weight: bold; font-size: 1.2em; margin-left: 25%; margin-top: 1.5em; }
			#ticket { padding: 7em 0.5em 0.5em; max-width: 20em; margin: 0 auto 1em; border: 0.5em solid transparent; border-image: url('img/border-strip.png') 80 round round; box-shadow: 0.4em 0.6em 1.2em #999; background: url('img/canossa-live.png') no-repeat top left; background-size: contain; background-color: #eee; }
		</style>
	</head>
	<body id="live-body" style="background: none; padding: 20px 0;"><!-- Below Header is displayed only outside Wordpress* -->
		<header id="header-live"><a href="https://www.canossacampus.it/live"><img id="header-logo" src="img/campus-live.png" title="Canossa Campus Happening Live, 2019 | Teatro Sociale, Brescia. venerdi 7 giugno 2019, ore 20.45"></a></header>
		<div id="live-main" class="container" style="text-align: center; width: 98%; ">
			<script type="text/javascript"><!-- *This is the Javascript that display above Header only outside Wordpress -->
				if ( window == window.top ) {
					document.getElementById("live-body").style.padding = "0";
					document.getElementById("sector1").style.fill = "rgb(255,255,255)";
					document.getElementById("sector2").style.fill = "rgb(255,255,255)";
					document.getElementById("sector3").style.fill = "rgb(255,255,255)";
					zone1.style.fill = "rgb(255,255,255)";
					zone2.style.fill = "rgb(255,255,255)";
					zone3.style.fill = "rgb(255,255,255)";
				} else {
					document.getElementById("header-live").style.display = "none"
					document.getElementById("header-logo").style.display = "none"
					document.getElementById("sector1").style.fill = "rgb(212,212,212)";
					document.getElementById("sector2").style.fill = "rgb(212,212,212)";
					document.getElementById("sector3").style.fill = "rgb(212,212,212)";
					zone1.style.fill = "rgb(212,212,212)";
					zone2.style.fill = "rgb(212,212,212)";
					zone3.style.fill = "rgb(212,212,212)";
				}
			</script>
			<div class="container">
				<?php
				$m = "";
				/* ########## Someone want to verify a QR Code, showing also all reservation details ########## */
				if (isset($_GET['verifica'])) {
					?><div id="ticket"><?php 
					$s = "";
					$e = 0;
					$m = $_GET['verifica'];
					$sql = "SELECT rowid, *
							  FROM 'live_sid'
							 WHERE md5 = '".$m."' AND status = '2'"; // Create a query to found exact md5 of QR Code of a regular reservation (status = 2)
					$query = $db->query($sql); // Executing the query
					$row = $query->fetchArray();
					/* ########## Check if requested verification is a valid reservation or not... ########## */
					if ( $row > 0 ) {
						$query = $db->query($sql); // Executing the query, again ?>
						<table border="1" cellpadding="4" style="text-align:center;padding:0.2em;margin:0 auto;border:1px solid #333; border-collapse:collapse;">
							<thead>
								<th><strong>Posto</strong></th>
								<th>Zona</th>
								<th>Lato</th>
								<th>Fila</th>
								<th>Importo</th>
							</thead><?php
							while($row = $query->fetchArray()) {
								$e = $e+$row['price'];
								?>
								<tr>
									<td style="font-weight:bold;color:#1c5280;"><?=$row['seat']?></td>
									<td><?=$row['zone']?></td>
									<td><?=$row['side']?></td>
									<td><?=$row['file']?></td>
									<td><?="&euro; ".$row['price'].",00"?></td>
								</tr><?php
								$u = $row['uid'];
								$l = $row['email'];
								$t = $row['timestamp'];
							} ?>
								<caption><h4>Dettagli Prenotazione</h4>
									<h3 style="font-size:1.6em;font-family:Times,Serif;color:#1c5280;font-style:italic;line-height:1em;">venerdì 7 giugno 2019<br>Teatro Sociale, 20.45</h3><div>
									Incaricato: <?=$u?><br />
									Prenotante: <?=$l?><br />
									Data e ora: <?=$t?><br />&nbsp;
								</div></caption>
							<tfoot><td colspan="5" align="right"><strong>Pagato <?="&euro; ".$e.",00"?>&nbsp;</strong></td></tfoot>
						</table><div style="font-size:1.2em;font-family:Times,Serif;color:#1c5280;font-style:italic;font-weight:bold;margin: 0.5em 0;"><span style="font-size:2em;color:#00c;vertical-align:sub !important;">&#0149;</span>&nbsp;Collocazione dei Posti prenotati</div>
					<?php } else { ?>
							<img src='img/annullata-bn.png'><br /><br /><div style='color:#f00;font-weight:bold;'>Prenotazione non trovata...</div>
							<div style='color:#f00;'>Contattare <?php echo ( isset($_SESSION['username']) ? "il Responsabile per ulteriori eventuali controlli e azioni: responsabile.live2019@canossacampus.it" : "la persona Incaricata con cui avete fatto la Prenotazione per verificarla, grazie." ) 
							if ( isset($_SESSION['username']) ) { mail("verify.live2019@canossacampus.it", "LIVE2019, ".$m." ".$_POST['username'], $log); } ?></div>
					<?php } ?>
					</div><br /><?php
				}
				/* ########## A delegate is submitting a reservation, check if every seat if free and calculate total price ########## */
				if (isset($_POST['prenota'])) {
					$p = "\r\n";
					$h = "<table border='1' cellpadding='4' style='text-align:center;padding:0.2em;margin:0 auto 2em;border:1px solid #333; border-collapse:collapse;'><thead>
						<th><strong>Posto</strong></th>
						<th>Zona</th>
						<th>Lato</th>
						<th>Fila</th>
						<th>Importo</th></thead>";
					$a = "Prenotazione confermata";
					$d = date('Y-m-d H:i:s');
					$s = "";
					$e = 0;
					$b = "";
					for ($i=0; $i<674; $i++) {
						if (isset($_POST['seat_'.$i])) {
							if ($_POST['seat_'.$i] == true) {
								$sql = "SELECT rowid, *
										  FROM 'live_sid'
										 WHERE idseat = '".$i."' AND status = '0'"; // Creating a query for free (status=0) seat ($_POST['seat_'.$i]==true) to reserve...
								$query = $db->query($sql); // Executing the query
								$row = $query->fetchArray();
								if ($row > 0) { // If found the seat and is free, read its data...
									$s = $s.", ".$i;
									$e = $e + $row['price'];
									$b = $b."<tr><td style='font-weight:bold;color:#1c5280;'>".$row['seat']."</td>";
									$b = $b."<td>".$row['zone']."</td>";
									$b = $b."<td>".$row['side']."</td>";
									$b = $b."<td>".$row['file']."</td>";
									$b = $b."<td>&euro; ".$row['price'].",00</td></tr>";
								} else { // Almost one seat is not free, reservation  cancelled...
									$s = $s.", <span style='color:#f00;font-weight:bold;'>".$i."</span>";
									$a = "<img src='img/annullata.png'><br /><br /><span style='color:#f00;'>Prenotazione annullata...</span>";
									$b = $b."<tr><td><span style='color:#f00;font-weight:bold;'>".$i."</span></td><td colspan='4' style='text-align:left;'><span style='color:#f00;'>Non disponibile</span></td></tr>";
								}
							}
						}
					}
					$s = "Incaricato: ".$_SESSION['username'].$p."Prenotante: ".$_POST['email'].$p."Data e ora: ".$d.( ($a === "Prenotazione confermata" && $e > 0) ? $p."Pagato: &euro; ".$e.",00" : "" );
					$m = md5($s); // $s is the text message, $m is the md5 of $s and is aldo the unique key for checking the reservation!
					if ($a === "Prenotazione confermata") { // If resevation is still available, not cancelled...
						for ($i=0; $i<674; $i++) {
							if (isset($_POST['seat_'.$i])) { // If there is almost one seat in reservation...
								if ($_POST['seat_'.$i] == true) {
									$sql = "UPDATE live_sid
												SET status = '2', uid = '".$_SESSION['username']."', email = '".$_POST['email']."', timestamp = '".$d."', md5 = '".$m."'
											  WHERE idseat = '".$i."' AND status = '0'"; // Creating a query to update all seats confirmed as free and now reserved!
									$db->exec($sql); // Executing the query, from now on these seats are reserved.
								}
							}
						}
					}
					/* ########## If everything if regular generating QR Code and showing all reservation details ($b) ########## */
					if ($a === "Prenotazione confermata" && $e > 0) {
						$PNG_TEMP_DIR = dirname(__FILE__)."/phpqrcode/temp/";
						$PNG_WEB_DIR = './phpqrcode/temp/';
						$filename = $PNG_TEMP_DIR.'Live2019_'.$m.'.png';
						QRcode::png("https://www.canossacampus.it/LIWE?verifica=".$m, $filename, 'M', 4, 2);
						/* ########## Sending also an EMail to the Booker ########## */
						if ( !mail_attachment($_POST['email'], $PNG_TEMP_DIR, basename($filename), "Campus Happening Live, 2019 - Prenotazione confermata", "Buongiorno, grazie per aver prenotato online, vi aspettiamo venerd&igrave; 7 giugno 2019 alle 20.45 al Teatro Sociale.<br/>Per tutti i dettagli della prenotazione, qui confermata, aprire <a href='https://www.canossacampus.it/LIWE?verifica=".str_replace("Live2019_", "", str_replace(".png", "", basename($filename)))."'>l'indirizzo</a> contenuto nel Codice QR allegato.<br />Istruzioni e informazioni disponibili a questo <a href='http://canossacampus.it/LIVE'>indirizzo</a>.<br /><br /><h4>".$a."</h4>".str_replace($p, "<br />", $s)."<br />&nbsp;".$h.$b) ) { 
							?><script type="text/javascript">alert("ATTENZIONE, problemi nell'invio dell'E-Mail...")</script>
						<?php } ?>
						<div id="smartphone">
							<img src="<?=$PNG_WEB_DIR.basename($filename)?>" /><br /><?php // Showing the QR Code from the md5 of $s (the unique key of this reservation)
					}
					if ($a === "Prenotazione confermata" && $e === 0) { // Showing a message if reservation was cancelled.
						$a = "<img src='img/annullata-bn.png'><br /><br /><span style='color:#f00;'>Prenotazione annullata...</span>";
						$s = $s.$p."<span style='color:#f00;'>Nessun Posto prenotato.</span>";
					} ?>
						<h4><?=$a?></h4>
					<?php echo str_replace($p, "<br/>", $s)."<br/>&nbsp;"; // Showing in any case all details of the reservation (seats and their status...)
					if ($b != "") { ?>
							<?=$h.$b?>
							</table>
					<?php } // Remember to take a photo of the QR Code!!!...
					if ($a === "Prenotazione confermata" && $e > 0) { echo '<div id="photo">Fare una foto, grazie!</div></div><br />'; }
				} ?>
			</div>
			<?php if (isset($_SESSION['rule'])) { // Only if someone delegated is logged in... ?>
				<form method="POST" action="./index.php" name="prenota" onkeypress="return event.keyCode != 13;">
					<!-- ########## Generating input form to collect reservation for every seat (see the above Javascript) ########## --><?php
					$sql = "SELECT rowid, *
							  FROM 'live_sid'"; // Creating a query for collect all seats data
					$query = $db->query($sql); // Executing the query
					?><?php
					while($row = $query->fetchArray()) { // For every free seat (status<2) creating an hidden input checkbox for the above Javascript (to change the status free/standby and collect reservation)
						if ($row['status'] < '2') { ?>
							<input 
								id="book_<?=$row['idseat']?>" 
								type="checkbox" 
								name="seat_<?=$row['idseat']?>" <?php echo ($row['status'] === '1' ? "checked" : "") ?>
								data-status="<?=$row['status']?>" 
								data-price="<?=$row['price']?>" 
								style="display:none;" /><?php
						}
					} ?>
					<!-- ########## Generating input form to collect a valid email, at least one seat reservation and the confirm ########## -->
					<div id="email-invalid" class="alert alert-danger" style="display:none;">ATTENZIONE, indirizzo email obbligatorio e valido, grazie.</div>
					<div id="seats-invalid" class="alert alert-danger" style="display:none;">ATTENZIONE, prenotare almeno un posto disponibile, grazie.</div>
					<input type="hidden" name="prenota" />
					<input type='text' name='email' name='email' placeholder="Indirizzo E-Mail del Prenotante" onchange="ValidateEmail(document.prenota.email)" style="margin-bottom:0 !important;" />
					<label for="email" style="font-weight: bold; display: inline;">Totale euro <span id="euro">0.00</span></label>&nbsp;
					<input type="button" id="prenota-submit" value="Incassa e conferma" class="btn btn-secondary" />
					<a href="https://<?php echo $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."?logout"; ?>" class="btn btn-danger">Interrompi ed esci</a>
					<?php if ( isset($_SESSION['rule']) && $_SESSION['rule'] == '0' ) { ?>
						<a href="../database/phpliteadmin.php" class="btn btn-warning" target="_blank">SQLite</a>
					<?php } ?>
					<script type="text/javascript">
						function ValidateEmail(inputText) { // Validating email, see also the "onkeypress" in the form tag to prevent keystroke-only submission (if email invalid the form does not have a submit button but keystroke may submitting with invalid data)
							var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
							if ( !inputText.value.match(mailformat) ) {
								document.getElementById('prenota-submit').className = "btn btn-secondary";
								document.getElementById('prenota-submit').type = "button";
								document.getElementById('email-invalid').style.display = "block";
								return false;
							} else {
								document.getElementById('prenota-submit').type = "submit";
								document.getElementById('prenota-submit').className = "btn btn-primary";
								document.getElementById('email-invalid').style.display = "none";
								return true;
							}
						}
					</script>
				</form>
			<?php } else { // Only if nobody delegated is logged in... generating the login form ?>
				<div class="modulo" class="container-fluid">
					<?php echo ($g == "" ? "" : "<div class='alert alert-danger'>".$g."</div>"); ?>
					<form method="POST" action="./index.php">
						<div class="form-group" class="container-fluid">
							<input type="text" name="username" placeholder="Nome utente dell'Incaricato" />
							<input type="password" name="password" placeholder="Password dell'Incaricato" />
							<button type="submit" class="btn btn-primary" style="margin-bottom:10px;">Accedi...</button>
						</div>
					</form>
				</div>
			<?php } ?>
			<!-- ########## Reading DB to generate the interactive SVG map of seats ########## -->
			<svg width="100%" class="img-fluid" xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:svgjs="http://svgjs.com/svgjs" viewBox="0 0 1280 960">
				<!-- grey rectangle for palcoscenico --><rect width="505" height="100" fill="#999" x="395" y="15"></rect>
				<!-- grey areas for gallerie --><g id="zone1a" style="stroke:none" transform="matrix(0.99995934,-0.00901778,0.0088329,0.97945818,-5.87908,17.711631)"> <path id="sector1a" style="fill:#d4d4d4;stroke:none" d="m 384,494 c 40.33401,43.08615 90.05322,77.17553 145.76676,97.02041 48.58755,17.9285 101.24924,24.37869 152.79423,19.17065 C 741.43123,604.7597 799.4692,584.20274 848.04007,550.30395 871.95983,533.58436 894.72998,515.01533 914,493 883.33333,463.66667 852.66667,434.33333 822,405 785.08434,446.51098 732.98721,473.68729 677.77999,480.37 604.01573,490.12899 527.5513,459.96904 478,405 c -31.33333,29.66667 -62.66667,59.33333 -94,89 z" id="zone1" /> </g>
				<g id="zone2a" style="stroke-width:2;stroke-miterlimit:4;stroke-dasharray:none;stroke:none"> <path id="sector1b" style="fill:#d4d4d4;stroke:none;stroke-width:2;stroke-dasharray:none;stroke-miterlimit:4" d="m 429,905 c 136.27515,48.41475 289.08198,51.27174 426,4 -19,-61 -38,-122 -57,-183 -99.34133,32.81118 -209.69967,32.2238 -308,-4 -20.33333,61 -40.66667,122 -61,183 z" id="zone2" /> </g>
				<g id="zone3a" style="stroke:none" transform="matrix(1.0238797,0,0,1,-16.097625,0.70710678)"> <path id="sector1c" style="fill:#d4d4d4;stroke:none" d="M 378,669 C 515.35193,762.66809 702.50493,775.94091 851.63276,702.34955 872.60826,693.08849 891.662,680.18892 911,668 886.33333,628.66667 861.66667,589.33333 837,550 760.75162,602.23621 663.54006,620.96657 573.2855,601.36087 530.26789,592.77279 489.47486,574.16497 453,550 c -25,39.66667 -50,79.33333 -75,119 z" id="zone3" /> </g>
				<!-- curves for barcaccia --><path id="barcacciasx" style="fill:none;stroke:#4d4d4d;stroke-width:4;stroke-linecap:butt;stroke-linejoin:miter;stroke-miterlimit:4;stroke-opacity:1;stroke-dasharray:none" d="m 843.19975,136.54992 c -8.29289,11.7506 -10.13388,24.098 -9.88388,35.19454 0,10.78338 1.38083,26.73655 10.92677,41.94455 7.07107,9.89949 14.46142,16.12347 28.95711,17.36091 14.31914,0.9584 25.02052,-7.66912 27.32322,-18.37652" />
				<path id="barcacciasx" style="fill:none;stroke:#4d4d4d;stroke-width:4;stroke-linecap:butt;stroke-linejoin:miter;stroke-miterlimit:4;stroke-opacity:1;stroke-dasharray:none" d="m 452.20889,137.21358 c 8.29289,11.7506 10.13388,24.098 9.88388,35.19454 0,10.78338 -1.38083,26.73655 -10.92677,41.94455 -7.07107,9.89949 -14.46142,16.12347 -28.95711,17.36091 -14.31914,0.9584 -25.02052,-7.66912 -27.32322,-18.37652" />
				<!-- 3 arches for gallerie --><path d="M145,255 a1,1 0 0,0 995,0" fill="none" stroke="#555" stroke-width="4"/><path d="M270,230 a1,1 0 0,0 755,0" fill="none" stroke="#555" stroke-width="4"/><path d="M395,230 a1,1 0 0,0 505,0" fill="none" stroke="#555" stroke-width="4"/>
				<!-- 3 vertical lines, for gallerie sx --><line x1="145" y1="60" x2="145" y2="255" stroke="#555" stroke-width="4" /><line x1="270" y1="60" x2="270" y2="230" stroke="#555" stroke-width="4" /><line x1="395" y1="60" x2="395" y2="230" stroke="#555" stroke-width="4" /><!-- vertical lines, dx --><line x1="1140" y1="60" x2="1140" y2="255" stroke="#555" stroke-width="4" /><line x1="1025" y1="60" x2="1025" y2="230" stroke="#555" stroke-width="4" /><line x1="900" y1="60" x2="900" y2="230" stroke="#555" stroke-width="4" />
				<!-- 3 horizontal lines, for palcoscenico --><line x1="115" y1="115" x2="145" y2="115" stroke="#555" stroke-width="4" /><line x1="240" y1="115" x2="270" y2="115" stroke="#555" stroke-width="4" /><line x1="365" y1="115" x2="395" y2="115" stroke="#555" stroke-width="4" /><!-- horizontal lines, dx --><line x1="1140" y1="115" x2="1170" y2="115" stroke="#555" stroke-width="4" /><line x1="1025" y1="115" x2="1055" y2="115" stroke="#555" stroke-width="4" /><line x1="900" y1="115" x2="930" y2="115" stroke="#555" stroke-width="4" />
				<!-- text labels, format styles --><style>.big { font: bold 3em sans-serif; } .med { font: bold 2em sans-serif; } .let { font: bold 1.5em sans-serif; } .sma { font: bold 0.8em sans-serif; } </style>
				<text x="645" y="65" class="big" fill="#fff" text-anchor="middle">TEATRO SOCIALE</text><text x="645" y="100" class="med" fill="#fff" text-anchor="middle">palcoscenico</text>
				<text x="305" y="50" class="sma" fill="#909">PROSCENIO 1</text><text x="906" y="50" class="sma" fill="#909">PROSCENIO 1</text><text x="175" y="50" class="sma" fill="#909">PROSCENIO 2</text><text x="1030" y="50" class="sma" fill="#909">PROSCENIO 2</text><text x="60" y="50" class="sma" fill="#909">PROSCENIO 3</text><text x="1140" y="50" class="sma" fill="#909">PROSCENIO 3</text>
				<text x="405" y="135" class="sma" fill="#909">BARCACCIA sx</text><text x="805" y="135" class="sma" fill="#909">BARCACCIA dx</text>
				<text x="635" y="135" class="sma" fill="#e0e" text-anchor="end">LATO SINISTRO</text><text x="655" y="135" class="sma" fill="#e0e" text-anchor="start">LATO DESTRO</text>
				<text x="645" y="275" class="med" fill="#909" text-anchor="middle">PLATEA</text><text x="645" y="470" class="med" fill="#909" text-anchor="middle">PLATEA</text><text x="645" y="570" text-anchor="middle" class="let" fill="#e0e">CENTRALE</text><rect x="582" y="524" width="130" height="26" fill="none" stroke="#666" stroke-width="2" stroke-dasharray="10 5"></rect><text x="645" y="542" text-anchor="middle" class="sma" fill="#666">REGIA</text>
				<text x="645" y="595" class="med" fill="#909" text-anchor="middle">GALLERIA 1</text><text x="645" y="740" class="med" fill="#909" text-anchor="middle">GALLERIA 2</text><text x="645" y="935" class="med" fill="#909" text-anchor="middle">GALLERIA 3</text>
				<text x="50" y="560" class="let" fill="#e0e" text-anchor="start">LATO</text><text x="50" y="590" class="let" fill="#e0e" text-anchor="start">SINISTRO</text>
				<text x="1240" y="560" class="let" fill="#e0e" text-anchor="end">LATO</text><text x="1240" y="590" class="let" fill="#e0e" text-anchor="end">DESTRO</text>
				<text x="645" y="910" class="let" fill="#e0e" text-anchor="middle">CENTRALE</text>
				<text x="310" y="110" class="med" fill="#909" transform="rotate(270 310,110)" text-anchor="end">GALLERIA 1</text><text x="1000" y="110" class="med" fill="#909" transform="rotate(270 1000,110)" text-anchor="end">GALLERIA 1</text>
				<text x="185" y="110" class="med" fill="#909" transform="rotate(270 185,110)" text-anchor="end">GALLERIA 2</text><text x="1125" y="110" class="med" fill="#909" transform="rotate(270 1125,110)" text-anchor="end">GALLERIA 2</text>
				<text x="75" y="110" class="med" fill="#909" transform="rotate(270 75,110)" text-anchor="end">GALLERIA 3</text><text x="1235" y="110" class="med" fill="#909" transform="rotate(270 1235,110)" text-anchor="end">GALLERIA 3</text>
				<!-- Platea, central-letters --><text x="644" y="178" class="let" fill="#000" text-anchor="middle">A</text><text x="644" y="196" class="let" fill="#000" text-anchor="middle">B</text><text x="644" y="214" class="let" fill="#000" text-anchor="middle">C</text><text x="644" y="232" class="let" fill="#000" text-anchor="middle">D</text><text x="644" y="250" class="let" fill="#000" text-anchor="middle">E</text><text x="644" y="295" class="let" fill="#000" text-anchor="middle">F</text><text x="644" y="313" class="let" fill="#000" text-anchor="middle">G</text><text x="644" y="331" class="let" fill="#000" text-anchor="middle">H</text><text x="644" y="349" class="let" fill="#000" text-anchor="middle">I</text><text x="644" y="367" class="let" fill="#000" text-anchor="middle">L</text><text x="644" y="385" class="let" fill="#000" text-anchor="middle">M</text><text x="644" y="403" class="let" fill="#000" text-anchor="middle">N</text><text x="644" y="421" class="let" fill="#000" text-anchor="middle">O</text><text x="644" y="439" class="let" fill="#000" text-anchor="middle">P</text>
				<!-- Gallerie 1-2-3, top-letters --><text x="85" y="320" class="let" fill="#000" text-anchor="middle" transform="rotate(60 85,320)">G</text><text x="1210" y="320" class="let" fill="#000" text-anchor="middle" transform="rotate(-60 1210,320)">G</text><text x="195" y="295" class="let" fill="#000" text-anchor="middle" transform="rotate(60 195,295)">E</text><text x="1100" y="295" class="let" fill="#000" text-anchor="middle" transform="rotate(-60 1100,295)">E</text><text x="320" y="295" class="let" fill="#000" text-anchor="middle" transform="rotate(60 320,295)">E</text><text x="970" y="300" class="let" fill="#000" text-anchor="middle" transform="rotate(-60 970,295)">E</text>
				<!-- Galleria 1, central-letters --><text x="445" y="430" class="let" fill="#000" text-anchor="middle" transform="rotate(40 445,425)">A</text><text x="850" y="430" class="let" fill="#000" text-anchor="middle" transform="rotate(-40 845,430)">A</text><text x="430" y="450" class="let" fill="#000" text-anchor="middle" transform="rotate(40 430,445)">B</text><text x="865" y="455" class="let" fill="#000" text-anchor="middle" transform="rotate(-40 855,450)">B</text><text x="415" y="470" class="let" fill="#000" text-anchor="middle" transform="rotate(40 415,465)">C</text><text x="880" y="480" class="let" fill="#000" text-anchor="middle" transform="rotate(-40 865,470)">C</text><text x="400" y="490" class="let" fill="#000" text-anchor="middle" transform="rotate(40 400,485)">D</text><text x="895" y="505" class="let" fill="#000" text-anchor="middle" transform="rotate(-40 875,490)">D</text>
				<!-- Galleria 2, central-letters --><text x="435" y="580" class="let" fill="#000" text-anchor="middle" transform="rotate(30 435,580)">A</text><text x="850" y="580" class="let" fill="#000" text-anchor="middle" transform="rotate(-30 850,580)">A</text><text x="420" y="600" class="let" fill="#000" text-anchor="middle" transform="rotate(30 420,600)">B</text><text x="865" y="600" class="let" fill="#000" text-anchor="middle" transform="rotate(-30 865,600)">B</text><text x="405" y="625" class="let" fill="#000" text-anchor="middle" transform="rotate(30 405,625)">C</text><text x="880" y="625" class="let" fill="#000" text-anchor="middle" transform="rotate(-30 880,625)">C</text><text x="390" y="650" class="let" fill="#000" text-anchor="middle" transform="rotate(30 390,650)">D</text><text x="895" y="650" class="let" fill="#000" text-anchor="middle" transform="rotate(-30 889,650)">D</text>
				<!-- Galleria 3, central-letters --><text x="480" y="755" class="let" fill="#000" text-anchor="middle" transform="rotate(15 480,755)">A</text><text x="800" y="755" class="let" fill="#000" text-anchor="middle" transform="rotate(-15 800,755)">A</text><text x="470" y="780" class="let" fill="#000" text-anchor="middle" transform="rotate(15 470,780)">B</text><text x="810" y="780" class="let" fill="#000" text-anchor="middle" transform="rotate(-15 810,780)">B</text><text x="460" y="805" class="let" fill="#000" text-anchor="middle" transform="rotate(15 460,805)">C</text><text x="820" y="805" class="let" fill="#000" text-anchor="middle" transform="rotate(-15 820,805)">C</text><text x="450" y="830" class="let" fill="#000" text-anchor="middle" transform="rotate(15 450,830)">D</text><text x="830" y="830" class="let" fill="#000" text-anchor="middle" transform="rotate(-15 830,830)">D</text><text x="440" y="855" class="let" fill="#000" text-anchor="middle" transform="rotate(15 440,855)">E</text><text x="840" y="855" class="let" fill="#000" text-anchor="middle" transform="rotate(-15 840,855)">E</text><text x="430" y="880" class="let" fill="#000" text-anchor="middle" transform="rotate(15 430,880)">F</text><text x="850" y="880" class="let" fill="#000" text-anchor="middle" transform="rotate(-15 850,880)">F</text>
				<!-- legenda --><text x="50" y="860" text-anchor="start" class="let" fill="#333">Posti</text><text x="50" y="885" text-anchor="start" class="let" fill="#090">&#0149;&nbsp;disponibili</text><text x="50" y="910" text-anchor="start" class="let" fill="#f90">&#0149;&nbsp;da confermare</text><text x="50" y="935" text-anchor="start" class="let" fill="#c00">&#0149;&nbsp;confermati e prenotati</text>
				<!-- this is the Javascript that change color of seat for booking them --><script type="text/ecmascript"><![CDATA[
					function checkseat(seatid) { // This Javascript is for change seat status (free/standby) but it's executed only by a logged user, see below...
						var seatobj=document.getElementById("seat_"+seatid);
						var bookobj=document.getElementById("book_"+seatid);
						if (seatobj.getAttribute("fill") == "rgb(0, 205, 0)") { // Seat is free (green), so it change to standby (orange)
							seatobj.setAttribute("fill", "rgb(255, 205, 0)");
							if (document.getElementById("book_"+seatid).checked == true) {
								document.getElementById("book_"+seatid).checked = false;
								document.getElementById("euro").innerHTML = (Number(document.getElementById("euro").innerHTML)-5).toFixed(2);
							} else { // With this 'If/Else' is changed and displayed in realtime also the seats and total price in the reservation form
								document.getElementById("book_"+seatid).checked = true;
								document.getElementById("euro").innerHTML = (Number(document.getElementById("euro").innerHTML)+5).toFixed(2);
							}
						} else {
							if (seatobj.getAttribute("fill") == "rgb(255, 205, 0)") { // Seat is in standby (orange), so it change to free (green)
								seatobj.setAttribute("fill", "rgb(0, 205, 0)");
								if (document.getElementById("book_"+seatid).checked == true) {
									document.getElementById("book_"+seatid).checked = false;
									document.getElementById("euro").innerHTML = (Number(document.getElementById("euro").innerHTML)-5).toFixed(2);
								} else { // With this 'If/Else' is changed and displayed in realtime also the seats and total price in the reservation form
									document.getElementById("book_"+seatid).checked = true;
									document.getElementById("euro").innerHTML = (Number(document.getElementById("euro").innerHTML)+5).toFixed(2);
								}
							} else { // Seat is already reserved (red/blue)
								alert(document.getElementById("note_"+seatid).innerHTML);
							}
						}
						if (Number(document.getElementById("euro").innerHTML) > 0) {
							if (ValidateEmail(document.prenota.email)) {
								document.getElementById('prenota-submit').type = "submit";
								document.getElementById('prenota-submit').className = "btn btn-primary";
							}
							document.getElementById('seats-invalid').style.display = "none";
						} else {
							document.getElementById('prenota-submit').className = "btn btn-secondary";
							document.getElementById('prenota-submit').type = "button";
							document.getElementById('seats-invalid').style.display = "block";
						}
					}
					]]></script><?php
				$sql = "SELECT rowid, FROM live_sid"; // Create a query to read all the seats data from DB
				$query = $db->query($sql); // Executing the query
				while($row = $query->fetchArray()) { // Generating the SVG code for every seat with color, popup and click funcions and data...
					?><circle 
						id="seat_<?=$row['idseat']?>" 
						r="<? echo (($row['idseat']>=92 && $row['idseat']<=329) ? '9' : '11' )?>" 
						cx="<?=$row['cx']?>" 
						cy="<?=$row['cy']?>" 
						fill="<?php echo (($row['md5'] != "" && $row['md5'] === $m) ? "rgb(0, 0, 205)" : ($row['status'] === '0' ? "rgb(0, 205, 0)" : ($row['status'] === '1' ? "rgb(255, 205, 0)" : "rgb(205, 0, 0)")))?>"
						onclick="<?php
							if ( isset($_SESSION['rule']) && $_SESSION['rule'] < '3' ) { // If a delegated is logged in may reserve seats with above Javascript
								echo "checkseat('".$row['idseat']."')";
							} else { // If nobody is logged in then showing only an alert about free or reserved seats
								echo ($row['status']>1 ? "alert('Posto n. ".$row['seat']." - ".$row['zone'].". Lato ".$row['side'].", Fila ".$row['file']." | PRENOTATO ".$row['timestamp']."')" : "alert('Le Prenotazioni sono riservate alle persone Incaricate, cercatele su: https://www.canossacampus.it/LIVE')" );
							} ?>">
						<title id="note_<?=$row['idseat']?>"><?php echo "Posto n. ".$row['seat']." - ".$row['zone'].". Lato ".$row['side'].", Fila ".$row['file'].
						($row['status']>1 ? " | PRENOTATO ".$row['timestamp'] : ""); ?></title>
					</circle><?php
				} ?>
			</svg>
		</div>
	</body>
</html><?php
function mail_attachment($mailto, $path, $filename, $subject, $message) {
	$file = $path.$filename;
	$file_size = filesize($file);
	$handle = fopen($file, "r");
	$content = fread($handle, $file_size);
	fclose($handle);
	$content = chunk_split(base64_encode($content));
	$uid = md5(uniqid(time()));
	$p = "\r\n";
	$header = "From: "."\"Campus Happening Live, 2019\""." <"."live2019@canossacampus.it".">".$p;
	$header .= "Reply-To: "."prenotazioni.live2019@canossacampus.it".$p;
	$header .= "MIME-Version: 1.0".$p;
	$header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"".$p.$p;
	$body = "--".$uid.$p;
	$body .= "Content-Type: text/html; charset=ISO-8859-1".$p;
	$body .= "Content-Transfer-Encoding: 8bit".$p.$p;
	$body .= $message.$p;
	$body .= "--".$uid.$p;
	$body .= "Content-Type: image/png; name=\"".$filename."\"".$p;
	$body .= "Content-Transfer-Encoding: base64".$p;
	$body .= "Content-Disposition: attachment; filename=\"".$filename."\"".$p.$p;
	$body .= $content.$p;
	$body .= "--".$uid."--";
	if (mail($mailto, $subject, $body, $header)) {
		return true;
	} else {
		return false;
	}
} ?>
