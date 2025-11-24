// Alternar entre Login y Registro
function toggleForms() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const msg = document.getElementById('message');
    
    msg.style.display = 'none'; // Ocultar mensajes previos
    
    if (loginForm.classList.contains('active')) {
        loginForm.classList.remove('active');
        registerForm.classList.add('active');
    } else {
        registerForm.classList.remove('active');
        loginForm.classList.add('active');
    }
}

// Función genérica para mostrar mensajes
function showMessage(text, isError) {
    const msg = document.getElementById('message');
    msg.textContent = text;
    msg.className = isError ? 'alert alert-error' : 'alert alert-success';
    msg.style.display = 'block';
}

// Lógica de REGISTRO
document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const nombre = document.getElementById('regName').value;
    const email = document.getElementById('regEmail').value;
    const password = document.getElementById('regPass').value;

    try {
        const response = await fetch('register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Compte creat correctament! Ara inicia sessió.', false);
            setTimeout(() => toggleForms(), 1500); // Cambiar a login tras 1.5s
            e.target.reset();
        } else {
            showMessage(data.message, true);
        }
    } catch (error) {
        showMessage('Error de connexió amb el servidor', true);
        console.error(error);
    }
});

// Lógica de LOGIN
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPass').value;

    try {
        const response = await fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Iniciant sessió...', false);
            // Guardar info básica en localStorage para usarla en el panel
            localStorage.setItem('agrisoft_user', JSON.stringify(data.user));
            
            // Redirigir al panel principal (subimos un nivel ../)
            setTimeout(() => {
                window.location.href = '../index.html'; 
            }, 1000);
        } else {
            showMessage(data.message, true);
        }
    } catch (error) {
        showMessage('Error de connexió', true);
        console.error(error);
    }
});