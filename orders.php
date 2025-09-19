<?php 
session_start();

// ==========================
// Require login
// ==========================
if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit;
}

// ==========================
// DB Connection (PDO)
// ==========================
function getDBConnection() {
    $db_host = "localhost";
    $db_port = "3307";
    $db_user = "root";
    $db_pass = "";
    $db_name = "core2_test";

    try {
        $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

$pdo = getDBConnection();
$sellerId = $_SESSION['seller_id'];

// ==========================
// Handle actions (approve / cancel)
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); 
    
    $action  = $_POST['action'] ?? '';
    $orderId = $_POST['order_id'] ?? null;

    if ($action === 'approve' && $orderId) {
        try {
            $stmt = $pdo->prepare("UPDATE orders 
                                   SET status='approved' 
                                   WHERE id=? AND seller_id=?");
            $stmt->execute([$orderId, $sellerId]);
            
            echo json_encode(['success' => $stmt->rowCount() > 0]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'cancel' && $orderId) {
        try {
            $reason = $_POST['reason'] ?? 'No reason provided';
            $stmt   = $pdo->prepare("UPDATE orders 
                                     SET status='cancelled', cancel_reason=? 
                                     WHERE id=? AND seller_id=?");
            $stmt->execute([$reason, $orderId, $sellerId]);

            echo json_encode(['success' => $stmt->rowCount() > 0]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// ==========================
// Get Order Details Function
// ==========================
function getOrderDetails($orderId, $pdo, $sellerId) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, u.name AS customer_name, s.store_name AS seller_name
            FROM orders o
            JOIN users u ON o.user_id = u.id 
            JOIN sellers s ON o.seller_id = s.id
            WHERE o.id = ? AND o.seller_id = ?
        ");
        $stmt->execute([$orderId, $sellerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    } catch (PDOException $e) {
        error_log("Database error in getOrderDetails: " . $e->getMessage());
        return false;
    }
}

// ==========================
// Handle AJAX request for order details
// ==========================
if (isset($_GET['get_order_details']) && isset($_GET['order_id'])) {
    header('Content-Type: application/json');
    
    $orderId = (int)$_GET['order_id'];
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }
    
    $orderDetails = getOrderDetails($orderId, $pdo, $sellerId);
    if ($orderDetails) {
        echo json_encode(['success' => true, 'order' => $orderDetails]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found or not authorized']);
    }
    exit;
}

// ==========================
// Fetch Orders Function
// ==========================
function fetchOrders($statusFilter, $pdo, $sellerId) {
    try {
        if ($statusFilter) {
            $stmt = $pdo->prepare("
                SELECT o.*, u.name AS customer_name, s.store_name AS seller_name
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                JOIN sellers s ON o.seller_id = s.id
                WHERE o.status=? AND o.seller_id=? 
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$statusFilter, $sellerId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT o.*, u.name AS customer_name, s.store_name AS seller_name
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                JOIN sellers s ON o.seller_id = s.id
                WHERE o.seller_id=? 
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$sellerId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in fetchOrders: " . $e->getMessage());
        return [];
    }
}

$statusFilter = $_GET['status'] ?? '';
$orders = fetchOrders($statusFilter, $pdo, $sellerId);

// ==========================
// Status badge helper
// ==========================
function getStatusBadge($status) {
    switch($status) {
        case 'pending': return 'bg-warning text-dark';
        case 'approved': return 'bg-success';
        case 'cancelled': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

// ==========================
// Table Rendering Function
// ==========================
function renderOrdersTable($orders) {
    if (!$orders || count($orders) === 0) {
        echo '<div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                <h4 class="text-muted">No orders found</h4>
                <p class="text-muted">There are no orders matching your current filter.</p>
              </div>';
        return;
    }
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Store</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['id']) ?></td>
                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                    <td><?= htmlspecialchars($order['seller_name']) ?></td>
                    <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                    <td><span class="badge <?= getStatusBadge($order['status']) ?>"><?= ucfirst($order['status']) ?></span></td>
                    <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary view-details" data-id="<?= $order['id'] ?>">View</button>
                        <?php if ($order['status'] === 'pending'): ?>
                            <button class="btn btn-sm btn-success approve-order" data-id="<?= $order['id'] ?>">Approve</button>
                            <button class="btn btn-sm btn-danger cancel-order" data-id="<?= $order['id'] ?>">Cancel</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ==========================
// AJAX fetch request (only table)
// ==========================
if (isset($_GET['fetch']) && $_GET['fetch'] === 'orders') {
    renderOrdersTable($orders);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- ✅ Navbar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- ✅ Sidebar -->
            <?php include __DIR__ . '/includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="mb-4">Orders</h2>

                <!-- ✅ Tabs -->
                <ul class="nav nav-tabs mb-3" id="orderTabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $statusFilter===''?'active':'' ?>" href="?">All</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $statusFilter==='pending'?'active':'' ?>" href="?status=pending">Pending</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $statusFilter==='approved'?'active':'' ?>" href="?status=approved">Approved</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $statusFilter==='cancelled'?'active':'' ?>" href="?status=cancelled">Cancelled</a>
                    </li>
                </ul>

                <!-- ✅ Orders Table -->
                <div id="ordersTable">
                    <?php renderOrdersTable($orders); ?>
                </div>
            </main>
        </div>
    </div>

    <!-- ✅ Order Details Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetails"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(function() {
        // View order details
        $(document).on("click", ".view-details", function() {
            let id = $(this).data("id");
            $.get("?get_order_details=1&order_id=" + id, function(res) {
                if (res.success) {
                    $("#orderDetails").html(
                        `<p><strong>Customer:</strong> ${res.order.customer_name}</p>
                         <p><strong>Store:</strong> ${res.order.seller_name}</p>
                         <p><strong>Total:</strong> ₱${parseFloat(res.order.total_amount).toFixed(2)}</p>
                         <p><strong>Status:</strong> ${res.order.status}</p>
                         <p><strong>Reason:</strong> ${res.order.cancel_reason ?? ''}</p>`
                    );
                } else {
                    $("#orderDetails").html("<p class='text-danger'>"+res.message+"</p>");
                }
                new bootstrap.Modal(document.getElementById("orderModal")).show();
            }, "json");
        });

        // Approve order
        $(document).on("click", ".approve-order", function() {
            let id = $(this).data("id");
            $.post("", {action:"approve", order_id:id}, function(res) {
                if (res.success) location.reload();
                else alert(res.message || "Failed to approve");
            }, "json");
        });

        // Cancel order
        $(document).on("click", ".cancel-order", function() {
            let id = $(this).data("id");
            let reason = prompt("Enter cancel reason:");
            if (!reason) return;
            $.post("", {action:"cancel", order_id:id, reason:reason}, function(res) {
                if (res.success) location.reload();
                else alert(res.message || "Failed to cancel");
            }, "json");
        });
    });
    </script>
</body>
</html>
