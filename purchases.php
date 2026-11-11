
<?php
include "config/db.php";

$message = "";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $supplier_id = (int)$_POST["supplier_id"];
    $medicine_id = (int)$_POST["medicine_id"];
    $batch_no = trim($_POST["batch_no"]);
    $expiry_date = $_POST["expiry_date"];
    $quantity = (int)$_POST["quantity"];
    $purchase_price = (float)$_POST["purchase_price"];

    if (
        $supplier_id <= 0 ||
        $medicine_id <= 0 ||
        $batch_no === "" ||
        $quantity <= 0 ||
        $purchase_price < 0 ||
        $expiry_date <= date("Y-m-d")
    ) {
        $message = "Please enter valid purchase details and a future expiry date.";
    } else {

        try {

            $conn->begin_transaction();

            // Calculate purchase amount
            $total = $quantity * $purchase_price;

            // Create purchase record
            $stmt = $conn->prepare(
                "INSERT INTO Purchase
                (supplier_id, purchase_date, total_amount)
                VALUES (?, CURDATE(), ?)"
            );

            $stmt->bind_param("id", $supplier_id, $total);
            $stmt->execute();

            $purchase_id = $conn->insert_id;
            $stmt->close();

            // Create a new medicine batch with zero stock
            $stmt = $conn->prepare(
                "INSERT INTO Medicine_Batch
                (medicine_id, batch_no, expiry_date, quantity)
                VALUES (?, ?, ?, 0)"
            );

            $stmt->bind_param(
                "iss",
                $medicine_id,
                $batch_no,
                $expiry_date
            );

            $stmt->execute();

            $batch_id = $conn->insert_id;
            $stmt->close();

            // Insert purchase item.
            // Increase_Stock trigger increases the batch quantity.
            $stmt = $conn->prepare(
                "INSERT INTO Purchase_Item
                (purchase_id, batch_id, quantity, purchase_price)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "iiid",
                $purchase_id,
                $batch_id,
                $quantity,
                $purchase_price
            );

            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $message = "Purchase completed! Stock increased successfully.";

        } catch (Throwable $e) {

            $conn->rollback();

            $message = "Purchase failed: " . $e->getMessage();
        }
    }
}

$suppliers = $conn->query(
    "SELECT supplier_id, supplier_name
     FROM Supplier ORDER BY supplier_name"
);

$medicines = $conn->query(
    "SELECT medicine_id, medicine_name
     FROM Medicine ORDER BY medicine_name"
);

$purchases = $conn->query(
    "SELECT p.purchase_id, p.purchase_date,
            p.total_amount, s.supplier_name
     FROM Purchase p
     JOIN Supplier s ON p.supplier_id = s.supplier_id
     ORDER BY p.purchase_id DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Purchase Management</title>
    <link rel="stylesheet"
          href="/MedicineInventory/css/style.css?v=3">
</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">

    <h1>Purchase Management</h1>

    <?php if ($message): ?>
        <p class="message">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <h2>Record New Purchase</h2>

    <form method="POST">

        <label>Select Supplier</label>

        <select name="supplier_id" required>
            <option value="">Choose Supplier</option>

            <?php while ($s = $suppliers->fetch_assoc()): ?>
                <option value="<?php echo $s['supplier_id']; ?>">
                    <?php echo htmlspecialchars($s['supplier_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Select Medicine</label>

        <select name="medicine_id" required>
            <option value="">Choose Medicine</option>

            <?php while ($m = $medicines->fetch_assoc()): ?>
                <option value="<?php echo $m['medicine_id']; ?>">
                    <?php echo htmlspecialchars($m['medicine_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Batch Number</label>
        <input type="text" name="batch_no"
               maxlength="50" required>

        <label>Expiry Date</label>
        <input type="date" name="expiry_date"
               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
               required>

        <label>Purchase Quantity</label>
        <input type="number" name="quantity"
               min="1" required>

        <label>Purchase Price Per Unit (₹)</label>
        <input type="number" name="purchase_price"
               min="0" step="0.01" required>

        <button type="submit">Record Purchase</button>

    </form>

    <h2>Purchase History</h2>

    <table>
        <tr>
            <th>Purchase ID</th>
            <th>Supplier</th>
            <th>Date</th>
            <th>Total Amount</th>
        </tr>

        <?php while ($p = $purchases->fetch_assoc()): ?>
        <tr>
            <td><?php echo $p['purchase_id']; ?></td>
            <td><?php echo htmlspecialchars($p['supplier_name']); ?></td>
            <td><?php echo $p['purchase_date']; ?></td>
            <td>₹<?php echo number_format($p['total_amount'], 2); ?></td>
        </tr>
        <?php endwhile; ?>

    </table>

</div>

</body>
</html>
