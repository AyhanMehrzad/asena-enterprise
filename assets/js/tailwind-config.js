try {
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                "colors": {
                    /* --- ASENA Premium Corporate Navy Blue & Royal Palette --- */
                    "primary": "#001a48",                  /* Dark Navy Blue */
                    "primary-container": "#002d72",        /* Deep Royal Navy Container */
                    "primary-light": "#1e40af",            /* Vibrant Royal Blue */
                    "primary-fixed": "#dae2ff",            /* Soft Ice Blue */
                    "primary-fixed-dim": "#b1c5ff",
                    "on-primary": "#ffffff",
                    "on-primary-container": "#7a97e2",
                    "on-primary-fixed": "#001946",
                    "on-primary-fixed-variant": "#224489",
                    "inverse-primary": "#b1c5ff",
                    
                    /* Signature ASENA Sunset Orange Accents */
                    "secondary": "#954a00",
                    "secondary-container": "#fd8100",      /* Vibrant Orange CTA */
                    "secondary-fixed": "#ffdcc6",          /* Warm Orange Tint */
                    "secondary-fixed-dim": "#ffb785",
                    "on-secondary": "#ffffff",
                    "on-secondary-container": "#5d2c00",
                    "on-secondary-fixed": "#301400",
                    "on-secondary-fixed-variant": "#723700",
                    
                    /* Deep Ocean Slate / Medical Blue Accent */
                    "tertiary": "#001f31",
                    "tertiary-container": "#133449",
                    "tertiary-fixed": "#cae6ff",
                    "tertiary-fixed-dim": "#abcae5",
                    "on-tertiary": "#ffffff",
                    "on-tertiary-container": "#7f9db6",
                    "on-tertiary-fixed": "#001e2f",
                    "on-tertiary-fixed-variant": "#2c4a60",
                    
                    /* Clean Premium Canvases */
                    "surface-tint": "#002d72",
                    "surface": "#f9f9f9",
                    "surface-bright": "#f9f9f9",
                    "surface-dim": "#dadada",
                    "surface-alt": "#F8F9FA",
                    "surface-variant": "#e2e2e2",
                    "surface-container": "#eeeeee",
                    "surface-container-low": "#f3f3f4",
                    "surface-container-high": "#e8e8e8",
                    "surface-container-highest": "#e2e2e2",
                    "surface-container-lowest": "#ffffff",
                    "background": "#f9f9f9",
                    
                    "on-background": "#1a1c1c",
                    "on-surface": "#1a1c1c",
                    "on-surface-variant": "#444651",
                    "inverse-surface": "#2f3131",
                    "inverse-on-surface": "#f0f1f1",
                    
                    "outline": "#747782",
                    "outline-variant": "#c4c6d2",
                    
                    "status-active": "#2E7D32",
                    "status-warning": "#FFC60A",
                    "status-paused": "#757575",
                    "error": "#ba1a1a",
                    "error-container": "#ffdad6",
                    "on-error": "#ffffff",
                    "on-error-container": "#93000a"
                },
                "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
                },
                "spacing": {
                    "margin-mobile": "16px",
                    "base": "4px",
                    "margin-desktop": "24px",
                    "gutter": "16px",
                    "container-max": "1280px"
                },
                "fontFamily": {
                    "headline-lg-mobile": ["Geist"],
                    "headline-lg": ["Geist"],
                    "label-sm": ["Geist"],
                    "headline-md": ["Geist"],
                    "display-lg": ["Geist"],
                    "title-lg": ["Geist"],
                    "label-lg": ["Geist"],
                    "body-lg": ["Geist"],
                    "body-md": ["Geist"]
                },
                "fontSize": {
                    "headline-lg-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                    "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "600"}],
                    "label-sm": ["12px", {"lineHeight": "16px", "fontWeight": "500"}],
                    "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                    "display-lg": ["48px", {"lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                    "title-lg": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                    "label-lg": ["14px", {"lineHeight": "20px", "letterSpacing": "0.01em", "fontWeight": "600"}],
                    "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                    "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}]
                }
            },
        },
    }
} catch (_e) {}
