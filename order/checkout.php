<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
}

// Check if customer details are set
if (!isset($_SESSION['customer_details'])) {
    header('Location: customer_details.php');
    exit;
}

$customer_details = $_SESSION['customer_details'];
$selected_products = $_SESSION['cart'];
$totalAmount = 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include ("../includes/header-tag.php"); ?>
</head>

<header>
    <?php include ("../includes/sidebar.php"); ?>
</header>

<body>
    <div class="bg-light mt-4 text-center">
        <h1><strong>CHECKOUT</strong></h1>
        <hr>
    </div>
    <div class="container rounded shadow p-4 mt-4">
        <h3>Customer Details</h3>
        <p>Name: <?php echo htmlspecialchars($customer_details['cust_name']); ?></p>
        <p>Phone: <?php echo htmlspecialchars($customer_details['cust_phone']); ?></p>
        <p>Address: <?php echo htmlspecialchars($customer_details['cust_address']); ?></p>
        <p>Date Required: <?php echo htmlspecialchars($customer_details['requiredDate']); ?></p>
        <p>Time Required: <?php echo htmlspecialchars($customer_details['requiredTime']); ?></p>
        <p>Remarks: <?php echo htmlspecialchars($customer_details['remarks']); ?></p>



        <h3>Order Summary</h3>
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Images</th>
                    <th>Name</th>
                    <th>Quantity</th>
                    <th>Request</th>
                    <th>Unit Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($selected_products as $prod_id => $product): ?>
                    <tr>
                        <?php $amount = $product['quantity'] * $product['price'];
                        $totalAmount += $amount; ?>
                        <td><?php echo htmlspecialchars($prod_id); ?></td>
                        <td><img class='object-fit-fill'
                                src='../assets/images/products/<?php echo htmlspecialchars($prod_id); ?>.png' width='100'
                                height='100'></td>
                        <td><?php echo $product['name']; ?></td>
                        <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($product['request']); ?></td>
                        <td>RM<?php echo htmlspecialchars($product['price']); ?></td>
                        <td><?php echo htmlspecialchars($amount); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th colspan="6">Total</th>
                    <th>RM<?php echo htmlspecialchars($totalAmount); ?></t>
                </tr>


            </tbody>
        </table>
        <div class="row justify-content-between">
            <div class="col-auto">
                <a href="product_selection.php" class="btn btn-primary">Back</a>
            </div>
            <div class="col-auto">
                <form method="POST" action="receipt.php">
                <div class="form-group">
                    <label for="payment_method">Payment Method:</label>
                    <select name="payment_method" id="payment_method" class="form-control" required>
                        <option value="">Select Payment Method</option>
                        <option value="QR">QR</option>
                        <option value="Online Transfer">Online Transfer</option>
                    </select>
                </div>
                <br>
                <button type="submit" class="btn btn-primary">Submit Order</button>
                </form>
            </div>
        </div>





    </div>
</body>

<?php include ("../includes/footer.php"); ?>
<?php include ("../includes/footer-tag.php"); ?>

</html>