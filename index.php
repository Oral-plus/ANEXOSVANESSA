<?php
session_start(); // Iniciar la sesión

// Conexión a la base de datos SQL Server (sin encriptación ni config.php)
$serverName = "HERCULES"; // Nombre del servidor SQL Server
$connectionOptions = array(
    "Database" => "RBOSKY3", // Nombre de la base de datos
    "Uid" => "sa",          // Usuario
    "PWD" => "Sky2022*!"    // Contraseña
);

// Crear la conexión
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Verificar la conexión
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$docNum = ""; // Inicializar la variable
$filePath = ""; // Inicializar la variable para la ruta del archivo

// Verificar si se ha enviado el formulario para buscar DocNum
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search']) && isset($_POST['inputDocNum'])) {
        // Obtener el número ingresado por el usuario
        $inputDocNum = $_POST['inputDocNum'];

        // Consultar el DocNum y la ruta del archivo
        $sql = "SELECT T1.DocNum, T0.AbsEntry, T0.trgtPath 
                FROM OINV T1 
                LEFT JOIN ATC1 T0 ON T0.AbsEntry = T1.DocEntry 
                WHERE T1.DocNum = ?";
        $params = array($inputDocNum);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Obtener el resultado
        if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $_SESSION['docNum'] = $row['DocNum'];
            $_SESSION['absEntry'] = $row['AbsEntry'];
            $docNum = $_SESSION['docNum'];
            $filePath = $row['trgtPath'];
        } else {
            $_SESSION['docNum'] = "";
            $docNum = "No se encontró el documento.";
            $filePath = "";
        }

        sqlsrv_free_stmt($stmt);
    }

    // Verificar si se ha enviado el formulario para subir archivo
    if (isset($_POST['upload']) && isset($_FILES['fileUpload'])) {
        $file = $_FILES['fileUpload'];

        // Verificar errores en la subida del archivo
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $file['tmp_name'];
            $fileName = $file['name'];
            $fileSize = $file['size'];
            $fileType = $file['type'];

            // Definir la ruta de destino para el archivo
            $uploadDir = 'IMG'; // Ruta relativa en el directorio raíz del servidor web
            $destination = $uploadDir . '/' . $fileName;

            // Verificar si la carpeta de destino existe
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Mover el archivo a la carpeta de destino
            if (move_uploaded_file($fileTmpPath, $destination)) {
                echo "Archivo subido correctamente.";

                // Actualizar la ruta del archivo en la base de datos
                $updateSql = "UPDATE ATC1 SET trgtPath = ? WHERE AbsEntry = (SELECT DocEntry FROM OINV WHERE DocNum = ?)";
                $updateParams = array($destination, $_SESSION['docNum']);
                $updateStmt = sqlsrv_query($conn, $updateSql, $updateParams);

                if ($updateStmt === false) {
                    die(print_r(sqlsrv_errors(), true));
                }

                sqlsrv_free_stmt($updateStmt);
            } else {
                echo "Error al subir el archivo.";
            }
        } else {
            echo "Error en la subida del archivo: " . $file['error'];
        }
    }
}

// Cerrar la conexión
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <center>  <title>Buscar DocNum y Subir Archivo</title>
    <style>

        /* From Uiverse.io by 3bdel3ziz-T */ 
.container {
  --transition: 350ms;
  --folder-W: 120px;
  width: 150px;
  --folder-H: 80px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  padding: 10px;
  background: linear-gradient(135deg, #6dd5ed, #2193b0);
  border-radius: 15px;
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
  height: calc(var(--folder-H) * 1.1);
  position: relative;
}

.folder {
  position: absolute;
  top: -20px;
  left: calc(50% - 60px);
  animation: float 2.5s infinite ease-in-out;
  transition: transform var(--transition) ease;
}

.folder:hover {
  transform: scale(1.05);
}

.folder .front-side,
.folder .back-side {
  position: absolute;
  transition: transform var(--transition);
  transform-origin: bottom center;
}

.folder .back-side::before,
.folder .back-side::after {
  content: "";
  display: block;
  background-color: white;
  opacity: 0.5;
  z-index: 0;
  width: var(--folder-W);
  height: var(--folder-H);
  position: absolute;
  transform-origin: bottom center;
  border-radius: 15px;
  transition: transform 350ms;
  z-index: 0;
}

.container:hover .back-side::before {
  transform: rotateX(-5deg) skewX(5deg);
}
.container:hover .back-side::after {
  transform: rotateX(-15deg) skewX(12deg);
}

.folder .front-side {
  z-index: 1;
}

.container:hover .front-side {
  transform: rotateX(-40deg) skewX(15deg);
}

.folder .tip {
  background: linear-gradient(135deg, #ff9a56, #ff6f56);
  width: 80px;
  height: 20px;
  border-radius: 12px 12px 0 0;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  position: absolute;
  top: -10px;
  z-index: 2;
}

.folder .cover {
  background: linear-gradient(135deg, #ffe563, #ffc663);
  width: var(--folder-W);
  height: var(--folder-H);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
  border-radius: 10px;
}

.custom-file-upload {
  font-size: 1.1em;
  color: #ffffff;
  text-align: center;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 10px;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: background var(--transition) ease;
  display: inline-block;
  width: 100%;
  padding: 10px 35px;
  position: relative;
}

.custom-file-upload:hover {
  background: rgba(255, 255, 255, 0.4);
}

.custom-file-upload input[type="file"] {
  display: none;
}

@keyframes float {
  0% {
    transform: translateY(0px);
  }

  50% {
    transform: translateY(-20px);
  }

  100% {
    transform: translateY(0px);
  }
}

        /* From Uiverse.io by cohencoo */ 
.input {
  border-radius: 10px;
  outline: 2px solid #FEBF00;
  border: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  background-color: #e2e2e2;
  outline-offset: 3px;
  padding: 10px 1rem;
  transition: 0.25s;
}

.input:focus {
  outline-offset: 5px;
  background-color: #fff
}
/* From Uiverse.io by vinodjangid07 */ 
.button {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background-color: rgb(20, 20, 20);
  border: none;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0px 0px 0px 4px rgba(180, 160, 255, 0.253);
  cursor: pointer;
  transition-duration: 0.3s;
  overflow: hidden;
  position: relative;
}

.svgIcon {
  width: 12px;
  transition-duration: 0.3s;
}

.svgIcon path {
  fill: white;
}

.button:hover {
  width: 140px;
  border-radius: 50px;
  transition-duration: 0.3s;
  background-color: rgb(181, 160, 255);
  align-items: center;
}

.button:hover .svgIcon {
  /* width: 20px; */
  transition-duration: 0.3s;
  transform: translateY(-200%);
}

.button::before {
  position: absolute;
  bottom: -20px;
  content: "Back to Top";
  color: white;
  /* transition-duration: .3s; */
  font-size: 0px;
}

.button:hover::before {
  font-size: 13px;
  opacity: 1;
  bottom: unset;
  /* transform: translateY(-30px); */
  transition-duration: 0.3s;
}
/* From Uiverse.io by CristianMontoya98 */ 
.btn {
 width: 4.5em;
 height: 2.3em;
 margin: 0.5em;
 background: black;
 color: white;
 border: none;
 border-radius: 0.625em;
 font-size: 20px;
 font-weight: bold;
 cursor: pointer;
 position: relative;
 z-index: 1;
 overflow: hidden;
}

button:hover {
 color: black;
}

button:after {
 content: "";
 background: white;
 position: absolute;
 z-index: -1;
 left: -20%;
 right: -20%;
 top: 0;
 bottom: 0;
 transform: skewX(-45deg) scale(0, 1);
 transition: all 0.5s;
}

button:hover:after {
 transform: skewX(-45deg) scale(1, 1);
 -webkit-transition: all 0.5s;
 transition: all 0.5s;
}
    </style>
</head>
<body>

<h1> Documentacion Firmada</h1>
    <form method="POST" action="" enctype="multipart/form-data">
        <br>
        <label for="inputDocNum">Ingrese el Número de Documento:</label>   <br>   <br>
        <input type="text" id="inputDocNum" name="inputDocNum" class="input" required>
   <br>
   <br>
  
        <button class="btn" type="submit" name="search"> Buscar
</button>
    </form>
    <br>
  
<?php echo htmlspecialchars($docNum); ?>

    <br><br> <br><br>



    <form method="POST" action="" enctype="multipart/form-data">
    
    <div class="container">
  <div class="folder">
    <div class="front-side">
      <div class="tip"></div>
      <div class="cover"></div>
    </div>
    <div class="back-side cover"></div>
  </div>
  <label class="custom-file-upload">
    <input class="title" type="file" id="fileUpload" name="fileUpload" type="file"  required />
   Seleccionar
  </label>
</div>
<br>
    

        <button class="button" type="submit" name="upload">
  <svg class="svgIcon" viewBox="0 0 384 512">
    <path
      d="M214.6 41.4c-12.5-12.5-32.8-12.5-45.3 0l-160 160c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L160 141.2V448c0 17.7 14.3 32 32 32s32-14.3 32-32V141.2L329.4 246.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-160-160z"
    ></path>
  </svg>
</button>

        
        <br><br><br>
    </form>

    <?php if ($filePath): ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Archivo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank"><?php echo htmlspecialchars(basename($filePath)); ?></a>
                    </td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>
    </center>
    
</body>
</html>
