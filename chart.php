<?php
// Create a new file called generate_chart.php
// This file will handle the chart generation and download

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: validation.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "expense_tracker";
$connection = new mysqli($servername, $username, $password, $dbname);

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$user_id = $_SESSION['user_id'];

// Get expenses by category
$query = "SELECT category, SUM(amount) as total_amount 
          FROM expenses 
          WHERE user_id = ? 
          GROUP BY category 
          ORDER BY total_amount DESC";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Process the data
$categories = [];
$amounts = [];
$max_amount = 0;

while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category'];
    $amounts[] = $row['total_amount'];
    if ($row['total_amount'] > $max_amount) {
        $max_amount = $row['total_amount'];
    }
}

// Chart dimensions
$width = 800;
$height = 500;
$padding = 60;
$bar_padding = 40;

// Calculate bar width based on number of categories
$bar_width = ($width - 2 * $padding) / count($categories);
$bar_width = min($bar_width, 80); // Maximum bar width

// Generate SVG
$svg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">
    <style>
        .chart-text { font-family: Arial; font-size: 12px; }
        .axis-label { font-family: Arial; font-size: 14px; font-weight: bold; }
    </style>
    <rect width="100%" height="100%" fill="#ffffff"/>';

// Draw Y-axis
$svg .= '<line x1="' . $padding . '" y1="' . ($height - $padding) . '" x2="' . $padding . '" y2="' . $padding . '" stroke="black" stroke-width="2"/>';

// Draw X-axis
$svg .= '<line x1="' . $padding . '" y1="' . ($height - $padding) . '" x2="' . ($width - $padding) . '" y2="' . ($height - $padding) . '" stroke="black" stroke-width="2"/>';

// Y-axis label
$svg .= '<text x="25" y="' . ($height / 2) . '" class="axis-label" transform="rotate(270, 25, ' . ($height / 2) . ')">Amount (RM)</text>';

// X-axis label
$svg .= '<text x="' . ($width / 2) . '" y="' . ($height - 10) . '" class="axis-label" text-anchor="middle">Categories</text>';

// Draw bars and labels
for ($i = 0; $i < count($categories); $i++) {
    $x = $padding + ($i * ($bar_width + $bar_padding));
    $bar_height = ($amounts[$i] / $max_amount) * ($height - 2 * $padding);
    $y = $height - $padding - $bar_height;

    // Generate a color based on index
    $hue = ($i * 137.5) % 360;
    $color = 'hsl(' . $hue . ', 70%, 60%)';

    // Draw bar
    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $bar_width . '" height="' . $bar_height . '" fill="' . $color . '"/>';

    // Category label
    $svg .= '<text x="' . ($x + $bar_width / 2) . '" y="' . ($height - $padding + 20) . '" 
             class="chart-text" text-anchor="middle" transform="rotate(0, ' . ($x + $bar_width / 2) . ', ' . ($height - $padding + 20) . ')">' .
        htmlspecialchars($categories[$i]) . '</text>';

    // Amount label
    $svg .= '<text x="' . ($x + $bar_width / 2) . '" y="' . ($y - 5) . '" 
             class="chart-text" text-anchor="middle">RM' . number_format($amounts[$i], 2) . '</text>';
}

// Close SVG tag
$svg .= '</svg>';

// Generate the file
$filename = 'expense_chart_' . date('Y-m-d_His') . '.svg';
$filepath = 'charts/' . $filename;

// Create charts directory if it doesn't exist
if (!file_exists('charts')) {
    mkdir('charts', 0777, true);
}

// Write SVG to file
file_put_contents($filepath, $svg);

// Force download
header('Content-Type: image/svg+xml');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);

// Clean up
unlink($filepath);
rmdir('charts');
exit();
?>