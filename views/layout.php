<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda</title>
    <link rel="shortcut icon" href="<?= asset('images/cit.png') ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= asset('build/styles.css') ?>">
    <script src="<?= asset('build/js/app.js') ?>"></script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="/app_carbajal">
                <img src="<?= asset('images/cit.png') ?>" alt="Logo" width="32" class="me-2">
                <span class="fw-bold"></span>
            </a>

            <!-- Toggler -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menu -->
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <!-- Inicio -->
                    <li class="nav-item">
                        <a class="nav-link" href="/app_carbajal">
                            <i class="bi bi-house-fill me-1"></i>Inicio
                        </a>
                    </li>

                    <!-- Gestión de Datos -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="catalogosDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear-fill me-1"></i>Gestión de Datos
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="catalogosDropdown">
                            <li><a class="dropdown-item" href="/app_carbajal/guardaBosques"><i class="bi bi-people-fill me-1"></i>Clientes</a></li>
                            <li><a class="dropdown-item" href="/app_carbajal/paquito"><i class="bi bi-box-seam me-1"></i>Productos</a></li>
                            <li><a class="dropdown-item" href="/app_carbajal/patitos"><i class="bi bi-tags-fill me-1"></i>Categorías</a></li>
                            <li><a class="dropdown-item" href="/app_carbajal/charquitos"><i class="bi bi-flag-fill me-1"></i>Prioridades</a></li>
                        </ul>
                    </li>

                    <!-- Ventas y Detalles -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="ventasDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-receipt-cutoff me-1"></i>Ventas
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="ventasDropdown">
                            <li><a class="dropdown-item" href="/app_carbajal/chaguito"><i class="bi bi-cash-stack me-1"></i>Gestión de Ventas</a></li>
                            <li><a class="dropdown-item" href="/app_carbajal/rambito"><i class="bi bi-list-ul me-1"></i>Detalles de Venta</a></li>
                        </ul>
                    </li>

                    <!-- Usuarios -->
                    <li class="nav-item">
                        <a class="nav-link" href="/app_carbajal/panas">
                            <i class="bi bi-shield-lock-fill me-1"></i>Usuarios
                        </a>
                    </li>
                </ul>

                <!-- Usuario / Perfil -->
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <span><?= $_SESSION['usuario_nombre'] ?? 'Perfil' ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="/app_carbajal/jumbo"><i class="bi bi-person-fill me-1"></i>Mi Perfil</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="/app_carbajal/irse"><i class="bi bi-box-arrow-right me-1"></i>Salir</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="progress fixed-bottom" style="height: 6px;">
        <div class="progress-bar progress-bar-animated bg-danger" id="bar" role="progressbar"></div>
    </div>

    <main class="container-fluid pt-5 mb-4" style="min-height: 85vh;">
        <?= $contenido; ?>
    </main>

    <footer class="container-fluid">
        <div class="row justify-content-center text-center">
            <div class="col-12">
                <p style="font-size: xx-small; font-weight: bold;">
                    Comando de Informática y Tecnología, <?= date('Y') ?> &copy;
                </p>
            </div>
        </div>
    </footer>
</body>

</html>