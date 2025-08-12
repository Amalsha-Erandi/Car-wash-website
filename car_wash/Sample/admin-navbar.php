<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}
?>
<!-- Admin Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-water me-2"></i>Smart Wash Admin
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view-orders.php' ? 'active' : ''; ?>" href="view-orders.php">
                        <i class="bi bi-calendar-check me-1"></i>Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-manage-orders.php' ? 'active' : ''; ?>" href="admin-manage-orders.php">
                        <i class="bi bi-box-seam me-1"></i>Product Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cash-orders.php' ? 'active' : ''; ?>" href="cash-orders.php">
                        <i class="bi bi-cash-coin me-1"></i>Cash Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-treatment-management.php' ? 'active' : ''; ?>" href="admin-treatment-management.php">
                        <i class="bi bi-gear me-1"></i>Services
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-manage-inventory.php' ? 'active' : ''; ?>" href="admin-manage-inventory.php">
                        <i class="bi bi-box-seam me-1"></i>Inventory
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'staff-management.php' ? 'active' : ''; ?>" href="staff-management.php">
                        <i class="bi bi-people me-1"></i>Staff
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-review-managment.php' ? 'active' : ''; ?>" href="admin-review-managment.php">
                        <i class="bi bi-star me-1"></i>Reviews
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-2"></i>
                        <?php echo isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'Admin'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="admin_profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="admin-logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    .navbar {
        padding: 1rem;
        background-color: #343a40 !important;
    }
    .navbar-brand {
        color: white !important;
        font-weight: bold;
    }
    .nav-link {
        color: rgba(255,255,255,.8) !important;
        padding: 0.5rem 1rem;
    }
    .nav-link:hover {
        color: white !important;
    }
    .dropdown-menu {
        margin-top: 0.5rem;
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        background-color: white;
    }
    .dropdown-item {
        padding: 0.5rem 1rem;
        color: #333;
    }
    .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    .admin-profile {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: white !important;
    }
    .btn-logout {
        color: #dc3545 !important;
    }
    .btn-logout:hover {
        background-color: #dc3545 !important;
        color: white !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize dropdowns
        const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
        dropdownElementList.forEach(function(dropdownToggleEl) {
            new bootstrap.Dropdown(dropdownToggleEl);
        });

        // Add active class to current page
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.nav-link:not(.dropdown-toggle)');
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage || 
                (currentPage === '' && href === 'dashboard.php') ||
                (href !== '#' && currentPage.includes(href))) {
                link.classList.add('active');
            }
        });
    });
</script>
