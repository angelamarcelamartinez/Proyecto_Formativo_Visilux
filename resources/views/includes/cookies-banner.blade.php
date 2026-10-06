<!-- =========================================
     DIÁLOGO DE COOKIES (Visioptica)
========================================= -->
<style>
    dialog#cookieDialog.cookie-dialog {
        position: fixed !important;
        bottom: 20px !important;
        top: auto !important;
        left: 0 !important;
        right: 0 !important;
        margin: auto !important;
        width: 90% !important;
        max-width: 850px !important;
        padding: 30px !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 1.25rem !important;
        background: #ffffff !important;
        color: #2A2A2A !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        z-index: 99999 !important;
    }

    dialog#cookieDialog.cookie-dialog::backdrop {
        background: rgba(42, 42, 42, 0.6) !important;
        backdrop-filter: blur(4px) !important;
    }

    dialog#cookieDialog .cookie-header {
        margin: 0 0 15px !important;
        color: #2A2A2A !important;
        font-size: 1.4rem !important;
        font-weight: 700 !important;
    }

    dialog#cookieDialog .cookie-header i {
        color: #B08D3E !important;
    }

    dialog#cookieDialog .cookie-body {
        color: #6B6B6B !important;
        line-height: 1.6 !important;
        font-size: 0.95rem !important;
        margin-bottom: 20px !important;
    }

    dialog#cookieDialog .cookie-body strong {
        color: #B08D3E !important;
    }

    dialog#cookieDialog .text-gold {
        color: #B08D3E !important;
    }

    dialog#cookieDialog .custom-checkbox:checked {
        background-color: #B08D3E !important;
        border-color: #B08D3E !important;
    }

    dialog#cookieDialog .cookie-actions {
        display: flex !important;
        justify-content: flex-end !important;
        gap: 12px !important;
        flex-wrap: wrap !important;
    }

    dialog#cookieDialog .cookie-actions .btn {
        padding: 10px 22px !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        cursor: pointer !important;
    }

    dialog#cookieDialog .btn-cookie-accept {
        background: #B08D3E !important;
        color: #ffffff !important;
        border: none !important;
    }

    dialog#cookieDialog .btn-cookie-accept:hover {
        background: #D4AF6A !important;
        color: #ffffff !important;
    }

    dialog#cookieDialog .btn-cookie-secondary {
        background: #FAF9F7 !important;
        color: #5C7080 !important;
        border: 1px solid #cbd5e1 !important;
    }

    dialog#cookieDialog .btn-cookie-secondary:hover {
        background: #e2e8f0 !important;
        color: #2A2A2A !important;
    }

    dialog#cookieDialog .btn-cookie-deny {
        background: transparent !important;
        color: #6B6B6B !important;
        border: 1px solid transparent !important;
    }

    dialog#cookieDialog .btn-cookie-deny:hover {
        background: #f8fafc !important;
        color: #dc3545 !important;
    }

    @media (max-width: 768px) {
        dialog#cookieDialog.cookie-dialog {
            width: 95% !important;
            padding: 20px !important;
            bottom: 10px !important;
        }
        dialog#cookieDialog .cookie-header {
            font-size: 1.2rem !important;
        }
        dialog#cookieDialog .cookie-actions {
            flex-direction: column-reverse !important;
        }
        dialog#cookieDialog .cookie-actions .btn {
            width: 100% !important;
            text-align: center !important;
        }
    }
</style>

<dialog id="cookieDialog" class="cookie-dialog">
    <h2 class="cookie-header">
        <i class="fas fa-cookie-bite me-2"></i> Gestión de Cookies
    </h2>

    <p class="cookie-body">
        En <strong>Visioptica</strong> utilizamos cookies para mejorar tu experiencia de navegación, optimizar nuestros servicios y recordar tus preferencias.
        <br><br>
        Puedes aceptar todas las cookies, rechazarlas o personalizar cuáles deseas permitir.
    </p>

    <div id="cookieOptions" class="cookie-options" style="display:none;">
        <hr class="text-muted">
        <h5 class="text-gold fw-bold mb-3">
            Personalizar cookies
        </h5>

        <div class="form-check mb-2">
            <input class="form-check-input custom-checkbox" type="checkbox" checked disabled id="necessaryCookies">
            <label class="form-check-label fw-semibold text-dark" for="necessaryCookies">
                Cookies necesarias
            </label>
            <small class="d-block text-muted">
                Necesarias para el funcionamiento básico del sistema y seguridad.
            </small>
        </div>

        <div class="form-check mb-2">
            <input class="form-check-input custom-checkbox" type="checkbox" id="preferencesCookies">
            <label class="form-check-label fw-semibold text-dark" for="preferencesCookies">
                Cookies de preferencias
            </label>
            <small class="d-block text-muted">
                Permiten recordar configuraciones y elecciones del usuario.
            </small>
        </div>

        <div class="form-check mb-2">
            <input class="form-check-input custom-checkbox" type="checkbox" id="analyticsCookies">
            <label class="form-check-label fw-semibold text-dark" for="analyticsCookies">
                Cookies analíticas
            </label>
            <small class="d-block text-muted">
                Nos ayudan a conocer cómo se utiliza el sitio para mejorarlo continuamente.
            </small>
        </div>
    </div>

    <form method="dialog" class="cookie-actions mt-4">
        <button type="submit" id="btnDeny" class="btn btn-cookie-deny">
            Rechazar
        </button>
        <button type="button" id="btnCustomize" class="btn btn-cookie-secondary">
            Personalizar
        </button>
        <button type="submit" id="btnAccept" class="btn btn-cookie-accept">
            Aceptar todas
        </button>
    </form>
</dialog>

<!-- SCRIPT DE GESTIÓN DE COOKIES -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const cookieDialog = document.getElementById("cookieDialog");
        const btnAccept = document.getElementById("btnAccept");
        const btnDeny = document.getElementById("btnDeny");
        const btnCustomize = document.getElementById("btnCustomize");
        const cookieOptions = document.getElementById("cookieOptions");

        const preferencesCookies = document.getElementById("preferencesCookies");
        const analyticsCookies = document.getElementById("analyticsCookies");

        const cookieSettings = localStorage.getItem("cookieSettings");

        if(!cookieSettings){
            if (typeof cookieDialog.showModal === "function") {
                cookieDialog.showModal();
            } else {
                cookieDialog.setAttribute("open", true);
            }
        }

        btnAccept.addEventListener("click", () => {
            localStorage.setItem(
                "cookieSettings",
                JSON.stringify({
                    necessary: true,
                    preferences: true,
                    analytics: true
                })
            );
            console.log("Todas las cookies aceptadas.");
        });

        btnDeny.addEventListener("click", () => {
            localStorage.removeItem("cookieSettings");
            console.log("Cookies rechazadas.");
        });

        btnCustomize.addEventListener("click", () => {
            cookieOptions.style.display = "block";
            btnCustomize.style.display = "none";
        });

        cookieOptions.addEventListener("change", () => {
            localStorage.setItem(
                "cookieSettings",
                JSON.stringify({
                    necessary: true,
                    preferences: preferencesCookies.checked,
                    analytics: analyticsCookies.checked
                })
            );
            console.log("Preferencias personalizadas guardadas.");
        });
    });
</script>