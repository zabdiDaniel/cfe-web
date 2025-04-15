</main>

<footer class="admin-footer">
    <div class="footer-content">
        <div class="footer-branding">
            <span class="footer-logo">CFE</span>
            <span class="footer-title">SIMTLEC <span class="admin-badge">Administrador</span></span>
        </div>
        <div class="footer-legal">
            <span class="copyright">© <?= date('Y') ?> Comisión Federal de Electricidad</span>
            <span class="version">v1.0.0</span>
        </div>
    </div>
</footer>

<style>
    .admin-footer {
        background-color: #003d2a;
        /* Verde oscuro CFE */
        color: white;
        padding: 1.5rem 0;
        margin-top: 3rem;
        /* Espacio antes del footer */
        border-top: 1px solid rgba(255, 255, 255, 0.1);
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
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .admin-badge {
        background-color: rgba(255, 255, 255, 0.2);
        padding: 0.2rem 0.6rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
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
            gap: 1rem;
            text-align: center;
        }

        .footer-legal {
            flex-direction: column;
            gap: 0.5rem;
        }

        .footer-title {
            flex-direction: column;
            gap: 0.3rem;
            margin-bottom: 0.5rem;
        }

        .admin-footer {
            padding: 1.5rem 0;
        }
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Funcionalidad de toggle password
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

