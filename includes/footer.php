<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet"> -->
<script src="/HMS/assets/js/script.js"></script>

<script>
    flatpickr(".date-picker", {
        dateFormat: "d/m/Y",
        allowInput: true,
        parseDate: (datestr, format) => {
            // Properly parse d/m/Y format
            if (!datestr) return null;

            // Handle d/m/Y format
            if (datestr.includes('/')) {
                const parts = datestr.split('/');
                if (parts.length === 3) {
                    // parts[0] = day, parts[1] = month, parts[2] = year
                    return new Date(parts[2], parts[1] - 1, parts[0]);
                }
            }

            // Fallback to standard parsing
            return new Date(datestr);
        },
        formatDate: (date, format) => {
            // Format the date as d/m/Y for display
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        },
        onClose: function(selectedDates, dateStr, instance) {
            // When the date picker is closed, update a hidden field with the ISO format
            const input = instance.input;
            if (selectedDates[0]) {
                // Create or update hidden field with ISO format date
                let hiddenField = document.getElementById(input.id + '_iso');
                if (!hiddenField) {
                    hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.id = input.id + '_iso';
                    hiddenField.name = input.name + '_iso';
                    input.parentNode.appendChild(hiddenField);
                }
                const isoDate = selectedDates[0].toISOString().split('T')[0]; // YYYY-MM-DD
                hiddenField.value = isoDate;
            }
        },
        // Initialize hidden ISO field on page load for existing values
        onReady: function(selectedDates, dateStr, instance) {
            if (dateStr) {
                const input = instance.input;
                const date = this.parseDate(dateStr, "d/m/Y");
                if (date) {
                    let hiddenField = document.getElementById(input.id + '_iso');
                    if (!hiddenField) {
                        hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.id = input.id + '_iso';
                        hiddenField.name = input.name + '_iso';
                        input.parentNode.appendChild(hiddenField);
                    }
                    const isoDate = date.toISOString().split('T')[0]; // YYYY-MM-DD
                    hiddenField.value = isoDate;
                }
            }
        }
    });
</script>
<script>
    $(function() {
        <?php
        if (isset($_SESSION['alert'])) {
            showAlert($_SESSION['alert']['title'], $_SESSION['alert']['message'], $_SESSION['alert']['type']);
            unset($_SESSION['alert']);
        }
        ?>
    });
</script>
</body>

</html>