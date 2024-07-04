<?php require_once ("../includes/connection.db.php"); ?>
<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['addStaffName'])) {
        // Get the form data for adding staff
        $staffName = $_POST["addStaffName"];
        $staffIc = $_POST["addStaffIc"];
        $staffPhone = $_POST["addStaffPhone"];

        // Prepare the SQL statement
        $sql = "INSERT INTO STAFF(STAFF_ID, SUPERVISOR_ID, STAFF_PASSWORD, STAFF_NAME, STAFF_IC, STAFF_PHONENUM) VALUES ('S'||LPAD(STAFF_ID_SEQ.NEXTVAL, 3, 0), NULL, 'abc123', :staffName, :staffIc, TO_CHAR(:staffPhone))";
        $stmt = oci_parse($dbconn, $sql);

        // Bind the parameters
        oci_bind_by_name($stmt, ":staffName", $staffName);
        oci_bind_by_name($stmt, ":staffIc", $staffIc);
        oci_bind_by_name($stmt, ":staffPhone", $staffPhone);

        // Execute the statement
        $result = oci_execute($stmt, OCI_DEFAULT);

        if ($result) {
            oci_commit($dbconn);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Error adding staff: " . oci_error($stmt);
        }

        // Free the statement
        oci_free_statement($stmt);

    } elseif (isset($_POST['updateStaffId'])) {
        // Get the form data for updating staff
        $staffId = $_POST["updateStaffId"];
        $staffName = $_POST["updateStaffName"];
        $staffIc = $_POST["updateStaffIc"];
        $staffPhone = $_POST["updateStaffPhone"];

        // Prepare the SQL statement
        $sql = "UPDATE STAFF SET STAFF_NAME = :staffName, STAFF_IC = :staffIc, STAFF_PHONENUM = :staffPhone WHERE STAFF_ID = :staffId";
        $stmt = oci_parse($dbconn, $sql);

        // Bind the parameters
        oci_bind_by_name($stmt, ":staffId", $staffId);
        oci_bind_by_name($stmt, ":staffName", $staffName);
        oci_bind_by_name($stmt, ":staffIc", $staffIc);
        oci_bind_by_name($stmt, ":staffPhone", $staffPhone);

        // Execute the statement
        $result = oci_execute($stmt, OCI_DEFAULT);

        if ($result) {
            oci_commit($dbconn);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Error updating staff: " . oci_error($stmt);
        }

        // Free the statement
        oci_free_statement($stmt);

    } elseif (isset($_POST['deleteStaffId'])) {
        // Get the form data for deleting staff
        $staffId = $_POST["deleteStaffId"];

        // Prepare the SQL statement
        $sql = "DELETE FROM STAFF WHERE STAFF_ID = :staffId";
        $stmt = oci_parse($dbconn, $sql);

        // Bind the parameters
        oci_bind_by_name($stmt, ":staffId", $staffId);

        // Execute the statement
        $result = oci_execute($stmt, OCI_DEFAULT);

        if ($result) {
            oci_commit($dbconn);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Error deleting staff: " . oci_error($stmt);
        }

        // Free the statement
        oci_free_statement($stmt);
    }
}

?>

<!DOCTYPE html>
<html>
<?php
include ("../includes/header-tag.php");
?>

<header>
    <?php
    include ("../includes/sidebar.php");
    ?>
</header>
<style>
    .btn-search {
        height: 100%;
        border-radius: 30%;
    }
</style>

<body>
    <div id="staffs" class="bg-light px-4 text-center">
        <h1><strong>STAFF LIST</strong></h1>
        <hr>
    </div>
    <div class="container">
        <div class="row justify-content-center m-4">
            <div class="col-md-6">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="text" class="form-control" name="filter"
                            placeholder="Search by Staff ID, Staff Name...">
                        <div class="input-group-append ps-2">
                            <button class="btn btn-search btn-success" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="justify-content-end">
            <button class="btn btn-primary btn-add mb-2" data-id="" data-bs-toggle="modal"
                data-bs-target="#addModal">Add
                Staff</button>
        </div>

        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th scope="col">Staff ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">IC</th>
                    <th scope="col">Phone Number</th>
                    <th scope="col">Supervisor</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $filter = isset($_GET['filter']) ? strtoupper($_GET['filter']) : '';
                $sql = "SELECT s.STAFF_ID,s.STAFF_NAME, s.STAFF_IC, s.STAFF_PHONENUM, sup.STAFF_NAME AS SUPERVISOR_NAME FROM STAFF s LEFT JOIN STAFF sup ON s.SUPERVISOR_ID = sup.STAFF_ID WHERE UPPER(s.STAFF_ID) LIKE '%' || :filter || '%' OR UPPER(s.STAFF_NAME) LIKE '%' || :filter || '%' ORDER BY s.STAFF_ID ASC";
                $stid = oci_parse($dbconn, $sql);
                oci_bind_by_name($stid, ":filter", $filter);
                oci_execute($stid);

                while ($row = oci_fetch_assoc($stid)) {
                    echo "<tr>";
                    echo "<th scope='row'>" . htmlspecialchars($row["STAFF_ID"], ENT_QUOTES, 'UTF-8') . "</th>";
                    echo "<td>" . htmlspecialchars($row["STAFF_NAME"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["STAFF_IC"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["STAFF_PHONENUM"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["SUPERVISOR_NAME"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td class='text-center'>";
                    echo "<button class='btn btn-primary btn-update' data-id='" . $row["STAFF_ID"] . "' data-bs-toggle='modal' data-bs-target='#updateModal'>Update</button> ";
                    echo "<button class='btn btn-danger btn-delete' data-id='" . $row["STAFF_ID"] . "' data-bs-toggle='modal' data-bs-target='#deleteModal'>Delete</button>";
                    echo "</td>";
                    echo "</tr>";
                }

                if (oci_num_rows($stid) == 0) {
                    echo "<tr><td colspan='5'>No staff found</td></tr>";
                }

                oci_free_statement($stid);
                CloseConn($dbconn);
                ?>
            </tbody>
        </table>
    </div>

    <div id="unassignedStaff" class="container mt-4">
        <h4>Unassigned Staff</h4>
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th scope="col">Staff ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Phone Number</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // SQL to fetch unassigned staff
                $sql_unassigned = "
                        SELECT s.STAFF_ID, s.STAFF_NAME, s.STAFF_PHONENUM
                        FROM STAFF s
                        MINUS
                        SELECT o.STAFF_ID, s.STAFF_NAME, s.STAFF_PHONENUM
                        FROM ORDERS o
                        JOIN STAFF s ON o.STAFF_ID = s.STAFF_ID
                    ";
                $stid_unassigned = oci_parse($dbconn, $sql_unassigned);
                oci_execute($stid_unassigned);

                while ($row_unassigned = oci_fetch_assoc($stid_unassigned)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row_unassigned["STAFF_ID"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row_unassigned["STAFF_NAME"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row_unassigned["STAFF_PHONENUM"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "</tr>";
                }

                if (oci_num_rows($stid_unassigned) == 0) {
                    echo "<tr><td colspan='3'>No unassigned staff found</td></tr>";
                }

                oci_free_statement($stid_unassigned);
                ?>
            </tbody>
        </table>
    </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <div class="mb-3">
                            <label for="addStaffName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="addStaffName" name="addStaffName" required>
                        </div>
                        <div class="mb-3">
                            <label for="addStaffIc" class="form-label">IC</label>
                            <input type="text" class="form-control" id="addStaffIc" name="addStaffIc" required>
                        </div>
                        <div class="mb-3">
                            <label for="addStaffPhone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="addStaffPhone" name="addStaffPhone" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">Update Staff Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <input type="hidden" id="updateStaffId" name="updateStaffId">
                        <div class="mb-3">
                            <label for="updateStaffName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="updateStaffName" name="updateStaffName"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="updateStaffIc" class="form-label">IC</label>
                            <input type="text" class="form-control" id="updateStaffIc" name="updateStaffIc" required>
                        </div>
                        <div class="mb-3">
                            <label for="updateStaffPhone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="updateStaffPhone" name="updateStaffPhone"
                                required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this staff member?</p>
                    <form id="deleteForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <input type="hidden" id="deleteStaffId" name="deleteStaffId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.btn-add').forEach(button => {
            button.addEventListener('click', function () {
                const staffId = this.getAttribute('data-id');
                document.getElementById('addStaffId').value = staffId;
            });
        });

        document.querySelectorAll('.btn-update').forEach(button => {
            button.addEventListener('click', function () {
                const staffId = this.getAttribute('data-id');
                document.getElementById('updateStaffId').value = staffId;
            });
        });

        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function () {
                const staffId = this.getAttribute('data-id');
                document.getElementById('deleteStaffId').value = staffId;
            });
        });
    </script>
</body>

<?php
include ("../includes/footer.php");
include ("../includes/footer-tag.php");
?>

</html>