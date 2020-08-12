<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Max-Age: 1000');
header("Content-Type: text/html;charset=utf-8");

   if(isset($_POST['login'])){
    try
    {
      $se=json_decode(stripslashes($_POST['login']));
      $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
      $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
      $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      //Si la estacion meteorologica esta registrada consulta la ultima fecha y hora de mediciones
      $stmt = $con->prepare("select Usua_id,Usua_identificacion,Usua_name,Usua_fechaRegistro,Usua_ultimaVisita,Usua_nombre,Usua_apellido,Usua_telefono,Usua_celular,Usua_direccion,Usua_departamento,Usua_ciudad,Usua_correo from usuario where Usua_name= :email AND Usua_contrasena = md5(:pass)");
      $stmt->bindParam(':email',$se->email, PDO::PARAM_INT);
      $stmt->bindParam(':pass',md5($se->pass), PDO::PARAM_INT);
      $stmt->execute();
      $results=$stmt->fetchAll(PDO::FETCH_ASSOC);


      if(!empty($results)){
         
         $json=json_encode($results);
         echo $json;  //Envia ultima hora y fecha guardadas en la base de datos
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
  else if(isset($_POST["Nuevo_UsuarioMOVIL"])){
    try{
        $se=json_decode(stripslashes($_POST["Nuevo_UsuarioMOVIL"]));
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
           }

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
           }
 
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
           }

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

        $stmt = $con->prepare("select * from cliente where Usua_id= :usua_id");
        $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
        if(!empty($result)){ //Se consultan los clientes, si hay clientes pueden existir facturas y ventas         
           $datos['CLIENTE']=$result;
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
           $stmt = $con->prepare("select * from tarea where Emple_id in(select Emple_id from empleado where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

           if(!empty($result)){//Si hay tareas puede que se usen insumos
              $datos['TAREAS']=$result;
                     
              $stmt = $con->prepare("select * from utiliza_insum_tarea where Tarea_id in(select Tarea_id from tarea where Usua_id=:usua_id)");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                      
              if(!empty($result)){//Si hay tareas puede que se usen insumos
                 $datos['UTILIZA_INSUM_TAREA']=$result;
              }
              
              $stmt = $con->prepare("select * from realiza_emplea_tarea_cultivo where Tarea_id in(select Tarea_id from tarea where Usua_id=:usua_id) AND Culti_id IN(select Culti_id from cultivo where Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id))");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                      
              if(!empty($result)){//Si hay tareas puede que se usen insumos
                 $datos['REALIZA_EMPLEA_TAREA_CULTIVO']=$result;
              }

              $stmt = $con->prepare("select * from realiza_emplea_tarea_cosecha where Tarea_id in(select Tarea_id from tarea where Usua_id= :usua_id) AND Cose_id IN(select Cose_id from cosecha where Culti_id IN(select Culti_id from cultivo where Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id)))");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                      
              if(!empty($result)){//Si hay tareas puede que se usen insumos
                 $datos['REALIZA_EMPLEA_TAREA_COSECHA']=$result;
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
       $usua_id =$se->USUA_ID;

        if($usua_id!='' && $usua_id!==NULL){

        $Array = json_decode($se->PARCELA,true);
        $Array2 = json_decode($se->CULTIVO,true);
        $Array4 = json_decode($se->NODOS,true);
        $Array44 = json_decode($se->MEDICIONES,true);
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
        $Array23 = json_decode($se->PROVEEDOR,true);
        $Array24 = json_decode($se->FACTURA_COMPRA,true);
        $Array25 = json_decode($se->COMPRAS,true);
        $Array27 = json_decode($se->EMPLEADO,true);
        $Array29 = json_decode($se->TAREAS,true);
        $Array30 = json_decode($se->UTILIZA_INSUM_TAREA,true);
        $Array31 = json_decode($se->ESTACION,true);
        $Array32 = json_decode($se->MEDICION_METEO,true);
        $Array33 = json_decode($se->EVENTOS,true);
        $Array34 = json_decode($se->ALERTAS,true);
        $Array35 = json_decode($se->ALERTA_SENSOR,true);
        $Array36 = json_decode($se->ALERTA_ESTACION,true);
        $Array37 = json_decode($se->REALIZA_TAREA_COSECHA,true);
        $Array38 = json_decode($se->REALIZA_TAREA_CULTIVO,true);


       $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
       $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
       $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      if(!empty($Array)){
        try {
           if($Array[0]=="TABLAVACIA"){
           $stmt1 = $con->prepare('delete from parcela where Usua_id= :usua_id');
           $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt1->execute();
           }
        else{
           $auxiliar= array();
            for($i = 0; $i < count($Array); $i++) {
              $existencia=Verifica_Existencia("PARCELA",$Array[$i]['Parce_nombre'],$usua_id);

              if($existencia){ //Si existe actualize
               // echo 'existe';
                $stmt = $con->prepare("UPDATE parcela SET Parce_departamento=:Parce_departamento,Parce_ciudad=:Parce_ciudad,Parce_vereda=:Parce_vereda,Parce_avaluo=:Parce_avaluo,Parce_areaTerreno=:Parce_areaTerreno,Parce_unidad=:Parce_unidad,Parce_tipoTerreno=:Parce_tipoTerreno,Parce_promedioRendimiento=:Parce_promedioRendimiento,Parce_obsevaciones=:Parce_obsevaciones,Parce_fechaRegistro=:Parce_fechaRegistro,Parce_fechaActualizacion=:Parce_fechaActualizacion where Parce_nombre=:Parce_nombre AND Usua_id= :Usua_id");
                $stmt->bindParam(':Parce_nombre',$Array[$i]['Parce_nombre'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_departamento',$Array[$i]['Parce_departamento'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_ciudad',$Array[$i]['Parce_ciudad'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_vereda',$Array[$i]['Parce_vereda'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_avaluo',$Array[$i]['Parce_avaluo'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_areaTerreno',$Array[$i]['Parce_areaTerreno'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_unidad',$Array[$i]['Parce_unidad'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_tipoTerreno',$Array[$i]['Parce_tipoTerreno'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_promedioRendimiento',$Array[$i]['Parce_promedioRendimiento'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_obsevaciones',$Array[$i]['Parce_obsevaciones'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_fechaRegistro',$Array[$i]['Parce_fechaRegistro'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_fechaActualizacion',$Array[$i]['Parce_fechaActualizacion'], PDO::PARAM_INT);
                $stmt->bindParam(':Usua_id',$Array[$i]['Usua_id'], PDO::PARAM_INT);
                $stmt->execute();
              }
              else{ //Sino existe inserte
               // echo 'no existe';
               $stmt = $con->prepare("INSERT INTO parcela (Parce_id,Parce_nombre,Parce_departamento,Parce_ciudad,Parce_vereda,Parce_avaluo,Parce_areaTerreno,Parce_unidad,Parce_tipoTerreno,Parce_promedioRendimiento,Parce_obsevaciones,Parce_fechaRegistro,Parce_fechaActualizacion,Usua_id) VALUES(null,:Parce_nombre,:Parce_departamento,:Parce_ciudad,:Parce_vereda,:Parce_avaluo,:Parce_areaTerreno,:Parce_unidad,:Parce_tipoTerreno,:Parce_promedioRendimiento,:Parce_obsevaciones,:Parce_fechaRegistro,:Parce_fechaActualizacion,:Usua_id)");

               $stmt->bindParam(':Parce_nombre',$Array[$i]['Parce_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_departamento',$Array[$i]['Parce_departamento'], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_ciudad',$Array[$i]['Parce_ciudad'], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_vereda',$Array[$i]['Parce_vereda'], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_avaluo',$Array[$i]['Parce_avaluo'], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_areaTerreno',$Array[$i]['Parce_areaTerreno'], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_unidad',$Array[$i]['Parce_unidad'], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_tipoTerreno',$Array[$i]['Parce_tipoTerreno'], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_promedioRendimiento',$Array[$i]['Parce_promedioRendimiento'], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_obsevaciones',$Array[$i]['Parce_obsevaciones'], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_fechaRegistro',$Array[$i]['Parce_fechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':Parce_fechaActualizacion',$Array[$i]['Parce_fechaActualizacion'], PDO::PARAM_INT);
               $stmt->bindParam(':Usua_id',$Array[$i]['Usua_id'], PDO::PARAM_INT);
               $stmt->execute();
               //echo 'Ultimo id '.$con->lastInsertId($stmt);
               //echo 'Ok';
            } 
            $var='"'.$Array[$i]['Parce_nombre'].'"';
            array_push($auxiliar,$var);
          }

          // Se procede a borrar las parcelas que no venian de la aplicacion de escritorio
          $statut = implode(',',$auxiliar);
          $stmt1 = $con->prepare('delete from parcela where Parce_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
          $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt1->execute();
          echo 9;
          }
         } 
         catch(Exception $e) { 
           echo 'Error conectando con la base de datos: PARCELA' . $e->getMessage();
           $stmt->rollback(); 
        } 
      }
      
      if(!empty($Array2)){
        try{
         if($Array2[0]=="TABLAVACIA"){
          $stmt1 = $con->prepare('delete from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)');
          $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt1->execute();
           }
        else{
         $auxiliar1= array();
         for($i = 0; $i < count($Array2); $i++) {
          $existencia=Verifica_Existencia("CULTIVO",$Array2[$i]['Culti_nombre'],$usua_id);
        
          if($existencia){
             $stmt = $con->prepare("select Parce_id from parcela where Parce_nombre= :parce_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':parce_nombre',$Array2[$i]['Parce_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
                $id_parcela=$results[0];
                $stmt = $con->prepare("UPDATE cultivo SET Culti_puntoSiembra=:Culti_puntoSiembra,Culti_duracion=:Culti_duracion,Culti_inversion=:Culti_inversion,Culti_estado=:Culti_estado,Culti_fecha=:Culti_fecha,Culti_fechaActualizacion=:Culti_fechaActualizacion,Culti_foto=:Culti_foto,Culti_area=:Culti_area,Culti_medida=:Culti_medida,Culti_observaciones=:Culti_observaciones,Culti_especie=:Culti_especie,Parce_id=:Parce_id WHERE Culti_nombre=:Culti_nombre AND Parce_id IN(SELECT Parce_id from parcela where Usua_id=:usua_id)");

                $stmt->bindParam(':Culti_nombre',$Array2[$i]['Culti_nombre'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_puntoSiembra',$Array2[$i]['Culti_puntoSiembra'][$i], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_duracion',$Array2[$i]['Culti_duracion'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_inversion',$Array2[$i]['Culti_inversion'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_estado',$Array2[$i]['Culti_estado'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_fecha',$Array2[$i]['Culti_fechaRegistro'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_fechaActualizacion',$Array2[$i]['Culti_UltimaActualizacion'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_foto',$Array2[$i]['Culti_foto'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_area',$Array2[$i]['Culti_area'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_medida',$Array2[$i]['Culti_medida'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_observaciones',$Array2[$i]['Culti_observaciones'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_especie',$Array2[$i]['Culti_especie'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_id',$id_parcela, PDO::PARAM_INT);
                $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
                $stmt->execute();
              }
          }
          else{ //Sino existe inserte     
             $stmt = $con->prepare("select Parce_id from parcela where Parce_nombre= :parce_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':parce_nombre',$Array2[$i]['Parce_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
                $id_parcela=$results[0];
                $stmt = $con->prepare("INSERT INTO cultivo (Culti_id,Culti_nombre,Culti_puntoSiembra,Culti_duracion,Culti_inversion,Culti_estado,Culti_fecha,Culti_fechaActualizacion,Culti_foto,Culti_area,Culti_medida,Culti_observaciones,Culti_especie,Parce_id) VALUES(null,:Culti_nombre,:Culti_puntoSiembra,:Culti_duracion,:Culti_inversion,:Culti_estado,:Culti_fecha,:Culti_fechaActualizacion,:Culti_foto,:Culti_area,:Culti_medida,:Culti_observaciones,:Culti_especie,:Parce_id)");

                $stmt->bindParam(':Culti_nombre',$Array2[$i]['Culti_nombre'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_puntoSiembra',$Array2[$i]['Culti_puntoSiembra'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_duracion',$Array2[$i]['Culti_duracion'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_inversion',$Array2[$i]['Culti_inversion'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_estado',$Array2[$i]['Culti_estado'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_fecha',$Array2[$i]['Culti_fechaRegistro'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_fechaActualizacion',$Array2[$i]['Culti_UltimaActualizacion'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_foto',$Array2[$i]['Culti_foto'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_area',$Array2[$i]['Culti_area'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_medida',$Array2[$i]['Culti_medida'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_observaciones',$Array2[$i]['Culti_observaciones'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_especie',$Array2[$i]['Culti_especie'], PDO::PARAM_INT);
                $stmt->bindParam(':Parce_id',$id_parcela, PDO::PARAM_INT);
                $stmt->execute();
             }
           }
            $var='"'.$Array2[$i]['Culti_nombre'].'"';
            array_push($auxiliar1,$var);
         }
          $statut = implode(',',$auxiliar1);
          $stmt1 = $con->prepare('delete from cultivo where Culti_nombre NOT IN('.$statut.') AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)');
          $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt1->execute();
          echo 9;
           }
         } 
         catch(Exception $e) { 
           echo 'Error conectando con la base de datos: CULTIVO ' . $e->getMessage();
           $stmt->rollback(); 
        } 
      }
       
      if(!empty($Array4)){
        try{ 
        if($Array4[0]=="TABLAVACIA"){
           $stmt1 = $con->prepare('delete from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
           $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt1->execute();
        }
        else{
         $auxiliar= array();
         for($i = 0; $i < count($Array4); $i++) {

          $existencia=Verifica_Existencia("NODOS",$Array4[$i]['Sens_Mac'],$usua_id);

          $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id IN(select Parce_id from parcela where Usua_id= :usua_id)");
          $stmt->bindParam(':culti_nombre',$Array4[$i]['Culti_nombre'], PDO::PARAM_INT);
          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt->execute();
          $results=$stmt->fetch();
        
          if($existencia){
               if($results){
                $id_cultivo=$results[0];

                $stmt = $con->prepare("UPDATE nodosensor SET Sens_nombre=:Sens_nombre,Sens_estado=:Sens_estado,Sens_PanId=:Sens_PanId,Sens_fechaRegistro=:Sens_fechaRegistro,Sens_fechaActualizacion=:Sens_fechaActualizacion,Culti_id=:Culti_id WHERE Sens_Mac=:Sens_Mac AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id=:usua_id))");

                $stmt->bindParam(':Sens_nombre',$Array4[$i]['Sens_nombre'], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_estado',$Array4[$i]['Sens_estado'], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_Mac',$Array4[$i]['Sens_Mac'], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_PanId',$Array4[$i]['Sens_PanId'], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_fechaRegistro',$Array4[$i]['Sens_fechaRegistro'], PDO::PARAM_INT);
                $stmt->bindParam(':Sens_fechaActualizacion',$Array4[$i]['Sens_fechaActualizacion'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
                $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
                $stmt->execute();
              }
          }
          else{ //Sino existe inserte  
              // $stmt = $con->prepare("SELECT Sens_id from nodosensor WHERE Sens_mac=:sens_mac AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
              // $stmt->bindParam(':sens_nombre',$Array4[$i]['Sens_mac'], PDO::PARAM_INT);
              // $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              // $stmt->execute();
              // $results=$stmt->fetch();
              if($results){
              $id_cultivo=$results[0];

              $stmt = $con->prepare("INSERT INTO nodosensor VALUES(null,:Sens_nombre,:Sens_estado,:Sens_Mac,:Sens_PanId,:Sens_fechaRegistro,:Sens_fechaActualizacion,:Culti_id)");
              $stmt->bindParam(':Sens_nombre',$Array4[$i]['Sens_nombre'], PDO::PARAM_INT);
              $stmt->bindParam(':Sens_estado',$Array4[$i]['Sens_estado'], PDO::PARAM_INT);
              $stmt->bindParam(':Sens_Mac',$Array4[$i]['Sens_Mac'], PDO::PARAM_INT);
              $stmt->bindParam(':Sens_PanId',$Array4[$i]['Sens_PanId'], PDO::PARAM_INT);
              $stmt->bindParam(':Sens_fechaRegistro',$Array4[$i]['Sens_fechaRegistro'], PDO::PARAM_INT);
              $stmt->bindParam(':Sens_fechaActualizacion',$Array4[$i]['Sens_fechaActualizacion'], PDO::PARAM_INT);
              $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
              $stmt->execute();
              //$id_ultimo=$stmt->lastInsertId(); 
             }
              // if($results){
              //    $id_sensor=$results[0];
              //    $stmt = $con->prepare("UPDATE medicionsensor SET Sens_id=:Sens_id WHERE Sens_id=:sens_id");
              //    $stmt->bindParam(':sens_id',$id_sensor, PDO::PARAM_INT);
              //    $stmt->bindParam(':Sens_id',$id_ultimo, PDO::PARAM_INT);
              // }
           }
            $var='"'.$Array4[$i]['Sens_nombre'].'"';
            array_push($auxiliar,$var);
           }
           $statut = implode(',',$auxiliar);
           $stmt1 = $con->prepare('delete from nodosensor where Sens_nombre NOT IN('.$statut.') AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
           $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt1->execute();
           echo 9;
         }
        }
        catch(Exception $e) { 
         echo 'Error conectando con la base de datos: NODO SENSOR ' . $e->getMessage();
         $stmt->rollback(); 
        } 
       }

      if(!empty($Array44)){
        echo 9;
         // try{
         //    if($Array44[0]=="TABLAVACIA"){
         //    $stmt1 = $con->prepare('delete from medicionsensor where Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))');
         //    $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
         //    $stmt1->execute();
         //   }
         //  else{
         //    $auxiliar= array();
         //    $auxiliar1= array();
         //     for($i = 0; $i < count($Array44); $i++) {
         //      //Verifica si la medicion existe
         //       $stmt = $con->prepare("select Sens_id from medicionsensor where MediSens_fecha=:medisens_fecha AND MediSens_hora=:medisens_hora");
         //       $stmt->bindParam(':medisens_fecha',$Array44[$i]['MediSens_fecha'], PDO::PARAM_INT);
         //       $stmt->bindParam(':medisens_hora',$Array44[$i]['MediSens_hora'], PDO::PARAM_INT);
         //       $stmt->execute();
         //       $result=$stmt->fetch();

         //       if($result){//si existe actualice
         //          $stmt = $con->prepare("select Sens_id from nodosensor where Sens_nombre=:sens_nombre AND Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))");
         //          $stmt->bindParam(':sens_nombre',$Array44[$i]['Sens_nombre'], PDO::PARAM_INT);
         //          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
         //          $stmt->execute();
         //          $results=$stmt->fetch();
                 
         //          if($results){
         //            $id_sensor=$results[0];

         //            $stmt = $con->prepare("UPDATE medicionsensor SET Sens_id=:Sens_id WHERE MediSens_fecha=:medisens_fecha AND MediSens_hora=:medisens_hora");
         //            $stmt->bindParam(':Sens_id',$id_sensor, PDO::PARAM_INT);
         //            $stmt->bindParam(':medisens_fecha',$Array44[$i]['MediSens_fecha'], PDO::PARAM_INT);
         //            $stmt->bindParam(':medisens_hora',$Array44[$i]['MediSens_hora'], PDO::PARAM_INT);
         //            $stmt->execute();
         //          }
         //       }
         //       else{//sino inserte
         //          $stmt = $con->prepare("select Sens_id from nodosensor where Sens_nombre=:sens_nombre AND Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))");
         //          $stmt->bindParam(':sens_nombre',$Array44[$i]['Sens_nombre'], PDO::PARAM_INT);
         //          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
         //          $stmt->execute();
         //          $results=$stmt->fetch();
                 
         //          if($results){
         //            $id_sensor=$results[0];

         //            $stmt = $con->prepare("INSERT INTO medicionsensor VALUES(null,:MediSens_temperatura,:MediSens_humedad,:MediSens_voltaje,:MediSens_fecha,:MediSens_hora,:Sens_id)");
         //            $stmt->bindParam(':MediSens_temperatura',$Array44[$i]['MediSens_temperatura'], PDO::PARAM_INT);
         //            $stmt->bindParam(':MediSens_humedad',$Array44[$i]['MediSens_humedad'], PDO::PARAM_INT);
         //            $stmt->bindParam(':MediSens_voltaje',$Array44[$i]['MediSens_voltaje'], PDO::PARAM_INT);
         //            $stmt->bindParam(':MediSens_fecha',$Array44[$i]['MediSens_fecha'], PDO::PARAM_INT);
         //            $stmt->bindParam(':MediSens_hora',$Array44[$i]['MediSens_hora'], PDO::PARAM_INT);
         //            $stmt->bindParam(':Sens_id',$id_sensor, PDO::PARAM_INT);
         //            $stmt->execute();
         //          }
         //       }
         //         $var='"'.$Array44[$i]['MediSens_fecha'].'"';
         //         $var1='"'.$Array44[$i]['MediSens_hora'].'"';
         //         array_push($auxiliar,$var);
         //         array_push($auxiliar1,$var1);
         //       }
         //         $statut = implode(',',$auxiliar);
         //         $statut1 = implode(',',$auxiliar1);
         //         $stmt1 = $con->prepare('delete from medicionsensor where MediSens_fecha NOT IN('.$statut.') AND MediSens_hora NOT IN('.$statut1.') AND Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))');
         //         $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
         //         $stmt1->execute();
         //         echo 9;
         //      } 
         //    }
         //    catch(Exception $e) { 
         //      echo 'Error conectando con la base de datos MEDICION_SENSOR: ' . $e->getMessage();
         //      $stmt->rollback(); 
         //    }
       } 

      if(!empty($Array5)){
       try{
        if($Array5[0]=="TABLAVACIA"){
           $stmt1 = $con->prepare('delete from cosecha where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
           $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt1->execute();
        }
        else{
         $auxiliar= array();
         for($i = 0; $i < count($Array5); $i++) {
          $existencia=Verifica_Existencia("COSECHAS",$Array5[$i]['Cose_nombre'],$usua_id);

           $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id IN(select Parce_id from parcela where Usua_id= :usua_id)");
          $stmt->bindParam(':culti_nombre',$Array5[$i]['Culti_nombre'], PDO::PARAM_INT);
          $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt->execute();
          $results=$stmt->fetch();

          if($existencia){
            if($results){
             $id_cultivo=$results[0];
             $stmt = $con->prepare("UPDATE cosecha SET Cose_volumenRecoleccion=:Cose_volumenRecoleccion,Cose_cantidad=:Cose_cantidad,Cose_medida=:Cose_medida,Cose_Observacion=:Cose_Observacion,Cose_fecha=:Cose_fecha,Cose_fechaActualizacion=:Cose_fechaActualizacion,Cose_calificativo=:Cose_calificativo,Cose_rendimiento=:Cose_rendimiento,Cose_puntos=:Cose_puntos,Cose_foto=:Cose_foto,Culti_id=:Culti_id WHERE Cose_nombre=:Cose_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");

                $stmt->bindParam(':Cose_nombre',$Array5[$i]['Cose_nombre'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_volumenRecoleccion',$Array5[$i]['Cose_volumenRecoleccion'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_cantidad',$Array5[$i]['Cose_cantidad'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_medida',$Array5[$i]['Cose_medida'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_Observacion',$Array5[$i]['Cose_Observacion'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_fecha',$Array5[$i]['Cose_fecha'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_fechaActualizacion',$Array5[$i]['Cose_fechaActualizacion'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_calificativo',$Array5[$i]['Cose_calificativo'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_rendimiento',$Array5[$i]['Cose_rendimiento'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_puntos',$Array5[$i]['Cose_puntos'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_foto',$Array5[$i]['Cose_foto'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
                $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
                $stmt->execute();
              }
          }
          else{ //Sino existe inserte   
           if($results){
             $id_cultivo=$results[0]; 
                $stmt = $con->prepare("INSERT INTO cosecha VALUES(null,:Cose_nombre,:Cose_volumenRecoleccion,:Cose_cantidad,:Cose_medida,:Cose_Observacion,:Cose_fecha,:Cose_fechaActualizacion,:Cose_calificativo,:Cose_rendimiento,:Cose_puntos,:Cose_foto,:Culti_id)");

                $stmt->bindParam(':Cose_nombre',$Array5[$i]['Cose_nombre'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_volumenRecoleccion',$Array5[$i]['Cose_volumenRecoleccion'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_cantidad',$Array5[$i]['Cose_cantidad'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_medida',$Array5[$i]['Cose_medida'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_Observacion',$Array5[$i]['Cose_Observacion'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_fecha',$Array5[$i]['Cose_fecha'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_fechaActualizacion',$Array5[$i]['Cose_fechaActualizacion'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_calificativo',$Array5[$i]['Cose_calificativo'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_rendimiento',$Array5[$i]['Cose_rendimiento'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_puntos',$Array5[$i]['Cose_puntos'], PDO::PARAM_INT);
                $stmt->bindParam(':Cose_foto',$Array5[$i]['Cose_foto'], PDO::PARAM_INT);
                $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
                $stmt->execute();
              }
           }
            $var='"'.$Array5[$i]['Cose_nombre'].'"';
            array_push($auxiliar,$var);
         }
          $statut = implode(',',$auxiliar);
          $stmt1 = $con->prepare('delete from cosecha where Cose_nombre NOT IN('.$statut.') AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
          $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
          $stmt1->execute();
          echo 9;
          } 
        }
         catch(Exception $e) { 
           echo 'Error conectando con la base de datos COSECHAS: ' . $e->getMessage();
           $stmt->rollback(); 
        } 
       }

      if(!empty($Array6)){
         try{
            if($Array6[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from insumo where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            for($i = 0; $i < count($Array6); $i++) {
            $existencia=Verifica_Existencia("INSUMOS",$Array6[$i]['Insum_nombre'],$usua_id);
        
            if($existencia){
               $stmt = $con->prepare("UPDATE insumo SET Insum_valor=:Insum_valor,Insum_fecha=:Insum_fecha,Insum_fechaActualizacion=:Insum_fechaActualizacion,Insum_Cantidad=:Insum_Cantidad,Insum_medida=:Insum_medida,Insum_categoria=:Insum_categoria,Insum_funcion=:Insum_funcion,Insum_marca=:Insum_marca WHERE Insum_nombre=:Insum_nombre AND Usua_id=:Usua_id");

               $stmt->bindParam(':Insum_nombre',$Array6[$i]['Insum_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_valor',$Array6[$i]['Insum_valor'], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_fecha',$Array6[$i]['Insum_fecha'], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_fechaActualizacion',$Array6[$i]['Insum_fechaActualizacion'], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_Cantidad',$Array6[$i]['Insum_Cantidad'], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_medida',$Array6[$i]['Insum_medida'], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_categoria',$Array6[$i]['Insum_categoria'], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_funcion',$Array6[$i]['Insum_funcion'], PDO::PARAM_INT);
               $stmt->bindParam(':Insum_marca',$Array6[$i]['Insum_marca'], PDO::PARAM_INT);
               $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
               $stmt->execute();
            }
            else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO insumo VALUES(null,:Insum_nombre,:Insum_valor,:Insum_fecha,:Insum_fechaActualizacion,:Insum_Cantidad,:Insum_medida,:Insum_categoria,:Insum_funcion,:Insum_marca,:Usua_id)");

             $stmt->bindParam(':Insum_nombre',$Array6[$i]['Insum_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_valor',$Array6[$i]['Insum_valor'], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_fecha',$Array6[$i]['Insum_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_fechaActualizacion',$Array6[$i]['Insum_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_Cantidad',$Array6[$i]['Insum_Cantidad'], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_medida',$Array6[$i]['Insum_medida'], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_categoria',$Array6[$i]['Insum_categoria'], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_funcion',$Array6[$i]['Insum_funcion'], PDO::PARAM_INT);
             $stmt->bindParam(':Insum_marca',$Array6[$i]['Insum_marca'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             }
            $var='"'.$Array6[$i]['Insum_nombre'].'"';
            array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from insumo where Insum_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos INSUMOS: ' . $e->getMessage();
            $stmt->rollback(); 
          }
      }    

      if(!empty($Array7)){
         try{
            if($Array7[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from enfermedad where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            for($i = 0; $i < count($Array7); $i++) {
                $existencia=Verifica_Existencia("ENFERMEDADES",$Array7[$i]['Enfe_nombre'],$usua_id);
        
          if($existencia){
             $stmt = $con->prepare("UPDATE enfermedad SET Enfe_nombreCientifico=:Enfe_nombreCientifico,Enfe_riesgo=:Enfe_riesgo,Enfe_foto=:Enfe_foto,Enfe_tratamiento=:Enfe_tratamiento,Enfe_fecha=:Enfe_fecha,Enfe_fechaActualizacion=:Enfe_fechaActualizacion,Enfe_obsevaciones=:Enfe_obsevaciones WHERE Enfe_nombre=:Enfe_nombre AND Usua_id=:Usua_id");

             $stmt->bindParam(':Enfe_nombre',$Array7[$i]['Enfe_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_nombreCientifico',$Array7[$i]['Enfe_nombreCientifico'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_riesgo',$Array7[$i]['Enfe_riesgo'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_foto',$Array7[$i]['Enfe_foto'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_tratamiento',$Array7[$i]['Enfe_tratamiento'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_fecha',$Array7[$i]['Enfe_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_fechaActualizacion',$Array7[$i]['Enfe_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_obsevaciones',$Array7[$i]['Enfe_obsevaciones'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO enfermedad VALUES(null,:Enfe_nombre,:Enfe_nombreCientifico,:Enfe_riesgo,:Enfe_foto,:Enfe_tratamiento,:Enfe_fecha,:Enfe_fechaActualizacion,:Enfe_obsevaciones,:Usua_id)");

             $stmt->bindParam(':Enfe_nombre',$Array7[$i]['Enfe_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_nombreCientifico',$Array7[$i]['Enfe_nombreCientifico'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_riesgo',$Array7[$i]['Enfe_riesgo'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_foto',$Array7[$i]['Enfe_foto'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_tratamiento',$Array7[$i]['Enfe_tratamiento'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_fecha',$Array7[$i]['Enfe_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_fechaActualizacion',$Array7[$i]['Enfe_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Enfe_obsevaciones',$Array7[$i]['Enfe_obsevaciones'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             }
             $var='"'.$Array7[$i]['Enfe_nombre'].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from enfermedad where Enfe_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos ENFERMEDAD: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array8)){
        try{
          if($Array8[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from afecta_enfermedad where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Enfe_id in(select Enfe_id from enfermedad where  Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
         else{
             $auxiliar= array();
             $auxiliar1= array();
         for($i = 0; $i < count($Array8); $i++) {
             $existencia=Verifica_Existencia2("AFECTA_ENFERMEDADES",$Array8[$i]['Enfe_nombre'],$Array8[$i]['Culti_nombre'],$usua_id);
        
          if($existencia){
              $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array8[$i]['Culti_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Enfe_id from enfermedad where Enfe_nombre= :enfe_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':enfe_nombre',$Array8[$i]['Enfe_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_cultivo=$results[0];
               $id_enfermedad=$results1[0];
               $stmt = $con->prepare("UPDATE afecta_enfermedad SET Afecta_Enfe_Afectacion=:Afecta_Enfe_Afectacion,Afecta_Enfe_Incidencia=:Afecta_Enfe_Incidencia,Afecta_Enfe_fechaAfectacion=:Afecta_Enfe_fechaAfectacion,Afecta_Enfe_fechaActualizacion=:Afecta_Enfe_fechaActualizacion WHERE Culti_id=:Culti_id AND Enfe_id=:Enfe_id");
              $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
              $stmt->bindParam(':Enfe_id',$id_enfermedad, PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Enfe_Afectacion',$Array8[$i]['Afecta_Enfe_Afectacion'], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Enfe_Incidencia',$Array8[$i]['Afecta_Enfe_Incidencia'], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Enfe_fechaAfectacion',$Array8[$i]['Afecta_Enfe_fechaAfectacion'], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Enfe_fechaActualizacion',$Array8[$i]['Afecta_Enfe_fechaActualizacion'], PDO::PARAM_INT);
              $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array8[$i]['Culti_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Enfe_id from enfermedad where Enfe_nombre= :enfe_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':enfe_nombre',$Array8[$i]['Enfe_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_cultivo=$results[0];
               $id_enfermedad=$results1[0];
               $stmt = $con->prepare("INSERT INTO afecta_enfermedad VALUES(null,:Culti_id,:Enfe_id,:Afecta_Enfe_Afectacion,:Afecta_Enfe_Incidencia,:Afecta_Enfe_fechaAfectacion,:Afecta_Enfe_fechaActualizacion)");

               $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
               $stmt->bindParam(':Enfe_id',$id_enfermedad, PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Enfe_Afectacion',$Array8[$i]['Afecta_Enfe_Afectacion'], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Enfe_Incidencia',$Array8[$i]['Afecta_Enfe_Incidencia'], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Enfe_fechaAfectacion',$Array8[$i]['Afecta_Enfe_fechaAfectacion'], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Enfe_fechaActualizacion',$Array8[$i]['Afecta_Enfe_fechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
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
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos AFECTA_ENFERMEDAD: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array9)){
         try{
            if($Array9[0]=="TABLAVACIA"){
             $stmt1 = $con->prepare('delete from tratamiento_enfermedad where Enfe_id in(select Enfe_id from enfermedad where Usua_id= :usua_id) AND Insum_id in(select Insum_id from insumo where Usua_id= :usua_id)');
             $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt1->execute();
           }
          else{
           $auxiliar= array();
           $auxiliar1= array();
           for($i = 0; $i < count($Array9); $i++) {
               $existencia=Verifica_Existencia2("TRATA_ENFERMEDADES",$Array9[$i]['Enfe_nombre'],$Array9[$i]['Insum_nombre'],$usua_id);
        
          if($existencia){ //Si existe actualize
            $stmt = $con->prepare("select Enfe_id from enfermedad where Enfe_nombre= :enfe_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':enfe_nombre',$Array9[$i]['Enfe_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array9[$i]['Insum_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_enfermedad=$results[0];
               $id_insumo=$results1[0];
               $stmt = $con->prepare("UPDATE tratamiento_enfermedad SET Trata_Enfe_nombre=:Trata_Enfe_nombre,Trata_Enfe_duracion=:Trata_Enfe_duracion,Trata_Enfe_duraMedida=:Trata_Enfe_duraMedida,Trata_Enfe_CantidadDosis=:Trata_Enfe_CantidadDosis,Trata_Enfe_MedidaDosis=:Trata_Enfe_MedidaDosis,Trata_Enfe_Periodo=:Trata_Enfe_Periodo,Trata_Enfe_PeMedida=:Trata_Enfe_PeMedida,Trata_Enfe_Reacciones=:Trata_Enfe_Reacciones,Trata_Enfe_Comentarios=:Trata_Enfe_Comentarios,Trata_Enfe_FechaRegistro=:Trata_Enfe_FechaRegistro,Trata_Enfe_FechaActualizacion=:Trata_Enfe_FechaActualizacion WHERE Enfe_id=:Enfe_id AND Insum_id=:Insum_id");

               $stmt->bindParam(':Enfe_id',$id_enfermedad, PDO::PARAM_INT);
               $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_nombre',$Array9[$i]['Trata_Enfe_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_duracion',$Array9[$i]['Trata_Enfe_duracion'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_duraMedida',$Array9[$i]['Trata_Enfe_duraMedida'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_CantidadDosis',$Array9[$i]['Trata_Enfe_CantidadDosis'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_MedidaDosis',$Array9[$i]['Trata_Enfe_MedidaDosis'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_Periodo',$Array9[$i]['Trata_Enfe_Periodo'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_PeMedida',$Array9[$i]['Trata_Enfe_PeMedida'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_Reacciones',$Array9[$i]['Trata_Enfe_Reacciones'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_Comentarios',$Array9[$i]['Trata_Enfe_Comentarios'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_FechaRegistro',$Array9[$i]['Trata_Enfe_FechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_FechaActualizacion',$Array9[$i]['Trata_Enfe_FechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Enfe_id from enfermedad where Enfe_nombre= :enfe_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':enfe_nombre',$Array9[$i]['Enfe_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array9[$i]['Insum_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_enfermedad=$results[0];
               $id_insumo=$results1[0];
               $stmt = $con->prepare("INSERT INTO tratamiento_enfermedad VALUES(null,:Enfe_id,:Insum_id,:Trata_Enfe_nombre,:Trata_Enfe_duracion,:Trata_Enfe_duraMedida,:Trata_Enfe_CantidadDosis,:Trata_Enfe_MedidaDosis,:Trata_Enfe_Periodo,:Trata_Enfe_PeMedida,:Trata_Enfe_Reacciones,:Trata_Enfe_Comentarios,:Trata_Enfe_FechaRegistro,:Trata_Enfe_FechaActualizacion)");

               $stmt->bindParam(':Enfe_id',$id_enfermedad, PDO::PARAM_INT);
               $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_nombre',$Array9[$i]['Trata_Enfe_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_duracion',$Array9[$i]['Trata_Enfe_duracion'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_duraMedida',$Array9[$i]['Trata_Enfe_duraMedida'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_CantidadDosis',$Array9[$i]['Trata_Enfe_CantidadDosis'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_MedidaDosis',$Array9[$i]['Trata_Enfe_MedidaDosis'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_Periodo',$Array9[$i]['Trata_Enfe_Periodo'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_PeMedida',$Array9[$i]['Trata_Enfe_PeMedida'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_Reacciones',$Array9[$i]['Trata_Enfe_Reacciones'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_Comentarios',$Array9[$i]['Trata_Enfe_Comentarios'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_FechaRegistro',$Array9[$i]['Trata_Enfe_FechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Enfe_FechaActualizacion',$Array9[$i]['Trata_Enfe_FechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
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
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos TRATA_ENFERMEDAD: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array10)){
         try{
            if($Array10[0]=="TABLAVACIA"){
             $stmt1 = $con->prepare('delete from resultados_trata_enfermedad where Trata_id in(select Trata_id from tratamiento_enfermedad where Enfe_id in(select Enfe_id from enfermedad where Usua_id= :usua_id))');
             $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt1->execute();
           }
          else{
           $auxiliar= array();
           $auxiliar1= array();
         for($i = 0; $i < count($Array10); $i++) {
         $existencia=Verifica_Existencia2("RESULTADOS_ENFERMEDADES",$Array10[$i]['Trata_Enfe_nombre'],$Array10[$i]['Result_nombre'],$usua_id);

          if($existencia){
            $stmt = $con->prepare("select Trata_id from tratamiento_enfermedad where Trata_Enfe_nombre= :trata_nombre AND Enfe_id in(select Enfe_id from enfermedad where Usua_id= :usua_id)");
            $stmt->bindParam(':trata_nombre',$Array10[$i]['Trata_Enfe_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            if($results){
               $id_tratamiento=$results[0];
               $stmt = $con->prepare("UPDATE resultados_trata_enfermedad SET Result_Nivel=:Result_Nivel,Result_Calificativo=:Result_Calificativo,Result_Pregunta1=:Result_Pregunta1,Result_Pregunta2=:Result_Pregunta2,Result_Pregunta3=:Result_Pregunta3,Result_observaciones=:Result_observaciones,Result_fechaRegistro=:Result_fechaRegistro,Result_fechaActualizacion=:Result_fechaActualizacion WHERE Result_nombre=:Result_nombre AND Trata_id=:Trata_id");

               $stmt->bindParam(':Trata_id',$id_tratamiento, PDO::PARAM_INT);
               $stmt->bindParam(':Result_nombre',$Array10[$i]['Result_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Nivel',$Array10[$i]['Result_Nivel'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Calificativo',$Array10[$i]['Result_Calificativo'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta1',$Array10[$i]['Result_Pregunta1'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta2',$Array10[$i]['Result_Pregunta2'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta3',$Array10[$i]['Result_Pregunta3'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_observaciones',$Array10[$i]['Result_observaciones'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaRegistro',$Array10[$i]['Result_fechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaActualizacion',$Array10[$i]['Result_fechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Trata_id from tratamiento_enfermedad where Trata_Enfe_nombre= :trata_nombre AND Enfe_id in(select Enfe_id from enfermedad where Usua_id= :usua_id)");
             $stmt->bindParam(':trata_nombre',$Array10[$i]['Trata_Enfe_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
               $id_tratamiento=$results[0];
               $stmt = $con->prepare("INSERT INTO resultados_trata_enfermedad VALUES(null,:Trata_id,:Result_nombre,:Result_Nivel,:Result_Calificativo,:Result_Pregunta1,:Result_Pregunta2,:Result_Pregunta3,:Result_observaciones,:Result_fechaRegistro,:Result_fechaActualizacion)");

               $stmt->bindParam(':Trata_id',$id_tratamiento, PDO::PARAM_INT);
               $stmt->bindParam(':Result_nombre',$Array10[$i]['Result_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Nivel',$Array10[$i]['Result_Nivel'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Calificativo',$Array10[$i]['Result_Calificativo'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta1',$Array10[$i]['Result_Pregunta1'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta2',$Array10[$i]['Result_Pregunta2'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta3',$Array10[$i]['Result_Pregunta3'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_observaciones',$Array10[$i]['Result_observaciones'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaRegistro',$Array10[$i]['Result_fechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaActualizacion',$Array10[$i]['Result_fechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
             }
           }
            if($id_tratamiento!='' && $id_tratamiento!==NULL){
             $var='"'.$Array10[$i]['Result_nombre'].'"';
             $var1='"'.$id_tratamiento.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
             }
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from resultados_trata_enfermedad where Result_nombre NOT IN('.$statut.') AND Trata_id NOT IN('.$statut1.')');
            $stmt1->execute();
            echo 9;
          }
         }
          catch(Exception $e) { 
            echo 'Error conectando con la base de datos RESULTADOS_ENFERMEDADES: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 
      // /********************************************************************/

      if(!empty($Array11)){
        try{
            if($Array11[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from maleza where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array11); $i++) {
          $existencia=Verifica_Existencia("MALEZA",$Array11[$i]['Male_nombre'],$usua_id);
        
          if($existencia){
             $stmt = $con->prepare("UPDATE maleza SET Male_nombreCientifico=:Male_nombreCientifico,Male_riesgo=:Male_riesgo,Male_foto=:Male_foto,Male_tratamiento=:Male_tratamiento,Male_fecha=:Male_fecha,Male_fechaActualizacion=:Male_fechaActualizacion,Male_obsevaciones=:Male_obsevaciones WHERE Male_nombre=:Male_nombre AND Usua_id=:Usua_id");

             $stmt->bindParam(':Male_nombre',$Array11[$i]['Male_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_nombreCientifico',$Array11[$i]['Male_nombreCientifico'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_riesgo',$Array11[$i]['Male_riesgo'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_foto',$Array11[$i]['Male_foto'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_tratamiento',$Array11[$i]['Male_tratamiento'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_fecha',$Array11[$i]['Male_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_fechaActualizacion',$Array11[$i]['Male_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_obsevaciones',$Array11[$i]['Male_obsevaciones'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO maleza VALUES(null,:Male_nombre,:Male_nombreCientifico,:Male_riesgo,:Male_foto,:Male_tratamiento,:Male_fecha,:Male_fechaActualizacion,:Male_obsevaciones,:Usua_id)");

             $stmt->bindParam(':Male_nombre',$Array11[$i]['Male_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_nombreCientifico',$Array11[$i]['Male_nombreCientifico'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_riesgo',$Array11[$i]['Male_riesgo'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_foto',$Array11[$i]['Male_foto'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_tratamiento',$Array11[$i]['Male_tratamiento'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_fecha',$Array11[$i]['Male_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_fechaActualizacion',$Array11[$i]['Male_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Male_obsevaciones',$Array11[$i]['Male_obsevaciones'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             }
            $var='"'.$Array11[$i]['Male_nombre'].'"';
            array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from maleza where Male_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos MALEZA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }   

      if(!empty($Array12)){
       try{
          if($Array12[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from afecta_maleza where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Male_id in(select Male_id from maleza where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
         else{
             $auxiliar= array();
             $auxiliar1= array();

         for($i = 0; $i < count($Array12); $i++) {
         $existencia=Verifica_Existencia2("AFECTA_MALEZA",$Array12[$i]['Male_nombre'],$Array12[$i]['Culti_nombre'],$usua_id);
        
          if($existencia){
               $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array12[$i]['Culti_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Male_id from maleza where Male_nombre= :male_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':male_nombre',$Array12[$i]['Male_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_cultivo=$results[0];
               $id_maleza=$results1[0];
               $stmt = $con->prepare("UPDATE afecta_maleza SET Afecta_Male_Afectacion=:Afecta_Male_Afectacion,Afecta_Male_Incidencia=:Afecta_Male_Incidencia,Afecta_Male_fechaAfectacion=:Afecta_Male_fechaAfectacion,Afecta_Male_fechaActualizacion=:Afecta_Male_fechaActualizacion WHERE Culti_id=:Culti_id AND Male_id=:Male_id");

              $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
              $stmt->bindParam(':Male_id',$id_maleza, PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_Afectacion',$Array12[$i]['Afecta_Male_Afectacion'], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_Incidencia',$Array12[$i]['Afecta_Male_Incidencia'], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_fechaAfectacion',$Array12[$i]['Afecta_Male_fechaAfectacion'], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_fechaActualizacion',$Array12[$i]['Afecta_Male_fechaActualizacion'], PDO::PARAM_INT);
              $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array12[$i]['Culti_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Male_id from maleza where Male_nombre= :male_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':male_nombre',$Array12[$i]['Male_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_cultivo=$results[0];
               $id_maleza=$results1[0];
               $stmt = $con->prepare("INSERT INTO afecta_maleza VALUES(null,:Culti_id,:Male_id,:Afecta_Male_Afectacion,:Afecta_Male_Incidencia,:Afecta_Male_fechaAfectacion,:Afecta_Male_fechaActualizacion)");

              $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
              $stmt->bindParam(':Male_id',$id_maleza, PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_Afectacion',$Array12[$i]['Afecta_Male_Afectacion'], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_Incidencia',$Array12[$i]['Afecta_Male_Incidencia'], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_fechaAfectacion',$Array12[$i]['Afecta_Male_fechaAfectacion'], PDO::PARAM_INT);
              $stmt->bindParam(':Afecta_Male_fechaActualizacion',$Array12[$i]['Afecta_Male_fechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
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
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos AFECTA_MALEZA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array13)){
         try{
            if($Array13[0]=="TABLAVACIA"){
             $stmt1 = $con->prepare('delete from tratamiento_maleza where Male_id in(select Male_id from maleza where Usua_id= :usua_id) AND Insum_id in(select Insum_id from insumo where Usua_id= :usua_id)');
             $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt1->execute();
           }
          else{
           $auxiliar= array();
           $auxiliar1= array();
         for($i = 0; $i < count($Array13); $i++) {
         $existencia=Verifica_Existencia2("TRATA_MALEZA",$Array13[$i]['Male_nombre'],$Array13[$i]['Insum_nombre'],$usua_id);
        
          if($existencia){
             $stmt = $con->prepare("select Male_id from maleza where Male_nombre= :male_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':male_nombre',$Array13[$i]['Male_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array13[$i]['Insum_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_maleza=$results[0];
               $id_insumo=$results1[0];
               $stmt = $con->prepare("UPDATE tratamiento_maleza SET Trata_Male_nombre=:Trata_Male_nombre,Trata_Male_duracion=:Trata_Male_duracion,Trata_Male_duraMedida=:Trata_Male_duraMedida,Trata_Male_CantidadDosis=:Trata_Male_CantidadDosis,Trata_Male_MedidaDosis=:Trata_Male_MedidaDosis,Trata_Male_Periodo=:Trata_Male_Periodo,Trata_Male_PeMedida=:Trata_Male_PeMedida,Trata_Male_Reacciones=:Trata_Male_Reacciones,Trata_Male_Comentarios=:Trata_Male_Comentarios,Trata_Male_FechaRegistro=:Trata_Male_FechaRegistro,Trata_Male_FechaActualizacion=:Trata_Male_FechaActualizacion WHERE Male_id=:Male_id AND Insum_id=:Insum_id");

               $stmt->bindParam(':Male_id',$id_maleza, PDO::PARAM_INT);
               $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_nombre',$Array13[$i]['Trata_Male_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_duracion',$Array13[$i]['Trata_Male_duracion'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_duraMedida',$Array13[$i]['Trata_Male_duraMedida'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_CantidadDosis',$Array13[$i]['Trata_Male_CantidadDosis'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_MedidaDosis',$Array13[$i]['Trata_Male_MedidaDosis'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_Periodo',$Array13[$i]['Trata_Male_Periodo'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_PeMedida',$Array13[$i]['Trata_Male_PeMedida'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_Reacciones',$Array13[$i]['Trata_Male_Reacciones'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_Comentarios',$Array13[$i]['Trata_Male_Comentarios'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_FechaRegistro',$Array13[$i]['Trata_Male_FechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_FechaActualizacion',$Array13[$i]['Trata_Male_FechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Male_id from maleza where Male_nombre= :male_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':male_nombre',$Array13[$i]['Male_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array13[$i]['Insum_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_maleza=$results[0];
               $id_insumo=$results1[0];
               $stmt = $con->prepare("INSERT INTO tratamiento_maleza VALUES(null,:Male_id,:Insum_id,:Trata_Male_nombre,:Trata_Male_duracion,:Trata_Male_duraMedida,:Trata_Male_CantidadDosis,:Trata_Male_MedidaDosis,:Trata_Male_Periodo,:Trata_Male_PeMedida,:Trata_Male_Reacciones,:Trata_Male_Comentarios,:Trata_Male_FechaRegistro,:Trata_Male_FechaActualizacion)");

               $stmt->bindParam(':Male_id',$id_maleza, PDO::PARAM_INT);
               $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_nombre',$Array13[$i]['Trata_Male_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_duracion',$Array13[$i]['Trata_Male_duracion'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_duraMedida',$Array13[$i]['Trata_Male_duraMedida'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_CantidadDosis',$Array13[$i]['Trata_Male_CantidadDosis'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_MedidaDosis',$Array13[$i]['Trata_Male_MedidaDosis'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_Periodo',$Array13[$i]['Trata_Male_Periodo'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_PeMedida',$Array13[$i]['Trata_Male_PeMedida'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_Reacciones',$Array13[$i]['Trata_Male_Reacciones'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_Comentarios',$Array13[$i]['Trata_Male_Comentarios'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_FechaRegistro',$Array13[$i]['Trata_Male_FechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Male_FechaActualizacion',$Array13[$i]['Trata_Male_FechaActualizacion'],PDO::PARAM_INT);
               $stmt->execute();
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
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos TRATA_MALEZA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }

      if(!empty($Array14)){
        try{
           if($Array14[0]=="TABLAVACIA"){
             $stmt1 = $con->prepare('delete from resultados_trata_maleza where Trata_id in(select Trata_id from tratamiento_maleza where Male_id in(select Male_id from maleza where Usua_id= :usua_id))');
             $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt1->execute();
            }
          else{
           $auxiliar= array();
           $auxiliar1= array();
         for($i = 0; $i < count($Array14); $i++) {
         $existencia=Verifica_Existencia("RESULTADOS_MALEZA",$Array14[$i]['Trata_Male_nombre'],$usua_id);
        
          if($existencia){
             $stmt = $con->prepare("select Trata_id from tratamiento_maleza where Trata_Male_nombre= :trata_nombre AND Male_id in(select Male_id from maleza where Usua_id= :usua_id)");
             $stmt->bindParam(':trata_nombre',$Array14[$i]['Trata_Male_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
               $id_tratamiento=$results[0];
               $stmt = $con->prepare("UPDATE resultados_trata_maleza SET Result_Nivel=:Result_Nivel,Result_Calificativo=:Result_Calificativo,Result_Pregunta1=:Result_Pregunta1,Result_Pregunta2=:Result_Pregunta2,Result_Pregunta3=:Result_Pregunta3,Result_observaciones=:Result_observaciones,Result_fechaRegistro=:Result_fechaRegistro,Result_fechaActualizacion=:Result_fechaActualizacion WHERE Trata_id=:Trata_id AND Result_nombre=:Result_nombre");

               $stmt->bindParam(':Trata_id',$id_tratamiento, PDO::PARAM_INT);
               $stmt->bindParam(':Result_nombre',$Array14[$i]['Result_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Nivel',$Array14[$i]['Result_Nivel'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Calificativo',$Array14[$i]['Result_Calificativo'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta1',$Array14[$i]['Result_Pregunta1'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta2',$Array14[$i]['Result_Pregunta2'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta3',$Array14[$i]['Result_Pregunta3'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_observaciones',$Array14[$i]['Result_observaciones'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaRegistro',$Array14[$i]['Result_fechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaActualizacion',$Array14[$i]['Result_fechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Trata_id from tratamiento_maleza where Trata_Male_nombre= :trata_nombre AND Male_id in(select Male_id from maleza where Usua_id= :usua_id)");
             $stmt->bindParam(':trata_nombre',$Array14[$i]['Trata_Male_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
               $id_tratamiento=$results[0];
               $stmt = $con->prepare("INSERT INTO resultados_trata_maleza VALUES(null,:Trata_id,:Result_nombre,:Result_Nivel,:Result_Calificativo,:Result_Pregunta1,:Result_Pregunta2,:Result_Pregunta3,:Result_observaciones,:Result_fechaRegistro,:Result_fechaActualizacion)");

               $stmt->bindParam(':Trata_id',$id_tratamiento, PDO::PARAM_INT);
               $stmt->bindParam(':Result_nombre',$Array14[$i]['Result_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Nivel',$Array14[$i]['Result_Nivel'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Calificativo',$Array14[$i]['Result_Calificativo'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta1',$Array14[$i]['Result_Pregunta1'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta2',$Array14[$i]['Result_Pregunta2'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta3',$Array14[$i]['Result_Pregunta3'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_observaciones',$Array14[$i]['Result_observaciones'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaRegistro',$Array14[$i]['Result_fechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaActualizacion',$Array14[$i]['Result_fechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
             }
           }
            $var='"'.$Array14[$i]['Result_nombre'].'"';
             $var1='"'.$id_tratamiento.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from resultados_trata_maleza where Result_nombre NOT IN('.$statut.') AND Trata_id NOT IN('.$statut1.')');
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos RESULTADOS_MALEZA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array15)){
        try{
            if($Array15[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from Insecto where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array15); $i++) {
          $existencia=Verifica_Existencia("PLAGA",$Array15[$i]['Inse_nombre'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("UPDATE Insecto SET Inse_nombre=:Inse_nombre,Inse_nombreCientifico=:Inse_nombreCientifico,Inse_riesgo=:Inse_riesgo,Inse_foto=:Inse_foto,Inse_tratamiento=:Inse_tratamiento,Inse_fecha=:Inse_fecha,Inse_fechaActualizacion=:Inse_fechaActualizacion,Inse_observaciones=:Inse_observaciones WHERE Inse_nombre=:Inse_nombre AND Usua_id=:Usua_id");

             $stmt->bindParam(':Inse_nombre',$Array15[$i]['Inse_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_nombreCientifico',$Array15[$i]['Inse_nombreCientifico'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_riesgo',$Array15[$i]['Inse_riesgo'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_foto',$Array15[$i]['Inse_foto'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_tratamiento',$Array15[$i]['Inse_tratamiento'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_fecha',$Array15[$i]['Inse_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_fechaActualizacion',$Array15[$i]['Inse_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_observaciones',$Array15[$i]['Inse_observaciones'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO Insecto VALUES(null,:Inse_nombre,:Inse_nombreCientifico,:Inse_riesgo,:Inse_foto,:Inse_tratamiento,:Inse_fecha,:Inse_fechaActualizacion,:Inse_observaciones,:Usua_id)");

             $stmt->bindParam(':Inse_nombre',$Array15[$i]['Inse_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_nombreCientifico',$Array15[$i]['Inse_nombreCientifico'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_riesgo',$Array15[$i]['Inse_riesgo'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_foto',$Array15[$i]['Inse_foto'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_tratamiento',$Array15[$i]['Inse_tratamiento'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_fecha',$Array15[$i]['Inse_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_fechaActualizacion',$Array15[$i]['Inse_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Inse_observaciones',$Array15[$i]['Inse_observaciones'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             }
           $var='"'.$Array15[$i]['Inse_nombre'].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from Insecto where Inse_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos INSECTO: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  
  
      if(!empty($Array16)){
        try{
          if($Array16[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from afecta_insecto where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Inse_id in(select Inse_id from Insecto where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
         else{
             $auxiliar= array();
             $auxiliar1= array();
         for($i = 0; $i < count($Array16); $i++) {
         $existencia=Verifica_Existencia2("AFECTA_PLAGA",$Array16[$i]['Inse_nombre'],$Array16[$i]['Culti_nombre'],$usua_id);
        
          if($existencia){
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array16[$i]['Culti_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Inse_id from Insecto where Inse_nombre= :inse_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':inse_nombre',$Array16[$i]['Inse_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_cultivo=$results[0];
               $id_insecto=$results1[0];
               $stmt = $con->prepare("UPDATE afecta_insecto SET Afecta_Insec_Afectacion=:Afecta_Insec_Afectacion,Afecta_Insec_Incidencia=:Afecta_Insec_Incidencia,Afecta_Insec_fechaAfectacion=:Afecta_Insec_fechaAfectacion,Afecta_Insec_fechaActualizacion=:Afecta_Insec_fechaActualizacion WHERE Culti_id=:Culti_id AND Inse_id=:Inse_id");

               $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
               $stmt->bindParam(':Inse_id',$id_insecto, PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_Afectacion',$Array16[$i]['Afecta_Insec_Afectacion'], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_Incidencia',$Array16[$i]['Afecta_Insec_Incidencia'], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_fechaAfectacion',$Array16[$i]['Afecta_Insec_fechaAfectacion'], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_fechaActualizacion',$Array16[$i]['Afecta_Insec_fechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array16[$i]['Culti_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Inse_id from Insecto where Inse_nombre= :inse_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':inse_nombre',$Array16[$i]['Inse_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_cultivo=$results[0];
               $id_insecto=$results1[0];
               $stmt = $con->prepare("INSERT INTO afecta_insecto VALUES(null,:Culti_id,:Inse_id,:Afecta_Insec_Afectacion,:Afecta_Insec_Incidencia,:Afecta_Insec_fechaAfectacion,:Afecta_Insec_fechaActualizacion)");

               $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
               $stmt->bindParam(':Inse_id',$id_insecto, PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_Afectacion',$Array16[$i]['Afecta_Insec_Afectacion'], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_Incidencia',$Array16[$i]['Afecta_Insec_Incidencia'], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_fechaAfectacion',$Array16[$i]['Afecta_Insec_fechaAfectacion'], PDO::PARAM_INT);
               $stmt->bindParam(':Afecta_Insec_fechaActualizacion',$Array16[$i]['Afecta_Insec_fechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
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
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos AFECTA_INSECTO: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array17)){
        try{
            if($Array17[0]=="TABLAVACIA"){
             $stmt1 = $con->prepare('delete from tratamiento_insecto where Inse_id in(select Inse_id from Insecto where Usua_id= :usua_id) AND Insum_id in(select Insum_id from insumo where Usua_id= :usua_id)');
             $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt1->execute();
           }
          else{
           $auxiliar= array();
           $auxiliar1= array();
         for($i = 0; $i < count($Array17); $i++) {
         $existencia=Verifica_Existencia2("TRATA_PLAGA",$Array17[$i]['Inse_nombre'],$Array17[$i]['Insum_nombre'],$usua_id);
        
          if($existencia){
             //echo 'Existencia '.$GLOBALS['PARCELA_ID'];
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Inse_id from Insecto where Inse_nombre= :inse_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':inse_nombre',$Array17[$i]['Inse_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array17[$i]['Insum_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results1=$stmt->fetch();

             if($results && $results1){
               $id_insecto=$results[0];
               $id_insumo=$results1[0];
               $stmt = $con->prepare("INSERT INTO tratamiento_insecto VALUES(null,:Inse_id,:Insum_id,:Trata_Inse_nombre,:Trata_Inse_duracion,:Trata_Inse_duraMedida,:Trata_Inse_CantidadDosis,:Trata_Inse_MedidaDosis,:Trata_Inse_Periodo,:Trata_Inse_PeMedida,:Trata_Inse_Reacciones,:Trata_Inse_Comentarios,:Trata_Inse_FechaRegistro,:Trata_Inse_FechaActualizacion)");

               $stmt->bindParam(':Inse_id',$id_insecto, PDO::PARAM_INT);
               $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_nombre',$Array17[$i]['Trata_Inse_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_duracion',$Array17[$i]['Trata_Inse_duracion'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_duraMedida',$Array17[$i]['Trata_Inse_duraMedida'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_CantidadDosis',$Array17[$i]['Trata_Inse_CantidadDosis'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_MedidaDosis',$Array17[$i]['Trata_Inse_MedidaDosis'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_Periodo',$Array17[$i]['Trata_Inse_Periodo'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_PeMedida',$Array17[$i]['Trata_Inse_PeMedida'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_Reacciones',$Array17[$i]['Trata_Inse_Reacciones'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_Comentarios',$Array17[$i]['Trata_Inse_Comentarios'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_FechaRegistro',$Array17[$i]['Trata_Inse_FechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':Trata_Inse_FechaActualizacion',$Array17[$i]['Trata_Inse_FechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
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
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos TRATA_PLAGA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array18)){
        try{
            if($Array18[0]=="TABLAVACIA"){
             $stmt1 = $con->prepare('delete from resultados_trata_insecto where Trata_id in(select Trata_id from tratamiento_insecto where Inse_id in(select Inse_id from Insecto where Usua_id= :usua_id))');
             $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt1->execute();
           }
          else{
           $auxiliar= array();
           $auxiliar1= array();
         for($i = 0; $i < count($Array18); $i++) {
         $existencia=Verifica_Existencia("RESULTADOS_PLAGA",$Array18[$i]['Trata_Inse_nombre'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("select Trata_id from tratamiento_insecto where Trata_Inse_nombre= :trata_nombre AND Inse_id in(select Inse_id from Insecto where Usua_id= :usua_id)");
            $stmt->bindParam(':trata_nombre',$Array18[$i]['Trata_Inse_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            if($results){
               $id_tratamiento=$results[0];
               $stmt = $con->prepare("UPDATE resultados_trata_insecto SET Result_Nivel=:Result_Nivel,Result_Calificativo=:Result_Calificativo,Result_Pregunta1=:Result_Pregunta1,Result_Pregunta2=:Result_Pregunta2,Result_Pregunta3=:Result_Pregunta3,Result_observaciones=:Result_observaciones,Result_fechaRegistro=:Result_fechaRegistro,Result_fechaActualizacion=:Result_fechaActualizacion WHERE Trata_id=:Trata_id AND Result_nombre=:Result_nombre");

               $stmt->bindParam(':Trata_id',$id_tratamiento, PDO::PARAM_INT);
               $stmt->bindParam(':Result_nombre',$Array18[$i]['Result_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Nivel',$Array18[$i]['Result_Nivel'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Calificativo',$Array18[$i]['Result_Calificativo'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta1',$Array18[$i]['Result_Pregunta1'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta2',$Array18[$i]['Result_Pregunta2'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta3',$Array18[$i]['Result_Pregunta3'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_observaciones',$Array18[$i]['Result_observaciones'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaRegistro',$Array18[$i]['Result_fechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaActualizacion',$Array18[$i]['Result_fechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Trata_id from tratamiento_insecto where Trata_Inse_nombre= :trata_nombre AND Inse_id in(select Inse_id from Insecto where Usua_id= :usua_id)");
             $stmt->bindParam(':trata_nombre',$Array18[$i]['Trata_Inse_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
               $id_tratamiento=$results[0];
               $stmt = $con->prepare("INSERT INTO resultados_trata_insecto VALUES(null,:Trata_id,:Result_nombre,:Result_Nivel,:Result_Calificativo,:Result_Pregunta1,:Result_Pregunta2,:Result_Pregunta3,:Result_observaciones,:Result_fechaRegistro,:Result_fechaActualizacion)");

               $stmt->bindParam(':Trata_id',$id_tratamiento, PDO::PARAM_INT);
               $stmt->bindParam(':Result_nombre',$Array18[$i]['Result_nombre'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Nivel',$Array18[$i]['Result_Nivel'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Calificativo',$Array18[$i]['Result_Calificativo'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta1',$Array18[$i]['Result_Pregunta1'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta2',$Array18[$i]['Result_Pregunta2'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_Pregunta3',$Array18[$i]['Result_Pregunta3'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_observaciones',$Array18[$i]['Result_observaciones'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaRegistro',$Array18[$i]['Result_fechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':Result_fechaActualizacion',$Array18[$i]['Result_fechaActualizacion'], PDO::PARAM_INT);
               $stmt->execute();
             }
           }
             $var='"'.$Array18[$i]['Result_nombre'].'"';
             $var1='"'.$id_tratamiento.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from resultados_trata_insecto where Result_nombre NOT IN('.$statut.') AND Trata_id NOT IN('.$statut1.')');
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos RESULTADOS_PLAGA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array19)){
        try{
            if($Array19[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from cliente where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array19); $i++) {
          $existencia=Verifica_Existencia("CLIENTE",$Array19[$i]['Clie_identificacion'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("UPDATE cliente SET Clie_nombre=:Clie_nombre,Clie_apellido=:Clie_apellido,Clie_Calificativo=:Clie_Calificativo,Clie_sexo=:Clie_sexo,Clie_razonSocial=:Clie_razonSocial,Clie_fechaRegistro=:Clie_fechaRegistro,Clie_fechaActualizacion=:Clie_fechaActualizacion,Clie_estado=:Clie_estado,Clie_telefono=:Clie_telefono,Clie_celular=:Clie_celular,Clie_pais=:Clie_pais,Clie_ciudad=:Clie_ciudad,Clie_correo=:Clie_correo,Clie_direccion=:Clie_direccion,Clie_observaciones=:Clie_observaciones WHERE Clie_identificacion=:Clie_identificacion AND Usua_id=:Usua_id");

            $stmt->bindParam(':Clie_identificacion',$Array19[$i]['Clie_identificacion'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_nombre',$Array19[$i]['Clie_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_apellido',$Array19[$i]['Clie_apellido'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_Calificativo',$Array19[$i]['Clie_Calificativo'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_sexo',$Array19[$i]['Clie_sexo'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_razonSocial',$Array19[$i]['Clie_razonSocial'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_fechaRegistro',$Array19[$i]['Clie_fechaRegistro'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_fechaActualizacion',$Array19[$i]['Clie_fechaActualizacion'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_estado',$Array19[$i]['Clie_estado'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_telefono',$Array19[$i]['Clie_telefono'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_celular',$Array19[$i]['Clie_celular'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_pais',$Array19[$i]['Clie_pais'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_ciudad',$Array19[$i]['Clie_ciudad'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_correo',$Array19[$i]['Clie_correo'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_direccion',$Array19[$i]['Clie_direccion'], PDO::PARAM_INT);
            $stmt->bindParam(':Clie_observaciones',$Array19[$i]['Clie_observaciones'], PDO::PARAM_INT);
            $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO cliente VALUES(null,:Clie_identificacion,:Clie_nombre,:Clie_apellido,:Clie_Calificativo,:Clie_sexo,:Clie_razonSocial,:Clie_fechaRegistro,:Clie_fechaActualizacion,:Clie_estado,:Clie_telefono,:Clie_celular,:Clie_pais,:Clie_ciudad,:Clie_correo,:Clie_direccion,:Clie_observaciones,:Usua_id)");

             $stmt->bindParam(':Clie_identificacion',$Array19[$i]['Clie_identificacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_nombre',$Array19[$i]['Clie_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_apellido',$Array19[$i]['Clie_apellido'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_Calificativo',$Array19[$i]['Clie_Calificativo'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_sexo',$Array19[$i]['Clie_sexo'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_razonSocial',$Array19[$i]['Clie_razonSocial'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_fechaRegistro',$Array19[$i]['Clie_fechaRegistro'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_fechaActualizacion',$Array19[$i]['Clie_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_estado',$Array19[$i]['Clie_estado'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_telefono',$Array19[$i]['Clie_telefono'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_celular',$Array19[$i]['Clie_celular'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_pais',$Array19[$i]['Clie_pais'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_ciudad',$Array19[$i]['Clie_ciudad'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_correo',$Array19[$i]['Clie_correo'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_direccion',$Array19[$i]['Clie_direccion'], PDO::PARAM_INT);
             $stmt->bindParam(':Clie_observaciones',$Array19[$i]['Clie_observaciones'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             }
             $var='"'.$Array19[$i]['Clie_identificacion'].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from cliente where Clie_identificacion NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos CLIENTE: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array23)){
        try{
            if($Array23[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from proveedor where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array23); $i++) {
          $existencia=Verifica_Existencia("PROVEEDOR",$Array23[$i]['Prove_nombre'],$usua_id);
        
          if($existencia){
             $stmt = $con->prepare("UPDATE proveedor SET Prove_horario=:Prove_horario,Prove_observaciones=:Prove_observaciones,Prove_direccionWeb=:Prove_direccionWeb,Prove_estado=:Prove_estado,Prove_personaContacto=:Prove_personaContacto,Prove_celular=:Prove_celular,Prove_sexo=:Prove_sexo,Prove_direccion=:Prove_direccion,Prove_correo=:Prove_correo,Prove_telefono=:Prove_telefono,Prove_pais=:Prove_pais,Prove_provincia=:Prove_provincia,Prove_fecha=:Prove_fecha,Prove_fechaActualizacion=:Prove_fechaActualizacion WHERE Prove_nombre=:Prove_nombre AND Usua_id=:Usua_id");

             $stmt->bindParam(':Prove_horario',$Array23[$i]['Prove_horario'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_observaciones',$Array23[$i]['Prove_observaciones'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_direccionWeb',$Array23[$i]['Prove_direccionWeb'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_estado',$Array23[$i]['Prove_estado'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_nombre',$Array23[$i]['Prove_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_personaContacto',$Array23[$i]['Prove_personaContacto'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_celular',$Array23[$i]['Prove_celular'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_sexo',$Array23[$i]['Prove_sexo'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_direccion',$Array23[$i]['Prove_direccion'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_correo',$Array23[$i]['Prove_correo'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_telefono',$Array23[$i]['Prove_telefono'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_pais',$Array23[$i]['Prove_pais'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_provincia',$Array23[$i]['Prove_provincia'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_fecha',$Array23[$i]['Prove_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_fechaActualizacion',$Array23[$i]['Prove_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO proveedor VALUES(null,:Prove_horario,:Prove_observaciones,:Prove_direccionWeb,:Prove_estado,:Prove_nombre,:Prove_personaContacto,:Prove_celular,:Prove_sexo,:Prove_direccion,:Prove_correo,:Prove_telefono,:Prove_pais,:Prove_provincia,:Prove_fecha,:Prove_fechaActualizacion,:Usua_id)");

             $stmt->bindParam(':Prove_horario',$Array23[$i]['Prove_horario'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_observaciones',$Array23[$i]['Prove_observaciones'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_direccionWeb',$Array23[$i]['Prove_direccionWeb'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_estado',$Array23[$i]['Prove_estado'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_nombre',$Array23[$i]['Prove_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_personaContacto',$Array23[$i]['Prove_personaContacto'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_celular',$Array23[$i]['Prove_celular'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_sexo',$Array23[$i]['Prove_sexo'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_direccion',$Array23[$i]['Prove_direccion'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_correo',$Array23[$i]['Prove_correo'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_telefono',$Array23[$i]['Prove_telefono'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_pais',$Array23[$i]['Prove_pais'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_provincia',$Array23[$i]['Prove_provincia'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_fecha',$Array23[$i]['Prove_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':Prove_fechaActualizacion',$Array23[$i]['Prove_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             }
             $var='"'.$Array23[$i]['Prove_nombre'].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from proveedor where Prove_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos PROVEEDOR: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array24)){
        try{
            if($Array24[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from factura where Prove_id in(select Prove_id from proveedor where Usua_id= :usua_id) AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array24); $i++) {
          $existencia=Verifica_Existencia3("FACTURA_COMPRA",$Array24[$i]['Prove_nombre'],$Array24[$i]['Culti_nombre'],$Array24[$i]['Fact_Nombre'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("select Prove_id from proveedor where Prove_nombre= :prove_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':prove_nombre',$Array24[$i]['Prove_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
            $stmt->bindParam(':culti_nombre',$Array24[$i]['Culti_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
              $id_proveedor=$results[0];
              $id_cultivo=$result[0];

              $stmt = $con->prepare("UPDATE factura SET Fact_nroArticulos=:Fact_nroArticulos,Fact_total=:Fact_total,Fact_Fecha=:Fact_Fecha,Prove_id=:Prove_id WHERE Fact_Nombre=:Fact_Nombre AND Culti_id=:Culti_id");
             $stmt->bindParam(':Fact_Nombre',$Array24[$i]['Fact_Nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_nroArticulos',$Array24[$i]['Fact_nroArticulos'], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_total',$Array24[$i]['Fact_total'], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_Fecha',$Array24[$i]['Fact_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
             $stmt->bindParam(':Prove_id',$id_proveedor, PDO::PARAM_INT);
             $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Prove_id from proveedor where Prove_nombre= :prove_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':prove_nombre',$Array24[$i]['Prove_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)");
             $stmt->bindParam(':culti_nombre',$Array24[$i]['Culti_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
              $id_proveedor=$results[0];
              $id_cultivo=$result[0];

              $stmt = $con->prepare("INSERT INTO factura VALUES(null,:Fact_Nombre,:Fact_nroArticulos,:Fact_total,:Fact_Fecha,:Culti_id,:Prove_id)");
             $stmt->bindParam(':Fact_Nombre',$Array24[$i]['Fact_Nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_nroArticulos',$Array24[$i]['Fact_nroArticulos'], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_total',$Array24[$i]['Fact_total'], PDO::PARAM_INT);
             $stmt->bindParam(':Fact_Fecha',$Array24[$i]['Fact_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':Culti_id',$id_cultivo, PDO::PARAM_INT);
             $stmt->bindParam(':Prove_id',$id_proveedor, PDO::PARAM_INT);
             $stmt->execute();
             }
           }
             $var='"'.$Array24[$i]['Fact_Nombre'].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from factura where Fact_Nombre NOT IN('.$statut.') AND Prove_id in(select Prove_id from proveedor where Usua_id= :usua_id) AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos FACTURA_COMPRA: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array25)){
         try{
            if($Array25[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from compra where Fact_id in(select Fact_id from factura where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))) AND Insum_id in(select Insum_id from insumo where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array25); $i++) {
          $existencia=Verifica_Existencia2("COMPRAS",$Array25[$i]['Insum_nombre'],$Array25[$i]['Fact_Nombre'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':insum_nombre',$Array25[$i]['Insum_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select Fact_id from factura where Fact_Nombre= :fact_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Prove_id in(select Prove_id from proveedor where Usua_id= :usua_id)");
            $stmt->bindParam(':fact_nombre',$Array25[$i]['Fact_Nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
              $id_insumo=$results[0];
              $id_factura=$result[0];
              $stmt = $con->prepare("UPDATE compra SET Comp_valorTotal=:Comp_valorTotal,Comp_cantidad=:Comp_cantidad SET WHERE Fact_id=:Fact_id AND Insum_id=:Insum_id");
              $stmt->bindParam(':Comp_valorTotal',$Array25[$i]['Comp_valorTotal'], PDO::PARAM_INT);
              $stmt->bindParam(':Comp_cantidad',$Array25[$i]['Comp_cantidad'], PDO::PARAM_INT);
              $stmt->bindParam(':Fact_id',$id_factura, PDO::PARAM_INT);
              $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
              $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':insum_nombre',$Array25[$i]['Insum_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Fact_id from factura where Fact_Nombre= :fact_nombre AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Prove_id in(select Prove_id from proveedor where Usua_id= :usua_id)");
             $stmt->bindParam(':fact_nombre',$Array25[$i]['Fact_Nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
              $id_insumo=$results[0];
              $id_factura=$result[0];
              $stmt = $con->prepare("INSERT INTO compra VALUES(null,:Comp_valorTotal,:Comp_cantidad,:Fact_id,:Insum_id)");
              $stmt->bindParam(':Comp_valorTotal',$Array25[$i]['Comp_valorTotal'], PDO::PARAM_INT);
              $stmt->bindParam(':Comp_cantidad',$Array25[$i]['Comp_cantidad'], PDO::PARAM_INT);
              $stmt->bindParam(':Fact_id',$id_factura, PDO::PARAM_INT);
              $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
              $stmt->execute();
             }
           }
            $var='"'.$id_factura.'"';
            $var1='"'.$id_insumo.'"';
            array_push($auxiliar,$var);
            array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from compra where Fact_id NOT IN('.$statut.') AND Insum_id NOT IN('.$statut1.') AND Fact_id in(select Fact_id from factura where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))) AND Insum_id in(select Insum_id from insumo where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos COMPRAS: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       }  

      if(!empty($Array27)){
         try{
            if($Array27[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from empleado where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array27); $i++) {
          $existencia=Verifica_Existencia("EMPLEADO",$Array27[$i]['Emple_identificacion'],$usua_id);
        
          if($existencia){
             $stmt = $con->prepare("UPDATE empleado SET Emple_pais=:Emple_pais,Emple_ciudad=:Emple_ciudad,Emple_sexo=:Emple_sexo,Emple_nombre=:Emple_nombre,Emple_apellido=:Emple_apellido,Emple_telefono=:Emple_telefono,Emple_celular=:Emple_celular,Emple_direccion=:Emple_direccion,Emple_fechaNacimiento=:Emple_fechaNacimiento,Emple_correo=:Emple_correo,Emple_sueldo=:Emple_sueldo,Emple_fechaRegistro=:Emple_fechaRegistro,Emple_fechaActualizacion=:Emple_fechaActualizacion,Emple_foto=:Emple_foto WHERE Emple_identificacion=:Emple_identificacion AND Usua_id=:Usua_id");

             $stmt->bindParam(':Emple_identificacion',$Array27[$i]['Emple_identificacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_pais',$Array27[$i]['Emple_pais'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_ciudad',$Array27[$i]['Emple_ciudad'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_sexo',$Array27[$i]['Emple_sexo'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_nombre',$Array27[$i]['Emple_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_apellido',$Array27[$i]['Emple_apellido'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_telefono',$Array27[$i]['Emple_telefono'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_celular',$Array27[$i]['Emple_celular'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_direccion',$Array27[$i]['Emple_direccion'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_fechaNacimiento',$Array27[$i]['Emple_fechaNacimiento'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_correo',$Array27[$i]['Emple_correo'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_sueldo',$Array27[$i]['Emple_sueldo'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_fechaRegistro',$Array27[$i]['Emple_fechaRegistro'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_fechaActualizacion',$Array27[$i]['Emple_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_foto',$Array27[$i]['Emple_foto'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
          }  
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO empleado VALUES(null,:Emple_identificacion,:Emple_pais,:Emple_ciudad,:Emple_sexo,:Emple_nombre,:Emple_apellido,:Emple_telefono,:Emple_celular,:Emple_direccion,:Emple_fechaNacimiento,:Emple_correo,:Emple_sueldo,:Emple_fechaRegistro,:Emple_fechaActualizacion,:Emple_foto,:Usua_id)");

             $stmt->bindParam(':Emple_identificacion',$Array27[$i]['Emple_identificacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_pais',$Array27[$i]['Emple_pais'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_ciudad',$Array27[$i]['Emple_ciudad'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_sexo',$Array27[$i]['Emple_sexo'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_nombre',$Array27[$i]['Emple_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_apellido',$Array27[$i]['Emple_apellido'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_telefono',$Array27[$i]['Emple_telefono'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_celular',$Array27[$i]['Emple_celular'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_direccion',$Array27[$i]['Emple_direccion'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_fechaNacimiento',$Array27[$i]['Emple_fechaNacimiento'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_correo',$Array27[$i]['Emple_correo'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_sueldo',$Array27[$i]['Emple_sueldo'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_fechaRegistro',$Array27[$i]['Emple_fechaRegistro'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_fechaActualizacion',$Array27[$i]['Emple_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_foto',$Array27[$i]['Emple_foto'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             }
             $var='"'.$Array27[$i]['Emple_identificacion'].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from empleado where Emple_identificacion NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos EMPLEADO: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array29)){
         try{
            if($Array29[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from tarea where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array29); $i++) {
          $existencia=Verifica_Existencia("TAREAS",$Array29[$i]['Tarea_nombre'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("select Emple_id from empleado where Emple_identificacion= :emple_identificacion AND Usua_id= :usua_id");
            $stmt->bindParam(':emple_identificacion',$Array29[$i]['Emple_identificacion'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

             if($results){
              $id_empleado=$results[0];
              $stmt = $con->prepare("UPDATE tarea SET Tarea_descripcion=:Tarea_descripcion,Tarea_prioridad=:Tarea_prioridad,Tarea_tipo=:Tarea_tipo,Tarea_fechaCreacion=:Tarea_fechaCreacion,Tarea_fechaCumplimiento=:Tarea_fechaCumplimiento,Tarea_horaCumplimiento=:Tarea_horaCumplimiento,Tarea_fechaActualizacion=:Tarea_fechaActualizacion,Tarea_estado=:Tarea_estado,Tarea_modelo=:Tarea_modelo,Emple_id=:Emple_id WHERE Tarea_nombre=:Tarea_nombre AND Usua_id=:Usua_id");

             $stmt->bindParam(':Tarea_nombre',$Array29[$i]['Tarea_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_descripcion',$Array29[$i]['Tarea_descripcion'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_prioridad',$Array29[$i]['Tarea_prioridad'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_tipo',$Array29[$i]['Tarea_tipo'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_fechaCreacion',$Array29[$i]['Tarea_fechaCreacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_fechaCumplimiento',$Array29[$i]['Tarea_fechaCumplimiento'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_horaCumplimiento',$Array29[$i]['Tarea_horaCumplimiento'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_fechaActualizacion',$Array29[$i]['Tarea_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_estado',$Array29[$i]['Tarea_estado'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_modelo',$Array29[$i]['Tarea_modelo'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_id',$id_empleado, PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Emple_id from empleado where Emple_identificacion= :emple_identificacion AND Usua_id= :usua_id");
             $stmt->bindParam(':emple_identificacion',$Array29[$i]['Emple_identificacion'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             if($results){
              $id_empleado=$results[0];
              $stmt = $con->prepare("INSERT INTO tarea VALUES(null,:Tarea_nombre,:Tarea_descripcion,:Tarea_prioridad,:Tarea_tipo,:Tarea_fechaCreacion,:Tarea_fechaCumplimiento,:Tarea_horaCumplimiento,:Tarea_fechaActualizacion,:Tarea_estado,:Tarea_modelo,:Emple_id,:Usua_id)");

             $stmt->bindParam(':Tarea_nombre',$Array29[$i]['Tarea_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_descripcion',$Array29[$i]['Tarea_descripcion'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_prioridad',$Array29[$i]['Tarea_prioridad'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_tipo',$Array29[$i]['Tarea_tipo'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_fechaCreacion',$Array29[$i]['Tarea_fechaCreacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_fechaCumplimiento',$Array29[$i]['Tarea_fechaCumplimiento'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_horaCumplimiento',$Array29[$i]['Tarea_horaCumplimiento'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_fechaActualizacion',$Array29[$i]['Tarea_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_estado',$Array29[$i]['Tarea_estado'], PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_modelo',$Array29[$i]['Tarea_modelo'], PDO::PARAM_INT);
             $stmt->bindParam(':Emple_id',$id_empleado, PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             }
           }
             $var='"'.$Array29[$i]['Tarea_nombre'].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from tarea where Tarea_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos TAREAS: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array30)){
        try{
            if($Array30[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from utiliza_insum_tarea where Insum_id in(select Insum_id from insumo where Usua_id= :usua_id) AND Tarea_id in(select Tarea_id from tarea where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array30); $i++) {
          $existencia=Verifica_Existencia2("UTILIZA_INSUM_TAREA",$Array30[$i]['Insum_nombre'],$Array30[$i]['Tarea_nombre'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':insum_nombre',$Array30[$i]['Insum_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select Tarea_id from tarea where Tarea_nombre= :tarea_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':tarea_nombre',$Array30[$i]['Tarea_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
              $id_insumo=$results[0];
              $id_tarea=$result[0];
              $stmt = $con->prepare("UPDATE utiliza_insum_tarea SET Insum_id=:Insum_id,Tarea_id=:Tarea_id WHERE Insum_id=:Insum_id AND Tarea_id=:Tarea_id");

             $stmt->bindParam(':Insum_id',$id_insumo, PDO::PARAM_INT);
             $stmt->bindParam(':Tarea_id',$id_tarea, PDO::PARAM_INT);
             $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            $stmt = $con->prepare("select Insum_id from insumo where Insum_nombre= :insum_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':insum_nombre',$Array30[$i]['Insum_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select Tarea_id from tarea where Tarea_nombre= :tarea_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':tarea_nombre',$Array30[$i]['Tarea_nombre'], PDO::PARAM_INT);
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
             }
           }
             $var='"'.$id_insumo.'"';
             $var1='"'.$id_tarea.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from utiliza_insum_tarea where Insum_id NOT IN('.$statut.') AND Tarea_id NOT IN('.$statut1.') AND Insum_id in(select Insum_id from insumo where Usua_id= :usua_id) AND Tarea_id in(select Tarea_id from tarea where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos UTILIZA_INSUM_TAREA: '. $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array31)){
        try{
            if($Array31[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from estacion_meteorologica where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array31); $i++) {
          $existencia=Verifica_Existencia("ESTACION",$Array31[$i]['estacion_id'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("select Parce_id from parcela where Parce_nombre= :parce_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':parce_nombre',$Array31[$i]['Parce_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            if($results){
              $id_parcela=$results[0];
              $stmt = $con->prepare("UPDATE estacion_meteorologica SET estacion_nombre=:estacion_nombre,estacion_fechaRegistro=:estacion_fechaRegistro,estacion_fechaactualizacion=:estacion_fechaactualizacion,parce_id=:parce_id WHERE estacion_id=:estacion_id");

             $stmt->bindParam(':estacion_id',$Array31[$i]['estacion_id'], PDO::PARAM_INT);
             $stmt->bindParam(':estacion_nombre',$Array31[$i]['estacion_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':estacion_fechaRegistro',$Array31[$i]['estacion_fechaRegistro'], PDO::PARAM_INT);
             $stmt->bindParam(':estacion_fechaactualizacion',$Array31[$i]['estacion_fechaactualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':parce_id',$id_parcela, PDO::PARAM_INT);
             $stmt->execute();
             }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            $stmt = $con->prepare("select Parce_id from parcela where Parce_nombre= :parce_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':parce_nombre',$Array31[$i]['Parce_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            if($results){
              $id_parcela=$results[0];
              $stmt = $con->prepare("INSERT INTO estacion_meteorologica VALUES(:estacion_id,:estacion_nombre,:estacion_fechaRegistro,:estacion_fechaactualizacion,:parce_id)");

             $stmt->bindParam(':estacion_id',$Array31[$i]['estacion_id'], PDO::PARAM_INT);
             $stmt->bindParam(':estacion_nombre',$Array31[$i]['estacion_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':estacion_fechaRegistro',$Array31[$i]['estacion_fechaRegistro'], PDO::PARAM_INT);
             $stmt->bindParam(':estacion_fechaactualizacion',$Array31[$i]['estacion_fechaactualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':parce_id',$id_parcela, PDO::PARAM_INT);
             $stmt->execute();
             }
           }
             $var='"'.$Array31[$i]['estacion_id'].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from estacion_meteorologica where estacion_id NOT IN('.$statut.') AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos ESTACION: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array32)){
         try{
            if($Array32[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from medicionmeteorologica where Estacion_id in (select estacion_id from estacion_meteorologica where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array32); $i++) {
             $var='"'.$Array32[$i]['MediMete_fecha'].'"';
             $var1='"'.$Array32[$i]['MediMete_hora'].'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from medicionmeteorologica where MediMete_fecha NOT IN('.$statut.') AND MediMete_hora NOT IN('.$statut.') AND Estacion_id IN (select estacion_id from estacion_meteorologica where Parce_id in(select Parce_id from parcela where Usua_id=:usua_id))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos MEDICION_METEO: ' . $e->getMessage();
            $stmt->rollback(); 
          }
       } 

      if(!empty($Array33)){
         try{
            if($Array33[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from evento where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array33); $i++) {
          $existencia=Verifica_Existencia("EVENTOS",$Array33[$i]['Evt_nombre'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("UPDATE evento SET Evt_fechaCreacion=:Evt_fechaCreacion,Evt_fechaActualizacion=:Evt_fechaActualizacion,Evt_fechaProgramada=:Evt_fechaProgramada,Evt_descripcion=:Evt_descripcion WHERE Evt_nombre=:Evt_nombre AND Usua_id=:Usua_id");

             $stmt->bindParam(':Evt_nombre',$Array33[$i]['Evt_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_fechaCreacion',$Array33[$i]['Evt_fechaCreacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_fechaActualizacion',$Array33[$i]['Evt_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_fechaProgramada',$Array33[$i]['Evt_fechaProgramada'], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_descripcion',$Array33[$i]['Evt_descripcion'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO evento VALUES(null,:Evt_nombre,:Evt_fechaCreacion,:Evt_fechaActualizacion,:Evt_fechaProgramada,:Evt_descripcion,:Usua_id)");

             $stmt->bindParam(':Evt_nombre',$Array33[$i]['Evt_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_fechaCreacion',$Array33[$i]['Evt_fechaCreacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_fechaActualizacion',$Array33[$i]['Evt_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_fechaProgramada',$Array33[$i]['Evt_fechaProgramada'], PDO::PARAM_INT);
             $stmt->bindParam(':Evt_descripcion',$Array33[$i]['Evt_descripcion'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             }
             $var='"'.$Array33[$i]['Evt_nombre'].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from evento where Evt_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos EVENTOS: '. $e->getMessage();
            $stmt->rollback(); 
          }
      }

      if(!empty($Array34)){
         try{
            if($Array34[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from alertapro where Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
         for($i = 0; $i < count($Array34); $i++) {
          $existencia=Verifica_Existencia("ALERTAS",$Array34[$i]['AlerPro_nombre'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("UPDATE alertapro SET AlerPro_nombre=:AlerPro_nombre,AlerPro_tipo=:AlerPro_tipo,AlerPro_variable=:AlerPro_variable,AlerPro_fecha=:AlerPro_fecha,AlerPro_fechaActualizacion=:AlerPro_fechaActualizacion,AlerPro_prioridad=:AlerPro_prioridad,AlerPro_valor=:AlerPro_valor,AlerPro_descripcion=:AlerPro_descripcion,AlerPro_Estado=:AlerPro_Estado WHERE AlerPro_nombre=:AlerPro_nombre AND Usua_id=:Usua_id");
            $stmt->bindParam(':AlerPro_nombre',$Array34[$i]['AlerPro_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_tipo',$Array34[$i]['AlerPro_tipo'], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_variable',$Array34[$i]['AlerPro_variable'], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_fecha',$Array34[$i]['AlerPro_fecha'], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_fechaActualizacion',$Array34[$i]['AlerPro_fechaActualizacion'], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_prioridad',$Array34[$i]['AlerPro_prioridad'], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_valor',$Array34[$i]['AlerPro_valor'], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_descripcion',$Array34[$i]['AlerPro_descripcion'], PDO::PARAM_INT);
            $stmt->bindParam(':AlerPro_Estado',$Array34[$i]['AlerPro_Estado'], PDO::PARAM_INT);
            $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("INSERT INTO alertapro VALUES(null,:AlerPro_nombre,:AlerPro_tipo,:AlerPro_variable,:AlerPro_fecha,:AlerPro_fechaActualizacion,:AlerPro_prioridad,:AlerPro_valor,:AlerPro_descripcion,:AlerPro_Estado,:Usua_id)");
             $stmt->bindParam(':AlerPro_nombre',$Array34[$i]['AlerPro_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_tipo',$Array34[$i]['AlerPro_tipo'], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_variable',$Array34[$i]['AlerPro_variable'], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_fecha',$Array34[$i]['AlerPro_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_fechaActualizacion',$Array34[$i]['AlerPro_fechaActualizacion'], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_prioridad',$Array34[$i]['AlerPro_prioridad'], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_valor',$Array34[$i]['AlerPro_valor'], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_descripcion',$Array34[$i]['AlerPro_descripcion'], PDO::PARAM_INT);
             $stmt->bindParam(':AlerPro_Estado',$Array34[$i]['AlerPro_Estado'], PDO::PARAM_INT);
             $stmt->bindParam(':Usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             }
             $var='"'.$Array34[$i]['AlerPro_nombre'].'"';
             array_push($auxiliar,$var);
           }
            $statut = implode(',',$auxiliar);
            $stmt1 = $con->prepare('delete from alertapro where AlerPro_nombre NOT IN('.$statut.') AND Usua_id= :usua_id');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos ALERTAS: '. $e->getMessage();
            $stmt->rollback(); 
          }
       }

      if(!empty($Array35)){
         try{
            if($Array35[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from alertaactiva_sensor where AlerPro_id in(select AlerPro_id from alertapro where Usua_id= :usua_id) AND MediSens_id in(select MediSens_id from medicionsensor where Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array35); $i++) {
          $existencia=Verifica_Existencia3("ALERTA_SENSOR",$Array35[$i]['AlerPro_nombre'],$Array35[$i]['MediSens_fecha'],$Array35[$i]['MediSens_hora'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("select AlerPro_id from alertapro where AlerPro_nombre= :alerpro_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':alerpro_nombre',$Array35[$i]['AlerPro_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select MediSens_id from medicionsensor where MediSens_fecha= :medisens_fecha AND MediSens_hora= :medisens_hora AND Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))");
            $stmt->bindParam(':medisens_fecha',$Array35[$i]['MediSens_fecha'], PDO::PARAM_INT);
            $stmt->bindParam(':medisens_hora',$Array35[$i]['MediSens_hora'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
                $id_alerta=$results[0];
                $id_medisens=$result[0];
                $stmt = $con->prepare("UPDATE alertaactiva_sensor SET AlerActiv_HoraMedicion=:AlerActiv_HoraMedicion,AlerActiv_Valor=:AlerActiv_Valor,AlerActiv_fechaRegistro=:AlerActiv_fechaRegistro,AlerActiv_Horaregistro=:AlerActiv_Horaregistro WHERE AlerPro_id=:AlerPro_id AND MediSens_id=:MediSens_id");
                $stmt->bindParam(':AlerActiv_HoraMedicion',$Array35[$i]['AlerActiv_HoraMedicion'], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_Valor',$Array35[$i]['AlerActiv_Valor'], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_fechaRegistro',$Array35[$i]['AlerActiv_fechaRegistro'], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_Horaregistro',$Array35[$i]['AlerActiv_Horaregistro'], PDO::PARAM_INT);
                $stmt->bindParam(':AlerPro_id',$id_alerta, PDO::PARAM_INT);
                $stmt->bindParam(':MediSens_id',$id_medisens, PDO::PARAM_INT);
                $stmt->execute();
            }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select AlerPro_id from alertapro where AlerPro_nombre= :alerpro_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':alerpro_nombre',$Array35[$i]['AlerPro_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select MediSens_id from medicionsensor where MediSens_fecha= :medisens_fecha AND MediSens_hora= :medisens_hora AND Sens_id in(select Sens_id from nodosensor where Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)))");
             $stmt->bindParam(':medisens_fecha',$Array35[$i]['MediSens_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':medisens_hora',$Array35[$i]['MediSens_hora'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
                $id_alerta=$results[0];
                $id_medisens=$result[0];
                $stmt = $con->prepare("INSERT INTO alertaactiva_sensor VALUES(null,:AlerActiv_HoraMedicion,:AlerActiv_Valor,:AlerActiv_fechaRegistro,:AlerActiv_Horaregistro,:AlerPro_id,:MediSens_id)");
                $stmt->bindParam(':AlerActiv_HoraMedicion',$Array35[$i]['AlerActiv_HoraMedicion'], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_Valor',$Array35[$i]['AlerActiv_Valor'], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_fechaRegistro',$Array35[$i]['AlerActiv_fechaRegistro'], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_Horaregistro',$Array35[$i]['AlerActiv_Horaregistro'], PDO::PARAM_INT);
                $stmt->bindParam(':AlerPro_id',$id_alerta, PDO::PARAM_INT);
                $stmt->bindParam(':MediSens_id',$id_medisens, PDO::PARAM_INT);
                $stmt->execute();
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
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos ALERTA_SENSOR: '. $e->getMessage();
            $stmt->rollback(); 
          }
       }

      if(!empty($Array36)){
         try{
            if($Array36[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from alertaactiva_meteorologia where AlerPro_id in(select AlerPro_id from alertapro where Usua_id= :usua_id) AND MediMete_id in(select MediMete_id from medicionmeteorologica where Estacion_id in(select Estacion_id from estacion_meteorologica where Parce_id in (select Parce_id from parcela where Usua_id= :usua_id)))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array36); $i++) {
          $existencia=Verifica_Existencia3("ALERTA_ESTACION",$Array36[$i]['AlerPro_nombre'],$Array36[$i]['MediMete_fecha'],$Array36[$i]['MediMete_hora'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("select AlerPro_id from alertapro where AlerPro_nombre= :alerpro_nombre AND Usua_id= :usua_id");
            $stmt->bindParam(':alerpro_nombre',$Array36[$i]['AlerPro_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select MediMete_id from medicionmeteorologica where MediMete_fecha= :medimete_fecha AND MediMete_hora= :medimete_hora AND Estacion_id in(select Estacion_id from estacion_meteorologica where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
            $stmt->bindParam(':medimete_fecha',$Array36[$i]['MediMete_fecha'], PDO::PARAM_INT);
            $stmt->bindParam(':medimete_hora',$Array36[$i]['MediMete_hora'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
               $id_alerta=$results[0];
               $id_medimete=$result[0];
               $stmt = $con->prepare("UPDATE alertaactiva_meteorologia SET AlerActiv_HoraMedicion=:AlerActiv_HoraMedicion,AlerActiv_Valor=:AlerActiv_Valor,AlerActiv_fechaRegistro=:AlerActiv_fechaRegistro,AlerActiv_Horaregistro=:AlerActiv_Horaregistro WHERE AlerPro_id=:AlerPro_id AND MediMete_id=:MediMete_id");
               $stmt->bindParam(':AlerActiv_HoraMedicion',$Array36[$i]['AlerActiv_HoraMedicion'], PDO::PARAM_INT);
               $stmt->bindParam(':AlerActiv_Valor',$Array36[$i]['AlerActiv_Valor'], PDO::PARAM_INT);
               $stmt->bindParam(':AlerActiv_fechaRegistro',$Array36[$i]['AlerActiv_fechaRegistro'], PDO::PARAM_INT);
               $stmt->bindParam(':AlerActiv_Horaregistro',$Array36[$i]['AlerActiv_Horaregistro'], PDO::PARAM_INT);
               $stmt->bindParam(':AlerPro_id',$id_alerta, PDO::PARAM_INT);
               $stmt->bindParam(':MediMete_id',$id_medimete, PDO::PARAM_INT);
               $stmt->execute();
           }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select AlerPro_id from alertapro where AlerPro_nombre= :alerpro_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':alerpro_nombre',$Array36[$i]['AlerPro_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select MediMete_id from medicionmeteorologica where MediMete_fecha= :medimete_fecha AND MediMete_hora= :medimete_hora AND Estacion_id in(select Estacion_id from estacion_meteorologica where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
             $stmt->bindParam(':medimete_fecha',$Array36[$i]['MediMete_fecha'], PDO::PARAM_INT);
             $stmt->bindParam(':medimete_hora',$Array36[$i]['MediMete_hora'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
                $id_alerta=$results[0];
                $id_medimete=$result[0];
                $stmt = $con->prepare("INSERT INTO alertaactiva_meteorologia VALUES(null,:AlerActiv_HoraMedicion,:AlerActiv_Valor,:AlerActiv_fechaRegistro,:AlerActiv_Horaregistro,:AlerPro_id,:MediMete_id)");
                $stmt->bindParam(':AlerActiv_HoraMedicion',$Array36[$i]['AlerActiv_HoraMedicion'], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_Valor',$Array36[$i]['AlerActiv_Valor'], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_fechaRegistro',$Array36[$i]['AlerActiv_fechaRegistro'], PDO::PARAM_INT);
                $stmt->bindParam(':AlerActiv_Horaregistro',$Array36[$i]['AlerActiv_Horaregistro'], PDO::PARAM_INT);
                $stmt->bindParam(':AlerPro_id',$id_alerta, PDO::PARAM_INT);
                $stmt->bindParam(':MediMete_id',$id_medimete, PDO::PARAM_INT);
                $stmt->execute();
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
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos ALERTA_ESTACION: '. $e->getMessage();
            $stmt->rollback(); 
          }
       }

      if(!empty($Array37)){
         try{
            if($Array37[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from realiza_emplea_tarea_cosecha where Tarea_id in(select Tarea_id from tarea where Usua_id= :usua_id) AND Cose_id IN(select Cose_id from cosecha where Culti_id IN(select Culti_id from cultivo where Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id)))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array37); $i++) {
          $existencia=Verifica_Existencia2("REALIZA_TAREA_COSECHA",$Array37[$i]['Cose_nombre'],$Array37[$i]['Tarea_nombre'],$usua_id);

          if($existencia){
            $stmt = $con->prepare("select Cose_id from cosecha where Cose_nombre=:cose_nombre AND Culti_id IN(select Culti_id from cultivo where Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id))");
            $stmt->bindParam(':cose_nombre',$Array37[$i]['Cose_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select Tarea_id from tarea where Tarea_nombre=:tarea_nombre AND Usua_id=:usua_id");
            $stmt->bindParam(':tarea_nombre',$Array37[$i]['Tarea_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
                $id_cosecha=$results[0];
                $id_tarea=$result[0];
                $stmt = $con->prepare("UPDATE realiza_emplea_tarea_cosecha SET Tarea_id=:tarea_id,Cose_id=:cose_id WHERE Tarea_id=:tarea_id AND Cose_id=:cose_id");
                $stmt->bindParam(':cose_id',$id_cosecha, PDO::PARAM_INT);
                $stmt->bindParam(':tarea_id',$id_tarea, PDO::PARAM_INT);
                $stmt->execute();
            }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Cose_id from cosecha where Cose_nombre= :cose_nombre AND Culti_id IN(select Culti_id from cultivo where Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id))");
             $stmt->bindParam(':cose_nombre',$Array37[$i]['Cose_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Tarea_id from tarea where Tarea_nombre=:tarea_nombre AND Usua_id=:usua_id");
             $stmt->bindParam(':tarea_nombre',$Array37[$i]['Tarea_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
                $id_cosecha=$results[0];
                $id_tarea=$result[0];
                $stmt = $con->prepare("INSERT INTO realiza_emplea_tarea_cosecha VALUES(null,:tarea_id,:cose_id)");
                $stmt->bindParam(':cose_id',$id_cosecha, PDO::PARAM_INT);
                $stmt->bindParam(':tarea_id',$id_tarea, PDO::PARAM_INT);
                $stmt->execute();
            }
           }
             $var='"'.$id_cosecha.'"';
             $var1='"'.$id_tarea.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from realiza_emplea_tarea_cosecha where Cose_id NOT IN('.$statut.') AND Tarea_id NOT IN('.$statut1.') AND Tarea_id in(select Tarea_id from tarea where Usua_id= :usua_id) AND Cose_id IN(select Cose_id from cosecha where Culti_id IN(select Culti_id from cultivo where Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id)))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos REALIZA_EMPLEA_TAREA_COSECHA: '. $e->getMessage();
            $stmt->rollback(); 
          }
       }

      if(!empty($Array38)){
         try{
            if($Array38[0]=="TABLAVACIA"){
            $stmt1 = $con->prepare('delete from realiza_emplea_tarea_cultivo where Tarea_id in(select Tarea_id from tarea where Usua_id= :usua_id) AND Culti_id IN(select Culti_id from cultivo where Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
           }
          else{
            $auxiliar= array();
            $auxiliar1= array();
         for($i = 0; $i < count($Array38); $i++) {
          $existencia=Verifica_Existencia2("REALIZA_TAREA_CULTIVO",$Array38[$i]['Culti_nombre'],$Array38[$i]['Tarea_nombre'],$usua_id);
        
          if($existencia){
            $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre=:culti_nombre AND Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id)");
            $stmt->bindParam(':culti_nombre',$Array38[$i]['Culti_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetch();

            $stmt = $con->prepare("select Tarea_id from tarea where Tarea_nombre=:tarea_nombre AND Usua_id=:usua_id");
            $stmt->bindParam(':tarea_nombre',$Array38[$i]['Tarea_nombre'], PDO::PARAM_INT);
            $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt->execute();
            $result=$stmt->fetch();

            if($results && $result){
                $id_cultivo=$results[0];
                $id_tarea=$result[0];
                $stmt = $con->prepare("UPDATE realiza_emplea_tarea_cultivo SET Tarea_id=:tarea_id,Culti_id=:culti_id WHERE Tarea_id=:tarea_id AND Culti_id=:culti_id");
                $stmt->bindParam(':culti_id',$id_cultivo, PDO::PARAM_INT);
                $stmt->bindParam(':tarea_id',$id_tarea, PDO::PARAM_INT);
                $stmt->execute();
            }
          }
          else{ //Sino existe inserte     `Insum_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             $stmt = $con->prepare("select Culti_id from cultivo where Culti_nombre=:culti_nombre AND Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id)");
             $stmt->bindParam(':culti_nombre',$Array38[$i]['Culti_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $results=$stmt->fetch();

             $stmt = $con->prepare("select Tarea_id from tarea where Tarea_nombre=:tarea_nombre AND Usua_id= :usua_id");
             $stmt->bindParam(':tarea_nombre',$Array38[$i]['Tarea_nombre'], PDO::PARAM_INT);
             $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
             $stmt->execute();
             $result=$stmt->fetch();

             if($results && $result){
                $id_cultivo=$results[0];
                $id_tarea=$result[0];
                $stmt = $con->prepare("INSERT INTO realiza_emplea_tarea_cultivo VALUES(null,:tarea_id,:culti_id)");
                $stmt->bindParam(':culti_id',$id_cultivo, PDO::PARAM_INT);
                $stmt->bindParam(':tarea_id',$id_tarea, PDO::PARAM_INT);
                $stmt->execute();
            }
           }
             $var='"'.$id_cultivo.'"';
             $var1='"'.$id_tarea.'"';
             array_push($auxiliar,$var);
             array_push($auxiliar1,$var1);
           }
            $statut = implode(',',$auxiliar);
            $statut1 = implode(',',$auxiliar1);
            $stmt1 = $con->prepare('delete from realiza_emplea_tarea_cultivo where Culti_id NOT IN('.$statut.') AND Tarea_id NOT IN('.$statut1.') AND Tarea_id in(select Tarea_id from tarea where Usua_id= :usua_id) AND Culti_id IN(select Culti_id from cultivo where Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id))');
            $stmt1->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
            $stmt1->execute();
            echo 9;
           } 
          }
           catch(Exception $e) { 
            echo 'Error conectando con la base de datos REALIZA_EMPLEA_TAREA_CULTIVO: '. $e->getMessage();
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
  else if(isset($_POST["Actualizar_Movil"])){
    try{
        $se=json_decode(stripslashes($_POST["Actualizar_Movil"]));
        $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
        $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $usua_id=$se->usua_id;

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
           }
           else{
              $datos['AFECTA_ENFERMEDADES']="TABLAVACIA";
           }

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
           }
           else{
              $datos['AFECTA_MALEZA']="TABLAVACIA";          
           }
 
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
           }
           else{
             $datos['AFECTA_PLAGA']="TABLAVACIA";        
           }

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

       $stmt = $con->prepare("select * from empleado where Usua_id= :usua_id");
       $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
       $stmt->execute();
       $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($result)){// Se consultan los empleados, si hay empleados se consultan los pagos
           $datos['EMPLEADO']=$result;
           //Si hay empleados puede que existan tareas
           // CONSULTA DE TAREAS
           $stmt = $con->prepare("select * from tarea where Emple_id in(select Emple_id from empleado where Usua_id= :usua_id)");
           $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
           $stmt->execute();
           $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

           if(!empty($result)){//Si hay tareas puede que se usen insumos
              $datos['TAREAS']=$result;
                     
              $stmt = $con->prepare("select * from utiliza_insum_tarea where Tarea_id in(select Tarea_id from tarea where Emple_id in(select Emple_id from empleado where Usua_id= :usua_id))");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                      
              if(!empty($result)){//Si hay tareas puede que se usen insumos
                 $datos['UTILIZA_INSUM_TAREA']=$result;
              }
              else{
                 $datos['UTILIZA_INSUM_TAREA']="TABLAVACIA";         
              }

              $stmt = $con->prepare("select * from realiza_emplea_tarea_cultivo where Tarea_id in(select Tarea_id from tarea where Usua_id=:usua_id) AND Culti_id IN(select Culti_id from cultivo where Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id))");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                      
              if(!empty($result)){//Si hay tareas puede que se usen insumos
                 $datos['REALIZA_EMPLEA_TAREA_CULTIVO']=$result;
              }
              else{
                $datos['REALIZA_EMPLEA_TAREA_CULTIVO']="TABLAVACIA"; 
              }

              $stmt = $con->prepare("select * from realiza_emplea_tarea_cosecha where Tarea_id in(select Tarea_id from tarea where Usua_id= :usua_id) AND Cose_id IN(select Cose_id from cosecha where Culti_id IN(select Culti_id from cultivo where Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id)))");
              $stmt->bindParam(':usua_id',$usua_id, PDO::PARAM_INT);
              $stmt->execute();
              $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                      
              if(!empty($result)){//Si hay tareas puede que se usen insumos
                 $datos['REALIZA_EMPLEA_TAREA_COSECHA']=$result;
              }
              else{
                $datos['REALIZA_EMPLEA_TAREA_COSECHA']="TABLAVACIA"; 
              }
           } 
           else{
             $datos['TAREAS']="TABLAVACIA";        
           }
        }
       else{
         $datos['EMPLEADO']=="TABLAVACIA";
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
    else if($tabla=='NODOS'){
       $stmt = $cone->prepare("select Sens_id from nodosensor where Sens_mac= :sens_mac AND Culti_id in(select Culti_id from cultivo where Parce_id in(select Parce_id from parcela where Usua_id= :usua_id))");
       $stmt->bindParam(':sens_mac',$campo1, PDO::PARAM_INT);
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
      $stmt = $cone->prepare("select Afec_id from afecta_maleza where Culti_id in(select Culti_id from cultivo where Culti_nombre= :culti_nombre AND Parce_id in(select Parce_id from parcela where Usua_id= :usua_id)) AND Male_id in(select Male_id from maleza where Male_nombre= :male_nombre AND Usua_id= :usua_id)");
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
    else if($tabla=='REALIZA_TAREA_CULTIVO'){
      $stmt = $cone->prepare("select Labor_id from realiza_emplea_tarea_cultivo where Tarea_id in(select Tarea_id from tarea where Tarea_nombre=:tarea_nombre AND Usua_id=:usua_id) AND Culti_id in(select Culti_id from cultivo where Culti_nombre=:culti_nombre AND Parce_id IN(select Parce_id from parcela where Usua_id=:usua_id))");
      $stmt->bindParam(':culti_nombre',$campo1, PDO::PARAM_INT);
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
    else if($tabla=='REALIZA_TAREA_COSECHA'){
      $stmt = $cone->prepare("select Labor_id from realiza_emplea_tarea_cosecha where Tarea_id in(select Tarea_id from tarea where Tarea_nombre=:tarea_nombre AND Usua_id=:usua_id) AND Cose_id IN(select Cose_id from cosecha where Cose_nombre=:cose_nombre AND Culti_id IN(select Culti_id from cultivo where Parce_id IN(select Parce_id from parcela where Usua_id= :usua_id)))");
      $stmt->bindParam(':cose_nombre',$campo1, PDO::PARAM_INT);
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