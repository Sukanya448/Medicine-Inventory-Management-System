
<?php
include "config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $batch_id = (int)$_POST["batch_id"];
    $quantity = (int)$_POST["quantity"];
    $price = (float)$_POST["selling_price"];

    if ($batch_id <= 0 || $quantity <= 0 || $price < 0) {
        $error = "Enter valid sale details.";
    } else {

        try {

            $conn->begin_transaction();

            // Lock selected batch to prevent simultaneous overselling
            $stmt = $conn->prepare(
                "SELECT quantity, expiry_date
                 FROM Medicine_Batch
                 WHERE batch_id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param("i", $batch_id);
            $stmt->execute();

            $batch = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$batch) {
                throw new Exception("Batch not found.");
            }

            if ($batch['expiry_date'] <= date("Y-m-d")) {
                throw new Exception("Expired medicine cannot be sold.");
            }

            if ($batch['quantity'] < $quantity) {
                throw new Exception("Insufficient stock available.");
            }

            $total = $quantity * $price;

            // Create sale record
            $stmt = $conn->prepare(
                "INSERT INTO Sales (sale_date, total_amount)
                 VALUES (CURDATE(), ?)"
            );

            $stmt->bind_param("d", $total);
            $stmt->execute();

            $sale_id = $conn->insert_id;
            $stmt->close();

            // Insert sale item.
            // Check_Sale and Reduce_Stock triggers execute here.
            $stmt = $conn->prepare(
                "INSERT INTO Sale_Item
                (sale_id, batch_id, quantity, selling_price)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "iiid",
                $sale_id,
                $batch_id,
                $quantity,
                $price
            );

            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $message = "Sale recorded successfully! Stock reduced.";

        } catch (Throwable $e) {

            $conn->rollback();

            $error = "Sale failed: " . $e->getMessage();
        }
    }
}

// Only show valid, non-expired batches with stock
$batches = $conn->query(
    "SELECT b.batch_id, b.batch_no,
            b.quantity, b.expiry_date,
            m.medicine_name, m.unit_price
     FROM Medicine_Batch b
     JOIN Medicine m ON b.medicine_id = m.medicine_id
     WHERE b.quantity > 0
       AND b.expiry_date > CURDATE()
     ORDER BY b.expiry_date ASC"
);

// Sales history
$sales = $conn->query(
    "SELECT s.sale_id, s.sale_date, s.total_amount,
            COUNT(si.sale_item_id) AS item_count
     FROM Sales s
     LEFT JOIN Sale_Item si ON s.sale_id = si.sale_id
     GROUP BY s.sale_id, s.sale_date, s.total_amount
     ORDER BY s.sale_id DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales Management</title>
    <link rel="stylesheet"
          href="/MedicineInventory/css/style.css?v=3">
</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">

    <h1>Sales Management</h1>

    <?php if ($message): ?>
        <p class="message">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <h2>Record New Sale</h2>

    <form method="POST">

        <label>Select Medicine Batch</label>

        <select name="batch_id" required>
            <option value="">Choose Medicine</option>

            <?php while ($b = $batches->fetch_assoc()): ?>

                <option value="<?php echo $b['batch_id']; ?>">

                    <?php
                    echo htmlspecialchars($b['medicine_name'])
                        . " | Batch: "
                        . htmlspecialchars($b['batch_no'])
                        . " | Stock: "
                        . $b['quantity']
                        . " | Expiry: "
                        . $b['expiry_date']
                        . " | Price: ₹"
                        . number_format($b['unit_price'], 2);
                    ?>

                </option>

            <?php endwhile; ?>
        </select>

        <label>Sale Quantity</label>
        <input type="number" name="quantity"
               min="1" required>

        <label>Selling Price Per Unit (₹)</label>
        <input type="number" name="selling_price"
               min="0" step="0.01" required>

        <button type="submit">Complete Sale</button>

    </form>

    <h2>Sales History</h2>

    <table>
        <tr>
            <th>Sale ID</th>
            <th>Date</th>
            <th>Items</th>
            <th>Total Amount</th>
        </tr>

        <?php while ($s = $sales->fetch_assoc()): ?>
        <tr>
            <td><?php echo $s['sale_id']; ?></td>
            <td><?php echo $s['sale_date']; ?></td>
            <td><?php echo $s['item_count']; ?></td>
            <td>₹<?php echo number_format($s['total_amount'], 2); ?></td>
        </tr>
        <?php endwhile; ?>

    </table>

</div>

</body>
</html>
