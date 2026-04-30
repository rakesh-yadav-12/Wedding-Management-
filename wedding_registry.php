<?php
// Wedding Registry Management System
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wedding_registry');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

session_start();

// Initialize database tables
initializeRegistryDatabase($conn);

// Sanitization function
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function initializeRegistryDatabase($conn) {
    $tables = [
        "CREATE TABLE IF NOT EXISTS registry_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            item_name VARCHAR(200) NOT NULL,
            category VARCHAR(100),
            description TEXT,
            price DECIMAL(10,2),
            quantity_needed INT DEFAULT 1,
            quantity_reserved INT DEFAULT 0,
            store_name VARCHAR(100),
            store_url VARCHAR(500),
            image_url VARCHAR(500),
            priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
            status ENUM('available', 'reserved', 'purchased') DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS registry_guests (
            id INT PRIMARY KEY AUTO_INCREMENT,
            guest_name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            relationship VARCHAR(50),
            rsvp_status ENUM('pending', 'attending', 'declined') DEFAULT 'pending',
            plus_one BOOLEAN DEFAULT FALSE,
            special_requests TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS registry_purchases (
            id INT PRIMARY KEY AUTO_INCREMENT,
            item_id INT,
            guest_id INT,
            quantity_purchased INT DEFAULT 1,
            purchase_date DATE,
            gift_message TEXT,
            gift_wrap BOOLEAN DEFAULT FALSE,
            thank_you_sent BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES registry_items(id) ON DELETE CASCADE,
            FOREIGN KEY (guest_id) REFERENCES registry_guests(id) ON DELETE SET NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS registry_wedding_info (
            id INT PRIMARY KEY AUTO_INCREMENT,
            couple_name VARCHAR(200),
            wedding_date DATE,
            venue_name VARCHAR(100),
            venue_address TEXT,
            wedding_theme VARCHAR(100),
            registry_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS registry_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100),
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $table) {
        $conn->query($table);
    }
    
    // Insert default wedding info if not exists
    $conn->query("INSERT IGNORE INTO registry_wedding_info (couple_name, wedding_date) VALUES ('The Happy Couple', CURDATE() + INTERVAL 6 MONTH)");
}

// Get wedding info
$wedding_info = $conn->query("SELECT * FROM registry_wedding_info LIMIT 1")->fetch_assoc();

// Handle form submissions
$success_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_item':
                $item_name = sanitizeInput($_POST['item_name']);
                $category = sanitizeInput($_POST['category']);
                $description = sanitizeInput($_POST['description']);
                $price = $_POST['price'] ?: 0;
                $quantity_needed = $_POST['quantity_needed'] ?: 1;
                $store_name = sanitizeInput($_POST['store_name']);
                $store_url = sanitizeInput($_POST['store_url']);
                $image_url = sanitizeInput($_POST['image_url']);
                $priority = sanitizeInput($_POST['priority']);
                
                $stmt = $conn->prepare("INSERT INTO registry_items (item_name, category, description, price, quantity_needed, store_name, store_url, image_url, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdissss", $item_name, $category, $description, $price, $quantity_needed, $store_name, $store_url, $image_url, $priority);
                if($stmt->execute()) {
                    $success_message = "Item added to registry!";
                    $_SESSION['last_added_id'] = $stmt->insert_id;
                }
                $stmt->close();
                break;
                
            case 'update_item':
                $id = $_POST['id'];
                $item_name = sanitizeInput($_POST['item_name']);
                $category = sanitizeInput($_POST['category']);
                $description = sanitizeInput($_POST['description']);
                $price = $_POST['price'] ?: 0;
                $quantity_needed = $_POST['quantity_needed'] ?: 1;
                $store_name = sanitizeInput($_POST['store_name']);
                $store_url = sanitizeInput($_POST['store_url']);
                $image_url = sanitizeInput($_POST['image_url']);
                $priority = sanitizeInput($_POST['priority']);
                $status = sanitizeInput($_POST['status']);
                
                $stmt = $conn->prepare("UPDATE registry_items SET item_name=?, category=?, description=?, price=?, quantity_needed=?, store_name=?, store_url=?, image_url=?, priority=?, status=? WHERE id=?");
                $stmt->bind_param("sssdisssssi", $item_name, $category, $description, $price, $quantity_needed, $store_name, $store_url, $image_url, $priority, $status, $id);
                if($stmt->execute()) {
                    $success_message = "Item updated!";
                }
                $stmt->close();
                break;
                
            case 'delete_item':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM registry_items WHERE id=?");
                $stmt->bind_param("i", $id);
                if($stmt->execute()) {
                    $success_message = "Item removed from registry!";
                }
                $stmt->close();
                break;
                
            case 'add_guest':
                $guest_name = sanitizeInput($_POST['guest_name']);
                $email = sanitizeInput($_POST['email']);
                $phone = sanitizeInput($_POST['phone']);
                $relationship = sanitizeInput($_POST['relationship']);
                $rsvp_status = sanitizeInput($_POST['rsvp_status']);
                $plus_one = isset($_POST['plus_one']) ? 1 : 0;
                $special_requests = sanitizeInput($_POST['special_requests']);
                
                $stmt = $conn->prepare("INSERT INTO registry_guests (guest_name, email, phone, relationship, rsvp_status, plus_one, special_requests) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssis", $guest_name, $email, $phone, $relationship, $rsvp_status, $plus_one, $special_requests);
                if($stmt->execute()) {
                    $success_message = "Guest added!";
                }
                $stmt->close();
                break;
                
            case 'record_purchase':
                $item_id = $_POST['item_id'];
                $guest_id = $_POST['guest_id'] ?: null;
                $quantity_purchased = $_POST['quantity_purchased'] ?: 1;
                $purchase_date = $_POST['purchase_date'];
                $gift_message = sanitizeInput($_POST['gift_message']);
                $gift_wrap = isset($_POST['gift_wrap']) ? 1 : 0;
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Record purchase
                    $stmt = $conn->prepare("INSERT INTO registry_purchases (item_id, guest_id, quantity_purchased, purchase_date, gift_message, gift_wrap) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiissi", $item_id, $guest_id, $quantity_purchased, $purchase_date, $gift_message, $gift_wrap);
                    $stmt->execute();
                    
                    // Update item status
                    $stmt2 = $conn->prepare("UPDATE registry_items SET quantity_reserved = quantity_reserved + ? WHERE id = ?");
                    $stmt2->bind_param("ii", $quantity_purchased, $item_id);
                    $stmt2->execute();
                    
                    // Check if item is now purchased
                    $stmt3 = $conn->prepare("UPDATE registry_items SET status = CASE WHEN quantity_reserved >= quantity_needed THEN 'purchased' ELSE 'reserved' END WHERE id = ?");
                    $stmt3->bind_param("i", $item_id);
                    $stmt3->execute();
                    
                    $conn->commit();
                    $success_message = "Purchase recorded! Thank you!";
                } catch (Exception $e) {
                    $conn->rollback();
                    $success_message = "Error recording purchase: " . $e->getMessage();
                }
                break;
                
            case 'update_wedding_info':
                $couple_name = sanitizeInput($_POST['couple_name']);
                $wedding_date = $_POST['wedding_date'];
                $venue_name = sanitizeInput($_POST['venue_name']);
                $venue_address = sanitizeInput($_POST['venue_address']);
                $wedding_theme = sanitizeInput($_POST['wedding_theme']);
                $registry_message = sanitizeInput($_POST['registry_message']);
                
                $stmt = $conn->prepare("UPDATE registry_wedding_info SET couple_name=?, wedding_date=?, venue_name=?, venue_address=?, wedding_theme=?, registry_message=? WHERE id=1");
                $stmt->bind_param("ssssss", $couple_name, $wedding_date, $venue_name, $venue_address, $wedding_theme, $registry_message);
                if($stmt->execute()) {
                    $success_message = "Wedding information updated!";
                    $wedding_info = $conn->query("SELECT * FROM registry_wedding_info LIMIT 1")->fetch_assoc();
                }
                $stmt->close();
                break;
                
            case 'mark_thank_you':
                $id = $_POST['id'];
                $stmt = $conn->prepare("UPDATE registry_purchases SET thank_you_sent = NOT thank_you_sent WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                break;
        }
    }
}

// Fetch data
$registry_items = $conn->query("SELECT *, 
    (quantity_needed - quantity_reserved) as quantity_remaining,
    (quantity_reserved / quantity_needed * 100) as progress_percent
    FROM registry_items ORDER BY priority, item_name");

$guests = $conn->query("SELECT * FROM registry_guests ORDER BY guest_name");
$purchases = $conn->query("SELECT p.*, i.item_name, g.guest_name 
    FROM registry_purchases p 
    LEFT JOIN registry_items i ON p.item_id = i.id 
    LEFT JOIN registry_guests g ON p.guest_id = g.id 
    ORDER BY p.purchase_date DESC");

// Statistics
$stats = $conn->query("SELECT 
    (SELECT COUNT(*) FROM registry_items) as total_items,
    (SELECT COUNT(*) FROM registry_items WHERE status = 'available') as available_items,
    (SELECT COUNT(*) FROM registry_items WHERE status = 'purchased') as purchased_items,
    (SELECT COALESCE(SUM(price * quantity_reserved), 0) FROM registry_items) as total_value,
    (SELECT COUNT(*) FROM registry_guests WHERE rsvp_status = 'attending') as attending_guests,
    (SELECT COUNT(*) FROM registry_guests) as total_guests")->fetch_assoc();

// Calculate days until wedding
$days_until_wedding = 0;
if ($wedding_info['wedding_date']) {
    $wedding_date = new DateTime($wedding_info['wedding_date']);
    $today = new DateTime();
    $days_until_wedding = $today->diff($wedding_date)->days;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Registry Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #9d7c5e;
            --primary-light: #d4b896;
            --secondary: #d8a48f;
            --accent: #c6a47e;
            --success: #7a9e7e;
            --warning: #e6a157;
            --danger: #d87c6f;
            --info: #6b8d9e;
            --light: #f9f5f0;
            --dark: #4a3c2a;
            --gray: #8a8a8a;
            --light-gray: #e9e9e9;
            --border-radius: 12px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f9f5f0 0%, #f0e6d6 100%);
            color: var(--dark);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .wedding-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .wedding-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary), var(--warning));
        }

        .couple-name {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 0.5rem;
            letter-spacing: 2px;
        }

        .wedding-date {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .countdown {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 1.5rem 0;
        }

        .countdown-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            min-width: 80px;
            backdrop-filter: blur(10px);
        }

        .countdown-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }

        .countdown-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Navigation */
        .registry-nav {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background: var(--light);
            color: var(--dark);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn.active {
            background: var(--primary);
            color: white;
        }

        .nav-btn:hover:not(.active) {
            background: var(--light-gray);
        }

        /* Stats Bar */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--primary);
        }

        .stat-info h3 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Registry Grid */
        .registry-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 2rem;
        }

        .item-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
        }

        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .item-card.reserved {
            opacity: 0.8;
        }

        .item-card.purchased {
            opacity: 0.6;
        }

        .item-status {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            z-index: 2;
        }

        .status-available {
            background: var(--success);
            color: white;
        }

        .status-reserved {
            background: var(--warning);
            color: white;
        }

        .status-purchased {
            background: var(--gray);
            color: white;
        }

        .item-image {
            height: 200px;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-image .placeholder {
            font-size: 3rem;
            color: var(--gray);
        }

        .item-content {
            padding: 1.5rem;
        }

        .item-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .item-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .item-category {
            display: inline-block;
            padding: 3px 10px;
            background: var(--light-gray);
            border-radius: 12px;
            font-size: 0.8rem;
            margin-bottom: 10px;
            color: var(--gray);
        }

        .item-description {
            color: var(--gray);
            margin-bottom: 15px;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .progress-container {
            margin: 15px 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .progress-bar {
            height: 8px;
            background: var(--light-gray);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success), #5a8a5e);
            border-radius: 4px;
        }

        .item-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--dark);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        /* Tables */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: var(--light-gray);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        /* Forms */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(157, 124, 94, 0.1);
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 1rem;
        }

        .form-row .form-group {
            flex: 1;
        }

        /* Guest View */
        .guest-view {
            text-align: center;
            padding: 3rem 0;
        }

        .guest-welcome {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .guest-message {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            color: var(--gray);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .registry-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .countdown {
                flex-wrap: wrap;
            }
            
            .nav-btn {
                flex: 1;
                min-width: 120px;
                justify-content: center;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            background: var(--success);
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10000;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <?php if ($success_message): ?>
    <div class="notification" id="successNotification">
        <i class="fas fa-check-circle"></i>
        <?php echo $success_message; ?>
    </div>
    <script>
        setTimeout(() => {
            document.getElementById('successNotification').remove();
        }, 3000);
    </script>
    <?php endif; ?>

    <div class="container">
        <!-- Wedding Header -->
        <header class="wedding-header fade-in">
            <h1 class="couple-name"><?php echo htmlspecialchars($wedding_info['couple_name']); ?></h1>
            <div class="wedding-date">
                <i class="far fa-calendar-alt"></i>
                <?php echo date('F j, Y', strtotime($wedding_info['wedding_date'])); ?>
                <?php if ($wedding_info['venue_name']): ?>
                • <?php echo htmlspecialchars($wedding_info['venue_name']); ?>
                <?php endif; ?>
            </div>
            
            <div class="countdown">
                <div class="countdown-item">
                    <span class="countdown-number"><?php echo $days_until_wedding; ?></span>
                    <span class="countdown-label">Days</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number"><?php echo $stats['attending_guests']; ?></span>
                    <span class="countdown-label">Attending</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number"><?php echo $stats['available_items']; ?></span>
                    <span class="countdown-label">Items Available</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number"><?php echo formatCurrency($stats['total_value']); ?></span>
                    <span class="countdown-label">Gift Value</span>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="registry-nav">
            <button class="nav-btn active" onclick="showSection('registry')">
                <i class="fas fa-gifts"></i> Registry
            </button>
            <button class="nav-btn" onclick="showSection('guests')">
                <i class="fas fa-users"></i> Guests (<?php echo $stats['total_guests']; ?>)
            </button>
            <button class="nav-btn" onclick="showSection('purchases')">
                <i class="fas fa-shopping-cart"></i> Purchases
            </button>
            <button class="nav-btn" onclick="showSection('public')">
                <i class="fas fa-eye"></i> Guest View
            </button>
            <button class="nav-btn" onclick="openModal('settingsModal')">
                <i class="fas fa-cog"></i> Settings
            </button>
        </nav>

        <!-- Registry Section -->
        <section id="registrySection" class="section active">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="color: var(--dark);"><i class="fas fa-gifts"></i> Wedding Registry</h2>
                <button class="btn btn-primary" onclick="openModal('itemModal')">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>

            <div class="stats-bar">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_items']; ?></h3>
                        <p>Total Items</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['available_items']; ?></h3>
                        <p>Available</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['purchased_items']; ?></h3>
                        <p>Purchased</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo formatCurrency($stats['total_value']); ?></h3>
                        <p>Total Value</p>
                    </div>
                </div>
            </div>

            <div class="registry-grid">
                <?php while($item = $registry_items->fetch_assoc()): ?>
                <?php 
                $progress = min(100, max(0, $item['progress_percent']));
                $remaining = $item['quantity_remaining'];
                ?>
                <div class="item-card <?php echo $item['status']; ?> fade-in">
                    <div class="item-status status-<?php echo $item['status']; ?>">
                        <?php echo ucfirst($item['status']); ?>
                    </div>
                    
                    <div class="item-image">
                        <?php if ($item['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                        <?php else: ?>
                        <div class="placeholder">
                            <i class="fas fa-gift"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="item-content">
                        <div class="item-category"><?php echo htmlspecialchars($item['category']); ?></div>
                        <h3 class="item-title"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                        <div class="item-price"><?php echo formatCurrency($item['price']); ?></div>
                        
                        <?php if ($item['description']): ?>
                        <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>Progress:</span>
                                <span><?php echo $item['quantity_reserved']; ?> of <?php echo $item['quantity_needed']; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        
                        <?php if ($item['store_name']): ?>
                        <p style="font-size: 0.9rem; color: var(--gray); margin-bottom: 10px;">
                            <i class="fas fa-store"></i> <?php echo htmlspecialchars($item['store_name']); ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="item-actions">
                            <?php if ($remaining > 0): ?>
                            <button class="btn btn-primary btn-sm" onclick="openPurchaseModal(<?php echo $item['id']; ?>)">
                                <i class="fas fa-cart-plus"></i> Reserve
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-sm" onclick="editItem(<?php echo $item['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm" onclick="deleteItem(<?php echo $item['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </section>

        <!-- Guests Section -->
        <section id="guestsSection" class="section" style="display: none;">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="color: var(--dark);"><i class="fas fa-users"></i> Guest List</h2>
                <button class="btn btn-primary" onclick="openModal('guestModal')">
                    <i class="fas fa-user-plus"></i> Add Guest
                </button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Guest Name</th>
                            <th>Contact</th>
                            <th>Relationship</th>
                            <th>RSVP Status</th>
                            <th>Plus One</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $guests->data_seek(0);
                        while($guest = $guests->fetch_assoc()): 
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($guest['guest_name']); ?></strong>
                                <?php if ($guest['special_requests']): ?>
                                <br><small style="color: var(--gray);"><?php echo htmlspecialchars($guest['special_requests']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($guest['email']): ?>
                                <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($guest['email']); ?></div>
                                <?php endif; ?>
                                <?php if ($guest['phone']): ?>
                                <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($guest['phone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($guest['relationship']); ?></td>
                            <td>
                                <span style="padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; background: 
                                    <?php echo $guest['rsvp_status'] == 'attending' ? 'var(--success)' : 
                                           ($guest['rsvp_status'] == 'declined' ? 'var(--danger)' : 'var(--warning)'); ?>; 
                                    color: white;">
                                    <?php echo ucfirst($guest['rsvp_status']); ?>
                                </span>
                            </td>
                            <td><?php echo $guest['plus_one'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <button class="btn btn-sm" onclick="editGuest(<?php echo $guest['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Purchases Section -->
        <section id="purchasesSection" class="section" style="display: none;">
            <div class="section-header" style="margin-bottom: 2rem;">
                <h2 style="color: var(--dark);"><i class="fas fa-shopping-cart"></i> Purchase History</h2>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Purchased By</th>
                            <th>Date</th>
                            <th>Quantity</th>
                            <th>Thank You</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $purchases->data_seek(0);
                        while($purchase = $purchases->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($purchase['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($purchase['guest_name'] ?? 'Anonymous'); ?></td>
                            <td><?php echo $purchase['purchase_date'] ? formatDate($purchase['purchase_date']) : '-'; ?></td>
                            <td><?php echo $purchase['quantity_purchased']; ?></td>
                            <td>
                                <button class="btn btn-sm <?php echo $purchase['thank_you_sent'] ? 'btn-success' : 'btn-warning'; ?>" 
                                        onclick="toggleThankYou(<?php echo $purchase['id']; ?>)">
                                    <i class="fas fa-<?php echo $purchase['thank_you_sent'] ? 'check' : 'envelope'; ?>"></i>
                                    <?php echo $purchase['thank_you_sent'] ? 'Sent' : 'Pending'; ?>
                                </button>
                            </td>
                            <td>
                                <?php if ($purchase['gift_message']): ?>
                                <button class="btn btn-sm" onclick="showMessage('<?php echo addslashes($purchase['gift_message']); ?>')">
                                    <i class="fas fa-message"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Public Guest View -->
        <section id="publicSection" class="section" style="display: none;">
            <div class="guest-view">
                <div class="guest-welcome fade-in">
                    <h2 style="color: var(--primary); margin-bottom: 1.5rem;"><?php echo htmlspecialchars($wedding_info['couple_name']); ?>'s Wedding Registry</h2>
                    
                    <?php if ($wedding_info['registry_message']): ?>
                    <div class="guest-message">
                        <?php echo nl2br(htmlspecialchars($wedding_info['registry_message'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin: 2rem 0; padding: 1.5rem; background: var(--light); border-radius: var(--border-radius);">
                        <h3 style="color: var(--dark); margin-bottom: 1rem;">Registry Items</h3>
                        <div class="registry-grid">
                            <?php 
                            $public_items = $conn->query("SELECT *, (quantity_needed - quantity_reserved) as quantity_remaining FROM registry_items WHERE status != 'purchased' ORDER BY priority");
                            while($item = $public_items->fetch_assoc()): 
                            ?>
                            <?php if ($item['quantity_remaining'] > 0): ?>
                            <div class="item-card fade-in">
                                <div class="item-content">
                                    <div class="item-category"><?php echo htmlspecialchars($item['category']); ?></div>
                                    <h3 class="item-title"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                                    <div class="item-price"><?php echo formatCurrency($item['price']); ?></div>
                                    
                                    <?php if ($item['description']): ?>
                                    <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div style="margin: 15px 0; font-size: 0.9rem; color: var(--gray);">
                                        <i class="fas fa-box"></i> <?php echo $item['quantity_remaining']; ?> remaining
                                    </div>
                                    
                                    <?php if ($item['store_url']): ?>
                                    <a href="<?php echo htmlspecialchars($item['store_url']); ?>" 
                                       target="_blank" 
                                       class="btn btn-primary" 
                                       style="width: 100%; justify-content: center;">
                                        <i class="fas fa-external-link-alt"></i> View at Store
                                    </a>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-success" 
                                            style="width: 100%; margin-top: 10px; justify-content: center;"
                                            onclick="openPublicPurchaseModal(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-gift"></i> Reserve This Item
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Add Item Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <h2 style="color: var(--dark); margin-bottom: 1.5rem;"><i class="fas fa-gift"></i> Add Registry Item</h2>
            <form method="POST" id="itemForm">
                <input type="hidden" name="action" value="add_item">
                <input type="hidden" name="id" id="itemId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Item Name *</label>
                        <input type="text" name="item_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control">
                            <option value="Kitchen">Kitchen</option>
                            <option value="Home">Home Decor</option>
                            <option value="Bedding">Bedding</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Entertainment">Entertainment</option>
                            <option value="Travel">Travel</option>
                            <option value="Cash Fund">Cash Fund</option>
                            <option value="Experience">Experience</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Price ($)</label>
                        <input type="number" step="0.01" name="price" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantity Needed</label>
                        <input type="number" name="quantity_needed" class="form-control" value="1" min="1">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Store Name</label>
                        <input type="text" name="store_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Store URL</label>
                        <input type="url" name="store_url" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Image URL</label>
                    <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-control">
                        <option value="high">High Priority</option>
                        <option value="medium" selected>Medium Priority</option>
                        <option value="low">Low Priority</option>
                    </select>
                </div>
                
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="button" class="btn" onclick="closeModal('itemModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Purchase Modal (Admin) -->
    <div id="purchaseModal" class="modal">
        <div class="modal-content">
            <h2 style="color: var(--dark); margin-bottom: 1.5rem;"><i class="fas fa-cart-plus"></i> Record Purchase</h2>
            <form method="POST" id="purchaseForm">
                <input type="hidden" name="action" value="record_purchase">
                <input type="hidden" name="item_id" id="purchaseItemId">
                
                <div class="form-group">
                    <label class="form-label">Item</label>
                    <input type="text" id="itemNameDisplay" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Purchased By</label>
                    <select name="guest_id" class="form-control">
                        <option value="">Anonymous</option>
                        <?php 
                        $guests->data_seek(0);
                        while($guest = $guests->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $guest['id']; ?>"><?php echo htmlspecialchars($guest['guest_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity_purchased" class="form-control" value="1" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Gift Message (Optional)</label>
                    <textarea name="gift_message" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="gift_wrap" value="1">
                        Gift Wrap Requested
                    </label>
                </div>
                
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="button" class="btn" onclick="closeModal('purchaseModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Record Purchase</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Public Purchase Modal -->
    <div id="publicPurchaseModal" class="modal">
        <div class="modal-content">
            <h2 style="color: var(--dark); margin-bottom: 1.5rem;"><i class="fas fa-gift"></i> Reserve This Gift</h2>
            <form method="POST" id="publicPurchaseForm">
                <input type="hidden" name="action" value="record_purchase">
                <input type="hidden" name="item_id" id="publicItemId">
                
                <div class="form-group">
                    <label class="form-label">Item</label>
                    <input type="text" id="publicItemName" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Your Name *</label>
                    <input type="text" name="guest_name" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relationship to Couple</label>
                        <select name="relationship" class="form-control">
                            <option value="Friend">Friend</option>
                            <option value="Family">Family</option>
                            <option value="Colleague">Colleague</option>
                            <option value="Relative">Relative</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Gift Message (Optional)</label>
                    <textarea name="gift_message" class="form-control" rows="3" placeholder="Wishing you a lifetime of happiness!"></textarea>
                </div>
                
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="button" class="btn" onclick="closeModal('publicPurchaseModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Reserve Gift</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settingsModal" class="modal">
        <div class="modal-content">
            <h2 style="color: var(--dark); margin-bottom: 1.5rem;"><i class="fas fa-cog"></i> Wedding Settings</h2>
            <form method="POST" id="settingsForm">
                <input type="hidden" name="action" value="update_wedding_info">
                
                <div class="form-group">
                    <label class="form-label">Couple's Names *</label>
                    <input type="text" name="couple_name" class="form-control" value="<?php echo htmlspecialchars($wedding_info['couple_name']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Wedding Date *</label>
                        <input type="date" name="wedding_date" class="form-control" value="<?php echo $wedding_info['wedding_date']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Wedding Theme</label>
                        <input type="text" name="wedding_theme" class="form-control" value="<?php echo htmlspecialchars($wedding_info['wedding_theme'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Venue Name</label>
                    <input type="text" name="venue_name" class="form-control" value="<?php echo htmlspecialchars($wedding_info['venue_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Venue Address</label>
                    <textarea name="venue_address" class="form-control" rows="2"><?php echo htmlspecialchars($wedding_info['venue_address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Registry Welcome Message</label>
                    <textarea name="registry_message" class="form-control" rows="4" placeholder="Thank you for visiting our registry! We're excited to start our life together and appreciate your support..."><?php echo htmlspecialchars($wedding_info['registry_message'] ?? ''); ?></textarea>
                </div>
                
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="button" class="btn" onclick="closeModal('settingsModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Navigation
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Update active nav button
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected section and activate button
            document.getElementById(sectionId + 'Section').style.display = 'block';
            event.currentTarget.classList.add('active');
        }

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Purchase functions
        function openPurchaseModal(itemId) {
            // Fetch item details
            fetch(`get_item.php?id=${itemId}`)
                .then(response => response.json())
                .then(item => {
                    document.getElementById('purchaseItemId').value = itemId;
                    document.getElementById('itemNameDisplay').value = item.item_name;
                    openModal('purchaseModal');
                })
                .catch(error => {
                    alert('Error loading item details');
                });
        }

        function openPublicPurchaseModal(itemId) {
            // Fetch item details
            fetch(`get_item.php?id=${itemId}`)
                .then(response => response.json())
                .then(item => {
                    document.getElementById('publicItemId').value = itemId;
                    document.getElementById('publicItemName').value = item.item_name;
                    openModal('publicPurchaseModal');
                });
        }

        // Form handling
        document.getElementById('publicPurchaseForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const guestName = formData.get('guest_name');
            
            // First, create guest record
            const guestData = new FormData();
            guestData.append('action', 'add_guest');
            guestData.append('guest_name', guestName);
            guestData.append('email', formData.get('email'));
            guestData.append('relationship', formData.get('relationship'));
            guestData.append('rsvp_status', 'attending');
            
            try {
                const guestResponse = await fetch(window.location.href, {
                    method: 'POST',
                    body: guestData
                });
                
                if (guestResponse.ok) {
                    // Then record purchase
                    const purchaseData = new FormData(this);
                    purchaseData.append('guest_id', 'new'); // Will be handled by server
                    
                    const purchaseResponse = await fetch(window.location.href, {
                        method: 'POST',
                        body: purchaseData
                    });
                    
                    if (purchaseResponse.ok) {
                        alert('Thank you for your gift! Your reservation has been recorded.');
                        closeModal('publicPurchaseModal');
                        window.location.reload();
                    }
                }
            } catch (error) {
                alert('Error processing your reservation. Please try again.');
            }
        });

        // Edit item
        function editItem(itemId) {
            // Fetch item data and populate form
            fetch(`get_item.php?id=${itemId}`)
                .then(response => response.json())
                .then(item => {
                    document.getElementById('itemId').value = item.id;
                    document.querySelector('#itemForm input[name="item_name"]').value = item.item_name;
                    document.querySelector('#itemForm select[name="category"]').value = item.category;
                    document.querySelector('#itemForm input[name="price"]').value = item.price;
                    document.querySelector('#itemForm input[name="quantity_needed"]').value = item.quantity_needed;
                    document.querySelector('#itemForm textarea[name="description"]').value = item.description || '';
                    document.querySelector('#itemForm input[name="store_name"]').value = item.store_name || '';
                    document.querySelector('#itemForm input[name="store_url"]').value = item.store_url || '';
                    document.querySelector('#itemForm input[name="image_url"]').value = item.image_url || '';
                    document.querySelector('#itemForm select[name="priority"]').value = item.priority;
                    
                    // Change form action to update
                    document.querySelector('#itemForm input[name="action"]').value = 'update_item';
                    document.querySelector('#itemModal h2').innerHTML = '<i class="fas fa-edit"></i> Edit Item';
                    
                    openModal('itemModal');
                });
        }

        // Delete item
        function deleteItem(itemId) {
            if (confirm('Are you sure you want to remove this item from the registry?')) {
                const formData = new FormData();
                formData.append('action', 'delete_item');
                formData.append('id', itemId);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                }).then(() => {
                    window.location.reload();
                });
            }
        }

        // Toggle thank you
        function toggleThankYou(purchaseId) {
            const formData = new FormData();
            formData.append('action', 'mark_thank_you');
            formData.append('id', purchaseId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(() => {
                window.location.reload();
            });
        }

        // Show gift message
        function showMessage(message) {
            alert('Gift Message:\n\n' + message);
        }

        // Auto-close modals on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id);
            }
        }

        // Countdown timer update
        function updateCountdown() {
            const countdownElement = document.querySelector('.countdown-number');
            if (countdownElement) {
                // Update days dynamically (for demo)
                const currentDays = parseInt(countdownElement.textContent);
                countdownElement.textContent = Math.max(0, currentDays - 1);
            }
        }
        // Update countdown every day (for demo, update every minute)
        setInterval(updateCountdown, 60000);
    </script>
</body>
</html>
<?php
$conn->close();
?>