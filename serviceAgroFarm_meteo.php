<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Max-Age: 1000');
//header("Content-Type: text/html;charset=utf-8");

    //Decodificamos los datos
  if(isset($_POST["Datos"])){
      $se=json_decode(stripslashes($_POST["Datos"]));

      try
      {
        $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.');
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $con->prepare("select * from medicionmeteorologica where estacion_id =".$se->estacion_id." and medimete_fecha='".$se->fecha."' and medimete_hora>'".$se->hora."' union select * from medicionmeteorologica where medimete_fecha>'".$se->fecha."' and estacion_id = ".$se->estacion_id." order by medimete_fecha asc,medimete_hora asc");

    	  $stmt->execute();
          while($datos = $stmt->fetch()){
           	 $cont[]=$datos;
          }

  	   $cad=json_encode($cont);
  	   echo $cad;  	
      }
      catch(PDOException $e)
      {
        echo 'Error conectando con la base de datos: ' . $e->getMessage();
      }
  }
  else if(isset($_POST["Inventario"])){
    //echo '................................666';
    try{
     $se=json_decode(stripslashes($_POST["Inventario"]));
     $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.');
     $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

     $stmt = $con->prepare("select estacion_identificador,estacion_fechaManufactura,estacion_horaManufactura,estacion_version from estacion_fabrica where estacion_identificador = :estacion_identificador");
     $stmt->bindParam(':estacion_identificador',$se->estacion, PDO::PARAM_INT);

        $stmt->execute();
          while($datos = $stmt->fetch()){
             $cont[]=$datos;
          }

          if(!empty($cont)){
             $stmt = $con->prepare("select estacion_id from estacion_meteorologica where estacion_id=:estacion_id");
             $stmt->bindParam(':estacion_id',$se->estacion, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

            if($results){
              echo 'ERA';
            }
            else{
              $cad=json_encode($cont);
              echo $cad;   
            }
          }
          else{
            echo 'NOK';
          }
        }
    catch(Exception $e){
      echo $e;
    }
  }
  else if(isset($_POST["Inventario_Sensor"])){
    //echo '................................666';
    try{
     $se=json_decode(stripslashes($_POST["Inventario_Sensor"]));
     $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.');
     $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

     $stmt = $con->prepare("select nodo_id,nodo_fechaManufactura,nodo_horaManufactura,nodo_version from nodos_fabrica where nodo_id = :nodo_identificador");
     $stmt->bindParam(':nodo_identificador',$se->nodo, PDO::PARAM_INT);

        $stmt->execute();
          while($datos = $stmt->fetch()){
             $cont[]=$datos;
          }

          if(!empty($cont)){
             $stmt = $con->prepare("select Sens_Mac from nodosensor where Sens_Mac=:nodo_identificador");
             $stmt->bindParam(':nodo_identificador',$se->nodo, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

            if($results){
              echo 1;
            }
            else{
              $cad=json_encode($cont);
              echo $cad;   
            }
          }
          else{
            echo 2;
          }
        }
    catch(Exception $e){
      echo $e;
    }
  }
  else if(isset($_POST["Mediciones_meteo"])){
     $se=json_decode(stripslashes($_POST["Mediciones_meteo"]));
     $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.');
     $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

     $stmt = $con->prepare("select MediMete_temperatura,MediMete_humedad,MediMete_direccion,MediMete_presion,MediMete_dioxidoCarbono,MediMete_velocidad,MediMete_luminocidad,MediMete_hora,MediMete_fecha from medicionmeteorologica where Estacion_id = :estacion_identificador");
     $stmt->bindParam(':estacion_identificador',$se->estacion_id, PDO::PARAM_INT);

        $stmt->execute();
          while($datos = $stmt->fetch()){
             $cont[]=$datos;
          }

          if(!empty($cont)){
              $cad=json_encode($cont);
              echo $cad;   
          }
          else{
            echo 'NOK'; //No existen mediciones meteorologicas para esa estación
          }
  }
//////////////////////////////////////////////////////////////////////////////////////////////////
  else if(isset($_POST["Sensor"])){
      $se=json_decode(stripslashes($_POST["Sensor"]));
      try
      {
        $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.');
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $con->prepare("select medicionsensor.MediSens_temperatura AS MediSens_temperatura,medicionsensor.MediSens_humedad AS MediSens_humedad,medicionsensor.MediSens_voltaje AS MediSens_voltaje,medicionsensor.MediSens_fecha AS MediSens_fecha,medicionsensor.MediSens_hora AS MediSens_hora FROM nodosensor INNER JOIN medicionsensor ON nodosensor.Sens_id = medicionsensor.Sens_id WHERE nodosensor.Sens_Mac=:sensor_identificador and medicionsensor.medisens_fecha=:sensor_fecha and medicionsensor.medisens_hora>:sensor_hora union select medicionsensor.MediSens_temperatura AS MediSens_temperatura,medicionsensor.MediSens_humedad AS MediSens_humedad,medicionsensor.MediSens_voltaje AS MediSens_voltaje,medicionsensor.MediSens_fecha AS MediSens_fecha,medicionsensor.MediSens_hora AS MediSens_hora FROM nodosensor INNER JOIN medicionsensor ON nodosensor.Sens_id = medicionsensor.Sens_id WHERE nodosensor.Sens_Mac=:sensor_identificador order by medisens_fecha asc,medisens_hora asc");
        $stmt->bindParam(':sensor_identificador',$se->sensor_id, PDO::PARAM_INT);
        $stmt->bindParam(':sensor_fecha',$se->fecha, PDO::PARAM_INT);
        $stmt->bindParam(':sensor_hora',$se->hora, PDO::PARAM_INT);

        $stmt->execute();
          while($datos = $stmt->fetch()){
             $cont[]=$datos;
          }

       $cad=json_encode($cont);
       echo $cad;   
      }
      catch(PDOException $e)
      {
        echo 'Error conectando con la base de datos: ';
      }
  }

  else if(isset($_POST["Mediciones_sensor"])){
     $se=json_decode(stripslashes($_POST["Mediciones_sensor"]));
     try
      {
        $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.');
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $con->prepare("select medicionsensor.MediSens_temperatura AS MediSens_temperatura,medicionsensor.MediSens_humedad AS MediSens_humedad,medicionsensor.MediSens_voltaje AS MediSens_voltaje,medicionsensor.MediSens_fecha AS MediSens_fecha,medicionsensor.MediSens_hora AS MediSens_hora FROM nodosensor INNER JOIN medicionsensor ON nodosensor.Sens_id = medicionsensor.Sens_id WHERE nodosensor.Sens_Mac=:sensor_identificador");
        $stmt->bindParam(':sensor_identificador',$se->sensor_id, PDO::PARAM_INT);

        $stmt->execute();
          while($datos = $stmt->fetch()){
             $cont[]=$datos;
          }

          if(!empty($cont)){
              $cad=json_encode($cont);
              echo $cad;   
          }
          else{
            echo 'NOK'; //No existen mediciones meteorologicas para esa estación
          }
       }
     catch(PDOException $e)
     {
       echo 'Error conectando con la base de datos: '.$e;
     }
  }
?>