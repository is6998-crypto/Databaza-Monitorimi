{{--
    Idle timeout (proposal B6.2): after SESSION_LIFETIME minutes without activity the user is
    logged out, but a warning modal with an "extend" button appears first. Never a hard lock:
    activity in any tab keeps every tab alive, and forms autosave before the logout.
--}}
@auth
@php
    $idleConfig = [
        'lifetime' => (int) config('session.lifetime') * 60,
        'warning' => (int) config('courtwatch.idle_warning_seconds'),
        'pingUrl' => route('session.ping'),
        'logoutUrl' => $logoutUrl ?? url('/admin/logout'),
        'loginUrl' => $loginUrl ?? url('/admin/login'),
        'csrf' => csrf_token(),
    ];
@endphp
<script>
    window.courtwatchIdleTimeout = window.courtwatchIdleTimeout || function (config) {
        const storageKey = 'courtwatch:last-activity';
        const read = () => { try { return parseInt(localStorage.getItem(storageKey) || '0', 10); } catch (e) { return 0; } };
        const write = (value) => { try { localStorage.setItem(storageKey, String(value)); } catch (e) { /* private mode */ } };

        return {
            warning: false,
            countdown: '',
            lastActivity: Date.now(),
            lastPing: Date.now(),
            timer: null,

            init() {
                write(this.lastActivity);
                window.addEventListener('storage', (event) => {
                    if (event.key === storageKey) {
                        this.lastActivity = Math.max(this.lastActivity, parseInt(event.newValue || '0', 10));
                        if (this.warning && this.remaining() > config.warning) { this.warning = false; }
                    }
                });
                // Livewire requests also refresh the server session.
                document.addEventListener('livewire:request', () => this.markActive(false));
                this.timer = setInterval(() => this.tick(), 1000);
            },

            remaining() {
                const last = Math.max(this.lastActivity, read());
                return config.lifetime - Math.floor((Date.now() - last) / 1000);
            },

            activity() {
                // While the warning is open only the explicit "extend" button counts.
                if (this.warning) { return; }
                this.markActive(true);
            },

            markActive(pingIfDue) {
                this.lastActivity = Date.now();
                write(this.lastActivity);
                if (pingIfDue && Date.now() - this.lastPing > 5 * 60 * 1000) { this.ping(); }
            },

            tick() {
                // Offline (e.g. the monitor app in a courtroom without signal): drafts stay on the
                // device, so there is nothing to protect by forcing a logout we cannot perform.
                if (!navigator.onLine) { this.warning = false; return; }
                const remaining = this.remaining();
                if (remaining <= 0) { this.logout(true); return; }
                if (remaining <= config.warning) {
                    if (!this.warning) { window.dispatchEvent(new CustomEvent('courtwatch:idle-warning')); }
                    this.warning = true;
                    const m = Math.floor(remaining / 60), s = remaining % 60;
                    this.countdown = m + ':' + String(s).padStart(2, '0');
                } else {
                    this.warning = false;
                }
            },

            async ping() {
                this.lastPing = Date.now();
                try {
                    const response = await fetch(config.pingUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'X-CSRF-TOKEN': config.csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (response.status === 401 || response.status === 419) { window.location.href = config.loginUrl; }
                } catch (e) { /* offline: keep local timer */ }
            },

            async extend() {
                this.warning = false;
                this.markActive(false);
                await this.ping();
            },

            async logout(expired = false) {
                clearInterval(this.timer);
                // Give autosaving forms a chance to store their state first.
                window.dispatchEvent(new CustomEvent('courtwatch:before-idle-logout'));
                await new Promise((resolve) => setTimeout(resolve, 400));
                try {
                    await fetch(config.logoutUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'X-CSRF-TOKEN': config.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    });
                } catch (e) { /* ignore */ }
                window.location.href = config.loginUrl + (expired ? '?timeout=1' : '');
            },
        };
    };
</script>
<div
    x-data="courtwatchIdleTimeout(@js($idleConfig))"
    x-on:mousemove.window.throttle.2000ms="activity()"
    x-on:keydown.window.throttle.2000ms="activity()"
    x-on:click.window="activity()"
    x-on:scroll.window.throttle.5000ms="activity()"
    x-on:touchstart.window.throttle.2000ms="activity()"
    x-cloak
    data-idle-timeout
>
    <div
        x-show="warning"
        x-transition.opacity
        class="cw-idle-backdrop"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="cw-idle-title"
        aria-describedby="cw-idle-text"
    >
        <div class="cw-idle-dialog">
            <h2 id="cw-idle-title" class="cw-idle-title">{{ __('app.idle.title') }}</h2>
            <p id="cw-idle-text" class="cw-idle-text">
                {{ __('app.idle.text') }}
                <strong x-text="countdown"></strong>
            </p>
            <p class="cw-idle-hint">{{ __('app.idle.hint') }}</p>
            <div class="cw-idle-actions">
                <button type="button" class="cw-idle-btn cw-idle-btn-secondary" x-on:click="logout()">{{ __('app.idle.logout') }}</button>
                <button type="button" class="cw-idle-btn cw-idle-btn-primary" x-on:click="extend()" data-idle-extend>{{ __('app.idle.extend') }}</button>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    .cw-idle-backdrop { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(12, 42, 66, .6); }
    .cw-idle-dialog { width: 100%; max-width: 26rem; border-radius: 1rem; background: #fff; color: #0c2a42; padding: 1.5rem; box-shadow: 0 20px 45px rgba(12,42,66,.3); }
    .dark .cw-idle-dialog { background: #121f30; color: #eef3f8; }
    .cw-idle-title { font-size: 1.125rem; font-weight: 800; margin: 0 0 .5rem; }
    .cw-idle-text { margin: 0 0 .5rem; }
    .cw-idle-hint { margin: 0 0 1.25rem; font-size: .875rem; opacity: .75; }
    .cw-idle-actions { display: flex; gap: .75rem; justify-content: flex-end; flex-wrap: wrap; }
    .cw-idle-btn { border-radius: .75rem; padding: .6rem 1rem; font-weight: 700; font-size: .875rem; cursor: pointer; border: 1px solid transparent; }
    .cw-idle-btn-primary { background: #f8c33a; color: #0c2a42; }
    .cw-idle-btn-secondary { background: transparent; border-color: #cbd5e1; color: inherit; }
</style>


@endauth
