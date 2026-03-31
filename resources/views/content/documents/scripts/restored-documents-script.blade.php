<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {

    // Archive confirmation
    $('.archive-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const rowCode = $(this).closest('tr').find('.badge.bg-label-primary').text().trim();
        const dataCode = $(this).data('tracking');
        const code = rowCode || dataCode || 'Document';

        Swal.fire({
            title: 'Archive Document?',
            text: `Move "${code}" back to archive?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, archive it',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) form.submit();
        });
    });

    // Auto-close alerts after 5s
    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(() => new bootstrap.Alert(alert).close(), 5000);
    });

    // Live search/filter functionality
    const searchInput = document.getElementById('liveSearchInput');
    const tableBody = document.querySelector('.dt-table tbody');
    const tableRows = tableBody ? Array.from(tableBody.querySelectorAll('tr')) : [];
    const dataRows = tableRows.filter(row => row.querySelector('.badge.bg-label-primary'));

    if (searchInput && tableBody && dataRows.length > 0) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCount = 0;
            
            dataRows.forEach(function(row) {
                // Get all text content from the row
                const trackingCode = row.querySelector('.badge.bg-label-primary')?.textContent.toLowerCase() || '';
                const docType = row.cells[1]?.textContent.toLowerCase() || '';
                const purpose = row.cells[2]?.textContent.toLowerCase() || '';
                const fileName = row.cells[3]?.textContent.toLowerCase() || '';
                const restoredAt = row.cells[4]?.textContent.toLowerCase() || '';
                
                // Check if search term matches any column
                const matchFound = trackingCode.includes(searchTerm) ||
                                 docType.includes(searchTerm) ||
                                 purpose.includes(searchTerm) ||
                                 fileName.includes(searchTerm) ||
                                 restoredAt.includes(searchTerm);
                
                // Show or hide row
                if (matchFound) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show "no results" message if no rows visible
            const noResultsRow = document.getElementById('noResultsRow');
            if (visibleCount === 0 && searchTerm !== '') {
                if (!noResultsRow) {
                    const newRow = document.createElement('tr');
                    newRow.id = 'noResultsRow';
                    newRow.innerHTML = `
                        <td colspan="7" class="dt-empty-search">
                            <i class="bx bx-search-alt-2 empty-icon"></i>
                            <p class="empty-title">No matching restored documents</p>
                            <p class="empty-subtitle">Try another keyword like tracking code or file name.</p>
                        </td>
                    `;
                    tableBody.appendChild(newRow);
                }
            } else if (noResultsRow) {
                noResultsRow.remove();
            }
        });
    }

});
</script>
