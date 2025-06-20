document.addEventListener('DOMContentLoaded', function() {
    const homestaySelect = document.getElementById('homestaySelect');
   const bookingFormHomestay = document.getElementById('bookingFormHomestay');
    const calendarContainer = document.querySelector('.calendar-container');
    if (!homestaySelect || !bookingFormHomestay) return;

    loadHomestays();

    async function loadHomestays() {
        try {
            const response = await fetch('process/get_homestays.php');
            const homestays = await response.json();

            // Function to create homestay option
            const createHomestayOption = (homestay, isBookingForm = false) => {
                const option = document.createElement('option');
                option.value = homestay.homestay_id;
                option.dataset.price = homestay.price_per_night;
                option.dataset.maxGuests = homestay.max_guests;
                option.textContent = isBookingForm
                    ? `${homestay.name} - RM${parseFloat(homestay.price_per_night).toFixed(2)}/night`
                    : `${homestay.name} - RM${parseFloat(homestay.price_per_night).toFixed(2)}/night (Max ${homestay.max_guests} guests)`;
                return option;
            };

            // Clear and populate main homestay select
            homestaySelect.innerHTML = '<option value="">Choose a homestay...</option>';
            homestays.forEach(homestay => {
                homestaySelect.appendChild(createHomestayOption(homestay));
            });

            // Clear and populate booking form homestay select
            bookingFormHomestay.innerHTML = '<option value="" disabled selected>Select Homestay</option>';
            homestays.forEach(homestay => {
                bookingFormHomestay.appendChild(createHomestayOption(homestay, true));
            });

            // Function to handle homestay selection
            const handleHomestaySelection = (selectedValue, source) => {
                if (selectedValue) {
                    updateCalendar(selectedValue);
                    calculateTotal();
                    
                    // Sync the other dropdown
                    if (source === 'main') {
                        bookingFormHomestay.value = selectedValue;
                    } else {
                        homestaySelect.value = selectedValue;
                    }
                } else {
                    // Show message when no homestay is selected
                    calendarContainer.querySelector('#calendar')?.remove();
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-info mt-3';
                    alertDiv.innerHTML = '<i class="fas fa-info-circle"></i> Please select a homestay to view availability.';
                    calendarContainer.appendChild(alertDiv);
                }
            };

            // Add event listeners to both selects
            homestaySelect.addEventListener('change', function() {
                handleHomestaySelection(this.value, 'main');
            });

            bookingFormHomestay.addEventListener('change', function() {
                handleHomestaySelection(this.value, 'booking');
            });

        } catch (error) {
            console.error('Error loading homestays:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load homestays. Please refresh the page.',
                confirmButtonColor: '#d33'
            });
        }
    }

    async function updateCalendar(homestayId) {
        try {
            const response = await fetch(`process/get_homestay_availability.php?homestay_id=${homestayId}`);
            const data = await response.json();
            
            // Get current date information
            const today = new Date();
            const month = today.toLocaleString('default', { month: 'long' }) + ' ' + today.getFullYear();
            const daysInMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).getDay();

            // Create calendar HTML
            let calendarHTML = `
                <div id="calendar" class="mt-4">
                    <div class="calendar-grid">
                        <h4 class="text-center">${month}</h4>
                        <div class="calendar-header">
                            <div>Sun</div>
                            <div>Mon</div>
                            <div>Tue</div>
                            <div>Wed</div>
                            <div>Thu</div>
                            <div>Fri</div>
                            <div>Sat</div>
                        </div>
                        <div class="calendar-days">`;

            // Add empty cells for days before the first day of month
            for (let i = 0; i < firstDay; i++) {
                calendarHTML += `<div class="calendar-day empty"></div>`;
            }

            // Add calendar days
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(today.getFullYear(), today.getMonth(), day);
                const dateStr = date.toISOString().split('T')[0];
                const isPast = date < new Date(new Date().setHours(0,0,0,0));
                const isBooked = data.booked_dates.includes(dateStr);

                let classes = ['calendar-day'];
                let status = '';

                if (isPast) {
                    classes.push('past');
                    status = 'Past';
                } else if (isBooked) {
                    classes.push('booked');
                    status = 'Booked';
                } else {
                    classes.push('available');
                    status = 'Available';
                }

                calendarHTML += `
                    <div class="${classes.join(' ')}" 
                         data-date="${dateStr}" 
                         title="${status}">
                        ${day}
                        ${isBooked && !isPast ? '<span class="booked-label">Booked</span>' : ''}
                    </div>`;
            }

            calendarHTML += `
                        </div>
                    </div>
                    <div class="calendar-legend mt-3">
                        <div class="legend-item"><span class="legend-color available"></span> Available</div>
                        <div class="legend-item"><span class="legend-color booked"></span> Booked</div>
                        <div class="legend-item"><span class="legend-color past"></span> Past</div>
                    </div>
                </div>`;

            // Update calendar container
            calendarContainer.querySelector('#calendar')?.remove();
            calendarContainer.querySelector('.alert')?.remove();
            calendarContainer.insertAdjacentHTML('beforeend', calendarHTML);

        } catch (error) {
            console.error('Error updating calendar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load availability calendar. Please try again.',
                confirmButtonColor: '#d33'
            });
        }
    }
});