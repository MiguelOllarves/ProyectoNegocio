<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Menú - Tu Inventario</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>iconos_negocio/logo1.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?? "" ?>css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Poppins', sans-serif; background: #fbfdfc; }
        .glass-card { background: rgba(255, 255, 255, 0.9); box-shadow: 0 10px 40px -10px rgba(0,0,0, 0.05); }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex items-center justify-center p-4 bg-slate-50">

    <div x-data="{
        code: '', loading: false, error: '',
        submit() {
            if(!this.code) return;
            window.location.href = '<?= BASE_URL ?>qrmenu/manage/<?= htmlspecialchars($slug) ?>?code=' + this.code;
        }
    }" class="glass-card rounded-3xl p-8 shadow-xl max-w-sm w-full text-center border border-slate-200">
        
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4 shadow-inner">
            <i class="fas fa-lock text-2xl text-slate-400"></i>
        </div>
        <h2 class="text-2xl font-black text-slate-800 mb-2">Acceso Protegido</h2>
        <p class="text-slate-500 text-xs mb-6 font-medium">Ingresa el PIN secreto de 4 dígitos que te proporcionamos al crear este Menú QR.</p>
        
        <form @submit.prevent="submit">
            <div class="relative w-max mx-auto mb-6" x-data="{ show: false }">
                <input :type="show ? 'text' : 'password'" x-model="code" required maxlength="4" class="w-32 pl-8 pr-10 text-center text-3xl font-black tracking-[0.5em] mx-auto py-3 bg-slate-100 border-2 border-slate-200 rounded-xl focus:border-brand-500 focus:outline-none focus:bg-white transition-all text-slate-700" placeholder="••••">
                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-brand-500">
                    <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            
            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-3.5 rounded-xl text-sm transition-colors">
                Ingresar para Actualizar
            </button>
        </form>
        
        <div class="mt-6 text-[10px] text-slate-400 font-bold uppercase tracking-widest"><a href="<?= BASE_URL ?>qrmenu" class="hover:text-slate-600">Volver</a></div>
    </div>

</body>
</html>
