<?php
// Database connection
$db = new mysqli("localhost", "root", "", "chores_app");
if ($db->connect_error) { 
    die("Connection failed: " . $db->connect_error); 
}

// Get service pricing from database
function getServicePrice($service, $is_urgent = false) {
    global $db;
    
    $query = $db->prepare("SELECT base_price, urgent_surcharge, currency FROM service_pricing WHERE service = ?");
    $query->bind_param("s", $service);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows > 0) {
        $pricing = $result->fetch_assoc();
        $base_price = $pricing['base_price'];
        $urgent_surcharge = $is_urgent ? $pricing['urgent_surcharge'] : 0;
        $total_price = $base_price + $urgent_surcharge;
        
        return [
            'base_price' => $base_price,
            'urgent_surcharge' => $urgent_surcharge,
            'total_price' => $total_price,
            'currency' => $pricing['currency'],
            'formatted_price' => number_format($total_price, 2, '.', ','),
            'formatted_currency' => $pricing['currency'] . ' ' . number_format($total_price, 2, '.', ',')
        ];
    }
    
    // Default pricing if not found in database
    return [
        'base_price' => 15000.00, // Default to cleaning price
        'urgent_surcharge' => $is_urgent ? 5000.00 : 0,
        'total_price' => 15000.00 + ($is_urgent ? 5000.00 : 0),
        'currency' => 'XAF',
        'formatted_price' => number_format(15000.00 + ($is_urgent ? 5000.00 : 0), 2, '.', ','),
        'formatted_currency' => 'XAF ' . number_format(15000.00 + ($is_urgent ? 5000.00 : 0), 2, '.', ',')
    ];
}

// Check if a job is urgent (within 24 hours)
function isUrgentJob($job_date) {
    $job_timestamp = strtotime($job_date);
    $urgent_threshold = strtotime('+24 hours');
    return $job_timestamp <= $urgent_threshold;
}

// Get all service pricing for display
function getAllServicePricing() {
    global $db;
    
    $query = $db->prepare("SELECT service, base_price, urgent_surcharge, currency FROM service_pricing ORDER BY base_price");
    $query->execute();
    $result = $query->get_result();
    
    $pricing = [];
    while ($row = $result->fetch_assoc()) {
        $pricing[$row['service']] = $row;
    }
    
    return $pricing;
}

// Format price display with currency symbol
function formatPrice($price, $currency = 'XAF') {
    return $currency . ' ' . number_format($price, 2, '.', ',');
}
?>
