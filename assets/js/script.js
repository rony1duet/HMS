function showAlert(title, message, type) {
  Swal.fire({
    title: title,
    text: message,
    icon: type || 'info',
  });
}
