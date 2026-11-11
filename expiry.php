
<?php
include "config/db.php";

// Expired batches
$expired = $conn->query(
    "SELECT m.medicine_name,
            b.batch_no,
            b.quantity,
            b.expiry_date
     FROM Medicine_Batch b
     JOIN Medicine m ON b.medicine_id = m.medicine_id
     WHERE b.expiry_date < CURDATE()
       AND b.quantity > 0
     ORDER BY b.expiry_date"
);

// Batches expiring within 30 days
$expiring = $conn->query(
    "SELECT m.medicine_name,
            b.batch_no,
            b.quantity,
            b.expiry_date
     FROM Medicine_Batch b
     JOIN Medicine m ON b.medicine_id = m.medicine_id
     WHERE b.expiry_date >= CURDATE()
       AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
       AND b.quantity > 0
     ORDER BY b.expiry_date"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Expiry Management</title>
    <link rel="stylesheet"
          href="/MedicineInventory/css/style.css?v=3">
</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">

    <h1>Medicine Expiry Management</h1>

    <h2>Expired Medicines</h2>

    <table>
        <tr>
            <th>Medicine</th>
            <th>Batch</th>
            <th>Quantity</th>
            <th>Expiry Date</th>
            <th>Status</th>
        </tr>

        <?php while ($row = $expired->fetch_assoc()): ?>

        <tr>
            <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
            <td><?php echo htmlspecialchars($row['batch_no']); ?></td>
            <td><?php echo $row['quantity']; ?></td>
            <td><?php echo $row['expiry_date']; ?></td>
            <td>
                <span class="status status-danger">Expired</span>
            </td>
        </tr>

        <?php endwhile; ?>

    </table>

    <h2>Expiring Within 30 Days</h2>

    <table>
        <tr>
            <th>Medicine</th>
            <th>Batch</th>
            <th>Quantity</th>
            <th>Expiry Date</th>
            <th>Status</th>
        </tr>

        <?php while ($row = $expiring->fetch_assoc()): ?>

        <tr>
            <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
            <td><?php echo htmlspecialchars($row['batch_no']); ?></td>
            <td><?php echo $row['quantity']; ?></td>
            <td><?php echo $row['expiry_date']; ?></td>
            <td>
                <span class="status status-warning">Expiring Soon</span>
            </td>
        </tr>

        <?php endwhile; ?>

    </table>

</div>

</body>
</html>
