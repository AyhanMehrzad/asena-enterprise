<?php
/**
 * Test Suite: Currency Formatting & 3-by-3 Number Parsing
 */

require_once __DIR__ . '/../includes/functions.php';

$testsPassed = 0;
$totalTests = 0;

function assertTest(bool $condition, string $testName) {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        $testsPassed++;
        echo " [PASS] Test {$totalTests}: {$testName}\n";
    } else {
        echo " [FAIL] Test {$totalTests}: {$testName}\n";
    }
}

echo "\n=========================================================\n";
echo "   ASENA ENTERPRISE CURRENCY FORMATTING TEST SUITE       \n";
echo "=========================================================\n\n";

// Test 1: Clean standard English formatted numbers with commas
$val1 = clean_toman_amount("3,455,000");
assertTest($val1 === 3455000, "Clean standard formatted string '3,455,000' -> 3455000 (actual: {$val1})");

// Test 2: Clean Persian digits with commas
$val2 = clean_toman_amount("۳,۴۵۵,۰۰۰");
assertTest($val2 === 3455000, "Clean Persian formatted string '۳,۴۵۵,۰۰۰' -> 3455000 (actual: {$val2})");

// Test 3: Clean Arabic digits with commas
$val3 = clean_toman_amount("١٠,٥٠٠,٠٠٠");
assertTest($val3 === 10500000, "Clean Arabic formatted string '١٠,٥٠٠,٠٠٠' -> 10500000 (actual: {$val3})");

// Test 4: Clean mixed string with currency label
$val4 = clean_toman_amount("  600,000 تومان ");
assertTest($val4 === 600000, "Clean mixed string '  600,000 تومان ' -> 600000 (actual: {$val4})");

// Test 5: Number format output check
$val5 = number_format(3455000);
assertTest($val5 === "3,455,000", "number_format(3455000) produces '3,455,000' (actual: '{$val5}')");

// Test 6: format_price output check
$val6 = format_price(3455000, true, false);
assertTest($val6 === "3,455,000 تومان", "format_price with Latin digits produces '3,455,000 تومان' (actual: '{$val6}')");

echo "\n---------------------------------------------------------\n";
echo " RESULTS: {$testsPassed} / {$totalTests} Tests Passed.\n";
echo "---------------------------------------------------------\n";

if ($testsPassed === $totalTests) {
    echo " ALL CURRENCY FORMATTING TESTS PASSED SUCCESSFULLY!\n\n";
    exit(0);
} else {
    echo " SOME TESTS FAILED.\n\n";
    exit(1);
}
