// Set current year in footer
document.getElementById('year').textContent = new Date().getFullYear();

document.addEventListener('DOMContentLoaded', function() {

    // ===== Modal =====
    const modal = document.getElementById('orderModal');
    const openBtn = document.getElementById('openOrderForm');
    const closeBtn = document.getElementById('closeModal');
    const closeSuccessBtn = document.getElementById('closeSuccess');

    if (openBtn) {
        openBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        });
    }

    // All .open-order-form links also open the modal
    document.querySelectorAll('.open-order-form').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        });
    });

    function closeModal() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (closeSuccessBtn) closeSuccessBtn.addEventListener('click', function() {
        closeModal();
        resetForm();
    });

    // Close on overlay click
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('open')) closeModal();
    });

    // ===== Multi-Step Form =====
    const form = document.getElementById('deliveryOrderForm');
    const steps = document.querySelectorAll('.form-step');
    const progressSteps = document.querySelectorAll('.progress-step');
    const btnBack = document.getElementById('btnBack');
    const btnNext = document.getElementById('btnNext');
    const btnSubmit = document.getElementById('submitOrder');
    const formActions = document.getElementById('formActions');
    let currentStep = 1;
    const totalSteps = 3;

    // Next button
    if (btnNext) {
        btnNext.addEventListener('click', function() {
            if (validateStep(currentStep)) {
                goToStep(currentStep + 1);
            }
        });
    }

    // Back button
    if (btnBack) {
        btnBack.addEventListener('click', function() {
            goToStep(currentStep - 1);
        });
    }

    function goToStep(step) {
        // Hide all steps
        steps.forEach(function(s) { s.classList.remove('active'); });
        // Show target step
        var target = document.querySelector('.form-step[data-step="' + step + '"]');
        if (target) target.classList.add('active');

        // Update progress indicators
        progressSteps.forEach(function(ps) {
            var psStep = parseInt(ps.dataset.step);
            ps.classList.remove('active', 'done');
            if (psStep === step) ps.classList.add('active');
            if (psStep < step) ps.classList.add('done');
        });

        currentStep = step;
        updateButtons();

        // Calculate delivery fee when reaching step 3
        if (step === 3) {
            calculateDeliveryFee();
        }
    }

    // Global variable to store calculated fee
    let calculatedDeliveryFee = 0;
    let deliveryDistanceKm = 0;

    function calculateDeliveryFee() {
        var pickupAddress = document.getElementById('pickupAddress').value;
        var pickupArea = document.getElementById('pickupArea').value;
        var deliveryAddress = document.getElementById('deliveryAddress').value;
        var deliveryArea = document.getElementById('deliveryArea').value;

        // Combine address with area for better accuracy
        var fullPickup = pickupAddress + (pickupArea ? ', ' + pickupArea : '');
        var fullDelivery = deliveryAddress + (deliveryArea ? ', ' + deliveryArea : '');

        // Show loading state
        document.getElementById('feeLoadingSection').style.display = 'block';
        document.getElementById('deliveryFeeSection').style.display = 'none';
        document.getElementById('feeErrorSection').style.display = 'none';

        // Call Metter API
        fetch('/api/wakalinelogistics/v1/metter/quote', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                pickup_address: fullPickup,
                dropoff_address: fullDelivery
            })
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Failed to calculate delivery fee');
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success && data.delivery_fee) {
                calculatedDeliveryFee = data.delivery_fee;
                deliveryDistanceKm = data.distance_km || 0;

                // Format and display the fee
                var formattedFee = '₦' + calculatedDeliveryFee.toLocaleString('en-NG');
                document.getElementById('calculatedFee').textContent = formattedFee;
                
                if (deliveryDistanceKm > 0) {
                    document.getElementById('deliveryDistance').textContent = 'Distance: ' + deliveryDistanceKm.toFixed(1) + ' km';
                } else {
                    document.getElementById('deliveryDistance').textContent = '';
                }

                // Hide loading, show fee
                document.getElementById('feeLoadingSection').style.display = 'none';
                document.getElementById('deliveryFeeSection').style.display = 'block';
            } else {
                throw new Error(data.message || 'Unable to calculate delivery fee');
            }
        })
        .catch(function(error) {
            console.error('Fee calculation error:', error);
            
            // Show error with fallback message
            document.getElementById('feeLoadingSection').style.display = 'none';
            document.getElementById('feeErrorSection').style.display = 'block';
            document.getElementById('feeErrorMessage').textContent = 
                'Unable to calculate exact fee. Our team will confirm the delivery cost when they contact you. Typical range: ₦1,500 - ₦8,000 depending on distance.';
            
            // Set a default fee for email
            calculatedDeliveryFee = 0;
        });
    }

    function updateButtons() {
        // Back button: hide on step 1
        btnBack.style.display = currentStep > 1 ? '' : 'none';
        // Next button: show on steps 1 and 2
        btnNext.style.display = currentStep < totalSteps ? '' : 'none';
        // Submit button: show on step 3 only
        btnSubmit.style.display = currentStep === totalSteps ? '' : 'none';
        // Show actions bar
        formActions.style.display = '';
    }

    function validateStep(step) {
        var stepEl = document.querySelector('.form-step[data-step="' + step + '"]');
        if (!stepEl) return true;

        var inputs = stepEl.querySelectorAll('[required]');
        var valid = true;

        inputs.forEach(function(input) {
            if (!input.value.trim()) {
                input.style.borderColor = '#dc2626';
                valid = false;
            } else {
                input.style.borderColor = '#d1d5db';
            }
        });

        // Email validation on step 1
        if (step === 1) {
            var email = document.getElementById('senderEmail');
            if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                email.style.borderColor = '#dc2626';
                valid = false;
            }
        }

        return valid;
    }

    function resetForm() {
        if (form) form.reset();
        goToStep(1);
        document.getElementById('formSuccess').style.display = 'none';
        document.getElementById('formError').style.display = 'none';
        // Show all form steps container elements
        steps.forEach(function(s) { s.style.display = ''; });
        document.querySelector('.form-progress').style.display = '';
        formActions.style.display = '';
    }

    // ===== EmailJS Init =====
    emailjs.init('FQ1fbEP10MYWg6Lwx');

    // ===== Form Submission =====
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!validateStep(3)) return;

            var submitBtn = document.getElementById('submitOrder');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            // Collect all form values
            var priceDisplay = calculatedDeliveryFee > 0 
                ? '₦' + calculatedDeliveryFee.toLocaleString('en-NG') 
                : 'To be confirmed';
            
            var orderData = {
                sender_name: document.getElementById('senderName').value,
                sender_phone: document.getElementById('senderPhone').value,
                sender_email: document.getElementById('senderEmail').value,
                pickup_address: document.getElementById('pickupAddress').value,
                pickup_area: document.getElementById('pickupArea').value || '',
                recipient_name: document.getElementById('recipientName').value,
                recipient_phone: document.getElementById('recipientPhone').value,
                delivery_address: document.getElementById('deliveryAddress').value,
                delivery_area: document.getElementById('deliveryArea').value || '',
                delivery_notes: document.getElementById('deliveryNotes').value || '',
                package_description: document.getElementById('packageDescription').value,
                package_size: document.getElementById('packageSize').value,
                preferred_time: document.getElementById('preferredTime').value,
                additional_notes: document.getElementById('additionalNotes').value || '',
                price: calculatedDeliveryFee,
                distance: deliveryDistanceKm,
                form_source: 'Landing Page'
            };

            // Submit order to database
            fetch('/api/orders/submit-public', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(orderData)
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    // Order saved successfully, now send emails
                    var templateParams = {
                        sender_name: orderData.sender_name,
                        sender_phone: orderData.sender_phone,
                        sender_email: orderData.sender_email,
                        pickup_address: orderData.pickup_address,
                        pickup_area: orderData.pickup_area || 'Not provided',
                        recipient_name: orderData.recipient_name,
                        recipient_phone: orderData.recipient_phone,
                        delivery_address: orderData.delivery_address,
                        delivery_area: orderData.delivery_area || 'Not provided',
                        delivery_notes: orderData.delivery_notes || 'None',
                        package_description: orderData.package_description,
                        package_size: orderData.package_size,
                        preferred_time: orderData.preferred_time,
                        additional_notes: orderData.additional_notes || 'None',
                        price: priceDisplay,
                        distance: deliveryDistanceKm > 0 ? deliveryDistanceKm.toFixed(1) + ' km' : 'N/A',
                        order_number: data.order_number
                    };

                    // Send emails (optional - order is already saved)
                    return emailjs.send('service_ycnmtd9', 'template_tsx6teo', templateParams)
                        .then(function() {
                            return emailjs.send('service_ycnmtd9', 'template_lpynyqn', templateParams);
                        })
                        .catch(function(emailError) {
                            console.log('Email sending failed, but order was saved:', emailError);
                        });
                } else {
                    throw new Error(data.message || 'Failed to submit order');
                }
            })
            .then(function() {
                // Show success message
                steps.forEach(function(s) { s.style.display = 'none'; });
                document.querySelector('.form-progress').style.display = 'none';
                formActions.style.display = 'none';
                document.getElementById('formSuccess').style.display = 'block';
                document.getElementById('formError').style.display = 'none';
            })
            .catch(function(error) {
                console.error('Order submission error:', error);
                document.getElementById('formError').style.display = 'block';
                document.getElementById('formErrorMsg').textContent = error.message || 'Failed to submit order. Please try again or contact us via WhatsApp.';
            })
            .finally(function() {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Order';
            });
        });
    }
});
