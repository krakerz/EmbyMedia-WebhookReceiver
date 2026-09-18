// Replaces a poster/backdrop <img> with the standard placeholder when it fails
// to load (e.g. a dead upstream link), and reports the failure back to the
// server so the entry gets swept up by the next dashboard cleanup pass.
// Material Design glyphs, kept in sync with resources/views/components/icon.blade.php
const MEDIA_TYPE_ICON_PATHS = {
    Movie: 'M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z',
    Episode: 'M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z',
    Audio: 'M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z',
    default: 'M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z',
};

window.handleMediaImageError = function (img, uuid, itemType, iconSizeClass) {
    if (img.dataset.errorHandled) return;
    img.dataset.errorHandled = 'true';

    const path = MEDIA_TYPE_ICON_PATHS[itemType] || MEDIA_TYPE_ICON_PATHS.default;

    const wrapper = document.createElement('div');
    wrapper.className = 'w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-800';

    const inner = document.createElement('div');
    inner.className = 'text-center';

    const sizeClasses = { 'text-6xl': 'w-16 h-16', 'text-8xl': 'w-24 h-24' };
    const iconEl = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    iconEl.setAttribute('viewBox', '0 0 24 24');
    iconEl.setAttribute('fill', 'currentColor');
    iconEl.setAttribute('class', (sizeClasses[iconSizeClass] || 'w-16 h-16') + ' mx-auto mb-2 text-gray-400 dark:text-gray-500');
    const iconPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    iconPath.setAttribute('d', path);
    iconEl.appendChild(iconPath);

    const label = document.createElement('p');
    label.className = 'text-sm text-gray-500 dark:text-gray-400 font-medium';
    label.textContent = itemType ? itemType.charAt(0).toUpperCase() + itemType.slice(1) : 'Media';

    inner.appendChild(iconEl);
    inner.appendChild(label);
    wrapper.appendChild(inner);
    img.replaceWith(wrapper);

    const token = document.querySelector('meta[name="csrf-token"]');
    fetch(`/webhook/${uuid}/image-error`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token ? token.content : '',
            'Accept': 'application/json',
        },
    }).catch(() => {});
};
