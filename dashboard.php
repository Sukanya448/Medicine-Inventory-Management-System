
<?php
include "config/db.php";

$medicineResult = $conn->query(
    "SELECT COUNT(*) AS total FROM Medicine"
);
$totalMedicines = $medicineResult->fetch_assoc()['total'];

$stockResult = $conn->query(
    "SELECT COALESCE(SUM(quantity),0) AS total
     FROM Medicine_Batch
     WHERE expiry_date > CURDATE()"
);
$totalStock = $stockResult->fetch_assoc()['total'];

$expiryResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM Medicine_Batch
     WHERE expiry_date BETWEEN CURDATE()
     AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
);
$expiryCount = $expiryResult->fetch_assoc()['total'];

$salesResult = $conn->query(
    "SELECT COALESCE(SUM(total_amount),0) AS total
     FROM Sales"
);
$totalSales = $salesResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Medicine Inventory Dashboard</title>
    <link rel="stylesheet"
      href="/MedicineInventory/css/style.css?v=3">
</head>

<body>

<div class="sidebar">
    <h2>Medicine System</h2>

    <a href="dashboard.php">Dashboard</a>
    <a href="medicines.php">Medicines</a>
    <a href="suppliers.php">Suppliers</a>
    <a href="purchases.php">Purchases</a>
    <a href="sales.php">Sales</a>
    <a href="expiry.php">Expiry Alerts</a>
    <a href="reports.php">Reports</a>
</div>

<div class="main">

    <h1>Medicine Inventory Dashboard</h1>
    <p>Welcome to your pharmacy management system.</p>

    <div class="cards">

        <div class="card">
            <h3>Total Medicines</h3>
            <h2><?php echo $totalMedicines; ?></h2>
        </div>

        <div class="card">
            <h3>Available Stock</h3>
            <h2><?php echo $totalStock; ?></h2>
        </div>

        <div class="card">
            <h3>Expiring Batches</h3>
            <h2><?php echo $expiryCount; ?></h2>
        </div>

        <div class="card">
            <h3>Total Sales</h3>
            <h2>₹<?php echo number_format($totalSales, 2); ?></h2>
        </div>

    </div>

</div>

</body>
</html>
