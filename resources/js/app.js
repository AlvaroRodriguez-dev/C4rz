import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// WMS - Verificación de pallets:
// El botón de cámara debe comportarse como un lector que alimenta el buscador
// Select2 y luego dispara la misma consulta que la selección manual.
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btnEscanear');
    const select = document.getElementById('selectPallet');
    const reader = document.getElementById('qrReader');

    // Solo intervenir en la pantalla de verificación de producción.
    if (!btn || !select || !reader || !document.getElementById('palletPanel')) return;

    let scanner = null;
    let procesandoResultado = false;

    const mostrarError = (mensaje) => {
        if (typeof window.mostrarAlerta === 'function') {
            window.mostrarAlerta(mensaje, 'error');
            return;
        }
        console.error(mensaje);
    };

    const detener = async () => {
        if (!scanner) {
            reader.classList.add('hidden');
            return;
        }

        const actual = scanner;
        scanner = null;

        try {
            await actual.stop();
        } catch (error) {
            console.warn('No fue posible detener el lector QR.', error);
        }

        try {
            actual.clear();
        } catch (error) {
            console.warn('No fue posible limpiar el lector QR.', error);
        }

        reader.classList.add('hidden');
    };

    const cargarResultadoEnBuscador = (valor) => {
        const numero = String(valor ?? '').trim();
        if (!numero) {
            mostrarError('El QR no contiene un número de pallet válido.');
            return;
        }

        const $select = window.jQuery ? window.jQuery(select) : null;
        if (!$select || !$select.hasClass('select2-hidden-accessible')) {
            mostrarError('No fue posible acceder al buscador de pallets.');
            return;
        }

        // Escribir el resultado en el Select2, igual que si el usuario
        // hubiera seleccionado manualmente el pallet.
        let option = Array.from(select.options).find(item => item.value === numero);
        if (!option) {
            option = new Option(numero, numero, true, true);
            select.appendChild(option);
        } else {
            option.selected = true;
        }

        $select.val(numero).trigger('change');

        // La vista ya tiene la función de consulta y validación del pallet.
        // La reutilizamos para evitar duplicar la lógica de negocio.
        if (typeof window.cargarPallet === 'function') {
            window.cargarPallet(numero);
        } else {
            mostrarError('No fue posible iniciar la consulta del pallet.');
        }
    };

    const iniciar = async () => {
        if (scanner) {
            await detener();
            return;
        }

        if (typeof window.Html5Qrcode === 'undefined') {
            mostrarError('El lector QR no está disponible en este navegador.');
            return;
        }

        reader.classList.remove('hidden');
        scanner = new window.Html5Qrcode('qrReader');
        procesandoResultado = false;

        try {
            await scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    videoConstraints: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1920, min: 1280 },
                        height: { ideal: 2560, min: 720 },
                        frameRate: { ideal: 30, min: 15 }
                    }
                },
                async (decodedText) => {
                    if (procesandoResultado) return;
                    procesandoResultado = true;

                    // Detener completamente la cámara antes de modificar
                    // el Select2. html5-qrcode expone stop() como Promise.
                    await detener();
                    cargarResultadoEnBuscador(decodedText);
                },
                () => {}
            );
        } catch (error) {
            console.error('No fue posible iniciar la cámara.', error);
            await detener();
            mostrarError('No fue posible acceder a la cámara. Revise los permisos del navegador.');
        }
    };

    // Usamos capture=true para impedir que el handler anterior de la vista
    // ejecute simultáneamente otro Html5Qrcode. Así queda un único flujo.
    btn.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();
        iniciar();
    }, true);
});
