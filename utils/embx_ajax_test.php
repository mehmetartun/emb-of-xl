<?php
include("embx_dbconn.php");
include("embx_functions_test.php");
//
//		FIle for processing the single page application
//
$pagefunction = $_GET["pf"];
switch ($pagefunction) {
	case "cleantables":
		embx_cleantables();
		break;
	
	case "processlogfile":
		$filename = $_GET["filename"];
		//$exists = embx_lookup("processed","filename","'". $filename . "'","filename");
		if ($exists) {
			//mysql_query("update processed set processed = 1 where filename='" . $filename . "'");
		} else {
			embx_processfile($filename);
			//mysql_query("insert into processed (filename, processed) values ('" . $filename . "',1) ");
		}
		break;
	case "logfilelist":
		$filelist = scandir("../source_files");
		echo "<ol>";
		foreach ($filelist as $filename) {
			if (strpos($filename, ".csv")) {
				$proc = embx_lookup("processed","filename","'" . $filename . "'","processed");
				if ($proc == 1) {
					echo "<li>" . $filename . " <span class='label success'>DONE</span></li>";
				} else {
					$uniq = uniqid();
					echo "<li>" . $filename . " <a class='label' id='" . $uniq . "'>PROC</a></li>";
					?>
					<script>
					$("#<? echo $uniq; ?>").click(function(){
						$("#<? echo $uniq; ?>").addClass("alert");
						embx_js_processfile("<? echo $filename; ?>");
					});
					</script>
					<?
				}
			} else {
				//echo "<li><pre>" . $filename . "</pre></li>";
			}
		}
		echo "</ol>";
	
		break;
	case "bondlist":
		$bonds = embx_sql("select distinct isin from orders order by isin asc");
		if (count($bonds)>0){
			echo "<ul>";
			foreach ($bonds as $bond){
				if ($bond["isin"]){
					echo "<li><a href='javascript:embx_getbonddetail(\"" . $bond["isin"] . "\")'>" . $bond["isin"] . "</a></li>";
				}
			}
			echo "</ul>";
		}	
		break;
	case "bondlistforday":
		$bonds = embx_sql("select distinct isin from orders where date(ordertime) = '" . $_GET["tradingday"] . "' order by isin asc");
		if (count($bonds)>0){
			echo "<ul>";
			foreach ($bonds as $bond){
				if ($bond["isin"]){
					echo "<li><a href='javascript:embx_getbonddetailforday(\"" . $bond["isin"] . "\",\"" . $_GET["tradingday"]  . "\")'>" . $bond["isin"] . "</a></li>";
				}
			}
			echo "</ul>";
		}	
		break;
	case "bonddetail":
		date_default_timezone_set("UTC");
		$isin = $_GET["isin"];
		//embx_bondupdate($isin);
		$orders = embx_sql("select * from orders where isin ='" . $isin . "' order by ordertime");
		echo "<h6>Orders for ISIN: " . $isin . "</h6>";
		echo "<table>";
		if (count($orders)>0){
			$prevdate = date_format(date_create($orders[0]["ordertime"]),"j F Y" );
			//echo "<div class='alert-box'>" . $prevdate . "</div>";
			//echo "<ul>";
			echo "<tr><th colspan='9'>" . $prevdate . "</th></tr>";
			foreach ($orders as $order) {
				if ($order["ordertype"] == "Live") { 
					$price = "<span class='label'>" . number_format($order["price"],4) . "</span>";
				}	else {
					$price = "<span class='label secondary'>" . number_format($order["price"],4) . "</span>";
				}
				if ($order["side"] == "BUY") { 
					$side = "<span class='label success'>B</span>";
				}	else {
					$side = "<span class='label alert'>S</span>";
				}
				$thedate =  date_format(date_create($order["ordertime"]),"j F Y" );
				if ($thedate != $prevdate){
					//echo "</ul><div class='alert-box'>" . $thedate . "</div><ul>";
					echo "<tr><th colspan='9'>" . $thedate . "</th></tr>";
				}
				echo "<tr><td><strong>" . $order["orderid"] .  "</strong></td><td>"  . $order["username"] . "</td><td>" . substr($order["ordertime"],11,8) . "</td><td>" . $order["action"]  . "</td><td>" .  $side 
					. "</td><td>" . $price . "</td><td style='text-align: right;'>" . number_format($order["size"],0) . "</td><td>" 
					. substr($order["endtime"],11,8) . "</td><td>" . $order["reason"] . "</td></tr>";
				
				$prevdate = $thedate;
			}
			echo "</table>";
		}
		break;

		case "bonddetailforday":
			date_default_timezone_set("UTC");
			$isin = $_GET["isin"];
			//embx_bondupdate($isin);
			$orders = embx_sql("select * from orders where isin ='" . $isin . "' and date(ordertime) = '" . $_GET["tradingday"] ."' order by ordertime");
			echo "<h6>Orders for ISIN: " . $isin . "</h6>";
			echo "<table>";
			if (count($orders)>0){
				$prevdate = date_format(date_create($orders[0]["ordertime"]),"j F Y" );
				//echo "<div class='alert-box'>" . $prevdate . "</div>";
				//echo "<ul>";
				echo "<tr><th colspan='9'>" . $prevdate . "</th></tr>";
				foreach ($orders as $order) {
					if ($order["ordertype"] == "Live") { 
						$price = "<span class='label'>" . number_format($order["price"],4) . "</span>";
					}	else {
						$price = "<span class='label secondary'>" . number_format($order["price"],4) . "</span>";
					}
					if ($order["side"] == "BUY") { 
						$side = "<span class='label success'>B</span>";
					}	else {
						$side = "<span class='label alert'>S</span>";
					}
					$thedate =  date_format(date_create($order["ordertime"]),"j F Y" );
					if ($thedate != $prevdate){
						//echo "</ul><div class='alert-box'>" . $thedate . "</div><ul>";
						echo "<tr><th colspan='9'>" . $thedate . "</th></tr>";
					}
					echo "<tr><td><strong>" . $order["orderid"] .  "</strong></td><td>"  . $order["username"] . "</td><td>" . substr($order["ordertime"],11,8) . "</td><td>" . $order["action"]  . "</td><td>" .  $side 
						. "</td><td>" . $price . "</td><td style='text-align: right;'>" . number_format($order["size"],0) . "</td><td>" 
						. substr($order["endtime"],11,8) . "</td><td>" . $order["reason"] . "</td></tr>";
				
					$prevdate = $thedate;
				}
				echo "</table>";
			}
			break;



} 




?>

