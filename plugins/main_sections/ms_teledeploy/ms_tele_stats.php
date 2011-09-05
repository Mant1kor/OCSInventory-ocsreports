<?php 
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://www.ocsinventory-ng.org
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2010 $$Author: Erwan Goalou

require('require/function_stats.php');


if($_SESSION['OCS']['CONFIGURATION']['TELEDIFF']=="YES"){

	if( isset($protectedPost["ACTION"]) and $protectedPost["ACTION"] == "VAL_SUCC") {	
		$sql="DELETE FROM devices WHERE name='DOWNLOAD' AND tvalue LIKE '%s' AND
				ivalue IN (SELECT id FROM download_enable WHERE fileid='%s') 
				AND hardware_id NOT IN (SELECT id FROM hardware WHERE deviceid='_SYSTEMGROUP_')";
		$arg=array('SUCCESS%',$protectedGet["stat"]);
		$resSupp = mysql2_query_secure($sql,$_SESSION['OCS']["writeServer"],$arg);
	}
	if( isset($protectedPost["ACTION"]) and $protectedPost["ACTION"] == "DEL_ALL") {	
		$sql="DELETE FROM devices WHERE name='DOWNLOAD' AND tvalue IS NOT NULL AND
				ivalue IN (SELECT id FROM download_enable WHERE fileid='%s') 
				AND hardware_id NOT IN (SELECT id FROM hardware WHERE deviceid='_SYSTEMGROUP_')";
		$arg=$protectedGet["stat"];
		$resSupp = mysql2_query_secure($sql,$_SESSION['OCS']["writeServer"],$arg);
	}
	if( isset($protectedPost["ACTION"]) and $protectedPost["ACTION"] == "DEL_NOT") {	
		$sql="DELETE FROM devices WHERE name='DOWNLOAD' AND tvalue IS NULL AND
				ivalue IN (SELECT id FROM download_enable WHERE fileid='%s') 
				AND hardware_id NOT IN (SELECT id FROM hardware WHERE deviceid='_SYSTEMGROUP_')";
		$arg=$protectedGet["stat"];
		$resSupp = mysql2_query_secure($sql,$_SESSION['OCS']["writeServer"],$arg);
	}
}

$form_name="show_stats";
$table_name=$form_name;	
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";

$sql="SELECT name FROM download_available WHERE fileid='%s'";
$arg=$protectedGet["stat"];
$res =mysql2_query_secure($sql, $_SESSION['OCS']["readServer"],$arg);		
$row=mysql_fetch_object($res);
printEnTete( $l->g(498)." <b>".$row -> name."</b> (".$l->g(296).": ".$protectedGet["stat"]." )");


//count max values for stats
$sql_count="SELECT COUNT(id) as nb 
			FROM devices d, download_enable e 
			WHERE e.fileid='%s'
 				AND e.id=d.ivalue 
				AND name='DOWNLOAD' 
				AND hardware_id NOT IN (SELECT id FROM hardware WHERE deviceid='_SYSTEMGROUP_' or deviceid='_DOWNLOADGROUP_')";
$arg=$protectedGet["stat"];
$rescount =mysql2_query_secure($sql_count, $_SESSION['OCS']["readServer"],$arg);	
$row=mysql_fetch_object($rescount);
$total=$row->nb;
if ($total<=0){
	msg_error($l->g(837));
	require_once(FOOTER_HTML);
	die();
}
$sqlStats="SELECT COUNT(id) as nb, tvalue as txt 
			FROM devices d, download_enable e 
			WHERE e.fileid='%s'
 				AND e.id=d.ivalue 
				AND name='DOWNLOAD' 
				AND hardware_id NOT IN (SELECT id FROM hardware WHERE deviceid='_SYSTEMGROUP_' or deviceid='_DOWNLOADGROUP_')";

$sqlStats.= " GROUP BY tvalue";

$resStats =mysql2_query_secure($sqlStats." ORDER BY nb DESC", $_SESSION['OCS']["readServer"],$arg);		
$i=0;
while ($row=mysql_fetch_object($resStats)){
	if( $row->txt =="" )
		$name_value[$i] = $l->g(482);
	else
		$name_value[$i] = $row->txt;
	$pourc=round(($row->nb*100)/$total,2);
	$legend[$i]=$name_value[$i]." (".$pourc."%)";
	$link[$i]=$name_value[$i];
	$name_value[$i].="<br>(".$pourc."%)";
	$count_value[$i]=$row->nb;
	if (isset($arr_FCColors[$i]))
		$color[$i]=$arr_FCColors[$i];
	else
		$color[$i]=$arr_FCColors[$i-10];	
	$color[$i]="plotProps: {fill: \"".$color[$i]."\"}";
	$i++;	
}
	echo '<br><div  class="mlt_bordure" >';
	echo '<CENTER><div id="chart" style="width: 900px; height: 500px"></div></CENTER>';
		echo '<script type="text/javascript">
		$(function() {
		  $("#chart").chart({
			  template: "pie_stat_teledeploy",
			  values: {
			    serie1: ['.implode(',',$count_value).']
			  },
			  labels: ["'.implode('","',$name_value).'"],
			  legend: ["'.implode('","',$legend).'"],
			  tooltips: {
			    serie1: ["'.implode('","',$name_value).'"]
			  },
			  defaultSeries: {
			    values: [{'.implode("}, {",$color).'
			    }]
			  }
			});		
		});
		
		$.elycharts.templates[\'pie_stat_teledeploy\'] = {
		  type: "pie",
		  defaultSeries: {
		    plotProps: {
		      stroke: "white",
		      "stroke-width": 2,
		      opacity: 0.8
		    },
		    highlight: {
		      move: 20
		    },
		    tooltip: { 
		     width: 200, height: 25,    
		     frameProps: {opacity: 0.5},
		     contentStyle : { "font-family": "Arial", "font-size": "9px", "line-height": "8px", color: "black" } 
		    },
		    startAnimation: {
		      active: true,
		      type: "grow"
		    }
		  },
		  features: {
		    legend: {
		      horizontal: false,
		      width: 240,
		      height: 115,
		      x: 650,
		      y: 380,
		      borderProps: {
		        "fill-opacity": 0.3
		      }
		    }
		  }
		};
		</script>';	
echo "</div><br>";

if($_SESSION['OCS']['CONFIGURATION']['TELEDIFF']=="YES"){
	echo "<table class='Fenetre' align='center' border='1' cellpadding='5' width='50%'><tr BGCOLOR='#C7D9F5'>";
	echo "<td width='33%' align='center'><a OnClick='pag(\"VAL_SUCC\",\"ACTION\",\"".$form_name."\");'><b>".$l->g(483)."</b></a></td>";	
	echo "<td width='33%' align='center'><a OnClick='pag(\"DEL_ALL\",\"ACTION\",\"".$form_name."\");'><b>".$l->g(571)."</b></a></td>";	
	echo "<td width='33%' align='center'><a OnClick='pag(\"DEL_NOT\",\"ACTION\",\"".$form_name."\");'><b>".$l->g(575)."</b></a></td>";
	echo "</tr></table><br><br>";
	echo "<input type='hidden' id='ACTION' name='ACTION' value=''>";
}
echo "<table class='Fenetre' align='center' border='1' cellpadding='5' width='50%'>
<tr BGCOLOR='#C7D9F5'><td width='30px'>&nbsp;</td><td align='center'><b>".$l->g(81)."</b></td><td align='center'><b>".$l->g(55)."</b></td></tr>";
$j=0;
while( $j<$i ) {
	$nb+=$count_value[$j];
	echo "<tr>";
	if (isset($arr_FCColors[$j]))
		echo "<td bgcolor='".$arr_FCColors[$j]."'>";
	else
		echo "<td>";
	echo "&nbsp;</td><td>".$link[$j]."</td><td>
			<a href='index.php?".PAG_INDEX."=".$pages_refs['ms_multi_search']."&prov=stat&id_pack=".$protectedGet["stat"]."&stat=".urlencode($link[$j])."'>".$count_value[$j]."</a>";
	if (substr_count($link[$j], 'SUC'))
		echo "<a href=# onclick=window.open(\"index.php?".PAG_INDEX."=".$pages_refs['ms_speed_stat']."&head=1&ta=".urlencode($link[$j])."&stat=".$protectedGet["stat"]."\",\"stats_speed\",\"location=0,status=0,scrollbars=0,menubar=0,resizable=0,width=1000,height=900\")>&nbsp<img src='image/stat.png'></a>";		
	echo "	</td></tr>";
	$j++;
}
echo "<tr bgcolor='#C7D9F5'><td bgcolor='white'>&nbsp;</td><td><b>".$l->g(87)."</b></td><td><b>".$nb."</b></td></tr>";
echo "</table><br><br>";
 echo "</form>";
  
?>
