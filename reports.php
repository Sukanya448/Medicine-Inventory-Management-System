
<?php
include "config/db.php";

// Total medicines
$totalMedicine = $conn->query(
    "SELECT COUNT(*) AS total FROM Medicine"
)->fetch_assoc()['total'];

// Total suppliers
$totalSupplier = $conn->query(
    "SELECT COUNT(*) AS total FROM Supplier"
)->fetch_assoc()['total'];

// Total purchase amount
$totalPurchase = $conn->query(
    "SELECT COALESCE(SUM(total_amount), 0) AS total
     FROM Purchase"
)->fetch_assoc()['total'];

// Total sales amount
$totalSales = $conn->query(
    "SELECT COALESCE(SUM(total_amount), 0) AS total
     FROM Sales"
)->fetch_assoc()['total'];

// Medicine-wise stock
$stock = $conn->query(
    "SELECT
        m.medicine_name,
        COALESCE(SUM(
            CASE
                WHEN b.expiry_date > CURDATE()
                THEN b.quantity
                ELSE 0
            END
        ), 0) AS available_stock
     FROM Medicine m
     LEFT JOIN Medicine_Batch b
        ON m.medicine_id = b.medicine_id
     GROUP BY m.medicine_id, m.medicine_name
     ORDER BY m.medicine_name"
);

// Medicine-wise sales
$medicineSales = $conn->query(
    "SELECT
        m.medicine_name,
        COALESCE(SUM(si.quantity), 0) AS total_sold,
        COALESCE(SUM(si.quantity * si.selling_price), 0)
            AS sales_amount
     FROM Medicine m
     LEFT JOIN Medicine_Batch b
        ON m.medicine_id = b.medicine_id
     LEFT JOIN Sale_Item si
        ON b.batch_id = si.batch_id
     GROUP BY m.medicine_id, m.medicine_name
     ORDER BY total_sold DESC"
);

// Supplier-wise purchases
$supplierPurchases = $conn->query(
    "SELECT
        s.supplier_name,
        COUNT(p.purchase_id) AS total_purchases,
        COALESCE(SUM(p.total_amount), 0) AS purchase_amount
     FROM Supplier s
     LEFT JOIN Purchase p
        ON s.supplier_id = p.supplier_id
     GROUP BY s.supplier_id, s.supplier_name"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventory Reports</title>
    <link rel="stylesheet"
          href="/MedicineInventory/css/style.css?v=3">
</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">

    <h1>Inventory Reports</h1>

    <div class="cards">

        <div class="card">
            <h3>Total Medicines</h3>
            <h2><?php echo $totalMedicine; ?></h2>
        </div>

        <div class="card">
            <h3>Total Suppliers</h3>
            <h2><?php echo $totalSupplier; ?></h2>
        </div>

        <div class="card">
            <h3>Total Purchases</h3>
            <h2>₹<?php echo number_format($totalPurchase, 2); ?></h2>
        </div>

        <div class="card">
            <h3>Total Sales</h3>
            <h2>₹<?php echo number_format($totalSales, 2); ?></h2>
        </div>

    </div>

    <h2>Medicine-wise Stock Report</h2>

    <table>
        <tr>
            <th>Medicine</th>
            <th>Available Stock</th>
        </tr>

        <?php while ($row = $stock->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
            <td><?php echo $row['available_stock']; ?></td>
        </tr>
        <?php endwhile; ?>

    </table>

    <h2>Medicine-wise Sales Report</h2>

    <table>
        <tr>
            <th>Medicine</th>
            <th>Quantity Sold</th>
            <th>Sales Amount</th>
        </tr>

        <?php while ($row = $medicineSales->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
            <td><?php echo $row['total_sold']; ?></td>
            <td>₹<?php echo number_format($row['sales_amount'], 2); ?></td>
        </tr>
        <?php endwhile; ?>

    </table>

    <h2>Supplier-wise Purchase Report</h2>

    <table>
        <tr>
            <th>Supplier</th>
            <th>Total Purchases</th>
            <th>Purchase Amount</th>
        </tr>

        <?php while ($row = $supplierPurchases->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
            <td><?php echo $row['total_purchases']; ?></td>
            <td>₹<?php echo number_format($row['purchase_amount'], 2); ?></td>
        </tr>
        <?php endwhile; ?>

    </table>

</div>

</body>
</html>
