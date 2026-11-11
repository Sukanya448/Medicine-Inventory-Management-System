
<?php
include "config/db.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["medicine_name"]);
    $category = trim($_POST["category"]);
    $manufacturer = trim($_POST["manufacturer"]);
    $price = $_POST["unit_price"];
    $reorder = $_POST["reorder_level"];

    if (
        $name === "" ||
        !is_numeric($price) ||
        !is_numeric($reorder) ||
        $price < 0 ||
        $reorder < 0 ||
        (int)$reorder != $reorder
    ) {
        $error = "Enter valid medicine details.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO Medicine
            (medicine_name, category, manufacturer,
             unit_price, reorder_level)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssdi",
            $name,
            $category,
            $manufacturer,
            $price,
            $reorder
        );

        if ($stmt->execute()) {
            $message = "Medicine added successfully!";
        } else {
            $error = "Unable to add medicine.";
        }

        $stmt->close();
    }
}

$result = $conn->query(
    "SELECT * FROM Medicine ORDER BY medicine_id DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Medicine Management</title>
    <link rel="stylesheet" href="/MedicineInventory/css/style.css?v=3">">
</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">

    <h1>Medicine Management</h1>

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

    <h2>Add New Medicine</h2>

    <form method="POST">

        <label>Medicine Name</label>
        <input type="text" name="medicine_name"
               maxlength="100" required>

        <label>Category</label>
        <input type="text" name="category"
               maxlength="50">

        <label>Manufacturer</label>
        <input type="text" name="manufacturer"
               maxlength="100">

        <label>Unit Price</label>
        <input type="number" name="unit_price"
               min="0" step="0.01" required>

        <label>Reorder Level</label>
        <input type="number" name="reorder_level"
               min="0" step="1" required>

        <button type="submit">Add Medicine</button>

    </form>

    <h2 style="margin-top:30px;">
        Medicine List
    </h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Manufacturer</th>
            <th>Price</th>
            <th>Reorder Level</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>

        <tr>
            <td><?php echo $row['medicine_id']; ?></td>

            <td>
                <?php echo htmlspecialchars($row['medicine_name']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row['category'] ?? ''); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row['manufacturer'] ?? ''); ?>
            </td>

            <td>
                ₹<?php echo number_format($row['unit_price'], 2); ?>
            </td>

            <td><?php echo $row['reorder_level']; ?></td>
        </tr>

        <?php endwhile; ?>

    </table>

</div>

</body>
</html>
