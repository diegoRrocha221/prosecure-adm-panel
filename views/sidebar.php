<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" 
                   href="dashboard.php" data-page="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'financial.php' ? 'active' : ''; ?>" 
                   href="financial.php" data-page="financial">
                    <i class="fas fa-chart-line"></i>
                    Financial
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'plans.php' ? 'active' : ''; ?>" 
                   href="plans.php" data-page="plans">
                    <i class="fas fa-box"></i>
                    Plans
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'blog.php' ? 'active' : ''; ?>" 
                   href="blog.php" data-page="blog">
                    <i class="fas fa-blog"></i>
                    Blog
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'support.php' ? 'active' : ''; ?>" 
                   href="support.php" data-page="support">
                    <i class="fas fa-headset"></i>
                    Support
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($currentPage, ['discount_settings.php', 'blog_filter_settings.php', 'display_settings.php']) ? 'active' : ''; ?>" 
                   href="#settingsSubmenu" data-bs-toggle="collapse" 
                   aria-expanded="<?php echo in_array($currentPage, ['discount_settings.php', 'blog_filter_settings.php', 'display_settings.php']) ? 'true' : 'false'; ?>">
                    <i class="fas fa-cog"></i>
                    Settings
                    <i class="fas fa-chevron-down ms-auto"></i>
                </a>
                <ul class="collapse nav flex-column ms-3 <?php echo in_array($currentPage, ['discount_settings.php', 'blog_filter_settings.php', 'display_settings.php']) ? 'show' : ''; ?>" 
                    id="settingsSubmenu">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'discount_settings.php' ? 'active' : ''; ?>" 
                           href="discount_settings.php" data-page="discount_settings">
                            <i class="fas fa-percentage"></i>
                            Discount Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'blog_filter_settings.php' ? 'active' : ''; ?>" 
                           href="blog_filter_settings.php" data-page="blog_filter_settings">
                            <i class="fas fa-filter"></i>
                            Blog Filter Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'display_settings.php' ? 'active' : ''; ?>" 
                           href="display_settings.php" data-page="display_settings">
                            <i class="fas fa-desktop"></i>
                            Display Settings
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
        
        <hr class="my-3 text-light">
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'admin_management.php' ? 'active' : ''; ?>" 
                   href="admin_management.php" data-page="admin_management">
                    <i class="fas fa-user-shield"></i>
                    Admin Management
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>