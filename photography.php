<?php
// ============================================
// DATABASE CONNECTION & FUNCTIONS
// ============================================
$host = "localhost";
$username = "root";
$password = "";
$database = "photo_app";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create table if not exists
$createTableSQL = "CREATE TABLE IF NOT EXISTS `photo_selections` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `photo_id` INT(11) NOT NULL,
    `photo_name` VARCHAR(255) NOT NULL,
    `user_ip` VARCHAR(45) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

mysqli_query($conn, $createTableSQL);

// Function to handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    if ($action == 'save_selection') {
        $photo_id = $_GET['photo_id'] ?? 0;
        $photo_name = $_GET['photo_name'] ?? '';
        
        // Check if already selected by this IP
        $checkSQL = "SELECT id FROM photo_selections WHERE photo_id = '$photo_id' AND user_ip = '$user_ip'";
        $checkResult = mysqli_query($conn, $checkSQL);
        
        if (mysqli_num_rows($checkResult) > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You have already selected this photo!'
            ]);
        } else {
            $sql = "INSERT INTO `photo_selections` (`photo_id`, `photo_name`, `user_ip`) 
                    VALUES ('$photo_id', '$photo_name', '$user_ip')";
            
            if (mysqli_query($conn, $sql)) {
                $last_id = mysqli_insert_id($conn);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Photo saved successfully!',
                    'selection_id' => $last_id,
                    'photo_name' => $photo_name
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Database error: ' . mysqli_error($conn)
                ]);
            }
        }
    } 
    elseif ($action == 'remove_selection') {
        $photo_id = $_GET['photo_id'] ?? 0;
        
        $sql = "DELETE FROM photo_selections WHERE photo_id = '$photo_id' AND user_ip = '$user_ip'";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Photo removed successfully!',
                'photo_id' => $photo_id
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . mysqli_error($conn)
            ]);
        }
    }
    elseif ($action == 'get_stats') {
        $user_ip = $_SERVER['REMOTE_ADDR'];
        
        // Total selections in database
        $totalSQL = "SELECT COUNT(*) as total FROM photo_selections";
        $totalResult = mysqli_query($conn, $totalSQL);
        $totalRow = mysqli_fetch_assoc($totalResult);
        
        // User's selections
        $userSQL = "SELECT COUNT(*) as user_total FROM photo_selections WHERE user_ip = '$user_ip'";
        $userResult = mysqli_query($conn, $userSQL);
        $userRow = mysqli_fetch_assoc($userResult);
        
        // User's selected photo IDs
        $userPhotosSQL = "SELECT photo_id FROM photo_selections WHERE user_ip = '$user_ip'";
        $userPhotosResult = mysqli_query($conn, $userPhotosSQL);
        $user_photos = [];
        while ($row = mysqli_fetch_assoc($userPhotosResult)) {
            $user_photos[] = $row['photo_id'];
        }
        
        echo json_encode([
            'total' => $totalRow['total'],
            'user_total' => $userRow['user_total'],
            'user_photos' => $user_photos
        ]);
    }
    elseif ($action == 'redirect_to_contact') {
        // Check if user has selected any photos
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $checkSQL = "SELECT COUNT(*) as count FROM photo_selections WHERE user_ip = '$user_ip'";
        $checkResult = mysqli_query($conn, $checkSQL);
        $checkRow = mysqli_fetch_assoc($checkResult);
        
        if ($checkRow['count'] > 0) {
            // Get user's selected photos
            $photosSQL = "SELECT * FROM photo_selections WHERE user_ip = '$user_ip'";
            $photosResult = mysqli_query($conn, $photosSQL);
            $selected_photos = [];
            while ($row = mysqli_fetch_assoc($photosResult)) {
                $selected_photos[] = $row;
            }
            
            // Store in session for contact page
            session_start();
            $_SESSION['selected_photos'] = $selected_photos;
            $_SESSION['user_ip'] = $user_ip;
            
            echo json_encode([
                'status' => 'success',
                'redirect' => true,
                'message' => 'Redirecting to contact form...',
                'count' => $checkRow['count']
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'redirect' => false,
                'message' => 'Please select at least one photo before proceeding!'
            ]);
        }
    }
    
    mysqli_close($conn);
    exit();
}

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    session_start();
    
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $user_ip = $_SESSION['user_ip'] ?? $_SERVER['REMOTE_ADDR'];
    
    // Get selected photos for this user
    $photosSQL = "SELECT * FROM photo_selections WHERE user_ip = '$user_ip'";
    $photosResult = mysqli_query($conn, $photosSQL);
    $selected_photos = [];
    while ($row = mysqli_fetch_assoc($photosResult)) {
        $selected_photos[] = $row;
    }
    
    // Here you would typically save the contact form data to another table
    // For demonstration, we'll just show a success message
    
    $contact_success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photography Selection Gallery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 3rem;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #3498db, #8e44ad);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: space-between;
            background: white;
            padding: 15px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        /* Gallery Grid */
        .gallery-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .gallery-title {
            text-align: center;
            margin-bottom: 40px;
            color: #2c3e50;
            font-size: 2.2rem;
            position: relative;
            display: inline-block;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .gallery-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 25%;
            width: 50%;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #8e44ad);
            border-radius: 2px;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        /* Photo Card */
        .photo-card {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .photo-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .photo-card.selected {
            box-shadow: 0 0 0 4px #2ecc71, 0 15px 30px rgba(46, 204, 113, 0.3);
        }
        
        .photo-card.selected::before {
            content: '✓ SELECTED';
            position: absolute;
            top: 15px;
            right: 15px;
            background: #2ecc71;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 2;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .photo-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .photo-card:hover .photo-img {
            transform: scale(1.05);
        }
        
        .photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .photo-card:hover .photo-overlay {
            opacity: 1;
        }
        
        .photo-title {
            color: white;
            font-size: 1.3rem;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .photo-id {
            color: #ddd;
            font-size: 0.9rem;
        }
        
        .select-btn, .unselect-btn {
            background: rgba(52, 152, 219, 0.9);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.3s;
            width: 100%;
        }
        
        .select-btn:hover {
            background: rgba(41, 128, 185, 0.9);
        }
        
        .unselect-btn {
            background: rgba(231, 76, 60, 0.9);
        }
        
        .unselect-btn:hover {
            background: rgba(192, 57, 43, 0.9);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .action-btn {
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .proceed-btn {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }
        
        .proceed-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(46, 204, 113, 0.3);
        }
        
        .reset-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .reset-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
        }
        
        /* Contact Form Section */
        .contact-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-top: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            display: <?php echo isset($contact_success) ? 'block' : 'none'; ?>;
        }
        
        .contact-title {
            text-align: center;
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .selected-photos-review {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .selected-photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .selected-photo-item {
            text-align: center;
        }
        
        .selected-photo-img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        /* Success Message */
        .success-message {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-top: 30px;
        }
        
        /* Notification */
        .notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 20px 30px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            z-index: 1000;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 15px;
            max-width: 400px;
            transform: translateX(150%);
            transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.success {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            border-left: 5px solid #1e8449;
        }
        
        .notification.error {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border-left: 5px solid #922b21;
        }
        
        .notification-icon {
            font-size: 1.8rem;
        }
        
        /* Floating Counter */
        .floating-counter {
            position: fixed;
            top: 30px;
            right: 30px;
            background: linear-gradient(135deg, #8e44ad, #9b59b6);
            color: white;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(142, 68, 173, 0.4);
            z-index: 999;
        }
        
        .counter-number {
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        .counter-label {
            font-size: 0.7rem;
            opacity: 0.9;
        }
        
        /* Loading Spinner */
        .spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2000;
        }
        
        .spinner.show {
            display: block;
        }
        
        .spinner-circle {
            width: 60px;
            height: 60px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .header h1 {
                font-size: 2.2rem;
            }
            
            .stats-bar {
                flex-wrap: wrap;
                gap: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Spinner -->
    <div class="spinner" id="loadingSpinner">
        <div class="spinner-circle"></div>
    </div>
    
    <!-- Floating Selection Counter -->
    <div class="floating-counter">
        <div class="counter-number" id="selectionCounter">0</div>
        <div class="counter-label">SELECTED</div>
    </div>
    
    <!-- Notification -->
    <div class="notification" id="notification">
        <div class="notification-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title" id="notificationTitle">Success!</div>
            <div class="notification-message" id="notificationMessage">Photo saved to database</div>
        </div>
    </div>
    
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-camera"></i> Photography Selection Gallery</h1>
            <p>Select your favorite photos and proceed to contact us for booking</p>
            <div style="margin-top: 20px; color: #7f8c8d;">
                <i class="fas fa-info-circle"></i> Your IP: <?php echo $_SERVER['REMOTE_ADDR']; ?>
            </div>
        </div>
        
        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number" id="totalPhotos">12</div>
                <div class="stat-label">Total Photos</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="selectedCount">0</div>
                <div class="stat-label">You Selected</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="databaseCount">0</div>
                <div class="stat-label">In Database</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="yourIpShort">
                    <?php 
                    $ip = $_SERVER['REMOTE_ADDR'];
                    echo substr($ip, 0, 3) . '***';
                    ?>
                </div>
                <div class="stat-label">Your IP</div>
            </div>
        </div>
        
        <!-- Photo Gallery -->
        <div class="gallery-container">
            <h2 class="gallery-title">Select Your Favorite Photos</h2>
            
            <div class="gallery-grid" id="photoGallery">
                <!-- Photos will be loaded here -->
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="action-btn proceed-btn" onclick="proceedToContact()">
                    <i class="fas fa-arrow-right"></i> Proceed to Contact (Selected: <span id="proceedCount">0</span>)
                </button>
                <button class="action-btn reset-btn" onclick="resetAllSelections()">
                    <i class="fas fa-redo"></i> Reset All Selections
                </button>
            </div>
        </div>
        
        <!-- Contact Form Section (Initially Hidden) -->
        <div class="contact-section" id="contactSection">
            <?php if (isset($contact_success)): ?>
                <div class="success-message">
                    <h2><i class="fas fa-check-circle"></i> Thank You!</h2>
                    <p style="margin-top: 15px; font-size: 1.1rem;">
                        Your contact form has been submitted successfully with <?php echo count($selected_photos); ?> selected photos.
                    </p>
                    <p style="margin-top: 10px;">We will contact you soon!</p>
                </div>
            <?php else: ?>
                <h2 class="contact-title">Contact Information</h2>
                
                <!-- Selected Photos Review -->
                <div class="selected-photos-review">
                    <h3><i class="fas fa-images"></i> Your Selected Photos (<span id="reviewCount">0</span>)</h3>
                    <div class="selected-photos-grid" id="selectedPhotosReview">
                        <!-- Selected photos will appear here -->
                    </div>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="contact_submit" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="Enter your full name">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Message *</label>
                        <textarea name="message" class="form-control" rows="5" required placeholder="Tell us about your photography needs..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="action-btn proceed-btn" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> Submit Contact Form
                        </button>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="#" onclick="showGallery()" style="color: #3498db; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Back to Gallery
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Instructions -->
        <div style="text-align: center; margin-top: 50px; color: #7f8c8d; padding: 20px;">
            <h3><i class="fas fa-mouse-pointer"></i> How it works:</h3>
            <p>1. Click any photo to select/deselect it<br>
            2. Click "Proceed to Contact" when ready<br>
            3. Fill out the contact form with your selected photos<br>
            4. Submit to complete your photography request</p>
        </div>
    </div>
    
    <script>
        // Sample photo data
        const photos = [
            {id: 1, name: "Sunset Beach", category: "Nature", url: "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800&auto=format&fit=crop"},
            {id: 2, name: "Mountain Peak", category: "Adventure", url: "https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=800&auto=format&fit=crop"},
            {id: 3, name: "Forest Path", category: "Nature", url: "https://images.unsplash.com/photo-1448375240586-882707db888b?w=800&auto=format&fit=crop"},
            {id: 4, name: "City Lights", category: "Urban", url: "https://images.unsplash.com/photo-1477959858617-67f85cf4f1df?w=800&auto=format&fit=crop"},
            {id: 5, name: "Desert Dunes", category: "Landscape", url: "https://images.unsplash.com/photo-1505118380757-91f5f5632de0?w=800&auto=format&fit=crop"},
            {id: 6, name: "Northern Lights", category: "Nature", url: "https://images.unsplash.com/photo-1502134249126-9f3755a50d78?w=800&auto=format&fit=crop"},
            {id: 7, name: "Waterfall", category: "Nature", url: "https://images.unsplash.com/photo-1512273222628-4daea6e55abb?w=800&auto=format&fit=crop"},
            {id: 8, name: "Winter Wonderland", category: "Seasonal", url: "https://images.unsplash.com/photo-1476820865390-c52aeebb9891?w=800&auto=format&fit=crop"},
            {id: 9, name: "Tropical Beach", category: "Travel", url: "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800&auto=format&fit=crop"},
            {id: 10, name: "Autumn Leaves", category: "Seasonal", url: "https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&auto=format&fit=crop"},
            {id: 11, name: "Star Trails", category: "Astro", url: "https://images.unsplash.com/photo-1444703686981-a3abbc4d4fe3?w=800&auto=format&fit=crop"},
            {id: 12, name: "Wildlife", category: "Animals", url: "https://images.unsplash.com/photo-1519068737630-e5db30e12e42?w=800&auto=format&fit=crop"}
        ];
        
        // Track selected photos
        let selectedPhotos = [];
        let selectionCount = 0;
        
        // DOM Elements
        const gallery = document.getElementById('photoGallery');
        const notification = document.getElementById('notification');
        const counter = document.getElementById('selectionCounter');
        const selectedCount = document.getElementById('selectedCount');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const contactSection = document.getElementById('contactSection');
        const proceedCount = document.getElementById('proceedCount');
        const reviewCount = document.getElementById('reviewCount');
        const selectedPhotosReview = document.getElementById('selectedPhotosReview');
        
        // Initialize
        loadUserSelections();
        
        // Load user's selections from database
        function loadUserSelections() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '<?php echo $_SERVER["PHP_SELF"]; ?>?action=get_stats', true);
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const stats = JSON.parse(xhr.responseText);
                        document.getElementById('databaseCount').textContent = stats.total || 0;
                        selectedPhotos = stats.user_photos || [];
                        selectionCount = stats.user_total || 0;
                        selectedCount.textContent = selectionCount;
                        counter.textContent = selectionCount;
                        proceedCount.textContent = selectionCount;
                        
                        renderGallery();
                        updateSelectedPhotosReview();
                    } catch (e) {
                        console.log('Error parsing stats:', e);
                    }
                }
            };
            
            xhr.send();
        }
        
        // Render photo gallery
        function renderGallery() {
            gallery.innerHTML = '';
            photos.forEach(photo => {
                const isSelected = selectedPhotos.includes(photo.id.toString());
                
                const photoCard = document.createElement('div');
                photoCard.className = `photo-card ${isSelected ? 'selected' : ''}`;
                photoCard.setAttribute('data-id', photo.id);
                
                photoCard.innerHTML = `
                    <img src="${photo.url}" alt="${photo.name}" class="photo-img">
                    <div class="photo-overlay">
                        <div class="photo-title">${photo.name}</div>
                        <div class="photo-id">ID: ${photo.id} | ${photo.category}</div>
                        <button class="${isSelected ? 'unselect-btn' : 'select-btn'}" 
                                onclick="${isSelected ? 'unselectPhoto' : 'selectPhoto'}(${photo.id}, '${photo.name}')">
                            <i class="fas ${isSelected ? 'fa-times' : 'fa-heart'}"></i> 
                            ${isSelected ? 'REMOVE SELECTION' : 'CLICK TO SELECT'}
                        </button>
                    </div>
                `;
                
                gallery.appendChild(photoCard);
            });
        }
        
        // Select photo function
        function selectPhoto(photoId, photoName) {
            // Show loading
            loadingSpinner.classList.add('show');
            
            // Prepare data for AJAX
            const data = {
                action: 'save_selection',
                photo_id: photoId,
                photo_name: photoName.replace(/\s+/g, '_').toLowerCase() + '.jpg'
            };
            
            // AJAX Request
            const xhr = new XMLHttpRequest();
            const url = '<?php echo $_SERVER["PHP_SELF"]; ?>?' + new URLSearchParams(data).toString();
            
            xhr.open('GET', url, true);
            
            xhr.onload = function() {
                loadingSpinner.classList.remove('show');
                
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.status === 'success') {
                        // Add to selected photos
                        selectedPhotos.push(photoId.toString());
                        selectionCount++;
                        
                        // Update UI
                        updateCounters();
                        renderGallery();
                        updateSelectedPhotosReview();
                        
                        // Show success notification
                        showNotification(
                            `Photo selected successfully!`,
                            'success'
                        );
                        
                        // Update database count
                        loadDatabaseCount();
                    } else {
                        showNotification(response.message, 'error');
                    }
                } else {
                    showNotification('Server error. Please try again.', 'error');
                }
            };
            
            xhr.onerror = function() {
                loadingSpinner.classList.remove('show');
                showNotification('Network error. Please check your connection.', 'error');
            };
            
            xhr.send();
        }
        
        // Unselect photo function
        function unselectPhoto(photoId, photoName) {
            // Show loading
            loadingSpinner.classList.add('show');
            
            // Prepare data for AJAX
            const data = {
                action: 'remove_selection',
                photo_id: photoId
            };
            
            // AJAX Request
            const xhr = new XMLHttpRequest();
            const url = '<?php echo $_SERVER["PHP_SELF"]; ?>?' + new URLSearchParams(data).toString();
            
            xhr.open('GET', url, true);
            
            xhr.onload = function() {
                loadingSpinner.classList.remove('show');
                
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.status === 'success') {
                        // Remove from selected photos
                        selectedPhotos = selectedPhotos.filter(id => id !== photoId.toString());
                        selectionCount--;
                        
                        // Update UI
                        updateCounters();
                        renderGallery();
                        updateSelectedPhotosReview();
                        
                        // Show success notification
                        showNotification(
                            `Photo removed from selections!`,
                            'success'
                        );
                        
                        // Update database count
                        loadDatabaseCount();
                    } else {
                        showNotification(response.message, 'error');
                    }
                } else {
                    showNotification('Server error. Please try again.', 'error');
                }
            };
            
            xhr.onerror = function() {
                loadingSpinner.classList.remove('show');
                showNotification('Network error. Please check your connection.', 'error');
            };
            
            xhr.send();
        }
        
        // Proceed to contact form
        function proceedToContact() {
            if (selectedPhotos.length === 0) {
                showNotification('Please select at least one photo before proceeding!', 'error');
                return;
            }
            
            loadingSpinner.classList.add('show');
            
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '<?php echo $_SERVER["PHP_SELF"]; ?>?action=redirect_to_contact', true);
            
            xhr.onload = function() {
                loadingSpinner.classList.remove('show');
                
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.status === 'success' && response.redirect) {
                        // Show contact section
                        contactSection.style.display = 'block';
                        document.querySelector('.gallery-container').style.display = 'none';
                        document.querySelector('.stats-bar').style.display = 'none';
                        document.querySelector('.header').style.display = 'none';
                        
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } else {
                        showNotification(response.message, 'error');
                    }
                }
            };
            
            xhr.send();
        }
        
        // Reset all selections
        function resetAllSelections() {
            if (selectedPhotos.length === 0) {
                showNotification('You have no selections to reset!', 'error');
                return;
            }
            
            if (!confirm('Are you sure you want to reset all your selections?')) {
                return;
            }
            
            loadingSpinner.classList.add('show');
            
            // Remove each selection one by one
            const removePromises = selectedPhotos.map(photoId => {
                return new Promise((resolve) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('GET', `<?php echo $_SERVER["PHP_SELF"]; ?>?action=remove_selection&photo_id=${photoId}`, true);
                    xhr.onload = resolve;
                    xhr.send();
                });
            });
            
            Promise.all(removePromises).then(() => {
                loadingSpinner.classList.remove('show');
                selectedPhotos = [];
                selectionCount = 0;
                updateCounters();
                renderGallery();
                updateSelectedPhotosReview();
                showNotification('All selections have been reset!', 'success');
                loadDatabaseCount();
            });
        }
        
        // Show gallery again
        function showGallery() {
            contactSection.style.display = 'none';
            document.querySelector('.gallery-container').style.display = 'block';
            document.querySelector('.stats-bar').style.display = 'flex';
            document.querySelector('.header').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Update selected photos review
        function updateSelectedPhotosReview() {
            reviewCount.textContent = selectedPhotos.length;
            selectedPhotosReview.innerHTML = '';
            
            selectedPhotos.forEach(photoId => {
                const photo = photos.find(p => p.id == photoId);
                if (photo) {
                    const photoItem = document.createElement('div');
                    photoItem.className = 'selected-photo-item';
                    photoItem.innerHTML = `
                        <img src="${photo.url}" alt="${photo.name}" class="selected-photo-img">
                        <div style="margin-top: 5px; font-size: 0.8rem;">${photo.name}</div>
                    `;
                    selectedPhotosReview.appendChild(photoItem);
                }
            });
        }
        
        // Show notification
        function showNotification(message, type = 'success') {
            const title = type === 'success' ? 'Success!' : 'Error!';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            document.getElementById('notificationTitle').innerHTML = title;
            document.getElementById('notificationMessage').innerHTML = message;
            
            notification.className = `notification ${type}`;
            notification.querySelector('.notification-icon').innerHTML = `<i class="fas ${icon}"></i>`;
            
            notification.classList.add('show');
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                notification.classList.remove('show');
            }, 5000);
        }
        
        // Update counters
        function updateCounters() {
            counter.textContent = selectionCount;
            selectedCount.textContent = selectionCount;
            proceedCount.textContent = selectionCount;
            document.getElementById('totalPhotos').textContent = photos.length;
        }
        
        // Load database count via AJAX
        function loadDatabaseCount() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '<?php echo $_SERVER["PHP_SELF"]; ?>?action=get_stats', true);
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const stats = JSON.parse(xhr.responseText);
                        document.getElementById('databaseCount').textContent = stats.total || 0;
                    } catch (e) {
                        console.log('Error parsing stats:', e);
                    }
                }
            };
            
            xhr.send();
        }
        
        // Add click handler to entire photo card
        document.addEventListener('click', function(e) {
            const photoCard = e.target.closest('.photo-card');
            if (photoCard && !e.target.closest('.select-btn') && !e.target.closest('.unselect-btn')) {
                const photoId = parseInt(photoCard.getAttribute('data-id'));
                const photoName = photos.find(p => p.id === photoId)?.name || `Photo ${photoId}`;
                
                if (selectedPhotos.includes(photoId.toString())) {
                    unselectPhoto(photoId, photoName);
                } else {
                    selectPhoto(photoId, photoName);
                }
            }
        });
        
        // Auto-refresh database count every 30 seconds
        setInterval(loadDatabaseCount, 30000);
    </script>
</body>
</html>