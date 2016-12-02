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
	$lines = file("../source_files/" . $filename);
	//echo "Processing <strong>" . $filename . "</strong><br />Number of lines = " . count($lines) . "<br />"; 
	
	foreach ($lines as $line) {
		$array = explode(";",$line);
		if (count($array) == 8 ){
			$logid = $array[0];	
			$time_1 = $array[1];
			$time_2 = $array[2];
			$type = $array[3];
			$user = $array[4];
			$counterparty = $array[5];
			$action = $array[6];
			$content = $array[7];
			mysql_query("insert into temp (logid, logtime, logothertime, type, user, cpty, action, content) values ( " 
					. $logid . ", '" . $time_1 . "', '" . $time_2 . "', '" . $type . "', '" 
					. $user . "', '" . $counterparty . "', '" . $action . "', '" . $content . "')" );
		}
	}
	
	$lines = embx_sql("select * from temp order by logid asc, logtime asc");
	//var_dump($lines);
	//var_dump($lines);
	$i=0;
	foreach ($lines as $line)  {
		$i = $i + 1;
		//$thisline = explode(";",$line );
		$thisline = $line;
		if (count($thisline == 8 )) {
			embx_processline($line);
		};
	}



	//mysql_query("insert into processed (processed) values (1) where filename = '" . $filename . "'");
}


function embx_processline($line){
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

			case "trader1info":
				embx_tradeinfo($time_1, $user, $counterparty, $content, $logid);
			break;
			case "trader2info":
				embx_tradeinfo($time_1, $user, $counterparty, $content, $logid);
			break;
		}
	} else {
			return;
	} 
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



function embx_columnchartformat($data,$x,$y){
	$chartdata = "";
	foreach ($data as $item){
		$chartdata = $chartdata . "['" . $item[$x] .   "', " . $item[$y] . "],";
	}
	$chartdata = substr($chartdata,0,strlen($chartdata)-1);
	
	return $chartdata;
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

?>