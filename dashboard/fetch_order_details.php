<?php
require_once ("../includes/connection.db.php");
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['orderId'])) {
    $orderId = $_POST['orderId'];
    $orderDetails = fetchOrderDetails($orderId);

    if (!empty($orderDetails)) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Product ID</th>';
        echo '<th>Product Name</th>';
        echo '<th>Quantity</th>';
        echo '<th>Amount</th>';
        echo '<th>Request</th>';
        echo '<th>Preparation Date</th>';
        echo '<th>Product Image</th>'; // Add a header for the product image
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($orderDetails as $detail) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($detail['PROD_ID'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($detail['PROD_NAME'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($detail['QUANTITY'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($detail['AMOUNT'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($detail['REQUEST'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($detail['PREPARATION_DATE'], ENT_QUOTES, 'UTF-8') . '</td>';

            // Assuming you have the image path stored in the product table
            $prodImagePath = '../assets/images/products/' . $detail['PROD_ID'] . '.png'; // Modify according to your path and naming convention
            if (file_exists($prodImagePath)) {
                echo '<td><img src="' . $prodImagePath . '" alt="Product Image" width="50" height="50"></td>';
            } else {
                echo '<td>No image available</td>';
            }

            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo 'No order details found.';
    }
}

function fetchOrderDetails($orderId)
{
    global $dbconn;
    $sql = "SELECT p.prod_id, p.prod_name, od.quantity, od.amount, od.request, od.preparation_date 
            FROM order_details od
            JOIN product p ON od.prod_id = p.prod_id
            WHERE od.order_id = :orderId";
    $stmt = oci_parse($dbconn, $sql);
    oci_bind_by_name($stmt, ":orderId", $orderId);
    oci_execute($stmt);

    $orderDetails = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $orderDetails[] = $row;
    }
    oci_free_statement($stmt);
    return $orderDetails;
}
?>