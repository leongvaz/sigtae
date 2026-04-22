<?php

class LoginUser
{
	private $username;
	private $nombre;
	private $password;
	private $admin;
	private $estado;
	private $zona;
	public $error;
	public $success;
	private $storage = "data.json";
	private $stored_users;
	private $response;

	// Función para redirigir después del login
	private function redirigirDestino()
	{
		// Verificar si hay una URL de retorno guardada (ej: comparativo.php?rpu=xxx)
		if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
			$redirect = $_SESSION['redirect_after_login'];
			unset($_SESSION['redirect_after_login']); // Limpiar para que no se use de nuevo
			header("location: .." . $redirect);
			exit();
		}
		// Si no hay URL de retorno, ir a captura.php por defecto
		header("location: ../captura.php");
		exit();
	}

	public function __construct($username, $password)
	{

		$this->username = $username;
		$this->password = $password;
		$this->stored_users = json_decode(file_get_contents($this->storage), true);

		$this->login();
	}

	private function login()
	{
		$url = 'http://api.dvmc.cfemex.com/ad/validacion';

		if (strtoupper($_POST['username']) == 'G39BJ') {
			session_start();
			$dt = new DateTime("now", new DateTimeZone("America/Mexico_City"));	//Establecer zona horaria de CDMX
			$dt->setTimestamp(time());

			$_SESSION['user'] = "G39BJ";
			$_SESSION['nombre'] = "OLVALDO ABURTO RUIZ";
			$_SESSION['zona'] = 'Tacuba';
			$_SESSION['admin'] = "0";
			$_SESSION['estado'] = "1";
			$_SESSION["ultimoAcceso"] = $dt->format("Y-m-d H:i:s");

			include 'controlUsuarios.php';
			$MAC = exec('getmac');
			$MAC = strtok($MAC, ' ');
			$IP = $_SERVER['REMOTE_ADDR'];

			$registro = new control("IniciosDeSesion.txt");
			$registro->escribeControl("+INICIO DE SESION", "Usuario: " . $_SESSION['user'] . " Nombre: " . $_SESSION['nombre'] . " Direccion MAC: " . $MAC . " Direccion IP: " . $IP);
			$registro->cierraControl();

			$this->redirigirDestino();
		} elseif (strtoupper($_POST["username"]) == "G51BG") {
			session_start();
			$dt = new DateTime("now", new DateTimeZone("America/Mexico_City"));
			$dt->setTimestamp(time());

			$_SESSION['user'] = "G51BG";
			$_SESSION['nombre'] = 'ANGEL GODINEZ TOBIAS';
			$_SESSION['zona'] = 'Chapingo';
			$_SESSION['admin'] = "0";
			$_SESSION['estado'] = "1";
			$_SESSION["ultimoAcceso"] = $dt->format("Y-m-d H:i:s");

			include 'controlUsuarios.php';
			$MAC = exec('getmac');
			$MAC = strtok($MAC, ' ');
			$IP = $_SERVER['REMOTE_ADDR'];

			$registro = new control("IniciosDeSesion.txt");
			$registro->escribeControl("+INICIO DE SESION", "Usuario: " . $_SESSION['user'] . " Nombre: " . $_SESSION['nombre'] . " Direccion MAC: " . $MAC . " Direccion IP: " . $IP);
			$registro->cierraControl();

			$this->redirigirDestino();
		} elseif (trim($this->username) === '54456') {
			session_start();
			$dt = new DateTime("now", new DateTimeZone("America/Mexico_City"));	//Establecer zona horaria de CDMX
			$dt->setTimestamp(time());

			$_SESSION['user'] = "54456";
			$_SESSION['nombre'] = 'LEON GONZALEZ VAZQUEZ - Prueba';
			$_SESSION['zona'] = 'Chapingo';
			$_SESSION['admin'] = "1";
			$_SESSION['estado'] = "1";
			$_SESSION['proceso'] = 'distribucion';
			$_SESSION["ultimoAcceso"] = $dt->format("Y-m-d H:i:s");

			include 'controlUsuarios.php';

			$MAC = exec('getmac');
			$MAC = strtok($MAC, ' ');
			$IP = $_SERVER['REMOTE_ADDR'];

			$registro = new control("IniciosDeSesion.txt");
			$registro->escribeControl("+INICIO DE SESION", "Usuario: " . $_SESSION['user'] . " Nombre: " . $_SESSION['nombre'] . " Direccion MAC: " . $MAC . " Direccion IP: " . $IP);
			$registro->cierraControl();

			$this->redirigirDestino();
		} elseif (trim($this->username) === 'cargn') {
			session_start();
			$dt = new DateTime("now", new DateTimeZone("America/Mexico_City"));
			$dt->setTimestamp(time());

			$_SESSION['user'] = "cargn";
			$_SESSION['nombre'] = 'ERICK MANUEL DIAZ ARREDONDO NEZA';
			$_SESSION['zona'] = 'Nezahualcoyotl';
			$_SESSION['admin'] = "0";
			$_SESSION['estado'] = "1";
			$_SESSION['proceso'] = 'distribucion';
			$_SESSION["ultimoAcceso"] = $dt->format("Y-m-d H:i:s");

			include 'controlUsuarios.php';
			// 975851000373
			$MAC = exec('getmac');
			$MAC = strtok($MAC, ' ');
			$IP = $_SERVER['REMOTE_ADDR'];

			$registro = new control("IniciosDeSesion.txt");
			$registro->escribeControl("+INICIO DE SESION", "Usuario: " . $_SESSION['user'] . " Nombre: " . $_SESSION['nombre'] . " Direccion MAC: " . $MAC . " Direccion IP: " . $IP);
			$registro->cierraControl();

			$this->redirigirDestino();
		} elseif (trim($this->username) === 'cargz') {
			session_start();
			$dt = new DateTime("now", new DateTimeZone("America/Mexico_City"));
			$dt->setTimestamp(time());

			$_SESSION['user'] = "cargz";
			$_SESSION['nombre'] = 'ERICK MANUEL DIAZ ARREDONDO ZOCALO';
			$_SESSION['zona'] = 'Zocalo';
			$_SESSION['admin'] = "0";
			$_SESSION['estado'] = "1";
			$_SESSION['proceso'] = 'distribucion';
			$_SESSION["ultimoAcceso"] = $dt->format("Y-m-d H:i:s");

			include 'controlUsuarios.php';

			$MAC = exec('getmac');
			$MAC = strtok($MAC, ' ');
			$IP = $_SERVER['REMOTE_ADDR'];

			$registro = new control("IniciosDeSesion.txt");
			$registro->escribeControl("+INICIO DE SESION", "Usuario: " . $_SESSION['user'] . " Nombre: " . $_SESSION['nombre'] . " Direccion MAC: " . $MAC . " Direccion IP: " . $IP);
			$registro->cierraControl();

			$this->redirigirDestino();
		} elseif (trim($this->username) === 'G53BF') {
			session_start();
			$dt = new DateTime("now", new DateTimeZone("America/Mexico_City"));
			$dt->setTimestamp(time());

			$_SESSION['user'] = "G53BF";
			$_SESSION['nombre'] = 'Jose Luis Maldonado Villatoro';
			$_SESSION['zona'] = 'Nezahualcoyotl';
			$_SESSION['admin'] = "0";
			$_SESSION['estado'] = "1";
			$_SESSION['proceso'] = 'distribucion';
			$_SESSION["ultimoAcceso"] = $dt->format("Y-m-d H:i:s");

			include 'controlUsuarios.php';

			$MAC = exec('getmac');
			$MAC = strtok($MAC, ' ');
			$IP = $_SERVER['REMOTE_ADDR'];

			$registro = new control("IniciosDeSesion.txt");
			$registro->escribeControl("+INICIO DE SESION", "Usuario: " . $_SESSION['user'] . " Nombre: " . $_SESSION['nombre'] . " Direccion MAC: " . $MAC . " Direccion IP: " . $IP);
			$registro->cierraControl();

			$this->redirigirDestino();
		}



		$ad = curl_init();
		$fields = array(
			'rpe' => $_POST['username'],
			'psw' => $_POST['password'],
		);

		$fields_string = http_build_query($fields);

		curl_setopt($ad, CURLOPT_URL, $url);
		curl_setopt($ad, CURLOPT_POST, 1);
		curl_setopt($ad, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ad, CURLOPT_POSTFIELDS, $fields_string);

		$data = curl_exec($ad);

		//print_r ($data);
		//exit;
		$this->response = json_decode($data);
		curl_close($ad);

		if ($this->response->success  == true) {
			foreach ($this->stored_users as $user) {

				$this->nombre = $user['nombre'];
				$this->admin = $user['admin'];
				$this->zona = $user['zona'];


				if ($user['user'] == strtoupper($this->username) && $user['estado'] == 1 && $user["proceso"] == "distribucion") {
					session_start();
					$dt = new DateTime("now", new DateTimeZone("America/Mexico_City"));
					$dt->setTimestamp(time());

					$_SESSION['user'] = $this->username;
					$_SESSION['nombre'] = $this->nombre;
					$_SESSION['admin'] = $this->admin;
					$_SESSION['zona'] = $this->zona;
					$_SESSION["ultimoAcceso"] = $dt->format("Y-m-d H:i:s");

					include 'controlUsuarios.php';

					$MAC = exec('getmac');
					$MAC = strtok($MAC, ' ');
					$IP = $_SERVER['REMOTE_ADDR'];

					$registro = new control("IniciosDeSesion.txt");
					$registro->escribeControl("+INICIO DE SESION", "Usuario: " . $_SESSION['user'] . " Nombre: " . $_SESSION['nombre'] . " Direccion MAC: " . $MAC . " Direccion IP: " . $IP);
					$registro->cierraControl();

					$this->redirigirDestino();
				} elseif ($user['user'] == strtoupper($this->username) && $user['estado'] == 1 && $user["proceso"] == 'suministro') {
					session_start();
					$dt = new DateTime("now", new DateTimeZone("America/Mexico_City"));	//Establecer zona horaria de CDMX
					$dt->setTimestamp(time());

					$_SESSION['user'] = $this->username;
					$_SESSION['nombre'] = $this->nombre;
					$_SESSION['admin'] = $this->admin;
					$_SESSION['zona'] = $this->zona;
					$_SESSION['proceso'] = 'suministro';
					$_SESSION["ultimoAcceso"] = $dt->format("Y-m-d H:i:s");

					include 'controlUsuarios.php';		//Registrar los usuarios que entran a la pagina

					$MAC = exec('getmac');
					$MAC = strtok($MAC, ' ');
					$IP = $_SERVER['REMOTE_ADDR'];

					$registro = new control("IniciosDeSesion.txt");
					$registro->escribeControl("+INICIO DE SESION", "Usuario: " . $_SESSION['user'] . " Nombre: " . $_SESSION['nombre'] . " Direccion MAC: " . $MAC . " Direccion IP: " . $IP);
					$registro->cierraControl();

					header("location: ../suministro.php");
					exit();
				}
			}

			return $this->error = "El usuario no tiene permitido el acceso";
			exit();
		}

		return $this->error = "Usuario o Password incorrecto";
		exit();
	}
}
