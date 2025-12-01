// Alternar entre Login y Registro
function toggleForms(e) {
    if(e) e.preventDefault();
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const msg = document.getElementById('message');
    
    msg.style.display = 'none'; 
    
    if (loginForm.classList.contains('active')) {
        loginForm.classList.remove('active');
        registerForm.classList.add('active');
    } else {
        registerForm.classList.remove('active');
        loginForm.classList.add('active');
    }
}

function showMessage(text, isError) {
    const msg = document.getElementById('message');
    msg.textContent = text;
    msg.className = isError ? 'alert alert-error' : 'alert alert-success';
    msg.style.display = 'block';
}

// REGISTRE
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
        
        // Comprovem si la resposta és JSON vàlid
        const text = await response.text();
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showMessage('Compte creat! Inicia sessió ara.', false);
                setTimeout(() => toggleForms(), 1500); 
                e.target.reset();
            } else {
                showMessage(data.message, true);
            }
        } catch (err) {
            console.error('Error parsing JSON:', text);
            showMessage('Error del servidor (mira la consola)', true);
        }

    } catch (error) {
        showMessage('Error de connexió', true);
        console.error(error);
    }
});

// LOGIN
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
        
        const text = await response.text();
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showMessage('Iniciant sessió...', false);
                localStorage.setItem('agrisoft_user', JSON.stringify(data.user));
                
                // --- CANVI IMPORTANT AQUÍ ---
                setTimeout(() => {
                    window.location.href = 'dashboard.html'; 
                }, 1000);
            } else {
                showMessage(data.message, true);
            }
        } catch (err) {
            console.error('Error parsing JSON:', text);
            showMessage('Error del servidor', true);
        }

    } catch (error) {
        showMessage('Error de connexió', true);
        console.error(error);
    }
});