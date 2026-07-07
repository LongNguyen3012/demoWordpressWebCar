(function() {
    const password = document.getElementById('reg_password');
    const confirm = document.getElementById('reg_password_confirm');
    const usernameInput = document.getElementById('reg_username');
    const emailInput = document.getElementById('reg_email');
    const errorDiv = document.getElementById('password-error');
    const strengthBar = document.getElementById('strength-bar');
    const strengthLabel = document.getElementById('strength-label');
    const form = document.getElementById('register-form');
    const requirementsList = document.getElementById('password-requirements');
    const usernameMessage = document.getElementById('username-message');

    const l10n = window.registerL10n || {};
    let usernameValid = false;
    let checkingUsername = false;

    function getStrength(pwd, inputs) {
        let score = 0;
        if (pwd.length >= 8) score++;
        if (/[A-Z]/.test(pwd)) score++;
        if (/[a-z]/.test(pwd)) score++;
        if (/[0-9]/.test(pwd)) score++;
        if (/[^a-zA-Z0-9]/.test(pwd)) score++;

        let penalty = 0;
        if (/(.)\1{2,}/.test(pwd)) penalty++;

        const sequences = ['abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', '0123456789'];
        for (let seq of sequences) {
            for (let i = 0; i < seq.length - 2; i++) {
                if (pwd.indexOf(seq.substr(i, 3)) !== -1) {
                    penalty++;
                    break;
                }
            }
        }

        const keyboard = ['qwertyuiop', 'asdfghjkl', 'zxcvbnm', '1234567890'];
        for (let row of keyboard) {
            for (let i = 0; i < row.length - 2; i++) {
                if (pwd.indexOf(row.substr(i, 3)) !== -1) {
                    penalty++;
                    break;
                }
            }
        }

        const lower = pwd.toLowerCase();
        if (inputs && inputs.length) {
            for (let input of inputs) {
                let clean = input;
                if (input.indexOf('@') !== -1) {
                    clean = input.split('@')[0];
                }
                if (clean.length >= 4 && lower.indexOf(clean.toLowerCase()) !== -1) {
                    penalty++;
                    break;
                }
            }
        }

        let final = Math.max(0, Math.min(4, score - penalty));
        const labels = ['weak', 'fair', 'good', 'strong', 'very_strong'];
        const labelKey = labels[final];
        return {
            score: final,
            label: l10n[labelKey] || labelKey
        };
    }

    function updateRequirements() {
        const pwd = password.value;
        const checks = [
            { key: 'length', test: pwd.length >= 8 },
            { key: 'uppercase', test: /[A-Z]/.test(pwd) },
            { key: 'lowercase', test: /[a-z]/.test(pwd) },
            { key: 'number', test: /[0-9]/.test(pwd) },
            { key: 'special', test: /[^a-zA-Z0-9]/.test(pwd) }
        ];

        requirementsList.innerHTML = '';
        checks.forEach(check => {
            if (!check.test) {
                const li = document.createElement('li');
                li.textContent = l10n[check.key] || check.key;
                requirementsList.appendChild(li);
            }
        });
    }

    function updateStrength() {
        const pwd = password.value;
        const inputs = [
            usernameInput.value,
            emailInput.value
        ];
        const result = getStrength(pwd, inputs);
        const pct = (result.score / 4) * 100;

        const colors = ['#d63638', '#e68a2e', '#f0b400', '#0a7e3c', '#0d8c3a'];
        strengthBar.style.width = pct + '%';
        strengthBar.style.background = colors[result.score];

        strengthLabel.textContent = pwd.length > 0 ? result.label : '';
        strengthLabel.style.color = colors[result.score];
    }

    function checkUsername() {
        const username = usernameInput.value.trim();
        if (username.length < 1) {
            usernameMessage.textContent = '';
            usernameValid = false;
            return;
        }

        checkingUsername = true;
        usernameMessage.textContent = '...';
        usernameMessage.style.color = '#666';

        const data = new FormData();
        data.append('action', 'check_username');
        data.append('username', username);
        data.append('nonce', l10n.nonce);

        fetch(l10n.ajax_url, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            checkingUsername = false;
            if (data.available) {
                usernameValid = true;
                usernameMessage.textContent = l10n.available;
                usernameMessage.style.color = '#0a7e3c';
            } else {
                usernameValid = false;
                usernameMessage.textContent = data.message || l10n.taken;
                usernameMessage.style.color = '#d63638';
            }
        })
        .catch(() => {
            checkingUsername = false;
            usernameValid = false;
            usernameMessage.textContent = 'Error checking username.';
            usernameMessage.style.color = '#d63638';
        });
    }

    // Debounce function with empty-field handling
    let usernameTimeout = null;
    function handleUsernameInput() {
        const username = usernameInput.value.trim();
        if (username.length === 0) {
            usernameMessage.textContent = '';
            usernameValid = false;
            clearTimeout(usernameTimeout);
            return;
        }
        clearTimeout(usernameTimeout);
        usernameTimeout = setTimeout(checkUsername, 200);
    }

    function updateAll() {
        updateRequirements();
        updateStrength();
    }

    form.addEventListener('submit', function(e) {
        const pwd = password.value;
        const inputs = [usernameInput.value, emailInput.value];
        const result = getStrength(pwd, inputs);

        if (!usernameValid && usernameInput.value.trim().length > 0) {
            e.preventDefault();
            errorDiv.textContent = l10n.taken || 'Username already taken.';
            errorDiv.style.color = '#d63638';
            usernameInput.focus();
            return;
        }

        const errors = [];
        if (pwd.length < 8) errors.push(l10n.length);
        if (!/[A-Z]/.test(pwd)) errors.push(l10n.uppercase);
        if (!/[a-z]/.test(pwd)) errors.push(l10n.lowercase);
        if (!/[0-9]/.test(pwd)) errors.push(l10n.number);
        if (!/[^a-zA-Z0-9]/.test(pwd)) errors.push(l10n.special);

        if (errors.length > 0) {
            e.preventDefault();
            errorDiv.textContent = l10n.required || 'Password must meet all requirements above.';
            errorDiv.style.color = '#d63638';
            password.focus();
            return;
        }

        if (result.score < 2) {
            e.preventDefault();
            errorDiv.textContent = l10n.too_weak || 'Password is too weak. Please choose a longer password with mixed characters, or a passphrase.';
            errorDiv.style.color = '#d63638';
            password.focus();
            return;
        }

        if (confirm.value !== pwd) {
            e.preventDefault();
            errorDiv.textContent = l10n.mismatch || 'Passwords do not match.';
            errorDiv.style.color = '#d63638';
            confirm.focus();
            return;
        }
    });

    password.addEventListener('input', updateAll);
    confirm.addEventListener('input', function() {
        if (confirm.value.length > 0 && confirm.value !== password.value) {
            errorDiv.textContent = l10n.mismatch || 'Passwords do not match.';
            errorDiv.style.color = '#d63638';
        } else {
            errorDiv.textContent = '';
            updateAll();
        }
    });

    // Username input: live check (with debounce) + update strength (for personal-info penalty)
    usernameInput.addEventListener('input', function() {
        handleUsernameInput();
        updateAll();
    });

    // Blur: final check when user leaves the field
    usernameInput.addEventListener('blur', function() {
        clearTimeout(usernameTimeout);
        checkUsername();
    });

    emailInput.addEventListener('input', updateAll);

    updateAll();
})();