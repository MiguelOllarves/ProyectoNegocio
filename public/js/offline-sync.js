/**
 * TuInventario - Offline Synchronization System
 * Uses Dexie.js to manage IndexedDB
 */

// Initialize Dexie Database
const db = new Dexie("TuInventarioDB");

// Define schema
db.version(1).stores({
    pending_sales: '++id, payload, created_at, status' // status can be 'pending', 'syncing'
});

window.offlineSync = {
    // Save a sale to the local database
    savePendingSale: async function(payload) {
        try {
            await db.pending_sales.add({
                payload: JSON.stringify(payload),
                created_at: new Date().toISOString(),
                status: 'pending'
            });
            console.log('[Offline Sync] Sale queued locally.');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: 'Venta Offline',
                    text: 'La venta ha sido guardada en tu dispositivo. Se sincronizará cuando regrese el internet.',
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
            
            // Try to sync immediately just in case
            this.syncSales();
        } catch (error) {
            console.error('[Offline Sync] Failed to queue sale:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se pudo guardar la venta offline.', 'error');
            }
        }
    },

    // Process all pending sales
    syncSales: async function() {
        if (!navigator.onLine) return; // Don't try if offline

        try {
            const pending = await db.pending_sales.where('status').equals('pending').toArray();
            if (pending.length === 0) return;

            console.log(`[Offline Sync] Found ${pending.length} pending sales. Syncing...`);
            let syncedCount = 0;

            for (const sale of pending) {
                // Mark as syncing to avoid duplicate processing
                await db.pending_sales.update(sale.id, { status: 'syncing' });

                try {
                    // Extract origin from BASE_URL if needed, but since it's the same domain:
                    // Using absolute path from BASE_URL if possible, otherwise relative
                    let processUrl = window.location.origin + '/sales/process';
                    // We can derive base url from the script src if necessary, but typical is:
                    // /sales/process or specific subdomain. Let's use relative to current origin,
                    // but we must be careful with subdirectories. 
                    
                    // Actually, the frontend JS already has access to BASE_URL in index.php
                    // But in offline-sync.js it might not. We will just use the current path up to root.
                    // A safe way: use the endpoint they normally use. But we don't have it here.
                    // We will just fetch '/sales/process'. Wait, what if the app is in a subfolder?
                    // Better to rely on a global BASE_URL variable which is defined in header.php.

                    const targetUrl = (window.BASE_URL || '/') + 'sales/process';

                    const response = await fetch(targetUrl, {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': window.csrfToken || ''
                        },
                        body: sale.payload
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        // Successfully processed on server, delete from local DB
                        await db.pending_sales.delete(sale.id);
                        syncedCount++;
                    } else {
                        // Server rejected it (e.g., validation error, not enough stock)
                        console.error('[Offline Sync] Server rejected sale:', data.message);
                        // We will delete it to avoid stuck queues
                        await db.pending_sales.delete(sale.id);
                    }
                } catch (fetchError) {
                    // Network error during fetch, revert to pending
                    console.error('[Offline Sync] Sync failed for sale:', sale.id, fetchError);
                    await db.pending_sales.update(sale.id, { status: 'pending' });
                }
            }

            if (syncedCount > 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sincronización Exitosa',
                        text: `Se han sincronizado ${syncedCount} venta(s) guardadas sin conexión.`,
                        timer: 4000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            }

        } catch (error) {
            console.error('[Offline Sync] Error during sync process:', error);
        }
    }
};

// Listeners
window.addEventListener('online', () => {
    console.log('[Offline Sync] Internet connection restored. Triggering sync.');
    window.offlineSync.syncSales();
});

// Run on page load if online
if (navigator.onLine) {
    // Small delay to let the app initialize
    setTimeout(() => {
        if(window.offlineSync) window.offlineSync.syncSales();
    }, 2000);
}
