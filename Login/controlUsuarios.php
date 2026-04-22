<?php
    class control {
        private $filecontrol;

        function __construct($path)
        {
            $this->filecontrol=fopen($path, "a");
        }
        function escribeControl ($tipo,$mensaje){
            $date=new DateTime("now", new DateTimeZone("America/Mexico_City"));
            fputs($this->filecontrol,"[". $tipo ."][". $date->format("d-m-Y H:i:s") ."]: ". $mensaje . "\n");
        }
        function cierraControl(){
            fclose($this->filecontrol);
        }
    }
?>

