<?php
session_start();

// Require login
if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit;
}

// DB Connection
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
        die("DB Connection failed: ".$e->getMessage());
    }
}

$pdo = getDBConnection();
$sellerId = $_SESSION['seller_id'];

// Handle Approve / Complete / Cancel / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['action'])) {
    $orderId = (int)$_POST['order_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE orders SET refund_status='approved' WHERE id=? AND seller_id=?");
            $stmt->execute([$orderId, $sellerId]);
        } elseif ($action === 'process') {
            $stmt = $pdo->prepare("UPDATE orders SET refund_status='processing' WHERE id=? AND seller_id=?");
            $stmt->execute([$orderId, $sellerId]);
        } elseif ($action === 'complete') {
            $stmt = $pdo->prepare("UPDATE orders SET refund_status='completed', refund_processed_date=NOW() WHERE id=? AND seller_id=?");
            $stmt->execute([$orderId, $sellerId]);
      
        } elseif ($action === 'reject') {
            $rejectionReason = $_POST['rejection_reason'] ?? '';
            if (empty($rejectionReason)) {
                $_SESSION['message'] = 'Rejection reason is required!';
                $_SESSION['message_type'] = 'error';
            } else {
                $stmt = $pdo->prepare("UPDATE orders SET refund_status='rejected', rejection_reason=?, rejection_date=NOW() WHERE id=? AND seller_id=?");
                $stmt->execute([$rejectionReason, $orderId, $sellerId]);
                $_SESSION['message'] = 'Refund request rejected successfully!';
                $_SESSION['message_type'] = 'success';
            }
        }
        
        if ($action !== 'reject' || !empty($_POST['rejection_reason'])) {
            $_SESSION['message'] = 'Refund status updated successfully!';
            $_SESSION['message_type'] = 'success';
        }
    } catch (Exception $e) {
        $_SESSION['message'] = 'Error updating refund status: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Fetch all orders for seller
$stmt = $pdo->prepare("
    SELECT o.*, 
        CONCAT(c.first_name,' ',c.last_name) AS customer_name, 
        c.email AS customer_email, 
        c.phone AS customer_phone,
        p.name AS product_name, 
        p.price AS product_price, 
        p.image AS product_image
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.seller_id = ? AND o.refund_status IS NOT NULL
    ORDER BY o.created_at DESC
");
$stmt->execute([$sellerId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process orders
$orders = [];
foreach($rows as $row) {
    $id = $row['id'];
    if(!isset($orders[$id])) {
        $orders[$id] = $row;
        $orders[$id]['products'] = [];
    }
    $orders[$id]['products'][] = [
        'name' => $row['product_name'],
        'price' => $row['product_price'],
        'image' => $row['product_image'],
        'quantity' => $row['quantity']
    ];
}

// Get filter from URL
$filter = $_GET['status'] ?? 'all';
$filteredOrders = [];

if ($filter === 'all') {
    $filteredOrders = $orders;
} else {
    $filteredOrders = array_filter($orders, fn($o) => ($o['refund_status'] ?? 'requested') == $filter);
}

// Get status counts
$statusCounts = [
    'all' => count($orders),
    'requested' => count(array_filter($orders, fn($o) => ($o['refund_status'] ?? 'requested') == 'requested')),
    'approved' => count(array_filter($orders, fn($o) => ($o['refund_status'] ?? 'requested') == 'approved')),
    'processing' => count(array_filter($orders, fn($o) => ($o['refund_status'] ?? 'requested') == 'processing')),
    'completed' => count(array_filter($orders, fn($o) => ($o['refund_status'] ?? 'requested') == 'completed')),
    
    'rejected' => count(array_filter($orders, fn($o) => ($o['refund_status'] ?? 'requested') == 'rejected'))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">

    <!-- Include navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Include sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 pt-16">
        <div class="p-6">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Refund Management</h1>
                <p class="text-gray-600 mt-1">Manage customer refund requests</p>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($_SESSION['message'])): ?>
            <div class="mb-6 p-4 rounded-md <?= $_SESSION['message_type'] === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
            <?php 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            endif; 
            ?>

            <!-- Status Filter Tabs -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <a href="?status=all" 
                           class="<?= $filter === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            All <span class="ml-1 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs"><?= $statusCounts['all'] ?></span>
                        </a>
                        <a href="?status=requested" 
                           class="<?= $filter === 'requested' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            Requested <span class="ml-1 bg-yellow-100 text-yellow-600 py-0.5 px-2 rounded-full text-xs"><?= $statusCounts['requested'] ?></span>
                        </a>
                        <a href="?status=approved" 
                           class="<?= $filter === 'approved' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            Approved <span class="ml-1 bg-blue-100 text-blue-600 py-0.5 px-2 rounded-full text-xs"><?= $statusCounts['approved'] ?></span>
                        </a>
                        <a href="?status=processing" 
                           class="<?= $filter === 'processing' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            Processing <span class="ml-1 bg-orange-100 text-orange-600 py-0.5 px-2 rounded-full text-xs"><?= $statusCounts['processing'] ?></span>
                        </a>
                        <a href="?status=completed" 
                           class="<?= $filter === 'completed' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            Completed <span class="ml-1 bg-green-100 text-green-600 py-0.5 px-2 rounded-full text-xs"><?= $statusCounts['completed'] ?></span>
                        </a>
                      
                        <a href="?status=rejected" 
                           class="<?= $filter === 'rejected' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            Rejected <span class="ml-1 bg-red-100 text-red-600 py-0.5 px-2 rounded-full text-xs"><?= $statusCounts['rejected'] ?></span>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Refunds Table -->
            <div class="bg-white rounded-lg shadow">
                <?php if(empty($filteredOrders)): ?>
                    <div class="p-12 text-center">
                        <i class="fas fa-undo text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No refund requests found</h3>
                        <p class="text-gray-500">When customers request refunds, they'll appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($filteredOrders as $order): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">#<?= $order['id'] ?></div>
                                        <div class="text-sm text-gray-500"><?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($order['customer_name'] ?: 'Customer #'.$order['customer_id']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_email'] ?: 'No email') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            ₱<?= number_format($order['refund_amount'] ?? $order['total_price'], 2) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        $status = $order['refund_status'] ?? 'requested';
                                        $statusClasses = [
                                            'requested' => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-blue-100 text-blue-800',
                                            'processing' => 'bg-orange-100 text-orange-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                         
                                            'rejected' => 'bg-red-100 text-red-800'
                                        ];
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $statusClasses[$status] ?? 'bg-gray-100 text-gray-800' ?>">
                                            <?= ucfirst($status) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $order['refund_requested_date'] ? date('M d, Y', strtotime($order['refund_requested_date'])) : 'N/A' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button onclick="openModal('modal<?= $order['id'] ?>')" 
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-eye mr-1"></i>
                                            View
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Refund Detail Modals -->
    <?php foreach($orders as $order): ?>
    <div id="modal<?= $order['id'] ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">
                        Refund Details - Order #<?= $order['id'] ?>
                    </h3>
                    <button onclick="closeModal('modal<?= $order['id'] ?>')" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-4 space-y-6">
                <!-- Status & Date -->
                <div class="flex items-center justify-between">
                    <?php 
                    $status = $order['refund_status'] ?? 'requested';
                    $statusClasses = [
                        'requested' => 'bg-yellow-100 text-yellow-800',
                        'approved' => 'bg-blue-100 text-blue-800',
                        'processing' => 'bg-orange-100 text-orange-800',
                        'completed' => 'bg-green-100 text-green-800',
                      
                        'rejected' => 'bg-red-100 text-red-800'
                    ];
                    ?>
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?= $statusClasses[$status] ?? 'bg-gray-100 text-gray-800' ?>">
                        <?= ucfirst($status) ?>
                    </span>
                    <div class="text-sm text-gray-500">
                        Requested: <?= $order['refund_requested_date'] ? date('M d, Y H:i', strtotime($order['refund_requested_date'])) : 'N/A' ?>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3">Customer Information</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Name:</span>
                            <span class="ml-2 text-gray-900"><?= htmlspecialchars($order['customer_name'] ?: 'Customer #'.$order['customer_id']) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Email:</span>
                            <span class="ml-2 text-gray-900"><?= htmlspecialchars($order['customer_email'] ?: 'N/A') ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Phone:</span>
                            <span class="ml-2 text-gray-900"><?= htmlspecialchars($order['customer_phone'] ?: 'N/A') ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Customer ID:</span>
                            <span class="ml-2 text-gray-900">#<?= $order['customer_id'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div>
                    <h4 class="font-medium text-gray-900 mb-3">Order Items</h4>
                    <div class="border rounded-lg overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                            <?php 
                            $totalAmount = 0;
                            foreach($order['products'] as $p): 
                                $subtotal = $p['price'] * $p['quantity'];
                                $totalAmount += $subtotal;
                            ?>
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($p['name']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?= $p['quantity'] ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">₱<?= number_format($p['price'], 2) ?></td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">₱<?= number_format($subtotal, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-sm font-medium text-gray-900 text-right">Total:</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900">₱<?= number_format($totalAmount, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Refund Information -->
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3">Refund Information</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Refund Amount:</span>
                            <span class="ml-2 text-lg font-bold text-green-600">₱<?= number_format($order['refund_amount'] ?? $totalAmount, 2) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Refund Method:</span>
                            <span class="ml-2 text-gray-900"><?= htmlspecialchars($order['refund_method'] ?: 'Same as payment method') ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Payment Method:</span>
                            <span class="ml-2 text-gray-900"><?= htmlspecialchars($order['payment_method'] ?: 'N/A') ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Reference #:</span>
                            <span class="ml-2 text-gray-900"><?= htmlspecialchars($order['refund_reference'] ?: 'Not generated') ?></span>
                        </div>
                    </div>
                    
                    <?php if($order['refund_reason']): ?>
                    <div class="mt-4">
                        <span class="font-medium text-gray-600">Refund Reason:</span>
                        <div class="mt-2 p-3 bg-white rounded border text-gray-900">
                            <?= nl2br(htmlspecialchars($order['refund_reason'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- NEW: Rejection Information (if rejected) -->
                <?php if($order['refund_status'] === 'rejected'): ?>
                <div class="bg-red-50 rounded-lg p-4">
                    <h4 class="font-medium text-red-900 mb-3">
                        <i class="fas fa-times-circle mr-2"></i>Rejection Details
                    </h4>
                    <div class="space-y-3">
                        <?php if($order['rejection_date']): ?>
                        <div class="text-sm">
                            <span class="text-red-600 font-medium">Rejected on:</span>
                            <span class="ml-2 text-red-900"><?= date('M d, Y H:i', strtotime($order['rejection_date'])) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($order['rejection_reason']): ?>
                        <div>
                            <span class="font-medium text-red-600">Rejection Reason:</span>
                            <div class="mt-2 p-3 bg-white rounded border border-red-200 text-red-900">
                                <?= nl2br(htmlspecialchars($order['rejection_reason'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Modal Footer with Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg">
                <div class="flex justify-end space-x-3">
                    <?php $currentStatus = $order['refund_status'] ?? 'requested'; ?>
                    
                    <?php if($currentStatus === 'requested'): ?>
                    <form method="post" class="inline">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                onclick="return confirm('Approve this refund request?')">
                            <i class="fas fa-check mr-1"></i>
                            Approve
                        </button>
                    </form>
                    
                    <!-- NEW: Reject button with modal -->
                    <button onclick="openRejectModal(<?= $order['id'] ?>)"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i class="fas fa-times mr-1"></i>
                        Reject
                    </button>
                    
                    <?php elseif($currentStatus === 'approved'): ?>
                    <form method="post" class="inline">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="action" value="process">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                                onclick="return confirm('Start processing this refund?')">
                            <i class="fas fa-cog mr-1"></i>
                            Start Processing
                        </button>
                    </form>
                    
                    <?php elseif($currentStatus === 'processing'): ?>
                    <form method="post" class="inline">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="action" value="complete">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                onclick="return confirm('Mark this refund as completed?')">
                            <i class="fas fa-check-circle mr-1"></i>
                            Mark Complete
                        </button>
                    </form>
                    <?php endif; ?>
                    
                  
                   
                    
                    <button onclick="closeModal('modal<?= $order['id'] ?>')" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- NEW: Rejection Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-[70] p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Reject Refund Request</h3>
            </div>
            <form id="rejectForm" method="post">
                <div class="px-6 py-4">
                    <input type="hidden" name="order_id" id="rejectOrderId">
                    <input type="hidden" name="action" value="reject">
                    
                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Rejection Reason <span class="text-red-500">*</span>
                    </label>
                    <textarea name="rejection_reason" 
                              id="rejection_reason" 
                              rows="4" 
                              required
                              placeholder="Please explain why this refund request is being rejected..."
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"></textarea>
                    <p class="mt-2 text-sm text-gray-500">This reason will be visible to the customer.</p>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg flex justify-end space-x-3">
                    <button type="button" 
                            onclick="closeRejectModal()"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                            onclick="return confirm('Are you sure you want to reject this refund request?')">
                        <i class="fas fa-times mr-1"></i>
                        Reject Refund
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    // NEW: Reject modal functions
    function openRejectModal(orderId) {
        // Close any open order modals first
        const openOrderModals = document.querySelectorAll('[id^="modal"]:not(.hidden)');
        openOrderModals.forEach(modal => {
            modal.classList.add('hidden');
        });
        
        document.getElementById('rejectOrderId').value = orderId;
        document.getElementById('rejection_reason').value = '';
        document.getElementById('rejectModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        // Don't reset body overflow here in case we want to go back to the order modal
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const visibleModals = document.querySelectorAll('[id^="modal"]:not(.hidden), #rejectModal:not(.hidden)');
            visibleModals.forEach(modal => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            });
        }
    });

    // Close modal on backdrop click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('bg-gray-600') && e.target.classList.contains('bg-opacity-50')) {
            e.target.classList.add('hidden');
            document.body.style.overflow = '';
        }
    });
    </script>

</body>
</html>