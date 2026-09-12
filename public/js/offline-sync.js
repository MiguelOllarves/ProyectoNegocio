/**
 * TuInventario - Offline Synchronization System v2.0
 * Uses Dexie.js to manage IndexedDB
 * Handles restaurant options (product configurations) in offline payloads.
 * Enhanced with retry logic and conflict resolution.
 */

// Initialize Dexie Database
const db = new Dexie("TuInventarioDB");

// Define schema - v3 adds retry count and batch support
db.version(3).stores({
    pending_sales: '++id, payload, created_at, status, retry_count, synced_at'
});

window.offlineSync = {
    MAX_RETRIES: 5,
    RETRY_DELAY_MS: 5000,

    // Save a sale to the local database
    savePendingSale: async function(payload) {
        try {
            await db.pending_sales.add({
                payload: JSON.stringify(payload),
                created_at: new Date().toISOString(),
                status: 'pending',
                retry_count: 0,
                synced_at: null
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
                    position: 'top-end',
                    background: '#1e293b',
                    color: '#e2e8f0',
                    iconColor: '#3b82f6'
                });
            }
            
            // Try to sync immediately just in case
            this.syncSales();
        } catch (error) {
            console.error('[Offline Sync] Failed to queue sale:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo guardar la venta offline.',
                    confirmButtonColor: '#2563eb'
                });
            }
        }
    },

    // Process all pending sales with retry logic
    syncSales: async function() {
        if (!navigator.onLine) return;

        try {
            const pending = await db.pending_sales.where('status').equals('pending').toArray();
            if (pending.length === 0) return;

            console.log(`[Offline Sync] Found ${pending.length} pending sales. Syncing...`);
            let syncedCount = 0;
            let failedCount = 0;

            for (const sale of pending) {
                // Mark as syncing to avoid duplicate processing
                await db.pending_sales.update(sale.id, { status: 'syncing' });

                try {
                    const targetUrl = (window.BASE_URL || '/') + 'sales/process';

                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10s timeout

                    const response = await fetch(targetUrl, {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': window.csrfToken || ''
                        },
                        body: sale.payload,
                        signal: controller.signal
                    });

                    clearTimeout(timeoutId);
                    const data = await response.json();
                    
                    if (data.success) {
                        await db.pending_sales.update(sale.id, { 
                            status: 'synced',
                            synced_at: new Date().toISOString()
                        });
                        // Delete after successful sync to keep DB clean
                        setTimeout(() => db.pending_sales.delete(sale.id), 60000);
                        syncedCount++;
                    } else {
                        console.error('[Offline Sync] Server rejected sale:', data.message);
                        const retryCount = (sale.retry_count || 0) + 1;
                        if (retryCount >= this.MAX_RETRIES) {
                            await db.pending_sales.update(sale.id, { 
                                status: 'failed',
                                retry_count: retryCount 
                            });
                            failedCount++;
                        } else {
                            await db.pending_sales.update(sale.id, { 
                                status: 'pending',
                                retry_count: retryCount 
                            });
                        }
                    }
                } catch (fetchError) {
                    console.error('[Offline Sync] Sync failed for sale:', sale.id, fetchError);
                    const retryCount = (sale.retry_count || 0) + 1;
                    if (retryCount >= this.MAX_RETRIES) {
                        await db.pending_sales.update(sale.id, { 
                            status: 'failed',
                            retry_count: retryCount 
                        });
                        failedCount++;
                    } else {
                        await db.pending_sales.update(sale.id, { 
                            status: 'pending',
                            retry_count: retryCount 
                        });
                    }
                }
            }

            if (syncedCount > 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sincronización Exitosa',
                        text: `${syncedCount} venta(s) sincronizada(s)${failedCount > 0 ? `. ${failedCount} con errores.` : '.'}`,
                        timer: 4000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end',
                        background: '#1e293b',
                        color: '#e2e8f0',
                        iconColor: '#22c55e'
                    });
                }
                // Notify user to refresh
                if (typeof htmx !== 'undefined') {
                    document.body.dispatchEvent(new Event('sync:complete'));
                }
            }

        } catch (error) {
            console.error('[Offline Sync] Error during sync process:', error);
        }
    },

    // Get count of pending sales
    getPendingCount: async function() {
        try {
            return await db.pending_sales.where('status').equals('pending').count();
        } catch (e) {
            return 0;
        }
    },

    // Clear all failed sales
    clearFailed: async function() {
        await db.pending_sales.where('status').equals('failed').delete();
    }
};

// Listeners
window.addEventListener('online', () => {
    console.log('[Offline Sync] Internet connection restored. Triggering sync.');
    setTimeout(() => window.offlineSync.syncSales(), 1000);
});

window.addEventListener('offline', () => {
    console.log('[Offline Sync] Internet connection lost. Queuing sales offline.');
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Sin Conexión',
            text: 'Estás sin internet. Las ventas se guardarán localmente.',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
            background: '#1e293b',
            color: '#e2e8f0',
            iconColor: '#f59e0b'
        });
    }
});

// Run on page load if online
if (navigator.onLine) {
    setTimeout(() => {
        if(window.offlineSync) window.offlineSync.syncSales();
    }, 2000);
}
