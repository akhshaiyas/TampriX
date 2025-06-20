<?php

// --- Database Configuration ---
$servername = 'localhost';
$db_username = 'root'; // Renamed to avoid conflict with session $display_username
$password = '';
$dbname = 'tampering';

// Create a new database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection to the database was successful
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// --- Data to be Inserted/Replaced ---
// This array holds all the meter data. 'tampering_label' is now 0 or 1.
$meter_data = [
    [1, 'Muppandal', '8.16', '77.33', 'green-dot.png', 'Reporting', '2022-06-08', 0, NULL, '10.252.10.1'],
    [2, 'Sultanpet', '10.52', '77.11', 'red-dot.png', 'Non Reporting', '2022-06-10', 1, 'Reverse Current', '10.252.10.2'],
    [3, 'Mettukadai', '10.52', '77.23', 'orange-dot.png', 'Detain', '2022-06-16', 0, NULL, '10.252.10.3'],
    [4, 'Arasampalayam', '10.51', '77.03', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.4'],
    [5, 'Edayarpalayam', '10.55', '77.07', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.5'],
    [6, 'Poolavadi', '10.44', '77.16', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.6'],
    [7, 'Pongalur', '10.58', '77.21', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.7'],
    [8, 'Poosaripatti', '10.41', '77.8', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.8'],
    [9, 'Kethanur', '10.54', '77.13', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.9'],
    [10, 'Myvadi', '10.36', '77.19', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.10'],
    [11, 'Anthiyur', '10.36', '77.11', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.11'],
    [12, 'Thaneerpandal', '10.57', '77.19', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.12'],
    [13, 'Karamadai', '11.11', '76.58', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Magnetic Interference', '10.252.10.13'],
    [14, 'Andipatti', '9.59', '77.35', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.14'],
    [15, 'T.Meenakshipuram', '9.52', '77.18', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.15'],
    [16, 'Chinna Santhi Puram', '9.42', '77.29', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.16'],
    [17, 'Senbagaramanpudur', '8.16', '77.31', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.17'],
    [18, 'Kathadimalai', '8.14', '77.33', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.18'],
    [19, 'Muttam', '8.8', '77.19', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Voltage Anomaly', '10.252.10.19'],
    [20, 'Muppandal', '8.16', '77.33', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.20'],
    [21, 'Kayathar P.Kulam', '8.58', '77.44', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.21'],
    [22, 'Thoothukudi', '8.76', '78.13', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.22'],
    [23, 'Ottapidaram', '8.54', '78.1', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.23'],
    [24, 'Onamkulam', '8.58', '77.51', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.24'],
    [25, 'Kayathar', '8.57', '77.43', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.25'],
    [26, 'Vagaikulam', '8.45', '78.0', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.26'],
    [27, 'Ramaswaram', '9.28', '79.31', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.27'],
    [28, 'Kalia Nagari', '13.32', '79.58', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.28'],
    [29, 'Mylampatti', '10.44', '78.16', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Reverse Current', '10.252.10.29'],
    [30, 'Kalugar', '10.23', '78.47', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Magnetic Interference', '10.252.10.30'],
    [31, 'Killukottai', '10.39', '78.56', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Voltage Anomaly', '10.252.10.31'],
    [32, 'Viralimalai', '10.36', '78.32', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Reverse Current', '10.252.10.32'],
    [33, 'Gandharvakottai', '10.32', '79.1', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Magnetic Interference', '10.252.10.33'],
    [34, 'Kayinankarai', '10.41', '78.44', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Voltage Anomaly', '10.252.10.34'],
    [35, 'Puliankulam', '8.19', '77.44', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.35'],
    [36, 'Thalaiyuthu', '8.48', '77.39', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.36'],
    [37, 'A.Pandiyapuram', '8.56', '77.39', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.37'],
    [38, 'Servalarhills', '8.42', '77.21', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.38'],
    [39, 'Ayakudi', '9.0', '77.21', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.39'],
    [40, 'Naduvakurichi', '9.7', '77.3', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.40'],
    [41, 'Marugalkurichi', '8.31', '77.4', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Reverse Current', '10.252.10.41'],
    [42, 'Thoopakudi', '8.46', '77.27', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Magnetic Interference', '10.252.10.42'],
    [43, 'Kumarapuram', '8.16', '77.35', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.43'],
    [44, 'Nettur', '8.54', '77.33', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.44'],
    [45, 'Shankaneri', '8.12', '77.4', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.45'],
    [46, 'Kannankulam', '8.1', '77.46', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.46'],
    [47, 'Gangaikondan', '8.51', '77.35', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.47'],
    [48, 'Overi', '8.18', '77.53', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.48'],
    [49, 'Mangalapuram', '9.3', '77.22', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.49'],
    [50, 'Achankundam', '8.57', '77.28', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.50'],
    [51, 'Panakudi', '8.19', '77.33', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.51'],
    [52, 'Vinayagapuram', '8.21', '77.37', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Voltage Anomaly', '10.252.10.52'],
    [53, 'Athankaraipallivasal', '8.18', '77.53', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Reverse Current', '10.252.10.53'],
    [54, 'Kuttam', '8.19', '77.57', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Magnetic Interference', '10.252.10.54'],
    [55, 'Athukurichi', '8.15', '77.46', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Voltage Anomaly', '10.252.10.55'],
    [56, 'Thanakkankulam', '9.88', '78.4', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Reverse Current', '10.252.10.56'],
    [57, 'Petharangapuram', '8.26', '77.68', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Magnetic Interference', '10.252.10.57'],
    [58, 'Kadayanallur', '9.7', '77.34', 'red-dot.png', 'Non Reporting', '2022-06-15', 1, 'Voltage Anomaly', '10.252.10.58'],
    [59, 'Poombuhar', '11.8', '79.51', 'orange-dot.png', 'Detain', '2022-06-15', 0, NULL, '10.252.10.59'],
    [60, 'Vedaranyam', '10.37', '79.84', 'orange-dot.png', 'Detain', '2022-06-15', 0, NULL, '10.252.10.60'],
    [61, 'Agasthianpalli', '10.35', '79.85', 'orange-dot.png', 'Detain', '2022-06-15', 0, NULL, '10.252.10.61'],
    [62, 'Ennore', '13.16', '80.19', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.62'],
    [63, 'Pushpathur', '10.33', '77.25', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.63'],
    [64, 'Pudupudur', '10.18', '77.5', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.64'],
    [65, 'Dharapuram', '10.44', '77.28', 'orange-dot.png', 'Detain', '2022-06-15', 0, NULL, '10.252.10.65'],
    [66, 'Kullapalayam', '10.47', '77.31', 'orange-dot.png', 'Detain', '2022-06-15', 0, NULL, '10.252.10.66'],
    [67, 'Thalavadi', '11.47', '77.1', 'orange-dot.png', 'Detain', '2022-06-15', 0, NULL, '10.252.10.67'],
    [68, 'Uthankarai', '12.15', '78.33', 'orange-dot.png', 'Detain', '2022-06-15', 0, NULL, '10.252.10.68'],
    [69, 'Nallampalli', '12.3', '78.6', 'orange-dot.png', 'Detain', '2022-06-15', 0, NULL, '10.252.10.69'],
    [70, 'Madam', '12.7', '77.49', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.70'],
    [71, 'Karumanthurai', '11.5', '78.37', 'orange-dot.png', 'Detain', '2022-06-15', 0, NULL, '10.252.10.71'],
    [72, 'Emerald', '11.2', '76.38', 'orange-dot.png', 'Detain', '2022-06-15', 0, NULL, '10.252.10.72'],
    [73, 'Uppatti', '11.31', '76.12', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.73'],
    [74, 'Thailpatti', '9.22', '77.48', 'orange-dot.png', 'Detain', '2022-06-15', 0, NULL, '10.252.10.74'],
    [75, 'M.S.Puram', '9.48', '77.39', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.75'],
    [76, 'Kamagiri', '12.21', '77.38.5', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.76'],
    [77, 'Jamunamaruthur', '12.35', '78.7', 'green-dot.png', 'Reporting', '2022-06-15', 0, NULL, '10.252.10.77']
];
