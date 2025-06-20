<?php
require('db.php');
session_start();


$registerError = '';
$loginError = '';

// --- Replace these with your actual Google Cloud Project ID and API Key ---
define('GOOGLE_CLOUD_PROJECT_ID', 'ENTER PROJECT ID');
define('GOOGLE_CLOUD_API_KEY', 'ENTER API KEY');
$RECAPTCHA_SITE_KEY = 'ENTER SITE KEY';

function verifyRecaptchaEnterprise($token, $siteKey, $action) {
    $url = "https://recaptchaenterprise.googleapis.com/v1/projects/" . GOOGLE_CLOUD_PROJECT_ID . "/assessments?key=" . GOOGLE_CLOUD_API_KEY;
    $request_body = [
        'event' => [
            'token' => $token,
            'expectedAction' => $action,
            'siteKey' => $siteKey
        ]
    ];
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($request_body),
            'ignore_errors' => true
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    if ($result === FALSE) {
        error_log("Failed to connect to reCAPTCHA Enterprise API. Check network or API key/project ID.");
        return ['success' => false, 'error-codes' => ['api-connection-failed']];
    }
    $response_data = json_decode($result, true);
    // Uncomment only for debugging:
    // die(json_encode($response_data, JSON_PRETTY_PRINT));
    return $response_data;
}

// Handle Registration
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
    $recaptcha_result = verifyRecaptchaEnterprise($recaptcha_token, $RECAPTCHA_SITE_KEY, 'register');
    if (!isset($recaptcha_result['name']) || ($recaptcha_result['riskAnalysis']['score'] ?? 0) < 0.5) {
        error_log("reCAPTCHA Enterprise registration failed.");
        $registerError = "reCAPTCHA verification failed. Please try again.";
    } else {
        $username = trim(mysqli_real_escape_string($con, $_POST['reg_username']));
        $password = $_POST['reg_password'];
        $confirm_password = $_POST['reg_confirm_password'];
        if ($password !== $confirm_password) {
            $registerError = "Passwords do not match!";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, password) VALUES ('$username', '$passwordHash')";
            if (mysqli_query($con, $query)) {
                $_SESSION['user_id'] = mysqli_insert_id($con);
                $_SESSION['username'] = $username;
                $_SESSION['logged_in'] = true; // <-- ONLY ADDED THIS LINE
                echo "Redirecting...";
                header("Location: default.php");
                exit();
            } else {
                if (mysqli_errno($con) == 1062) {
                    $registerError = "Username already exists. Please choose a different one.";
                } else {
                    $registerError = "Error occurred during registration. Please try again.";
                }
            }
        }
    }
}

// Handle Login
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
    $recaptcha_result = verifyRecaptchaEnterprise($recaptcha_token, $RECAPTCHA_SITE_KEY, 'login');
    if (!isset($recaptcha_result['name']) || ($recaptcha_result['riskAnalysis']['score'] ?? 0) < 0.5) {
        error_log("reCAPTCHA Enterprise login failed.");
        $loginError = "reCAPTCHA verification failed. Please try again.";
    } else {
        $username = trim(mysqli_real_escape_string($con, $_POST['login_username']));
        $password = $_POST['login_password'];
        $result = mysqli_query($con, "SELECT * FROM users WHERE username='$username'");
        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['logged_in'] = true; // <-- ONLY ADDED THIS LINE
                echo "Redirecting...";
                header("Location: default.php");
                exit();
            } else {
                $loginError = "Incorrect password.";
            }
        } else {
            $loginError = "Username not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TNEB Account</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
    <style>
        body {
            background-image: url('tower.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Roboto', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
            box-sizing: border-box;
        }
        .main-container {
             background: rgba(255,255,255,0.85); /* Make it more transparent */
             padding: 2.5rem 2rem 2rem 2rem;
             border-radius: 18px;
             box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.20);
             width: 100%;
             max-width: 400px;
             text-align: center;
             position: relative;
            }

        .logo {
            width: 200px;
            height: auto;
            margin-bottom: 0.7rem;
        }
        h2 {
            color: #24404e;
            margin-bottom: 0.7rem;
            font-weight: 700;
            font-size: 1.4rem;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 0.7rem;
        }
        input[type="text"], input[type="password"] {
            padding: 0.7rem;
            border: 1.5px solid #b7c3cd;
            border-radius: 8px;
            outline: none;
            font-size: 1rem;
            background: #f8fafb;
            transition: border 0.2s;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border: 1.5px solid #24404e;
        }
        button {
            padding: 0.7rem;
            background: linear-gradient(90deg, #24404e, #356d8c);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 0.3rem;
            transition: background 0.2s;
        }
        button:hover {
            background: linear-gradient(90deg, #356d8c, #24404e);
        }
        .toggle-link {
            margin-top: 0.7rem;
            font-size: 1rem;
        }
        .toggle-link a {
            color: #24404e;
            text-decoration: underline;
            font-weight: 700;
            cursor: pointer;
        }
        .toggle-link a:hover {
            color: #356d8c;
        }
        .hidden {
            display: none;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 1rem;
            font-weight: 700;
            font-size: 1rem;
        }
        .success {
            color: #2ecc71;
            margin-bottom: 1rem;
            font-weight: 700;
            font-size: 1rem;
        }
        .grecaptcha-badge {
            bottom: 10px !important;
            right: 10px !important;
            position: fixed !important;
            z-index: 9999;
            opacity: 1 !important;
            visibility: visible !important;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <img src="tneb_logo.png" alt="TNEB Logo" class="logo">
        <div id="form-container" <?php if ($loginError) echo 'class="hidden"'; ?>>
            <h2 id="form-title">Create Your TNEB Account</h2>
            <form id="register-form" method="post" autocomplete="off">
                <?php if ($registerError): ?>
                    <div class="error"><?= htmlspecialchars($registerError) ?></div>
                <?php endif; ?>
                <input type="hidden" name="action" value="register">
                <input type="text" name="reg_username" placeholder="username" required maxlength="32">
                <input type="password" name="reg_password" placeholder="password" required minlength="6">
                <input type="password" name="reg_confirm_password" placeholder="confirm password" required minlength="6">
                <input type="hidden" id="g-recaptcha-response-register" name="g-recaptcha-response">
                <button type="submit">Sign Up</button>
            </form>
            <div class="toggle-link">
                <a id="show-login" onclick="toggleForm('login')">Already have an account? Login</a>
            </div>
        </div>
        <div id="login-container" class="<?php if (!$loginError) echo 'hidden'; ?>">
            <h2>Login to TNEB</h2>
            <form id="login-form" method="post" autocomplete="off">
                <?php if ($loginError): ?>
                    <div class="error"><?= htmlspecialchars($loginError) ?></div>
                <?php endif; ?>
                <input type="hidden" name="action" value="login">
                <input type="text" name="login_username" placeholder="username" required maxlength="32">
                <input type="password" name="login_password" placeholder="password" required minlength="6">
                <input type="hidden" id="g-recaptcha-response-login" name="g-recaptcha-response">
                <button type="submit">Login</button>
            </form>
            <div class="toggle-link">
                <a id="show-register" onclick="toggleForm('register')">New here? Create an account</a>
            </div>
        </div>
    </div>
    <!-- reCAPTCHA Enterprise script -->
    <script src="https://www.google.com/recaptcha/enterprise.js?render=<?= $RECAPTCHA_SITE_KEY ?>"></script>
    <script>
        const RECAPTCHA_SITE_KEY = '<?= $RECAPTCHA_SITE_KEY ?>';
        function setRecaptchaRegister() {
            grecaptcha.enterprise.ready(function() {
                grecaptcha.enterprise.execute(RECAPTCHA_SITE_KEY, {action: 'register'}).then(function(token) {
                    document.getElementById('g-recaptcha-response-register').value = token;
                });
            });
        }
        function setRecaptchaLogin() {
            grecaptcha.enterprise.ready(function() {
                grecaptcha.enterprise.execute(RECAPTCHA_SITE_KEY, {action: 'login'}).then(function(token) {
                    document.getElementById('g-recaptcha-response-login').value = token;
                });
            });
        }
        document.addEventListener('DOMContentLoaded', function() {
            const isLoginContainerInitiallyVisible = !document.getElementById('login-container').classList.contains('hidden');
            if (isLoginContainerInitiallyVisible) {
                setRecaptchaLogin();
            } else {
                setRecaptchaRegister();
            }
        });
        function toggleForm(form) {
            if(form === 'login') {
                document.getElementById('form-title').textContent = "Login to TNEB";
                document.getElementById('form-container').classList.add('hidden');
                document.getElementById('login-container').classList.remove('hidden');
                setRecaptchaLogin();
            } else {
                document.getElementById('form-title').textContent = "Create Your TNEB Account";
                document.getElementById('login-container').classList.add('hidden');
                document.getElementById('form-container').classList.remove('hidden');
                setRecaptchaRegister();
            }
        }
        // Handle form submissions to ensure reCAPTCHA token is fresh
        document.getElementById('register-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            setRecaptchaRegister();
            setTimeout(() => { this.submit(); }, 100);
        });
        document.getElementById('login-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            setRecaptchaLogin();
            setTimeout(() => { this.submit(); }, 100);
        });
    </script>
</body>
</html>
