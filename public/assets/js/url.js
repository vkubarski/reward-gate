window.rewardGateUrl = function (path = '/') {
    const basePath = document.documentElement.dataset.basePath || '';
    const normalizedPath = `/${String(path).replace(/^\/+/, '')}`;

    return normalizedPath === '/'
        ? `${basePath}/`
        : `${basePath}${normalizedPath}`;
};
