<?php
include("embx_dbconn.php");
include("embx_functions.php");
//
//		FIle for processing the single page application
//
$pagefunction = $_GET["pf"];
switch ($pagefunction) {
	case "settings":
	
	

	
	break; // settings
	
	
	case "cleantables":
		embx_cleantables();
		break;
	
	case "processlogfile":
		$filename = $_GET["filename"];
		$exists = embx_lookup("processed","filename","'". $filename . "'","id");
		if ($exists) {
			//mysql_query("update processed set processed = 1 where filename='" . $filename . "'");
		} else {
			$rs = EMBXDB::get()->query("insert into processed (filename, processed) values ('" . $filename . "',1) ");
			embx_processfile($filename);
		}
		break; // processlogfile

	case "showrejects":
		$filename = $_GET["filename"];
		$exists = embx_lookup("processed","filename","'". $filename . "'","id");
		echo "Exists: " . $exists . "<br />";
		if ($exists){
			$rejnoid = embx_sql("select * from rejects where fileid = " . $exists, " and isnull(logid)");
			$rejid = embx_sql("select * from rejects where fileid = " . $exists, " and not isnull(logid)");
			echo "Rejects without ID <br />";
			foreach($rejnoid as $rej){
				echo $rej["content"] . " " . "<br/>";
			}
			echo "Rejects with ID <br />";
			foreach($rejid as $rej){
				echo $rej["content"] . " " . "<br/>";
			}
		}
	break;
	
	case "fileupload":
		$data = array();
		if(isset($_GET['files']))
		{  
		    $error = false;
		    $files = array();
		    $uploaddir = './uploads/';
		    foreach($_FILES as $file)
		    {
		        if(move_uploaded_file($file['tmp_name'], $uploaddir .basename($file['name'])))
		        {
		            $files[] = $uploaddir .$file['name'];
		        }
		        else
		        {
		            $error = true;
		        }
		    }
		    $data = ($error) ? array('error' => 'There was an error uploading your files') : array('files' => $files);
		}
		else
		{
		    $data = array('success' => 'Form was submitted', 'formData' => $_POST);
		}
		echo json_encode($data);
	break;

	case "logfilelist":
		$filelist =  scandir("../source_files");
		natcasesort($filelist);
		krsort($filelist);
		
		echo "<ol>";
		foreach ($filelist as $filename) {
			if (strpos($filename, ".csv")) {
				$proc = embx_lookup("processed","filename","'" . $filename . "'","processed");
				if ($proc == 1) {
					$uniq = uniqid();
					echo "<li>" . $filename . " <span class='label success'>DONE</span>  <a class='label alert' id='" . $uniq . "rej" . "'>REJECTS</a></li>";

					?>
					<script>
					$("#<? echo $uniq; ?>").click(function(){
						$("#<? echo $uniq . "rej"; ?>").addClass("alert");
						embx_js_showrejects("<? echo $filename; ?>");
					});
					</script>
					<?
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
		$bondnames = embx_sql("select * from bonds");
		
		if (count($bonds)>0){
			echo "<ul>";
			foreach ($bonds as $bond){
				if ($bond["isin"]){
					$bondname = embx_getbondname_fromisin($bond["isin"],$bondnames);	
					echo "<li><a href='javascript:embx_getbonddetail(\"" . $bond["isin"] . "\")'>" . $bond["isin"] . "</a> ".$bondname." </li>";
				}
			}
			echo "</ul>";
		}	
	break;
	case "bondlistforday":
		$bondnames = embx_sql("select * from bonds");
		$bonds = embx_sql("select distinct isin from orders where date(ordertime) = '" . $_GET["tradingday"] . "' order by isin asc");
		if (count($bonds)>0){
			echo "<ul>";
			foreach ($bonds as $bond){
				if ($bond["isin"]){
					$bondname = embx_getbondname_fromisin($bond["isin"],$bondnames);
					echo "<li><a href='javascript:embx_getbonddetailforday(\"" . $bond["isin"] . "\",\"" . $_GET["tradingday"]  . "\")'>" . $bondname . "</a>
						&nbsp;&nbsp; <a href='javascript:embx_graphmarket(\"" . $bond["isin"] . "\",\"" . $_GET["tradingday"]  . "\")'><i class='fi-graph-trend'></i></a></li>";
				}
			}
			echo "</ul>";
		}	
		break;
	case "bondlistfordayforuser":
		$user = $_GET["user"];
		$bonds = embx_sql("select distinct isin from orders where date(ordertime) = '" . $_GET["tradingday"] . "' 
					and username = '" .  $user . "'  order by isin asc");
		if (count($bonds)>0){
			echo "<ul>";
			foreach ($bonds as $bond){
				if ($bond["isin"]){
					echo "<li><a href='javascript:embx_getbonddetailforday(\"" . $bond["isin"] . "\",\"" . $_GET["tradingday"]  . "\")'>" . $bond["isin"] . "</a>
						</li>";
				}
			}
			echo "</ul>";
		}	
		break;
		case "bondlistfordayforcpty":
			$cpty = $_GET["cpty"];
			$bonds = embx_sql("select distinct isin from orders where date(ordertime) = '" . $_GET["tradingday"] . "' 
						and left(username,4) = '" .  $cpty . "'  order by isin asc");
			if (count($bonds)>0){
				echo "<ul>";
				foreach ($bonds as $bond){
					if ($bond["isin"]){
						echo "<li><a href='javascript:embx_getbonddetailforday(\"" . $bond["isin"] . "\",\"" . $_GET["tradingday"]  . "\")'>" . $bond["isin"] . "</a></li>";
					}
				}
				echo "</ul>";
			}	
			break; // bondlistfordayforuser
			
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
				echo "	<tr>
							<td><strong>" . $order["orderid"] .  "</strong></td>
							<td>"  . $order["username"] . "</td>
							<td>" . substr($order["ordertime"],11,8) . "</td>
							<td>" . $order["action"]  . "</td>
							<td>" .  $side . "</td>
							<td>" . $price . "</td>
							<td style='text-align: right;'>" . number_format($order["size"],0) . "</td>
							<td>" . substr($order["endtime"],11,8) . "</td><td>" . $order["reason"] . "</td>
						</tr>";
						$prevdate = $thedate;
			}
			echo "</table>";
		}
	break;
	
	case "isincountperday":
	
	$isincountperday = embx_sql("select tradingday, count(isin) as isincount from (select date(ordertime) as tradingday, 
							isin from orders group by tradingday, isin) as temptable group by tradingday");
	if (count($isincountperday) > 0){
		echo "<table class='embx-table'><thead><tr><th>Trading Day</th><th>ISINs</th></tr></thead><tbody>";
		foreach ($isincountperday as $isincount){
			echo "<tr><td>" . $isincount["tradingday"] . "</td><td>" . $isincount["isincount"]. "</td></tr>";
		}
		echo "</tbody></table>";
	}
	
	break;	
	case "userlist":
		$users = embx_sql("	select users.username, count(users.isin) as isincount 
							from (select distinct username, isin from orders) as users 
							group by users.username 
							order by isincount desc");
		if (count($users)>0){
			echo "<ul>";
			foreach ($users as $user){
				if ($user["username"] && $user["username"] != "system"){
					echo "<li><a href='javascript:embx_getuserdetail(\"" . $user["username"] . "\")'>" 
								. $user["username"] .  "</a> ".$user["isincount"]." ISINs</li>";
				}
			}
			echo "</ul>";
		}	
	break;
		
	case "cptylist":
		$cptys = embx_sql(" select cptys.cpty, count(cptys.isin) as isincount 
							from (select distinct left(username,4) as cpty, isin from orders) as cptys 
							group by cptys.cpty order by isincount desc");
		if (count($cptys)>0){
			echo "<ul>";
			foreach ($cptys as $cpty){
				if ($cpty["cpty"] && $cpty["cpty"] != "syst"){
					echo "<li><a href='javascript:embx_getcptydetail(\"" . $cpty["cpty"] . "\")'>" 
							. $cpty["cpty"] .  "</a> ". $cpty["isincount"]." ISINs</li>";
				}
			}
			echo "</ul>";
		}	
		break;

	case "userdetail":
		$user = $_GET["user"];
		$detail = embx_sql("	select t1.tradingday, count(isin) as isincount 
								from (	select distinct date(ordertime) as tradingday, isin 
										from orders where username = '". $user ."') as t1 
								group by tradingday order by tradingday desc limit 20");
		$ret = embx_columngraph(	"pagecontent", 
								$detail, 
								"tradingday", 
								"isincount", 
								"Date", 
								"No of ISINs", 
								"Number of ISINs quoted by", 
								"Quoted ISINs by " . $user . " only", 
								"$.get('utils/embx_ajax.php?pf=bondlistfordayforuser&tradingday=' + this.category + '&user=" . $user . "',function(data){
										$('#detailcontent').html(data);
										});
								$('#detailheader').html('ISIN\'s quoted by " . $user . " <br />on ' + this.category);", 
								"<b>{point.y:,.0f} ISINs</b>" );
		echo  "<script>" . $ret . "</script>";
		
		break;
		
		case "cptydetail":
		$cpty = $_GET["cpty"];
		$detail = embx_sql("select t1.tradingday, count(isin) as isincount from (select distinct date(ordertime) as tradingday, 
					isin from orders where left(username,4) = '". $cpty ."') as t1 group by tradingday order by tradingday desc limit 20");
		
		var_dump($detail);
		$ret = embx_columngraph("pagecontent", 
								$detail, 
								"tradingday", 
								"isincount", 
								"Date", 
								"No of ISINs", 
								"Number of ISINs quoted by", 
								"Quoted ISINs by " . $cpty . " only", 
								"$.get('utils/embx_ajax.php?pf=bondlistfordayforcpty&tradingday=' + this.category + '&cpty=" . $cpty . "',function(data){
										$('#detailcontent').html(data);
										});
								$('#detailheader').html('ISIN\'s quoted by " . $cpty . " <br />on ' + this.category);", 
								"<b>{point.y:,.0f} ISINs</b>" );
		echo  "<script>" . $ret . "</script>";
		
		break;
		



		case "marketsnapshot":
			date_default_timezone_set("UTC");
			
			$tradingday = ($_GET["tradingday"]);
			$starttradingday = "'" . $tradingday . " 00:00:00'";
			$minutes = $_GET["minutes"];
			
				$sst = strtotime($tradingday) + $minutes * 60;
				$snapshottime = date("Y-m-d H:i:s",$sst);
				//echo $snapshottime;
				if ($minutes == ""){
					$snapshottime = $tradingday . " " . $_GET["snapshottime"];
				}
				echo $snapshottime;
				//echo $hour . "<br />";
				//$interval = new DateInterval('PT' . $hour . 'H');
				//$snapshottime = date_add($tradingday, $interval);
				//echo "snapshottime = " . date_format($snapshottime,"Y-m-d H:i:s")  . "<br />";

				$isin = $_GET["isin"];
				
											

				$sql = "select price, size, username from orders where side = 'BUY'
						and ordertime > " . $starttradingday . " and  ordertime <= '" . $snapshottime . 
					"' and ( endtime > '" . $snapshottime . "' or isnull(endtime) ) " . 
					" and ordertype = 'Live' and isin = '" . $isin . "' order by price desc";
				$bidlive = embx_sql($sql);
				//echo $sql . "<br />";
				//echo "<br/>Bid live <br />";
				//var_dump($bidlive);
				$sql = "select price, size, username from orders where side = 'SELL'  
						and ordertime > " . $starttradingday . "   and ordertime <= '" . $snapshottime . 
					"' and ( endtime > '" . $snapshottime . "' or isnull(endtime) ) " . 
					" and ordertype = 'Live' and isin = '" . $isin . "'  order by price asc";
				$asklive = embx_sql($sql);

				
				//echo $sql . "<br />";
				//echo "<br/>Ask live <br />";
				//var_dump($asklive);

				$sql = "select price, size, username from orders where side = 'BUY' 
						and ordertime > " . $starttradingday . "  and ordertime <= '" . $snapshottime . 
					"' and ( endtime > '" . $snapshottime . "' or isnull(endtime) ) " . 
					"  and ordertype = 'Indicative' and isin = '" . $isin . "'  order by price desc";
				$bidindicative = embx_sql($sql);
				//echo $sql . "<br />";
				//echo "<br/>Bid indic <br />";
				//var_dump($bidindicative);
				
				$sql = "select price, size, username from orders where side = 'SELL' and  
						ordertime > " . $starttradingday . " and  ordertime <= '" . $snapshottime .  
					"' and ( endtime > '" . $snapshottime . "' or isnull(endtime) ) " . 
					"  and ordertype = 'Indicative' and isin = '" . $isin . "'  order by price asc";
				$askindicative = embx_sql($sql);
				//echo "<br/>Ask indic <br />";
				//var_dump($askindicative);
				//echo $sql . "<br />";

				echo "<h5>Market at " . $snapshottime . " "  .    "</h5>";
				$livebids = count($bidlive);
				$liveasks = count($asklive);
				$indicativebids = count($bidindicative);
				$indicativeasks = count($askindicative);
				?>
				<table>
					<thead>
						<tr>
							<th>Buyer</th>
							<th>Bid Amt</th>
							<th>Bid Price</th>
							<th>Ask Price</th>
							<th>Ask Amt</th>
							<th>Seller</th>
						</tr>
					</thead>
					<tbody>
				
				<?
				if ($livebids || $liveasks) {
					
					if ($livebids >= $liveasks) {
						$i=0;
						for($j=0; $j < $livebids; $j+=1){
							if ($i< $liveasks){
								?>
									<tr><td><? echo $bidlive[$j]["username"];?></td>
										<td><? echo number_format($bidlive[$j]["size"],0);?></td>
										<td><span class='label'><? echo number_format($bidlive[$j]["price"],4);?></span></td>
										<td><span class='label'><? echo number_format($asklive[$j]["price"],4);?></td>
										<td><? echo number_format($asklive[$j]["size"],0);?></td>
										<td><? echo $asklive[$j]["username"];?></td>
									</tr>
								<?
							} else {
								?>
									<tr><td><? echo $bidlive[$j]["username"];?></td>
										<td><? echo number_format($bidlive[$j]["size"],0);?></td>
										<td><span class='label'><? echo number_format($bidlive[$j]["price"],4);?></td>
										<td></td>
										<td></td>
										<td></td>
									</tr>
								<?
							}
						$i = $i+1;	
						}
					} else {
						$i=0;
						for($j=0; $j < $liveasks; $j+=1){
							if ($i< $livebids){
								?>
									<tr>
										<td><? echo $bidlive[$j]["username"];?></td>
										<td><? echo number_format($bidlive[$j]["size"],0);?></td>
										<td><span class='label'><? echo number_format($bidlive[$j]["price"],4);?></td>
										<td><span class='label'><? echo number_format($asklive[$j]["price"],4);?></td>
										<td><? echo number_format($asklive[$j]["size"],0);?></td>
										<td><? echo $asklive[$j]["username"];?></td>
									</tr>
								<?
							} else {
								?>
									<tr>
										<td></td>
										<td></td>
										<td></td>
										<td><span class='label'><? echo number_format($asklive[$j]["price"],4);?></td>
										<td><? echo $asklive[$j]["size"];?></td>
										<td><? echo $asklive[$j]["username"];?></td>
									</tr>
								<?
							}
						$i = $i+1;	
						}						
					}
				}
				

				if ($indicativebids || $indicativeasks) {
					
					if ($indicativebids >= $indicativeasks) {
						$i=0;
						for($j=0; $j < $indicativebids; $j+=1){
							if ($i < $indicativeasks){
								?>
									<tr><td><? echo $bidindicative[$j]["username"];?></td>
										<td class='size'><? echo number_format($bidindicative[$j]["size"],0);?></td>
										<td><span class='label secondary'><? 
											echo number_format($bidindicative[$j]["price"],4);?></span></td>
										<td><span class='label secondary'><? echo number_format($askindicative[$j]["price"],4);?></td>
										<td class='size'><? echo number_format($askindicative[$j]["size"],0);?></td>
										<td><? echo $askindicative[$j]["username"];?></td>
									</tr>
								<?
							} else {
								?>
									<tr><td><? echo $bidindicative[$j]["username"];?></td>
										<td class='size'><? echo number_format($bidindicative[$j]["size"],0);?></td>
										<td><span class='label secondary'><? echo number_format($bidindicative[$j]["price"],4);?></td>
										<td></td>
										<td></td>
										<td></td>
									</tr>
								<?
							}
						$i = $i+1;	
						}
					} else {
						$i=0;
						for($j=0; $j < $indicativeasks; $j+=1){
							if ($i < $indicativebids){
								?>
									<tr>
										<td><? echo $bidindicative[$j]["username"];?></td>
										<td  class='size'><? echo number_format($bidindicative[$j]["size"],0);?></td>
										<td><span class='label secondary'><? echo number_format($bidindicative[$j]["price"],4);?></td>
										<td><span class='label secondary'><? echo number_format($askindicative[$j]["price"],4);?></td>
										<td class='size'><? echo number_format($askindicative[$j]["size"],0);?></td>
										<td><? echo $askindicative[$j]["username"];?></td>
									</tr>
								<?
							} else {
								?>
									<tr>
										<td></td>
										<td></td>
										<td></td>
										<td><span class='label secondary'><? echo number_format($askindicative[$j]["price"],4);?></td>
										<td class='size'><? echo number_format($askindicative[$j]["size"],0);?></td>
										<td><? echo $askindicative[$j]["username"];?></td>
									</tr>
								<?
							}
						$i = $i+1;	
						}						
					}
				}


				
		break;		
		
		case "tradesummary":
			date_default_timezone_set("UTC");
			
			//embx_bondupdate($isin);
			$orders = embx_sql("select * from trades  order by tradetime");
			echo "<h6>Trade Summary</h6>";
			echo "<table class='embx-table'>";
			if (count($orders)>0){
				$prevdate = date_format(date_create($orders[0]["tradetime"]),"j F Y" );
				//echo "<div class='alert-box'>" . $prevdate . "</div>";
				//echo "<ul>";
				echo "<tr><th colspan='7'>" . $prevdate . "</th></tr>";
				foreach ($orders as $order) {
					$thedate =  date_format(date_create($order["tradetime"]),"j F Y" );
					if ($thedate != $prevdate){
						//echo "</ul><div class='alert-box'>" . $thedate . "</div><ul>";
						echo "<tr><th colspan='7'>" . $thedate . "</th></tr>";
					}
					$prevdate = $thedate;
						$price =  number_format($order["price"],4);
						
						if ($order["buyer"] == $order["giver"]) {
							echo "<tr><td><span class='label success'>B</span></td><td>" . $order["buyer"] . "</td>" .
								"<td><span class='label alert'>S</span></td><td>" . $order["seller"] . "</td>" .
									"<td class='liveprice'>" . $price . "</td>" .
									"<td style='text-align: right;'>" . $order["currency"] . " " . number_format($order["size"],0) . "</td>" .
									"<td><a href='javascript:embx_getbonddetailforday(\"" . $order["isin"] . "\",\"" . substr($order["tradetime"],0,10) . "\")'>" . $order["isin"] . "</a></td></tr>" ;
						} else {
							echo "<tr><td><span class='label alert'>S</span></td><td>" . $order["seller"] . "</td>" .
								"<td><span class='label success'>B</span></td><td>" . $order["buyer"] . "</td>" .
									"<td class='liveprice'>" . $price. "</td>" .
									"<td style='text-align: right;'>" . $order["currency"] . " " . number_format($order["size"],0) . "</td>" .
									"<td><a href='javascript:embx_getbonddetailforday(\"" . $order["isin"] . "\",\"" . substr($order["tradetime"],0,10)  . "\")'>" . $order["isin"] . "</a></td></tr>" ;
							
						}


				}
				echo "</table>";
			}
			break;
			case "tradesummarydateisin":
				date_default_timezone_set("UTC");
				$theisin = $_GET["isin"];
				$thedate = $_GET["tradedate"];
				//embx_bondupdate($isin);
				$orders = embx_sql("select * from trades where isin = '" . $theisin . "' and date(tradetime) = '" . $thedate . "' order by tradetime");
				if ($orders){
					echo "<h6>Trade Summary for ". $thedate . " and for ". $theisin . "</h6>";
					echo "<table class='embx-table'>";
					if (count($orders)>0){
						foreach ($orders as $order) {
							$thedate =  date_format(date_create($order["tradetime"]),"j F Y" );
							if ($thedate != $prevdate){
								//echo "</ul><div class='alert-box'>" . $thedate . "</div><ul>";
								echo "<tr><th colspan='7'>" . $thedate . "</th></tr>";
							}
							$prevdate = $thedate;
								$price =  number_format($order["price"],4);
						
								if ($order["buyer"] == $order["giver"]) {
									echo 	"<tr>
												<td><span class='label success'>B</span></td>
												<td>" . $order["buyer"] . "</td>
												<td><span class='label alert'>S</span></td>
												<td>" . $order["seller"] . "</td>
												<td class='liveprice'>" . $price . "</td>
												<td style='text-align: right;'>" . $order["currency"] . " " 
													. number_format($order["size"],0) . "</td>
												<td><a href='javascript:embx_getbonddetailforday(\"" . $order["isin"] . "\",\""
													. substr($order["tradetime"],0,10) . "\")'>" . $order["isin"] . "</a></td>
											</tr>" ;
								} else {
									echo 	"<tr>
												<td><span class='label alert'>S</span></td>
												<td>" . $order["seller"] . "</td>
												<td><span class='label success'>B</span></td>
												<td>" . $order["buyer"] . "</td>
												<td class='liveprice'>" . $price. "</td>
												<td style='text-align: right;'>" . $order["currency"] . " " 
													. number_format($order["size"],0) . "</td>
												<td><a href='javascript:embx_getbonddetailforday(\"" . $order["isin"] . "\",\"" 
													. substr($order["tradetime"],0,10)  . "\")'>" . $order["isin"] . "</a></td>
											</tr>" ;
								}


						}
						echo "</table>";
					}
				}
				break;

		case "bonddetailforday":
			date_default_timezone_set("UTC");
			$isin = $_GET["isin"];
			//embx_bondupdate($isin);
			$orders = embx_sql("select * from orders where isin ='" . $isin 
						. "' and date(ordertime) = '" . $_GET["tradingday"] ."' order by ordertime");
			$trades = embx_tradesfordayforisin($isin,$_GET["tradingday"]);
			if ($trades){
				echo $trades;
			}
			//echo embx_tradesfordayforisin($isin,$_GET["tradingday"]);
			
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
					echo "<tr>	<td><strong>" . $order["orderid"] .  "</strong></td>
								<td>"  . $order["username"] . "</td>
								<td><a href='#' class='ordertime' id='" 
									. substr($order["ordertime"],11,8) . "'>" 
									. substr($order["ordertime"],11,8) . "</a></td>
								<td>" . $order["action"] . "</td>
								<td>" .  $side . "</td>
								<td>" . $price . "</td>
								<td style='text-align: right;'>" . number_format($order["size"],0) . "</td>
								<td><a href='#' class='ordertime'  id='" 
								. substr($order["endtime"],11,8) . "'>" 
								. substr($order["endtime"],11,8) . "</a></td>
								<td>" . $order["reason"] . "</td>
						</tr>";
					$prevdate = $thedate;
				}
				echo "</table>";
				?>
					<div class="row">
						<div class="small-2 columns">
							<div id="hourselection" class="range-slider vertical-range" 
								data-slider data-options="vertical: true; start:1080; end:480;">
								<span class="range-slider-handle" role="slider" tabindex="0"></span>
								<span class="range-slider-active-segment"></span>
								<input type="hidden">
							</div>
						</div>
						<div id="marketsnapshot" class="small-10 columns">
						</div>
					</div>
					<input type="hidden" value="0" id="marketsnapshotworking">
				<script>
				$(document).foundation();
				$(document).foundation('slider', 'reflow');
				if ($("#marketsnapshotworking").val() == 0) {
					$("#marketsnapshotworking").val(1);
					$.get("utils/embx_ajax.php?pf=marketsnapshot&isin=<? 
							echo $isin; ?>&tradingday=<? echo $_GET["tradingday"]; ?>&minutes=720",function(data){
						$('#marketsnapshot').html(data);
					}).done(function(){
						$("#marketsnapshotworking").val(0);
					});
				} else {
					
				}	
				$('#hourselection').on('change.fndtn.slider', function(){
					if ($("#marketsnapshotworking").val() == 0) {
						$("#marketsnapshotworking").val(1);
						$.get("utils/embx_ajax.php?pf=marketsnapshot&isin=<? 
							echo $isin; ?>&tradingday=<? echo $_GET["tradingday"]; 
							?>&minutes=" + $('#hourselection').attr('data-slider') ,function(data){
							$('#marketsnapshot').html(data);
						}).done(function(){
							$("#marketsnapshotworking").val(0);
						});
					}	
				});
						
				function selectsnapshot(stime){
					//alert(stime);
					$("#marketsnapshotworking").val(1);
			
					$.get("utils/embx_ajax.php?pf=marketsnapshot&isin=<? 
						echo $isin; ?>&tradingday=<? echo $_GET["tradingday"]; ?>&snapshottime=" + stime ,function(data){
							$('#marketsnapshot').html(data);
					}).done(function(){
						$("#marketsnapshotworking").val(0);
					});
				}
				$(".ordertime").click(function(){
					selectsnapshot(this.id);
				});
				</script>
				<?
			}
			break;



} 




?>

