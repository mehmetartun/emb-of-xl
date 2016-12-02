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

	case "bondlistforday":
		$bonds = embx_sql("select distinct orders.isin, bonds.currency, bonds.bondname, currencies.rate from orders, 
			bonds, currencies where date(orders.ordertime) = '" . $_GET["tradingday"] . "' 
			and bonds.isin = orders.isin and bonds.currency = currencies.currency order by bonds.bondname");
		$ret = json_encode($bonds);
		echo '{"items": ' . $ret . "}";

	break;
	
	case "processbond":
		$isin = $_GET["isin"];
		$tradingday = $_GET["tradingday"];
		$res = embx_getendofday($isin, $tradingday);
		if ($res) {
			
		} else {
			$res = embx_markethistory($isin, $tradingday);
			$sql = "DELETE FROM endofday WHERE tradingday = '" . $tradingday . "' AND isin='". $isin . "'";
			embx_sql($sql);
			$sql = "INSERT INTO endofday (isin,tradingday, max_sz_livebid, 
										max_sz_liveask, max_sz_indicativebid,
										max_sz_indicativeask, px_last_live_bid, px_last_live_ask,
										px_last_indicative_bid, px_last_indicative_ask,
										ts_last_live_bid, ts_last_live_ask, ts_last_indicative_bid,
										ts_last_indicative_ask) VALUES ('" . $isin . "', " .
										"'". $tradingday . "', ".
										$res["max_sz_livebid"].", ".
										$res["max_sz_liveask"].", ".
										$res["max_sz_indicativebid"].", ".
										$res["max_sz_indicativeask"].", ".
										$res["px_last_live_bid"].", ".
										$res["px_last_live_ask"].", ".
										$res["px_last_indicative_bid"].", ".
										$res["px_last_indicative_ask"].", ".
										$res["ts_last_live_bid"].", ".
										$res["ts_last_live_ask"].", ".
										$res["ts_last_indicative_bid"].", ".
										$res["ts_last_indicative_ask"].") ";
										//echo $sql;
			embx_sql($sql);
		}
		
		$bondname = embx_lookup("bonds","isin","'".$isin."'","bondname");
		$res["bondname"] = $bondname;								
		$ret = json_encode($res);
		echo '{"items": ' . $ret . "}";
	break;
	


} 




?>

