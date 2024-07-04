<?php
require_once ("../includes/connection.db.php");
session_start();
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
}

// Function to fetch order details based on order ID
function fetchOrderDetails($orderId)
{
    global $dbconn;
    $sql = "SELECT p.prod_id, p.prod_name, od.orderlist_id, od.quantity, od.amount, od.request, TO_CHAR(od.preparation_date, 'yyyy-mm-dd HH24:MI:SS') AS preparation_date
            FROM product p
            LEFT JOIN order_details od ON p.prod_id = od.prod_id
            WHERE od.order_id = :orderId";
    $stmt = oci_parse($dbconn, $sql);
    oci_bind_by_name($stmt, ":orderId", $orderId);
    oci_execute($stmt);

    $orderDetails = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Format preparation_date to remove milliseconds and display in 24-hour format
        $row['PREPARATION_DATE'] = date('Y-m-d H:i:s', strtotime($row['PREPARATION_DATE']));

        $orderDetails[] = $row;
    }
    oci_free_statement($stmt);
    return $orderDetails;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $filterStaff = isset($_GET['filterStaff']) ? strtoupper($_GET['filterStaff']) : '';
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';

    if (!empty($filterStaff)) {
        // Filter by Staff ID
        $sql = "SELECT ORDER_ID, STAFF_ID, CUST_NAME, CUST_PHONENUM, DELIVERY_ADDRESS, ORDER_DATE, TO_CHAR(ORDER_TIME, 'HH24:MI') AS ORDER_TIME, REQUIRED_DATE, TO_CHAR(REQUIRED_TIME, 'HH24:MI') AS REQUIRED_TIME, ORDER_REMARKS, ORDER_STATUS 
                FROM ORDERS
                WHERE UPPER(STAFF_ID) LIKE '%' || :filterStaff || '%'
                OR UPPER(CUST_NAME) LIKE '%' || :filterStaff || '%'
                ORDER BY REQUIRED_TIME DESC";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":filterStaff", $filterStaff);
    } elseif (!empty($startDate) && !empty($endDate)) {
        // Filter by Date Range
        $sql = "SELECT ORDER_ID, STAFF_ID, CUST_NAME, CUST_PHONENUM, DELIVERY_ADDRESS, ORDER_DATE, TO_CHAR(ORDER_TIME, 'HH24:MI') AS ORDER_TIME, REQUIRED_DATE, TO_CHAR(REQUIRED_TIME, 'HH24:MI') AS REQUIRED_TIME, ORDER_REMARKS, ORDER_STATUS 
                FROM ORDERS
                WHERE ORDER_DATE BETWEEN TO_DATE(:startDate, 'yyyy-mm-dd') AND TO_DATE(:endDate, 'yyyy-mm-dd')
                ORDER BY REQUIRED_TIME DESC";

        $stid = oci_parse($dbconn, $sql);
        oci_bind_by_name($stid, ":startDate", $startDate);
        oci_bind_by_name($stid, ":endDate", $endDate);
    } else {
        // Default query to fetch all orders
        $sql = "SELECT ORDER_ID, STAFF_ID, CUST_NAME, CUST_PHONENUM, DELIVERY_ADDRESS, ORDER_DATE, TO_CHAR(ORDER_TIME, 'HH24:MI') AS ORDER_TIME, REQUIRED_DATE, TO_CHAR(REQUIRED_TIME, 'HH24:MI') AS REQUIRED_TIME, ORDER_REMARKS, ORDER_STATUS 
                FROM ORDERS
                ORDER BY REQUIRED_TIME DESC";

        $stid = oci_parse($dbconn, $sql);
    }

    oci_execute($stid);
}
?>

<!DOCTYPE html>
<html>
<?php include ("../includes/header-tag.php"); ?>

<header>
    <?php include ("../includes/sidebar.php"); ?>
</header>
<style>
    .btn-search {
        height: 100%;
        border-radius: 30%;
    }
</style>

<body>
    <div class="bg-white bg-gradient shadow">
        <h2 class="p-3 text-center"><strong>ORDERS LIST</strong></h2>
    </div>

    <div class="container bg-white bg-gradient bg-opacity-75 rounded p-4">
        <div class="row justify-content-center m-4">
            <div class="col-md-6">
                <!-- Staff ID Search Form -->
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="input-group">
                        <input type="text" class="form-control" name="filterStaff" placeholder="Filter by Staff ID..."
                            value="<?php echo htmlspecialchars($filterStaff); ?>">
                        <div class="input-group-append ps-2">
                            <button class="btn btn-search btn-success" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="betweenDates" class="row justify-content-center m-4">
            <div class="col-md-6">
                <p>Display by Order Date:-</p>
                <!-- Date Range Search Form -->
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="row">
                        <div class="col">
                            <label for="startDate" class="form-label">Start Date:</label>
                            <input type="date" class="form-control" id="startDate" name="startDate"
                                value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="col">
                            <label for="endDate" class="form-label">End Date:</label>
                            <input type="date" class="form-control" id="endDate" name="endDate"
                                value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th scope="col">Staff ID</th>
                    <th scope="col">Order ID</th>
                    <th scope="col">Customer Name</th>
                    <th scope="col">Customer Phone</th>
                    <th scope="col">Delivery Address</th>
                    <th scope="col">Order Date</th>
                    <th scope="col">Order Time</th>
                    <th scope="col">Required Date</th>
                    <th scope="col">Required Time</th>
                    <th scope="col">Remarks</th>
                    <th scope="col">Status</th>
                    <th scope="col">Details</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = oci_fetch_assoc($stid)) {
                    echo "<tr>";
                    echo "<th>" . htmlspecialchars($row["STAFF_ID"], ENT_QUOTES, 'UTF-8') . "</th>";
                    echo "<td>" . htmlspecialchars($row["ORDER_ID"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["CUST_NAME"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["CUST_PHONENUM"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["DELIVERY_ADDRESS"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["ORDER_DATE"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["ORDER_TIME"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["REQUIRED_DATE"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["REQUIRED_TIME"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . (($row["ORDER_REMARKS"] === "NULL") ? '-' : htmlspecialchars($row["ORDER_REMARKS"], ENT_QUOTES, 'UTF-8')) . "</td>";
                    echo "<td>" . htmlspecialchars($row["ORDER_STATUS"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td class='text-center'><button class='btn btn-info' onclick='fetchOrderDetails(\"" . $row["ORDER_ID"] . "\")'>Details</button></td>";
                    echo "<td class='text-center'>";
                    echo "<button class='btn btn-primary btn-update' data-id='" . $row["ORDER_ID"] . "' data-bs-toggle='modal' data-bs-target='#updateModal'>Update</button> ";
                    echo "</td>";
                    echo "</tr>";
                }

                if (oci_num_rows($stid) == 0) {
                    echo "<tr><td colspan='13'>No orders found</td></tr>";
                }

                oci_free_statement($stid);
                CloseConn($dbconn);
                ?>
            </tbody>
        </table>
    </div>

    <!-- Update Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">Update Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="hidden" id="updateOrderId" name="updateOrderId">
                        <div class="mb-3">
                            <label for="updateOrderStatus" class="form-label">Status</label>
                            <select class="form-control" id="updateOrderStatus" name="updateOrderStatus" required>
                                <option value="PENDING">PENDING</option>
                                <option value="COMPLETED">COMPLETED</option>
                                <option value="CANCELLED">CANCELLED</option>
                            </select>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailsModalBody">
                    <!-- Order details will be loaded here by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.btn-update').forEach(button => {
            button.addEventListener('click', function () {
                const orderId = this.getAttribute('data-id');
                const orderStatus = this.closest('tr').querySelector('td:nth-child(12)').textContent.trim();

                document.getElementById('updateOrderId').value = orderId;
                document.getElementById('updateOrderStatus').value = orderStatus;

                const updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
                updateModal.show();
            });
        });

        function fetchOrderDetails(orderId) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'fetch_order_details.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                    document.getElementById('detailsModalBody').innerHTML = xhr.responseText;
                    const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
                    detailsModal.show();
                }
            };
            xhr.send('orderId=' + orderId);
        }
    </script>
</body>

<?php include ("../includes/footer.php");
include ("../includes/footer-tag.php"); ?>

</html>