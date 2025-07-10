<?php
// Set timezone to Indian Standard Time
date_default_timezone_set('Asia/Kolkata');

// Start the session to store registration IDs for the thank you page
session_start();

// Database connection variables
$servername = "localhost";
$username = "u361874700_wof";
$password = "@TtziJrV#pE3";
$dbname = "u361874700_wof_registrati";


// Processing form data when form is submitted
if (isset($_POST['submit'])) {

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // --- Primary Contact Details (Raw from POST) ---
    $primary_name = $_POST['primary_name'];
    $primary_contact = $_POST['primary_contact'];
    $primary_email = $_POST['primary_email'];
    $primary_address = $_POST['primary_address'];
    $emergency_name = $_POST['emergency_name'];
    $emergency_contact = $_POST['emergency_contact'];

    // Determine the WhatsApp Number
    $is_whatsapp = $_POST['is_whatsapp_number'];
    if ($is_whatsapp === 'No' && !empty($_POST['whatsapp_number'])) {
        $whatsapp_number = $_POST['whatsapp_number'];
    } else {
        $whatsapp_number = $primary_contact;
    }

    // Check if contact number is unique
    $check_sql = "SELECT id FROM primary_contacts WHERE contact_number = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("s", $primary_contact);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    if ($result->num_rows > 0) {
        echo "<script>alert('Error: This contact number (" . htmlspecialchars($primary_contact) . ") has already been registered.'); window.history.back();</script>";
        $stmt_check->close();
        $conn->close();
        exit();
    }
    $stmt_check->close();

    // Insert primary contact using prepared statements
    $sql_primary = "INSERT INTO primary_contacts (full_name, contact_number, email, address, emergency_contact_name, emergency_contact_number, whatsapp_number) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_primary = $conn->prepare($sql_primary);
    $stmt_primary->bind_param("sssssss", $primary_name, $primary_contact, $primary_email, $primary_address, $emergency_name, $emergency_contact, $whatsapp_number);

    if ($stmt_primary->execute()) {
        $primary_contact_id = $conn->insert_id;
        $registered_members = [];

        // --- Member Details Insertion ---
        if (isset($_POST['members']) && is_array($_POST['members'])) {
            $last_member_id_result = $conn->query("SELECT id FROM members ORDER BY id DESC LIMIT 1");
            $last_member_id = ($last_member_id_result->num_rows > 0) ? $last_member_id_result->fetch_assoc()['id'] : 0;

            $sql_member = "INSERT INTO members (unique_rider_id, primary_contact_id, first_name, last_name, dob, sex, blood_group, interested_in_blood_donation, tshirt_size, whatsapp_number, medical_conditions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_member = $conn->prepare($sql_member);

            $stmt_member->bind_param("sisssssssss", $unique_rider_id, $primary_contact_id, $first_name, $last_name, $dob, $sex, $blood_group, $blood_donation, $tshirt_size, $member_whatsapp, $medical_conditions);

            foreach ($_POST['members'] as $key => $memberData) {
                $last_member_id++;
                
                $unique_rider_id = "WR" . str_pad($last_member_id, 4, '0', STR_PAD_LEFT);

                if ($key == 1) {
                    $name_parts = explode(' ', $primary_name, 2);
                    $first_name = $name_parts[0];
                    $last_name = $name_parts[1] ?? '';
                } else {
                    $first_name = $memberData['first_name'];
                    $last_name = $memberData['last_name'];
                }

                $dob = $memberData['dob'];
                $sex = $memberData['sex'];
                $blood_group = $memberData['blood_group'];
                $blood_donation = $memberData['blood_donation'];
                $tshirt_size = $memberData['tshirt_size'];
                $medical_conditions = $memberData['medical_conditions'] ?? '';

                if (isset($memberData['use_primary_whatsapp']) && $memberData['use_primary_whatsapp'] === 'No' && !empty($memberData['member_whatsapp'])) {
                    $member_whatsapp = $memberData['member_whatsapp'];
                } else {
                    $member_whatsapp = $whatsapp_number;
                }
                
                if ($stmt_member->execute()) {
                    $full_name = trim("$first_name $last_name");
                    $registered_members[] = ['name' => $full_name, 'id' => $unique_rider_id];
                }
            }
            $stmt_member->close();
        }

        $stmt_primary->close();
        $conn->close();

        $_SESSION['registered_members'] = $registered_members;
        header("Location: thankyou.php");
        exit();

    } else {
        echo "<script>alert('A database error occurred. Please try again.'); window.history.back();</script>";
        $stmt_primary->close();
        $conn->close();
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wheels of Freedom - Rider Registration</title>
    
    <link rel="icon" type="image/png" href="/imgs/logo.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Teachers&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Teachers', sans-serif; background-color: #f4f4f4; color: #333; line-height: 1.6; margin: 0; padding: 0; }
        .container { width: 80%; margin: auto; overflow: hidden; padding: 20px 0; }
        .banner img { width: 100%; height: auto; }
        .form-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h1, h2, h3 { color: #1a237e; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        .required-ast { color: red; margin-left: 2px; }
        input[type="text"], input[type="email"], input[type="tel"], input[type="date"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { display: inline-block; background: #ff6f00; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 18px; font-family: 'Teachers', sans-serif; }
        .btn:hover { background: #e65100; }
        .add-member-btn { background: #1a237e; }
        .remove-member-btn { background: #c9302c; font-size: 14px; padding: 8px 15px; margin-top: 10px; float: right; }
        .remove-member-btn:hover { background: #ac2925; }
        .member-form-section { border-top: 2px solid #ddd; padding-top: 20px; margin-top: 20px; }
        .checkbox-group { display: flex; align-items: flex-start; }
        .checkbox-group input { margin-right: 10px; margin-top: 5px; }
        .hidden { display: none; }
        .banner-desktop { display: block; }
        .banner-mobile { display: none; }

        /* ADDED: Styles for Size Chart Link */
        .size-chart-link { text-align: right; font-size: 0.9em; margin-top: 5px; }
        .size-chart-link a { color: #1a237e; text-decoration: none; }
        .size-chart-link a:hover { text-decoration: underline; }

        /* ADDED: Styles for Modal Popup */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; 
            background-color: rgba(0,0,0,0.6);
            animation-name: fadeIn;
            animation-duration: 0.3s;
        }
        .modal-content {
            position: relative;
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
            animation-name: slideIn;
            animation-duration: 0.3s;
        }
        .modal-content img {
            width: 100%;
            height: auto;
        }
        .close-btn {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 25px;
            font-size: 35px;
            font-weight: bold;
        }
        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        @keyframes fadeIn { from {opacity: 0} to {opacity: 1} }
        @keyframes slideIn { from {top: -100px; opacity: 0} to {top: 0; opacity: 1} }

        @media screen and (max-width: 768px) {
            .banner-desktop { display: none; }
            .banner-mobile { display: block; }
            .container { width: 95%; }
        }
    </style>
</head>
<body>

    <div class="banner">
        <img src="/imgs/wb_desk.gif" alt="Wheels of Freedom Banner" class="banner-desktop">
        <img src="/imgs/wb_mob.gif" alt="Wheels of Freedom Banner" class="banner-mobile">
    </div>

    <div class="container">
        <div class="form-container">
            <h1>Wheels of Freedom – Rider Registration</h1>
            <p>Join us for Wheels of Freedom, a 30 KM Tiranga Ride happening on 15th August 2025 in Bhopal. 🇮🇳🚴‍♂️</p>

            <form action="" method="post" id="registrationForm">
                <h2>Section 1: Primary Contact Details</h2>
                <div class="form-group">
                    <label for="primary_name">Full Name of Primary Contact<span class="required-ast">*</span></label>
                    <input type="text" id="primary_name" name="primary_name" required>
                </div>
                <div class="form-group">
                    <label for="primary_contact">Contact Number (10 digits, no country code)<span class="required-ast">*</span></label>
                    <input type="tel" id="primary_contact" name="primary_contact" required pattern="\d{10}" title="Please enter exactly 10 digits." oninput="this.value = this.value.replace(/[^\d]/g, '')">
                </div>
                <div class="form-group">
                    <label>Is this your WhatsApp number?<span class="required-ast">*</span></label>
                    <select id="is_whatsapp_select" name="is_whatsapp_number" onchange="togglePrimaryWhatsappField()">
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div id="primary_whatsapp_field" class="form-group hidden">
                    <label for="whatsapp_number">Please enter your WhatsApp Number<span class="required-ast">*</span></label>
                    <input type="tel" id="whatsapp_number" name="whatsapp_number" pattern="\d{10}" title="Please enter exactly 10 digits." oninput="this.value = this.value.replace(/[^\d]/g, '')">
                </div>
                <div class="form-group">
                    <label for="primary_email">Email ID<span class="required-ast">*</span></label>
                    <input type="email" id="primary_email" name="primary_email" required>
                </div>
                <div class="form-group">
                    <label for="emergency_name">Emergency Contact Name<span class="required-ast">*</span></label>
                    <input type="text" id="emergency_name" name="emergency_name" required pattern="[a-zA-Z\s]+" title="Please enter text only (no numbers or special characters).">
                </div>
                <div class="form-group">
                    <label for="emergency_contact">Emergency Contact Number<span class="required-ast">*</span></label>
                    <input type="tel" id="emergency_contact" name="emergency_contact" required pattern="\d+" title="Please enter only numbers." oninput="this.value = this.value.replace(/[^\d]/g, '')">
                </div>
                <div class="form-group">
                    <label for="primary_address">Residential Address<span class="required-ast">*</span></label>
                    <textarea id="primary_address" name="primary_address" rows="4" required></textarea>
                </div>

                <hr>

                <h2>Section 2: Member Details</h2>
                <div id="members-container"></div>

                <div class="form-group">
                    <button type="button" class="btn add-member-btn" id="addMemberBtn">Add Family Member / Friend</button>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="final_consent" name="final_consent" value="1" required>
                    <label for="final_consent">I confirm that all participants listed are voluntarily participating in this ride and agree to the consent and liability terms outlined by the organizers. I understand the risks involved and waive the organizers of any liability in case of injury or mishap.<span class="required-ast">*</span></label>
                </div>

                <div class="form-group">
                     <button type="submit" name="submit" class="btn">Register Now</button>
                </div>
            </form>
        </div>
    </div>

    <div id="sizeChartModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeSizeChart()">&times;</span>
            <img src="/imgs/size.jpeg" alt="T-Shirt Size Chart">
        </div>
    </div>

    <script>
    let memberCount = 0;

    // ADDED: Functions to control the modal popup
    const modal = document.getElementById('sizeChartModal');
    function openSizeChart() {
        modal.style.display = "block";
    }
    function closeSizeChart() {
        modal.style.display = "none";
    }
    // Close modal if user clicks outside the content area
    window.onclick = function(event) {
        if (event.target == modal) {
            closeSizeChart();
        }
    }

    function togglePrimaryWhatsappField() {
        const selectElement = document.getElementById('is_whatsapp_select');
        const whatsappField = document.getElementById('primary_whatsapp_field');
        const whatsappInput = document.getElementById('whatsapp_number');
        if (selectElement.value === 'No') {
            whatsappField.classList.remove('hidden');
            whatsappInput.required = true;
        } else {
            whatsappField.classList.add('hidden');
            whatsappInput.required = false;
        }
    }

    function toggleWhatsappField(selectElement, index) {
        const whatsappField = document.getElementById(`whatsapp_field_${index}`);
        const whatsappInput = document.getElementById(`member_whatsapp_${index}`);
        if (selectElement.value === 'No') {
            whatsappField.classList.remove('hidden');
            whatsappInput.required = true;
        } else {
            whatsappField.classList.add('hidden');
            whatsappInput.required = false;
        }
    }

    function createMemberFields(memberIndex) {
        const isFirstMember = (memberIndex === 1);
        const nameFieldsHTML = isFirstMember ?
            `<div class="hidden">
                <input type="text" name="members[${memberIndex}][first_name]" value="placeholder">
                <input type="text" name="members[${memberIndex}][last_name]" value="placeholder">
             </div>` :
            `<div class="form-group">
                <label for="first_name_${memberIndex}">First Name<span class="required-ast">*</span></label>
                <input type="text" id="first_name_${memberIndex}" name="members[${memberIndex}][first_name]" required>
            </div>
            <div class="form-group">
                <label for="last_name_${memberIndex}">Last Name<span class="required-ast">*</span></label>
                <input type="text" id="last_name_${memberIndex}" name="members[${memberIndex}][last_name]" required>
            </div>`;

        const whatsappHTML = isFirstMember ? '' :
            `<div class="form-group">
                <label>Use a different WhatsApp number for this rider?<span class="required-ast">*</span></label>
                <select name="members[${memberIndex}][use_primary_whatsapp]" onchange="toggleWhatsappField(this, ${memberIndex})">
                    <option value="Yes">No, use primary's number</option>
                    <option value="No">Yes</option>
                </select>
            </div>
            <div id="whatsapp_field_${memberIndex}" class="form-group hidden">
                <label for="member_whatsapp_${memberIndex}">Rider's WhatsApp Number<span class="required-ast">*</span></label>
                <input type="tel" id="member_whatsapp_${memberIndex}" name="members[${memberIndex}][member_whatsapp]" pattern="\\d{10}" title="Please enter exactly 10 digits." oninput="this.value = this.value.replace(/[^\\d]/g, '')">
            </div>`;
            
        const removeButtonHTML = isFirstMember ? '' :
            `<button type="button" class="btn remove-member-btn" onclick="removeMember(${memberIndex})">Remove</button>`;

        // MODIFIED: Added size-chart-link div below the T-Shirt Size select
        const memberHTML = `
            <div class="member-form-section" id="member_section_${memberIndex}">
                <h3>Rider ${memberIndex} Details ${isFirstMember ? '(Primary Contact)' : ''}</h3>
                ${removeButtonHTML}
                ${nameFieldsHTML}
                <div class="form-group">
                    <label for="dob_${memberIndex}">Date of Birth<span class="required-ast">*</span></label>
                    <input type="date" id="dob_${memberIndex}" name="members[${memberIndex}][dob]" required>
                </div>
                <div class="form-group">
                    <label>Sex<span class="required-ast">*</span></label>
                    <select name="members[${memberIndex}][sex]" required>
                        <option value="Male">Male</option> <option value="Female">Female</option> <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Blood Group<span class="required-ast">*</span></label>
                    <select name="members[${memberIndex}][blood_group]" required>
                        <option value="A+">A+</option> <option value="A-">A-</option> <option value="B+">B+</option> <option value="B-">B-</option> <option value="AB+">AB+</option> <option value="AB-">AB-</option> <option value="O+">O+</option> <option value="O-">O-</option> <option value="Don't Know">Don’t Know</option>
                    </select>
                </div>
                <div class="form-group">
                     <label>Interested in Blood Donation?<span class="required-ast">*</span></label>
                     <select name="members[${memberIndex}][blood_donation]" required>
                         <option value="Yes">Yes</option> <option value="No">No</option> <option value="Maybe">Maybe</option>
                     </select>
                </div>
                <div class="form-group">
                    <label>T-Shirt Size<span class="required-ast">*</span></label>
                    <select name="members[${memberIndex}][tshirt_size]" required>
                        <option value="XS">XS</option> <option value="S">S</option> <option value="M">M</option> <option value="L">L</option> <option value="XL">XL</option> <option value="XXL">XXL</option> <option value="XXXL">XXXL</option>
                    </select>
                    <div class="size-chart-link">
                        <a href="javascript:void(0);" onclick="openSizeChart()">View Size Chart</a>
                    </div>
                </div>
                ${whatsappHTML}
                <div class="form-group">
                    <label for="medical_conditions_${memberIndex}">Any known medical conditions? (Optional)</label>
                    <textarea id="medical_conditions_${memberIndex}" name="members[${memberIndex}][medical_conditions]" rows="3"></textarea>
                </div>
            </div>`;

        return memberHTML;
    }

    function addMember() {
        memberCount++;
        const membersContainer = document.getElementById('members-container');
        const newMemberDiv = document.createElement('div');
        newMemberDiv.innerHTML = createMemberFields(memberCount);
        membersContainer.appendChild(newMemberDiv);
    }

    function removeMember(memberIndex) {
        const memberSection = document.getElementById(`member_section_${memberIndex}`);
        if (memberSection) {
            memberSection.remove();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const addMemberBtn = document.getElementById('addMemberBtn');
        addMember();
        if (addMemberBtn) {
            addMemberBtn.addEventListener('click', addMember);
        }
    });
    </script>
</body>
</html>