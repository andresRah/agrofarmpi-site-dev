<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Max-Age: 1000');
    //Decodificamos los datos
  if(isset($_POST["Login"])){
    try{
        $se=json_decode(stripslashes($_POST["Login"]));
        $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
        $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $con->prepare("select * from usuario where Usua_name= :nick_name AND Usua_contrasena= :pass");
        $stmt->bindParam(':nick_name',$se->nick_name, PDO::PARAM_INT);
        $stmt->bindParam(':pass',md5($se->pass), PDO::PARAM_INT);
        $stmt->execute();
        $results=$stmt->fetchAll(PDO::FETCH_ASSOC);

          if(!empty($results)){        
             $json=json_encode($results);
             echo $json;  //ENVIA TODOS LOS DATOS DEL USUARIO, INCLUYENDO LA FECHA Y HORA DEL ULTIMO ACCESO
          }
          else{
             echo 'NOK'; //USUARIO NO REGISTRADO, LOGIN ERROR
         }
      }
      catch(Exception $e)
      {
        echo 'Error conectando con la base de datos: ' . $e->getMessage();
      }
  } 
  else if(isset($_POST["Nuevo_UsuarioJAVA"])){
    try{
        $se=json_decode(stripslashes($_POST["Nuevo_UsuarioJAVA"]));
        $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
        $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $con->prepare("select Usua_id from usuario where Usua_identificacion= :usua_id");
        $stmt->bindParam(':usua_id',$se->usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $results=$stmt->fetch();

        $usua_id='';

        foreach ($results as $indice => $val)    
        {
           if(strcmp($indice,'Usua_id')==0){
               $usua_id=$val;
           }
        }

        if($usua_id!='')
        {
          $stmt = $con->prepare("select * from parcela where Usua_id= :usua_id"); //Se consultan las parcelas
          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt->execute();
          $results=$stmt->fetchAll(PDO::FETCH_ASSOC);
        
          if(!empty($results)){        
            $json_parcela=$results;
            $datos['PARCELAS']=$json_parcela;

          $stmt = $con->prepare("select * from cultivo where parce_id in(select parce_id from parcela where Usua_id= :usua_id)"); //Se consultan los cultivos
          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt->execute();
          $results=$stmt->fetchAll(PDO::FETCH_ASSOC);

          if(!empty($results)){ //Se consultan los nodos sensores asociados a esos cultivos
            $datos['CULTIVO']=$results;

            $stmt = $con->prepare("select * from resultados_rasta where Culti_id in(select culti_id from cultivo where parce_id in(select parce_id from parcela where Usua_id= :usua_id))");
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetchAll(PDO::FETCH_ASSOC);
   
            if(!empty($results)){//Alerta Programada, si existe existen alertas meteorologicas y por medicion
              $datos['RASTA']=$results;
            }
            
            $stmt = $con->prepare("select * from nodosensor where culti_id in(select culti_id from cultivo where parce_id in(select parce_id from parcela where Usua_id= :usua_id))"); //Se consultan las parcelas
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetchAll(PDO::FETCH_ASSOC);

            if(!empty($results)){  //Se consultan las mediciones de cada sensor asociado a cada cultivo
               $datos['NODOS']=$results;              
  
               $stmt = $con->prepare("select * from medicionsensor where sens_id in(select sens_id from nodosensor where culti_id in(select culti_id from cultivo where parce_id in(select parce_id from parcela where Usua_id= :usua_id)))");
               $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
               $stmt->execute();
               $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                   
              if(!empty($result)){// Se buscan los insumos registrados por ese usuario
                $datos['MEDICIONES']=$result;  
              }
            }
            
            /*Si existen cultivos probablemente existan cosechas*/
            $stmt = $con->prepare("select * from cosecha where Culti_id in(select culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
            if(!empty($result)){ //Se consultan las cosechas            
               $datos['COSECHAS']=$result;
            }           
          }
        }

        $stmt = $con->prepare("select * from insumo where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){// Se buscan las enfermedades registradas por ese usuario
           $datos['INSUMOS']=$result;
        }         
        
        $stmt = $con->prepare("select * from enfermedad where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){  // Se buscan las afecciones que esa enfermedad haya causado a algun cultivo
           $datos['ENFERMEDADES']=$result;  

           $stmt = $con->prepare("select * from afecta_enfermedad where Enfe_id in(select enfe_id from enfermedad where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

           if(!empty($result)){// Se buscan los tratamientos registrados para esa enfermedad            
              $datos['AFECTA_ENFERMEDADES']=$result; 

              $stmt = $con->prepare("select * from tratamiento_enfermedad where Enfe_id in(select enfe_id from enfermedad where Usua_id= :usua_id)");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                   
              if(!empty($result)){// Se buscan los resultados obtenidos para ese tratamiento de enfermedad               
                 $datos['TRATA_ENFERMEDADES']=$result;

                 $stmt = $con->prepare("select * from resultados_trata_enfermedad where Trata_id in(select Trata_id from tratamiento_enfermedad where Enfe_id in(select Enfe_id from enfermedad where Usua_id= :usua_id))");
                 $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
                 $stmt->execute();
                 $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

                 if(!empty($result)){               
                   $datos['RESULTADOS_ENFERMEDADES']=$result;
                 }
              }
           }
        }
                   
        $stmt = $con->prepare("select * from maleza where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){               
           $datos['MALEZA']=$result; 

           $stmt = $con->prepare("select * from afecta_maleza where male_id in(select male_id from maleza where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                        
           if(!empty($result)){               
              $datos['AFECTA_MALEZA']=$result; 
                      
              $stmt = $con->prepare("select * from tratamiento_maleza where male_id in(select male_id from maleza where Usua_id= :usua_id)");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                   
              if(!empty($result)){// Se buscan los resultados obtenidos para ese tratamiento de enfermedad               
                 $datos['TRATA_MALEZA']=$result;

                 $stmt = $con->prepare("select * from resultados_trata_maleza where Trata_id in(select Trata_id from tratamiento_maleza where male_id in(select Male_id from maleza where Usua_id= :usua_id))");
                 $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
                 $stmt->execute();
                 $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

                 if(!empty($result)){               
                   $datos['RESULTADOS_MALEZA']=$result;
                 }
              }
           }
        }
                      
        $stmt = $con->prepare("select * from Insecto where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){               
            $datos['PLAGA']=$result; 

           $stmt = $con->prepare("select * from afecta_insecto where inse_id in(select inse_id from Insecto where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                        
           if(!empty($result)){               
              $datos['AFECTA_PLAGA']=$result; 

              $stmt = $con->prepare("select * from tratamiento_insecto where inse_id in(select inse_id from Insecto where Usua_id= :usua_id)");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                   
              if(!empty($result)){// Se buscan los resultados obtenidos para ese tratamiento de enfermedad               
                 $datos['TRATA_PLAGA']=$result;

                 $stmt = $con->prepare("select * from resultados_trata_insecto where Trata_id in(select Trata_id from tratamiento_insecto where inse_id in(select Inse_id from Insecto where Usua_id= :usua_id))");
                 $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
                 $stmt->execute();
                 $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

                 if(!empty($result)){ //Se consultan los clientes              
                    $datos['RESULTADOS_PLAGA']=$result;
                 }   
              }
           }
        }

        $stmt = $con->prepare("select * from cliente where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
        if(!empty($result)){ //Se consultan los clientes, si hay clientes pueden existir facturas y ventas         
           $datos['CLIENTE']=$result;

           $stmt = $con->prepare("select * from facturaventa where Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

           if(!empty($result)){ //Se consultan las facturas,si existen facturas se consultan ventas            
              $datos['FACTURA_VENTA']=$result;

              $stmt = $con->prepare("select * from ventacosecha where Cose_id in(select cose_id from cosecha where Culti_id in(select culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

              if(!empty($result)){ //Se consultan las ventas por cosecha
                 $datos['VENTA_COSECHA']=$result;
              }
                        
              $stmt = $con->prepare("select * from ventainsumo where Fact_id in(select Fact_id from facturaventa where Clie_id in(select Clie_id from cliente where Usua_id= :usua_id))");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

              if(!empty($result)){ //Se consultan las ventas por insumo
                 $datos['VENTA_INSUMO']=$result;
              }
            }
          }

       $stmt = $con->prepare("select * from proveedor where Usua_id= :usua_id");
       $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
       $stmt->execute();
       $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
       if(!empty($result)){ //Se consultan los proveedores, si existen proveedores existen facturas y ompras              
          $datos['PROVEEDOR']=$result;
                    
          $stmt = $con->prepare("select * from factura where Prove_id in(select Prove_id from proveedor where Usua_id= :usua_id)");
          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt->execute();
          $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

          if(!empty($result)){// Si existen facturas entonces existen compras
             $datos['FACTURA_COMPRA']=$result;  

             $stmt = $con->prepare("select * from compra where Fact_id in(select Fact_id from factura where Prove_id in(select Prove_id from proveedor where Usua_id= :usua_id))");
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                      
             if(!empty($result)){// Se consultan las compras
                $datos['COMPRAS']=$result;                        
             }
           }
         }                    
                    
       $stmt = $con->prepare("select * from nomina where Usua_id= :usua_id");
       $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
       $stmt->execute();
       $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
       if(!empty($result)){// Se consultan las nominas registradas
          $datos['NOMINAS']=$result;
       }

       $stmt = $con->prepare("select * from empleado where Usua_id= :usua_id");
       $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
       $stmt->execute();
       $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
        if(!empty($result)){// Se consultan los empleados, si hay empleados se consultan los pagos
           $datos['EMPLEADO']=$result;

           $stmt = $con->prepare("select * from pago where Emple_id in(select Emple_id from empleado where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

           if(!empty($result)){
             $datos['PAGOS']=$result;
           }
    
           //Si hay empleados puede que existan tareas
           // CONSULTA DE TAREAS
           $stmt = $con->prepare("select * from tarea where Usua_id= :usua_id");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

           if(!empty($result)){//Si hay tareas puede que se usen insumos
              $datos['TAREAS']=$result;
                     
              $stmt = $con->prepare("select * from utiliza_insum_tarea where Tarea_id in(select Tarea_id from tarea Usua_id= :usua_id)");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                      
              if(!empty($result)){//Si hay tareas puede que se usen insumos
                 $datos['UTILIZA_INSUM_TAREA']=$result;
              }
           }                       
        }

       //Se consultan las estaciones meteorologicas registradas
       $stmt = $con->prepare("select * from estacion_meteorologica where Parce_id in(select parce_id from parcela where Usua_id= :usua_id)");
       $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
       $stmt->execute();
       $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

       if(!empty($result)){//Si existen estaciones meterologicas registradas hay mediciones
          $datos['ESTACION']=$result;

          $stmt = $con->prepare("select * from medicionmeteorologica where Estacion_id in(select Estacion_id from estacion_meteorologica where Parce_id in(select parce_id from parcela where Usua_id= :usua_id))");
          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt->execute();
          $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

          if(!empty($result)){//Mediciones meteorologicas
            $datos['MEDICION_METEO']=$result;
          }              
      }

      //Se consultan los eventos del calendario
        $stmt = $con->prepare("select * from evento where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){//Eventos
           $datos['EVENTOS']=$result;
        }              

        //Se consultan alertas programadas
        $stmt = $con->prepare("select * from alertapro where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){//Alerta Programada, si existe existen alertas meteorologicas y por medicion
           $datos['ALERTAS']=$result;

           $stmt = $con->prepare("select * from alertaactiva_sensor where AlerPro_id in(select AlerPro_id from alertapro where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

           if(!empty($result)){//Alerta Programada, si existe existen alertas meteorologicas y por medicion
              $datos['ALERTA_SENSOR']=$result;
           }

          $stmt = $con->prepare("select * from alertaactiva_meteorologia where AlerPro_id in(select AlerPro_id from alertapro where Usua_id= :usua_id)");
          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt->execute();
          $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

          if(!empty($result)){//Alerta Programada, si existe existen alertas meteorologicas y por medicion
            $datos['ALERTA_ESTACION']=$result;
         }
        }  
        echo json_encode($datos); 
       }   
       else{
         echo 'NOK'; //USUARIO NO REGISTRADO, LOGIN ERROR
       }
      }
      catch(Exception $e)
      {
        echo 'Error conectando con la base de datos, sincronizacion nuevo usuario: ' . $e->getMessage();
      }
  }
  else if(isset($_POST["Actualizar_Servidor"])){ //Se envia la tabla de cambios del servidor al cliente dependiendo el Usua_id
    try{
      $se=json_decode($_POST["Actualizar_Servidor"]);
       //$seo=json_decode($se->PARCELA);
       
      $usua_id = json_decode($se->USUA_ID,true);

      if($usua_id!='' && $usua_id!==NULL){
      $Array = json_decode($se->PARCELA,true);
      $Array2 = json_decode($se->CULTIVO,true);
      $Array3 = json_decode($se->RASTA,true);
      $Array4 = json_decode($se->NODOS,true);
      $Array44 = json_decode($se->MEDICIONES,true);
      //Mediciones sensores faltaria
      $Array5 = json_decode($se->COSECHAS,true);
      $Array6 = json_decode($se->INSUMOS,true);
      $Array7 = json_decode($se->ENFERMEDADES,true);
      $Array8 = json_decode($se->AFECTA_ENFERMEDADES,true);
      $Array9 = json_decode($se->TRATA_ENFERMEDADES,true);
      $Array10 = json_decode($se->RESULTADOS_ENFERMEDADES,true);
      $Array11 = json_decode($se->MALEZA,true);
      $Array12 = json_decode($se->AFECTA_MALEZA,true);
      $Array13 = json_decode($se->TRATA_MALEZA,true);
      $Array14 = json_decode($se->RESULTADOS_MALEZA,true);
      $Array15 = json_decode($se->PLAGA,true);
      $Array16 = json_decode($se->AFECTA_PLAGA,true);
      $Array17 = json_decode($se->TRATA_PLAGA,true);
      $Array18 = json_decode($se->RESULTADOS_PLAGA,true);
      $Array19 = json_decode($se->CLIENTE,true);
      $Array20 = json_decode($se->FACTURA_VENTA,true);
      $Array21 = json_decode($se->VENTA_COSECHA,true);
      $Array22 = json_decode($se->VENTA_INSUMO,true);
      $Array23 = json_decode($se->PROVEEDOR,true);
      $Array24 = json_decode($se->FACTURA_COMPRA,true);
      $Array25 = json_decode($se->COMPRAS,true);
      $Array26 = json_decode($se->NOMINAS,true);
      $Array27 = json_decode($se->EMPLEADO,true);
      $Array28 = json_decode($se->PAGOS,true);
      $Array29 = json_decode($se->TAREAS,true);
      $Array30 = json_decode($se->UTILIZA_INSUM_TAREA,true);
      $Array31 = json_decode($se->ESTACION,true);
      $Array32 = json_decode($se->MEDICION_METEO,true);
      $Array33 = json_decode($se->EVENTOS,true);
      $Array34 = json_decode($se->ALERTAS,true);
      $Array35 = json_decode($se->ALERTA_SENSOR,true);
      $Array36 = json_decode($se->ALERTA_ESTACION,true);

      $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
      $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
      $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      if(!empty($Array)){
        try {
          if($Array['Usua_id'][0]=="TABLAVACIA"){
           $stmt1 = $con->prepare('delete from parcela where Usua_id= :usua_id');
           $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt1->execute();
           }
        else{
           $auxiliar= array();
            for($i = 0; $i < count($Array['Usua_id']); $i++) {

              $existencia=Verifica_Existencia("PARCELA",$Array['Parce_nombre'][$i],$usua_id);

              if($existencia){ //Si existe actualize
                $stmt = $con->prepare("UPDATE parcela SET Parce_departamento=:Parce_departamento,Parce_ciudad=:Parce_ciudad,Parce_vereda=:Parce_vereda,Parce_avaluo=:Parce_avaluo,Parce_areaTerreno=:Parce_areaTerreno,Parce_unidad=:Parce_unidad,Parce_tipoTerreno=:Parce_tipoTerreno,Parce_promedioRendimiento=:Parce_promedioRendimiento,Parce_obsevaciones=:Parce_obsevaciones,Parce_fechaRegistro=:Parce_fechaRegistro,Parce_fechaActualizacion=:Parce_fechaActualizacion where Parce_nombre=:Parce_nombre AND Usua_id= :Usua_id");
                $stmt->bindParam(':Parce_nombre',$Array['Parce_nombre'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_departamento',$Array['Parce_departamento'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_ciudad',$Array['Parce_ciudad'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_vereda',$Array['Parce_vereda'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_avaluo',$Array['Parce_avaluo'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_areaTerreno',$Array['Parce_areaTerreno'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_unidad',$Array['Parce_unidad'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_tipoTerreno',$Array['Parce_tipoTerreno'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_promedioRendimiento',$Array['Parce_promedioRendimiento'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_obsevaciones',$Array['Parce_obsevaciones'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_fechaRegistro',$Array['Parce_fechaRegistro'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_fechaActualizacion',$Array['Parce_fechaActualizacion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Usua_id',$Array['Usua_id'][$i], PDO::PARAM_INT);
                $stmt->execute();
              }
              else{ //Sino existe inserte
               $stmt = $con->prepare("INSERT INTO parcela (Parce_id,Parce_nombre,Parce_departamento,Parce_ciudad,Parce_vereda,Parce_avaluo,Parce_areaTerreno,Parce_unidad,Parce_tipoTerreno,Parce_promedioRendimiento,Parce_obsevaciones,Parce_fechaRegistro,Parce_fechaActualizacion,Usua_id) VALUES(null,:Parce_nombre,:Parce_departamento,:Parce_ciudad,:Parce_vereda,:Parce_avaluo,:Parce_areaTerreno,:Parce_unidad,:Parce_tipoTerreno,:Parce_promedioRendimiento,:Parce_obsevaciones,:Parce_fechaRegistro,:Parce_fechaActualizacion,:Usua_id)");

               $stmt->bindParam(':Parce_nombre',$Array['Parce_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_departamento',$Array['Parce_departamento'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_ciudad',$Array['Parce_ciudad'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_vereda',$Array['Parce_vereda'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_avaluo',$Array['Parce_avaluo'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_areaTerreno',$Array['Parce_areaTerreno'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_unidad',$Array['Parce_unidad'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_tipoTerreno',$Array['Parce_tipoTerreno'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_promedioRendimiento',$Array['Parce_promedioRendimiento'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_obsevaciones',$Array['Parce_obsevaciones'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_fechaRegistro',$Array['Parce_fechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_fechaActualizacion',$Array['Parce_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Usua_id',$Array['Usua_id'][$i], PDO::PARAM_INT);
               $stmt->execute();
               //echo 'Ultimo id '.$con->lastInsertId($stmt);
               echo 'Ok';
            } 
            $var='"'.$Array['Parce_nombre'][$i].'"';
            array_push($auxiliar,$var);
          }

          // Se procede a borrar las parcelas que no venian de la aplicacion de escritorio
          $statut = implode(',',$auxiliar);
          $stmt1 = $con->prepare('delete from parcela where Parce_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
          $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt1->execute();
          }
         } 
         catch(Exception $e) { 
           echo 'Error conectando con la base de datos: PARCELA' . $e->getMessage();
           $stmt->rollback(); 
        } 
      }

      if(!empty($Array2)){
        try{
         if($Array2['Culti_nombre'][0]=="TABLAVACIA"){
           $stmt1 = $con->prepare('delete from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)');
           $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt1->execute();
           }
        else{
         $auxiliar1= array();
         for($i = 0; $i < count($Array2['Culti_nombre']); $i++) {
          $existencia=Verifica_Existencia("CULTIVO",$Array2['Culti_nombre'][$i],$usua_id);
        
          if($existencia){
             $stmt = $con->prepare("select Parce_id from parcela where Parce_nombre= :parce_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':parce_nombre',$Array2['Parce_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
                $id_parcela=$results[0];
                $stmt = $con->prepare("UPDATE cultivo SET Culti_puntoSiembra=:Culti_puntoSiembra,Culti_duracion=:Culti_duracion,Culti_inversion=:Culti_inversion,Culti_estado=:Culti_estado,Culti_fecha=:Culti_fecha,Culti_foto=:Culti_foto,Culti_area=:Culti_area,Culti_medida=:Culti_medida,Culti_observaciones=:Culti_observaciones,Culti_especie=:Culti_especie,Parce_id=:Parce_id WHERE Culti_nombre=:Culti_nombre AND Parce_id IN(SELECT Parce_id from parcela where Usua_id=:usua_id)");

                $stmt->bindParam(':Culti_nombre',$Array2['Culti_nombre'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_puntoSiembra',$Array2['Culti_puntoSiembra'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_duracion',$Array2['Culti_duracion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_inversion',$Array2['Culti_inversion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_estado',$Array2['Culti_estado'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_fecha',$Array2['Culti_fecha'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_foto',$Array2['Culti_foto'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_area',$Array2['Culti_area'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_medida',$Array2['Culti_medida'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_observaciones',$Array2['Culti_observaciones'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_especie',$Array2['Culti_especie'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_id',$id_parcela, PDO::PARAM_INT);
                $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
                $stmt->execute();
                echo 'Update Ok';
              }
          }
          else{ //Sino existe inserte         
             $stmt = $con->prepare("select Parce_id from parcela where Parce_nombre= :parce_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':parce_nombre',$Array2['Parce_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
                $id_parcela=$results[0];
                $stmt = $con->prepare("INSERT INTO cultivo (Culti_id,Culti_nombre,Culti_puntoSiembra,Culti_duracion,Culti_inversion,Culti_estado,Culti_fecha,Culti_foto,Culti_area,Culti_medida,Culti_observaciones,Culti_especie,Parce_id) VALUES(null,:Culti_nombre,:Culti_puntoSiembra,:Culti_duracion,:Culti_inversion,:Culti_estado,:Culti_fecha,:Culti_foto,:Culti_area,:Culti_medida,:Culti_observaciones,:Culti_especie,:Parce_id)");

                $stmt->bindParam(':Culti_nombre',$Array2['Culti_nombre'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_puntoSiembra',$Array2['Culti_puntoSiembra'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_duracion',$Array2['Culti_duracion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_inversion',$Array2['Culti_inversion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_estado',$Array2['Culti_estado'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_fecha',$Array2['Culti_fecha'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_foto',$Array2['Culti_foto'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_area',$Array2['Culti_area'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_medida',$Array2['Culti_medida'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_observaciones',$Array2['Culti_observaciones'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_especie',$Array2['Culti_especie'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_id',$id_parcela, PDO::PARAM_INT);
                $stmt->execute();
                echo 'Ok';
             }
           }
            $var='"'.$Array2['Culti_nombre'][$i].'"';
            array_push($auxiliar1,$var);
         }
          $statut = implode(',',$auxiliar1);
          $stmt1 = $con->prepare('delete from cultivo where Culti_nombre NOT IN('.$statut.') AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)');
          $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt1->execute();
           }
         } 
         catch(Exception $e) { 
           echo 'Error conectando con la base de datos: CULTIVO ' . $e->getMessage();
           $stmt->rollback(); 
        } 
      }

      if(!empty($Array3)){
        try{
         if($Array3['Rasta_nombre'][0]=="TABLAVACIA"){
           $stmt1 = $con->prepare('delete from resultados_rasta where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
           $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt1->execute();
           }
        else{
         $auxiliar2= array();
         for($i = 0; $i < count($Array3['Rasta_nombre']); $i++) {
          $existencia=Verifica_Existencia("RASTA",$Array3['Rasta_nombre'][$i],$usua_id);
        
          if($existencia){
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array3['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

          if($results){
            $id_cultivo=$results[0];

            $stmt = $con->prepare("UPDATE resultados_rasta SET Rasta_formaTerreno=:Rasta_formaTerreno,Rasta_textura=:Rasta_textura,Rasta_ph=:Rasta_ph,Rasta_nivelCarbonatos=:Rasta_nivelCarbonatos,Rasta_profundidadCarbonatos=:Rasta_profundidadCarbonatos,Rasta_medidaCarbonatos=:Rasta_medidaCarbonatos,Rasta_pedregosidad=:Rasta_pedregosidad,Rasta_profundidadPedregosidad=:Rasta_profundidadPedregosidad,Rasta_medidaPedregosidad=:Rasta_medidaPedregosidad,Rasta_resistenciaRompimientoSeco=:Rasta_resistenciaRompimientoSeco,Rasta_resistenciaRompimientoHumedo=:Rasta_resistenciaRompimientoHumedo,Rasta_Estructura=:Rasta_Estructura,Rasta_pregunta1=:Rasta_pregunta1,Rasta_pregunta2=:Rasta_pregunta2,Rasta_pregunta3=:Rasta_pregunta3,Rasta_pregunta4=:Rasta_pregunta4,Rasta_pregunta5=:Rasta_pregunta5,Rasta_pregunta6=:Rasta_pregunta6,Rasta_pregunta7=:Rasta_pregunta7,Rasta_pregunta8=:Rasta_pregunta8,Rasta_pregunta9=:Rasta_pregunta9,Rasta_pregunta10=:Rasta_pregunta10,Rasta_pregunta11=:Rasta_pregunta11,Rasta_pregunta12=:Rasta_pregunta12,Rasta_pregunta13=:Rasta_pregunta13,Rasta_pregunta14=:Rasta_pregunta14,Rasta_pregunta15=:Rasta_pregunta15,Rasta_pregunta16=:Rasta_pregunta16,Rasta_pregunta17=:Rasta_pregunta17,Rasta_pregunta18=:Rasta_pregunta18,Rasta_FechaRegistro=:Rasta_FechaRegistro,Rasta_FechaActualizacion=:Rasta_FechaActualizacion,Color_id=:Color_id,Culti_id=:Culti_id WHERE Rasta_nombre=:Rasta_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id=:usua_id))");

            $stmt->bindParam(':Rasta_nombre',$Array3['Rasta_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_formaTerreno',$Array3['Rasta_formaTerreno'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_textura',$Array3['Rasta_textura'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_ph',$Array3['Rasta_ph'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_nivelCarbonatos',$Array3['Rasta_nivelCarbonatos'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_profundidadCarbonatos',$Array3['Rasta_profundidadCarbonatos'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_medidaCarbonatos',$Array3['Rasta_medidaCarbonatos'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pedregosidad',$Array3['Rasta_pedregosidad'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_profundidadPedregosidad',$Array3['Rasta_profundidadPedregosidad'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_medidaPedregosidad',$Array3['Rasta_medidaPedregosidad'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_resistenciaRompimientoSeco',$Array3['Rasta_resistenciaRompimientoSeco'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_resistenciaRompimientoHumedo',$Array3['Rasta_resistenciaRompimientoHumedo'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_Estructura',$Array3['Rasta_Estructura'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta1',$Array3['Rasta_pregunta1'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta2',$Array3['Rasta_pregunta2'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta3',$Array3['Rasta_pregunta3'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta4',$Array3['Rasta_pregunta4'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta5',$Array3['Rasta_pregunta5'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta6',$Array3['Rasta_pregunta6'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta7',$Array3['Rasta_pregunta7'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta8',$Array3['Rasta_pregunta8'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta9',$Array3['Rasta_pregunta9'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta10',$Array3['Rasta_pregunta10'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta11',$Array3['Rasta_pregunta11'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta12',$Array3['Rasta_pregunta12'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta13',$Array3['Rasta_pregunta13'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta14',$Array3['Rasta_pregunta14'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta15',$Array3['Rasta_pregunta15'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta16',$Array3['Rasta_pregunta16'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta17',$Array3['Rasta_pregunta17'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta18',$Array3['Rasta_pregunta18'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_FechaRegistro',$Array3['Rasta_FechaRegistro'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_FechaActualizacion',$Array3['Rasta_FechaActualizacion'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Color_id',$Array3['Color_id'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            echo 'Ok';
            }
          }
          else{ //Sino existe inserte  
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array3['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

          if($results){
            $id_cultivo=$results[0];
            echo 'cultivo id '.$id_cultivo;
            $stmt = $con->prepare("INSERT INTO resultados_rasta VALUES(null,:Rasta_nombre,:Rasta_formaTerreno,:Rasta_textura,:Rasta_ph,:Rasta_nivelCarbonatos,:Rasta_profundidadCarbonatos,:Rasta_medidaCarbonatos,:Rasta_pedregosidad,:Rasta_profundidadPedregosidad,:Rasta_medidaPedregosidad,:Rasta_resistenciaRompimientoSeco,:Rasta_resistenciaRompimientoHumedo,:Rasta_Estructura,:Rasta_pregunta1,:Rasta_pregunta2,:Rasta_pregunta3,:Rasta_pregunta4,:Rasta_pregunta5,:Rasta_pregunta6,:Rasta_pregunta7,:Rasta_pregunta8,:Rasta_pregunta9,:Rasta_pregunta10,:Rasta_pregunta11,:Rasta_pregunta12,:Rasta_pregunta13,:Rasta_pregunta14,:Rasta_pregunta15,:Rasta_pregunta16,:Rasta_pregunta17,:Rasta_pregunta18,:Rasta_FechaRegistro,:Rasta_FechaActualizacion,:Color_id,:Culti_id)");

            $stmt->bindParam(':Rasta_nombre',$Array3['Rasta_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_formaTerreno',$Array3['Rasta_formaTerreno'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_textura',$Array3['Rasta_textura'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_ph',$Array3['Rasta_ph'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_nivelCarbonatos',$Array3['Rasta_nivelCarbonatos'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_profundidadCarbonatos',$Array3['Rasta_profundidadCarbonatos'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_medidaCarbonatos',$Array3['Rasta_medidaCarbonatos'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pedregosidad',$Array3['Rasta_pedregosidad'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_profundidadPedregosidad',$Array3['Rasta_profundidadPedregosidad'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_medidaPedregosidad',$Array3['Rasta_medidaPedregosidad'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_resistenciaRompimientoSeco',$Array3['Rasta_resistenciaRompimientoSeco'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_resistenciaRompimientoHumedo',$Array3['Rasta_resistenciaRompimientoHumedo'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_Estructura',$Array3['Rasta_Estructura'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta1',$Array3['Rasta_pregunta1'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta2',$Array3['Rasta_pregunta2'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta3',$Array3['Rasta_pregunta3'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta4',$Array3['Rasta_pregunta4'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta5',$Array3['Rasta_pregunta5'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta6',$Array3['Rasta_pregunta6'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta7',$Array3['Rasta_pregunta7'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta8',$Array3['Rasta_pregunta8'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta9',$Array3['Rasta_pregunta9'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta10',$Array3['Rasta_pregunta10'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta11',$Array3['Rasta_pregunta11'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta12',$Array3['Rasta_pregunta12'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta13',$Array3['Rasta_pregunta13'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta14',$Array3['Rasta_pregunta14'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta15',$Array3['Rasta_pregunta15'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta16',$Array3['Rasta_pregunta16'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta17',$Array3['Rasta_pregunta17'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_pregunta18',$Array3['Rasta_pregunta18'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_FechaRegistro',$Array3['Rasta_FechaRegistro'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Rasta_FechaActualizacion',$Array3['Rasta_FechaActualizacion'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Color_id',$Array3['Color_id'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
            $stmt->execute();
            echo 'Ok';
            }
           }
           $var='"'.$Array3['Rasta_nombre'][$i].'"';
           array_push($auxiliar2,$var);
          } 
           $statut = implode(',',$auxiliar2);
           $stmt1 = $con->prepare('delete from resultados_rasta where Rasta_nombre NOT IN('.$statut.') AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
           $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt1->execute();
           }       
         }
         catch(Exception $e) { 
           echo 'Error conectando con la base de datos: RASTA ' . $e->getMessage();
           $stmt->rollback(); 
        }
       }
       
      if(!empty($Array4)){
        try{ 
        if($Array4['Sens_nombre'][0]=="TABLAVACIA"){
           $stmt1 = $con->prepare('delete from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
           $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt1->execute();
        }
        else{
         $auxiliar= array();
         for($i = 0; $i < count($Array4['Sens_nombre']); $i++) {
          $existencia=Verifica_Existencia("NODOS",$Array4['Sens_nombre'][$i],$usua_id);
        
          if($existencia){
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array4['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
                $id_cultivo=$results[0];
                $stmt = $con->prepare("UPDATE nodosensor SET Sens_estado=:Sens_estado,Sens_Mac=:Sens_Mac,Sens_PanId=:Sens_PanId,Sens_fechaRegistro=:Sens_fechaRegistro,Sens_fechaActualizacion=:Sens_fechaActualizacion,Culti_id=:Culti_id WHERE Sens_nombre=:Sens_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id=:usua_id))");

                $stmt->bindParam(':Sens_nombre',$Array4['Sens_nombre'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_estado',$Array4['Sens_estado'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_Mac',$Array4['Sens_Mac'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_PanId',$Array4['Sens_PanId'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_fechaRegistro',$Array4['Sens_fechaRegistro'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_fechaActualizacion',$Array4['Sens_fechaActualizacion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
                $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
                $stmt->execute();
                echo 'Ok';
              }
          }
          else{ //Sino existe inserte    
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array4['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
                $id_cultivo=$results[0];
                $stmt = $con->prepare("INSERT INTO nodosensor VALUES(null,:Sens_nombre,:Sens_estado,:Sens_Mac,:Sens_PanId,:Sens_fechaRegistro,:Sens_fechaActualizacion,:Culti_id)");

                $stmt->bindParam(':Sens_nombre',$Array4['Sens_nombre'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_estado',$Array4['Sens_estado'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_Mac',$Array4['Sens_Mac'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_PanId',$Array4['Sens_PanId'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_fechaRegistro',$Array4['Sens_fechaRegistro'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_fechaActualizacion',$Array4['Sens_fechaActualizacion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
                $stmt->execute();
                echo 'Ok';
             }
           }
            $var='"'.$Array4['Sens_nombre'][$i].'"';
            array_push($auxiliar,$var);
           }
           $statut = implode(',',$auxiliar);
           $stmt1 = $con->prepare('delete from nodosensor where Sens_nombre NOT IN('.$statut.') AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
           $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt1->execute();
         }
        }
        catch(Exception $e) { 
         echo 'Error conectando con la base de datos: NODO SENSOR ' . $e->getMessage();
         $stmt->rollback(); 
        } 
       }

      if(!empty($Array44)){
         try{
            if($Array44['Sens_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from medicionsensor where Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array44['Sens_id']); $i++) {
             $var='"'.$Array44['MediSens_fecha'][$i].'"';
             $var1='"'.$Array44['MediSens_hora'][$i].'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from medicionsensor where MediSens_fecha NOT IN('.$statut.') AND MediSens_hora NOT IN('.$statut1.') AND Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos MEDICION_SENSOR: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array5)){
       try{
        if($Array5['Cose_nombre'][0]=="TABLAVACIA"){
           $stmt1 = $con->prepare('delete from cosecha where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
           $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt1->execute();
        }
        else{
         $auxiliar= array();
         for($i = 0; $i < count($Array5['Cose_nombre']); $i++) {
          $existencia=Verifica_Existencia("COSECHAS",$Array5['Cose_nombre'][$i],$usua_id);

          if($existencia){
              $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array5['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
                $id_cultivo=$results[0];
                $stmt = $con->prepare("UPDATE cosecha SET Cose_volumenRecoleccion=:Cose_volumenRecoleccion,Cose_cantidad=:Cose_cantidad,Cose_medida=:Cose_medida,Cose_Observacion=:Cose_Observacion,Cose_fecha=:Cose_fecha,Cose_fechaActualizacion=:Cose_fechaActualizacion,Cose_calificativo=:Cose_calificativo,Cose_rendimiento=:Cose_rendimiento,Cose_puntos=:Cose_puntos,Cose_foto=:Cose_foto,Culti_id=:Culti_id WHERE Cose_nombre=:Cose_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");

                $stmt->bindParam(':Cose_nombre',$Array5['Cose_nombre'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_volumenRecoleccion',$Array5['Cose_volumenRecoleccion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_cantidad',$Array5['Cose_cantidad'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_medida',$Array5['Cose_medida'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_Observacion',$Array5['Cose_Observacion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_fecha',$Array5['Cose_fecha'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_fechaActualizacion',$Array5['Cose_fechaActualizacion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_calificativo',$Array5['Cose_calificativo'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_rendimiento',$Array5['Cose_rendimiento'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_puntos',$Array5['Cose_puntos'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_foto',$Array5['Cose_foto'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
                $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
                $stmt->execute();
                echo 'Ok';
             }
          }
          else{ //Sino existe inserte    
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array5['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
                $id_cultivo=$results[0];
                $stmt = $con->prepare("INSERT INTO cosecha VALUES(null,:Cose_nombre,:Cose_volumenRecoleccion,:Cose_cantidad,:Cose_medida,:Cose_Observacion,:Cose_fecha,:Cose_fechaActualizacion,:Cose_calificativo,:Cose_rendimiento,:Cose_puntos,:Cose_foto,:Culti_id)");

                $stmt->bindParam(':Cose_nombre',$Array5['Cose_nombre'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_volumenRecoleccion',$Array5['Cose_volumenRecoleccion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_cantidad',$Array5['Cose_cantidad'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_medida',$Array5['Cose_medida'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_Observacion',$Array5['Cose_Observacion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_fecha',$Array5['Cose_fecha'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_fechaActualizacion',$Array5['Cose_fechaActualizacion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_calificativo',$Array5['Cose_calificativo'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_rendimiento',$Array5['Cose_rendimiento'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_puntos',$Array5['Cose_puntos'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_foto',$Array5['Cose_foto'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
                $stmt->execute();
                echo 'Ok';
             }
           }
            $var='"'.$Array5['Cose_nombre'][$i].'"';
            array_push($auxiliar,$var);
         }
         echo 'eeeeeeeeeeeeeeeeeeeeeeeeeee ';
          $statut = implode(',',$auxiliar);
          $stmt1 = $con->prepare('delete from cosecha where Cose_nombre NOT IN('.$statut.') AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
          $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt1->execute();
          } 
        }
         catch(Exception $e) { 
           echo 'Error conectando con la base de datos COSECHAS: ' . $e->getMessage();
           $stmt->rollback(); 
        } 
       }

      if(!empty($Array6)){
         try{
            if($Array6['Insum_nombre'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from insumo where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            for($i = 0; $i < count($Array6['Insum_nombre']); $i++) {
            $existencia=Verifica_Existencia("INSUMOS",$Array6['Insum_nombre'][$i],$usua_id);
        
            if($existencia){
               $stmt = $con->prepare("UPDATE insumo SET Insum_valor=:Insum_valor,Insum_fecha=:Insum_fecha,Insum_fechaActualizacion=:Insum_fechaActualizacion,Insum_Cantidad=:Insum_Cantidad,Insum_medida=:Insum_medida,Insum_categoria=:Insum_categoria,Insum_funcion=:Insum_funcion,Insum_marca=:Insum_marca WHERE Insum_nombre=:Insum_nombre AND Usua_id=:Usua_id");

               $stmt->bindParam(':Insum_nombre',$Array6['Insum_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_valor',$Array6['Insum_valor'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_fecha',$Array6['Insum_fecha'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_fechaActualizacion',$Array6['Insum_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_Cantidad',$Array6['Insum_Cantidad'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_medida',$Array6['Insum_medida'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_categoria',$Array6['Insum_categoria'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_funcion',$Array6['Insum_funcion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_marca',$Array6['Insum_marca'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
            }
            else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO insumo VALUES(null,:Insum_nombre,:Insum_valor,:Insum_fecha,:Insum_fechaActualizacion,:Insum_Cantidad,:Insum_medida,:Insum_categoria,:Insum_funcion,:Insum_marca,:Usua_id)");

             $stmt->bindParam(':Insum_nombre',$Array6['Insum_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_valor',$Array6['Insum_valor'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_fecha',$Array6['Insum_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_fechaActualizacion',$Array6['Insum_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_Cantidad',$Array6['Insum_Cantidad'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_medida',$Array6['Insum_medida'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_categoria',$Array6['Insum_categoria'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_funcion',$Array6['Insum_funcion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_marca',$Array6['Insum_marca'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
            $var='"'.$Array6['Insum_nombre'][$i].'"';
            array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from insumo where Insum_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos INSUMOS: ' . $e->getMessage();
            $stmt->rollback(); 
          }
      }    

      if(!empty($Array7)){
         try{
            if($Array7['Enfe_nombre'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from enfermedad where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            for($i = 0; $i < count($Array7['Enfe_nombre']); $i++) {
                $existencia=Verifica_Existencia("ENFERMEDADES",$Array7['Enfe_nombre'][$i],$usua_id);
        
          if($existencia){
             $stmt = $con->prepare("UPDATE enfermedad SET Enfe_nombreCientifico=:Enfe_nombreCientifico,Enfe_riesgo=:Enfe_riesgo,Enfe_foto=:Enfe_foto,Enfe_tratamiento=:Enfe_tratamiento,Enfe_fecha=:Enfe_fecha,Enfe_fechaActualizacion=:Enfe_fechaActualizacion,Enfe_obsevaciones=:Enfe_obsevaciones WHERE Enfe_nombre=:Enfe_nombre AND Usua_id=:Usua_id");

             $stmt->bindParam(':Enfe_nombre',$Array7['Enfe_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_nombreCientifico',$Array7['Enfe_nombreCientifico'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_riesgo',$Array7['Enfe_riesgo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_foto',$Array7['Enfe_foto'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_tratamiento',$Array7['Enfe_tratamiento'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_fecha',$Array7['Enfe_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_fechaActualizacion',$Array7['Enfe_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_obsevaciones',$Array7['Enfe_obsevaciones'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO enfermedad VALUES(null,:Enfe_nombre,:Enfe_nombreCientifico,:Enfe_riesgo,:Enfe_foto,:Enfe_tratamiento,:Enfe_fecha,:Enfe_fechaActualizacion,:Enfe_obsevaciones,:Usua_id)");

             $stmt->bindParam(':Enfe_nombre',$Array7['Enfe_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_nombreCientifico',$Array7['Enfe_nombreCientifico'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_riesgo',$Array7['Enfe_riesgo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_foto',$Array7['Enfe_foto'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_tratamiento',$Array7['Enfe_tratamiento'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_fecha',$Array7['Enfe_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_fechaActualizacion',$Array7['Enfe_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_obsevaciones',$Array7['Enfe_obsevaciones'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
             $var='"'.$Array7['Enfe_nombre'][$i].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from enfermedad where Enfe_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos ENFERMEDAD: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array8)){
        try{
          if($Array8['Afec_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from afecta_enfermedad where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Enfe_id in(select Enfe_id from enfermedad where  Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
         else{
             $auxiliar= array();
             $auxiliar1= array();
         for($i = 0; $i < count($Array8['Afec_id']); $i++) {
             $existencia=Verifica_Existencia2("AFECTA_ENFERMEDADES",$Array8['Enfe_nombre'][$i],$Array8['Culti_nombre'][$i],$usua_id);
        
          if($existencia){
              $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array8['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Enfe_id from enfermedad where Enfe_nombre= :enfe_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':enfe_nombre',$Array8['Enfe_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_cultivo=$results[0];
               $id_enfermedad=$results1[0];
               $stmt = $con->prepare("UPDATE afecta_enfermedad SET Afecta_Enfe_Afectacion=:Afecta_Enfe_Afectacion,Afecta_Enfe_Incidencia=:Afecta_Enfe_Incidencia,Afecta_Enfe_fechaAfectacion=:Afecta_Enfe_fechaAfectacion,Afecta_Enfe_fechaActualizacion=:Afecta_Enfe_fechaActualizacion WHERE Culti_id=:Culti_id AND Enfe_id=:Enfe_id");
               $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
               $stmt->bindParam(':Enfe_id',$id_enfermedad, PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Enfe_Afectacion',$Array8['Afecta_Enfe_Afectacion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Enfe_Incidencia',$Array8['Afecta_Enfe_Incidencia'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Enfe_fechaAfectacion',$Array8['Afecta_Enfe_fechaAfectacion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Enfe_fechaActualizacion',$Array8['Afecta_Enfe_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array8['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Enfe_id from enfermedad where Enfe_nombre= :enfe_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':enfe_nombre',$Array8['Enfe_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_cultivo=$results[0];
               $id_enfermedad=$results1[0];
               $stmt = $con->prepare("INSERT INTO afecta_enfermedad VALUES(null,:Culti_id,:Enfe_id,:Afecta_Enfe_Afectacion,:Afecta_Enfe_Incidencia,:Afecta_Enfe_fechaAfectacion,:Afecta_Enfe_fechaActualizacion)");

               $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
               $stmt->bindParam(':Enfe_id',$id_enfermedad, PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Enfe_Afectacion',$Array8['Afecta_Enfe_Afectacion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Enfe_Incidencia',$Array8['Afecta_Enfe_Incidencia'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Enfe_fechaAfectacion',$Array8['Afecta_Enfe_fechaAfectacion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Enfe_fechaActualizacion',$Array8['Afecta_Enfe_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
           }
             $var='"'.$id_cultivo.'"';
             $var1='"'.$id_enfermedad.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from afecta_enfermedad where Culti_id NOT IN('.$statut.') AND Enfe_id NOT IN('.$statut1.')');
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos AFECTA_ENFERMEDAD: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array9)){
         try{
            if($Array9['Trata_id'][0]=="TABLAVACIA"){
             $stmt1 = $con->prepare('delete from tratamiento_enfermedad where Enfe_id in(select Enfe_id from enfermedad where Usua_id= :usua_id) AND Insum_id in(select Insum_id from insumo where Usua_id= :usua_id)');
             $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt1->execute();
           }
          else{
           $auxiliar= array();
           $auxiliar1= array();
           for($i = 0; $i < count($Array9['Trata_id']); $i++) {
               $existencia=Verifica_Existencia2("TRATA_ENFERMEDADES",$Array9['Enfe_nombre'][$i],$Array9['Insum_nombre'][$i],$usua_id);
        
          if($existencia){ //Si existe actualize
            $stmt = $con->prepare("select Enfe_id from enfermedad where Enfe_nombre= :enfe_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':enfe_nombre',$Array9['Enfe_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array9['Insum_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_enfermedad=$results[0];
               $id_insumo=$results1[0];
               $stmt = $con->prepare("UPDATE tratamiento_enfermedad SET Trata_Enfe_nombre=:Trata_Enfe_nombre,Trata_Enfe_duracion=:Trata_Enfe_duracion,Trata_Enfe_duraMedida=:Trata_Enfe_duraMedida,Trata_Enfe_CantidadDosis=:Trata_Enfe_CantidadDosis,Trata_Enfe_MedidaDosis=:Trata_Enfe_MedidaDosis,Trata_Enfe_Periodo=:Trata_Enfe_Periodo,Trata_Enfe_PeMedida=:Trata_Enfe_PeMedida,Trata_Enfe_Reacciones=:Trata_Enfe_Reacciones,Trata_Enfe_Comentarios=:Trata_Enfe_Comentarios,Trata_Enfe_FechaRegistro=:Trata_Enfe_FechaRegistro,Trata_Enfe_FechaActualizacion=:Trata_Enfe_FechaActualizacion WHERE Enfe_id=:Enfe_id AND Insum_id=:Insum_id");

               $stmt->bindParam(':Enfe_id',$id_enfermedad, PDO::PARAM_INT);
               $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_nombre',$Array9['Trata_Enfe_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_duracion',$Array9['Trata_Enfe_duracion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_duraMedida',$Array9['Trata_Enfe_duraMedida'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_CantidadDosis',$Array9['Trata_Enfe_CantidadDosis'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_MedidaDosis',$Array9['Trata_Enfe_MedidaDosis'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_Periodo',$Array9['Trata_Enfe_Periodo'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_PeMedida',$Array9['Trata_Enfe_PeMedida'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_Reacciones',$Array9['Trata_Enfe_Reacciones'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_Comentarios',$Array9['Trata_Enfe_Comentarios'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_FechaRegistro',$Array9['Trata_Enfe_FechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_FechaActualizacion',$Array9['Trata_Enfe_FechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Enfe_id from enfermedad where Enfe_nombre= :enfe_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':enfe_nombre',$Array9['Enfe_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array9['Insum_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_enfermedad=$results[0];
               $id_insumo=$results1[0];
               $stmt = $con->prepare("INSERT INTO tratamiento_enfermedad VALUES(null,:Enfe_id,:Insum_id,:Trata_Enfe_nombre,:Trata_Enfe_duracion,:Trata_Enfe_duraMedida,:Trata_Enfe_CantidadDosis,:Trata_Enfe_MedidaDosis,:Trata_Enfe_Periodo,:Trata_Enfe_PeMedida,:Trata_Enfe_Reacciones,:Trata_Enfe_Comentarios,:Trata_Enfe_FechaRegistro,:Trata_Enfe_FechaActualizacion)");

               $stmt->bindParam(':Enfe_id',$id_enfermedad, PDO::PARAM_INT);
               $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_nombre',$Array9['Trata_Enfe_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_duracion',$Array9['Trata_Enfe_duracion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_duraMedida',$Array9['Trata_Enfe_duraMedida'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_CantidadDosis',$Array9['Trata_Enfe_CantidadDosis'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_MedidaDosis',$Array9['Trata_Enfe_MedidaDosis'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_Periodo',$Array9['Trata_Enfe_Periodo'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_PeMedida',$Array9['Trata_Enfe_PeMedida'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_Reacciones',$Array9['Trata_Enfe_Reacciones'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_Comentarios',$Array9['Trata_Enfe_Comentarios'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_FechaRegistro',$Array9['Trata_Enfe_FechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_FechaActualizacion',$Array9['Trata_Enfe_FechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
           }
             $var='"'.$id_insumo.'"';
             $var1='"'.$id_enfermedad.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from tratamiento_enfermedad where Insum_id NOT IN('.$statut.') AND Enfe_id NOT IN('.$statut1.')');
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos TRATA_ENFERMEDAD: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array10)){
         try{
            if($Array10['Result_id'][0]=="TABLAVACIA"){
             $stmt1 = $con->prepare('delete from resultados_trata_enfermedad where Trata_id in(select Trata_id from tratamiento_enfermedad where Enfe_id in(select Enfe_id from enfermedad where Usua_id= :usua_id))');
             $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt1->execute();
           }
          else{
           $auxiliar= array();
           $auxiliar1= array();
         for($i = 0; $i < count($Array10['Result_id']); $i++) {
         $existencia=Verifica_Existencia2("RESULTADOS_ENFERMEDADES",$Array10['Trata_Enfe_nombre'][$i],$Array10['Result_nombre'][$i],$usua_id);

          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("select Trata_id from tratamiento_enfermedad where Trata_Enfe_nombre= :trata_nombre AND Enfe_id in(select Enfe_id from enfermedad where Usua_id= :usua_id)");
            $stmt->bindParam(':trata_nombre',$Array10['Trata_Enfe_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            if($results){
               $id_tratamiento=$results[0];
               $stmt = $con->prepare("UPDATE resultados_trata_enfermedad SET Result_Nivel=:Result_Nivel,Result_Calificativo=:Result_Calificativo,Result_Pregunta1=:Result_Pregunta1,Result_Pregunta2=:Result_Pregunta2,Result_Pregunta3=:Result_Pregunta3,Result_observaciones=:Result_observaciones,Result_fechaRegistro=:Result_fechaRegistro,Result_fechaActualizacion=:Result_fechaActualizacion WHERE Result_nombre=:Result_nombre AND Trata_id=:Trata_id");

               $stmt->bindParam(':Trata_id',$id_tratamiento, PDO::PARAM_INT);
               $stmt->bindParam(':Result_nombre',$Array10['Result_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Nivel',$Array10['Result_Nivel'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Calificativo',$Array10['Result_Calificativo'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta1',$Array10['Result_Pregunta1'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta2',$Array10['Result_Pregunta2'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta3',$Array10['Result_Pregunta3'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_observaciones',$Array10['Result_observaciones'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaRegistro',$Array10['Result_fechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaActualizacion',$Array10['Result_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select Trata_id from tratamiento_enfermedad where Trata_Enfe_nombre= :trata_nombre AND Enfe_id in(select Enfe_id from enfermedad where Usua_id= :usua_id)");
             $stmt->bindParam(':trata_nombre',$Array10['Trata_Enfe_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
               $id_tratamiento=$results[0];
               $stmt = $con->prepare("INSERT INTO resultados_trata_enfermedad VALUES(null,:Trata_id,:Result_nombre,:Result_Nivel,:Result_Calificativo,:Result_Pregunta1,:Result_Pregunta2,:Result_Pregunta3,:Result_observaciones,:Result_fechaRegistro,:Result_fechaActualizacion)");

               $stmt->bindParam(':Trata_id',$id_tratamiento, PDO::PARAM_INT);
               $stmt->bindParam(':Result_nombre',$Array10['Result_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Nivel',$Array10['Result_Nivel'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Calificativo',$Array10['Result_Calificativo'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta1',$Array10['Result_Pregunta1'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta2',$Array10['Result_Pregunta2'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta3',$Array10['Result_Pregunta3'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_observaciones',$Array10['Result_observaciones'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaRegistro',$Array10['Result_fechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaActualizacion',$Array10['Result_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
           }
            if($id_tratamiento!='' && $id_tratamiento!==NULL){
             $var='"'.$Array10['Result_nombre'][$i].'"';
             $var1='"'.$id_tratamiento.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
             }
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from resultados_trata_enfermedad where Result_nombre NOT IN('.$statut.') AND Trata_id NOT IN('.$statut1.')');
            $stmt1->execute();
          }
         }
          catch(Exception $e) { 
            echo 'Error conectando con la base de datos RESULTADOS_ENFERMEDADES: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array11)){
        try{
            if($Array11['Male_nombre'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from maleza where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array11['Male_nombre']); $i++) {
          $existencia=Verifica_Existencia("MALEZA",$Array11['Male_nombre'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
             $stmt = $con->prepare("UPDATE maleza SET Male_nombreCientifico=:Male_nombreCientifico,Male_riesgo=:Male_riesgo,Male_foto=:Male_foto,Male_tratamiento=:Male_tratamiento,Male_fecha=:Male_fecha,Male_fechaActualizacion=:Male_fechaActualizacion,Male_obsevaciones=:Male_obsevaciones WHERE Male_nombre=:Male_nombre AND Usua_id=:Usua_id");

             $stmt->bindParam(':Male_nombre',$Array11['Male_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_nombreCientifico',$Array11['Male_nombreCientifico'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_riesgo',$Array11['Male_riesgo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_foto',$Array11['Male_foto'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_tratamiento',$Array11['Male_tratamiento'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_fecha',$Array11['Male_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_fechaActualizacion',$Array11['Male_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_obsevaciones',$Array11['Male_obsevaciones'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO maleza VALUES(null,:Male_nombre,:Male_nombreCientifico,:Male_riesgo,:Male_foto,:Male_tratamiento,:Male_fecha,:Male_fechaActualizacion,:Male_obsevaciones,:Usua_id)");

             $stmt->bindParam(':Male_nombre',$Array11['Male_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_nombreCientifico',$Array11['Male_nombreCientifico'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_riesgo',$Array11['Male_riesgo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_foto',$Array11['Male_foto'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_tratamiento',$Array11['Male_tratamiento'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_fecha',$Array11['Male_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_fechaActualizacion',$Array11['Male_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Male_obsevaciones',$Array11['Male_obsevaciones'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
            $var='"'.$Array11['Male_nombre'][$i].'"';
            array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from maleza where Male_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos MALEZA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }   

      if(!empty($Array12)){
       try{
          if($Array12['Afec_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from afecta_maleza where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Male_id in(select Male_id from maleza where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
         else{
             $auxiliar= array();
             $auxiliar1= array();

         for($i = 0; $i < count($Array12['Afec_id']); $i++) {
         $existencia=Verifica_Existencia2("AFECTA_MALEZA",$Array12['Male_nombre'][$i],$Array12['Culti_nombre'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
               $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array12['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Male_id from maleza where Male_nombre= :male_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':male_nombre',$Array12['Male_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_cultivo=$results[0];
               $id_maleza=$results1[0];
               $stmt = $con->prepare("UPDATE afecta_maleza SET Afecta_Male_Afectacion=:Afecta_Male_Afectacion,Afecta_Male_Incidencia=:Afecta_Male_Incidencia,Afecta_Male_fechaAfectacion=:Afecta_Male_fechaAfectacion,Afecta_Male_fechaActualizacion=:Afecta_Male_fechaActualizacion WHERE Culti_id=:Culti_id AND Male_id=:Male_id");

              $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
              $stmt->bindParam(':Male_id',$id_maleza, PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_Afectacion',$Array12['Afecta_Male_Afectacion'][$i], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_Incidencia',$Array12['Afecta_Male_Incidencia'][$i], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_fechaAfectacion',$Array12['Afecta_Male_fechaAfectacion'][$i], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_fechaActualizacion',$Array12['Afecta_Male_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array12['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Male_id from maleza where Male_nombre= :male_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':male_nombre',$Array12['Male_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_cultivo=$results[0];
               $id_maleza=$results1[0];
               $stmt = $con->prepare("INSERT INTO afecta_maleza VALUES(null,:Culti_id,:Male_id,:Afecta_Male_Afectacion,:Afecta_Male_Incidencia,:Afecta_Male_fechaAfectacion,:Afecta_Male_fechaActualizacion)");

              $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
              $stmt->bindParam(':Male_id',$id_maleza, PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_Afectacion',$Array12['Afecta_Male_Afectacion'][$i], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_Incidencia',$Array12['Afecta_Male_Incidencia'][$i], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_fechaAfectacion',$Array12['Afecta_Male_fechaAfectacion'][$i], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_fechaActualizacion',$Array12['Afecta_Male_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
           }
            $var='"'.$id_cultivo.'"';
             $var1='"'.$id_maleza.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from afecta_maleza where Culti_id NOT IN('.$statut.') AND Male_id NOT IN('.$statut1.')');
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos AFECTA_MALEZA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array13)){
         try{
            if($Array13['Trata_id'][0]=="TABLAVACIA"){
             $stmt1 = $con->prepare('delete from tratamiento_maleza where Male_id in(select Male_id from maleza where Usua_id= :usua_id) AND Insum_id in(select Insum_id from insumo where Usua_id= :usua_id)');
             $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt1->execute();
           }
          else{
           $auxiliar= array();
           $auxiliar1= array();
         for($i = 0; $i < count($Array13['Trata_id']); $i++) {
         $existencia=Verifica_Existencia2("TRATA_MALEZA",$Array13['Male_nombre'][$i],$Array13['Insum_nombre'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
             $stmt = $con->prepare("select Male_id from maleza where Male_nombre= :male_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':male_nombre',$Array13['Male_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array13['Insum_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_maleza=$results[0];
               $id_insumo=$results1[0];
               $stmt = $con->prepare("UPDATE tratamiento_maleza SET Trata_Male_nombre=:Trata_Male_nombre,Trata_Male_duracion=:Trata_Male_duracion,Trata_Male_duraMedida=:Trata_Male_duraMedida,Trata_Male_CantidadDosis=:Trata_Male_CantidadDosis,Trata_Male_MedidaDosis=:Trata_Male_MedidaDosis,Trata_Male_Periodo=:Trata_Male_Periodo,Trata_Male_PeMedida=:Trata_Male_PeMedida,Trata_Male_Reacciones=:Trata_Male_Reacciones,Trata_Male_Comentarios=:Trata_Male_Comentarios,Trata_Male_FechaRegistro=:Trata_Male_FechaRegistro,Trata_Male_FechaActualizacion=:Trata_Male_FechaActualizacion WHERE Male_id=:Male_id AND Insum_id=:Insum_id");

               $stmt->bindParam(':Male_id',$id_maleza, PDO::PARAM_INT);
               $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_nombre',$Array13['Trata_Male_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_duracion',$Array13['Trata_Male_duracion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_duraMedida',$Array13['Trata_Male_duraMedida'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_CantidadDosis',$Array13['Trata_Male_CantidadDosis'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_MedidaDosis',$Array13['Trata_Male_MedidaDosis'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_Periodo',$Array13['Trata_Male_Periodo'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_PeMedida',$Array13['Trata_Male_PeMedida'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_Reacciones',$Array13['Trata_Male_Reacciones'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_Comentarios',$Array13['Trata_Male_Comentarios'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_FechaRegistro',$Array13['Trata_Male_FechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_FechaActualizacion',$Array13['Trata_Male_FechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select Male_id from maleza where Male_nombre= :male_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':male_nombre',$Array13['Male_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array13['Insum_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_maleza=$results[0];
               $id_insumo=$results1[0];
               $stmt = $con->prepare("INSERT INTO tratamiento_maleza VALUES(null,:Male_id,:Insum_id,:Trata_Male_nombre,:Trata_Male_duracion,:Trata_Male_duraMedida,:Trata_Male_CantidadDosis,:Trata_Male_MedidaDosis,:Trata_Male_Periodo,:Trata_Male_PeMedida,:Trata_Male_Reacciones,:Trata_Male_Comentarios,:Trata_Male_FechaRegistro,:Trata_Male_FechaActualizacion)");

               $stmt->bindParam(':Male_id',$id_maleza, PDO::PARAM_INT);
               $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_nombre',$Array13['Trata_Male_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_duracion',$Array13['Trata_Male_duracion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_duraMedida',$Array13['Trata_Male_duraMedida'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_CantidadDosis',$Array13['Trata_Male_CantidadDosis'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_MedidaDosis',$Array13['Trata_Male_MedidaDosis'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_Periodo',$Array13['Trata_Male_Periodo'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_PeMedida',$Array13['Trata_Male_PeMedida'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_Reacciones',$Array13['Trata_Male_Reacciones'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_Comentarios',$Array13['Trata_Male_Comentarios'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_FechaRegistro',$Array13['Trata_Male_FechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_FechaActualizacion',$Array13['Trata_Male_FechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
           }
             $var='"'.$id_insumo.'"';
             $var1='"'.$id_maleza.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from tratamiento_maleza where Insum_id NOT IN('.$statut.') AND Male_id NOT IN('.$statut1.')');
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos TRATA_MALEZA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }

      if(!empty($Array14)){
        try{
           if($Array14['Result_id'][0]=="TABLAVACIA"){
             $stmt1 = $con->prepare('delete from resultados_trata_maleza where Trata_id in(select Trata_id from tratamiento_maleza where Male_id in(select Male_id from maleza where Usua_id= :usua_id))');
             $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt1->execute();
            }
          else{
           $auxiliar= array();
           $auxiliar1= array();
         for($i = 0; $i < count($Array14['Result_id']); $i++) {
         $existencia=Verifica_Existencia("RESULTADOS_MALEZA",$Array14['Trata_Male_nombre'][$i],$usua_id);
        
          if($existencia){
             echo 'existe';
             $stmt = $con->prepare("select Trata_id from tratamiento_maleza where Trata_Male_nombre= :trata_nombre AND Male_id in(select Male_id from maleza where Usua_id= :usua_id)");
             $stmt->bindParam(':trata_nombre',$Array14['Trata_Male_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
               $id_tratamiento=$results[0];
               $stmt = $con->prepare("UPDATE resultados_trata_maleza SET Result_Nivel=:Result_Nivel,Result_Calificativo=:Result_Calificativo,Result_Pregunta1=:Result_Pregunta1,Result_Pregunta2=:Result_Pregunta2,Result_Pregunta3=:Result_Pregunta3,Result_observaciones=:Result_observaciones,Result_fechaRegistro=:Result_fechaRegistro,Result_fechaActualizacion=:Result_fechaActualizacion WHERE Trata_id=:Trata_id AND Result_nombre=:Result_nombre");

               $stmt->bindParam(':Trata_id',$id_tratamiento, PDO::PARAM_INT);
               $stmt->bindParam(':Result_nombre',$Array14['Result_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Nivel',$Array14['Result_Nivel'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Calificativo',$Array14['Result_Calificativo'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta1',$Array14['Result_Pregunta1'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta2',$Array14['Result_Pregunta2'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta3',$Array14['Result_Pregunta3'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_observaciones',$Array14['Result_observaciones'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaRegistro',$Array14['Result_fechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaActualizacion',$Array14['Result_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select Trata_id from tratamiento_maleza where Trata_Male_nombre= :trata_nombre AND Male_id in(select Male_id from maleza where Usua_id= :usua_id)");
             $stmt->bindParam(':trata_nombre',$Array14['Trata_Male_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
               $id_tratamiento=$results[0];
               $stmt = $con->prepare("INSERT INTO resultados_trata_maleza VALUES(null,:Trata_id,:Result_nombre,:Result_Nivel,:Result_Calificativo,:Result_Pregunta1,:Result_Pregunta2,:Result_Pregunta3,:Result_observaciones,:Result_fechaRegistro,:Result_fechaActualizacion)");

               $stmt->bindParam(':Trata_id',$id_tratamiento, PDO::PARAM_INT);
               $stmt->bindParam(':Result_nombre',$Array14['Result_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Nivel',$Array14['Result_Nivel'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Calificativo',$Array14['Result_Calificativo'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta1',$Array14['Result_Pregunta1'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta2',$Array14['Result_Pregunta2'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta3',$Array14['Result_Pregunta3'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_observaciones',$Array14['Result_observaciones'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaRegistro',$Array14['Result_fechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaActualizacion',$Array14['Result_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
           }
            $var='"'.$Array14['Result_nombre'][$i].'"';
             $var1='"'.$id_tratamiento.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from resultados_trata_maleza where Result_nombre NOT IN('.$statut.') AND Trata_id NOT IN('.$statut1.')');
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos RESULTADOS_MALEZA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array15)){
        try{
            if($Array15['Inse_nombre'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from Insecto where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array15['Inse_id']); $i++) {
          $existencia=Verifica_Existencia("PLAGA",$Array15['Inse_nombre'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("UPDATE Insecto SET Inse_nombre=:Inse_nombre,Inse_nombreCientifico=:Inse_nombreCientifico,Inse_riesgo=:Inse_riesgo,Inse_foto=:Inse_foto,Inse_tratamiento=:Inse_tratamiento,Inse_fecha=:Inse_fecha,Inse_fechaActualizacion=:Inse_fechaActualizacion,Inse_observaciones=:Inse_observaciones WHERE Inse_nombre=:Inse_nombre AND Usua_id=:Usua_id");

             $stmt->bindParam(':Inse_nombre',$Array15['Inse_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_nombreCientifico',$Array15['Inse_nombreCientifico'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_riesgo',$Array15['Inse_riesgo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_foto',$Array15['Inse_foto'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_tratamiento',$Array15['Inse_tratamiento'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_fecha',$Array15['Inse_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_fechaActualizacion',$Array15['Inse_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_observaciones',$Array15['Inse_observaciones'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO Insecto VALUES(null,:Inse_nombre,:Inse_nombreCientifico,:Inse_riesgo,:Inse_foto,:Inse_tratamiento,:Inse_fecha,:Inse_fechaActualizacion,:Inse_observaciones,:Usua_id)");

             $stmt->bindParam(':Inse_nombre',$Array15['Inse_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_nombreCientifico',$Array15['Inse_nombreCientifico'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_riesgo',$Array15['Inse_riesgo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_foto',$Array15['Inse_foto'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_tratamiento',$Array15['Inse_tratamiento'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_fecha',$Array15['Inse_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_fechaActualizacion',$Array15['Inse_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_observaciones',$Array15['Inse_observaciones'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
           $var='"'.$Array15['Inse_nombre'][$i].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from Insecto where Inse_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos INSECTO: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  
  
      if(!empty($Array16)){
        try{
          if($Array16['Afec_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from afecta_insecto where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Inse_id in(select Inse_id from Insecto where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
         else{
             $auxiliar= array();
             $auxiliar1= array();
         for($i = 0; $i < count($Array16['Afec_id']); $i++) {
         $existencia=Verifica_Existencia2("AFECTA_PLAGA",$Array16['Inse_nombre'][$i],$Array16['Culti_nombre'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array16['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Inse_id from Insecto where Inse_nombre= :inse_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':inse_nombre',$Array16['Inse_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_cultivo=$results[0];
               $id_insecto=$results1[0];
               $stmt = $con->prepare("UPDATE afecta_insecto SET Afecta_Insec_Afectacion=:Afecta_Insec_Afectacion,Afecta_Insec_Incidencia=:Afecta_Insec_Incidencia,Afecta_Insec_fechaAfectacion=:Afecta_Insec_fechaAfectacion,Afecta_Insec_fechaActualizacion=:Afecta_Insec_fechaActualizacion WHERE Culti_id=:Culti_id AND Inse_id=:Inse_id");

               $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
               $stmt->bindParam(':Inse_id',$id_insecto, PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_Afectacion',$Array16['Afecta_Insec_Afectacion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_Incidencia',$Array16['Afecta_Insec_Incidencia'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_fechaAfectacion',$Array16['Afecta_Insec_fechaAfectacion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_fechaActualizacion',$Array16['Afecta_Insec_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array16['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Inse_id from Insecto where Inse_nombre= :inse_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':inse_nombre',$Array16['Inse_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_cultivo=$results[0];
               $id_insecto=$results1[0];
               $stmt = $con->prepare("INSERT INTO afecta_insecto VALUES(null,:Culti_id,:Inse_id,:Afecta_Insec_Afectacion,:Afecta_Insec_Incidencia,:Afecta_Insec_fechaAfectacion,:Afecta_Insec_fechaActualizacion)");

               $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
               $stmt->bindParam(':Inse_id',$id_insecto, PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_Afectacion',$Array16['Afecta_Insec_Afectacion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_Incidencia',$Array16['Afecta_Insec_Incidencia'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_fechaAfectacion',$Array16['Afecta_Insec_fechaAfectacion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_fechaActualizacion',$Array16['Afecta_Insec_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
           }
            $var='"'.$id_cultivo.'"';
            $var1='"'.$id_insecto.'"';
            array_push($auxiliar,$var);
            array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from afecta_insecto where Culti_id NOT IN('.$statut.') AND Inse_id NOT IN('.$statut1.')');
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos AFECTA_INSECTO: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array17)){
        try{
            if($Array17['Trata_id'][0]=="TABLAVACIA"){
             $stmt1 = $con->prepare('delete from tratamiento_insecto where Inse_id in(select Inse_id from Insecto where Usua_id= :usua_id) AND Insum_id in(select Insum_id from insumo where Usua_id= :usua_id)');
             $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt1->execute();
           }
          else{
           $auxiliar= array();
           $auxiliar1= array();
         for($i = 0; $i < count($Array17['Trata_id']); $i++) {
         $existencia=Verifica_Existencia2("TRATA_PLAGA",$Array17['Inse_nombre'][$i],$Array17['Insum_nombre'][$i],$usua_id);
        
          if($existencia){
             //echo 'Existencia '.$GLOBALS['PARCELA_ID'];
            echo 'existe';
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Inse_id from Insecto where Inse_nombre= :inse_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':inse_nombre',$Array17['Inse_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array17['Insum_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_insecto=$results[0];
               $id_insumo=$results1[0];
               $stmt = $con->prepare("INSERT INTO tratamiento_insecto VALUES(null,:Inse_id,:Insum_id,:Trata_Inse_nombre,:Trata_Inse_duracion,:Trata_Inse_duraMedida,:Trata_Inse_CantidadDosis,:Trata_Inse_MedidaDosis,:Trata_Inse_Periodo,:Trata_Inse_PeMedida,:Trata_Inse_Reacciones,:Trata_Inse_Comentarios,:Trata_Inse_FechaRegistro,:Trata_Inse_FechaActualizacion)");

               $stmt->bindParam(':Inse_id',$id_insecto, PDO::PARAM_INT);
               $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_nombre',$Array17['Trata_Inse_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_duracion',$Array17['Trata_Inse_duracion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_duraMedida',$Array17['Trata_Inse_duraMedida'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_CantidadDosis',$Array17['Trata_Inse_CantidadDosis'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_MedidaDosis',$Array17['Trata_Inse_MedidaDosis'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_Periodo',$Array17['Trata_Inse_Periodo'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_PeMedida',$Array17['Trata_Inse_PeMedida'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_Reacciones',$Array17['Trata_Inse_Reacciones'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_Comentarios',$Array17['Trata_Inse_Comentarios'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_FechaRegistro',$Array17['Trata_Inse_FechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_FechaActualizacion',$Array17['Trata_Inse_FechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
           }
             $var='"'.$id_insumo.'"';
             $var1='"'.$id_insecto.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from tratamiento_insecto where Insum_id NOT IN('.$statut.') AND Inse_id NOT IN('.$statut1.')');
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos TRATA_PLAGA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array18)){
        try{
            if($Array18['Trata_id'][0]=="TABLAVACIA"){
             $stmt1 = $con->prepare('delete from resultados_trata_insecto where Trata_id in(select Trata_id from tratamiento_insecto where Enfe_id in(select Enfe_id from enfermedad where Usua_id= :usua_id))');
             $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt1->execute();
           }
          else{
           $auxiliar= array();
           $auxiliar1= array();
         for($i = 0; $i < count($Array18['Result_id']); $i++) {
         $existencia=Verifica_Existencia("RESULTADOS_PLAGA",$Array18['Trata_Inse_nombre'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("select Trata_id from tratamiento_insecto where Trata_Inse_nombre= :trata_nombre AND Inse_id in(select Inse_id from Insecto where Usua_id= :usua_id)");
            $stmt->bindParam(':trata_nombre',$Array18['Trata_Inse_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            if($results){
               $id_tratamiento=$results[0];
               $stmt = $con->prepare("UPDATE resultados_trata_insecto SET Result_Nivel=:Result_Nivel,Result_Calificativo=:Result_Calificativo,Result_Pregunta1=:Result_Pregunta1,Result_Pregunta2=:Result_Pregunta2,Result_Pregunta3=:Result_Pregunta3,Result_observaciones=:Result_observaciones,Result_fechaRegistro=:Result_fechaRegistro,Result_fechaActualizacion=:Result_fechaActualizacion WHERE Trata_id=:Trata_id AND Result_nombre=:Result_nombre");

               $stmt->bindParam(':Trata_id',$id_tratamiento, PDO::PARAM_INT);
               $stmt->bindParam(':Result_nombre',$Array18['Result_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Nivel',$Array18['Result_Nivel'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Calificativo',$Array18['Result_Calificativo'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta1',$Array18['Result_Pregunta1'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta2',$Array18['Result_Pregunta2'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta3',$Array18['Result_Pregunta3'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_observaciones',$Array18['Result_observaciones'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaRegistro',$Array18['Result_fechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaActualizacion',$Array18['Result_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select Trata_id from tratamiento_insecto where Trata_Inse_nombre= :trata_nombre AND Inse_id in(select Inse_id from Insecto where Usua_id= :usua_id)");
             $stmt->bindParam(':trata_nombre',$Array18['Trata_Inse_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
               $id_tratamiento=$results[0];
               $stmt = $con->prepare("INSERT INTO resultados_trata_insecto VALUES(null,:Trata_id,:Result_nombre,:Result_Nivel,:Result_Calificativo,:Result_Pregunta1,:Result_Pregunta2,:Result_Pregunta3,:Result_observaciones,:Result_fechaRegistro,:Result_fechaActualizacion)");

               $stmt->bindParam(':Trata_id',$id_tratamiento, PDO::PARAM_INT);
               $stmt->bindParam(':Result_nombre',$Array18['Result_nombre'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Nivel',$Array18['Result_Nivel'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Calificativo',$Array18['Result_Calificativo'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta1',$Array18['Result_Pregunta1'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta2',$Array18['Result_Pregunta2'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta3',$Array18['Result_Pregunta3'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_observaciones',$Array18['Result_observaciones'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaRegistro',$Array18['Result_fechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaActualizacion',$Array18['Result_fechaActualizacion'][$i], PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
             }
           }
             $var='"'.$Array18['Result_nombre'][$i].'"';
             $var1='"'.$id_tratamiento.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from resultados_trata_insecto where Result_nombre NOT IN('.$statut.') AND Trata_id NOT IN('.$statut1.')');
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos RESULTADOS_PLAGA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array19)){
        try{
            if($Array19['Clie_nombre'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from cliente where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array19['Clie_nombre']); $i++) {
          $existencia=Verifica_Existencia("CLIENTE",$Array19['Clie_identificacion'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("UPDATE cliente SET Clie_nombre=:Clie_nombre,Clie_apellido=:Clie_apellido,Clie_Calificativo=:Clie_Calificativo,Clie_sexo=:Clie_sexo,Clie_razonSocial=:Clie_razonSocial,Clie_fechaRegistro=:Clie_fechaRegistro,Clie_fechaActualizacion=:Clie_fechaActualizacion,Clie_estado=:Clie_estado,Clie_telefono=:Clie_telefono,Clie_celular=:Clie_celular,Clie_pais=:Clie_pais,Clie_ciudad=:Clie_ciudad,Clie_correo=:Clie_correo,Clie_direccion=:Clie_direccion,Clie_observaciones=:Clie_observaciones WHERE Clie_identificacion=:Clie_identificacion AND Usua_id=:Usua_id");

            $stmt->bindParam(':Clie_identificacion',$Array19['Clie_identificacion'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_nombre',$Array19['Clie_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_apellido',$Array19['Clie_apellido'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_Calificativo',$Array19['Clie_Calificativo'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_sexo',$Array19['Clie_sexo'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_razonSocial',$Array19['Clie_razonSocial'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_fechaRegistro',$Array19['Clie_fechaRegistro'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_fechaActualizacion',$Array19['Clie_fechaActualizacion'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_estado',$Array19['Clie_estado'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_telefono',$Array19['Clie_telefono'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_celular',$Array19['Clie_celular'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_pais',$Array19['Clie_pais'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_ciudad',$Array19['Clie_ciudad'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_correo',$Array19['Clie_correo'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_direccion',$Array19['Clie_direccion'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_observaciones',$Array19['Clie_observaciones'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            echo 'Ok';
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO cliente VALUES(null,:Clie_identificacion,:Clie_nombre,:Clie_apellido,:Clie_Calificativo,:Clie_sexo,:Clie_razonSocial,:Clie_fechaRegistro,:Clie_fechaActualizacion,:Clie_estado,:Clie_telefono,:Clie_celular,:Clie_pais,:Clie_ciudad,:Clie_correo,:Clie_direccion,:Clie_observaciones,:Usua_id)");

             $stmt->bindParam(':Clie_identificacion',$Array19['Clie_identificacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_nombre',$Array19['Clie_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_apellido',$Array19['Clie_apellido'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_Calificativo',$Array19['Clie_Calificativo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_sexo',$Array19['Clie_sexo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_razonSocial',$Array19['Clie_razonSocial'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_fechaRegistro',$Array19['Clie_fechaRegistro'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_fechaActualizacion',$Array19['Clie_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_estado',$Array19['Clie_estado'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_telefono',$Array19['Clie_telefono'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_celular',$Array19['Clie_celular'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_pais',$Array19['Clie_pais'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_ciudad',$Array19['Clie_ciudad'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_correo',$Array19['Clie_correo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_direccion',$Array19['Clie_direccion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_observaciones',$Array19['Clie_observaciones'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
             $var='"'.$Array19['Clie_identificacion'][$i].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from cliente where Clie_identificacion NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos CLIENTE: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      ///////////////////////////////////////////////////
      if(!empty($Array20)){
        try{
            if($Array20['Fact_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from facturaventa where Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array20['Fact_id']); $i++) {
          $existencia=Verifica_Existencia2("FACTURA_VENTA",$Array20['Clie_identificacion'][$i],$Array20['Fact_nombre'][$i],$usua_id);
        
          if($existencia){
             echo 'existe';
             $stmt = $con->prepare("select Clie_id from cliente where Clie_identificacion= :clie_identificacion AND Usua_id= :usua_id");
             $stmt->bindParam(':clie_identificacion',$Array20['Clie_identificacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
              $id_cliente=$results[0];
              $stmt = $con->prepare("UPDATE facturaventa SET Fact_nroArticulos=:Fact_nroArticulos,Fact_total=:Fact_total,Fact_Fecha=:Fact_Fecha,Clie_id=:Clie_id WHERE Fact_nombre=:Fact_nombre AND Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)");

             $stmt->bindParam(':Fact_nombre',$Array20['Fact_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_nroArticulos',$Array20['Fact_nroArticulos'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_total',$Array20['Fact_total'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_Fecha',$Array20['Fact_Fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_id',$id_cliente, PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select Clie_id from cliente where Clie_identificacion= :clie_identificacion AND Usua_id= :usua_id");
             $stmt->bindParam(':clie_identificacion',$Array20['Clie_identificacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
              $id_cliente=$results[0];
              $stmt = $con->prepare("INSERT INTO facturaventa VALUES(null,:Fact_nombre,:Fact_nroArticulos,:Fact_total,:Fact_Fecha,:Clie_id)");

             $stmt->bindParam(':Fact_nombre',$Array20['Fact_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_nroArticulos',$Array20['Fact_nroArticulos'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_total',$Array20['Fact_total'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_Fecha',$Array20['Fact_Fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_id',$id_cliente, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
           }
            $var='"'.$Array20['Fact_nombre'][$i].'"';
            array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from facturaventa where Fact_nombre NOT IN('.$statut.') AND Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos FACTURA_VENTA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array21)){
          try{
            if($Array21['VentaCose_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from ventacosecha where Fact_id in(select Fact_id from facturaventa where Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)) AND Cose_id in(select Cose_id from cosecha where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array21['VentaCose_id']); $i++) {
          $existencia=Verifica_Existencia2("VENTA_COSECHA",$Array21['Fact_nombre'][$i],$Array21['Cose_nombre'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
             $stmt = $con->prepare("select Cose_id from cosecha where Cose_nombre= :cose_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
             $stmt->bindParam(':cose_nombre',$Array21['Cose_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Fact_id from facturaventa where Fact_nombre= :fact_nombre AND Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)");
             $stmt->bindParam(':fact_nombre',$Array21['Fact_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
              $id_cosecha=$results[0];
              $id_factura=$result[0];
              $stmt = $con->prepare("UPDATE ventacosecha SET VentaCose_cantidad=:VentaCose_cantidad,VentaCose_valor=:VentaCose_valor WHERE Cose_id=:Cose_id AND Fact_id=:Fact_id");
              $stmt->bindParam(':VentaCose_cantidad',$Array21['VentaCose_cantidad'][$i], PDO::PARAM_INT);
              $stmt->bindParam(':VentaCose_valor',$Array21['VentaCose_valor'][$i], PDO::PARAM_INT);
              $stmt->bindParam(':Cose_id',$id_cosecha, PDO::PARAM_INT);
              $stmt->bindParam(':Fact_id',$id_factura, PDO::PARAM_INT);
              $stmt->execute();
              echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select Cose_id from cosecha where Cose_nombre= :cose_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
             $stmt->bindParam(':cose_nombre',$Array21['Cose_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Fact_id from facturaventa where Fact_nombre= :fact_nombre AND Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)");
             $stmt->bindParam(':fact_nombre',$Array21['Fact_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
              $id_cosecha=$results[0];
              $id_factura=$result[0];
              $stmt = $con->prepare("INSERT INTO ventacosecha VALUES(null,:VentaCose_cantidad,:VentaCose_valor,:Cose_id,:Fact_id)");

             $stmt->bindParam(':VentaCose_cantidad',$Array21['VentaCose_cantidad'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':VentaCose_valor',$Array21['VentaCose_valor'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Cose_id',$id_cosecha, PDO::PARAM_INT);
             $stmt->bindParam(':Fact_id',$id_factura, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
           }
            $var='"'.$id_cosecha.'"';
            $var1='"'.$id_factura.'"';
            array_push($auxiliar,$var);
            array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from ventacosecha where Cose_id NOT IN('.$statut.') AND Fact_id IN('.$statut1.')');
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos VENTA_COSECHA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array22)){
          try{
            if($Array22['VentaInsu_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from ventainsumo where Fact_id in(select Fact_id from facturaventa where Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)) AND Insum_id in(select Insum_id from insumo where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array22['VentaInsu_id']); $i++) {
          $existencia=Verifica_Existencia2("VENTA_INSUMO",$Array22['Fact_nombre'][$i],$Array22['Insum_nombre'][$i],$usua_id);
        
          if($existencia){
             echo 'existe';
             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array22['Insum_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Fact_id from facturaventa where Fact_nombre= :fact_nombre AND Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)");
             $stmt->bindParam(':fact_nombre',$Array22['Fact_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
              $id_insumo=$results[0];
              $id_factura=$result[0];
              $stmt = $con->prepare("UPDATE ventainsumo SET VentaInsu_cantidad=:VentaInsu_cantidad,VentaInsu_valor=:VentaInsu_valor WHERE Insum_id=:Insum_id AND Fact_id=:Fact_id");

             $stmt->bindParam(':VentaInsu_cantidad',$Array22['VentaInsu_cantidad'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':VentaInsu_valor',$Array22['VentaInsu_valor'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
             $stmt->bindParam(':Fact_id',$id_factura, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array22['Insum_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Fact_id from facturaventa where Fact_nombre= :fact_nombre AND Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)");
             $stmt->bindParam(':fact_nombre',$Array22['Fact_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
              echo 'aqui';
              $id_insumo=$results[0];
              $id_factura=$result[0];
              $stmt = $con->prepare("INSERT INTO ventainsumo VALUES(null,:VentaInsu_cantidad,:VentaInsu_valor,:Insum_id,:Fact_id)");

             $stmt->bindParam(':VentaInsu_cantidad',$Array22['VentaInsu_cantidad'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':VentaInsu_valor',$Array22['VentaInsu_valor'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
             $stmt->bindParam(':Fact_id',$id_factura, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
           }
            $var='"'.$id_insumo.'"';
            $var1='"'.$id_factura.'"';
            array_push($auxiliar,$var);
            array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from ventainsumo where Insum_id NOT IN('.$statut.') AND Fact_id IN('.$statut1.')');
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos VENTA_INSUMO: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array23)){
        try{
            if($Array23['Prove_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from proveedor where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array23['Prove_id']); $i++) {
          $existencia=Verifica_Existencia("PROVEEDOR",$Array23['Prove_nombre'][$i],$usua_id);
        
          if($existencia){
             //echo 'Existencia '.$GLOBALS['PARCELA_ID'];
            echo 'existe';
             $stmt = $con->prepare("UPDATE proveedor SET Prove_horario=:Prove_horario,Prove_observaciones=:Prove_observaciones,Prove_direccionWeb=:Prove_direccionWeb,Prove_estado=:Prove_estado,Prove_personaContacto=:Prove_personaContacto,Prove_celular=:Prove_celular,Prove_sexo=:Prove_sexo,Prove_direccion=:Prove_direccion,Prove_correo=:Prove_correo,Prove_telefono=:Prove_telefono,Prove_pais=:Prove_pais,Prove_provincia=:Prove_provincia,Prove_fecha=:Prove_fecha,Prove_fechaActualizacion=:Prove_fechaActualizacion WHERE Prove_nombre=:Prove_nombre AND Usua_id=:Usua_id");

             $stmt->bindParam(':Prove_horario',$Array23['Prove_horario'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_observaciones',$Array23['Prove_observaciones'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_direccionWeb',$Array23['Prove_direccionWeb'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_estado',$Array23['Prove_estado'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_nombre',$Array23['Prove_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_personaContacto',$Array23['Prove_personaContacto'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_celular',$Array23['Prove_celular'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_sexo',$Array23['Prove_sexo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_direccion',$Array23['Prove_direccion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_correo',$Array23['Prove_correo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_telefono',$Array23['Prove_telefono'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_pais',$Array23['Prove_pais'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_provincia',$Array23['Prove_provincia'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_fecha',$Array23['Prove_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_fechaActualizacion',$Array23['Prove_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO proveedor VALUES(null,:Prove_horario,:Prove_observaciones,:Prove_direccionWeb,:Prove_estado,:Prove_nombre,:Prove_personaContacto,:Prove_celular,:Prove_sexo,:Prove_direccion,:Prove_correo,:Prove_telefono,:Prove_pais,:Prove_provincia,:Prove_fecha,:Prove_fechaActualizacion,:Usua_id)");

             $stmt->bindParam(':Prove_horario',$Array23['Prove_horario'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_observaciones',$Array23['Prove_observaciones'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_direccionWeb',$Array23['Prove_direccionWeb'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_estado',$Array23['Prove_estado'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_nombre',$Array23['Prove_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_personaContacto',$Array23['Prove_personaContacto'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_celular',$Array23['Prove_celular'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_sexo',$Array23['Prove_sexo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_direccion',$Array23['Prove_direccion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_correo',$Array23['Prove_correo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_telefono',$Array23['Prove_telefono'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_pais',$Array23['Prove_pais'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_provincia',$Array23['Prove_provincia'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_fecha',$Array23['Prove_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_fechaActualizacion',$Array23['Prove_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
             $var='"'.$Array23['Prove_nombre'][$i].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from proveedor where Prove_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos PROVEEDOR: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array24)){
        try{
            if($Array24['Fact_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from factura where Prove_id in(select Prove_id from proveedor where Usua_id= :usua_id) AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array24['Fact_id']); $i++) {
          $existencia=Verifica_Existencia3("FACTURA_COMPRA",$Array24['Prove_nombre'][$i],$Array24['Culti_nombre'][$i],$Array24['Fact_Nombre'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("select Prove_id from proveedor where Prove_nombre= :prove_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':prove_nombre',$Array24['Prove_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
            $stmt->bindParam(':culti_nombre',$Array24['Culti_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
              $id_proveedor=$results[0];
              $id_cultivo=$result[0];

              $stmt = $con->prepare("UPDATE factura SET Fact_nroArticulos=:Fact_nroArticulos,Fact_total=:Fact_total,Fact_Fecha=:Fact_Fecha WHERE Fact_Nombre=:Fact_Nombre AND Culti_id=:Culti_id AND Prove_id=:Prove_id");
             $stmt->bindParam(':Fact_Nombre',$Array24['Fact_Nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_nroArticulos',$Array24['Fact_nroArticulos'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_total',$Array24['Fact_total'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_Fecha',$Array24['Fact_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
             $stmt->bindParam(':Prove_id',$id_proveedor, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select Prove_id from proveedor where Prove_nombre= :prove_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':prove_nombre',$Array24['Prove_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array24['Culti_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
              $id_proveedor=$results[0];
              $id_cultivo=$result[0];

              $stmt = $con->prepare("INSERT INTO factura VALUES(null,:Fact_Nombre,:Fact_nroArticulos,:Fact_total,:Fact_Fecha,:Culti_id,:Prove_id)");
             $stmt->bindParam(':Fact_Nombre',$Array24['Fact_Nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_nroArticulos',$Array24['Fact_nroArticulos'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_total',$Array24['Fact_total'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_Fecha',$Array24['Fact_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
             $stmt->bindParam(':Prove_id',$id_proveedor, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
           }
             $var='"'.$Array24['Fact_Nombre'][$i].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from factura where Fact_Nombre NOT IN('.$statut.') AND Prove_id in(select Prove_id from proveedor where Usua_id= :usua_id) AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos FACTURA_COMPRA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array25)){
         try{
            if($Array25['Comp_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from compra where Fact_id in(select Fact_id from factura where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))) AND Insum_id in(select Insum_id from insumo where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array25['Comp_id']); $i++) {
          $existencia=Verifica_Existencia2("COMPRAS",$Array25['Insum_nombre'][$i],$Array25['Fact_Nombre'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':insum_nombre',$Array25['Insum_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select Fact_id from factura where Fact_Nombre= :fact_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Prove_id in(select Prove_id from proveedor where Usua_id= :usua_id)");
            $stmt->bindParam(':fact_nombre',$Array25['Fact_Nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
              $id_insumo=$results[0];
              $id_factura=$result[0];
              $stmt = $con->prepare("UPDATE compra SET Comp_valorTotal=:Comp_valorTotal,Comp_cantidad=:Comp_cantidad SET WHERE Fact_id=:Fact_id AND Insum_id=:Insum_id");
              $stmt->bindParam(':Comp_valorTotal',$Array25['Comp_valorTotal'][$i], PDO::PARAM_INT);
              $stmt->bindParam(':Comp_cantidad',$Array25['Comp_cantidad'][$i], PDO::PARAM_INT);
              $stmt->bindParam(':Fact_id',$id_factura, PDO::PARAM_INT);
              $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
              $stmt->execute();
              echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array25['Insum_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Fact_id from factura where Fact_Nombre= :fact_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Prove_id in(select Prove_id from proveedor where Usua_id= :usua_id)");
             $stmt->bindParam(':fact_nombre',$Array25['Fact_Nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
              $id_insumo=$results[0];
              $id_factura=$result[0];
              $stmt = $con->prepare("INSERT INTO compra VALUES(null,:Comp_valorTotal,:Comp_cantidad,:Fact_id,:Insum_id)");
              $stmt->bindParam(':Comp_valorTotal',$Array25['Comp_valorTotal'][$i], PDO::PARAM_INT);
              $stmt->bindParam(':Comp_cantidad',$Array25['Comp_cantidad'][$i], PDO::PARAM_INT);
              $stmt->bindParam(':Fact_id',$id_factura, PDO::PARAM_INT);
              $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
              $stmt->execute();
              echo 'Ok';
             }
           }
            $var='"'.$id_factura.'"';
            $var1='"'.$id_insumo.'"';
            array_push($auxiliar,$var);
            array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from compra where Fact_id IN('.$statut.') AND Insum_id NOT IN('.$statut1.')');
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos COMPRAS: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array26)){
         try{
            if($Array26['Nom_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from nomina where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array26['Nom_id']); $i++) {
          $existencia=Verifica_Existencia("NOMINAS",$Array26['Nom_nombre'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("UPDATE nomina SET Nom_fecha=:Nom_fecha,Nom_totalSueldos=:Nom_totalSueldos,Nom_totalAuxTransporte=:Nom_totalAuxTransporte,Nom_totalDevengados=:Nom_totalDevengados,Nom_totalSalud=:Nom_totalSalud,Nom_totalPensiones=:Nom_totalPensiones,Nom_totalDeducido=:Nom_totalDeducido,Nom_totalPagos=:Nom_totalPagos WHERE Nom_nombre=:Nom_nombre AND Usua_id=:Usua_id");

            $stmt->bindParam(':Nom_nombre',$Array26['Nom_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Nom_fecha',$Array26['Nom_fecha'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Nom_totalSueldos',$Array26['Nom_totalSueldos'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Nom_totalAuxTransporte',$Array26['Nom_totalAuxTransporte'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Nom_totalDevengados',$Array26['Nom_totalDevengados'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Nom_totalSalud',$Array26['Nom_totalSalud'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Nom_totalPensiones',$Array26['Nom_totalPensiones'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Nom_totalDeducido',$Array26['Nom_totalDeducido'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Nom_totalPagos',$Array26['Nom_totalPagos'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            echo 'Ok';
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO nomina VALUES(null,:Nom_nombre,:Nom_fecha,:Nom_totalSueldos,:Nom_totalAuxTransporte,:Nom_totalDevengados,:Nom_totalSalud,:Nom_totalPensiones,:Nom_totalDeducido,:Nom_totalPagos,:Usua_id)");

             $stmt->bindParam(':Nom_nombre',$Array26['Nom_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Nom_fecha',$Array26['Nom_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Nom_totalSueldos',$Array26['Nom_totalSueldos'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Nom_totalAuxTransporte',$Array26['Nom_totalAuxTransporte'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Nom_totalDevengados',$Array26['Nom_totalDevengados'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Nom_totalSalud',$Array26['Nom_totalSalud'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Nom_totalPensiones',$Array26['Nom_totalPensiones'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Nom_totalDeducido',$Array26['Nom_totalDeducido'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Nom_totalPagos',$Array26['Nom_totalPagos'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
            $var='"'.$Array26['Nom_nombre'][$i].'"';
            array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from nomina where Nom_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos NOMINAS: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array27)){
         try{
            if($Array27['Emple_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from empleado where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array27['Emple_id']); $i++) {
          $existencia=Verifica_Existencia("EMPLEADO",$Array27['Emple_identificacion'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
             $stmt = $con->prepare("UPDATE empleado SET Emple_pais=:Emple_pais,Emple_ciudad=:Emple_ciudad,Emple_sexo=:Emple_sexo,Emple_nombre=:Emple_nombre,Emple_apellido=:Emple_apellido,Emple_telefono=:Emple_telefono,Emple_celular=:Emple_celular,Emple_direccion=:Emple_direccion,Emple_fechaNacimiento=:Emple_fechaNacimiento,Emple_correo=:Emple_correo,Emple_sueldo=:Emple_sueldo,Emple_fechaRegistro=:Emple_fechaRegistro,Emple_fechaActualizacion=:Emple_fechaActualizacion,Emple_foto=:Emple_foto WHERE Emple_identificacion=:Emple_identificacion AND Usua_id=:Usua_id");

             $stmt->bindParam(':Emple_identificacion',$Array27['Emple_identificacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_pais',$Array27['Emple_pais'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_ciudad',$Array27['Emple_ciudad'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_sexo',$Array27['Emple_sexo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_nombre',$Array27['Emple_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_apellido',$Array27['Emple_apellido'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_telefono',$Array27['Emple_telefono'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_celular',$Array27['Emple_celular'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_direccion',$Array27['Emple_direccion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_fechaNacimiento',$Array27['Emple_fechaNacimiento'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_correo',$Array27['Emple_correo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_sueldo',$Array27['Emple_sueldo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_fechaRegistro',$Array27['Emple_fechaRegistro'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_fechaActualizacion',$Array27['Emple_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_foto',$Array27['Emple_foto'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
          }  
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO empleado VALUES(null,:Emple_identificacion,:Emple_pais,:Emple_ciudad,:Emple_sexo,:Emple_nombre,:Emple_apellido,:Emple_telefono,:Emple_celular,:Emple_direccion,:Emple_fechaNacimiento,:Emple_correo,:Emple_sueldo,:Emple_fechaRegistro,:Emple_fechaActualizacion,:Emple_foto,:Usua_id)");

             $stmt->bindParam(':Emple_identificacion',$Array27['Emple_identificacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_pais',$Array27['Emple_pais'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_ciudad',$Array27['Emple_ciudad'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_sexo',$Array27['Emple_sexo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_nombre',$Array27['Emple_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_apellido',$Array27['Emple_apellido'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_telefono',$Array27['Emple_telefono'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_celular',$Array27['Emple_celular'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_direccion',$Array27['Emple_direccion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_fechaNacimiento',$Array27['Emple_fechaNacimiento'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_correo',$Array27['Emple_correo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_sueldo',$Array27['Emple_sueldo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_fechaRegistro',$Array27['Emple_fechaRegistro'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_fechaActualizacion',$Array27['Emple_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_foto',$Array27['Emple_foto'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
             $var='"'.$Array27['Emple_identificacion'][$i].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from empleado where Emple_identificacion NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos EMPLEADO: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array28)){
          try{
            if($Array28['Pago_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from pago where Nom_id in(select Nom_id from nomina where Usua_id= :usua_id) AND Emple_id in(select Emple_id from empleado where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array28['Pago_id']); $i++) {
          $existencia=Verifica_Existencia2("PAGOS",$Array28['Nom_nombre'][$i],$Array28['Emple_identificacion'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("select Emple_id from empleado where Emple_identificacion= :emple_identificacion AND Usua_id= :usua_id");
            $stmt->bindParam(':emple_identificacion',$Array28['Emple_identificacion'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select Nom_id from nomina where Nom_nombre= :nom_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':nom_nombre',$Array28['Nom_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
              echo 'aqui';
              $id_empleado=$results[0];
              $id_nomina=$result[0];
              $stmt = $con->prepare("UPDATE pago SET Pago_fecha=:Pago_fecha,Pago_SalarioBase=:Pago_SalarioBase,Pago_nroDias=:Pago_nroDias,Pago_Sueldo=:Pago_Sueldo,Pago_AuxTransporte=:Pago_AuxTransporte,Pago_Devengado=:Pago_Devengado,Pago_Salud=:Pago_Salud,Pago_Pension=:Pago_Pension,Pago_FondoSolidaridad=:Pago_FondoSolidaridad,Pago_Deducido=:Pago_Deducido,Pago_Total=:Pago_Total,Pago_ValorDia=:Pago_ValorDia,Pago_ValorHora=:Pago_ValorHora WHERE Emple_id=:Emple_id AND Nom_id=:Nom_id");

             $stmt->bindParam(':Pago_fecha',$Array28['Pago_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_SalarioBase',$Array28['Pago_SalarioBase'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_nroDias',$Array28['Pago_nroDias'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_Sueldo',$Array28['Pago_Sueldo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_AuxTransporte',$Array28['Pago_AuxTransporte'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_Devengado',$Array28['Pago_Devengado'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_Salud',$Array28['Pago_Salud'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_Pension',$Array28['Pago_Pension'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_FondoSolidaridad',$Array28['Pago_FondoSolidaridad'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_Deducido',$Array28['Pago_Deducido'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_Total',$Array28['Pago_Total'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_ValorDia',$Array28['Pago_ValorDia'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_ValorHora',$Array28['Pago_ValorHora'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_id',$id_empleado, PDO::PARAM_INT);
             $stmt->bindParam(':Nom_id',$id_nomina, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select Emple_id from empleado where Emple_identificacion= :emple_identificacion AND Usua_id= :usua_id");
             $stmt->bindParam(':emple_identificacion',$Array28['Emple_identificacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Nom_id from nomina where Nom_nombre= :nom_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':nom_nombre',$Array28['Nom_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
              echo 'aqui';
              $id_empleado=$results[0];
              $id_nomina=$result[0];
              $stmt = $con->prepare("INSERT INTO pago VALUES(null,:Pago_fecha,:Pago_SalarioBase,:Pago_nroDias,:Pago_Sueldo,:Pago_AuxTransporte,:Pago_Devengado,:Pago_Salud,:Pago_Pension,:Pago_FondoSolidaridad,:Pago_Deducido,:Pago_Total,:Pago_ValorDia,:Pago_ValorHora,:Emple_id,:Nom_id)");

             $stmt->bindParam(':Pago_fecha',$Array28['Pago_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_SalarioBase',$Array28['Pago_SalarioBase'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_nroDias',$Array28['Pago_nroDias'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_Sueldo',$Array28['Pago_Sueldo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_AuxTransporte',$Array28['Pago_AuxTransporte'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_Devengado',$Array28['Pago_Devengado'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_Salud',$Array28['Pago_Salud'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_Pension',$Array28['Pago_Pension'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_FondoSolidaridad',$Array28['Pago_FondoSolidaridad'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_Deducido',$Array28['Pago_Deducido'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_Total',$Array28['Pago_Total'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_ValorDia',$Array28['Pago_ValorDia'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Pago_ValorHora',$Array28['Pago_ValorHora'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_id',$id_empleado, PDO::PARAM_INT);
             $stmt->bindParam(':Nom_id',$id_nomina, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
           }
             $var='"'.$id_empleado.'"';
             $var1='"'.$id_nomina.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from pago where Emple_id NOT IN('.$statut.') AND Nom_id NOT IN('.$statut1.') AND Nom_id in(select Nom_id from nomina where Usua_id= :usua_id) AND Emple_id in(select Emple_id from empleado where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos PAGOS: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array29)){
         try{
            if($Array29['Tarea_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from tarea where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array29['Tarea_id']); $i++) {
          $existencia=Verifica_Existencia("TAREAS",$Array29['Tarea_nombre'][$i],$usua_id);
        
          if($existencia){
             //echo 'Existencia '.$GLOBALS['PARCELA_ID'];
            echo 'existe';
            $stmt = $con->prepare("select Emple_id from empleado where Emple_identificacion= :emple_identificacion AND Usua_id= :usua_id");
            $stmt->bindParam(':emple_identificacion',$Array29['Emple_identificacion'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

             if($results){
              $id_empleado=$results[0];
              $stmt = $con->prepare("UPDATE tarea SET Tarea_descripcion=:Tarea_descripcion,Tarea_prioridad=:Tarea_prioridad,Tarea_tipo=:Tarea_tipo,Tarea_fechaCreacion=:Tarea_fechaCreacion,Tarea_fechaCumplimiento=:Tarea_fechaCumplimiento,Tarea_horaCumplimiento=:Tarea_horaCumplimiento,Tarea_fechaActualizacion=:Tarea_fechaActualizacion,Tarea_estado=:Tarea_estado,Tarea_modelo=:Tarea_modelo,Emple_id=:Emple_id WHERE Tarea_nombre=:Tarea_nombre AND Usua_id=:Usua_id");

             $stmt->bindParam(':Tarea_nombre',$Array29['Tarea_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_descripcion',$Array29['Tarea_descripcion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_prioridad',$Array29['Tarea_prioridad'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_tipo',$Array29['Tarea_tipo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_fechaCreacion',$Array29['Tarea_fechaCreacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_fechaCumplimiento',$Array29['Tarea_fechaCumplimiento'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_horaCumplimiento',$Array29['Tarea_horaCumplimiento'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_fechaActualizacion',$Array29['Tarea_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_estado',$Array29['Tarea_estado'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_modelo',$Array29['Tarea_modelo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_id',$id_empleado, PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select Emple_id from empleado where Emple_identificacion= :emple_identificacion AND Usua_id= :usua_id");
             $stmt->bindParam(':emple_identificacion',$Array29['Emple_identificacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
              $id_empleado=$results[0];
              $stmt = $con->prepare("INSERT INTO tarea VALUES(null,:Tarea_nombre,:Tarea_descripcion,:Tarea_prioridad,:Tarea_tipo,:Tarea_fechaCreacion,:Tarea_fechaCumplimiento,:Tarea_horaCumplimiento,:Tarea_fechaActualizacion,:Tarea_estado,:Tarea_modelo,:Emple_id,:Usua_id)");

             $stmt->bindParam(':Tarea_nombre',$Array29['Tarea_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_descripcion',$Array29['Tarea_descripcion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_prioridad',$Array29['Tarea_prioridad'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_tipo',$Array29['Tarea_tipo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_fechaCreacion',$Array29['Tarea_fechaCreacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_fechaCumplimiento',$Array29['Tarea_fechaCumplimiento'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_horaCumplimiento',$Array29['Tarea_horaCumplimiento'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_fechaActualizacion',$Array29['Tarea_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_estado',$Array29['Tarea_estado'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_modelo',$Array29['Tarea_modelo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_id',$id_empleado, PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
           }
             $var='"'.$Array29['Tarea_nombre'][$i].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from tarea where Tarea_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos TAREAS: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array30)){
        try{
            if($Array30['LaborInsumo_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from utiliza_insum_tarea where Insum_id in(select Insum_id from insumo where Usua_id= :usua_id) AND Tarea_id in(select Tarea_id from tarea where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array30['LaborInsumo_id']); $i++) {
          $existencia=Verifica_Existencia2("UTILIZA_INSUM_TAREA",$Array30['Insum_nombre'][$i],$Array30['Tarea_nombre'][$i],$usua_id);
        
           if(!$existencia){ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe---';
            $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':insum_nombre',$Array30['Insum_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select Tarea_id from tarea where Tarea_nombre= :tarea_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':tarea_nombre',$Array30['Tarea_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
              $id_insumo=$results[0];
              $id_tarea=$result[0];

              $stmt = $con->prepare("INSERT INTO utiliza_insum_tarea VALUES(null,:Insum_id,:Tarea_id)");

             $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_id',$id_tarea, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
           }
             $var='"'.$id_insumo.'"';
             $var1='"'.$id_tarea.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from utiliza_insum_tarea where Insum_id NOT IN('.$statut.') AND Tarea_id IN('.$statut1.')');
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos UTILIZA_INSUM_TAREA: '. $e->getMessage();
          }
       } 

      if(!empty($Array31)){
        try{
            if($Array31['estacion_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from estacion_meteorologica where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array31['estacion_id']); $i++) {
          $existencia=Verifica_Existencia("ESTACION",$Array31['estacion_id'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("select Parce_id from parcela where Parce_nombre= :parce_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':parce_nombre',$Array31['Parce_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            if($results){
              $id_parcela=$results[0];
              $stmt = $con->prepare("UPDATE estacion_meteorologica SET estacion_nombre=:estacion_nombre,estacion_fechaRegistro=:estacion_fechaRegistro,estacion_fechaactualizacion=:estacion_fechaactualizacion,parce_id=:parce_id WHERE estacion_id=:estacion_id");

             $stmt->bindParam(':estacion_id',$Array31['estacion_id'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':estacion_nombre',$Array31['estacion_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':estacion_fechaRegistro',$Array31['estacion_fechaRegistro'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':estacion_fechaactualizacion',$Array31['estacion_fechaactualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':parce_id',$id_parcela, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
            $stmt = $con->prepare("select Parce_id from parcela where Parce_nombre= :parce_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':parce_nombre',$Array31['Parce_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            if($results){
              $id_parcela=$results[0];
              $stmt = $con->prepare("INSERT INTO estacion_meteorologica VALUES(:estacion_id,:estacion_nombre,:estacion_fechaRegistro,:estacion_fechaactualizacion,:parce_id)");

             $stmt->bindParam(':estacion_id',$Array31['estacion_id'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':estacion_nombre',$Array31['estacion_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':estacion_fechaRegistro',$Array31['estacion_fechaRegistro'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':estacion_fechaactualizacion',$Array31['estacion_fechaactualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':parce_id',$id_parcela, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
           }
             $var='"'.$Array31['estacion_id'][$i].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from estacion_meteorologica where estacion_id NOT IN('.$statut.') AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos ESTACION: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array32)){
         try{
            if($Array32['Estacion_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from medicionmeteorologica where Estacion_id in (select estacion_id from estacion_meteorologica where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array32['Estacion_id']); $i++) {
             $var='"'.$Array32['MediMete_fecha'][$i].'"';
             $var1='"'.$Array32['MediMete_hora'][$i].'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from medicionmeteorologica where MediMete_fecha NOT IN('.$statut.') AND MediMete_hora NOT IN('.$statut.') AND Estacion_id IN (select estacion_id from estacion_meteorologica where Parce_id in(select Parce_id from parcela where Usua_id=:usua_id))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos MEDICION_METEO: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array33)){
         try{
            if($Array33['Evt_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from evento where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array33['Evt_id']); $i++) {
          $existencia=Verifica_Existencia("EVENTOS",$Array33['Evt_nombre'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("UPDATE evento SET Evt_fechaCreacion=:Evt_fechaCreacion,Evt_fechaActualizacion=:Evt_fechaActualizacion,Evt_fechaProgramada=:Evt_fechaProgramada,Evt_descripcion=:Evt_descripcion WHERE Evt_nombre=:Evt_nombre AND Usua_id=:Usua_id");

             $stmt->bindParam(':Evt_nombre',$Array33['Evt_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_fechaCreacion',$Array33['Evt_fechaCreacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_fechaActualizacion',$Array33['Evt_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_fechaProgramada',$Array33['Evt_fechaProgramada'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_descripcion',$Array33['Evt_descripcion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("INSERT INTO evento VALUES(null,:Evt_nombre,:Evt_fechaCreacion,:Evt_fechaActualizacion,:Evt_fechaProgramada,:Evt_descripcion,:Usua_id)");

             $stmt->bindParam(':Evt_nombre',$Array33['Evt_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_fechaCreacion',$Array33['Evt_fechaCreacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_fechaActualizacion',$Array33['Evt_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_fechaProgramada',$Array33['Evt_fechaProgramada'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_descripcion',$Array33['Evt_descripcion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
             $var='"'.$Array33['Evt_nombre'][$i].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from evento where Evt_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos EVENTOS: '. $e->getMessage();
            $stmt->rollback(); 
          }
      }

      if(!empty($Array34)){
         try{
            if($Array34['AlerPro_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from alertapro where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array34['AlerPro_id']); $i++) {
          $existencia=Verifica_Existencia("ALERTAS",$Array34['AlerPro_nombre'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("UPDATE alertapro SET AlerPro_nombre=:AlerPro_nombre,AlerPro_tipo=:AlerPro_tipo,AlerPro_variable=:AlerPro_variable,AlerPro_fecha=:AlerPro_fecha,AlerPro_fechaActualizacion=:AlerPro_fechaActualizacion,AlerPro_prioridad=:AlerPro_prioridad,AlerPro_valor=:AlerPro_valor,AlerPro_descripcion=:AlerPro_descripcion,AlerPro_Estado=:AlerPro_Estado WHERE AlerPro_nombre=:AlerPro_nombre AND Usua_id=:Usua_id");
            $stmt->bindParam(':AlerPro_nombre',$Array34['AlerPro_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_tipo',$Array34['AlerPro_tipo'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_variable',$Array34['AlerPro_variable'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_fecha',$Array34['AlerPro_fecha'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_fechaActualizacion',$Array34['AlerPro_fechaActualizacion'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_prioridad',$Array34['AlerPro_prioridad'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_valor',$Array34['AlerPro_valor'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_descripcion',$Array34['AlerPro_descripcion'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_Estado',$Array34['AlerPro_Estado'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            echo 'Ok';
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("INSERT INTO alertapro VALUES(null,:AlerPro_nombre,:AlerPro_tipo,:AlerPro_variable,:AlerPro_fecha,:AlerPro_fechaActualizacion,:AlerPro_prioridad,:AlerPro_valor,:AlerPro_descripcion,:AlerPro_Estado,:Usua_id)");
             $stmt->bindParam(':AlerPro_nombre',$Array34['AlerPro_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_tipo',$Array34['AlerPro_tipo'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_variable',$Array34['AlerPro_variable'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_fecha',$Array34['AlerPro_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_fechaActualizacion',$Array34['AlerPro_fechaActualizacion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_prioridad',$Array34['AlerPro_prioridad'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_valor',$Array34['AlerPro_valor'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_descripcion',$Array34['AlerPro_descripcion'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_Estado',$Array34['AlerPro_Estado'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             echo 'Ok';
             }
             $var='"'.$Array34['AlerPro_nombre'][$i].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from alertapro where AlerPro_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos ALERTAS: '. $e->getMessage();
            $stmt->rollback(); 
          }
       }

      if(!empty($Array35)){
         try{
            if($Array35['AlerActiv_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from alertaactiva_sensor where AlerPro_id in(select AlerPro_id from alertapro where Usua_id= :usua_id) AND MediSens_id in(select MediSens_id from medicionsensor where Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array35['AlerActiv_id']); $i++) {
          $existencia=Verifica_Existencia3("ALERTA_SENSOR",$Array35['AlerPro_nombre'][$i],$Array35['MediSens_fecha'][$i],$Array35['MediSens_hora'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("select AlerPro_id from alertapro where AlerPro_nombre= :alerpro_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':alerpro_nombre',$Array35['AlerPro_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select MediSens_id from medicionsensor where MediSens_fecha= :medisens_fecha AND MediSens_hora= :medisens_hora AND Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))");
            $stmt->bindParam(':medisens_fecha',$Array35['MediSens_fecha'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':medisens_hora',$Array35['MediSens_hora'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
                $id_alerta=$results[0];
                $id_medisens=$result[0];
                $stmt = $con->prepare("UPDATE alertaactiva_sensor SET AlerActiv_HoraMedicion=:AlerActiv_HoraMedicion,AlerActiv_Valor=:AlerActiv_Valor,AlerActiv_fechaRegistro=:AlerActiv_fechaRegistro,AlerActiv_Horaregistro=:AlerActiv_Horaregistro WHERE AlerPro_id=:AlerPro_id AND MediSens_id=:MediSens_id");
                $stmt->bindParam(':AlerActiv_HoraMedicion',$Array35['AlerActiv_HoraMedicion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_Valor',$Array35['AlerActiv_Valor'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_fechaRegistro',$Array35['AlerActiv_fechaRegistro'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_Horaregistro',$Array35['AlerActiv_Horaregistro'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':AlerPro_id',$id_alerta, PDO::PARAM_INT);
                $stmt->bindParam(':MediSens_id',$id_medisens, PDO::PARAM_INT);
                $stmt->execute();
                echo 'Ok';
            }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select AlerPro_id from alertapro where AlerPro_nombre= :alerpro_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':alerpro_nombre',$Array35['AlerPro_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select MediSens_id from medicionsensor where MediSens_fecha= :medisens_fecha AND MediSens_hora= :medisens_hora AND Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))");
             $stmt->bindParam(':medisens_fecha',$Array35['MediSens_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':medisens_hora',$Array35['MediSens_hora'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
                $id_alerta=$results[0];
                $id_medisens=$result[0];
                $stmt = $con->prepare("INSERT INTO alertaactiva_sensor VALUES(null,:AlerActiv_HoraMedicion,:AlerActiv_Valor,:AlerActiv_fechaRegistro,:AlerActiv_Horaregistro,:AlerPro_id,:MediSens_id)");
                $stmt->bindParam(':AlerActiv_HoraMedicion',$Array35['AlerActiv_HoraMedicion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_Valor',$Array35['AlerActiv_Valor'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_fechaRegistro',$Array35['AlerActiv_fechaRegistro'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_Horaregistro',$Array35['AlerActiv_Horaregistro'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':AlerPro_id',$id_alerta, PDO::PARAM_INT);
                $stmt->bindParam(':MediSens_id',$id_medisens, PDO::PARAM_INT);
                $stmt->execute();
                echo 'Ok';
            }
           }
             $var='"'.$id_alerta.'"';
             $var1='"'.$id_medisens.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from alertaactiva_sensor where AlerPro_id NOT IN('.$statut.') AND MediSens_id NOT IN('.$statut1.') AND AlerPro_id in(select AlerPro_id from alertapro where Usua_id= :usua_id) AND MediSens_id in(select MediSens_id from medicionsensor where Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos ALERTA_SENSOR: '. $e->getMessage();
            $stmt->rollback(); 
          }
       }

      if(!empty($Array36)){
         try{
            if($Array36['AlerActiv_id'][0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from alertaactiva_meteorologia where AlerPro_id in(select AlerPro_id from alertapro where Usua_id= :usua_id) AND MediMete_id in(select MediMete_id from medicionmeteorologica where Estacion_id in(select Estacion_id from estacion_meteorologica where Parce_id in (select Parce_id from parcela where Usua_id= :usua_id)))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array36['AlerActiv_id']); $i++) {
          $existencia=Verifica_Existencia3("ALERTA_ESTACION",$Array36['AlerPro_nombre'][$i],$Array36['MediMete_fecha'][$i],$Array36['MediMete_hora'][$i],$usua_id);
        
          if($existencia){
            echo 'existe';
            $stmt = $con->prepare("select AlerPro_id from alertapro where AlerPro_nombre= :alerpro_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':alerpro_nombre',$Array36['AlerPro_nombre'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select MediMete_id from medicionmeteorologica where MediMete_fecha= :medimete_fecha AND MediMete_hora= :medimete_hora AND Estacion_id in(select Estacion_id from estacion_meteorologica where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
            $stmt->bindParam(':medimete_fecha',$Array36['MediMete_fecha'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':medimete_hora',$Array36['MediMete_hora'][$i], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
               $id_alerta=$results[0];
               $id_medimete=$result[0];
               $stmt = $con->prepare("UPDATE alertaactiva_meteorologia SET AlerActiv_HoraMedicion=:AlerActiv_HoraMedicion,AlerActiv_Valor=:AlerActiv_Valor,AlerActiv_fechaRegistro=:AlerActiv_fechaRegistro,AlerActiv_Horaregistro=:AlerActiv_Horaregistro WHERE AlerPro_id=:AlerPro_id AND MediMete_id=:MediMete_id");
               $stmt->bindParam(':AlerActiv_HoraMedicion',$Array36['AlerActiv_HoraMedicion'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':AlerActiv_Valor',$Array36['AlerActiv_Valor'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':AlerActiv_fechaRegistro',$Array36['AlerActiv_fechaRegistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':AlerActiv_Horaregistro',$Array36['AlerActiv_Horaregistro'][$i], PDO::PARAM_INT);
               $stmt->bindParam(':AlerPro_id',$id_alerta, PDO::PARAM_INT);
               $stmt->bindParam(':MediMete_id',$id_medimete, PDO::PARAM_INT);
               $stmt->execute();
               echo 'Ok';
           }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            echo 'no existe';
             $stmt = $con->prepare("select AlerPro_id from alertapro where AlerPro_nombre= :alerpro_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':alerpro_nombre',$Array36['AlerPro_nombre'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select MediMete_id from medicionmeteorologica where MediMete_fecha= :medimete_fecha AND MediMete_hora= :medimete_hora AND Estacion_id in(select Estacion_id from estacion_meteorologica where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
             $stmt->bindParam(':medimete_fecha',$Array36['MediMete_fecha'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':medimete_hora',$Array36['MediMete_hora'][$i], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
                $id_alerta=$results[0];
                $id_medimete=$result[0];
                $stmt = $con->prepare("INSERT INTO alertaactiva_meteorologia VALUES(null,:AlerActiv_HoraMedicion,:AlerActiv_Valor,:AlerActiv_fechaRegistro,:AlerActiv_Horaregistro,:AlerPro_id,:MediMete_id)");
                $stmt->bindParam(':AlerActiv_HoraMedicion',$Array36['AlerActiv_HoraMedicion'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_Valor',$Array36['AlerActiv_Valor'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_fechaRegistro',$Array36['AlerActiv_fechaRegistro'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_Horaregistro',$Array36['AlerActiv_Horaregistro'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':AlerPro_id',$id_alerta, PDO::PARAM_INT);
                $stmt->bindParam(':MediMete_id',$id_medimete, PDO::PARAM_INT);
                $stmt->execute();
                echo 'Ok';
            }
           }
             $var='"'.$id_alerta.'"';
             $var1='"'.$id_medimete.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from alertaactiva_meteorologia where AlerPro_id NOT IN('.$statut.') AND MediMete_id NOT IN('.$statut1.') AND AlerPro_id in(select AlerPro_id from alertapro where Usua_id= :usua_id) AND MediMete_id in(select MediMete_id from medicionmeteorologica where Estacion_id in(select Estacion_id from estacion_meteorologica where Parce_id in (select Parce_id from parcela where Usua_id= :usua_id)))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos ALERTA_ESTACION: '. $e->getMessage();
            $stmt->rollback(); 
          }
       }
      }
    }
     catch(PDOException  $e)
     {
       echo 'Error conectando con la base de datos: ' . $e->getMessage();
       die();
     } 
  }
  else if(isset($_POST["Actualizar_Usuario"])){
    try{
        $se=json_decode(stripslashes($_POST["Actualizar_Usuario"]));
        $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
        $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $con->prepare("select Usua_id from usuario where Usua_identificacion= :usua_id");
        $stmt->bindParam(':usua_id',$se->usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $results=$stmt->fetch();

        $usua_id='';

        foreach ($results as $indice => $val)    
        {
           if(strcmp($indice,'Usua_id')==0){
               $usua_id=$val;
           }
        }

        if($usua_id!='')
        {
          $stmt = $con->prepare("select * from parcela where Usua_id= :usua_id"); //Se consultan las parcelas
          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt->execute();
          $results=$stmt->fetchAll(PDO::FETCH_ASSOC);
        
          if(!empty($results)){        
            $json_parcela=$results;
            $datos['PARCELAS']=$json_parcela;

          $stmt = $con->prepare("select * from cultivo where parce_id in(select parce_id from parcela where Usua_id= :usua_id)"); //Se consultan los cultivos
          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt->execute();
          $results=$stmt->fetchAll(PDO::FETCH_ASSOC);

          if(!empty($results)){ //Se consultan los nodos sensores asociados a esos cultivos
            $datos['CULTIVO']=$results;

            $stmt = $con->prepare("select * from resultados_rasta where Culti_id in(select culti_id from cultivo where parce_id in(select parce_id from parcela where Usua_id= :usua_id))");
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetchAll(PDO::FETCH_ASSOC);
   
            if(!empty($results)){//Alerta Programada, si existe existen alertas meteorologicas y por medicion
              $datos['RASTA']=$results;
            }
            else{
              $datos['RASTA']="TABLAVACIA"; 
            }
            
            $stmt = $con->prepare("select * from nodosensor where culti_id in(select culti_id from cultivo where parce_id in(select parce_id from parcela where Usua_id= :usua_id))"); //Se consultan las parcelas
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetchAll(PDO::FETCH_ASSOC);

            if(!empty($results)){  //Se consultan las mediciones de cada sensor asociado a cada cultivo
               $datos['NODOS']=$results;              
  
               $stmt = $con->prepare("select * from medicionsensor where sens_id in(select sens_id from nodosensor where culti_id in(select culti_id from cultivo where parce_id in(select parce_id from parcela where Usua_id= :usua_id)))");
               $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
               $stmt->execute();
               $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                   
              if(!empty($result)){// Se buscan los insumos registrados por ese usuario
                $datos['MEDICIONES']=$result;  
              }
              else{
                $datos['MEDICIONES']="TABLAVACIA";   
              }
            }
            else{
              $datos['NODOS']="TABLAVACIA";    
            }
            
            /*Si existen cultivos probablemente existan cosechas*/
            $stmt = $con->prepare("select * from cosecha where Culti_id in(select culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
            if(!empty($result)){ //Se consultan las cosechas            
               $datos['COSECHAS']=$result;
            }   
            else{
               $datos['COSECHAS']="TABLAVACIA"; 
            }
          }
          else{
           $datos['CULTIVO']="TABLAVACIA";   
          }
        }
        else{
           $datos['PARCELAS']="TABLAVACIA";
        }

        $stmt = $con->prepare("select * from insumo where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){// Se buscan las enfermedades registradas por ese usuario
           $datos['INSUMOS']=$result;
        }  
        else{
           $datos['INSUMOS']="TABLAVACIA"; 
        }
        
        $stmt = $con->prepare("select * from enfermedad where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){  // Se buscan las afecciones que esa enfermedad haya causado a algun cultivo
           $datos['ENFERMEDADES']=$result;  

           $stmt = $con->prepare("select * from afecta_enfermedad where Enfe_id in(select enfe_id from enfermedad where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

           if(!empty($result)){// Se buscan los tratamientos registrados para esa enfermedad            
              $datos['AFECTA_ENFERMEDADES']=$result; 

              $stmt = $con->prepare("select * from tratamiento_enfermedad where Enfe_id in(select enfe_id from enfermedad where Usua_id= :usua_id)");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                   
              if(!empty($result)){// Se buscan los resultados obtenidos para ese tratamiento de enfermedad               
                 $datos['TRATA_ENFERMEDADES']=$result;

                 $stmt = $con->prepare("select * from resultados_trata_enfermedad where Trata_id in(select Trata_id from tratamiento_enfermedad where Enfe_id in(select Enfe_id from enfermedad where Usua_id= :usua_id))");
                 $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
                 $stmt->execute();
                 $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

                 if(!empty($result)){               
                   $datos['RESULTADOS_ENFERMEDADES']=$result;
                 }
                 else{
                   $datos['RESULTADOS_ENFERMEDADES']="TABLAVACIA";     
                 }
              }
              else{
                 $datos['TRATA_ENFERMEDADES']="TABLAVACIA";    
              }
           }
           else{
              $datos['AFECTA_ENFERMEDADES']="TABLAVACIA";
           }
        }
        else{
          $datos['ENFERMEDADES']="TABLAVACIA";   
        }
                   
        $stmt = $con->prepare("select * from maleza where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){               
           $datos['MALEZA']=$result; 

           $stmt = $con->prepare("select * from afecta_maleza where male_id in(select male_id from maleza where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                        
           if(!empty($result)){               
              $datos['AFECTA_MALEZA']=$result; 
                      
              $stmt = $con->prepare("select * from tratamiento_maleza where male_id in(select male_id from maleza where Usua_id= :usua_id)");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                   
              if(!empty($result)){// Se buscan los resultados obtenidos para ese tratamiento de enfermedad               
                 $datos['TRATA_MALEZA']=$result;

                 $stmt = $con->prepare("select * from resultados_trata_maleza where Trata_id in(select Trata_id from tratamiento_maleza where male_id in(select Male_id from maleza where Usua_id= :usua_id))");
                 $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
                 $stmt->execute();
                 $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

                 if(!empty($result)){               
                   $datos['RESULTADOS_MALEZA']=$result;
                 }
                 else{
                   $datos['RESULTADOS_MALEZA']="TABLAVACIA";                  
                 }
              }
              else{
                 $datos['TRATA_MALEZA']="TABLAVACIA";              
              }
           }
           else{
              $datos['AFECTA_MALEZA']="TABLAVACIA";          
           }
        }
        else{
           $datos['MALEZA']="TABLAVACIA";      
        }
                      
        $stmt = $con->prepare("select * from Insecto where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){               
            $datos['PLAGA']=$result; 

           $stmt = $con->prepare("select * from afecta_insecto where inse_id in(select inse_id from Insecto where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                        
           if(!empty($result)){               
              $datos['AFECTA_PLAGA']=$result; 

              $stmt = $con->prepare("select * from tratamiento_insecto where inse_id in(select inse_id from Insecto where Usua_id= :usua_id)");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                   
              if(!empty($result)){// Se buscan los resultados obtenidos para ese tratamiento de enfermedad               
                 $datos['TRATA_PLAGA']=$result;

                 $stmt = $con->prepare("select * from resultados_trata_insecto where Trata_id in(select Trata_id from tratamiento_insecto where inse_id in(select Inse_id from Insecto where Usua_id= :usua_id))");
                 $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
                 $stmt->execute();
                 $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

                 if(!empty($result)){ //Se consultan los clientes              
                    $datos['RESULTADOS_PLAGA']=$result;
                 }   
                 else{
                   $datos['RESULTADOS_PLAGA']="TABLAVACIA";                  
                 }
              }
              else{
                $datos['TRATA_PLAGA']="TABLAVACIA";             
              }
           }
           else{
             $datos['AFECTA_PLAGA']="TABLAVACIA";        
           }
        }
        else{
          $datos['PLAGA']="TABLAVACIA";   
        }

        $stmt = $con->prepare("select * from cliente where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
        if(!empty($result)){ //Se consultan los clientes, si hay clientes pueden existir facturas y ventas         
           $datos['CLIENTE']=$result;

           $stmt = $con->prepare("select * from facturaventa where Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

           if(!empty($result)){ //Se consultan las facturas,si existen facturas se consultan ventas            
              $datos['FACTURA_VENTA']=$result;

              $stmt = $con->prepare("select * from ventacosecha where Cose_id in(select cose_id from cosecha where Culti_id in(select culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

              if(!empty($result)){ //Se consultan las ventas por cosecha
                 $datos['VENTA_COSECHA']=$result;
              }
              else{
                $datos['VENTA_COSECHA']="TABLAVACIA";          
              }
                        
              $stmt = $con->prepare("select * from ventainsumo where Fact_id in(select Fact_id from facturaventa where Clie_id in(select Clie_id from cliente where Usua_id= :usua_id))");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

              if(!empty($result)){ //Se consultan las ventas por insumo
                 $datos['VENTA_INSUMO']=$result;
              }
              else{
                $datos['VENTA_INSUMO']="TABLAVACIA";            
              }
            }
            else{
              $datos['FACTURA_VENTA']="TABLAVACIA";        
            }
          }
          else{
           $datos['CLIENTE']="TABLAVACIA";   
          }

       $stmt = $con->prepare("select * from proveedor where Usua_id= :usua_id");
       $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
       $stmt->execute();
       $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
       if(!empty($result)){ //Se consultan los proveedores, si existen proveedores existen facturas y ompras              
          $datos['PROVEEDOR']=$result;
                    
          $stmt = $con->prepare("select * from factura where Prove_id in(select Prove_id from proveedor where Usua_id= :usua_id)");
          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt->execute();
          $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

          if(!empty($result)){// Si existen facturas entonces existen compras
             $datos['FACTURA_COMPRA']=$result;  

             $stmt = $con->prepare("select * from compra where Fact_id in(select Fact_id from factura where Prove_id in(select Prove_id from proveedor where Usua_id= :usua_id))");
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                      
             if(!empty($result)){// Se consultan las compras
                $datos['COMPRAS']=$result;                        
             }
             else{
                $datos['COMPRAS']="TABLAVACIA";           
             }
           }
           else{
             $datos['FACTURA_COMPRA']="TABLAVACIA";       
           }
         } 
         else{
           $datos['PROVEEDOR']="TABLAVACIA";   
         }
                    
       $stmt = $con->prepare("select * from nomina where Usua_id= :usua_id");
       $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
       $stmt->execute();
       $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
       if(!empty($result)){// Se consultan las nominas registradas
          $datos['NOMINAS']=$result;
       }
       else{
         $datos['NOMINAS']="TABLAVACIA";   
       }

       $stmt = $con->prepare("select * from empleado where Usua_id= :usua_id");
       $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
       $stmt->execute();
       $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
        if(!empty($result)){// Se consultan los empleados, si hay empleados se consultan los pagos
           $datos['EMPLEADO']=$result;

           $stmt = $con->prepare("select * from pago where Emple_id in(select Emple_id from empleado where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

           if(!empty($result)){
             $datos['PAGOS']=$result;
           }
           else{
             $datos['PAGOS']="TABLAVACIA";    
           }
    
           //Si hay empleados puede que existan tareas
           // CONSULTA DE TAREAS
           $stmt = $con->prepare("select * from tarea where Usua_id= :usua_id");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

           if(!empty($result)){//Si hay tareas puede que se usen insumos
              $datos['TAREAS']=$result;
                     
              $stmt = $con->prepare("select * from utiliza_insum_tarea where Tarea_id in(select Tarea_id from tarea where Usua_id= :usua_id)");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                      
              if(!empty($result)){//Si hay tareas puede que se usen insumos
                 $datos['UTILIZA_INSUM_TAREA']=$result;
              }
              else{
                 $datos['UTILIZA_INSUM_TAREA']="TABLAVACIA";         
              }
           } 
           else{
             $datos['TAREAS']="TABLAVACIA";        
           }
        }
        else{
           $datos['EMPLEADO']="TABLAVACIA";    
        }

       //Se consultan las estaciones meteorologicas registradas
       $stmt = $con->prepare("select * from estacion_meteorologica where Parce_id in(select parce_id from parcela where Usua_id= :usua_id)");
       $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
       $stmt->execute();
       $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

       if(!empty($result)){//Si existen estaciones meterologicas registradas hay mediciones
          $datos['ESTACION']=$result;

          $stmt = $con->prepare("select * from medicionmeteorologica where Estacion_id in(select Estacion_id from estacion_meteorologica where Parce_id in(select parce_id from parcela where Usua_id= :usua_id))");
          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt->execute();
          $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

          if(!empty($result)){//Mediciones meteorologicas
            $datos['MEDICION_METEO']=$result;
          }    
          else{
            $datos['MEDICION_METEO']="TABLAVACIA";          
          }
      }
      else{
        $datos['ESTACION']="TABLAVACIA";    
      }

      //Se consultan los eventos del calendario
        $stmt = $con->prepare("select * from evento where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){//Eventos
           $datos['EVENTOS']=$result;
        }
        else{
           $datos['EVENTOS']="TABLAVACIA";        
        }

        //Se consultan alertas programadas
        $stmt = $con->prepare("select * from alertapro where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){//Alerta Programada, si existe existen alertas meteorologicas y por medicion
           $datos['ALERTAS']=$result;

           $stmt = $con->prepare("select * from alertaactiva_sensor where AlerPro_id in(select AlerPro_id from alertapro where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

           if(!empty($result)){//Alerta Programada, si existe existen alertas meteorologicas y por medicion
              $datos['ALERTA_SENSOR']=$result;
           }
           else{
              $datos['ALERTA_SENSOR']="TABLAVACIA";   
           }

          $stmt = $con->prepare("select * from alertaactiva_meteorologia where AlerPro_id in(select AlerPro_id from alertapro where Usua_id= :usua_id)");
          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt->execute();
          $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

          if(!empty($result)){//Alerta Programada, si existe existen alertas meteorologicas y por medicion
            $datos['ALERTA_ESTACION']=$result;
          }
         else{
            $datos['ALERTA_ESTACION']="TABLAVACIA";   
         }
        }  
        else{
          $datos['ALERTAS']="TABLAVACIA";   
        }
        echo json_encode($datos); 
       }   
       else{
         echo 'NOK'; //USUARIO NO REGISTRADO, LOGIN ERROR
       }
      }
      catch(Exception $e)
      {
        echo 'Error conectando con la base de datos, sincronizacion nuevo usuario: ' . $e->getMessage();
      }
  }

  function Verifica_Existencia($tabla,$campo1,$Usuario) {
    $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
    $cone = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
    $cone->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if($tabla=='PARCELA'){
       $stmt = $cone->prepare("select Usua_id from parcela where Parce_nombre= :parce_nombre AND Usua_id= :usua_id");
       $stmt->bindParam(':parce_nombre',$campo1, PDO::PARAM_INT);
       $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
       $stmt->execute();
       $results=$stmt->fetch();

       if($results){
          return true;
       }else{
          return false;
       }
    }
    else if($tabla=='CULTIVO'){
       $stmt = $cone->prepare("select Parce_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
       $stmt->bindParam(':culti_nombre',$campo1, PDO::PARAM_INT);
       $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
       $stmt->execute();
       $results=$stmt->fetch();

       //$GLOBALS['PARCELA_ID']=$results[0];

       if($results){
          return true;
       }else{
          return false;
       }
    }
    else if($tabla=='RASTA'){
       $stmt = $cone->prepare("select Rasta_id from resultados_rasta where Rasta_nombre= :rasta_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
       $stmt->bindParam(':rasta_nombre',$campo1, PDO::PARAM_INT);
       $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
       $stmt->execute();
       $results=$stmt->fetch();

       if($results){
          return true;
       }else{
          return false;
       }
    }
    else if($tabla=='NODOS'){
       $stmt = $cone->prepare("select Sens_id from nodosensor where Sens_nombre= :sens_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
       $stmt->bindParam(':sens_nombre',$campo1, PDO::PARAM_INT);
       $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
       $stmt->execute();
       $results=$stmt->fetch();

       if($results){
          return true;
       }else{
          return false;
       }
    }
    else if($tabla=='COSECHAS'){
       $stmt = $cone->prepare("select Cose_id from cosecha where Cose_nombre= :cose_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
       $stmt->bindParam(':cose_nombre',$campo1, PDO::PARAM_INT);
       $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
       $stmt->execute();
       $results=$stmt->fetch();

       if($results){
          return true;
       }else{
          return false;
       }
    }
    else if($tabla=='INSUMOS'){
      $stmt = $cone->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
      $stmt->bindParam(':insum_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='ENFERMEDADES'){
      $stmt = $cone->prepare("select Enfe_id from enfermedad where Enfe_nombre= :enfe_nombre AND Usua_id= :usua_id");
      $stmt->bindParam(':enfe_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='MALEZA'){
      $stmt = $cone->prepare("select Male_id from maleza where Male_nombre= :male_nombre AND Usua_id= :usua_id");
      $stmt->bindParam(':male_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='RESULTADOS_MALEZA'){
      $stmt = $cone->prepare("select Result_id from resultados_trata_maleza where Trata_id in(select Trata_id from tratamiento_maleza where Trata_Male_nombre= :trata_nombre AND Male_id in(select Male_id from maleza where Usua_id= :usua_id))");
      $stmt->bindParam(':trata_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='PLAGA'){
      $stmt = $cone->prepare("select Inse_id from Insecto where Inse_nombre= :inse_nombre AND Usua_id= :usua_id");
      $stmt->bindParam(':inse_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='RESULTADOS_PLAGA'){
      $stmt = $cone->prepare("select Result_id from resultados_trata_insecto where Trata_id in(select Trata_id from tratamiento_insecto where Trata_Inse_nombre= :trata_nombre AND Inse_id in(select Inse_id from Insecto where Usua_id= :usua_id))");
      $stmt->bindParam(':trata_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='CLIENTE'){
      $stmt = $cone->prepare("select Clie_id from cliente where Clie_identificacion=:clie_identificacion AND Usua_id= :usua_id");
      $stmt->bindParam(':clie_identificacion',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='PROVEEDOR'){
      $stmt = $cone->prepare("select Prove_id from proveedor where Prove_nombre= :prove_nombre AND Usua_id= :usua_id");
      $stmt->bindParam(':prove_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='NOMINAS'){
      $stmt = $cone->prepare("select Nom_id from nomina where Nom_nombre= :nom_nombre AND Usua_id= :usua_id");
      $stmt->bindParam(':nom_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='EMPLEADO'){
      $stmt = $cone->prepare("select Emple_id from empleado where Emple_identificacion= :emple_identificacion AND Usua_id= :usua_id");
      $stmt->bindParam(':emple_identificacion',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='TAREAS'){
      $stmt = $cone->prepare("select Tarea_id from tarea where Tarea_nombre= :tarea_nombre AND Usua_id= :usua_id");
      $stmt->bindParam(':tarea_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='ESTACION'){
      $stmt = $cone->prepare("select estacion_id from estacion_meteorologica where estacion_id= :estacion_id");
      $stmt->bindParam(':estacion_id',$campo1, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='EVENTOS'){
      $stmt = $cone->prepare("select Evt_id from evento where Evt_nombre= :evt_nombre AND Usua_id= :usua_id");
      $stmt->bindParam(':evt_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='ALERTAS'){
      $stmt = $cone->prepare("select AlerPro_id from alertapro where AlerPro_nombre= :alerpro_nombre AND Usua_id= :usua_id");
      $stmt->bindParam(':alerpro_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
  } 

  function Verifica_Existencia2($tabla,$campo1,$campo2,$Usuario) {
    $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
    $cone = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
    $cone->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if($tabla=='AFECTA_ENFERMEDADES'){
      $stmt = $cone->prepare("select Afec_id from afecta_enfermedad where Culti_id in(select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Enfe_id in(select Enfe_id from enfermedad where Enfe_nombre= :enfe_nombre AND Usua_id= :usua_id)");
      $stmt->bindParam(':enfe_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':culti_nombre',$campo2, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='TRATA_ENFERMEDADES'){
     $stmt = $cone->prepare("select Trata_id from tratamiento_enfermedad where Enfe_id in(select Enfe_id from enfermedad where Enfe_nombre= :enfe_nombre AND Usua_id= :usua_id) AND Insum_id in(select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id)");
      $stmt->bindParam(':enfe_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':insum_nombre',$campo2, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='RESULTADOS_ENFERMEDADES'){ 
      $stmt = $cone->prepare("select Result_id from resultados_trata_enfermedad where Trata_id in(select Trata_id from tratamiento_enfermedad where Trata_Enfe_nombre= :trata_nombre AND Enfe_id in(select Enfe_id from enfermedad where Usua_id= :usua_id)) AND Result_nombre=:result_nombre");
      $stmt->bindParam(':trata_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':result_nombre',$campo2, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();      
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='AFECTA_MALEZA'){
      $stmt = $cone->prepare("select Afec_id from afecta_maleza where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Male_id in(select Male_id from maleza where Male_nombre= :male_nombre AND Usua_id= :usua_id)");
      $stmt->bindParam(':male_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':culti_nombre',$campo2, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='TRATA_MALEZA'){
     $stmt = $cone->prepare("select Trata_id from tratamiento_maleza where Male_id in(select Male_id from maleza where Male_nombre= :male_nombre AND Usua_id= :usua_id) AND Insum_id in(select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id)");
      $stmt->bindParam(':male_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':insum_nombre',$campo2, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='AFECTA_PLAGA'){
      $stmt = $cone->prepare("select Afec_id from afecta_insecto where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Inse_id in(select Inse_id from Insecto where Inse_nombre= :inse_nombre AND Usua_id= :usua_id)");
      $stmt->bindParam(':inse_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':culti_nombre',$campo2, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='TRATA_PLAGA'){/////////////////////////////////////////////////////
     $stmt = $cone->prepare("select Trata_id from tratamiento_insecto where Inse_id in(select Inse_id from Insecto where Inse_nombre= :inse_nombre AND Usua_id= :usua_id) AND Insum_id in(select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id)");
      $stmt->bindParam(':inse_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':insum_nombre',$campo2, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='VENTA_COSECHA'){
      $stmt = $cone->prepare("select VentaCose_id from ventacosecha where Fact_id in(select Fact_id from facturaventa where Fact_nombre= :fact_nombre AND Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)) AND Cose_id in(select Cose_id from cosecha where Cose_nombre= :cose_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))");
      $stmt->bindParam(':fact_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':cose_nombre',$campo2, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='VENTA_INSUMO'){
      $stmt = $cone->prepare("select VentaInsu_id from ventainsumo where Fact_id in(select Fact_id from facturaventa where Fact_nombre= :fact_nombre AND Clie_id in(select Clie_id from cliente where Usua_id= :usua_id)) AND Insum_id in(select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id)");
      $stmt->bindParam(':fact_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':insum_nombre',$campo2, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='FACTURA_VENTA'){
      $stmt = $cone->prepare("select Fact_id from facturaventa where Clie_id in(select Clie_id from cliente where Clie_identificacion= :clie_identificacion AND Usua_id= :usua_id) AND Fact_nombre= :fact_nombre");
      $stmt->bindParam(':clie_identificacion',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->bindParam(':fact_nombre',$campo2, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='COMPRAS'){
      $stmt = $cone->prepare("select Comp_id from compra where Fact_id in(select Fact_id from factura where Fact_nombre= :fact_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))) AND Insum_id in(select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id)");
      $stmt->bindParam(':insum_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->bindParam(':fact_nombre',$campo2, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='PAGOS'){
      $stmt = $cone->prepare("select Pago_id from pago where Nom_id in(select Nom_id from nomina where Nom_nombre= :nom_nombre AND Usua_id= :usua_id) AND Emple_id in(select Emple_id from empleado where Emple_identificacion= :emple_identificacion  AND Usua_id= :usua_id)");
      $stmt->bindParam(':nom_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->bindParam(':emple_identificacion',$campo2, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='UTILIZA_INSUM_TAREA'){
      $stmt = $cone->prepare("select LaborInsumo_id from utiliza_insum_tarea where Insum_id in(select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id) AND Tarea_id in(select Tarea_id from tarea where Tarea_nombre= :tarea_nombre  AND Usua_id= :usua_id)");
      $stmt->bindParam(':insum_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->bindParam(':tarea_nombre',$campo2, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
  }

  function Verifica_Existencia3($tabla,$campo1,$campo2,$campo3,$Usuario) {
    $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
    $cone = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
    $cone->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if($tabla=='FACTURA_COMPRA'){
      $stmt = $cone->prepare("select Fact_id from factura where Prove_id in(select Prove_id from proveedor where Prove_nombre= :prove_nombre AND Usua_id= :usua_id) AND Culti_id in(select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Fact_nombre= :fact_nombre");
      $stmt->bindParam(':prove_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':culti_nombre',$campo2, PDO::PARAM_INT);
      $stmt->bindParam(':fact_nombre',$campo3, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='ALERTA_SENSOR'){
      $stmt = $cone->prepare("select AlerActiv_id from alertaactiva_sensor where AlerPro_id in(select AlerPro_id from alertapro where AlerPro_nombre= :alerpro_nombre AND Usua_id= :usua_id) AND MediSens_id in(select MediSens_id from medicionsensor where MediSens_fecha= :medisens_fecha AND MediSens_hora= :medisens_hora AND Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))))");
      $stmt->bindParam(':alerpro_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':medisens_fecha',$campo2, PDO::PARAM_INT);
      $stmt->bindParam(':medisens_hora',$campo3, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    }
    else if($tabla=='ALERTA_ESTACION'){
      $stmt = $cone->prepare("select AlerActiv_id from alertaactiva_meteorologia where AlerPro_id in(select AlerPro_id from alertapro where AlerPro_nombre= :alerpro_nombre AND Usua_id= :usua_id) AND MediMete_id in(select MediMete_id from medicionmeteorologica where MediMete_fecha= :medimete_fecha AND MediMete_hora= :medimete_hora AND Estacion_id in(select Estacion_id from estacion_meteorologica where Parce_id in (select Parce_id from parcela where Usua_id= :usua_id)))");
      $stmt->bindParam(':alerpro_nombre',$campo1, PDO::PARAM_INT);
      $stmt->bindParam(':medimete_fecha',$campo2, PDO::PARAM_INT);
      $stmt->bindParam(':medimete_hora',$campo3, PDO::PARAM_INT);
      $stmt->bindParam(':usua_id',$Usuario, PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetch();

      if($results){
         return true;
      }else{
         return false;
      }
    } 
  }
?>