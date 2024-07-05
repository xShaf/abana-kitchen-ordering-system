<?php
require_once '../includes/connection.db.php';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
    $endDate = date('Y-m-d', strtotime($startDate . ' +4 days')); // Adjusted to include 5 days total

    $totalSalesByDay = [];
    $totalOrdersByDay = [];
    $totalOrders = 0;
    $averageOrderValue = 0;
    $mostOrderedProducts = [];
    $paymentMethodBreakdown = [];
    $orderStatusSummary = [];
    $orderDetails = [];

    if (!empty($startDate)) {
        // Generate date range array
        $dateRange = [];
        for ($i = 0; $i < 5; $i++) {
            $dateRange[] = date('Y-m-d', strtotime($startDate . " +$i days"));
        }

        // Helper function to initialize data arrays with zero values
        function initializeDataArray($dateRange)
        {
            $dataArray = [];
            foreach ($dateRange as $date) {
                $dataArray[$date] = 0;
            }
            return $dataArray;
        }

        $totalSalesByDay = initializeDataArray($dateRange);
        $totalOrdersByDay = initializeDataArray($dateRange);

        // Total Sales by Day
        $sql = "SELECT 
                    TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD') AS ORDER_DATE,
                    SUM(R.TOTAL_AMOUNT) AS TOTAL_SALES
                FROM 
                    ORDERS O
                JOIN 
                    RECEIPT R ON O.ORDER_ID = R.ORDER_ID
                WHERE 
                    O.ORDER_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')
                GROUP BY 
                    TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD')
                ORDER BY 
                    TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD')";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            $totalSalesByDay[$row['ORDER_DATE']] = $row['TOTAL_SALES'];
        }

        oci_free_statement($stid);

        // Total Orders by Day
        $sql = "SELECT 
                    TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD') AS ORDER_DATE,
                    COUNT(O.ORDER_ID) AS TOTAL_ORDERS
                FROM 
                    ORDERS O
                WHERE 
                    O.ORDER_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')
                GROUP BY 
                    TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD')
                ORDER BY 
                    TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD')";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            $totalOrdersByDay[$row['ORDER_DATE']] = $row['TOTAL_ORDERS'];
        }

        oci_free_statement($stid);

        $averageOrderValueByDay = initializeDataArray($dateRange);

        $sql = "SELECT 
            TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD') AS ORDER_DATE,
            AVG(R.TOTAL_AMOUNT) AS AVG_ORDER_VALUE
        FROM 
            ORDERS O
        JOIN 
            RECEIPT R ON O.ORDER_ID = R.ORDER_ID
        WHERE 
            O.ORDER_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')
        GROUP BY 
            TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD')
        ORDER BY 
            TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD')";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            $averageOrderValueByDay[$row['ORDER_DATE']] = $row['AVG_ORDER_VALUE'];
        }

        oci_free_statement($stid);

        // Most Ordered Products by Date Range
        $mostOrderedProductsByPeriod = [];

        $sql = "SELECT 
            P.PROD_NAME, SUM(OD.QUANTITY) AS QUANTITY_SOLD,
            TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD') AS ORDER_DATE
        FROM 
            ORDER_DETAILS OD
        JOIN 
            PRODUCT P ON OD.PROD_ID = P.PROD_ID
        JOIN 
            ORDERS O ON OD.ORDER_ID = O.ORDER_ID
        WHERE 
            O.ORDER_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')
        GROUP BY 
            P.PROD_NAME, TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD')
        ORDER BY 
            ORDER_DATE, QUANTITY_SOLD DESC";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            $date = $row['ORDER_DATE'];
            if (!isset($mostOrderedProductsByPeriod[$date])) {
                $mostOrderedProductsByPeriod[$date] = [];
            }
            $mostOrderedProductsByPeriod[$date][] = [
                'PROD_NAME' => $row['PROD_NAME'],
                'QUANTITY_SOLD' => $row['QUANTITY_SOLD']
            ];
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
        .container {
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            height: 100%;
        }

        .container-title {
            margin-bottom: 20px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            <?php if (!empty($startDate)): ?>
                // Data for Total Sales by Day Chart
                var dates = <?php echo json_encode(array_keys($totalSalesByDay)); ?>;
                var sales = <?php echo json_encode(array_values($totalSalesByDay)); ?>;

                // Total Sales by Day Chart
                var ctxTotalSales = document.getElementById('totalSalesChart').getContext('2d');
                var totalSalesChart = new Chart(ctxTotalSales, {
                    type: 'bar',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'Total Sales (RM)',
                            data: sales,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
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
                                text: 'Total Sales by Day'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    },
                });

                // Data for Total Orders by Day Chart
                var orders = <?php echo json_encode(array_values($totalOrdersByDay)); ?>;

                // Total Orders by Day Chart
                var ctxTotalOrders = document.getElementById('totalOrdersChart').getContext('2d');
                var totalOrdersChart = new Chart(ctxTotalOrders, {
                    type: 'bar',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'Total Orders',
                            data: orders,
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
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
                                text: 'Total Orders by Day'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    },
                });

                // Data for Average Order Value by Day Chart
                var averageOrderValues = <?php echo json_encode(array_values($averageOrderValueByDay)); ?>;

                // Average Order Value by Day Chart
                var ctxAvgOrderValue = document.getElementById('averageOrderValueChart').getContext('2d');
                var avgOrderValueChart = new Chart(ctxAvgOrderValue, {
                    type: 'bar',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'Average Order Value (RM)',
                            data: averageOrderValues,
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1
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
                                text: 'Average Order Value by Day'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    },
                });

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
                        responsive: false,
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

                // Data for Most Ordered Products by Period Chart
                var productNamesByPeriod = <?php echo json_encode(array_keys($mostOrderedProductsByPeriod)); ?>;
                var productQuantitiesByPeriod = <?php echo json_encode(array_values($mostOrderedProductsByPeriod)); ?>;

                // Most Ordered Products by Period Chart
                var ctxMostOrderedByPeriod = document.getElementById('mostOrderedProductsByPeriodChart').getContext('2d');
                var mostOrderedByPeriodChart = new Chart(ctxMostOrderedByPeriod, {
                    type: 'bar',
                    data: {
                        labels: productNamesByPeriod,
                        datasets: productQuantitiesByPeriod.map(function (products) {
                            return {
                                label: products[0]['PROD_NAME'], // Assuming the first product in each array is the most ordered
                                data: products.map(function (product) {
                                    return product['QUANTITY_SOLD'];
                                }),
                                backgroundColor: '#FF6384', // Random color for each dataset
                                borderColor: '#FF6384',
                                borderWidth: 1
                            };
                        })
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Most Ordered Products by 5-Day Period'
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

            // Search and sum functionality for order details
            document.getElementById('searchProduct').addEventListener('input', function () {
                var searchValue = this.value.toLowerCase();
                var rows = document.querySelectorAll('#orderDetailsTable tbody tr');
                var totalAmount = 0;

                rows.forEach(function (row) {
                    var productName = row.querySelector('td:nth-child(3)').innerText.toLowerCase();
                    var amount = parseFloat(row.querySelector('td:nth-child(5)').innerText.replace('RM', ''));
                    if (productName.includes(searchValue)) {
                        row.style.display = '';
                        totalAmount += amount;
                    } else {
                        row.style.display = 'none';
                    }
                });

                document.getElementById('totalAmount').innerText = 'Total Amount: RM' + totalAmount.toFixed(2);
            });
        });

    </script>
</head>

<body>
    <?php include_once ('../includes/sidebar.php'); ?>
    <div class="bg-white gradient shadow">
        <h2 class="p-3 text-center"><strong>Daily Sales Reports</strong></h2>
    </div>
    <div class="container bg-white bg-gradient bg-opacity-75 border rounded shadow-lg p-4">
        <div class="container shadow rounded">
            <div class="row justify-content-center align-items-center g-2">
                <div id="betweenDates" class="row justify-content-center m-4">
                    <div class="col-md-6">
                        <p>Select the start date to display daily sales reports:</p>
                        <!-- Date Search Form -->
                        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="row">
                                <div class="col">
                                    <label for="startDate" class="form-label">Select Start Date:</label>
                                    <input type="date" class="form-control" id="startDate" name="startDate"
                                        value="<?php echo htmlspecialchars($startDate); ?>">
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($startDate)): ?>
                    <div class="container bg-white rounded mb-4">
                        <h5 class="container-title">Total Sales by Day:</h5>
                        <canvas id="totalSalesChart" height="100px"></canvas>
                    </div>

                    <div class="container bg-white rounded mb-4">
                        <h5 class="container-title">Total Orders by Day:</h5>
                        <canvas id="totalOrdersChart" height="100px"></canvas>
                    </div>

                    <div class="container bg-white rounded mb-4">
                        <h5 class="container-title">Average Order Value by Day:</h5>
                        <canvas id="averageOrderValueChart" height="100px"></canvas>
                    </div>

                    <div class="container bg-white rounded mb-4">
                        <h5 class="container-title">Most Ordered Products by 5-Day Period:</h5>
                        <canvas id="mostOrderedProductsByPeriodChart" height="100px"></canvas>
                    </div>

                    <div class="container bg-white rounded mb-4">
                        <h5 class="container-title">Payment Method Breakdown:</h5>
                        <ul>
                            <?php foreach ($paymentMethodBreakdown as $method): ?>
                                <li><?php echo htmlspecialchars($method['PAYMENT_METHOD']) . ' - RM' . number_format($method['TOTAL_SALES'], 2); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <canvas id="paymentMethodChart" height="300px"></canvas>
                    </div>

                    <div class="container bg-white rounded mb-4">
                        <h5 class="container-title">Order Status Summary:</h5>
                        <ul>
                            <?php foreach ($orderStatusSummary as $status): ?>
                                <li><?php echo htmlspecialchars($status['ORDER_STATUS']) . ' - ' . $status['COUNT']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <canvas id="orderStatusChart" height="100px"></canvas>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        <div class="container bg-white rounded mt-4">
            <h5 class="container-title">Order Details:</h5>
            <input type="text" id="searchProduct" class="form-control" placeholder="Search by product name">
            <p id="totalAmount" class="mt-2">Total Amount: RM0.00</p>
            <table class="table" id="orderDetailsTable">
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
    </div>

    <?php
    include_once ('../includes/footer.php');
    include_once ('../includes/footer-tag.php');
    ?>
</body>

</html>