<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    });

    // Live search filter
    $('#liveSearchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        const tableBody = $('.dt-table tbody');
        let visibleCount = 0;

        tableBody.find('tr').each(function() {
            // Skip empty state row
            if ($(this).find('td').length === 1 && $(this).find('.dt-empty').length) {
                return;
            }

            // Get value from tracking code column only
            const trackingCode = $(this).find('td:eq(0)').text().toLowerCase();

            // Check if search term matches tracking code
            const matches = trackingCode.includes(searchTerm);

            if (matches) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        // Show/hide empty state
        if (visibleCount === 0 && searchTerm.length > 0) {
            tableBody.find('tr:has(.dt-empty)').show();
        } else if (searchTerm.length === 0) {
            tableBody.find('tr').show();
        } else {
            tableBody.find('tr:has(.dt-empty)').hide();
        }
    });

    // Archive confirmation
    $('.archive-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const code = $(this).closest('tr').find('.badge.bg-label-primary').text().trim();

        Swal.fire({
            title: 'Archive Document?',
            text: `Move "${code}" to archive?`,
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
});
</script>
