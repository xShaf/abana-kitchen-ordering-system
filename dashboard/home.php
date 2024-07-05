<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
}

include_once ('../includes/connection.db.php');

$sql = "SELECT COUNT(*) AS remaining_orders FROM orders WHERE order_status = 'PENDING'";
$stmt = oci_parse($dbconn, $sql);
oci_execute($stmt);

$row = oci_fetch_array($stmt, OCI_ASSOC);
$remainingOrders = $row['REMAINING_ORDERS'];

// Close the database connection
oci_close($dbconn);

$sql = "SELECT SUM(quantity * amount) AS total_revenue FROM order_details";
$stmt = oci_parse($dbconn, $sql);
oci_execute($stmt);

$row = oci_fetch_array($stmt, OCI_ASSOC);
$totalRevenue = $row['TOTAL_REVENUE'];

// Close the database connection
oci_close($dbconn);

$sql = "SELECT SUM(quantity) AS TOTAL_PRODUCT_SOLD FROM order_details";
$stmt = oci_parse($dbconn, $sql);
oci_execute($stmt);

$row = oci_fetch_array($stmt, OCI_ASSOC);
$totalProductSold = $row['TOTAL_PRODUCT_SOLD'];

// Close the database connection
oci_close($dbconn);

// Query to fetch preparation dates for dropdown
$dateQuery = "SELECT DISTINCT TO_CHAR(preparation_Date, 'yyyy-mm-dd') AS preparation_Date FROM ORDER_DETAILS ORDER BY preparation_Date DESC";
$dateStmt = oci_parse($dbconn, $dateQuery);
oci_execute($dateStmt);
?>

<html>
<?php include ("../includes/header-tag.php"); ?>

<header>
    <?php include ("../includes/sidebar.php"); ?>
</header>
<style>
    body {
        background-color: papayawhip;
    }

    .btn-red {
        background-color: #BA4D52;
        color: white;

    }

    .btn-red:hover {
        background-color: #8b3a3e;
    }

    .card-title {
        color: white;
    }
</style>

<body class="gradient">
    <div class="bg-white bg-gradient shadow">
        <h2 class="p-3 text-center"><strong>ABANA KITCHEN ORDERING SYSTEM</strong></h2>
    </div>

    <div class="container bg-white bg-gradient bg-opacity-75 border rounded shadow-lg p-4">
        <h2 class=""><strong>Sales Summary</strong></h2>
        <div class="row p-4">
            <div class="col-12 col-md-4 mb-4">
                <div class="card shadow-lg bg-danger bg-gradient">
                    <div class="card-body shadow">
                        <h3 class="card-title"><strong><i class="bi bi-cart"></i> ORDERS REMAINING</strong></h3>
                        <p class="card-text">
                        <h3 class="text-white"><?php echo $remainingOrders; ?> Orders</h3>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-4">
                <div class="card shadow-lg bg-warning bg-gradient">
                    <div class="card-body shadow">
                        <h3 class="card-title"><strong><i class="bi bi-basket"></i> PRODUCT SOLD</strong></h3>
                        <p class="card-text">
                        <h3 class="text-white"><?php echo $totalProductSold ?> Quantity</h3>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-4 mb-4">
                <div class="card shadow-lg bg-success bg-gradient">
                    <div class="card-body shadow">
                        <h3 class="card-title"><strong><i class="bi bi-cash"></i> INCOME</strong></h3>
                        <p class="card-text">
                        <h3 class="text-white">RM<?php echo number_format($totalRevenue, 2) ?></h3>
                        </p>
                    </div>
                </div>
            </div>
            <div id="preparationSchedule" class="col-12 mb-4">
                <div class="card shadow">
                    <h2 class="p-4"><strong>Orders Preparation Schedule</strong></h2>
                    <div class="p-4">
                        <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="preparationDate" class="form-label">Select Preparation Date:</label>
                                    <select class="form-control" id="preparationDate" name="preparationDate">
                                        <?php
                                        while ($dateRow = oci_fetch_assoc($dateStmt)) {
                                            $dateValue = $dateRow['PREPARATION_DATE'];
                                            echo "<option value='$dateValue'>$dateValue</option>";
                                        }
                                        oci_free_statement($dateStmt);
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <button type="submit" class="btn btn-primary mt-4">Filter</button>
                                </div>
                            </div>
                        </form>

                        <!-- Display queried results -->
                        <?php
                        if (isset($_GET['preparationDate'])) {
                            $selectedDate = $_GET['preparationDate'];

                            // Query for products scheduled for completion on the selected date
                            $sql = "
                        SELECT OD.prod_ID, P.prod_Name, OD.quantity, OD.preparation_Date, O.order_ID, O.order_Date, S.staff_ID, S.staff_Name
                        FROM ORDER_DETAILS OD
                        JOIN ORDERS O ON OD.order_ID = O.order_ID
                        JOIN PRODUCT P ON OD.prod_ID = P.prod_ID
                        LEFT JOIN STAFF S ON O.staff_ID = S.staff_ID
                        WHERE OD.preparation_Date = TO_DATE(:selectedDate, 'yyyy-mm-dd')
                        ORDER BY O.order_Date";

                            $stid = oci_parse($dbconn, $sql);
                            oci_bind_by_name($stid, ":selectedDate", $selectedDate);
                            oci_execute($stid);

                            // Display results in a table
                            echo '<div class="mt-4">';
                            echo '<table class="table table-bordered table-hover">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th scope="col">Order ID</th>';
                            echo '<th scope="col">Order Date</th>';
                            echo '<th scope="col">Product Name</th>';
                            echo '<th scope="col">Quantity</th>';
                            echo '<th scope="col">Preparation Date</th>';
                            echo '<th scope="col">Staff Name</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';

                            while ($row = oci_fetch_assoc($stid)) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row["ORDER_ID"], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . htmlspecialchars($row["ORDER_DATE"], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . htmlspecialchars($row["PROD_NAME"], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . htmlspecialchars($row["QUANTITY"], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . htmlspecialchars($row["PREPARATION_DATE"], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>' . htmlspecialchars($row["STAFF_NAME"], ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '</tr>';
                            }

                            echo '</tbody>';
                            echo '</table>';
                            echo '</div>';

                            oci_free_statement($stid);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 mb-4">
            <div class="card shadow">
                <h2 class="p-4"><strong>Add new order here!</strong></h2>
                <a href="../order/customer_details.php" name="" id="" class="btn btn-primary btn-lg m-4" href="#"
                    role="button">Add new order!</a>

            </div>
        </div>
        <!-- Orders with Highest Total Amount Section -->
        <div id="highestTotalAmount" class="col-12 mb-4">
            <div class="card shadow">
                <h2 class="p-4"><strong>Orders with Highest Total Amount</strong></h2>
                <p class="ps-4">Free Abana Kitchen Gift (Keychain)</p>
                <div class="ps-4">
                    <?php
                    // Query for orders with the highest total amount
                    $sql = "
                    SELECT O.order_ID, O.cust_Name, P.prod_Name, OD.amount
                    FROM ORDERS O
                    JOIN ORDER_DETAILS OD ON O.order_ID = OD.order_ID
                    JOIN PRODUCT P ON OD.prod_ID = P.prod_ID
                    WHERE OD.amount = (
                        SELECT MAX(amount)
                        FROM ORDER_DETAILS
                    )";

                    $stid = oci_parse($dbconn, $sql);
                    oci_execute($stid);

                    // Display results in a table
                    echo '<div class="mt-4">';
                    echo '<table class="table table-bordered table-hover">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th scope="col">Order ID</th>';
                    echo '<th scope="col">Customer Name</th>';
                    echo '<th scope="col">Product Name</th>';
                    echo '<th scope="col">Amount</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';

                    while ($row = oci_fetch_assoc($stid)) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row["ORDER_ID"], ENT_QUOTES, 'UTF-8') . '</td>';
                        echo '<td>' . htmlspecialchars($row["CUST_NAME"], ENT_QUOTES, 'UTF-8') . '</td>';
                        echo '<td>' . htmlspecialchars($row["PROD_NAME"], ENT_QUOTES, 'UTF-8') . '</td>';
                        echo '<td>' . htmlspecialchars($row["AMOUNT"], ENT_QUOTES, 'UTF-8') . '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';

                    oci_free_statement($stid);
                    ?>
                </div>
            </div>
        </div>
        <!-- Products Scheduled for Completion Section -->

    </div>
</body>

<?php include ("../includes/footer.php");
include ("../includes/footer-tag.php"); ?>

</html>