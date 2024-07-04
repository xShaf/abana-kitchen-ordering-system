<?php
require_once ("../includes/connection.db.php");
session_start();
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['addProductName'])) {
        // Get the form data for adding product
        $prodName = $_POST["addProductName"];
        $prodPrice = $_POST["addProductPrice"];
        $prodBestBefore = $_POST["addProductBestBefore"];
        $prodType = $_POST["addProductType"];

        // Prepare the SQL statement for inserting into PRODUCT
        $sql = "INSERT INTO PRODUCT(PROD_ID, PROD_NAME, PROD_PRICE, PROD_BESTBEFORE, PROD_TYPE) 
                VALUES ('P'||LPAD(prod_ID_seq.NEXTVAL, 3, 0), :prodName, :prodPrice, :prodBestBefore, :prodType)";
        $stmt = oci_parse($dbconn, $sql);

        // Bind the parameters
        oci_bind_by_name($stmt, ":prodName", $prodName);
        oci_bind_by_name($stmt, ":prodPrice", $prodPrice);
        oci_bind_by_name($stmt, ":prodBestBefore", $prodBestBefore);
        oci_bind_by_name($stmt, ":prodType", $prodType);

        // Execute the statement
        $result = oci_execute($stmt, OCI_DEFAULT);

        if ($result) {
            // Commit the product insertion
            oci_commit($dbconn);

            // Get the last inserted product ID
            $sql = "SELECT 'P'||LPAD(prod_ID_seq.CURRVAL, 3, 0) AS PROD_ID FROM DUAL";
            $stmt = oci_parse($dbconn, $sql);
            oci_execute($stmt);
            $prodId = oci_fetch_row($stmt)[0];

            // Insert into child table based on product type
            if ($prodType == 'freshly made') {
                $fmCategoryName = $_POST["addFMCategoryName"];
                $fmSize = $_POST["addFMSize"];
                $sql = "INSERT INTO FRESHLY_MADE(PROD_ID, FM_CATEGORYNAME, FM_SIZE) VALUES (:prodId, :fmCategoryName, :fmSize)";
                $stmt = oci_parse($dbconn, $sql);
                oci_bind_by_name($stmt, ":prodId", $prodId);
                oci_bind_by_name($stmt, ":fmCategoryName", $fmCategoryName);
                oci_bind_by_name($stmt, ":fmSize", $fmSize);
            } elseif ($prodType == 'frozen') {
                $storage_temp = $_POST["addstorage_temp"];
                $cookingMethod = $_POST["addCookingMethod"];
                $sql = "INSERT INTO FROZEN(PROD_ID, STORAGE_TEMP, COOKING_METHOD) VALUES (:prodId, :storage_temp, :cookingMethod)";
                $stmt = oci_parse($dbconn, $sql);
                oci_bind_by_name($stmt, ":prodId", $prodId);
                oci_bind_by_name($stmt, ":storage_temp", $storage_temp);
                oci_bind_by_name($stmt, ":cookingMethod", $cookingMethod);
            }

            // Execute the child table insertion
            $result = oci_execute($stmt, OCI_DEFAULT);

            if ($result) {
                oci_commit($dbconn);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $error = oci_error($stmt);
                echo "Error adding product details: " . htmlspecialchars($error['message'], ENT_QUOTES, 'UTF-8');
            }
        } else {
            $error = oci_error($stmt);
            echo "Error adding product: " . htmlspecialchars($error['message'], ENT_QUOTES, 'UTF-8');
        }

        // Free the statement
        oci_free_statement($stmt);

    } elseif (isset($_POST['updateProductId'])) {
        // Get the form data for updating product
        $prodId = $_POST["updateProductId"];
        $prodName = $_POST["updateProductName"];
        $prodPrice = $_POST["updateProductPrice"];
        $prodBestBefore = $_POST["updateProductBestBefore"];
        $prodType = $_POST["updateProductType"];

        // Prepare the SQL statement for updating PRODUCT
        $sql = "UPDATE PRODUCT SET PROD_NAME = :prodName, PROD_PRICE = :prodPrice, PROD_BESTBEFORE = :prodBestBefore, PROD_TYPE = :prodType 
                WHERE PROD_ID = :prodId";
        $stmt = oci_parse($dbconn, $sql);

        // Bind the parameters
        oci_bind_by_name($stmt, ":prodId", $prodId);
        oci_bind_by_name($stmt, ":prodName", $prodName);
        oci_bind_by_name($stmt, ":prodPrice", $prodPrice);
        oci_bind_by_name($stmt, ":prodBestBefore", $prodBestBefore);
        oci_bind_by_name($stmt, ":prodType", $prodType);

        // Execute the statement
        $result = oci_execute($stmt, OCI_DEFAULT);

        if ($result) {
            oci_commit($dbconn);

            // Update the child table based on product type
            if ($prodType == 'freshly made') {
                $fmCategoryName = $_POST["updateFMCategoryName"];
                $fmSize = $_POST["updateFMSize"];
                $sql = "UPDATE FRESHLY_MADE SET FM_CATEGORYNAME = :fmCategoryName, FM_SIZE = :fmSize WHERE PROD_ID = :prodId";
                $stmt = oci_parse($dbconn, $sql);
                oci_bind_by_name($stmt, ":prodId", $prodId);
                oci_bind_by_name($stmt, ":fmCategoryName", $fmCategoryName);
                oci_bind_by_name($stmt, ":fmSize", $fmSize);

                $result = oci_execute($stmt, OCI_DEFAULT);

            } elseif ($prodType == 'frozen') {
                $storage_temp = $_POST["updatestorage_temp"];
                $cookingMethod = $_POST["updateCookingMethod"];
                $sql = "UPDATE FROZEN SET STORAGE_TEMP = :storage_temp, COOKING_METHOD = :cookingMethod WHERE PROD_ID = :prodId";
                $stmt = oci_parse($dbconn, $sql);
                oci_bind_by_name($stmt, ":prodId", $prodId);
                oci_bind_by_name($stmt, ":storage_temp", $storage_temp);
                oci_bind_by_name($stmt, ":cookingMethod", $cookingMethod);

                $result = oci_execute($stmt, OCI_DEFAULT);

            }

            // Execute the child table update

            if ($result) {
                oci_commit($dbconn);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                echo "Error updating product details: " . oci_error($stmt);
            }
        } else {
            echo "Error updating product: " . oci_error($stmt);
        }

        // Free the statement
        oci_free_statement($stmt);

    } elseif (isset($_POST['deleteProductId'])) {
        // Get the form data for deleting product
        $prodId = $_POST["deleteProductId"];

        // Prepare the SQL statement for deleting from PRODUCT
        $sql = "DELETE FROM PRODUCT WHERE PROD_ID = :prodId";
        $stmt = oci_parse($dbconn, $sql);

        // Bind the parameters
        oci_bind_by_name($stmt, ":prodId", $prodId);

        // Execute the statement
        $result = oci_execute($stmt, OCI_DEFAULT);

        if ($result) {
            oci_commit($dbconn);

            // Delete from child table based on product type
            $prodType = $_POST["deleteProductType"];
            if ($prodType == 'freshly made') {
                $sql = "DELETE FROM FRESHLY_MADE WHERE PROD_ID = :prodId";
            } elseif ($prodType == 'frozen') {
                $sql = "DELETE FROM FROZEN WHERE PROD_ID = :prodId";
            }
            $stmt = oci_parse($dbconn, $sql);
            oci_bind_by_name($stmt, ":prodId", $prodId);

            // Execute the child table deletion
            $result = oci_execute($stmt, OCI_DEFAULT);

            if ($result) {
                oci_commit($dbconn);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                echo "Error deleting product details: " . oci_error($stmt);
            }
        } else {
            echo "Error deleting product: " . oci_error($stmt);
        }

        // Free the statement
        oci_free_statement($stmt);
    }
}
?>


<!DOCTYPE html>
<html>

<head>
    <?php
    include ("../includes/header-tag.php");
    ?>
</head>

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
    <div class="bg-light px-4 text-center">
        <h2 class="p-2"><strong>PRODUCT LIST</strong></h2>
        <hr>
    </div>
    <div class="container bg-white bg-opacity-75 rounded p-4">
        <div class="row justify-content-center m-4">
            <div class="col-md-6">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="text" class="form-control" name="filter" placeholder="Filter by name...">
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
                Product</button>
        </div>

        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th scope="col">Product ID</th>
                    <th scope="col">Image</th>
                    <th scope="col">Name</th>
                    <th scope="col">Price</th>
                    <th scope="col">Best Before</th>
                    <th scope="col">Type</th>
                    <th scope="col">Details</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $filter = isset($_GET['filter']) ? strtoupper($_GET['filter']) : '';
                $sql = "SELECT p.PROD_ID, p.PROD_NAME, p.PROD_PRICE, p.PROD_BESTBEFORE, p.PROD_TYPE, 
                               fm.FM_CATEGORYNAME, fm.FM_SIZE, 
                               f.STORAGE_TEMP, f.COOKING_METHOD 
                        FROM PRODUCT p
                        LEFT JOIN FRESHLY_MADE fm ON p.PROD_ID = fm.PROD_ID
                        LEFT JOIN FROZEN f ON p.PROD_ID = f.PROD_ID
                        WHERE UPPER(p.PROD_NAME) LIKE '%' || :filter || '%' 
                        ORDER BY p.PROD_ID ASC";
                $stid = oci_parse($dbconn, $sql);
                oci_bind_by_name($stid, ":filter", $filter);
                oci_execute($stid);

                while ($row = oci_fetch_assoc($stid)) {
                    echo "<tr>";
                    echo "<th scope='row'>" . htmlspecialchars($row["PROD_ID"], ENT_QUOTES, 'UTF-8') . "</th>";
                    echo "<td><img class='object-fit-fill' src='../assets/images/products/" . htmlspecialchars($row["PROD_ID"], ENT_QUOTES, 'UTF-8') . ".png' width='100' height='100'></td>";
                    echo "<td>" . htmlspecialchars($row["PROD_NAME"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["PROD_PRICE"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["PROD_BESTBEFORE"], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row["PROD_TYPE"], ENT_QUOTES, 'UTF-8') . "</td>";

                    echo "<td>";
                    if ($row["PROD_TYPE"] == 'freshly made') {
                        if ($row['FM_SIZE'] == 'null') {
                            echo "Size: -";
                        } else {
                            echo "Size: -" . htmlspecialchars($row["FM_SIZE"], ENT_QUOTES, 'UTF-8');
                        }
                        echo "<br>Category: " . htmlspecialchars($row["FM_CATEGORYNAME"], ENT_QUOTES, 'UTF-8') . "<br>";
                    } elseif ($row['PROD_TYPE'] == 'frozen') {
                        echo 'Storage Temperature: ' . htmlspecialchars($row['STORAGE_TEMP'], ENT_QUOTES, 'UTF-8') . "<br>";
                        echo "Cooking Method: " . htmlspecialchars($row["COOKING_METHOD"], ENT_QUOTES, 'UTF-8');
                    }
                    ;
                    echo "</td>";
                    echo "<td class='text-center'>";
                    echo "<div class='btn-group'>";
                    echo "<button class='btn btn-primary btn-update' data-id='" . $row["PROD_ID"] . "' data-type='" . $row["PROD_TYPE"] . "' data-bs-toggle='modal' data-bs-target='#updateModal'>Update</button> ";
                    echo "<button class='btn btn-danger btn-delete' data-id='" . $row["PROD_ID"] . "' data-type='" . $row["PROD_TYPE"] . "' data-bs-toggle='modal' data-bs-target='#deleteModal'>Delete</button>";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }


                if (oci_num_rows($stid) == 0) {
                    echo "<tr><td colspan='6'>No products found</td></tr>";
                }

                oci_free_statement($stid);
                CloseConn($dbconn);
                ?>
            </tbody>
        </table>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <div class="mb-3">
                            <label for="addProductName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="addProductName" name="addProductName" required>
                        </div>
                        <div class="mb-3">
                            <label for="addProductPrice" class="form-label">Price</label>
                            <input type="text" class="form-control" id="addProductPrice" name="addProductPrice"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="addProductBestBefore" class="form-label">Best Before</label>
                            <input type="text" class="form-control" id="addProductBestBefore"
                                name="addProductBestBefore" required>
                        </div>
                        <div class="mb-3">
                            <label for="addProductType" class="form-label">Type</label>
                            <select class="form-control" id="addProductType" name="addProductType" required>
                                <option value="freshly made">Freshly Made</option>
                                <option value="frozen">Frozen</option>
                            </select>
                        </div>
                        <!-- Fields to show/hide based on selection -->
                        <div class="mb-3" id="addFreshlyMadeFields">
                            <label for="addFMCategoryName" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="addFMCategoryName" name="addFMCategoryName">
                            <label for="addFMSize" class="form-label">Size</label>
                            <input type="text" class="form-control" id="addFMSize" name="addFMSize">
                        </div>
                        <div class="mb-3" id="addFrozenFields">
                            <label for="addstorage_temp" class="form-label">Storage Temperature</label>
                            <input type="text" class="form-control" id="addstorage_temp" name="addstorage_temp">
                            <label for="addCookingMethod" class="form-label">Cooking Method</label>
                            <input type="text" class="form-control" id="addCookingMethod" name="addCookingMethod">
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
                    <h5 class="modal-title" id="updateModalLabel">Update Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="hidden" id="updateProductId" name="updateProductId">
                        <div class="mb-3">
                            <label for="updateProductName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="updateProductName" name="updateProductName"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="updateProductPrice" class="form-label">Price</label>
                            <input type="text" class="form-control" id="updateProductPrice" name="updateProductPrice"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="updateProductBestBefore" class="form-label">Best Before</label>
                            <input type="text" class="form-control" id="updateProductBestBefore"
                                name="updateProductBestBefore" required>
                        </div>
                        <div class="mb-3">
                            <label for="updateProductType" class="form-label">Type</label>
                            <select class="form-control" id="updateProductType" name="updateProductType" required>
                                <option value="freshly made">Freshly Made</option>
                                <option value="frozen">Frozen</option>
                            </select>
                        </div>
                        <!-- Fields to show/hide based on selection -->
                        <div class="mb-3" id="updateFreshlyMadeFields">
                            <label for="updateFMCategoryName" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="updateFMCategoryName"
                                name="updateFMCategoryName">
                            <label for="updateFMSize" class="form-label">Size</label>
                            <input type="text" class="form-control" id="updateFMSize" name="updateFMSize">
                        </div>
                        <div class="mb-3" id="updateFrozenFields">
                            <label for="updatestorage_temp" class="form-label">Storage Temperature</label>
                            <input type="text" class="form-control" id="updatestorage_temp" name="updatestorage_temp">
                            <label for="updateCookingMethod" class="form-label">Cooking Method</label>
                            <input type="text" class="form-control" id="updateCookingMethod" name="updateCookingMethod">
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
    </div>


    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this product?</p>
                    <form id="deleteForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <input type="hidden" id="deleteProductId" name="deleteProductId">
                        <input type="hidden" id="deleteProductType" name="deleteProductType">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.btn-add').forEach(button => {
            button.addEventListener('click', function () {
                const prodId = this.getAttribute('data-id');
                document.getElementById('addProductId').value = prodId;
            });
        });

        document.querySelectorAll('.btn-update').forEach(button => {
            button.addEventListener('click', function () {
                const prodId = this.getAttribute('data-id');
                const prodType = this.getAttribute('data-type');
                document.getElementById('updateProductId').value = prodId;
                document.getElementById('updateProductType').value = prodType;
            });
        });

        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function () {
                const prodId = this.getAttribute('data-id');
                const prodType = this.getAttribute('data-type');
                document.getElementById('deleteProductId').value = prodId;
                document.getElementById('deleteProductType').value = prodType;
            });
        });

        // Hide/show child table fields based on product type selection
        document.getElementById('addProductType').addEventListener('change', function () {
            toggleChildTableFields(this.value, 'add');
        });

        document.getElementById('updateProductType').addEventListener('change', function () {
            toggleChildTableFields(this.value, 'update');
        });

        // Function to toggle visibility of child table fields based on product type
        function toggleChildTableFields(prodType, action) {
            const freshlyMadeFields = document.getElementById(`${action}FreshlyMadeFields`);
            const frozenFields = document.getElementById(`${action}FrozenFields`);

            if (prodType === 'freshly made') {
                freshlyMadeFields.style.display = 'block';
                frozenFields.style.display = 'none';
            } else if (prodType === 'frozen') {
                freshlyMadeFields.style.display = 'none';
                frozenFields.style.display = 'block';
            } else {
                freshlyMadeFields.style.display = 'none';
                frozenFields.style.display = 'none';
            }
        }

        // Initialize fields visibility based on selected type on page load
        document.addEventListener('DOMContentLoaded', function () {
            toggleChildTableFields(document.getElementById('addProductType').value, 'add');
            toggleChildTableFields(document.getElementById('updateProductType').value, 'update');
        });

        // Event listeners to update visibility on type change
        document.getElementById('addProductType').addEventListener('change', function () {
            toggleChildTableFields(this.value, 'add');
        });

        document.getElementById('updateProductType').addEventListener('change', function () {
            toggleChildTableFields(this.value, 'update');
        });




    </script>

</body>

<?php
include ("../includes/footer.php");
include ("../includes/footer-tag.php");
?>

</html>