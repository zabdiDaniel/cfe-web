</main>

<footer class="sticker-footer">
    <div class="footer-content">
        <div class="footer-branding">
            <span class="footer-logo">CFE</span>
            <span class="footer-title">SIMTLEC</span>
        </div>
        <div class="footer-legal">
            <span class="copyright">© <?= date('Y') ?> Comisión Federal de Electricidad</span>
            <span class="version">v1.0.0</span>
        </div>
    </div>
</footer>

<style>
    .sticker-footer {
        background-color: #003d2a; /* Verde CFE */
        color: white;
        padding: 1.2rem 0;
        position: sticky;
        bottom: 0;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.08);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        z-index: 100;
    }
    
    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .footer-branding {
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }
    
    .footer-logo {
        font-weight: 800;
        font-size: 1.4rem;
        letter-spacing: 0.5px;
    }
    
    .footer-title {
        font-size: 0.95rem;
        opacity: 0.9;
        font-weight: 500;
    }
    
    .footer-legal {
        display: flex;
        gap: 1.5rem;
        align-items: center;
    }
    
    .copyright {
        font-size: 0.85rem;
        opacity: 0.85;
    }
    
    .version {
        background-color: rgba(255, 255, 255, 0.15);
        padding: 0.25rem 0.6rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            gap: 0.8rem;
            text-align: center;
        }
        
        .footer-legal {
            flex-direction: column;
            gap: 0.5rem;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Funcionalidad de toggle password (mantenido del original)
    const togglePassword = document.getElementById('togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('contrasena');
            const icon = this.querySelector('i');
            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('fa-eye-slash');
            icon.classList.toggle('fa-eye');
        });
    }
</script>
</body>
</html>