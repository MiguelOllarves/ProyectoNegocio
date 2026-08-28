<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Tu Inventario</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>?serve_logo=1">
    <link rel="stylesheet" href="<?= BASE_URL ?? "" ?>css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; }
        .bg-gradient { background: linear-gradient(135deg, #064e3b 0%, #0e7490 50%, #155e75 100%); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen text-slate-800 p-4">
    
    <div class="w-full max-w-5xl bg-white rounded-3xl shadow-2xl overflow-hidden my-10 flex flex-col md:flex-row">
        <!-- Panel Izquierdo (Visual) -->
        <div class="hidden md:flex md:w-2/5 bg-gradient p-10 text-white flex-col justify-between relative overflow-hidden">
            <div class="absolute top-[-50px] left-[-50px] w-40 h-40 bg-brand-400/30 rounded-full blur-2xl"></div>
            <div class="absolute bottom-[-50px] right-[-50px] w-40 h-40 bg-accent-400/30 rounded-full blur-2xl"></div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-10">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center border border-white/30 shadow-lg p-1">
                        <img src="<?= BASE_URL ?>?serve_logo=1" alt="Logo" class="w-full h-full object-contain drop-shadow-sm">
                    </div>
                    <span class="text-2xl font-black tracking-tight">Tu Inventario</span>
                </div>
                <h2 class="text-3xl font-bold mb-4 leading-tight">Impulsa tu negocio con tecnología</h2>
                <p class="text-white/80 text-sm leading-relaxed">
                    Crea tu cuenta en minutos. Selecciona tu rubro y obtén un Punto de Venta local, un Catálogo Público para vender online y reportes adaptados a tu negocio.
                </p>
            </div>
            <div class="relative z-10 text-sm text-white/60 font-medium">
                &copy; <?= date('Y') ?> Tu Inventario
            </div>
        </div>

        <!-- Formulario Derecho -->
        <div class="w-full md:w-3/5 p-8 md:p-12">
            <div class="mb-8">
                <h1 class="text-2xl font-black text-slate-800 mb-2">Crea tu Espacio de Trabajo</h1>
                <p class="text-slate-500 text-sm">Completa los datos de tu empresa y administrador. ¡Todo en un solo paso!</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 text-sm font-bold border border-red-200 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-lg"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="bg-green-50 text-green-700 p-8 rounded-xl mb-8 text-center border border-green-200 flex flex-col items-center">
                    <i class="fas fa-check-circle text-5xl mb-4 text-green-500"></i>
                    <p class="text-lg font-bold"><?= htmlspecialchars($success) ?></p>
                    <a href="<?= BASE_URL ?>auth" class="mt-6 px-6 py-2.5 bg-brand-600 text-white rounded-xl shadow hover:bg-brand-700 transition font-bold">Ir al Inicio de Sesión</a>
                </div>
            <?php else: ?>

            <form action="<?= BASE_URL ?>auth/process_register" method="POST" id="registerForm" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                
                <!-- Sección Negocio -->
                <div>
                    <h3 class="text-sm font-black text-brand-600 uppercase tracking-wider mb-4 border-b pb-2"><i class="fas fa-store mr-2"></i> Datos del Negocio</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Nombre Comercial <span class="text-red-500">*</span></label>
                            <input type="text" name="business_name" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm font-medium" placeholder="Ej. Inversiones CA">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Categoría / Rubro <span class="text-red-500">*</span></label>
                            <select name="category" required class="w-full p-2.5 bg-brand-50 border border-brand-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm font-bold text-brand-700 cursor-pointer">
                                <option value="">SELECCIONA TU RUBRO...</option>
                                <option value="gastronomia">🍔 Gastronomía (Comida)</option>
                                <option value="viveres">🛒 Víveres (Bodegón)</option>
                                <option value="repuestos">⚙️ Repuestos / Ferretería</option>
                                <option value="vehiculos">🚗 Vehículos</option>
                                <option value="bienes_raices">🏠 Bienes Raíces</option>
                                <option value="tecnologia">📱 Tecnología</option>
                                <option value="general">📦 Mercadería General</option>
                            </select>
                            <p class="text-[10px] text-slate-500 mt-1">*Vital para adaptar tu sistema</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">RIF <span class="text-slate-400 font-normal">(Opcional)</span></label>
                            <input type="text" name="rif" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm font-medium" placeholder="J-123456789">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Teléfono Empresa <span class="text-red-500">*</span></label>
                            <input type="text" name="business_phone" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm font-medium" placeholder="0424-0000000">
                        </div>
                    </div>
                </div>

                <!-- Sección Administrador -->
                <div>
                    <h3 class="text-sm font-black text-brand-600 uppercase tracking-wider mb-4 border-b pb-2 mt-2"><i class="fas fa-user-shield mr-2"></i> Datos del Administrador</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Nombre y Apellido <span class="text-red-500">*</span></label>
                            <input type="text" name="owner_name" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Cédula <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" name="document_id" id="document_id" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm font-medium pr-10">
                                <span id="doc_status" class="absolute right-3 top-2.5 text-sm hidden"></span>
                            </div>
                            <p id="doc_msg" class="text-[10px] mt-1 hidden"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="email" name="email" id="reg_email" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm font-medium pr-10">
                                <span id="email_status" class="absolute right-3 top-2.5 text-sm hidden"></span>
                            </div>
                            <p id="email_msg" class="text-[10px] mt-1 hidden"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Teléfono <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" name="owner_phone" id="owner_phone" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm font-medium pr-10">
                                <span id="phone_status" class="absolute right-3 top-2.5 text-sm hidden"></span>
                            </div>
                            <p id="phone_msg" class="text-[10px] mt-1 hidden"></p>
                        </div>
                        <div x-data="{ show: false }">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Contraseña <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="password" id="password" required minlength="8" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium pr-10">
                                <button type="button" @click="show = !show" class="absolute right-3 top-2.5 text-slate-400 hover:text-brand-500"><i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i></button>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1 leading-tight">Mínimo 8 caracteres. Debe incluir al menos una mayúscula, una minúscula y un número.</p>
                        </div>
                        <div x-data="{ show: false }">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Confirmar Contraseña <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="confirm_password" id="confirm_password" required minlength="4" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium pr-10">
                                <button type="button" @click="show = !show" class="absolute right-3 top-2.5 text-slate-400 hover:text-brand-500"><i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <a href="<?= BASE_URL ?>?login=1" class="text-sm font-bold text-slate-500 hover:text-brand-600 transition">Ya tengo cuenta</a>
                    <button type="submit" id="submitBtn" onclick="return document.getElementById('password').value === document.getElementById('confirm_password').value ? true : (alert('Las contraseñas no coinciden') || false)" class="w-full sm:w-auto bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-brand-500/30 transition-all hover:-translate-y-0.5">
                        Registrar Negocio <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </form>

            <script>
            // === Validación AJAX en tiempo real ===
            const BASE = '<?= BASE_URL ?>';
            const fields = [
                { input: 'document_id', field: 'document_id', status: 'doc_status', msg: 'doc_msg', label: 'cédula' },
                { input: 'reg_email', field: 'email', status: 'email_status', msg: 'email_msg', label: 'correo' },
                { input: 'owner_phone', field: 'phone', status: 'phone_status', msg: 'phone_msg', label: 'teléfono' }
            ];
            let validState = { document_id: true, reg_email: true, owner_phone: true };

            fields.forEach(f => {
                const el = document.getElementById(f.input);
                if (!el) return;
                let timer;
                el.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => checkField(f), 500); });
                el.addEventListener('blur', () => { clearTimeout(timer); checkField(f); });
            });

            async function checkField(f) {
                const el = document.getElementById(f.input);
                const val = el.value.trim();
                const statusEl = document.getElementById(f.status);
                const msgEl = document.getElementById(f.msg);
                if (!val || val.length < 3) { statusEl.classList.add('hidden'); msgEl.classList.add('hidden'); return; }

                try {
                    const res = await fetch(BASE + 'auth/check_unique', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                        body: JSON.stringify({ field: f.field, value: val })
                    });
                    const data = await res.json();
                    statusEl.classList.remove('hidden');
                    msgEl.classList.remove('hidden');
                    
                    if (data.available) {
                        statusEl.innerHTML = '<i class="fas fa-check-circle text-green-500"></i>';
                        el.classList.remove('border-red-400'); el.classList.add('border-green-400');
                        msgEl.textContent = '✓ Disponible'; msgEl.className = 'text-[10px] mt-1 text-green-600 font-bold';
                        validState[f.input] = true;
                    } else {
                        statusEl.innerHTML = '<i class="fas fa-times-circle text-red-500"></i>';
                        el.classList.remove('border-green-400'); el.classList.add('border-red-400');
                        msgEl.textContent = data.message || 'Ya registrado'; msgEl.className = 'text-[10px] mt-1 text-red-600 font-bold';
                        validState[f.input] = false;
                    }
                    updateSubmitBtn();
                } catch(e) {}
            }

            function updateSubmitBtn() {
                const btn = document.getElementById('submitBtn');
                const allValid = Object.values(validState).every(v => v);
                btn.disabled = !allValid;
                btn.style.opacity = allValid ? '1' : '0.5';
                btn.style.pointerEvents = allValid ? 'auto' : 'none';
            }
            </script>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>

