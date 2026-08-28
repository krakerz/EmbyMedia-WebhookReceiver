// Replaces a poster/backdrop <img> with the standard placeholder when it fails
// to load (e.g. a dead upstream link), and reports the failure back to the
// server so the entry gets swept up by the next dashboard cleanup pass.
window.handleMediaImageError = function (img, uuid, itemType, iconSizeClass) {
    if (img.dataset.errorHandled) return;
    img.dataset.errorHandled = 'true';

    const icons = { Movie: '🎬', Episode: '📺', Audio: '🎵' };
    const icon = icons[itemType] || '📁';

    const wrapper = document.createElement('div');
    wrapper.className = 'w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-800';

    const inner = document.createElement('div');
    inner.className = 'text-center';

    const iconEl = document.createElement('div');
    iconEl.className = (iconSizeClass || 'text-6xl') + ' mb-2';
    iconEl.textContent = icon;

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
