<?php
// includes/navbar.php
?>
<nav class="navbar navbar-dark bg-gradient-blue shadow-sm fixed-top mb-5 px-3 d-flex justify-content-between align-items-center">
  <!-- Mobile Sidebar Toggle -->
  <button class="btn btn-link text-white d-lg-none" type="button" data-sidebar-toggle>
    <i class="bi bi-list fs-3"></i>
  </button>

  <!-- Brand -->
  <a class="navbar-brand fw-bold text-white ms-2" href="homes.php">
    <i class="bi bi-speedometer2 me-2"></i> iMARKET
  </a>

  <!-- Right Side -->
  <ul class="navbar-nav flex-row align-items-center">
    <!-- Notifications -->
    <li class="nav-item me-3">
      <a class="nav-link position-relative text-white" href="notifications.php">
        <i class="bi bi-bell fs-5"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
      </a>
    </li>

    <!-- Profile Dropdown -->
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-person-circle fs-4 me-2"></i>
        <span class="fw-semibold d-none d-sm-inline">
          <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'; ?>
        </span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0">
        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      </ul>
    </li>
  </ul>
</nav>

<style>
/* Reset body and html margins/padding */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html, body {
  margin: 0 !important;
  padding: 0 !important;
}

/* Match navbar to sidebar gradient */
.bg-gradient-blue {
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
  position: relative;
  margin: 0 !important;
  top: 0 !important;
}

.bg-gradient-blue::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: linear-gradient(135deg, rgba(30, 64, 175, 0.9) 0%, rgba(59, 130, 246, 0.8) 100%);
  z-index: -1;
}

/* Remove any spacing around navbar */
.navbar {
  margin: 0 !important;
  border: none !important;
  border-radius: 0 !important;
}

/* Ensure fixed positioning works correctly */
.fixed-top {
  top: 0 !important;
  z-index: 1030 !important;
  position: fixed !important;
  width: 100% !important;
}

/* Navbar link hover */
.navbar .nav-link:hover {
  color: #facc15 !important; /* Yellow on hover */
}

/* Fix any container margins that might cause issues */
.container, .container-fluid {
  padding-top: 0 !important;
}

/* Ensure no margin on first element after navbar */
.main-content {
  margin-top: 0 !important;
  padding-top: 0 !important;
}

/* Additional reset for common spacing issues */
.navbar + * {
  margin-top: 0 !important;
}
</style>