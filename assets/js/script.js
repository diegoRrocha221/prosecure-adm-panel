// Admin Panel JavaScript

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
    
    // JSON formatter
    $('.format-json').each(function() {
        try {
            var json = $(this).text();
            var obj = JSON.parse(json);
            $(this).html(syntaxHighlight(JSON.stringify(obj, null, 2)));
        } catch (e) {
            // Invalid JSON, leave as is
        }
    });
    
    // Search form - prevent empty submissions
    $('#searchForm').on('submit', function(e) {
        var searchInput = $(this).find('input[name="search"]').val().trim();
        if (searchInput === '' && !$(this).find('input[type="date"]').val()) {
            e.preventDefault();
            alert('Please enter a search term or select a date range.');
        }
    });
    
    // Clear filters button
    $('#clearFilters').on('click', function() {
        window.location.href = window.location.pathname;
    });
    
    // Table row click (if data-href attribute exists)
    $('tr[data-href]').on('click', function(e) {
        if (!$(e.target).is('a, button, input, select')) {
            window.location.href = $(this).data('href');
        }
    });
    
    // Loading indicator for forms
    $('form').on('submit', function() {
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
    });
});

// Syntax highlighting for JSON
function syntaxHighlight(json) {
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        var cls = 'json-value';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'json-key';
            }
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}

// Export table to CSV
function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll("table tr");
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (var j = 0; j < cols.length; j++) {
            var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        
        csv.push(row.join(","));
    }
    
    downloadCSV(csv.join("\n"), filename);
}

function downloadCSV(csv, filename) {
    var csvFile;
    var downloadLink;
    
    csvFile = new Blob([csv], {type: "text/csv"});
    downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}

// Format plan JSON data
function formatPlanData(planData) {
    try {
        var plans = JSON.parse(planData);
        var html = '<div class="list-group">';
        
        plans.forEach(function(plan, index) {
            var badge = plan.is_master == 1 ? 
                '<span class="badge bg-primary ms-2">Master</span>' : 
                '<span class="badge bg-secondary ms-2">Additional</span>';
            
            var username = plan.username && plan.username !== 'none' ? 
                '<small class="text-muted">Assigned to: ' + plan.username + '</small>' : 
                '<small class="text-muted">Not assigned</small>';
            
            html += '<div class="list-group-item">' +
                    '<div class="d-flex w-100 justify-content-between">' +
                    '<h6 class="mb-1">' + plan.plan_name + badge + '</h6>' +
                    '<small>Plan ID: ' + plan.plan_id + '</small>' +
                    '</div>' +
                    username +
                    '</div>';
        });
        
        html += '</div>';
        return html;
    } catch (e) {
        return '<div class="alert alert-warning">Invalid plan data</div>';
    }
}
