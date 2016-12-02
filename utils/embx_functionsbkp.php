<?php
function embx_sql($sql) {
	$ret = [];
	$rs = mysql_query($sql);
		if ($rs ){
			if (mysql_num_rows($rs) > 0) {
				$row = mysql_fetch_assoc($rs);
				$j = 0;
				do {
					foreach($row as $key => $value) {
						$ret[$j][$key] = $value;	
					};
					$j=$j+1;
				} while ($row = mysql_fetch_assoc($rs));
			};
		};
	return $ret;
}

function embx_lookup($table,$key_name,$key_value,$lookup_name){
	$sqltxt = "SELECT " . $lookup_name . " FROM " . $table . " WHERE " . $key_name  . " = " . $key_value;
	//echo $sqltxt;
	$rs = mysql_query($sqltxt);
	if ($rs){
		if (mysql_num_rows($rs) > 0) {
			$row = mysql_fetch_assoc($rs);
			return $row[$lookup_name];	
		} else {
			return null;
		}
	}
}

function embx_processfile($filename) {
	
	mysql_query("truncate temp");
	$f = embx_lookup("processed","filename","'". $filename . "'" ,"id");
	echo "Dumping f:<br/>";
	var_dump($f);
	echo "Finish Dumping f:<br/>";
	$fileid = $f;
	$lines = file("../source_files/" . $filename);
	
	echo "Number of lines original: " . count($lines) . "<br />";
	
	foreach ($lines as $line) {
		
		$array = explode(";",$line);
		var_dump($array);
		echo "count of array = " . count($array) . " <br />";
		if (count($array) >= 8 ){
			$logid = $array[0];	
			$time_1 = $array[1];
			$time_2 = $array[2];
			$type = $array[3];
			$user = $array[4];
			$counterparty = $array[5];
			$action = $array[6];
			if (count($array) == 8) {
				$content = $array[7];
			} else {
				$content = $array[8];
			}
			mysql_query("insert into temp (logid, logtime, logothertime, type, user, cpty, action, content) values ( " 
					. $logid . ", '" . $time_1 . "', '" . $time_2 . "', '" . $type . "', '" 
					. $user . "', '" . $counterparty . "', '" . $action . "', '" . $content . "')" );
		} else {
			$array = explode(";",$line);
			if (is_numeric($array[0])){
				mysql_query("insert into rejects ( logid, fileid, line) values (" . $array[0] . "," . $fileid . ",'" . mysql_escape_string($line) . "')");
				//echo "REJ LOGID - insert into rejects ( fileid, line) values (" . $fileid . ",'" . mysql_escape_string($line) . "') <br/>";
			} else {
				mysql_query("insert into rejects ( fileid, line) values (" . $fileid . ",'" . mysql_escape_string($line) . "')");
				//echo "REJ NO-LOGID - insert into rejects ( fileid, line) values (" . $fileid . ",'" . mysql_escape_string($line) . "')";
			}
		}
	}

	$lines = embx_sql("select * from temp order by logid asc, logtime asc");
	$i=0;
	echo "Number of lines in temp: " . count($lines) . "<br />";
	foreach ($lines as $line)  {
		$i = $i + 1;
		//$thisline = explode(";",$line );
		$thisline = $line;
		if (count($thisline == 8 )) {
			embx_processline($line,$fileid);
		};
	}
}

function embx_processline($line,  $fileid){
	//$array = explode(";",$line);
	$array = $line;

	if (count($array) == 8 ){

		$logid = $array["logid"];	
		$time_1 = $array["logtime"];
		$time_2 = $array["logothertime"];
		$type = $array["type"];
		$user = $array["user"];
		$counterparty = $array["cpty"];
		$action = $array["action"];
		$content = $array["content"];
	
		switch ($action) {
			case "order/add":
				//echo "<p>Processing order/add</p>";
				embx_order_add($time_1, $user, $counterparty, $content, $logid);
				break;
			case "order/update":
				//echo "<p>Processing order/update</p>";
				embx_order_update($time_1, $user, $counterparty, $content, $logid);
				break;
			case "order/takeover":
				//echo "<p>Processing order/takeover</p>";
				embx_order_update($time_1, $user, $counterparty, $content, $logid, 'takeover');
				break;
				
			case "order/cancel":
				//echo "<p>Processing order/cancel</p>";
				embx_order_cancel($time_1, $user, $counterparty, $content, $logid);
				break;
			case "panic/user":
				//echo "<p>Processing panic/user</p>";
				embx_order_cancel_panic($time_1, $user, $counterparty, $content, $logid);
				break;	
			case "panic/counterparty":
				//echo "<p>Processing panic/counterparty</p>";
				embx_order_cancel_panic($time_1, $user, $counterparty, $content, $logid);
				break;	
			case "panic/counterparties":
				//echo "<p>Processing panic/counterparties</p>";
				embx_order_cancel_panic($time_1, $user, $counterparty, $content, $logid);
				break;	
			case "trade/capture":
				embx_trade_capture($time_1, $user, $counterparty, $content, $logid);
				break;
			case "trader1info":
				embx_tradeinfo($time_1, $user, $counterparty, $content, $logid);
			break;
			case "trader2info":
				embx_tradeinfo($time_1, $user, $counterparty, $content, $logid);
			break;

			default:
			$temp = "";
			foreach ($line as $item){
				$temp = $temp . $item . ";";
			}
			$temp = substr($temp, 0, strlen($temp)-1);
			mysql_query("insert into rejects (logid, fileid, line) values (" . $logid . "," . $fileid . ",'" . mysql_escape_string($temp) . "')");
			echo "Catch default - insert into rejects (logid, fileid, line) values (" . $logid . "," . $fileid . ",'" . mysql_escape_string($temp) . "')";
		}
		return;
	} else {
			return;
	} 
}

function embx_order_add($time_1, $user, $counterparty, $content, $logid){
	$beg = strpos($content, '[');
	$end = strpos($content, ']');
	$orderid = substr($content, $beg+7,$end-$beg-7);
	//echo "<p>OrderID = @" . $orderid . "@</p>";
	
	$beg = strpos($content, 'with');
	$end = strpos($content, '(size)');

	$size = substr($content, $beg+5,$end-$beg-6);
	//echo "<p>Size = @" . $size . "@</p>";
	
	$beg = strpos($content, '(ISIN:');
	$end = strpos($content, ') bonds');

	$isin = substr($content, $beg+6,$end-$beg-6);
	//echo "<p>ISIN = @" . $isin . "@</p>";
	
	$beg = strpos($content, 'bonds at ');
	$end = strpos($content, '(', strpos($content, "(ISIN")+1);
//	echo "<p>" . $beg . "</p>";
//	echo "<p>" . $end . "</p>";
	$price = substr($content, $beg+9,$end-$beg-9);
	//echo "<p>PRICE = @" . $price . "@</p>";
	
	//$beg = strpos($content, 'bonds at ');
	$beg = strpos($content, '(', strpos($content, "(ISIN")+1);
//	echo "<p>" . $beg . "</p>";
//	echo "<p>" . $end . "</p>";
	$quotetype = substr($content, $beg+1,5);
	//echo "<p>QUOTE = @" . $quotetype . "@</p>";
	
	$beg = strpos($content, ') on');
	$end = strpos($content, 'side');

	$side = substr($content, $beg+5,$end-$beg-6);
	//echo "<p>SIDE = @" . $side . "@</p>";

	$beg = strpos($content, 'Type: [');
	$end = strpos($content, ']',$beg);

	$ordertype = explode(",",str_replace(" ","",substr($content, $beg+7,$end-$beg-7)));
	//echo "<p>FORCE = @" . $ordertype[0] . "@</p>";
	//echo "<p>NAME = @" . $ordertype[1] . "@</p>";
	//echo "<p>INDIC = @" . $ordertype[2] . "@</p>";
	//echo "<p>PARTIAL = @" . $ordertype[3] . "@</p>";
	//echo "<p>NORMAL = @" . $ordertype[4] . "@</p>";
	
	$sql = "insert into orders (orderid, logid, username, counterparty, isin, side, quotetype, size, price, ordertime, ordertype, 
								timeinforce, filltype, anonymity,  action) values 
								(" .
								$orderid . ", " .
								$logid . ", " .
								"'" . $user . "', " .
								"'" . $counterparty . "', " .
								"'" . $isin . "', " .
								"'" . $side . "', " .
								"'" . $quotetype . "', " .
								"" . $size. ", " .
								"" . $price . ", " .
								"'" . $time_1 . "', " .
								"'" . $ordertype[2] . "', " .
								"'" . $ordertype[0] . "', " .
								"'" . $ordertype[3] . "', " .
								"'" . $ordertype[1] . "', " .
								"'" . "add-" . $logid . "'" .
									" )";
	$res = mysql_query($sql);
}

function embx_order_cancel($time_1, $user, $counterparty, $content, $logid){
	$beg = strpos($content, '[');
	$end = strpos($content, ']');
	$orderid = substr($content, $beg+7,$end-$beg-7);
	//echo "<p>OrderID = @" . $orderid . "@</p>";
	
	$beg = strpos($content, 'with');
	$end = strpos($content, '(size)');

	$size = substr($content, $beg+5,$end-$beg-6);
	//echo "<p>Size = @" . $size . "@</p>";
	
	$beg = strpos($content, '(ISIN:');
	$end = strpos($content, ') bonds');

	$isin = substr($content, $beg+6,$end-$beg-6);
	//echo "<p>ISIN = @" . $isin . "@</p>";
	
	$beg = strpos($content, 'bonds at ');
	$end = strpos($content, '(', strpos($content, "(ISIN")+1);
//	echo "<p>" . $beg . "</p>";
//	echo "<p>" . $end . "</p>";
	$price = substr($content, $beg+9,$end-$beg-9);
	//echo "<p>PRICE = @" . $price . "@</p>";
	
	//$beg = strpos($content, 'bonds at ');
	$beg = strpos($content, '(', strpos($content, "(ISIN")+1);
//	echo "<p>" . $beg . "</p>";
//	echo "<p>" . $end . "</p>";
	$quotetype = substr($content, $beg+1,5);
	//echo "<p>QUOTE = @" . $quotetype . "@</p>";
	
	$beg = strpos($content, ') on');
	$end = strpos($content, 'side');

	$side = substr($content, $beg+5,$end-$beg-6);
	//echo "<p>SIDE = @" . $side . "@</p>";
	
	$beg = strpos($content, '] from');
	$end = strpos($content, 'from CP:');

	$user1 = substr($content, $beg+7,$end-$beg-8);
	//echo "<p>SIDE = @" . $side . "@</p>";
	
	$beg = strpos($content, 'CP:');
	$end = strpos($content, 'with');

	$cpty1 = substr($content, $beg+3,$end-$beg-4);
	//echo "<p>SIDE = @" . $side . "@</p>";

	$beg = strpos($content, 'Type: [');
	$end = strpos($content, ']',$beg);

	$ordertype = explode(",",str_replace(" ","",substr($content, $beg+7,$end-$beg-7)));
	//echo "<p>FORCE = @" . $ordertype[0] . "@</p>";
	//echo "<p>NAME = @" . $ordertype[1] . "@</p>";
	//echo "<p>INDIC = @" . $ordertype[2] . "@</p>";
	//echo "<p>PARTIAL = @" . $ordertype[3] . "@</p>";
	//echo "<p>NORMAL = @" . $ordertype[4] . "@</p>";
	
	if (!strpos($content,"automatically")){
		
		$id = embx_sql("select id from orders where orderid = " . $orderid . " and ordertime <= '" . $time_1 . "' and isnull(endtime) order by ordertime desc , id desc limit 1" );
	
		$sqlupdate = "update orders set endtime = '" . $time_1 . "', reason = 'cancel-" . $logid . "' where id = " . $id[0]["id"];
		mysql_query($sqlupdate);
	} else {
		if ($ordertype[0] != "FoK"){
			//echo $ordertype . " " . $content . "<br />";
			//echo "select id from orders where ISIN = '" . $isin . "' and ordertime <='" . $time_1 . "' and isnull(endtime) order by ordertime desc limit 1 <br />";
			// $id = embx_sql("select id from orders where ISIN = '" . $isin . "' and ordertime <='" . $time_1 . "' and isnull(endtime) and username = '" . $user1 . 
			//	"' and price = " . $price . " order by ordertime desc limit 1");
			// $sqlupdate = "update orders set endtime = '" . $time_1 . "', reason = 'autocancel' where id = " . $id[0]["id"];
			// echo $sqlupdate . "<br />";
			//mysql_query($sqlupdate);
			$sql = "insert into orders (orderid, logid, username, counterparty, isin, side, quotetype, size, price, ordertime, ordertype, 
					timeinforce, filltype, anonymity, action) values 
					(" .
					$orderid . ", " .
					$logid . ", " .
					"'" . $user1 . "', " .
					"'" . $cpty1 . "', " .
					"'" . $isin . "', " .
					"'" . $side . "', " .
					"'" . $quotetype . "', " .
					"" . $size. ", " .
					"" . $price . ", " .
					"'" . $time_1 . "', " .
					"'" . $ordertype[2] . "', " .
					"'" . $ordertype[0] . "', " .
					"'" . $ordertype[3] . "', " .
					"'" . $ordertype[1] . "', " .
					"'" . "traderemain" . "'" .
					" )";
			mysql_query($sql);
		}
	}
	
	//$res = mysql_query($sql);
	//var_dump($res);
	//echo "<p>" . $sql . "</p>";
	
	
}

function embx_tradeinfo($time_1, $user, $counterparty, $content, $logid){
	echo "here";
	$beg = strpos($content, 'trade');
	$end = strpos($content, ']');
	$tradeid = substr($content, $beg+6,$end-$beg-6);
	echo "TradeID=@" . $tradeid . "@<br />";
	//echo "<p>OrderID = @" . $orderid . "@</p>";
	
	$beg = strpos($content, 'User');
	$end = strpos($content, '(');

	$user = substr($content, $beg+5,$end-$beg-6);
	echo "User=@" . $user . "@<br />";
	
	$beg = strpos($content, '<');
	$end = strpos($content, 'S>');

	$side = substr($content, $beg+1,$end-$beg-1);
	echo "Side=@" . $side . "@<br />";
	
	$beg = strpos($content, '(');
	$end = strpos($content, ')');

	$role = substr($content, $beg+1,$end-$beg-1);
	echo "Role=@" . $role . "@<br />";
	
	$beg = strpos($content, 'with');
	$end = strpos($content, '(size');

	$size = substr($content, $beg+5,$end-$beg-6);
	echo "Size=@" . $size . "@<br />";
	//echo "<p>Size = @" . $size . "@</p>";
	
	$beg = strpos($content, '(ISIN:');
	$end = strpos($content, ') bonds');

	$isin = substr($content, $beg+6,$end-$beg-6);
	echo "<p>ISIN = @" . $isin . "@</p>";
	
	
	$beg = strpos($content, ' in ');
	$end = strpos($content, ').');

	$ccy = substr($content, $beg+4,$end-$beg-4);
	echo "<p>CCY = @" . $ccy . "@</p>";
	
	$beg = strpos($content, 'bonds at ');
	$end = strpos($content, '(', strpos($content, "(ISIN")+1);
//	echo "<p>" . $beg . "</p>";
//	echo "<p>" . $end . "</p>";
	$price = substr($content, $beg+9,$end-$beg-9);
	echo "<p>PRICE = @" . $price . "@</p>";
	
	//$beg = strpos($content, 'bonds at ');
	$beg = strpos($content, '(', strpos($content, "(ISIN")+1);
//	echo "<p>" . $beg . "</p>";
//	echo "<p>" . $end . "</p>";
	$quotetype = substr($content, $beg+1,5);
	echo "<p>QUOTE = @" . $quotetype . "@</p>";
	
	$check = embx_sql("select * from trades where tradeid = " . $tradeid);
	if ($side == "BUY") { $side = 'buyer';} else { $side = 'seller';}
	if ($check){
		$sql = "update trades set " . $side . "='" . $user . "', " . $role . "='" . $user . "' where tradeid = " . $check[0]["tradeid"];
		echo $sql;
		mysql_query($sql);
	} else {
		
		$sql = "update trades set " . $side . "='" . $user . "', " . $role . "='" . $user . "' where tradeid = " . $check[0]["tradeid"];
		echo $sql . "<br />";

				$sql = "insert into trades (tradeid, logid, " . $role . ", " . $side . ",  isin,  quotetype, size, price, tradetime, currency) values 
					(" .
					$tradeid . ", " .
					$logid . ", " .
					"'" . $user . "', " .
					"'" . $user . "', " .
					"'" . $isin . "', " .
					"'" . $quotetype . "', " .
					"" . $size. ", " .
					"" . $price . ", " .
					"'" . $time_1 . "', " .
					"'" . $ccy . "' )";
					echo $sql;
	}	mysql_query($sql);

	
}

function embx_order_cancel_panic($time_1, $user, $counterparty, $content, $logid){
	$beg = strpos($content, '[');
	$end = strpos($content, ']');
	$orders = substr($content, $beg+1,$end-$beg-1);
	//echo "<p>OrderID = @" . $orderid . "@</p>";
	
	$orderlist = explode(',',$orders);
	if (count($orderlist)>0){
		foreach($orderlist as $order){
			$id = embx_sql("select id from orders where orderid = " . $order . " and ordertime <='" . $time_1 . "' order by ordertime desc, id desc limit 1" );
	
			$sqlupdate = "update orders set endtime = '" . $time_1 . "', reason = 'panic' where id = " . $id[0]["id"];
			mysql_query($sqlupdate);
		}
	}
	
	//echo "<p>FORCE = @" . $ordertype[0] . "@</p>";
	//echo "<p>NAME = @" . $ordertype[1] . "@</p>";
	//echo "<p>INDIC = @" . $ordertype[2] . "@</p>";
	//echo "<p>PARTIAL = @" . $ordertype[3] . "@</p>";
	//echo "<p>NORMAL = @" . $ordertype[4] . "@</p>";
	

	
	
}

function embx_trade_capture($time_1, $user, $counterparty, $content, $logid){
	$beg = strpos($content, 'match');
	$end = strpos($content, ']');
	if ($beg && ($beg < $end)){
		$orderid = substr($content, $beg+6,$end-$beg-6);
		echo "trade capture orderid=" . $orderid . "<br />";
		//echo "<p>OrderID = @" . $orderid . "@</p>";
		
		$id = embx_sql("select id from orders where orderid = " . $orderid . " and ordertime <='" . $time_1 . "' order by ordertime desc limit 1" );
	
		$sqlupdate = "update orders set endtime = '" . $time_1 . "', reason = 'trade' where id = " . $id[0]["id"];

		mysql_query($sqlupdate);
	}

}



function embx_order_update($time_1, $user, $counterparty, $content, $logid, $reasontext = 'update'){
	$beg = strpos($content, '[');
	$end = strpos($content, ']');
	$orderid = substr($content, $beg+7,$end-$beg-7);
	
	$beg = strpos($content, 'with');
	$end = strpos($content, '(size)');

	$size = substr($content, $beg+5,$end-$beg-6);
	
	$beg = strpos($content, '(ISIN:');
	$end = strpos($content, ') bonds');

	$isin = substr($content, $beg+6,$end-$beg-6);
	
	$beg = strpos($content, 'bonds at ');
	$end = strpos($content, '(', strpos($content, "(ISIN")+1);
	$price = substr($content, $beg+9,$end-$beg-9);
	
	$beg = strpos($content, '(', strpos($content, "(ISIN")+1);
	$quotetype = substr($content, $beg+1,5);
	
	$beg = strpos($content, ') on');
	$end = strpos($content, 'side');

	$side = substr($content, $beg+5,$end-$beg-6);

	$beg = strpos($content, 'Type: [');
	$end = strpos($content, ']',$beg);

	$ordertype = explode(",",str_replace(" ","",substr($content, $beg+7,$end-$beg-7)));
	
	$id = embx_sql("select id from orders where orderid = " . $orderid . " and ordertime <= '" . $time_1 . "' and isnull(endtime) order by ordertime desc, id desc limit 1" );
	$sqlupdate = "update orders set endtime = '" . $time_1 . "', reason = '" . $reasontext . "-" . $logid . "' where id = " . $id[0]["id"];
	mysql_query($sqlupdate);
	//var_dump($id);
	//echo $sqlupdate . "<br />";
	$sql = "insert into orders (orderid, logid, username, counterparty, isin, side, quotetype, size, price, ordertime, ordertype, 
								timeinforce, filltype, anonymity, action) values 
								(" .
								$orderid . ", " .
								$logid . ", " .
								"'" . $user . "', " .
								"'" . $counterparty . "', " .
								"'" . $isin . "', " .
								"'" . $side . "', " .
								"'" . $quotetype . "', " .
								"" . $size . ", " .
								"" . $price . ", " .
								"'" . $time_1 . "', " .
								"'" . $ordertype[2] . "', " .
								"'" . $ordertype[0] . "', " .
								"'" . $ordertype[3] . "', " .
								"'" . $ordertype[1] . "', " .
								"'update-" . $logid . "' " .
								" )";
	$res = mysql_query($sql);
	//var_dump($res);
	echo "<p>" . $sql . "</p>";
}

function embx_bondupdate($isin){
	$sql = "select * from orders where isin='" . $isin . "'";
	echo $sql;
	$orders = embx_sql($sql);
	if ($orders != ""){
		foreach ($orders as $order){
			$i = $i+1;
			echo $order["orderid"] . " " . $order["logid"] . "<br />";
			$orderid = $order["orderid"];
			if ($i < 250) {
				mysql_query("update orders set isin='" . $isin . "' where isnull(isin) and orderid=" . $orderid);
				echo "Time=" . time() . "<br />";
				echo "update orders set isin='" . $isin . "' where isnull(isin) and orderid=" . $orderid . "<br />";
				
			}
		}
	}
}

function embx_cleantables(){
	mysql_query("truncate processed");
	mysql_query("truncate orders");
}

function embx_columnchartformat($data,$x,$y){
	$chartdata = "";
	foreach ($data as $item){
		$chartdata = $chartdata . "['" . $item[$x] .   "', " . $item[$y] . "],";
	}
	$chartdata = substr($chartdata,0,strlen($chartdata)-1);
	
	return $chartdata;
}


function embx_markethistorygraph($containerid, $data,  $xaxis, $yaxis, $title, $subtitle){
	
	$livebids = embx_preparedata($data["hour"],$data["px_livebid"]);
	$indicativebids = embx_preparedata($data["hour"],$data["px_indicativebid"]);
	$liveasks = embx_preparedata($data["hour"],$data["px_liveask"]);
	$indicativeasks = embx_preparedata($data["hour"],$data["px_indicativeask"]);
	//$categories = embx_preparecategories($data["category"]);
	$ret = "
	$(function () {
	    $('#".$containerid."').highcharts({
	        chart: {
	            type: 'scatter',
	            zoomType: 'xy'
	        },
	        title: {
	            text: '".$title."'
	        },
	        subtitle: {
	            text: '".$subtitle."'
	        },
	        xAxis: {
				type: 'datetime',
	            title: {
	                enabled: true,
	                text: '".$xaxis."'
	            },
	            //startOnTick: true,
	            //endOnTick: true,
	            min: 25200000,
				max: 64800000,
				showLastLabel: true
	        },
	        yAxis: {
	            title: {
	                text: '".$yaxis."'
	            }
	        },
	        legend: {
				enabled: false,
	            layout: 'vertical',
	            align: 'left',
	            verticalAlign: 'top',
	            x: 100,
	            y: 70,
	            floating: true,
	            backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF',
	            borderWidth: 1
	        },
	        plotOptions: {
	            scatter: {
	                marker: {
	                    radius: 4,
	                    states: {
	                        hover: {
	                            enabled: true,
	                            lineColor: 'rgb(100,100,100)'
	                        }
	                    }
	                },
	                states: {
	                    hover: {
	                        marker: {
	                            enabled: false
	                        }
	                    }
	                },
	                tooltip: {
	                    headerFormat: '<b>{series.name}</b><br>',
	                    pointFormat: 'Time {point.x:%H:%M}, Px {point.y:.4f}'
	                }
	            }
	        },
	        series: [
						{
				            name: 'LiveBids',
				            color: 'rgba(255, 83, 83, .9)',
				            data: ".$livebids.",
							marker: {
								radius: 7,
								symbol: 'circle'}
								
						},
					 	{
				            name: 'LiveAsks',
				            color: 'rgba(83, 255, 83, .9)',
				            data: ".$liveasks.",
							marker: {
								radius: 7,
								symbol: 'circle'}
		        		},
						{
				            name: 'IndicativeBids',
				            color: 'rgba(255, 83, 83, .4)',
				            data: ".$indicativebids.",
							marker: {
								radius: 4,
								symbol: 'circle'}
						},
					 	{
				            name: 'IndicativeAsks',
				            color: 'rgba(83, 255, 83, .4)',
				            data: ".$indicativeasks.",
							marker: {
								radius: 4,
								symbol: 'circle'}
		        		}
					]
	    });
	});	
	";
	return $ret;	
	
	
}



function embx_columngraph($containerid, $data, $xcol, $ycol, $xaxis, $yaxis, $title, $subtitle, $clickfunction, $tooltipformat ){
	$ret = "
	$(function () {
	    $('#" . $containerid . "').highcharts({
	        chart: {
	            type: 'column'
	        },
	        title: {
	            text: '" . $title . "'
	        },
	        subtitle: {
	            text: '" . $subtitle . "'
	        },
	        xAxis: {
	            type: 'category',
				categories: [ " ;
				
				$dum = 0;
				foreach ($data as $item){
					if ($dum > 0){ $ret = $ret .  ",";}
					$ret = $ret . "'" . $item[$xcol] . "'";
					$dum = $dum +1;
				}	
					
				$ret = $ret . " ],

	            labels: {
	                rotation: -45,
	                style: {
	                    fontSize: '13px',
	                    fontFamily: 'Verdana, sans-serif'
	                }
	            }
	        },
	        yAxis: {
	            min: 0,
	            title: {
	                text: '" . $yaxis . "'
	            }
	        },
	        plotOptions: {
	            series: {
	                cursor: 'pointer',
	                point: {
	                    events: {
	                        click: function () { ";
							$ret = $ret . $clickfunction . "
	                        }
	                    }
	                }
	            }
	        },
	        legend: {
	            enabled: false
	        },
	        tooltip: {
	            pointFormat: '" . $tooltipformat . "'
	        },
	        series: [{
	            name: 'TradeData',
	            data: [";
				$chartdata = "";
				foreach ($data as $item){
					$chartdata = $chartdata . "['" . $item[$xcol] .   "', " . $item[$ycol] . "],";
				}
				$chartdata = substr($chartdata,0,strlen($chartdata)-1);
				$ret = $ret .  $chartdata;
				$ret = $ret . "
	            ],
	            dataLabels: {
	                enabled: true,
	                rotation: -90,
	                color: '#FFFFFF',
	                align: 'right',
	                format: '{point.y:,.0f}', // one decimal
	                y: 10, // 10 pixels down from the top
	                style: {
	                    fontSize: '13px',
	                    fontFamily: 'Verdana, sans-serif'
	                }
	            }
	        }]
	    });
	});	
	";
	return $ret;
}

function embx_tradesfordayforisin($theisin,$thedate){
	date_default_timezone_set("UTC");
//	$theisin = $_GET["isin"];
//	$thedate = $_GET["tradedate"];
	//embx_bondupdate($isin);
	$ret = "";
	$orders = embx_sql("select * from trades where isin = '" . $theisin . "' and date(tradetime) = '" . $thedate . "' order by tradetime");
	//$ret = "select * from trades where isin = '" . $theisin . "' and date(tradetime) = '" . $thedate . "' order by tradetime";
	if ($orders){
		$ret = $ret .  "<h6>Trade Summary for ". $thedate . " and for ". $theisin . "</h6>";
		$ret = $ret .  "<table class='embx-table'>";
		if (count($orders)>0){
			foreach ($orders as $order) {
				$thedate =  date_format(date_create($order["tradetime"]),"j F Y" );
				if ($thedate != $prevdate){
					//echo "</ul><div class='alert-box'>" . $thedate . "</div><ul>";
					$ret = $ret .   "<tr><th colspan='7'>" . $thedate . "</th></tr>";
				}
				$prevdate = $thedate;
					$price =  number_format($order["price"],4);
			
					if ($order["buyer"] == $order["giver"]) {
						$ret = $ret .   "<tr><td><span class='label success'>B</span></td><td>" . $order["buyer"] . "</td>" .
							"<td><span class='label alert'>S</span></td><td>" . $order["seller"] . "</td>" .
								"<td class='liveprice'>" . $price . "</td>" .
								"<td style='text-align: right;'>" . $order["currency"] . " " . number_format($order["size"],0) . "</td>" .
								"<td><a href='javascript:embx_getbonddetailforday(\"" . $order["isin"] . "\",\"" . substr($order["tradetime"],0,10) . "\")'>" . $order["isin"] . "</a></td></tr>" ;
					} else {
						$ret = $ret .  "<tr><td><span class='label alert'>S</span></td><td>" . $order["seller"] . "</td>" .
							"<td><span class='label success'>B</span></td><td>" . $order["buyer"] . "</td>" .
								"<td class='liveprice'>" . $price. "</td>" .
								"<td style='text-align: right;'>" . $order["currency"] . " " . number_format($order["size"],0) . "</td>" .
								"<td><a href='javascript:embx_getbonddetailforday(\"" . $order["isin"] . "\",\"" . substr($order["tradetime"],0,10)  . "\")'>" . $order["isin"] . "</a></td></tr>" ;
				
					}


			}
			$ret = $ret .  "</table>";
		}
	}
	return $ret;
}

function embx_pricehistory($isin, $date){
	// 1. create a timestamp array for that date
		
	
	// 2. poll the database for bids and offers for live prices on each timestamp array

	
}

function embx_marketsnapshot($isin,$tradingday, $minutes){

	date_default_timezone_set("UTC");
	
	// $tradingday = ($_GET["tradingday"]);
	$starttradingday = "'" . $tradingday . " 00:00:00'";
	// $minutes = $_GET["minutes"];
	
		$sst = strtotime($tradingday) + $minutes * 60;
		$snapshottime = date("Y-m-d H:i:s",$sst);
		//echo $snapshottime;
		//if ($minutes == ""){
		//	$snapshottime = $tradingday . " " . $_GET["snapshottime"];
		//}
		//echo $snapshottime;
		//echo $hour . "<br />";
		//$interval = new DateInterval('PT' . $hour . 'H');
		//$snapshottime = date_add($tradingday, $interval);
		//echo "snapshottime = " . date_format($snapshottime,"Y-m-d H:i:s")  . "<br />";

		//$isin = $_GET["isin"];
		
									

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

		$livebids = count($bidlive);
		$liveasks = count($asklive);
		$indicativebids = count($bidindicative);
		$indicativeasks = count($askindicative);

		$ret["livebids"] = $bidlive;
		$ret["liveasks"] = $asklive;
		$ret["indicativebids"] = $bidindicative;
		$ret["indicativeasks"] = $askindicative;
		
		$ret["minute"] = $minute;
		$ret["tradingday"] = $tradingday;
		if ($livebids) {
			$px_livebid = $bidlive[0]["price"];
			foreach ($bidlive as $livebid){
				$sz_livebid = $sz_livebid + $livebid["size"];
			}
		}
		if ($liveasks) {
			$px_liveask = $asklive[0]["price"];
			foreach ($asklive as $liveask){
				$sz_liveask = $sz_liveask + $liveask["size"];
			}
		}
		if ($indicativebids) {
			$px_indicativebid = $bidindicative[0]["price"];
			foreach ($bidindicative as $indicativebid){
				$sz_indicativebid = $sz_indicativebid + $indicativebid["size"];
			}
		}
		if ($indicativeasks) {
			$px_indicativeask = $askindicative[0]["price"];
			foreach ($askindicative as $indicativeask){
				$sz_indicativeask = $sz_indicativeask + $indicativeask["size"];
			}
		}
		$ret["sz_livebid"] = $sz_livebid;
		$ret["sz_liveask"] = $sz_liveask;
		$ret["sz_indicativebid"] = $sz_indicativebid;
		$ret["sz_indicativeask"] = $sz_indicativeask;
		$ret["px_livebid"] = $px_livebid;
		$ret["px_liveask"] = $px_liveask;
		$ret["px_indicativebid"] = $px_indicativebid;
		$ret["px_indicativeask"] = $px_indicativeask;
		
		
		return $ret;


	
}

function embx_markethistory($isin, $tradingday, $startminute = 420, $endminute = 1080, $interval = 15){
	$minute = $startminute;
	$itemp = 0;
	$sz_livebid = 0;
	$sz_liveask = 0;
	$sz_indicativebid = 0;
	$sz_indicativeask = 0;
	
	 do {
		$mkt = embx_marketsnapshot($isin,$tradingday, $minute);
		//var_dump($mkt);
		$sz_livebid = max($mkt["sz_livebid"],$sz_livebid);
		$sz_liveask = max($mkt["sz_liveask"],$sz_liveask);
		$sz_indicativebid = max($mkt["sz_indicativebid"],$sz_indicativebid);
		$sz_indicativeask = max($mkt["sz_indicativeask"],$sz_indicativeask);
		
		$ret["minute"][$itemp] = $minute;
		//$ret["hour"][$itemp] = $minute/60;
		$ret["hour"][$itemp] = $minute * 60000;	
		$ret["px_livebid"][$itemp] = $mkt["px_livebid"];
		$ret["px_liveask"][$itemp] = $mkt["px_liveask"];
		$ret["px_indicativebid"][$itemp] = $mkt["px_indicativebid"];
		$ret["px_indicativeask"][$itemp] = $mkt["px_indicativeask"];
		$ret["category"][$itemp] = floor($minute/60) . ":" . ($minute - floor($minute/60));
		$itemp = $itemp+1;
		$minute = $minute + $interval;
	} while ($minute <= $endminute);
	
	$ret["max_sz_livebid"] = $sz_livebid;
	$ret["max_sz_liveask"] = $sz_liveask;
	$ret["max_sz_indicativebid"] = $sz_indicativebid;
	$ret["max_sz_indicativeask"] = $sz_indicativeask;
	
	
	//var_dump($sz_livebid);
	//var_dump($sz_liveask);
	//var_dump($sz_indicativebid);
	//var_dump($sz_indicativeask);
	
	//var_dump($mkt);
	//echo "<br/> <br />";
	
	return $ret;
}


function embx_preparedata($x, $y){
	$numpoints = count($x);
	for ($j = 0; $j < $numpoints; $j++ ){
		if($y[$j]){
			$ret = $ret . ",[" . $x[$j] . "," . $y[$j] ."]";
		}
	}
	$ret = substr($ret, 1, strlen($ret)-1);
	$ret = "[" . $ret  ."]";
	return $ret;
}
function embx_preparecategories($x){
	$numpoints = count($x);
	for ($j = 0; $j < $numpoints; $j++ ){
			$ret = $ret .  "," . $x[$j] ;
	}
	$ret = substr($ret, 1, strlen($ret)-1);
	$ret = "[" . $ret  ."]";
	return $ret;
}

?>