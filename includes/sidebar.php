<?php
// Get current page name to highlight active menu
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Enhanced Sidebar with Blue Theme -->
<div class="sidebar bg-gradient-blue text-white position-fixed" id="sidebar" style="width: 270px; height: 100vh; top: 0; left: 0; overflow-y: auto; z-index: 1030;">
  <!-- Sidebar Header -->
  <div class="sidebar-header p-4 border-bottom border-light-subtle">
    <div class="d-flex align-items-center">
      <div class="logo-container bg-white bg-opacity-15 backdrop-blur rounded-3 d-flex align-items-center justify-content-center me-3 shadow-sm" style="width:45px; height:45px;">
        <i class="bi bi-speedometer2 text-white fs-5"></i>
      </div>
      <div>
        <h5 class="mb-1 fw-bold text-white">iMARKET</h5>
     
      </div>
    </div>
  </div>

  <!-- User Profile Card -->
  <div class="user-profile-card mx-3 mt-3 mb-4">
    <div class="card bg-white bg-opacity-10 backdrop-blur border-0 shadow-sm">
      <div class="card-body p-3">
        <div class="d-flex align-items-center">
          <div class="user-avatar bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width:40px; height:40px;">
            <i class="bi bi-person-fill text-white"></i>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-0 text-white fw-semibold">
              <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : ''; ?>
            </h6>
            <small class="text-white-50"> <?php echo isset($_SESSION['role']) ? $_SESSION['role'] : '$role'; ?></small>
          </div>
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-light btn-ghost" type="button" data-bs-toggle="dropdown">
              <i class="bi bi-three-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
              <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
              <li><a class="dropdown-item" href="preferences.php"><i class="bi bi-sliders me-2"></i>Preferences</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Navigation Menu -->
  <nav class="sidebar-nav px-3">
    <ul class="nav flex-column p-0">
      <!-- Dashboard -->
      <li class="nav-item mb-1">
        <a class="nav-link modern-nav-link <?php echo ($currentPage == 'homes.php' || $currentPage == 'index.php') ? 'active' : ''; ?>" 
           href="homes.php">
          <div class="nav-icon">
            <i class="bi bi-house-door"></i>
          </div>
          <span>Dashboard</span>
        </a>
      </li>
      
      <li class="nav-item mb-1">
        <a class="nav-link modern-nav-link <?php echo $currentPage == 'Product.php' ? 'active' : ''; ?>" 
           href="product.php">
          <div class="nav-icon">
            <i class="bi bi-people"></i>
          </div>
          <span>Product</span>
        </a>
      </li>
      
      <!-- Orders -->
      <li class="nav-item mb-1">
        <a class="nav-link modern-nav-link d-flex align-items-center <?php echo in_array($currentPage, ['orders.php', 'order-details.php']) ? 'active' : ''; ?>" 
           href="orders.php">
          <div class="nav-icon">
            <i class="bi bi-cart3"></i>
          </div>
          <span>Orders</span>
         
        </a>
      </li>
      

      
      <!-- Divider -->
      <li><hr class="sidebar-divider my-4"></li>
      
      <!-- Management Section -->
      <li class="nav-item mb-1">
        <small class="text-white-50 fw-semibold text-uppercase px-3 mb-2 d-block" style="font-size: 0.75rem; letter-spacing: 0.5px;">Management</small>
      </li>
      
      <!-- Settings -->
      <li class="nav-item mb-1">
        <a class="nav-link modern-nav-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>" 
           href="settings.php">
          <div class="nav-icon">
            <i class="bi bi-gear"></i>
          </div>
          <span>Settings</span>
        </a>
      </li>
      
      <!-- Users Management -->
      <li class="nav-item mb-1">
        <a class="nav-link modern-nav-link <?php echo $currentPage == 'users.php' ? 'active' : ''; ?>" 
           href="users.php">
          <div class="nav-icon">
            <i class="bi bi-person-gear"></i>
          </div>
          <span>Users</span>
        </a>
      </li>
    </ul>
  </nav>
  
  
  <!-- Sidebar Footer -->
  
</div>

<!-- Mobile Sidebar Overlay -->
<div class="sidebar-overlay d-lg-none" id="sidebarOverlay" style="display: none;"></div>

<style>
/* Blue Gradient Background */
.bg-gradient-blue {
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
  position: relative;
}

.bg-gradient-blue::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(30, 64, 175, 0.9) 0%, rgba(59, 130, 246, 0.8) 100%);
  z-index: -1;
}

/* Backdrop Blur Effect */
.backdrop-blur {
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

/* Sidebar Styles */
.sidebar {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
}

/* Enhanced Navigation Links */
.modern-nav-link {
  color: rgba(255, 255, 255, 0.9) !important;
  padding: 0.875rem 1rem !important;
  border-radius: 12px !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
  border: none !important;
  display: flex !important;
  align-items: center !important;
  text-decoration: none !important;
  position: relative !important;
  overflow: hidden !important;
  margin-bottom: 0.25rem !important;
}

.modern-nav-link::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 0;
  height: 100%;
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
  transition: width 0.3s ease;
  z-index: 0;
}

.modern-nav-link:hover::before {
  width: 100%;
}

.modern-nav-link:hover {
  color: white !important;
  background: rgba(255, 255, 255, 0.15) !important;
  transform: translateX(5px) !important;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
}

.modern-nav-link.active {
  color: white !important;
  background: rgba(255, 255, 255, 0.2) !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
  border-left: 4px solid rgba(255, 255, 255, 0.8) !important;
}

/* Navigation Icons */
.nav-icon {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 0.75rem;
  position: relative;
  z-index: 1;
}

.nav-icon i {
  font-size: 1.1rem;
}

/* Collapse Icon Animation */
.collapse-icon {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  z-index: 1;
}

.modern-nav-link[aria-expanded="true"] .collapse-icon {
  transform: rotate(180deg);
}

/* Submenu Styles */
.submenu {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 8px;
  margin: 0.5rem 0;
  padding: 0.5rem 0;
}

.submenu-link {
  color: rgba(255, 255, 255, 0.8) !important;
  padding: 0.625rem 1.5rem !important;
  font-size: 0.9rem !important;
  border-radius: 8px !important;
  transition: all 0.2s ease !important;
  margin: 0.125rem 0.5rem !important;
}

.submenu-link:hover {
  color: white !important;
  background: rgba(255, 255, 255, 0.1) !important;
  transform: translateX(3px) !important;
}

.submenu-link.active {
  color: white !important;
  background: rgba(255, 255, 255, 0.15) !important;
  border-left: 3px solid rgba(255, 255, 255, 0.8) !important;
}

/* Enhanced Badge */
.pulse-badge {
  animation: pulse 2s infinite;
  font-size: 0.7rem !important;
  font-weight: 600 !important;
  padding: 0.25rem 0.5rem !important;
  border-radius: 10px !important;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2) !important;
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}

/* Sidebar Divider */
.sidebar-divider {
  border-top: 1px solid rgba(255, 255, 255, 0.2) !important;
  margin: 1rem 1.5rem !important;
  opacity: 0.6;
}

/* Enhanced Buttons */
.btn-ghost {
  background: transparent !important;
  border: 1px solid rgba(255, 255, 255, 0.3) !important;
  transition: all 0.2s ease !important;
}

.btn-ghost:hover {
  background: rgba(255, 255, 255, 0.1) !important;
  border-color: rgba(255, 255, 255, 0.5) !important;
  transform: translateY(-1px) !important;
}

/* Cards Enhancement */
.card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.card:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

/* Mobile responsiveness */
@media (max-width: 991px) {
  .sidebar {
    transform: translateX(-100%);
  }
  
  .sidebar.show {
    transform: translateX(0);
  }
  
  .sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 1025;
    backdrop-filter: blur(2px);
  }
}

/* Scrollbar Enhancement */
.sidebar::-webkit-scrollbar {
  width: 6px;
}

.sidebar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
}

.sidebar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 10px;
  transition: background 0.2s ease;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}

/* Text Selection */
.sidebar ::selection {
  background: rgba(255, 255, 255, 0.2);
}
</style>

<script>
// Enhanced sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const collapseElements = document.querySelectorAll('[data-bs-toggle="collapse"]');
  
  // Mobile sidebar toggle functionality
  function initMobileSidebar() {
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    
    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        toggleSidebar();
      });
    }
    
    // Close sidebar when clicking overlay
    if (overlay) {
      overlay.addEventListener('click', closeSidebar);
    }
    
    // Handle escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && sidebar.classList.contains('show')) {
        closeSidebar();
      }
    });
  }
  
  function toggleSidebar() {
    if (window.innerWidth <= 991) {
      sidebar.classList.toggle('show');
      if (overlay) {
        overlay.style.display = sidebar.classList.contains('show') ? 'block' : 'none';
      }
      
      // Prevent body scroll when sidebar is open
      document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
    }
  }
  
  function closeSidebar() {
    sidebar.classList.remove('show');
    if (overlay) {
      overlay.style.display = 'none';
    }
    document.body.style.overflow = '';
  }
  
  // Handle window resize
  window.addEventListener('resize', function() {
    if (window.innerWidth > 991) {
      closeSidebar();
    }
  });
  
  // Auto-collapse other accordion items
  collapseElements.forEach(element => {
    element.addEventListener('click', function() {
      const targetId = this.getAttribute('data-bs-target') || this.getAttribute('href');
      const otherCollapses = document.querySelectorAll('.collapse.show');
      
      otherCollapses.forEach(collapse => {
        if (collapse.id !== targetId.substring(1)) {
          const bsCollapse = new bootstrap.Collapse(collapse, {
            toggle: false
          });
          bsCollapse.hide();
        }
      });
    });
  });
  
  // Smooth scroll to active menu item
  const activeLink = document.querySelector('.nav-link.active');
  if (activeLink) {
    setTimeout(() => {
      activeLink.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'nearest',
        inline: 'nearest'
      });
    }, 100);
  }
  
  // Add click ripple effect
  document.querySelectorAll('.modern-nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
        z-index: 0;
      `;
      
      this.appendChild(ripple);
      
      setTimeout(() => {
        ripple.remove();
      }, 600);
    });
  });
  
  // Initialize mobile functionality
  initMobileSidebar();
});

// Add CSS for ripple animation
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
  @keyframes ripple {
    to {
      transform: scale(4);
      opacity: 0;
    }
  }
`;
document.head.appendChild(rippleStyle);
</script>