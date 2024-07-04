<?php
require_once ('../includes/connection.db.php');

// Define the order ID to fetch
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;

// Query to fetch data
$sql = "SELECT o.order_id, o.order_date, o.staff_id, TO_CHAR(o.ORDER_TIME, 'HH24:MI:ss') ORDER_TIME, o.required_date, TO_CHAR(o.REQUIRED_TIME, 'HH24:MI:ss') REQUIRED_TIME,
               o.cust_name, o.cust_phonenum, o.delivery_address, o.order_remarks,
               r.receipt_id, r.recp_date, TO_CHAR(R.RECP_TIME, 'HH24:MI:ss') RECP_TIME, r.payment_method, r.total_amount,
               od.prod_id, od.quantity, od.amount, od.request, p.prod_name 
        FROM orders o
        JOIN receipt r ON o.order_id = r.order_id
        JOIN order_details od ON o.order_id = od.order_id
        JOIN product p ON od.prod_id = p.prod_id
        WHERE o.order_id = :order_id";

// Prepare the statement
$stmt = oci_parse($dbconn, $sql);

if (!$stmt) {
    $e = oci_error($dbconn);
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Bind the order_id parameter
oci_bind_by_name($stmt, ":order_id", $order_id);

// Execute the query
$result = oci_execute($stmt);

if (!$result) {
    $e = oci_error($stmt);
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Initialize variables to store receipt information
$receipt_info = [];
$order_details = [];

// Fetch the first row to get receipt information
$row = oci_fetch_assoc($stmt);

if ($row) {
    // Store receipt information in a separate array
    $receipt_info = [
        'RECEIPT_ID' => $row['RECEIPT_ID'],
        'RECP_DATE' => $row['RECP_DATE'],
        'RECP_TIME' => $row['RECP_TIME'],
        'ORDER_ID' => $row['ORDER_ID'],
        'STAFF_ID' => $row['STAFF_ID'],
        'ORDER_DATE' => $row['ORDER_DATE'],
        'ORDER_TIME' => $row['ORDER_TIME'],
        'CUST_NAME' => $row['CUST_NAME'],
        'CUST_PHONENUM' => $row['CUST_PHONENUM'],
        'DELIVERY_ADDRESS' => $row['DELIVERY_ADDRESS'],
        'REQUIRED_DATE' => $row['REQUIRED_DATE'],
        'REQUIRED_TIME' => $row['REQUIRED_TIME'],
        'ORDER_REMARKS' => $row['ORDER_REMARKS'],
        'PAYMENT_METHOD' => $row['PAYMENT_METHOD'],
        'TOTAL_AMOUNT' => $row['TOTAL_AMOUNT']
    ];

    // Loop through all rows to fetch order details
    do {
        $order_details[] = [
            'PROD_ID' => $row['PROD_ID'],
            'PROD_NAME' => $row['PROD_NAME'],
            'REQUEST' => $row['REQUEST'],
            'QUANTITY' => $row['QUANTITY'],
            'AMOUNT' => $row['AMOUNT']
        ];
    } while ($row = oci_fetch_assoc($stmt));
}

// Close the statement and connection
oci_free_statement($stmt);
oci_close($dbconn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f2f2f2;
        }

        .container {
            width: 80%;
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            margin: 5px 0;
            color: #333;
        }

        .header p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }

        .logo {
            display: block;
            margin: 0 auto;
            width: 120px;
            height: auto;
        }

        .receipt-info {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ccc;
        }

        .receipt-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }

        .order-details {
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .order-details p {
            margin: 5px 0;
            font-size: 14px;
            color: #333;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .product-table th,
        .product-table td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
            font-size: 14px;
            color: #333;
        }

        .product-table th {
            background-color: #f2f2f2;
        }

        .notes {
            margin-top: 20px;
        }

        .notes p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
        }

        .footer p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }

        @media print {
            button {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Abana's Kitchen</h1>
            <p>68, Jalan 2/92B, Taman Kobena, Cheras, 56100 Kuala Lumpur</p>
            <img src="../assets/images/logo.png" class="logo" alt="Company Logo">
        </div>

        <div class="receipt-info">
            <div class="row justify-content-between align-items-start g-2">
                <div class="col">
                    <p><strong>Receipt ID:</strong> <?php echo $receipt_info['RECEIPT_ID']; ?></p>
                    <p><strong>Receipt Date:</strong> <?php echo $receipt_info['RECP_DATE']; ?></p>
                    <p><strong>Receipt Time:</strong> <?php echo $receipt_info['RECP_TIME']; ?></p>
                </div>
                <div class="col">
                    <p><strong>Order ID:</strong> <?php echo $receipt_info['ORDER_ID']; ?></p>
                    <p><strong>Order Date:</strong> <?php echo $receipt_info['ORDER_DATE']; ?></p>
                    <p><strong>Order Time:</strong> <?php echo $receipt_info['ORDER_TIME']; ?></p>
                </div>
            </div>
        </div>

        <div class="order-details">
            <div class="row justify-content-between align-items-start g-2">
                <div class="col">
                    <p><strong>Name:</strong> <?php echo $receipt_info['CUST_NAME']; ?></p>
                    <p><strong>Phone:</strong> <?php echo $receipt_info['CUST_PHONENUM']; ?></p>
                    <p><strong>Address:</strong> <?php echo $receipt_info['DELIVERY_ADDRESS']; ?></p>
                </div>
                <div class="col">
                    <p><strong>Required Date:</strong> <?php echo $receipt_info['REQUIRED_DATE']; ?></p>
                    <p><strong>Required Time:</strong> <?php echo $receipt_info['REQUIRED_TIME']; ?></p>
                    <p><strong>Order Remarks:</strong> <?php echo $receipt_info['ORDER_REMARKS']; ?></p>
                    <p><strong>Payment Method:</strong> <?php echo $receipt_info['PAYMENT_METHOD']; ?></p>
                    <br>
                </div>
            </div>

            <table class="product-table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Request</th>
                        <th>Quantity</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_details as $detail): ?>
                        <tr>
                            <td><?php echo $detail['PROD_ID']; ?></td>
                            <td><?php echo $detail['PROD_NAME']; ?></td>
                            <td><?php echo (is_null($detail['REQUEST']) || strtolower($detail['REQUEST']) === 'null') ? '-' : $detail['REQUEST']; ?>
                            </td>
                            <td><?php echo $detail['QUANTITY']; ?></td>
                            <td><?php echo 'RM ' . $detail['AMOUNT']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="4">Total Amount</td>
                        <td><?php echo 'RM ' . $receipt_info['TOTAL_AMOUNT']; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="notes">
            <p>Thank you for your purchase!</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Abana's Kitchen. All rights reserved.</p>
            <button onclick="printReceiptContainer()">Print Receipt</button>
        </div>

    </div>
</body>
<script>
    function printReceiptContainer() {
        var containerContent = document.querySelector('.container').innerHTML;
        var printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Receipt</title>');

        // Get the CSS styles from the original page
        var styles = '';
        var styleSheets = document.styleSheets;
        for (var i = 0; i < styleSheets.length; i++) {
            var cssRules = styleSheets[i].cssRules || styleSheets[i].rules;
            for (var j = 0; j < cssRules.length; j++) {
                styles += cssRules[j].cssText;
            }
        }

        // Write the CSS styles to the new window
        printWindow.document.write('<style>' + styles + '</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(containerContent);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
</script>


</html>