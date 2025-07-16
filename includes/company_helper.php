<?php
function getCompanySettings($pdo) {
    $stmt = $pdo->query("SELECT * FROM company_settings LIMIT 1");
    $company = $stmt->fetch();
    
    // Return default values if no company settings exist
    if (!$company) {
        return [
            'company_name' => 'Your Company Name',
            'address' => '123 Business Street<br>City, State 12345',
            'phone' => '(555) 123-4567',
            'email' => 'info@yourcompany.com',
            'website' => 'www.yourcompany.com',
            'tax_number' => '',
            'bank_account' => ''
        ];
    }
    
    return $company;
}
?> 