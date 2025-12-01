<nav class="navbar navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-shield-alt me-2"></i>ProSecure Admin
        </a>
        
        <div class="d-flex align-items-center">
            <span class="text-light me-3">
                <i class="fas fa-user-circle me-1"></i>
                <?php echo htmlspecialchars(getAdminName()); ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>
