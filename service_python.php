<?php
    //Decodificamos los datos
    if(isset($_POST['results'])){

    try
    {
      $se=json_decode(stripslashes($_POST['results']));
      $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
      $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
      $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      //Si la estacion meteorologica esta registrada consulta la ultima fecha y hora de mediciones
      $stmt = $con->prepare("select estacion_id from estacion_meteorologica where estacion_id= :estacion_identificador");
      $stmt->bindParam(':estacion_identificador',$se->identificador, PDO::PARAM_INT);
      $filas=$stmt->execute();

      if($filas>0){
          //retorna al raspberry pi la ultima hora y fecha de la ultima medicion meteorologica
          $stmt = $con->prepare("select MediMete_hora AS MediMete_hora,
                                 MediMete_fecha FROM medicionmeteorologica where estacion_id= :estacion_identificador ORDER BY medimete_fecha desc,medimete_hora desc LIMIT 0,1");
          $stmt->bindParam(':estacion_identificador',$se->identificador, PDO::PARAM_INT);

          $filas=$stmt->execute();

          while($datos = $stmt->fetch()){
             $cont[]=$datos;
          }
        //retorna la fecha y la hora en formato Json codificado  
        if(!empty($cont)){
            $respuesta=json_encode($cont[0]);
            echo $respuesta;  //Envia ultima hora y fecha guardadas en la base de datos
        }
        else{
           echo 'SDTS'; //Sin datos registrados, base mediciones meteorologicas vacia
        }
      }
      else{
          echo 'ESTNRE'; //Estacion meterologica no registrada
      }
    }
    catch(Exception $e)
    {
      echo 'Error conectando con la base de datos: ' . $e->getMessage();
    }
   }

   //El raspberry pi busca en su bd interna mediciones posteriores a la fecha y hora enviada por el servidor y la envia en forma de respuesta 
   else if(isset($_POST['respuesta'])){  //stripslashes reemplazaba a isset
     try
     {
      $Array = json_decode(stripslashes($_POST['respuesta']), true);

      //Si encontro valores envia un array de mediciones que se proceden a guardar en la base de datos del servidor principal
      if($Array!=null){
      $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
      $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
      $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $cont=0;

      foreach ($Array as $key => $value)
        {
            // echo ''.$key;
            foreach ($value as $indice => $val)    
            {
                //echo  $indice.' '.$val.'<br> ';

                if(strcmp($indice,'MediMete_fecha')==0){
                    $fecha=$val;
                }
                else if(strcmp($indice,'MediMete_dioxidoCarbono')==0){
                    $co2=$val;
                }
                else if(strcmp($indice,'MediMete_humedad')==0){
                    $humedad=$val;
                }
                else if(strcmp($indice,'MediMete_velocidad')==0){
                    $velocidad=$val;
                }
                else if(strcmp($indice,'MediMete_direccion')==0){
                    $direccion=$val;
                }
                else if(strcmp($indice,'MediMete_luminocidad')==0){
                    $luminocidad=$val;
                }
                else if(strcmp($indice,'MediMete_presion')==0){
                    $presion=$val;
                }
                else if(strcmp($indice,'MediMete_temperatura')==0){
                    $temperatura=$val;
                }
                else if(strcmp($indice,'MediMete_hora')==0){
                    $hora=$val;
                }
                else if(strcmp($indice,'Estacion_id')==0){
                    $id=$val;
                }

                if($cont==9){
                    $stmt = $con->prepare("INSERT into medicionmeteorologica(Medimete_temperatura,Medimete_humedad,Medimete_direccion,Medimete_presion,Medimete_dioxidocarbono,MediMete_Velocidad,Medimete_luminocidad,Medimete_hora,Medimete_fecha,estacion_id) values(:temperatura,:humedad,:direccion,:presion,:co2,:velocidad,:luminocidad,:hora,:fecha,:id)");
                    $stmt->bindParam(':temperatura',$temperatura, PDO::PARAM_STR);
                    $stmt->bindParam(':humedad',$humedad, PDO::PARAM_STR);
                    $stmt->bindParam(':direccion',$direccion, PDO::PARAM_STR);
                    $stmt->bindParam(':presion',$presion, PDO::PARAM_STR);
                    $stmt->bindParam(':co2',$co2, PDO::PARAM_STR);
                    $stmt->bindParam(':velocidad',$velocidad, PDO::PARAM_STR);
                    $stmt->bindParam(':luminocidad',$luminocidad, PDO::PARAM_STR);
                    $stmt->bindParam(':hora',$hora, PDO::PARAM_STR);
                    $stmt->bindParam(':fecha',$fecha, PDO::PARAM_STR);
                    $stmt->bindParam(':id',$id, PDO::PARAM_INT);

                    $filas=$stmt->execute(); 
                }
                $cont++;
            }
           $cont=0;
        }

        //Si la consulta retorna un 1 significa que la inserción se realizo exitosamente
        if($filas==1){
            echo 'OK';
        }
        else{
            echo 'NOK'; //Inserción en la base de datos del servidor fallo
        }
       } 
       else{
         echo 'ARRN'; //Array null
       }
    }
       catch(Exception $e)
    {
      echo 'Error conectando con la base de datos: ' . $e->getMessage();
    }
   }

  else if(isset($_POST['estacion'])){
    try{
      //Se consulta si la estacion meteorologica que envio la peticion se encuentra registrada en la tabla           estacion_meteorologica, con lo cual se identifica que esta activa y funcionando.
      $se = json_decode(stripslashes($_POST['estacion']));
      $array_respuesta=[];

      if($se!=null){
        $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
        $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $con->prepare("SELECT parcela.`Parce_id` AS Parce_id,parcela.`Parce_nombre` AS Parce_nombre,parcela.`Parce_departamento` AS Parce_departamento,parcela.`Parce_ciudad` AS Parce_ciudad,parcela.`Parce_vereda` AS Parce_vereda,parcela.`Parce_avaluo` AS Parce_avaluo,parcela.`Parce_areaTerreno` AS Parce_areaTerreno,parcela.`Parce_unidad` AS Parce_unidad,parcela.`Parce_tipoTerreno` AS Parce_tipoTerreno,parcela.`Parce_promedioRendimiento` AS Parce_promedioRendimiento, parcela.`Parce_fechaRegistro` AS Parce_fechaRegistro,parcela.`Parce_fechaActualizacion` AS Parce_fechaActualizacion FROM `parcela` parcela INNER JOIN `estacion_meteorologica` estacion_meteorologica ON parcela.`Parce_id` = estacion_meteorologica.`parce_id`
          WHERE estacion_meteorologica.`estacion_id` = :estacion_identificador");
        
        $stmt->bindParam(':estacion_identificador',$se->identificador, PDO::PARAM_INT);
        $stmt->execute();
        //$cad=json_encode($cont);
        //echo $cad;   
           while($datos = $stmt->fetch()){
                 $cont_parcelas[]=$datos;
           }
          
          // Si retorna mas de una fila se guarda la informacion de la parcela y se consultan los cultivos
          if(!empty($cont_parcelas)){

            $array_respuesta[0]['Parcelas']=json_encode($cont_parcelas);
            $id_parcela=(string)$cont_parcelas[0]['Parce_id'];
             
            $stmt = $con->prepare("SELECT cultivo.`Culti_id` AS Culti_id,cultivo.`Culti_nombre` AS Culti_nombre,cultivo.`Culti_puntoSiembra` AS Culti_puntoSiembra,cultivo.`Culti_especie` AS Culti_especie,cultivo.`Culti_duracion` AS Culti_duracion,cultivo.`Culti_inversion` AS Culti_inversion,cultivo.`Culti_estado` AS Culti_estado,cultivo.`Culti_fecha` AS Culti_fecha,cultivo.`Culti_area` AS Culti_area,cultivo.`Culti_medida` AS Culti_medida FROM `cultivo` cultivo WHERE cultivo.`Parce_id` = :parcela_identificador");
            $stmt->bindParam(':parcela_identificador',$id_parcela, PDO::PARAM_INT);
            $stmt->execute();
            
            while($datos = $stmt->fetch()){
                  $cont_cultivos[]=$datos;
            }

            // Si se encontraron cultivos dentro de esa parcela, se buscan los nodos sensores de cada cultivo
            if(!empty($cont_cultivos)){
   
              $array_respuesta[1]['Cultivos']=json_encode($cont_cultivos);
              for($a=0;$a<count($cont_cultivos);$a++){
                
                $cultivo_identificador=(string)$cont_cultivos[$a]['Culti_id'];
                
                $stmt = $con->prepare("SELECT nodosensor.`Sens_PanId` AS Sens_PanId,nodosensor.`Sens_Mac` AS Sens_Mac,nodosensor.`Sens_estado` AS Sens_estado,nodosensor.`Sens_fechaActualizacion` AS Sens_fechaActualizacion,nodosensor.`Sens_fechaRegistro` AS Sens_fechaRegistro,nodosensor.`Sens_id` AS Sens_id,nodosensor.`Sens_nombre` AS Sens_nombre,nodosensor.`Culti_id` AS Culti_id FROM `nodosensor` nodosensor WHERE nodosensor.`Culti_id` = :cultivo_id");
                $stmt->bindParam(':cultivo_id',$cultivo_identificador, PDO::PARAM_INT);
                $stmt->execute();
            
                while($datos = $stmt->fetch()){
                      $cont_nodos[$a][]=$datos;
                }                
              }

              if(!empty($cont_nodos)){
                  $array_respuesta[2]['Nodos']=json_encode($cont_nodos);
              }
              else{
               $array_respuesta[2]['Nodos']=""; 
              }
            }
            else{
              $array_respuesta[1]['Cultivos']="";
            }
          }
          else{
            $array_respuesta[0]['Parcelas']="";
          }

          if(!empty($array_respuesta)){
             $respuesta=json_encode($array_respuesta);
             echo $respuesta; //Envia toda la consulta en formato Json
          }
          else{
             echo 'SCINC'; //Sincrononizacion incompleta, estación meteorológica no esta en uso
          }
        }
      }
        catch(Exception $e)
        {
          echo 'Error conectando con la base de datos: ' . $e->getMessage();
        }
      }

      else if(isset($_POST['mediciones'])){
        try{
          $Array_idSens = json_decode(stripslashes($_POST['mediciones']));

          if($Array_idSens!=null){
            $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.');
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $array_respuesta=[];
            $cont=0;
          foreach ($Array_idSens as $indice => $id_sensor)
           {
            $cont_medicionesSens=[]; //Vacia el array, para evitar registros duplicados
            $stmt = $con->prepare("SELECT MediSens_fecha,MediSens_hora,Sens_id from medicionsensor where Sens_id=:identificador_sensor ORDER BY medicionsensor.MediSens_fecha DESC,medicionsensor.MediSens_hora DESC LIMIT 0,1");
            $stmt->bindParam(':identificador_sensor',$id_sensor, PDO::PARAM_INT);
            $stmt->execute();

               while($datos = $stmt->fetch()){
                     $cont_medicionesSens[]=$datos;
               }            
               
             if(!empty($cont_medicionesSens)){
               $array_respuesta[$cont]=json_encode($cont_medicionesSens);
               $cont=$cont+1;
             }
              // else{
              //   $array_respuesta[$indice]=
              // }
           }
           
           if(!empty($array_respuesta)){
              $respuesta=json_encode($array_respuesta);
           }
           else{
              $respuesta='MSNULL'; //no se encontraron mediciones de sensores remotos 
           }
          
           echo $respuesta; //Envia toda la consulta en formato Json
        }
      }
        catch(Exception $e)
        {
          echo 'Error conectando con la base de datos: ' . $e->getMessage();
        }
      }

    else if(isset($_POST['ultimas_mediciones'])){
      try{
         $Array_MediSens = json_decode(stripslashes($_POST['ultimas_mediciones']));

         if($Array_MediSens!=null){
            $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.');
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $cont=0;

            foreach ($Array_MediSens as $key => $value)
             {
                 foreach ($value as $indice => $val)    
                 {
                  // echo $indice.'  -----   '.$val.'<br>';
                  if(strcmp($indice,'MediSens_temperatura')==0){
                      $temperatura=$val;
                  }
                  else if(strcmp($indice,'MediSens_humedad')==0){
                      $humedad=$val;
                  }
                  else if(strcmp($indice,'MediSens_voltaje')==0){
                      $voltaje=$val;
                  }
                  else if(strcmp($indice,'MediSens_fecha')==0){
                      $fecha=$val;
                  }
                  else if(strcmp($indice,'MediSens_hora')==0){
                      $hora=$val;
                  }
                  else if(strcmp($indice,'Sens_id')==0){
                      $sens_id=$val;
                  }

                  if($cont==5){

                    $stmt = $con->prepare("INSERT into medicionsensor(MediSens_temperatura,MediSens_humedad,MediSens_voltaje,MediSens_fecha,MediSens_hora,Sens_id) values(:temperatura,:humedad,:voltaje,:fecha,:hora,:sens_id)");
                    $stmt->bindParam(':temperatura',$temperatura, PDO::PARAM_INT);
                    $stmt->bindParam(':humedad',$humedad, PDO::PARAM_INT);
                    $stmt->bindParam(':voltaje',$voltaje, PDO::PARAM_INT);
                    $stmt->bindParam(':fecha',$fecha, PDO::PARAM_INT);
                    $stmt->bindParam(':hora',$hora, PDO::PARAM_INT);
                    $stmt->bindParam(':sens_id',$sens_id, PDO::PARAM_INT);

                    $filas=$stmt->execute(); 
                  }
                  $cont++;
               }
                $cont=0;
             }
           // Si la consulta retorna un 1 significa que la inserción se realizo exitosamente
           if($filas==1){
            echo 'OK';
           }
           else{
            echo 'NOK'; //Inserción en la base de datos del servidor fallo
           }
         }  
       else{
         echo 'ARRN'; //Array null
       }
    }
       catch(Exception $e)
        {
          echo 'Error conectando con la base de datos: ' . $e->getMessage();
        }
    }
     else if(isset($_POST['prueba1'])){
      try{
         echo 'AQUI AQUI AQUI';
       }
       catch(Exception $e)
        {
          echo 'Error conectando con la base de datos: ' . $e->getMessage();
        }
    }

?>