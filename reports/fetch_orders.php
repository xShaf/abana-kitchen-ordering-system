<?php
require_once '../includes/connection.db.php';

$type = $_GET['type'];

// Prepare the query based on the product type
if ($type == "frozen") {
    $sql = "
        SELECT O.order_ID, O.cust_Name, O.delivery_Address, P.prod_Name, OD.quantity, OD.quantity * P.prod_Price AS total_amount
        FROM ORDERS O
        JOIN ORDER_DETAILS OD ON O.order_ID = OD.order_ID
        JOIN PRODUCT P ON OD.prod_ID = P.prod_ID
        WHERE P.prod_Type = 'frozen'
        ORDER BY O.order_Date";
} else if ($type == "freshly_made") {
    $sql = "
        SELECT O.order_ID, O.cust_Name, O.delivery_Address, P.prod_Name, OD.quantity, OD.quantity * P.prod_Price AS total_amount
        FROM ORDERS O
        JOIN ORDER_DETAILS OD ON O.order_ID = OD.order_ID
        JOIN PRODUCT P ON OD.prod_ID = P.prod_ID
        WHERE P.prod_Type = 'freshly made'
        ORDER BY O.order_Date";
}

$stid = oci_parse($dbconn, $sql);
oci_execute($stid);

$orders = [];
while ($row = oci_fetch_assoc($stid)) {
    $orders[] = [
        'ORDER_ID' => htmlspecialchars($row["ORDER_ID"], ENT_QUOTES, 'UTF-8'),
        'CUST_NAME' => htmlspecialchars($row["CUST_NAME"], ENT_QUOTES, 'UTF-8'),
        'DELIVERY_ADDRESS' => htmlspecialchars($row["DELIVERY_ADDRESS"], ENT_QUOTES, 'UTF-8'),
        'PROD_NAME' => htmlspecialchars($row["PROD_NAME"], ENT_QUOTES, 'UTF-8'),
        'QUANTITY' => htmlspecialchars($row["QUANTITY"], ENT_QUOTES, 'UTF-8'),
        'TOTAL_AMOUNT' => htmlspecialchars($row["TOTAL_AMOUNT"], ENT_QUOTES, 'UTF-8')
    ];
}

oci_free_statement($stid);
CloseConn($dbconn);

echo json_encode($orders);
?>