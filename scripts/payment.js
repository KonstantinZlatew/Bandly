document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.plan-form');
    const popup = document.getElementById('subscriptionPopup');
    const popupMessage = document.getElementById('popupMessage');
    const popupCancel = document.getElementById('popupCancel');
    const popupIgnore = document.getElementById('popupIgnore');
    
    let pendingForm = null;
    let pendingPlanCode = null;

    // Function to submit form properly with plan code
    function submitForm(form, planCode) {
        // Create a hidden input to ensure the plan value is sent
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'plan';
        hiddenInput.value = planCode;
        form.appendChild(hiddenInput);
        
        // Submit the form
        form.submit();
    }

    // Intercept form submissions
    forms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Get the plan code from the form's data attribute or button value
            const planCode = form.dataset.planCode || form.querySelector('button[type="submit"]').value;
            pendingPlanCode = planCode;
            
            // Check subscription status
            try {
                const response = await fetch('api/subscription-check.php');
                const data = await response.json();
                
                if (data.ok && data.has_subscription) {
                    // User has active subscription - show popup
                    pendingForm = form;
                    popupMessage.textContent = `You already have an active subscription (${data.plan_name}). Are you sure you want to purchase another plan?`;
                    popup.style.display = 'flex';
                } else {
                    // No subscription - proceed normally
                    submitForm(form, planCode);
                }
            } catch (error) {
                console.error('Error checking subscription:', error);
                // On error, proceed with submission
                submitForm(form, planCode);
            }
        });
    });

    // Cancel button - close popup
    popupCancel.addEventListener('click', function() {
        popup.style.display = 'none';
        pendingForm = null;
        pendingPlanCode = null;
    });

    // Ignore button - proceed with purchase
    popupIgnore.addEventListener('click', function() {
        popup.style.display = 'none';
        if (pendingForm && pendingPlanCode) {
            submitForm(pendingForm, pendingPlanCode);
        }
        pendingForm = null;
        pendingPlanCode = null;
    });

    // Close popup when clicking outside
    popup.addEventListener('click', function(e) {
        if (e.target === popup) {
            popup.style.display = 'none';
            pendingForm = null;
            pendingPlanCode = null;
        }
    });
});