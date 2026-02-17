<?php
require_once __DIR__ . '/../config/config.php';
$current_user_id = getCurrentUserId();
$current_role = getCurrentUserRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-briefcase"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/dashboard.php">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/services.php">
                                    <i class="bi bi-list-ul"></i> Services
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/clients.php">
                                    <i class="bi bi-people"></i> Clients
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/requests.php">
                                    <i class="bi bi-inbox"></i> Requests
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/projects.php">
                                    <i class="bi bi-folder"></i> Projects
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/invoices.php">
                                    <i class="bi bi-receipt"></i> Invoices
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/messages.php">
                                    <i class="bi bi-envelope"></i> Messages
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/reports.php">
                                    <i class="bi bi-graph-up"></i> Reports
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../client/dashboard.php">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../client/services.php">
                                    <i class="bi bi-list-ul"></i> Services
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../client/requests.php">
                                    <i class="bi bi-plus-circle"></i> My Requests
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../client/projects.php">
                                    <i class="bi bi-folder"></i> My Projects
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../client/invoices.php">
                                    <i class="bi bi-receipt"></i> Invoices
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../client/messages.php">
                                    <i class="bi bi-envelope"></i> Messages
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="../auth/profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../auth/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../auth/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container-fluid py-4">
