<?php
/**
 * Date Filter Helper Functions
 * Add this to the top of list pages (invoice.php, sell_log.php, etc.)
 */

function getDateRange($filter) {
    $today = date('Y-m-d');
    
    switch($filter) {
        case 'today':
            return [
                'start' => $today,
                'end' => $today
            ];
        case 'yesterday':
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            return [
                'start' => $yesterday,
                'end' => $yesterday
            ];
        case 'tomorrow':
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            return [
                'start' => $tomorrow,
                'end' => $tomorrow
            ];
        case '3_days':
            return [
                'start' => date('Y-m-d', strtotime('-3 days')), 
                'end' => $today
            ];
            
        case '1_week':
            return [
                'start' => date('Y-m-d', strtotime('-1 week')), 
                'end' => $today
            ];
            
        case '1_month':
            return [
                'start' => date('Y-m-d', strtotime('-1 month')), 
                'end' => $today
            ];
            
        case '3_months':
            return [
                'start' => date('Y-m-d', strtotime('-3 months')), 
                'end' => $today
            ];
            
        case '6_months':
            return [
                'start' => date('Y-m-d', strtotime('-6 months')), 
                'end' => $today
            ];
            
        default:
            return null;
    }
}

function applyDateFilter(&$query, $date_column, $date_filter = null, $start_date = null, $end_date = null) {
    if($date_filter) {
        $range = getDateRange($date_filter);
        if($range) {
            $query .= " AND DATE($date_column) BETWEEN '{$range['start']}' AND '{$range['end']}'";
        }
    } elseif($start_date && $end_date) {
        $start_date = mysqli_real_escape_string($GLOBALS['conn'], $start_date);
        $end_date = mysqli_real_escape_string($GLOBALS['conn'], $end_date);
        $query .= " AND DATE($date_column) BETWEEN '$start_date' AND '$end_date'";
    }
}
