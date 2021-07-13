<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'files/php/index.php'; ?>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <style>
        .highcharts-data-table {
            display: none;
        }
        .highcharts-credits {
            display: none;
        }
        
        select {
            margin: auto;
            margin-left: auto;
            margin-right: auto;
            padding: 4px;
            width: 45%;
            margin: 1%;
        }
        @media (max-width: 600px) {
            select {
                width: 95%;
                margin-top: 2%;
            }
        }
    </style>
</head>
<body>
    <header>
        <?php include 'files/php/arriba.php'; ?>
    </header>
    
    <select id="mySelect" onchange="myFunction()">
        <option value="" selected="selected" disabled="disabled">--Seleccione una gráfica--</option>
        <?php
            $servername = "localhost";
            $username = "phpmyadmin";
            $password = "pass";
            $dbname = "Domotica";

            // Create connection
            $conn = new mysqli($servername, $username, $password, $dbname);
            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $sql = "SELECT Tipo FROM Tipos WHERE RoA='r' group by Tipo;";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
					if($row["Tipo"] == "humedadPlanta1") echo "<option value='Humedad'>Humedad</option>";
					else if($row["Tipo"] != "humedadPlanta2" && $row["Tipo"] != "humedadPlanta3" && $row["Tipo"] != "humedadPlanta4") echo "<option value='".ucfirst($row["Tipo"])."'>".ucfirst($row["Tipo"])."</option>";
                    //array_push($ind, $row["Tipo"]);
                }
            }
        ?>
    </select>

    <select id="mySelectT" onchange="myFunction()">
        <option value="" selected="selected" disabled="disabled">--Seleccione un periodo de tiempo--
        <option value="s">60 s
        <option value="m">60 min
        <option value="h">24 h
        <option value="d">30 d
    </select>
    
    <div id="container" style="min-width: 310px; height: 400px; margin: 0 auto; display: none;"></div>
    <?php include 'files/php/GG.php'; ?>
    <script>
        <?php if($_GET['g']=="" && $_GET['t']=="") {echo"</script></body></html>"; die;} ?>
        document.getElementById("container").style.display = "block";
        var unidades = "?";
        if(<?php echo "'".$_GET['g']."'"; ?>=="Temperatura") unidades = '°C';
        else if(<?php echo "'".$_GET['g']."'"; ?>=="Humedad") unidades = '%';
        else if(<?php echo "'".$_GET['g']."'"; ?>=="Presión") unidades = 'hPa';
        else if(<?php echo "'".$_GET['g']."'"; ?>=="Luz") unidades = '%';
        else if(<?php echo "'".$_GET['g']."'"; ?>=="Agua") unidades = 'mm/h';
        
        <?php
            $sql = "SELECT ind,ID FROM Tipos WHERE Tipo=\"".strtolower($_GET['g'])."\";";
			if($_GET['g']=="Humedad") $sql = "SELECT ind,ID FROM Tipos WHERE Tipo='humedadPlanta1' OR Tipo='humedadPlanta2' OR Tipo='humedadPlanta3' OR Tipo='humedadPlanta4' OR Tipo='humedad';";
            $result = $conn->query($sql);
            $ind = array();
            $IDs = array();
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    array_push($ind, $row["ind"]);
                    array_push($IDs, $row["ID"]);
                }
            }
            
            $nombre = array();
            foreach($IDs as $x) {
                $sql = "SELECT Nombre FROM Nombres WHERE ID=\"".$x."\";";
                $result = $conn->query($sql);
                while($row = $result->fetch_assoc()) {
                    array_push($nombre, $row["Nombre"]);
                }
            }
            
            $TEXTO = array();
            $tmp = 0;
            if($_GET['t']=="s" || $_GET['t']=="m") $tmp = 60;
            else if($_GET['t']=="h") $tmp = 24;
            else if($_GET['t']=="d") $tmp = 30;
            foreach($ind as $x) {
                $val = array();
                $index = array_search($x, $ind);
                $sql = "SELECT Time,Val FROM Valor WHERE ind=\"".$x."\" AND Tiempo=\"".$_GET['t']."\" ORDER BY Valor.Time ASC;";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $z = 0;
                    while($row = $result->fetch_assoc()) {
                        while($row["Time"] > $z) {
                            if($val[$z-1]=="") array_push($val, 0);
                            else array_push($val, $val[$z-1]);
                            //array_push($val, 0);
                            $z++;
                        }
                        if($row["Time"] == $z) {
                            array_push($val, $row["Val"]);
                            $z++;
                        }
                    }
                    while($z<$tmp) {
                        array_push($val, 0);
                        $z++;
                    }
                }
                
                $valores = "";
                for($y = 0; $y != $tmp; $y++) $valores .= $val[$y].", ";
                $valores = substr($valores, 0, -2);
                array_push($TEXTO, "{ name: '".$nombre[$index]."', data: [".$valores."] }");
            }
            $conn->close();
        ?>

Highcharts.chart('container', {
  chart: {
    type: 'line'
  },
  title: {
    text: <?php echo "'".$_GET['g']."'"; ?>
  },
  yAxis: {
    title: {
      text: <?php echo "'".$_GET['g']."'"; ?>+' ('+unidades+')'
    }
  },
  xAxis: {
    title: {
      text: 'Tiempo ['+<?php echo "'".$_GET['t']."'"; ?>+']'
    }
  },
  plotOptions: { 
    line: {
      dataLabels: {
        enabled: true
      },
      enableMouseTracking: true
    }
  },
  series: [
  <?php foreach($TEXTO as $t) {
    echo $t;
    if(array_search($t, $TEXTO)!=(count($TEXTO)-1)) echo ", ";
  }?>
  ]
});
    </script>
</body>
</html>
