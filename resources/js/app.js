import "./bootstrap";
import { createIcons, icons } from "lucide";

// Initialize Lucide icons
document.addEventListener("DOMContentLoaded", () => {
    createIcons({
        icons,
        // Optional: specify default icon size
        // attrs: {
        //     width: '24',
        //     height: '24'
        // },
        // Optional: specify CSS class to add to all icons
        // nameAttr: 'data-lucide',
    });
});
