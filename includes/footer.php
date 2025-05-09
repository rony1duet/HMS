<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet"> -->
<script src="/HMS/assets/js/script.js"></script>
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