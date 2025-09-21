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
// Handle Actions
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'approved' WHERE id = :id AND seller_id = :seller_id");
                $stmt->execute(['id' => $orderId, 'seller_id' => $sellerId]);
                break;

            case 'ship':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'shipped' WHERE id = :id AND seller_id = :seller_id");
                $stmt->execute(['id' => $orderId, 'seller_id' => $sellerId]);
                break;

            case 'deliver':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'delivered' WHERE id = :id AND seller_id = :seller_id");
                $stmt->execute(['id' => $orderId, 'seller_id' => $sellerId]);
                break;

            case 'cancel':
                if (!empty($_POST['reason'])) {
                    $reason = $_POST['reason'];
                    $notes = $_POST['notes'] ?? '';
                    $fullReason = $notes ? $reason . ' - ' . $notes : $reason;
                    
                    $stmt = $pdo->prepare("UPDATE orders SET status = 'canceled', cancel_reason = :reason WHERE id = :id AND seller_id = :seller_id");
                    $stmt->execute(['reason' => $fullReason, 'id' => $orderId, 'seller_id' => $sellerId]);
                }
                break;

            case 'approve_return':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'returned', refund_status = 'approved' WHERE id = :id AND seller_id = :seller_id");
                $stmt->execute(['id' => $orderId, 'seller_id' => $sellerId]);
                break;

            case 'reject_return':
                if (!empty($_POST['rejection_reason'])) {
                    $rejectionReason = $_POST['rejection_reason'];
                    $stmt = $pdo->prepare("UPDATE orders SET refund_status = 'rejected', cancel_reason = :reason WHERE id = :id AND seller_id = :seller_id");
                    $stmt->execute(['reason' => 'Return rejected: ' . $rejectionReason, 'id' => $orderId, 'seller_id' => $sellerId]);
                }
                break;

            case 'process_return':
                if (!empty($_POST['return_reason'])) {
                    $returnReason = $_POST['return_reason'];
                    $stmt = $pdo->prepare("UPDATE orders SET return_reason = :return_reason, refund_status = 'pending' WHERE id = :id AND seller_id = :seller_id");
                    $stmt->execute(['return_reason' => $returnReason, 'id' => $orderId, 'seller_id' => $sellerId]);
                }
                break;
        }

        header("Location: ".$_SERVER['PHP_SELF']."?success=1");
        exit;
    } catch (Exception $e) {
        $error = "Action failed: " . $e->getMessage();
    }
}

// ==========================
// Fetch Orders with Cancellations or Return/Refund Requests Only - FIXED QUERY
// ==========================
$sql = "
    SELECT 
        o.id AS order_id,
        o.user_id AS customer_id,
        o.seller_id,
        o.total_amount,
        o.status,
        o.created_at,
        o.cancel_reason,
        o.return_reason,
        o.refund_status,
        p.name AS product_name,
        p.image_url,
        oi.quantity,
        oi.price
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.seller_id = :seller_id 
    AND (
        o.status = 'canceled' 
        OR o.return_reason IS NOT NULL 
        OR (o.refund_status IS NOT NULL AND o.refund_status != 'none')
    )
    ORDER BY o.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['seller_id' => $sellerId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group data by order
$orders = [];
foreach ($rows as $row) {
    $oid = $row['order_id'];
    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'order_id' => $row['order_id'],
            'customer_id' => $row['customer_id'],
            'status' => $row['status'],
            'total_amount' => $row['total_amount'],
            'created_at' => $row['created_at'],
            'cancel_reason' => $row['cancel_reason'],
            'return_reason' => $row['return_reason'],
            'refund_status' => $row['refund_status'],
            'products' => []
        ];
    }
    $orders[$oid]['products'][] = [
        'name' => $row['product_name'],
        'image_url' => $row['image_url'],
        'quantity' => $row['quantity'],
        'price' => $row['price']
    ];
}

// Helper functions
function getStatusInfo($status) {
    $statusInfo = [
        'pending' => ['class' => 'bg-yellow-100 text-yellow-800', 'label' => 'Pending'],
        'approved' => ['class' => 'bg-blue-100 text-blue-800', 'label' => 'Approved'],
        'shipped' => ['class' => 'bg-purple-100 text-purple-800', 'label' => 'Shipped'],
        'delivered' => ['class' => 'bg-green-100 text-green-800', 'label' => 'Delivered'],
        'canceled' => ['class' => 'bg-red-100 text-red-800', 'label' => 'Canceled'],
        'returned' => ['class' => 'bg-orange-100 text-orange-800', 'label' => 'Returned']
    ];
    
    return $statusInfo[$status] ?? ['class' => 'bg-gray-100 text-gray-800', 'label' => ucfirst($status)];
}

function getRefundStatusInfo($refundStatus) {
    $statusInfo = [
        'none' => ['class' => 'bg-gray-100 text-gray-600', 'label' => 'No Refund'],
        'pending' => ['class' => 'bg-yellow-100 text-yellow-800', 'label' => 'Refund Pending'],
        'approved' => ['class' => 'bg-green-100 text-green-800', 'label' => 'Refund Approved'],
        'rejected' => ['class' => 'bg-red-100 text-red-800', 'label' => 'Refund Rejected'],
        'processed' => ['class' => 'bg-blue-100 text-blue-800', 'label' => 'Refund Processed']
    ];
    
    return $statusInfo[$refundStatus] ?? ['class' => 'bg-gray-100 text-gray-800', 'label' => 'Unknown'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Management - Cancellation & Returns</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .modal-hidden { display: none !important; }
        .workflow-step {
            transition: all 0.3s ease;
        }
        .workflow-step.completed {
            background-color: #10b981;
            color: white;
        }
        .workflow-step.current {
            background-color: #3b82f6;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="ml-64 pt-20 p-6 relative z-10">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">Cancellations & Returns Management</h2>
                    <p class="text-gray-600 mt-2">Manage canceled orders and refund requests</p>
                </div>
                
                <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    Action completed successfully!
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Statistics Cards - Updated to show correct counts -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-4 rounded-lg shadow text-center border-l-4 border-red-500">
                    <div class="text-2xl font-bold text-red-600"><?= count(array_filter($orders, fn($o) => $o['status'] === 'canceled')) ?></div>
                    <div class="text-sm text-gray-600">Canceled Orders</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow text-center border-l-4 border-orange-500">
                    <div class="text-2xl font-bold text-orange-600"><?= count(array_filter($orders, fn($o) => !empty($o['return_reason']))) ?></div>
                    <div class="text-sm text-gray-600">Return Requests</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow text-center border-l-4 border-yellow-500">
                    <div class="text-2xl font-bold text-yellow-600"><?= count(array_filter($orders, fn($o) => $o['refund_status'] === 'pending')) ?></div>
                    <div class="text-sm text-gray-600">Pending Refunds</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow text-center border-l-4 border-blue-500">
                    <div class="text-2xl font-bold text-blue-600"><?= count(array_filter($orders, fn($o) => in_array($o['refund_status'], ['approved', 'rejected']))) ?></div>
                    <div class="text-sm text-gray-600">Processed Refunds</div>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded">
                    <div class="flex items-center">
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-blue-800">No Cancellations or Returns</h3>
                            <p class="text-sm text-blue-700 mt-1">You don't have any canceled orders or refund requests at the moment.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white">Order #</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white">Customer</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white">Issue Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white">Total</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($orders as $order): ?>
                                <?php $statusInfo = getStatusInfo($order['status']); ?>
                                <?php $refundInfo = getRefundStatusInfo($order['refund_status'] ?? 'none'); ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium">#<?= htmlspecialchars($order['order_id']) ?></td>
                                    <td class="px-4 py-3">Customer <?= htmlspecialchars($order['customer_id']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusInfo['class'] ?>">
                                            <?= $statusInfo['label'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php 
                                        $issueType = '';
                                        $issueClass = '';
                                        if ($order['status'] === 'canceled') {
                                            $issueType = 'Cancellation';
                                            $issueClass = 'bg-red-100 text-red-800';
                                        } elseif (!empty($order['return_reason'])) {
                                            $issueType = 'Return Request';
                                            $issueClass = 'bg-orange-100 text-orange-800';
                                        } elseif (!empty($order['refund_status']) && $order['refund_status'] !== 'none') {
                                            $issueType = 'Refund Request';
                                            $issueClass = 'bg-yellow-100 text-yellow-800';
                                        }
                                        ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $issueClass ?>">
                                            <?= $issueType ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">₱<?= number_format($order['total_amount'], 2) ?></td>
                                    <td class="px-4 py-3 text-sm"><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                    <td class="px-4 py-3">
                                        <button onclick="openModal('orderModal<?= $order['order_id'] ?>')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm">
                                            <i class="bi bi-eye"></i> Review
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Order Management Modals -->
                <?php foreach ($orders as $order): ?>
                <div id="orderModal<?= $order['order_id'] ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                        <div class="p-6">
                            <!-- Header -->
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h3 class="text-xl font-bold">Order #<?= $order['order_id'] ?> - Issue Review</h3>
                                    <?php 
                                    if ($order['status'] === 'canceled') {
                                        echo '<span class="text-sm text-red-600">Cancellation Review</span>';
                                    } elseif (!empty($order['return_reason'])) {
                                        echo '<span class="text-sm text-orange-600">Return Request Review</span>';
                                    } elseif (!empty($order['refund_status']) && $order['refund_status'] !== 'none') {
                                        echo '<span class="text-sm text-yellow-600">Refund Request Review</span>';
                                    }
                                    ?>
                                </div>
                                <button onclick="closeModal('orderModal<?= $order['order_id'] ?>')" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Issue Summary -->
                            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                                <h4 class="font-semibold mb-3 text-gray-800">Issue Summary</h4>
                                <div class="space-y-2">
                                    <?php if ($order['status'] === 'canceled' && !empty($order['cancel_reason'])): ?>
                                    <div class="flex items-start space-x-3">
                                        <span class="inline-block w-2 h-2 bg-red-500 rounded-full mt-2"></span>
                                        <div>
                                            <span class="font-medium text-red-700">Cancellation Reason:</span>
                                            <p class="text-gray-700"><?= htmlspecialchars($order['cancel_reason']) ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($order['return_reason'])): ?>
                                    <div class="flex items-start space-x-3">
                                        <span class="inline-block w-2 h-2 bg-orange-500 rounded-full mt-2"></span>
                                        <div>
                                            <span class="font-medium text-orange-700">Return Reason:</span>
                                            <p class="text-gray-700"><?= htmlspecialchars($order['return_reason']) ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($order['refund_status']) && $order['refund_status'] !== 'none'): ?>
                                    <div class="flex items-start space-x-3">
                                        <span class="inline-block w-2 h-2 bg-blue-500 rounded-full mt-2"></span>
                                        <div>
                                            <span class="font-medium text-blue-700">Refund Status:</span>
                                            <span class="ml-2 px-2 py-1 text-xs rounded-full <?= getRefundStatusInfo($order['refund_status'])['class'] ?>">
                                                <?= getRefundStatusInfo($order['refund_status'])['label'] ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Order Details -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <h4 class="font-semibold mb-2">Order Information</h4>
                                    <div class="space-y-2 text-sm">
                                        <div><span class="font-medium">Status:</span> 
                                            <span class="ml-2 px-2 py-1 text-xs rounded-full <?= getStatusInfo($order['status'])['class'] ?>">
                                                <?= getStatusInfo($order['status'])['label'] ?>
                                            </span>
                                        </div>
                                        <div><span class="font-medium">Total:</span> ₱<?= number_format($order['total_amount'], 2) ?></div>
                                        <div><span class="font-medium">Created:</span> <?= $order['created_at'] ?></div>
                                        <?php if (!empty($order['cancel_reason'])): ?>
                                        <div><span class="font-medium">Cancel Reason:</span> <?= htmlspecialchars($order['cancel_reason']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Return/Refund Information -->
                                <?php if (!empty($order['return_reason']) || (!empty($order['refund_status']) && $order['refund_status'] !== 'none')): ?>
                                <div>
                                    <h4 class="font-semibold mb-2 text-orange-600">Return & Refund Info</h4>
                                    <div class="space-y-2 text-sm bg-orange-50 p-4 rounded-lg">
                                        <?php if (!empty($order['return_reason'])): ?>
                                        <div><span class="font-medium">Return Reason:</span> <?= htmlspecialchars($order['return_reason']) ?></div>
                                        <?php endif; ?>
                                        <div><span class="font-medium">Refund Status:</span> 
                                            <span class="ml-2 px-2 py-1 text-xs rounded-full <?= getRefundStatusInfo($order['refund_status'] ?? 'none')['class'] ?>">
                                                <?= getRefundStatusInfo($order['refund_status'] ?? 'none')['label'] ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Products -->
                            <div class="mb-6">
                                <h4 class="font-semibold mb-3">Products</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full border border-gray-200 rounded-lg">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Product</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Quantity</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Price</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <?php foreach ($order['products'] as $product): ?>
                                            <tr>
                                                <td class="px-4 py-2"><?= htmlspecialchars($product['name']) ?></td>
                                                <td class="px-4 py-2"><?= $product['quantity'] ?></td>
                                                <td class="px-4 py-2">₱<?= number_format($product['price'], 2) ?></td>
                                                <td class="px-4 py-2">₱<?= number_format($product['price'] * $product['quantity'], 2) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Action Buttons - Only for Refund Processing -->
                            <div class="flex flex-wrap gap-3 pt-4 border-t">
                                <?php if ($order['refund_status'] === 'pending'): ?>
                                    <!-- Pending refund actions -->
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <input type="hidden" name="action" value="approve_return">
                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                                            <i class="bi bi-check-circle"></i> Approve Refund
                                        </button>
                                    </form>

                                    <button onclick="openRejectModal('rejectModal<?= $order['order_id'] ?>')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                                        <i class="bi bi-x-circle"></i> Reject Refund
                                    </button>
                                    
                                <?php elseif ($order['status'] === 'delivered' && empty($order['return_reason'])): ?>
                                    <!-- Allow manual return processing for delivered orders -->
                                    <button onclick="openReturnModal('returnModal<?= $order['order_id'] ?>')" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded text-sm">
                                        <i class="bi bi-arrow-return-left"></i> Process Return Request
                                    </button>
                                    
                                <?php else: ?>
                                    <!-- No actions available -->
                                    <div class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm">
                                        <i class="bi bi-info-circle"></i> 
                                        <?php if ($order['status'] === 'canceled'): ?>
                                            Order has been canceled - no further actions available
                                        <?php elseif ($order['refund_status'] === 'approved'): ?>
                                            Refund has been approved
                                        <?php elseif ($order['refund_status'] === 'rejected'): ?>
                                            Refund has been rejected
                                        <?php else: ?>
                                            No actions available for this order
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <button onclick="closeModal('orderModal<?= $order['order_id'] ?>')" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm ml-auto">
                                    <i class="bi bi-x"></i> Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Return Request Modal -->
                <div id="returnModal<?= $order['order_id'] ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-orange-600">Process Return Request</h3>
                            <button onclick="closeReturnModal('returnModal<?= $order['order_id'] ?>')" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                            <input type="hidden" name="action" value="process_return">
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Return reason from customer:</label>
                                <select name="return_reason" required class="form-select w-full">
                                    <option value="">-- Select return reason --</option>
                                    <option value="Defective product">Defective product</option>
                                    <option value="Wrong item received">Wrong item received</option>
                                    <option value="Damaged during shipping">Damaged during shipping</option>
                                    <option value="Not as described">Not as described</option>
                                    <option value="Size/fit issues">Size/fit issues</option>
                                    <option value="Customer changed mind">Customer changed mind</option>
                                    <option value="Quality not satisfactory">Quality not satisfactory</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="mb-4 p-3 bg-yellow-50 rounded border">
                                <p class="text-sm text-yellow-700">
                                    <i class="bi bi-info-circle"></i>
                                    This will mark the return as pending and require your approval for refund processing.
                                </p>
                            </div>

                            <div class="flex gap-2 justify-end">
                                <button type="button" onclick="closeReturnModal('returnModal<?= $order['order_id'] ?>')" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
                                    Cancel
                                </button>
                                <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded text-sm">
                                    Process Return
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reject Refund Modal -->
                <div id="rejectModal<?= $order['order_id'] ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-red-600">Reject Refund Request</h3>
                            <button onclick="closeRejectModal('rejectModal<?= $order['order_id'] ?>')" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                            <input type="hidden" name="action" value="reject_return">
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rejection reason:</label>
                                <select name="rejection_reason" required class="form-select w-full">
                                    <option value="">-- Select rejection reason --</option>
                                    <option value="Item not eligible for return">Item not eligible for return</option>
                                    <option value="Return period expired">Return period expired</option>
                                    <option value="Item condition not acceptable">Item condition not acceptable</option>
                                    <option value="Missing original packaging">Missing original packaging</option>
                                    <option value="Item was used/damaged by customer">Item was used/damaged by customer</option>
                                    <option value="Insufficient documentation/proof">Insufficient documentation/proof</option>
                                    <option value="Return shipping not arranged">Return shipping not arranged</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="mb-4 p-3 bg-red-50 rounded border">
                                <p class="text-sm text-red-700">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    This will permanently reject the refund request. Make sure to communicate the reason to the customer.
                                </p>
                            </div>

                            <div class="flex gap-2 justify-end">
                                <button type="button" onclick="closeRejectModal('rejectModal<?= $order['order_id'] ?>')" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
                                    Cancel
                                </button>
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                                    Confirm Rejection
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
    </main>

    <script>
        // Modal Functions
        function openModal(id) {
            document.getElementById(id).classList.remove("hidden");
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.add("hidden");
            document.body.style.overflow = '';
        }

        function openReturnModal(id) {
            document.getElementById(id).classList.remove("hidden");
            document.body.style.overflow = 'hidden';
        }
        
        function closeReturnModal(id) {
            document.getElementById(id).classList.add("hidden");
            document.body.style.overflow = '';
        }

        function openRejectModal(id) {
            document.getElementById(id).classList.remove("hidden");
            document.body.style.overflow = 'hidden';
        }
        
        function closeRejectModal(id) {
            document.getElementById(id).classList.add("hidden");
            document.body.style.overflow = '';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('bg-opacity-50')) {
                event.target.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('[id$="Modal"]');
                modals.forEach(modal => {
                    if (!modal.classList.contains('hidden')) {
                        modal.classList.add('hidden');
                        document.body.style.overflow = '';
                    }
                });
            }
        });

        // Form validations and confirmations
        document.addEventListener('DOMContentLoaded', function() {
            // Return processing confirmation
            document.querySelectorAll('form').forEach(form => {
                if (form.querySelector('input[name="action"][value="process_return"]')) {
                    form.addEventListener('submit', function(e) {
                        const reason = this.querySelector('select[name="return_reason"]').value;
                        if (!reason) {
                            e.preventDefault();
                            alert('Please select a return reason.');
                            return false;
                        }
                        
                        const confirmation = confirm('Process this return request? This will set refund status to pending.');
                        if (!confirmation) {
                            e.preventDefault();
                            return false;
                        }
                    });
                }

                // Refund approval confirmation
                if (form.querySelector('input[name="action"][value="approve_return"]')) {
                    form.addEventListener('submit', function(e) {
                        const confirmation = confirm('Approve this refund? The order will be marked as returned and refund approved.');
                        if (!confirmation) {
                            e.preventDefault();
                            return false;
                        }
                    });
                }

                // Refund rejection confirmation
                if (form.querySelector('input[name="action"][value="reject_return"]')) {
                    form.addEventListener('submit', function(e) {
                        const reason = this.querySelector('select[name="rejection_reason"]').value;
                        if (!reason) {
                            e.preventDefault();
                            alert('Please select a rejection reason.');
                            return false;
                        }
                        
                        const confirmation = confirm('Reject this refund request? This action cannot be undone.');
                        if (!confirmation) {
                            e.preventDefault();
                            return false;
                        }
                    });
                }
            });
        });

        // Auto-hide success message
        setTimeout(function() {
            const successMsg = document.querySelector('.bg-green-100');
            if (successMsg) {
                successMsg.style.display = 'none';
            }
        }, 5000);
    </script>

    <!-- Database Schema Information -->
    <!--
    Your current database schema is perfect! The system works with:

    orders table columns:
    - id (primary key)
    - user_id (customer ID)
    - seller_id (your seller ID)
    - total_amount (order total)
    - status (pending, approved, shipped, delivered, canceled, returned)
    - created_at (order creation time)
    - cancel_reason (cancellation reason - can be NULL)
    - return_reason (return reason - can be NULL)
    - refund_status (none, pending, approved, rejected, processed)

    No additional tables needed! The system uses your existing schema.
    -->
</body>
</html>