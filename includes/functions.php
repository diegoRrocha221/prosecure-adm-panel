<?php
/**
 * Helper Functions
 */

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatDate($date) {
    if (empty($date)) return 'N/A';
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (empty($datetime)) return 'N/A';
    return date('M d, Y H:i', strtotime($datetime));
}

function formatMoney($amount) {
    return '$' . number_format($amount, 2);
}

function formatStatus($status) {
    $statusMap = [
        0 => '<span class="badge bg-secondary">Inactive</span>',
        1 => '<span class="badge bg-success">Active</span>',
    ];
    
    return $statusMap[$status] ?? '<span class="badge bg-warning">Unknown</span>';
}

function formatTrialStatus($isTrial) {
    return $isTrial == 1 
        ? '<span class="badge bg-warning">Trial</span>' 
        : '<span class="badge bg-success">Paid</span>';
}

function formatPaymentStatus($isPaid) {
    return $isPaid == 1 
        ? '<span class="badge bg-success">Paid</span>' 
        : '<span class="badge bg-danger">Unpaid</span>';
}

function getPaginationInfo($currentPage, $totalPages, $perPage, $totalRecords) {
    $currentPage = (int)$currentPage;
    $perPage = (int)$perPage;
    $totalRecords = (int)$totalRecords;
    
    $start = ($currentPage - 1) * $perPage + 1;
    $end = min($currentPage * $perPage, $totalRecords);
    
    return [
        'start' => $start,
        'end' => $end,
        'total' => $totalRecords
    ];
}

function renderPagination($currentPage, $totalPages, $baseUrl) {
    $currentPage = (int)$currentPage;
    $totalPages = (int)$totalPages;
    
    if ($totalPages <= 1) return '';
    
    $html = '<nav><ul class="pagination justify-content-center">';
    
    // Previous button
    $prevDisabled = $currentPage <= 1 ? 'disabled' : '';
    $prevPage = max(1, $currentPage - 1);
    $html .= '<li class="page-item ' . $prevDisabled . '">
                <a class="page-link" href="' . $baseUrl . '&page=' . $prevPage . '">Previous</a>
              </li>';
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">
                    <a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a>
                  </li>';
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    $nextDisabled = $currentPage >= $totalPages ? 'disabled' : '';
    $nextPage = min($totalPages, $currentPage + 1);
    $html .= '<li class="page-item ' . $nextDisabled . '">
                <a class="page-link" href="' . $baseUrl . '&page=' . $nextPage . '">Next</a>
              </li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}

function alert($message, $type = 'info') {
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}
