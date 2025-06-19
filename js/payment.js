document.addEventListener('DOMContentLoaded', function() {
    // Payment method selection handling
    const paymentMethodInputs = document.querySelectorAll('input[name="payment_method"]');
    const qrCodeSection = document.getElementById('qrCodePayment');

    paymentMethodInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value === 'qr_code') {
                qrCodeSection.style.display = 'block';
                qrCodeSection.scrollIntoView({ behavior: 'smooth' });
            } else {
                qrCodeSection.style.display = 'none';
            }
        });
    });

    // Receipt file upload validation
    const receiptInput = document.getElementById('paymentReceipt');
    if (receiptInput) {
        receiptInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload a valid file (JPG, PNG, or PDF)');
                    this.value = '';
                    return;
                }

                // Check file size (max 5MB)
                const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                if (file.size > maxSize) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }

                // Preview image if it's an image file
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewContainer = document.getElementById('receiptPreview');
                        if (previewContainer) {
                            previewContainer.innerHTML = `
                                <img src="${e.target.result}" alt="Receipt Preview" 
                                     style="max-width: 300px; max-height: 300px; object-fit: contain;">
                            `;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            }
        });
    }

    // Form submission handling
    const paymentForm = document.querySelector('form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
            const receiptFile = document.getElementById('paymentReceipt');

            if (!selectedPaymentMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return;
            }

            if (selectedPaymentMethod.value === 'qr_code' && (!receiptFile || !receiptFile.files[0])) {
                e.preventDefault();
                alert('Please upload your payment receipt');
                return;
            }
        });
    }

    // Payment status update handling (for admin)
    const approvalButtons = document.querySelectorAll('.approval-btn');
    approvalButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.action;
            const paymentId = this.dataset.paymentId;
            const bookingId = this.dataset.bookingId;

            if (confirm(`Are you sure you want to ${action} this payment?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin_checkpayment.php';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = `${action}_payment`;

                const paymentInput = document.createElement('input');
                paymentInput.type = 'hidden';
                paymentInput.name = 'payment_id';
                paymentInput.value = paymentId;

                const bookingInput = document.createElement('input');
                bookingInput.type = 'hidden';
                bookingInput.name = 'booking_id';
                bookingInput.value = bookingId;

                form.appendChild(actionInput);
                form.appendChild(paymentInput);
                form.appendChild(bookingInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});