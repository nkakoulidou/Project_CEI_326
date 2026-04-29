<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function getProjectBasePath(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $needle = '/project-root/';
    $position = strpos($scriptName, $needle);

    if ($position !== false) {
        return substr($scriptName, 0, $position) . '/project-root';
    }

    return str_starts_with($scriptName, '/project-root') ? '/project-root' : '';
}

function supportedLocales(): array
{
    return ['en', 'el'];
}

function currentLocale(): string
{
    $locale = $_SESSION['locale'] ?? 'el';
    return in_array($locale, supportedLocales(), true) ? $locale : 'el';
}

function setAppLocale(string $locale): void
{
    if (in_array($locale, supportedLocales(), true)) {
        $_SESSION['locale'] = $locale;
    }
}

function translations(): array
{
    static $cache = [];
    $locale = currentLocale();

    if (!isset($cache[$locale])) {
        $file = __DIR__ . '/../lang/' . $locale . '.php';
        $cache[$locale] = file_exists($file) ? (array) require $file : [];
    }

    return $cache[$locale];
}

function t(string $key, array $replace = []): string
{
    $text = translations()[$key] ?? $key;

    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }

    return $text;
}

function renderNavbar(): void
{
    $isLoggedIn = isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
    $role = $_SESSION['role'] ?? '';
    $username = (string) ($_SESSION['username'] ?? '');
    $basePath = getProjectBasePath();
    $locale = currentLocale();

    $links = [
        ['key' => 'nav.home', 'label' => t('nav.home'), 'href' => $basePath . '/index.php'],
        ['key' => 'nav.search', 'label' => t('nav.search'), 'href' => $basePath . '/search.php'],
    ];

    if (!$isLoggedIn) {
        $links[] = ['key' => 'nav.login', 'label' => t('nav.login'), 'href' => $basePath . '/index.php?form=login#auth-panel'];
        $links[] = ['key' => 'nav.register', 'label' => t('nav.register'), 'href' => $basePath . '/index.php?form=register#auth-panel'];
    } elseif ($role === 'admin') {
        $links[] = ['key' => 'nav.dashboard', 'label' => t('nav.dashboard'), 'href' => $basePath . '/admin/dashboard.php'];
        $links[] = ['key' => 'nav.logout', 'label' => t('nav.logout'), 'href' => $basePath . '/auth/logout.php'];
    } else {
        $links[] = ['key' => 'nav.my_profile', 'label' => t('nav.my_profile'), 'href' => $basePath . '/modules/profile.php'];
        $links[] = ['key' => 'nav.track_my_applications', 'label' => t('nav.track_my_applications'), 'href' => $basePath . '/modules/list.php'];
        $links[] = ['key' => 'nav.track_others', 'label' => t('nav.track_others'), 'href' => $basePath . '/modules/track-others.php'];
        $links[] = ['key' => 'nav.logout', 'label' => t('nav.logout'), 'href' => $basePath . '/auth/logout.php'];
    }
    ?>
    <nav class="navbar">
        <div class="navbar__inner">
            <a class="navbar__brand" href="<?php echo htmlspecialchars($basePath . '/index.php', ENT_QUOTES, 'UTF-8'); ?>">
                <img class="navbar__logo" src="<?php echo htmlspecialchars($basePath . '/assets/images/owlogo.png', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(t('site.logo_alt'), ENT_QUOTES, 'UTF-8'); ?>">
                <span data-i18n-key="site.brand"><?php echo htmlspecialchars(t('site.brand'), ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <div class="navbar__menu">
                <?php foreach ($links as $link): ?>
                    <a class="navbar__link" data-i18n-key="<?php echo htmlspecialchars($link['key'], ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="navbar__actions">
                <?php if ($isLoggedIn): ?>
                    <p class="navbar__welcome" data-i18n-key="nav.welcome" data-i18n-username="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(t('nav.welcome', ['username' => $username]), ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <div class="lang-switch" role="group" data-i18n-attr="aria-label" data-i18n-key="lang.label" aria-label="<?php echo htmlspecialchars(t('lang.label'), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="button" class="lang-switch__btn <?php echo $locale === 'en' ? 'is-active' : ''; ?>" data-lang="en" data-i18n-key="lang.en"><?php echo htmlspecialchars(t('lang.en'), ENT_QUOTES, 'UTF-8'); ?></button>
                    <button type="button" class="lang-switch__btn <?php echo $locale === 'el' ? 'is-active' : ''; ?>" data-lang="el" data-i18n-key="lang.el"><?php echo htmlspecialchars(t('lang.el'), ENT_QUOTES, 'UTF-8'); ?></button>
                </div>
            </div>
        </div>
    </nav>
    <script>
        (function () {
            var switchers = document.querySelectorAll('.lang-switch__btn');
            if (!switchers.length) {
                return;
            }

            switchers.forEach(function (button) {
                button.addEventListener('click', function () {
                    var locale = button.getAttribute('data-lang');
                    fetch('<?php echo htmlspecialchars($basePath . '/set-language.php', ENT_QUOTES, 'UTF-8'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ locale: locale })
                    }).then(function (response) {
                        return response.json();
                    }).then(function (payload) {
                        if (!payload || payload.success !== true) {
                            return;
                        }

                        var dict = payload.translations || {};
                        document.documentElement.setAttribute('lang', payload.locale || locale);
                        switchers.forEach(function (btn) {
                            btn.classList.toggle('is-active', btn.getAttribute('data-lang') === (payload.locale || locale));
                        });

                        document.querySelectorAll('[data-i18n-key]').forEach(function (node) {
                            var key = node.getAttribute('data-i18n-key');
                            if (!key || !Object.prototype.hasOwnProperty.call(dict, key)) {
                                return;
                            }

                            var value = dict[key];
                            if (key === 'nav.welcome') {
                                var username = node.getAttribute('data-i18n-username') || '';
                                value = String(value).replace(':username', username);
                            }

                            if (node.getAttribute('data-i18n-attr') === 'aria-label') {
                                node.setAttribute('aria-label', value);
                                return;
                            }

                            node.textContent = value;
                        });

                        // Soft-refresh page content so all server-rendered translations update
                        // without requiring a manual browser refresh.
                        fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function (response) { return response.text(); })
                            .then(function (html) {
                                var parser = new DOMParser();
                                var doc = parser.parseFromString(html, 'text/html');
                                var newMain = doc.querySelector('main');
                                var currentMain = document.querySelector('main');

                                if (doc.title) {
                                    document.title = doc.title;
                                }

                                if (newMain && currentMain) {
                                    currentMain.innerHTML = newMain.innerHTML;
                                }
                            })
                            .catch(function () {
                                return null;
                            });
                    }).catch(function () {
                        return null;
                    });
                });
            });
        })();
    </script>
    <?php
}
