function updateDashboard() {
    $.ajax({
        url: 'process_log.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            // Only update if there are new entries
            if (data && data.length > 0) {
                // Clear existing table rows except the header
                $('#lastheard tr:not(:first)').remove();

                data.forEach(function(entry) {
                    $('#lastheard').append(
                        `<tr>
                            <td>${entry.time}</td>
                            <td>${entry.src}</td>
                            <td>${entry.dst}</td>
                            <td>${entry.type}</td>
                            <td>${entry.can}</td>
                            <td>${entry.mer || 'N/A'}</td>
                            <td>${entry.duration}</td>
                        </tr>`
                    );
                });
            }
        },
        error: function() {
            console.error('Failed to fetch dashboard data');
        }
    });
}

$(document).ready(function() {
    updateDashboard(); // Initial load
    setInterval(updateDashboard, 1000);
});
