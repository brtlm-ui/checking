// Employee Login Form
document.getElementById('employeeLoginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const errorDiv = document.getElementById('employee-error');
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    // Get form data
    const formData = {
        employee_id: document.getElementById('employee_id').value,
        pin: document.getElementById('pin').value
    };
    
    // Disable button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Logging in...';
    
    try {
        const response = await fetch('../api/employee_login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            errorDiv.classList.add('d-none');
            window.location.href = data.redirect;
        } else {
            errorDiv.textContent = data.message;
            errorDiv.classList.remove('d-none');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Login';
        }
    } catch (error) {
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.classList.remove('d-none');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Login';
    }
});

// Admin Login Form
document.getElementById('adminLoginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const errorDiv = document.getElementById('admin-error');
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    // Get form data
    const formData = {
        username: document.getElementById('username').value,
        password: document.getElementById('password').value
    };
    
    // Disable button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Logging in...';
    
    try {
        const response = await fetch('../api/admin_login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            errorDiv.classList.add('d-none');
            window.location.href = data.redirect;
        } else {
            errorDiv.textContent = data.message;
            errorDiv.classList.remove('d-none');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Login';
        }
    } catch (error) {
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.classList.remove('d-none');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Login';
    }
});

// Clear error messages when switching tabs
document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', function() {
        document.getElementById('employee-error')?.classList.add('d-none');
        document.getElementById('admin-error')?.classList.add('d-none');
    });
});