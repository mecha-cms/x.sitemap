<?php namespace x;

function sitemap($content) {
    extract($GLOBALS, \EXTR_SKIP);
    return \strtr($content, ['</head>' => '<link href="' . $url->clean . '/sitemap.xml" rel="sitemap" type="application/xml" title="' . \i('Sitemap') . ' | ' . \w($site->title) . '"></head>']);
}

// Insert some HTML `<link>` that maps to the sitemap resource
if ('sitemap.xml' !== \basename($url->path)) {
    // Make sure to run the hook before `x\minify`
    \Hook::set('content', __NAMESPACE__ . "\\sitemap", 1.9);
}