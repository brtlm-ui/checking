document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addEmployeeForm');
    
    // Real-time validation
    form.querySelectorAll('input, select').forEach(input => {
        input.addEventListener('input', function() {
            validateField(this);
        });
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        let hasErrors = false;
        
        // Validate all fields
        form.querySelectorAll('input, select').forEach(input => {
            if (!validateField(input)) {
                hasErrors = true;
            }
        });

        if (hasErrors) {
            e.preventDefault();
        }
    });

    function validateField(input) {
        const errorDiv = input.nextElementSibling;
        let isValid = true;
        let errorMessage = '';

        // Clear previous errors
        input.classList.remove('is-invalid');
        if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
            errorDiv.textContent = '';
        }

        // Required field validation
        if (input.hasAttribute('required') && !input.value.trim()) {
            errorMessage = `${input.previousElementSibling.textContent.replace(' *', '')} is required`;
            isValid = false;
        }

        // Specific field validations
        switch(input.name) {
            case 'employee_id':
                if (!/^\d{4}$/.test(input.value) && input.value.trim()) {
                    errorMessage = 'Employee ID must be exactly 4 digits';
                    isValid = false;
                }
                break;

            case 'pin':
                if (!/^\d{6}$/.test(input.value) && input.value.trim()) {
                    errorMessage = 'PIN must be exactly 6 digits';
                    isValid = false;
                }
                break;

            case 'first_name':
            case 'last_name':
                if (!/^[a-zA-Z\s]{2,50}$/.test(input.value) && input.value.trim()) {
                    errorMessage = 'Must contain only letters (2-50 characters)';
                    isValid = false;
                }
                break;

            case 'email':
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value) && input.value.trim()) {
                    errorMessage = 'Invalid email format';
                    isValid = false;
                }
                break;
        }

        // Display error if validation fails
        if (!isValid) {
            input.classList.add('is-invalid');
            if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                errorDiv.textContent = errorMessage;
            }
        }

        return isValid;
    }
});