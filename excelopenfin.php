<?php
include("utils/embx_dbconn.php");
include("utils/embx_functions.php");  //helsp
?>
<!doctype html>
<html class="no-js" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EMB Openfin Excel</title>
	<link rel="shortcut icon" href="images/embx-a-favicon.ico">
	<link rel="icon" sizes="16x16 32x32 64x64" href="images/embx-a-favicon.ico">
	<link rel="icon" type="image/png" sizes="196x196" href="images/embx-a-favicon-192.png">
	<link rel="icon" type="image/png" sizes="160x160" href="images/embx-a-favicon-160.png">
	<link rel="icon" type="image/png" sizes="96x96" href="images/embx-a-favicon-96.png">
	<link rel="icon" type="image/png" sizes="64x64" href="images/embx-a-favicon-64.png">
	<link rel="icon" type="image/png" sizes="32x32" href="images/embx-a-favicon-32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="images/embx-a-favicon-16.png">
	<link rel="apple-touch-icon" href="images/embx-a-favicon-57.png">
	<link rel="apple-touch-icon" sizes="114x114" href="images/embx-a-favicon-114.png">
	<link rel="apple-touch-icon" sizes="72x72" href="images/embx-a-favicon-72.png">
	<link rel="apple-touch-icon" sizes="144x144" href="images/embx-a-favicon-144.png">
	<link rel="apple-touch-icon" sizes="60x60" href="images/embx-a-favicon-60.png">
	<link rel="apple-touch-icon" sizes="120x120" href="images/embx-a-favicon-120.png">
	<link rel="apple-touch-icon" sizes="76x76" href="images/embx-a-favicon-76.png">
	<link rel="apple-touch-icon" sizes="152x152" href="images/embx-a-favicon-152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="images/embx-a-favicon-180.png">
	<meta name="msapplication-TileColor" content="#FFFFFF">
	<meta name="msapplication-TileImage" content="images/embx-a-favicon-144.png">
	<meta name="msapplication-config" content="images/browserconfig.xml">
    <link rel="stylesheet" href="stylesheets/app.css" />
    <link rel="stylesheet" href="images/foundation-icons.css" />	
	<link href='http://fonts.googleapis.com/css?family=Source+Code+Pro:200,300,400,500,600,700,900' rel='stylesheet' type='text/css'>
	<link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700,900,200italic,300italic,400italic,600italic,700italic,900italic' rel='stylesheet' type='text/css'>
	<link href='http://fonts.googleapis.com/css?family=Roboto+Condensed:200,300,400,600,700,900,200italic,300italic,400italic,600italic,700italic,900italic' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" type="text/css" href="stylesheets/jquery.datetimepicker.css"/>
	
    <script src="bower_components/modernizr/modernizr.js"></script>
	<style>
	.file-upload {
	  position: relative;
	  overflow: hidden;
	 }
	.file-upload input.file-input {
	  position: absolute;
	  top: 0;
	  right: 0;
	  margin: 0;
	  padding: 0;
	  font-size: 20px;
	  cursor: pointer;
	  opacity: 0;
	  filter: alpha(opacity=0);
 	 }
	 .activeworkbook {
		 color: green;
	 }
	</style>
  </head>
<body>
	<nav class="top-bar" data-topbar role="navigation" style="margin-bottom: 20px;">
		<ul class="title-area">
			<li class="name">
				<h1><a href="#">EMBonds Openfin Excel</a></h1>
			</li>
			<!-- Remove the class "menu-icon" to get rid of menu icon. Take out "Menu" to just have icon alone -->
			<li class="toggle-topbar menu-icon">
				<a href="#"><span>Menu</span></a>
			</li>
		</ul>

		<section class="top-bar-section">
			<!-- Right Nav Section -->
			<ul class="right">
				<!-- <li class="active"><a href="#">Right Button Active</a></li> -->
				<!--<li ><a id="logfilelist" href="#">Log Files</a></li>
				<li ><a id="bondlist" href="#">Bonds</a></li>
				<li ><a id="userlist" href="#">Users</a></li>
				<li ><a id="cptylist" href="#">Cptys</a></li>
				<li ><a id="tradesummary" href="#">Trades</a></li>
				<li class="has-dropdown">
					<a href="#">Utils</a>
					<ul class="dropdown">
						<li><a id="processtradingday" href="endofday.php">End of Day</a></li>
						<li><a id="createaudio" href="sound.php">Audio Generation</a></li>
					</ul>
				</li>
				<li class="has-dropdown">
					<a href="#">Graphs</a>
					<ul class="dropdown">
						<li><a id="graph_isincount" class="graphlink" href="#">ISIN Count</a></li>
						<li><a id="graph_isincount_live" class="graphlink" href="#">Live ISIN Count</a></li>
						<li><a id="graph_usercount" class="graphlink" href="#">User Count</a></li>
						
					</ul>
				</li>
					-->
			</ul>

			<input type='hidden' id='currentworkbooks' value='' />
			<input type='hidden' id='activeworkbook' value='' />
			<input type='hidden' id='wswait' value=''/>
			<input type='hidden' id='embondsworkbook' value='' />
			

		<!-- Left Nav Section -->
		<ul class="left">
			
			<li><a href="#"><i id="heartbeatsign" style="color: #ff6666; opacity: 0.5;"class="fi-heart"></i></a></li>
			<li id="calculationMode"></li>
		</ul>
	</section>
	  </nav>

		<div class="row">
			<div class="small-12 large-3 columns" id="detailcolumn">
				<div class='row'>
					<div class="small-12 columns">
						<h5 id="detailheader">Excel</h5>
					</div>
					<div id="detailcontent" class="small-12 columns">
						<label>Excel Connection Status</label>
						<p id="excelconnectionstatus">Checking Connection</p>

						<label>Excel Open Workbooks</label>
						<ul id="excelopenworkbooks" class="no-bullet">
							<li>No Workbook Open</li>
						</ul>

						<label>All Excel Sheets</label>
						<ul class='no-bullet' id="excelsheets"></ul>
						<label>Last Heartbeat with Excel</label>
						<p id="lasthearbeat"></p>
						<label>EMBonds Cell</label>
						<p id="embondscell"></p>
						
					</div>
					<div id="orderadding" class="small-12 columns">
						<a href="#" id="orderadd" class="button expand">Order Add</a>
					</div>
				</div>
			</div>
			<div class="small-12 large-9 columns">
				<h5 id="pageheader">Connection Properties</h5>
				<div class="row">
					<div class="small-12 columns">
						<form>
							<div  class="small-4 columns">
								<label>Server
								<input id="server" name="server" value="demo" type="text" /></label>
							</div>
							<div  class="small-3 columns">
								<label>Username
								<input id="username" name="username" value="BANK1U03" type="text" /></label>
							</div>
							<div  class="small-5 columns">
								<label>Password</label>
								<div class="row">
									<div class="small-7 columns">
										<input id="password" name="password" value="123" type="text" />
									</div>
									<div  class="small-5 columns">
										<a href="#" id="connecttoserver" class="button postfix">Connect</a>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<div class="row">
					<div id="pagecontent" class="small-12 columns">
						
					</div>
				</div>
				<div class="row">
					<div id="messages" class="small-12 columns">
					</div>
				</div>
			</div>
		</div>
		<input id="embondspublishingworksheet" value="0" type="hidden" />
    <script src="bower_components/jquery/dist/jquery.min.js"></script>
	<script src="js/jquery.form.js"></script>	
    <script src="bower_components/foundation/js/foundation.min.js"></script>
	<script src="hc/js/highcharts.js"></script>
	<script src="hc/js/modules/exporting.js"></script>
	<script src="js/jquery.datetimepicker.full.js"></script>
	<script src="js/ExcelAPI NEW.js"></script>
	<script src="js/moment.min.js"></script>
	<script src="js/openfinexcel.js"></script>
    <!-- <script src="js/app.js"></script> -->
	<script src="js/papaparse.js"></script>
	<script src="js/embonds-client_tester-util-v1.0.4.js"></script>
	<script src="js/embonds-client-tester-v1.0.4.js"></script>
	   <script src="js/numeral.min.js"></script>	
	
	
	
  </body>
</html>









