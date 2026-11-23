<?php
session_start();
// Handle Role Switch (Admin vs User)
if (isset($_GET['role'])) {
    $_SESSION['is_admin'] = ($_GET['role'] === 'admin');
}
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cosc 428 - LEMP Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4 mb-5">
        
        <!-- Header & Role Toggle -->
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Cosc 428: LEMP Stack Project</h2>
                <div>
                    <span class="me-2 fw-bold">Role: <?php echo $isAdmin ? 'Admin' : 'User'; ?></span>
                    <?php if($isAdmin): ?>
                        <a href="?role=user" class="btn btn-sm btn-warning fw-bold">Switch to User Mode</a>
                    <?php else: ?>
                        <a href="?role=admin" class="btn btn-sm btn-success fw-bold">Switch to Admin Mode</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php
        $host = 'mysql';
        $db   = 'appdb';
        $user = 'appuser';
        $pass = 'apppass';

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // --- 1. HANDLE FILE DELETE (ADMIN ONLY) ---
            if ($isAdmin && isset($_GET['delete'])) {
                $fileToDelete = basename($_GET['delete']);
                $filePath = "uploads/" . $fileToDelete;
                if (file_exists($filePath)) {
                    unlink($filePath);
                    echo '<div class="alert alert-danger alert-dismissible fade show">🗑️ File <strong>' . htmlspecialchars($fileToDelete) . '</strong> has been deleted. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                }
            }

            // --- 2. HANDLE FILE UPLOAD (ADMIN ONLY) ---
            if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploaded_file'])) {
                $fileName = basename($_FILES['uploaded_file']['name']);
                $targetFile = "uploads/" . $fileName;
                $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'txt', 'docx'];
                $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed)) {
                    if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $targetFile)) {
                        echo '<div class="alert alert-success alert-dismissible fade show">✅ File <strong>' . htmlspecialchars($fileName) . '</strong> uploaded successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    } else {
                        echo '<div class="alert alert-danger">❌ Error moving file. Check folder permissions.</div>';
                    }
                } else {
                    echo '<div class="alert alert-warning">⚠️ Invalid file type. Only images, PDFs, and docs allowed.</div>';
                }
            }

            // --- 3. HANDLE TEXT MESSAGE SUBMIT ---
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['msg'])) {
                $stmt = $pdo->prepare("INSERT INTO messages (content) VALUES (?)");
                $stmt->execute([$_POST['msg']]);
            }

            // --- 4. FETCH LIVE STATS ---
            $totalMessages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
            $totalFiles = 0;
            $dir = "uploads/";
            if (is_dir($dir)) {
                $totalFiles = count(array_diff(scandir($dir), array('.', '..')));
            }

            // --- 5. DISPLAY STATS & SERVER STATUS DASHBOARD ---
            echo '<div class="row text-center mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm border-primary">
                            <div class="card-body">
                                <h6 class="text-muted">Total Messages</h6>
                                <h2 class="text-primary fw-bold">' . $totalMessages . '</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-success">
                            <div class="card-body">
                                <h6 class="text-muted">Total Files Uploaded</h6>
                                <h2 class="text-success fw-bold">' . $totalFiles . '</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-dark">
                            <div class="card-body">
                                <h6 class="text-muted">Server Uptime (Hours)</h6>
                                <h2 class="text-dark fw-bold">' . round(file_get_contents('/proc/uptime') / 3600, 1) . '</h2>
                            </div>
                        </div>
                    </div>
                  </div>';

        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">❌ Database Connection failed: ' . $e->getMessage() . '</div>';
        }
        ?>

        <div class="row">
            <!-- Left Column: Files -->
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">📁 File Manager</h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($isAdmin): ?>
                        <!-- Upload Form (Admin Only) -->
                        <form method="POST" enctype="multipart/form-data" class="mb-4 bg-light p-3 rounded border border-success">
                            <label class="form-label fw-bold text-success">Admin: Upload a new file</label>
                            <div class="input-group">
                                <input type="file" name="uploaded_file" class="form-control" required>
                                <button type="submit" class="btn btn-success">Upload</button>
                            </div>
                        </form>
                        <?php endif; ?>

                        <!-- File List -->
                        <h5 class="border-bottom pb-2">Available Downloads</h5>
                        <ul class="list-group">
                            <?php
                            if (isset($dir) && is_dir($dir)) {
                                $files = array_diff(scandir($dir), array('.', '..'));
                                if (count($files) > 0) {
                                    foreach ($files as $file) {
                                        $filePath = $dir . $file;
                                       $fileSize = is_numeric(filesize($filePath)) ? round(filesize($filePath) / 1024, 2) : 0; 
                                        
                                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>' . htmlspecialchars($file) . '</strong><br>
                                                    <small class="text-muted">' . $fileSize . ' KB</small>
                                                </div>
                                                <div>';
                                        // Everyone can download
                                        echo '<a href="' . $filePath . '" class="btn btn-sm btn-outline-primary me-2" download>Download</a>';
                                        
                                        // Only Admin can delete
                                        if ($isAdmin) {
                                            echo '<a href="?delete=' . urlencode($file) . '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Delete this file?\');">Delete</a>';
                                        }
                                        echo '</div></li>';
                                    }
                                } else {
                                    echo '<li class="list-group-item text-muted">No files uploaded yet.</li>';
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Column: Messages -->
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">💬 Message Board</h4>
                    </div>
                    <div class="card-body">
                    
                        <!-- Search Bar -->
                        <form method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search messages..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button type="submit" class="btn btn-outline-secondary">Search</button>
                            </div>
                        </form>

                        <!-- Message Form -->
                        <form method="POST" class="mb-4 bg-light p-3 rounded border border-primary">
                            <label class="form-label fw-bold text-primary">Post a new message</label>
                            <div class="input-group">
                                <input type="text" name="msg" class="form-control" placeholder="Type your message here..." required>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>

                        <!-- Message List -->
                        <h5 class="border-bottom pb-2">Recent Messages</h5>
                        <ul class="list-group">
                            <?php
                            if (isset($pdo)) {
                                $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
                                
                                if ($searchTerm) {
                                    // Prepared statement to prevent SQL Injection
                                    $stmt = $pdo->prepare("SELECT * FROM messages WHERE content LIKE ? ORDER BY id DESC");
                                    $stmt->execute(["%$searchTerm%"]);
                                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } else {
                                    $rows = $pdo->query("SELECT * FROM messages ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
                                }

                                if (count($rows) > 0) {
                                    foreach ($rows as $r) {
                                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>' . htmlspecialchars($r['content']) . '</span>
                                                <span class="badge bg-secondary rounded-pill">' . date('H:i', strtotime($r['created_at'])) . '</span>
                                              </li>';
                                    }
                                } else {
                                    echo '<li class="list-group-item text-muted">No messages found.</li>';
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>