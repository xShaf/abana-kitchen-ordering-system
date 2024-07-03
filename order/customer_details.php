<?php
require_once ("../includes/connection.db.php");
session_start();
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
}
$today = date("Y-m-d");
$twoMoreDays = date("Y-m-d", strtotime($today . " +4 days"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cust_name = htmlspecialchars(trim($_POST['cust_name']));
    $cust_phone = htmlspecialchars(trim($_POST['cust_phone']));
    $cust_address = htmlspecialchars(trim($_POST['cust_address']));
    $requiredDate = htmlspecialchars(trim($_POST['requiredDate']));
    $requiredTime = htmlspecialchars(trim($_POST['requiredTime']));
    $remarks = htmlspecialchars(trim($_POST['remarks']));

    // Validate input
    if (!empty($cust_name) && !empty($cust_phone) && !empty($cust_address) && !empty($requiredDate) && !empty($requiredTime)) {
        $_SESSION['customer_details'] = [
            'cust_name' => $cust_name,
            'cust_phone' => $cust_phone,
            'cust_address' => $cust_address,
            'requiredDate' => $requiredDate,
            'requiredTime' => $requiredTime,
            'remarks' => $remarks
        ];
        header('Location: product_selection.php');
        exit;
    } else {
        $error = "All fields are required!";
    }
}
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
        <h1><strong>CUSTOMER DETAILS</strong></h1>
        <hr>
    </div>
    <div class="container rounded shadow p-4 mt-4">
        <h3>Enter customer details</h3>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="customer_details.php" method="post">
            <label class="form-label" for="cust_name">Name:</label>
            <input class="form-control" type="text" id="cust_name" name="cust_name" placeholder="Ali Bin Abu" required><br>

            <label class="form-label" for="cust_phone">Phone:</label>
            <input class="form-control" type="text" id="cust_phone" name="cust_phone" placeholder="0123456789" required><br>

            <label class="form-label" for="cust_address">Delivery Address:</label>
            <textarea class="form-control" id="cust_address" name="cust_address" placeholder="Universiti Teknologi MARA Cawangan Perak Kampus Tapah, 35400 Tapah Road, Perak" required></textarea><br>

            <label class="form-lable" for="requiredDate">Required Date:</label>
            <input class="form-control" type="date" name="requiredDate" min="<?php echo $twoMoreDays ?>" requried><br>

            <label class="form-label" for="requiredTime">Required Time:</label>
            <input id="" name="requiredTime" class="form-control" type="time" required /><br>

            <label class="form-label" for="remarks">Order Remarks:</label>
            <textarea name="remarks" id="" class="form-control" placeholder="If I'm not home, leave to neighbor"></textarea><br>

            <div class="d-flex flex-row-reverse">
                <input name="" id="" class="btn btn-primary" type="submit" value="Next" />
            </div>
        </form>
    </div>
</body>


<?php include ("../includes/footer.php"); ?>
<?php include ("../includes/footer-tag.php"); ?>

</html>