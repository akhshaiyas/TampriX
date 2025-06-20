<?php
session_start();
$inactivity_limit = 900; // 15 minutes

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactivity_limit) {
    session_unset();
    session_destroy();
    header("Location: register.php");
    exit();
}


// Update last activity time
$_SESSION['last_activity'] = time();
// Redirect to login page if user is not logged in
if (!isset($_SESSION['username'])) {
    header('Location: register.php');
    exit();
}

// Get the username for display
$display_username = htmlspecialchars($_SESSION['username']);

// Get the username for display
$display_username = htmlspecialchars($_SESSION['username']);
$servername = 'localhost';
$db_username = 'root'; // Renamed to avoid conflict with session $display_username
$password = '';
$dbname = 'tampering';

// Database connection
$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$event_flags = [
    'tampered' => 'Tampered',
    'reverse_current' => 'Reverse Current',
    'magnetic_field_detected' => 'Magnetic Field',
    'cover_open_event' => 'Cover Open',
    'neutral_missing_event' => 'Neutral Missing',
    'voltage_anomaly' => 'Voltage Anomaly',
    'communication_loss' => 'Communication Loss'
];

$meter_ids = $conn->query("SELECT DISTINCT meter_id FROM datasheet_1 ORDER BY meter_id");

// Pie chart for tampered vs not tampered
$pie_sql = "SELECT tampering_label, COUNT(*) as count FROM datasheet_1 GROUP BY tampering_label";
$pie_result = $conn->query($pie_sql);
$pie_labels = ['Tampered', 'Not Tampered'];
$pie_counts = [0, 0];
$total_meters = 0;
$meter_tampered = 0;
while ($row = $pie_result->fetch_assoc()) {
    if ($row['tampering_label'] == 1) {
        $pie_counts[0] = (int)$row['count'];
        $meter_tampered = $row['count'];
    } else {
        $pie_counts[1] = (int)$row['count'];
    }
    $total_meters += $row['count'];
}
$percent_tampered = $total_meters ? round($meter_tampered * 100 / $total_meters, 1) : 0;
// Pie chart for event flags H-L for tampered rows
$event_cols = [
    'reverse_current',
    'magnetic_field_detected',
    'cover_open_event',
    'neutral_missing_event',
    'voltage_anomaly'
];
$event_labels = [
    'Reverse Current',
    'Magnetic Field',
    'Cover Open',
    'Neutral Missing',
    'Voltage Anomaly'
];
$event_counts = [];
foreach ($event_cols as $col) {
    $sql = "SELECT COUNT(*) as cnt FROM datasheet_1 WHERE `$col`=1 AND tampering_label=1";
    $r = $conn->query($sql);
    $event_counts[] = $r->fetch_assoc()['cnt'];
}
$show_event_chart = array_sum($event_counts) > 0;
// Pie charts for categorical data (excluding Time Slot)
$cat_cols = [
    'meter_location_type'=>'Meter Location Type',
    'consumer_type'=>'Consumer Type',
    'bill_payment_history'=>'Bill Payment',
    'season'=>'Season'
];
$cat_data = [];
foreach ($cat_cols as $col=>$label) {
    $sql = "SELECT `$col`, COUNT(*) as cnt FROM datasheet_1 WHERE tampering_label=1 GROUP BY `$col`";
    $r = $conn->query($sql);
    $labels = [];
    $counts = [];
    while ($row = $r->fetch_assoc()) {
        $labels[] = $row[$col] === '' ? 'Unknown' : $row[$col];
        $counts[] = $row['cnt'];
    }
    $cat_data[$col] = ['label'=>$label,'labels'=>$labels,'counts'=>$counts];
}


// Filter logic
$where = [];
if (!empty($_GET['event_flag']) && isset($event_flags[$_GET['event_flag']])) {
    $flag = $conn->real_escape_string($_GET['event_flag']);
    if ($flag === 'tampered') {
        $where[] = "`tampering_label` = 1";
    } else {
        $where[] = "`$flag` = 1";
    }
}
if (!empty($_GET['meter_id'])) {
    $meter_id = $conn->real_escape_string($_GET['meter_id']);
    $where[] = "`meter_id` = '$meter_id'";
}
if (!empty($_GET['filter_date'])) {
    $user_date = $conn->real_escape_string($_GET['filter_date']);
    $date_parts = explode('-', $user_date);
    if (count($date_parts) === 3) {
        $filter_ddmmyyyy = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
        $where[] = "LEFT(`timestamp`, 10) = '$filter_ddmmyyyy'";
    }
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT * FROM datasheet_1 $where_sql";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Smart Meter Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="styles.css" />
    <style>
        /* General Body Styling (if not already in styles.css) */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center; /* Center content horizontally */
            min-height: 100vh;
        }

        /* TOP NAVIGATION BAR */
        .top-navbar {
            display: flex;
            justify-content: space-between; /* Pushes left and right items to ends */
            align-items: center;
            background-color: #24404e; /* Dark blue background */
            padding: 10px 20px;
            width: 100%;
            box-sizing: border-box; /* Include padding in width */
            color: #fff;
            height: 60px; /* Fixed height for the navbar */
            position: sticky; /* Make it sticky at the top */
            top: 0;
            z-index: 1000; /* Ensure it's above other content */
        }

        .top-navbar .left-items {
            flex: 1; /* Allows left items to take available space */
            display: flex; /* Ensures alignment if elements are added here */
            justify-content: flex-start;
        }

        .top-navbar .center-title {
            flex: 2; /* Allows title to take more space and center */
            text-align: center;
            color: #fff;
            font-size: 1.8rem;
            margin: 0; /* Reset default margin */
            display: flex; /* To vertically align icon and text */
            align-items: center;
            justify-content: center;
            gap: 10px; /* Space between icon and text */
        }
        .top-navbar .center-title i {
            color: #f1c40f; /* Yellow for icon */
        }

        .top-navbar .right-items {
            flex: 1; /* Allows right items to take available space */
            display: flex;
            align-items: center;
            justify-content: flex-end; /* Pushes content to the right */
            gap: 20px; /* Space between profile and external dashboard */
        }

        /* Profile Dropdown Styles (re-pasting for clarity, ensure it's in styles.css) */
        .profile-dropdown {
            position: relative;
            display: inline-block;
            cursor: pointer;
            z-index: 1001;
        }

        .profile-dropdown .profile-icon {
            font-size: 1.8rem;
            color: #fff;
            transition: color 0.2s ease;
        }

        .profile-dropdown .profile-icon:hover {
            color: #ccc;
        }

        .profile-dropdown .dropdown-content {
            display: none;
            position: absolute;
            background-color: #34495e; /* Darker background for dropdown */
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.4);
            z-index: 1;
            right: 0; /* Align to the right of the icon */
            border-radius: 8px;
            overflow: hidden;
            padding: 10px 0;
            top: calc(100% + 10px);
        }

        .profile-dropdown .dropdown-content p {
            color: #ecf0f1;
            padding: 8px 15px;
            margin: 0;
            font-size: 0.9rem;
            border-bottom: 1px solid #4a667b;
            text-align: left;
        }

        .profile-dropdown .dropdown-content form {
            margin: 0;
            padding: 0;
        }

        .profile-dropdown .dropdown-content button {
            background: none;
            border: none;
            color: #ecf0f1;
            padding: 10px 15px;
            text-align: left;
            text-decoration: none;
            display: block;
            width: 100%;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-dropdown .dropdown-content button:hover {
            background-color: #4a667b;
        }

        .profile-dropdown:hover .dropdown-content {
            display: block;
        }

        /* External Dashboard Button Styles */
        .redirect-button {
            background-color: #2980b9; /* Blue */
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s ease;
        }

        .redirect-button:hover {
            background-color: #3498db;
        }

        /* Remaining Dashboard Styles (from your original code) */
        .filter-form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-top: 20px; /* Space from the top navbar */
            width: 95%;
            max-width: 1200px; /* Adjust as needed */
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            align-items: flex-end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .filter-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .filter-group select,
        .filter-group input[type="date"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.9rem;
            min-width: 150px;
        }
        .filter-btn, .reset-btn, .analysis-btn {
            background-color: #2ecc71; /* Green */
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s ease;
            text-decoration: none; /* For reset button (anchor) */
        }
        .filter-btn:hover, .reset-btn:hover, .analysis-btn:hover {
            background-color: #27ae60;
        }
        .reset-btn {
            background-color: #f39c12; /* Orange */
        }
        .reset-btn:hover {
            background-color: #e67e22;
        }
        .analysis-btn {
            background-color: #9b59b6; /* Purple */
        }
        .analysis-btn:hover {
            background-color: #8e44ad;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1002; /* Above everything */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 25px;
            border-radius: 12px;
            width: 90%;
            max-width: 1000px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            position: relative;
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 20px;
            cursor: pointer;
            transition: color 0.2s;
        }
        .close:hover, .close:focus {
            color: #333;
            text-decoration: none;
        }
        .modal-header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .modal-header h2 {
            color: #24404e;
            font-size: 1.8rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .summary-cards {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .summary-card {
            background-color: #ecf0f1;
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 1.05rem;
            font-weight: bold;
            color: #34495e;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .tampered-card { background-color: #e74c3c; color: white; }
        .ok-card { background-color: #2ecc71; color: white; }
        .percentage-card { background-color: #3498db; color: white; }

        .modal-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            justify-content: center;
        }
        .pie-section {
            background-color: #f8f8f8;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        .pie-section canvas {
            max-width: 100%;
            height: auto;
        }

        /* Table Styles */
        .table-scroll {
            width: 95%;
            max-width: 1200px; /* Adjust as needed */
            overflow-x: auto;
            margin-top: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px; /* Space below table */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px; /* Ensure table is wide enough for content */
        }
        table th, table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
            font-size: 0.9rem;
        }
        table th {
            background-color: #24404e;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
       /* table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }*/
        table tbody tr:hover {
            background-color: #f1f1f1;
        }
        .tampered-row {
            background-color: #ffe6e6; /* Light red for tampered rows */
        }
        .tampered-row:hover {
            background-color: #ffcccc;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.8em;
            text-transform: uppercase;
        }
        .badge-tampered { background-color: #e74c3c; color: white; }
        .badge-ok { background-color: #2ecc71; color: white; }
        .no-data {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 1.1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .top-navbar {
                flex-direction: column;
                height: auto;
                padding: 10px;
            }
            .top-navbar .left-items,
            .top-navbar .right-items,
            .top-navbar .center-title {
                width: 100%;
                justify-content: center; /* Center items in smaller view */
                margin-top: 5px;
                text-align: center;
            }
            .filter-form {
                flex-direction: column;
                align-items: center;
                width: 95%;
            }
            .filter-group {
                width: 100%;
                align-items: center;
            }
            .filter-group select,
            .filter-group input[type="date"] {
                width: 90%;
            }
            .modal-content {
                width: 95%;
                padding: 20px;
            }
            .modal-body {
                grid-template-columns: 1fr;
            }
            .summary-cards {
                flex-direction: column;
            }
            table th, table td {
                padding: 8px 10px;
            }
        }
        .site-name {
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            color:rgb(248, 249, 251);
            letter-spacing: 1px;
            margin-right: 24px; /* Optional: space after TampriX if needed */
            user-select: none;
        }

    </style>
</head>
<body>
    <div class="top-navbar">
        <div class="left-items">
            <span class="site-name">TampriX</span>
        </div>

        <h1 class="center-title"><i class="fas fa-bolt"></i> Meter Tampering Monitoring</h1>

        <div class="right-items">
            <button class="redirect-button" onclick="window.location.href='https://predictioneb.streamlit.app/'">
                <i class="fas fa-external-link-alt"></i> External Dashboard
            </button>

            <div class="profile-dropdown">
                <span class="profile-icon" id="profileIcon"><i class="fas fa-user-circle"></i></span>
                <div class="dropdown-content" id="profileDropdownContent">
                    <p>Welcome, <?= $display_username ?></p>
                    <form action="logout.php" method="post" style="margin: 0;">
                        <button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label><i class="fas fa-tachometer-alt"></i> Meter ID:</label>
            <select name="meter_id">
                <option value="">All Meters</option>
                <?php foreach($meter_ids as $mid): ?>
                    <option value="<?= htmlspecialchars($mid['meter_id']) ?>" <?= (isset($_GET['meter_id']) && $_GET['meter_id'] == $mid['meter_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mid['meter_id']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label><i class="fas fa-flag"></i> Event Type:</label>
            <select name="event_flag">
                <option value="">All Events</option>
                <?php foreach($event_flags as $flag => $label): ?>
                    <option value="<?= $flag ?>" <?= (isset($_GET['event_flag']) && $_GET['event_flag'] == $flag) ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label><i class="fas fa-calendar-day"></i> Date:</label>
            <input type="date" name="filter_date" value="<?= htmlspecialchars($_GET['filter_date'] ?? '') ?>" />
        </div>
        <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> Apply Filters</button>
        <a href="index.php" class="reset-btn"><i class="fas fa-sync"></i> Reset</a>
        <button type="button" class="analysis-btn" onclick="openModal()"><i class="fas fa-chart-pie"></i> Analysis</button>
    </form>
    <div id="analysisModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div class="modal-header">
                <h2><i class="fas fa-chart-pie"></i> Analysis Dashboard</h2>
                <div class="summary-cards">
                    <div class="summary-card"><i class="fas fa-tachometer-alt"></i> Total: <?= $total_meters ?></div>
                    <div class="summary-card tampered-card"><i class="fas fa-bolt"></i> Tampered: <?= $meter_tampered ?></div>
                    <div class="summary-card ok-card"><i class="fas fa-check-circle"></i> Not Tampered: <?= $total_meters - $meter_tampered ?></div>
                    <div class="summary-card percentage-card"><i class="fas fa-percentage"></i> <?= $percent_tampered ?>% Tampered</div>
                </div>
            </div>
            <div class="modal-body">
                <div class="pie-section">
                    <canvas id="tamperedPie"></canvas>
                </div>
                <?php if ($meter_tampered > 0 && $show_event_chart): ?>
                <div class="pie-section">
                    <canvas id="eventPie"></canvas>
                </div>
                <?php endif; ?>
                <?php foreach ($cat_data as $col => $data): ?>
                    <?php if (array_sum($data['counts']) > 0): ?>
                    <div class="pie-section">
                        <canvas id="catPie_<?= $col ?>"></canvas>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>


    <div class="table-scroll">
        <?php
        if ($result && $result->num_rows > 0) {
            echo "<table>";
            echo "<thead><tr>";
            while ($fieldinfo = $result->fetch_field()) {
                echo "<th>" . htmlspecialchars($fieldinfo->name) . "</th>";
            }
            echo "</tr></thead><tbody>";
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()) {
                $rowClass = '';
                if (isset($row['tampering_label'])) {
                    $label = strtolower(trim($row['tampering_label']));
                    if ($label == '1' || $label == 'tampered') {
                        $rowClass = 'class="tampered-row"';
                    }
                }
                echo "<tr $rowClass>";
                foreach ($row as $key => $value) {
                    if ($key === 'tampering_label') {
                        $badgeClass = (strtolower(trim($value)) == '1' || strtolower(trim($value)) == 'tampered') ? 'badge badge-tampered' : 'badge badge-ok';
                        $badgeText = (strtolower(trim($value)) == '1' || strtolower(trim($value)) == 'tampered') ? 'Tampered' : 'Not Tampered';
                        echo "<td><span class=\"$badgeClass\">" . htmlspecialchars($badgeText) . "</span></td>";
                    } else {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                }
                echo "</tr>";
            }

            echo "</tbody></table>";
        } else {
            echo "<p class='no-data'>No matching records found</p>";
        }
        $conn->close();
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    function openModal() {
        document.getElementById('analysisModal').style.display = 'flex'; // Use flex to center
        setTimeout(function() {
            window.tamperedPieChart?.destroy();
            window.eventPieChart?.destroy();
            <?php foreach ($cat_data as $col => $data): ?>
            window["catPieChart_<?= $col ?>"]?.destroy();
            <?php endforeach; ?>

            window.tamperedPieChart = new Chart(document.getElementById('tamperedPie'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode($pie_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($pie_counts) ?>,
                        backgroundColor: ['#e74c3c', '#3498db']
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: true, position: 'bottom' },
                        title: { display: true, text: 'Tampered vs Not Tampered' }
                    }
                }
            });

            <?php if ($meter_tampered > 0 && $show_event_chart): ?>
            window.eventPieChart = new Chart(document.getElementById('eventPie'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode($event_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($event_counts) ?>,
                        backgroundColor: ['#e67e22', '#8e44ad', '#16a085', '#2980b9', '#c0392b']
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: true, position: 'bottom' },
                        title: { display: true, text: 'Event Flags Causing Tampering' }
                    }
                }
            });
            <?php endif; ?>

            <?php foreach ($cat_data as $col => $data): ?>
            <?php if (array_sum($data['counts']) > 0): ?>
            window["catPieChart_<?= $col ?>"] = new Chart(document.getElementById('catPie_<?= $col ?>'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode($data['labels']) ?>,
                    datasets: [{
                        data: <?= json_encode($data['counts']) ?>,
                        backgroundColor: [
                            '#e74c3c', '#3498db', '#f1c40f', '#2ecc71', '#9b59b6', '#e67e22', '#1abc9c', '#34495e', '#ff69b4', '#7f8c8d'
                        ]
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: true, position: 'bottom' },
                        title: { display: true, text: <?= json_encode($data['label']) ?> }
                    }
                }
            });
            <?php endif; ?>
            <?php endforeach; ?>
        }, 100);
    }

    function closeModal() {
        document.getElementById('analysisModal').style.display = 'none';
    }

    window.onclick = function(event) {
        var modal = document.getElementById('analysisModal');
        if (event.target == modal) modal.style.display = "none";
    }
    </script>
</body>
</html>
