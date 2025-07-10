<?php
// Must start session to access the session variables
session_start();

// MODIFIED: Check for the new session variable
if (isset($_SESSION['registered_members']) && !empty($_SESSION['registered_members'])) {
    $registered_members = $_SESSION['registered_members'];
    // Clear the session variable so it doesn't show again on refresh
    unset($_SESSION['registered_members']);
} else {
    // If no data is found, redirect back to the form
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful!</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Teachers&display=swap" rel="stylesheet">
    <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-TSQXGJG6');</script>
<!-- End Google Tag Manager -->

    <style>
        body {
            font-family: 'Teachers', sans-serif;
            background-color: #f4f4f4;
            color: #333;
            text-align: center;
            padding-top: 50px;
        }
        .container {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: inline-block;
            text-align: left; /* Align text to the left inside the container */
        }
        h1 {
            color: #1a237e;
            font-size: 2.5em;
            text-align: center; /* Center the heading */
        }
        p {
            font-size: 1.2em;
            text-align: center; /* Center the paragraphs */
        }
        .id-list {
            list-style-type: none;
            padding: 0;
            font-size: 1.3em; /* Slightly adjusted font size */
            margin-top: 20px;
            text-align: center;
        }
        .id-list li {
            margin-bottom: 10px; /* Add space between list items */
        }
        .id-list-name {
            font-weight: bold;
            color: #333;
        }
        .id-list-id {
            font-weight: bold;
            color: #ff6f00;
        }
        .center-link {
            text-align: center; /* Center the link */
            margin-top: 30px;
        }
        a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #1a237e;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TSQXGJG6"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

    <div class="container">
        <h1>Thank You!</h1>
        <p>Your registration for Wheels of Freedom is complete.</p>

        <?php if (!empty($registered_members)): ?>
            <p>Your unique registration number(s) are:</p>
            
            <ul class="id-list">
                <?php foreach ($registered_members as $member): ?>
                    <li>
                        <span class="id-list-name"><?php echo htmlspecialchars($member['name']); ?></span> = 
                        <span class="id-list-id"><?php echo htmlspecialchars($member['id']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="center-link">
            <a href="index.php">Register Another Group</a>
        </div>
    </div>

</body>
</html>