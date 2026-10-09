/**
 * JABODKA-SIMS shared UI helpers.
 *
 * - Toasts (success / error / info)
 * - Inline field validation (client side) + server-side error rendering
 * - Delegated input filters (letters only / numeric only / no spinners)
 * - Modal helpers (open / close / fetch-fragment)
 *
 * Attached to window.Jabodka. No build-time assumptions beyond being imported
 * from resources/js/app.js.
 */

const NAME_RE = /^[A-Za-z][A-Za-z .,'’-]*$/;
const DIGITS_RE = /^[0-9]+$/;
const PHONE_RE = /^[0-9+][0-9+\s-]*$/;
const MONEY_RE = /^[0-9]+(\.[0-9]{1,2})?$/;

/* ────────────────────────────── toasts ────────────────────────────── */

let toastSeq = 0;

function toastRoot() {
    let root = document.getElementById('toast-root');
    if (!root) {
        root = document.createElement('div');
        root.id = 'toast-root';
        root.className = 'toast-root';
        document.body.appendChild(root);
    }
    return root;
}

function toast(message, type = 'success', options = {}) {
    if (!message) return null;

    const root = toastRoot();
    const node = document.createElement('div');
    const id = ++toastSeq;
    node.className = 'toast toast-' + (['success', 'error', 'info'].includes(type) ? type : 'info');
    node.setAttribute('role', 'status');
    node.dataset.toastId = String(id);

    const icon = document.createElement('span');
    icon.className = 'toast-icon';
    icon.innerHTML =
        type === 'success'
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
            : type === 'error'
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

    const text = document.createElement('span');
    text.className = 'toast-msg';
    text.textContent = message;

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'toast-close';
    close.setAttribute('aria-label', 'Dismiss');
    close.innerHTML = '&times;';

    node.append(icon, text, close);
    root.appendChild(node);

    const dismiss = () => {
        if (!node.isConnected) return;
        node.classList.remove('toast-in');
        setTimeout(() => node.remove(), 200);
    };

    const timer = setTimeout(dismiss, options.duration ?? 5000);
    close.addEventListener('click', () => {
        clearTimeout(timer);
        dismiss();
    });

    requestAnimationFrame(() => node.classList.add('toast-in'));

    return node;
}

/* ──────────────────────── validation helpers ──────────────────────── */

function humanize(name) {
    const base = String(name || '').split('.').pop() || '';
    return base.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase());
}

function labelFor(input) {
    return input.dataset.label || input.getAttribute('placeholder') || humanize(input.name) || 'This field';
}

const validators = {
    required(value) {
        return String(value ?? '').trim() !== '' ? '' : 'is required.';
    },
    name(value) {
        const v = String(value ?? '').trim();
        if (v === '') return '';
        return NAME_RE.test(v) ? '' : 'may only contain letters (spaces, hyphens, apostrophes and periods are allowed).';
    },
    digits(value) {
        const v = String(value ?? '').trim();
        if (v === '') return '';
        return DIGITS_RE.test(v) ? '' : 'may only contain numbers.';
    },
    phone(value) {
        const v = String(value ?? '').trim();
        if (v === '') return '';
        return PHONE_RE.test(v) ? '' : 'may only contain numbers, dashes and spaces.';
    },
    money(value) {
        const v = String(value ?? '').trim();
        if (v === '') return '';
        return MONEY_RE.test(v) ? '' : 'must be a valid amount (numbers only, up to 2 decimals).';
    },
    integer(value) {
        const v = String(value ?? '').trim();
        if (v === '') return '';
        return /^[0-9]+$/.test(v) ? '' : 'must be a whole number.';
    },
    positive(value) {
        const v = String(value ?? '').trim();
        if (v === '') return '';
        return parseFloat(v) > 0 ? '' : 'must be greater than zero.';
    },
    min(value, label, min) {
        const v = String(value ?? '').trim();
        if (v === '') return '';
        return parseFloat(v) >= parseFloat(min) ? '' : `must be at least ${min}.`;
    },
    max(value, label, max) {
        const v = String(value ?? '');
        if (v === '') return '';
        return v.length <= parseInt(max, 10) ? '' : `may not be longer than ${max} characters.`;
    },
    date(value) {
        const v = String(value ?? '').trim();
        if (v === '') return '';
        return !Number.isNaN(Date.parse(v)) ? '' : 'must be a valid date.';
    },
};

function parseValidators(spec) {
    return String(spec || '')
        .split('|')
        .map((part) => part.trim())
        .filter(Boolean)
        .map((part) => {
            const [name, ...params] = part.split(':');
            return { name, params };
        });
}

function validateInput(input) {
    const spec = input.dataset.validate;
    if (!spec) return '';

    const label = labelFor(input);
    const value = input.type === 'checkbox' ? (input.checked ? '1' : '') : input.value;

    for (const rule of parseValidators(spec)) {
        const fn = validators[rule.name];
        if (!fn) continue;
        const message = fn(value, label, ...(rule.params || []));
        if (message) return `${label} ${message}`;
    }

    return '';
}

function messageFor(input) {
    return validateInput(input);
}

function errorNodeFor(input) {
    const form = input.form || document;
    const key = input.dataset.errorFor || input.name;
    if (key) {
        const explicit = form.querySelector(`[data-error-for="${cssEscape(key)}"]`);
        if (explicit) return explicit;
    }

    const host = input.closest('[data-field]') || input.closest('label') || input.parentElement;
    if (!host) return null;

    let node = host.querySelector(':scope > .js-field-error');
    if (!node) {
        node = document.createElement('p');
        node.className = 'js-field-error';
        host.appendChild(node);
    }
    return node;
}

function cssEscape(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') return window.CSS.escape(value);
    return String(value).replace(/["\\]/g, '\\$&');
}

function setFieldError(input, message) {
    const node = errorNodeFor(input);

    if (message) {
        input.classList.add('js-invalid');
        input.style.borderColor = '#ef4444';
        input.setAttribute('aria-invalid', 'true');
        if (node) {
            node.textContent = message;
            node.hidden = false;
        }
    } else {
        input.classList.remove('js-invalid');
        input.style.borderColor = '';
        input.removeAttribute('aria-invalid');
        if (node) {
            node.textContent = '';
            node.hidden = true;
        }
    }
}

function guard(scope) {
    const root = scope instanceof HTMLFormElement ? scope : scope || document;

    const fields = Array.from(root.querySelectorAll('[data-validate]')).filter(
        (input) => !input.disabled && input.type !== 'hidden'
    );

    let firstInvalid = null;

    fields.forEach((input) => {
        const message = validateInput(input);
        setFieldError(input, message);
        if (message) {
            input.dataset.touched = '1';
            if (!firstInvalid) firstInvalid = input;
        }
    });

    if (firstInvalid) {
        toast('Please fix the highlighted fields.', 'error');
        firstInvalid.scrollIntoView({ block: 'center', behavior: 'smooth' });
        setTimeout(() => firstInvalid.focus({ preventScroll: true }), 150);
        return false;
    }

    return true;
}

function renderServerErrors(errors) {
    const keys = Object.keys(errors || {});
    if (!keys.length) return 0;

    let shown = 0;

    keys.forEach((key) => {
        const messages = errors[key];
        const message = Array.isArray(messages) ? messages[0] : messages;
        const input =
            document.querySelector(`[name="${cssEscape(key)}"]`) ||
            document.querySelector(`[data-error-for="${cssEscape(key)}"]`);

        if (input) {
            if (input.matches('[data-validate], input, select, textarea')) {
                setFieldError(input, message);
            } else if (input.classList.contains('js-field-error')) {
                input.textContent = message;
                input.hidden = false;
            }
            shown++;
        }
    });

    if (shown) {
        toast('Some fields need your attention.', 'error', { duration: 7000 });
    } else if (keys.length) {
        const first = errors[keys[0]];
        toast(Array.isArray(first) ? first[0] : first, 'error', { duration: 7000 });
    }

    return shown;
}

/* ──────────────────────── input filters ───────────────────────────── */

const FILTERS = {
    digits: (v) => v.replace(/[^0-9]/g, ''),
    integer: (v) => v.replace(/[^0-9]/g, ''),
    money: (v) => {
        const cleaned = v.replace(/[^0-9.]/g, '');
        const parts = cleaned.split('.');
        if (parts.length > 2) return parts[0] + '.' + parts.slice(1).join('');
        if (parts[1] !== undefined) return parts[0] + '.' + parts[1].slice(0, 2);
        return cleaned;
    },
    phone: (v) => v.replace(/[^0-9+\s-]/g, ''),
    alnum: (v) => v.replace(/[^0-9A-Za-z-]/g, ''),
    text: (v) => v,
};

function prepareInput(el) {
    if (!(el instanceof HTMLInputElement)) return;

    if (el.dataset.numeric && el.type === 'number') {
        el.type = 'text';
        el.setAttribute('inputmode', el.dataset.numeric === 'money' ? 'decimal' : 'numeric');
        el.classList.add('no-spinner');
    }

    if (el.dataset.letters !== undefined && el.type === 'text' && !el.dataset.originalInputmode) {
        el.setAttribute('inputmode', 'text');
    }
}

function applyFilter(el) {
    if (!(el instanceof HTMLInputElement) || el.readOnly) return;

    const mode = el.dataset.numeric;
    const letters = el.dataset.letters;

    if (mode && FILTERS[mode]) {
        const filtered = FILTERS[mode](el.value);
        if (filtered !== el.value) {
            const atEnd = el.selectionStart === el.value.length;
            el.value = filtered;
            if (!atEnd) {
                try {
                    el.setSelectionRange(filtered.length, filtered.length);
                } catch (e) { /* type=number cannot set selection */ }
            }
        }
    }

    if (letters !== undefined) {
        const filtered = el.value.replace(/[^A-Za-z .,'’-]/g, '');
        if (filtered !== el.value) el.value = filtered;
    }
}

/* ──────────────────────── modals ──────────────────────────────────── */

function modalOpen(name) {
    window.dispatchEvent(new CustomEvent('modal-open', { detail: name }));
}

function modalClose(name) {
    window.dispatchEvent(new CustomEvent('modal-close', { detail: name }));
}

function modalRoot(name) {
    return document.querySelector(`[data-modal="${cssEscape(name)}"]`);
}

function modalContent(name) {
    return modalRoot(name)?.querySelector('[data-modal-content]') || null;
}

function modalBody(name, html) {
    const target = modalContent(name);
    if (target) target.innerHTML = html;
    return target;
}

async function modalUrl(name, url, options = {}) {
    modalOpen(name);

    const target = modalContent(name);
    if (target) {
        target.innerHTML = `<div class="modal-loading"><span class="modal-spinner"></span>${options.loadingLabel || 'Loading…'}</div>`;
    }

    try {
        const res = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
                ...(options.headers || {}),
            },
        });

        if (!res.ok) throw new Error(`Request failed (${res.status})`);

        const html = await res.text();
        if (target) target.innerHTML = html;
        return true;
    } catch (error) {
        if (target) {
            target.innerHTML =
                '<p class="text-sm text-red-600 text-center py-6">Could not load this document. Please try again.</p>';
        }
        toast('Could not load this document.', 'error');
        return false;
    }
}

function printModal(name) {
    const root = modalRoot(name);
    if (!root) {
        window.print();
        return;
    }

    const area = root.querySelector('[data-print-area]') || root.querySelector('[data-modal-content]');
    if (area) area.classList.add('print-receipt');

    document.body.classList.add('modal-printing');
    window.print();

    const cleanup = () => {
        document.body.classList.remove('modal-printing');
        area?.classList.remove('print-receipt');
        window.removeEventListener('afterprint', cleanup);
    };
    window.addEventListener('afterprint', cleanup);
    // Safari/Firefox do not always fire afterprint promptly.
    setTimeout(cleanup, 2000);
}

/* ──────────────────────── boot ────────────────────────────────────── */

function boot() {
    const flash = window.__JABODKA_FLASH__ || {};

    if (flash.status) toast(flash.status, 'success');
    if (flash.error) toast(flash.error, 'error', { duration: 7000 });
    if (flash.errors) renderServerErrors(flash.errors);

    // Delegated input filtering + field preparation.
    document.addEventListener('focusin', (event) => prepareInput(event.target));
    document.addEventListener(
        'input',
        (event) => {
            applyFilter(event.target);
            const el = event.target;
            if (el instanceof HTMLElement && el.dataset && el.dataset.touched && el.dataset.validate) {
                setFieldError(el, validateInput(el));
            }
        },
        true
    );

    // Re-validate on blur so mistakes are corrected as soon as the user leaves a field.
    document.addEventListener(
        'focusout',
        (event) => {
            const el = event.target;
            if (!(el instanceof HTMLElement) || !el.dataset?.validate) return;
            if (!el.value && !el.dataset.touched) return;
            const message = validateInput(el);
            el.dataset.touched = '1';
            setFieldError(el, message);
        },
        true
    );

    // Client-side validation gate for plain HTML form submissions.
    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.hasAttribute('data-no-guard')) return;
            if (!guard(form)) event.preventDefault();
        },
        true
    );

    // Escape closes the topmost open modal.
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const openModals = Array.from(document.querySelectorAll('[data-modal]')).filter(
            (el) => el.getClientRects().length > 0
        );
        if (openModals.length) modalClose(openModals[openModals.length - 1].dataset.modal);
    });

    document.documentElement.classList.add('jabodka-ready');
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

/* ──────────── shared cart helpers (usable inside modals too) ──────────── */

function invoiceCart(catalog, preselect) {
    return {
        catalog: catalog || [],
        query: '',
        matches: [],
        lines: [],
        draft: { variant_id: '', label: '', qty: 1, cost: '' },
        init() {
            if (preselect) {
                const found = this.catalog.find((v) => String(v.id) === String(preselect));
                if (found) this.pick(found);
            }
        },
        filter() {
            const q = this.query.toLowerCase().trim();
            this.matches = q.length < 1 ? [] : this.catalog.filter((v) => v.label.toLowerCase().includes(q)).slice(0, 8);
        },
        pick(v) {
            this.draft.variant_id = v.id;
            this.draft.label = v.label;
            this.query = v.label;
            this.matches = [];
        },
        addLine() {
            if (!this.draft.variant_id) return;
            this.lines.push({
                variant_id: this.draft.variant_id,
                label: this.draft.label,
                qty: this.draft.qty || 1,
                cost: this.draft.cost,
            });
            this.draft = { variant_id: '', label: '', qty: 1, cost: '' };
            this.query = '';
        },
        lineTotal(line) {
            const cost = parseFloat(line.cost);
            if (isNaN(cost)) return '—';
            return '₱' + (cost * (parseInt(line.qty) || 0)).toFixed(2);
        },
        grandTotal() {
            return this.lines.reduce((s, l) => {
                const cost = parseFloat(l.cost);
                return s + (isNaN(cost) ? 0 : cost * (parseInt(l.qty) || 0));
            }, 0).toFixed(2);
        },
    };
}

function consignmentCart(catalog) {
    return {
        catalog: catalog || [],
        query: '',
        matches: [],
        lines: [],
        draft: { variant_id: '', label: '', qty: 1, base: '', markup: '' },
        filter() {
            const q = this.query.toLowerCase().trim();
            this.matches = q.length < 1 ? [] : this.catalog.filter((v) => v.label.toLowerCase().includes(q)).slice(0, 8);
        },
        pick(v) {
            this.draft.variant_id = v.id;
            this.draft.label = v.label;
            this.query = v.label;
            this.matches = [];
        },
        addLine() {
            if (!this.draft.variant_id) return;
            this.lines.push({ ...this.draft, qty: this.draft.qty || 1, base: this.draft.base || 0, markup: this.draft.markup || 0 });
            this.draft = { variant_id: '', label: '', qty: 1, base: '', markup: '' };
            this.query = '';
        },
    };
}

window.invoiceCart = invoiceCart;
window.consignmentCart = consignmentCart;

window.Jabodka = {
    toast,
    guard,
    validators,
    validateInput,
    setFieldError,
    renderServerErrors,
    modalOpen,
    modalClose,
    modalUrl,
    modalBody,
    printModal,
};
