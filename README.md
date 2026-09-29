# Studentsleads — University Leads

Plugin de WordPress para captar estudiantes interesados en **paquetes de estudio en el extranjero**. Desarrollado para la alianza [Campus Life Panamá](https://campuslifepa.com) × [TheUForYou](https://theuforyou.com).

**Versión actual: 1.3.0**

## Qué incluye

### 🧭 Formulario conversacional — `[university_leads_form]`
- Wizard de una pregunta por pantalla (estilo Typeform) con barra de progreso.
- Saluda al estudiante por su nombre en las preguntas siguientes.
- Opciones tipo tarjeta con auto-avance; la pregunta de países es de selección múltiple.
- Anti-spam (honeypot + nonce), validación en servidor y degradación sin JavaScript.

### 🎯 Algoritmo de recomendación
Cada respuesta (destinos, inglés, fechas, nivel) suma puntos a 3 paquetes; el de mayor puntaje se recomienda al instante en pantalla y por correo. Matriz ajustable con el filtro `ul_scoring_matrix`.

### 📋 Dashboard kanban (wp-admin)
Menú **University Leads**: tablero *Nuevos → Potenciales → Contactados → Cerrados* con drag & drop, detalle completo de respuestas y eliminación.

### 🔐 Portal para el equipo — `[university_leads_dashboard]`
Página del sitio protegida por contraseña (sin usuario de WordPress): lista de leads con filtros por estado, botones de contacto (WhatsApp / correo / llamada), cambio de estado y **exportación a CSV/Excel**. Sesión firmada de 12 h y límite de intentos por IP.

### ✉️ Correos HTML
- Aviso al equipo: datos completos, paquete recomendado, link a WhatsApp y botón al dashboard.
- Confirmación al estudiante con su paquete y próximos pasos (`{nombre}`, `{paquete}` personalizables).

### ⚙️ Ajustes
Logo (biblioteca de medios), títulos y textos del formulario, color de acento, correos, nombres/descripciones de paquetes y contraseña del portal.

## Instalación

1. Descarga esta carpeta y comprime `university-leads/` en un `.zip` (o usa el zip de Releases).
2. WordPress → Plugins → Añadir nuevo → Subir plugin.
3. Configura en **University Leads → Ajustes**.
4. Inserta `[university_leads_form]` en la página de captación y `[university_leads_dashboard]` en la página del equipo.

## Landing de la alianza

`landing/alianza-theuforyou.html` — página completa para un bloque "HTML personalizado" de WordPress: hero con ambos logos, beneficios, paquetes, cómo funciona y el formulario integrado. Los links de imágenes a reemplazar están marcados con `PON-AQUI`.

## Estructura

```
university-leads/
├── university-leads.php      # Bootstrap del plugin
├── includes/
│   ├── fields.php            # Preguntas y opciones del cuestionario
│   ├── scoring.php           # Algoritmo de recomendación de paquete
│   ├── form.php              # Shortcode del formulario + procesamiento
│   ├── emails.php            # Correos HTML (equipo y estudiante)
│   ├── dashboard.php         # Kanban en wp-admin
│   ├── portal.php            # Portal frontal con contraseña + export CSV
│   ├── settings.php          # Página de ajustes
│   └── cpt.php               # Tipo de contenido ul_lead + estados
├── assets/                   # CSS/JS del formulario, kanban, portal y ajustes
├── readme.txt                # Readme estándar de WordPress (changelog)
└── uninstall.php             # Limpieza al desinstalar
```
