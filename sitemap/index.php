<?php

namespace x {
    function sitemap($content) {
        \extract($GLOBALS, \EXTR_SKIP);
        return \strtr($content, ['</head>' => '<link href="' . $url->current(false, false) . '/sitemap.xml" rel="sitemap" type="application/xml" title="' . \i('Sitemap') . ' | ' . \w($site->title) . '"></head>']);
    }
    // Insert some HTML `<link>` that maps to the sitemap resource
    if ('sitemap.xml' !== \basename($url->path ?? "")) {
        // Make sure to run the hook before `x\minify`
        \Hook::set('content', __NAMESPACE__ . "\\sitemap", 1.9);
    }
}

namespace x\sitemap {
    function route($path) {
        \extract($GLOBALS, \EXTR_SKIP);
        $path = \trim($path ?? "", '/');
        $route = \trim($state->route ?? "", '/');
        $folder = \dirname(\LOT . \D . 'page' . \D . ($path ?: $route));
        $page = new \Page(\exist([
            $folder . '.archive',
            $folder . '.page'
        ], 1) ?: null);
        $page_exist = $page->exist();
        $content = "";
        // `./foo/sitemap.xml`
        // `./foo/bar/sitemap.xml`
        if (\substr_count($path, '/')) {
            $content .= '<?xml version="1.0" encoding="utf-8"?>';
            $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            if ($page_exist) {
                foreach (\g($folder, 0, true) as $k => $v) {
                    if (!$kk = \exist([
                        $k . '.archive',
                        $k . '.page'
                    ], 1)) {
                        continue;
                    }
                    $content .= '<url>';
                    $content .= '<loc>' . \Hook::fire('link', ['/' . ($r = \strtr(\strtr($k, [\LOT . \D . 'page' . \D => ""]), \D, '/'))]) . '</loc>';
                    $priority = \b(1 - (\substr_count($r, '/') * .1), [.5, 1]); // `0.5` to `1.0`
                    $content .= '<lastmod>' . \date('c', \filemtime($kk)) . '</lastmod>';
                    $content .= '<changefreq>monthly</changefreq>';
                    $content .= '<priority>' . $priority . '</priority>';
                    $content .= '</url>';
                }
            }
            $content .= '</urlset>';
        // `./sitemap.xml`
        } else {
            $page_exist = true;
            $content .= '<?xml version="1.0" encoding="utf-8"?>';
            $content .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach (\g(\LOT . \D . 'page', 0, true) as $k => $v) {
                if (0 === \q(\g($k, 'archive,page'))) {
                    // Ignore empty folder(s)
                    continue;
                }
                if (!$kk = \exist([
                    $k . '.archive',
                    $k . '.page'
                ])) {
                    continue;
                }
                $content .= '<sitemap>';
                $content .= '<loc>' . \Hook::fire('link', ['/' . \strtr(\strtr($k, [\LOT . \D . 'page' . \D => ""]), \D, '/') . '/sitemap.xml']) . '</loc>';
                $content .= '<lastmod>' . \date('c', \filemtime($kk)) . '</lastmod>';
                $content .= '</sitemap>';
            }
            $content .= '</sitemapindex>';
        }
        $content = \Hook::fire('content', [$content]);
        $age = 60 * 60 * 24; // Cache output for a day
        \ob_start();
        \ob_start("\\ob_gzhandler");
        echo $content; // The response body
        \ob_end_flush();
        $size = \ob_get_length();
        \status($page_exist ? 200 : 404, $page_exist ? [
            'cache-control' => 'max-age=' . $age . ', private',
            'content-length' => $size,
            'expires' => \gmdate('D, d M Y H:i:s', $age + $_SERVER['REQUEST_TIME']) . ' GMT',
            'pragma' => 'private'
        ] : [
            'cache-control' => 'max-age=0, must-revalidate, no-cache, no-store',
            'content-length' => $size,
            'expires' => '0',
            'pragma' => 'no-cache'
        ]);
        \type('application/xml');
        echo \ob_get_clean();
        \Hook::fire('let');
        exit;
    }
    if ('sitemap.xml' === \basename($url->path ?? "")) {
        \Hook::set('route', __NAMESPACE__ . "\\route", 10);
    }
}