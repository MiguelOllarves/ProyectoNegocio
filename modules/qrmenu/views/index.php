<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Menú QR - Tu Inventario</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>iconos_negocio/logo1.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?? "" ?>css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <script>
        // Global CSRF interceptor for standalone page
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
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #fbfdfc 0%, #f0f9f6 50%, #e6f4f1 100%); }
        .glass-card { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: 0 10px 40px -10px rgba(22, 163, 74, 0.1); }
        .btn-gradient { background: linear-gradient(135deg, #16a34a, #10b981); box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.5); transition: all 0.3s; }
        .btn-gradient:hover { box-shadow: 0 15px 30px -5px rgba(16, 185, 129, 0.7); transform: translateY(-2px); background: linear-gradient(135deg, #15803d, #059669); }
        .blob-1 { position: fixed; top: -10%; left: -10%; width: 500px; height: 500px; background: rgba(134, 239, 172, 0.3); border-radius: 50%; filter: blur(90px); z-index: -1; animation: float 8s infinite alternate; }
        .blob-2 { position: fixed; bottom: -10%; right: -10%; width: 600px; height: 600px; background: rgba(52, 211, 153, 0.2); border-radius: 50%; filter: blur(120px); z-index: -1; animation: float 10s infinite alternate-reverse; }
        @keyframes float { 0% { transform: translate(0, 0) scale(1); } 100% { transform: translate(30px, 50px) scale(1.1); } }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen relative overflow-x-hidden pt-8 pb-16">
    <div class="blob-1"></div><div class="blob-2"></div>

    <div class="max-w-4xl mx-auto px-4 relative z-10">
        <!-- Header -->
        <div class="flex items-center justify-between mb-10">
            <a href="<?= BASE_URL ?>" class="flex items-center gap-2">
                <img src="<?= BASE_URL ?>iconos_negocio/logo1.png" alt="Logo" class="w-8 h-8 object-contain">
                <span class="text-xl font-black"><span class="text-slate-900">Tu</span> <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-500 to-brand-700">Inventario</span></span>
            </a>
            <a href="<?= BASE_URL ?>auth/register" class="text-xs font-bold text-brand-600 bg-brand-50 px-4 py-2 rounded-full border border-brand-100 hover:bg-brand-100 transition">Probar el Sistema Completo</a>
        </div>

        <!-- Main Content -->
        <div x-data="qrCreator()" class="glass-card rounded-3xl p-6 md:p-12 shadow-2xl relative">
            <div x-show="!success" x-transition.opacity>
                <div class="text-center mb-10">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-brand-100/50 mb-4 shadow-sm border border-brand-200">
                        <i class="fas fa-qrcode text-3xl text-brand-500"></i>
                    </div>
                    <h1 class="text-3xl md:text-5xl font-black text-slate-800 tracking-tight leading-tight mb-4">Crea el menú con QR de tu negocio <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-500 to-brand-600">Gratis</span></h1>
                    <p class="text-slate-500 max-w-2xl mx-auto font-medium text-sm md:text-base">Sin registro, sin configuraciones complejas. Sube tu imagen o PDF, imprimelo una vez y actualízalo cada vez que cambies el menú sin cambiar el código QR.</p>
                </div>

                <div x-show="error" class="bg-red-50 text-red-600 p-4 rounded-xl text-sm mb-6 border border-red-200 font-bold flex items-center" x-cloak>
                    <i class="fas fa-exclamation-triangle mr-3"></i> <span x-text="error"></span>
                </div>

                <form @submit.prevent="createMenu">
                    <div class="relative border-2 border-dashed border-brand-200 rounded-2xl p-8 text-center bg-white/40 hover:bg-white/60 transition-colors group cursor-pointer mb-8" :class="{'border-brand-500 bg-brand-50/50': file}">
                        <input type="file" id="file" accept=".pdf, image/png, image/jpeg, image/webp" @change="handleFile" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                        <div x-show="!file">
                            <i class="fas fa-file-upload text-4xl text-brand-300 group-hover:text-brand-500 transition-colors mb-3"></i>
                            <h3 class="font-bold text-slate-700 text-lg">Haz clic o arrastra tu archivo aquí</h3>
                            <p class="text-xs text-slate-400 mt-1">Formatos: PDF, JPG, PNG (Máx 6 MB)</p>
                        </div>
                        <div x-show="file" x-cloak>
                            <i class="fas fa-check-circle text-5xl text-brand-500 mb-3 drop-shadow-md"></i>
                            <h3 class="font-bold text-slate-800 text-lg" x-text="fileName"></h3>
                            <p class="text-xs text-brand-600 mt-1 font-bold bg-brand-100 inline-block px-3 py-1 rounded-full text-[10px]"><i class="fas fa-star"></i> Archivo listo para empezar</p>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading || !file" class="w-full btn-gradient text-white font-black py-4 rounded-xl text-lg flex items-center justify-center transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!loading"><i class="fas fa-magic mr-2"></i> Generar Menú con QR</span>
                        <span x-show="loading"><i class="fas fa-circle-notch fa-spin mr-2"></i> Procesando tu menú...</span>
                    </button>
                </form>
            </div>

            <!-- Success State -->
            <div x-show="success" x-cloak x-transition.opacity>
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-4 shadow-inner border-4 border-white">
                        <i class="fas fa-check text-4xl text-green-500"></i>
                    </div>
                    <h2 class="text-3xl font-black text-slate-800">¡Tu Menú QR está listo!</h2>
                    <p class="text-slate-500 mt-2 text-sm">Escanea, descarga y comparte tu nuevo código.</p>
                </div>

                <div class="flex flex-col md:flex-row gap-8 items-center bg-white/50 p-6 rounded-2xl border border-white">
                    <div class="w-full md:w-1/2 flex flex-col items-center">
                        <div id="qrcode-container" class="bg-white p-4 rounded-xl shadow-md border border-gray-100 mb-4"></div>
                        <button @click="downloadQr()" class="bg-brand-600 hover:bg-brand-500 text-white font-bold py-2.5 px-6 rounded-lg text-sm transition-colors w-full sm:w-auto shadow-md">
                            <i class="fas fa-download mr-1"></i> Descargar Imagen QR
                        </button>
                        <a :href="data.qr_url" target="_blank" class="text-xs text-slate-500 hover:text-brand-600 mt-4 font-bold flex items-center"><i class="fas fa-external-link-alt mr-1"></i> Abrir Enlace Público</a>
                    </div>
                    
                    <div class="w-full md:w-1/2">
                        <div class="bg-red-50 border border-red-100 rounded-xl p-5 shadow-sm text-left relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-red-100 rounded-fullblur-xl opacity-50 -mr-10 -mt-10"></div>
                            <h3 class="font-black text-red-700 text-base mb-2 flex items-center"><i class="fas fa-lock mr-2"></i> ¡Guarda esta información!</h3>
                            <p class="text-xs text-red-600/80 mb-4 font-medium leading-relaxed">Este sistema es sin registro. Esta información es la <b>única forma</b> que tendrás de actualizar tu menú el día de mañana.</p>
                            
                            <div class="bg-white rounded-lg p-3 border border-red-100 flex justify-between items-center mb-3">
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase">Enlace de Edición Secreto</span>
                                    <span class="text-xs font-medium text-slate-600 truncate block max-w-[180px]" x-text="data.edit_url"></span>
                                </div>
                                <button @click="copyEditURL" class="w-8 h-8 rounded bg-slate-100 text-slate-500 hover:bg-brand-100 hover:text-brand-600 transition flex items-center justify-center shrink-0" title="Copiar"><i class="fas fa-copy"></i></button>
                            </div>

                            <div class="bg-white rounded-lg p-3 border border-red-100 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center font-black text-red-600 shrink-0 text-lg" x-text="data.code"></div>
                                <div>
                                    <span class="block text-xs font-bold text-slate-700">Tu Código PIN</span>
                                    <span class="text-[10px] text-slate-500">Anota este PIN si abres la URL desde otro dispositivo.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 text-center pt-8 border-t border-slate-200/50">
                    <a href="<?= BASE_URL ?>" class="text-sm font-bold text-brand-600 hover:text-brand-700"><i class="fas fa-arrow-left mr-1"></i> Volver a inicio</a>
                </div>
            </div>
        </div>
        
        <p class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-10">Empoderado por Tu Inventario</p>
    </div>

    <script>
        function qrCreator() {
            return {
                file: null,
                fileName: '',
                base64Data: '',
                loading: false,
                error: '',
                success: false,
                data: {},
                
                handleFile(e) {
                    const f = e.target.files[0];
                    if (!f) return;
                    if (f.size > 6 * 1024 * 1024) {
                        this.error = 'El archivo supera el máximo de 6 MB permitidos.';
                        e.target.value = '';
                        this.file = null;
                        this.fileName = '';
                        return;
                    }
                    this.error = '';
                    this.file = f;
                    this.fileName = f.name;
                    
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        this.base64Data = event.target.result;
                    };
                    reader.readAsDataURL(f);
                },
                
                async createMenu() {
                    if (!this.base64Data) return;
                    this.loading = true;
                    this.error = '';
                    
                    const formData = new FormData();
                    formData.append('menu_file_base64', this.base64Data);
                    
                    try {
                        const res = await fetch('<?= BASE_URL ?>qrmenu/api_create', { method: 'POST', body: formData });
                        const json = await res.json();
                        if (json.success) {
                            this.data = json;
                            this.success = true;
                            setTimeout(() => {
                                new QRCode(document.getElementById("qrcode-container"), {
                                    text: json.qr_url,
                                    width: 180,
                                    height: 180,
                                    colorDark : "#1e293b",
                                    colorLight : "#ffffff",
                                    correctLevel : QRCode.CorrectLevel.H
                                });
                            }, 100);
                        } else {
                            this.error = json.message || 'Error desconocido';
                        }
                    } catch (e) {
                        this.error = 'Error de conexión con el servidor.';
                    } finally {
                        this.loading = false;
                    }
                },
                
                downloadQr() {
                    const img = document.querySelector('#qrcode-container img');
                    if(!img) return;
                    const a = document.createElement('a');
                    a.href = img.src;
                    a.download = 'Menu-QR-' + this.data.slug + '.png';
                    a.click();
                },
                
                async copyEditURL() {
                    try {
                        await navigator.clipboard.writeText(this.data.edit_url);
                        alert('¡Enlace de edición copiado al portapapeles!');
                    } catch(e) {
                        alert('No se pudo copiar automáticamente: ' + this.data.edit_url);
                    }
                }
            }
        }
    </script>
</body>
</html>
