<?php namespace x\sitemap;

// Insert some HTML `<link>` that maps to the sitemap resource
function content($content) {
    \extract(\lot(), \EXTR_SKIP);
    return \strtr($content ?? "", ['</head>' => '<link href="' . $url->current(false, false) . '/sitemap.xml" rel="sitemap" title="' . \i('Sitemap') . ' | ' . \w($state->title) . '" type="application/xml"></head>']);
}

function route($content, $path) {
    if (null !== $content) {
        return $content;
    }
    \extract(\lot(), \EXTR_SKIP);
    $fire = $_GET['fire'] ?? null;
    // Validate function name
    if ($fire && !\preg_match('/^[a-z_$][\w$]*(\.[a-z_$][\w$]*)*$/i', $fire)) {
        \status(403);
        return "";
    }
    $path = \trim(\dirname($path ?? ""), '/');
    $route = \trim($state->route ?? 'index', '/');
    $folder = ($folder_page = \LOT . \D . 'page') . \D . ($path ?: $route);
    $page = new \Page($exist = \exist([
        $folder . '.archive',
        $folder . '.page'
    ], 1) ?: null);
    // `./foo/sitemap.xml`
    // `./foo/bar/sitemap.xml`
    if ("" !== $path) {
        $lot = [
            0 => 'urlset',
            1 => [],
            2 => [
                'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9'
            ]
        ];
        // `./-/sitemap.xml`
        if ('-' === $path) {
            foreach (\g($folder_page, 'archive,page') as $k => $v) {
                $loc = \Hook::fire('link', ['/' . ($route === ($n = \pathinfo($k, \PATHINFO_FILENAME)) ? "" : $n)]);
                $lot[1][$loc] = [
                    0 => 'url',
                    1 => [
                        ['changefreq', 'monthly', []],
                        ['lastmod', \date('c', \filemtime($k)), []],
                        ['loc', $loc, []],
                        ['priority', 1, []]
                    ],
                    2 => []
                ];
            }
        } else if ($exist) {
            foreach (\g($folder, 0, true) as $k => $v) {
                if (!$kk = \exist([
                    $k . '.archive',
                    $k . '.page'
                ], 1)) {
                    continue;
                }
                $loc = \Hook::fire('link', ['/' . ($r = \strtr(\strtr($k, [$folder_page . \D => ""]), \D, '/'))]);
                $priority = \b(1 - (\substr_count($r, '/') * 0.1), [0.5, 1]); // `0.5` to `1.0`
                $lot[1][$loc] = [
                    0 => 'url',
                    1 => [
                        ['changefreq', 'monthly', []],
                        ['lastmod', \date('c', \filemtime($kk)), []],
                        ['loc', $loc, []],
                        ['priority', $priority, []]
                    ],
                    2 => []
                ];
            }
        }
    // `./sitemap.xml`
    } else {
        $exist = true;
        $lot = [
            0 => 'sitemapindex',
            1 => [],
            2 => [
                'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9'
            ]
        ];
        $loc = \Hook::fire('link', ['/-/sitemap.xml']);
        $lot[1][$loc] = [
            0 => 'sitemap',
            1 => [
                ['lastmod', \date('c', \filemtime($folder_page)), []],
                ['loc', $loc, []]
            ],
            2 => []
        ];
        foreach (\g($folder_page, 0, true) as $k => $v) {
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
            $loc = \Hook::fire('link', ['/' . \strtr(\strtr($k, [$folder_page . \D => ""]), \D, '/') . '/sitemap.xml']);
            $lot[1][$loc] = [
                0 => 'sitemap',
                1 => [
                    ['lastmod', \date('c', \filemtime($kk)), []],
                    ['loc', $loc, []]
                ],
                2 => []
            ];
        }
    }
    $age = 60 * 60 * 24; // Cache for a day
    $content = '<?xml version="1.0" encoding="utf-8"?>' . (new \XML(\Hook::fire('y.sitemap', [$lot], $page), true));
    \status($exist ? 200 : 404, $exist ? [
        'cache-control' => 'max-age=' . $age . ', private',
        'expires' => \gmdate('D, d M Y H:i:s', $age + $_SERVER['REQUEST_TIME']) . ' GMT',
        'pragma' => 'private'
    ] : [
        'cache-control' => 'max-age=0, must-revalidate, no-cache, no-store',
        'expires' => '0',
        'pragma' => 'no-cache'
    ]);
    \type('application/' . ($fire ? 'javascript' : 'xml'));
    return $fire ? $fire . '(' . \To::JSON($content) . ');' : $content;
}

if ('sitemap.xml' !== \basename($url->path ?? "")) {
    \Hook::set('content', __NAMESPACE__ . "\\content", -1);
} else {
    \Hook::set('route', __NAMESPACE__ . "\\route", 10);
}