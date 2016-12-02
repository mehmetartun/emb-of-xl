<?php
include("embx_dbconn.php");
include("embx_functions.php");
//
//		FIle for processing the single page application
//
$graphcontent = $_GET["gc"];
switch ($graphcontent) {
	case "graphmarket":
	$isin = $_GET["isin"];
	$tradingday = $_GET["tradingday"];
	$data = embx_markethistory($isin, $tradingday);
	$bondname = embx_lookup("bonds","isin","'".$isin."'","bondname");
	
	$graphtitle = "Market for ".$bondname." on ".$tradingday;
	$subtitle = "Live: " . 	number_format($data["max_sz_livebid"]/1000000,2) . "/" . 
							number_format($data["max_sz_liveask"]/1000000,2) . " MM | " . 
								"Indicative: " . number_format($data["max_sz_indicativebid"]/1000000,2) . "/" . 
							number_format($data["max_sz_indicativeask"]/1000000,2) . " MM " ;
	
	$ret = embx_markethistorygraph("pagecontent", $data,  "Hour", "Price", $graphtitle , $subtitle);
	
	echo  "<script>" . $ret . "</script>";
	//echo $ret;
	break;
	
	
	
	
	case "graph_isincount":
		$sql = "select 
					t1.orderdate as tradingday, count(t1.isin) as isincount 
				from 
			    	(select distinct date(ordertime) as orderdate, isin from orders) as t1 
				group by 
					tradingday order by tradingday DESC limit 20";
		$data = embx_sql($sql);

		?>
		<script>
		$(function () {
		    $('#pagecontent').highcharts({
		        chart: {
		            type: 'column'
		        },
		        title: {
		            text: 'Number of ISIN\'s quoted'
		        },
		        subtitle: {
		            text: 'Includes live and indicative orders'
		        },
		        xAxis: {
		            type: 'category',
					categories: [ <?
					$dum = 0;
					foreach ($data as $item){
						if ($dum > 0){ echo ",";}
						echo "'" . $item["tradingday"] . "'";
						$dum = $dum +1;
					}	
						
					?> ],

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
		                text: 'Number of ISIN\'s'
		            }
		        },
		        plotOptions: {
		            series: {
		                cursor: 'pointer',
		                point: {
		                    events: {
		                        click: function () {
									$.get("utils/embx_ajax.php?pf=bondlistforday&tradingday=" + this.category,function(data){
										$('#detailcontent').html(data);
									});
									$('#detailheader').html("ISIN's quoted on " + this.category);
		                        }
		                    }
		                }
		            }
		        },
		        legend: {
		            enabled: false
		        },
		        tooltip: {
		            pointFormat: '<b>{point.y:,.0f} ISIN\'s</b>'
		        },
		        series: [{
		            name: 'TradeData',
		            data: [
		/*                ['Shanghai', 23.7],
		                ['Lagos', 16.1],
		                ['Instanbul', 14.2],
		                ['Karachi', 14.0],
		                ['Mumbai', 12.5],
		                ['Moscow', 12.1],
		                ['São Paulo', 11.8],
		                ['Beijing', 11.7],
		                ['Guangzhou', 11.1],
		                ['Delhi', 11.1],
		                ['Shenzhen', 10.5],
		                ['Seoul', 10.4],
		                ['Jakarta', 10.0],
		                ['Kinshasa', 9.3],
		                ['Tianjin', 9.3],
		                ['Tokyo', 9.0],
		                ['Cairo', 8.9],
		                ['Dhaka', 8.9],
		                ['Mexico City', 8.9],
		                ['Lima', 8.9]
		*/
						<?
						echo embx_columnchartformat($data,"tradingday","isincount");
						?>
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
		</script>
		<?
		
	break;
	case "graph_isincount_live":
		$sql = "select 
					t1.orderdate as tradingday, count(t1.isin) as isincount 
				from 
			    	(select distinct date(ordertime) as orderdate, isin from orders where ordertype='Live') as t1 
				group by 
					tradingday order by tradingday DESC limit 20";
		$data = embx_sql($sql);

		?>
		<script>
		$(function () {
		    $('#pagecontent').highcharts({
		        chart: {
		            type: 'column'
		        },
		        title: {
		            text: 'Number of Live ISIN\'s quoted'
		        },
		        subtitle: {
		            text: 'Includes live  orders'
		        },
		        xAxis: {
		            //type: 'category',
					categories: [ <?
					$dum = 0;
					foreach ($data as $item){
						if ($dum > 0){ echo ",";}
						echo "'" . $item["tradingday"] . "'";
						$dum = $dum +1;
					}	
						
					?> ],
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
		                text: 'Number of Live ISIN\'s'
		            }
		        },
		        legend: {
		            enabled: false
		        },
		        tooltip: {
		            pointFormat: '<b>{point.y:.1f} ISIN\'s</b>'
		        },
		        plotOptions: {
		            series: {
		                cursor: 'pointer',
		                point: {
		                    events: {
		                        click: function () {
									$.get("utils/embx_ajax.php?pf=bondlistforday&tradingday=" + this.category,function(data){
										$('#detailcontent').html(data);
									});
									$('#detailheader').html("ISIN's quoted on " + this.category);
		                        }
		                    }
		                }
		            }
		        },
		        series: [{
		            name: 'TradeData',
		            data: [
		/*                ['Shanghai', 23.7],
		                ['Lagos', 16.1],
		                ['Instanbul', 14.2],
		                ['Karachi', 14.0],
		                ['Mumbai', 12.5],
		                ['Moscow', 12.1],
		                ['São Paulo', 11.8],
		                ['Beijing', 11.7],
		                ['Guangzhou', 11.1],
		                ['Delhi', 11.1],
		                ['Shenzhen', 10.5],
		                ['Seoul', 10.4],
		                ['Jakarta', 10.0],
		                ['Kinshasa', 9.3],
		                ['Tianjin', 9.3],
		                ['Tokyo', 9.0],
		                ['Cairo', 8.9],
		                ['Dhaka', 8.9],
		                ['Mexico City', 8.9],
		                ['Lima', 8.9]
		*/
						<?
						echo embx_columnchartformat($data,"tradingday","isincount");
						?>
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
		</script>
		<?
		
	break;
	case "graph_usercount":
		$sql = "select 
					t1.orderdate as tradingday, count(t1.username) as usercount 
				from 
			    	(select distinct date(ordertime) as orderdate, username from orders ) as t1 
				group by 
					tradingday order by tradingday desc limit 20";
					
		$data = embx_sql($sql);

		$ret = embx_columngraph("pagecontent", 
								$data, 
								"tradingday", 
								"usercount", 
								"Date", 
								"No of Users", 
								"Number of Active Users", 
								"Includes only those entering orders to the platform", 
								"$.get('utils/embx_ajax.php?pf=bondlistforday&tradingday=' + this.category,function(data){
										$('#detailcontent').html(data);
										});
								$('#detailheader').html('ISIN\'s quoted on ' + this.category);", 
								"<b>{point.y:,.0f} Users</b>" );
		echo  "<script>" . $ret . "</script>";
	break;
	
	



} 




?>

