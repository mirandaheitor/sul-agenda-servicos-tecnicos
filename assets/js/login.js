// Handle password visibility toggle
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const toggleButton = document.querySelector('.password-toggle');
    
    toggleButton.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle eye icon
        const icon = toggleButton.querySelector('i');
        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
    });
});

// Handle login form submission
async function handleLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    try {
        const response = await fetch('api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();
        
        if (data.success) {
            // Store session token in cookie
            document.cookie = `auth_token=${data.token}; path=/; secure; samesite=Strict`;
            
            // Redirect based on user role
            switch(data.user.role) {
                case 'admin':
                    window.location.href = 'pages/admin/dashboard.php';
                    break;
                case 'coordinator':
                    window.location.href = 'pages/coordinator/dashboard.php';
                    break;
                case 'technician':
                    window.location.href = 'pages/technician/dashboard.php';
                    break;
                case 'support':
                    window.location.href = 'pages/support/dashboard.php';
                    break;
                default:
                    alert('Tipo de usuário não reconhecido');
            }
        } else {
            alert(data.message || 'Erro ao fazer login. Verifique suas credenciais.');
        }
    } catch (error) {
        console.error('Erro ao fazer login:', error);
        alert('Erro ao conectar com o servidor. Tente novamente mais tarde.');
    }
}

// Check if user is already logged in
function checkAuthStatus() {
    const token = document.cookie.split('; ').find(row => row.startsWith('auth_token='));
    if (token) {
        // Redirect to last accessed page or dashboard
        fetch('api/auth/verify.php', {
            headers: {
                'Authorization': `Bearer ${token.split('=')[1]}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirectUrl || 'pages/dashboard.php';
            }
        })
        .catch(error => {
            console.error('Erro ao verificar autenticação:', error);
            // Clear invalid token
            document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        });
    }
}

// Check auth status when page loads
checkAuthStatus();
