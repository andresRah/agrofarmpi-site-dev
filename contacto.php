<?php

if(isset($_POST["contacto"])){
    $se=json_decode(stripslashes($_POST["contacto"]));

    $nombre = $se->nombre;
    $email = $se->email;
    $mensaje = $se->mensaje;
    $asunto = $se->asunto;
    $para = 'andres.arevalopar@gmail.com';
    $header = 'From: ' . $email;
    $msjCorreo = "Nombre: ".$nombre." E-Mail: ".$email." Mensaje: ".$mensaje;

    try{
      $ch = curl_init();
      $string_path =  "{\"personalizations\":
                          [{\"to\": [{\"email\": \"andres.arevalopar@gmail.com\"},{\"email\": \"carlosriapira1@gmail.com\"}]}],
                            \"from\": {\"email\": \"".$email."\"},
                            \"subject\": \"🍓 Agrofarmpi - ".$asunto."\",
                            \"content\": [{\"type\": \"text/plain\", \"value\": \"".$msjCorreo."\"}]}";

      echo $string_path;

      curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $string_path);

      /*curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"personalizations\": [{\"to\": [{\"email\":carlosriapira1@gmail.com\"\"},{\"email\":andres.arevalopar@gmail.com\"\"}]}],\"from\":
                                            {\"email\": \"$email\"},\"subject\": \"'🍓 Agrofarmpi nuevo contacto - '.$asunto\",\"content\":
                                            [{\"type\": \"text/html\", \"value\": \"<html><head></head><body><h3>Contenido cuerpo! - <br/>'.$mensaje.'<br></h3><br/><h3>Nombre: '.$nombre.'</h3><br/><strong>Email: '.$email.'</strong></body></html>\"}]}");*/


      $headers = array();
      $headers[] = 'Authorization: Bearer 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';
      $headers[] = 'Content-Type: application/json';
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $result = curl_exec($ch);
      if (curl_errno($ch)) {
          echo 'Error:' . curl_error($ch);
      }
      curl_close($ch);
    }
    catch(Exception $e) {
      echo 7;
      echo 'Error enviando correo electrónico' . $e->getMessage();
    }
}

else if(isset($_POST["registro"])){
  try{
    $se=json_decode(stripslashes($_POST["registro"]));
    $auxiliar1=0;
    $auxiliar2=0;
    $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
    $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $con->prepare("select Usua_id from usuario where Usua_correo=:usua_correo");
    $stmt->bindParam(':usua_correo',$se->email, PDO::PARAM_STR);
    $stmt->execute();
    $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
    if(!empty($result)){ //Se consultan las cosechas            
      $auxiliar1=1;
    } 

    $stmt = $con->prepare("select Usua_id from usuario where Usua_name=:usua_name");
    $stmt->bindParam(':usua_name',$se->nick, PDO::PARAM_STR);
    $stmt->execute();
    $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
    if(!empty($result)){ //Se consultan las cosechas            
      $auxiliar2=2;
    } 

    if($auxiliar1==1 && $auxiliar2==2){
      echo 3;
    }
    else if($auxiliar1==1 && $auxiliar2==0){
      echo 1;
    }
    else if($auxiliar1==0 && $auxiliar2==2){
      echo 2;
    }
    else{
      $stmt = $con->prepare("INSERT INTO usuario VALUES(null,:Usua_identificacion,:Usua_name,:Usua_nombre,:Usua_apellido,:Usua_telefono,:Usua_celular,:Usua_direccion,:Usua_departamento,:Usua_ciudad,:Usua_correo,:Usua_contrasena,'2014-05-06 11:11:11','2014-05-06 11:11:11','fondo1.jpg','Sonido2.wav')");

      $stmt->bindParam(':Usua_identificacion',$se->identificacion, PDO::PARAM_INT);
      $stmt->bindParam(':Usua_name',$se->nick, PDO::PARAM_INT);
      $stmt->bindParam(':Usua_nombre',$se->nombres, PDO::PARAM_INT);
      $stmt->bindParam(':Usua_apellido',$se->apellidos, PDO::PARAM_INT);
      $stmt->bindParam(':Usua_telefono',$se->telefono, PDO::PARAM_INT);
      $stmt->bindParam(':Usua_celular',$se->celular, PDO::PARAM_INT);
      $stmt->bindParam(':Usua_direccion',$se->direccion, PDO::PARAM_INT);
      $stmt->bindParam(':Usua_departamento',$se->departamento, PDO::PARAM_INT);
      $stmt->bindParam(':Usua_ciudad',$se->ciudad, PDO::PARAM_INT);
      $stmt->bindParam(':Usua_correo',$se->email, PDO::PARAM_INT);
      $stmt->bindParam(':Usua_contrasena',$se->pass, PDO::PARAM_INT);
      $stmt->execute();

      echo 6;
    }
   }
   catch(Exception $e) { 
      echo 7;
      echo 'Error conectando con la base de datos: PARCELA' . $e->getMessage();
   } 
}
else if(isset($_POST["acceso"])){
   try{
    $se=json_decode(stripslashes($_POST["acceso"]));

    $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
    $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $con->prepare("select * from usuario where (Usua_correo=:usua_correo OR Usua_name=:usua_name) AND Usua_contrasena=:usua_pass");
    $stmt->bindParam(':usua_correo',$se->user, PDO::PARAM_STR);
    $stmt->bindParam(':usua_name',$se->user, PDO::PARAM_STR);
    $stmt->bindParam(':usua_pass',md5($se->pass), PDO::PARAM_STR);
    $stmt->execute();
    $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    
    if(!empty($result)){          
      echo 6;
    } 
    else{
      echo 7;
    }
   }
   catch(Exception $e) { 
      echo 7;
      echo 'Error conectando con la base de datos: PARCELA' . $e->getMessage();
   } 
}
else if(isset($_POST["recuperacion"])){
  try{
    $se=json_decode(stripslashes($_POST["recuperacion"]));

    $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
    $con = new PDO('mysql:host=localhost;dbname=agrofarm_agrofarmpi', 'agrofarm_leo', 'Endgame55.',$params);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $con->prepare("select * from usuario where Usua_correo=:usua_correo");
    $stmt->bindParam(':usua_correo',$se->correo, PDO::PARAM_STR);
    $stmt->execute();
    $result=$stmt->fetch();
                    
   if(!empty($result)){ 

      $stmt = $con->prepare("update usuario set Usua_contrasena=:usua_contrasena where Usua_correo=:usua_correo");
      $stmt->bindParam(':usua_contrasena',$se->contrasena, PDO::PARAM_STR);
      $stmt->bindParam(':usua_correo',$se->correo, PDO::PARAM_STR);
      $stmt->execute();

      $email = $se->correo; 
      $correo = $se->correo; 
      $titulo = 'Recordatorio de Clave'; 
      $para = $se->correo; 
      $mensaje = ''; 
      $mensaje .= "Estimado  ".$result[3].' '.$result[4]; 
      $mensaje .= "\n"; 
      $mensaje .= "\n"; 
      $mensaje .= "Usted realizo una solicitud de cambio de clave."; 
      $mensaje .= "\n"; 
      $mensaje .= 'La solicitud se hizo para volver a enviar su contraseña a esta dirección, si no has solicitado este pedido de contraseña, ignore completamente este mensaje.'; 
      $mensaje .= "\n"; 
      $mensaje .= "\n"; 
      $mensaje .= 'Tu nombre de usuario es: '.$result[2]; 
      $mensaje .= "\n"; 
      $mensaje .= 'Tu clave es: '.$se->contrasena; 
      $mensaje .= "\n"; 
      $enviarmail = mail($correo, $titulo, $mensaje, "From:". 'administracion@agrofarmpi.com'); 
      
      echo 6;  //Correo de recuperacion de contraseña enviado
      } 
      else { 
      echo 7; //No existe ese correo registrado
      } 
   }
   catch(Exception $e) { 
      echo 7;
      echo 'Error conectando con la base de datos: PARCELA' . $e->getMessage();
   } 
}
?>
