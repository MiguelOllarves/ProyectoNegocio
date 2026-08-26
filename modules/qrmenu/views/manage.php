<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Menú QR - Tu Inventario</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>iconos_negocio/logo1.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?? "" ?>css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <script>
        const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const _origFetch = window.fetch;
        window.fetch = function() {
            let [resource, config] = arguments;
            if(config === undefined) config = {};
            if(config.method && ['POST', 'PUT', 'DELETE'].includes(config.method.toUpperCase())) {
                config.headers = { ...config.headers, 'X-CSRF-Token': _csrfToken };
            }
            return _origFetch(resource, config);
        };
    </script>
    
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Poppins', sans-serif; background: #fbfdfc; }
        .glass-card { background: rgba(255, 255, 255, 0.9); box-shadow: 0 10px 40px -10px rgba(0,0,0, 0.05); }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen relative pt-8 pb-16">

    <div class="max-w-3xl mx-auto px-4 relative z-10">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-200">
            <div class="flex items-center gap-2">
                <i class="fas fa-edit text-brand-500 text-xl"></i>
                <span class="text-lg font-black text-slate-800">Actualizar Menú</span>
            </div>
            <a href="<?= BASE_URL ?>qrmenu" class="text-xs font-bold text-slate-400 hover:text-slate-600 transition"><i class="fas fa-arrow-left"></i> Salir</a>
        </div>

        <div x-data="qrManager()" class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-200">
            <div class="flex flex-col md:flex-row gap-8">
                
                <!-- Columna Actual -->
                <div class="w-full md:w-1/3 text-center border-r-0 md:border-r border-slate-100 pr-0 md:pr-8">
                    <h4 class="text-xs font-bold text-slate-400 uppercase mb-4">Tu QR Actual</h4>
                    <div id="qrcode-container" class="bg-slate-50 p-4 rounded-xl border border-slate-100 mb-4 inline-block"></div>
                    <a href="<?= $qr_url ?>" target="_blank" class="block w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-lg transition"><i class="fas fa-external-link-alt mr-1"></i> Ver en vivo</a>
                </div>

                <!-- Columna Subida -->
                <div class="w-full md:w-2/3">
                    <h3 class="text-xl font-black text-slate-800 mb-2">Reemplazar Archivo</h3>
                    <p class="text-xs text-slate-500 mb-6 font-medium">Sube simplemente el nuevo documento. El código QR principal se mantendrá intacto y apuntará instantáneamente al archivo nuevo.</p>

                    <div x-show="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-xs mb-4 border border-red-200 font-bold" x-cloak>
                        <i class="fas fa-exclamation-triangle mr-1"></i> <span x-text="error"></span>
                    </div>
                    <div x-show="successMsg" class="bg-green-50 text-green-600 p-3 rounded-lg text-xs mb-4 border border-green-200 font-bold" x-cloak>
                        <i class="fas fa-check-circle mr-1"></i> <span x-text="successMsg"></span>
                    </div>

                    <form @submit.prevent="updateMenu">
                        <div class="relative border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center bg-slate-50 hover:bg-slate-100 transition-colors cursor-pointer mb-6" :class="{'border-brand-500 bg-brand-50': file}">
                            <input type="file" id="file" accept=".pdf, image/png, image/jpeg, image/webp" @change="handleFile" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div x-show="!file">
                                <i class="fas fa-file-upload text-3xl text-slate-300 mb-2"></i>
                                <h4 class="font-bold text-slate-600 text-sm">Selecciona tu nuevo menú</h4>
                                <p class="text-[10px] text-slate-400 mt-1">PDF o Imágenes (Máx 6 MB)</p>
                            </div>
                            <div x-show="file" x-cloak>
                                <i class="fas fa-check text-2xl text-brand-500 mb-2"></i>
                                <h4 class="font-bold text-slate-800 text-sm truncate px-4" x-text="fileName"></h4>
                                <p class="text-[10px] text-brand-600 mt-1 font-bold">Listo para reemplazar</p>
                            </div>
                        </div>

                        <button type="submit" :disabled="loading || !file" class="w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-3.5 rounded-xl text-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!loading"><i class="fas fa-sync-alt mr-1"></i> Guardar Cambios</span>
                            <span x-show="loading"><i class="fas fa-circle-notch fa-spin mr-1"></i> Guardando...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function qrManager() {
            return {
                file: null, fileName: '', base64Data: '',
                loading: false, error: '', successMsg: '',
                slug: '<?= htmlspecialchars($slug) ?>',
                code: '<?= htmlspecialchars($code) ?>',
                qrUrl: '<?= $qr_url ?>',
                
                init() {
                    new QRCode(document.getElementById("qrcode-container"), {
                        text: this.qrUrl, width: 120, height: 120,
                        colorDark : "#334155", colorLight : "#f8fafc", correctLevel : QRCode.CorrectLevel.H
                    });
                },
                
                handleFile(e) {
                    const f = e.target.files[0];
                    if (!f) return;
                    if (f.size > 6 * 1024 * 1024) {
                        this.error = 'El archivo supera el máximo de 6 MB.';
                        e.target.value = ''; this.file = null; this.fileName = '';
                        return;
                    }
                    this.error = ''; this.successMsg = '';
                    this.file = f; this.fileName = f.name;
                    
                    const reader = new FileReader();
                    reader.onload = (event) => { this.base64Data = event.target.result; };
                    reader.readAsDataURL(f);
                },
                
                async updateMenu() {
                    if (!this.base64Data) return;
                    this.loading = true; this.error = ''; this.successMsg = '';
                    
                    const fd = new FormData();
                    fd.append('menu_file_base64', this.base64Data);
                    fd.append('slug', this.slug);
                    fd.append('code', this.code);
                    
                    try {
                        const res = await fetch('<?= BASE_URL ?>qrmenu/api_update', { method: 'POST', body: fd });
                        const json = await res.json();
                        if (json.success) {
                            this.successMsg = json.message;
                            this.file = null;
                            document.getElementById('file').value = '';
                        } else {
                            this.error = json.message || 'Error desconocido';
                        }
                    } catch (e) {
                        this.error = 'Error de conexión.';
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
