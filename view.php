<?php
session_start();

// Security Check: If the user is not logged in, redirect to the login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// --- Database Credentials ---
$servername = "localhost";
$username = "u361874700_wof";
$password = "@TtziJrV#pE3";
$dbname = "u361874700_wof_registrati";

// --- Listen for the Export Action ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $conn_export = new mysqli($servername, $username, $password, $dbname);
    if ($conn_export->connect_error) { die("Connection failed: " . $conn_export->connect_error); }

    // MODIFIED: Added 'interested_in_blood_donation' to the query
    $sql_export = "SELECT
                pc.full_name AS primary_name, pc.contact_number AS primary_contact, pc.email AS primary_email,
                pc.address AS primary_address, pc.emergency_contact_name, pc.emergency_contact_number, pc.registration_timestamp,
                m.unique_rider_id, m.first_name, m.last_name, m.dob, m.sex, m.blood_group, m.interested_in_blood_donation, m.tshirt_size,
                m.whatsapp_number AS member_whatsapp, m.medical_conditions
            FROM primary_contacts pc
            JOIN members m ON pc.id = m.primary_contact_id
            ORDER BY pc.registration_timestamp DESC, m.id ASC";
    $result_export = $conn_export->query($sql_export);

    $filename = "wof_registrations_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');

    // MODIFIED: Added 'Blood Donation' to the CSV header
    fputcsv($output, [
        'Rider ID', 'Rider Name', 'Registration Date', 'Primary Contact Name', 'Primary Contact Number', 'Primary Email', 'Emergency Contact',
        'Rider DOB', 'Sex', 'Blood Group', 'Blood Donation', 'T-Shirt Size', 'Rider WhatsApp', 'Medical Conditions'
    ]);

    if ($result_export->num_rows > 0) {
        while ($row = $result_export->fetch_assoc()) {
            // MODIFIED: Added blood donation status to the CSV row
            fputcsv($output, [
                $row['unique_rider_id'], trim($row['first_name'] . ' ' . $row['last_name']), date("Y-m-d H:i:s", strtotime($row['registration_timestamp'])),
                $row['primary_name'], $row['primary_contact'], $row['primary_email'], $row['emergency_contact_name'] . ' (' . $row['emergency_contact_number'] . ')',
                $row['dob'], $row['sex'], $row['blood_group'], $row['interested_in_blood_donation'], $row['tshirt_size'],
                $row['member_whatsapp'], $row['medical_conditions']
            ]);
        }
    }
    $conn_export->close();
    fclose($output);
    exit();
}

// --- Regular Page Logic with Pagination and Counts ---
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$total_riders = $conn->query("SELECT COUNT(*) as count FROM members")->fetch_assoc()['count'];
$total_entries = $conn->query("SELECT COUNT(*) as count FROM primary_contacts")->fetch_assoc()['count'];

$limit = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$primary_ids_result = $conn->query("SELECT id FROM primary_contacts ORDER BY registration_timestamp DESC LIMIT $limit OFFSET $offset");
$page_primary_ids = [];
while($row = $primary_ids_result->fetch_assoc()) {
    $page_primary_ids[] = $row['id'];
}

$registrations = [];
if (!empty($page_primary_ids)) {
    $ids_string = implode(',', $page_primary_ids);
    // MODIFIED: Added 'interested_in_blood_donation' to the query
    $sql = "SELECT
                pc.full_name AS primary_name, pc.contact_number AS primary_contact, pc.email AS primary_email,
                pc.address AS primary_address, pc.emergency_contact_name, pc.emergency_contact_number, pc.registration_timestamp,
                m.id AS member_id, m.unique_rider_id, m.primary_contact_id, m.first_name, m.last_name, m.dob, m.sex,
                m.blood_group, m.interested_in_blood_donation, m.tshirt_size, m.whatsapp_number AS member_whatsapp, m.medical_conditions
            FROM primary_contacts pc
            JOIN members m ON pc.id = m.primary_contact_id
            WHERE pc.id IN ($ids_string)
            ORDER BY pc.registration_timestamp DESC, m.id ASC";

    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $registrations[$row['primary_contact_id']]['primary_details'] = $row;
            $registrations[$row['primary_contact_id']]['members'][] = $row;
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Registrations - Wheels of Freedom</title>
    <link rel="icon" type="image/png" href="/imgs/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Teachers&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Teachers', sans-serif; background-color: #f4f4f4; color: #333; line-height: 1.6; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: auto; background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;}
        h1 { color: #1a237e; margin: 0; }
        .header-buttons { display: flex; gap: 10px; }
        .btn { display: inline-block; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; border: none; cursor: pointer; }
        .export-btn { background: #008000; }
        .export-btn:hover { background: #006000; }
        .logout-btn { background: #c9302c; }
        .logout-btn:hover { background: #ac2925; }
        .stats-bar { display: flex; gap: 30px; background-color: #eef; padding: 15px; border-radius: 8px; margin-bottom: 20px; justify-content: center; }
        .stat { text-align: center; }
        .stat-value { font-size: 2em; font-weight: bold; color: #1a237e; }
        .stat-label { font-size: 1em; color: #555; }
        .pagination { text-align: center; margin: 30px 0; }
        .pagination a { color: #1a237e; padding: 8px 16px; text-decoration: none; border: 1px solid #ddd; margin: 0 4px; border-radius: 4px; }
        .pagination a.active { background-color: #1a237e; color: white; border: 1px solid #1a237e; }
        .pagination a:hover:not(.active) { background-color: #ddd; }
        .registration-group { border: 1px solid #ccc; border-radius: 8px; margin-bottom: 30px; padding: 20px; }
        .primary-details h2 { color: #ff6f00; margin-top: 0; border-bottom: 1px solid #ff6f00; padding-bottom: 5px; }
        .primary-details p { margin: 5px 0; }
        .primary-details strong { color: #1a237e; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #1a237e; color: #fff; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .no-registrations { text-align: center; font-size: 1.2em; color: #777; padding: 50px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Wheels of Freedom - All Registrations</h1>
            <div class="header-buttons">
                <a href="view.php?export=csv" class="btn export-btn">Export All to CSV</a>
                <a href="logout.php" class="btn logout-btn">Logout</a>
            </div>
        </div>

        <div class="stats-bar">
            <div class="stat">
                <div class="stat-value"><?php echo $total_entries; ?></div>
                <div class="stat-label">Total Entries</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo $total_riders; ?></div>
                <div class="stat-label">Total Riders</div>
            </div>
        </div>

        <?php if (!empty($registrations)): ?>
            <?php foreach ($registrations as $group): ?>
                <div class="registration-group">
                    <div class="primary-details">
                        <h2>Primary Contact Details</h2>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($group['primary_details']['primary_name']); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($group['primary_details']['primary_contact']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($group['primary_details']['primary_email']); ?></p>
                        <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($group['primary_details']['emergency_contact_name']) . ' (' . htmlspecialchars($group['primary_details']['emergency_contact_number']) . ')'; ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($group['primary_details']['primary_address']); ?></p>
                        <p><strong>Registered On:</strong> <?php echo date("d M Y, h:i A", strtotime($group['primary_details']['registration_timestamp'])); ?></p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Rider ID</th>
                                <th>Name</th>
                                <th>Date of Birth</th>
                                <th>Sex</th>
                                <th>Blood Group</th>
                                <th>Blood Donation</th>
                                <th>T-Shirt</th>
                                <th>WhatsApp No.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($group['members'] as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['unique_rider_id']); ?></td>
                                    <td><?php echo htmlspecialchars(trim($member['first_name'] . ' ' . $member['last_name'])); ?></td>
                                    <td><?php echo date("d M Y", strtotime($member['dob'])); ?></td>
                                    <td><?php echo htmlspecialchars($member['sex']); ?></td>
                                    <td><?php echo htmlspecialchars($member['blood_group']); ?></td>
                                    <td><?php echo htmlspecialchars($member['interested_in_blood_donation']); ?></td>
                                    <td><?php echo htmlspecialchars($member['tshirt_size']); ?></td>
                                    <td><?php echo htmlspecialchars($member['member_whatsapp']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>

            <div class="pagination">
                <?php
                $total_pages = ceil($total_entries / $limit);
                if ($total_pages > 1):
                    if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                    <?php endif;

                    for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php if ($i == $page) echo 'active'; ?>"><?php echo $i; ?></a>
                    <?php endfor;

                    if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                    <?php endif;
                endif;
                ?>
            </div>

        <?php else: ?>
            <p class="no-registrations">There are no registrations to display yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>