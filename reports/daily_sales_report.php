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
    $totalQuantity = 0;
    $sumTotalSales = 0;

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
    SUM(OD.AMOUNT*OD.QUANTITY) AS TOTAL_SALES
FROM
    ORDER_DETAILS OD
    JOIN ORDERS O ON OD.ORDER_ID = O.ORDER_ID
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
        $sql = "SELECT
                    TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD') AS ORDER_DATE,
                    P.PROD_NAME,
                    SUM(OD.QUANTITY) AS QUANTITY_SOLD,
                    RANK() OVER (PARTITION BY TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD') ORDER BY SUM(OD.QUANTITY) DESC) AS RANK
                FROM
                    ORDER_DETAILS OD
                    JOIN PRODUCT P ON OD.PROD_ID = P.PROD_ID
                    JOIN ORDERS O ON OD.ORDER_ID = O.ORDER_ID
                WHERE
                    O.ORDER_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')
                GROUP BY
                    TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD'), P.PROD_NAME
                ORDER BY
                    ORDER_DATE, RANK";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            $date = $row['ORDER_DATE'];
            $rank = $row['RANK'];
            if ($rank <= 5) {
                if (!isset($mostOrderedProductsByDay[$date])) {
                    $mostOrderedProductsByDay[$date] = [];
                }
                $mostOrderedProductsByDay[$date][] = [
                    'PROD_NAME' => $row['PROD_NAME'],
                    'QUANTITY_SOLD' => $row['QUANTITY_SOLD']
                ];
            }
        }

        oci_free_statement($stid);




        // Payment Method Breakdown
        $sql = "SELECT 
                    TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD') AS ORDER_DATE,
                    R.PAYMENT_METHOD, SUM(R.TOTAL_AMOUNT) AS TOTAL_SALES
                FROM 
                    RECEIPT R
                JOIN 
                    ORDERS O ON R.ORDER_ID = O.ORDER_ID
                WHERE 
                    O.ORDER_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')
                GROUP BY 
                    TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD'), R.PAYMENT_METHOD
                ORDER BY 
                    TO_CHAR(O.ORDER_DATE, 'YYYY-MM-DD'), R.PAYMENT_METHOD";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
        oci_execute($stid);

        $paymentMethodData = initializeDataArray($dateRange);
        $paymentMethodDataQR = initializeDataArray($dateRange);
        $paymentMethodDataOnlineTransfer = initializeDataArray($dateRange);

        while ($row = oci_fetch_assoc($stid)) {
            if ($row['PAYMENT_METHOD'] == 'QR') {
                $paymentMethodDataQR[$row['ORDER_DATE']] = $row['TOTAL_SALES'];
            } elseif ($row['PAYMENT_METHOD'] == 'Online Transfer') {
                $paymentMethodDataOnlineTransfer[$row['ORDER_DATE']] = $row['TOTAL_SALES'];
            }
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

    $productSalesReport = [];

    if (!empty($startDate)) {
        // Product Sales Report
        $sql = "SELECT
    P.PROD_NAME,
    SUM(OD.QUANTITY) AS QUANTITY_SOLD,
    AVG(OD.AMOUNT) AS AVG_SALES,
    MAX(OD.AMOUNT / OD.QUANTITY) AS PRICE,
    SUM(OD.AMOUNT) AS TOTAL_SALES
FROM
    ORDER_DETAILS OD
    JOIN PRODUCT P ON OD.PROD_ID = P.PROD_ID
    JOIN ORDERS O ON OD.ORDER_ID = O.ORDER_ID
WHERE
    O.ORDER_DATE BETWEEN TO_DATE(:startDate, 'YYYY-MM-DD') AND TO_DATE(:endDate, 'YYYY-MM-DD')
GROUP BY
    P.PROD_NAME
ORDER BY
    P.PROD_NAME";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            $productSalesReport[] = $row;
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
                const ctx = document.getElementById('paymentMethodChart').getContext('2d');
                const paymentMethodChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode(array_keys($paymentMethodData)); ?>,
                        datasets: [{
                            label: 'QR',
                            data: <?php echo json_encode(array_values($paymentMethodDataQR)); ?>,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            fill: false,
                            tension: 0.1
                        }, {
                            label: 'Online Transfer',
                            data: <?php echo json_encode(array_values($paymentMethodDataOnlineTransfer)); ?>,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            fill: false,
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            },
                            y: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Total Sales Amount'
                                }
                            }
                        }
                    }
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
                var mostOrderedProductsByDay = <?php echo json_encode($mostOrderedProductsByDay); ?>;
                var ctxMostOrderedByDay = document.getElementById('mostOrderedProductsByDayChart').getContext('2d');
                var datasets = [];
                dates.forEach(function (date) {
                    if (mostOrderedProductsByDay[date]) {
                        mostOrderedProductsByDay[date].forEach(function (product, index) {
                            if (!datasets[index]) {
                                datasets[index] = {
                                    label: product.PROD_NAME,
                                    data: Array(dates.length).fill(0),
                                    backgroundColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.2)`,
                                    borderColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 1)`,
                                    borderWidth: 1
                                };
                            }
                            datasets[index].data[dates.indexOf(date)] = product.QUANTITY_SOLD;
                        });
                    }
                });

                var mostOrderedByDayChart = new Chart(ctxMostOrderedByDay, {
                    type: 'bar',
                    data: {
                        labels: dates,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Top 5 Most Ordered Products by Day'
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
        <h2 class="p-3 text-center"><strong>Daily Sales Report & Sales Report By Product</strong></h2>
    </div>
    <div class="container bg-white bg-gradient bg-opacity-75 border rounded shadow-lg p-4">
        <div class="container shadow rounded">
            <div class="row justify-content-center align-items-center g-2">
                <div id="betweenDates" class="row justify-content-center m-4">
                    <div class="col-md-6">
                        <p>Select the start date to display daily sales reports (5 days duration):</p>
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
                    <h4 class="text-center">Sales Report from <?php echo $startDate ?> until <?php echo $endDate ?></h4>
                <?php endif; ?>
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
                        <h5 class="container-title">Most Ordered Products by Day:</h5>
                        <canvas id="mostOrderedProductsByDayChart" height="100px"></canvas>
                    </div>

                    <div class="container bg-white rounded mb-4">
                        <h5 class="container-title">Payment Method Breakdown:</h5>
                        <canvas id="paymentMethodChart" height="100px"></canvas>
                    </div>

                    <div class="container bg-white rounded mb-4">
                        <h5 class="container-title">Order Status Summary:</h5>
                        <canvas id="orderStatusChart" height="100px"></canvas>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        <br>
        <?php if (!empty($startDate)): ?>
            <div class="container bg-white rounded mb-4">
                <h5 class="container-title">Sales Report by Product from <?php echo $startDate ?> until <?php echo $endDate ?>:
                </h5>
                <table class="table">
                    <thead>
                        <tr class="table-primary">
                            <th>Product Name</th>
                            <th>Quantity Sold</th>
                            <th>Average Sales (RM)</th>
                            <th>Price (RM)</th>
                            <th>Total Sales (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productSalesReport as $report): ?>
                            <?php
                            $totalQuantity += $report['QUANTITY_SOLD'];
                            $sumTotalSales += $report['TOTAL_SALES'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['PROD_NAME']); ?></td>
                                <td><?php echo $report['QUANTITY_SOLD']; ?></td>
                                <td>RM<?php echo number_format($report['AVG_SALES'], 2); ?></td>
                                <td>RM<?php echo number_format($report['PRICE'], 2); ?></td>
                                <td>RM<?php echo number_format($report['TOTAL_SALES'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-success">
                            <td><strong>TOTAL</strong></td>
                            <td><strong><?php echo htmlspecialchars($totalQuantity); ?></strong></td>
                            <td colspan="2"></td>
                            <td><strong>RM<?php echo number_format($sumTotalSales, 2); ?></strong></td>
                        </tr>

                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="container bg-white rounded mt-4">
            <h5 class="container-title">Order Details:</h5>
            <input type="text" id="searchProduct" class="form-control" placeholder="Search by product name">
            <p id="totalAmount" class="mt-2">Total Amount: RM0.00</p>
            <table class="table" id="orderDetailsTable">
                <thead>
                    <tr class="table-primary">
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