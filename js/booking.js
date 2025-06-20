document.addEventListener('DOMContentLoaded', function() {
    const bookingForm = document.getElementById('bookingForm');
    if (!bookingForm) return;

    bookingForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Basic form validation
        const checkInDate = document.getElementById('checkInDate').value;
        const checkOutDate = document.getElementById('checkOutDate').value;
        const totalGuests = document.getElementById('totalGuests').value;
        const homestayId = document.getElementById('homestaySelect').value;
        const paymentReceipt = document.querySelector('input[name="payment_receipt"]').files[0];

        if (!checkInDate || !checkOutDate || !totalGuests || !homestayId || !paymentReceipt) {
            Swal.fire({
                icon: 'error',
                title: 'Missing Information',
                text: 'Please fill in all required fields and upload payment receipt.',
                confirmButtonColor: '#d33'
            });
            return;
        }

        try {
            const formData = new FormData(bookingForm);
            
            const response = await fetch('process/process_booking.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                if (result.redirect) {
                    window.location.href = result.redirect;
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Booking Failed',
                    text: result.message,
                    confirmButtonColor: '#d33'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred. Please try again.',
                confirmButtonColor: '#d33'
            });
        }
    });
});

// Function to validate number of guests
function validateGuests(input) {
    const maxGuests = parseInt(document.querySelector('#homestaySelect option:checked').dataset.maxGuests) || 1;
    const errorDiv = document.getElementById('guestError');
    
    if (input.value > maxGuests) {
        errorDiv.textContent = `Maximum ${maxGuests} guests allowed for this homestay`;
        errorDiv.style.display = 'block';
        input.value = maxGuests;
    } else if (input.value < 1) {
        errorDiv.textContent = 'Minimum 1 guest required';
        errorDiv.style.display = 'block';
        input.value = 1;
    } else {
        errorDiv.style.display = 'none';
    }
}