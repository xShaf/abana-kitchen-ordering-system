<?php
require_once '../includes/connection.db.php';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $selectDate = isset($_GET['selectDate']) ? $_GET['selectDate'] : '';
    $totalSales = 0;
    $totalOrders = 0;
    $averageOrderValue = 0;
    $topSellingProducts = [];
    $paymentMethodBreakdown = [];
    $orderStatusSummary = [];
    $orderDetails = [];

    if (!empty($selectDate)) {
        // Total Sales, Total Orders, and Average Order Value
        $sql = "SELECT 
                    SUM(R.TOTAL_AMOUNT) AS TOTAL_SALES,
                    COUNT(O.ORDER_ID) AS TOTAL_ORDERS,
                    AVG(R.TOTAL_AMOUNT) AS AVERAGE_ORDER_VALUE
                FROM 
                    ORDERS O
                JOIN 
                    RECEIPT R ON O.ORDER_ID = R.ORDER_ID
                WHERE 
                    O.ORDER_DATE = TO_DATE(:selectDate, 'YYYY-MM-DD')
                GROUP BY 
                    O.ORDER_DATE";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":selectDate", $selectDate);
        oci_execute($stid);

        if ($row = oci_fetch_assoc($stid)) {
            $totalSales = $row['TOTAL_SALES'];
            $totalOrders = $row['TOTAL_ORDERS'];
            $averageOrderValue = $row['AVERAGE_ORDER_VALUE'];
        }

        oci_free_statement($stid);

        // Top-Selling Products
        $sql = "SELECT 
                    P.PROD_NAME, SUM(OD.QUANTITY) AS QUANTITY_SOLD
                FROM 
                    ORDER_DETAILS OD
                JOIN 
                    PRODUCT P ON OD.PROD_ID = P.PROD_ID
                JOIN 
                    ORDERS O ON OD.ORDER_ID = O.ORDER_ID
                WHERE 
                    O.ORDER_DATE = TO_DATE(:selectDate, 'YYYY-MM-DD')
                GROUP BY 
                    P.PROD_NAME
                ORDER BY 
                    QUANTITY_SOLD DESC";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":selectDate", $selectDate);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            $topSellingProducts[] = $row;
        }

        oci_free_statement($stid);

        // Payment Method Breakdown
        $sql = "SELECT 
                    R.PAYMENT_METHOD, SUM(R.TOTAL_AMOUNT) AS TOTAL_SALES
                FROM 
                    RECEIPT R
                JOIN 
                    ORDERS O ON R.ORDER_ID = O.ORDER_ID
                WHERE 
                    O.ORDER_DATE = TO_DATE(:selectDate, 'YYYY-MM-DD')
                GROUP BY 
                    R.PAYMENT_METHOD";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":selectDate", $selectDate);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            $paymentMethodBreakdown[] = $row;
        }

        oci_free_statement($stid);

        // Order Status Summary
        $sql = "SELECT 
                    O.ORDER_STATUS, COUNT(O.ORDER_ID) AS COUNT
                FROM 
                    ORDERS O
                WHERE 
                    O.ORDER_DATE = TO_DATE(:selectDate, 'YYYY-MM-DD')
                GROUP BY 
                    O.ORDER_STATUS";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":selectDate", $selectDate);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            $orderStatusSummary[] = $row;
        }

        oci_free_statement($stid);

        // Detailed Order List
        $sql = "SELECT 
                    O.ORDER_ID, O.CUST_NAME, P.PROD_NAME, OD.QUANTITY, OD.AMOUNT
                FROM 
                    ORDERS O
                JOIN 
                    ORDER_DETAILS OD ON O.ORDER_ID = OD.ORDER_ID
                JOIN 
                    PRODUCT P ON OD.PROD_ID = P.PROD_ID
                WHERE 
                    O.ORDER_DATE = TO_DATE(:selectDate, 'YYYY-MM-DD')";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":selectDate", $selectDate);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            $orderDetails[] = $row;
        }

        oci_free_statement($stid);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include_once ('../includes/header-tag.php'); ?>
    <style>
        .card {
            margin: 20px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            height: 100%;
        }

        .card-title {
            margin-bottom: 20px;
        }

        .cards-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: stretch;
        }

        .card-wrapper {
            flex: 0 1 300px;
            display: flex;
            align-items: stretch;
        }

        .card ul {
            padding-left: 20px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            <?php if (!empty($selectDate)): ?>
                // Data for Payment Method Breakdown Chart
                var paymentMethods = <?php echo json_encode(array_column($paymentMethodBreakdown, 'PAYMENT_METHOD')); ?>;
                var paymentAmounts = <?php echo json_encode(array_column($paymentMethodBreakdown, 'TOTAL_SALES')); ?>;

                // Payment Method Breakdown Chart
                var ctxPayment = document.getElementById('paymentMethodChart').getContext('2d');
                var paymentMethodChart = new Chart(ctxPayment, {
                    type: 'pie',
                    data: {
                        labels: paymentMethods,
                        datasets: [{
                            data: paymentAmounts,
                            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Payment Method Breakdown'
                            }
                        }
                    },
                });

                // Data for Order Status Summary Chart
                var orderStatuses = <?php echo json_encode(array_column($orderStatusSummary, 'ORDER_STATUS')); ?>;
                var orderCounts = <?php echo json_encode(array_column($orderStatusSummary, 'COUNT')); ?>;

                // Order Status Summary Chart
                var ctxOrderStatus = document.getElementById('orderStatusChart').getContext('2d');
                var orderStatusChart = new Chart(ctxOrderStatus, {
                    type: 'bar',
                    data: {
                        labels: orderStatuses,
                        datasets: [{
                            label: 'Orders',
                            data: orderCounts,
                            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false,
                            },
                            title: {
                                display: true,
                                text: 'Order Status Summary'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    },
                });
            <?php endif; ?>
        });
    </script>
</head>

<body>
    <?php include_once ('../includes/sidebar.php'); ?>
    <div class="bg-light pt-4 text-center shadow">
        <h1><strong>Daily Sales Reports</strong></h1>
        <hr>
    </div>
    <div class="container">
        <div class="container shadow rounded">
            <div class="row justify-content-center align-items-center g-2">
                <div id="betweenDates" class="row justify-content-center m-4">
                    <div class="col-md-6">
                        <p>Select the date to display daily sales reports:</p>
                        <!-- Date Search Form -->
                        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="row">
                                <div class="col">
                                    <label for="selectDate" class="form-label">Select Date:</label>
                                    <input type="date" class="form-control" id="selectDate" name="selectDate"
                                        value="<?php echo htmlspecialchars($selectDate); ?>">
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($selectDate)): ?>
                    <div class="cards-container">
                        <div class="card-wrapper">
                            <div class="card">
                                <h5 class="card-title">Total Sales for <?php echo htmlspecialchars($selectDate); ?>:</h5>
                                <p class="card-text">RM<?php echo number_format($totalSales, 2); ?></p>
                            </div>
                        </div>
                        <div class="card-wrapper">
                            <div class="card">
                                <h5 class="card-title">Total Orders for <?php echo htmlspecialchars($selectDate); ?>:</h5>
                                <p class="card-text"><?php echo $totalOrders; ?></p>
                            </div>
                        </div>
                        <div class="card-wrapper">
                            <div class="card">
                                <h5 class="card-title">Average Order Value for <?php echo htmlspecialchars($selectDate); ?>:
                                </h5>
                                <p class="card-text">RM<?php echo number_format($averageOrderValue, 2); ?></p>
                            </div>
                        </div>
                        <div class="card-wrapper">
                            <div class="card">
                                <h5 class="card-title">Top-Selling Products:</h5>
                                <ul>
                                    <?php foreach ($topSellingProducts as $product): ?>
                                        <li><?php echo htmlspecialchars($product['PROD_NAME']) . ' - ' . $product['QUANTITY_SOLD']; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="card-wrapper">
                            <div class="card">
                                <h5 class="card-title">Payment Method Breakdown:</h5>
                                <ul>
                                    <?php foreach ($paymentMethodBreakdown as $method): ?>
                                        <li><?php echo htmlspecialchars($method['PAYMENT_METHOD']) . ' - RM' . number_format($method['TOTAL_SALES'], 2); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <canvas id="paymentMethodChart"></canvas>
                            </div>
                        </div>
                        <div class="card-wrapper">
                            <div class="card">
                                <h5 class="card-title">Order Status Summary:</h5>
                                <ul>
                                    <?php foreach ($orderStatusSummary as $status): ?>
                                        <li><?php echo htmlspecialchars($status['ORDER_STATUS']) . ' - ' . $status['COUNT']; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <canvas id="orderStatusChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <h5 class="card-title">Order Details:</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer Name</th>
                                    <th>Product Name</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderDetails as $detail): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($detail['ORDER_ID']); ?></td>
                                        <td><?php echo htmlspecialchars($detail['CUST_NAME']); ?></td>
                                        <td><?php echo htmlspecialchars($detail['PROD_NAME']); ?></td>
                                        <td><?php echo $detail['QUANTITY']; ?></td>
                                        <td>RM<?php echo number_format($detail['AMOUNT'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    include_once ('../includes/footer.php');
    include_once ('../includes/footer-tag.php');
    ?>
</body>

</html>