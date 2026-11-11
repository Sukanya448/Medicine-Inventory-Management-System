
<?php
include "config/db.php";

$result = $conn->query(
    "SELECT
        m.medicine_id,
        m.medicine_name,
        m.reorder_level,
        COALESCE(SUM(b.quantity), 0) AS available_stock
     FROM Medicine m
     LEFT JOIN Medicine_Batch b
        ON m.medicine_id = b.medicine_id
        AND b.expiry_date > CURDATE()
     GROUP BY
        m.medicine_id,
        m.medicine_name,
        m.reorder_level
     HAVING COALESCE(SUM(b.quantity), 0)
        <= m.reorder_level
     ORDER BY available_stock ASC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Low Stock Alerts</title>
    <link rel="stylesheet"
          href="/MedicineInventory/css/style.css?v=3">
</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">

    <h1>Low Stock Medicines</h1>

    <p>Medicines that have reached their reorder level.</p>

    <table>
        <tr>
            <th>Medicine ID</th>
            <th>Medicine Name</th>
            <th>Available Stock</th>
            <th>Reorder Level</th>
            <th>Status</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>

        <tr>
            <td><?php echo $row['medicine_id']; ?></td>

            <td>
                <?php echo htmlspecialchars($row['medicine_name']); ?>
            </td>

            <td><?php echo $row['available_stock']; ?></td>

            <td><?php echo $row['reorder_level']; ?></td>

            <td>
                <span class="status status-danger">
                    Low Stock
                </span>
            </td>
        </tr>

        <?php endwhile; ?>

    </table>

</div>

</body>
</html>
