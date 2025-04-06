// Initialize common functionality
$(document).ready(function () {
  // Add any global event handlers or initialization code here
  console.log("Custom JS initialized");
});

// Function to show alerts (used by PHP session alerts)
function showAlert(title, message, type) {
  Swal.fire({
    title: title,
    text: message,
    icon: type,
  });
}
