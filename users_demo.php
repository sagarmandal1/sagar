<?php
session_start();

// Mock data for demonstration (since database is not available)
$mock_users = [
    [
        'id' => 1,
        'name' => 'সাগর মন্ডল',
        'email' => 'sagar@example.com',
        'phone' => '+8801712345678',
        'balance' => 1500.00,
        'created_at' => '2025-01-15 10:30:00'
    ],
    [
        'id' => 2,
        'name' => 'রহিম উদ্দিন',
        'email' => 'rahim@example.com',
        'phone' => '+8801812345678',
        'balance' => 2500.00,
        'created_at' => '2025-01-14 09:15:00'
    ],
    [
        'id' => 3,
        'name' => 'করিম আহমেদ',
        'email' => 'karim@example.com',
        'phone' => '+8801912345678',
        'balance' => 800.00,
        'created_at' => '2025-01-13 14:20:00'
    ],
    [
        'id' => 4,
        'name' => 'ফাতেমা খাতুন',
        'email' => 'fatema@example.com',
        'phone' => '+8801612345678',
        'balance' => 3200.00,
        'created_at' => '2025-01-12 11:45:00'
    ],
    [
        'id' => 5,
        'name' => 'আব্দুল হালিম',
        'email' => 'halim@example.com',
        'phone' => '+8801512345678',
        'balance' => 950.00,
        'created_at' => '2025-01-11 16:30:00'
    ]
];

$mock_activities = [
    [
        'user_name' => 'সাগর মন্ডল',
        'amount' => 500.00,
        'note' => 'API সেবার জন্য প্রাথমিক ক্রেডিট',
        'created_at' => '2025-01-16 10:30:00'
    ],
    [
        'user_name' => 'রহিম উদ্দিন',
        'amount' => 1000.00,
        'note' => 'মাসিক প্ল্যান রিচার্জ',
        'created_at' => '2025-01-16 09:15:00'
    ],
    [
        'user_name' => 'ফাতেমা খাতুন',
        'amount' => 750.00,
        'note' => 'বোনাস ক্রেডিট',
        'created_at' => '2025-01-16 08:45:00'
    ],
    [
        'user_name' => 'করিম আহমেদ',
        'amount' => 200.00,
        'note' => 'রেফারেল বোনাস',
        'created_at' => '2025-01-15 22:10:00'
    ],
    [
        'user_name' => 'আব্দুল হালিম',
        'amount' => 300.00,
        'note' => 'স্টার্টার প্ল্যান ক্রয়',
        'created_at' => '2025-01-15 20:30:00'
    ]
];

// Mock today's summary
$today_summary = [
    'transactions' => 8,
    'total_amount' => 4250.00
];

// Initialize action
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$users = $mock_users;
$recent_activities = $mock_activities;

// Handle demo form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enhanced_add_credit']) && isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'ক্রেডিট সফলভাবে যোগ করা হয়েছে! (ডেমো মোড)']);
        exit;
    }
    
    // For demo purposes, just set success message
    $_SESSION['success'] = "ডেমো মোডে কাজ করছে! ডাটাবেস সংযোগের জন্য README দেখুন।";
    header("Location: users_demo.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ইউজার ম্যানেজমেন্ট সিস্টেম - ডেমো</title>
    
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
        
        .demo-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
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
    <!-- Demo Badge -->
    <div class="demo-badge">
        <i class="fas fa-eye me-2"></i>
        ডেমো মোড
    </div>

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
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            এটি একটি ডেমো ভার্সন। সম্পূর্ণ ফিচারের জন্য ডাটাবেস সেটআপ করুন।
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
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
                                                <div class="h3 mb-1"><?php echo $today_summary['transactions']; ?></div>
                                                <small class="opacity-75">লেনদেন</small>
                                            </div>
                                            <div class="col-6">
                                                <div class="h3 mb-1">৳<?php echo number_format($today_summary['total_amount'], 2); ?></div>
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
                                            <?php foreach (array_slice($recent_activities, 0, 5) as $activity): ?>
                                                <div class="activity-item">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($activity['user_name']); ?></strong>
                                                            <div class="small opacity-75">
                                                                <?php echo htmlspecialchars($activity['note']); ?>
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
                            <button type="button" class="btn btn-gradient" onclick="showDemoAlert()">
                                <i class="fas fa-user-plus me-2"></i>
                                নতুন ইউজার
                            </button>
                        </div>
                    </div>

                    <!-- Users List -->
                    <div class="row" id="usersList">
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
                                                    onclick="showDemoAlert()">
                                                <i class="fas fa-edit me-1"></i>
                                                সম্পাদনা
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success flex-fill" 
                                                    onclick="showDemoAlert()">
                                                <i class="fas fa-plus me-1"></i>
                                                ক্রেডিট
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="showDemoAlert()">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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

            // Enhanced Add Credit (Demo)
            addCreditBtn.addEventListener('click', function() {
                const userId = userSelect.value;
                const amount = parseFloat(creditAmount.value);

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
                
                // Demo success message
                showAlert(`ডেমো মোডে ${userName} এর অ্যাকাউন্টে ৳${amount} (${calls} API কল) যোগ করা হলো!`, 'success');
                
                // Reset form
                userSelect.value = '';
                creditAmount.value = '';
                creditNote.value = '';
                creditForm.style.display = 'none';
                apiCalls.textContent = '০';
            });
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

        function showDemoAlert() {
            showAlert('এটি ডেমো মোড! সম্পূর্ণ ফিচারের জন্য ডাটাবেস সেটআপ করুন।', 'info');
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
        }, 8000);
    </script>
</body>
</html>