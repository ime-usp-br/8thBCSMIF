import './bootstrap';

// Only set Alpine if not already defined (avoid conflicts)
if (typeof window.Alpine === 'undefined') {
    window.Alpine = Alpine;
    // Start Alpine only if Livewire hasn't already
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.Alpine.version) {
            Alpine.start();
        }
    });
}

import.meta.glob([
    '../images/**',  
    '../fonts/**',  
  ]);