<style>
    .sidebar {
        width: 250px;
        height: 100vh;
        background-color: #BA4D52;
    }

    .sidebar-header {
        display: flex;
        align-items: center;
        padding: 10px;
        color: #fff;
    }

    .sidebar-menu {
        list-style-type: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-menu li a {
        display: flex;
        align-items: center;
        padding: 10px;
        color: #fff;
        text-decoration: none;
        transition: background-color 0.5s;
    }

    .sidebar-menu li a:hover {
        background-color: #8E3A40;
    }

    .sidebar-menu li a i {
        margin-right: 10px;
    }

    .sidebar-logo {
        text-align: center;
        margin: 10px 0;
    }

    .accordion-body ul {
        list-style-type: none;
        padding-left: 0;
    }

    .accordion-body li {
        padding-left: 0;
    }

    .offcanvas-body {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .logout-button {
        margin-top: auto;
        padding: 10px;
        background-color: #BA4D52;
    }

    .logout-button a {
        display: block;
        text-align: center;
        color: #fff;
        text-decoration: none;
    }

    .burgerbar {
        position: fixed;
        top: 10px;
        left: 10px;
        z-index: 1000;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    }
</style>

<button class="btn burgerbar position-absolute shadow" type="button" data-bs-toggle="offcanvas"
    data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar" style="background-color: #BA4D52;">
    &#9776;
</button>

<body>

    <div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="offcanvasSidebar"
        aria-labelledby="offcanvasSidebarLabel">
        <div class="offcanvas-header sidebar-header">
            <h5 class="offcanvas-title" id="offcanvasSidebarLabel">Abana Kitchen Ordering System</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="text-center">
                <img src="/abana-kitchen-ordering-system/assets/images/logo.png" class="sidebar-logo"
                    style="width: 150px; height: 150px; border-radius: 50%;" alt="Logo">
            </div>
            <ul class="sidebar-menu">
                <li>
                    <hr style="height: 5px; background-color: #ffffff;">
                </li>
                <li class="rounded mb-2"><a href="/abana-kitchen-ordering-system/dashboard/home.php" class="rounded"><i
                            class="bi bi-house"></i><span>Home</span></a>
                </li>
                <li class="rounded mb-2"> <a href="/abana-kitchen-ordering-system/dashboard/staff-list.php" name=""
                        id="" class="btn btn-red p-2" href="#" role="button"><i class="bi bi-person-vcard"></i> Staff
                        List</a>
                </li>
                <li class="rounded mb-2"><a href="/abana-kitchen-ordering-system/dashboard/product-list.php" name=""
                        id="" class="btn btn-red p-2" href="#" role="button"><i class="bi bi-basket3-fill"></i> Product
                        List</a>

                </li>
                <li class="rounded mb-2"><a href="/abana-kitchen-ordering-system/dashboard/orders-list.php" name=""
                        id="" class="btn btn-red p-2" href="#" role="button"><i class="bi bi-card-list"></i> Order
                        List</a>

                </li>
                <li class="rounded mb-2"><a href="/abana-kitchen-ordering-system/dashboard/receipt-list.php" name=""
                        id="" class="btn btn-red p-2" href="#" role="button"><i class="bi bi-receipt"></i> Receipt
                        List</a>

                </li>
                <li class="rounded mb-2"><a href="/abana-kitchen-ordering-system/reports/daily_sales_report.php" name="" id=""
                        class="btn btn-red p-2" href="#" role="button"><i class="bi bi-receipt-cutoff"></i>Daily Sales
                        Reports</a>
                </li>
                <li>
                <li class="rounded mb-2"><a href="/abana-kitchen-ordering-system/reports/weekly_sales_report.php" name="" id=""
                        class="btn btn-red p-2" href="#" role="button"><i class="bi bi-receipt-cutoff"></i>Weekly Sales
                        Reports</a>

                </li>
                <li>
                    <div class="accordion" id="sidebarAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne"
                                    style="background-color: #BA4D52; color: #ffffff;">
                                    Query Reports
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne"
                                data-bs-parent="#sidebarAccordion" style="background-color: #BA4D52;">
                                <div class="accordion-body">
                                    <ul>
                                        <li><a href="/abana-kitchen-ordering-system/reports/home.php#top-selling">Most Ordered Products</a></li>
                                        <li><a href="/abana-kitchen-ordering-system/reports/home.php#frozen">Orders for Frozen Products</a></li>
                                        <li><a href="/abana-kitchen-ordering-system/dashboard/home.php#highestTotalAmount">Orders with Highest Total Amount</a></li>
                                        <li><a href="/abana-kitchen-ordering-system/dashboard/staff-list.php">Search for Customers</a></li>
                                        <li><a href="/abana-kitchen-ordering-system/dashboard/staff-list.php#staffs">Staff and Their Supervisors</a></li>
                                        <li><a href="/abana-kitchen-ordering-system/dashboard/orders-list.php#search">Search Orders Assigned to Staff</a></li>
                                        <li><a href="/abana-kitchen-ordering-system/dashboard/staff-list.php#unassignedStaff">Unassigned Staff</a></li>
                                        <li><a href="/abana-kitchen-ordering-system/dashboard/orders_list.php#betweenDates">Search Orders Between Certain Date</a></li>
                                        <li><a href="/abana-kitchen-ordering-system/reports/home.php#paymentStatistics">Count of QR vs Online Transfer</a></li>
                                        <li><a href="/abana-kitchen-ordering-system/dashboard/home.php#scheduledCompletion">Products Scheduled for Completion</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
            <hr style="height: 5px; background-color: #ffffff;">
            <div class="logout-button">
                <form method="post">
                    <a href="logout.php" class="rounded" type="submit" name="logout" value="Logout"><i
                            class="bi bi-box-arrow-left"></i><span> Logout</a>
                </form>
            </div>
        </div>
    </div>
</body>