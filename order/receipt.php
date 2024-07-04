<?php
session_start();
require_once ("../includes/connection.db.php");

// Redirect if staff ID is not set
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
}

// Redirect if customer details are not set
if (!isset($_SESSION['customer_details'])) {
    header('Location: customer_details.php');
    exit;
}

// Redirect if cart is empty or not set
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: product_selection.php');
    exit;
}

// Check if payment method is set
if (!isset($_POST['payment_method']) || empty($_POST['payment_method'])) {
    header('Location: order_form.php');
    exit;
}

// Retrieve session data
$customer_details = $_SESSION['customer_details'];
$selected_products = $_SESSION['cart'];
$payment_method = $_POST['payment_method'];

// Initialize total amount
$totalAmount = 0;

// Prepare order date and preparation date
$requiredDateTime = $customer_details['requiredDate'] . ' ' . $customer_details['requiredTime'];
$TwoDaysBefore = date("Y-m-d", strtotime($customer_details['requiredDate'] . " -2 days"));

// Insert order into database
$sql_order = "INSERT INTO orders (ORDER_ID, STAFF_ID, CUST_NAME, CUST_PHONENUM, DELIVERY_ADDRESS, ORDER_DATE, ORDER_TIME, REQUIRED_DATE, REQUIRED_TIME, ORDER_REMARKS, ORDER_STATUS)
              VALUES ('N'||LPAD(orders_id_seq.NEXTVAL, 4, '0'), :staff_id, :cust_name, :cust_phone, :cust_address, TRUNC(SYSTIMESTAMP), SYSTIMESTAMP, TO_DATE(:required_date, 'YYYY-MM-DD'), TO_TIMESTAMP(:required_timestamp, 'YYYY-MM-DD HH24:MI:SS'), :order_remarks, 'PENDING')
              RETURNING ORDER_ID INTO :order_id";

$stid_order = oci_parse($dbconn, $sql_order);

$order_id = null;
oci_bind_by_name($stid_order, ':order_id', $order_id, 40);
oci_bind_by_name($stid_order, ':staff_id', $_SESSION['staff_id']);
oci_bind_by_name($stid_order, ':cust_name', $customer_details['cust_name']);
oci_bind_by_name($stid_order, ':cust_phone', $customer_details['cust_phone']);
oci_bind_by_name($stid_order, ':cust_address', $customer_details['cust_address']);
oci_bind_by_name($stid_order, ':required_date', $customer_details['requiredDate']);
oci_bind_by_name($stid_order, ':required_timestamp', $requiredDateTime);
oci_bind_by_name($stid_order, ':order_remarks', $customer_details['remarks']);

oci_execute($stid_order);

// Insert each product into order_details
$preparation_date = date("Y-m-d", strtotime($customer_details['requiredDate'] . " -2 days"));

$sql_order_details = "INSERT INTO order_details (orderlist_id, prod_id, order_id, quantity, amount, request, preparation_date)
                       VALUES ('L'||LPAD(orderlist_id_seq.NEXTVAL, 3, '0'), :product_id, :order_id, :quantity, :unit_price, :request, TO_DATE(:preparation_date, 'YYYY-MM-DD'))";
$stid_order_details = oci_parse($dbconn, $sql_order_details);

foreach ($selected_products as $prod_id => $product) {
    $amount = $product['quantity'] * $product['price'];
    $totalAmount += $amount;

    oci_bind_by_name($stid_order_details, ':product_id', $prod_id);
    oci_bind_by_name($stid_order_details, ':order_id', $order_id);
    oci_bind_by_name($stid_order_details, ':quantity', $product['quantity']);
    oci_bind_by_name($stid_order_details, ':unit_price', $product['price']);
    oci_bind_by_name($stid_order_details, ':request', $product['request']);
    oci_bind_by_name($stid_order_details, ':preparation_date', $preparation_date, -1);

    oci_execute($stid_order_details);
}

// Insert data into receipt table
$sql_receipt = "INSERT INTO receipt (RECEIPT_ID, ORDER_ID, RECP_DATE, RECP_TIME, PAYMENT_METHOD, TOTAL_AMOUNT)
                 VALUES ('R'||LPAD(receipt_id_seq.NEXTVAL, 4, '0'), :order_id, TRUNC(SYSTIMESTAMP), SYSTIMESTAMP, :payment_method, :total_amount)";
$stid_receipt = oci_parse($dbconn, $sql_receipt);

oci_bind_by_name($stid_receipt, ':order_id', $order_id);
oci_bind_by_name($stid_receipt, ':payment_method', $payment_method);
oci_bind_by_name($stid_receipt, ':total_amount', $totalAmount);

oci_execute($stid_receipt);
$_SESSION['order_id'] = $order_id;

oci_free_statement($stid_order);
oci_free_statement($stid_order_details);
oci_free_statement($stid_receipt);
oci_close($dbconn);

//  Clear the session
unset($_SESSION['customer_details']);
unset($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include ("../includes/header-tag.php"); ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .receipt-container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .receipt-header {
            background-color: #f8f9fa;
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #ccc;
        }

        .receipt-header h1 {
            margin-bottom: 0;
        }

        .receipt-content {
            margin-top: 20px;
        }
    </style>
</head>
<style>
    .receipt-container {
        max-width: 700px;
        margin: auto;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .header {
        text-align: center;
        border-bottom: 1px solid #ccc;
        margin-bottom: 20px;
    }

    .header img {
        width: 100px;
    }

    .customer-info,
    .order-summary {
        margin-bottom: 20px;
    }

    .order-summary th,
    .order-summary td {
        text-align: center;
    }

    .total {
        font-weight: bold;
    }

    .footer {
        text-align: right;
        font-style: italic;
    }
</style>

<body>
    <?php include ("../includes/sidebar.php"); ?>
    <div class="receipt-header bg-white bg-gradient shadow">
        <h2 class="text-center"><strong>ORDER SUBMITTED</strong></h2>
    </div>
    <div class="receipt-container mt-4 bg-white">

        <h3>
            <center>Thank you for your order!</center>
        </h3>
        <p>
            <center>Your order has been successfully submitted. Click button bellow to generate the receipt:</center>
        </p>
        <div class="text-center">
            <div class="text-center">
                <button class="btn btn-success" onclick="openReceiptInNewTab()">Generate Receipt</button>
                <a href="../dashboard/home.php" name="" id="" class="btn btn-primary" href="#" role="button">Back to
                    Home</a>
            </div>
        </div>
    </div>
</body>
<script>
    function openReceiptInNewTab() {
        var orderID = "<?php echo urlencode($_SESSION['order_id']); ?>";
        var url = "receipt-generator.php?order_id=" + orderID;
        window.open(url, '_blank');
    }
</script>

<?php include ("../includes/footer.php"); ?>
<?php include ("../includes/footer-tag.php"); ?>
</body>

</html>