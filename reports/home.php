<?php
require_once '../includes/connection.db.php';

function check_oci_error($resource)
{
    if (!$resource) {
        $e = oci_error();
        throw new Exception($e['message']);
    }
}

// Query for top 3 best selling products
$sqlTopProducts = "
    SELECT * FROM (
        SELECT P.PROD_ID, P.PROD_NAME, SUM(OD.quantity) AS TOTAL_QUANTITY, SUM(R.total_Amount) AS TOTAL_SALES
        FROM PRODUCT P
        JOIN ORDER_DETAILS OD 
        ON P.PROD_ID = OD.PROD_ID
        JOIN RECEIPT R 
        ON OD.order_ID = R.order_ID
        GROUP BY P.PROD_ID, P.PROD_NAME
        ORDER BY TOTAL_QUANTITY DESC
    ) WHERE ROWNUM <= 3
";

$stidTopProducts = oci_parse($dbconn, $sqlTopProducts);
check_oci_error($stidTopProducts);
oci_execute($stidTopProducts);
check_oci_error($stidTopProducts);

// Query for payment method statistics
$sqlPaymentStats = "
    SELECT
        SUM(CASE WHEN payment_Method = 'Online Transfer' THEN 1 ELSE 0 END) AS TOTAL_INSTANT_TRANSFER,
        SUM(CASE WHEN payment_Method = 'Online Transfer' THEN total_Amount ELSE 0 END) AS TOTAL_AMOUNT_INSTANT_TRANSFER,
        SUM(CASE WHEN payment_Method = 'QR' THEN 1 ELSE 0 END) AS TOTAL_QR,
        SUM(CASE WHEN payment_Method = 'QR' THEN total_Amount ELSE 0 END) AS TOTAL_AMOUNT_QR
    FROM RECEIPT
    ";

$stidPaymentStats = oci_parse($dbconn, $sqlPaymentStats);
check_oci_error($stidPaymentStats);
oci_execute($stidPaymentStats);
check_oci_error($stidPaymentStats);

$paymentStats = oci_fetch_assoc($stidPaymentStats);

$totalInstantTransfer = htmlspecialchars($paymentStats["TOTAL_INSTANT_TRANSFER"], ENT_QUOTES, 'UTF-8');
$totalAmountInstantTransfer = htmlspecialchars($paymentStats["TOTAL_AMOUNT_INSTANT_TRANSFER"], ENT_QUOTES, 'UTF-8');
$totalQR = htmlspecialchars($paymentStats["TOTAL_QR"], ENT_QUOTES, 'UTF-8');
$totalAmountQR = htmlspecialchars($paymentStats["TOTAL_AMOUNT_QR"], ENT_QUOTES, 'UTF-8');

oci_free_statement($stidPaymentStats);
oci_free_statement($stidTopProducts);

require_once '../includes/header-tag.php';
?>

<!DOCTYPE html>
<html>

<head>
    <style>
        .card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 20px;
            margin: 10px;
            text-align: center;
        }

        .card-title {
            font-size: 1.25rem;
            margin-bottom: 10px;
        }

        .card-text {
            font-size: 1rem;
        }

        #paymentMethodChart {
            max-width: 700px;
            max-height: 700px;
        }

        .btn-search {
            height: 100%;
            border-radius: 30%;
        }
    </style>
</head>

<body>
    <header>
        <?php require_once '../includes/sidebar.php'; ?>
    </header>
    <div class="bg-white bg-gradient shadow">
        <h2 class="p-3 text-center"><strong>Query Reports</strong></h2>
    </div>
    <div id="top-selling" class="container bg-white bg-opacity-75 rounded shadow p-4">
        <h4><strong>
                <center>Most Ordered Products</center>
            </strong></h4>
        <div class="row card-container">
            <?php
            $stidTopProducts = oci_parse($dbconn, $sqlTopProducts);
            check_oci_error($stidTopProducts);
            oci_execute($stidTopProducts);
            check_oci_error($stidTopProducts);
            while ($row = oci_fetch_assoc($stidTopProducts)):
                ?>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <p class="card-text">
                                <strong><?php echo htmlspecialchars($row["PROD_ID"], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                <?php echo htmlspecialchars($row["PROD_NAME"], ENT_QUOTES, 'UTF-8'); ?><br>
                                Total Quantity Sold:
                                <?php echo htmlspecialchars($row["TOTAL_QUANTITY"], ENT_QUOTES, 'UTF-8'); ?><br>
                                Total Sales:
                                RM<?php echo htmlspecialchars($row["TOTAL_SALES"], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <div class="table p-4">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th scope="col">Product Name</th>
                            <th scope="col">Total Quantity Sold</th>
                            <th scope="col">Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stidTopProducts = oci_parse($dbconn, $sqlTopProducts);
                        check_oci_error($stidTopProducts);
                        oci_execute($stidTopProducts);
                        check_oci_error($stidTopProducts);
                        while ($row = oci_fetch_assoc($stidTopProducts)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["PROD_ID"], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row["PROD_NAME"], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row["TOTAL_QUANTITY"], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>RM<?php echo htmlspecialchars($row["TOTAL_SALES"], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <br>
    <div id="productCategory" class="container bg-white bg-opacity-75 rounded shadow p-4">
        <h4><strong>
                <center>Orders By Product Category</center>
            </strong></h4>
        <div class="d-flex justify-content-center mb-3">
            <button class="btn btn-primary me-2" id="toggleFrozen">Frozen</button>
            <button class="btn btn-secondary" id="toggleFreshlyMade">Freshly Made</button>
        </div>
        <div class="row card-container p-4">
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th scope="col">Order ID</th>
                        <th scope="col">Customer Name</th>
                        <th scope="col">Delivery Address</th>
                        <th scope="col">Product Name</th>
                        <th scope="col">Quantity</th>
                        <th scope="col">Total Amount (RM)</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <!-- Orders will be dynamically populated here -->
                </tbody>
            </table>
        </div>
    </div>
    <br>
    <div id="qrOnlineTransfer" class="container bg-white bg-opacity-75 rounded shadow">
        <h4><strong>
                <center>Count of QR vs Online Transfer</center>
            </strong></h4>
        <div class="row justify-content-between align-items-end g-2">
            <div class="col-md-6">
                <canvas id="paymentMethodChart"></canvas>
            </div>
            <div class="col-md-6">
                <div class="card-container d-flex flex-column ms-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Instant Transfers</h5>
                                <p class="card-text">Total Number: <?php echo $totalInstantTransfer; ?></p>
                                <p class="card-text">Total Amount:
                                    RM<?php echo number_format($totalAmountInstantTransfer, 2); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total QR Payments</h5>
                                <p class="card-text">Total Number: <?php echo $totalQR; ?></p>
                                <p class="card-text">Total Amount: RM<?php echo number_format($totalAmountQR, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table p-4">
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th scope="col">Total Instant Transfer</th>
                        <th scope="col">Total Amount Transfer Transfer</th>
                        <th scope="col">Total QR</th>
                        <th scope="col">Total Amount QR</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="">
                        <td scope="row"><?php echo $totalInstantTransfer; ?></td>
                        <td><?php echo number_format($totalAmountInstantTransfer, 2); ?></td>
                        <td><?php echo $totalQR; ?></td>
                        <td><?php echo number_format($totalAmountQR, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // JavaScript to handle toggle and update table content
        document.getElementById('toggleFrozen').addEventListener('click', function () {
            fetchOrders('frozen');
        });

        document.getElementById('toggleFreshlyMade').addEventListener('click', function () {
            fetchOrders('freshly_made');
        });

        function fetchOrders(productType) {
            fetch('fetch_orders.php?type=' + productType)
                .then(response => response.json())
                .then(data => {
                    const ordersTableBody = document.getElementById('ordersTableBody');
                    ordersTableBody.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(order => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${order.ORDER_ID}</td>
                                <td>${order.CUST_NAME}</td>
                                <td>${order.DELIVERY_ADDRESS}</td>
                                <td>${order.PROD_NAME}</td>
                                <td>${order.QUANTITY}</td>
                                <td>${order.TOTAL_AMOUNT}</td>
                            `;
                            ordersTableBody.appendChild(row);
                        });
                    } else {
                        ordersTableBody.innerHTML = '<tr><td colspan="6">No orders found</td></tr>';
                    }
                });
        }

        // Initialize with frozen products
        fetchOrders('frozen');

        // Chart.js script to create the bar graph
        const ctx = document.getElementById('paymentMethodChart').getContext('2d');
        const paymentMethodChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Instant Transfer', 'QR Payments'],
                datasets: [{
                    label: 'Total Number of Transactions',
                    data: [<?php echo $totalInstantTransfer; ?>, <?php echo $totalQR; ?>],
                    backgroundColor: ['#4e73df', '#1cc88a'],
                    borderColor: ['#4e73df', '#1cc88a'],
                    borderWidth: 1
                }, {
                    label: 'Total Amount (RM)',
                    data: [<?php echo $totalAmountInstantTransfer; ?>, <?php echo $totalAmountQR; ?>],
                    backgroundColor: ['#36b9cc', '#f6c23e'],
                    borderColor: ['#36b9cc', '#f6c23e'],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php require_once '../includes/footer.php'; ?>
    <?php require_once '../includes/footer-tag.php'; ?>
</body>

</html>