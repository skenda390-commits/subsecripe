// assets/js/auth.js

document.addEventListener('DOMContentLoaded', () => {
    const userBtn = document.getElementById('user-btn');
    const authModal = document.getElementById('auth-modal');

    // Load the modal content
    fetch('auth-modal.html')
        .then(response => response.text())
        .then(data => {
            authModal.innerHTML = data;
            initializeAuthModal();
        });

    userBtn.addEventListener('click', () => {
        authModal.style.display = 'flex';
    });
});

function initializeAuthModal() {
    const authModal = document.getElementById('auth-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const loginMessage = document.getElementById('login-message');
    const registerMessage = document.getElementById('register-message');

    closeModalBtn.addEventListener('click', () => {
        authModal.style.display = 'none';
    });

    // Close modal if clicked outside the content
    authModal.addEventListener('click', (event) => {
        if (event.target === authModal) {
            authModal.style.display = 'none';
        }
    });

    // Login form submission
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;

        try {
            const response = await fetch('../api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });

            const result = await response.json();

            if (response.ok) {
                loginMessage.textContent = result.message;
                loginMessage.className = 'form-message success';
                setTimeout(() => {
                    authModal.style.display = 'none';
                    // Potentially reload the page or update UI
                    window.location.reload();
                }, 1000);
            } else {
                loginMessage.textContent = result.message;
                loginMessage.className = 'form-message error';
            }
        } catch (error) {
            loginMessage.textContent = 'An error occurred. Please try again.';
            loginMessage.className = 'form-message error';
        }
    });

    // Registration form submission
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('register-email').value;
        const password = document.getElementById('register-password').value;

        // Simple device fingerprint
        const device_fingerprint = navigator.userAgent + navigator.language + window.screen.width + window.screen.height;

        try {
            const response = await fetch('../api/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password, device_fingerprint })
            });

            const result = await response.json();

            if (response.status === 201) {
                registerMessage.textContent = result.message;
                registerMessage.className = 'form-message success';
                 setTimeout(() => {
                    // Switch to login tab after successful registration
                    openTab(null, 'login-tab');
                    document.getElementById('login-email').value = email;
                }, 1500);
            } else {
                registerMessage.textContent = result.message;
                registerMessage.className = 'form-message error';
            }
        } catch (error) {
            registerMessage.textContent = 'An error occurred. Please try again.';
            registerMessage.className = 'form-message error';
        }
    });

    // --- PayPal Buttons Logic ---
    const initializePayPalButtons = () => {
        // Check if PayPal SDK is loaded
        if (typeof paypal === 'undefined') {
            console.error('PayPal SDK not loaded.');
            return;
        }

        // Check if user is logged in. If not, show a message.
        if (!window.userData || !window.userData.loggedIn) {
            const plansContainer = document.querySelector('.plans-container');
            const payPalContainers = plansContainer.querySelectorAll('[id^="paypal-button-container"]');
            payPalContainers.forEach(c => c.innerHTML = '<p class="form-message error" style="font-size: 0.8rem;">Please log in to subscribe.</p>')
            return;
        }

        // PayPal plans
        const plans = [
            { id: 'P-6ML527490D2009848NAFY5WA', container: 'paypal-button-container-P-6ML527490D2009848NAFY5WA' },
            { id: 'P-6V5326030C814122HNAFZAFI', container: 'paypal-button-container-P-6V5326030C814122HNAFZAFI' },
            { id: 'P-5VS57764X8846254FNCLRMMA', container: 'paypal-button-container-P-5VS57764X8846254FNCLRMMA' }
        ];

        plans.forEach(plan => {
            const container = document.getElementById(plan.container);
            if(container) {
                container.innerHTML = ''; // Clear any previous messages
                paypal.Buttons({
                    createSubscription: function(data, actions) {
                        return actions.subscription.create({
                            'plan_id': plan.id
                        });
                    },
                    onApprove: function(data, actions) {
                        fetch('../api/paypal_transaction.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                orderID: data.orderID,
                                planId: plan.id
                            })
                        })
                        .then(res => res.json())
                        .then(result => {
                            if (result.status === 'success') {
                                alert('Subscription successful! Your plan has been updated.');
                                window.location.reload();
                            } else {
                                alert('An error occurred: ' + result.message);
                            }
                        });
                    },
                    onError: function(err) {
                        console.error('PayPal button error:', err);
                        alert('An error occurred with the PayPal payment. Please try again.');
                    }
                }).render('#' + plan.container);
            }
        });
    };

    initializePayPalButtons();
}

// Function to handle tab switching in the modal
function openTab(evt, tabName) {
    const tabContents = document.getElementsByClassName('tab-content');
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].style.display = 'none';
    }

    const tabLinks = document.getElementsByClassName('tab-link');
    for (let i = 0; i < tabLinks.length; i++) {
        tabLinks[i].className = tabLinks[i].className.replace(' active', '');
    }

    document.getElementById(tabName).style.display = 'block';
    if (evt) {
        evt.currentTarget.className += ' active';
    } else {
        // Find the button that controls this tab and set it to active
        const btn = document.querySelector(`.tab-link[onclick*="${tabName}"]`);
        if(btn) btn.className += ' active';
    }
}