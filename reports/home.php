<?php require_once '../includes/connection.db.php';

?>
<!DOCTYPE html>
<html>

<head>
    <?php require_once '../includes/header-tag.php'; ?>
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

<header>
    <?php require_once '../includes/sidebar.php'; ?>
</header>

<body>
    <div class="bg-light px-4 text-center shadow">
        <h1><strong>Query Reports</strong></h1>
        <hr>
    </div>
    <div id="top-selling" class="container rounded shadow p-4">
        <h4><strong>
                <center>Most Ordered Products</center>
            </strong></h4>
        <div class="row card-container">
            <?php
            // Query for top 3 best selling products
            $sql = "
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
            $stid = oci_parse($dbconn, $sql);
            oci_execute($stid);

            while ($row = oci_fetch_assoc($stid)) {
                echo '<div class="col-md-4">';
                echo '<div class="card">';
                echo '<div class="card-body">';
                echo '<h5 class="card-title">' . htmlspecialchars($row["PROD_NAME"], ENT_QUOTES, 'UTF-8') . '</h5>';
                echo '<p class="card-text">Total Quantity Sold: ' . htmlspecialchars($row["TOTAL_QUANTITY"], ENT_QUOTES, 'UTF-8') . '</p>';
                echo '<p class="card-text">Total Sales: RM' . htmlspecialchars($row["TOTAL_SALES"], ENT_QUOTES, 'UTF-8') . '</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }

            oci_free_statement($stid);
            ?>
        </div>
    </div>

    <!-- Orders for Products table with toggle -->
    <div id="frozen" class="container rounded shadow p-4 mt-4">
        <h4><strong>
                <center>Orders for Frozen Products (and Freshly Made)</center>
            </strong></h4>
        <div class="d-flex justify-content-center mb-3">
            <button class="btn btn-primary me-2" id="toggleFrozen">Frozen</button>
            <button class="btn btn-secondary" id="toggleFreshlyMade">Freshly Made</button>
        </div>
        <div class="row card-container">
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
    <div id="paymentStatistics" class="container rounded shadow p-4">
        <h4><strong>
                <center>Payment Method Statistics</center>
            </strong></h4>
        <div class="row justify-content-between align-items-end g-2">
            <div class="col-md-6">
                <canvas id="paymentMethodChart"></canvas>
            </div>
            <div class="col-md-6">
                <div class="card-container d-flex flex-column ms-4">
                    <?php
                    // Query for payment method statistics
                    $sql = "
            SELECT
                SUM(CASE WHEN payment_Method = 'Online Transfer' THEN 1 ELSE 0 END) AS TOTAL_INSTANT_TRANSFER,
                SUM(CASE WHEN payment_Method = 'Online Transfer' THEN total_Amount ELSE 0 END) AS TOTAL_AMOUNT_INSTANT_TRANSFER,
                SUM(CASE WHEN payment_Method = 'QR' THEN 1 ELSE 0 END) AS TOTAL_QR,
                SUM(CASE WHEN payment_Method = 'QR' THEN total_Amount ELSE 0 END) AS TOTAL_AMOUNT_QR
            FROM RECEIPT
            ";
                    $stid = oci_parse($dbconn, $sql);
                    oci_execute($stid);

                    if ($row = oci_fetch_assoc($stid)) {
                        $totalInstantTransfer = htmlspecialchars($row["TOTAL_INSTANT_TRANSFER"], ENT_QUOTES, 'UTF-8');
                        $totalAmountInstantTransfer = htmlspecialchars($row["TOTAL_AMOUNT_INSTANT_TRANSFER"], ENT_QUOTES, 'UTF-8');
                        $totalQR = htmlspecialchars($row["TOTAL_QR"], ENT_QUOTES, 'UTF-8');
                        $totalAmountQR = htmlspecialchars($row["TOTAL_AMOUNT_QR"], ENT_QUOTES, 'UTF-8');

                        echo '<div class="col-md-6">';
                        echo '<div class="card">';
                        echo '<div class="card-body">';
                        echo '<h5 class="card-title">Total Instant Transfers</h5>';
                        echo '<p class="card-text">Total Number: ' . $totalInstantTransfer . '</p>';
                        echo '<p class="card-text">Total Amount: RM' . $totalAmountInstantTransfer . '</p>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';

                        echo '<div class="col-md-6">';
                        echo '<div class="card">';
                        echo '<div class="card-body">';
                        echo '<h5 class="card-title">Total QR Payments</h5>';
                        echo '<p class="card-text">Total Number: ' . $totalQR . '</p>';
                        echo '<p class="card-text">Total Amount: RM' . $totalAmountQR . '</p>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }

                    oci_free_statement($stid);
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
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

</html>