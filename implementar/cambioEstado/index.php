<?php
  require '../Login/comprobarSesion.php';

  if($_SESSION['userCambioEstado'] == 0){
      header('location: ../Login/sinPermisos.php');
  }
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cambiar estado de medidores</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

  <div class="container mt-5">
    <h2 class="mb-4">Actualizar Estado de Medidores</h2>

    <div class="mb-3">
      <label for="estado" class="form-label">Estado de los medidores</label>
      <select class="form-select" id="estado">
        <option value="2">LANDIS (2)</option>
        <option value="3">ENERI (3)</option>
        <option value="4">ALDESA (4)</option>
        <option value="6">SINAMED (6)</option>
        <option value="7">SIGAMI CENTRALIZADO (7)</option>
      </select>
    </div>

    <div class="mb-3">
      <label for="medidores" class="form-label">Pega los medidores aquí (uno por línea)</label>
      <textarea class="form-control" id="medidores" rows="12" placeholder="Ej:&#10;E807PH&#10;E806PH&#10;E808PH"></textarea>
    </div>

    <button class="btn btn-primary" onclick="enviarMedidores()">Actualizar</button>

    <div id="resultado" class="mt-4 alert d-none"></div>
  </div>

  <script>
    function enviarMedidores() {
      const estado = document.getElementById("estado").value;
      const textarea = document.getElementById("medidores");
      const resultado = document.getElementById("resultado");

      const datos = textarea.value.trim();
      if (!datos) {
        resultado.className = 'alert alert-warning';
        resultado.textContent = "⚠️ No hay medidores para enviar.";
        resultado.classList.remove("d-none");
        return;
      }

      const medidores = datos.split("\n").map(m => m.trim()).filter(Boolean);

      fetch('actualizar_medidores.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ estado: estado, medidores: medidores })
      })
      .then(resp => resp.json())
      .then(data => {
        if (data.exito) {
          resultado.className = 'alert alert-success';
          resultado.textContent = "✅ ÉXITO: Medidores cambiados exitosamente de estado.";
        } else {
          resultado.className = 'alert alert-danger';
          resultado.textContent = "❌ Error: " + (data.mensaje || "No se pudo completar la operación.");
        }
        resultado.classList.remove("d-none");
      })
      .catch(err => {
        console.error(err);
        resultado.className = 'alert alert-danger';
        resultado.textContent = "❌ Error en la comunicación con el servidor.";
        resultado.classList.remove("d-none");
      });
    }
  </script>

</body>
</html>
