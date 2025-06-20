<?php
session_start();
$inactivity_limit = 900; // 15 minutes

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactivity_limit) {
    session_unset();
    session_destroy();
    header("Location: register.php");
    exit();
}


// Update last activity time for session management
$_SESSION['last_activity'] = time();

// Redirect to login page if user is not logged in (first-time check or after logout)
if (!isset($_SESSION['username'])) {
    header('Location: register.php');
    exit();
}

// Get username for display, ensuring it's safe for HTML output
$display_username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';

$servername = 'localhost';
$db_username = 'root'; // Renamed to avoid conflict with session $display_username
$password = '';
$dbname = 'tampering';

// Create database connection
$conn = new mysqli($servername, $db_username, $password, $dbname);

// Check database connection
if ($conn->connect_error) {
    // Log detailed error for debugging, but show a generic message to the user
    error_log('Database Connection Failed: ' . $conn->connect_error);
    die('Failed to connect to the database. Please try again later.');
}

// Data fetching logic for map markers
$locations = [];
$sql = 'SELECT * FROM meter_data';
$result = $conn->query($sql);

if ($result !== FALSE) {
    while ($row1 = $result->fetch_assoc()) {
        // Sanitize data for HTML output
        $name = htmlspecialchars($row1['name']);
        $latitude = htmlspecialchars($row1['latitude']);
        $longitude = htmlspecialchars($row1['longitude']);
        $tampering_label = htmlspecialchars($row1['tampering_label']);
        $tampering_reason = htmlspecialchars($row1['tampering_reason'] ?? 'N/A');
        $status = htmlspecialchars($row1['status']);
        $sim_ip = htmlspecialchars($row1['SIM_IP']);

        // Determine marker icon based on status and tampering_label
        $marker_icon_url = 'WM.png'; // Default icon
        if ($status === 'Non Reporting') {
            $marker_icon_url = 'WM_red.png'; // Prioritize non-reporting status
        } elseif ($tampering_label == 1) {
            $marker_icon_url = 'WM.png'; // If tampered but reporting, use default icon
        }

        // Construct info window content
        $info_content = '<h4 style="color:blue">Meter: ' . $name . '</h4>' .
                        '<p><strong>Status:</strong> ' . $status . '</p>' .
                        '<p><strong>Location:</strong> ' . $latitude . ', ' . $longitude . '</p>' .
                        '<p><strong>SIM IP:</strong> ' . $sim_ip . '</p>' .
                        '<p><strong>Tampered:</strong> ' . ($tampering_label == 1 ? 'Yes' : 'No') . '</p>';

        if ($tampering_label == 1 && $tampering_reason !== 'N/A') {
            $info_content .= '<p><strong>Reason:</strong> ' . $tampering_reason . '</p>';
        }

        // Add alerts based on status/tampering
        if ($status === 'Non Reporting') {
            $info_content .= '<p style="color:red; font-weight:bold; margin-top:10px;">ALERT: This Meter is Not Reporting!</p>';
        } elseif ($tampering_label == 1) {
            $info_content .= '<p style="color:orange; font-weight:bold; margin-top:10px;">ALERT: This Meter is Tampered!</p>';
        }

        // Add to locations array for JavaScript (used by Google Maps)
        $locations[] = [$info_content, $latitude, $longitude, $marker_icon_url];
    }
    $result->free(); // Free result set
}

// Encode PHP array to JSON for JavaScript
$data = json_encode($locations);

// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMR SIM Map Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <style>
        /* General Body Styling */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8fff8;
            margin: 0;
            padding: 0;
        }

        /* Top Bar - contains profile, weather, time */
        .top-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 30px;
            margin-bottom: 0; /* Adjusted for tighter spacing with content below */
            background: #0a2239;
            color: #fff;
            padding: 12px 30px;
            font-size: 1.1em;
            border-radius: 0 0 10px 10px;
            position: relative;
        }
        .top-bar i { margin-right: 7px; }

        /* Profile Dropdown */
        .profile-dropdown {
            display: flex;
            align-items: center;
            position: relative;
            cursor: pointer;
            z-index: 1000;
            margin-right: auto;
            gap: 20px; /* Pushes weather and time to the right */
        }

        .profile-icon {
            font-size: 1.5em;
            color: #fff;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #0a2239;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            left: 0;
            border-radius: 8px;
            overflow: hidden;
            top: 45px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dropdown-content.show {
            display: block;
        }

        .dropdown-content p {
            color: #fff;
            padding: 12px 16px;
            margin: 0;
            font-size: 1em;
            white-space: nowrap;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dropdown-content button {
            color: #fff;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.2s;
        }

        .dropdown-content button:hover {
            background-color: #1a3a5a;
        }

        /* Outer container for TNEB Logo and Main Dashboard Tile */
        .outer-dashboard-container {
          width: 100%;
          max-width: 1200px;
          margin: 20px auto 0 auto; /* This will center the whole container */
          display: flex;
          align-items: center;
          justify-content: center;   /* <--- Center children horizontally */
          gap: 20px;
          padding: 10px 0;
        }

        /* Large TNEB Logo (outside the tile) */
        .tneb-logo-large {
            height: 130px; /* Large size */
            width: auto; /* Maintain aspect ratio */
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            background: #fff;
            padding: 8px 15px;
            object-fit: contain;
            transition: filter 0.3s, transform 0.3s;
        }
        .animated-logo:hover {
            filter: drop-shadow(0 0 25px #1e90ff) brightness(1.2);
            transform: scale(1.15) rotate(-10deg);
        }
        .animated-logo-click {
            animation: pulse 0.6s ease-out;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.25) rotate(10deg); box-shadow: 0 0 40px 10px #00bfff88; }
            100% { transform: scale(1); }
        }

        /* Main Dashboard Tile - Contains Report button, Developed By, AMR SIM track */
        .dashboard-main-tile {
            flex-grow: 1; /* Allows it to take up available space */
            display: flex;
            align-items: center;
            justify-content: space-between; /* Space out items inside */
            padding: 15px 30px; /* Padding for the tile */
            background: #ffffff; /* White background */
            border-radius: 20px; /* Rounded corners */
            box-shadow: 0 8px 32px rgba(30, 144, 255, 0.10); /* Soft shadow */
            gap: 15px; /* Gap between internal items */
        }

        /* Report Button */
        .report-btn {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 12px 28px;
            background: linear-gradient(90deg, #1e90ff 60%, #3498db 100%);
            color: #fff;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.08em;
            border: none;
            outline: none;
            cursor: pointer;
            box-shadow: 0 2px 8px #e0eaff;
            transition: background 0.2s, transform 0.2s;
            /* margin-left removed, now handled by gap in parent and flex-grow on text */
        }
        .report-btn:hover {
            background: linear-gradient(90deg, #3498db 60%, #1e90ff 100%);
            transform: translateY(-2px) scale(1.04);
        }

        /* Developed By Text */
        .developed-by-text {
            font-size: 1.1em;
            font-weight: 500;
            color: #0a2239;
            text-align: center; /* Default for mobile, will be centered in main tile on desktop */
            flex-grow: 1; /* Allows it to take available space in the middle */
        }

        /* AMR SIM track info display */
        .amr-info-display {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.22em;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #0a2239;
            white-space: nowrap; /* Prevents wrapping on desktop */
        }
        .amr-info-display i {
            font-size: 2.2em;
            color: #1e90ff;
        }

        /* Map Container */
        #map {
            width: 95vw; /* Match the main container breadth */
            max-width: 1200px; /* Match the main container max-width */
            height: 480px;
            margin: 32px auto 0 auto;
            border-radius: 18px;
            box-shadow: 0 4px 18px #1e90ff22;
        }

        /* Responsive styles */
        @media (min-width: 901px) { /* Desktop specific styles for fine-tuning */
            .developed-by-text {
                text-align: center; /* Center within the available space */
            }
        }

        @media (max-width: 900px) { /* Tablet and Mobile styles */
            .top-bar {
                flex-wrap: wrap;
                justify-content: space-between;
                gap: 10px;
                padding: 12px 15px;
            }
            .profile-dropdown {
                margin-right: 0;
                /* ADDED FOR MOBILE: Position dropdown relative to the viewport, not just parent */
                position: static; 
                width: 100%; 
                display: flex; 
                justify-content: flex-start; 
            }
            .dropdown-content {
                /* ADJUSTED POSITIONING FOR MOBILE */
                left: unset; 
                right: 0; 
                top: 45px; 
                width: auto; 
                min-width: 160px; 
                max-width: calc(100vw - 20px); 
                box-sizing: border-box; 
            }
            .top-bar .weather-info, .top-bar .time-info {
                font-size: 0.9em;
                width: 48%;
                text-align: center;
            }

            .outer-dashboard-container {
                flex-direction: column; /* Stack logo and main tile */
                align-items: center;
                gap: 15px;
                padding: 15px;
            }
            
            .tneb-logo-large {
                height: 80px; /* Adjust logo size for smaller screens */
            }
            .dashboard-main-tile {
                flex-direction: column; /* Stack items inside the main tile */
                width: 100%; /* Take full width on small screens */
                padding: 15px;
                gap: 10px; /* Adjust internal gap */
            }
            .report-btn {
                width: 90%; /* Make button wider */
                margin-left: 0; /* Remove specific margin on mobile */
            }
            .developed-by-text {
                text-align: center;
                order: 3; /* Position it correctly when stacked (after button and AMR info) */
                margin-left: 0; /* Remove auto margin on mobile */
            }
            .amr-info-display {
                flex-direction: column; /* Stack icon and text */
                text-align: center;
                white-space: normal; /* Allow text to wrap */
            }
            .amr-info-display i {
                margin-bottom: 5px;
            }

            /* Map adjustments for smaller screens */
            #map {
                width: 95vw; /* Maintain viewport width on small screens */
            }
        }
        .header-left {
            display: flex;
            align-items: center;      /* Vertically center icon and text */
            gap: 12px;                /* Space between icon and text */
            padding: 12px 0 12px 12px;/* Optional: add left padding for spacing */
        }

        .profile-icon {
            font-size: 2rem;          /* Icon size (adjust as needed) */
            color: #fff;              /* Icon color */
            display: flex;
            align-items: center;
        }

        .site-name {
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif; /* Modern font */
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="profile-dropdown">
            <span class="profile-icon" id="profileIcon"><i class="fas fa-user-circle"></i></span>
            <span class="site-name">TampriX</span>
            <div class="dropdown-content" id="profileDropdownContent">
                <p>Welcome, <?= $display_username ?></p>
                <form action="logout.php" method="post" style="margin: 0;">
                    <button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </div>
        </div>
        
        <div class="weather-info"><i class="fas fa-cloud-sun"></i> Chennai: 36°C, Overcast</div>
        <div class="time-info"><i class="fas fa-clock"></i> <span id="currentTime"></span></div>
    </div>

    <div class="outer-dashboard-container">
        <img src="TNEB.png" alt="TNEB Logo" class="tneb-logo-large animated-logo" id="tnebLogo">

        <div class="dashboard-main-tile">
            <button class="report-btn" onclick="window.location.href='index.php'">
                <i class="fas fa-table"></i> Report
            </button>

            <span class="developed-by-text">
                Developed by Akhshaiya &amp; Krishnashree<br>
                B.E (ECE) 2023-2027
            </span>

            <span class="amr-info-display">
                <i class="fas fa-map-marker-alt"></i>
                <span>AMR SIM track</span>
            </span>
        </div>
    </div>

    <div id="map"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Function to update current time
    function updateTime() {
        const now = new Date();
        document.getElementById('currentTime').textContent = now.toLocaleString('en-GB', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
    // Update time every second and on initial load
    setInterval(updateTime, 1000);
    updateTime(); // Initial call to display time immediately

    // Profile dropdown functionality
    document.addEventListener('DOMContentLoaded', function() {
        const profileIcon = document.getElementById('profileIcon');
        const profileDropdownContent = document.getElementById('profileDropdownContent');

        if (profileIcon && profileDropdownContent) {
            profileIcon.addEventListener('click', function(event) {
                profileDropdownContent.classList.toggle('show');
                event.stopPropagation();
            });

            window.addEventListener('click', function(event) {
                if (!profileIcon.contains(event.target) && !profileDropdownContent.contains(event.target)) {
                    if (profileDropdownContent.classList.contains('show')) {
                        profileDropdownContent.classList.remove('show');
                    }
                }
            });
        } else {
            console.error("Profile icon or dropdown content element not found. Check IDs: profileIcon, profileDropdownContent");
        }
    });

    // TNEB logo animation effect on click
    document.addEventListener('DOMContentLoaded', function() {
        var logo = document.getElementById('tnebLogo');
        if (logo) {
            logo.addEventListener('click', function() {
                logo.classList.add('animated-logo-click');
                setTimeout(function() {
                    logo.classList.remove('animated-logo-click');
                }, 600);
            });
        }
    });

    // PHP-generated JavaScript variable containing map marker data.
    var data = <?php echo $data; ?>;

    // Google Maps initialization function
    function initMap() {
        var map = new google.maps.Map(document.getElementById('map'), {
            zoom: 7,
            center: new google.maps.LatLng(11.127123, 78.656891), // Center of Tamil Nadu, India
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            mapTypeControl: false,
            streetViewControl: false,
            panControl: false,
            zoomControlOptions: { position: google.maps.ControlPosition.LEFT_BOTTOM }
        });

        var infowindow = new google.maps.InfoWindow({ maxWidth: 250 });
        var markers = [];

        for (var i = 0; i < data.length; i++) {
            var marker = new google.maps.Marker({
                position: new google.maps.LatLng(parseFloat(data[i][1]), parseFloat(data[i][2])),
                map: map,
                icon: {
                    url: data[i][3],
                    scaledSize: new google.maps.Size(30, 30),
                    origin: new google.maps.Point(0,0),
                    anchor: new google.maps.Point(15, 15)
                }
            });
            markers.push(marker);

            google.maps.event.addListener(marker, 'click', (function(marker, i) {
                return function() {
                    infowindow.setContent(data[i][0]);
                    infowindow.open(map, marker);
                }
            })(marker, i));
        }

        function autoCenter() {
            var bounds = new google.maps.LatLngBounds();
            if (markers.length > 0) {
                for (var i = 0; i < markers.length; i++) {
                    bounds.extend(markers[i].position);
                }
                map.fitBounds(bounds);
                if (markers.length === 1) map.setZoom(12);
            } else {
                map.setCenter(new google.maps.LatLng(11.127123, 78.656891));
                map.setZoom(7);
            }
        }
        autoCenter();
    }
    </script>
    <script async defer src="Put in Google MAP API key"></script>
</body>
</html>
