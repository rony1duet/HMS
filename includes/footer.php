<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

<!-- JQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>

<!-- Sweet Alert 2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Custom JS -->
<script src="/HMS/assets/js/script.js"></script>

<!-- Show Alert -->
<script>
    $(function() {
        <?php
        if (isset($_SESSION['alert'])) {
            showAlert($_SESSION['alert']['title'], $_SESSION['alert']['message'], $_SESSION['alert']['type']);
            unset($_SESSION['alert']);
        }
        ?>
    })
</script>

</body>

</html>