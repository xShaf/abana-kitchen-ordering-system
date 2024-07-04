<?php require_once ("../includes/connection.db.php"); ?>
<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
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
    <div class="bg-white bg-gradident shadow">
        <h2 class="p-3 text-center"><strong>RECEIPT LIST</strong></h2>
    </div>
    <div class="container bg-white bg-opacity-75 rounded p-4">
        <div class="row justify-content-center m-4">
            <div class="col-md-6">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="text" class="form-control" name="filter" placeholder="Filter by order ID...">
                        <div class="input-group-append ps-2">
                            <button class="btn btn-search btn-success" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th scope="col">Receipt ID</th>
                    <th scope="col">Order ID</th>
                    <th scope="col">Receipt Date</th>
                    <th scope="col">Receipt Time</th>
                    <th scope="col">Payment Method</th>
                    <th scope="col">Total Amount</th>
                    <th scope="col">Generate Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $filter = isset($_GET['filter']) ? strtoupper($_GET['filter']) : '';
                $sql = "SELECT RECEIPT_ID, ORDER_ID, 
                        TO_CHAR(RECP_DATE, 'YYYY-MM-DD') AS RECP_DATE, 
                        TO_CHAR(RECP_TIME, 'HH24:MI:SS') AS RECP_TIME, 
                        PAYMENT_METHOD, TOTAL_AMOUNT 
                        FROM RECEIPT 
                        WHERE UPPER(ORDER_ID) LIKE '%' || :filter || '%' 
                        ORDER BY RECP_DATE DESC";
                $stid = oci_parse($dbconn, $sql);
                oci_bind_by_name($stid, ":filter", $filter);
                oci_execute($stid);

                while ($row = oci_fetch_assoc($stid)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row["RECEIPT_ID"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["ORDER_ID"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . date("d/m/Y", strtotime($row["RECP_DATE"])) . "</td>";
                    echo "<td>" . date("d/m/Y H:i:s", strtotime($row["RECP_TIME"])) . "</td>";
                    echo "<td>" . htmlspecialchars($row["PAYMENT_METHOD"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>RM" . htmlspecialchars($row["TOTAL_AMOUNT"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td class='text-center'>";
                    echo "<button class='btn btn-success btn-generate-receipt' data-id='" . $row["ORDER_ID"] . "'>Generate Receipt</button>";
                    echo "</td>";
                    echo "</tr>";
                }

                if (oci_num_rows($stid) == 0) {
                    echo "<tr><td colspan='7'>No receipts found</td></tr>";
                }

                oci_free_statement($stid);
                CloseConn($dbconn);
                ?>
            </tbody>
        </table>
    </div>

    <script>
        document.querySelectorAll('.btn-generate-receipt').forEach(button => {
            button.addEventListener('click', function () {
                const orderId = this.getAttribute('data-id');
                // Redirect to the receipt-generator.php with the ORDER_ID
                window.open(`../order/receipt-generator.php?order_id=${orderId}`, '_blank');
            });
        });

    </script>
</body>

<?php include ("../includes/footer.php"); ?>
<?php include ("../includes/footer-tag.php"); ?>

</html>