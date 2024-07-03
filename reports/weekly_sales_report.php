<?php
require_once '../includes/connection.db.php';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
    $totalSales = 0;
    $totalOrders = 0;
    $averageOrderValue = 0;
    $topSellingProducts = [];
    $paymentMethodBreakdown = [];
    $orderStatusSummary = [];
    $orderDetails = [];

    if (!empty($startDate) && !empty($endDate)) {
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
                    O.ORDER_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')
                GROUP BY 
                    O.ORDER_DATE";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
        oci_execute($stid);

        if ($row = oci_fetch_assoc($stid)) {
            $totalSales = $row['TOTAL_SALES'];
            $totalOrders = $row['TOTAL_ORDERS'];
            $averageOrderValue = $row['AVERAGE_ORDER_VALUE'];
        }

        oci_free_statement($stid);

        // Top-Selling Products
        $sql = "SELECT * FROM (
    SELECT P.PROD_NAME, SUM(OD.QUANTITY) AS QUANTITY_SOLD
    FROM ORDER_DETAILS OD
    JOIN PRODUCT P ON OD.PROD_ID = P.PROD_ID
    JOIN ORDERS O ON OD.ORDER_ID = O.ORDER_ID
    WHERE O.ORDER_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')
    GROUP BY P.PROD_NAME
    ORDER BY QUANTITY_SOLD DESC
) WHERE ROWNUM <= 3
";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
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
                    O.ORDER_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')
                GROUP BY 
                    R.PAYMENT_METHOD";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
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
                    O.ORDER_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')
                GROUP BY 
                    O.ORDER_STATUS";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
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
                    O.ORDER_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
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
            <?php if (!empty($startDate) && !empty($endDate)): ?>
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
        <h1><strong>Weekly Sales Reports</strong></h1>
        <hr>
    </div>
    <div class="container">
        <div class="container shadow rounded">
            <div class="row justify-content-center align-items-center g-2">
                <div id="betweenDates" class="row justify-content-center m-4">
                    <div class="col-md-6">
                        <p>Select the date range to display weekly sales reports:</p>
                        <!-- Date Range Search Form -->
                        <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="form-group">
                                <label for="startDate">Start Date:</label>
                                <input type="date" class="form-control" id="startDate" name="startDate" required
                                    value="<?php echo $startDate; ?>">
                            </div>
                            <div class="form-group">
                                <label for="endDate">End Date:</label>
                                <input type="date" class="form-control" id="endDate" name="endDate" required
                                    value="<?php echo $endDate; ?>">
                            </div>
                            <button type="submit" class="btn btn-primary mt-2">Get Report</button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (!empty($startDate) && !empty($endDate)): ?>
                <div class="cards-container">
                    <div class="card-wrapper">
                        <div class="card">
                            <h3 class="card-title">Total Sales</h3>
                            <p>RM<?php echo number_format($totalSales, 2); ?></p>
                        </div>
                    </div>
                    <div class="card-wrapper">
                        <div class="card">
                            <h3 class="card-title">Total Orders</h3>
                            <p><?php echo $totalOrders; ?></p>
                        </div>
                    </div>
                    <div class="card-wrapper">
                        <div class="card">
                            <h3 class="card-title">Average Order Value</h3>
                            <p>RM<?php echo number_format($averageOrderValue, 2); ?></p>
                        </div>
                    </div>
                    <div class="card-wrapper">
                        <div class="card">
                            <h3 class="card-title">Top-Selling Products</h3>
                            <ul>
                                <?php foreach ($topSellingProducts as $product): ?>
                                    <li><?php echo htmlspecialchars($product['PROD_NAME']); ?>:
                                        <?php echo $product['QUANTITY_SOLD']; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="card-wrapper">
                        <div class="card">
                            <h3 class="card-title">Payment Method Breakdown</h3>
                            <canvas id="paymentMethodChart"></canvas>
                        </div>
                    </div>
                    <div class="card-wrapper">
                        <div class="card">
                            <h3 class="card-title">Order Status Summary</h3>
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">Detailed Order List</h3>
                    <table class="table table-striped">
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
                            <?php foreach ($orderDetails as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['ORDER_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($order['CUST_NAME']); ?></td>
                                    <td><?php echo htmlspecialchars($order['PROD_NAME']); ?></td>
                                    <td><?php echo htmlspecialchars($order['QUANTITY']); ?></td>
                                    <td><?php echo number_format($order['AMOUNT'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>