document.addEventListener('DOMContentLoaded', function() {
    const signUpForm = document.querySelector('.sign-up-form');
    const passwordToggles = document.querySelectorAll('.password-toggle');

    // Handle password visibility toggle
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const passwordInput = this.parentElement.querySelector('input');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Update icon based on password visibility
            const svg = this.querySelector('svg');
            if (type === 'text') {
                svg.innerHTML = `
                    <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z" fill="currentColor"/>
                `;
            } else {
                svg.innerHTML = `
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" fill="currentColor"/>
                `;
            }
        });
    });

    // Handle form submission
    signUpForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Form submission started');

        // Get all form data
        const formData = {
            firstName: document.getElementById('first-name').value,
            lastName: document.getElementById('last-name').value,
            studentNumber: document.getElementById('student-number').value,
            email: document.getElementById('email').value,
            password: document.getElementById('new-password').value,
            confirmPassword: document.getElementById('confirm-password').value
        };

        console.log('Form data collected:', {
            ...formData,
            password: '********', // Hide password in logs
            confirmPassword: '********'
        });

        try {
            console.log('Sending data to server...');
            const response = await fetch('/FoundIT/auth/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(formData)
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new TypeError("Oops, we haven't got JSON!");
            }

            const data = await response.json();
            console.log('Server response data:', data);

            if (data.success) {
                console.log('Registration successful - Redirecting to login');
                showNotification('success', 'Registration Successful', 'Your account has been created! Please login.');
                setTimeout(() => {
                    window.location.href = '/FoundIT/auth/login.html';
                }, 1200);
            } else {
                console.log('Registration failed:', data.message);
                showNotification('error', 'Registration Failed', data.message || 'Registration failed. Please try again.');
            }
        } catch (error) {
            console.error('Error during registration:', error);
            console.error('Error details:', {
                name: error.name,
                message: error.message,
                stack: error.stack
            });
            showNotification('error', 'Network Error', 'An error occurred. Please try again later.');
        }
    });
});

// --- Custom Notification Helper (inline, copied from login.html) ---
function showNotification(type, title, message) {
    console.log('Showing notification:', type, title, message);
    let notif = document.querySelector('.custom-notification');
    if (!notif) {
        notif = document.createElement('div');
        notif.className = 'custom-notification ' + type;
        notif.innerHTML = `
            <span class="notification-icon">&#9888;</span>
            <div class="notification-content">
                <div class="notification-title">${title}</div>
                <div class="notification-message">${message}</div>
            </div>
        `;
        document.body.appendChild(notif);
    } else {
        notif.className = 'custom-notification ' + type;
        notif.querySelector('.notification-title').textContent = title;
        notif.querySelector('.notification-message').textContent = message;
    }
    notif.classList.add('show');
    setTimeout(() => notif.classList.remove('show'), 4000);
} 