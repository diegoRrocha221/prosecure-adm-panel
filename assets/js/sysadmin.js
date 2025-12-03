var currentServer = null;
var currentLogPath = null;
var currentConfigPath = null;
var logRefreshInterval = null;
var autoRefreshInterval = null;
var isRefreshing = false;

$(document).ready(function() {
    // Initial load with batching
    refreshAllServersInBatches();
    
    // Auto-refresh every 15 seconds
    autoRefreshInterval = setInterval(function() {
        if (!isRefreshing) {
            refreshAllServersInBatches();
        }
    }, 15000);
});

function refreshAllServers() {
    if (isRefreshing) {
        console.log('Already refreshing, skipping...');
        return;
    }
    refreshAllServersInBatches();
}

function refreshAllServersInBatches() {
    isRefreshing = true;
    $('#refresh-icon').addClass('refresh-indicator');
    
    // Get all server keys
    var serverKeys = [
        'lb-web', 'web1', 'web2', 
        'radius1', 'radius2',
        'lb-db1', 'lb-db2',
        'db1', 'db2', 'db3'
    ];
    
    // Process in batches of 2 servers at a time
    var batchSize = 2;
    var batches = [];
    
    for (var i = 0; i < serverKeys.length; i += batchSize) {
        batches.push(serverKeys.slice(i, i + batchSize));
    }
    
    // Process batches sequentially with delay
    processBatchSequentially(batches, 0);
}

function processBatchSequentially(batches, index) {
    if (index >= batches.length) {
        // All batches processed
        isRefreshing = false;
        $('#refresh-icon').removeClass('refresh-indicator');
        $('#last-update').text('Last update: ' + new Date().toLocaleTimeString());
        return;
    }
    
    var batch = batches[index];
    var promises = [];
    
    // Process current batch
    batch.forEach(function(serverKey) {
        promises.push(loadServerStatus(serverKey));
    });
    
    // Wait for current batch to complete, then move to next
    Promise.all(promises).then(function() {
        // Delay before next batch to avoid overwhelming connections
        setTimeout(function() {
            processBatchSequentially(batches, index + 1);
        }, 500); // 500ms delay between batches
    }).catch(function(error) {
        console.error('Batch processing error:', error);
        // Continue with next batch even if current fails
        setTimeout(function() {
            processBatchSequentially(batches, index + 1);
        }, 500);
    });
}

function loadServerStatus(serverKey) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: 'ajax/sysadmin_status.php',
            type: 'GET',
            data: { server: serverKey },
            dataType: 'json',
            timeout: 10000, // 10 second timeout per request
            success: function(response) {
                if (response.success) {
                    updateServerCard(serverKey, response);
                    loadServerResources(serverKey);
                }
                resolve();
            },
            error: function(xhr, status, error) {
                console.error('Failed to fetch status for ' + serverKey + ':', error);
                // Show error state
                $('#services-' + serverKey).html('<span class="badge bg-danger">Connection Error</span>');
                resolve(); // Resolve anyway to continue with other servers
            }
        });
    });
}

function updateServerCard(serverKey, data) {
    if (!data.success || !data.data) {
        return;
    }
    
    var servicesHtml = '';
    var cardStatus = 'good';
    
    $.each(data.data, function(key, value) {
        if (key === 'selinux') {
            var selinuxClass = value === 'Enforcing' ? 'success' : (value === 'Permissive' ? 'warning' : 'danger');
            servicesHtml += '<span class="badge bg-' + selinuxClass + ' service-badge">SELinux: ' + value + '</span>';
        } else if (key === 'vip_master') {
            var vipClass = value === 'MASTER' ? 'success' : 'secondary';
            servicesHtml += '<span class="badge bg-' + vipClass + ' service-badge">VIP: ' + value + '</span>';
        } else if (key === 'galera') {
            var galeraClass = value.cluster_status === 'Primary' ? 'success' : 'warning';
            servicesHtml += '<span class="badge bg-' + galeraClass + ' service-badge">Galera: ' + value.local_state + '</span>';
        } else if (typeof value === 'object' && value.status) {
            var statusClass = value.status === 'running' ? 'success' : 'danger';
            if (value.status !== 'running') {
                cardStatus = 'danger';
            }
            servicesHtml += '<span class="badge bg-' + statusClass + ' service-badge">' + key + ': ' + value.status + '</span>';
        }
    });
    
    $('#services-' + serverKey).html(servicesHtml);
    $('#server-' + serverKey).removeClass('status-good status-warning status-danger').addClass('status-' + cardStatus);
}

function loadServerResources(serverKey) {
    $.ajax({
        url: 'ajax/sysadmin_resources.php',
        type: 'GET',
        data: { server: serverKey },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if (response.success) {
                var html = '<div class="row">';
                
                // CPU
                var cpuColor = response.cpu < 70 ? 'success' : (response.cpu < 85 ? 'warning' : 'danger');
                html += '<div class="col-12 mb-2">';
                html += '<small class="text-muted">CPU Usage</small>';
                html += '<div class="resource-bar">';
                html += '<div class="resource-fill bg-' + cpuColor + '" style="width: ' + response.cpu + '%">' + response.cpu.toFixed(1) + '%</div>';
                html += '</div>';
                html += '</div>';
                
                // Memory
                var memColor = response.memory < 70 ? 'success' : (response.memory < 85 ? 'warning' : 'danger');
                html += '<div class="col-12 mb-2">';
                html += '<small class="text-muted">Memory Usage</small>';
                html += '<div class="resource-bar">';
                html += '<div class="resource-fill bg-' + memColor + '" style="width: ' + response.memory + '%">' + response.memory.toFixed(1) + '%</div>';
                html += '</div>';
                html += '</div>';
                
                // Disk
                var diskColor = response.disk < 70 ? 'success' : (response.disk < 85 ? 'warning' : 'danger');
                html += '<div class="col-12 mb-2">';
                html += '<small class="text-muted">Disk Usage</small>';
                html += '<div class="resource-bar">';
                html += '<div class="resource-fill bg-' + diskColor + '" style="width: ' + response.disk + '%">' + response.disk + '%</div>';
                html += '</div>';
                html += '</div>';
                
                html += '<div class="col-12"><small class="text-muted">Uptime: ' + response.uptime + '</small></div>';
                html += '</div>';
                
                $('#resources-' + serverKey).html(html);
            }
        },
        error: function() {
            $('#resources-' + serverKey).html('<small class="text-danger">Failed to load resources</small>');
        }
    });
}
function viewServerDetails(serverKey) {
  currentServer = serverKey;
  $('#serverModalTitle').text('Server Details - ' + serverKey);
  $('#serverModal').modal('show');
  
  $.ajax({
      url: 'ajax/sysadmin_details.php',
      type: 'GET',
      data: { server: serverKey },
      dataType: 'json',
      timeout: 15000,
      success: function(response) {
          if (response.success) {
              var html = '<div class="row">';
              
              html += '<div class="col-12 mb-3">';
              html += '<h5>Services</h5>';
              html += '<div class="table-responsive">';
              html += '<table class="table table-sm">';
              html += '<thead><tr><th>Service</th><th>Status</th><th>Actions</th></tr></thead>';
              html += '<tbody>';
              
              $.each(response.data, function(key, value) {
                  if (typeof value === 'object' && value.status) {
                      var statusClass = value.status === 'running' ? 'success' : 'danger';
                      var statusIcon = value.status === 'running' ? 'fa-check-circle' : 'fa-times-circle';
                      html += '<tr>';
                      html += '<td>' + key + '</td>';
                      html += '<td><span class="badge bg-' + statusClass + '"><i class="fas ' + statusIcon + '"></i> ' + value.status + '</span></td>';
                      html += '<td>';
                      html += '<button class="btn btn-sm btn-outline-primary" onclick="restartService(\'' + serverKey + '\', \'' + key + '\')"><i class="fas fa-redo"></i> Restart</button>';
                      html += '</td>';
                      html += '</tr>';
                  }
              });
              
              html += '</tbody></table></div></div>';
              html += '</div>';
              
              $('#serverModalBody').html(html);
          } else {
              $('#serverModalBody').html('<div class="alert alert-danger">' + response.message + '</div>');
          }
      },
      error: function() {
          $('#serverModalBody').html('<div class="alert alert-danger">Failed to load server details</div>');
      }
  });
}

function restartService(serverKey, service) {
  if (!confirm('Are you sure you want to restart ' + service + '?')) {
      return;
  }
  
  $.ajax({
      url: 'ajax/sysadmin_restart.php',
      type: 'POST',
      data: { server: serverKey, service: service },
      dataType: 'json',
      timeout: 30000,
      success: function(response) {
          if (response.success) {
              alert('Service restarted successfully');
              viewServerDetails(serverKey);
              loadServerStatus(serverKey);
          } else {
              alert('Failed to restart service: ' + response.message);
          }
      },
      error: function() {
          alert('Failed to restart service');
      }
  });
}

function viewConfigs(serverKey) {
  currentServer = serverKey;
  
  $.ajax({
      url: 'ajax/sysadmin_list_configs.php',
      type: 'GET',
      data: { server: serverKey },
      dataType: 'json',
      success: function(response) {
          if (response.success) {
              var html = '<div class="list-group">';
              
              $.each(response.configs, function(index, config) {
                  html += '<a href="#" class="list-group-item list-group-item-action" onclick="loadConfig(\'' + serverKey + '\', \'' + config + '\'); return false;">';
                  html += '<i class="fas fa-file-code me-2"></i>' + config;
                  html += '</a>';
              });
              
              html += '</div>';
              
              $('#serverModalTitle').text('Configuration Files - ' + serverKey);
              $('#serverModalBody').html(html);
              $('#serverModal').modal('show');
          }
      }
  });
}

function loadConfig(serverKey, configPath) {
  currentServer = serverKey;
  currentConfigPath = configPath;
  
  $('#configModalTitle').text('Edit Configuration - ' + serverKey);
  $('#configPath').text(configPath);
  $('#configModal').modal('show');
  
  $.ajax({
      url: 'ajax/sysadmin_read_config.php',
      type: 'GET',
      data: { server: serverKey, path: configPath },
      dataType: 'json',
      timeout: 15000,
      success: function(response) {
          if (response.success) {
              $('#configContent').val(response.content);
          } else {
              alert('Failed to load configuration: ' + response.message);
          }
      }
  });
}

function saveConfig() {
  if (!confirm('Are you sure you want to save this configuration?')) {
      return;
  }
  
  var content = $('#configContent').val();
  
  $.ajax({
      url: 'ajax/sysadmin_write_config.php',
      type: 'POST',
      data: {
          server: currentServer,
          path: currentConfigPath,
          content: content
      },
      dataType: 'json',
      timeout: 15000,
      success: function(response) {
          if (response.success) {
              alert('Configuration saved successfully');
              $('#configModal').modal('hide');
          } else {
              alert('Failed to save configuration: ' + response.message);
          }
      }
  });
}

function viewLogs(serverKey) {
  currentServer = serverKey;
  
  $.ajax({
      url: 'ajax/sysadmin_list_logs.php',
      type: 'GET',
      data: { server: serverKey },
      dataType: 'json',
      success: function(response) {
          if (response.success) {
              if (response.logs.length === 1) {
                  loadLog(serverKey, response.logs[0]);
              } else {
                  var html = '<div class="list-group">';
                  
                  $.each(response.logs, function(index, log) {
                      html += '<a href="#" class="list-group-item list-group-item-action" onclick="loadLog(\'' + serverKey + '\', \'' + log + '\'); return false;">';
                      html += '<i class="fas fa-file-alt me-2"></i>' + log;
                      html += '</a>';
                  });
                  
                  html += '</div>';
                  
                  $('#serverModalTitle').text('Log Files - ' + serverKey);
                  $('#serverModalBody').html(html);
                  $('#serverModal').modal('show');
              }
          }
      }
  });
}

function loadLog(serverKey, logPath) {
  currentServer = serverKey;
  currentLogPath = logPath;
  
  $('#logModalTitle').text('Log Viewer - ' + logPath);
  $('#logModal').modal('show');
  
  refreshLog();
  
  // Auto-refresh log every 5 seconds
  if (logRefreshInterval) {
      clearInterval(logRefreshInterval);
  }
  logRefreshInterval = setInterval(refreshLog, 5000);
}

function refreshLog() {
  $.ajax({
      url: 'ajax/sysadmin_read_log.php',
      type: 'GET',
      data: { server: currentServer, path: currentLogPath, lines: 100 },
      dataType: 'json',
      timeout: 15000,
      success: function(response) {
          if (response.success) {
              $('#logContent').text(response.content);
              $('#logContent').scrollTop($('#logContent')[0].scrollHeight);
          }
      }
  });
}

$('#logModal').on('hidden.bs.modal', function() {
  if (logRefreshInterval) {
      clearInterval(logRefreshInterval);
      logRefreshInterval = null;
  }
});

function testConnection(serverKey) {
  $.ajax({
      url: 'ajax/sysadmin_test_connection.php',
      type: 'GET',
      data: { server: serverKey },
      dataType: 'json',
      timeout: 15000,
      success: function(response) {
          if (response.success) {
              var html = '<div class="alert alert-info">';
              html += '<h6>Connection Test Results:</h6>';
              html += '<ul>';
              html += '<li>Ping: ' + (response.can_ping ? '<span class="text-success">✓ Success</span>' : '<span class="text-danger">✗ Failed</span>') + '</li>';
              html += '<li>SSH Port: ' + (response.ssh_port_open ? '<span class="text-success">✓ Open</span>' : '<span class="text-danger">✗ Closed</span>') + '</li>';
              html += '<li>Authentication: ' + (response.can_authenticate ? '<span class="text-success">✓ Success</span>' : '<span class="text-danger">✗ Failed</span>') + '</li>';
              html += '</ul>';
              html += '</div>';
              
              $('#serverModalTitle').text('Connection Test - ' + serverKey);
              $('#serverModalBody').html(html);
              $('#serverModal').modal('show');
          } else {
              alert('Test failed: ' + response.message);
          }
      },
      error: function() {
          alert('Failed to test connection');
      }
  });
}

// Palo Alto Functions
function viewPaloAltoUsers(fwKey) {
  $('#paloAltoModalTitle').text('IP-User Mappings - ' + fwKey.toUpperCase());
  $('#paloAltoModal').modal('show');
  
  $.ajax({
      url: 'ajax/paloalto_users.php',
      type: 'GET',
      data: { fw: fwKey },
      dataType: 'json',
      timeout: 15000,
      success: function(response) {
          if (response.success && response.users) {
              var html = '<div class="table-responsive">';
              html += '<table class="table table-sm table-striped">';
              html += '<thead><tr><th>IP Address</th><th>Username</th><th>Timeout</th></tr></thead>';
              html += '<tbody>';
              
              $.each(response.users, function(index, user) {
                  html += '<tr>';
                  html += '<td>' + user.ip + '</td>';
                  html += '<td>' + user.user + '</td>';
                  html += '<td>' + user.timeout + '</td>';
                  html += '</tr>';
              });
              
              html += '</tbody></table></div>';
              $('#paloAltoModalBody').html(html);
          } else {
              $('#paloAltoModalBody').html('<div class="alert alert-warning">No user mappings found</div>');
          }
      },
      error: function() {
          $('#paloAltoModalBody').html('<div class="alert alert-danger">Failed to fetch user mappings</div>');
      }
  });
}

function viewPaloAltoSessions(fwKey) {
  $('#paloAltoModalTitle').text('Active Sessions - ' + fwKey.toUpperCase());
  $('#paloAltoModal').modal('show');
  
  $.ajax({
      url: 'ajax/paloalto_sessions.php',
      type: 'GET',
      data: { fw: fwKey },
      dataType: 'json',
      timeout: 15000,
      success: function(response) {
          if (response.success) {
              var html = '<pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
              $('#paloAltoModalBody').html(html);
          } else {
              $('#paloAltoModalBody').html('<div class="alert alert-danger">' + response.message + '</div>');
          }
      }
  });
}

function viewPaloAltoHA(fwKey) {
  $('#paloAltoModalTitle').text('High Availability Status - ' + fwKey.toUpperCase());
  $('#paloAltoModal').modal('show');
  
  $.ajax({
      url: 'ajax/paloalto_ha.php',
      type: 'GET',
      data: { fw: fwKey },
      dataType: 'json',
      timeout: 15000,
      success: function(response) {
          if (response.success) {
              var html = '<pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
              $('#paloAltoModalBody').html(html);
          } else {
              $('#paloAltoModalBody').html('<div class="alert alert-danger">' + response.message + '</div>');
          }
      }
  });
}

// Clean up on page unload
$(window).on('beforeunload', function() {
  if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval);
  }
  if (logRefreshInterval) {
      clearInterval(logRefreshInterval);
  }
});