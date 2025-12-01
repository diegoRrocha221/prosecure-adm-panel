<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" 
                   href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'admin_management.php' ? 'active' : ''; ?>" 
                   href="admin_management.php">
                    <i class="fas fa-user-shield"></i>
                    Admin Management
                </a>
            </li>
        </ul>
        
        <hr class="my-3 text-light">
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
