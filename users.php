<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'user_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize action
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $balance = (float)($_POST['balance'] ?? 0);
        
        if (!empty($name) && !empty($email)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, balance, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $email, $phone, $balance]);
                $_SESSION['success'] = "User created successfully!";
            } catch(PDOException $e) {
                $_SESSION['error'] = "Error creating user: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Name and email are required!";
        }
        header("Location: users.php");
        exit;
    }
    
    if (isset($_POST['update_user'])) {
        $id = (int)$_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $balance = (float)($_POST['balance'] ?? 0);
        
        if (!empty($name) && !empty($email)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, balance = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $balance, $id]);
                $_SESSION['success'] = "User updated successfully!";
            } catch(PDOException $e) {
                $_SESSION['error'] = "Error updating user: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Name and email are required!";
        }
        header("Location: users.php");
        exit;
    }
    
    if (isset($_POST['add_credit'])) {
        $user_id = (int)$_POST['user_id'];
        $amount = (float)$_POST['amount'];
        $note = trim($_POST['note'] ?? '');
        
        if ($amount > 0) {
            try {
                $pdo->beginTransaction();
                
                // Update user balance
                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$amount, $user_id]);
                
                // Add credit transaction
                $stmt = $pdo->prepare("INSERT INTO credit_transactions (user_id, amount, note, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user_id, $amount, $note]);
                
                $pdo->commit();
                $_SESSION['success'] = "Credit added successfully!";
            } catch(PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Error adding credit: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Amount must be greater than 0!";
        }
        header("Location: users.php");
        exit;
    }
    
    if (isset($_POST['enhanced_add_credit'])) {
        $user_id = (int)$_POST['user_id'];
        $amount = (float)$_POST['amount'];
        $note = trim($_POST['note'] ?? '');
        
        if ($amount > 0) {
            try {
                $pdo->beginTransaction();
                
                // Update user balance
                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$amount, $user_id]);
                
                // Add credit transaction
                $stmt = $pdo->prepare("INSERT INTO credit_transactions (user_id, amount, note, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user_id, $amount, $note]);
                
                $pdo->commit();
                
                // Return JSON response for AJAX
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'ক্রেডিট সফলভাবে যোগ করা হয়েছে!']);
                    exit;
                }
                
                $_SESSION['success'] = "ক্রেডিট সফলভাবে যোগ করা হয়েছে!";
            } catch(PDOException $e) {
                $pdo->rollBack();
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'ত্রুটি: ' . $e->getMessage()]);
                    exit;
                }
                $_SESSION['error'] = "Error adding credit: " . $e->getMessage();
            }
        } else {
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'পরিমাণ ০ এর বেশি হতে হবে!']);
                exit;
            }
            $_SESSION['error'] = "Amount must be greater than 0!";
        }
        
        if (!isset($_POST['ajax'])) {
            header("Location: users.php");
            exit;
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $id = (int)$_POST['user_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "User deleted successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
        }
        header("Location: users.php");
        exit;
    }
}

// Handle delete action
if ($action === 'delete' && $user_id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "User deleted successfully!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
    }
    header("Location: users.php");
    exit;
}

// Fetch data for different actions
if ($action === 'list') {
    // Get users
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get today's credit summary
    $stmt = $pdo->query("SELECT COUNT(*) as transactions, SUM(amount) as total_amount FROM credit_transactions WHERE DATE(created_at) = CURDATE()");
    $today_summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent credit activities
    $stmt = $pdo->query("
        SELECT ct.*, u.name as user_name 
        FROM credit_transactions ct 
        JOIN users u ON ct.user_id = u.id 
        ORDER BY ct.created_at DESC 
        LIMIT 10
    ");
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($action === 'edit' && $user_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ইউজার ম্যানেজমেন্ট সিস্টেম</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts for Bengali -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Noto Sans Bengali', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .enhanced-credit-section {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border-radius: 15px;
            color: white;
            margin-bottom: 30px;
            overflow: hidden;
            position: relative;
        }
        
        .enhanced-credit-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }
        
        .credit-header {
            background: rgba(0,0,0,0.1);
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .quick-amount-btn {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 10px;
            padding: 10px 15px;
            margin: 5px;
            transition: all 0.3s ease;
        }
        
        .quick-amount-btn:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            color: white;
            transform: translateY(-2px);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #FF6B6B 0%, #ee5a5a 100%);
            border-radius: 15px;
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .activity-item {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #4CAF50;
            transition: transform 0.2s ease;
        }
        
        .activity-item:hover {
            transform: translateX(5px);
            background: rgba(255,255,255,0.15);
        }
        
        .user-search {
            border-radius: 50px;
            border: 3px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.9);
            padding: 12px 20px;
        }
        
        .api-calculator {
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            color: white;
        }
        
        .user-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            border: none;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">
                <div class="main-container p-4">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h1 class="display-4 fw-bold text-primary">
                            <i class="fas fa-users-cog me-3"></i>
                            ইউজার ম্যানেজমেন্ট সিস্টেম
                        </h1>
                        <p class="lead text-muted">সহজ ও কার্যকর ইউজার এবং ক্রেডিট ম্যানেজমেন্ট</p>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($action === 'list'): ?>
                    
                    <!-- Enhanced Credit Management Section -->
                    <div class="enhanced-credit-section fade-in">
                        <div class="credit-header">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h2 class="h3 mb-0">
                                        <i class="fas fa-coins me-2"></i>
                                        উন্নত ক্রেডিট ম্যানেজমেন্ট সিস্টেম
                                    </h2>
                                    <p class="mb-0 opacity-75">দ্রুত ও সহজভাবে ইউজারদের ক্রেডিট যোগ করুন</p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="d-flex align-items-center justify-content-md-end">
                                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="row">
                                <!-- User Search and Credit Addition -->
                                <div class="col-lg-8">
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-search me-2"></i>
                                            ইউজার অনুসন্ধান করুন
                                        </label>
                                        <select id="userSelect" class="form-select user-search">
                                            <option value="">ইউজার নির্বাচন করুন...</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                        data-balance="<?php echo $user['balance']; ?>">
                                                    <?php echo htmlspecialchars($user['name']) . ' - ব্যালেন্স: ৳' . number_format($user['balance'], 2); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div id="creditForm" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">
                                                    <i class="fas fa-money-bill-wave me-2"></i>
                                                    পরিমাণ (টাকা)
                                                </label>
                                                <input type="number" id="creditAmount" class="form-control" 
                                                       placeholder="পরিমাণ লিখুন..." min="1" step="0.01">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">
                                                    <i class="fas fa-sticky-note me-2"></i>
                                                    নোট (ঐচ্ছিক)
                                                </label>
                                                <input type="text" id="creditNote" class="form-control" 
                                                       placeholder="নোট লিখুন...">
                                            </div>
                                        </div>
                                        
                                        <!-- Quick Amount Buttons -->
                                        <div class="mt-3">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-bolt me-2"></i>
                                                দ্রুত নির্বাচন
                                            </label>
                                            <div>
                                                <button type="button" class="btn quick-amount-btn" data-amount="50">৳৫০</button>
                                                <button type="button" class="btn quick-amount-btn" data-amount="100">৳১০০</button>
                                                <button type="button" class="btn quick-amount-btn" data-amount="200">৳২০০</button>
                                                <button type="button" class="btn quick-amount-btn" data-amount="500">৳৫০০</button>
                                                <button type="button" class="btn quick-amount-btn" data-amount="1000">৳১০০০</button>
                                            </div>
                                        </div>
                                        
                                        <!-- API Calculator -->
                                        <div class="api-calculator">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-calculator me-2"></i>
                                                        <strong>API কল সংখ্যা:</strong>
                                                        <span id="apiCalls" class="ms-2 badge bg-light text-dark">০</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <small class="opacity-75">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        ১ টাকা = ১০ API কল
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <button type="button" id="addCreditBtn" class="btn btn-gradient btn-lg pulse">
                                                <i class="fas fa-plus-circle me-2"></i>
                                                ক্রেডিট যোগ করুন
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Today's Summary -->
                                <div class="col-lg-4">
                                    <div class="stats-card">
                                        <h4 class="mb-3">
                                            <i class="fas fa-chart-pie me-2"></i>
                                            আজকের সারসংক্ষেপ
                                        </h4>
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <div class="h3 mb-1"><?php echo $today_summary['transactions'] ?? 0; ?></div>
                                                <small class="opacity-75">লেনদেন</small>
                                            </div>
                                            <div class="col-6">
                                                <div class="h3 mb-1">৳<?php echo number_format($today_summary['total_amount'] ?? 0, 2); ?></div>
                                                <small class="opacity-75">মোট পরিমাণ</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Recent Activities -->
                                    <div>
                                        <h5 class="mb-3 text-white">
                                            <i class="fas fa-history me-2"></i>
                                            সাম্প্রতিক কার্যক্রম
                                        </h5>
                                        <div class="recent-activities" style="max-height: 300px; overflow-y: auto;">
                                            <?php if (!empty($recent_activities)): ?>
                                                <?php foreach (array_slice($recent_activities, 0, 5) as $activity): ?>
                                                    <div class="activity-item">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($activity['user_name']); ?></strong>
                                                                <div class="small opacity-75">
                                                                    <?php echo $activity['note'] ? htmlspecialchars($activity['note']) : 'ক্রেডিট যোগ করা হয়েছে'; ?>
                                                                </div>
                                                                <div class="small opacity-50">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    <?php echo date('d M Y, h:i A', strtotime($activity['created_at'])); ?>
                                                                </div>
                                                            </div>
                                                            <div class="text-end">
                                                                <div class="fw-bold">৳<?php echo number_format($activity['amount'], 2); ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="activity-item text-center">
                                                    <i class="fas fa-inbox fa-2x mb-2 opacity-50"></i>
                                                    <div class="small opacity-75">কোন কার্যক্রম পাওয়া যায়নি</div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h2 class="h4 mb-0">
                                <i class="fas fa-users me-2"></i>
                                ইউজার তালিকা
                            </h2>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <button type="button" class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                <i class="fas fa-user-plus me-2"></i>
                                নতুন ইউজার
                            </button>
                        </div>
                    </div>

                    <!-- Users List -->
                    <div class="row" id="usersList">
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card user-card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 50px; height: 50px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div class="ms-3">
                                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                                                    <p class="card-text text-muted mb-0">
                                                        <i class="fas fa-envelope me-1"></i>
                                                        <?php echo htmlspecialchars($user['email']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <?php if ($user['phone']): ?>
                                                <p class="card-text">
                                                    <i class="fas fa-phone me-2"></i>
                                                    <?php echo htmlspecialchars($user['phone']); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <span class="text-muted">ব্যালেন্স:</span>
                                                <span class="h5 mb-0 text-success">৳<?php echo number_format($user['balance'], 2); ?></span>
                                            </div>
                                            
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary flex-fill" 
                                                        onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                    <i class="fas fa-edit me-1"></i>
                                                    সম্পাদনা
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success flex-fill" 
                                                        onclick="openCreditModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                                    <i class="fas fa-plus me-1"></i>
                                                    ক্রেডিট
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">কোন ইউজার পাওয়া যায়নি</h4>
                                    <p class="text-muted">নতুন ইউজার যোগ করতে উপরের বোতাম ক্লিক করুন।</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php elseif ($action === 'edit' && isset($edit_user)): ?>
                    <!-- Edit User Form -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-user-edit me-2"></i>
                                ইউজার সম্পাদনা
                            </h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">নাম *</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo htmlspecialchars($edit_user['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">ইমেইল *</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">ফোন</label>
                                        <input type="text" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($edit_user['phone']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">ব্যালেন্স</label>
                                        <input type="number" class="form-control" name="balance" step="0.01"
                                               value="<?php echo $edit_user['balance']; ?>">
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" name="update_user" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        আপডেট করুন
                                    </button>
                                    <a href="users.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        ফিরে যান
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        নতুন ইউজার তৈরি
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">নাম *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ইমেইল *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ফোন</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">প্রাথমিক ব্যালেন্স</label>
                            <input type="number" class="form-control" name="balance" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" name="create_user" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            সংরক্ষণ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>
                        ইউজার সম্পাদনা
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">নাম *</label>
                            <input type="text" class="form-control" name="name" id="editUserName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ইমেইল *</label>
                            <input type="email" class="form-control" name="email" id="editUserEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ফোন</label>
                            <input type="text" class="form-control" name="phone" id="editUserPhone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ব্যালেন্স</label>
                            <input type="number" class="form-control" name="balance" id="editUserBalance" step="0.01">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" name="update_user" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            আপডেট
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Credit Modal (Original) -->
    <div class="modal fade" id="addCreditModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-coins me-2"></i>
                        ক্রেডিট যোগ করুন
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="user_id" id="creditUserId">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-user me-2"></i>
                            ইউজার: <strong id="creditUserName"></strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">পরিমাণ (টাকা) *</label>
                            <input type="number" class="form-control" name="amount" min="0.01" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">নোট</label>
                            <input type="text" class="form-control" name="note" placeholder="ক্রেডিট সম্পর্কে নোট...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" name="add_credit" class="btn btn-success">
                            <i class="fas fa-plus-circle me-2"></i>
                            ক্রেডিট যোগ করুন
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        নিশ্চিতকরণ
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <p id="confirmMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">না</button>
                    <button type="button" class="btn btn-danger" id="confirmYes">হ্যাঁ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Enhanced Credit Management JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const userSelect = document.getElementById('userSelect');
            const creditForm = document.getElementById('creditForm');
            const creditAmount = document.getElementById('creditAmount');
            const creditNote = document.getElementById('creditNote');
            const addCreditBtn = document.getElementById('addCreditBtn');
            const apiCalls = document.getElementById('apiCalls');
            const quickAmountBtns = document.querySelectorAll('.quick-amount-btn');

            // Show credit form when user is selected
            userSelect.addEventListener('change', function() {
                if (this.value) {
                    creditForm.style.display = 'block';
                    creditForm.classList.add('fade-in');
                } else {
                    creditForm.style.display = 'none';
                }
            });

            // Live API calculation
            creditAmount.addEventListener('input', function() {
                const amount = parseFloat(this.value) || 0;
                const calls = Math.floor(amount * 10); // 1 taka = 10 API calls
                apiCalls.textContent = calls.toLocaleString('bn-BD');
            });

            // Quick amount buttons
            quickAmountBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const amount = this.dataset.amount;
                    creditAmount.value = amount;
                    creditAmount.dispatchEvent(new Event('input'));
                    
                    // Add animation
                    this.classList.add('pulse');
                    setTimeout(() => this.classList.remove('pulse'), 1000);
                });
            });

            // Enhanced Add Credit
            addCreditBtn.addEventListener('click', function() {
                const userId = userSelect.value;
                const amount = parseFloat(creditAmount.value);
                const note = creditNote.value;

                if (!userId) {
                    showAlert('দয়া করে একটি ইউজার নির্বাচন করুন!', 'warning');
                    return;
                }

                if (!amount || amount <= 0) {
                    showAlert('দয়া করে সঠিক পরিমাণ লিখুন!', 'warning');
                    return;
                }

                const userName = userSelect.options[userSelect.selectedIndex].dataset.name;
                const calls = Math.floor(amount * 10);
                
                // Show confirmation
                showConfirmation(
                    `আপনি কি নিশ্চিত যে ${userName} এর অ্যাকাউন্টে ৳${amount} (${calls} API কল) যোগ করতে চান?`,
                    function() {
                        addCreditAjax(userId, amount, note);
                    }
                );
            });

            function addCreditAjax(userId, amount, note) {
                const formData = new FormData();
                formData.append('enhanced_add_credit', '1');
                formData.append('user_id', userId);
                formData.append('amount', amount);
                formData.append('note', note);
                formData.append('ajax', '1');

                // Show loading
                addCreditBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>প্রক্রিয়াকরণ...';
                addCreditBtn.disabled = true;

                fetch('users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        // Reset form
                        userSelect.value = '';
                        creditAmount.value = '';
                        creditNote.value = '';
                        creditForm.style.display = 'none';
                        apiCalls.textContent = '০';
                        
                        // Refresh page after 2 seconds
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        showAlert(data.message, 'danger');
                    }
                })
                .catch(error => {
                    showAlert('একটি ত্রুটি ঘটেছে!', 'danger');
                })
                .finally(() => {
                    // Reset button
                    addCreditBtn.innerHTML = '<i class="fas fa-plus-circle me-2"></i>ক্রেডিট যোগ করুন';
                    addCreditBtn.disabled = false;
                });
            }
        });

        // Utility functions
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.main-container');
            container.insertBefore(alertDiv, container.children[1]);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        function showConfirmation(message, callback) {
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            document.getElementById('confirmMessage').textContent = message;
            
            document.getElementById('confirmYes').onclick = function() {
                confirmModal.hide();
                callback();
            };
            
            confirmModal.show();
        }

        // Original modal functions
        function openEditModal(user) {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUserName').value = user.name;
            document.getElementById('editUserEmail').value = user.email;
            document.getElementById('editUserPhone').value = user.phone || '';
            document.getElementById('editUserBalance').value = user.balance;
            
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }

        function openCreditModal(userId, userName) {
            document.getElementById('creditUserId').value = userId;
            document.getElementById('creditUserName').textContent = userName;
            
            const modal = new bootstrap.Modal(document.getElementById('addCreditModal'));
            modal.show();
        }

        function confirmDelete(userId, userName) {
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            document.getElementById('confirmMessage').textContent = `আপনি কি নিশ্চিত যে "${userName}" কে মুছে দিতে চান?`;
            
            document.getElementById('confirmYes').onclick = function() {
                window.location.href = `users.php?action=delete&user_id=${userId}`;
            };
            
            confirmModal.show();
        }

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.querySelector('.btn-close')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>