/*!
* Tabler v1.0.0-beta20 (https://tabler.io) - Theme switcher
* Copyright 2018-2023 The Tabler Authors
* Copyright 2018-2023 codecalm.net Paweł Kuna
* Licensed under MIT (https://github.com/tabler/tabler/blob/master/LICENSE)
*/
(function (factory) {
	typeof define === 'function' && define.amd ? define(factory) :
	factory();
})((function () { 'use strict';

	var themeStorageKey = "tablerTheme";
	var defaultTheme = "light";

	function getQueryTheme() {
		try {
			var searchParams = new URLSearchParams(window.location.search);
			var theme = searchParams.get('theme');
			if (theme === 'dark' || theme === 'light') {
				return theme;
			}
		} catch (e) {}
		return null;
	}

	function cleanThemeQueryParam() {
		try {
			if (window.history && window.history.replaceState && window.location.search) {
				var url = new URL(window.location.href);
				if (url.searchParams.has('theme')) {
					url.searchParams.delete('theme');
					var newUrl = url.pathname + (url.search ? url.search : '') + url.hash;
					window.history.replaceState(window.history.state, document.title, newUrl);
				}
			}
		} catch (e) {}
	}

	function getInitialTheme() {
		var queryTheme = getQueryTheme();
		if (queryTheme) {
			try {
				localStorage.setItem(themeStorageKey, queryTheme);
			} catch (e) {}
			cleanThemeQueryParam();
			return queryTheme;
		}
		try {
			var storedTheme = localStorage.getItem(themeStorageKey);
			if (storedTheme === 'dark' || storedTheme === 'light') {
				return storedTheme;
			}
		} catch (e) {}
		return defaultTheme;
	}

	function applyTheme(theme) {
		var isDark = (theme === 'dark');
		var root = document.documentElement;
		if (root) {
			if (isDark) {
				root.setAttribute('data-bs-theme', 'dark');
				root.classList.add('theme-dark');
			} else {
				root.removeAttribute('data-bs-theme');
				root.classList.remove('theme-dark');
			}
		}
		if (document.body) {
			if (isDark) {
				document.body.setAttribute('data-bs-theme', 'dark');
				document.body.classList.add('theme-dark');
			} else {
				document.body.removeAttribute('data-bs-theme');
				document.body.classList.remove('theme-dark');
			}
		}
	}

	var currentTheme = getInitialTheme();
	applyTheme(currentTheme);

	function onReady(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn, { once: true });
		} else {
			fn();
		}
	}

	onReady(function () {
		applyTheme(currentTheme);

		if (!window.__tablerThemeToggleBound) {
			window.__tablerThemeToggleBound = true;

			document.addEventListener('click', function (e) {
				var target = e.target;
				var link = (target && typeof target.closest === 'function') ? target.closest('a[href*="theme="]') : null;
				if (!link) return;

				var href = link.getAttribute('href') || '';
				var match = href.match(/[?&]theme=(dark|light)/);
				if (!match) return;

				e.preventDefault();
				var targetTheme = match[1];
				currentTheme = targetTheme;
				try {
					localStorage.setItem(themeStorageKey, targetTheme);
				} catch (err) {}

				applyTheme(targetTheme);
				cleanThemeQueryParam();

				try {
					var tooltip = window.bootstrap && window.bootstrap.Tooltip && window.bootstrap.Tooltip.getInstance(link);
					if (tooltip) {
						tooltip.hide();
					}
				} catch (err) {}
			});
		}
	});

	window.tablerTheme = {
		get: function () {
			return currentTheme;
		},
		set: function (theme) {
			if (theme === 'dark' || theme === 'light') {
				currentTheme = theme;
				try {
					localStorage.setItem(themeStorageKey, theme);
				} catch (e) {}
				applyTheme(theme);
				cleanThemeQueryParam();
			}
		},
		toggle: function () {
			this.set(currentTheme === 'dark' ? 'light' : 'dark');
		}
	};

}));
