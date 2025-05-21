// Form elements
const form = document.querySelector('.sign-up-form');
const step1 = document.getElementById('step1');
const step2 = document.getElementById('step2');
const nextBtn = document.getElementById('next-btn');

// Create back button
const backBtn = document.createElement('button');
backBtn.type = 'button';
backBtn.className = 'back-btn';
backBtn.textContent = 'Back';
const buttonContainer = step2.querySelector('.button-container');
buttonContainer.insertBefore(backBtn, buttonContainer.firstChild);

// Input elements
const firstNameInput = document.getElementById('first-name');
const lastNameInput = document.getElementById('last-name');
const studentNumberInput = document.getElementById('student-number');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('new-password');
const confirmPasswordInput = document.getElementById('confirm-password');
const eulaCheckbox = document.getElementById('eula');
const eulaContainer = document.querySelector('.eula-container');

// Password strength indicators
const passwordStrengthIndicator = document.createElement('div');
passwordStrengthIndicator.className = 'password-strength';
passwordInput.parentElement.appendChild(passwordStrengthIndicator);

// Validation functions
function isValidName(name) {
    // At least 2 characters, letters only (including spaces and hyphens for compound names)
    const nameRegex = /^[A-Za-z\s-]{2,}$/;
    return nameRegex.test(name);
}

function isValidEmail(email) {
    // More strict email validation
    const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return emailRegex.test(email);
}

function isValidStudentNumber(number) {
    // Exactly 10-12 digits
    const studentNumberRegex = /^\d{10,15}$/;
    return studentNumberRegex.test(number);
}

function isEmpty(value) {
    return value.trim() === '';
}

function validateStep1Fields() {
    let isValid = true;
    
    if (isEmpty(firstNameInput.value)) {
        showError(firstNameInput, 'First name is required');
        isValid = false;
    } else if (!isValidName(firstNameInput.value)) {
        showError(firstNameInput, 'Enter a valid first name (at least 2 letters)');
        isValid = false;
    } else {
        hideError(firstNameInput);
    }
    
    if (isEmpty(lastNameInput.value)) {
        showError(lastNameInput, 'Last name is required');
        isValid = false;
    } else if (!isValidName(lastNameInput.value)) {
        showError(lastNameInput, 'Enter a valid last name (at least 2 letters)');
        isValid = false;
    } else {
        hideError(lastNameInput);
    }
    
    if (isEmpty(studentNumberInput.value)) {
        showError(studentNumberInput, 'Student number is required');
        isValid = false;
    } else if (!isValidStudentNumber(studentNumberInput.value)) {
        showError(studentNumberInput, 'Student number must be 10-12 digits');
        isValid = false;
    } else {
        hideError(studentNumberInput);
    }
    
    if (!eulaCheckbox.checked) {
        showError(eulaContainer, 'Please agree to the EULA before continuing');
        isValid = false;
    } else {
        hideError(eulaContainer);
    }
    
    return isValid;
}

function validateStep2Fields() {
    let isValid = true;
    
    if (isEmpty(emailInput.value)) {
        showError(emailInput, 'Email is required');
        isValid = false;
    } else if (!isValidEmail(emailInput.value)) {
        showError(emailInput, 'Please enter a valid email address');
        isValid = false;
    } else {
        hideError(emailInput);
    }
    
    if (isEmpty(passwordInput.value)) {
        showError(passwordInput, 'Password is required');
        isValid = false;
    } else {
        const strength = checkPasswordStrength(passwordInput.value);
        if (strength.strength === 'Weak') {
            showError(passwordInput, 'Password is too weak');
            isValid = false;
        } else {
            hideError(passwordInput);
        }
    }
    
    if (isEmpty(confirmPasswordInput.value)) {
        showError(confirmPasswordInput, 'Please confirm your password');
        isValid = false;
    } else if (confirmPasswordInput.value !== passwordInput.value) {
        showError(confirmPasswordInput, 'Passwords do not match');
        isValid = false;
    } else {
        hideError(confirmPasswordInput);
    }
    
    return isValid;
}

function checkPasswordStrength(password) {
    let strength = 0;
    const feedback = [];

    // Length check
    if (password.length < 6) {
        feedback.push('Too short');
    } else {
        strength += 1;
    }

    // Uppercase check
    if (/[A-Z]/.test(password)) {
        strength += 1;
    } else {
        feedback.push('Add uppercase');
    }

    // Lowercase check
    if (/[a-z]/.test(password)) {
        strength += 1;
    } else {
        feedback.push('Add lowercase');
    }

    // Number check
    if (/\d/.test(password)) {
        strength += 1;
    } else {
        feedback.push('Add number');
    }

    // Special character check
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
        strength += 1;
    } else {
        feedback.push('Add special char');
    }

    let strengthText = '';
    let strengthClass = '';

    switch (true) {
        case (strength <= 2):
            strengthText = 'Weak';
            strengthClass = 'weak';
            break;
        case (strength <= 4):
            strengthText = 'Medium';
            strengthClass = 'medium';
            break;
        case (strength === 5):
            strengthText = 'Strong';
            strengthClass = 'strong';
            break;
    }

    return {
        strength: strengthText,
        feedback: feedback.join(', '),
        class: strengthClass
    };
}

// Event Listeners
studentNumberInput.addEventListener('input', function() {
    if (!isEmpty(this.value)) {
        const isValid = isValidStudentNumber(this.value);
        this.classList.toggle('invalid', !isValid);
        if (!isValid) {
            showError(this, 'Student number must be 10-12 digits');
        } else {
            hideError(this);
        }
    }
});

emailInput.addEventListener('input', function() {
    if (!isEmpty(this.value)) {
        const isValid = isValidEmail(this.value);
        this.classList.toggle('invalid', !isValid);
        if (!isValid) {
            showError(this, 'Please enter a valid email address');
        } else {
            hideError(this);
        }
    }
});

passwordInput.addEventListener('input', function() {
    if (!isEmpty(this.value)) {
        const strength = checkPasswordStrength(this.value);
        passwordStrengthIndicator.textContent = `${strength.strength}${strength.feedback ? ': ' + strength.feedback : ''}`;
        passwordStrengthIndicator.className = `password-strength ${strength.class}`;
    }
});

confirmPasswordInput.addEventListener('input', function() {
    if (!isEmpty(this.value)) {
        const isMatch = this.value === passwordInput.value;
        this.classList.toggle('invalid', !isMatch);
        if (!isMatch) {
            showError(this, 'Passwords do not match');
        } else {
            hideError(this);
        }
    }
});

// Navigation between steps
nextBtn.addEventListener('click', function() {
    // Clear any existing EULA error
    hideError(eulaContainer);
    
    if (!eulaCheckbox.checked) {
        showError(eulaContainer, 'Please agree to the EULA before continuing');
        return;
    }

    if (validateStep1Fields()) {
        step1.style.opacity = '0';
        setTimeout(() => {
            step1.style.display = 'none';
            step2.style.display = 'block';
            setTimeout(() => {
                step2.classList.add('active');
                const indicator2 = step2.querySelector('.indicator-line-2');
                indicator2.style.backgroundColor = 'var(--clr-primary)';
            }, 50);
        }, 300);
    }
});

backBtn.addEventListener('click', function() {
    step2.classList.remove('active');
    const indicator2 = step2.querySelector('.indicator-line-2');
    indicator2.style.backgroundColor = '#E5E7EB';
    setTimeout(() => {
        step2.style.display = 'none';
        step1.style.display = 'block';
        setTimeout(() => {
            step1.style.opacity = '1';
        }, 50);
    }, 300);
});

// Form submission
form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (validateStep1Fields() && validateStep2Fields()) {
        // If all validations pass, you can submit the form
        console.log('Form submitted successfully');
    }
});

// Event Listeners for real-time validation
firstNameInput.addEventListener('input', function() {
    if (!isEmpty(this.value)) {
        const isValid = isValidName(this.value);
        this.classList.toggle('invalid', !isValid);
        if (!isValid) {
            showError(this, 'Enter a valid first name (at least 2 letters)');
        } else {
            hideError(this);
        }
    }
});

lastNameInput.addEventListener('input', function() {
    if (!isEmpty(this.value)) {
        const isValid = isValidName(this.value);
        this.classList.toggle('invalid', !isValid);
        if (!isValid) {
            showError(this, 'Enter a valid last name (at least 2 letters)');
        } else {
            hideError(this);
        }
    }
});

// Helper functions
function showError(element, message) {
    let errorDiv = element.parentElement.querySelector('.error-message');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        element.parentElement.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
    element.classList.add('invalid');
}

function hideError(element) {
    const errorDiv = element.parentElement.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
    element.classList.remove('invalid');
} 