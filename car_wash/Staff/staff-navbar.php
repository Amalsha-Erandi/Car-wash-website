<?php
if (!isset($_SESSION['staff_id'])) {
    header("Location: staff-login.php");
    exit;
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="staff-dashboard.php">
            <i class="bi bi-water"></i> Smart Wash
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'staff-dashboard.php' ? 'active' : ''; ?>" 
                       href="staff-dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my-bookings.php' ? 'active' : ''; ?>" 
                       href="my-bookings.php">
                        <i class="bi bi-calendar-check"></i> My Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my-schedule.php' ? 'active' : ''; ?>" 
                       href="my-schedule.php">
                        <i class="bi bi-calendar-week"></i> My Schedule
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['staff_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="staff-profile.php">
                                <i class="bi bi-person"></i> My Profile
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="staff-logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav> 