<script>
</script>
<?php
session_start();
include 'conexion_bd.php'; 
// VERIFICA SI YA EXISTE EL USUARIO
if (!isset($_SESSION['usuario'])) {
    header("Location: admin.php");
    exit();
}
// OBTIENE USUARIO
$usuario = $_SESSION['usuario'];
// OBTIENE ID DE role_id
$stmt = $conn->prepare("SELECT role_id FROM usuarios WHERE nombre_usuario = :usuario LIMIT 1");
$stmt->bindParam(':usuario', $usuario);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $role_id = $result['role_id']; 
} else {
    header("Location: admin.php");
    exit();
}
// Definir los nombres de los roles
$rolesNombres = [
    1 => 'Super Administrador',
    2 => 'Administrador',
    3 => 'Usuario Líder',
    4 => 'Usuario'
];
// Obtener el nombre del rol correspondiente
$nombreRol = isset($rolesNombres[$role_id]) ? $rolesNombres[$role_id] : 'Desconocido';

// ROLES PERMITIDOS
$roles_permitidos = [1, 2, 3, 4]; 
// Verificar si el usuario tiene un rol permitido
if (!in_array($role_id, $roles_permitidos)) {
    // Redirige a la página de acceso denegado si no tiene permisos
    header("Location: acceso_denegado.php");
    exit();
}
// OBTENER PERMISOS
$stmt = $conn->prepare("SELECT permiso FROM permisos WHERE rol_id = :role_id");
$stmt->bindParam(':role_id', $role_id);
$stmt->execute();
$permisos = $stmt->fetchAll(PDO::FETCH_COLUMN);
// DEFINIR PERMISOS
$permisosPorRol = [
    1 => ['ver_panel', 'ver_usuarios', 'editar_usuarios', 'crear_usuarios', 'crear_movimientos', 'ver_graficas'], 
    2 => ['ver_panel', 'ver_usuarios', 'crear_movimientos', 'ver_graficas'], 
    3 => ['ver_panel', 'crear_movimientos'], 
    4 => ['ver_panel'],
];
// VERIFICA PERMISOS
$permisosPermitidos = $permisosPorRol[$role_id] ?? [];
$conn = null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>SIIMM</title>
    <link rel="stylesheet" href="style_interfaz.css">
    <link rel="shortcut icon" href="imagenes/mujer3.jpg" type="image/x-icon">
<!-- ICONOS -->
  <link rel="stylesheet" type="text/css" href="boxicons-2.1.4/css/boxicons.min.css">
</head>
<?php include 'popup.php'; ?>
<style>
    .resultadosChart {
        display: flex;
        z-index: 100000;
        background-color: white;
        position: fixed;
        left: 50%;
        top: 50%;
        bottom: 0;
        transform: translate(-50%, -50%);
        padding: 20px;
        border-radius: 10px;
        overflow-y: scroll;
        box-shadow: 0px 4px 9px 0px gray;
        width: 700px;
        height: 400px;
        align-items: center;
    }
    .resultadosChart {
        display: none;
        cursor: pointer;
    }
    .cerrarGrafic {
        position: absolute;
        right: 0;
        background: red;
        color: white;
        padding: 5px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        grid-gap: 5px;
        font-size: 12px;
        cursor: pointer;
        top: 10px;
        right: 10px;
    }
    .cerrarGrafic:hover {
        box-shadow: 0px 4px 6px 0px #ddd;
    }
    .cerrarGrafic ion-icon {
        font-size: 15px;
    }
</style>
<body>
    <div class="sidebar">
        <a href="principal.php" class="logo">
            <div class="logo-name"><span>MinMujer</span></div>
            <img src="imagenes/mujer3.jpg" style="display: none;">
        </a>
        <ul class="side-menu">
        <?php if (in_array('ver_panel', $permisosPermitidos)): ?>
                <li><a href="principal.php"><i class='bx bx-home'></i>PRINCIPAL</a></li>
            <?php endif; ?>
            <?php if (in_array('ver_graficas', $permisosPermitidos)): ?>
                <li><a href="graficas.php"><i class='bx bx-line-chart'></i>GRÁFICAS</a></li>
            <?php endif; ?>
            <?php if (in_array('crear_movimientos', $permisosPermitidos)): ?>
                <li><a href="form_movimientos.php"><i class='bx bx-file'></i>FORMULARIO</a></li>
            <?php endif; ?>
            <?php if (in_array('ver_panel', $permisosPermitidos)): ?>
                <li><a href="consultas_movimientos.php"><i class='bx bx-question-mark'></i>CONSULTAS</a></li>
            <?php endif; ?>
            <?php if (in_array('crear_usuarios', $permisosPermitidos)): ?>
                <li><a href="registro.php"><i class='bx bx-user-plus'></i>CREAR USUARIOS</a></li>
            <?php endif; ?>
            <?php if (in_array('editar_usuarios', $permisosPermitidos)): ?>
                <li><a href="editar_usuario.php"><i class='bx bx-user'></i>EDITAR USUARIOS</a></li>
            <?php endif; ?>
            <?php if (in_array('ver_usuarios', $permisosPermitidos)): ?>
                <li><a href="datos_mujeres.php"><i class='bx bx-female'></i>MUJERES</a></li>
            <?php endif; ?>
            <li><a href="#"><i class='bx bx-cog'></i>Settings</a></li>
        </ul>
    </div>
<!-- NAVEGACIÓN -->
    <div class="content">
        <nav>
            <i class='bx bx-menu'></i>
<!-- BUSCADOR DEL NAVEGADOR -->
<form action="#" method="GET" role="search">
    <div class="form-input">
        <label for="searchInput" class="visually-hidden"></label>
        <input type="search" id="searchInput" name="q" class="buscador" placeholder="Buscar" required>
        <button class="search-btn" type="submit"><i class='bx bx-search'></i></button>
    </div>
</form>
<!-- BOTON DE MODO CLARO Y OSCURO -->
    <button id="theme-toggle" class="theme-toggle">
        <i class='bx bx-sun' id="theme-icon"></i>
    </button>
<!-- ICONO DE NOTIFICACIONES -->
            <a href="#" class="notif">
                <i class='bx bx-bell'></i>
                <span class="count">12</span>
            </a>
<!-- OPCIONES DE PERFIL -->
            <div class="profile" aria-haspopup="true" role="button">
    <img src="images/logo.png">
    <ul class="profile-dropdown">
      <li><a href="editar_usuario_usuarios.php">Editar perfil</a></li>
      <li><a href="cerrar_sesion.php">Cerrar sesión</a></li>
    </ul>
  </div>
</nav>
<!-- MAIN -->
 <!-- ALERTAS -->
 <?php if ($mensaje_exitoso): ?>
    <div class="mensaje_exitoso"><?php echo $mensaje_exitoso; ?></div>
<?php endif; ?>
<?php if ($mensaje_erroneo): ?>
    <div class="mensaje_erroneo"><?php echo $mensaje_erroneo; ?></div>
<?php endif; ?>
        <main>
        <div class="header">
        <div class="left">
            <h1>Principal</h1>
        </div>
    </div>
    <div class="bienvenida">
        <h2 class="principal_d">Bienvenido: <p class="segundario_d"><?php echo htmlspecialchars($nombre_usuario); ?></p></h2>
        <h2 class="principal_d">Cuenta: <p class="segundario_d"><?php echo htmlspecialchars($nombreRol); ?></p></h2>
    </div>
       </main>
</div>
<!-- SCRIPTS -->
<script>
// SELECTORES
        const sideLinks = document.querySelectorAll('.sidebar .side-menu li a:not(.logout)');
        const menuBar = document.querySelector('.content nav .bx.bx-menu');
        const sideBar = document.querySelector('.sidebar');
        const searchBtn = document.querySelector('.content nav form .form-input button');
        const searchBtnIcon = document.querySelector('.content nav form .form-input button .bx');
        const searchForm = document.querySelector('.content nav form');
        const themeToggle = document.getElementById('theme-toggle');
        const body = document.body;
// FUNCIONES
        function toggleActiveLink(event) {
            sideLinks.forEach((link) => link.parentElement.classList.remove('active'));
            event.target.parentElement.classList.add('active');
        }
        function toggleSidebar() {
            sideBar.classList.toggle('close');
            const logoName = document.querySelector('.logo-name span');
            const logoImage = document.querySelector('.logo img');
            if (sideBar.classList.contains('close')) {
                logoName.style.display = 'none';
                logoImage.style.display = 'block';
                localStorage.setItem('sidebarClosed', true);
            } else {
                logoName.style.display = 'block';
                logoImage.style.display = 'none';
                localStorage.setItem('sidebarClosed', false);
            }
        }
        function toggleSearchForm() {
            if (window.innerWidth < 576) {
                event.preventDefault();
                searchForm.classList.toggle('show');
                searchBtnIcon.classList.toggle('bx-search');
                searchBtnIcon.classList.toggle('bx-x');
            }
        }
        function updateSidebarOnResize() {
            if (window.innerWidth < 768) {
                sideBar.classList.add('close');
            } else {
                sideBar.classList.remove('close');
            }
        }
        function updateSearchFormOnResize() {
            if (window.innerWidth > 576) {
                searchBtnIcon.classList.replace('bx-x', 'bx-search');
                searchForm.classList.remove('show');
              }
        }
        sideLinks.forEach((link) => link.addEventListener('click', toggleActiveLink));
        menuBar.addEventListener('click', toggleSidebar);
        searchBtn.addEventListener('click', toggleSearchForm);
        window.addEventListener('resize', updateSidebarOnResize);
        window.addEventListener('resize', updateSearchFormOnResize);
        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark');
            const isDark = body.classList.contains('dark');
            localStorage.setItem('darkMode', isDark);
            if (isDark) {
                themeToggle.innerHTML = '<i class="bx bx-moon" style="color: #fff;"></i>';
            } else {
                themeToggle.innerHTML = '<i class="bx bx-sun" style="color: #000;"></i>';
            }
        });
        sideBar.addEventListener('transitionend', function() {
            if (sideBar.classList.contains('close')) {
                const logoImage = document.querySelector('.logo img');
                logoImage.style.display = 'block';
            }
        });
        document.addEventListener('DOMContentLoaded', () => {
            const darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'true') {
                body.classList.add('dark');
                themeToggle.innerHTML = '<i class="bx bx-moon" style="color: #fff;"></i>';
            } else {
                themeToggle.innerHTML = '<i class="bx bx-sun" style="color: #000;"></i>';
            }
            const sidebarClosed = localStorage.getItem('sidebarClosed');
            if (sidebarClosed === 'true') {
                sideBar.classList.add('close');
                const logoImage = document.querySelector('.logo img');
                logoImage.style.display = 'block';
                const logoName = document.querySelector('.logo-name span');
                logoName.style.display = 'none';
            }
        });
    </script>
      <!-- ALERTAS -->
  <script>
        setTimeout(function() {
    document.querySelectorAll('.mensaje_exitoso,.mensaje_erroneo').forEach(function(element) {
        element.style.display = 'none';
    });
}, 5000);
    </script>
</body>
</html>