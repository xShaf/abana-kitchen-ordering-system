<?php
require_once "includes/connection.db.php";

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = $_POST['staff_id'];
    $password = $_POST['password'];

    // Prepare the SQL query to fetch the user
    $sql = 'SELECT STAFF_PASSWORD FROM staff WHERE STAFF_ID = :staff_id';
    $stid = oci_parse($dbconn, $sql);

    if (!$stid) {
        $e = oci_error($dbconn);
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }

    oci_bind_by_name($stid, ':staff_id', $staff_id);

    // Execute the query
    $r = oci_execute($stid);

    if (!$r) {
        $e = oci_error($stid);
        echo "<p>Error: " . htmlentities($e['message']) . "</p>";
    } else {
        $row = oci_fetch_array($stid, OCI_ASSOC);

        // For demonstration, assuming passwords are stored as plain text. Use password_verify for hashed passwords.
        if ($row && $password == $row['STAFF_PASSWORD']) {
            // Login successful
            echo 'Login successful! Welcome, ' . htmlspecialchars($staff_id) . '!';
            header('Location: /abana-kitchen-ordering-system/dashboard/home.php');

            session_start();
            $_SESSION['staff_id'] = $staff_id;
        } else {
            // Login failed
            $login_error = 'Invalid username or password!';
        }

        // Free the statement
        oci_free_statement($stid);
    }
}

// Close the database connection
CloseConn($dbconn);
?>

<html>
<?php
include ("includes/header-tag.php");
?>
<style>
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #32645d;
        margin: 0;
        font-family: 'Poppins';
    }

    .login-container {
        background: white;
        border-radius: 10px;
        padding: 2rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
        position: relative;
    }

    .login-container img {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: #32645d;
        position: absolute;
        top: -75px;
        left: calc(50% - 75px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .login-container h2 {
        margin-top: 50px;
        margin-bottom: 1rem;
    }

    .login-container input {
        width: 100%;
        padding: 0.75rem;
        margin: 0.5rem 0;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-sizing: border-box;
    }

    .login-container button {
        width: 100%;
        padding: 0.75rem;
        margin-top: 1rem;
        background-color: #00a0e3;
        border: none;
        border-radius: 5px;
        color: white;
        font-size: 1rem;
        cursor: pointer;
    }

    .login-container button:hover {
        background-color: #007bb5;
    }
</style>

<body>
    <header>
    </header>
    <main>
        <div class="login-container">
            <img src="assets/images/logo.png" alt="Logo">
            <h2>Login</h2>
            <?php if ($login_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($login_error); ?></p>
            <?php endif; ?>
            <form action="" method="post">
                <input type="text" name="staff_id" placeholder="Staff ID" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Sign in</button>
            </form>
        </div>
    </main>
</body>
<?php
include ("includes/footer.php");
include ("includes/footer-tag.php");
?>

</html>