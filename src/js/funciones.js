import Swal from 'sweetalert2';
import DataTable from "datatables.net-bs5";
import { lenguaje } from "./lenguaje.js";
import { CONFIG } from './config.js';

/**
 * Valida los campos de un formulario
 * 
 * Propósito:
 * - Verifica que los campos requeridos no estén vacíos
 * - Marca visualmente los campos inválidos
 * - Permite excluir campos específicos de la validación
 * - Determina si el formulario es válido para enviar
 * 
 * Analogía:
 * Es como un inspector que revisa una lista de documentos:
 * 1. Revisa cada documento (campo)
 * 2. Marca con rojo los que faltan (is-invalid)
 * 3. Ignora los documentos opcionales (excepciones)
 * 4. Al final dice si todo está completo o no
 * 
 * Ejemplo de uso:
 * const formCategoria = document.querySelector('#formCategoria');
 * 
 * if (validarFormulario(formCategoria)) {
 *     const formData = new FormData(formCategoria);
 *     guardarCategoria(formData);
 * } else {
 *     mostrarAlerta('error', 'Validación', 'Complete todos los campos requeridos');
 * }
 * 
 * if (validarFormulario(formCategoria, ['imagen', 'descripcion'])) {
 *     procesarFormularioParcial();
 * }
 * 
 * @param {HTMLFormElement} formulario - El formulario a validar
 * @param {string[]} excepciones - IDs de campos que no requieren validación
 * @returns {boolean} true si el formulario es válido, false si hay campos requeridos vacíos
 */

export const validarFormulario = (formulario, excepciones = []) => {
    // 1. Selecciona todos los campos del formulario
    const elements = formulario.querySelectorAll("input, select, textarea");

    // 2. Array para almacenar resultados de validación
    let validarFormulario = []

    // 3. Revisa cada campo
    elements.forEach(element => {
        // Si el campo está vacío Y no está en las excepciones
        if (!element.value.trim() && !excepciones.includes(element.id)) {
            // Marca el campo como inválido visualmente
            element.classList.add('is-invalid');
            // Registra la invalidación
            validarFormulario.push(false)
        } else {
            // Remueve la marca de inválido si el campo es válido
            element.classList.remove('is-invalid');
        }
    });

    // 4. Verifica si hay algún campo inválido
    let noenviar = validarFormulario.includes(false);

    // 5. Retorna true si todo es válido, false si hay campos inválidos
    return !noenviar;
}

/**
 * Configuración personalizada de notificaciones Toast
 * 
 * Propósito:
 * - Proporciona notificaciones no intrusivas
 * - Muestra mensajes temporales en la esquina superior
 * - Incluye barra de progreso para el tiempo restante
 * - Permite pausar/reanudar el temporizador
 * 
 * Analogía:
 * Es como un post-it digital que:
 * 1. Aparece discretamente en una esquina
 * 2. Se desvanece automáticamente
 * 3. Muestra cuánto tiempo permanecerá visible
 * 4. Se puede "congelar" al pasar el mouse por encima
 * 
 * Ejemplo de uso:
 * // Mostrar una notificación de éxito
 * Toast.fire({
 *     icon: 'success',
 *     title: 'Categoría guardada correctamente'
 * });
 * 
 * // Mostrar una notificación de error
 * Toast.fire({
 *     icon: 'error',
 *     title: 'No se pudo guardar la categoría'
 * });
 * 
 * // Mostrar una notificación de información
 * Toast.fire({
 *     icon: 'info',
 *     title: 'Procesando solicitud...'
 * });
 */
export const Toast = Swal.mixin({
    toast: true,                  // Habilita el modo toast
    position: 'top-end',         // Posición en la pantalla
    showConfirmButton: false,     // Oculta el botón de confirmación
    timer: 3000,                 // Duración en milisegundos
    timerProgressBar: true,      // Muestra barra de progreso
    didOpen: (toast) => {
        // Pausa el temporizador al pasar el mouse
        toast.addEventListener('mouseenter', Swal.stopTimer)
        // Reanuda el temporizador al quitar el mouse
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});


// MIASSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSS


/**
 * Realiza peticiones HTTP a la API y maneja las respuestas
 * 
 * Propósito:
 * - Estandariza las peticiones a la API
 * - Valida las respuestas del servidor
 * - Maneja errores de forma consistente
 * - Asegura respuestas JSON válidas
 * 
 * Analogía:
 * Es como un mensajero especializado que:
 * 1. Entrega solicitudes en un formato específico
 * 2. Verifica que las respuestas sean válidas
 * 3. Traduce las respuestas a un formato útil
 * 4. Reporta cualquier problema en el proceso
 * 
 * Ejemplo de uso:
 * // Obtener lista de categorías
 * try {
 *     const data = await apiFetch('/categorias/obtenerCategorias');
 *     console.log('Categorías:', data.categorias);
 * } catch (error) {
 *     console.error('Error:', error.message);
 * }
 * 
 * // Guardar nueva categoría
 * try {
 *     const formData = new FormData(formulario);
 *     const data = await apiFetch('/categorias/guardarCategoria', {
 *         method: 'POST',
 *         body: formData
 *     });
 *     console.log('Respuesta:', data.mensaje);
 * } catch (error) {
 *     console.error('Error:', error.message);
 * }
 * 
 * @param {string} url - URL del endpoint de la API
 * @param {Object} options - Opciones de la petición fetch
 * @param {string} options.method - Método HTTP ('GET', 'POST', etc.)
 * @param {FormData|null} options.body - Datos a enviar en la petición
 * @returns {Promise<Object>} Datos de la respuesta parseados como JSON
 * @throws {Error} Si la respuesta está vacía o no es JSON válido
 */
export async function apiFetch(endpoint, options = {}) {
    try {
        // 1) Montar la URL base
        let url = `${CONFIG.API_URL}${endpoint}`;

        // 2) Averiguar el método de la petición
        const method = (options.method || 'GET').toUpperCase();

        // 3) Si es GET y hay options.body, lo pasamos a query string
        if (method === 'GET' && options.body) {
            let params;
            if (options.body instanceof FormData) {
                params = new URLSearchParams(options.body);
            } else if (typeof options.body === 'object') {
                params = new URLSearchParams(Object.entries(options.body));
            }
            const qs = params.toString();
            if (qs) {
                url += url.includes('?') ? '&' : '?';
                url += qs;
            }
        }

        // 4) Ejecutar el fetch (sin body en GET)
        const resp = await fetch(url, {
            method,
            body: method === 'GET' ? null : options.body,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        // 5) Leer y validar la respuesta
        const raw = await resp.text();
        if (!raw.trim()) throw new Error('Respuesta vacía del servidor');
        const data = JSON.parse(raw);
        if (data.tipo !== 'success') throw new Error(data.mensaje || 'Error desconocido');

        return data;
    } catch (error) {
        console.error('Error en apiFetch:', error);
        throw error;
    }
}





/**
 * Muestra una alerta modal usando SweetAlert2
 * 
 * Propósito:
 * - Estandariza la presentación de mensajes al usuario
 * - Proporciona feedback visual con iconos intuitivos
 * - Asegura interacción del usuario mediante confirmación
 * - Mantiene consistencia en la interfaz
 * 
 * Analogía:
 * Es como un asistente que:
 * 1. Interrumpe educadamente al usuario
 * 2. Muestra un mensaje importante
 * 3. Espera confirmación antes de continuar
 * 
 * Ejemplo de uso:
 * // Mostrar mensaje de éxito
 * await mostrarAlerta(
 *     'success',
 *     'Operación Completada',
 *     'Los datos se guardaron correctamente'
 * );
 * 
 * // Mostrar mensaje de error
 * await mostrarAlerta(
 *     'error',
 *     'Error',
 *     'No se pudo completar la operación'
 * );
 * 
 * @param {string} icon - Tipo de icono ('success', 'error', 'warning', 'info')
 * @param {string} title - Título de la alerta
 * @param {string} text - Mensaje detallado
 * @returns {Promise} Promesa que resuelve cuando el usuario confirma
 */
export function mostrarAlerta(icon, title, text) {
    return Swal.fire({ icon, title, text, confirmButtonText: "Aceptar" });
}

/**
 * Controla el estado de habilitación de un botón
 * 
 * Propósito:
 * - Previene múltiples envíos de formulario
 * - Proporciona feedback visual durante operaciones
 * - Mejora la experiencia del usuario
 * - Evita errores por clics repetidos
 * 
 * Analogía:
 * Es como un interruptor de seguridad que:
 * 1. Bloquea el control mientras está en uso
 * 2. Previene acciones accidentales
 * 3. Se desbloquea cuando es seguro operar
 * 
 * Ejemplo de uso:
 * const botonGuardar = document.querySelector('#btnGuardar');
 * 
 * estadoBoton(botonGuardar, true);
 * 
 * try {
 *     await guardarDatos();
 * } finally {
 *     estadoBoton(botonGuardar, false);
 * }
 * 
 * @param {HTMLButtonElement} btn - Elemento botón a controlar
 * @param {boolean} disabled - true para deshabilitar, false para habilitar
 */
export function estadoBoton(btn, disabled) {
    if (btn) btn.disabled = disabled;
}

/**
 * Corrige ortografía usando la API de LanguageTool
 * @param {string} texto - Texto a corregir
 * @returns {Promise<string>} - Texto corregido
 */
async function corregirOrtografiaAPI(texto) {
    try {
        const response = await fetch('https://api.languagetool.org/v2/check', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'text': texto,
                'language': 'es',
                'enabledOnly': 'false'
            })
        });

        const data = await response.json();
        let textoCorregido = texto;

        // Aplicar correcciones desde el final para no afectar los índices
        data.matches.reverse().forEach(match => {
            const replacement = match.replacements[0]?.value || match.word;
            textoCorregido = textoCorregido.substring(0, match.offset) +
                replacement +
                textoCorregido.substring(match.offset + match.length);
        });

        return textoCorregido;
    } catch (error) {
        console.error('Error en corrección ortográfica:', error);
        return texto; // Devolver texto original si hay error
    }
}

/**
 * Formatea un texto para que cada palabra inicie con mayúscula
 * Útil para nombres, títulos, etiquetas, etc.
 */
/**
 * Formatea texto con corrección ortográfica
 */
export async function formatearTexto(texto) {
    if (!texto) return '';

    // Primero corregir ortografía
    const textoCorregido = await corregirOrtografiaAPI(texto);

    // Luego aplicar formato de mayúsculas
    return textoCorregido
        .toLowerCase()
        .split(' ')
        .map(palabra => palabra.charAt(0).toUpperCase() + palabra.slice(1))
        .join(' ');
}

/**
 * Formatea un párrafo: primera letra y después de punto
 * Útil para descripciones, contenido, etc.
 */

/**
 * Formatea párrafo con corrección ortográfica
 */
export async function formatearParrafo(texto) {
    if (!texto) return '';

    // Primero corregir ortografía
    const textoCorregido = await corregirOrtografiaAPI(texto);

    // Luego aplicar formato de oraciones
    return textoCorregido
        .toLowerCase()
        .split('. ')
        .map(oracion => {
            oracion = oracion.trim();
            return oracion.charAt(0).toUpperCase() + oracion.slice(1);
        })
        .join('. ');
}


export function initCRUD({
    entity,
    formSelector,
    tableSelector,
    relations = [],
    columns,
    validate,
    fields,
    onSaveSuccess,
    onEditClick
}) {
    // 0) Referencias al DOM
    const idField = fields[0];
    const singularKey = entity.endsWith('s')
        ? entity.slice(0, -1)
        : entity;
    const Form = document.querySelector(formSelector);
    const btnGuardar = Form.querySelector("#btnGuardar");
    const btnModificar = Form.querySelector("#btnModificar");
    const btnLimpiar = Form.querySelector("#btnLimpiar");

    // 1) resetForm: limpia formulario y muestra sólo el botón correcto
    function resetForm() {
        Form.reset();
        btnGuardar.classList.remove("d-none");
        btnModificar.classList.add("d-none");
    }

    // 2) cargarRelaciones: pobla los <select> de categorías y prioridades
    async function cargarRelaciones() {
        for (const { field, route, valueKey, textKey } of relations) {
            const select = Form.querySelector(`[name="${field}"]`);
            if (!select) continue;
            select.innerHTML = '<option value="">-- Selecciona --</option>';
            try {
                const data = await apiFetch(route);
                // busca la primera propiedad que sea array
                const key = Object.keys(data).find(k => Array.isArray(data[k]));
                (data[key] || []).forEach(item => {
                    const o = document.createElement('option');
                    o.value = item[valueKey];
                    o.textContent = item[textKey];
                    select.append(o);
                });
            } catch (e) {
                console.error(`Error cargando ${field}:`, e);
            }
        }
    }

    // 3) DataTable + función cargar() para traer y pintar la tabla
    const tabla = new DataTable(tableSelector, {
        language: lenguaje,
        dom: "Bfrtip",
        columns
    });
    let lista = [];
    async function cargar() {
        try {
            const data = await apiFetch(CONFIG.ROUTES[entity].read);
            lista = data[entity] || [];
            tabla.clear().rows.add(lista).draw();
            if (!lista.length) {
                await mostrarAlerta("info", "Información", `No hay ${entity} registrados`);
            }
        } catch (e) {
            console.error(e);
            await mostrarAlerta("error", "Error", e.message);
        }
    }

    // 4) Document ready: primero cargas relaciones, reseteas formulario y luego la tabla
    document.addEventListener("DOMContentLoaded", async () => {
        await cargarRelaciones();
        resetForm();
        await cargar();
    });

    // 5) onEditClick: recarga relaciones antes de mostrar modal de edición
    const originalOnEdit = onEditClick;
    onEditClick = async () => {
        await cargarRelaciones();
        originalOnEdit?.();
    };

    Form.addEventListener("submit", async e => {
        e.preventDefault();
        estadoBoton(btnGuardar, true);
        const formData = new FormData(Form);

        // Formatear campos según su tipo
        for (const field of fields) {
            const input = Form.querySelector(`[name=${field}]`);
            if (!input) continue;

            let textoFormateado;
            if (input.classList.contains('parrafo')) {
                textoFormateado = await formatearParrafo(input.value);
            } else if (input.type === 'text') {
                textoFormateado = await formatearTexto(input.value);
            }

            if (textoFormateado) {
                formData.set(field, textoFormateado);
            }
        }


        const errores = validate(formData);

        if (errores.length) {
            await mostrarAlerta("error", "Validación", errores.join("\n"));
            estadoBoton(btnGuardar, false);
            return;
        }

        try {
            const data = await apiFetch(CONFIG.ROUTES[entity].create, {
                method: "POST",
                body: formData
            });
            await mostrarAlerta("success", "Éxito", data.mensaje);
            resetForm();
            await cargar();
            onSaveSuccess?.();
        } catch (err) {
            console.error(err);
            await mostrarAlerta("error", "Error", err.message);
        } finally {
            estadoBoton(btnGuardar, false);
        }
    });

    btnModificar.addEventListener("click", async e => {
        e.preventDefault();
        estadoBoton(btnModificar, true);
        const formData = new FormData(Form);

        // Formatear campos según su tipo
        for (const field of fields) {
            const input = Form.querySelector(`[name=${field}]`);
            if (!input) continue;

            let textoFormateado;
            if (input.classList.contains('parrafo')) {
                textoFormateado = await formatearParrafo(input.value);
            } else if (input.type === 'text') {
                textoFormateado = await formatearTexto(input.value);
            }

            if (textoFormateado) {
                formData.set(field, textoFormateado);
            }
        }



        const errores = validate(formData);

        if (errores.length) {
            await mostrarAlerta("error", "Validación", errores.join("\n"));
            estadoBoton(btnModificar, false);
            return;
        }
        try {
            const data = await apiFetch(CONFIG.ROUTES[entity].update, {
                method: "POST",
                body: formData
            });
            await mostrarAlerta("success", "Éxito", data.mensaje);
            resetForm();
            await cargar();
            onSaveSuccess?.();
        } catch (err) {
            console.error(err);
            await mostrarAlerta("error", "Error", err.message);
        } finally {
            estadoBoton(btnModificar, false);
        }
    });

    btnLimpiar.addEventListener("click", resetForm);

    // tabla.on("click", ".btn-editar", async ev => {
    //     const btn = ev.target.closest('.btn-editar');
    //     if (!btn) return;
    //     const id = btn.dataset.id;
    //     try {
    //         const { [singularKey]: item } = await apiFetch(
    //             `${CONFIG.ROUTES[entity].find}?${idField}=${id}`
    //         );

    //         await cargarRelaciones();

    //         fields.forEach(f => {
    //             const inp = Form.querySelector(`[name=${f}]`);
    //             if (inp) inp.value = item[f] ?? "";
    //         });
    //         btnGuardar.classList.add("d-none");
    //         btnModificar.classList.remove("d-none");
    //     } catch (err) {
    //         console.error(err);
    //         await mostrarAlerta("error", "Error", err.message);
    //     }
    //     onEditClick?.();
    // });

    tabla.on("click", ".btn-editar", async ev => {
        const btn = ev.target.closest('.btn-editar');
        if (!btn) return;
        const id = btn.dataset.id;
        try {
            // ❌ ANTES (causaba el error):
            // const { [singularKey]: item } = await apiFetch(
            //     `${CONFIG.ROUTES[entity].find}?${idField}=${id}`
            // );

            // ✅ DESPUÉS (corregido):
            const response = await apiFetch(
                `${CONFIG.ROUTES[entity].find}?${idField}=${id}`
            );

            // Buscar el item en la respuesta - puede estar en diferentes propiedades
            const item = response[singularKey] || response[entity] || response.data || response;

            if (!item) {
                throw new Error('Elemento no encontrado en la respuesta');
            }

            await cargarRelaciones();

            fields.forEach(f => {
                const inp = Form.querySelector(`[name=${f}]`);
                if (inp) inp.value = item[f] ?? "";
            });
            btnGuardar.classList.add("d-none");
            btnModificar.classList.remove("d-none");
        } catch (err) {
            console.error(err);
            await mostrarAlerta("error", "Error", err.message);
        }
        onEditClick?.();
    });

    tabla.on("click", ".btn-eliminar", async ev => {
        const id = ev.currentTarget.dataset.id;
        const row = tabla.row(ev.currentTarget.closest("tr")).data();
        const nombre = row[fields[1]];
        const { isConfirmed } = await Swal.fire({
            icon: "warning",
            title: "¿Estás seguro?",
            html: `Se eliminará:<br><strong>${nombre}</strong>`,
            showCancelButton: true,
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar"
        });
        if (!isConfirmed) return;
        try {
            const fd = new FormData();
            fd.append(idField, id);
            const data = await apiFetch(CONFIG.ROUTES[entity].delete, {
                method: "POST",
                body: fd
            });
            await mostrarAlerta("success", "Éxito", data.mensaje);
            await cargar();
        } catch (err) {
            console.error(err);
            await mostrarAlerta("error", "Error", err.message);
        }
    });

    document.addEventListener("DOMContentLoaded", cargar);
    return { cargar, cargarRelaciones };
}

// función auxiliar
/**
 * Capitaliza la primera letra de una cadena de texto
 * 
 * Propósito:
 * - Estandariza el formato de nombres y etiquetas
 * - Asegura consistencia en URLs y endpoints
 * - Ayuda en la presentación de datos
 * - Formatea textos para la interfaz
 * 
 * Analogía:
 * Es como un corrector automático que:
 * 1. Toma cualquier palabra
 * 2. Asegura que empiece con mayúscula
 * 3. Mantiene el resto en minúsculas
 * 
 * Ejemplo de uso:
 * capitalize('categorias') // -> 'Categorias'
 * 
 * const url = `/api/${entity}/buscar${capitalize(entity)}`;
 * const mensaje = `${capitalize(tipo)} de producto exitoso`;
 * 
 * @param {string} s - Cadena de texto a capitalizar
 * @returns {string} Cadena con la primera letra en mayúscula
 */
function capitalize(s) {
    return s.charAt(0).toUpperCase() + s.slice(1);
}
