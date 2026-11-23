<?php
// Handle Database Connection
$host = 'mysql';
$db   = 'appdb';
$user = 'appuser';
$pass = 'apppass';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle New Invoice Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_name'])) {
        $stmt = $pdo->prepare("INSERT INTO invoices (customer_name, item_description, amount) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['customer_name'], $_POST['item_description'], $_POST['amount']]);
        $successMessage = "Invoice created successfully!";
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .invoice-card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); border: none; }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary fw-bold">🧾 Invoice Management System</h2>
            <span class="badge bg-dark p-2">Cosc 428 Project</span>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm">
                ✅ <?php echo $successMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column: Create Invoice Form -->
            <div class="col-md-4 mb-4">
                <div class="card invoice-card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Create New Invoice</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Customer Name</label>
                                <input type="text" name="customer_name" class="form-control" placeholder="e.g., John Doe" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Item Description</label>
                                <input type="text" name="item_description" class="form-control" placeholder="e.g., Web Hosting" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Amount ($)</label>
                                <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100 fw-bold">Generate Invoice</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Invoices Table -->
            <div class="col-md-8 mb-4">
                <div class="card invoice-card h-100">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Invoices</h5>
                        <!-- Search Bar -->
                        <form method="GET" class="d-flex" style="max-width: 300px;">
                            <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Search customer..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-light">Search</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice ID</th>
                                        <th>Customer</th>
                                        <th>Item</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $search = isset($_GET['search']) ? $_GET['search'] : '';
                                    if ($search) {
                                        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE customer_name LIKE ? ORDER BY id DESC");
                                        $stmt->execute(["%$search%"]);
                                    } else {
                                        $stmt = $pdo->query("SELECT * FROM invoices ORDER BY id DESC");
                                    }
                                    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    $totalRevenue = 0;

                                    if (count($invoices) > 0) {
                                        foreach ($invoices as $inv) {
                                            $totalRevenue += $inv['amount'];
                                            echo "<tr>
                                                    <td><span class='badge bg-secondary'>#".str_pad($inv['id'], 4, '0', STR_PAD_LEFT)."</span></td>
                                                    <td>".htmlspecialchars($inv['customer_name'])."</td>
                                                    <td>".htmlspecialchars($inv['item_description'])."</td>
                                                    <td class='fw-bold text-success'>$".number_format($inv['amount'], 2)."</td>
                                                    <td><small class='text-muted'>".date('M d, Y', strtotime($inv['created_at']))."</small></td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center text-muted py-4'>No invoices found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                                <tfoot class="table-dark">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Total Revenue:</td>
                                        <td class="fw-bold text-warning">$<?php echo number_format($totalRevenue, 2); ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>