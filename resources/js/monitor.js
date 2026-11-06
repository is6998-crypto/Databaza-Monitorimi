/**
 * CourtWatch monitor app (PWA).
 *
 * - Works offline: cases and answer options are cached in IndexedDB; drafts are stored in
 *   IndexedDB on every change (autosave).
 * - "Send for review" puts the draft in an outbox; the outbox is synced to the server when the
 *   device is online (on load, on the `online` event and every minute).
 * - Syncing is idempotent: every draft has a client UUID, so a retried request never creates a
 *   second hearing.
 */
import Alpine from 'alpinejs';

const DB_NAME = 'courtwatch-monitor';
const DB_VERSION = 1;

function openDb() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains('drafts')) {
                db.createObjectStore('drafts', { keyPath: 'client_uuid' });
            }
            if (!db.objectStoreNames.contains('cache')) {
                db.createObjectStore('cache');
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function withStore(store, mode, operation) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(store, mode);
        const request = operation(transaction.objectStore(store));
        transaction.oncomplete = () => resolve(request ? request.result : undefined);
        transaction.onerror = () => reject(transaction.error);
        transaction.onabort = () => reject(transaction.error);
    });
}

export const idb = {
    all: (store) => withStore(store, 'readonly', (s) => s.getAll()),
    get: (store, key) => withStore(store, 'readonly', (s) => s.get(key)),
    put: (store, value, key) => withStore(store, 'readwrite', (s) => (key === undefined ? s.put(value) : s.put(value, key))),
    delete: (store, key) => withStore(store, 'readwrite', (s) => s.delete(key)),
    clear: (store) => withStore(store, 'readwrite', (s) => s.clear()),
};

const FIELDS = [
    'client_uuid', 'case_id', 'hearing_date', 'hearing_type', 'venue', 'is_public', 'started_late', 'delay_minutes',
    'delay_cause', 'was_postponed', 'postponement_reasons', 'recording', 'interpretation_needed', 'interpretation_provided',
    'phone_used', 'phone_users', 'security_adequate', 'security_comment', 'participants', 'present_party_ids', 'notes',
];

function uuid() {
    if (window.crypto && window.crypto.randomUUID) {
        return window.crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
}

function clone(value) {
    return JSON.parse(JSON.stringify(value));
}

window.monitorApp = function monitorApp(config) {
    return {
        t: config.t,
        online: navigator.onLine,
        screen: 'home',
        data: null,
        drafts: [],
        draft: null,
        caseQuery: '',
        syncing: false,
        authLost: false,
        lastSync: null,
        saveTimer: null,
        notice: null,

        async init() {
            window.addEventListener('online', () => {
                this.online = true;
                this.refresh().then(() => this.syncAll());
            });
            window.addEventListener('offline', () => {
                this.online = false;
            });

            try {
                this.data = (await idb.get('cache', 'bootstrap')) || null;
                await this.loadDrafts();
            } catch (e) {
                this.notice = this.t.storage_unavailable;
            }

            if (navigator.onLine) {
                await this.refresh();
                await this.syncAll();
            }

            setInterval(() => {
                if (navigator.onLine) {
                    this.syncAll();
                }
            }, 60000);

            this.$watch('draft', () => this.scheduleSave());

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register(config.serviceWorkerUrl, { scope: '/m' })
                    .then(() => navigator.serviceWorker.ready)
                    .then((registration) => {
                        if (navigator.onLine && registration.active) {
                            registration.active.postMessage('refresh-shell');
                        }
                    })
                    .catch(() => {});
            }
        },

        // ---------------------------------------------------------------- data

        async refresh() {
            try {
                const response = await fetch(config.bootstrapUrl, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (response.status === 401 || response.status === 419 || response.redirected) {
                    this.authLost = true;
                    return;
                }

                if (!response.ok) {
                    return;
                }

                this.data = await response.json();
                this.authLost = false;
                await idb.put('cache', clone(this.data), 'bootstrap');
            } catch (e) {
                // offline: keep the cached copy
            }
        },

        async loadDrafts() {
            const drafts = await idb.all('drafts');
            this.drafts = drafts.sort((a, b) => (b._updated_at || 0) - (a._updated_at || 0));
        },

        get cases() {
            const cases = (this.data && this.data.cases) || [];
            const query = this.caseQuery.trim().toLowerCase();
            return query === '' ? cases.slice(0, 50) : cases.filter((c) => c.label.toLowerCase().includes(query)).slice(0, 50);
        },

        get currentCase() {
            if (!this.draft || !this.data) {
                return null;
            }
            return this.data.cases.find((c) => String(c.id) === String(this.draft.case_id)) || null;
        },

        caseLabel(id) {
            const found = this.data && this.data.cases.find((c) => String(c.id) === String(id));
            return found ? found.label : this.t.unknown_case;
        },

        option(group, value) {
            return (this.data && this.data.options[group] && this.data.options[group][value]) || value;
        },

        pendingCount() {
            return this.drafts.filter((d) => d._status === 'pending').length;
        },

        // ---------------------------------------------------------------- drafts

        newDraft(caseId = '') {
            this.draft = {
                client_uuid: uuid(),
                case_id: caseId,
                hearing_date: (this.data && this.data.today) || new Date().toISOString().slice(0, 10),
                hearing_type: '',
                venue: 'courtroom',
                is_public: true,
                started_late: false,
                delay_minutes: null,
                delay_cause: '',
                was_postponed: false,
                postponement_reasons: [],
                recording: '',
                interpretation_needed: false,
                interpretation_provided: null,
                phone_used: false,
                phone_users: [],
                security_adequate: true,
                security_comment: '',
                participants: [],
                present_party_ids: [],
                notes: '',
                _status: 'draft',
                _errors: {},
                _submit: false,
                _updated_at: Date.now(),
            };
            this.caseQuery = '';
            this.screen = 'form';
            if (caseId) {
                this.selectCase(caseId);
            }
            window.scrollTo(0, 0);
        },

        editDraft(draft) {
            this.draft = clone(draft);
            if (this.draft._status !== 'synced') {
                this.draft._status = 'draft';
            }
            this.screen = 'form';
            window.scrollTo(0, 0);
        },

        selectCase(id) {
            this.draft.case_id = id;
            const current = this.currentCase;
            this.draft.present_party_ids = current ? current.parties.filter((p) => p.present_by_default).map((p) => p.id) : [];
        },

        toggle(field, value) {
            const list = this.draft[field] || [];
            const index = list.indexOf(value);
            if (index === -1) {
                list.push(value);
            } else {
                list.splice(index, 1);
            }
            this.draft[field] = list;
        },

        has(field, value) {
            return (this.draft[field] || []).includes(value);
        },

        scheduleSave() {
            if (!this.draft || this.draft._status === 'synced') {
                return;
            }
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.saveDraft(), 400);
        },

        async saveDraft() {
            if (!this.draft) {
                return;
            }
            this.draft._updated_at = Date.now();
            await idb.put('drafts', clone(this.draft));
            await this.loadDrafts();
        },

        validateLocally() {
            const errors = {};
            if (!this.draft.case_id) {
                errors.case_id = [this.t.case_required];
            }
            if (!this.draft.hearing_date) {
                errors.hearing_date = [this.t.date_required];
            } else if (this.data && this.draft.hearing_date > this.data.today) {
                errors.hearing_date = [this.t.date_future];
            }
            if (this.draft.was_postponed && this.draft.postponement_reasons.length === 0) {
                errors.postponement_reasons = [this.t.reasons_required];
            }
            this.draft._errors = errors;
            return Object.keys(errors).length === 0;
        },

        async keepDraft() {
            await this.saveDraft();
            this.closeForm();
        },

        async sendForReview() {
            if (!this.validateLocally()) {
                window.scrollTo(0, 0);
                return;
            }
            this.draft._status = 'pending';
            this.draft._submit = true;
            await this.saveDraft();
            this.closeForm();
            await this.syncAll();
        },

        closeForm() {
            clearTimeout(this.saveTimer);
            this.draft = null;
            this.screen = 'home';
            window.scrollTo(0, 0);
        },

        async removeDraft(draft) {
            if (!window.confirm(this.t.confirm_delete)) {
                return;
            }
            await idb.delete('drafts', draft.client_uuid);
            await this.loadDrafts();
        },

        async clearSynced() {
            for (const draft of this.drafts.filter((d) => d._status === 'synced')) {
                await idb.delete('drafts', draft.client_uuid);
            }
            await this.loadDrafts();
        },

        // ---------------------------------------------------------------- sync

        csrf() {
            return (this.data && this.data.csrf) || document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        },

        async syncAll() {
            if (this.syncing || !navigator.onLine) {
                return;
            }
            this.syncing = true;
            try {
                const drafts = await idb.all('drafts');
                for (const draft of drafts.filter((d) => d._status === 'pending')) {
                    await this.syncOne(draft);
                }
                this.lastSync = new Date();
            } finally {
                this.syncing = false;
                await this.loadDrafts();
            }
        },

        async syncOne(draft) {
            const payload = {};
            FIELDS.forEach((field) => {
                payload[field] = draft[field] === '' ? null : draft[field];
            });
            payload.submit = Boolean(draft._submit);

            let response;
            try {
                response = await fetch(config.storeUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: JSON.stringify(payload),
                });
            } catch (e) {
                return; // still offline — keep it in the outbox
            }

            if (response.status === 200 || response.status === 201) {
                draft._server = await response.json();
                draft._status = 'synced';
                draft._errors = {};
            } else if (response.status === 422) {
                const body = await response.json();
                draft._status = 'error';
                draft._errors = body.errors || {};
            } else if (response.status === 401 || response.status === 419) {
                this.authLost = true;
                return;
            } else {
                return;
            }

            draft._updated_at = Date.now();
            await idb.put('drafts', clone(draft));
        },

        async signOut() {
            if (this.pendingCount() > 0 && !window.confirm(this.t.confirm_signout_pending)) {
                return;
            }
            await idb.delete('cache', 'bootstrap');
            if (window.caches) {
                const keys = await caches.keys();
                await Promise.all(keys.filter((k) => k.startsWith('courtwatch-monitor')).map((k) => caches.delete(k)));
            }
            const form = document.getElementById('monitor-logout');
            form.submit();
        },

        statusLabel(draft) {
            return this.t.status[draft._status] || draft._status;
        },

        formatTime(timestamp) {
            if (!timestamp) {
                return '';
            }
            const date = new Date(timestamp);
            return date.toLocaleDateString('de-DE') + ' ' + date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
        },
    };
};

window.Alpine = Alpine;
Alpine.start();
