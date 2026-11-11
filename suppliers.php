
<?php
include "config/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["supplier_name"]);
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);

    if ($name === "") {
        $message = "Supplier name is required.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO Supplier
             (supplier_name, phone, address)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param("sss", $name, $phone, $address);

        if ($stmt->execute()) {
            $message = "Supplier added successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$result = $conn->query(
    "SELECT * FROM Supplier ORDER BY supplier_id DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Supplier Management</title>
    <link rel="stylesheet"
          href="/MedicineInventory/css/style.css?v=3">
</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">

    <h1>Supplier Management</h1>

    <?php if ($message): ?>
        <p class="message">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <h2>Add New Supplier</h2>

    <form method="POST">

        <label>Supplier Name</label>
        <input type="text" name="supplier_name"
               maxlength="100" required>

        <label>Phone Number</label>
        <input type="text" name="phone"
               maxlength="15">

        <label>Address</label>
        <input type="text" name="address"
               maxlength="255">

        <button type="submit">Add Supplier</button>

    </form>

    <h2>Supplier List</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Supplier Name</th>
            <th>Phone</th>
            <th>Address</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>

        <tr>
            <td><?php echo $row['supplier_id']; ?></td>

            <td>
                <?php echo htmlspecialchars($row['supplier_name']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row['phone'] ?? ''); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row['address'] ?? ''); ?>
            </td>
        </tr>

        <?php endwhile; ?>

    </table>

</div>

</body>
</html>
