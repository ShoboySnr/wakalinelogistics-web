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
            var templateParams = {
                sender_name: document.getElementById('senderName').value,
                sender_phone: document.getElementById('senderPhone').value,
                sender_email: document.getElementById('senderEmail').value,
                pickup_address: document.getElementById('pickupAddress').value,
                pickup_area: document.getElementById('pickupArea').value || 'Not provided',
                recipient_name: document.getElementById('recipientName').value,
                recipient_phone: document.getElementById('recipientPhone').value,
                delivery_address: document.getElementById('deliveryAddress').value,
                delivery_area: document.getElementById('deliveryArea').value || 'Not provided',
                delivery_notes: document.getElementById('deliveryNotes').value || 'None',
                package_description: document.getElementById('packageDescription').value,
                package_size: document.getElementById('packageSize').value,
                preferred_time: document.getElementById('preferredTime').value,
                additional_notes: document.getElementById('additionalNotes').value || 'None',
                price: '₦3,500'
            };

            // Send admin notification email
            emailjs.send('service_ycnmtd9', 'template_tsx6teo', templateParams)
            .then(function() {
                // Send customer confirmation email
                return emailjs.send('service_ycnmtd9', 'template_lpynyqn', templateParams);
            })
            .then(function() {
                // Both emails sent successfully
                steps.forEach(function(s) { s.style.display = 'none'; });
                document.querySelector('.form-progress').style.display = 'none';
                formActions.style.display = 'none';
                document.getElementById('formSuccess').style.display = 'block';
                document.getElementById('formError').style.display = 'none';
            })
            .catch(function(error) {
                console.error('EmailJS error:', error);
                document.getElementById('formError').style.display = 'block';
                document.getElementById('formErrorMsg').textContent = 'Failed to send order. Please try again or contact us via WhatsApp.';
            })
            .finally(function() {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Order';
            });
        });
    }
});
