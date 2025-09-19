<?php
session_start();
// Example dummy values (replace with DB queries)
$totalProducts = 120;
$totalOrders = 45;
$totalRevenue = 15230.50;
$totalCustomers = 300;
$salesDates = json_encode(["Mon","Tue","Wed","Thu","Fri","Sat","Sun"]);
$salesData = json_encode([500, 700, 400, 900, 650, 1200, 800]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    /* Main layout styles */
    body {
      padding-top: 56px; /* Account for fixed navbar */
    }
    
    .main-content {
      margin-left: 250px;
      padding: 20px;
      min-height: calc(100vh - 56px);
      transition: margin-left 0.3s ease;
    }
    
    @media (max-width: 991px) {
      .main-content {
         padding: 20px;
        margin-left: 0;
        margin-top: 100px;
        padding: 15px;
      }
    }
    
    /* Dashboard specific styles */
    .dashboard-card {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .dashboard-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 1rem;
    }
    
    .chart-container {
      position: relative;
      height: 400px;
    }
  </style>
</head>
<body>
  <!-- Include navbar -->
  <?php include 'includes/navbar.php'; ?>
  
  <!-- Include sidebar -->
  <?php include 'includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <div class="main-content">
    <div class="container-fluid">
      <!-- Page Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="h3 mb-0">Dashboard</h1>
          <p class="text-muted">Welcome back! Here's what's happening with your store.</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary">
            <i class="bi bi-download me-2"></i>Export
          </button>
          <button class="btn btn-primary">
            <i class="bi bi-plus me-2"></i>Add New
          </button>
        </div>
      </div>
      
      <!-- Statistics Cards -->
      <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card dashboard-card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                  <i class="bi bi-box"></i>
                </div>
                <div>
                  <h6 class="card-title text-muted mb-1">Total Products</h6>
                  <h3 class="mb-0 text-dark"><?php echo number_format($totalProducts); ?></h3>
                  <small class="text-success">
                    <i class="bi bi-arrow-up"></i> 12% from last month
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card dashboard-card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                  <i class="bi bi-cart-check"></i>
                </div>
                <div>
                  <h6 class="card-title text-muted mb-1">Total Orders</h6>
                  <h3 class="mb-0 text-dark"><?php echo number_format($totalOrders); ?></h3>
                  <small class="text-success">
                    <i class="bi bi-arrow-up"></i> 8% from last month
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card dashboard-card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                  <i class="bi bi-currency-dollar"></i>
                </div>
                <div>
                  <h6 class="card-title text-muted mb-1">Total Revenue</h6>
                  <h3 class="mb-0 text-dark">$<?php echo number_format($totalRevenue, 2); ?></h3>
                  <small class="text-success">
                    <i class="bi bi-arrow-up"></i> 15% from last month
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card dashboard-card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                  <i class="bi bi-people"></i>
                </div>
                <div>
                  <h6 class="card-title text-muted mb-1">Total Customers</h6>
                  <h3 class="mb-0 text-dark"><?php echo number_format($totalCustomers); ?></h3>
                  <small class="text-success">
                    <i class="bi bi-arrow-up"></i> 5% from last month
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Charts Row -->
      <div class="row">
        <div class="col-lg-8 mb-4">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Sales Performance</h5>
                <div class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Last 7 days
                  </button>
                  <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Last 7 days</a></li>
                    <li><a class="dropdown-item" href="#">Last 30 days</a></li>
                    <li><a class="dropdown-item" href="#">Last 3 months</a></li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="salesChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 mb-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
              <h5 class="card-title mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
              <div class="activity-item d-flex align-items-center mb-3">
                <div class="activity-icon bg-success bg-opacity-10 text-success rounded-circle me-3 p-2">
                  <i class="bi bi-cart-plus"></i>
                </div>
                <div>
                  <p class="mb-0">New order #1234</p>
                  <small class="text-muted">2 minutes ago</small>
                </div>
              </div>
              <div class="activity-item d-flex align-items-center mb-3">
                <div class="activity-icon bg-primary bg-opacity-10 text-primary rounded-circle me-3 p-2">
                  <i class="bi bi-person-plus"></i>
                </div>
                <div>
                  <p class="mb-0">New customer registered</p>
                  <small class="text-muted">5 minutes ago</small>
                </div>
              </div>
              <div class="activity-item d-flex align-items-center mb-3">
                <div class="activity-icon bg-warning bg-opacity-10 text-warning rounded-circle me-3 p-2">
                  <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                  <p class="mb-0">Low stock alert</p>
                  <small class="text-muted">10 minutes ago</small>
                </div>
              </div>
              <div class="activity-item d-flex align-items-center mb-3">
                <div class="activity-icon bg-info bg-opacity-10 text-info rounded-circle me-3 p-2">
                  <i class="bi bi-box"></i>
                </div>
                <div>
                  <p class="mb-0">Product updated</p>
                  <small class="text-muted">15 minutes ago</small>
                </div>
              </div>
              <div class="text-center">
                <a href="#" class="btn btn-sm btn-outline-primary">View all activity</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Sales Chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?php echo $salesDates; ?>,
        datasets: [{
          label: 'Sales ($)',
          data: <?php echo $salesData; ?>,
          borderColor: '#0d6efd',
          backgroundColor: 'rgba(13, 110, 253, 0.1)',
          fill: true,
          tension: 0.4,
          pointRadius: 4,
          pointHoverRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return '$' + value;
              }
            }
          }
        }
      }
    });
  </script>
</body>
</html>