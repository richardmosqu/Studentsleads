=== University Leads ===
Contributors: theuforyou
Tags: leads, students, study abroad, form, crm
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Captación de estudiantes interesados en estudiar en el extranjero, con recomendación automática de paquete y dashboard kanban. Para Campus Life / TheUForYou.

== Description ==

Inserta el shortcode `[university_leads_form]` en cualquier página para mostrar el formulario de captación.

**Formulario inteligente**

* Datos de contacto (nombre, correo, teléfono/WhatsApp, institución actual).
* Cuestionario: nivel de estudios, país de destino, nivel de inglés y fecha de inicio.
* Algoritmo de puntaje: cada respuesta suma puntos a los paquetes y el de mayor puntaje se recomienda automáticamente. El estudiante ve su paquete ideal al enviar el formulario.
* Anti-spam con honeypot y nonce; validación completa en el servidor.

**Dashboard kanban (tipo Asana)**

En el menú **University Leads** del panel: tablero con columnas *Nuevos → Potenciales → Contactados → Cerrados*. Arrastra las tarjetas entre columnas (o usa el selector en móvil/táctil) para actualizar el estado. Cada tarjeta muestra contacto, paquete recomendado y todas las respuestas.

**Notificaciones**

* Correo al equipo con todos los datos y el paquete recomendado (destinatario configurable).
* Correo de confirmación al estudiante, personalizable con los comodines `{nombre}` y `{paquete}`.

**Portal de leads para el equipo (frontend)**

Crea una página con el shortcode `[university_leads_dashboard]` y define una contraseña en Ajustes. El equipo entra con esa contraseña (sin usuario de WordPress, sesión de 12 horas) y puede: ver todos los leads con filtros por estado, contactarlos con un clic (WhatsApp, correo, llamada), cambiar su estado y **exportar todo a CSV/Excel**. Protegido con límite de intentos de contraseña.

**Ajustes**

En *University Leads → Ajustes*: logo del formulario (biblioteca de medios), título, subtítulo, texto del botón, mensaje de éxito, color de acento, correo del equipo, correo del estudiante y nombres/descripciones de los 3 paquetes.

**Para desarrolladores**

* Filtro `ul_scoring_matrix` para ajustar los puntos del algoritmo.
* Filtro `ul_fields` para modificar preguntas y opciones.
* Filtro `ul_team_recipient` para el destinatario del aviso interno.
* Filtro `ul_dashboard_capability` para cambiar la capacidad requerida (por defecto `manage_options`).

== Installation ==

1. Sube la carpeta `university-leads` a `/wp-content/plugins/` (o instala el .zip desde Plugins → Añadir nuevo).
2. Activa el plugin.
3. Configura logo, textos y correos en *University Leads → Ajustes*.
4. Inserta `[university_leads_form]` en la página deseada.

== Changelog ==

= 1.4.0 =
* Se eliminan del cuestionario la pregunta de beca/financiamiento y la de presupuesto anual; el paquete "Becas y Financiamiento" desaparece de la recomendación. El nivel de inglés y el destino pasan a ser las señales principales del algoritmo.

= 1.3.0 =
* Nuevo portal de leads para el equipo: shortcode `[university_leads_dashboard]` protegido por contraseña (definida en Ajustes). Lista de leads con pestañas por estado, botones de contacto (WhatsApp/correo/llamada), cambio de estado y exportación a CSV/Excel. Sesión de 12 horas con cookie firmada y límite de 5 intentos de contraseña por IP.

= 1.2.1 =
* Los títulos del formulario siempre quedan centrados aunque el tema sobreescriba márgenes.

= 1.2.0 =
* La pregunta de países ahora es de selección múltiple: el estudiante puede marcar varios destinos y el algoritmo suma los puntos de cada uno. Se muestran separados por comas en el dashboard y en los correos.
* Mejor espaciado y márgenes en los textos: título, subtítulo, preguntas y especialmente la pantalla de éxito (más aire, líneas más cortas y legibles).

= 1.1.1 =
* Los botones del formulario ya no heredan los colores ni el hover del tema del sitio: siempre usan el color de acento configurado en Ajustes.
* Optimización para teléfono: botón principal a lo ancho, áreas táctiles de 48px, tipografía ajustada y campos con fuente de 16px para evitar el zoom automático de iOS.

= 1.1.0 =
* Formulario rediseñado como wizard conversacional: una pregunta por pantalla, barra de progreso, opciones tipo tarjeta con auto-avance, saludo personalizado con el nombre y avance con Enter. Degrada a formulario clásico sin JavaScript.
* Correos en HTML con diseño de marca (logo, color de acento): aviso interno con tabla de datos, tarjeta del paquete recomendado, link directo a WhatsApp y botón al dashboard; confirmación al estudiante con su paquete y próximos pasos.

= 1.0.0 =
* Versión inicial: formulario con algoritmo de recomendación, dashboard kanban, notificaciones por correo y ajustes personalizables.
