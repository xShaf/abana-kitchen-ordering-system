<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}


// Function to sanitize input
function sanitize_input($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

$search_keyword = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $search_keyword = sanitize_input($_POST['search']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        // Adding a product to the cart
        $product_id = sanitize_input($_POST['product_id']);
        $product_name = sanitize_input($_POST['product_name']);
        $product_price = sanitize_input($_POST['product_price']);
        $quantity = (int) sanitize_input($_POST['quantity']);
        $order_request = sanitize_input($_POST['request']);

        if ($quantity > 0) {
            // Check if the product is already in the cart
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = array(
                    'name' => $product_name,
                    'price' => $product_price,
                    'quantity' => $quantity,
                    'request' => $order_request
                );
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        // Deleting a product from the cart
        $product_id = sanitize_input($_POST['product_id']);
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
    }
}

require_once ("../includes/connection.db.php");

try {
    $sql = "SELECT PROD_ID, PROD_NAME, PROD_PRICE FROM PRODUCT";
    if ($search_keyword) {
        $sql .= " WHERE LOWER(PROD_NAME) LIKE LOWER(:keyword)";
    }
    $sql .= " GROUP BY PROD_ID, PROD_NAME, PROD_PRICE ORDER BY PROD_ID ASC";
    $stmt = oci_parse($dbconn, $sql);

    if ($search_keyword) {
        $search_keyword = "%$search_keyword%";
        oci_bind_by_name($stmt, ":keyword", $search_keyword);
    }

    oci_execute($stmt);
    $products = array();
    while ($row = oci_fetch_assoc($stmt)) {
        $products[] = $row;
    }
    oci_free_statement($stmt);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

CloseConn($dbconn);

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
    <div class="bg-light mt-4 px-4 text-center">
        <h1><strong>SELECT PRODUCT</strong></h1>
        <hr>
    </div>
    <div class="p-4">
        <div class="row">
            <div class="px-4 shadow col-md-6">
                <h3>Add to Cart</h3>
                <form action="product_selection.php" method="POST" class="mb-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by Product Name"
                        value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <button class="btn btn-primary mt-2" type="submit">Search</button>
                </form>
                <form action="product_selection.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Product Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Request</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <form action="product_selection.php" method="POST">
                                        <td><?php echo htmlspecialchars($product['PROD_ID']); ?></td>
                                        <td>
                                            <img class='object-fit-fill'
                                                src='../assets/images/products/<?php echo htmlspecialchars($product['PROD_ID']); ?>.png'
                                                width='100' height='100'>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['PROD_NAME']); ?></td>
                                        <td><?php echo "RM" . htmlspecialchars($product['PROD_PRICE']); ?></td>
                                        <td>
                                            <input class="form-control" type="number" name="quantity" value="1" min="1">
                                        </td>
                                        <td>
                                            <input class="form-control" type="text" name="request"
                                                placeholder="Order Request">
                                        </td>
                                        <td>
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id"
                                                value="<?php echo htmlspecialchars($product['PROD_ID']); ?>">
                                            <input type="hidden" name="product_name"
                                                value="<?php echo htmlspecialchars($product['PROD_NAME']); ?>">
                                            <input type="hidden" name="product_price"
                                                value="<?php echo htmlspecialchars($product['PROD_PRICE']); ?>">
                                            <button class="btn btn-primary" type="submit">Add to Cart</button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            </div>
            <div class="px-4 shadow col-md-6">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Images</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Request</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $id => $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($id); ?></td>
                                <td><img class='object-fit-fill'
                                        src='../assets/images/products/<?php echo htmlspecialchars($id); ?>.png' width='100'
                                        height='100'></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo "RM" . htmlspecialchars($product['price']); ?></td>
                                <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($product['request']); ?></td>
                                <td>
                                    <form action="product_selection.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($id); ?>">
                                        <button class="btn btn-danger" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a class="btn btn-primary" href="checkout.php">Proceed to Checkout</a>
            </div>
        </div>
    </div>
</body>

<?php include ("../includes/footer.php"); ?>
<?php include ("../includes/footer-tag.php"); ?>

</html>