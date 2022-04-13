<?php

namespace x {
    function sitemap($content) {
        \extract($GLOBALS, \EXTR_SKIP);
        return \strtr($content ?? "", ['</head>' => '<link href="' . $url->current(false, false) . '/sitemap.xml" rel="sitemap" title="' . \i('Sitemap') . ' | ' . \w($site->title) . '" type="application/xml"></head>']);
    }
    // Insert some HTML `<link>` that maps to the sitemap resource
    if ('sitemap.xml' !== \basename($url->path ?? "")) {
        // Make sure to run the hook before `x\link\content`
        \Hook::set('content', __NAMESPACE__ . "\\sitemap", -1);
    }
}

namespace x\sitemap {
    function route($content, $p) {
        if (null !== $content) {
            return $content;
        }
        \extract($GLOBALS, \EXTR_SKIP);
        $fire = $_GET['fire'] ?? null;
        $path = \trim(\dirname($p ?? ""), '/');
        $route = \trim($state->route ?? "", '/');
        $folder = \LOT . \D . 'page' . \D . ($path ?: $route);
        $page = new \Page(\exist([
            $folder . '.archive',
            $folder . '.page'
        ], 1) ?: null);
        $page_exist = $page->exist();
        // Validate function name
        if ($fire && !\preg_match('/^[a-z_$][\w$]*(\.[a-z_$][\w$]*)*$/i', $fire)) {
            \status(403);
            return "";
        }
        $content = "";
        // `./foo/sitemap.xml`
        // `./foo/bar/sitemap.xml`
        if ("" !== $path) {
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
        $age = 60 * 60 * 24; // Cache output for a day
        \status($page_exist ? 200 : 404, $page_exist ? [
            'cache-control' => 'max-age=' . $age . ', private',
            'expires' => \gmdate('D, d M Y H:i:s', $age + $_SERVER['REQUEST_TIME']) . ' GMT',
            'pragma' => 'private'
        ] : [
            'cache-control' => 'max-age=0, must-revalidate, no-cache, no-store',
            'expires' => '0',
            'pragma' => 'no-cache'
        ]);
        \type('application/' . ($fire ? 'javascript' : 'xml'));
        return $fire ? $fire . '(' . \json_encode($content, \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT | \JSON_HEX_TAG | \JSON_UNESCAPED_UNICODE) . ');' : $content;
    }
    if ('sitemap.xml' === \basename($url->path ?? "")) {
        \Hook::set('route', __NAMESPACE__ . "\\route", 10);
    }
}