import './bootstrap';
import Alpine from 'alpinejs';

// Start Alpine.js
window.Alpine = Alpine;
Alpine.start();

import.meta.glob([
    '../images/**',  
    '../fonts/**',  
  ]);