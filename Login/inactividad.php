<?php
    $dt=new DateTime("now", new DateTimeZone("America/Mexico_City"));	
	$dt->setTimestamp(time());

    $ahora = $dt->format("Y-m-d H:i:s") ;
    
    $tiempo_transcurrido = (strtotime($ahora)-strtotime($_SESSION['ultimoAcceso']));

    if(isset($_SESSION['user'])){
        if($tiempo_transcurrido >=  1800){
            header("Location: Login/cerrarSession.php");
        }
    } else{
        header("Location: ../index.php");
    }
?>
